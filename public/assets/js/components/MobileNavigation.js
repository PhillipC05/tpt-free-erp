/**
 * TPT Free ERP - Mobile Navigation Component
 * Touch-optimized navigation for mobile devices
 */

class MobileNavigation extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            menuItems: [],
            activeItem: null,
            position: 'bottom', // bottom, top, side
            theme: 'light',
            showLabels: false,
            hapticFeedback: true,
            swipeGestures: true,
            onNavigate: null,
            onToggle: null,
            ...props
        };

        this.state = {
            isOpen: false,
            activeTab: null,
            touchStartX: 0,
            touchStartY: 0,
            touchEndX: 0,
            touchEndY: 0,
            swipeThreshold: 50
        };

        // Bind methods
        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchMove = this.handleTouchMove.bind(this);
        this.handleTouchEnd = this.handleTouchEnd.bind(this);
        this.handleNavigate = this.handleNavigate.bind(this);
        this.toggleMenu = this.toggleMenu.bind(this);
        this.closeMenu = this.closeMenu.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
    }

    componentDidMount() {
        // Add touch event listeners for swipe gestures
        if (this.props.swipeGestures) {
            document.addEventListener('touchstart', this.handleTouchStart, { passive: false });
            document.addEventListener('touchmove', this.handleTouchMove, { passive: false });
            document.addEventListener('touchend', this.handleTouchEnd, { passive: false });
        }

        // Add keyboard navigation
        document.addEventListener('keydown', this.handleKeyDown);

        // Set initial active tab
        if (this.props.menuItems.length > 0) {
            this.setState({ activeTab: this.props.activeItem || this.props.menuItems[0].id });
        }
    }

    componentWillUnmount() {
        // Remove event listeners
        document.removeEventListener('touchstart', this.handleTouchStart);
        document.removeEventListener('touchmove', this.handleTouchMove);
        document.removeEventListener('touchend', this.handleTouchEnd);
        document.removeEventListener('keydown', this.handleKeyDown);
    }

    handleTouchStart(e) {
        this.setState({
            touchStartX: e.touches[0].clientX,
            touchStartY: e.touches[0].clientY
        });
    }

    handleTouchMove(e) {
        if (!this.state.touchStartX || !this.state.touchStartY) return;

        const touchEndX = e.touches[0].clientX;
        const touchEndY = e.touches[0].clientY;

        this.setState({
            touchEndX,
            touchEndY
        });

        // Prevent scrolling if it's a horizontal swipe
        const deltaX = Math.abs(touchEndX - this.state.touchStartX);
        const deltaY = Math.abs(touchEndY - this.state.touchStartY);

        if (deltaX > deltaY && deltaX > 10) {
            e.preventDefault();
        }
    }

    handleTouchEnd(e) {
        if (!this.state.touchStartX || !this.state.touchEndX) return;

        const deltaX = this.state.touchEndX - this.state.touchStartX;
        const deltaY = this.state.touchEndY - this.state.touchStartY;
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        // Determine swipe direction
        if (absDeltaX > this.state.swipeThreshold && absDeltaX > absDeltaY) {
            if (deltaX > 0) {
                this.handleSwipeRight();
            } else {
                this.handleSwipeLeft();
            }
        }

        // Reset touch coordinates
        this.setState({
            touchStartX: 0,
            touchStartY: 0,
            touchEndX: 0,
            touchEndY: 0
        });
    }

    handleSwipeLeft() {
        // Navigate to next tab
        const currentIndex = this.props.menuItems.findIndex(item => item.id === this.state.activeTab);
        const nextIndex = (currentIndex + 1) % this.props.menuItems.length;
        const nextItem = this.props.menuItems[nextIndex];

        this.handleNavigate(nextItem, new Event('swipe'));
        this.triggerHapticFeedback('light');
    }

    handleSwipeRight() {
        // Navigate to previous tab
        const currentIndex = this.props.menuItems.findIndex(item => item.id === this.state.activeTab);
        const prevIndex = currentIndex === 0 ? this.props.menuItems.length - 1 : currentIndex - 1;
        const prevItem = this.props.menuItems[prevIndex];

        this.handleNavigate(prevItem, new Event('swipe'));
        this.triggerHapticFeedback('light');
    }

    handleNavigate(item, event) {
        event.preventDefault();

        // Update active tab
        this.setState({ activeTab: item.id });

        // Trigger haptic feedback
        this.triggerHapticFeedback('medium');

        // Navigate
        if (item.route) {
            Router.navigate(item.route);
        } else if (item.href) {
            window.location.href = item.href;
        } else if (item.action) {
            item.action(item);
        }

        // Call callback
        if (this.props.onNavigate) {
            this.props.onNavigate(item, event);
        }
    }

    handleKeyDown(e) {
        // Handle keyboard navigation
        const { menuItems } = this.props;
        const currentIndex = menuItems.findIndex(item => item.id === this.state.activeTab);

        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                const prevIndex = currentIndex === 0 ? menuItems.length - 1 : currentIndex - 1;
                this.handleNavigate(menuItems[prevIndex], e);
                break;
            case 'ArrowRight':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % menuItems.length;
                this.handleNavigate(menuItems[nextIndex], e);
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                this.handleNavigate(menuItems[currentIndex], e);
                break;
        }
    }

    toggleMenu() {
        this.setState({ isOpen: !this.state.isOpen });
        this.triggerHapticFeedback('light');
    }

    closeMenu() {
        this.setState({ isOpen: false });
    }

    triggerHapticFeedback(type = 'light') {
        if (!this.props.hapticFeedback || !navigator.vibrate) return;

        const patterns = {
            light: [10],
            medium: [20],
            heavy: [30],
            success: [20, 10, 20],
            error: [50, 10, 50, 10, 50]
        };

        navigator.vibrate(patterns[type] || patterns.light);
    }

    render() {
        const { position, theme, showLabels, menuItems } = this.props;
        const { isOpen, activeTab } = this.state;

        const navClasses = [
            'mobile-navigation',
            `nav-${position}`,
            `nav-${theme}`,
            isOpen ? 'open' : '',
            showLabels ? 'show-labels' : ''
        ].filter(Boolean).join(' ');

        const nav = DOM.create('nav', {
            className: navClasses,
            role: 'navigation',
            'aria-label': 'Mobile navigation'
        });

        if (position === 'bottom') {
            nav.appendChild(this.renderBottomNavigation());
        } else if (position === 'top') {
            nav.appendChild(this.renderTopNavigation());
        } else if (position === 'side') {
            nav.appendChild(this.renderSideNavigation());
        }

        return nav;
    }

    renderBottomNavigation() {
        const { menuItems, showLabels } = this.props;
        const { activeTab } = this.state;

        const nav = DOM.create('div', { className: 'bottom-nav' });

        menuItems.forEach(item => {
            const isActive = item.id === activeTab;
            const itemClasses = [
                'nav-item',
                isActive ? 'active' : '',
                item.badge ? 'has-badge' : ''
            ].filter(Boolean).join(' ');

            const navItem = DOM.create('div', {
                className: itemClasses,
                onclick: (e) => this.handleNavigate(item, e),
                'aria-label': item.title,
                role: 'button',
                tabindex: 0
            });

            // Icon
            if (item.icon) {
                const icon = DOM.create('div', { className: 'nav-icon' });
                icon.innerHTML = `<i class="${item.icon}"></i>`;
                navItem.appendChild(icon);
            }

            // Label
            if (showLabels || item.alwaysShowLabel) {
                const label = DOM.create('div', { className: 'nav-label' }, item.title);
                navItem.appendChild(label);
            }

            // Badge
            if (item.badge) {
                const badge = DOM.create('div', { className: 'nav-badge' }, item.badge.toString());
                navItem.appendChild(badge);
            }

            // Active indicator
            if (isActive) {
                const indicator = DOM.create('div', { className: 'nav-indicator' });
                navItem.appendChild(indicator);
            }

            nav.appendChild(navItem);
        });

        return nav;
    }

    renderTopNavigation() {
        const { menuItems } = this.props;

        const nav = DOM.create('div', { className: 'top-nav' });

        // Menu button for collapsible menu
        const menuBtn = DOM.create('button', {
            className: 'menu-toggle',
            onclick: this.toggleMenu,
            'aria-label': 'Toggle menu'
        });
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        nav.appendChild(menuBtn);

        // Brand/Logo
        const brand = DOM.create('div', { className: 'nav-brand' });
        brand.innerHTML = '<i class="fas fa-cogs"></i><span>TPT ERP</span>';
        nav.appendChild(brand);

        // Action buttons
        const actions = DOM.create('div', { className: 'nav-actions' });

        // Search button
        const searchBtn = DOM.create('button', {
            className: 'action-btn',
            onclick: () => this.handleAction('search'),
            'aria-label': 'Search'
        });
        searchBtn.innerHTML = '<i class="fas fa-search"></i>';
        actions.appendChild(searchBtn);

        // Notification button
        const notificationBtn = DOM.create('button', {
            className: 'action-btn',
            onclick: () => this.handleAction('notifications'),
            'aria-label': 'Notifications'
        });
        notificationBtn.innerHTML = '<i class="fas fa-bell"></i>';
        actions.appendChild(notificationBtn);

        // User menu button
        const userBtn = DOM.create('button', {
            className: 'action-btn',
            onclick: () => this.handleAction('user'),
            'aria-label': 'User menu'
        });
        userBtn.innerHTML = '<i class="fas fa-user"></i>';
        actions.appendChild(userBtn);

        nav.appendChild(actions);

        // Collapsible menu
        if (this.state.isOpen) {
            const menu = DOM.create('div', { className: 'top-nav-menu' });

            menuItems.forEach(item => {
                const menuItem = DOM.create('a', {
                    href: item.route || '#',
                    className: 'menu-item',
                    onclick: (e) => {
                        this.handleNavigate(item, e);
                        this.closeMenu();
                    }
                });

                if (item.icon) {
                    const icon = DOM.create('i', { className: item.icon });
                    menuItem.appendChild(icon);
                }

                const text = DOM.create('span', {}, item.title);
                menuItem.appendChild(text);

                menu.appendChild(menuItem);
            });

            nav.appendChild(menu);
        }

        return nav;
    }

    renderSideNavigation() {
        const { menuItems } = this.props;
        const { isOpen } = this.state;

        const nav = DOM.create('div', { className: 'side-nav' });

        // Overlay for mobile
        if (isOpen) {
            const overlay = DOM.create('div', {
                className: 'nav-overlay',
                onclick: this.closeMenu
            });
            nav.appendChild(overlay);
        }

        // Side panel
        const panel = DOM.create('div', {
            className: `side-panel ${isOpen ? 'open' : ''}`
        });

        // Header
        const header = DOM.create('div', { className: 'side-header' });

        const closeBtn = DOM.create('button', {
            className: 'close-btn',
            onclick: this.closeMenu,
            'aria-label': 'Close menu'
        });
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeBtn);

        const brand = DOM.create('div', { className: 'side-brand' });
        brand.innerHTML = '<i class="fas fa-cogs"></i><span>TPT ERP</span>';
        header.appendChild(brand);

        panel.appendChild(header);

        // Menu
        const menu = DOM.create('ul', { className: 'side-menu' });

        menuItems.forEach(item => {
            const li = DOM.create('li', { className: 'menu-item' });

            const link = DOM.create('a', {
                href: item.route || '#',
                className: item.id === this.state.activeTab ? 'active' : '',
                onclick: (e) => {
                    this.handleNavigate(item, e);
                    this.closeMenu();
                }
            });

            if (item.icon) {
                const icon = DOM.create('i', { className: item.icon });
                link.appendChild(icon);
            }

            const text = DOM.create('span', {}, item.title);
            link.appendChild(text);

            if (item.badge) {
                const badge = DOM.create('span', { className: 'item-badge' }, item.badge.toString());
                link.appendChild(badge);
            }

            li.appendChild(link);

            // Submenu
            if (item.children && item.children.length > 0) {
                const submenu = DOM.create('ul', { className: 'submenu' });

                item.children.forEach(child => {
                    const childLi = DOM.create('li', { className: 'submenu-item' });
                    const childLink = DOM.create('a', {
                        href: child.route || '#',
                        onclick: (e) => {
                            this.handleNavigate(child, e);
                            this.closeMenu();
                        }
                    }, child.title);
                    childLi.appendChild(childLink);
                    submenu.appendChild(childLi);
                });

                li.appendChild(submenu);
            }

            menu.appendChild(li);
        });

        panel.appendChild(menu);

        nav.appendChild(panel);

        return nav;
    }

    handleAction(action) {
        // Handle action buttons
        console.log('Action:', action);
        this.triggerHapticFeedback('light');
    }

    // Public API methods
    setActiveTab(tabId) {
        this.setState({ activeTab: tabId });
    }

    openMenu() {
        this.setState({ isOpen: true });
    }

    closeMenu() {
        this.setState({ isOpen: false });
    }

    addMenuItem(item) {
        this.props.menuItems.push(item);
        this.forceUpdate();
    }

    removeMenuItem(itemId) {
        this.props.menuItems = this.props.menuItems.filter(item => item.id !== itemId);
        this.forceUpdate();
    }

    enableHapticFeedback(enabled = true) {
        this.props.hapticFeedback = enabled;
    }

    enableSwipeGestures(enabled = true) {
        this.props.swipeGestures = enabled;

        if (enabled) {
            document.addEventListener('touchstart', this.handleTouchStart, { passive: false });
            document.addEventListener('touchmove', this.handleTouchMove, { passive: false });
            document.addEventListener('touchend', this.handleTouchEnd, { passive: false });
        } else {
            document.removeEventListener('touchstart', this.handleTouchStart);
            document.removeEventListener('touchmove', this.handleTouchMove);
            document.removeEventListener('touchend', this.handleTouchEnd);
        }
    }
}

