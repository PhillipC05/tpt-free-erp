/**
 * TPT Free ERP - Data Formatter Utility
 * Comprehensive data formatting for dates, numbers, currencies, and text transformations
 */

class DataFormatter {
    constructor(options = {}) {
        this.options = {
            locale: 'en-US',
            timezone: 'UTC',
            currency: 'USD',
            dateFormat: 'MM/DD/YYYY',
            timeFormat: 'HH:mm:ss',
            numberFormat: {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            },
            ...options
        };

        this.init();
    }

    init() {
        // Initialize formatters
        this.dateFormatter = new Intl.DateTimeFormat(this.options.locale, {
            timeZone: this.options.timezone
        });

        this.numberFormatter = new Intl.NumberFormat(this.options.locale, this.options.numberFormat);

        this.currencyFormatter = new Intl.NumberFormat(this.options.locale, {
            style: 'currency',
            currency: this.options.currency
        });

        this.percentFormatter = new Intl.NumberFormat(this.options.locale, {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // ============================================================================
    // DATE AND TIME FORMATTING
    // ============================================================================

    formatDate(date, format = null) {
        if (!date) return '';

        const dateObj = this.parseDate(date);
        if (!dateObj) return '';

        format = format || this.options.dateFormat;

        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');

        switch (format.toUpperCase()) {
            case 'MM/DD/YYYY':
                return `${month}/${day}/${year}`;
            case 'DD/MM/YYYY':
                return `${day}/${month}/${year}`;
            case 'YYYY-MM-DD':
                return `${year}-${month}-${day}`;
            case 'DD-MM-YYYY':
                return `${day}-${month}-${year}`;
            case 'MM-DD-YYYY':
                return `${month}-${day}-${year}`;
            case 'YYYY/MM/DD':
                return `${year}/${month}/${day}`;
            case 'DD MMM YYYY':
                return `${day} ${this.getMonthName(dateObj.getMonth())} ${year}`;
            case 'MMM DD, YYYY':
                return `${this.getMonthName(dateObj.getMonth())} ${day}, ${year}`;
            default:
                return this.dateFormatter.format(dateObj);
        }
    }

    formatTime(time, format = null) {
        if (!time) return '';

        const dateObj = this.parseDate(time);
        if (!dateObj) return '';

        format = format || this.options.timeFormat;

        const hours = dateObj.getHours();
        const minutes = String(dateObj.getMinutes()).padStart(2, '0');
        const seconds = String(dateObj.getSeconds()).padStart(2, '0');

        switch (format.toUpperCase()) {
            case 'HH:MM:SS':
                return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
            case 'HH:MM':
                return `${String(hours).padStart(2, '0')}:${minutes}`;
            case 'H:MM:SS':
                return `${hours}:${minutes}:${seconds}`;
            case 'H:MM':
                return `${hours}:${minutes}`;
            case 'HH:MM:SS AM/PM':
                const period = hours >= 12 ? 'PM' : 'AM';
                const hour12 = hours % 12 || 12;
                return `${hour12}:${minutes}:${seconds} ${period}`;
            case 'HH:MM AM/PM':
                const period2 = hours >= 12 ? 'PM' : 'AM';
                const hour122 = hours % 12 || 12;
                return `${hour122}:${minutes} ${period2}`;
            default:
                return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
        }
    }

    formatDateTime(dateTime, options = {}) {
        if (!dateTime) return '';

        const dateObj = this.parseDate(dateTime);
        if (!dateObj) return '';

        const dateFormat = options.dateFormat || this.options.dateFormat;
        const timeFormat = options.timeFormat || this.options.timeFormat;
        const separator = options.separator || ' ';

        const dateStr = this.formatDate(dateObj, dateFormat);
        const timeStr = this.formatTime(dateObj, timeFormat);

        return `${dateStr}${separator}${timeStr}`;
    }

    formatRelativeTime(date, referenceDate = new Date()) {
        if (!date) return '';

        const dateObj = this.parseDate(date);
        if (!dateObj) return '';

        const diffInSeconds = Math.floor((referenceDate - dateObj) / 1000);

        if (diffInSeconds < 60) {
            return diffInSeconds <= 1 ? 'just now' : `${diffInSeconds} seconds ago`;
        }

        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) {
            return diffInMinutes === 1 ? '1 minute ago' : `${diffInMinutes} minutes ago`;
        }

        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) {
            return diffInHours === 1 ? '1 hour ago' : `${diffInHours} hours ago`;
        }

        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) {
            return diffInDays === 1 ? '1 day ago' : `${diffInDays} days ago`;
        }

        const diffInWeeks = Math.floor(diffInDays / 7);
        if (diffInWeeks < 4) {
            return diffInWeeks === 1 ? '1 week ago' : `${diffInWeeks} weeks ago`;
        }

        const diffInMonths = Math.floor(diffInDays / 30);
        if (diffInMonths < 12) {
            return diffInMonths === 1 ? '1 month ago' : `${diffInMonths} months ago`;
        }

        const diffInYears = Math.floor(diffInDays / 365);
        return diffInYears === 1 ? '1 year ago' : `${diffInYears} years ago`;
    }

