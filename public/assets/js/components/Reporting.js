/**
 * TPT Free ERP - Reporting Component
 * Complete business intelligence, custom reports, and data visualization interface
 */

class Reporting extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Business Intelligence & Reporting',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            reports: [],
            dashboards: [],
            dataSources: [],
            aiInsights: [],
            analytics: {},
            filters: {
                category: '',
                status: '',
                created_by: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedReports: [],
            selectedDashboards: [],
            showReportModal: false,
            showDashboardModal: false,
            showDataSourceModal: false,
            showInsightModal: false,
            editingReport: null,
            editingDashboard: null,
            editingDataSource: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            }
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadReports = this.loadReports.bind(this);
        this.loadDashboards = this.loadDashboards.bind(this);
        this.loadDataSources = this.loadDataSources.bind(this);
        this.loadAIInsights = this.loadAIInsights.bind(this);
        this.loadAnalytics = this.loadAnalytics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleReportSelect = this.handleReportSelect.bind(this);
        this.handleDashboardSelect = this.handleDashboardSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showReportModal = this.showReportModal.bind(this);
        this.hideReportModal = this.hideReportModal.bind(this);
        this.saveReport = this.saveReport.bind(this);
        this.generateReport = this.generateReport.bind(this);
        this.exportReport = this.exportReport.bind(this);
        this.showDashboardModal = this.showDashboardModal.bind(this);
        this.hideDashboardModal = this.hideDashboardModal.bind(this);
        this.saveDashboard = this.saveDashboard.bind(this);
        this.addWidgetToDashboard = this.addWidgetToDashboard.bind(this);
        this.showDataSourceModal = this.showDataSourceModal.bind(this);
        this.hideDataSourceModal = this.hideDataSourceModal.bind(this);
        this.saveDataSource = this.saveDataSource.bind(this);
        this.scheduleReport = this.scheduleReport.bind(this);
        this.shareReport = this.shareReport.bind(this);
        this.showInsightModal = this.showInsightModal.bind(this);
        this.hideInsightModal = this.hideInsightModal.bind(this);
        this.generateAIInsight = this.generateAIInsight.bind(this);
    }

    async componentDidMount() {
        await this.loadInitialData();
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load basic data
            await Promise.all([
                this.loadDataSources()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading reporting data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load reporting data'
            });
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'reports':
                await this.loadReports();
                break;
            case 'dashboards':
                await this.loadDashboards();
                break;
            case 'data-sources':
                await this.loadDataSources();
                break;
            case 'ai-insights':
                await this.loadAIInsights();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/reporting/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading reporting overview:', error);
        }
    }

    async loadReports() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/reporting/reports?${params}`);
            this.setState({
                reports: response.reports,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading reports:', error);
        }
    }

    async loadDashboards() {
        try {
            const response = await API.get('/reporting/dashboards');
            this.setState({ dashboards: response.dashboards });
        } catch (error) {
            console.error('Error loading dashboards:', error);
        }
    }

    async loadDataSources() {
        try {
            const response = await API.get('/reporting/data-sources');
            this.setState({ dataSources: response.data_sources });
        } catch (error) {
            console.error('Error loading data sources:', error);
        }
    }

    async loadAIInsights() {
        try {
            const response = await API.get('/reporting/ai-insights');
            this.setState({ aiInsights: response.insights });
        } catch (error) {
            console.error('Error loading AI insights:', error);
        }
    }

    async loadAnalytics() {
        try {
            const response = await API.get('/reporting/analytics');
            this.setState({ analytics: response });
        } catch (error) {
            console.error('Error loading reporting analytics:', error);
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
            if (this.state.currentView === 'reports') {
                this.loadReports();
            }
        });
    }

    handleReportSelect(reportId, selected) {
        const selectedReports = selected
            ? [...this.state.selectedReports, reportId]
            : this.state.selectedReports.filter(id => id !== reportId);

        this.setState({ selectedReports });
    }

    handleDashboardSelect(dashboardId, selected) {
        const selectedDashboards = selected
            ? [...this.state.selectedDashboards, dashboardId]
            : this.state.selectedDashboards.filter(id => id !== dashboardId);

        this.setState({ selectedDashboards });
    }

    async handleBulkAction(action) {
        if (this.state.selectedReports.length === 0 && this.state.selectedDashboards.length === 0) {
            App.showNotification({
                type: 'warning',
                message: 'Please select items first'
            });
            return;
        }

        try {
            switch (action) {
                case 'update_status':
                    await this.showBulkUpdateModal('status');
                    break;
                case 'update_category':
                    await this.showBulkUpdateModal('category');
                    break;
                case 'export_selected':
                    await this.exportSelected();
                    break;
                case 'delete_selected':
                    await this.deleteSelected();
                    break;
                case 'share_selected':
                    await this.shareSelected();
                    break;
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            App.showNotification({
                type: 'error',
                message: 'Bulk action failed'
            });
        }
    }

    async showBulkUpdateModal(field) {
        // Implementation for bulk update modal
        App.showNotification({
            type: 'info',
            message: 'Bulk update not yet implemented'
        });
    }

    async exportSelected() {
        // Implementation for export selected
        App.showNotification({
            type: 'info',
            message: 'Export selected not yet implemented'
        });
    }

    async deleteSelected() {
        // Implementation for delete selected
        App.showNotification({
            type: 'info',
            message: 'Delete selected not yet implemented'
        });
    }

    async shareSelected() {
        // Implementation for share selected
        App.showNotification({
            type: 'info',
            message: 'Share selected not yet implemented'
        });
    }

    showReportModal(report = null) {
        this.setState({
            showReportModal: true,
            editingReport: report
        });
    }

    hideReportModal() {
        this.setState({
            showReportModal: false,
            editingReport: null
        });
    }

    async saveReport(reportData) {
        try {
            await API.post('/reporting/reports', reportData);
            App.showNotification({
                type: 'success',
                message: 'Report created successfully'
            });
            this.hideReportModal();
            await this.loadReports();
        } catch (error) {
            console.error('Error saving report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save report'
            });
        }
    }

    async generateReport(reportId, parameters = {}) {
        try {
            const response = await API.post(`/reporting/reports/${reportId}/generate`, { parameters });
            App.showNotification({
                type: 'success',
                message: 'Report generated successfully'
            });
            return response.report_data;
        } catch (error) {
            console.error('Error generating report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to generate report'
            });
            throw error;
        }
    }

    async exportReport(reportId, format, parameters = {}) {
        try {
            const response = await fetch(`/api/reporting/reports/${reportId}/export/${format}?${new URLSearchParams({ parameters: JSON.stringify(parameters) })}`);
            if (!response.ok) {
                throw new Error('Export failed');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report_${reportId}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            App.showNotification({
                type: 'success',
                message: 'Report exported successfully'
            });
        } catch (error) {
            console.error('Error exporting report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to export report'
            });
        }
    }

    showDashboardModal(dashboard = null) {
        this.setState({
            showDashboardModal: true,
            editingDashboard: dashboard
        });
    }

    hideDashboardModal() {
        this.setState({
            showDashboardModal: false,
            editingDashboard: null
        });
    }

    async saveDashboard(dashboardData) {
        try {
            await API.post('/reporting/dashboards', dashboardData);
            App.showNotification({
                type: 'success',
                message: 'Dashboard created successfully'
            });
            this.hideDashboardModal();
            await this.loadDashboards();
        } catch (error) {
            console.error('Error saving dashboard:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save dashboard'
            });
        }
    }

    async addWidgetToDashboard(dashboardId, widgetData) {
        try {
            await API.post(`/reporting/dashboards/${dashboardId}/widgets`, widgetData);
            App.showNotification({
                type: 'success',
                message: 'Widget added successfully'
            });
        } catch (error) {
            console.error('Error adding widget:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to add widget'
            });
        }
    }

    showDataSourceModal(dataSource = null) {
        this.setState({
            showDataSourceModal: true,
            editingDataSource: dataSource
        });
    }

    hideDataSourceModal() {
        this.setState({
            showDataSourceModal: false,
            editingDataSource: null
        });
    }

    async saveDataSource(dataSourceData) {
        try {
            await API.post('/reporting/data-sources', dataSourceData);
            App.showNotification({
                type: 'success',
                message: 'Data source created successfully'
            });
            this.hideDataSourceModal();
            await this.loadDataSources();
        } catch (error) {
            console.error('Error saving data source:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save data source'
            });
        }
    }

    async scheduleReport(reportId, scheduleData) {
        try {
            await API.post(`/reporting/reports/${reportId}/schedule`, scheduleData);
            App.showNotification({
                type: 'success',
                message: 'Report scheduled successfully'
            });
        } catch (error) {
            console.error('Error scheduling report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to schedule report'
            });
        }
    }

    async shareReport(reportId, shareData) {
        try {
            await API.post(`/reporting/reports/${reportId}/share`, shareData);
            App.showNotification({
                type: 'success',
                message: 'Report shared successfully'
            });
        } catch (error) {
            console.error('Error sharing report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to share report'
            });
        }
    }

    showInsightModal() {
        this.setState({
            showInsightModal: true
        });
    }

    hideInsightModal() {
        this.setState({
            showInsightModal: false
        });
    }

    async generateAIInsight(insightData) {
        try {
            await API.post('/reporting/ai-insights/generate', insightData);
            App.showNotification({
                type: 'success',
                message: 'AI insight generated successfully'
            });
            this.hideInsightModal();
            await this.loadAIInsights();
        } catch (error) {
            console.error('Error generating AI insight:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to generate AI insight'
            });
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'reporting-container' });

        // Header
        const header = DOM.create('div', { className: 'reporting-header' });
        const titleElement = DOM.create('h1', { className: 'reporting-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'reporting-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showReportModal) {
            container.appendChild(this.renderReportModal());
        }

        if (this.state.showDashboardModal) {
            container.appendChild(this.renderDashboardModal());
        }

        if (this.state.showDataSourceModal) {
            container.appendChild(this.renderDataSourceModal());
        }

        if (this.state.showInsightModal) {
            container.appendChild(this.renderInsightModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'reports', label: 'Reports', icon: 'fas fa-file-alt' },
            { id: 'dashboards', label: 'Dashboards', icon: 'fas fa-chart-bar' },
            { id: 'data-sources', label: 'Data Sources', icon: 'fas fa-database' },
            { id: 'ai-insights', label: 'AI Insights', icon: 'fas fa-brain' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-line' }
        ];

        const nav = DOM.create('nav', { className: 'reporting-nav' });
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
            DOM.create('p', {}, 'Loading reporting data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'reports':
                return this.renderReports();
            case 'dashboards':
                return this.renderDashboards();
            case 'data-sources':
                return this.renderDataSources();
            case 'ai-insights':
                return this.renderAIInsights();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'reporting-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Recent reports
        const recentReports = this.renderRecentReports();
        dashboard.appendChild(recentReports);

        // Popular dashboards
        const popularDashboards = this.renderPopularDashboards();
        dashboard.appendChild(popularDashboards);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.report_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Reports',
                value: overview.total_reports || 0,
                icon: 'fas fa-file-alt',
                color: 'primary'
            },
            {
                title: 'Total Dashboards',
                value: overview.total_dashboards || 0,
                icon: 'fas fa-chart-bar',
                color: 'success'
            },
            {
                title: 'Data Sources',
                value: overview.data_sources || 0,
                icon: 'fas fa-database',
                color: 'info'
            },
            {
                title: 'Scheduled Reports',
                value: overview.scheduled_reports || 0,
                icon: 'fas fa-clock',
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

    renderRecentReports() {
        const reports = this.state.overview.recent_reports || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Recent Reports'));

        if (reports.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No recent reports'));
        } else {
            const reportsList = DOM.create('ul', { className: 'reports-list' });
            reports.slice(0, 5).forEach(report => {
                const listItem = DOM.create('li', { className: 'report-item' });
                listItem.appendChild(DOM.create('div', { className: 'report-name' }, report.report_name));
                listItem.appendChild(DOM.create('div', { className: 'report-meta' },
                    `By ${report.created_by_first} ${report.created_by_last} • ${this.formatTimeAgo(report.last_run)}`
                ));
                reportsList.appendChild(listItem);
            });
            section.appendChild(reportsList);
        }

        return section;
    }

    renderPopularDashboards() {
        const dashboards = this.state.overview.popular_dashboards || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Popular Dashboards'));

        if (dashboards.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No popular dashboards'));
        } else {
            const dashboardsList = DOM.create('ul', { className: 'dashboards-list' });
            dashboards.slice(0, 5).forEach(dashboard => {
                const listItem = DOM.create('li', { className: 'dashboard-item' });
                listItem.appendChild(DOM.create('div', { className: 'dashboard-name' }, dashboard.dashboard_name));
                listItem.appendChild(DOM.create('div', { className: 'dashboard-meta' },
                    `${dashboard.view_count} views • Last viewed ${this.formatTimeAgo(dashboard.last_viewed)}`
                ));
                dashboardsList.appendChild(listItem);
            });
            section.appendChild(dashboardsList);
        }

        return section;
    }

    renderReports() {
        const reportsView = DOM.create('div', { className: 'reports-view' });

        // Toolbar
        const toolbar = this.renderReportsToolbar();
        reportsView.appendChild(toolbar);

        // Filters
        const filters = this.renderReportsFilters();
        reportsView.appendChild(filters);

        // Reports table
        const table = this.renderReportsTable();
        reportsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        reportsView.appendChild(pagination);

        return reportsView;
    }

    renderReportsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedReports.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedReports.length} selected`
            ));

            const actions = ['update_status', 'update_category', 'export_selected', 'share_selected'];
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
            onclick: () => this.showReportModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Report';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderReportsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search reports...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Category filter
        const categoryGroup = DOM.create('div', { className: 'filter-group' });
        const categorySelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.category,
            onchange: (e) => this.handleFilterChange('category', e.target.value)
        });
        categorySelect.appendChild(DOM.create('option', { value: '' }, 'All Categories'));
        const categories = ['financial', 'operational', 'sales', 'inventory', 'hr', 'manufacturing', 'quality', 'compliance', 'executive', 'custom'];
        categories.forEach(cat => {
            categorySelect.appendChild(DOM.create('option', { value: cat }, cat.charAt(0).toUpperCase() + cat.slice(1)));
        });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        categoryGroup.appendChild(categorySelect);
        filters.appendChild(categoryGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-group' });
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'draft', 'active', 'archived'];
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

    renderReportsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'report_name', label: 'Report Name' },
            { key: 'category', label: 'Category' },
            { key: 'created_by', label: 'Created By' },
            { key: 'last_run', label: 'Last Run' },
            { key: 'execution_time', label: 'Exec Time' },
            { key: 'view_count', label: 'Views' },
            { key: 'status', label: 'Status' },
            { key: 'actions', label: 'Actions', width: '250px' }
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

        this.state.reports.forEach(report => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedReports.includes(report.id),
                onchange: (e) => this.handleReportSelect(report.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Report Name
            row.appendChild(DOM.create('td', {}, report.report_name));

            // Category
            row.appendChild(DOM.create('td', {}, report.category ? report.category.charAt(0).toUpperCase() + report.category.slice(1) : 'N/A'));

            // Created By
            row.appendChild(DOM.create('td', {}, report.created_by_first ? `${report.created_by_first} ${report.created_by_last}` : 'N/A'));

            // Last Run
            row.appendChild(DOM.create('td', {}, report.last_run ? this.formatDate(report.last_run) : 'Never'));

            // Execution Time
            row.appendChild(DOM.create('td', {}, report.execution_time ? `${report.execution_time}s` : 'N/A'));

            // Views
            row.appendChild(DOM.create('td', {}, report.view_count || 0));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${report.status}`
            }, report.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const runButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.generateReport(report.id)
            });
            runButton.innerHTML = '<i class="fas fa-play"></i>';
            actions.appendChild(runButton);

            const exportButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.showExportModal(report)
            });
            exportButton.innerHTML = '<i class="fas fa-download"></i>';
            actions.appendChild(exportButton);

            const scheduleButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showScheduleModal(report)
            });
            scheduleButton.innerHTML = '<i class="fas fa-clock"></i>';
            actions.appendChild(scheduleButton);

            const shareButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showShareModal(report)
            });
            shareButton.innerHTML = '<i class="fas fa-share"></i>';
            actions.appendChild(shareButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.showReportModal(report)
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

    renderDashboards() {
        const dashboardsView = DOM.create('div', { className: 'dashboards-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showDashboardModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Dashboard';
        toolbar.appendChild(addButton);
        dashboardsView.appendChild(toolbar);

        // Dashboards grid
        const grid = this.renderDashboardsGrid();
        dashboardsView.appendChild(grid);

        return dashboardsView;
    }

    renderDashboardsGrid() {
        const grid = DOM.create('div', { className: 'dashboards-grid' });

        this.state.dashboards.forEach(dashboard => {
            const card = DOM.create('div', { className: 'dashboard-card' });

            const header = DOM.create('div', { className: 'dashboard-header' });
            header.appendChild(DOM.create('h3', {}, dashboard.dashboard_name));
            header.appendChild(DOM.create('p', {}, dashboard.description || 'No description'));
            card.appendChild(header);

            const meta = DOM.create('div', { className: 'dashboard-meta' });
            meta.appendChild(DOM.create('span', { className: 'meta-item' },
                `Created by ${dashboard.created_by_first} ${dashboard.created_by_last}`
            ));
            meta.appendChild(DOM.create('span', { className: 'meta-item' },
                `Modified ${this.formatTimeAgo(dashboard.last_modified)}`
            ));
            card.appendChild(meta);

            const actions = DOM.create('div', { className: 'dashboard-actions' });
            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewDashboard(dashboard)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i> View';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showDashboardModal(dashboard)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i> Edit';
            actions.appendChild(editButton);

            const shareButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.shareDashboard(dashboard)
            });
            shareButton.innerHTML = '<i class="fas fa-share"></i> Share';
            actions.appendChild(shareButton);

            card.appendChild(actions);
            grid.appendChild(card);
        });

        return grid;
    }

    renderDataSources() {
        const dataSourcesView = DOM.create('div', { className: 'data-sources-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showDataSourceModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Data Source';
        toolbar.appendChild(addButton);
        dataSourcesView.appendChild(toolbar);

        // Data sources table
        const table = this.renderDataSourcesTable();
        dataSourcesView.appendChild(table);

        return dataSourcesView;
    }

    renderDataSourcesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'source_name', label: 'Source Name' },
            { key: 'source_type', label: 'Type' },
            { key: 'connection_status', label: 'Status' },
            { key: 'last_sync', label: 'Last Sync' },
            { key: 'record_count', label: 'Records' },
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

        this.state.dataSources.forEach(source => {
            const row = DOM.create('tr', {});

            // Source Name
            row.appendChild(DOM.create('td', {}, source.source_name));

            // Type
            row.appendChild(DOM.create('td', {}, source.source_type));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${source.connection_status}`
            }, source.connection_status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Last Sync
            row.appendChild(DOM.create('td', {}, source.last_sync ? this.formatDate(source.last_sync) : 'Never'));

            // Records
            row.appendChild(DOM.create('td', {}, source.record_count ? source.record_count.toLocaleString() : 'N/A'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const testButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.testDataSource(source)
            });
            testButton.innerHTML = '<i class="fas fa-plug"></i>';
            actions.appendChild(testButton);

            const syncButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.syncDataSource(source)
            });
            syncButton.innerHTML = '<i class="fas fa-sync"></i>';
            actions.appendChild(syncButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showDataSourceModal(source)
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

    renderAIInsights() {
        const insightsView = DOM.create('div', { className: 'ai-insights-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const generateButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showInsightModal()
        });
        generateButton.innerHTML = '<i class="fas fa-brain"></i> Generate Insight';
        toolbar.appendChild(generateButton);
        insightsView.appendChild(toolbar);

        // AI insights list
        const insightsList = this.renderAIInsightsList();
        insightsView.appendChild(insightsList);

        return insightsView;
    }

    renderAIInsightsList() {
        const list = DOM.create('div', { className: 'insights-list' });

        this.state.aiInsights.forEach(insight => {
            const card = DOM.create('div', { className: 'insight-card' });

            const header = DOM.create('div', { className: 'insight-header' });
            header.appendChild(DOM.create('h3', {}, insight.insight_type));
            header.appendChild(DOM.create('span', { className: 'insight-confidence' },
                `${insight.confidence_score}% confidence`
            ));
            card.appendChild(header);

            const content = DOM.create('div', { className: 'insight-content' });
            content.appendChild(DOM.create('p', {}, insight.description));
            content.appendChild(DOM.create('p', { className: 'insight-recommendation' }, insight.recommendation));
            card.appendChild(content);

            const meta = DOM.create('div', { className: 'insight-meta' });
            meta.appendChild(DOM.create('span', { className: 'meta-item' },
                `Generated ${this.formatTimeAgo(insight.generated_at)}`
            ));
            meta.appendChild(DOM.create('span', { className: 'meta-item' },
                `Impact: ${insight.impact_level}`
            ));
            card.appendChild(meta);

            const actions = DOM.create('div', { className: 'insight-actions' });
            const implementButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.implementInsight(insight)
            });
            implementButton.innerHTML = '<i class="fas fa-check"></i> Implement';
            actions.appendChild(implementButton);

            const dismissButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.dismissInsight(insight)
            });
            dismissButton.innerHTML = '<i class="fas fa-times"></i> Dismiss';
            actions.appendChild(dismissButton);

            card.appendChild(actions);
            list.appendChild(card);
        });

        return list;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });

        // Usage analytics
        const usageSection = this.renderUsageAnalytics();
        analyticsView.appendChild(usageSection);

        // Performance metrics
        const performanceSection = this.renderPerformanceMetrics();
        analyticsView.appendChild(performanceSection);

        // Data quality
        const qualitySection = this.renderDataQuality();
        analyticsView.appendChild(qualitySection);

        return analyticsView;
    }

    renderUsageAnalytics() {
        const analytics = this.state.analytics.usage_analytics || {};
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Usage Analytics'));

        const metrics = DOM.create('div', { className: 'analytics-metrics' });

        const metricData = [
            { label: 'Total Sessions', value: analytics.total_sessions || 0 },
            { label: 'Unique Users', value: analytics.unique_users || 0 },
            { label: 'Avg Session Time', value: `${analytics.avg_session_duration || 0} min` },
            { label: 'Total Page Views', value: analytics.total_page_views || 0 }
        ];

        metricData.forEach(metric => {
            const metricItem = DOM.create('div', { className: 'metric-item' });
            metricItem.appendChild(DOM.create('div', { className: 'metric-value' }, metric.value));
            metricItem.appendChild(DOM.create('div', { className: 'metric-label' }, metric.label));
            metrics.appendChild(metricItem);
        });

        section.appendChild(metrics);
        return section;
    }

    renderPerformanceMetrics() {
        const metrics = this.state.analytics.performance_metrics || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Performance Metrics'));

        if (metrics.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No performance data available'));
        } else {
            const metricsList = DOM.create('ul', { className: 'metrics-list' });
            metrics.forEach(metric => {
                const listItem = DOM.create('li', { className: 'metric-item' });
                listItem.appendChild(DOM.create('span', { className: 'metric-name' }, metric.metric_name));
                listItem.appendChild(DOM.create('span', { className: 'metric-value' }, metric.metric_value));
                listItem.appendChild(DOM.create('span', { className: 'metric-target' }, `Target: ${metric.target_value}`));
                listItem.appendChild(DOM.create('span', { className: 'metric-change' },
                    `${metric.performance_percentage}%`
                ));
                metricsList.appendChild(listItem);
            });
            section.appendChild(metricsList);
        }

        return section;
    }

    renderDataQuality() {
        const quality = this.state.analytics.data_quality_metrics || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Data Quality Metrics'));

        if (quality.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No quality data available'));
        } else {
            const qualityList = DOM.create('ul', { className: 'quality-list' });
            quality.forEach(item => {
                const listItem = DOM.create('li', { className: 'quality-item' });
                listItem.appendChild(DOM.create('span', { className: 'quality-source' }, item.source_name));
                listItem.appendChild(DOM.create('span', { className: 'quality-score' },
                    `Completeness: ${item.completeness_score}%`
                ));
                listItem.appendChild(DOM.create('span', { className: 'quality-score' },
                    `Accuracy: ${item.accuracy_score}%`
                ));
                listItem.appendChild(DOM.create('span', { className: 'quality-overall' },
                    `Overall: ${item.overall_quality_score}%`
                ));
                qualityList.appendChild(listItem);
            });
            section.appendChild(qualityList);
        }

        return section;
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
            if (this.state.currentView === 'reports') {
                this.loadReports();
            }
        });
    }

    renderReportModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingReport ? 'Edit Report' : 'Create Report'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideReportModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'report-form' });

        // Report Name
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Report Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            required: true,
            value: this.state.editingReport?.report_name || ''
        });
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        // Description
        const descGroup = DOM.create('div', { className: 'form-group' });
        descGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 3,
            value: this.state.editingReport?.description || ''
        });
        descGroup.appendChild(descTextarea);
        form.appendChild(descGroup);

        // Category
        const categoryGroup = DOM.create('div', { className: 'form-group' });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        const categorySelect = DOM.create('select', { className: 'form-control' });
        const categories = ['financial', 'operational', 'sales', 'inventory', 'hr', 'manufacturing', 'quality', 'compliance', 'executive', 'custom'];
        categories.forEach(cat => {
            const option = DOM.create('option', {
                value: cat,
                selected: this.state.editingReport?.category === cat
            }, cat.charAt(0).toUpperCase() + cat.slice(1));
            categorySelect.appendChild(option);
        });
        categoryGroup.appendChild(categorySelect);
        form.appendChild(categoryGroup);

        // Query Template
        const queryGroup = DOM.create('div', { className: 'form-group' });
        queryGroup.appendChild(DOM.create('label', {}, 'SQL Query Template:'));
        const queryTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 8,
            required: true,
            placeholder: 'SELECT * FROM table_name WHERE condition = {parameter}',
            value: this.state.editingReport?.query_template || ''
        });
        queryGroup.appendChild(queryTextarea);
        form.appendChild(queryGroup);

        // Data Source
        const dataSourceGroup = DOM.create('div', { className: 'form-group' });
        dataSourceGroup.appendChild(DOM.create('label', {}, 'Data Source:'));
        const dataSourceSelect = DOM.create('select', { className: 'form-control' });
        dataSourceSelect.appendChild(DOM.create('option', { value: '' }, 'Default'));
        this.state.dataSources.forEach(source => {
            const option = DOM.create('option', {
                value: source.id,
                selected: this.state.editingReport?.data_source_id === source.id
            }, source.source_name);
            dataSourceSelect.appendChild(option);
        });
        dataSourceGroup.appendChild(dataSourceSelect);
        form.appendChild(dataSourceGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideReportModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const saveButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const reportData = {
                    report_name: nameInput.value.trim(),
                    description: descTextarea.value.trim(),
                    category: categorySelect.value,
                    query_template: queryTextarea.value.trim(),
                    data_source_id: dataSourceSelect.value || null
                };
                this.saveReport(reportData);
            }
        }, this.state.editingReport ? 'Update Report' : 'Create Report');
        footer.appendChild(saveButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    renderDashboardModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingDashboard ? 'Edit Dashboard' : 'Create Dashboard'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDashboardModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'dashboard-form' });

        // Dashboard Name
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Dashboard Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            required: true,
            value: this.state.editingDashboard?.dashboard_name || ''
        });
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        // Description
        const descGroup = DOM.create('div', { className: 'form-group' });
        descGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 3,
            value: this.state.editingDashboard?.description || ''
        });
        descGroup.appendChild(descTextarea);
        form.appendChild(descGroup);

        // Category
        const categoryGroup = DOM.create('div', { className: 'form-group' });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        const categorySelect = DOM.create('select', { className: 'form-control' });
        const categories = ['general', 'financial', 'operational', 'sales', 'inventory', 'hr', 'manufacturing', 'quality', 'executive'];
        categories.forEach(cat => {
            const option = DOM.create('option', {
                value: cat,
                selected: this.state.editingDashboard?.category === cat
            }, cat.charAt(0).toUpperCase() + cat.slice(1));
            categorySelect.appendChild(option);
        });
        categoryGroup.appendChild(categorySelect);
        form.appendChild(categoryGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideDashboardModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const saveButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const dashboardData = {
                    dashboard_name: nameInput.value.trim(),
                    description: descTextarea.value.trim(),
                    category: categorySelect.value
                };
                this.saveDashboard(dashboardData);
            }
        }, this.state.editingDashboard ? 'Update Dashboard' : 'Create Dashboard');
        footer.appendChild(saveButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    renderDataSourceModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingDataSource ? 'Edit Data Source' : 'Create Data Source'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDataSourceModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'data-source-form' });

        // Source Name
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Source Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            required: true,
            value: this.state.editingDataSource?.source_name || ''
        });
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        // Source Type
        const typeGroup = DOM.create('div', { className: 'form-group' });
        typeGroup.appendChild(DOM.create('label', {}, 'Source Type:'));
        const typeSelect = DOM.create('select', { className: 'form-control' });
        const types = ['postgresql', 'mysql', 'mongodb', 'api', 'csv', 'excel', 'json'];
        types.forEach(type => {
            const option = DOM.create('option', {
                value: type,
                selected: this.state.editingDataSource?.source_type === type
            }, type.toUpperCase());
            typeSelect.appendChild(option);
        });
        typeGroup.appendChild(typeSelect);
        form.appendChild(typeGroup);

        // Connection String
        const connGroup = DOM.create('div', { className: 'form-group' });
        connGroup.appendChild(DOM.create('label', {}, 'Connection String:'));
        const connInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'host=localhost port=5432 dbname=mydb user=myuser password=mypass',
            value: this.state.editingDataSource?.connection_string || ''
        });
        connGroup.appendChild(connInput);
        form.appendChild(connGroup);

        // Description
        const descGroup = DOM.create('div', { className: 'form-group' });
        descGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descTextarea = DOM.create('textarea', {
            className: 'form-control',
            rows: 3,
            value: this.state.editingDataSource?.description || ''
        });
        descGroup.appendChild(descTextarea);
        form.appendChild(descGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideDataSourceModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const saveButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const dataSourceData = {
                    source_name: nameInput.value.trim(),
                    source_type: typeSelect.value,
                    connection_string: connInput.value.trim(),
                    description: descTextarea.value.trim()
                };
                this.saveDataSource(dataSourceData);
            }
        }, this.state.editingDataSource ? 'Update Data Source' : 'Create Data Source');
        footer.appendChild(saveButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    renderInsightModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Generate AI Insight'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideInsightModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        const form = DOM.create('form', { className: 'insight-form' });

        // Data Source
        const sourceGroup = DOM.create('div', { className: 'form-group' });
        sourceGroup.appendChild(DOM.create('label', {}, 'Data Source:'));
        const sourceSelect = DOM.create('select', { className: 'form-control' });
        this.state.dataSources.forEach(source => {
            const option = DOM.create('option', { value: source.id }, source.source_name);
            sourceSelect.appendChild(option);
        });
        sourceGroup.appendChild(sourceSelect);
        form.appendChild(sourceGroup);

        // Insight Type
        const typeGroup = DOM.create('div', { className: 'form-group' });
        typeGroup.appendChild(DOM.create('label', {}, 'Insight Type:'));
        const typeSelect = DOM.create('select', { className: 'form-control' });
        const types = ['trend_analysis', 'anomaly_detection', 'predictive_insights', 'correlation_analysis', 'pattern_recognition'];
        types.forEach(type => {
            const option = DOM.create('option', { value: type }, type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
            typeSelect.appendChild(option);
        });
        typeGroup.appendChild(typeSelect);
        form.appendChild(typeGroup);

        body.appendChild(form);
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const cancelButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-secondary',
            onclick: () => this.hideInsightModal()
        }, 'Cancel');
        footer.appendChild(cancelButton);

        const generateButton = DOM.create('button', {
            type: 'button',
            className: 'btn btn-primary',
            onclick: () => {
                const insightData = {
                    data_source_id: sourceSelect.value,
                    insight_type: typeSelect.value
                };
                this.generateAIInsight(insightData);
            }
        }, 'Generate Insight');
        footer.appendChild(generateButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);

        return modal;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    formatDate(dateString) {
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

    showExportModal(report) {
        // Implementation for export modal
        App.showNotification({
            type: 'info',
            message: 'Export modal not yet implemented'
        });
    }

    showScheduleModal(report) {
        // Implementation for schedule modal
        App.showNotification({
            type: 'info',
            message: 'Schedule modal not yet implemented'
        });
    }

    showShareModal(report) {
        // Implementation for share modal
        App.showNotification({
            type: 'info',
            message: 'Share modal not yet implemented'
        });
    }

    viewDashboard(dashboard) {
        // Implementation for viewing dashboard
        App.showNotification({
            type: 'info',
            message: 'Dashboard view not yet implemented'
        });
    }

    shareDashboard(dashboard) {
        // Implementation for sharing dashboard
        App.showNotification({
            type: 'info',
            message: 'Dashboard sharing not yet implemented'
        });
    }

    testDataSource(source) {
        // Implementation for testing data source
        App.showNotification({
            type: 'info',
            message: 'Data source testing not yet implemented'
        });
    }

    syncDataSource(source) {
        // Implementation for syncing data source
        App.showNotification({
            type: 'info',
            message: 'Data source sync not yet implemented'
        });
    }

    implementInsight(insight) {
        // Implementation for implementing insight
        App.showNotification({
            type: 'info',
            message: 'Insight implementation not yet implemented'
        });
    }

    dismissInsight(insight) {
        // Implementation for dismissing insight
        App.showNotification({
            type: 'info',
            message: 'Insight dismissal not yet implemented'
        });
    }
}

// Export the component
window.Reporting = Reporting;