// Touch Gesture Manager
class TouchGestureManager {
    constructor() {
        this.gestures = new Map();
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.minSwipeDistance = 50;
        this.maxSwipeTime = 300;
        this.touchStartTime = 0;

        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchEnd = this.handleTouchEnd.bind(this);
    }

    init() {
        document.addEventListener('touchstart', this.handleTouchStart, { passive: true });
        document.addEventListener('touchend', this.handleTouchEnd, { passive: true });
    }

    destroy() {
        document.removeEventListener('touchstart', this.handleTouchStart);
        document.removeEventListener('touchend', this.handleTouchEnd);
    }

    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        this.touchStartTime = Date.now();
    }

    handleTouchEnd(e) {
        if (!this.touchStartX || !this.touchStartY) return;

        this.touchEndX = e.changedTouches[0].clientX;
        this.touchEndY = e.changedTouches[0].clientY;

        const touchEndTime = Date.now();
        const touchDuration = touchEndTime - this.touchStartTime;

        if (touchDuration > this.maxSwipeTime) return;

        const deltaX = this.touchEndX - this.touchStartX;
        const deltaY = this.touchEndY - this.touchStartY;
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        // Determine swipe direction
        if (absDeltaX > this.minSwipeDistance && absDeltaX > absDeltaY) {
            if (deltaX > 0) {
                this.triggerGesture('swipeRight', { deltaX, deltaY });
            } else {
                this.triggerGesture('swipeLeft', { deltaX, deltaY });
            }
        } else if (absDeltaY > this.minSwipeDistance && absDeltaY > absDeltaX) {
            if (deltaY > 0) {
                this.triggerGesture('swipeDown', { deltaX, deltaY });
            } else {
                this.triggerGesture('swipeUp', { deltaX, deltaY });
            }
        }

        // Reset
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.touchStartTime = 0;
    }

    on(gesture, callback) {
        if (!this.gestures.has(gesture)) {
            this.gestures.set(gesture, []);
        }
        this.gestures.get(gesture).push(callback);
    }

    off(gesture, callback) {
        if (this.gestures.has(gesture)) {
            const callbacks = this.gestures.get(gesture);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    triggerGesture(gesture, data) {
        if (this.gestures.has(gesture)) {
            this.gestures.get(gesture).forEach(callback => {
                callback(data);
            });
        }
    }
}

// Global touch gesture manager
const TouchGestures = new TouchGestureManager();

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => TouchGestures.init());
} else {
    TouchGestures.init();
}

// Register components
ComponentRegistry.register('MobileNavigation', MobileNavigation);

// Make globally available
window.MobileNavigation = MobileNavigation;
window.TouchGestureManager = TouchGestureManager;
window.TouchGestures = TouchGestures;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MobileNavigation,
        TouchGestureManager
    };
}
