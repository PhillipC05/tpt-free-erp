/**
 * TPT Free ERP - Event Manager Utility
 * Comprehensive event system for component communication and state management
 */

class EventManager {
    constructor(options = {}) {
        this.options = {
            enableLogging: false,
            maxListeners: 100,
            wildcardEvents: true,
            asyncEvents: true,
            ...options
        };

        this.events = new Map();
        this.onceEvents = new Map();
        this.wildcardEvents = new Map();
        this.middlewares = new Set();
        this.eventHistory = [];
        this.eventStats = new Map();

        this.init();
    }

    init() {
        // Set up global error handling for events
        if (typeof window !== 'undefined') {
            this.setupGlobalErrorHandling();
        }
    }

    // ============================================================================
    // EVENT REGISTRATION
    // ============================================================================

    on(eventName, listener, options = {}) {
        const config = {
            priority: 0,
            once: false,
            context: null,
            ...options
        };

        if (!this.events.has(eventName)) {
            this.events.set(eventName, new Set());
        }

        const eventListeners = this.events.get(eventName);

        // Check max listeners limit
        if (eventListeners.size >= this.options.maxListeners) {
            console.warn(`Max listeners (${this.options.maxListeners}) reached for event: ${eventName}`);
            return false;
        }

        // Create listener wrapper with metadata
        const listenerWrapper = {
            listener: config.context ? listener.bind(config.context) : listener,
            originalListener: listener,
            priority: config.priority,
            once: config.once,
            context: config.context,
            id: this.generateListenerId(),
            registeredAt: Date.now()
        };

        eventListeners.add(listenerWrapper);

        // Handle wildcard events
        if (this.options.wildcardEvents && eventName.includes('*')) {
            this.registerWildcardEvent(eventName, listenerWrapper);
        }

        if (this.options.enableLogging) {
            console.log(`Event listener registered: ${eventName}`, listenerWrapper);
        }

        return listenerWrapper.id;
    }

    once(eventName, listener, options = {}) {
        const config = { ...options, once: true };
        return this.on(eventName, listener, config);
    }

    off(eventName, listener) {
        if (!this.events.has(eventName)) {
            return false;
        }

        const eventListeners = this.events.get(eventName);
        let removed = false;

        if (typeof listener === 'string') {
            // Remove by listener ID
            for (const listenerWrapper of eventListeners) {
                if (listenerWrapper.id === listener) {
                    eventListeners.delete(listenerWrapper);
                    removed = true;
                    break;
                }
            }
        } else {
            // Remove by listener function
            for (const listenerWrapper of eventListeners) {
                if (listenerWrapper.originalListener === listener ||
                    listenerWrapper.listener === listener) {
                    eventListeners.delete(listenerWrapper);
                    removed = true;
                    break;
                }
            }
        }

        // Clean up empty event sets
        if (eventListeners.size === 0) {
            this.events.delete(eventName);
        }

        // Remove from wildcard events if applicable
        if (this.options.wildcardEvents && eventName.includes('*')) {
            this.unregisterWildcardEvent(eventName, listener);
        }

        if (this.options.enableLogging && removed) {
            console.log(`Event listener removed: ${eventName}`);
        }

        return removed;
    }

    removeAllListeners(eventName) {
        if (eventName) {
            this.events.delete(eventName);
            this.onceEvents.delete(eventName);
            if (this.options.wildcardEvents) {
                this.wildcardEvents.delete(eventName);
            }
            return true;
        } else {
            // Remove all listeners
            this.events.clear();
            this.onceEvents.clear();
            this.wildcardEvents.clear();
            return true;
        }
    }

    // ============================================================================
    // EVENT EMISSION
    // ============================================================================

    emit(eventName, ...args) {
        const startTime = Date.now();

        // Update event statistics
        this.updateEventStats(eventName);

        // Apply middlewares
        const processedArgs = this.applyMiddlewares(eventName, args);

        // Emit to regular listeners
        const regularResults = this.emitToListeners(eventName, processedArgs);

        // Emit to wildcard listeners
        const wildcardResults = this.emitToWildcardListeners(eventName, processedArgs);

        // Combine results
        const allResults = [...regularResults, ...wildcardResults];

        // Log event emission
        if (this.options.enableLogging) {
            const duration = Date.now() - startTime;
            console.log(`Event emitted: ${eventName}`, {
                args: processedArgs,
                listeners: allResults.length,
                duration: `${duration}ms`
            });
        }

        // Add to event history
        this.addToHistory(eventName, processedArgs, allResults.length, startTime);

        return allResults;
    }

