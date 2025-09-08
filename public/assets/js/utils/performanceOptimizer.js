/**
 * TPT Free ERP - Performance Optimizer Utility
 * Lazy loading, code splitting, bundle optimization, and virtual scrolling
 */

class PerformanceOptimizer {
    constructor(options = {}) {
        this.options = {
            enableLazyLoading: true,
            enableCodeSplitting: true,
            enableVirtualScrolling: true,
            enableBundleOptimization: true,
            lazyLoadThreshold: 200, // pixels
            virtualScrollItemHeight: 50,
            bundleSizeLimit: 244 * 1024, // 244KB
            preloadCritical: true,
            enableServiceWorker: true,
            ...options
        };

        this.lazyLoadObserver = null;
        this.virtualScrollInstances = new Map();
        this.bundleAnalyzer = new Map();
        this.preloadQueue = new Set();
        this.serviceWorker = null;

        this.init();
    }

    init() {
        if (this.options.enableLazyLoading) {
            this.setupLazyLoading();
        }

        if (this.options.enableVirtualScrolling) {
            this.setupVirtualScrolling();
        }

        if (this.options.enableBundleOptimization) {
            this.setupBundleOptimization();
        }

        if (this.options.enableServiceWorker) {
            this.setupServiceWorker();
        }

        if (this.options.preloadCritical) {
            this.preloadCriticalResources();
        }
    }

    // ============================================================================
    // LAZY LOADING SYSTEM
    // ============================================================================

