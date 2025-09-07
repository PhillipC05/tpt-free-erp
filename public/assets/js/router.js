/**
 * TPT Free ERP - Router
 * Client-side routing system for single-page application
 */

class Router {
    constructor() {
        this.routes = new Map();
        this.currentRoute = null;
        this.middlewares = [];
        this.history = [];
        this.historyIndex = -1;
        this.isNavigating = false;

        // Bind methods
        this.handlePopState = this.handlePopState.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);

        this.init();
    }

    /**
     * Initialize the router
     */
    init() {
        // Listen for browser navigation
        window.addEventListener('popstate', this.handlePopState);

        // Listen for link clicks
        document.addEventListener('click', this.handleLinkClick);

        // Handle initial route
        this.handleInitialRoute();
    }

    /**
     * Add a route
     */
    addRoute(path, handler, options = {}) {
        // Convert route pattern to regex
        const regex = this.pathToRegex(path);
        const route = {
            path,
            regex,
            handler,
            params: [],
            meta: options.meta || {},
            guards: options.guards || [],
            beforeEnter: options.beforeEnter,
            beforeLeave: options.beforeLeave
        };

        // Extract parameter names
        const paramNames = path.match(/:([^\/]+)/g);
        if (paramNames) {
            route.params = paramNames.map(name => name.slice(1));
        }

        this.routes.set(path, route);
        return this;
    }

    /**
     * Remove a route
     */
    removeRoute(path) {
        this.routes.delete(path);
        return this;
    }

    /**
     * Navigate to a route
     */
    async navigate(path, options = {}) {
        if (this.isNavigating) {
            console.warn('Navigation already in progress');
            return;
        }

        this.isNavigating = true;

        try {
            const route = this.findRoute(path);
            if (!route) {
                throw new Error(`Route not found: ${path}`);
            }

            // Check route guards
            if (!(await this.checkGuards(route, path))) {
                return;
            }

            // Call beforeLeave hook
            if (this.currentRoute && this.currentRoute.beforeLeave) {
                const result = await this.currentRoute.beforeLeave(this.currentRoute, route);
                if (result === false) {
                    return;
                }
            }

            // Call beforeEnter hook
            if (route.beforeEnter) {
                const result = await route.beforeEnter(route, this.currentRoute);
                if (result === false) {
                    return;
                }
            }

            // Extract parameters
            const params = this.extractParams(path, route);
            const query = this.parseQuery(window.location.search);

            // Update current route
            const previousRoute = this.currentRoute;
            this.currentRoute = {
                ...route,
                params,
                query,
                fullPath: path,
                meta: { ...route.meta }
            };

            // Update browser history
            if (!options.replace) {
                window.history.pushState(
                    { path, route: this.currentRoute },
                    '',
                    path
                );

                // Add to internal history
                this.history = this.history.slice(0, this.historyIndex + 1);
                this.history.push(path);
                this.historyIndex = this.history.length - 1;
            } else {
                window.history.replaceState(
                    { path, route: this.currentRoute },
                    '',
                    path
                );
            }

            // Update active navigation links
            this.updateActiveLinks(path);

            // Call route handler
            await this.callHandler(route, {
                params,
                query,
                fullPath: path,
                meta: route.meta,
                from: previousRoute
            });

            // Update page title
            this.updatePageTitle(route.meta.title);

            // Scroll to top or specified position
            if (options.scroll !== false) {
                window.scrollTo(0, 0);
            }

        } catch (error) {
            console.error('Navigation error:', error);
            this.handleNavigationError(error, path);
        } finally {
            this.isNavigating = false;
        }
    }

    /**
     * Go back in history
     */
    goBack() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            const path = this.history[this.historyIndex];
            this.navigate(path, { replace: true });
        }
    }

    /**
     * Go forward in history
     */
    goForward() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const path = this.history[this.historyIndex];
            this.navigate(path, { replace: true });
        }
    }

    /**
     * Replace current route
     */
    replace(path) {
        return this.navigate(path, { replace: true });
    }

    /**
     * Find route by path
     */
    findRoute(path) {
        for (const route of this.routes.values()) {
            if (route.regex.test(path)) {
                return route;
            }
        }
        return null;
    }

    /**
     * Convert path pattern to regex
     */
    pathToRegex(path) {
        return new RegExp(
            '^' +
            path
                .replace(/:([^\/]+)/g, '([^\/]+)') // Replace :param with capture group
                .replace(/\*/g, '.*') // Replace * with wildcard
                .replace(/\//g, '\\/') + // Escape forward slashes
            '$'
        );
    }

    /**
     * Extract parameters from path
     */
    extractParams(path, route) {
        const matches = path.match(route.regex);
        const params = {};

        if (matches && route.params.length > 0) {
            route.params.forEach((param, index) => {
                params[param] = matches[index + 1];
            });
        }

        return params;
    }

    /**
     * Parse query string
     */
    parseQuery(queryString) {
        const query = {};
        const params = new URLSearchParams(queryString);

        for (const [key, value] of params) {
            // Handle array parameters
            if (query[key]) {
                if (Array.isArray(query[key])) {
                    query[key].push(value);
                } else {
                    query[key] = [query[key], value];
                }
            } else {
                query[key] = value;
            }
        }

        return query;
    }

    /**
     * Check route guards
     */
    async checkGuards(route, path) {
        for (const guard of route.guards) {
            try {
                const result = await guard(route, this.currentRoute);
                if (result === false) {
                    return false;
                }
                if (typeof result === 'string') {
                    // Redirect to another route
                    this.navigate(result);
                    return false;
                }
            } catch (error) {
                console.error('Guard error:', error);
                return false;
            }
        }
        return true;
    }

    /**
     * Call route handler
     */
    async callHandler(route, context) {
        try {
            // Run middlewares
            for (const middleware of this.middlewares) {
                await middleware(context);
            }

            // Call route handler
            if (typeof route.handler === 'function') {
                await route.handler(context);
            } else if (typeof route.handler === 'string') {
                // Load component
                await this.loadComponent(route.handler, context);
            }
        } catch (error) {
            console.error('Handler error:', error);
            throw error;
        }
    }

    /**
     * Load component dynamically
     */
    async loadComponent(componentName, context) {
        try {
            // This would load components dynamically
            // For now, we'll use a simple component loader
            const componentLoader = window.ComponentLoader || this.defaultComponentLoader;
            await componentLoader.load(componentName, context);
        } catch (error) {
            console.error('Component loading error:', error);
            throw error;
        }
    }

    /**
     * Default component loader
     */
    defaultComponentLoader = {
        async load(componentName, context) {
            // Simple component loading - replace with your component system
            const mainContent = DOM.$('#main-content');
            if (mainContent) {
                mainContent.innerHTML = `<div class="loading">Loading ${componentName}...</div>`;
            }
        }
    };

    /**
     * Handle browser back/forward navigation
     */
    handlePopState(event) {
        if (event.state && event.state.path) {
            this.navigate(event.state.path, { replace: true });
        }
    }

    /**
     * Handle link clicks for SPA navigation
     */
    handleLinkClick(event) {
        const link = event.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');

        // Check if it's an internal link
        if (this.isInternalLink(href)) {
            event.preventDefault();
            this.navigate(href);
        }
    }

    /**
     * Check if link is internal
     */
    isInternalLink(href) {
        if (!href) return false;

        // Handle absolute URLs
        if (href.startsWith('http')) {
            return href.startsWith(window.location.origin);
        }

        // Handle relative URLs
        return !href.startsWith('#') && !href.startsWith('mailto:') && !href.includes('://');
    }

    /**
     * Handle initial route on page load
     */
    handleInitialRoute() {
        const path = window.location.pathname + window.location.search;
        this.navigate(path, { replace: true });
    }

    /**
     * Update active navigation links
     */
    updateActiveLinks(currentPath) {
        // Remove active class from all links
        DOM.$$('.nav-link').forEach(link => {
            DOM.removeClass(link, 'active');
        });

        // Add active class to current route links
        DOM.$$(`.nav-link[data-route="${currentPath}"]`).forEach(link => {
            DOM.addClass(link, 'active');
        });

        // Handle nested routes
        const pathSegments = currentPath.split('/').filter(Boolean);
        for (let i = pathSegments.length; i > 0; i--) {
            const partialPath = '/' + pathSegments.slice(0, i).join('/');
            DOM.$$(`.nav-link[data-route="${partialPath}"]`).forEach(link => {
                DOM.addClass(link, 'active');
            });
        }
    }

    /**
     * Update page title
     */
    updatePageTitle(title) {
        if (title) {
            document.title = `${title} - ${CONFIG.APP.NAME}`;
        } else {
            document.title = CONFIG.APP.NAME;
        }
    }

    /**
     * Handle navigation errors
     */
    handleNavigationError(error, path) {
        // Try to navigate to 404 page
        if (path !== '/404') {
            this.navigate('/404', { replace: true });
        } else {
            // Show error in main content
            const mainContent = DOM.$('#main-content');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="error-page">
                        <h1>Navigation Error</h1>
                        <p>${error.message}</p>
                        <button onclick="Router.navigate('/')">Go Home</button>
                    </div>
                `;
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
     * Add route guard
     */
    guard(guard) {
        // Add global guard
        this.globalGuards = this.globalGuards || [];
        this.globalGuards.push(guard);
        return this;
    }

    /**
     * Get current route
     */
    getCurrentRoute() {
        return this.currentRoute;
    }

    /**
     * Get route history
     */
    getHistory() {
        return [...this.history];
    }

    /**
     * Clear route history
     */
    clearHistory() {
        this.history = [];
        this.historyIndex = -1;
    }

    /**
     * Destroy router
     */
    destroy() {
        window.removeEventListener('popstate', this.handlePopState);
        document.removeEventListener('click', this.handleLinkClick);
        this.routes.clear();
        this.middlewares = [];
        this.clearHistory();
    }
}

// Create global router instance
const Router = new Router();

// Make router globally available
window.Router = Router;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Router;
}
