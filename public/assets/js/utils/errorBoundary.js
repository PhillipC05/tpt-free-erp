/**
 * TPT Free ERP - Error Boundary Utility
 * Global error handling, logging, and recovery mechanisms
 */

class ErrorBoundary {
    constructor(options = {}) {
        this.options = {
            enableLogging: true,
            enableReporting: true,
            maxRetries: 3,
            retryDelay: 1000,
            showErrorUI: true,
            logLevel: 'error', // 'error', 'warn', 'info', 'debug'
            reportEndpoint: '/api/errors',
            ...options
        };

        this.errorHistory = [];
        this.retryCounts = new Map();
        this.recoveryStrategies = new Map();

        this.init();
    }

    init() {
        this.setupGlobalErrorHandlers();
        this.setupUnhandledRejectionHandler();
        this.setupConsoleOverride();
    }

    // ============================================================================
    // GLOBAL ERROR HANDLING
    // ============================================================================

    setupGlobalErrorHandlers() {
        // Handle JavaScript errors
        window.addEventListener('error', (event) => {
            this.handleError({
                type: 'javascript_error',
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                stack: event.error?.stack
            });
        });

        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            this.handleError({
                type: 'unhandled_promise_rejection',
                message: event.reason?.message || 'Unhandled promise rejection',
                reason: event.reason,
                stack: event.reason?.stack
            });

            // Prevent the default handler from firing
            event.preventDefault();
        });

        // Handle resource loading errors
        window.addEventListener('error', (event) => {
            if (event.target !== window && event.target instanceof HTMLElement) {
                this.handleError({
                    type: 'resource_error',
                    message: `Failed to load resource: ${event.target.src || event.target.href}`,
                    target: event.target,
                    resourceType: event.target.tagName.toLowerCase()
                });
            }
        }, true);
    }

    setupUnhandledRejectionHandler() {
        // Additional unhandled rejection handler for better coverage
        if (typeof process !== 'undefined' && process.on) {
            process.on('unhandledRejection', (reason, promise) => {
                this.handleError({
                    type: 'node_unhandled_rejection',
                    message: 'Unhandled promise rejection in Node.js environment',
                    reason,
                    promise
                });
            });
        }
    }

    setupConsoleOverride() {
        // Override console methods to capture logs
        if (this.options.enableLogging) {
            const originalConsole = { ...console };

            ['error', 'warn', 'info', 'debug', 'log'].forEach(level => {
                console[level] = (...args) => {
                    // Call original method
                    originalConsole[level](...args);

                    // Log to our system if level matches
                    if (this.shouldLog(level)) {
                        this.logToSystem(level, args);
                    }
                };
            });
        }
    }

    // ============================================================================
    // ERROR HANDLING METHODS
    // ============================================================================

    handleError(errorInfo) {
        // Add timestamp and unique ID
        const error = {
            id: this.generateErrorId(),
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            url: window.location.href,
            ...errorInfo
        };

        // Add to error history
        this.addToHistory(error);

        // Log the error
        if (this.options.enableLogging) {
            this.logError(error);
        }

        // Report the error
        if (this.options.enableReporting) {
            this.reportError(error);
        }

        // Show error UI if enabled
        if (this.options.showErrorUI) {
            this.showErrorUI(error);
        }

        // Attempt recovery
        this.attemptRecovery(error);

        return error.id;
    }

    handleAsyncError(operation, error) {
        return this.handleError({
            type: 'async_operation_error',
            message: `Async operation failed: ${operation}`,
            operation,
            originalError: error,
            stack: error?.stack
        });
    }

    handleComponentError(componentName, error, errorInfo) {
        return this.handleError({
            type: 'component_error',
            message: `Component error in ${componentName}`,
            componentName,
            originalError: error,
            errorInfo,
            stack: error?.stack
        });
    }

    handleApiError(endpoint, error, requestData = null) {
        return this.handleError({
            type: 'api_error',
            message: `API request failed: ${endpoint}`,
            endpoint,
            originalError: error,
            requestData,
            status: error?.status,
            statusText: error?.statusText,
            response: error?.response
        });
    }

    // ============================================================================
    // LOGGING METHODS
    // ============================================================================

    logError(error) {
        const logMessage = this.formatErrorMessage(error);

        switch (this.options.logLevel) {
            case 'debug':
                console.debug(logMessage, error);
                break;
            case 'info':
                console.info(logMessage, error);
                break;
            case 'warn':
                console.warn(logMessage, error);
                break;
            case 'error':
            default:
                console.error(logMessage, error);
                break;
        }
    }

    logToSystem(level, args) {
        const logEntry = {
            level,
            timestamp: new Date().toISOString(),
            message: args.join(' '),
            args: args,
            url: window.location.href,
            userAgent: navigator.userAgent
        };

        // Store in local history for debugging
        if (this.errorHistory.length > 100) {
            this.errorHistory.shift();
        }

        this.errorHistory.push(logEntry);
    }

    shouldLog(level) {
        const levels = ['debug', 'info', 'warn', 'error'];
        const currentLevelIndex = levels.indexOf(this.options.logLevel);
        const messageLevelIndex = levels.indexOf(level);

        return messageLevelIndex >= currentLevelIndex;
    }

    // ============================================================================
    // ERROR REPORTING
    // ============================================================================

    async reportError(error) {
        if (!this.options.reportEndpoint) return;

        try {
            const reportData = {
                error: {
                    ...error,
                    // Remove sensitive information
                    userAgent: undefined,
                    stack: this.sanitizeStack(error.stack)
                },
                environment: {
                    userAgent: navigator.userAgent,
                    url: window.location.href,
                    timestamp: new Date().toISOString(),
                    viewport: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    },
                    localStorage: this.checkLocalStorage(),
                    sessionStorage: this.checkSessionStorage()
                }
            };

            await fetch(this.options.reportEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(reportData)
            });
        } catch (reportError) {
            console.warn('Failed to report error:', reportError);
        }
    }

    sanitizeStack(stack) {
        if (!stack) return undefined;

        // Remove sensitive information from stack trace
        return stack
            .split('\n')
            .filter(line => !line.includes('password') && !line.includes('token'))
            .join('\n');
    }

    checkLocalStorage() {
        try {
            return typeof Storage !== 'undefined' && !!window.localStorage;
        } catch {
            return false;
        }
    }

    checkSessionStorage() {
        try {
            return typeof Storage !== 'undefined' && !!window.sessionStorage;
        } catch {
            return false;
        }
    }

    // ============================================================================
    // ERROR UI DISPLAY
    // ============================================================================

    showErrorUI(error) {
        // Create error notification
        this.showErrorNotification(error);

        // For critical errors, show modal
        if (this.isCriticalError(error)) {
            this.showErrorModal(error);
        }
    }

    showErrorNotification(error) {
        const notification = {
            type: 'error',
            title: 'An error occurred',
            message: this.getErrorMessage(error),
            duration: 5000,
            action: {
                label: 'Report Issue',
                callback: () => this.reportErrorManually(error)
            }
        };

        // Use the application's notification system if available
        if (window.App && window.App.showNotification) {
            window.App.showNotification(notification);
        } else {
            // Fallback to browser alert
            alert(`${notification.title}: ${notification.message}`);
        }
    }

    showErrorModal(error) {
        const modal = document.createElement('div');
        modal.className = 'error-modal-overlay';
        modal.innerHTML = `
            <div class="error-modal">
                <div class="error-modal-header">
                    <h3>Something went wrong</h3>
                    <button class="error-modal-close">&times;</button>
                </div>
                <div class="error-modal-body">
                    <p class="error-message">${this.getErrorMessage(error)}</p>
                    <div class="error-details">
                        <strong>Error ID:</strong> ${error.id}<br>
                        <strong>Time:</strong> ${new Date(error.timestamp).toLocaleString()}<br>
                        <strong>Type:</strong> ${error.type}
                    </div>
                </div>
                <div class="error-modal-footer">
                    <button class="btn btn-secondary" onclick="location.reload()">Reload Page</button>
                    <button class="btn btn-primary" onclick="this.reportErrorManually(error)">Report Issue</button>
                </div>
            </div>
        `;

        // Add close functionality
        const closeBtn = modal.querySelector('.error-modal-close');
        closeBtn.onclick = () => document.body.removeChild(modal);

        // Close on overlay click
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        };

        document.body.appendChild(modal);
    }

    // ============================================================================
    // RECOVERY MECHANISMS
    // ============================================================================

    attemptRecovery(error) {
        const strategy = this.getRecoveryStrategy(error.type);

        if (strategy) {
            try {
                strategy(error);
            } catch (recoveryError) {
                console.error('Recovery strategy failed:', recoveryError);
            }
        }
    }

    registerRecoveryStrategy(errorType, strategy) {
        this.recoveryStrategies.set(errorType, strategy);
    }

    getRecoveryStrategy(errorType) {
        return this.recoveryStrategies.get(errorType);
    }

    // Default recovery strategies
    setupDefaultRecoveryStrategies() {
        // Network error recovery
        this.registerRecoveryStrategy('api_error', (error) => {
            if (error.status === 401) {
                // Redirect to login
                window.location.href = '/login';
            } else if (error.status >= 500) {
                // Show retry option
                this.showRetryUI(error);
            }
        });

        // JavaScript error recovery
        this.registerRecoveryStrategy('javascript_error', (error) => {
            // Attempt to reload problematic resources
            if (error.filename) {
                this.reloadResource(error.filename);
            }
        });

        // Component error recovery
        this.registerRecoveryStrategy('component_error', (error) => {
            // Attempt to re-render component
            if (error.componentName) {
                this.recoverComponent(error.componentName);
            }
        });
    }

    showRetryUI(error) {
        const retryBtn = document.createElement('button');
        retryBtn.className = 'btn btn-primary retry-btn';
        retryBtn.textContent = 'Retry';
        retryBtn.onclick = () => {
            this.retryOperation(error);
            document.body.removeChild(retryBtn.parentElement);
        };

        const container = document.createElement('div');
        container.className = 'retry-container';
        container.innerHTML = '<p>Operation failed. Would you like to retry?</p>';
        container.appendChild(retryBtn);

        document.body.appendChild(container);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (container.parentElement) {
                document.body.removeChild(container);
            }
        }, 10000);
    }

    async retryOperation(error) {
        const retryCount = this.retryCounts.get(error.id) || 0;

        if (retryCount < this.options.maxRetries) {
            this.retryCounts.set(error.id, retryCount + 1);

            // Wait before retry
            await this.delay(this.options.retryDelay * (retryCount + 1));

            // Emit retry event
            if (window.EventManager) {
                window.EventManager.emit('error:retry', error);
            }

            // Here you would implement the actual retry logic
            // This depends on the specific operation that failed
        } else {
            console.warn('Max retries exceeded for error:', error.id);
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    generateErrorId() {
        return `error_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    addToHistory(error) {
        if (this.errorHistory.length >= 100) {
            this.errorHistory.shift();
        }
        this.errorHistory.push(error);
    }

    getErrorHistory(limit = 50) {
        return this.errorHistory.slice(-limit);
    }

    clearHistory() {
        this.errorHistory = [];
    }

    formatErrorMessage(error) {
        let message = `[${error.type.toUpperCase()}] ${error.message}`;

        if (error.filename) {
            message += ` (${error.filename}:${error.lineno}:${error.colno})`;
        }

        return message;
    }

    getErrorMessage(error) {
        switch (error.type) {
            case 'javascript_error':
                return 'A JavaScript error occurred. Please refresh the page.';
            case 'api_error':
                return 'Failed to communicate with the server. Please check your connection.';
            case 'resource_error':
                return 'Failed to load some resources. Please refresh the page.';
            case 'component_error':
                return 'A component failed to load properly.';
            default:
                return error.message || 'An unexpected error occurred.';
        }
    }

    isCriticalError(error) {
        const criticalTypes = ['javascript_error', 'unhandled_promise_rejection'];
        return criticalTypes.includes(error.type);
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    reloadResource(url) {
        // Attempt to reload a resource by appending a cache-busting parameter
        const separator = url.includes('?') ? '&' : '?';
        const newUrl = `${url}${separator}_retry=${Date.now()}`;

        // This is a simplified implementation
        // In practice, you'd need to handle different resource types
        console.log('Attempting to reload resource:', newUrl);
    }

    recoverComponent(componentName) {
        // Attempt to recover a component
        console.log('Attempting to recover component:', componentName);

        // Emit recovery event
        if (window.EventManager) {
            window.EventManager.emit('component:recover', componentName);
        }
    }

    reportErrorManually(error) {
        const report = {
            errorId: error.id,
            message: this.getErrorMessage(error),
            timestamp: error.timestamp,
            url: window.location.href
        };

        // Copy to clipboard if available
        if (navigator.clipboard) {
            navigator.clipboard.writeText(JSON.stringify(report, null, 2));
        }

        alert('Error details copied to clipboard. Please send this information to support.');
    }

    // ============================================================================
    // CONFIGURATION METHODS
    // ============================================================================

    setLogLevel(level) {
        this.options.logLevel = level;
    }

    setReportEndpoint(endpoint) {
        this.options.reportEndpoint = endpoint;
    }

    enableReporting(enabled = true) {
        this.options.enableReporting = enabled;
    }

    enableLogging(enabled = true) {
        this.options.enableLogging = enabled;
    }

    // ============================================================================
    // EXPORT METHODS
    // ============================================================================

    toJSON() {
        return {
            options: this.options,
            errorCount: this.errorHistory.length,
            recentErrors: this.getErrorHistory(10),
            retryCounts: Object.fromEntries(this.retryCounts)
        };
    }

    static fromJSON(data) {
        const boundary = new ErrorBoundary(data.options);
        // Note: Error history cannot be fully restored
        return boundary;
    }

    // ============================================================================
    // CLEANUP METHODS
    // ============================================================================

    destroy() {
        this.errorHistory = [];
        this.retryCounts.clear();
        this.recoveryStrategies.clear();

        // Remove event listeners if possible
        // Note: Global error handlers cannot be easily removed
    }
}

// Initialize default recovery strategies
ErrorBoundary.prototype.setupDefaultRecoveryStrategies = function() {
    // Network error recovery
    this.registerRecoveryStrategy('api_error', (error) => {
        if (error.status === 401) {
            // Redirect to login
            window.location.href = '/login';
        } else if (error.status >= 500) {
            // Show retry option
            this.showRetryUI(error);
        }
    });

    // JavaScript error recovery
    this.registerRecoveryStrategy('javascript_error', (error) => {
        // Attempt to reload problematic resources
        if (error.filename) {
            this.reloadResource(error.filename);
        }
    });

    // Component error recovery
    this.registerRecoveryStrategy('component_error', (error) => {
        // Attempt to recover component
        if (error.componentName) {
            this.recoverComponent(error.componentName);
        }
    });
};

// Export the utility
window.ErrorBoundary = ErrorBoundary;
