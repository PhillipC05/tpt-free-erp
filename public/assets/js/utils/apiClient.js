/**
 * API Client Utility
 * Provides centralized API communication with error handling and caching
 */

class ApiClient {
    constructor(options = {}) {
        this.baseURL = options.baseURL || '/api';
        this.timeout = options.timeout || 30000;
        this.headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        };

        this.cache = new Map();
        this.cacheTimeout = options.cacheTimeout || 300000; // 5 minutes
        this.requestQueue = new Map();
        this.interceptors = {
            request: [],
            response: []
        };

        // Setup default interceptors
        this.setupDefaultInterceptors();
    }

    /**
     * Setup default request/response interceptors
     */
    setupDefaultInterceptors() {
        // Request interceptor for authentication
        this.addRequestInterceptor((config) => {
            const token = this.getAuthToken();
            if (token) {
                config.headers = {
                    ...config.headers,
                    'Authorization': `Bearer ${token}`
                };
            }
            return config;
        });

        // Response interceptor for error handling
        this.addResponseInterceptor(
            (response) => response,
            (error) => this.handleApiError(error)
        );
    }

    /**
     * Add request interceptor
     */
    addRequestInterceptor(interceptor) {
        this.interceptors.request.push(interceptor);
    }

    /**
     * Add response interceptor
     */
    addResponseInterceptor(successInterceptor, errorInterceptor) {
        this.interceptors.response.push({ success: successInterceptor, error: errorInterceptor });
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        return localStorage.getItem('auth_token') ||
               sessionStorage.getItem('auth_token') ||
               this.getCookie('auth_token');
    }

    /**
     * Get cookie value
     */
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    /**
     * Make HTTP request
     */
    async request(method, endpoint, data = null, options = {}) {
        const config = {
            method: method.toUpperCase(),
            headers: { ...this.headers },
            timeout: this.timeout,
            ...options
        };

        // Build URL
        const url = endpoint.startsWith('http') ? endpoint : `${this.baseURL}${endpoint}`;

        // Add query parameters for GET requests
        let finalUrl = url;
        if (method.toUpperCase() === 'GET' && data) {
            const params = new URLSearchParams(data);
            finalUrl += `?${params.toString()}`;
        }

        // Add request body for non-GET requests
        if (method.toUpperCase() !== 'GET' && data) {
            if (data instanceof FormData) {
                config.body = data;
                delete config.headers['Content-Type']; // Let browser set it for FormData
            } else {
                config.body = JSON.stringify(data);
            }
        }

        // Apply request interceptors
        let processedConfig = config;
        for (const interceptor of this.interceptors.request) {
            processedConfig = await interceptor(processedConfig);
        }

        // Check cache for GET requests
        const cacheKey = `${method}:${finalUrl}`;
        if (method.toUpperCase() === 'GET' && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                return cached.data;
            } else {
                this.cache.delete(cacheKey);
            }
        }

        // Prevent duplicate requests
        if (this.requestQueue.has(cacheKey)) {
            return this.requestQueue.get(cacheKey);
        }

        // Create abort controller for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        processedConfig.signal = controller.signal;

        // Make the request
        const requestPromise = this.makeHttpRequest(finalUrl, processedConfig);

        // Store in request queue
        this.requestQueue.set(cacheKey, requestPromise);

        try {
            const response = await requestPromise;

            // Clear timeout
            clearTimeout(timeoutId);

            // Apply response interceptors
            let processedResponse = response;
            for (const interceptor of this.interceptors.response) {
                processedResponse = await interceptor.success(processedResponse);
            }

            // Cache GET responses
            if (method.toUpperCase() === 'GET') {
                this.cache.set(cacheKey, {
                    data: processedResponse,
                    timestamp: Date.now()
                });
            }

            return processedResponse;
        } catch (error) {
            // Clear timeout
            clearTimeout(timeoutId);

            // Apply error interceptors
            let processedError = error;
            for (const interceptor of this.interceptors.response) {
                if (interceptor.error) {
                    processedError = await interceptor.error(processedError);
                }
            }

            throw processedError;
        } finally {
            // Remove from request queue
            this.requestQueue.delete(cacheKey);
        }
    }

    /**
     * Make actual HTTP request
     */
    async makeHttpRequest(url, config) {
        const response = await fetch(url, config);

        if (!response.ok) {
            const errorData = await response.text();
            let errorMessage;

            try {
                const errorJson = JSON.parse(errorData);
                errorMessage = errorJson.message || errorJson.error || `HTTP ${response.status}`;
            } catch {
                errorMessage = errorData || `HTTP ${response.status}`;
            }

            const error = new Error(errorMessage);
            error.status = response.status;
            error.statusText = response.statusText;
            error.response = response;
            throw error;
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            return await response.text();
        }
    }

    /**
     * Handle API errors
     */
    async handleApiError(error) {
        // Handle authentication errors
        if (error.status === 401) {
            this.handleAuthError();
            throw new Error('Authentication required');
        }

        // Handle rate limiting
        if (error.status === 429) {
            const retryAfter = error.response.headers.get('Retry-After');
            throw new Error(`Rate limited. Try again in ${retryAfter || 'a few'} seconds`);
        }

        // Handle server errors
        if (error.status >= 500) {
            throw new Error('Server error. Please try again later');
        }

        // Handle network errors
        if (error.name === 'AbortError') {
            throw new Error('Request timeout');
        }

        if (!navigator.onLine) {
            throw new Error('No internet connection');
        }

        // Re-throw other errors
        throw error;
    }

    /**
     * Handle authentication errors
     */
    handleAuthError() {
        // Clear stored tokens
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

        // Redirect to login or show login modal
        if (typeof App !== 'undefined' && App.showLogin) {
            App.showLogin();
        } else {
            window.location.href = '/login';
        }
    }

    // ============================================================================
    // HTTP METHOD SHORTCUTS
    // ============================================================================

    async get(endpoint, params = null, options = {}) {
        return this.request('GET', endpoint, params, options);
    }

    async post(endpoint, data = null, options = {}) {
        return this.request('POST', endpoint, data, options);
    }

    async put(endpoint, data = null, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }

    async patch(endpoint, data = null, options = {}) {
        return this.request('PATCH', endpoint, data, options);
    }

    async delete(endpoint, options = {}) {
        return this.request('DELETE', null, options);
    }

    // ============================================================================
    // BATCH OPERATIONS
    // ============================================================================

    /**
     * Execute multiple requests in parallel
     */
    async batch(requests) {
        const promises = requests.map(req =>
            this.request(req.method, req.endpoint, req.data, req.options)
        );

        try {
            const results = await Promise.allSettled(promises);
            return results.map((result, index) => ({
                request: requests[index],
                success: result.status === 'fulfilled',
                data: result.status === 'fulfilled' ? result.value : null,
                error: result.status === 'rejected' ? result.reason : null
            }));
        } catch (error) {
            throw new Error('Batch request failed');
        }
    }

    /**
     * Execute requests sequentially
     */
    async sequential(requests) {
        const results = [];

        for (const req of requests) {
            try {
                const data = await this.request(req.method, req.endpoint, req.data, req.options);
                results.push({
                    request: req,
                    success: true,
                    data,
                    error: null
                });
            } catch (error) {
                results.push({
                    request: req,
                    success: false,
                    data: null,
                    error
                });

                // Stop on first error if not configured to continue
                if (!req.continueOnError) {
                    break;
                }
            }
        }

        return results;
    }

    // ============================================================================
    // FILE UPLOAD METHODS
    // ============================================================================

    /**
     * Upload single file
     */
    async uploadFile(endpoint, file, options = {}) {
        const formData = new FormData();
        formData.append('file', file);

        if (options.additionalData) {
            Object.keys(options.additionalData).forEach(key => {
                formData.append(key, options.additionalData[key]);
            });
        }

        return this.request('POST', endpoint, formData, {
            ...options,
            headers: {
                ...this.headers,
                // Don't set Content-Type for FormData
            }
        });
    }

    /**
     * Upload multiple files
     */
    async uploadFiles(endpoint, files, options = {}) {
        const formData = new FormData();

        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        if (options.additionalData) {
            Object.keys(options.additionalData).forEach(key => {
                formData.append(key, options.additionalData[key]);
            });
        }

        return this.request('POST', endpoint, formData, {
            ...options,
            headers: {
                ...this.headers,
                // Don't set Content-Type for FormData
            }
        });
    }

    // ============================================================================
    // CACHE MANAGEMENT
    // ============================================================================

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Clear specific cache entry
     */
    clearCacheEntry(method, endpoint) {
        const cacheKey = `${method.toUpperCase()}:${this.baseURL}${endpoint}`;
        this.cache.delete(cacheKey);
    }

    /**
     * Get cache size
     */
    getCacheSize() {
        return this.cache.size;
    }

    /**
     * Set cache timeout
     */
    setCacheTimeout(timeout) {
        this.cacheTimeout = timeout;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Set authentication token
     */
    setAuthToken(token, remember = false) {
        if (remember) {
            localStorage.setItem('auth_token', token);
        } else {
            sessionStorage.setItem('auth_token', token);
        }

        // Also set as cookie for server-side access
        document.cookie = `auth_token=${token}; path=/; max-age=${remember ? 2592000 : ''}; SameSite=Strict`;
    }

    /**
     * Clear authentication token
     */
    clearAuthToken() {
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!this.getAuthToken();
    }

    /**
     * Set base URL
     */
    setBaseURL(url) {
        this.baseURL = url;
    }

    /**
     * Set default headers
     */
    setDefaultHeaders(headers) {
        this.headers = { ...this.headers, ...headers };
    }

    /**
     * Set timeout
     */
    setTimeout(timeout) {
        this.timeout = timeout;
    }
}

// Create global instance
const apiClient = new ApiClient();

// Make globally available
if (typeof window !== 'undefined') {
    window.ApiClient = ApiClient;
    window.apiClient = apiClient;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiClient;
}