    async emitAsync(eventName, ...args) {
        if (!this.options.asyncEvents) {
            return this.emit(eventName, ...args);
        }

        return new Promise((resolve, reject) => {
            try {
                const results = this.emit(eventName, ...args);
                resolve(results);
            } catch (error) {
                reject(error);
            }
        });
    }

    emitToListeners(eventName, args) {
        const results = [];

        if (!this.events.has(eventName)) {
            return results;
        }

        const eventListeners = this.events.get(eventName);
        const listenersToRemove = [];

        // Sort listeners by priority (higher priority first)
        const sortedListeners = Array.from(eventListeners).sort((a, b) => b.priority - a.priority);

        for (const listenerWrapper of sortedListeners) {
            try {
                const result = listenerWrapper.listener(...args);
                results.push({
                    listenerId: listenerWrapper.id,
                    result,
                    success: true
                });

                // Remove once listeners
                if (listenerWrapper.once) {
                    listenersToRemove.push(listenerWrapper);
                }
            } catch (error) {
                console.error(`Event listener error for ${eventName}:`, error);
                results.push({
                    listenerId: listenerWrapper.id,
                    error: error.message,
                    success: false
                });

                // Optionally remove faulty listeners
                if (this.options.removeFaultyListeners) {
                    listenersToRemove.push(listenerWrapper);
                }
            }
        }

        // Remove once listeners and faulty listeners
        listenersToRemove.forEach(listener => {
            eventListeners.delete(listener);
        });

        return results;
    }

    emitToWildcardListeners(eventName, args) {
        if (!this.options.wildcardEvents) {
            return [];
        }

        const results = [];

        for (const [pattern, listeners] of this.wildcardEvents) {
            if (this.matchesWildcard(eventName, pattern)) {
                for (const listenerWrapper of listeners) {
                    try {
                        const result = listenerWrapper.listener(...args);
                        results.push({
                            listenerId: listenerWrapper.id,
                            pattern,
                            result,
                            success: true
                        });
                    } catch (error) {
                        console.error(`Wildcard event listener error for ${pattern}:`, error);
                        results.push({
                            listenerId: listenerWrapper.id,
                            pattern,
                            error: error.message,
                            success: false
                        });
                    }
                }
            }
        }

        return results;
    }

    // ============================================================================
    // WILDCARD EVENT SYSTEM
    // ============================================================================

    registerWildcardEvent(pattern, listenerWrapper) {
        if (!this.wildcardEvents.has(pattern)) {
            this.wildcardEvents.set(pattern, new Set());
        }
        this.wildcardEvents.get(pattern).add(listenerWrapper);
    }

    unregisterWildcardEvent(pattern, listener) {
        if (this.wildcardEvents.has(pattern)) {
            const listeners = this.wildcardEvents.get(pattern);
            for (const listenerWrapper of listeners) {
                if (listenerWrapper.originalListener === listener ||
                    listenerWrapper.listener === listener) {
                    listeners.delete(listenerWrapper);
                    break;
                }
            }

            if (listeners.size === 0) {
                this.wildcardEvents.delete(pattern);
            }
        }
    }

