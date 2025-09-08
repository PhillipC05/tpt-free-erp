/**
 * Base Component Class
 * Provides common functionality for all components
 */

class BaseComponent {
    constructor(props = {}) {
        this.props = { ...this.defaultProps, ...props };
        this.state = { ...this.defaultState };
        this.bindings = new Set();
        this.eventListeners = new Set();
        this.timers = new Set();
        this.abortControllers = new Set();

        // Auto-bind methods
        this.autoBindMethods();

        // Initialize component
        this.initialize();
    }

    // ============================================================================
    // DEFAULT PROPERTIES (TO BE OVERRIDDEN BY SUBCLASSES)
    // ============================================================================

    get defaultProps() {
        return {
            title: 'Component',
            currentView: 'default'
        };
    }

    get defaultState() {
        return {
            loading: false,
            error: null,
            currentView: this.props.currentView
        };
    }

    // ============================================================================
    // LIFECYCLE METHODS
    // ============================================================================

    /**
     * Initialize component (called in constructor)
     */
    initialize() {
        // Override in subclasses
    }

    /**
     * Component did mount
     */
    async componentDidMount() {
        // Override in subclasses
    }

    /**
     * Component will unmount
     */
    componentWillUnmount() {
        // Cleanup
        this.clearTimers();
        this.clearEventListeners();
        this.abortPendingRequests();
    }

    /**
     * Component did update
     */
    componentDidUpdate(prevProps, prevState) {
        // Override in subclasses
    }

    // ============================================================================
    // STATE MANAGEMENT
    // ============================================================================

    /**
     * Set state and trigger re-render
     */
    setState(newState, callback = null) {
        const prevState = { ...this.state };
        this.state = { ...this.state, ...newState };

        // Call update callback if provided
        if (callback) {
            callback();
        }

        // Trigger component update
        this.componentDidUpdate(this.props, prevState);

        // Trigger re-render if render method exists
        if (this.render && this.container) {
            this.updateView();
        }
    }

    /**
     * Update props
     */
    setProps(newProps) {
        const prevProps = { ...this.props };
        this.props = { ...this.props, ...newProps };

        // Trigger component update
        this.componentDidUpdate(prevProps, this.state);
    }

    // ============================================================================
    // METHOD BINDING
    // ============================================================================

    /**
     * Auto-bind methods that start with 'handle' or are in bindMethods list
     */
    autoBindMethods() {
        const methodsToBind = this.bindMethods || [];

        // Add common handler methods
        const proto = Object.getPrototypeOf(this);
        const methodNames = Object.getOwnPropertyNames(proto);

        methodNames.forEach(methodName => {
            if (methodName.startsWith('handle') ||
                methodName.startsWith('on') ||
                methodsToBind.includes(methodName)) {
                this.bindMethod(methodName);
            }
        });
    }

    /**
     * Bind a method to this instance
     */
    bindMethod(methodName) {
        if (typeof this[methodName] === 'function') {
            this[methodName] = this[methodName].bind(this);
            this.bindings.add(methodName);
        }
    }

    // ============================================================================
    // EVENT MANAGEMENT
    // ============================================================================

    /**
     * Add event listener
     */
    addEventListener(element, event, handler, options = {}) {
        if (element && typeof handler === 'function') {
            element.addEventListener(event, handler, options);
            this.eventListeners.add({ element, event, handler });
        }
    }

    /**
     * Remove event listener
     */
    removeEventListener(element, event, handler) {
        if (element && typeof handler === 'function') {
            element.removeEventListener(event, handler);
            this.eventListeners.forEach((listener, index) => {
                if (listener.element === element &&
                    listener.event === event &&
                    listener.handler === handler) {
                    this.eventListeners.delete(listener);
                }
            });
        }
    }

    /**
     * Clear all event listeners
     */
    clearEventListeners() {
        this.eventListeners.forEach(({ element, event, handler }) => {
            if (element && element.removeEventListener) {
                element.removeEventListener(event, handler);
            }
        });
        this.eventListeners.clear();
    }

