/**
 * TPT Free ERP - Storage Manager Utility
 * Comprehensive localStorage/sessionStorage wrapper with data persistence, cache management, and validation
 */

class StorageManager {
    constructor(options = {}) {
        this.options = {
            prefix: 'tpt_erp_',
            defaultStorage: 'localStorage', // 'localStorage' or 'sessionStorage'
            compress: false,
            encrypt: false,
            ttl: null, // Time to live in milliseconds
            maxSize: 5 * 1024 * 1024, // 5MB default limit
            ...options
        };

        this.storage = this.getStorage(this.options.defaultStorage);
        this.cache = new Map();
        this.listeners = new Map();

        this.init();
    }

    init() {
        // Clean up expired items on initialization
        this.cleanup();

        // Set up storage event listener for cross-tab synchronization
        if (typeof window !== 'undefined') {
            window.addEventListener('storage', this.handleStorageEvent.bind(this));
        }
    }

    // ============================================================================
    // STORAGE TYPE MANAGEMENT
    // ============================================================================

    getStorage(type) {
        if (typeof window === 'undefined') {
            return this.createMemoryStorage();
        }

        try {
            switch (type) {
                case 'sessionStorage':
                    return window.sessionStorage;
                case 'localStorage':
                default:
                    return window.localStorage;
            }
        } catch (error) {
            console.warn('Storage not available, falling back to memory storage:', error);
            return this.createMemoryStorage();
        }
    }

    createMemoryStorage() {
        const storage = {};
        return {
            getItem: (key) => storage[key] || null,
            setItem: (key, value) => { storage[key] = value; },
            removeItem: (key) => { delete storage[key]; },
            clear: () => { Object.keys(storage).forEach(key => delete storage[key]); },
            get length() { return Object.keys(storage).length; },
            key: (index) => Object.keys(storage)[index] || null
        };
    }

    // ============================================================================
    // BASIC STORAGE OPERATIONS
    // ============================================================================

    set(key, value, options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ttl: this.options.ttl,
            compress: this.options.compress,
            encrypt: this.options.encrypt,
            ...options
        };

        const prefixedKey = this.getPrefixedKey(key);
        const storage = this.getStorage(config.storage);

