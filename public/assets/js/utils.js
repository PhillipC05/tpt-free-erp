/**
 * TPT Free ERP - Utility Functions
 * Common utility functions used throughout the application
 */

/**
 * DOM Utilities
 */
const DOM = {
    /**
     * Get element by selector
     */
    $(selector, context = document) {
        return context.querySelector(selector);
    },

    /**
     * Get elements by selector
     */
    $$(selector, context = document) {
        return Array.from(context.querySelectorAll(selector));
    },

    /**
     * Create element with attributes and content
     */
    create(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);

        // Set attributes
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'textContent') {
                element.textContent = value;
            } else if (key === 'innerHTML') {
                element.innerHTML = value;
            } else if (key.startsWith('on') && typeof value === 'function') {
                element.addEventListener(key.slice(2).toLowerCase(), value);
            } else {
                element.setAttribute(key, value);
            }
        });

        // Set content
        if (content && !attributes.textContent && !attributes.innerHTML) {
            element.textContent = content;
        }

        return element;
    },

    /**
     * Add event listener with delegation
     */
    on(event, selector, handler, context = document) {
        context.addEventListener(event, (e) => {
            if (e.target.matches(selector) || e.target.closest(selector)) {
                handler.call(e.target, e);
            }
        });
    },

    /**
     * Toggle class on element
     */
    toggleClass(element, className, force) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.toggle(className, force);
        }
    },

    /**
     * Add class to element
     */
    addClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.add(className);
        }
    },

    /**
     * Remove class from element
     */
    removeClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.classList.remove(className);
        }
    },

    /**
     * Check if element has class
     */
    hasClass(element, className) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        return element ? element.classList.contains(className) : false;
    },

    /**
     * Get or set element attribute
     */
    attr(element, name, value) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (!element) return null;

        if (value === undefined) {
            return element.getAttribute(name);
        } else {
            element.setAttribute(name, value);
            return element;
        }
    },

    /**
     * Show element
     */
    show(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.style.display = '';
        }
    },

    /**
     * Hide element
     */
    hide(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.style.display = 'none';
        }
    },

    /**
     * Toggle element visibility
     */
    toggle(element) {
        if (typeof element === 'string') {
            element = this.$(element);
        }
        if (element) {
            element.style.display = element.style.display === 'none' ? '' : 'none';
        }
    }
};

/**
 * String Utilities
 */
