/**
 * TPT Free ERP - Notification Component
 * Advanced notification and alert system with various types and display modes
 */

class Notification extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'info', // success, error, warning, info
            title: '',
            message: '',
            duration: null, // null = persistent, number = auto-hide after ms
            closable: true,
            showIcon: true,
            position: 'top-right', // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
            zIndex: 1060,
            onClose: null,
            onClick: null,
            actions: [], // array of action buttons
            persistent: false, // if true, won't auto-hide
            sound: false, // play sound on show
            ...props
        };

        this.state = {
            isVisible: true,
            isAnimating: false,
            remainingTime: this.props.duration
        };

        // Bind methods
        this.handleClose = this.handleClose.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleAction = this.handleAction.bind(this);
        this.startTimer = this.startTimer.bind(this);
        this.clearTimer = this.clearTimer.bind(this);
        this.playSound = this.playSound.bind(this);
    }

    componentDidMount() {
        if (this.props.sound) {
            this.playSound();
        }

        if (this.props.duration && !this.props.persistent) {
            this.startTimer();
        }
    }

    componentWillUnmount() {
        this.clearTimer();
    }

    startTimer() {
        this.timer = setTimeout(() => {
            this.handleClose();
        }, this.props.duration);
    }

    clearTimer() {
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    }

    playSound() {
        try {
            // Create audio context for notification sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Configure sound based on type
            const frequencies = {
                success: 800,
                error: 400,
                warning: 600,
                info: 500
            };

            oscillator.frequency.setValueAtTime(frequencies[this.props.type] || 500, audioContext.currentTime);
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.warn('Could not play notification sound:', error);
        }
    }

    handleClose() {
        this.setState({ isAnimating: true });

        setTimeout(() => {
            this.setState({ isVisible: false });
            if (this.props.onClose) {
                this.props.onClose();
            }
        }, 300);
    }

    handleClick() {
        if (this.props.onClick) {
            this.props.onClick();
        }
    }

    handleAction(action) {
        if (action.onClick) {
            action.onClick(action);
        }
    }

    render() {
        if (!this.state.isVisible) return null;

        const {
            type,
            title,
            message,
            closable,
            showIcon,
            position,
            zIndex,
            actions
        } = this.props;

        const notificationClasses = [
            'notification',
            `notification-${type}`,
            `notification-${position}`,
            this.state.isAnimating ? 'animating' : '',
            closable ? 'closable' : ''
        ].filter(Boolean).join(' ');

        const notification = DOM.create('div', {
            className: notificationClasses,
            style: `z-index: ${zIndex}`,
            onclick: this.handleClick
        });

        // Icon
        if (showIcon) {
            const icon = DOM.create('div', { className: 'notification-icon' });
            const iconClass = this.getIconClass(type);
            icon.innerHTML = `<i class="${iconClass}"></i>`;
            notification.appendChild(icon);
        }

        // Content
        const content = DOM.create('div', { className: 'notification-content' });

        // Title
        if (title) {
            const titleElement = DOM.create('div', { className: 'notification-title' }, title);
            content.appendChild(titleElement);
        }

        // Message
        if (message) {
            const messageElement = DOM.create('div', { className: 'notification-message' }, message);
            content.appendChild(messageElement);
        }

        notification.appendChild(content);

        // Actions
        if (actions && actions.length > 0) {
            const actionsContainer = DOM.create('div', { className: 'notification-actions' });

            actions.forEach(action => {
                const actionBtn = DOM.create('button', {
                    className: `notification-action ${action.className || ''}`,
                    onclick: (e) => {
                        e.stopPropagation();
                        this.handleAction(action);
                    }
                }, action.label);

                actionsContainer.appendChild(actionBtn);
            });

            notification.appendChild(actionsContainer);
        }

        // Close button
        if (closable) {
            const closeBtn = DOM.create('button', {
                className: 'notification-close',
                'aria-label': 'Close notification',
                onclick: (e) => {
                    e.stopPropagation();
                    this.handleClose();
                }
            });
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            notification.appendChild(closeBtn);
        }

        // Progress bar for timed notifications
        if (this.props.duration && !this.props.persistent) {
            const progressBar = DOM.create('div', { className: 'notification-progress' });
            const progressFill = DOM.create('div', {
                className: 'notification-progress-fill',
                style: `animation-duration: ${this.props.duration}ms`
            });
            progressBar.appendChild(progressFill);
            notification.appendChild(progressBar);
        }

        return notification;
    }

    getIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// Toast Notification Manager
