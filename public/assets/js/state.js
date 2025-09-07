/**
 * TPT Free ERP - State Management
 * Centralized state management system using Observer pattern
 */

class StateManager {
    constructor() {
        this.state = {};
        this.listeners = new Map();
        this.middlewares = [];
        this.history = [];
        this.historyIndex = -1;
        this.maxHistorySize = 50;
        this.isUpdating = false;

        // Initialize default state
        this.initializeDefaultState();
    }

    /**
     * Initialize default application state
     */
    initializeDefaultState() {
        this.state = {
            // User state
            user: {
                isAuthenticated: false,
                currentUser: null,
                permissions: [],
                preferences: {
                    theme: 'light',
                    language: 'en',
                    timezone: 'UTC',
                    dateFormat: 'YYYY-MM-DD',
                    timeFormat: 'HH:mm:ss'
                }
            },

            // UI state
            ui: {
                sidebarCollapsed: false,
                loading: false,
                notifications: [],
                modals: [],
                toasts: [],
                search: {
                    query: '',
                    results: [],
                    isSearching: false
                }
            },

            // Application data
            data: {
                modules: [],
                settings: {},
                cache: new Map()
            },

            // Navigation state
            navigation: {
                currentRoute: null,
                breadcrumbs: [],
                history: []
            },

            // Business modules state
            modules: {
                finance: {
                    accounts: [],
                    transactions: [],
                    loading: false
                },
                inventory: {
                    products: [],
                    stock: [],
                    loading: false
                },
                sales: {
                    customers: [],
                    orders: [],
                    opportunities: [],
                    loading: false
                },
                hr: {
                    employees: [],
                    payroll: [],
                    loading: false
                },
                projects: {
                    projects: [],
                    tasks: [],
                    timeEntries: [],
                    loading: false
                },
                quality: {
                    checks: [],
                    audits: [],
                    nonConformances: [],
                    loading: false
                },
                assets: {
                    assets: [],
                    maintenance: [],
                    loading: false
                },
                fieldService: {
                    calls: [],
                    technicians: [],
                    schedules: [],
                    loading: false
                },
                lms: {
                    courses: [],
                    enrollments: [],
                    certifications: [],
                    loading: false
                },
                iot: {
                    devices: [],
                    sensors: [],
                    readings: [],
                    loading: false
                }
            },

            // System state
            system: {
                online: navigator.onLine,
                lastSync: null,
                version: CONFIG.APP.VERSION,
                environment: CONFIG.APP.ENVIRONMENT
            }
        };
    }

    /**
     * Get state value
     */
    get(path, defaultValue = undefined) {
        return this.getNestedValue(this.state, path, defaultValue);
    }

    /**
     * Set state value
     */
    async set(path, value, options = {}) {
        if (this.isUpdating) {
            console.warn('State update already in progress');
            return;
        }

        this.isUpdating = true;

        try {
            const oldValue = this.get(path);
            const newState = this.setNestedValue({ ...this.state }, path, value);

            // Run middlewares
            for (const middleware of this.middlewares) {
                const result = await middleware(path, value, oldValue, newState);
                if (result === false) {
                    return; // Middleware prevented the update
                }
            }

            // Update state
            this.state = newState;

            // Add to history if not silent
            if (!options.silent) {
                this.addToHistory(path, oldValue, value);
            }

            // Notify listeners
            this.notifyListeners(path, value, oldValue);

            // Persist to storage if needed
            if (options.persist) {
                this.persistState(path, value);
            }

        } catch (error) {
            console.error('State update error:', error);
        } finally {
            this.isUpdating = false;
        }
    }

    /**
     * Update multiple state values
     */
    async update(updates, options = {}) {
        if (this.isUpdating) {
            console.warn('State update already in progress');
            return;
        }

        this.isUpdating = true;

        try {
            const newState = { ...this.state };
            const changes = [];

            // Apply all updates
            for (const [path, value] of Object.entries(updates)) {
                const oldValue = this.getNestedValue(this.state, path);
                this.setNestedValue(newState, path, value);
                changes.push({ path, oldValue, newValue: value });
            }

            // Run middlewares for each change
            for (const change of changes) {
                for (const middleware of this.middlewares) {
                    const result = await middleware(change.path, change.newValue, change.oldValue, newState);
                    if (result === false) {
                        return; // Middleware prevented the update
                    }
                }
            }

            // Update state
            this.state = newState;

            // Add to history if not silent
            if (!options.silent) {
                for (const change of changes) {
                    this.addToHistory(change.path, change.oldValue, change.newValue);
                }
            }

            // Notify listeners for each change
            for (const change of changes) {
                this.notifyListeners(change.path, change.newValue, change.oldValue);
            }

            // Persist to storage if needed
            if (options.persist) {
                for (const change of changes) {
                    this.persistState(change.path, change.newValue);
                }
            }

        } catch (error) {
            console.error('State update error:', error);
        } finally {
            this.isUpdating = false;
        }
    }

    /**
     * Subscribe to state changes
     */
    subscribe(path, callback, options = {}) {
        if (!this.listeners.has(path)) {
            this.listeners.set(path, new Set());
        }

        const listener = {
            callback,
            once: options.once || false,
            immediate: options.immediate || false
        };

        this.listeners.get(path).add(listener);

        // Call immediately if requested
        if (options.immediate) {
            const currentValue = this.get(path);
            callback(currentValue, undefined, path);
        }

        // Return unsubscribe function
        return () => {
            const listeners = this.listeners.get(path);
            if (listeners) {
                listeners.delete(listener);
                if (listeners.size === 0) {
                    this.listeners.delete(path);
                }
            }
        };
    }