    matchesWildcard(eventName, pattern) {
        // Convert wildcard pattern to regex
        const regexPattern = pattern
            .replace(/\*/g, '.*')
            .replace(/\?/g, '.')
            .replace(/\//g, '\\/');

        const regex = new RegExp(`^${regexPattern}$`);
        return regex.test(eventName);
    }

    // ============================================================================
    // MIDDLEWARE SYSTEM
    // ============================================================================

    use(middleware) {
        this.middlewares.add(middleware);
        return this;
    }

    removeMiddleware(middleware) {
        this.middlewares.delete(middleware);
        return this;
    }

    applyMiddlewares(eventName, args) {
        let processedArgs = args;

        for (const middleware of this.middlewares) {
            try {
                processedArgs = middleware(eventName, processedArgs);
            } catch (error) {
                console.error('Middleware error:', error);
            }
        }

        return processedArgs;
    }

    // ============================================================================
    // EVENT INSPECTION AND DEBUGGING
    // ============================================================================

    getListeners(eventName) {
        if (!this.events.has(eventName)) {
            return [];
        }

        return Array.from(this.events.get(eventName)).map(wrapper => ({
            id: wrapper.id,
            priority: wrapper.priority,
            once: wrapper.once,
            registeredAt: wrapper.registeredAt
        }));
    }

    getAllListeners() {
        const allListeners = {};

        for (const [eventName, listeners] of this.events) {
            allListeners[eventName] = this.getListeners(eventName);
        }

        return allListeners;
    }

    getEventStats() {
        return Object.fromEntries(this.eventStats);
    }

    getEventHistory(limit = 50) {
        return this.eventHistory.slice(-limit);
    }

    clearHistory() {
        this.eventHistory = [];
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    generateListenerId() {
        return `listener_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    updateEventStats(eventName) {
        if (!this.eventStats.has(eventName)) {
            this.eventStats.set(eventName, {
                count: 0,
                lastEmitted: null,
                firstEmitted: null
            });
        }

        const stats = this.eventStats.get(eventName);
        stats.count++;
        stats.lastEmitted = Date.now();

        if (!stats.firstEmitted) {
            stats.firstEmitted = stats.lastEmitted;
        }
    }

    addToHistory(eventName, args, listenerCount, startTime) {
        if (this.eventHistory.length >= 1000) {
            this.eventHistory.shift(); // Keep only last 1000 events
        }

        this.eventHistory.push({
            eventName,
            args: args.length > 10 ? args.slice(0, 10) : args, // Limit args for memory
            listenerCount,
            timestamp: startTime,
            duration: Date.now() - startTime
        });
    }

    setupGlobalErrorHandling() {
        // Catch unhandled promise rejections from async event listeners
        if (typeof window !== 'undefined' && 'addEventListener' in window) {
            window.addEventListener('unhandledrejection', (event) => {
                if (event.reason && event.reason.message &&
                    event.reason.message.includes('EventManager')) {
                    console.error('Unhandled event listener error:', event.reason);
                    event.preventDefault();
                }
            });
        }
    }

    // ============================================================================
    // PRESET EVENT TYPES
    // ============================================================================

    static getEventTypes() {
        return {
            // Component lifecycle events
            COMPONENT_MOUNT: 'component:mount',
            COMPONENT_UNMOUNT: 'component:unmount',
            COMPONENT_UPDATE: 'component:update',

            // Data events
            DATA_LOAD: 'data:load',
            DATA_SAVE: 'data:save',
            DATA_UPDATE: 'data:update',
            DATA_DELETE: 'data:delete',

            // User interaction events
            USER_LOGIN: 'user:login',
            USER_LOGOUT: 'user:logout',
            USER_ACTION: 'user:action',

            // API events
            API_REQUEST: 'api:request',
            API_SUCCESS: 'api:success',
            API_ERROR: 'api:error',

            // Navigation events
            NAVIGATION_CHANGE: 'navigation:change',
            ROUTE_CHANGE: 'route:change',

            // Notification events
            NOTIFICATION_SHOW: 'notification:show',
            NOTIFICATION_HIDE: 'notification:hide',

            // Modal events
            MODAL_OPEN: 'modal:open',
            MODAL_CLOSE: 'modal:close',

            // Form events
            FORM_SUBMIT: 'form:submit',
            FORM_VALIDATION: 'form:validation',
            FORM_ERROR: 'form:error',

            // Table events
            TABLE_SORT: 'table:sort',
            TABLE_FILTER: 'table:filter',
            TABLE_PAGE: 'table:page',
            TABLE_SELECT: 'table:select',

            // Custom application events
            APP_READY: 'app:ready',
            APP_ERROR: 'app:error',
            APP_UPDATE: 'app:update'
        };
    }

    // ============================================================================
    // PRESET MIDDLEWARES
    // ============================================================================

    static getMiddlewares() {
        return {
            // Logging middleware
            logger: (eventName, args) => {
                console.log(`[EventManager] ${eventName}:`, args);
                return args;
            },

            // Performance monitoring middleware
            performance: (eventName, args) => {
                const start = performance.now();
                // This would be called after event emission to measure performance
                return args;
            },

            // Validation middleware
            validator: (eventName, args) => {
                // Add validation logic here
                return args;
            },

            // Transformation middleware
            transformer: (eventName, args) => {
                // Transform event data here
                return args;
            }
        };
    }

    // ============================================================================
    // EXPORT METHODS
    // ============================================================================

    toJSON() {
        return {
            options: this.options,
            events: Array.from(this.events.keys()),
            listenerCount: this.getTotalListenerCount(),
            eventHistorySize: this.eventHistory.length,
            stats: this.getEventStats()
        };
    }

    getTotalListenerCount() {
        let total = 0;
        for (const listeners of this.events.values()) {
            total += listeners.size;
        }
        return total;
    }

    static fromJSON(data) {
        const manager = new EventManager(data.options);
        // Note: Event listeners cannot be restored from JSON
        return manager;
    }

    // ============================================================================
    // CLEANUP METHODS
    // ============================================================================

    destroy() {
        this.events.clear();
        this.onceEvents.clear();
        this.wildcardEvents.clear();
        this.middlewares.clear();
        this.eventHistory = [];
        this.eventStats.clear();
    }
}

// Export the utility
window.EventManager = EventManager;