    formatDuration(seconds, format = 'HH:MM:SS') {
        if (!seconds || seconds < 0) return '00:00:00';

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        switch (format.toUpperCase()) {
            case 'HH:MM:SS':
                return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            case 'MM:SS':
                return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            case 'H:MM:SS':
                return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            case 'H M':
                return `${hours}h ${minutes}m`;
            case 'M S':
                return `${minutes}m ${secs}s`;
            default:
                return `${hours}:${minutes}:${secs}`;
        }
    }

    // ============================================================================
    // NUMBER FORMATTING
    // ============================================================================

    formatNumber(number, options = {}) {
        if (number === null || number === undefined || isNaN(number)) return '0';

        const config = {
            minimumFractionDigits: options.minimumFractionDigits || this.options.numberFormat.minimumFractionDigits,
            maximumFractionDigits: options.maximumFractionDigits || this.options.numberFormat.maximumFractionDigits,
            ...options
        };

        const formatter = new Intl.NumberFormat(this.options.locale, config);
        return formatter.format(number);
    }

    formatCurrency(amount, currency = null, options = {}) {
        if (amount === null || amount === undefined || isNaN(amount)) return '$0.00';

        currency = currency || this.options.currency;

        const config = {
            style: 'currency',
            currency: currency,
            ...options
        };

        const formatter = new Intl.NumberFormat(this.options.locale, config);
        return formatter.format(amount);
    }

    formatPercent(value, options = {}) {
        if (value === null || value === undefined || isNaN(value)) return '0%';

        const config = {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            ...options
        };

        const formatter = new Intl.NumberFormat(this.options.locale, config);
        return formatter.format(value / 100);
    }

