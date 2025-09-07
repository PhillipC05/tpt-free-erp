/**
 * TPT Free ERP - API Client
 * Handles all HTTP communication with the backend API
 */

class APIClient {
    constructor() {
        this.baseURL = CONFIG.API.BASE_URL;
        this.timeout = CONFIG.API.TIMEOUT;
        this.retryAttempts = CONFIG.API.RETRY_ATTEMPTS;
        this.retryDelay = CONFIG.API.RETRY_DELAY;
        this.cache = new Map();
        this.pendingRequests = new Map();
    }

    /**
     * Make HTTP request
     */
    async request(method, endpoint, data = null, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${this.baseURL}${endpoint}`;
        const cacheKey = `${method}:${url}:${JSON.stringify(data)}`;

        // Check cache for GET requests
        if (method === 'GET' && CONFIG.CACHE.ENABLED && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < CONFIG.CACHE.TTL) {
                return cached.data;
            } else {
                this.cache.delete(cacheKey);
            }
        }

        // Check for pending identical requests
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        const requestPromise = this._makeRequest(method, url, data, options, cacheKey);
        this.pendingRequests.set(cacheKey, requestPromise);

        try {
            const result = await requestPromise;
            this.pendingRequests.delete(cacheKey);
            return result;
        } catch (error) {
            this.pendingRequests.delete(cacheKey);
            throw error;
        }
    }

    /**
     * Make the actual HTTP request
     */
    async _makeRequest(method, url, data, options, cacheKey, attempt = 1) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        try {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            };

            // Add authorization header if token exists
            const token = this.getAuthToken();
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const requestOptions = {
                method: method.toUpperCase(),
                headers,
                signal: controller.signal,
                ...options
            };

            // Add body for non-GET requests
            if (data && method.toUpperCase() !== 'GET') {
                requestOptions.body = JSON.stringify(data);
            }

            // Add query parameters for GET requests
            if (data && method.toUpperCase() === 'GET') {
                const params = new URLSearchParams();
                Object.entries(data).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        params.append(key, value);
                    }
                });
                const separator = url.includes('?') ? '&' : '?';
                url += separator + params.toString();
            }

            const response = await fetch(url, requestOptions);
            clearTimeout(timeoutId);

            // Handle different response types
            let responseData;
            const contentType = response.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                responseData = await response.json();
            } else {
                responseData = await response.text();
            }

            // Handle HTTP errors
            if (!response.ok) {
                const error = new Error(responseData.message || `HTTP ${response.status}`);
                error.status = response.status;
                error.data = responseData;
                throw error;
            }

            // Cache successful GET responses
            if (method === 'GET' && CONFIG.CACHE.ENABLED && response.ok) {
                this.cache.set(cacheKey, {
                    data: responseData,
                    timestamp: Date.now()
                });

                // Clean up old cache entries
                if (this.cache.size > CONFIG.CACHE.MAX_SIZE) {
                    const oldestKey = this.cache.keys().next().value;
                    this.cache.delete(oldestKey);
                }
            }

            return responseData;

        } catch (error) {
            clearTimeout(timeoutId);

            // Handle network errors and timeouts
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }

            // Retry logic for certain errors
            if (CONFIG.ERRORS.RETRY_FAILED_REQUESTS &&
                attempt < this.retryAttempts &&
                this.shouldRetry(error)) {

                await this.delay(this.retryDelay * attempt);
                return this._makeRequest(method, url, data, options, cacheKey, attempt + 1);
            }

            // Log errors in development
            if (CONFIG.APP.DEBUG && CONFIG.ERRORS.LOG_TO_CONSOLE) {
                console.error('API Request Failed:', {
                    method,
                    url,
                    error: error.message,
                    attempt,
                    stack: error.stack
                });
            }

            throw error;
        }
    }

    /**
     * Determine if request should be retried
     */
    shouldRetry(error) {
        // Retry on network errors, timeouts, and 5xx server errors
        return error.name === 'TypeError' ||
               error.message === 'Request timeout' ||
               (error.status && error.status >= 500);
    }

    /**
     * Delay helper
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        return StorageUtils.get(CONFIG.AUTH.TOKEN_KEY);
    }

    /**
     * Set authentication token
     */
    setAuthToken(token) {
        StorageUtils.set(CONFIG.AUTH.TOKEN_KEY, token);
    }

    /**
     * Clear authentication token
     */
    clearAuthToken() {
        StorageUtils.remove(CONFIG.AUTH.TOKEN_KEY);
        StorageUtils.remove(CONFIG.AUTH.REFRESH_TOKEN_KEY);
        StorageUtils.remove(CONFIG.AUTH.USER_KEY);
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        const token = this.getAuthToken();
        const user = StorageUtils.get(CONFIG.AUTH.USER_KEY);

        if (!token || !user) {
            return false;
        }

        // Check if token is expired
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const currentTime = Date.now() / 1000;

            if (payload.exp && payload.exp < currentTime) {
                this.clearAuthToken();
                return false;
            }
        } catch (e) {
            this.clearAuthToken();
            return false;
        }

        return true;
    }

    /**
     * Refresh authentication token
     */
    async refreshToken() {
        const refreshToken = StorageUtils.get(CONFIG.AUTH.REFRESH_TOKEN_KEY);

        if (!refreshToken) {
            throw new Error('No refresh token available');
        }

        try {
            const response = await this.request('POST', '/auth/refresh', {
                refresh_token: refreshToken
            });

            if (response.token) {
                this.setAuthToken(response.token);
                if (response.refresh_token) {
                    StorageUtils.set(CONFIG.AUTH.REFRESH_TOKEN_KEY, response.refresh_token);
                }
                return response.token;
            } else {
                throw new Error('Invalid refresh response');
            }
        } catch (error) {
            this.clearAuthToken();
            throw error;
        }
    }

    /**
     * HTTP method shortcuts
     */
    get(endpoint, params = null, options = {}) {
        return this.request('GET', endpoint, params, options);
    }

    post(endpoint, data = null, options = {}) {
        return this.request('POST', endpoint, data, options);
    }

    put(endpoint, data = null, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }

    patch(endpoint, data = null, options = {}) {
        return this.request('PATCH', endpoint, data, options);
    }

    delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }

    /**
     * Authentication methods
     */
    async login(credentials) {
        const response = await this.post('/auth/login', credentials);

        if (response.token) {
            this.setAuthToken(response.token);
            if (response.refresh_token) {
                StorageUtils.set(CONFIG.AUTH.REFRESH_TOKEN_KEY, response.refresh_token);
            }
            if (response.user) {
                StorageUtils.set(CONFIG.AUTH.USER_KEY, response.user);
            }
        }

        return response;
    }

    async logout() {
        try {
            await this.post('/auth/logout');
        } catch (error) {
            // Ignore logout errors
        } finally {
            this.clearAuthToken();
        }
    }

    async register(userData) {
        return this.post('/auth/register', userData);
    }

    async forgotPassword(email) {
        return this.post('/auth/forgot-password', { email });
    }

    async resetPassword(token, password) {
        return this.post('/auth/reset-password', { token, password });
    }

    /**
     * User methods
     */
    async getCurrentUser() {
        return this.get('/users/me');
    }

    async updateProfile(userData) {
        return this.put('/users/me', userData);
    }

    async changePassword(passwordData) {
        return this.put('/users/me/password', passwordData);
    }

    /**
     * Generic CRUD methods
     */
    async getList(resource, params = {}) {
        return this.get(`/${resource}`, params);
    }

    async getById(resource, id, params = {}) {
        return this.get(`/${resource}/${id}`, params);
    }

    async create(resource, data) {
        return this.post(`/${resource}`, data);
    }

    async update(resource, id, data) {
        return this.put(`/${resource}/${id}`, data);
    }

    async remove(resource, id) {
        return this.delete(`/${resource}/${id}`);
    }

    /**
     * File upload methods
     */
    async uploadFile(file, resource = 'files', onProgress = null) {
        const formData = new FormData();
        formData.append('file', file);

        const options = {
            headers: {
                // Don't set Content-Type for FormData
                ...Object.fromEntries(
                    Object.entries(this.getDefaultHeaders()).filter(([key]) => key !== 'Content-Type')
                )
            }
        };

        if (onProgress) {
            // Add upload progress tracking
            const xhr = new XMLHttpRequest();

            return new Promise((resolve, reject) => {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        onProgress(Math.round((e.loaded / e.total) * 100));
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error(`Upload failed: ${xhr.status}`));
                    }
                });

                xhr.addEventListener('error', () => reject(new Error('Upload failed')));
                xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

                xhr.open('POST', `${this.baseURL}/${resource}`);
                const token = this.getAuthToken();
                if (token) {
                    xhr.setRequestHeader('Authorization', `Bearer ${token}`);
                }

                xhr.send(formData);
            });
        }

        return this.request('POST', `/${resource}`, formData, options);
    }

    /**
     * WebSocket connection for real-time features
     */
    connectWebSocket(path = '/ws') {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}${path}`;

        const token = this.getAuthToken();
        const ws = new WebSocket(`${wsUrl}?token=${token}`);

        return new Promise((resolve, reject) => {
            ws.onopen = () => resolve(ws);
            ws.onerror = (error) => reject(error);
        });
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Get default headers
     */
    getDefaultHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        const token = this.getAuthToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        return headers;
    }
}

// Create global API instance
const API = new APIClient();

// Make API globally available
window.API = API;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIClient;
}
