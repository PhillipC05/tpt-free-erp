/**
 * TPT Free ERP - Human Resources Component (Refactored)
 * Main HR dashboard and employee management interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class HR extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderer for employees
        this.tableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.tableRenderer.setDataCallback(() => this.state.employees || []);
        this.tableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedEmployees: selectedIds });
        });
        this.tableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.tableRenderer.setDataChangeCallback(() => {
            this.loadEmployees();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadEmployees',
            'loadDepartments',
            'loadAttendance',
            'loadPayroll',
            'loadPerformance',
            'loadLeave',
            'loadRecruitment',
            'loadTraining',
            'handleViewChange',
            'handleFilterChange',
            'handleBulkAction',
            'showEmployeeModal',
            'hideEmployeeModal',
            'saveEmployee',
            'deleteEmployee',
            'showDepartmentModal',
            'hideDepartmentModal',
            'saveDepartment'
        ];
    }

    async componentDidMount() {
        await this.loadInitialData();
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load basic data
            await Promise.all([
                this.loadDepartments()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading HR data:', error);
            this.showErrorNotification('Failed to load HR data');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'employees':
                await this.loadEmployees();
                break;
            case 'departments':
                await this.loadDepartments();
                break;
            case 'attendance':
                await this.loadAttendance();
                break;
            case 'payroll':
                await this.loadPayroll();
                break;
            case 'performance':
                await this.loadPerformance();
                break;
            case 'leave':
                await this.loadLeave();
                break;
            case 'recruitment':
                await this.loadRecruitment();
                break;
            case 'training':
                await this.loadTraining();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await this.apiRequest('GET', '/hr/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading HR overview:', error);
        }
    }

    async loadEmployees() {
        try {
            const params = {
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            };

            const response = await this.apiRequest('GET', '/hr/employees', params);
            this.setState({
                employees: response.employees || [],
                pagination: response.pagination || this.state.pagination
            });
        } catch (error) {
            console.error('Error loading employees:', error);
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiRequest('GET', '/hr/departments');
            this.setState({ departments: response.departments || [] });
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }

    async loadAttendance() {
        try {
            const response = await this.apiRequest('GET', '/hr/attendance');
            this.setState({
                attendance: response.attendance || [],
                attendanceAnalytics: response.analytics
            });
        } catch (error) {
            console.error('Error loading attendance:', error);
        }
    }

    async loadPayroll() {
        try {
            const response = await this.apiRequest('GET', '/hr/payroll');
            this.setState({
                payroll: response.payroll_runs || [],
                salaryStructures: response.salary_structures,
                payrollAnalytics: response.analytics
            });
        } catch (error) {
            console.error('Error loading payroll:', error);
        }
    }

    async loadPerformance() {
        try {
            const response = await this.apiRequest('GET', '/hr/performance');
            this.setState({
                performance: response.performance_reviews || [],
                goalSetting: response.goal_setting,
                performanceAnalytics: response.analytics
            });
        } catch (error) {
            console.error('Error loading performance:', error);
        }
    }

    async loadLeave() {
        try {
            const response = await this.apiRequest('GET', '/hr/leave');
            this.setState({ leave: response.leave || [] });
        } catch (error) {
            console.error('Error loading leave:', error);
        }
    }

    async loadRecruitment() {
        try {
            const response = await this.apiRequest('GET', '/hr/recruitment');
            this.setState({
                recruitment: response.job_postings || [],
                applicants: response.applicant_tracking,
                recruitmentAnalytics: response.analytics
            });
        } catch (error) {
            console.error('Error loading recruitment:', error);
        }
    }

    async loadTraining() {
        try {
            const response = await this.apiRequest('GET', '/hr/training');
            this.setState({
                training: response.training_programs || [],
                courseCatalog: response.course_catalog,
                trainingAnalytics: response.analytics
            });
        } catch (error) {
            console.error('Error loading training:', error);
        }
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
            if (this.state.currentView === 'employees') {
                this.loadEmployees();
            }
        });
    }

    handleEmployeeSelect(employeeId, selected) {
        const selectedEmployees = selected
            ? [...this.state.selectedEmployees, employeeId]
            : this.state.selectedEmployees.filter(id => id !== employeeId);

        this.setState({ selectedEmployees });
    }

    async handleBulkAction(action, selectedIds) {
        if (!selectedIds || selectedIds.length === 0) {
            this.showWarning('Please select employees first');
            return;
        }

        try {
            switch (action) {
                case 'delete':
                    if (await this.confirm({
                        title: 'Confirm Bulk Termination',
                        message: `Are you sure you want to terminate ${selectedIds.length} employees?`,
                        type: 'danger'
                    })) {
                        await this.bulkTerminateEmployees(selectedIds);
                    }
                    break;
                case 'update_department':
                    await this.showBulkUpdateModal('department', selectedIds);
                    break;
                case 'update_salary':
                    await this.showBulkUpdateModal('salary', selectedIds);
                    break;
                case 'update_status':
                    await this.showBulkUpdateModal('status', selectedIds);
                    break;
                case 'send_notification':
                    await this.sendBulkNotification(selectedIds);
                    break;
                case 'export':
                    await this.exportEmployees(selectedIds);
                    break;
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            this.showErrorNotification('Bulk action failed');
        }
    }

    async bulkTerminateEmployees(selectedIds) {
        // Implementation for bulk termination
        this.showInfo('Bulk termination not yet implemented');
    }

    async showBulkUpdateModal(field, selectedIds) {
        // Implementation for bulk update modal
        this.showInfo('Bulk update not yet implemented');
    }

    async sendBulkNotification(selectedIds) {
        // Implementation for bulk notification
        this.showInfo('Bulk notification not yet implemented');
    }

    async exportEmployees(selectedIds) {
        // Implementation for export
        this.showInfo('Export not yet implemented');
    }

    showEmployeeModal(employee = null) {
        this.setState({
            showEmployeeModal: true,
            editingEmployee: employee
        });
    }

    hideEmployeeModal() {
        this.setState({
            showEmployeeModal: false,
            editingEmployee: null
        });
    }

    async saveEmployee(employeeData) {
        try {
            if (this.state.editingEmployee) {
                await this.apiRequest('PUT', `/hr/employees/${this.state.editingEmployee.id}`, employeeData);
                this.showSuccess('Employee updated successfully');
            } else {
                await this.apiRequest('POST', '/hr/employees', employeeData);
                this.showSuccess('Employee created successfully');
            }

            this.hideEmployeeModal();
            await this.loadEmployees();
        } catch (error) {
            console.error('Error saving employee:', error);
            this.showErrorNotification(error.message || 'Failed to save employee');
        }
    }

    async deleteEmployee(employeeId) {
        if (!await this.confirm({
            title: 'Confirm Termination',
            message: 'Are you sure you want to terminate this employee?',
            type: 'danger'
        })) {
            return;
        }

        try {
            await this.apiRequest('DELETE', `/hr/employees/${employeeId}`);
            this.showSuccess('Employee terminated successfully');
            await this.loadEmployees();
        } catch (error) {
            console.error('Error terminating employee:', error);
            this.showErrorNotification(error.message || 'Failed to terminate employee');
        }
    }

    showDepartmentModal(department = null) {
        this.setState({
            showDepartmentModal: true,
            editingDepartment: department
        });
    }

    hideDepartmentModal() {
        this.setState({
            showDepartmentModal: false,
            editingDepartment: null
        });
    }

    async saveDepartment(departmentData) {
        try {
            await this.apiRequest('POST', '/hr/departments', departmentData);
            this.showSuccess('Department created successfully');
            this.hideDepartmentModal();
            await this.loadDepartments();
        } catch (error) {
            console.error('Error saving department:', error);
            this.showErrorNotification(error.message || 'Failed to save department');
        }
    }

    showAttendanceModal(employee = null) {
        this.setState({
            showAttendanceModal: true,
            selectedEmployee: employee
        });
    }

    hideAttendanceModal() {
        this.setState({
            showAttendanceModal: false,
            selectedEmployee: null
        });
    }

    async saveAttendance(attendanceData) {
        try {
            await this.apiRequest('POST', '/hr/attendance', attendanceData);
            this.showSuccess('Attendance recorded successfully');
            this.hideAttendanceModal();
            await this.loadAttendance();
        } catch (error) {
            console.error('Error saving attendance:', error);
            this.showErrorNotification(error.message || 'Failed to save attendance');
        }
    }

    showPayrollModal() {
        this.setState({
            showPayrollModal: true
        });
    }

    hidePayrollModal() {
        this.setState({
            showPayrollModal: false
        });
    }

    async processPayroll(payrollData) {
        try {
            await this.apiRequest('POST', '/hr/payroll/process', payrollData);
            this.showSuccess('Payroll processed successfully');
            this.hidePayrollModal();
            await this.loadPayroll();
        } catch (error) {
            console.error('Error processing payroll:', error);
            this.showErrorNotification(error.message || 'Failed to process payroll');
        }
    }

    showPerformanceModal(employee = null) {
        this.setState({
            showPerformanceModal: true,
            selectedEmployee: employee
        });
    }

    hidePerformanceModal() {
        this.setState({
            showPerformanceModal: false,
            selectedEmployee: null
        });
    }

    async savePerformanceReview(reviewData) {
        try {
            await this.apiRequest('POST', '/hr/performance/reviews', reviewData);
            this.showSuccess('Performance review created successfully');
            this.hidePerformanceModal();
            await this.loadPerformance();
        } catch (error) {
            console.error('Error saving performance review:', error);
            this.showErrorNotification(error.message || 'Failed to save performance review');
        }
    }

    showLeaveModal(employee = null) {
        this.setState({
            showLeaveModal: true,
            selectedEmployee: employee
        });
    }

    hideLeaveModal() {
        this.setState({
            showLeaveModal: false,
            selectedEmployee: null
        });
    }

    async saveLeaveRequest(leaveData) {
        try {
            await this.apiRequest('POST', '/hr/leave', leaveData);
            this.showSuccess('Leave request submitted successfully');
            this.hideLeaveModal();
            await this.loadLeave();
        } catch (error) {
            console.error('Error saving leave request:', error);
            this.showErrorNotification(error.message || 'Failed to save leave request');
        }
    }

    async approveLeaveRequest(leaveId) {
        try {
            await this.apiRequest('PUT', `/hr/leave/${leaveId}/approve`);
            this.showSuccess('Leave request approved successfully');
            await this.loadLeave();
        } catch (error) {
            console.error('Error approving leave request:', error);
            this.showErrorNotification(error.message || 'Failed to approve leave request');
        }
    }

    showRecruitmentModal() {
        this.setState({
            showRecruitmentModal: true
        });
    }

    hideRecruitmentModal() {
        this.setState({
            showRecruitmentModal: false
        });
    }

    async saveJobPosting(jobData) {
        try {
            await this.apiRequest('POST', '/hr/recruitment/jobs', jobData);
            this.showSuccess('Job posting created successfully');
            this.hideRecruitmentModal();
            await this.loadRecruitment();
        } catch (error) {
            console.error('Error saving job posting:', error);
            this.showErrorNotification(error.message || 'Failed to save job posting');
        }
    }

    showTrainingModal() {
        this.setState({
            showTrainingModal: true
        });
    }

    hideTrainingModal() {
        this.setState({
            showTrainingModal: false
        });
    }

    async saveTrainingProgram(trainingData) {
        try {
            await this.apiRequest('POST', '/hr/training/programs', trainingData);
            this.showSuccess('Training program created successfully');
            this.hideTrainingModal();
            await this.loadTraining();
        } catch (error) {
            console.error('Error saving training program:', error);
            this.showErrorNotification(error.message || 'Failed to save training program');
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'hr-container' });

        // Header
        const header = DOM.create('div', { className: 'hr-header' });
        const titleElement = DOM.create('h1', { className: 'hr-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'hr-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showEmployeeModal) {
            container.appendChild(this.renderEmployeeModal());
        }

        if (this.state.showDepartmentModal) {
            container.appendChild(this.renderDepartmentModal());
        }

        if (this.state.showAttendanceModal) {
            container.appendChild(this.renderAttendanceModal());
        }

        if (this.state.showPayrollModal) {
            container.appendChild(this.renderPayrollModal());
        }

        if (this.state.showPerformanceModal) {
            container.appendChild(this.renderPerformanceModal());
        }

        if (this.state.showLeaveModal) {
            container.appendChild(this.renderLeaveModal());
        }

        if (this.state.showRecruitmentModal) {
            container.appendChild(this.renderRecruitmentModal());
        }

        if (this.state.showTrainingModal) {
            container.appendChild(this.renderTrainingModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'employees', label: 'Employees', icon: 'fas fa-users' },
            { id: 'departments', label: 'Departments', icon: 'fas fa-building' },
            { id: 'attendance', label: 'Attendance', icon: 'fas fa-clock' },
            { id: 'payroll', label: 'Payroll', icon: 'fas fa-money-check' },
            { id: 'performance', label: 'Performance', icon: 'fas fa-chart-line' },
            { id: 'leave', label: 'Leave', icon: 'fas fa-calendar-times' },
            { id: 'recruitment', label: 'Recruitment', icon: 'fas fa-user-plus' },
            { id: 'training', label: 'Training', icon: 'fas fa-graduation-cap' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'hr-nav' });
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

    renderLoading() {
        return DOM.create('div', { className: 'loading-container' },
            DOM.create('div', { className: 'spinner' }),
            DOM.create('p', {}, 'Loading HR data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'employees':
                return this.renderEmployees();
            case 'departments':
                return this.renderDepartments();
            case 'attendance':
                return this.renderAttendance();
            case 'payroll':
                return this.renderPayroll();
            case 'performance':
                return this.renderPerformance();
            case 'leave':
                return this.renderLeave();
            case 'recruitment':
                return this.renderRecruitment();
            case 'training':
                return this.renderTraining();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'hr-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Workforce metrics
        const workforceSection = this.renderWorkforceMetrics();
        dashboard.appendChild(workforceSection);

        // Recent activities
        const activitiesSection = this.renderRecentActivities();
        dashboard.appendChild(activitiesSection);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.workforce_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Employees',
                value: overview.active_employees || 0,
                icon: 'fas fa-users',
                color: 'primary'
            },
            {
                title: 'New Hires',
                value: overview.new_hires || 0,
                icon: 'fas fa-user-plus',
                color: 'success'
            },
            {
                title: 'Terminations',
                value: overview.terminations || 0,
                icon: 'fas fa-user-minus',
                color: 'danger'
            },
            {
                title: 'Avg Tenure',
                value: (overview.avg_tenure_years || 0) + ' yrs',
                icon: 'fas fa-calendar',
                color: 'info'
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

    renderWorkforceMetrics() {
        const attendance = this.state.overview.attendance_summary || {};
        const metricsSection = DOM.create('div', { className: 'dashboard-section' });
        metricsSection.appendChild(DOM.create('h3', {}, 'Workforce Metrics'));

        const metrics = DOM.create('div', { className: 'workforce-metrics' });

        const metricData = [
            { label: 'Present Today', value: attendance.present_count || 0, color: 'success' },
            { label: 'Absent Today', value: attendance.absent_count || 0, color: 'danger' },
            { label: 'Late Arrivals', value: attendance.late_count || 0, color: 'warning' },
            { label: 'Attendance Rate', value: (attendance.attendance_rate || 0) + '%', color: 'info' }
        ];

        metricData.forEach(metric => {
            const metricItem = DOM.create('div', { className: `metric-item ${metric.color}` });
            metricItem.appendChild(DOM.create('div', { className: 'metric-value' }, metric.value));
            metricItem.appendChild(DOM.create('div', { className: 'metric-label' }, metric.label));
            metrics.appendChild(metricItem);
        });

        metricsSection.appendChild(metrics);
        return metricsSection;
    }

    renderRecentActivities() {
        const activities = this.state.overview.recent_activities || [];
        const activitiesSection = DOM.create('div', { className: 'dashboard-section' });
        activitiesSection.appendChild(DOM.create('h3', {}, 'Recent Activities'));

        if (activities.length === 0) {
            activitiesSection.appendChild(DOM.create('p', { className: 'no-data' }, 'No recent activities'));
        } else {
            const activitiesList = DOM.create('ul', { className: 'activities-list' });
            activities.slice(0, 5).forEach(activity => {
                const activityItem = DOM.create('li', { className: 'activity-item' });
                activityItem.appendChild(DOM.create('span', { className: 'activity-description' }, activity.description));
                activityItem.appendChild(DOM.create('span', { className: 'activity-time' }, this.formatTimeAgo(activity.created_at)));
                activitiesList.appendChild(activityItem);
            });
            activitiesSection.appendChild(activitiesList);
        }

        return activitiesSection;
    }

    renderEmployees() {
        const employeesView = DOM.create('div', { className: 'employees-view' });

        // Toolbar
        const toolbar = this.renderEmployeesToolbar();
        employeesView.appendChild(toolbar);

        // Filters
        const filters = this.renderEmployeesFilters();
        employeesView.appendChild(filters);

        // Employees table
        const table = this.renderEmployeesTable();
        employeesView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        employeesView.appendChild(pagination);

        return employeesView;
    }

    renderEmployeesToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedEmployees.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedEmployees.length} selected`
            ));

            const actions = ['update_department', 'update_salary', 'update_status', 'send_notification', 'export', 'delete'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action, this.state.selectedEmployees)
                }, action.replace('_', ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showEmployeeModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Employee';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderEmployeesFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search employees...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Department filter
        const departmentGroup = DOM.create('div', { className: 'filter-group' });
        const departmentSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.department,
            onchange: (e) => this.handleFilterChange('department', e.target.value)
        });
        departmentSelect.appendChild(DOM.create('option', { value: '' }, 'All Departments'));
        this.state.departments.forEach(dept => {
            departmentSelect.appendChild(DOM.create('option', { value: dept.id }, dept.department_name));
        });
        departmentGroup.appendChild(DOM.create('label', {}, 'Department:'));
        departmentGroup.appendChild(departmentSelect);
        filters.appendChild(departmentGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-group' });
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'active', 'inactive', 'terminated'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        return filters;
    }

    renderEmployeesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'employee_id', label: 'Employee ID' },
            { key: 'first_name', label: 'Name' },
            { key: 'department', label: 'Department' },
            { key: 'position_title', label: 'Position' },
            { key: 'salary', label: 'Salary' },
            { key: 'hire_date', label: 'Hire Date' },
            { key: 'employment_status', label: 'Status' },
            { key: 'actions', label: 'Actions', width: '150px' }
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

        this.state.employees.forEach(employee => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedEmployees.includes(employee.id),
                onchange: (e) => this.handleEmployeeSelect(employee.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Employee ID
            row.appendChild(DOM.create('td', {}, employee.employee_id));

            // Name
            row.appendChild(DOM.create('td', {}, `${employee.first_name} ${employee.last_name}`));

            // Department
            row.appendChild(DOM.create('td', {}, employee.department_name || 'N/A'));

            // Position
            row.appendChild(DOM.create('td', {}, employee.position_title || 'N/A'));

            // Salary
            row.appendChild(DOM.create('td', {}, '$' + (employee.salary || 0).toLocaleString()));

            // Hire Date
            row.appendChild(DOM.create('td', {}, this.formatDate(employee.hire_date)));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${employee.employment_status}`
            }, employee.employment_status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewEmployeeDetails(employee)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showEmployeeModal(employee)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const attendanceButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.showAttendanceModal(employee)
            });
            attendanceButton.innerHTML = '<i class="fas fa-clock"></i>';
            actions.appendChild(attendanceButton);

            const deleteButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-danger',
                onclick: () => this.deleteEmployee(employee.id)
            });
            deleteButton.innerHTML = '<i class="fas fa-user-minus"></i>';
            actions.appendChild(deleteButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderDepartments() {
        const departmentsView = DOM.create('div', { className: 'departments-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showDepartmentModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Department';
        toolbar.appendChild(addButton);
        departmentsView.appendChild(toolbar);

        // Departments grid
        const grid = DOM.create('div', { className: 'departments-grid' });

        this.state.departments.forEach(dept => {
            const card = DOM.create('div', { className: 'department-card' });

            const header = DOM.create('div', { className: 'department-header' });
            header.appendChild(DOM.create('h3', {}, dept.department_name));
            header.appendChild(DOM.create('p', {}, dept.description || 'No description'));
            card.appendChild(header);

            const stats = DOM.create('div', { className: 'department-stats' });
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, dept.employee_count || 0),
                DOM.create('span', { className: 'stat-label' }, 'Employees')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, '$' + (dept.avg_salary || 0).toLocaleString()),
                DOM.create('span', { className: 'stat-label' }, 'Avg Salary')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, dept.manager_first ? `${dept.manager_first} ${dept.manager_last}` : 'Not assigned'),
                DOM.create('span', { className: 'stat-label' }, 'Manager')
            ));
            card.appendChild(stats);

            grid.appendChild(card);
        });

        departmentsView.appendChild(grid);
        return departmentsView;
    }

    renderAttendance() {
        const attendanceView = DOM.create('div', { className: 'attendance-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const recordButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showAttendanceModal()
        });
        recordButton.innerHTML = '<i class="fas fa-plus"></i> Record Attendance';
        toolbar.appendChild(recordButton);
        attendanceView.appendChild(toolbar);

        // Attendance table
        const table = this.renderAttendanceTable();
        attendanceView.appendChild(table);

        return attendanceView;
    }

    renderAttendanceTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'first_name', label: 'Employee' },
            { key: 'record_date', label: 'Date' },
            { key: 'check_in_time', label: 'Check In' },
            { key: 'check_out_time', label: 'Check Out' },
            { key: 'hours_worked', label: 'Hours' },
            { key: 'status', label: 'Status' },
            { key: 'is_late', label: 'Late' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {}, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.attendance.forEach(record => {
            const row = DOM.create('tr', {});

            // Employee
            row.appendChild(DOM.create('td', {}, `${record.first_name} ${record.last_name}`));

            // Date
            row.appendChild(DOM.create('td', {}, this.formatDate(record.record_date)));

            // Check In
            row.appendChild(DOM.create('td', {}, record.check_in_time || 'N/A'));

            // Check Out
            row.appendChild(DOM.create('td', {}, record.check_out_time || 'N/A'));

            // Hours
            row.appendChild(DOM.create('td', {}, record.hours_worked ? record.hours_worked.toFixed(2) : '0.00'));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${record.status}`
            }, record.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Late
            const lateCell = DOM.create('td', {});
            const lateIndicator = DOM.create('span', {
                className: record.is_late ? 'late-indicator late' : 'late-indicator on-time'
            }, record.is_late ? 'Late' : 'On Time');
            lateCell.appendChild(lateIndicator);
            row.appendChild(lateCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderPayroll() {
        const payrollView = DOM.create('div', { className: 'payroll-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const processButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showPayrollModal()
        });
        processButton.innerHTML = '<i class="fas fa-cogs"></i> Process Payroll';
        toolbar.appendChild(processButton);
        payrollView.appendChild(toolbar);

        // Payroll table
        const table = this.renderPayrollTable();
        payrollView.appendChild(table);

        return payrollView;
    }

    renderPayrollTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'payroll_date', label: 'Payroll Date' },
            { key: 'employee_count', label: 'Employees' },
            { key: 'total_gross', label: 'Total Gross' },
            { key: 'total_deductions', label: 'Total Deductions' },
            { key: 'total_net', label: 'Total Net' },
            { key: 'status', label: 'Status' },
            { key: 'actions', label: 'Actions', width: '120px' }
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

        this.state.payroll.forEach(run => {
            const row = DOM.create('tr', {});

            // Payroll Date
            row.appendChild(DOM.create('td', {}, this.formatDate(run.payroll_date)));

            // Employee Count
            row.appendChild(DOM.create('td', {}, run.employee_count || 0));

            // Total Gross
            row.appendChild(DOM.create('td', {}, '$' + (run.total_gross || 0).toLocaleString()));

            // Total Deductions
            row.appendChild(DOM.create('td', {}, '$' + (run.total_deductions || 0).toLocaleString()));

            // Total Net
            row.appendChild(DOM.create('td', {}, '$' + (run.total_net || 0).toLocaleString()));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${run.status}`
            }, run.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewPayrollDetails(run)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderPerformance() {
        const performanceView = DOM.create('div', { className: 'performance-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const reviewButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showPerformanceModal()
        });
        reviewButton.innerHTML = '<i class="fas fa-plus"></i> Create Review';
        toolbar.appendChild(reviewButton);
        performanceView.appendChild(toolbar);

        // Performance table
        const table = this.renderPerformanceTable();
        performanceView.appendChild(table);

        return performanceView;
    }

    renderPerformanceTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'first_name', label: 'Employee' },
            { key: 'review_period', label: 'Period' },
            { key: 'overall_rating', label: 'Rating' },
            { key: 'goals_achieved_percentage', label: 'Goals %' },
            { key: 'review_date', label: 'Review Date' },
            { key: 'reviewer_first', label: 'Reviewer' },
            { key: 'actions', label: 'Actions', width: '120px' }
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

        this.state.performance.forEach(review => {
            const row = DOM.create('tr', {});

            // Employee
            row.appendChild(DOM.create('td', {}, `${review.first_name} ${review.last_name}`));

            // Period
            row.appendChild(DOM.create('td', {}, review.review_period));

            // Rating
            const ratingCell = DOM.create('td', {});
            const ratingBadge = DOM.create('span', {
                className: `rating-badge rating-${review.overall_rating}`
            }, `${review.overall_rating}/5`);
            ratingCell.appendChild(ratingBadge);
            row.appendChild(ratingCell);

            // Goals %
            row.appendChild(DOM.create('td', {}, `${review.goals_achieved_percentage || 0}%`));

            // Review Date
            row.appendChild(DOM.create('td', {}, this.formatDate(review.review_date)));

            // Reviewer
            row.appendChild(DOM.create('td', {}, review.reviewer_first ? `${review.reviewer_first} ${review.reviewer_last}` : 'N/A'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewPerformanceReview(review)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderLeave() {
        const leaveView = DOM.create('div', { className: 'leave-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const requestButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showLeaveModal()
        });
        requestButton.innerHTML = '<i class="fas fa-plus"></i> Request Leave';
        toolbar.appendChild(requestButton);
        leaveView.appendChild(toolbar);

        // Leave table
        const table = this.renderLeaveTable();
        leaveView.appendChild(table);

        return leaveView;
    }

    renderLeaveTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'first_name', label: 'Employee' },
            { key: 'leave_type', label: 'Type' },
            { key: 'start_date', label: 'Start Date' },
            { key: 'end_date', label: 'End Date' },
            { key: 'days_requested', label: 'Days' },
            { key: 'status', label: 'Status' },
            { key: 'actions', label: 'Actions', width: '120px' }
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

        this.state.leave.forEach(leave => {
            const row = DOM.create('tr', {});

            // Employee
            row.appendChild(DOM.create('td', {}, `${leave.first_name} ${leave.last_name}`));

            // Type
            row.appendChild(DOM.create('td', {}, leave.leave_type));

            // Start Date
            row.appendChild(DOM.create('td', {}, this.formatDate(leave.start_date)));

            // End Date
            row.appendChild(DOM.create('td', {}, this.formatDate(leave.end_date)));

            // Days
            row.appendChild(DOM.create('td', {}, leave.days_requested));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${leave.status}`
            }, leave.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            if (leave.status === 'pending') {
                const approveButton = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-success',
                    onclick: () => this.approveLeaveRequest(leave.id)
                });
                approveButton.innerHTML = '<i class="fas fa-check"></i>';
                actions.appendChild(approveButton);
            }

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewLeaveDetails(leave)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderRecruitment() {
        const recruitmentView = DOM.create('div', { className: 'recruitment-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const postButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showRecruitmentModal()
        });
        postButton.innerHTML = '<i class="fas fa-plus"></i> Post Job';
        toolbar.appendChild(postButton);
        recruitmentView.appendChild(toolbar);

        // Job postings table
        const table = this.renderRecruitmentTable();
        recruitmentView.appendChild(table);

        return recruitmentView;
    }

    renderRecruitmentTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'job_title', label: 'Job Title' },
            { key: 'department_name', label: 'Department' },
            { key: 'application_count', label: 'Applications' },
            { key: 'posting_date', label: 'Posted' },
            { key: 'closing_date', label: 'Closing' },
            { key: 'status', label: 'Status' },
            { key: 'actions', label: 'Actions', width: '120px' }
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

        this.state.recruitment.forEach(job => {
            const row = DOM.create('tr', {});

            // Job Title
            row.appendChild(DOM.create('td', {}, job.job_title));

            // Department
            row.appendChild(DOM.create('td', {}, job.department_name || 'N/A'));

            // Applications
            row.appendChild(DOM.create('td', {}, job.application_count || 0));

            // Posted
            row.appendChild(DOM.create('td', {}, this.formatDate(job.posting_date)));

            // Closing
            row.appendChild(DOM.create('td', {}, job.closing_date ? this.formatDate(job.closing_date) : 'Open'));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${job.status}`
            }, job.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewJobDetails(job)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.editJobPosting(job)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderTraining() {
        const trainingView = DOM.create('div', { className: 'training-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const createButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showTrainingModal()
        });
        createButton.innerHTML = '<i class="fas fa-plus"></i> Create Program';
        toolbar.appendChild(createButton);
        trainingView.appendChild(toolbar);

        // Training programs table
        const table = this.renderTrainingTable();
        trainingView.appendChild(table);

        return trainingView;
    }

    renderTrainingTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'program_name', label: 'Program Name' },
            { key: 'enrolled_count', label: 'Enrolled' },
            { key: 'completed_count', label: 'Completed' },
            { key: 'completion_rate', label: 'Completion %' },
            { key: 'start_date', label: 'Start Date' },
            { key: 'end_date', label: 'End Date' },
            { key: 'actions', label: 'Actions', width: '120px' }
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

        this.state.training.forEach(program => {
            const row = DOM.create('tr', {});

            // Program Name
            row.appendChild(DOM.create('td', {}, program.program_name));

            // Enrolled
            row.appendChild(DOM.create('td', {}, program.enrolled_count || 0));

            // Completed
            row.appendChild(DOM.create('td', {}, program.completed_count || 0));

            // Completion Rate
            row.appendChild(DOM.create('td', {}, `${program.completion_rate || 0}%`));

            // Start Date
            row.appendChild(DOM.create('td', {}, this.formatDate(program.start_date)));

            // End Date
            row.appendChild(DOM.create('td', {}, program.end_date ? this.formatDate(program.end_date) : 'Ongoing'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewTrainingProgram(program)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.editTrainingProgram(program)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });

        // Placeholder for analytics content
        analyticsView.appendChild(DOM.create('div', { className: 'analytics-placeholder' },
            DOM.create('h3', {}, 'HR Analytics'),
            DOM.create('p', {}, 'Analytics dashboard coming soon...')
        ));

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
            this.loadEmployees();
        });
    }

    renderEmployeeModal() {
        // Placeholder for employee modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {},
            this.state.editingEmployee ? 'Edit Employee' : 'Add New Employee'
        ));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideEmployeeModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Employee form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderDepartmentModal() {
        // Placeholder for department modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Add New Department'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDepartmentModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Department form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderAttendanceModal() {
        // Placeholder for attendance modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Record Attendance'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideAttendanceModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Attendance form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderPayrollModal() {
        // Placeholder for payroll modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Process Payroll'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hidePayrollModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Payroll processing form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderPerformanceModal() {
        // Placeholder for performance modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Create Performance Review'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hidePerformanceModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Performance review form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderLeaveModal() {
        // Placeholder for leave modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Request Leave'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideLeaveModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Leave request form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderRecruitmentModal() {
        // Placeholder for recruitment modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Create Job Posting'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideRecruitmentModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Job posting form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderTrainingModal() {
        // Placeholder for training modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Create Training Program'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideTrainingModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Training program form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    // Utility methods
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    formatTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));

        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        return `${Math.floor(diffInMinutes / 1440)}d ago`;
    }
}

// Register component
ComponentRegistry.register('HR', HR);

// Make globally available
if (typeof window !== 'undefined') {
    window.HR = HR;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HR;
}
