/**
 * TPT Free ERP - Learning Management System Component (Refactored)
 * Complete course management, student enrollment, certification, and compliance training interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class LMS extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderers for different data types
        this.coursesTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.enrollmentsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.certificationsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.assessmentsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.complianceTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.coursesTableRenderer.setDataCallback(() => this.state.courses || []);
        this.coursesTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedCourses: selectedIds });
        });
        this.coursesTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.coursesTableRenderer.setDataChangeCallback(() => {
            this.loadCourses();
        });

        this.enrollmentsTableRenderer.setDataCallback(() => this.state.enrollments || []);
        this.enrollmentsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedEnrollments: selectedIds });
        });
        this.enrollmentsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.enrollmentsTableRenderer.setDataChangeCallback(() => {
            this.loadEnrollments();
        });

        this.certificationsTableRenderer.setDataCallback(() => this.state.certifications || []);
        this.certificationsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedCertifications: selectedIds });
        });
        this.certificationsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.certificationsTableRenderer.setDataChangeCallback(() => {
            this.loadCertifications();
        });

        this.assessmentsTableRenderer.setDataCallback(() => this.state.assessments || []);
        this.assessmentsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedCourses: selectedIds });
        });
        this.assessmentsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.assessmentsTableRenderer.setDataChangeCallback(() => {
            this.loadAssessments();
        });

        this.complianceTableRenderer.setDataCallback(() => this.state.compliance || []);
        this.complianceTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedCourses: selectedIds });
        });
        this.complianceTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.complianceTableRenderer.setDataChangeCallback(() => {
            this.loadCompliance();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadCourses',
            'loadEnrollments',
            'loadCertifications',
            'loadAssessments',
            'loadCompliance',
            'loadAnalytics',
            'handleViewChange',
            'handleFilterChange',
            'handleCourseSelect',
            'handleEnrollmentSelect',
            'handleCertificationSelect',
            'handleBulkAction',
            'showCourseModal',
            'hideCourseModal',
            'saveCourse',
            'showEnrollmentModal',
            'hideEnrollmentModal',
            'saveEnrollment',
            'showCertificationModal',
            'hideCertificationModal',
            'saveCertification',
            'updateProgress',
            'submitAssessmentResult',
            'updateComplianceStatus',
            'bulkEnrollStudents',
            'bulkUpdateCourses',
            'exportCourses'
        ];
    }

    async componentDidMount() {
        await this.loadInitialData();
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            this.showNotification('Failed to load LMS data', 'error');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'courses':
                await this.loadCourses();
                break;
            case 'enrollments':
                await this.loadEnrollments();
                break;
            case 'certifications':
                await this.loadCertifications();
                break;
            case 'assessments':
                await this.loadAssessments();
                break;
            case 'compliance':
                await this.loadCompliance();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const [overview, enrollmentStats, certificationStatus, trainingCompliance, assessmentResults, learningAnalytics, upcomingDeadlines, trainingAlerts] = await Promise.all([
                this.apiRequest('/lms/overview'),
                this.apiRequest('/lms/enrollment-stats'),
                this.apiRequest('/lms/certification-status'),
                this.apiRequest('/lms/training-compliance'),
                this.apiRequest('/lms/assessment-results'),
                this.apiRequest('/lms/learning-analytics'),
                this.apiRequest('/lms/upcoming-deadlines'),
                this.apiRequest('/lms/training-alerts')
            ]);

            this.setState({
                overview: {
                    ...overview,
                    enrollmentStats,
                    certificationStatus,
                    trainingCompliance,
                    assessmentResults,
                    learningAnalytics,
                    upcomingDeadlines,
                    trainingAlerts
                }
            });
        } catch (error) {
            this.showNotification('Failed to load LMS overview', 'error');
        }
    }

    async loadCourses() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/lms/courses?${params}`);
            this.setState({
                courses: response.courses,
                pagination: response.pagination
            });
        } catch (error) {
            this.showNotification('Failed to load courses', 'error');
        }
    }

    async loadEnrollments() {
        try {
            const response = await this.apiRequest('/lms/enrollments');
            this.setState({ enrollments: response });
        } catch (error) {
            this.showNotification('Failed to load enrollments', 'error');
        }
    }

    async loadCertifications() {
        try {
            const response = await this.apiRequest('/lms/certifications');
            this.setState({ certifications: response });
        } catch (error) {
            this.showNotification('Failed to load certifications', 'error');
        }
    }

    async loadAssessments() {
        try {
            const response = await this.apiRequest('/lms/assessments');
            this.setState({ assessments: response });
        } catch (error) {
            this.showNotification('Failed to load assessments', 'error');
        }
    }

    async loadCompliance() {
        try {
            const response = await this.apiRequest('/lms/compliance-requirements');
            this.setState({ compliance: response });
        } catch (error) {
            this.showNotification('Failed to load compliance requirements', 'error');
        }
    }

    async loadAnalytics() {
        try {
            const [metrics, engagement, effectiveness] = await Promise.all([
                this.apiRequest('/lms/learning-metrics'),
                this.apiRequest('/lms/student-engagement'),
                this.apiRequest('/lms/course-effectiveness')
            ]);

            this.setState({
                analytics: {
                    metrics,
                    engagement,
                    effectiveness
                }
            });
        } catch (error) {
            this.showNotification('Failed to load learning analytics', 'error');
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
            if (this.state.currentView === 'courses') {
                this.loadCourses();
            }
        });
    }

    handleCourseSelect(courseId, selected) {
        const selectedCourses = selected
            ? [...this.state.selectedCourses, courseId]
            : this.state.selectedCourses.filter(id => id !== courseId);

        this.setState({ selectedCourses });
    }

    handleEnrollmentSelect(enrollmentId, selected) {
        const selectedEnrollments = selected
            ? [...this.state.selectedEnrollments, enrollmentId]
            : this.state.selectedEnrollments.filter(id => id !== enrollmentId);

        this.setState({ selectedEnrollments });
    }

    handleCertificationSelect(certificationId, selected) {
        const selectedCertifications = selected
            ? [...this.state.selectedCertifications, certificationId]
            : this.state.selectedCertifications.filter(id => id !== certificationId);

        this.setState({ selectedCertifications });
    }

    async handleBulkAction(action, selectedIds = null) {
        const selectedCourses = selectedIds || this.state.selectedCourses;
        const selectedEnrollments = selectedIds || this.state.selectedEnrollments;
        const selectedCertifications = selectedIds || this.state.selectedCertifications;

        if (selectedCourses.length === 0 && selectedEnrollments.length === 0 && selectedCertifications.length === 0) {
            this.showNotification('Please select items first', 'warning');
            return;
        }

        try {
            switch (action) {
                case 'bulk_enroll_students':
                    await this.bulkEnrollStudents();
                    break;
                case 'bulk_update_courses':
                    await this.bulkUpdateCourses();
                    break;
                case 'export_courses':
                    await this.exportCourses();
                    break;
                case 'bulk_certify':
                    await this.bulkCertifyStudents();
                    break;
            }
        } catch (error) {
            this.showNotification('Bulk action failed', 'error');
        }
    }

    async bulkEnrollStudents() {
        // Implementation for bulk enrollment modal
        this.showNotification('Bulk enrollment modal coming soon', 'info');
    }

    async bulkUpdateCourses() {
        // Implementation for bulk course update modal
        this.showNotification('Bulk course update modal coming soon', 'info');
    }

    async exportCourses() {
        try {
            const response = await this.apiRequest('/lms/export-courses', null, 'blob');
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'courses_export.csv');
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            this.showNotification('Courses exported successfully', 'success');
        } catch (error) {
            this.showNotification('Export failed', 'error');
        }
    }

    async bulkCertifyStudents() {
        // Implementation for bulk certification modal
        this.showNotification('Bulk certification modal coming soon', 'info');
    }

    showCourseModal(course = null) {
        this.setState({
            showCourseModal: true,
            editingCourse: course
        });
    }

    hideCourseModal() {
        this.setState({
            showCourseModal: false,
            editingCourse: null
        });
    }

    async saveCourse(courseData) {
        try {
            const method = this.state.editingCourse ? 'PUT' : 'POST';
            const url = this.state.editingCourse
                ? `/lms/courses/${this.state.editingCourse.id}`
                : '/lms/courses';

            await this.apiRequest(url, method, courseData);

            this.showNotification(
                `Course ${this.state.editingCourse ? 'updated' : 'created'} successfully`,
                'success'
            );

            this.hideCourseModal();
            await this.loadCourses();
        } catch (error) {
            this.showNotification(error.message || 'Failed to save course', 'error');
        }
    }

    showEnrollmentModal(enrollment = null) {
        this.setState({
            showEnrollmentModal: true,
            editingEnrollment: enrollment
        });
    }

    hideEnrollmentModal() {
        this.setState({
            showEnrollmentModal: false,
            editingEnrollment: null
        });
    }

    async saveEnrollment(enrollmentData) {
        try {
            await this.apiRequest('/lms/enroll-student', 'POST', enrollmentData);

            this.showNotification('Student enrolled successfully', 'success');

            this.hideEnrollmentModal();
            await this.loadEnrollments();
        } catch (error) {
            this.showNotification(error.message || 'Failed to save enrollment', 'error');
        }
    }

    showCertificationModal(certification = null) {
        this.setState({
            showCertificationModal: true,
            editingCertification: certification
        });
    }

    hideCertificationModal() {
        this.setState({
            showCertificationModal: false,
            editingCertification: null
        });
    }

    async saveCertification(certificationData) {
        try {
            await this.apiRequest('/lms/award-certification', 'POST', certificationData);

            this.showNotification('Certification awarded successfully', 'success');

            this.hideCertificationModal();
            await this.loadCertifications();
        } catch (error) {
            this.showNotification(error.message || 'Failed to save certification', 'error');
        }
    }

    async updateProgress(enrollmentId, progress) {
        try {
            await this.apiRequest(`/lms/enrollments/${enrollmentId}/progress`, 'POST', { progress_percentage: progress });

            this.showNotification('Progress updated successfully', 'success');

            await this.loadEnrollments();
        } catch (error) {
            this.showNotification(error.message || 'Failed to update progress', 'error');
        }
    }

    async submitAssessmentResult(assessmentData) {
        try {
            await this.apiRequest('/lms/submit-assessment-result', 'POST', assessmentData);

            this.showNotification('Assessment result submitted successfully', 'success');

            await this.loadAssessments();
        } catch (error) {
            this.showNotification(error.message || 'Failed to submit assessment', 'error');
        }
    }

    async updateComplianceStatus(complianceData) {
        try {
            await this.apiRequest('/lms/update-compliance-status', 'POST', complianceData);

            this.showNotification('Compliance status updated successfully', 'success');

            await this.loadCompliance();
        } catch (error) {
            this.showNotification(error.message || 'Failed to update compliance', 'error');
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'lms-container' });

        // Header
        const header = DOM.create('div', { className: 'lms-header' });
        const titleElement = DOM.create('h1', { className: 'lms-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'lms-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showCourseModal) {
            container.appendChild(this.renderCourseModal());
        }

        if (this.state.showEnrollmentModal) {
            container.appendChild(this.renderEnrollmentModal());
        }

        if (this.state.showCertificationModal) {
            container.appendChild(this.renderCertificationModal());
        }

        if (this.state.showAssessmentModal) {
            container.appendChild(this.renderAssessmentModal());
        }

        if (this.state.showComplianceModal) {
            container.appendChild(this.renderComplianceModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'courses', label: 'Courses', icon: 'fas fa-book' },
            { id: 'enrollments', label: 'Enrollments', icon: 'fas fa-users' },
            { id: 'certifications', label: 'Certifications', icon: 'fas fa-certificate' },
            { id: 'assessments', label: 'Assessments', icon: 'fas fa-clipboard-check' },
            { id: 'compliance', label: 'Compliance', icon: 'fas fa-shield-alt' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'lms-nav' });
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
            DOM.create('p', {}, 'Loading LMS data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'courses':
                return this.renderCourses();
            case 'enrollments':
                return this.renderEnrollments();
            case 'certifications':
                return this.renderCertifications();
            case 'assessments':
                return this.renderAssessments();
            case 'compliance':
                return this.renderCompliance();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'lms-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Learning metrics chart
        const metricsChart = this.renderLearningMetricsChart();
        dashboard.appendChild(metricsChart);

        // Upcoming deadlines
        const deadlines = this.renderUpcomingDeadlines();
        dashboard.appendChild(deadlines);

        // Training alerts
        const alerts = this.renderTrainingAlerts();
        dashboard.appendChild(alerts);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview;
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Courses',
                value: overview.total_courses || 0,
                icon: 'fas fa-book',
                color: 'primary'
            },
            {
                title: 'Active Enrollments',
                value: overview.active_enrollments || 0,
                icon: 'fas fa-users',
                color: 'success'
            },
            {
                title: 'Certifications Awarded',
                value: overview.total_certifications || 0,
                icon: 'fas fa-certificate',
                color: 'info'
            },
            {
                title: 'Compliance Rate',
                value: `${overview.compliance_rate || 0}%`,
                icon: 'fas fa-shield-alt',
                color: 'warning'
            },
            {
                title: 'Avg Completion Rate',
                value: `${overview.avg_completion_rate || 0}%`,
                icon: 'fas fa-chart-line',
                color: 'secondary'
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

    renderLearningMetricsChart() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Learning Metrics Overview'));

        const chartContainer = DOM.create('div', { className: 'chart-container' });
        chartContainer.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Learning metrics chart coming soon...'));
        section.appendChild(chartContainer);

        return section;
    }

    renderUpcomingDeadlines() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Upcoming Deadlines'));

        const deadlines = this.state.overview.upcomingDeadlines || [];
        if (deadlines.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No upcoming deadlines'));
        } else {
            const deadlineList = DOM.create('ul', { className: 'deadline-list' });
            deadlines.slice(0, 5).forEach(deadline => {
                const listItem = DOM.create('li', { className: 'deadline-item' });
                listItem.appendChild(DOM.create('div', { className: 'deadline-info' },
                    DOM.create('strong', {}, deadline.course_name),
                    DOM.create('span', {}, `${deadline.student_first} ${deadline.student_last} - Due: ${this.formatDate(deadline.due_date)}`)
                ));
                deadlineList.appendChild(listItem);
            });
            section.appendChild(deadlineList);
        }

        return section;
    }

    renderTrainingAlerts() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Training Alerts'));

        const alerts = this.state.overview.trainingAlerts || [];
        if (alerts.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No training alerts'));
        } else {
            const alertList = DOM.create('ul', { className: 'alert-list' });
            alerts.slice(0, 5).forEach(alert => {
                const listItem = DOM.create('li', { className: `alert-item ${alert.severity}` });
                listItem.appendChild(DOM.create('div', { className: 'alert-info' },
                    DOM.create('strong', {}, alert.alert_type),
                    DOM.create('span', {}, alert.message)
                ));
                alertList.appendChild(listItem);
            });
            section.appendChild(alertList);
        }

        return section;
    }

    renderCourses() {
        const coursesView = DOM.create('div', { className: 'courses-view' });

        // Toolbar
        const toolbar = this.renderCoursesToolbar();
        coursesView.appendChild(toolbar);

        // Filters
        const filters = this.renderCoursesFilters();
        coursesView.appendChild(filters);

        // Courses table
        const table = this.renderCoursesTable();
        coursesView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        coursesView.appendChild(pagination);

        return coursesView;
    }

    renderCoursesToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedCourses.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedCourses.length} selected`
            ));

            const actions = ['bulk_update_courses', 'export_courses'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action)
                }, action.replace(/_/g, ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showCourseModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Course';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderCoursesFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search courses...',
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
        const statuses = ['', 'draft', 'published', 'archived'];
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

    renderCoursesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'course_name', label: 'Course Name' },
            { key: 'course_code', label: 'Code' },
            { key: 'category', label: 'Category' },
            { key: 'instructor', label: 'Instructor' },
            { key: 'enrollments', label: 'Enrollments' },
            { key: 'completion_rate', label: 'Completion Rate' },
            { key: 'status', label: 'Status' },
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

        this.state.courses.forEach(course => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedCourses.includes(course.id),
                onchange: (e) => this.handleCourseSelect(course.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Course Name
            row.appendChild(DOM.create('td', {}, course.course_name));

            // Course Code
            row.appendChild(DOM.create('td', {}, course.course_code));

            // Category
            row.appendChild(DOM.create('td', {}, course.category_name || 'N/A'));

            // Instructor
            row.appendChild(DOM.create('td', {},
                course.instructor_first ? `${course.instructor_first} ${course.instructor_last}` : 'Unassigned'
            ));

            // Enrollments
            row.appendChild(DOM.create('td', {}, course.enrollment_count || 0));

            // Completion Rate
            const completionCell = DOM.create('td', {});
            if (course.completion_rate) {
                completionCell.appendChild(DOM.create('span', { className: 'completion-rate' },
                    `${course.completion_rate}%`
                ));
            } else {
                completionCell.appendChild(DOM.create('span', {}, 'N/A'));
            }
            row.appendChild(completionCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${course.status}`
            }, course.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewCourse(course)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showCourseModal(course)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const enrollButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.showEnrollmentModal(course)
            });
            enrollButton.innerHTML = '<i class="fas fa-user-plus"></i>';
            actions.appendChild(enrollButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderEnrollments() {
        const enrollmentsView = DOM.create('div', { className: 'enrollments-view' });

        // Toolbar
        const toolbar = this.renderEnrollmentsToolbar();
        enrollmentsView.appendChild(toolbar);

        // Enrollments table
        const table = this.renderEnrollmentsTable();
        enrollmentsView.appendChild(table);

        return enrollmentsView;
    }

    renderEnrollmentsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedEnrollments.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedEnrollments.length} selected`
            ));

            const actions = ['bulk_enroll_students'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action)
                }, action.replace(/_/g, ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showEnrollmentModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Enroll Student';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderEnrollmentsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'student', label: 'Student' },
            { key: 'course', label: 'Course' },
            { key: 'progress', label: 'Progress' },
            { key: 'status', label: 'Status' },
            { key: 'enrollment_date', label: 'Enrolled' },
            { key: 'due_date', label: 'Due Date' },
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

        this.state.enrollments.forEach(enrollment => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedEnrollments.includes(enrollment.id),
                onchange: (e) => this.handleEnrollmentSelect(enrollment.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Student
            row.appendChild(DOM.create('td', {}, `${enrollment.student_first} ${enrollment.student_last}`));

            // Course
            row.appendChild(DOM.create('td', {}, enrollment.course_name));

            // Progress
            const progressCell = DOM.create('td', {});
            const progressBar = DOM.create('div', { className: 'progress-bar' });
            progressBar.appendChild(DOM.create('div', {
                className: 'progress-fill',
                style: `width: ${enrollment.progress_percentage}%`
            }));
            progressBar.appendChild(DOM.create('span', { className: 'progress-text' },
                `${enrollment.progress_percentage}%`
            ));
            progressCell.appendChild(progressBar);
            row.appendChild(progressCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${enrollment.status}`
            }, enrollment.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Enrollment Date
            row.appendChild(DOM.create('td', {}, this.formatDate(enrollment.enrollment_date)));

            // Due Date
            const dueCell = DOM.create('td', {});
            if (enrollment.due_date) {
                const daysUntil = enrollment.days_until_due;
                const dateText = this.formatDate(enrollment.due_date);
                const dateClass = daysUntil < 0 ? 'overdue' : daysUntil <= 7 ? 'due-soon' : 'normal';
                dueCell.appendChild(DOM.create('span', { className: `date ${dateClass}` }, dateText));
            } else {
                dueCell.appendChild(DOM.create('span', {}, 'No due date'));
            }
            row.appendChild(dueCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewEnrollment(enrollment)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const progressButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showProgressModal(enrollment)
            });
            progressButton.innerHTML = '<i class="fas fa-chart-line"></i>';
            actions.appendChild(progressButton);

            const certifyButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.showCertificationModal(enrollment)
            });
            certifyButton.innerHTML = '<i class="fas fa-certificate"></i>';
            actions.appendChild(certifyButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderCertifications() {
        const certificationsView = DOM.create('div', { className: 'certifications-view' });

        // Toolbar
        const toolbar = this.renderCertificationsToolbar();
        certificationsView.appendChild(toolbar);

        // Certifications table
        const table = this.renderCertificationsTable();
        certificationsView.appendChild(table);

        return certificationsView;
    }

    renderCertificationsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedCertifications.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedCertifications.length} selected`
            ));

            const actions = ['bulk_certify'];
            actions.forEach(action => {
                const button = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-secondary',
                    onclick: () => this.handleBulkAction(action)
                }, action.replace(/_/g, ' '));
                bulkActions.appendChild(button);
            });

            leftSection.appendChild(bulkActions);
        }

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showCertificationModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Award Certification';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderCertificationsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'student', label: 'Student' },
            { key: 'certification', label: 'Certification' },
            { key: 'score', label: 'Score' },
            { key: 'issue_date', label: 'Issue Date' },
            { key: 'expiry_date', label: 'Expiry Date' },
            { key: 'status', label: 'Status' },
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

        this.state.certifications.forEach(certification => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedCertifications.includes(certification.id),
                onchange: (e) => this.handleCertificationSelect(certification.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Student
            row.appendChild(DOM.create('td', {}, `${certification.student_first} ${certification.student_last}`));

            // Certification
            row.appendChild(DOM.create('td', {}, certification.certification_name));

            // Score
            row.appendChild(DOM.create('td', {}, `${certification.score}%`));

            // Issue Date
            row.appendChild(DOM.create('td', {}, this.formatDate(certification.issue_date)));

            // Expiry Date
            const expiryCell = DOM.create('td', {});
            if (certification.expiry_date) {
                const daysUntil = certification.days_until_expiry;
                const dateText = this.formatDate(certification.expiry_date);
                const dateClass = daysUntil < 0 ? 'expired' : daysUntil <= 30 ? 'expiring-soon' : 'valid';
                expiryCell.appendChild(DOM.create('span', { className: `date ${dateClass}` }, dateText));
            } else {
                expiryCell.appendChild(DOM.create('span', {}, 'No expiry'));
            }
            row.appendChild(expiryCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${certification.status}`
            }, certification.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewCertification(certification)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const downloadButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.downloadCertificate(certification)
            });
            downloadButton.innerHTML = '<i class="fas fa-download"></i>';
            actions.appendChild(downloadButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderAssessments() {
        const assessmentsView = DOM.create('div', { className: 'assessments-view' });
        assessmentsView.appendChild(DOM.create('h3', {}, 'Assessments & Testing'));
        assessmentsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Assessments interface coming soon...'));
        return assessmentsView;
    }

    renderCompliance() {
        const complianceView = DOM.create('div', { className: 'compliance-view' });
        complianceView.appendChild(DOM.create('h3', {}, 'Compliance Training'));
        complianceView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Compliance interface coming soon...'));
        return complianceView;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'Learning Analytics'));
        analyticsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Analytics interface coming soon...'));
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
            if (this.state.currentView === 'courses') {
                this.loadCourses();
            }
        });
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderCourseModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingCourse ? 'Edit Course' : 'Create Course'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCourseModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Course modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderEnrollmentModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingEnrollment ? 'Edit Enrollment' : 'Enroll Student'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideEnrollmentModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Enrollment modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderCertificationModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingCertification ? 'Edit Certification' : 'Award Certification'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCertificationModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Certification modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderAssessmentModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Assessment'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideAssessmentModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Assessment modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderComplianceModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Compliance'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideComplianceModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Compliance modal coming soon...'));
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

    // ============================================================================
    // PLACEHOLDER METHODS (TO BE IMPLEMENTED)
    // ============================================================================

    hideAssessmentModal() {
        this.setState({ showAssessmentModal: false });
    }

    hideComplianceModal() {
        this.setState({ showComplianceModal: false });
    }

    viewCourse(course) {
        // Implementation for viewing course details
        this.showNotification('Course details view coming soon', 'info');
    }

    viewEnrollment(enrollment) {
        // Implementation for viewing enrollment details
        this.showNotification('Enrollment details view coming soon', 'info');
    }

    viewCertification(certification) {
        // Implementation for viewing certification details
        this.showNotification('Certification details view coming soon', 'info');
    }

    showProgressModal(enrollment) {
        // Implementation for progress modal
        this.showNotification('Progress modal coming soon', 'info');
    }

    downloadCertificate(certification) {
        // Implementation for downloading certificate
        this.showNotification('Certificate download coming soon', 'info');
    }
}

// Export the component
window.LMS = LMS;