        try {
            const item = {
                value: value,
                timestamp: Date.now(),
                ttl: config.ttl,
                version: 1
            };

            let serializedValue = JSON.stringify(item);

            // Compress if enabled
            if (config.compress && typeof CompressionStream !== 'undefined') {
                serializedValue = this.compress(serializedValue);
            }

            // Encrypt if enabled
            if (config.encrypt) {
                serializedValue = this.encrypt(serializedValue);
            }

            // Check size limit
            if (serializedValue.length > this.options.maxSize) {
                throw new Error(`Data size (${serializedValue.length} bytes) exceeds maximum allowed size (${this.options.maxSize} bytes)`);
            }

            storage.setItem(prefixedKey, serializedValue);

            // Update cache
            this.cache.set(prefixedKey, item);

            // Notify listeners
            this.notifyListeners(key, 'set', value);

            return true;
        } catch (error) {
            console.error('Storage set error:', error);
            return false;
        }
    }

    get(key, defaultValue = null, options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const prefixedKey = this.getPrefixedKey(key);
        const storage = this.getStorage(config.storage);

        try {
            // Check cache first
            if (this.cache.has(prefixedKey)) {
                const cachedItem = this.cache.get(prefixedKey);
                if (!this.isExpired(cachedItem)) {
                    return cachedItem.value;
                } else {
                    // Remove expired item from cache
                    this.cache.delete(prefixedKey);
                }
            }

            const serializedValue = storage.getItem(prefixedKey);
            if (!serializedValue) {
                return defaultValue;
            }

            let item;
            try {
                // Decrypt if needed
                let processedValue = serializedValue;
                if (this.options.encrypt) {
                    processedValue = this.decrypt(processedValue);
                }

                // Decompress if needed
                if (this.options.compress && typeof DecompressionStream !== 'undefined') {
                    processedValue = this.decompress(processedValue);
                }

                item = JSON.parse(processedValue);
            } catch (parseError) {
                console.warn('Failed to parse stored value, removing:', parseError);
                this.remove(key);
                return defaultValue;
            }

            // Check if expired
            if (this.isExpired(item)) {
                this.remove(key);
                return defaultValue;
            }

            // Update cache
            this.cache.set(prefixedKey, item);

            return item.value;
        } catch (error) {
            console.error('Storage get error:', error);
            return defaultValue;
        }
    }

    remove(key, options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const prefixedKey = this.getPrefixedKey(key);
        const storage = this.getStorage(config.storage);

        try {
            storage.removeItem(prefixedKey);
            this.cache.delete(prefixedKey);

            // Notify listeners
            this.notifyListeners(key, 'remove', null);

            return true;
        } catch (error) {
            console.error('Storage remove error:', error);
            return false;
        }
    }

    clear(options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const storage = this.getStorage(config.storage);

        try {
            // Clear only prefixed keys
            const keysToRemove = [];
            for (let i = 0; i < storage.length; i++) {
                const key = storage.key(i);
                if (key && key.startsWith(this.options.prefix)) {
                    keysToRemove.push(key);
                }
            }

            keysToRemove.forEach(key => {
                storage.removeItem(key);
                this.cache.delete(key);
            });

            // Notify listeners
            this.listeners.forEach((listeners, key) => {
                listeners.forEach(callback => {
                    try {
                        callback(key, 'clear', null);
                    } catch (error) {
                        console.error('Listener callback error:', error);
                    }
                });
            });

            return true;
        } catch (error) {
            console.error('Storage clear error:', error);
            return false;
        }
    }

    has(key, options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const prefixedKey = this.getPrefixedKey(key);
        const storage = this.getStorage(config.storage);

        try {
            const serializedValue = storage.getItem(prefixedKey);
            if (!serializedValue) return false;

            const item = JSON.parse(serializedValue);
            return !this.isExpired(item);
        } catch (error) {
            return false;
        }
    }

    keys(options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const storage = this.getStorage(config.storage);
        const keys = [];

        try {
            for (let i = 0; i < storage.length; i++) {
                const key = storage.key(i);
                if (key && key.startsWith(this.options.prefix)) {
                    const originalKey = key.substring(this.options.prefix.length);
                    keys.push(originalKey);
                }
            }
        } catch (error) {
            console.error('Storage keys error:', error);
        }

        return keys;
    }

    size(options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const storage = this.getStorage(config.storage);
        let totalSize = 0;

        try {
            for (let i = 0; i < storage.length; i++) {
                const key = storage.key(i);
                if (key && key.startsWith(this.options.prefix)) {
                    const value = storage.getItem(key);
                    if (value) {
                        totalSize += key.length + value.length;
                    }
                }
            }
        } catch (error) {
            console.error('Storage size calculation error:', error);
        }

        return totalSize;
    }

    // ============================================================================
    // ADVANCED FEATURES
    // ============================================================================

    setMultiple(items, options = {}) {
        const results = {};
        let success = true;

        for (const [key, value] of Object.entries(items)) {
            const result = this.set(key, value, options);
            results[key] = result;
            if (!result) success = false;
        }

        return { success, results };
    }

    getMultiple(keys, options = {}) {
        const results = {};

        for (const key of keys) {
            results[key] = this.get(key, null, options);
        }

        return results;
    }

    removeMultiple(keys, options = {}) {
        const results = {};
        let success = true;

        for (const key of keys) {
            const result = this.remove(key, options);
            results[key] = result;
            if (!result) success = false;
        }

        return { success, results };
    }

    // ============================================================================
    // CACHE MANAGEMENT
    // ============================================================================

    clearCache() {
        this.cache.clear();
    }

    getCacheSize() {
        return this.cache.size;
    }

    preloadCache(keys) {
        for (const key of keys) {
            this.get(key); // This will populate the cache
        }
    }

    // ============================================================================
    // EXPIRATION MANAGEMENT
    // ============================================================================

    isExpired(item) {
        if (!item.ttl) return false;

        const now = Date.now();
        const itemTime = item.timestamp || 0;

        return (now - itemTime) > item.ttl;
    }

    setTTL(key, ttl, options = {}) {
        const value = this.get(key);
        if (value !== null) {
            return this.set(key, value, { ...options, ttl });
        }
        return false;
    }

    getTTL(key, options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const prefixedKey = this.getPrefixedKey(key);
        const storage = this.getStorage(config.storage);

        try {
            const serializedValue = storage.getItem(prefixedKey);
            if (!serializedValue) return null;

            const item = JSON.parse(serializedValue);
            if (!item.ttl) return null;

            const now = Date.now();
            const itemTime = item.timestamp || 0;
            const remaining = item.ttl - (now - itemTime);

            return remaining > 0 ? remaining : 0;
        } catch (error) {
            return null;
        }
    }

    cleanup(options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const storage = this.getStorage(config.storage);
        const keysToRemove = [];

        try {
            for (let i = 0; i < storage.length; i++) {
                const key = storage.key(i);
                if (key && key.startsWith(this.options.prefix)) {
                    const serializedValue = storage.getItem(key);
                    if (serializedValue) {
                        try {
                            const item = JSON.parse(serializedValue);
                            if (this.isExpired(item)) {
                                keysToRemove.push(key);
                            }
                        } catch (parseError) {
                            // Remove corrupted items
                            keysToRemove.push(key);
                        }
                    }
                }
            }

            keysToRemove.forEach(key => {
                storage.removeItem(key);
                this.cache.delete(key);
            });

            return keysToRemove.length;
        } catch (error) {
            console.error('Storage cleanup error:', error);
            return 0;
        }
    }

    // ============================================================================
    // EVENT SYSTEM
    // ============================================================================

    on(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, new Set());
        }
        this.listeners.get(key).add(callback);
    }

    off(key, callback) {
        if (this.listeners.has(key)) {
            this.listeners.get(key).delete(callback);
        }
    }

    notifyListeners(key, action, value) {
        if (this.listeners.has(key)) {
            this.listeners.get(key).forEach(callback => {
                try {
                    callback(key, action, value);
                } catch (error) {
                    console.error('Listener callback error:', error);
                }
            });
        }
    }

    handleStorageEvent(event) {
        if (!event.key || !event.key.startsWith(this.options.prefix)) {
            return;
        }

        const originalKey = event.key.substring(this.options.prefix.length);

        if (event.newValue === null) {
            // Item was removed
            this.cache.delete(event.key);
            this.notifyListeners(originalKey, 'remove', null);
        } else {
            // Item was set
            try {
                const item = JSON.parse(event.newValue);
                this.cache.set(event.key, item);
                this.notifyListeners(originalKey, 'set', item.value);
            } catch (error) {
                console.error('Failed to parse storage event value:', error);
            }
        }
    }

    // ============================================================================
    // COMPRESSION & ENCRYPTION
    // ============================================================================

    async compress(data) {
        if (typeof CompressionStream === 'undefined') {
            return data;
        }

        try {
            const stream = new CompressionStream('gzip');
            const writer = stream.writable.getWriter();
            const reader = stream.readable.getReader();

            writer.write(new TextEncoder().encode(data));
            writer.close();

            const chunks = [];
            let done = false;

            while (!done) {
                const { value, done: readerDone } = await reader.read();
                done = readerDone;
                if (value) {
                    chunks.push(value);
                }
            }

            const compressed = new Uint8Array(chunks.reduce((acc, chunk) => acc + chunk.length, 0));
            let offset = 0;
            for (const chunk of chunks) {
                compressed.set(chunk, offset);
                offset += chunk.length;
            }

            return btoa(String.fromCharCode(...compressed));
        } catch (error) {
            console.warn('Compression failed, using uncompressed data:', error);
            return data;
        }
    }

    async decompress(data) {
        if (typeof DecompressionStream === 'undefined') {
            return data;
        }

        try {
            const compressed = Uint8Array.from(atob(data), c => c.charCodeAt(0));
            const stream = new DecompressionStream('gzip');
            const writer = stream.writable.getWriter();
            const reader = stream.readable.getReader();

            writer.write(compressed);
            writer.close();

            const chunks = [];
            let done = false;

            while (!done) {
                const { value, done: readerDone } = await reader.read();
                done = readerDone;
                if (value) {
                    chunks.push(value);
                }
            }

            const decompressed = new Uint8Array(chunks.reduce((acc, chunk) => acc + chunk.length, 0));
            let offset = 0;
            for (const chunk of chunks) {
                decompressed.set(chunk, offset);
                offset += chunk.length;
            }

            return new TextDecoder().decode(decompressed);
        } catch (error) {
            console.warn('Decompression failed, using compressed data:', error);
            return data;
        }
    }

    encrypt(data) {
        // Simple XOR encryption for demonstration
        // In production, use proper encryption libraries
        const key = this.options.prefix;
        let result = '';

        for (let i = 0; i < data.length; i++) {
            result += String.fromCharCode(data.charCodeAt(i) ^ key.charCodeAt(i % key.length));
        }

        return btoa(result);
    }

    decrypt(data) {
        try {
            const decoded = atob(data);
            const key = this.options.prefix;
            let result = '';

            for (let i = 0; i < decoded.length; i++) {
                result += String.fromCharCode(decoded.charCodeAt(i) ^ key.charCodeAt(i % key.length));
            }

            return result;
        } catch (error) {
            console.warn('Decryption failed:', error);
            return data;
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    getPrefixedKey(key) {
        return `${this.options.prefix}${key}`;
    }

    getStorageInfo(options = {}) {
        const config = {
            storage: this.options.defaultStorage,
            ...options
        };

        const storage = this.getStorage(config.storage);

        return {
            type: config.storage,
            available: this.isStorageAvailable(config.storage),
            used: this.size(options),
            maxSize: this.options.maxSize,
            itemCount: this.keys(options).length
        };
    }

    isStorageAvailable(type) {
        try {
            const storage = this.getStorage(type);
            const testKey = '__storage_test__';
            storage.setItem(testKey, 'test');
            storage.removeItem(testKey);
            return true;
        } catch (error) {
            return false;
        }
    }

    // ============================================================================
    // PRESETS
    // ============================================================================

    static getPresets() {
        return {
            // Default configuration
            default: {
                prefix: 'tpt_erp_',
                defaultStorage: 'localStorage',
                compress: false,
                encrypt: false,
                ttl: null,
                maxSize: 5 * 1024 * 1024
            },

            // Secure configuration
            secure: {
                prefix: 'tpt_secure_',
                defaultStorage: 'sessionStorage',
                compress: true,
                encrypt: true,
                ttl: 24 * 60 * 60 * 1000, // 24 hours
                maxSize: 2 * 1024 * 1024
            },

            // Cache configuration
            cache: {
                prefix: 'tpt_cache_',
                defaultStorage: 'sessionStorage',
                compress: false,
                encrypt: false,
                ttl: 60 * 60 * 1000, // 1 hour
                maxSize: 10 * 1024 * 1024
            },

            // Persistent configuration
            persistent: {
                prefix: 'tpt_persist_',
                defaultStorage: 'localStorage',
                compress: true,
                encrypt: false,
                ttl: null,
                maxSize: 50 * 1024 * 1024
            }
        };
    }

    static createPreset(presetName) {
        const presets = StorageManager.getPresets();
        const preset = presets[presetName];

        if (!preset) {
            throw new Error(`Preset '${presetName}' not found`);
        }

        return new StorageManager(preset);
    }

    // ============================================================================
    // EXPORT METHODS
    // ============================================================================

    toJSON() {
        return {
            options: this.options,
            keys: this.keys(),
            size: this.size(),
            cacheSize: this.getCacheSize()
        };
    }

    static fromJSON(data) {
        return new StorageManager(data.options);
    }
}

// Export the utility
window.StorageManager = StorageManager;
