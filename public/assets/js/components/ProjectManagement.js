/**
 * TPT Free ERP - Project Management Component (Refactored)
 * Complete project planning, task management, and resource allocation interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class ProjectManagement extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderers for different data types
        this.projectsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.tasksTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.timeEntriesTableRenderer = this.createTableRenderer({
            selectable: false,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.projectsTableRenderer.setDataCallback(() => this.state.projects || []);
        this.projectsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedProjects: selectedIds });
        });
        this.projectsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.projectsTableRenderer.setDataChangeCallback(() => {
            this.loadProjects();
        });

        this.tasksTableRenderer.setDataCallback(() => this.state.tasks || []);
        this.tasksTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedTasks: selectedIds });
        });
        this.tasksTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.tasksTableRenderer.setDataChangeCallback(() => {
            this.loadTasks();
        });

        this.timeEntriesTableRenderer.setDataCallback(() => this.state.timeEntries || []);
        this.timeEntriesTableRenderer.setDataChangeCallback(() => {
            this.loadTimeEntries();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadProjects',
            'loadTasks',
            'loadTimeEntries',
            'loadGanttData',
            'loadResourceUtilization',
            'loadTemplates',
            'loadAnalytics',
            'handleViewChange',
            'handleFilterChange',
            'handleProjectSelect',
            'handleTaskSelect',
            'handleBulkAction',
            'showProjectModal',
            'hideProjectModal',
            'saveProject',
            'showTaskModal',
            'hideTaskModal',
            'saveTask',
            'startTimeTracking',
            'stopTimeTracking',
            'createTaskDependency',
            'updateProjectProgress',
            'updateTaskProgress'
        ];
    }

    async componentDidMount() {
        await this.loadInitialData();
        this.startTimerCheck();
    }

    componentWillUnmount() {
        if (this.timerCheckInterval) {
            clearInterval(this.timerCheckInterval);
        }
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load basic data
            await Promise.all([
                this.checkActiveTimer()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            this.showNotification('Failed to load project management data', 'error');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'projects':
                await this.loadProjects();
                break;
            case 'tasks':
                await this.loadTasks();
                break;
            case 'time-tracking':
                await this.loadTimeEntries();
                break;
            case 'gantt':
                await this.loadGanttData();
                break;
            case 'resources':
                await this.loadResourceUtilization();
                break;
            case 'templates':
                await this.loadTemplates();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await this.apiRequest('/project-management/overview');
            this.setState({ overview: response });
        } catch (error) {
            this.showNotification('Failed to load project overview', 'error');
        }
    }

    async loadProjects() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/project-management/projects?${params}`);
            this.setState({
                projects: response.projects,
                pagination: response.pagination
            });
        } catch (error) {
            this.showNotification('Failed to load projects', 'error');
        }
    }

    async loadTasks() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/project-management/tasks?${params}`);
            this.setState({
                tasks: response.tasks,
                pagination: response.pagination
            });
        } catch (error) {
            this.showNotification('Failed to load tasks', 'error');
        }
    }

    async loadTimeEntries() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/project-management/time-entries?${params}`);
            this.setState({
                timeEntries: response.time_entries,
                pagination: response.pagination
            });
        } catch (error) {
            this.showNotification('Failed to load time entries', 'error');
        }
    }

    async loadGanttData() {
        try {
            const response = await this.apiRequest('/project-management/gantt');
            this.setState({ ganttData: response });
        } catch (error) {
            this.showNotification('Failed to load Gantt data', 'error');
        }
    }

    async loadResourceUtilization() {
        try {
            const response = await this.apiRequest('/project-management/resources/utilization');
            this.setState({ resourceUtilization: response });
        } catch (error) {
            this.showNotification('Failed to load resource utilization', 'error');
        }
    }

    async loadTemplates() {
        try {
            const response = await this.apiRequest('/project-management/templates');
            this.setState({ templates: response });
        } catch (error) {
            this.showNotification('Failed to load templates', 'error');
        }
    }

    async loadAnalytics() {
        try {
            const response = await this.apiRequest('/project-management/analytics');
            this.setState({ analytics: response });
        } catch (error) {
            this.showNotification('Failed to load project analytics', 'error');
        }
    }

    async checkActiveTimer() {
        try {
            // This would be implemented to check for active timers
            // For now, we'll set it to null
            this.setState({ activeTimer: null });
        } catch (error) {
            console.error('Error checking active timer:', error);
        }
    }

    startTimerCheck() {
        this.timerCheckInterval = setInterval(() => {
            this.checkActiveTimer();
        }, 30000); // Check every 30 seconds
    }

    handleViewChange(view) {
        this.setState({ currentView: view }, () => {
            this.loadCurrentViewData();
        });
    }

    handleFilterChange(filterName, value) {
        const newFilters = { ...this.state.filters, [filterName]: value };
        this.setState({
            filters: newFilters,
            pagination: { ...this.state.pagination, page: 1 }
        }, () => {
            if (this.state.currentView === 'projects') {
                this.loadProjects();
            } else if (this.state.currentView === 'tasks') {
                this.loadTasks();
            } else if (this.state.currentView === 'time-tracking') {
                this.loadTimeEntries();
            }
        });
    }

    handleProjectSelect(projectId, selected) {
        const selectedProjects = selected
            ? [...this.state.selectedProjects, projectId]
            : this.state.selectedProjects.filter(id => id !== projectId);

        this.setState({ selectedProjects });
    }

    handleTaskSelect(taskId, selected) {
        const selectedTasks = selected
            ? [...this.state.selectedTasks, taskId]
            : this.state.selectedTasks.filter(id => id !== taskId);

        this.setState({ selectedTasks });
    }

    async handleBulkAction(action, selectedIds) {
        if (!selectedIds || selectedIds.length === 0) {
            this.showNotification('Please select items first', 'warning');
            return;
        }

        try {
            switch (action) {
                case 'update_status':
                    await this.showBulkUpdateModal('status', selectedIds);
                    break;
                case 'update_priority':
                    await this.showBulkUpdateModal('priority', selectedIds);
                    break;
                case 'assign_tasks':
                    await this.showBulkAssignModal(selectedIds);
                    break;
                case 'export_selected':
                    await this.exportSelected(selectedIds);
                    break;
                case 'delete_selected':
                    await this.deleteSelected(selectedIds);
                    break;
            }
        } catch (error) {
            this.showNotification('Bulk action failed', 'error');
        }
    }

    async showBulkUpdateModal(field, selectedIds) {
        // Implementation for bulk update modal
        this.showNotification('Bulk update not yet implemented', 'info');
    }

    async showBulkAssignModal(selectedIds) {
        // Implementation for bulk assign modal
        this.showNotification('Bulk assign not yet implemented', 'info');
    }

    async exportSelected(selectedIds) {
        // Implementation for export selected
        this.showNotification('Export selected not yet implemented', 'info');
    }

    async deleteSelected(selectedIds) {
        // Implementation for delete selected
        this.showNotification('Delete selected not yet implemented', 'info');
    }

    showProjectModal(project = null) {
        this.setState({
            showProjectModal: true,
            editingProject: project
        });
    }

    hideProjectModal() {
        this.setState({
            showProjectModal: false,
            editingProject: null
        });
    }

    async saveProject(projectData) {
        try {
            if (this.state.editingProject) {
                await this.apiRequest(`/project-management/projects/${this.state.editingProject.id}`, 'PUT', projectData);
                this.showNotification('Project updated successfully', 'success');
            } else {
                await this.apiRequest('/project-management/projects', 'POST', projectData);
                this.showNotification('Project created successfully', 'success');
            }
            this.hideProjectModal();
            await this.loadProjects();
        } catch (error) {
            this.showNotification('Failed to save project', 'error');
        }
    }

    showTaskModal(task = null) {
        this.setState({
            showTaskModal: true,
            editingTask: task
        });
    }

    hideTaskModal() {
        this.setState({
            showTaskModal: false,
            editingTask: null
        });
    }

    async saveTask(taskData) {
        try {
            if (this.state.editingTask) {
                await this.apiRequest(`/project-management/tasks/${this.state.editingTask.id}`, 'PUT', taskData);
                this.showNotification('Task updated successfully', 'success');
            } else {
                await this.apiRequest('/project-management/tasks', 'POST', taskData);
                this.showNotification('Task created successfully', 'success');
            }
            this.hideTaskModal();
            await this.loadTasks();
        } catch (error) {
            this.showNotification('Failed to save task', 'error');
        }
    }

    async startTimeTracking(taskId) {
        try {
            const response = await this.apiRequest(`/project-management/tasks/${taskId}/start-timer`, 'POST');
            this.showNotification('Time tracking started', 'success');
            await this.checkActiveTimer();
        } catch (error) {
            this.showNotification('Failed to start time tracking', 'error');
        }
    }

    async stopTimeTracking(taskId) {
        try {
            const response = await this.apiRequest(`/project-management/tasks/${taskId}/stop-timer`, 'POST');
            this.showNotification(`Time tracking stopped. Logged ${response.hours_logged} hours.`, 'success');
            await this.checkActiveTimer();
            await this.loadTimeEntries();
        } catch (error) {
            this.showNotification('Failed to stop time tracking', 'error');
        }
    }

    async createTaskDependency(taskId, dependencyData) {
        try {
            await this.apiRequest(`/project-management/tasks/${taskId}/dependencies`, 'POST', dependencyData);
            this.showNotification('Task dependency created successfully', 'success');
        } catch (error) {
            this.showNotification('Failed to create task dependency', 'error');
        }
    }

    async updateProjectProgress(projectId, progress) {
        try {
            await this.apiRequest(`/project-management/projects/${projectId}`, 'PUT', { progress_percentage: progress });
            this.showNotification('Project progress updated', 'success');
            await this.loadProjects();
        } catch (error) {
            this.showNotification('Failed to update project progress', 'error');
        }
    }

    async updateTaskProgress(taskId, progress) {
        try {
            await this.apiRequest(`/project-management/tasks/${taskId}`, 'PUT', { progress_percentage: progress });
            this.showNotification('Task progress updated', 'success');
            await this.loadTasks();
        } catch (error) {
            this.showNotification('Failed to update task progress', 'error');
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'project-management-container' });

        // Header
        const header = DOM.create('div', { className: 'project-management-header' });
        const titleElement = DOM.create('h1', { className: 'project-management-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        // Active timer indicator
        if (this.state.activeTimer) {
            const timerIndicator = this.renderActiveTimer();
            header.appendChild(timerIndicator);
        }

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'project-management-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showProjectModal) {
            container.appendChild(this.renderProjectModal());
        }

        if (this.state.showTaskModal) {
            container.appendChild(this.renderTaskModal());
        }

        if (this.state.showTimeModal) {
            container.appendChild(this.renderTimeModal());
        }

        if (this.state.showDependencyModal) {
            container.appendChild(this.renderDependencyModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'projects', label: 'Projects', icon: 'fas fa-project-diagram' },
            { id: 'tasks', label: 'Tasks', icon: 'fas fa-tasks' },
            { id: 'time-tracking', label: 'Time Tracking', icon: 'fas fa-clock' },
            { id: 'gantt', label: 'Gantt Chart', icon: 'fas fa-chart-gantt' },
            { id: 'resources', label: 'Resources', icon: 'fas fa-users' },
            { id: 'templates', label: 'Templates', icon: 'fas fa-copy' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-line' }
        ];

        const nav = DOM.create('nav', { className: 'project-management-nav' });
        const tabList = DOM.create('ul', { className: 'nav-tabs' });

        tabs.forEach(tab => {
            const tabItem = DOM.create('li', { className: 'nav-item' });
            const tabLink = DOM.create('a', {
                href: '#',
                className: `nav-link ${this.state.currentView === tab.id ? 'active' : ''}`,
                onclick: (e) => {
                    e.preventDefault();
                    this.handleViewChange(tab.id);
                }
            });

            const icon = DOM.create('i', { className: tab.icon });
            const label = DOM.create('span', { className: 'nav-text' }, tab.label);

            tabLink.appendChild(icon);
            tabLink.appendChild(label);
            tabItem.appendChild(tabLink);
            tabList.appendChild(tabItem);
        });

        nav.appendChild(tabList);
        return nav;
    }

    renderActiveTimer() {
        const timer = DOM.create('div', { className: 'active-timer-indicator' });
        const icon = DOM.create('i', { className: 'fas fa-stopwatch' });
        const text = DOM.create('span', {}, 'Timer Active');
        const stopButton = DOM.create('button', {
            className: 'btn btn-sm btn-danger',
            onclick: () => this.stopTimeTracking(this.state.activeTimer.task_id)
        }, 'Stop');

        timer.appendChild(icon);
        timer.appendChild(text);
        timer.appendChild(stopButton);

        return timer;
    }

    renderLoading() {
        return DOM.create('div', { className: 'loading-container' },
            DOM.create('div', { className: 'spinner' }),
            DOM.create('p', {}, 'Loading project management data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'projects':
                return this.renderProjects();
            case 'tasks':
                return this.renderTasks();
            case 'time-tracking':
                return this.renderTimeTracking();
            case 'gantt':
                return this.renderGantt();
            case 'resources':
                return this.renderResources();
            case 'templates':
                return this.renderTemplates();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'project-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Active projects
        const activeProjects = this.renderActiveProjects();
        dashboard.appendChild(activeProjects);

        // Upcoming deadlines
        const upcomingDeadlines = this.renderUpcomingDeadlines();
        dashboard.appendChild(upcomingDeadlines);

        // Resource utilization
        const resourceUtilization = this.renderResourceUtilization();
        dashboard.appendChild(resourceUtilization);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.project_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Projects',
                value: overview.total_projects || 0,
                icon: 'fas fa-project-diagram',
                color: 'primary'
            },
            {
                title: 'Active Projects',
                value: overview.active_projects || 0,
                icon: 'fas fa-play-circle',
                color: 'success'
            },
            {
                title: 'Total Tasks',
                value: overview.total_tasks || 0,
                icon: 'fas fa-tasks',
                color: 'info'
            },
            {
                title: 'Completed Tasks',
                value: overview.completed_tasks || 0,
                icon: 'fas fa-check-circle',
                color: 'success'
            },
            {
                title: 'Overdue Projects',
                value: overview.overdue_projects || 0,
                icon: 'fas fa-exclamation-triangle',
                color: 'warning'
            }
        ];

        cardData.forEach(data => {
            const card = DOM.create('div', { className: `overview-card ${data.color}` });
            const icon = DOM.create('div', { className: 'card-icon' },
                DOM.create('i', { className: data.icon })
            );
            const content = DOM.create('div', { className: 'card-content' });
            content.appendChild(DOM.create('h3', { className: 'card-value' }, data.value));
            content.appendChild(DOM.create('p', { className: 'card-title' }, data.title));

            card.appendChild(icon);
            card.appendChild(content);
            cards.appendChild(card);
        });

        return cards;
    }

    renderActiveProjects() {
        const projects = this.state.overview.active_projects || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Active Projects'));

        if (projects.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No active projects'));
        } else {
            const projectsList = DOM.create('ul', { className: 'projects-list' });
            projects.slice(0, 5).forEach(project => {
                const listItem = DOM.create('li', { className: 'project-item' });
                listItem.appendChild(DOM.create('div', { className: 'project-name' }, project.project_name));
                listItem.appendChild(DOM.create('div', { className: 'project-meta' },
                    `Manager: ${project.manager_first} ${project.manager_last} • Progress: ${project.progress_percentage}% • Due: ${this.formatDate(project.end_date)}`
                ));

                // Progress bar
                const progressBar = DOM.create('div', { className: 'progress-bar' });
                const progressFill = DOM.create('div', {
                    className: 'progress-fill',
                    style: `width: ${project.progress_percentage}%`
                });
                progressBar.appendChild(progressFill);
                listItem.appendChild(progressBar);

                projectsList.appendChild(listItem);
            });
            section.appendChild(projectsList);
        }

        return section;
    }

    renderUpcomingDeadlines() {
        const deadlines = this.state.overview.upcoming_deadlines || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Upcoming Deadlines'));

        if (deadlines.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No upcoming deadlines'));
        } else {
            const deadlinesList = DOM.create('ul', { className: 'deadlines-list' });
            deadlines.slice(0, 5).forEach(deadline => {
                const listItem = DOM.create('li', { className: 'deadline-item' });
                listItem.appendChild(DOM.create('div', { className: 'deadline-name' }, deadline.project_name));
                listItem.appendChild(DOM.create('div', { className: 'deadline-meta' },
                    `Due: ${this.formatDate(deadline.target_date)} • Days left: ${deadline.days_until_target}`
                ));

                const priorityClass = deadline.priority === 'high' ? 'high-priority' :
                                    deadline.priority === 'urgent' ? 'urgent' : 'normal';
                listItem.classList.add(priorityClass);

                deadlinesList.appendChild(listItem);
            });
            section.appendChild(deadlinesList);
        }

        return section;
    }

    renderResourceUtilization() {
        const utilization = this.state.overview.resource_utilization || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Resource Utilization'));

        if (utilization.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No resource data available'));
        } else {
            const utilizationList = DOM.create('ul', { className: 'utilization-list' });
            utilization.slice(0, 5).forEach(resource => {
                const listItem = DOM.create('li', { className: 'utilization-item' });
                listItem.appendChild(DOM.create('div', { className: 'resource-name' },
                    `${resource.first_name} ${resource.last_name}`
                ));
                listItem.appendChild(DOM.create('div', { className: 'resource-meta' },
                    `Tasks: ${resource.assigned_tasks} • Hours: ${resource.total_hours} • Rate: ${resource.utilization_rate}%`
                ));

                // Utilization bar
                const utilizationBar = DOM.create('div', { className: 'utilization-bar' });
                const utilizationFill = DOM.create('div', {
                    className: 'utilization-fill',
                    style: `width: ${Math.min(resource.utilization_rate, 100)}%`
                });
                utilizationBar.appendChild(utilizationFill);
                listItem.appendChild(utilizationBar);

                utilizationList.appendChild(listItem);
            });
            section.appendChild(utilizationList);
        }

        return section;
    }

    renderProjects() {
        const projectsView = DOM.create('div', { className: 'projects-view' });

        // Toolbar
        const toolbar = this.renderProjectsToolbar();
        projectsView.appendChild(toolbar);

        // Filters
        const filters = this.renderProjectsFilters();
        projectsView.appendChild(filters);

        // Projects table
        const table = this.renderProjectsTable();
        projectsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        projectsView.appendChild(pagination);

        return projectsView;
    }

    renderProjectsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedProjects.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedProjects.length} selected`
            ));

            const actions = ['update_status', 'update_priority', 'export_selected'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action)
                }, action.replace('_', ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showProjectModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Project';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderProjectsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search projects...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-group' });
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'planning', 'active', 'on_hold', 'completed', 'cancelled'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Priority filter
        const priorityGroup = DOM.create('div', { className: 'filter-group' });
        const prioritySelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.priority,
            onchange: (e) => this.handleFilterChange('priority', e.target.value)
        });
        const priorities = ['', 'low', 'medium', 'high', 'critical'];
        priorities.forEach(priority => {
            prioritySelect.appendChild(DOM.create('option', { value: priority },
                priority === '' ? 'All Priorities' : priority.charAt(0).toUpperCase() + priority.slice(1)
            ));
        });
        priorityGroup.appendChild(DOM.create('label', {}, 'Priority:'));
        priorityGroup.appendChild(prioritySelect);
        filters.appendChild(priorityGroup);

        return filters;
    }

    renderProjectsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'project_name', label: 'Project Name' },
            { key: 'manager', label: 'Manager' },
            { key: 'status', label: 'Status' },
            { key: 'progress', label: 'Progress' },
            { key: 'start_date', label: 'Start Date' },
            { key: 'end_date', label: 'End Date' },
            { key: 'budget', label: 'Budget' },
            { key: 'actions', label: 'Actions', width: '200px' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {
                style: header.width ? `width: ${header.width};` : ''
            }, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.projects.forEach(project => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedProjects.includes(project.id),
                onchange: (e) => this.handleProjectSelect(project.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Project Name
            row.appendChild(DOM.create('td', {}, project.project_name));

            // Manager
            row.appendChild(DOM.create('td', {},
                project.manager_first ? `${project.manager_first} ${project.manager_last}` : 'N/A'
            ));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${project.status}`
            }, project.status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Progress
            const progressCell = DOM.create('td', {});
            const progressBar = DOM.create('div', { className: 'progress-bar small' });
            const progressFill = DOM.create('div', {
                className: 'progress-fill',
                style: `width: ${project.progress_percentage}%`
            });
            progressFill.appendChild(DOM.create('span', { className: 'progress-text' },
                `${project.progress_percentage}%`
            ));
            progressBar.appendChild(progressFill);
            progressCell.appendChild(progressBar);
            row.appendChild(progressCell);

            // Start Date
            row.appendChild(DOM.create('td', {}, this.formatDate(project.start_date)));

            // End Date
            row.appendChild(DOM.create('td', {}, this.formatDate(project.end_date)));

            // Budget
            row.appendChild(DOM.create('td', {},
                project.budget ? `$${project.budget.toLocaleString()}` : 'N/A'
            ));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewProject(project)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showProjectModal(project)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const tasksButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.viewProjectTasks(project)
            });
            tasksButton.innerHTML = '<i class="fas fa-tasks"></i>';
            actions.appendChild(tasksButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderTasks() {
        const tasksView = DOM.create('div', { className: 'tasks-view' });

        // Toolbar
        const toolbar = this.renderTasksToolbar();
        tasksView.appendChild(toolbar);

        // Filters
        const filters = this.renderTasksFilters();
        tasksView.appendChild(filters);

        // Tasks table
        const table = this.renderTasksTable();
        tasksView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        tasksView.appendChild(pagination);

        return tasksView;
    }

    renderTasksToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedTasks.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedTasks.length} selected`
            ));

            const actions = ['update_status', 'update_priority', 'assign_tasks', 'export_selected'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action)
                }, action.replace('_', ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showTaskModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Task';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderTasksFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search tasks...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-group' });
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'not_started', 'in_progress', 'review', 'completed', 'cancelled'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Priority filter
        const priorityGroup = DOM.create('div', { className: 'filter-group' });
        const prioritySelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.priority,
            onchange: (e) => this.handleFilterChange('priority', e.target.value)
        });
        const priorities = ['', 'low', 'medium', 'high', 'urgent'];
        priorities.forEach(priority => {
            prioritySelect.appendChild(DOM.create('option', { value: priority },
                priority === '' ? 'All Priorities' : priority.charAt(0).toUpperCase() + priority.slice(1)
            ));
        });
        priorityGroup.appendChild(DOM.create('label', {}, 'Priority:'));
        priorityGroup.appendChild(prioritySelect);
        filters.appendChild(priorityGroup);

        return filters;
    }

    renderTasksTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'task_name', label: 'Task Name' },
            { key: 'project', label: 'Project' },
            { key: 'assignee', label: 'Assignee' },
            { key: 'status', label: 'Status' },
            { key: 'priority', label: 'Priority' },
            { key: 'progress', label: 'Progress' },
            { key: 'due_date', label: 'Due Date' },
            { key: 'actions', label: 'Actions', width: '300px' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {
                style: header.width ? `width: ${header.width};` : ''
            }, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.tasks.forEach(task => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedTasks.includes(task.id),
                onchange: (e) => this.handleTaskSelect(task.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Task Name
            row.appendChild(DOM.create('td', {}, task.task_name));

            // Project
            row.appendChild(DOM.create('td', {}, task.project_name || 'N/A'));

            // Assignee
            row.appendChild(DOM.create('td', {},
                task.assignee_first ? `${task.assignee_first} ${task.assignee_last}` : 'Unassigned'
            ));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${task.status}`
            }, task.status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Priority
            const priorityCell = DOM.create('td', {});
            const priorityBadge = DOM.create('span', {
                className: `priority-badge ${task.priority}`
            }, task.priority);
            priorityCell.appendChild(priorityBadge);
            row.appendChild(priorityCell);

            // Progress
            const progressCell = DOM.create('td', {});
            const progressBar = DOM.create('div', { className: 'progress-bar small' });
            const progressFill = DOM.create('div', {
                className: 'progress-fill',
                style: `width: ${task.progress_percentage}%`
            });
            progressFill.appendChild(DOM.create('span', { className: 'progress-text' },
                `${task.progress_percentage}%`
            ));
            progressBar.appendChild(progressFill);
            progressCell.appendChild(progressBar);
            row.appendChild(progressCell);

            // Due Date
            const dueDateCell = DOM.create('td', {});
            const dueDate = DOM.create('span', {}, this.formatDate(task.due_date));
            if (task.days_until_due < 0) {
                dueDate.classList.add('overdue');
            } else if (task.days_until_due <= 3) {
                dueDate.classList.add('due-soon');
            }
            dueDateCell.appendChild(dueDate);
            row.appendChild(dueDateCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const timerButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => {
                    if (this.state.activeTimer) {
                        this.stopTimeTracking(task.id);
                    } else {
                        this.startTimeTracking(task.id);
                    }
                }
            });
            timerButton.innerHTML = this.state.activeTimer ?
                '<i class="fas fa-stop"></i>' : '<i class="fas fa-play"></i>';
            actions.appendChild(timerButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showTaskModal(task)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const dependencyButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.showDependencyModal(task)
            });
            dependencyButton.innerHTML = '<i class="fas fa-link"></i>';
            actions.appendChild(dependencyButton);

            const completeButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.updateTaskProgress(task.id, 100)
            });
            completeButton.innerHTML = '<i class="fas fa-check"></i>';
            actions.appendChild(completeButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderTimeTracking() {
        const timeView = DOM.create('div', { className: 'time-tracking-view' });

        // Current timer status
        if (this.state.activeTimer) {
            const timerStatus = this.renderTimerStatus();
            timeView.appendChild(timerStatus);
        }

        // Time entries table
        const table = this.renderTimeEntriesTable();
        timeView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        timeView.appendChild(pagination);

        return timeView;
    }

    renderTimerStatus() {
        const status = DOM.create('div', { className: 'timer-status' });
        status.appendChild(DOM.create('h3', {}, 'Active Timer'));
        status.appendChild(DOM.create('p', {},
            `Tracking time for task: ${this.state.activeTimer.task_name}`
        ));

        const stopButton = DOM.create('button', {
            className: 'btn btn-danger',
            onclick: () => this.stopTimeTracking(this.state.activeTimer.task_id)
        }, 'Stop Timer');

        status.appendChild(stopButton);
        return status;
    }

    renderTimeEntriesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'task', label: 'Task' },
            { key: 'project', label: 'Project' },
            { key: 'user', label: 'User' },
            { key: 'start_time', label: 'Start Time' },
            { key: 'end_time', label: 'End Time' },
            { key: 'duration', label: 'Duration' },
            { key: 'billable', label: 'Billable' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {}, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.timeEntries.forEach(entry => {
            const row = DOM.create('tr', {});

            // Task
            row.appendChild(DOM.create('td', {}, entry.task_name || 'N/A'));

            // Project
            row.appendChild(DOM.create('td', {}, entry.project_name || 'N/A'));

            // User
            row.appendChild(DOM.create('td', {}, `${entry.first_name} ${entry.last_name}`));

            // Start Time
            row.appendChild(DOM.create('td', {}, this.formatDateTime(entry.start_time)));

            // End Time
            row.appendChild(DOM.create('td', {}, entry.end_time ? this.formatDateTime(entry.end_time) : 'Active'));

            // Duration
            row.appendChild(DOM.create('td', {}, entry.hours_logged ? `${entry.hours_logged}h` : 'N/A'));

            // Billable
            const billableCell = DOM.create('td', {});
            const billableBadge = DOM.create('span', {
                className: `status-badge ${entry.is_billable ? 'yes' : 'no'}`
            }, entry.is_billable ? 'Yes' : 'No');
            billableCell.appendChild(billableBadge);
            row.appendChild(billableCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderGantt() {
        const ganttView = DOM.create('div', { className: 'gantt-view' });
        ganttView.appendChild(DOM.create('h3', {}, 'Gantt Chart'));
        ganttView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Gantt chart visualization coming soon...'));
        return ganttView;
    }

    renderResources() {
        const resourcesView = DOM.create('div', { className: 'resources-view' });
        resourcesView.appendChild(DOM.create('h3', {}, 'Resource Management'));
        resourcesView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Resource management interface coming soon...'));
        return resourcesView;
    }

    renderTemplates() {
        const templatesView = DOM.create('div', { className: 'templates-view' });
        templatesView.appendChild(DOM.create('h3', {}, 'Project Templates'));
        templatesView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Template management interface coming soon...'));
        return templatesView;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'Project Analytics'));
        analyticsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Analytics dashboard coming soon...'));
        return analyticsView;
    }

    renderPagination() {
        const { pagination } = this.state;
        const paginationDiv = DOM.create('div', { className: 'pagination' });

        if (pagination.pages <= 1) return paginationDiv;

        // Previous button
        if (pagination.page > 1) {
            const prevButton = DOM.create('button', {
                className: 'btn btn-outline-secondary',
                onclick: () => this.changePage(pagination.page - 1)
            }, 'Previous');
            paginationDiv.appendChild(prevButton);
        }

        // Page numbers
        const startPage = Math.max(1, pagination.page - 2);
        const endPage = Math.min(pagination.pages, pagination.page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageButton = DOM.create('button', {
                className: `btn ${i === pagination.page ? 'btn-primary' : 'btn-outline-secondary'}`,
                onclick: () => this.changePage(i)
            }, i.toString());
            paginationDiv.appendChild(pageButton);
        }

        // Next button
        if (pagination.page < pagination.pages) {
            const nextButton = DOM.create('button', {
                className: 'btn btn-outline-secondary',
                onclick: () => this.changePage(pagination.page + 1)
            }, 'Next');
            paginationDiv.appendChild(nextButton);
        }

        return paginationDiv;
    }

    changePage(page) {
        this.setState({
            pagination: { ...this.state.pagination, page }
        }, () => {
            if (this.state.currentView === 'projects') {
                this.loadProjects();
            } else if (this.state.currentView === 'tasks') {
                this.loadTasks();
            } else if (this.state.currentView === 'time-tracking') {
                this.loadTimeEntries();
            }
        });
    }

    renderProjectModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingProject ? 'Edit Project' : 'Create Project'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideProjectModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'project-form' });

        // Project Name
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Project Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            required: true,
            value: this.state.editingProject?.project_name || ''
        });
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        // Description
        const descGroup = DOM.create('div', { className: 'form-group' });
        descGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 3,
            value: this.state.editingProject?.description || ''
        });
        descGroup.appendChild(descTextarea);
        form.appendChild(descGroup);

        // Start Date
        const startDateGroup = DOM.create('div', { className: 'form-group' });
        startDateGroup.appendChild(DOM.create('label', {}, 'Start Date:'));
        const startDateInput = DOM.create('input', {
            type: 'date',
            className: 'form-control',
            required: true,
            value: this.state.editingProject?.start_date || ''
        });
        startDateGroup.appendChild(startDateInput);
        form.appendChild(startDateGroup);

        // End Date
        const endDateGroup = DOM.create('div', { className: 'form-group' });
        endDateGroup.appendChild(DOM.create('label', {}, 'End Date:'));
        const endDateInput = DOM.create('input', {
            type: 'date',
            className: 'form-control',
            required: true,
            value: this.state.editingProject?.end_date || ''
        });
        endDateGroup.appendChild(endDateInput);
        form.appendChild(endDateGroup);

        // Budget
        const budgetGroup = DOM.create('div', { className: 'form-group' });
        budgetGroup.appendChild(DOM.create('label', {}, 'Budget:'));
        const budgetInput = DOM.create('input', {
            type: 'number',
            className: 'form-control',
            step: '0.01',
            value: this.state.editingProject?.budget || ''
        });
        budgetGroup.appendChild(budgetInput);
        form.appendChild(budgetGroup);

        // Priority
        const priorityGroup = DOM.create('div', { className: 'form-group' });
        priorityGroup.appendChild(DOM.create('label', {}, 'Priority:'));
        const prioritySelect = DOM.create('select', { className: 'form-control' });
        const priorities = ['low', 'medium', 'high', 'critical'];
        priorities.forEach(priority => {
            const option = DOM.create('option', {
                value: priority,
                selected: this.state.editingProject?.priority === priority
            }, priority.charAt(0).toUpperCase() + priority.slice(1));
            prioritySelect.appendChild(option);
        });
        priorityGroup.appendChild(prioritySelect);
        form.appendChild(priorityGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideProjectModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const saveButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const projectData = {
                    project_name: nameInput.value.trim(),
                    description: descTextarea.value.trim(),
                    start_date: startDateInput.value,
                    end_date: endDateInput.value,
                    budget: budgetInput.value ? parseFloat(budgetInput.value) : null,
                    priority: prioritySelect.value
                };
                this.saveProject(projectData);
            }
        }, this.state.editingProject ? 'Update Project' : 'Create Project');
        footer.appendChild(saveButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    renderTaskModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingTask ? 'Edit Task' : 'Create Task'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideTaskModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'task-form' });

        // Task Name
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Task Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            required: true,
            value: this.state.editingTask?.task_name || ''
        });
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        // Description
        const descGroup = DOM.create('div', { className: 'form-group' });
        descGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 3,
            value: this.state.editingTask?.description || ''
        });
        descGroup.appendChild(descTextarea);
        form.appendChild(descGroup);

        // Project
        const projectGroup = DOM.create('div', { className: 'form-group' });
        projectGroup.appendChild(DOM.create('label', {}, 'Project:'));
        const projectSelect = DOM.create('select', { className: 'form-control' });
        this.state.projects.forEach(project => {
            const option = DOM.create('option', {
                value: project.id,
                selected: this.state.editingTask?.project_id === project.id
            }, project.project_name);
            projectSelect.appendChild(option);
        });
        projectGroup.appendChild(projectSelect);
        form.appendChild(projectGroup);

        // Due Date
        const dueDateGroup = DOM.create('div', { className: 'form-group' });
        dueDateGroup.appendChild(DOM.create('label', {}, 'Due Date:'));
        const dueDateInput = DOM.create('input', {
            type: 'date',
            className: 'form-control',
            value: this.state.editingTask?.due_date || ''
        });
        dueDateGroup.appendChild(dueDateInput);
        form.appendChild(dueDateGroup);

        // Estimated Hours
        const hoursGroup = DOM.create('div', { className: 'form-group' });
        hoursGroup.appendChild(DOM.create('label', {}, 'Estimated Hours:'));
        const hoursInput = DOM.create('input', {
            type: 'number',
            className: 'form-control',
            step: '0.5',
            value: this.state.editingTask?.estimated_hours || ''
        });
        hoursGroup.appendChild(hoursInput);
        form.appendChild(hoursGroup);

        // Priority
        const priorityGroup = DOM.create('div', { className: 'form-group' });
        priorityGroup.appendChild(DOM.create('label', {}, 'Priority:'));
        const prioritySelect = DOM.create('select', { className: 'form-control' });
        const priorities = ['low', 'medium', 'high', 'urgent'];
        priorities.forEach(priority => {
            const option = DOM.create('option', {
                value: priority,
                selected: this.state.editingTask?.priority === priority
            }, priority.charAt(0).toUpperCase() + priority.slice(1));
            prioritySelect.appendChild(option);
        });
        priorityGroup.appendChild(prioritySelect);
        form.appendChild(priorityGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideTaskModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const saveButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const taskData = {
                    task_name: nameInput.value.trim(),
                    description: descTextarea.value.trim(),
                    project_id: projectSelect.value,
                    due_date: dueDateInput.value,
                    estimated_hours: hoursInput.value ? parseFloat(hoursInput.value) : null,
                    priority: prioritySelect.value
                };
                this.saveTask(taskData);
            }
        }, this.state.editingTask ? 'Update Task' : 'Create Task');
        footer.appendChild(saveButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    renderTimeModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Time Tracking'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideTimeModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Time tracking modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderDependencyModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Task Dependencies'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDependencyModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Task dependency management coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    formatTimeAgo(dateString) {
        if (!dateString) return '';
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
        return date.toLocaleDateString();
    }

    hideTimeModal() {
        this.setState({ showTimeModal: false });
    }

    hideDependencyModal() {
        this.setState({ showDependencyModal: false });
    }

    viewProject(project) {
        // Implementation for viewing project details
        this.showNotification('Project details view coming soon', 'info');
    }

    viewProjectTasks(project) {
        // Implementation for viewing project tasks
        this.setState({
            filters: { ...this.state.filters, project: project.id },
            currentView: 'tasks'
        }, () => {
            this.loadTasks();
        });
    }
}

// Export the component
window.ProjectManagement = ProjectManagement;
