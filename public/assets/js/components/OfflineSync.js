/**
 * TPT Free ERP - Offline Data Synchronization
 * Handles offline data storage, queuing, and synchronization
 */

class OfflineSync extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            enableOfflineStorage: true,
            syncInterval: 30000, // 30 seconds
            maxRetries: 3,
            retryDelay: 5000, // 5 seconds
            conflictResolution: 'server-wins', // server-wins, client-wins, manual
            onSyncStart: null,
            onSyncComplete: null,
            onSyncError: null,
            onConflict: null,
            ...props
        };

        this.state = {
            isOnline: navigator.onLine,
            isSyncing: false,
            lastSyncTime: null,
            pendingChanges: 0,
            syncErrors: [],
            storageQuota: null,
            storageUsage: null
        };

        // Bind methods
        this.handleOnlineStatusChange = this.handleOnlineStatusChange.bind(this);
        this.startSync = this.startSync.bind(this);
        this.stopSync = this.stopSync.bind(this);
        this.syncData = this.syncData.bind(this);
        this.handleSyncMessage = this.handleSyncMessage.bind(this);
        this.checkStorageQuota = this.checkStorageQuota.bind(this);
    }

    componentDidMount() {
        // Listen for online/offline events
        window.addEventListener('online', this.handleOnlineStatusChange);
        window.addEventListener('offline', this.handleOnlineStatusChange);

        // Listen for service worker messages
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', this.handleSyncMessage);
        }

        // Check storage quota
        this.checkStorageQuota();

        // Load pending changes count
        this.loadPendingChangesCount();

        // Start sync if online
        if (this.state.isOnline) {
            this.startSync();
        }
    }

    componentWillUnmount() {
        window.removeEventListener('online', this.handleOnlineStatusChange);
        window.removeEventListener('offline', this.handleOnlineStatusChange);

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.removeEventListener('message', this.handleSyncMessage);
        }

        this.stopSync();
    }

    handleOnlineStatusChange() {
        const isOnline = navigator.onLine;
        this.setState({ isOnline });

        if (isOnline) {
            // Start sync when coming online
            this.startSync();
            App.showNotification({
                type: 'success',
                message: 'Connection restored. Syncing data...'
            });
        } else {
            // Stop sync when going offline
            this.stopSync();
            App.showNotification({
                type: 'warning',
                message: 'You are offline. Changes will be synced when connection is restored.'
            });
        }
    }

    handleSyncMessage(event) {
        const { type, data } = event.data;

        switch (type) {
            case 'SYNC_COMPLETE':
                this.handleSyncComplete(data);
                break;
            case 'SYNC_ERROR':
                this.handleSyncError(data);
                break;
            case 'CONFLICT_DETECTED':
                this.handleConflict(data);
                break;
            default:
                console.log('[OfflineSync] Unknown message type:', type);
        }
    }

    startSync() {
        if (!this.state.isOnline || this.state.isSyncing) return;

        this.setState({ isSyncing: true });

        if (this.props.onSyncStart) {
            this.props.onSyncStart();
        }

        // Start periodic sync
        this.syncTimer = setInterval(() => {
            this.syncData();
        }, this.props.syncInterval);

        // Initial sync
        this.syncData();
    }

    stopSync() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
            this.syncTimer = null;
        }

        this.setState({ isSyncing: false });
    }

    async syncData() {
        if (!this.state.isOnline || this.state.isSyncing) return;

        try {
            this.setState({ isSyncing: true });

            // Get pending changes
            const pendingChanges = await this.getPendingChanges();

            if (pendingChanges.length === 0) {
                this.setState({
                    isSyncing: false,
                    lastSyncTime: new Date()
                });
                return;
            }

            // Send changes to server
            const syncResult = await this.sendChangesToServer(pendingChanges);

            // Handle conflicts
            if (syncResult.conflicts && syncResult.conflicts.length > 0) {
                await this.handleConflicts(syncResult.conflicts);
            }

            // Update local data with server response
            await this.updateLocalData(syncResult.updates);

            // Clear synced changes
            await this.clearSyncedChanges(syncResult.syncedIds);

            // Update state
            this.setState({
                isSyncing: false,
                lastSyncTime: new Date(),
                pendingChanges: await this.getPendingChangesCount()
            });

            if (this.props.onSyncComplete) {
                this.props.onSyncComplete(syncResult);
            }

        } catch (error) {
            console.error('[OfflineSync] Sync failed:', error);

            this.setState({
                isSyncing: false,
                syncErrors: [...this.state.syncErrors, {
                    timestamp: new Date(),
                    error: error.message
                }]
            });

            if (this.props.onSyncError) {
                this.props.onSyncError(error);
            }

            // Retry with exponential backoff
            if (this.retryCount < this.props.maxRetries) {
                setTimeout(() => {
                    this.retryCount++;
                    this.syncData();
                }, this.props.retryDelay * Math.pow(2, this.retryCount));
            }
        }
    }

    async getPendingChanges() {
        // Get pending changes from IndexedDB
        const db = await this.openIndexedDB();
        const transaction = db.transaction(['pendingChanges'], 'readonly');
        const store = transaction.objectStore('pendingChanges');

        return new Promise((resolve, reject) => {
            const request = store.getAll();

            request.onsuccess = () => {
                resolve(request.result);
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    async sendChangesToServer(changes) {
        // Group changes by endpoint
        const changesByEndpoint = {};

        changes.forEach(change => {
            if (!changesByEndpoint[change.endpoint]) {
                changesByEndpoint[change.endpoint] = [];
            }
            changesByEndpoint[change.endpoint].push(change);
        });

        // Send batched requests
        const results = await Promise.allSettled(
            Object.entries(changesByEndpoint).map(async ([endpoint, endpointChanges]) => {
                try {
                    const response = await API.request('POST', `/sync/${endpoint}`, {
                        changes: endpointChanges
                    });

                    return {
                        endpoint,
                        success: true,
                        data: response,
                        changes: endpointChanges
                    };
                } catch (error) {
                    return {
                        endpoint,
                        success: false,
                        error: error.message,
                        changes: endpointChanges
                    };
                }
            })
        );

        // Process results
        const syncResult = {
            syncedIds: [],
            conflicts: [],
            updates: [],
            errors: []
        };

        results.forEach(result => {
            if (result.status === 'fulfilled') {
                const { endpoint, success, data, changes, error } = result.value;

                if (success) {
                    syncResult.syncedIds.push(...changes.map(c => c.id));
                    if (data.updates) {
                        syncResult.updates.push(...data.updates);
                    }
                    if (data.conflicts) {
                        syncResult.conflicts.push(...data.conflicts);
                    }
                } else {
                    syncResult.errors.push({
                        endpoint,
                        error,
                        changes
                    });
                }
            } else {
                syncResult.errors.push({
                    error: result.reason.message,
                    changes: []
                });
            }
        });

        return syncResult;
    }

    async handleConflicts(conflicts) {
        for (const conflict of conflicts) {
            await this.resolveConflict(conflict);
        }
    }

    async resolveConflict(conflict) {
        const resolution = this.props.conflictResolution;

        switch (resolution) {
            case 'server-wins':
                // Accept server version
                await this.updateLocalData([conflict.serverVersion]);
                break;

            case 'client-wins':
                // Keep client version and re-sync
                await this.resendChange(conflict.clientChange);
                break;

            case 'manual':
                // Notify user of conflict
                if (this.props.onConflict) {
                    this.props.onConflict(conflict);
                }
                break;

            default:
                console.warn('[OfflineSync] Unknown conflict resolution strategy:', resolution);
        }
    }

    async updateLocalData(updates) {
        const db = await this.openIndexedDB();

        for (const update of updates) {
            const transaction = db.transaction([update.store], 'readwrite');
            const store = transaction.objectStore(update.store);

            await new Promise((resolve, reject) => {
                const request = store.put(update.data);

                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        }
    }

    async clearSyncedChanges(syncedIds) {
        const db = await this.openIndexedDB();
        const transaction = db.transaction(['pendingChanges'], 'readwrite');
        const store = transaction.objectStore('pendingChanges');

        for (const id of syncedIds) {
            await new Promise((resolve, reject) => {
                const request = store.delete(id);

                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        }
    }

    async resendChange(change) {
        const db = await this.openIndexedDB();
        const transaction = db.transaction(['pendingChanges'], 'readwrite');
        const store = transaction.objectStore('pendingChanges');

        // Update timestamp to resend
        change.timestamp = new Date();
        change.retryCount = (change.retryCount || 0) + 1;

        await new Promise((resolve, reject) => {
            const request = store.put(change);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async openIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('tpt-erp-offline', 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create stores if they don't exist
                if (!db.objectStoreNames.contains('pendingChanges')) {
                    const pendingStore = db.createObjectStore('pendingChanges', { keyPath: 'id' });
                    pendingStore.createIndex('endpoint', 'endpoint', { unique: false });
                    pendingStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                if (!db.objectStoreNames.contains('localData')) {
                    db.createObjectStore('localData', { keyPath: 'id' });
                }

                if (!db.objectStoreNames.contains('syncMetadata')) {
                    db.createObjectStore('syncMetadata', { keyPath: 'key' });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async checkStorageQuota() {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            try {
                const estimate = await navigator.storage.estimate();
                this.setState({
                    storageQuota: estimate.quota,
                    storageUsage: estimate.usage
                });
            } catch (error) {
                console.warn('[OfflineSync] Could not check storage quota:', error);
            }
        }
    }

    async loadPendingChangesCount() {
        try {
            const pendingChanges = await this.getPendingChanges();
            this.setState({ pendingChanges: pendingChanges.length });
        } catch (error) {
            console.warn('[OfflineSync] Could not load pending changes count:', error);
        }
    }

    async getPendingChangesCount() {
        const pendingChanges = await this.getPendingChanges();
        return pendingChanges.length;
    }

    handleSyncComplete(data) {
        console.log('[OfflineSync] Sync completed:', data);
        this.setState({
            lastSyncTime: new Date(),
            pendingChanges: 0
        });
    }

    handleSyncError(error) {
        console.error('[OfflineSync] Sync error:', error);
        this.setState({
            syncErrors: [...this.state.syncErrors, {
                timestamp: new Date(),
                error: error.message
            }]
        });
    }

    handleConflict(conflict) {
        console.warn('[OfflineSync] Conflict detected:', conflict);

        if (this.props.onConflict) {
            this.props.onConflict(conflict);
        }
    }

    // Public API methods
    async queueChange(change) {
        if (!this.props.enableOfflineStorage) return;

        const db = await this.openIndexedDB();
        const transaction = db.transaction(['pendingChanges'], 'readwrite');
        const store = transaction.objectStore('pendingChanges');

        const changeData = {
            id: `${change.endpoint}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            endpoint: change.endpoint,
            method: change.method || 'POST',
            data: change.data,
            timestamp: new Date(),
            retryCount: 0
        };

        await new Promise((resolve, reject) => {
            const request = store.add(changeData);

            request.onsuccess = () => {
                this.setState({ pendingChanges: this.state.pendingChanges + 1 });
                resolve();
            };

            request.onerror = () => reject(request.error);
        });
    }

    async getLocalData(storeName, key) {
        const db = await this.openIndexedDB();
        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async setLocalData(storeName, data) {
        const db = await this.openIndexedDB();
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);

        await new Promise((resolve, reject) => {
            const request = store.put(data);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async clearLocalData(storeName) {
        const db = await this.openIndexedDB();
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);

        await new Promise((resolve, reject) => {
            const request = store.clear();

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    getSyncStatus() {
        return {
            isOnline: this.state.isOnline,
            isSyncing: this.state.isSyncing,
            lastSyncTime: this.state.lastSyncTime,
            pendingChanges: this.state.pendingChanges,
            syncErrors: this.state.syncErrors,
            storageQuota: this.state.storageQuota,
            storageUsage: this.state.storageUsage
        };
    }

    forceSync() {
        if (this.state.isOnline) {
            this.syncData();
        }
    }

    render() {
        // This component doesn't render anything visible
        // It manages offline sync in the background
        return null;
    }
}

// Offline Data Manager
class OfflineDataManager {
    constructor() {
        this.db = null;
        this.isInitialized = false;
    }

    async init() {
        if (this.isInitialized) return;

        this.db = await this.openIndexedDB();
        this.isInitialized = true;
    }

    async openIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('tpt-erp-data', 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores for different data types
                const stores = [
                    'users', 'projects', 'tasks', 'customers', 'orders',
                    'products', 'inventory', 'invoices', 'settings'
                ];

                stores.forEach(storeName => {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, { keyPath: 'id' });
                        store.createIndex('updated_at', 'updated_at', { unique: false });
                        store.createIndex('sync_status', 'sync_status', { unique: false });
                    }
                });
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async get(storeName, id) {
        await this.init();
        const transaction = this.db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.get(id);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAll(storeName, options = {}) {
        await this.init();
        const transaction = this.db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            let request;

            if (options.index) {
                const index = store.index(options.index);
                request = index.getAll(options.value);
            } else {
                request = store.getAll();
            }

            request.onsuccess = () => {
                let results = request.result;

                // Apply filters
                if (options.filter) {
                    results = results.filter(options.filter);
                }

                // Apply sorting
                if (options.sortBy) {
                    results.sort((a, b) => {
                        const aVal = a[options.sortBy];
                        const bVal = b[options.sortBy];
                        return options.sortOrder === 'desc' ? bVal - aVal : aVal - bVal;
                    });
                }

                // Apply pagination
                if (options.limit) {
                    const start = options.offset || 0;
                    results = results.slice(start, start + options.limit);
                }

                resolve(results);
            };

            request.onerror = () => reject(request.error);
        });
    }

    async set(storeName, data) {
        await this.init();
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);

        // Add sync metadata
        data.updated_at = new Date();
        data.sync_status = data.sync_status || 'pending';

        return new Promise((resolve, reject) => {
            const request = store.put(data);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async delete(storeName, id) {
        await this.init();
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.delete(id);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async clear(storeName) {
        await this.init();
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.clear();

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async getSyncStatus(storeName) {
        await this.init();
        const allData = await this.getAll(storeName);
        const pending = allData.filter(item => item.sync_status === 'pending');
        const synced = allData.filter(item => item.sync_status === 'synced');
        const failed = allData.filter(item => item.sync_status === 'failed');

        return {
            total: allData.length,
            pending: pending.length,
            synced: synced.length,
            failed: failed.length,
            lastSync: Math.max(...allData.map(item => new Date(item.updated_at)))
        };
    }
}

// Global instances
const OfflineSyncManager = new OfflineSync();
const OfflineData = new OfflineDataManager();

// Initialize offline data manager
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => OfflineData.init());
} else {
    OfflineData.init();
}

// Register components
ComponentRegistry.register('OfflineSync', OfflineSync);

// Make globally available
window.OfflineSync = OfflineSync;
window.OfflineSyncManager = OfflineSyncManager;
window.OfflineData = OfflineData;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        OfflineSync,
        OfflineDataManager
    };
}