    // ============================================================================
    // TIMER MANAGEMENT
    // ============================================================================

    /**
     * Set timeout
     */
    setTimeout(callback, delay) {
        const timer = window.setTimeout(callback, delay);
        this.timers.add(timer);
        return timer;
    }

    /**
     * Set interval
     */
    setInterval(callback, delay) {
        const timer = window.setInterval(callback, delay);
        this.timers.add(timer);
        return timer;
    }

    /**
     * Clear specific timer
     */
    clearTimer(timer) {
        if (timer) {
            window.clearTimeout(timer);
            window.clearInterval(timer);
            this.timers.delete(timer);
        }
    }

    /**
     * Clear all timers
     */
    clearTimers() {
        this.timers.forEach(timer => {
            window.clearTimeout(timer);
            window.clearInterval(timer);
        });
        this.timers.clear();
    }

    // ============================================================================
    // REQUEST MANAGEMENT
    // ============================================================================

    /**
     * Create abort controller
     */
    createAbortController() {
        const controller = new AbortController();
        this.abortControllers.add(controller);
        return controller;
    }

    /**
     * Abort pending requests
     */
    abortPendingRequests() {
        this.abortControllers.forEach(controller => {
            if (!controller.signal.aborted) {
                controller.abort();
            }
        });
        this.abortControllers.clear();
    }

    // ============================================================================
    // DOM UTILITIES
    // ============================================================================

    /**
     * Create DOM element
     */
    createElement(tagName, attributes = {}, content = '') {
        return DOM.create(tagName, attributes, content);
    }

    /**
     * Find element in container
     */
    $(selector) {
        return this.container ? this.container.querySelector(selector) : null;
    }

    /**
     * Find all elements in container
     */
    $$(selector) {
        return this.container ? Array.from(this.container.querySelectorAll(selector)) : [];
    }

    // ============================================================================
    // VIEW MANAGEMENT
    // ============================================================================

    /**
     * Set container element
     */
    setContainer(container) {
        this.container = container;
        this.updateView();
    }

    /**
     * Update view
     */
    updateView() {
        if (this.container && this.render) {
            const newContent = this.render();
            if (newContent) {
                this.container.innerHTML = '';
                this.container.appendChild(newContent);
            }
        }
    }

