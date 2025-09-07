/**
 * TPT Free ERP - Navigation Component
 * Advanced navigation system with menus, breadcrumbs, and responsive design
 */

class Navigation extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'sidebar', // sidebar, topbar, bottom, tabs
            theme: 'light', // light, dark, auto
            collapsible: true,
            collapsed: false,
            showBreadcrumbs: true,
            showSearch: true,
            showUserMenu: true,
            showNotifications: true,
            menuItems: [],
            activeItem: null,
            onNavigate: null,
            onToggle: null,
            onSearch: null,
            ...props
        };

        this.state = {
            collapsed: this.props.collapsed,
            searchQuery: '',
            notifications: [],
            userMenuOpen: false,
            mobileMenuOpen: false,
            breadcrumbs: []
        };

        // Bind methods
        this.handleToggle = this.handleToggle.bind(this);
        this.handleNavigate = this.handleNavigate.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handleUserMenuToggle = this.handleUserMenuToggle.bind(this);
        this.handleMobileMenuToggle = this.handleMobileMenuToggle.bind(this);
        this.handleNotificationClick = this.handleNotificationClick.bind(this);
        this.updateBreadcrumbs = this.updateBreadcrumbs.bind(this);
        this.renderMenuItem = this.renderMenuItem.bind(this);
        this.renderBreadcrumbs = this.renderBreadcrumbs.bind(this);
    }

    componentDidMount() {
        // Listen for route changes to update active item and breadcrumbs
        window.addEventListener('popstate', this.updateBreadcrumbs);
        this.updateBreadcrumbs();

        // Load notifications
        this.loadNotifications();
    }

    componentWillUnmount() {
        window.removeEventListener('popstate', this.updateBreadcrumbs);
    }

    handleToggle() {
        const collapsed = !this.state.collapsed;
        this.setState({ collapsed });

        if (this.props.onToggle) {
            this.props.onToggle(collapsed);
        }
    }

    handleNavigate(item, event) {
        event.preventDefault();

        // Update active item
        this.props.activeItem = item.id;

        // Close mobile menu
        this.setState({ mobileMenuOpen: false });

        // Navigate
        if (item.route) {
            Router.navigate(item.route);
        } else if (item.href) {
            window.location.href = item.href;
        } else if (item.action) {
            item.action(item);
        }

        // Update breadcrumbs
        this.updateBreadcrumbs();

        // Call callback
        if (this.props.onNavigate) {
            this.props.onNavigate(item, event);
        }
    }

    handleSearch(query) {
        this.setState({ searchQuery: query });

        if (this.props.onSearch) {
            this.props.onSearch(query);
        }
    }

    handleUserMenuToggle() {
        this.setState({ userMenuOpen: !this.state.userMenuOpen });
    }

    handleMobileMenuToggle() {
        this.setState({ mobileMenuOpen: !this.state.mobileMenuOpen });
    }

    handleNotificationClick(notification) {
        // Mark as read
        this.markNotificationAsRead(notification.id);

        // Handle notification action
        if (notification.action) {
            notification.action(notification);
        }
    }

    async loadNotifications() {
        try {
            // This would load from API
            const notifications = [
                {
                    id: 1,
                    title: 'New order received',
                    message: 'Order #1234 has been placed',
                    type: 'info',
                    read: false,
                    timestamp: new Date(Date.now() - 1000 * 60 * 5) // 5 minutes ago
                },
                {
                    id: 2,
                    title: 'Payment overdue',
                    message: 'Invoice #5678 is overdue',
                    type: 'warning',
                    read: false,
                    timestamp: new Date(Date.now() - 1000 * 60 * 30) // 30 minutes ago
                }
            ];

            this.setState({ notifications });
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    markNotificationAsRead(notificationId) {
        const notifications = this.state.notifications.map(notification =>
            notification.id === notificationId
                ? { ...notification, read: true }
                : notification
        );
        this.setState({ notifications });
    }

    updateBreadcrumbs() {
        const breadcrumbs = State.get('navigation.breadcrumbs') || [];
        this.setState({ breadcrumbs });
    }

    render() {
        const { type, theme, collapsible, showBreadcrumbs, showSearch, showUserMenu, showNotifications } = this.props;
        const { collapsed, searchQuery, notifications, userMenuOpen, mobileMenuOpen, breadcrumbs } = this.state;

        const navClasses = [
            'navigation',
            `nav-${type}`,
            `nav-${theme}`,
            collapsed ? 'collapsed' : '',
            mobileMenuOpen ? 'mobile-open' : ''
        ].filter(Boolean).join(' ');

        const nav = DOM.create('nav', { className: navClasses });

        // Mobile menu toggle
        if (type === 'sidebar') {
            const mobileToggle = DOM.create('button', {
                className: 'mobile-menu-toggle',
                'aria-label': 'Toggle mobile menu',
                onclick: this.handleMobileMenuToggle
            });
            mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
            nav.appendChild(mobileToggle);
        }

        // Sidebar navigation
        if (type === 'sidebar') {
            const sidebar = this.renderSidebar();
            nav.appendChild(sidebar);
        }

        // Topbar navigation
        if (type === 'topbar') {
            const topbar = this.renderTopbar();
            nav.appendChild(topbar);
        }

        // Breadcrumbs
        if (showBreadcrumbs && breadcrumbs.length > 0) {
            const breadcrumbsElement = this.renderBreadcrumbs();
            nav.appendChild(breadcrumbsElement);
        }

        return nav;
    }

    renderSidebar() {
        const { menuItems, collapsible } = this.props;
        const { collapsed } = this.state;

        const sidebar = DOM.create('div', { className: 'nav-sidebar' });

        // Header
        const header = DOM.create('div', { className: 'nav-header' });

        if (collapsible) {
            const toggleBtn = DOM.create('button', {
                className: 'nav-toggle',
                'aria-label': collapsed ? 'Expand sidebar' : 'Collapse sidebar',
                onclick: this.handleToggle
            });
            toggleBtn.innerHTML = collapsed ?
                '<i class="fas fa-chevron-right"></i>' :
                '<i class="fas fa-chevron-left"></i>';
            header.appendChild(toggleBtn);
        }

        // Logo/Brand
        const brand = DOM.create('div', { className: 'nav-brand' });
        const logo = DOM.create('div', { className: 'nav-logo' });
        logo.innerHTML = '<i class="fas fa-cogs"></i>';

        const title = DOM.create('span', { className: 'nav-title' });
        title.textContent = collapsed ? 'TPT' : 'TPT ERP';

        brand.appendChild(logo);
        brand.appendChild(title);
        header.appendChild(brand);

        sidebar.appendChild(header);

        // Menu
        const menu = DOM.create('ul', { className: 'nav-menu' });

        menuItems.forEach(item => {
            const menuItem = this.renderMenuItem(item);
            menu.appendChild(menuItem);
        });

        sidebar.appendChild(menu);

        // Footer
        const footer = DOM.create('div', { className: 'nav-footer' });
        const version = DOM.create('div', { className: 'nav-version' });
        version.textContent = collapsed ? 'v1.0' : 'Version 1.0.0';
        footer.appendChild(version);
        sidebar.appendChild(footer);

        return sidebar;
    }

    renderTopbar() {
        const { showSearch, showUserMenu, showNotifications } = this.props;
        const { searchQuery, notifications, userMenuOpen } = this.state;

        const topbar = DOM.create('div', { className: 'nav-topbar' });

        // Left section
        const left = DOM.create('div', { className: 'nav-left' });

        // Brand
        const brand = DOM.create('div', { className: 'nav-brand' });
        const logo = DOM.create('div', { className: 'nav-logo' });
        logo.innerHTML = '<i class="fas fa-cogs"></i>';
        const title = DOM.create('span', { className: 'nav-title' }, 'TPT ERP');
        brand.appendChild(logo);
        brand.appendChild(title);
        left.appendChild(brand);

        topbar.appendChild(left);

        // Center section - Search
        if (showSearch) {
            const center = DOM.create('div', { className: 'nav-center' });
            const searchGroup = DOM.create('div', { className: 'nav-search' });
            const searchInput = DOM.create('input', {
                type: 'text',
                className: 'search-input',
                placeholder: 'Search...',
                value: searchQuery,
                oninput: (e) => this.handleSearch(e.target.value)
            });
            const searchIcon = DOM.create('i', { className: 'fas fa-search search-icon' });

            searchGroup.appendChild(searchInput);
            searchGroup.appendChild(searchIcon);
            center.appendChild(searchGroup);
            topbar.appendChild(center);
        }

        // Right section
        const right = DOM.create('div', { className: 'nav-right' });

        // Notifications
        if (showNotifications) {
            const notificationBtn = DOM.create('button', {
                className: 'nav-btn notification-btn',
                'aria-label': 'Notifications',
                onclick: () => this.toggleNotifications()
            });
            notificationBtn.innerHTML = '<i class="fas fa-bell"></i>';

            const badge = DOM.create('span', { className: 'notification-badge' });
            badge.textContent = this.getUnreadNotificationCount().toString();
            notificationBtn.appendChild(badge);

            right.appendChild(notificationBtn);
        }

        // User menu
        if (showUserMenu) {
            const userMenu = this.renderUserMenu();
            right.appendChild(userMenu);
        }

        topbar.appendChild(right);

        return topbar;
    }

    renderMenuItem(item) {
        const { collapsed } = this.state;
        const isActive = this.props.activeItem === item.id;
        const hasChildren = item.children && item.children.length > 0;

        const li = DOM.create('li', {
            className: `nav-item ${isActive ? 'active' : ''} ${hasChildren ? 'has-children' : ''}`
        });

        const link = DOM.create('a', {
            href: item.route || item.href || '#',
            className: 'nav-link',
            onclick: (e) => this.handleNavigate(item, e)
        });

        // Icon
        if (item.icon) {
            const icon = DOM.create('i', { className: item.icon });
            link.appendChild(icon);
        }

        // Title
        if (!collapsed || !item.icon) {
            const title = DOM.create('span', { className: 'nav-text' }, item.title);
            link.appendChild(title);
        }

        // Badge
        if (item.badge) {
            const badge = DOM.create('span', { className: `nav-badge ${item.badge.type || 'default'}` });
            badge.textContent = item.badge.text;
            link.appendChild(badge);
        }

        li.appendChild(link);

        // Children
        if (hasChildren && !collapsed) {
            const submenu = DOM.create('ul', { className: 'nav-submenu' });

            item.children.forEach(child => {
                const childItem = this.renderMenuItem(child);
                submenu.appendChild(childItem);
            });

            li.appendChild(submenu);
        }

        return li;
    }

    renderBreadcrumbs() {
        const { breadcrumbs } = this.state;

        const breadcrumbsContainer = DOM.create('div', { className: 'nav-breadcrumbs' });
        const breadcrumbsList = DOM.create('ol', { className: 'breadcrumbs-list' });

        // Home breadcrumb
        const homeItem = DOM.create('li', { className: 'breadcrumb-item' });
        const homeLink = DOM.create('a', {
            href: '/',
            onclick: (e) => {
                e.preventDefault();
                Router.navigate('/');
            }
        });
        homeLink.innerHTML = '<i class="fas fa-home"></i>';
        homeItem.appendChild(homeLink);
        breadcrumbsList.appendChild(homeItem);

        // Other breadcrumbs
        breadcrumbs.forEach((crumb, index) => {
            const separator = DOM.create('li', { className: 'breadcrumb-separator' }, '/');
            breadcrumbsList.appendChild(separator);

            const crumbItem = DOM.create('li', { className: 'breadcrumb-item' });

            if (index === breadcrumbs.length - 1) {
                // Last item - not a link
                crumbItem.textContent = crumb.label;
                crumbItem.classList.add('active');
            } else {
                // Link
                const crumbLink = DOM.create('a', { href: '#' }, crumb.label);
                crumbItem.appendChild(crumbLink);
            }

            breadcrumbsList.appendChild(crumbItem);
        });

        breadcrumbsContainer.appendChild(breadcrumbsList);
        return breadcrumbsContainer;
    }

    renderUserMenu() {
        const { userMenuOpen } = this.state;
        const user = State.get('user.currentUser');

        const userMenu = DOM.create('div', { className: 'user-menu' });

        const userBtn = DOM.create('button', {
            className: 'user-btn',
            onclick: this.handleUserMenuToggle
        });

        // User avatar
        const avatar = DOM.create('div', { className: 'user-avatar' });
        if (user && user.avatar) {
            avatar.innerHTML = `<img src="${user.avatar}" alt="${user.first_name || 'User'}">`;
        } else {
            avatar.innerHTML = '<i class="fas fa-user"></i>';
        }
        userBtn.appendChild(avatar);

        // User info
        const userInfo = DOM.create('div', { className: 'user-info' });
        const userName = DOM.create('span', { className: 'user-name' });
        userName.textContent = user ? (user.first_name || user.username || 'User') : 'Guest';
        userInfo.appendChild(userName);
        userBtn.appendChild(userInfo);

        // Dropdown arrow
        const arrow = DOM.create('i', { className: 'fas fa-chevron-down user-arrow' });
        userBtn.appendChild(arrow);

        userMenu.appendChild(userBtn);

        // Dropdown menu
        if (userMenuOpen) {
            const dropdown = DOM.create('div', { className: 'user-dropdown' });
            const menuItems = [
                { label: 'Profile', icon: 'fas fa-user', action: () => Router.navigate('/profile') },
                { label: 'Settings', icon: 'fas fa-cog', action: () => Router.navigate('/settings') },
                { label: 'Help', icon: 'fas fa-question-circle', action: () => Router.navigate('/help') },
                { type: 'divider' },
                { label: 'Logout', icon: 'fas fa-sign-out-alt', action: () => this.handleLogout() }
            ];

            menuItems.forEach(item => {
                if (item.type === 'divider') {
                    const divider = DOM.create('div', { className: 'dropdown-divider' });
                    dropdown.appendChild(divider);
                } else {
                    const menuItem = DOM.create('a', {
                        href: '#',
                        className: 'dropdown-item',
                        onclick: (e) => {
                            e.preventDefault();
                            item.action();
                            this.setState({ userMenuOpen: false });
                        }
                    });

                    const icon = DOM.create('i', { className: item.icon });
                    const label = DOM.create('span', {}, item.label);

                    menuItem.appendChild(icon);
                    menuItem.appendChild(label);
                    dropdown.appendChild(menuItem);
                }
            });

            userMenu.appendChild(dropdown);
        }

        return userMenu;
    }

    toggleNotifications() {
        // This would show a notification dropdown
        console.log('Toggle notifications');
    }

    getUnreadNotificationCount() {
        return this.state.notifications.filter(n => !n.read).length;
    }

    handleLogout() {
        // Handle logout
        State.set('user.isAuthenticated', false);
        State.set('user.currentUser', null);
        Router.navigate('/login');
    }

    // Public API methods
    setActiveItem(itemId) {
        this.props.activeItem = itemId;
        this.forceUpdate();
    }

    setCollapsed(collapsed) {
        this.setState({ collapsed });
    }

    addMenuItem(item) {
        this.props.menuItems.push(item);
        this.forceUpdate();
    }

    removeMenuItem(itemId) {
        this.props.menuItems = this.props.menuItems.filter(item => item.id !== itemId);
        this.forceUpdate();
    }

    updateBreadcrumbs(breadcrumbs) {
        this.setState({ breadcrumbs });
    }
}

// Tab Navigation Component
class TabNavigation extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            tabs: [],
            activeTab: null,
            onTabChange: null,
            closable: false,
            addable: false,
            onAddTab: null,
            ...props
        };

        this.state = {
            activeTab: this.props.activeTab || (this.props.tabs[0] ? this.props.tabs[0].id : null)
        };

        this.handleTabClick = this.handleTabClick.bind(this);
        this.handleTabClose = this.handleTabClose.bind(this);
        this.handleAddTab = this.handleAddTab.bind(this);
    }

    handleTabClick(tabId, event) {
        event.preventDefault();
        this.setState({ activeTab: tabId });

        if (this.props.onTabChange) {
            this.props.onTabChange(tabId);
        }
    }

    handleTabClose(tabId, event) {
        event.preventDefault();
        event.stopPropagation();

        // Remove tab
        this.props.tabs = this.props.tabs.filter(tab => tab.id !== tabId);

        // Set new active tab
        if (this.state.activeTab === tabId) {
            const remainingTabs = this.props.tabs;
            if (remainingTabs.length > 0) {
                this.setState({ activeTab: remainingTabs[0].id });
            } else {
                this.setState({ activeTab: null });
            }
        }

        this.forceUpdate();
    }

    handleAddTab() {
        if (this.props.onAddTab) {
            this.props.onAddTab();
        }
    }

    render() {
        const { tabs, closable, addable } = this.props;
        const { activeTab } = this.state;

        const nav = DOM.create('div', { className: 'tab-navigation' });
        const tabList = DOM.create('div', { className: 'tab-list' });

        tabs.forEach(tab => {
            const tabElement = DOM.create('div', {
                className: `tab-item ${tab.id === activeTab ? 'active' : ''}`,
                onclick: (e) => this.handleTabClick(tab.id, e)
            });

            // Tab icon
            if (tab.icon) {
                const icon = DOM.create('i', { className: tab.icon });
                tabElement.appendChild(icon);
            }

            // Tab title
            const title = DOM.create('span', { className: 'tab-title' }, tab.title);
            tabElement.appendChild(title);

            // Tab badge
            if (tab.badge) {
                const badge = DOM.create('span', { className: 'tab-badge' }, tab.badge.toString());
                tabElement.appendChild(badge);
            }

            // Close button
            if (closable && tab.closable !== false) {
                const closeBtn = DOM.create('button', {
                    className: 'tab-close',
                    'aria-label': 'Close tab',
                    onclick: (e) => this.handleTabClose(tab.id, e)
                });
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                tabElement.appendChild(closeBtn);
            }

            tabList.appendChild(tabElement);
        });

        // Add tab button
        if (addable) {
            const addTab = DOM.create('button', {
                className: 'tab-add',
                'aria-label': 'Add tab',
                onclick: this.handleAddTab
            });
            addTab.innerHTML = '<i class="fas fa-plus"></i>';
            tabList.appendChild(addTab);
        }

        nav.appendChild(tabList);

        // Tab content
        if (activeTab) {
            const activeTabData = tabs.find(tab => tab.id === activeTab);
            if (activeTabData && activeTabData.content) {
                const content = DOM.create('div', { className: 'tab-content' });

                if (typeof activeTabData.content === 'string') {
                    content.innerHTML = activeTabData.content;
                } else if (activeTabData.content instanceof Node) {
                    content.appendChild(activeTabData.content);
                }

                nav.appendChild(content);
            }
        }

        return nav;
    }
}

// Register components
ComponentRegistry.register('Navigation', Navigation);
ComponentRegistry.register('TabNavigation', TabNavigation);

// Make globally available
window.Navigation = Navigation;
window.TabNavigation = TabNavigation;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Navigation, TabNavigation };
}