const StringUtils = {
    /**
     * Capitalize first letter
     */
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    },

    /**
     * Convert to camelCase
     */
    camelCase(str) {
        return str.replace(/[-_\s]+(.)?/g, (_, c) => c ? c.toUpperCase() : '');
    },

    /**
     * Convert to kebab-case
     */
    kebabCase(str) {
        return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    },

    /**
     * Convert to snake_case
     */
    snakeCase(str) {
        return str.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();
    },

    /**
     * Truncate string with ellipsis
     */
    truncate(str, length = 50, suffix = '...') {
        if (str.length <= length) return str;
        return str.substring(0, length - suffix.length) + suffix;
    },

    /**
     * Generate random string
     */
    random(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    /**
     * Slugify string
     */
    slugify(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

/**
 * Array Utilities
 */
const ArrayUtils = {
    /**
     * Remove duplicates from array
     */
    unique(arr) {
        return [...new Set(arr)];
    },

    /**
     * Shuffle array
     */
    shuffle(arr) {
        const shuffled = [...arr];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    },

    /**
     * Chunk array into smaller arrays
     */
    chunk(arr, size) {
        const chunks = [];
        for (let i = 0; i < arr.length; i += size) {
            chunks.push(arr.slice(i, i + size));
        }
        return chunks;
    },

    /**
     * Group array by key
     */
    groupBy(arr, key) {
        return arr.reduce((groups, item) => {
            const group = item[key];
            groups[group] = groups[group] || [];
            groups[group].push(item);
            return groups;
        }, {});
    },

    /**
     * Sort array by key
     */
    sortBy(arr, key, direction = 'asc') {
        return [...arr].sort((a, b) => {
            if (a[key] < b[key]) return direction === 'asc' ? -1 : 1;
            if (a[key] > b[key]) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    }
};

/**
 * Date/Time Utilities
 */
const DateUtils = {
    /**
     * Format date
     */
    format(date, format = 'YYYY-MM-DD') {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },

    /**
     * Get relative time
     */
    relativeTime(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }

        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;

        return this.format(date, 'MMM DD, YYYY');
    },

    /**
     * Add days to date
     */
    addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    },

    /**
     * Check if date is today
     */
    isToday(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    },

    /**
     * Check if date is yesterday
     */
    isYesterday(date) {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        return date.toDateString() === yesterday.toDateString();
    }
};

/**
 * Number Utilities
 */
const NumberUtils = {
    /**
     * Format number with commas
     */
    format(num, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    },

    /**
     * Format currency
     */
    currency(num, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(num);
    },

    /**
     * Format percentage
     */
    percentage(num, decimals = 1) {
        return `${this.format(num * 100, decimals)}%`;
    },

    /**
     * Clamp number between min and max
     */
    clamp(num, min, max) {
        return Math.min(Math.max(num, min), max);
    },

    /**
     * Round to nearest multiple
     */
    roundTo(num, multiple) {
        return Math.round(num / multiple) * multiple;
    }
};

/**
 * Validation Utilities
 */
const ValidationUtils = {
    /**
     * Validate email
     */
    isEmail(email) {
        return CONFIG.VALIDATION.EMAIL_PATTERN.test(email);
    },

    /**
     * Validate phone number
     */
    isPhone(phone) {
        return CONFIG.VALIDATION.PHONE_PATTERN.test(phone);
    },

    /**
     * Validate password strength
     */
    isStrongPassword(password) {
        if (password.length < CONFIG.VALIDATION.PASSWORD_MIN_LENGTH) return false;
        if (CONFIG.VALIDATION.PASSWORD_REQUIRE_UPPERCASE && !/[A-Z]/.test(password)) return false;
        if (CONFIG.VALIDATION.PASSWORD_REQUIRE_LOWERCASE && !/[a-z]/.test(password)) return false;
        if (CONFIG.VALIDATION.PASSWORD_REQUIRE_NUMBERS && !/\d/.test(password)) return false;
        if (CONFIG.VALIDATION.PASSWORD_REQUIRE_SYMBOLS && !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) return false;
        return true;
    },

    /**
     * Validate UUID
     */
    isUUID(uuid) {
        return CONFIG.VALIDATION.UUID_PATTERN.test(uuid);
    },

    /**
     * Validate required field
     */
    isRequired(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    },

    /**
     * Validate minimum length
     */
    minLength(value, min) {
        return String(value).length >= min;
    },

    /**
     * Validate maximum length
     */
    maxLength(value, max) {
        return String(value).length <= max;
    }
};

/**
 * Storage Utilities
 */
const StorageUtils = {
    /**
     * Get item from localStorage
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.warn('Error reading from localStorage:', e);
            return defaultValue;
        }
    },

    /**
     * Set item in localStorage
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.warn('Error writing to localStorage:', e);
            return false;
        }
    },

    /**
     * Remove item from localStorage
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.warn('Error removing from localStorage:', e);
            return false;
        }
    },

    /**
     * Clear all localStorage
     */
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (e) {
            console.warn('Error clearing localStorage:', e);
            return false;
        }
    }
};

/**
 * URL Utilities
 */
const URLUtils = {
    /**
     * Get query parameter
     */
    getParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },

    /**
     * Set query parameter
     */
    setParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.replaceState({}, '', url);
    },

    /**
     * Remove query parameter
     */
    removeParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.replaceState({}, '', url);
    },

    /**
     * Get all query parameters
     */
    getAllParams() {
        const params = {};
        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams) {
            params[key] = value;
        }
        return params;
    }
};

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
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
}

/**
 * Deep clone object
 */
function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    if (obj instanceof Date) return new Date(obj.getTime());
    if (obj instanceof Array) return obj.map(item => deepClone(item));
    if (typeof obj === 'object') {
        const cloned = {};
        Object.keys(obj).forEach(key => {
            cloned[key] = deepClone(obj[key]);
        });
        return cloned;
    }
}

/**
 * Check if object is empty
 */
function isEmpty(obj) {
    if (obj === null || obj === undefined) return true;
    if (typeof obj === 'string' || Array.isArray(obj)) return obj.length === 0;
    if (typeof obj === 'object') return Object.keys(obj).length === 0;
    return false;
}

/**
 * Generate unique ID
 */
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Make utilities globally available
window.DOM = DOM;
window.StringUtils = StringUtils;
window.ArrayUtils = ArrayUtils;
window.DateUtils = DateUtils;
window.NumberUtils = NumberUtils;
window.ValidationUtils = ValidationUtils;
window.StorageUtils = StorageUtils;
window.URLUtils = URLUtils;
window.debounce = debounce;
window.throttle = throttle;
window.deepClone = deepClone;
window.isEmpty = isEmpty;
window.generateId = generateId;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DOM,
        StringUtils,
        ArrayUtils,
        DateUtils,
        NumberUtils,
        ValidationUtils,
        StorageUtils,
        URLUtils,
        debounce,
        throttle,
        deepClone,
        isEmpty,
        generateId
    };
}
