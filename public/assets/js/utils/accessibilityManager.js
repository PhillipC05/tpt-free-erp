/**
 * TPT Free ERP - Accessibility Manager Utility
 * ARIA labels, keyboard navigation, screen reader support, and color contrast
 */

class AccessibilityManager {
    constructor(options = {}) {
        this.options = {
            enableAriaLabels: true,
            enableKeyboardNavigation: true,
            enableScreenReaderSupport: true,
            enableColorContrast: true,
            enableFocusManagement: true,
            enableSkipLinks: true,
            announceDynamicContent: true,
            highContrastMode: false,
            reducedMotion: false,
            ...options
        };

        this.focusableElements = [];
        this.skipLinks = new Map();
        this.liveRegions = new Map();
        this.ariaLabels = new Map();
        this.colorContrastCache = new Map();

        this.init();
    }

    init() {
        this.detectUserPreferences();
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupScreenReaderSupport();
        this.setupColorContrast();
        this.setupSkipLinks();
        this.setupLiveRegions();

        // Listen for preference changes
        this.setupPreferenceListeners();
    }

    // ============================================================================
    // USER PREFERENCE DETECTION
    // ============================================================================

    detectUserPreferences() {
        // Detect high contrast mode
        if (window.matchMedia) {
            const highContrastQuery = window.matchMedia('(prefers-contrast: high)');
            this.options.highContrastMode = highContrastQuery.matches;

            highContrastQuery.addEventListener('change', (e) => {
                this.options.highContrastMode = e.matches;
                this.handleHighContrastChange(e.matches);
            });
        }

        // Detect reduced motion preference
        if (window.matchMedia) {
            const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.options.reducedMotion = reducedMotionQuery.matches;

            reducedMotionQuery.addEventListener('change', (e) => {
                this.options.reducedMotion = e.matches;
                this.handleReducedMotionChange(e.matches);
            });
        }

        // Detect color scheme preference
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            this.isDarkMode = darkModeQuery.matches;

            darkModeQuery.addEventListener('change', (e) => {
                this.isDarkMode = e.matches;
                this.handleColorSchemeChange(e.matches);
            });
        }
    }

    setupPreferenceListeners() {
        // Listen for accessibility preference changes
        if ('matchMedia' in window) {
            // High contrast
            const contrastQuery = window.matchMedia('(prefers-contrast: high)');
            contrastQuery.addEventListener('change', (e) => {
                this.emit('contrast-changed', { highContrast: e.matches });
            });

            // Reduced motion
            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            motionQuery.addEventListener('change', (e) => {
                this.emit('motion-changed', { reducedMotion: e.matches });
            });

            // Color scheme
            const colorQuery = window.matchMedia('(prefers-color-scheme: dark)');
            colorQuery.addEventListener('change', (e) => {
                this.emit('color-scheme-changed', { darkMode: e.matches });
            });
        }
    }

    handleHighContrastChange(enabled) {
        document.documentElement.classList.toggle('high-contrast', enabled);

        if (window.EventManager) {
            window.EventManager.emit('accessibility:high-contrast-changed', { enabled });
        }
    }

    handleReducedMotionChange(enabled) {
        document.documentElement.classList.toggle('reduced-motion', enabled);

        if (window.EventManager) {
            window.EventManager.emit('accessibility:motion-changed', { enabled });
        }
    }

    handleColorSchemeChange(isDark) {
        document.documentElement.classList.toggle('dark-mode', isDark);

        if (window.EventManager) {
            window.EventManager.emit('accessibility:color-scheme-changed', { isDark });
        }
    }

    // ============================================================================
    // ARIA LABELS AND ROLES
    // ============================================================================

    setupAriaLabels() {
        if (!this.options.enableAriaLabels) return;

        // Add ARIA labels to common elements
        this.addAriaToButtons();
        this.addAriaToForms();
        this.addAriaToNavigation();
        this.addAriaToTables();
        this.addAriaToModals();
    }

    addAriaToButtons() {
        const buttons = document.querySelectorAll('button:not([aria-label]):not([aria-labelledby])');

        buttons.forEach(button => {
            if (!button.textContent.trim() && !button.querySelector('img, svg, .icon')) {
                const ariaLabel = this.generateAriaLabel(button);
                if (ariaLabel) {
                    button.setAttribute('aria-label', ariaLabel);
                }
            }
        });
    }

    addAriaToForms() {
        const inputs = document.querySelectorAll('input:not([aria-label]):not([aria-labelledby])');
        const textareas = document.querySelectorAll('textarea:not([aria-label]):not([aria-labelledby])');
        const selects = document.querySelectorAll('select:not([aria-label]):not([aria-labelledby])');

        [...inputs, ...textareas, ...selects].forEach(element => {
            if (!element.getAttribute('aria-label') && !element.getAttribute('aria-labelledby')) {
                const label = this.findAssociatedLabel(element);
                if (!label) {
                    const ariaLabel = this.generateAriaLabel(element);
                    if (ariaLabel) {
                        element.setAttribute('aria-label', ariaLabel);
                    }
                }
            }
        });
    }

    addAriaToNavigation() {
        const navs = document.querySelectorAll('nav:not([aria-label])');

        navs.forEach(nav => {
            if (!nav.getAttribute('aria-label')) {
                nav.setAttribute('aria-label', 'Navigation menu');
            }
        });

        // Add ARIA labels to navigation links
        const navLinks = document.querySelectorAll('nav a:not([aria-label])');
        navLinks.forEach(link => {
            if (!link.textContent.trim()) {
                link.setAttribute('aria-label', 'Navigation link');
            }
        });
    }

    addAriaToTables() {
        const tables = document.querySelectorAll('table:not([aria-label])');

        tables.forEach(table => {
            if (!table.getAttribute('aria-label') && !table.getAttribute('aria-labelledby')) {
                const caption = table.querySelector('caption');
                if (caption) {
                    table.setAttribute('aria-labelledby', caption.id || this.generateId(caption));
                } else {
                    table.setAttribute('aria-label', 'Data table');
                }
            }
        });
    }

    addAriaToModals() {
        const modals = document.querySelectorAll('[role="dialog"]:not([aria-modal])');

        modals.forEach(modal => {
            modal.setAttribute('aria-modal', 'true');

            // Add aria-labelledby if there's a title
            const title = modal.querySelector('h1, h2, h3, [role="heading"]');
            if (title && !modal.getAttribute('aria-labelledby')) {
                modal.setAttribute('aria-labelledby', title.id || this.generateId(title));
            }

            // Add aria-describedby if there's descriptive content
            const description = modal.querySelector('.modal-description, .dialog-description');
            if (description && !modal.getAttribute('aria-describedby')) {
                modal.setAttribute('aria-describedby', description.id || this.generateId(description));
            }
        });
    }

    generateAriaLabel(element) {
        // Generate meaningful ARIA labels based on element context
        const id = element.id;
        const classes = Array.from(element.classList);
        const dataAttributes = Object.keys(element.dataset);

        // Check for common patterns
        if (classes.includes('btn-close') || classes.includes('close-button')) {
            return 'Close';
        }

        if (classes.includes('btn-menu') || classes.includes('menu-toggle')) {
            return 'Toggle menu';
        }

        if (element.type === 'search') {
            return 'Search';
        }

        if (element.type === 'email') {
            return 'Email address';
        }

        if (dataAttributes.includes('tooltip')) {
            return element.dataset.tooltip;
        }

        // Generate based on ID or context
        if (id) {
            return this.idToLabel(id);
        }

        return null;
    }

    findAssociatedLabel(element) {
        // Find associated label element
        const id = element.id;
        if (id) {
            return document.querySelector(`label[for="${id}"]`);
        }

        // Check parent for label
        let parent = element.parentElement;
        while (parent && parent !== document.body) {
            const label = parent.querySelector('label');
            if (label) return label;
            parent = parent.parentElement;
        }

        return null;
    }

    idToLabel(id) {
        // Convert camelCase or kebab-case IDs to readable labels
        return id
            .replace(/([a-z])([A-Z])/g, '$1 $2') // camelCase to spaces
            .replace(/-/g, ' ') // kebab-case to spaces
            .replace(/\b\w/g, l => l.toUpperCase()) // Capitalize first letter of each word
            .trim();
    }

    generateId(element) {
        const id = `aria-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        element.id = id;
        return id;
    }

    // ============================================================================
    // KEYBOARD NAVIGATION
    // ============================================================================

    setupKeyboardNavigation() {
        if (!this.options.enableKeyboardNavigation) return;

        document.addEventListener('keydown', this.handleKeydown.bind(this));
        document.addEventListener('focusin', this.handleFocusIn.bind(this));
        document.addEventListener('focusout', this.handleFocusOut.bind(this));
    }

    handleKeydown(event) {
        const { key, target, ctrlKey, altKey, shiftKey } = event;

        // Skip if user is typing in an input
        if (this.isTypingElement(target)) return;

        switch (key) {
            case 'Tab':
                this.handleTabNavigation(event);
                break;
            case 'Enter':
            case ' ':
                this.handleActivation(event);
                break;
            case 'Escape':
                this.handleEscape(event);
                break;
            case 'ArrowUp':
            case 'ArrowDown':
            case 'ArrowLeft':
            case 'ArrowRight':
                this.handleArrowNavigation(event);
                break;
            case 'Home':
            case 'End':
                this.handleHomeEndNavigation(event);
                break;
            case 'PageUp':
            case 'PageDown':
                this.handlePageNavigation(event);
                break;
        }

        // Handle skip links
        if (altKey && key >= '1' && key <= '9') {
            this.activateSkipLink(parseInt(key) - 1);
        }
    }

    isTypingElement(element) {
        const typingElements = ['input', 'textarea', 'select'];
        return typingElements.includes(element.tagName.toLowerCase()) ||
               element.contentEditable === 'true' ||
               element.getAttribute('role') === 'textbox';
    }

    handleTabNavigation(event) {
        // Enhanced tab navigation with focus trapping for modals
        const activeModal = document.querySelector('[role="dialog"][aria-modal="true"]');

        if (activeModal) {
            this.trapFocus(activeModal, event);
        }
    }

    handleActivation(event) {
        const target = event.target;

        // Handle custom elements that should be activated with Enter/Space
        if (target.getAttribute('role') === 'button' ||
            target.getAttribute('role') === 'menuitem' ||
            target.classList.contains('clickable')) {

            event.preventDefault();
            target.click();
        }
    }

    handleEscape(event) {
        // Close modals, menus, etc.
        const activeModal = document.querySelector('[role="dialog"][aria-modal="true"]');
        const activeMenu = document.querySelector('[role="menu"][aria-expanded="true"]');

        if (activeModal) {
            this.closeModal(activeModal);
        } else if (activeMenu) {
            this.closeMenu(activeMenu);
        }
    }

    handleArrowNavigation(event) {
        const target = event.target;

        // Handle navigation in lists, menus, etc.
        if (target.closest('[role="menu"]') ||
            target.closest('[role="listbox"]') ||
            target.closest('[role="tablist"]')) {

            event.preventDefault();
            this.navigateWithArrows(target, event.key);
        }
    }

    handleHomeEndNavigation(event) {
        const target = event.target;

        if (target.closest('[role="menu"]') ||
            target.closest('[role="listbox"]') ||
            target.closest('[role="tablist"]')) {

            event.preventDefault();
            this.navigateToEnd(target, event.key === 'Home' ? 'first' : 'last');
        }
    }

    handlePageNavigation(event) {
        // Handle page up/down in long lists
        const target = event.target;

        if (target.closest('[role="listbox"]')) {
            event.preventDefault();
            this.pageNavigate(target, event.key === 'PageUp' ? -5 : 5);
        }
    }

    trapFocus(container, event) {
        const focusableElements = this.getFocusableElements(container);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    }

    getFocusableElements(container) {
        const selectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
            '[contenteditable="true"]'
        ];

        return Array.from(container.querySelectorAll(selectors.join(', ')))
            .filter(element => {
                const style = window.getComputedStyle(element);
                return style.display !== 'none' && style.visibility !== 'hidden';
            });
    }

    navigateWithArrows(element, direction) {
        const container = element.closest('[role="menu"], [role="listbox"], [role="tablist"]');
        if (!container) return;

        const items = this.getFocusableElements(container);
        const currentIndex = items.indexOf(element);

        let nextIndex;
        switch (direction) {
            case 'ArrowUp':
            case 'ArrowLeft':
                nextIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                break;
            case 'ArrowDown':
            case 'ArrowRight':
                nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                break;
        }

        if (nextIndex !== undefined && items[nextIndex]) {
            items[nextIndex].focus();
        }
    }

    navigateToEnd(element, position) {
        const container = element.closest('[role="menu"], [role="listbox"], [role="tablist"]');
        if (!container) return;

        const items = this.getFocusableElements(container);
        const targetIndex = position === 'first' ? 0 : items.length - 1;

        if (items[targetIndex]) {
            items[targetIndex].focus();
        }
    }

    pageNavigate(element, delta) {
        const container = element.closest('[role="listbox"]');
        if (!container) return;

        const items = this.getFocusableElements(container);
        const currentIndex = items.indexOf(element);
        const nextIndex = Math.max(0, Math.min(items.length - 1, currentIndex + delta));

        if (items[nextIndex]) {
            items[nextIndex].focus();
        }
    }

    closeModal(modal) {
        const closeButton = modal.querySelector('[aria-label="Close"], .close, .btn-close');
        if (closeButton) {
            closeButton.click();
        } else {
            // Fallback: remove modal
            modal.remove();
        }
    }

    closeMenu(menu) {
        menu.setAttribute('aria-expanded', 'false');
        const trigger = document.querySelector(`[aria-controls="${menu.id}"]`);
        if (trigger) {
            trigger.focus();
        }
    }

    // ============================================================================
    // FOCUS MANAGEMENT
    // ============================================================================

    setupFocusManagement() {
        if (!this.options.enableFocusManagement) return;

        // Track focus history for better navigation
        this.focusHistory = [];
        this.focusIndex = -1;
    }

    handleFocusIn(event) {
        const element = event.target;

        // Add to focus history
        if (this.focusHistory.length >= 10) {
            this.focusHistory.shift();
        }

        this.focusHistory.push(element);
        this.focusIndex = this.focusHistory.length - 1;

        // Add focus-visible class for keyboard navigation
        document.documentElement.classList.add('focus-visible');

        // Announce focus changes to screen readers if needed
        if (this.options.announceDynamicContent) {
            this.announceFocusChange(element);
        }
    }

    handleFocusOut(event) {
        // Clear focus-visible class after a delay
        setTimeout(() => {
            if (!document.querySelector(':focus')) {
                document.documentElement.classList.remove('focus-visible');
            }
        }, 100);
    }

    announceFocusChange(element) {
        const label = this.getAccessibleName(element);
        if (label) {
            this.announceToScreenReader(`Focused: ${label}`);
        }
    }

    getAccessibleName(element) {
        // Get the accessible name of an element
        return element.getAttribute('aria-label') ||
               element.getAttribute('aria-labelledby') ||
               element.textContent?.trim() ||
               element.getAttribute('title') ||
               element.getAttribute('placeholder') ||
               '';
    }

    moveFocus(direction) {
        // Move focus in a specific direction (for custom navigation)
        const currentElement = document.activeElement;
        const focusableElements = this.getFocusableElements(document);

        if (focusableElements.length === 0) return;

        const currentIndex = focusableElements.indexOf(currentElement);

        let nextIndex;
        switch (direction) {
            case 'next':
                nextIndex = currentIndex < focusableElements.length - 1 ? currentIndex + 1 : 0;
                break;
            case 'previous':
                nextIndex = currentIndex > 0 ? currentIndex - 1 : focusableElements.length - 1;
                break;
            case 'first':
                nextIndex = 0;
                break;
            case 'last':
                nextIndex = focusableElements.length - 1;
                break;
        }

        if (nextIndex !== undefined && focusableElements[nextIndex]) {
            focusableElements[nextIndex].focus();
        }
    }

    // ============================================================================
    // SCREEN READER SUPPORT
    // ============================================================================

    setupScreenReaderSupport() {
        if (!this.options.enableScreenReaderSupport) return;

        this.setupLiveRegions();
        this.setupAriaLive();
        this.setupScreenReaderAnnouncements();
    }

    setupLiveRegions() {
        // Create live regions for dynamic content announcements
        const assertiveLive = document.createElement('div');
        assertiveLive.setAttribute('aria-live', 'assertive');
        assertiveLive.setAttribute('aria-atomic', 'true');
        assertiveLive.className = 'sr-only accessibility-live-region assertive';
        assertiveLive.id = 'accessibility-live-assertive';

        const politeLive = document.createElement('div');
        politeLive.setAttribute('aria-live', 'polite');
        politeLive.setAttribute('aria-atomic', 'true');
        politeLive.className = 'sr-only accessibility-live-region polite';
        politeLive.id = 'accessibility-live-polite';

        document.body.appendChild(assertiveLive);
        document.body.appendChild(politeLive);

        this.liveRegions.set('assertive', assertiveLive);
        this.liveRegions.set('polite', politeLive);
    }

    setupAriaLive() {
        // Add aria-live to dynamic content areas
        const dynamicAreas = document.querySelectorAll('.notifications, .messages, .alerts, .status');

        dynamicAreas.forEach(area => {
            if (!area.getAttribute('aria-live')) {
                area.setAttribute('aria-live', 'polite');
                area.setAttribute('aria-atomic', 'false');
            }
        });
    }

    setupScreenReaderAnnouncements() {
        // Override common methods to announce changes
        this.interceptConsoleLogs();
        this.interceptNotifications();
    }

    announceToScreenReader(message, priority = 'polite') {
        const liveRegion = this.liveRegions.get(priority);

        if (liveRegion) {
            // Clear previous content and add new message
            liveRegion.textContent = '';
            setTimeout(() => {
                liveRegion.textContent = message;
            }, 100);
        }
    }

    announcePageChange(title) {
        const message = `Page changed to: ${title}`;
        this.announceToScreenReader(message, 'assertive');

        // Update page title for screen readers
        if (document.title !== title) {
            document.title = title;
        }
    }

    announceLoadingState(isLoading, context = '') {
        const message = isLoading ?
            `Loading ${context}` :
            `${context} loaded`;

        this.announceToScreenReader(message, isLoading ? 'assertive' : 'polite');
    }

    announceError(error) {
        const message = `Error: ${error.message || error}`;
        this.announceToScreenReader(message, 'assertive');
    }

    interceptConsoleLogs() {
        // Intercept console.error to announce critical errors
        const originalError = console.error;
        console.error = (...args) => {
            originalError(...args);

            // Announce critical errors
            const message = args.join(' ');
            if (message.includes('Error') || message.includes('Failed')) {
                this.announceToScreenReader(`Error: ${message}`, 'assertive');
            }
        };
    }

    interceptNotifications() {
        // Intercept application notifications
        if (window.App && window.App.showNotification) {
            const originalShowNotification = window.App.showNotification;

            window.App.showNotification = (notification) => {
                originalShowNotification(notification);

                // Announce notification to screen readers
                const message = `${notification.type}: ${notification.message}`;
                this.announceToScreenReader(message, notification.type === 'error' ? 'assertive' : 'polite');
            };
        }
    }

    // ============================================================================
    // SKIP LINKS
    // ============================================================================

    setupSkipLinks() {
        if (!this.options.enableSkipLinks) return;

        // Create skip links container
        const skipLinksContainer = document.createElement('div');
        skipLinksContainer.className = 'accessibility-skip-links';
        skipLinksContainer.setAttribute('aria-label', 'Skip navigation links');

        // Add common skip links
        const skipLinks = [
            { href: '#main-content', text: 'Skip to main content' },
            { href: '#navigation', text: 'Skip to navigation' },
            { href: '#search', text: 'Skip to search' }
        ];

        skipLinks.forEach((link, index) => {
            const anchor = document.createElement('a');
            anchor.href = link.href;
            anchor.textContent = link.text;
            anchor.className = 'accessibility-skip-link';
            anchor.setAttribute('data-skip-index', index);

            // Position off-screen initially
            anchor.style.position = 'absolute';
            anchor.style.left = '-9999px';
            anchor.style.top = 'auto';
            anchor.style.width = '1px';
            anchor.style.height = '1px';
            anchor.style.overflow = 'hidden';

            // Show on focus
            anchor.addEventListener('focus', () => {
                anchor.style.left = '6px';
                anchor.style.top = '6px';
                anchor.style.width = 'auto';
                anchor.style.height = 'auto';
            });

            anchor.addEventListener('blur', () => {
                anchor.style.left = '-9999px';
                anchor.style.width = '1px';
                anchor.style.height = '1px';
            });

            skipLinksContainer.appendChild(anchor);
            this.skipLinks.set(index, anchor);
        });

        document.body.insertBefore(skipLinksContainer, document.body.firstChild);
    }

    activateSkipLink(index) {
        const link = this.skipLinks.get(index);
        if (link) {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    addSkipLink(href, text) {
        const index = this.skipLinks.size;
        const anchor = document.createElement('a');
        anchor.href = href;
        anchor.textContent = text;
        anchor.className = 'accessibility-skip-link';
        anchor.setAttribute('data-skip-index', index);

        // Apply same styling as other skip links
        anchor.style.position = 'absolute';
        anchor.style.left = '-9999px';
        // ... (same styling as above)

        const container = document.querySelector('.accessibility-skip-links');
        if (container) {
            container.appendChild(anchor);
            this.skipLinks.set(index, anchor);
        }
    }

    // ============================================================================
    // COLOR CONTRAST IMPROVEMENTS
    // ============================================================================

    setupColorContrast() {
        if (!this.options.enableColorContrast) return;

        this.checkColorContrast();
        this.setupColorContrastWatcher();
    }

    checkColorContrast() {
        const elements = document.querySelectorAll('*');

        elements.forEach(element => {
            const styles = window.getComputedStyle(element);
            const backgroundColor = styles.backgroundColor;
            const color = styles.color;

            if (backgroundColor && color &&
                backgroundColor !== 'rgba(0, 0, 0, 0)' &&
                backgroundColor !== 'transparent') {

                const contrastRatio = this.calculateContrastRatio(color, backgroundColor);

                if (contrastRatio < 4.5) {
                    // Low contrast warning
                    element.setAttribute('data-low-contrast', 'true');
                    element.classList.add('accessibility-low-contrast');

                    // Store for reporting
                    this.colorContrastCache.set(element, contrastRatio);
                }
            }
        });
    }

    calculateContrastRatio(color1, color2) {
        // Convert colors to RGB
        const rgb1 = this.parseColor(color1);
        const rgb2 = this.parseColor(color2);

        if (!rgb1 || !rgb2) return 1;

        // Calculate relative luminance
        const lum1 = this.calculateLuminance(rgb1);
        const lum2 = this.calculateLuminance(rgb2);

        const brightest = Math.max(lum1, lum2);
        const darkest = Math.min(lum1, lum2);

        return (brightest + 0.05) / (darkest + 0.05);
    }

    parseColor(color) {
        // Parse CSS color values to RGB
        if (color.startsWith('#')) {
            return this.hexToRgb(color);
        } else if (color.startsWith('rgb')) {
            return this.rgbToArray(color);
        } else if (color.startsWith('hsl')) {
            return this.hslToRgb(color);
        }

        // Named colors - simplified mapping
        const namedColors = {
            'black': [0, 0, 0],
            'white': [255, 255, 255],
            'red': [255, 0, 0],
            'green': [0, 128, 0],
            'blue': [0, 0, 255],
            // Add more as needed
        };

        return namedColors[color.toLowerCase()] || null;
    }

    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? [
            parseInt(result[1], 16),
            parseInt(result[2], 16),
            parseInt(result[3], 16)
        ] : null;
    }

    rgbToArray(rgb) {
        const match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        return match ? [parseInt(match[1]), parseInt(match[2]), parseInt(match[3])] : null;
    }

    hslToRgb(hsl) {
        // Simplified HSL to RGB conversion
        const match = hsl.match(/^hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)$/);
        if (!match) return null;

        const h = parseInt(match[1]) / 360;
        const s = parseInt(match[2]) / 100;
        const l = parseInt(match[3]) / 100;

        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (q - p) * 6 * t;
            if (t < 1/2) return q;
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
            return p;
        };

        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;

        const r = hue2rgb(p, q, h + 1/3);
        const g = hue2rgb(p, q, h);
        const b = hue2rgb(p, q, h - 1/3);

        return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
    }

    calculateLuminance(rgb) {
        const [r, g, b] = rgb.map(c => {
            c = c / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });

        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    setupColorContrastWatcher() {
        // Watch for dynamic content changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            this.checkElementContrast(node);
                        }
                    });
                } else if (mutation.type === 'attributes' &&
                          (mutation.attributeName === 'style' ||
                           mutation.attributeName === 'class')) {
                    this.checkElementContrast(mutation.target);
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    checkElementContrast(element) {
        const styles = window.getComputedStyle(element);
        const backgroundColor = styles.backgroundColor;
        const color = styles.color;

        if (backgroundColor && color &&
            backgroundColor !== 'rgba(0, 0, 0, 0)' &&
            backgroundColor !== 'transparent') {

            const contrastRatio = this.calculateContrastRatio(color, backgroundColor);

            if (contrastRatio < 4.5) {
                element.setAttribute('data-low-contrast', 'true');
                element.classList.add('accessibility-low-contrast');
            } else {
                element.removeAttribute('data-low-contrast');
                element.classList.remove('accessibility-low-contrast');
            }
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    emit(event, data) {
        if (window.EventManager) {
            window.EventManager.emit(`accessibility:${event}`, data);
        }
    }

    getAccessibilityReport() {
        return {
            ariaLabelsCount: document.querySelectorAll('[aria-label]').length,
            focusableElementsCount: this.getFocusableElements(document).length,
            lowContrastElementsCount: document.querySelectorAll('[data-low-contrast]').length,
            skipLinksCount: this.skipLinks.size,
            liveRegionsCount: this.liveRegions.size,
            colorContrastIssues: Array.from(this.colorContrastCache.entries()).map(([element, ratio]) => ({
                element: element.outerHTML.substring(0, 100) + '...',
                contrastRatio: ratio
            }))
        };
    }

    enableHighContrastMode() {
        this.options.highContrastMode = true;
        this.handleHighContrastChange(true);
    }

    disableHighContrastMode() {
        this.options.highContrastMode = false;
        this.handleHighContrastChange(false);
    }

    enableReducedMotion() {
        this.options.reducedMotion = true;
        this.handleReducedMotionChange(true);
    }

    disableReducedMotion() {
        this.options.reducedMotion = false;
        this.handleReducedMotionChange(false);
    }

    // ============================================================================
    // CLEANUP METHODS
    // ============================================================================

    destroy() {
        // Remove event listeners
        document.removeEventListener('keydown', this.handleKeydown);
        document.removeEventListener('focusin', this.handleFocusIn);
        document.removeEventListener('focusout', this.handleFocusOut);

        // Clear caches
        this.focusableElements = [];
        this.skipLinks.clear();
        this.liveRegions.clear();
        this.ariaLabels.clear();
        this.colorContrastCache.clear();

        // Remove accessibility classes
        document.documentElement.classList.remove(
            'high-contrast',
            'reduced-motion',
            'dark-mode',
            'focus-visible'
        );
    }
}

// ============================================================================
// CSS UTILITIES (to be added to main CSS)
// ============================================================================

/*
.accessibility-skip-links {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1000;
}

.accessibility-skip-link {
    background: #000;
    color: #fff;
    padding: 8px;
    text-decoration: none;
    border-radius: 0 0 4px 4px;
    font-size: 14px;
    z-index: 1000;
}

.accessibility-skip-link:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.accessibility-low-contrast {
    border: 1px solid #ff6b6b !important;
}

.accessibility-low-contrast::after {
    content: "⚠ Low contrast";
    position: absolute;
    top: 0;
    right: 0;
    background: #ff6b6b;
    color: white;
    font-size: 12px;
    padding: 2px 4px;
    border-radius: 0 0 0 4px;
}

.focus-visible:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

@media (prefers-contrast: high) {
    .high-contrast {
        filter: contrast(150%);
    }
}
*/

// Export the utility
window.AccessibilityManager = AccessibilityManager;