    setupLazyLoading() {
        if (!('IntersectionObserver' in window)) {
            console.warn('IntersectionObserver not supported, falling back to scroll-based lazy loading');
            this.setupScrollBasedLazyLoading();
            return;
        }

        this.lazyLoadObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadLazyElement(entry.target);
                    this.lazyLoadObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: `${this.options.lazyLoadThreshold}px`
        });
    }

    setupScrollBasedLazyLoading() {
        let ticking = false;

        const checkLazyLoad = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.checkScrollLazyLoad();
                    ticking = false;
                });
                ticking = true;
            }
        };

        window.addEventListener('scroll', checkLazyLoad, { passive: true });
        window.addEventListener('resize', checkLazyLoad, { passive: true });

        // Initial check
        this.checkScrollLazyLoad();
    }

    checkScrollLazyLoad() {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        const viewportHeight = window.innerHeight;
        const scrollTop = window.pageYOffset;

        lazyElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const elementTop = rect.top + scrollTop;

            if (elementTop < scrollTop + viewportHeight + this.options.lazyLoadThreshold) {
                this.loadLazyElement(element);
            }
        });
    }

    async loadLazyElement(element) {
        const lazyType = element.dataset.lazy;
        const lazySrc = element.dataset.src;

        if (!lazySrc) return;

        try {
            switch (lazyType) {
                case 'image':
                    await this.loadLazyImage(element, lazySrc);
                    break;
                case 'component':
                    await this.loadLazyComponent(element, lazySrc);
                    break;
                case 'script':
                    await this.loadLazyScript(element, lazySrc);
                    break;
                case 'module':
                    await this.loadLazyModule(element, lazySrc);
                    break;
                default:
                    console.warn(`Unknown lazy load type: ${lazyType}`);
            }

            element.classList.add('lazy-loaded');
            element.classList.remove('lazy-loading');

            // Emit lazy load event
            if (window.EventManager) {
                window.EventManager.emit('performance:lazy-loaded', {
                    element,
                    type: lazyType,
                    src: lazySrc
                });
            }
        } catch (error) {
            console.error('Lazy loading failed:', error);
            element.classList.add('lazy-error');

            if (window.ErrorBoundary) {
                window.ErrorBoundary.handleAsyncError('lazy-loading', error);
            }
        }
    }

    async loadLazyImage(element, src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                element.src = src;
                element.classList.remove('lazy-placeholder');
                resolve();
            };
            img.onerror = reject;
            img.src = src;
        });
    }

    async loadLazyComponent(element, componentPath) {
        try {
            const module = await import(componentPath);
            const ComponentClass = module.default || module;

            if (typeof ComponentClass === 'function') {
                const component = new ComponentClass(element.dataset.props ? JSON.parse(element.dataset.props) : {});
                element.innerHTML = '';
                element.appendChild(component.render());
            }
        } catch (error) {
            throw new Error(`Failed to load component: ${componentPath}`);
        }
    }

    async loadLazyScript(element, src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.onload = resolve;
            script.onerror = reject;
            script.src = src;
            document.head.appendChild(script);
        });
    }

    async loadLazyModule(element, modulePath) {
        const module = await import(modulePath);
        element.dataset.module = JSON.stringify(module);
        return module;
    }

    observeLazyElement(element) {
        if (this.lazyLoadObserver) {
            this.lazyLoadObserver.observe(element);
        } else {
            // Fallback for scroll-based lazy loading
            element.classList.add('lazy-observed');
        }
    }

    // ============================================================================
    // CODE SPLITTING SYSTEM
    // ============================================================================

    setupCodeSplitting() {
        // Setup dynamic imports for route-based code splitting
        this.setupRouteSplitting();

        // Setup component-based code splitting
        this.setupComponentSplitting();
    }

    setupRouteSplitting() {
        // Intercept navigation to load route components dynamically
        if (window.Router) {
            const originalNavigate = window.Router.navigate;

            window.Router.navigate = async (route) => {
                const routeModule = await this.loadRouteModule(route);
                if (routeModule) {
                    originalNavigate.call(window.Router, route);
                }
            };
        }
    }

    async loadRouteModule(route) {
        const routeMap = {
            '/hr': () => import('../components/HR.js'),
            '/inventory': () => import('../components/Inventory.js'),
            '/sales': () => import('../components/Sales.js'),
            '/manufacturing': () => import('../components/Manufacturing.js'),
            '/procurement': () => import('../components/Procurement.js'),
            '/projects': () => import('../components/ProjectManagement.js'),
            '/quality': () => import('../components/QualityManagement.js'),
            '/assets': () => import('../components/AssetManagement.js'),
            '/field-service': () => import('../components/FieldService.js'),
            '/lms': () => import('../components/LMS.js'),
            '/iot': () => import('../components/IoT.js'),
            '/analytics': () => import('../components/AdvancedAnalytics.js')
        };

        const loader = routeMap[route];
        if (loader) {
            try {
                const module = await loader();
                return module;
            } catch (error) {
                console.error(`Failed to load route module: ${route}`, error);
                return null;
            }
        }

        return null;
    }

    setupComponentSplitting() {
        // Setup dynamic loading for heavy components
        this.componentMap = {
            'Chart': () => import('../components/Chart.js'),
            'DataTable': () => import('../components/DataTable.js'),
            'FormBuilder': () => import('../components/FormBuilder.js'),
            'AdvancedAnalytics': () => import('../components/AdvancedAnalytics.js')
        };
    }

    async loadComponent(componentName, container, props = {}) {
        const loader = this.componentMap[componentName];

        if (!loader) {
            throw new Error(`Component not found in split map: ${componentName}`);
        }

        try {
            const module = await loader();
            const ComponentClass = module.default || module;

            const component = new ComponentClass(props);
            container.innerHTML = '';
            container.appendChild(component.render());

            return component;
        } catch (error) {
            console.error(`Failed to load component: ${componentName}`, error);
            throw error;
        }
    }

    // ============================================================================
    // VIRTUAL SCROLLING SYSTEM
    // ============================================================================

    setupVirtualScrolling() {
        // Virtual scrolling is implemented per table/component
        // This provides the base functionality
    }

    createVirtualScroller(container, options = {}) {
        const config = {
            itemHeight: this.options.virtualScrollItemHeight,
            containerHeight: 400,
            overscan: 5,
            ...options
        };

        const scroller = new VirtualScroller(container, config);
        this.virtualScrollInstances.set(container, scroller);

        return scroller;
    }

    destroyVirtualScroller(container) {
        const scroller = this.virtualScrollInstances.get(container);
        if (scroller) {
            scroller.destroy();
            this.virtualScrollInstances.delete(container);
        }
    }
}

class VirtualScroller {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.items = [];
        this.scrollTop = 0;
        this.visibleRange = { start: 0, end: 0 };