    formatFileSize(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    formatOrdinal(number) {
        if (number === null || number === undefined || isNaN(number)) return '';

        const num = Math.abs(number);
        const lastDigit = num % 10;
        const lastTwoDigits = num % 100;

        if (lastTwoDigits >= 11 && lastTwoDigits <= 13) {
            return number + 'th';
        }

        switch (lastDigit) {
            case 1:
                return number + 'st';
            case 2:
                return number + 'nd';
            case 3:
                return number + 'rd';
            default:
                return number + 'th';
        }
    }

    // ============================================================================
    // TEXT FORMATTING
    // ============================================================================

    formatText(text, options = {}) {
        if (!text) return '';

        let formatted = text;

        if (options.capitalize) {
            formatted = this.capitalize(formatted);
        }

        if (options.titleCase) {
            formatted = this.toTitleCase(formatted);
        }

        if (options.uppercase) {
            formatted = formatted.toUpperCase();
        }

        if (options.lowercase) {
            formatted = formatted.toLowerCase();
        }

        if (options.truncate && options.maxLength) {
            formatted = this.truncate(formatted, options.maxLength, options.suffix || '...');
        }

        if (options.slugify) {
            formatted = this.slugify(formatted);
        }

        return formatted;
    }

    capitalize(text) {
        if (!text) return '';
        return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
    }

    toTitleCase(text) {
        if (!text) return '';
        return text.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
    }

    truncate(text, maxLength, suffix = '...') {
        if (!text || text.length <= maxLength) return text;
        return text.substring(0, maxLength - suffix.length) + suffix;
    }

    slugify(text) {
        if (!text) return '';
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    formatPhoneNumber(phone, format = 'international') {
        if (!phone) return '';

        // Remove all non-digit characters
        const cleaned = phone.replace(/\D/g, '');

        switch (format) {
            case 'us':
                if (cleaned.length === 10) {
                    return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
                }
                break;
            case 'international':
                if (cleaned.length === 10) {
                    return `+1 (${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
                } else if (cleaned.length === 11 && cleaned.startsWith('1')) {
                    return `+${cleaned.slice(0, 1)} (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7)}`;
                }
                break;
            case 'dots':
                if (cleaned.length === 10) {
                    return `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6)}`;
                }
                break;
        }

        return phone; // Return original if no format matches
    }

    formatAddress(address) {
        if (!address) return '';

        const parts = [];
        if (address.street) parts.push(address.street);
        if (address.city) parts.push(address.city);
        if (address.state) parts.push(address.state);
        if (address.zipCode) parts.push(address.zipCode);
        if (address.country) parts.push(address.country);

        return parts.join(', ');
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    parseDate(date) {
        if (!date) return null;

        if (date instanceof Date) return date;

        if (typeof date === 'number') return new Date(date);

        if (typeof date === 'string') {
            // Try different date formats
            const parsed = new Date(date);
            return isNaN(parsed.getTime()) ? null : parsed;
        }

        return null;
    }

    getMonthName(monthIndex, format = 'long') {
        const date = new Date(2000, monthIndex, 1);
        return date.toLocaleDateString(this.options.locale, { month: format });
    }

    getWeekdayName(dayIndex, format = 'long') {
        const date = new Date(2000, 0, dayIndex + 1); // Sunday = 0
        return date.toLocaleDateString(this.options.locale, { weekday: format });
    }

    isValidDate(date) {
        const parsed = this.parseDate(date);
        return parsed !== null && !isNaN(parsed.getTime());
    }

    isToday(date) {
        const parsed = this.parseDate(date);
        if (!parsed) return false;

        const today = new Date();
        return parsed.toDateString() === today.toDateString();
    }

    isYesterday(date) {
        const parsed = this.parseDate(date);
        if (!parsed) return false;

        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        return parsed.toDateString() === yesterday.toDateString();
    }

    isTomorrow(date) {
        const parsed = this.parseDate(date);
        if (!parsed) return false;

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return parsed.toDateString() === tomorrow.toDateString();
    }

    // ============================================================================
    // PRESET FORMATTERS
    // ============================================================================

    static getPresets() {
        return {
            // US formats
            us: {
                locale: 'en-US',
                timezone: 'America/New_York',
                currency: 'USD',
                dateFormat: 'MM/DD/YYYY',
                timeFormat: 'HH:mm:ss'
            },

            // European formats
            eu: {
                locale: 'en-GB',
                timezone: 'Europe/London',
                currency: 'EUR',
                dateFormat: 'DD/MM/YYYY',
                timeFormat: 'HH:mm:ss'
            },

            // ISO formats
            iso: {
                locale: 'en-US',
                timezone: 'UTC',
                currency: 'USD',
                dateFormat: 'YYYY-MM-DD',
                timeFormat: 'HH:mm:ss'
            },

            // Short formats
            short: {
                locale: 'en-US',
                timezone: 'UTC',
                currency: 'USD',
                dateFormat: 'MM/DD/YY',
                timeFormat: 'HH:mm'
            }
        };
    }

    // ============================================================================
    // CONFIGURATION METHODS
    // ============================================================================

    setLocale(locale) {
        this.options.locale = locale;
        this.init();
    }

    setTimezone(timezone) {
        this.options.timezone = timezone;
        this.init();
    }

    setCurrency(currency) {
        this.options.currency = currency;
        this.init();
    }

    setDateFormat(format) {
        this.options.dateFormat = format;
    }

    setTimeFormat(format) {
        this.options.timeFormat = format;
    }

    // ============================================================================
    // EXPORT METHODS
    // ============================================================================

    toJSON() {
        return {
            options: this.options
        };
    }

    static fromJSON(data) {
        return new DataFormatter(data.options);
    }

    static createPreset(presetName) {
        const presets = DataFormatter.getPresets();
        const preset = presets[presetName];

        if (!preset) {
            throw new Error(`Preset '${presetName}' not found`);
        }

        return new DataFormatter(preset);
    }
}

// Export the utility
window.DataFormatter = DataFormatter;
