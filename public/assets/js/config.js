/**
 * TPT Free ERP - Configuration
 * Application configuration settings
 */

const CONFIG = {
    // API Configuration
    API: {
        BASE_URL: '/api/v1',
        TIMEOUT: 30000,
        RETRY_ATTEMPTS: 3,
        RETRY_DELAY: 1000
    },

    // Application Settings
    APP: {
        NAME: 'TPT Free ERP',
        VERSION: '1.0.0',
        ENVIRONMENT: 'development', // development, staging, production
        DEBUG: true,
        LOCALE: 'en-US',
        TIMEZONE: 'UTC'
    },

    // UI Configuration
    UI: {
        THEME: 'light', // light, dark, auto
        SIDEBAR_COLLAPSED: false,
        NOTIFICATIONS_ENABLED: true,
        ANIMATION_DURATION: 300,
        TOAST_DURATION: 5000
    },

    // Authentication
    AUTH: {
        TOKEN_KEY: 'tpt_erp_token',
        REFRESH_TOKEN_KEY: 'tpt_erp_refresh_token',
        USER_KEY: 'tpt_erp_user',
        SESSION_TIMEOUT: 3600000, // 1 hour
        REFRESH_THRESHOLD: 300000 // 5 minutes
    },

    // Cache Configuration
    CACHE: {
        ENABLED: true,
        TTL: 300000, // 5 minutes
        MAX_SIZE: 50 // Maximum cached items
    },

    // PWA Configuration
    PWA: {
        ENABLED: true,
        CACHE_NAME: 'tpt-erp-v1',
        OFFLINE_PAGE: '/offline.html'
    },

    // Feature Flags
    FEATURES: {
        DASHBOARD_WIDGETS: true,
        REAL_TIME_NOTIFICATIONS: true,
        OFFLINE_MODE: true,
        DARK_MODE: false,
        MULTI_LANGUAGE: false,
        ADVANCED_SEARCH: true,
        EXPORT_FUNCTIONALITY: true,
        BULK_OPERATIONS: true
    },

    // Module Configuration
    MODULES: {
        FINANCE: { enabled: true, permissions: ['read', 'write', 'admin'] },
        INVENTORY: { enabled: true, permissions: ['read', 'write', 'admin'] },
        SALES: { enabled: true, permissions: ['read', 'write', 'admin'] },
        HR: { enabled: true, permissions: ['read', 'write', 'admin'] },
        PROCUREMENT: { enabled: true, permissions: ['read', 'write', 'admin'] },
        MANUFACTURING: { enabled: true, permissions: ['read', 'write', 'admin'] },
        PROJECTS: { enabled: true, permissions: ['read', 'write', 'admin'] },
        QUALITY: { enabled: true, permissions: ['read', 'write', 'admin'] },
        ASSETS: { enabled: true, permissions: ['read', 'write', 'admin'] },
        FIELD_SERVICE: { enabled: true, permissions: ['read', 'write', 'admin'] },
        LMS: { enabled: true, permissions: ['read', 'write', 'admin'] },
        IOT: { enabled: true, permissions: ['read', 'write', 'admin'] },
        REPORTING: { enabled: true, permissions: ['read', 'write', 'admin'] }
    },

    // Validation Rules
    VALIDATION: {
        PASSWORD_MIN_LENGTH: 8,
        PASSWORD_REQUIRE_UPPERCASE: true,
        PASSWORD_REQUIRE_LOWERCASE: true,
        PASSWORD_REQUIRE_NUMBERS: true,
        PASSWORD_REQUIRE_SYMBOLS: false,
        EMAIL_PATTERN: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        PHONE_PATTERN: /^\+?[\d\s\-\(\)]+$/
    },

    // File Upload Configuration
    UPLOAD: {
        MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB
        ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        CHUNK_SIZE: 1024 * 1024 // 1MB chunks
    },

    // Data Table Configuration
    TABLE: {
        DEFAULT_PAGE_SIZE: 25,
        PAGE_SIZE_OPTIONS: [10, 25, 50, 100],
        SORTABLE: true,
        FILTERABLE: true,
        EXPORTABLE: true,
        SELECTABLE: true
    },

    // Notification Settings
    NOTIFICATIONS: {
        POSITION: 'top-right',
        TYPES: {
            SUCCESS: { duration: 5000, icon: 'check-circle' },
            ERROR: { duration: 7000, icon: 'exclamation-circle' },
            WARNING: { duration: 6000, icon: 'exclamation-triangle' },
            INFO: { duration: 5000, icon: 'info-circle' }
        }
    },

    // Chart Configuration
    CHART: {
        DEFAULT_TYPE: 'line',
        COLORS: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
        ANIMATION_DURATION: 1000,
        RESPONSIVE: true
    },

    // Search Configuration
    SEARCH: {
        DEBOUNCE_DELAY: 300,
        MIN_QUERY_LENGTH: 2,
        MAX_RESULTS: 50,
        HIGHLIGHT_RESULTS: true
    },

    // Date/Time Configuration
    DATETIME: {
        FORMAT: {
            DATE: 'YYYY-MM-DD',
            TIME: 'HH:mm:ss',
            DATETIME: 'YYYY-MM-DD HH:mm:ss',
            DISPLAY_DATE: 'MMM DD, YYYY',
            DISPLAY_TIME: 'HH:mm',
            DISPLAY_DATETIME: 'MMM DD, YYYY HH:mm'
        },
        TIMEZONE: 'UTC',
        LOCALE: 'en'
    },

    // Error Handling
    ERRORS: {
        LOG_TO_CONSOLE: true,
        LOG_TO_SERVER: true,
        SHOW_USER_FRIENDLY: true,
        RETRY_FAILED_REQUESTS: true
    },

    // Performance Monitoring
    PERFORMANCE: {
        ENABLED: true,
        SAMPLE_RATE: 0.1, // 10% of requests
        METRICS: ['page_load', 'api_response', 'user_interaction']
    }
};

// Environment-specific overrides
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    CONFIG.APP.ENVIRONMENT = 'development';
    CONFIG.APP.DEBUG = true;
    CONFIG.API.BASE_URL = 'http://localhost:8000/api/v1';
} else if (window.location.hostname.includes('staging')) {
    CONFIG.APP.ENVIRONMENT = 'staging';
    CONFIG.APP.DEBUG = false;
    CONFIG.API.BASE_URL = 'https://staging-api.tpterp.com/api/v1';
} else {
    CONFIG.APP.ENVIRONMENT = 'production';
    CONFIG.APP.DEBUG = false;
    CONFIG.API.BASE_URL = 'https://api.tpterp.com/api/v1';
}

// Make CONFIG globally available
window.CONFIG = CONFIG;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
}