    /**
     * Show loading state
     */
    showLoading(message = 'Loading...') {
        if (this.container) {
            this.container.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p>${message}</p>
                </div>
            `;
        }
    }

    /**
     * Show error state
     */
    showError(message = 'An error occurred', retryCallback = null) {
        if (this.container) {
            const errorDiv = this.createElement('div', { className: 'error-container' });

            const errorMessage = this.createElement('div', { className: 'error-message' }, message);
            errorDiv.appendChild(errorMessage);

            if (retryCallback) {
                const retryButton = this.createElement('button', {
                    className: 'btn btn-primary retry-button',
                    onclick: retryCallback
                }, 'Retry');
                errorDiv.appendChild(retryButton);
            }

            this.container.innerHTML = '';
            this.container.appendChild(errorDiv);
        }
    }

    /**
     * Show empty state
     */
    showEmpty(message = 'No data available', actionButton = null) {
        if (this.container) {
            const emptyDiv = this.createElement('div', { className: 'empty-container' });

            const emptyMessage = this.createElement('div', { className: 'empty-message' }, message);
            emptyDiv.appendChild(emptyMessage);

            if (actionButton) {
                emptyDiv.appendChild(actionButton);
            }

            this.container.innerHTML = '';
            this.container.appendChild(emptyDiv);
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Format date
     */
    formatDate(dateString, options = {}) {
        if (!dateString) return '';

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            ...options
        });
    }

    /**
     * Format date and time
     */
    formatDateTime(dateString, options = {}) {
        if (!dateString) return '';

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            ...options
        });
    }

    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'USD') {
        if (amount === null || amount === undefined) return '';

        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }

    /**
     * Format number
     */
    formatNumber(number, options = {}) {
        if (number === null || number === undefined) return '';

        return new Intl.NumberFormat('en-US', options).format(number);
    }

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = this.setTimeout(later, wait);
        };
    }

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                this.setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Deep clone object
     */
    deepClone(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj.getTime());
        if (obj instanceof Array) return obj.map(item => this.deepClone(item));

        const cloned = {};
        Object.keys(obj).forEach(key => {
            cloned[key] = this.deepClone(obj[key]);
        });
        return cloned;
    }

    /**
     * Check if object is empty
     */
    isEmpty(obj) {
        if (!obj) return true;
        if (Array.isArray(obj)) return obj.length === 0;
        if (typeof obj === 'object') return Object.keys(obj).length === 0;
        if (typeof obj === 'string') return obj.trim().length === 0;
        return false;
    }

    // ============================================================================
    // NOTIFICATION METHODS
    // ============================================================================

    /**
     * Show success notification
     */
    showSuccess(message, options = {}) {
        this.showNotification('success', message, options);
    }

    /**
     * Show error notification
     */
    showErrorNotification(message, options = {}) {
        this.showNotification('error', message, options);
    }

    /**
     * Show warning notification
     */
    showWarning(message, options = {}) {
        this.showNotification('warning', message, options);
    }

    /**
     * Show info notification
     */
    showInfo(message, options = {}) {
        this.showNotification('info', message, options);
    }

    /**
     * Show notification (to be overridden or use global App.showNotification)
     */
    showNotification(type, message, options = {}) {
        if (typeof App !== 'undefined' && App.showNotification) {
            App.showNotification({ type, message, ...options });
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    // ============================================================================
    // API METHODS
    // ============================================================================

    /**
     * Make API request
     */
    async apiRequest(method, endpoint, data = null, options = {}) {
        try {
            return await apiClient.request(method, endpoint, data, options);
        } catch (error) {
            this.handleApiError(error);
            throw error;
        }
    }

    /**
     * Handle API error
     */
    handleApiError(error) {
        let message = 'An error occurred';

        if (error.message) {
            message = error.message;
        } else if (error.status) {
            switch (error.status) {
                case 400:
                    message = 'Invalid request';
                    break;
                case 401:
                    message = 'Authentication required';
                    break;
                case 403:
                    message = 'Access denied';
                    break;
                case 404:
                    message = 'Resource not found';
                    break;
                case 500:
                    message = 'Server error';
                    break;
                default:
                    message = `Error ${error.status}`;
            }
        }

        this.showErrorNotification(message);
        this.setState({ error: message, loading: false });
    }

    // ============================================================================
    // MODAL METHODS
    // ============================================================================

    /**
     * Show modal
     */
    showModal(modalId, options = {}) {
        return modalManager.show(modalId, options);
    }

    /**
     * Hide modal
     */
    hideModal(modalId) {
        modalManager.hide(modalId);
    }

    /**
     * Show confirmation dialog
     */
    confirm(options = {}) {
        return ModalManager.confirm(options);
    }

    /**
     * Show alert dialog
     */
    alert(options = {}) {
        return ModalManager.alert(options);
    }

    // ============================================================================
    // TABLE METHODS
    // ============================================================================

    /**
     * Create table renderer
     */
    createTableRenderer(options = {}) {
        return new TableRenderer(options);
    }

    /**
     * Create employee table
     */
    createEmployeeTable(container, employees, options = {}) {
        return TableRenderer.createEmployeeTable(container, employees, options);
    }

    /**
     * Create project table
     */
    createProjectTable(container, projects, options = {}) {
        return TableRenderer.createProjectTable(container, projects, options);
    }
}

// Make globally available
if (typeof window !== 'undefined') {
    window.BaseComponent = BaseComponent;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BaseComponent;
}
