/**
 * TPT Free ERP - Dashboard Component
 * Main dashboard with widgets and key metrics
 */

class Dashboard extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Dashboard',
            showWelcome: true,
            refreshInterval: 300000, // 5 minutes
            widgets: [
                'quick-stats',
                'recent-activity',
                'upcoming-tasks',
                'system-status',
                'notifications'
            ],
            ...props
        };

        this.state = {
            isLoading: true,
            data: {},
            widgets: [],
            lastRefresh: null,
            refreshTimer: null
        };

        // Bind methods
        this.loadDashboardData = this.loadDashboardData.bind(this);
        this.refreshDashboard = this.refreshDashboard.bind(this);
        this.handleWidgetAction = this.handleWidgetAction.bind(this);
        this.renderWidget = this.renderWidget.bind(this);
        this.startAutoRefresh = this.startAutoRefresh.bind(this);
        this.stopAutoRefresh = this.stopAutoRefresh.bind(this);
    }

    async componentDidMount() {
        await this.loadDashboardData();
        this.startAutoRefresh();
    }

    componentWillUnmount() {
        this.stopAutoRefresh();
    }

    async loadDashboardData() {
        try {
            this.setState({ isLoading: true });

            // Load data for all enabled widgets
            const data = {};

            for (const widgetType of this.props.widgets) {
                try {
                    data[widgetType] = await this.loadWidgetData(widgetType);
                } catch (error) {
                    console.warn(`Failed to load ${widgetType} widget data:`, error);
                    data[widgetType] = { error: true };
                }
            }

            this.setState({
                data,
                lastRefresh: new Date(),
                isLoading: false
            });

        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            this.setState({ isLoading: false });
            App.showNotification({
                type: 'error',
                message: 'Failed to load dashboard data'
            });
        }
    }

    async loadWidgetData(widgetType) {
        switch (widgetType) {
            case 'quick-stats':
                return await API.get('/dashboard/stats');
            case 'recent-activity':
                return await API.get('/dashboard/activity');
            case 'upcoming-tasks':
                return await API.get('/dashboard/tasks');
            case 'system-status':
                return await API.get('/dashboard/system-status');
            case 'notifications':
                return await API.get('/dashboard/notifications');
            default:
                return {};
        }
    }

    refreshDashboard() {
        this.loadDashboardData();
    }

    startAutoRefresh() {
        if (this.props.refreshInterval > 0) {
            this.state.refreshTimer = setInterval(() => {
                this.loadDashboardData();
            }, this.props.refreshInterval);
        }
    }

    stopAutoRefresh() {
        if (this.state.refreshTimer) {
            clearInterval(this.state.refreshTimer);
            this.state.refreshTimer = null;
        }
    }

    handleWidgetAction(widgetType, action, data) {
        switch (action) {
            case 'view-all':
                Router.navigate(`/${widgetType}`);
                break;
            case 'create':
                this.handleCreateAction(widgetType, data);
                break;
            case 'edit':
                this.handleEditAction(widgetType, data);
                break;
            case 'delete':
                this.handleDeleteAction(widgetType, data);
                break;
            default:
                console.log(`Unknown action: ${action} for widget: ${widgetType}`);
        }
    }

    handleCreateAction(widgetType, data) {
        // Handle create actions based on widget type
        switch (widgetType) {
            case 'upcoming-tasks':
                Router.navigate('/projects/tasks/create');
                break;
            case 'notifications':
                // Maybe open notification settings
                break;
            default:
                console.log(`Create action for ${widgetType}`);
        }
    }

    handleEditAction(widgetType, data) {
        // Handle edit actions
        console.log(`Edit action for ${widgetType}:`, data);
    }

    handleDeleteAction(widgetType, data) {
        // Handle delete actions with confirmation
        if (confirm('Are you sure you want to delete this item?')) {
            console.log(`Delete action for ${widgetType}:`, data);
        }
    }

    render() {
        const { title, showWelcome, widgets } = this.props;
        const { isLoading, data, lastRefresh } = this.state;
        const user = State.get('user.currentUser');

        const dashboard = DOM.create('div', { className: 'dashboard' });

        // Header
        const header = DOM.create('div', { className: 'dashboard-header' });
        const titleElement = DOM.create('h1', { className: 'dashboard-title' }, title);

        const actions = DOM.create('div', { className: 'dashboard-actions' });

        const refreshBtn = DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: this.refreshDashboard,
            disabled: isLoading
        });
        refreshBtn.innerHTML = isLoading ?
            '<i class="fas fa-spinner fa-spin"></i> Loading...' :
            '<i class="fas fa-sync-alt"></i> Refresh';

        actions.appendChild(refreshBtn);

        if (lastRefresh) {
            const lastRefreshText = DOM.create('span', {
                className: 'last-refresh'
            }, `Last updated: ${lastRefresh.toLocaleTimeString()}`);
            actions.appendChild(lastRefreshText);
        }

        header.appendChild(titleElement);
        header.appendChild(actions);
        dashboard.appendChild(header);

        // Welcome message
        if (showWelcome && user) {
            const welcome = DOM.create('div', { className: 'dashboard-welcome' });
            const welcomeText = DOM.create('h2', { className: 'welcome-text' },
                `Welcome back, ${user.first_name || user.username || 'User'}!`);
            const welcomeSubtext = DOM.create('p', { className: 'welcome-subtext' },
                'Here\'s what\'s happening with your business today.');
            welcome.appendChild(welcomeText);
            welcome.appendChild(welcomeSubtext);
            dashboard.appendChild(welcome);
        }

        // Widgets grid
        const widgetsGrid = DOM.create('div', { className: 'dashboard-widgets' });

        if (isLoading && !lastRefresh) {
            // Show loading skeleton
            widgetsGrid.appendChild(this.renderLoadingSkeleton());
        } else {
            // Render actual widgets
            widgets.forEach(widgetType => {
                const widgetData = data[widgetType] || {};
                const widget = this.renderWidget(widgetType, widgetData);
                if (widget) {
                    widgetsGrid.appendChild(widget);
                }
            });
        }

        dashboard.appendChild(widgetsGrid);

        return dashboard;
    }

    renderLoadingSkeleton() {
        const skeleton = DOM.create('div', { className: 'widget-skeleton' });

        for (let i = 0; i < 6; i++) {
            const skeletonWidget = DOM.create('div', { className: 'skeleton-widget' });
            const skeletonHeader = DOM.create('div', { className: 'skeleton-header' });
            const skeletonContent = DOM.create('div', { className: 'skeleton-content' });

            skeletonWidget.appendChild(skeletonHeader);
            skeletonWidget.appendChild(skeletonContent);
            skeleton.appendChild(skeletonWidget);
        }

        return skeleton;
    }

    renderWidget(widgetType, data) {
        if (data.error) {
            return this.renderErrorWidget(widgetType);
        }

        switch (widgetType) {
            case 'quick-stats':
                return this.renderQuickStatsWidget(data);
            case 'recent-activity':
                return this.renderRecentActivityWidget(data);
            case 'upcoming-tasks':
                return this.renderUpcomingTasksWidget(data);
            case 'system-status':
                return this.renderSystemStatusWidget(data);
            case 'notifications':
                return this.renderNotificationsWidget(data);
            default:
                return null;
        }
    }

    renderQuickStatsWidget(data) {
        const widget = DOM.create('div', { className: 'widget quick-stats-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, 'Quick Stats');
        const actions = DOM.create('div', { className: 'widget-actions' });
        const viewAllBtn = DOM.create('button', {
            className: 'btn btn-link btn-sm',
            onclick: () => this.handleWidgetAction('quick-stats', 'view-all')
        }, 'View All');

        actions.appendChild(viewAllBtn);
        header.appendChild(title);
        header.appendChild(actions);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const statsGrid = DOM.create('div', { className: 'stats-grid' });

        const stats = [
            { label: 'Active Projects', value: data.activeProjects || 0, icon: 'fas fa-project-diagram', color: 'primary' },
            { label: 'Pending Tasks', value: data.pendingTasks || 0, icon: 'fas fa-tasks', color: 'warning' },
            { label: 'Open Orders', value: data.openOrders || 0, icon: 'fas fa-shopping-cart', color: 'success' },
            { label: 'Revenue (MTD)', value: `$${data.monthlyRevenue || 0}`, icon: 'fas fa-dollar-sign', color: 'info' }
        ];

        stats.forEach(stat => {
            const statItem = DOM.create('div', { className: 'stat-item' });
            const icon = DOM.create('div', { className: `stat-icon ${stat.color}` });
            icon.innerHTML = `<i class="${stat.icon}"></i>`;

            const value = DOM.create('div', { className: 'stat-value' }, stat.value.toString());
            const label = DOM.create('div', { className: 'stat-label' }, stat.label);

            statItem.appendChild(icon);
            statItem.appendChild(value);
            statItem.appendChild(label);
            statsGrid.appendChild(statItem);
        });

        content.appendChild(statsGrid);
        widget.appendChild(content);

        return widget;
    }

    renderRecentActivityWidget(data) {
        const widget = DOM.create('div', { className: 'widget recent-activity-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, 'Recent Activity');
        const actions = DOM.create('div', { className: 'widget-actions' });
        const viewAllBtn = DOM.create('button', {
            className: 'btn btn-link btn-sm',
            onclick: () => this.handleWidgetAction('recent-activity', 'view-all')
        }, 'View All');

        actions.appendChild(viewAllBtn);
        header.appendChild(title);
        header.appendChild(actions);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const activityList = DOM.create('div', { className: 'activity-list' });

        const activities = data.activities || [];

        if (activities.length === 0) {
            const emptyState = DOM.create('div', { className: 'empty-state' });
            emptyState.innerHTML = `
                <i class="fas fa-history"></i>
                <p>No recent activity</p>
            `;
            activityList.appendChild(emptyState);
        } else {
            activities.slice(0, 5).forEach(activity => {
                const activityItem = DOM.create('div', { className: 'activity-item' });
                const icon = DOM.create('div', { className: 'activity-icon' });
                icon.innerHTML = `<i class="${activity.icon || 'fas fa-circle'}"></i>`;

                const content = DOM.create('div', { className: 'activity-content' });
                const text = DOM.create('div', { className: 'activity-text' }, activity.description);
                const time = DOM.create('div', { className: 'activity-time' }, this.formatTimeAgo(activity.timestamp));

                content.appendChild(text);
                content.appendChild(time);

                activityItem.appendChild(icon);
                activityItem.appendChild(content);
                activityList.appendChild(activityItem);
            });
        }

        content.appendChild(activityList);
        widget.appendChild(content);

        return widget;
    }

    renderUpcomingTasksWidget(data) {
        const widget = DOM.create('div', { className: 'widget upcoming-tasks-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, 'Upcoming Tasks');
        const actions = DOM.create('div', { className: 'widget-actions' });
        const createBtn = DOM.create('button', {
            className: 'btn btn-primary btn-sm',
            onclick: () => this.handleWidgetAction('upcoming-tasks', 'create')
        }, 'Create Task');

        actions.appendChild(createBtn);
        header.appendChild(title);
        header.appendChild(actions);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const tasksList = DOM.create('div', { className: 'tasks-list' });

        const tasks = data.tasks || [];

        if (tasks.length === 0) {
            const emptyState = DOM.create('div', { className: 'empty-state' });
            emptyState.innerHTML = `
                <i class="fas fa-tasks"></i>
                <p>No upcoming tasks</p>
            `;
            tasksList.appendChild(emptyState);
        } else {
            tasks.slice(0, 5).forEach(task => {
                const taskItem = DOM.create('div', { className: 'task-item' });
                const checkbox = DOM.create('input', {
                    type: 'checkbox',
                    className: 'task-checkbox',
                    checked: task.completed,
                    onchange: () => this.handleTaskToggle(task.id)
                });

                const content = DOM.create('div', { className: 'task-content' });
                const title = DOM.create('div', { className: 'task-title' }, task.title);
                const meta = DOM.create('div', { className: 'task-meta' });
                meta.innerHTML = `
                    <span class="task-project">${task.project}</span>
                    <span class="task-due">${this.formatDate(task.dueDate)}</span>
                `;

                content.appendChild(title);
                content.appendChild(meta);

                taskItem.appendChild(checkbox);
                taskItem.appendChild(content);
                tasksList.appendChild(taskItem);
            });
        }

        content.appendChild(tasksList);
        widget.appendChild(content);

        return widget;
    }

    renderSystemStatusWidget(data) {
        const widget = DOM.create('div', { className: 'widget system-status-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, 'System Status');
        header.appendChild(title);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const statusGrid = DOM.create('div', { className: 'status-grid' });

        const statuses = [
            { name: 'Database', status: data.database || 'online', icon: 'fas fa-database' },
            { name: 'API', status: data.api || 'online', icon: 'fas fa-server' },
            { name: 'Storage', status: data.storage || 'online', icon: 'fas fa-hdd' },
            { name: 'Email', status: data.email || 'online', icon: 'fas fa-envelope' }
        ];

        statuses.forEach(status => {
            const statusItem = DOM.create('div', { className: `status-item ${status.status}` });
            const icon = DOM.create('div', { className: 'status-icon' });
            icon.innerHTML = `<i class="${status.icon}"></i>`;

            const info = DOM.create('div', { className: 'status-info' });
            const name = DOM.create('div', { className: 'status-name' }, status.name);
            const statusBadge = DOM.create('div', { className: `status-badge ${status.status}` }, status.status);

            info.appendChild(name);
            info.appendChild(statusBadge);

            statusItem.appendChild(icon);
            statusItem.appendChild(info);
            statusGrid.appendChild(statusItem);
        });

        content.appendChild(statusGrid);
        widget.appendChild(content);

        return widget;
    }

    renderNotificationsWidget(data) {
        const widget = DOM.create('div', { className: 'widget notifications-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, 'Notifications');
        const actions = DOM.create('div', { className: 'widget-actions' });
        const viewAllBtn = DOM.create('button', {
            className: 'btn btn-link btn-sm',
            onclick: () => this.handleWidgetAction('notifications', 'view-all')
        }, 'View All');

        actions.appendChild(viewAllBtn);
        header.appendChild(title);
        header.appendChild(actions);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const notificationsList = DOM.create('div', { className: 'notifications-list' });

        const notifications = data.notifications || [];

        if (notifications.length === 0) {
            const emptyState = DOM.create('div', { className: 'empty-state' });
            emptyState.innerHTML = `
                <i class="fas fa-bell"></i>
                <p>No new notifications</p>
            `;
            notificationsList.appendChild(emptyState);
        } else {
            notifications.slice(0, 5).forEach(notification => {
                const notificationItem = DOM.create('div', { className: `notification-item ${notification.read ? 'read' : 'unread'}` });
                const icon = DOM.create('div', { className: 'notification-icon' });
                icon.innerHTML = `<i class="${notification.icon || 'fas fa-info-circle'}"></i>`;

                const content = DOM.create('div', { className: 'notification-content' });
                const text = DOM.create('div', { className: 'notification-text' }, notification.message);
                const time = DOM.create('div', { className: 'notification-time' }, this.formatTimeAgo(notification.timestamp));

                content.appendChild(text);
                content.appendChild(time);

                notificationItem.appendChild(icon);
                notificationItem.appendChild(content);
                notificationsList.appendChild(notificationItem);
            });
        }

        content.appendChild(notificationsList);
        widget.appendChild(content);

        return widget;
    }

    renderErrorWidget(widgetType) {
        const widget = DOM.create('div', { className: 'widget error-widget' });

        const header = DOM.create('div', { className: 'widget-header' });
        const title = DOM.create('h3', { className: 'widget-title' }, StringUtils.capitalize(widgetType.replace('-', ' ')));
        header.appendChild(title);
        widget.appendChild(header);

        const content = DOM.create('div', { className: 'widget-content' });
        const error = DOM.create('div', { className: 'error-state' });
        error.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <p>Failed to load ${widgetType} data</p>
            <button class="btn btn-sm btn-secondary" onclick="this.refreshDashboard()">
                <i class="fas fa-redo"></i> Retry
            </button>
        `;
        content.appendChild(error);
        widget.appendChild(content);

        return widget;
    }

    async handleTaskToggle(taskId) {
        try {
            await API.patch(`/tasks/${taskId}`, { completed: true });
            this.refreshDashboard();
            App.showNotification({
                type: 'success',
                message: 'Task completed!'
            });
        } catch (error) {
            console.error('Failed to update task:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to update task'
            });
        }
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

    formatDate(dateString) {
        if (!dateString) return '';

        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
}

// Register component
ComponentRegistry.register('Dashboard', Dashboard);

// Make globally available
window.Dashboard = Dashboard;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Dashboard;
}