class ToastManager {
    constructor() {
        this.toasts = new Map();
        this.container = null;
        this.positions = {
            'top-right': [],
            'top-left': [],
            'bottom-right': [],
            'bottom-left': [],
            'top-center': [],
            'bottom-center': []
        };
    }

    init() {
        if (!this.container) {
            this.container = DOM.create('div', { className: 'toast-container' });
            document.body.appendChild(this.container);
        }
    }

    show(options = {}) {
        this.init();

        const toast = new Notification({
            ...options,
            onClose: () => {
                this.remove(toast.id);
                if (options.onClose) {
                    options.onClose();
                }
            }
        });

        const id = `toast_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        toast.id = id;

        this.toasts.set(id, toast);
        this.positions[options.position || 'top-right'].push(id);

        // Mount toast
        if (this.container) {
            toast.mount(this.container);
        }

        return toast;
    }

    remove(id) {
        const toast = this.toasts.get(id);
        if (toast) {
            // Remove from positions
            Object.keys(this.positions).forEach(position => {
                const index = this.positions[position].indexOf(id);
                if (index > -1) {
                    this.positions[position].splice(index, 1);
                }
            });

            this.toasts.delete(id);
        }
    }

    clear(position = null) {
        if (position) {
            this.positions[position].forEach(id => {
                const toast = this.toasts.get(id);
                if (toast) {
                    toast.handleClose();
                }
            });
            this.positions[position] = [];
        } else {
            this.toasts.forEach(toast => toast.handleClose());
            Object.keys(this.positions).forEach(pos => {
                this.positions[pos] = [];
            });
        }
    }

    // Convenience methods
    success(message, options = {}) {
        return this.show({
            type: 'success',
            message,
            duration: 5000,
            ...options
        });
    }

    error(message, options = {}) {
        return this.show({
            type: 'error',
            message,
            duration: 7000,
            ...options
        });
    }

    warning(message, options = {}) {
        return this.show({
            type: 'warning',
            message,
            duration: 6000,
            ...options
        });
    }

    info(message, options = {}) {
        return this.show({
            type: 'info',
            message,
            duration: 5000,
            ...options
        });
    }
}

// Alert Dialog Component
class Alert extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'info', // success, error, warning, info, question
            title: '',
            message: '',
            confirmText: 'OK',
            cancelText: 'Cancel',
            showCancel: false,
            onConfirm: null,
            onCancel: null,
            ...props
        };

        this.handleConfirm = this.handleConfirm.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
    }

    handleConfirm() {
        if (this.props.onConfirm) {
            this.props.onConfirm();
        }
        this.close();
    }

    handleCancel() {
        if (this.props.onCancel) {
            this.props.onCancel();
        }
        this.close();
    }

    close() {
        // This would be handled by the modal system
        if (this.modal) {
            this.modal.hide();
        }
    }

    render() {
        const { type, title, message, confirmText, cancelText, showCancel } = this.props;

        const alert = DOM.create('div', { className: `alert alert-${type}` });

        // Icon
        const icon = DOM.create('div', { className: 'alert-icon' });
        const iconClass = this.getAlertIconClass(type);
        icon.innerHTML = `<i class="${iconClass}"></i>`;
        alert.appendChild(icon);

        // Content
        const content = DOM.create('div', { className: 'alert-content' });

        if (title) {
            const titleElement = DOM.create('h3', { className: 'alert-title' }, title);
            content.appendChild(titleElement);
        }

        if (message) {
            const messageElement = DOM.create('p', { className: 'alert-message' }, message);
            content.appendChild(messageElement);
        }

        alert.appendChild(content);

        // Actions
        const actions = DOM.create('div', { className: 'alert-actions' });

        if (showCancel) {
            const cancelBtn = DOM.create('button', {
                className: 'btn btn-secondary',
                onclick: this.handleCancel
            }, cancelText);
            actions.appendChild(cancelBtn);
        }

        const confirmBtn = DOM.create('button', {
            className: `btn btn-${this.getButtonClass(type)}`,
            onclick: this.handleConfirm
        }, confirmText);
        actions.appendChild(confirmBtn);

        alert.appendChild(actions);

        return alert;
    }

    getAlertIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
            question: 'fas fa-question-circle'
        };
        return icons[type] || icons.info;
    }

    getButtonClass(type) {
        const classes = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info',
            question: 'primary'
        };
        return classes[type] || 'primary';
    }

    // Static methods for easy creation
    static show(options = {}) {
        return Modal.alert(options);
    }
}

// Notification Center Component
class NotificationCenter extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            position: 'dropdown', // dropdown, panel, sidebar
            maxItems: 50,
            showUnreadBadge: true,
            autoRefresh: true,
            refreshInterval: 30000, // 30 seconds
            onMarkAsRead: null,
            onMarkAllAsRead: null,
            onClearAll: null,
            ...props
        };

        this.state = {
            notifications: [],
            unreadCount: 0,
            isOpen: false,
            isLoading: false
        };

        // Bind methods
        this.loadNotifications = this.loadNotifications.bind(this);
        this.markAsRead = this.markAsRead.bind(this);
        this.markAllAsRead = this.markAllAsRead.bind(this);
        this.clearAll = this.clearAll.bind(this);
        this.toggleOpen = this.toggleOpen.bind(this);
        this.startAutoRefresh = this.startAutoRefresh.bind(this);
        this.stopAutoRefresh = this.stopAutoRefresh.bind(this);
    }

    componentDidMount() {
        this.loadNotifications();
        if (this.props.autoRefresh) {
            this.startAutoRefresh();
        }
    }

    componentWillUnmount() {
        this.stopAutoRefresh();
    }

    startAutoRefresh() {
        this.refreshTimer = setInterval(() => {
            this.loadNotifications();
        }, this.props.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    async loadNotifications() {
        try {
            this.setState({ isLoading: true });

            // This would load from API
            const notifications = [
                {
                    id: 1,
                    title: 'New order received',
                    message: 'Order #1234 has been placed by customer ABC Corp',
                    type: 'info',
                    read: false,
                    timestamp: new Date(Date.now() - 1000 * 60 * 5),
                    actions: [
                        { label: 'View Order', action: () => Router.navigate('/sales/orders/1234') }
                    ]
                },
                {
                    id: 2,
                    title: 'Payment overdue',
                    message: 'Invoice #5678 is 7 days overdue',
                    type: 'warning',
                    read: false,
                    timestamp: new Date(Date.now() - 1000 * 60 * 30),
                    actions: [
                        { label: 'View Invoice', action: () => Router.navigate('/finance/invoices/5678') }
                    ]
                },
                {
                    id: 3,
                    title: 'System maintenance',
                    message: 'Scheduled maintenance will begin in 2 hours',
                    type: 'info',
                    read: true,
                    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2)
                }
            ];

            const unreadCount = notifications.filter(n => !n.read).length;

            this.setState({
                notifications,
                unreadCount,
                isLoading: false
            });

        } catch (error) {
            console.error('Failed to load notifications:', error);
            this.setState({ isLoading: false });
        }
    }

    markAsRead(notificationId) {
        const notifications = this.state.notifications.map(notification =>
            notification.id === notificationId
                ? { ...notification, read: true }
                : notification
        );

        const unreadCount = notifications.filter(n => !n.read).length;

        this.setState({ notifications, unreadCount });

        if (this.props.onMarkAsRead) {
            this.props.onMarkAsRead(notificationId);
        }
    }

    markAllAsRead() {
        const notifications = this.state.notifications.map(notification => ({
            ...notification,
            read: true
        }));

        this.setState({ notifications, unreadCount: 0 });

        if (this.props.onMarkAllAsRead) {
            this.props.onMarkAllAsRead();
        }
    }

    clearAll() {
        this.setState({ notifications: [], unreadCount: 0 });

        if (this.props.onClearAll) {
            this.props.onClearAll();
        }
    }

    toggleOpen() {
        this.setState({ isOpen: !this.state.isOpen });
    }

    render() {
        const { position, showUnreadBadge } = this.props;
        const { notifications, unreadCount, isOpen, isLoading } = this.state;

        if (position === 'dropdown') {
            return this.renderDropdown();
        } else if (position === 'panel') {
            return this.renderPanel();
        } else if (position === 'sidebar') {
            return this.renderSidebar();
        }

        return this.renderDropdown(); // default
    }

    renderDropdown() {
        const { showUnreadBadge } = this.props;
        const { notifications, unreadCount, isOpen, isLoading } = this.state;

        const dropdown = DOM.create('div', { className: 'notification-dropdown' });

        // Trigger button
        const trigger = DOM.create('button', {
            className: 'notification-trigger',
            onclick: this.toggleOpen,
            'aria-label': 'Notifications'
        });

        trigger.innerHTML = '<i class="fas fa-bell"></i>';

        if (showUnreadBadge && unreadCount > 0) {
            const badge = DOM.create('span', { className: 'notification-badge' });
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
            trigger.appendChild(badge);
        }

        dropdown.appendChild(trigger);

        // Dropdown menu
        if (isOpen) {
            const menu = DOM.create('div', { className: 'notification-menu' });

            // Header
            const header = DOM.create('div', { className: 'notification-header' });
            const title = DOM.create('h3', {}, 'Notifications');

            const actions = DOM.create('div', { className: 'notification-actions' });

            if (unreadCount > 0) {
                const markAllReadBtn = DOM.create('button', {
                    className: 'btn btn-link btn-sm',
                    onclick: this.markAllAsRead
                }, 'Mark all read');
                actions.appendChild(markAllReadBtn);
            }

            const clearAllBtn = DOM.create('button', {
                className: 'btn btn-link btn-sm',
                onclick: this.clearAll
            }, 'Clear all');
            actions.appendChild(clearAllBtn);

            header.appendChild(title);
            header.appendChild(actions);
            menu.appendChild(header);

            // Content
            const content = DOM.create('div', { className: 'notification-content' });

            if (isLoading) {
                const loading = DOM.create('div', { className: 'notification-loading' });
                loading.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                content.appendChild(loading);
            } else if (notifications.length === 0) {
                const empty = DOM.create('div', { className: 'notification-empty' });
                empty.innerHTML = '<i class="fas fa-bell-slash"></i> No notifications';
                content.appendChild(empty);
            } else {
                const list = DOM.create('div', { className: 'notification-list' });

                notifications.slice(0, 10).forEach(notification => {
                    const item = this.renderNotificationItem(notification);
                    list.appendChild(item);
                });

                content.appendChild(list);
            }

            menu.appendChild(content);
            dropdown.appendChild(menu);
        }

        return dropdown;
    }

    renderPanel() {
        // Panel version would be more complex
        return this.renderDropdown();
    }

    renderSidebar() {
        // Sidebar version would be more complex
        return this.renderDropdown();
    }

    renderNotificationItem(notification) {
        const item = DOM.create('div', {
            className: `notification-item ${notification.read ? 'read' : 'unread'}`,
            onclick: () => {
                if (!notification.read) {
                    this.markAsRead(notification.id);
                }
                // Handle click action
            }
        });

        // Icon
        const icon = DOM.create('div', { className: 'notification-icon' });
        const iconClass = this.getNotificationIconClass(notification.type);
        icon.innerHTML = `<i class="${iconClass}"></i>`;
        item.appendChild(icon);

        // Content
        const content = DOM.create('div', { className: 'notification-content' });
        const title = DOM.create('div', { className: 'notification-title' }, notification.title);
        const message = DOM.create('div', { className: 'notification-message' }, notification.message);
        const time = DOM.create('div', { className: 'notification-time' }, this.formatTimeAgo(notification.timestamp));

        content.appendChild(title);
        content.appendChild(message);
        content.appendChild(time);
        item.appendChild(content);

        // Actions
        if (notification.actions && notification.actions.length > 0) {
            const actions = DOM.create('div', { className: 'notification-item-actions' });

            notification.actions.forEach(action => {
                const actionBtn = DOM.create('button', {
                    className: 'btn btn-link btn-sm',
                    onclick: (e) => {
                        e.stopPropagation();
                        action.action();
                    }
                }, action.label);
                actions.appendChild(actionBtn);
            });

            item.appendChild(actions);
        }

        return item;
    }

    getNotificationIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    formatTimeAgo(timestamp) {
        if (!timestamp) return '';

        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
    }
}

// Global instances
const ToastManagerInstance = new ToastManager();

// Register components
ComponentRegistry.register('Notification', Notification);
ComponentRegistry.register('Alert', Alert);
ComponentRegistry.register('NotificationCenter', NotificationCenter);

// Make globally available
window.Notification = Notification;
window.Alert = Alert;
window.NotificationCenter = NotificationCenter;
window.Toast = ToastManagerInstance;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        Notification,
        Alert,
        NotificationCenter,
        ToastManager
    };
}