        this.init();
    }

    init() {
        this.setupContainer();
        this.setupScrollHandler();
        this.render();
    }

    setupContainer() {
        this.container.style.height = `${this.options.containerHeight}px`;
        this.container.style.overflow = 'auto';
        this.container.style.position = 'relative';

        // Create viewport
        this.viewport = document.createElement('div');
        this.viewport.style.height = `${this.getTotalHeight()}px`;
        this.viewport.style.position = 'relative';

        // Create content area
        this.content = document.createElement('div');
        this.content.style.position = 'absolute';
        this.content.style.top = '0';
        this.content.style.left = '0';
        this.content.style.right = '0';

        this.viewport.appendChild(this.content);
        this.container.appendChild(this.viewport);
    }

    setupScrollHandler() {
        this.container.addEventListener('scroll', (e) => {
            this.scrollTop = e.target.scrollTop;
            this.updateVisibleRange();
            this.render();
        }, { passive: true });
    }

    setItems(items) {
        this.items = items;
        this.viewport.style.height = `${this.getTotalHeight()}px`;
        this.updateVisibleRange();
        this.render();
    }

    getTotalHeight() {
        return this.items.length * this.options.itemHeight;
    }

    updateVisibleRange() {
        const start = Math.floor(this.scrollTop / this.options.itemHeight);
        const visibleCount = Math.ceil(this.options.containerHeight / this.options.itemHeight);
        const end = Math.min(start + visibleCount + this.options.overscan, this.items.length);

        this.visibleRange = {
            start: Math.max(0, start - this.options.overscan),
            end: end
        };
    }

    render() {
        const visibleItems = this.items.slice(this.visibleRange.start, this.visibleRange.end);
        const offsetY = this.visibleRange.start * this.options.itemHeight;

        this.content.style.transform = `translateY(${offsetY}px)`;
        this.content.innerHTML = '';

        visibleItems.forEach((item, index) => {
            const itemElement = this.renderItem(item, this.visibleRange.start + index);
            this.content.appendChild(itemElement);
        });
    }

    renderItem(item, index) {
        const element = document.createElement('div');
        element.style.height = `${this.options.itemHeight}px`;
        element.style.display = 'flex';
        element.style.alignItems = 'center';
        element.style.padding = '0 16px';
        element.style.borderBottom = '1px solid #eee';

        if (typeof this.options.renderItem === 'function') {
            element.innerHTML = this.options.renderItem(item, index);
        } else {
            element.textContent = item.toString();
        }

        return element;
    }

    destroy() {
        if (this.container && this.viewport) {
            this.container.removeChild(this.viewport);
        }
        this.items = [];
    }
}

// ============================================================================
// BUNDLE OPTIMIZATION SYSTEM
// ============================================================================

class BundleAnalyzer {
    constructor() {
        this.bundles = new Map();
        this.totalSize = 0;
    }

    analyzeBundle(name, content) {
        const size = this.calculateSize(content);
        const gzipSize = this.estimateGzipSize(content);

        this.bundles.set(name, {
            size,
            gzipSize,
            modules: this.extractModules(content),
            lastAnalyzed: new Date()
        });

        this.totalSize += size;

        return {
            name,
            size,
            gzipSize,
            efficiency: this.calculateEfficiency(size)
        };
    }

    calculateSize(content) {
        if (typeof content === 'string') {
            return new Blob([content]).size;
        }
        return content.length || 0;
    }

    estimateGzipSize(content) {
        // Rough estimation - in practice you'd use actual gzip compression
        return Math.round(this.calculateSize(content) * 0.3);
    }

    extractModules(content) {
        // Extract module names from bundle (simplified)
        const moduleRegex = /define\("([^"]+)"/g;
        const modules = [];
        let match;

        while ((match = moduleRegex.exec(content)) !== null) {
            modules.push(match[1]);
        }

        return modules;
    }

    calculateEfficiency(size) {
        const limit = 244 * 1024; // 244KB
        return Math.max(0, (limit - size) / limit * 100);
    }

    getBundleReport() {
        const report = {
            totalBundles: this.bundles.size,
            totalSize: this.totalSize,
            bundles: Array.from(this.bundles.entries()).map(([name, data]) => ({
                name,
                size: data.size,
                gzipSize: data.gzipSize,
                moduleCount: data.modules.length,
                efficiency: this.calculateEfficiency(data.size)
            }))
        };

        return report;
    }

    optimizeBundle(content, options = {}) {
        let optimized = content;

        // Remove comments
        optimized = optimized.replace(/\/\*[\s\S]*?\*\//g, '');
        optimized = optimized.replace(/\/\/.*$/gm, '');

        // Remove whitespace
        optimized = optimized.replace(/\s+/g, ' ');
        optimized = optimized.replace(/\s*([{}();,])\s*/g, '$1');

        // Remove unused code (simplified)
        if (options.removeConsole) {
            optimized = optimized.replace(/console\.\w+\([^)]*\);?/g, '');
        }

        return optimized;
    }
}

// ============================================================================
// SERVICE WORKER SYSTEM
// ============================================================================

class ServiceWorkerManager {
    constructor() {
        this.registration = null;
        this.cacheName = 'tpt-erp-v1';
    }

    async register() {
        if ('serviceWorker' in navigator) {
            try {
                this.registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered successfully');

                this.registration.addEventListener('updatefound', () => {
                    const newWorker = this.registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });

                return true;
            } catch (error) {
                console.error('Service Worker registration failed:', error);
                return false;
            }
        }

        return false;
    }

    showUpdateNotification() {
        // Show update available notification
        if (window.App && window.App.showNotification) {
            window.App.showNotification({
                type: 'info',
                title: 'Update Available',
                message: 'A new version is available. Refresh to update.',
                action: {
                    label: 'Refresh',
                    callback: () => window.location.reload()
                }
            });
        }
    }

    async update() {
        if (this.registration) {
            await this.registration.update();
        }
    }

    async unregister() {
        if (this.registration) {
            await this.registration.unregister();
        }
    }
}