    /**
     * Unsubscribe from state changes
     */
    unsubscribe(path, callback) {
        const listeners = this.listeners.get(path);
        if (listeners) {
            for (const listener of listeners) {
                if (listener.callback === callback) {
                    listeners.delete(listener);
                    break;
                }
            }
            if (listeners.size === 0) {
                this.listeners.delete(path);
            }
        }
    }

    /**
     * Add middleware
     */
    use(middleware) {
        this.middlewares.push(middleware);
        return this;
    }

    /**
     * Get nested value from object
     */
    getNestedValue(obj, path, defaultValue = undefined) {
        const keys = path.split('.');
        let current = obj;

        for (const key of keys) {
            if (current && typeof current === 'object' && key in current) {
                current = current[key];
            } else {
                return defaultValue;
            }
        }

        return current;
    }

    /**
     * Set nested value in object
     */
    setNestedValue(obj, path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        let current = obj;

        // Create nested objects if they don't exist
        for (const key of keys) {
            if (!(key in current) || typeof current[key] !== 'object' || current[key] === null) {
                current[key] = {};
            }
            current = current[key];
        }

        current[lastKey] = value;
        return obj;
    }

    /**
     * Notify listeners of state change
     */
    notifyListeners(path, newValue, oldValue) {
        const listeners = this.listeners.get(path);
        if (listeners) {
            for (const listener of listeners) {
                try {
                    listener.callback(newValue, oldValue, path);
                    if (listener.once) {
                        listeners.delete(listener);
                    }
                } catch (error) {
                    console.error('Listener error:', error);
                }
            }
        }

        // Also notify wildcard listeners
        this.notifyWildcardListeners(path, newValue, oldValue);
    }

    /**
     * Notify wildcard listeners
     */
    notifyWildcardListeners(path, newValue, oldValue) {
        for (const [listenerPath, listeners] of this.listeners) {
            if (listenerPath.includes('*')) {
                const regex = new RegExp(listenerPath.replace(/\*/g, '.*'));
                if (regex.test(path)) {
                    for (const listener of listeners) {
                        try {
                            listener.callback(newValue, oldValue, path);
                            if (listener.once) {
                                listeners.delete(listener);
                            }
                        } catch (error) {
                            console.error('Wildcard listener error:', error);
                        }
                    }
                }
            }
        }
    }

    /**
     * Add change to history
     */
    addToHistory(path, oldValue, newValue) {
        const change = {
            path,
            oldValue: deepClone(oldValue),
            newValue: deepClone(newValue),
            timestamp: Date.now()
        };

        this.history.push(change);
        this.historyIndex = this.history.length - 1;

        // Limit history size
        if (this.history.length > this.maxHistorySize) {
            this.history.shift();
            this.historyIndex--;
        }
    }

    /**
     * Undo last change
     */
    async undo() {
        if (this.historyIndex >= 0) {
            const change = this.history[this.historyIndex];
            await this.set(change.path, change.oldValue, { silent: true });
            this.historyIndex--;
        }
    }

    /**
     * Redo last undone change
     */
    async redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const change = this.history[this.historyIndex];
            await this.set(change.path, change.newValue, { silent: true });
        }
    }

    /**
     * Persist state to storage
     */
    persistState(path, value) {
        try {
            const persistKey = `state_${path.replace(/\./g, '_')}`;
            StorageUtils.set(persistKey, value);
        } catch (error) {
            console.warn('Failed to persist state:', error);
        }
    }

    /**
     * Load persisted state
     */
    loadPersistedState() {
        try {
            // Load user preferences
            const preferences = StorageUtils.get('user_preferences');
            if (preferences) {
                this.set('user.preferences', preferences, { silent: true });
            }

            // Load UI state
            const uiState = StorageUtils.get('ui_state');
            if (uiState) {
                this.set('ui', { ...this.state.ui, ...uiState }, { silent: true });
            }

        } catch (error) {
            console.warn('Failed to load persisted state:', error);
        }
    }

    /**
     * Reset state to initial values
     */
    reset() {
        this.initializeDefaultState();
        this.history = [];
        this.historyIndex = -1;
        this.notifyListeners('*', this.state, {});
    }

    /**
     * Get current state snapshot
     */
    getState() {
        return deepClone(this.state);
    }

    /**
     * Replace entire state
     */
    replaceState(newState) {
        this.state = deepClone(newState);
        this.history = [];
        this.historyIndex = -1;
        this.notifyListeners('*', this.state, {});
    }

    /**
     * Get state history
     */
    getHistory() {
        return [...this.history];
    }

    /**
     * Clear state history
     */
    clearHistory() {
        this.history = [];
        this.historyIndex = -1;
    }

    /**
     * Destroy state manager
     */
    destroy() {
        this.listeners.clear();
        this.middlewares = [];
        this.clearHistory();
    }
}

// Create global state manager instance
const State = new StateManager();

// Load persisted state on initialization
State.loadPersistedState();

// Make state manager globally available
window.State = State;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StateManager;
}