// ============================================================================
// PRELOADING SYSTEM
// ============================================================================

class ResourcePreloader {
    constructor() {
        this.preloaded = new Set();
        this.preloadQueue = [];
    }

    preloadCriticalResources() {
        const criticalResources = [
            '/assets/css/main.css',
            '/assets/js/app.js',
            '/assets/js/components/Dashboard.js',
            '/assets/js/utils/baseComponent.js',
            '/assets/js/utils/apiClient.js'
        ];

        criticalResources.forEach(resource => {
            this.preloadResource(resource);
        });
    }

    async preloadResource(url, priority = 'high') {
        if (this.preloaded.has(url)) {
            return;
        }

        try {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = url;

            if (url.endsWith('.js')) {
                link.as = 'script';
            } else if (url.endsWith('.css')) {
                link.as = 'style';
            } else if (url.match(/\.(png|jpg|jpeg|gif|svg|webp)$/)) {
                link.as = 'image';
            }

            if (priority === 'high') {
                link.setAttribute('fetchpriority', 'high');
            }

            document.head.appendChild(link);
            this.preloaded.add(url);

            return new Promise((resolve, reject) => {
                link.onload = resolve;
                link.onerror = reject;
            });
        } catch (error) {
            console.warn(`Failed to preload resource: ${url}`, error);
        }
    }

    async preloadOnIdle(url) {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => this.preloadResource(url, 'low'));
        } else {
            setTimeout(() => this.preloadResource(url, 'low'), 1000);
        }
    }

    async preloadOnVisible(url) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.preloadResource(url);
                    observer.disconnect();
                }
            });
        });

        // Observe a placeholder element or the entire document
        observer.observe(document.body);
    }
}

// ============================================================================
// MAIN CLASS EXTENSIONS
// ============================================================================

// Add bundle analyzer to PerformanceOptimizer
PerformanceOptimizer.prototype.BundleAnalyzer = BundleAnalyzer;
PerformanceOptimizer.prototype.ServiceWorkerManager = ServiceWorkerManager;
PerformanceOptimizer.prototype.ResourcePreloader = ResourcePreloader;

// Setup bundle optimization
PerformanceOptimizer.prototype.setupBundleOptimization = function() {
    this.bundleAnalyzer = new BundleAnalyzer();

    // Analyze current bundle
    if (window.performance && window.performance.getEntriesByType) {
        const resources = window.performance.getEntriesByType('resource');
        resources.forEach(resource => {
            if (resource.name.includes('.js')) {
                this.bundleAnalyzer.analyzeBundle(resource.name, { size: resource.transferSize });
            }
        });
    }
};

// Setup service worker
PerformanceOptimizer.prototype.setupServiceWorker = function() {
    this.serviceWorker = new ServiceWorkerManager();
    this.serviceWorker.register();
};

// Preload critical resources
PerformanceOptimizer.prototype.preloadCriticalResources = function() {
    const preloader = new ResourcePreloader();
    preloader.preloadCriticalResources();
};

// ============================================================================
// UTILITY METHODS
// ============================================================================

PerformanceOptimizer.prototype.getPerformanceMetrics = function() {
    const metrics = {
        lazyLoadedElements: document.querySelectorAll('.lazy-loaded').length,
        virtualScrollInstances: this.virtualScrollInstances.size,
        bundleAnalysis: this.bundleAnalyzer ? this.bundleAnalyzer.getBundleReport() : null,
        serviceWorkerStatus: this.serviceWorker ? 'registered' : 'not registered'
    };

    // Add timing metrics
    if (window.performance && window.performance.timing) {
        const timing = window.performance.timing;
        metrics.pageLoadTime = timing.loadEventEnd - timing.navigationStart;
        metrics.domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;
    }

    return metrics;
};

PerformanceOptimizer.prototype.optimizeImages = function(container = document) {
    const images = container.querySelectorAll('img[data-src]');

    images.forEach(img => {
        if (!img.dataset.optimized) {
            // Add responsive image attributes
            if (img.dataset.sizes) {
                img.sizes = img.dataset.sizes;
            }

            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }

            // Add loading="lazy" if not already present
            if (!img.hasAttribute('loading')) {
                img.loading = 'lazy';
            }

            img.dataset.optimized = 'true';
        }
    });
};

PerformanceOptimizer.prototype.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

PerformanceOptimizer.prototype.throttle = function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    };

    PerformanceOptimizer.prototype.memoize = function(func) {
        const cache = new Map();
        return function(...args) {
            const key = JSON.stringify(args);
            if (cache.has(key)) {
                return cache.get(key);
            }
            const result = func.apply(this, args);
            cache.set(key, result);
            return result;
        };
    };

// Export the utility
window.PerformanceOptimizer = PerformanceOptimizer;
