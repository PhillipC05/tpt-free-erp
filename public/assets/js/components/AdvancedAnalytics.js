/**
 * TPT Free ERP - Advanced Analytics & Business Intelligence Component
 * Complete data visualization, predictive modeling, and business intelligence interface
 */

class AdvancedAnalytics extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Advanced Analytics & BI',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            dashboard: {},
            dashboards: [],
            visualizations: [],
            predictiveModels: [],
            reports: [],
            realTimeMetrics: [],
            alerts: [],
            filters: {
                category: '',
                type: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedItems: [],
            showCreateModal: false,
            showFilterModal: false,
            editingItem: null,
            modalType: '',
            liveDataInterval: null,
            chartInstances: new Map()
        };

        // Bind methods
        this.loadDashboard = this.loadDashboard.bind(this);
        this.loadDashboards = this.loadDashboards.bind(this);
        this.loadVisualizations = this.loadVisualizations.bind(this);
        this.loadPredictiveModels = this.loadPredictiveModels.bind(this);
        this.loadReports = this.loadReports.bind(this);
        this.loadRealTimeMetrics = this.loadRealTimeMetrics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleItemSelect = this.handleItemSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showCreateModal = this.showCreateModal.bind(this);
        this.hideCreateModal = this.hideCreateModal.bind(this);
        this.saveItem = this.saveItem.bind(this);
        this.showFilterModal = this.showFilterModal.bind(this);
        this.hideFilterModal = this.hideFilterModal.bind(this);
        this.applyFilters = this.applyFilters.bind(this);
        this.createDashboard = this.createDashboard.bind(this);
        this.createVisualization = this.createVisualization.bind(this);
        this.createReport = this.createReport.bind(this);
        this.runPredictiveModel = this.runPredictiveModel.bind(this);
        this.executeReport = this.executeReport.bind(this);
        this.exportData = this.exportData.bind(this);
        this.generateInsights = this.generateInsights.bind(this);
        this.startLiveUpdates = this.startLiveUpdates.bind(this);
        this.stopLiveUpdates = this.stopLiveUpdates.bind(this);
        this.renderChart = this.renderChart.bind(this);
        this.updateChart = this.updateChart.bind(this);
        this.destroyChart = this.destroyChart.bind(this);
    }

    async componentDidMount() {
        await this.loadInitialData();

        // Start live updates for real-time view
        if (this.state.currentView === 'realtime') {
            this.startLiveUpdates();
        }
    }

    componentWillUnmount() {
        this.stopLiveUpdates();
        // Destroy all chart instances
        this.state.chartInstances.forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.setState({ chartInstances: new Map() });
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading analytics data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load analytics data'
            });
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadDashboard();
                break;
            case 'dashboards':
                await this.loadDashboards();
                break;
            case 'visualizations':
                await this.loadVisualizations();
                break;
            case 'predictive':
                await this.loadPredictiveModels();
                break;
            case 'reports':
                await this.loadReports();
                break;
            case 'realtime':
                await this.loadRealTimeMetrics();
                break;
        }
    }

    async loadDashboard() {
        try {
            const [overview, keyMetrics, dataInsights, predictiveAnalytics] = await Promise.all([
                API.get('/analytics/dashboard/overview'),
                API.get('/analytics/key-metrics'),
                API.get('/analytics/data-insights'),
                API.get('/analytics/predictive-analytics')
            ]);

            this.setState({
                dashboard: {
                    overview,
                    keyMetrics,
                    dataInsights,
                    predictiveAnalytics
                }
            });
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    async loadDashboards() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination?.page || 1,
                limit: this.state.pagination?.limit || 50
            });

            const response = await API.get(`/analytics/dashboards?${params}`);
            this.setState({
                dashboards: response.dashboards || [],
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading dashboards:', error);
        }
    }

    async loadVisualizations() {
        try {
            const [charts, graphs, maps, customVisualizations] = await Promise.all([
                API.get('/analytics/charts'),
                API.get('/analytics/graphs'),
                API.get('/analytics/maps'),
                API.get('/analytics/custom-visualizations')
            ]);

            this.setState({
                visualizations: {
                    charts: charts || [],
                    graphs: graphs || [],
                    maps: maps || [],
                    custom: customVisualizations || []
                }
            });
        } catch (error) {
            console.error('Error loading visualizations:', error);
        }
    }

    async loadPredictiveModels() {
        try {
            const [models, algorithms, trainingData, modelPerformance] = await Promise.all([
                API.get('/analytics/predictive-models'),
                API.get('/analytics/algorithms'),
                API.get('/analytics/training-data'),
                API.get('/analytics/model-performance')
            ]);

            this.setState({
                predictiveModels: {
                    models: models || [],
                    algorithms: algorithms || [],
                    trainingData: trainingData || [],
                    performance: modelPerformance || []
                }
            });
        } catch (error) {
            console.error('Error loading predictive models:', error);
        }
    }

    async loadReports() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination?.page || 1,
                limit: this.state.pagination?.limit || 50
            });

            const response = await API.get(`/analytics/reports?${params}`);
            this.setState({
                reports: response.reports || [],
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading reports:', error);
        }
    }

    async loadRealTimeMetrics() {
        try {
            const response = await API.get('/analytics/real-time-metrics');
            this.setState({
                realTimeMetrics: response.metrics || [],
                alerts: response.alerts || []
            });
        } catch (error) {
            console.error('Error loading real-time metrics:', error);
        }
    }

    handleViewChange(view) {
        // Stop previous live updates
        this.stopLiveUpdates();

        this.setState({ currentView: view }, async () => {
            await this.loadCurrentViewData();

            // Start live updates if needed
            if (view === 'realtime') {
                this.startLiveUpdates();
            }
        });
    }

    handleFilterChange(filterName, value) {
        const newFilters = { ...this.state.filters, [filterName]: value };
        this.setState({
            filters: newFilters,
            pagination: { ...this.state.pagination, page: 1 }
        });
    }

    handleItemSelect(itemId, selected) {
        const selectedItems = selected
            ? [...this.state.selectedItems, itemId]
            : this.state.selectedItems.filter(id => id !== itemId);

        this.setState({ selectedItems });
    }

    async handleBulkAction(action) {
        if (this.state.selectedItems.length === 0) {
            App.showNotification({
                type: 'warning',
                message: 'Please select items first'
            });
            return;
        }

        try {
            switch (action) {
                case 'bulk_delete':
                    await this.bulkDelete();
                    break;
                case 'bulk_publish':
                    await this.bulkPublish();
                    break;
                case 'bulk_archive':
                    await this.bulkArchive();
                    break;
                case 'bulk_export':
                    await this.bulkExport();
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

    async bulkDelete() {
        for (const itemId of this.state.selectedItems) {
            try {
                await API.delete(`/analytics/${this.state.currentView}/${itemId}`);
            } catch (e) {
                console.error(`Failed to delete item ${itemId}:`, e);
            }
        }

        App.showNotification({
            type: 'success',
            message: `${this.state.selectedItems.length} items deleted`
        });

        this.setState({ selectedItems: [] });
        await this.loadCurrentViewData();
    }

    async bulkPublish() {
        for (const itemId of this.state.selectedItems) {
            try {
                await API.post(`/analytics/${this.state.currentView}/${itemId}/publish`);
            } catch (e) {
                console.error(`Failed to publish item ${itemId}:`, e);
            }
        }

        App.showNotification({
            type: 'success',
            message: `${this.state.selectedItems.length} items published`
        });

        this.setState({ selectedItems: [] });
        await this.loadCurrentViewData();
    }

    async bulkArchive() {
        for (const itemId of this.state.selectedItems) {
            try {
                await API.post(`/analytics/${this.state.currentView}/${itemId}/archive`);
            } catch (e) {
                console.error(`Failed to archive item ${itemId}:`, e);
            }
        }

        App.showNotification({
            type: 'success',
            message: `${this.state.selectedItems.length} items archived`
        });

        this.setState({ selectedItems: [] });
        await this.loadCurrentViewData();
    }

    async bulkExport() {
        try {
            const response = await API.post('/analytics/export/bulk', {
                items: this.state.selectedItems,
                type: this.state.currentView
            });

            App.showNotification({
                type: 'success',
                message: 'Bulk export initiated'
            });

            this.setState({ selectedItems: [] });
        } catch (error) {
            console.error('Bulk export failed:', error);
            App.showNotification({
                type: 'error',
                message: 'Bulk export failed'
            });
        }
    }

    showCreateModal(type, item = null) {
        this.setState({
            showCreateModal: true,
            modalType: type,
            editingItem: item
        });
    }

    hideCreateModal() {
        this.setState({
            showCreateModal: false,
            modalType: '',
            editingItem: null
        });
    }

    async saveItem(itemData) {
        try {
            const endpoint = this.state.editingItem
                ? `/analytics/${this.state.modalType}/${this.state.editingItem.id}`
                : `/analytics/${this.state.modalType}`;

            const method = this.state.editingItem ? 'PUT' : 'POST';
            const response = await API.request(method, endpoint, itemData);

            App.showNotification({
                type: 'success',
                message: `${this.state.modalType} ${this.state.editingItem ? 'updated' : 'created'} successfully`
            });

            this.hideCreateModal();
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error saving item:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save item'
            });
        }
    }

    showFilterModal() {
        this.setState({ showFilterModal: true });
    }

    hideFilterModal() {
        this.setState({ showFilterModal: false });
    }

    applyFilters() {
        this.hideFilterModal();
        this.loadCurrentViewData();
    }

    async createDashboard(dashboardData) {
        try {
            await API.post('/analytics/dashboards', dashboardData);

            App.showNotification({
                type: 'success',
                message: 'Dashboard created successfully'
            });

            await this.loadDashboards();
        } catch (error) {
            console.error('Error creating dashboard:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create dashboard'
            });
        }
    }

    async createVisualization(visualizationData) {
        try {
            await API.post('/analytics/visualizations', visualizationData);

            App.showNotification({
                type: 'success',
                message: 'Visualization created successfully'
            });

            await this.loadVisualizations();
        } catch (error) {
            console.error('Error creating visualization:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create visualization'
            });
        }
    }

    async createReport(reportData) {
        try {
            await API.post('/analytics/reports', reportData);

            App.showNotification({
                type: 'success',
                message: 'Report created successfully'
            });

            await this.loadReports();
        } catch (error) {
            console.error('Error creating report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create report'
            });
        }
    }

    async runPredictiveModel(modelData) {
        try {
            const response = await API.post('/analytics/run-predictive-model', modelData);

            App.showNotification({
                type: 'success',
                message: 'Prediction completed successfully'
            });

            return response;
        } catch (error) {
            console.error('Error running predictive model:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to run predictive model'
            });
            throw error;
        }
    }

    async executeReport(reportId, parameters = {}) {
        try {
            const response = await API.post(`/analytics/reports/${reportId}/execute`, {
                parameters
            });

            App.showNotification({
                type: 'success',
                message: 'Report executed successfully'
            });

            return response;
        } catch (error) {
            console.error('Error executing report:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to execute report'
            });
            throw error;
        }
    }

    async exportData(exportData) {
        try {
            const response = await API.post('/analytics/export', exportData);

            App.showNotification({
                type: 'success',
                message: 'Data export initiated'
            });

            return response;
        } catch (error) {
            console.error('Error exporting data:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to export data'
            });
            throw error;
        }
    }

    async generateInsights(insightData) {
        try {
            const response = await API.post('/analytics/generate-insights', insightData);

            App.showNotification({
                type: 'success',
                message: 'Insights generated successfully'
            });

            return response;
        } catch (error) {
            console.error('Error generating insights:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to generate insights'
            });
            throw error;
        }
    }

    startLiveUpdates() {
        this.liveDataInterval = setInterval(async () => {
            try {
                if (this.state.currentView === 'realtime') {
                    await this.loadRealTimeMetrics();
                }
            } catch (error) {
                console.error('Error updating live data:', error);
            }
        }, 5000); // Update every 5 seconds
    }

    stopLiveUpdates() {
        if (this.liveDataInterval) {
            clearInterval(this.liveDataInterval);
            this.liveDataInterval = null;
        }
    }

    renderChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        // Destroy existing chart if it exists
        this.destroyChart(canvasId);

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, config);

        // Store chart instance
        this.state.chartInstances.set(canvasId, chart);

        return chart;
    }

    updateChart(canvasId, newData) {
        const chart = this.state.chartInstances.get(canvasId);
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    }

    destroyChart(canvasId) {
        const chart = this.state.chartInstances.get(canvasId);
        if (chart) {
            chart.destroy();
            this.state.chartInstances.delete(canvasId);
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'analytics-container' });

        // Header
        const header = DOM.create('div', { className: 'analytics-header' });
        const titleElement = DOM.create('h1', { className: 'analytics-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'analytics-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showCreateModal) {
            container.appendChild(this.renderCreateModal());
        }

        if (this.state.showFilterModal) {
            container.appendChild(this.renderFilterModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'dashboards', label: 'Dashboards', icon: 'fas fa-th-large' },
            { id: 'visualizations', label: 'Visualizations', icon: 'fas fa-chart-bar' },
            { id: 'predictive', label: 'Predictive', icon: 'fas fa-brain' },
            { id: 'reports', label: 'Reports', icon: 'fas fa-file-alt' },
            { id: 'realtime', label: 'Real-time', icon: 'fas fa-bolt' }
        ];

        const nav = DOM.create('nav', { className: 'analytics-nav' });
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
            DOM.create('p', {}, 'Loading analytics data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'dashboards':
                return this.renderDashboards();
            case 'visualizations':
                return this.renderVisualizations();
            case 'predictive':
                return this.renderPredictive();
            case 'reports':
                return this.renderReports();
            case 'realtime':
                return this.renderRealTime();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'analytics-dashboard' });

        // Key metrics overview
        const keyMetrics = this.renderKeyMetrics();
        dashboard.appendChild(keyMetrics);

        // Data insights
        const dataInsights = this.renderDataInsights();
        dashboard.appendChild(dataInsights);

        // Predictive analytics alerts
        const predictiveAlerts = this.renderPredictiveAlerts();
        dashboard.appendChild(predictiveAlerts);

        // Recent activity
        const recentActivity = this.renderRecentActivity();
        dashboard.appendChild(recentActivity);

        return dashboard;
    }

    renderKeyMetrics() {
        const metrics = this.state.dashboard.keyMetrics || [];
        const metricsDiv = DOM.create('div', { className: 'key-metrics' });
        metricsDiv.appendChild(DOM.create('h3', {}, 'Key Metrics'));

        const metricsGrid = DOM.create('div', { className: 'metrics-grid' });

        metrics.forEach(metric => {
            const metricCard = DOM.create('div', { className: `metric-card ${metric.trend}` });
            metricCard.appendChild(DOM.create('div', { className: 'metric-name' }, metric.name));
            metricCard.appendChild(DOM.create('div', { className: 'metric-value' }, metric.current_value.toString()));
            metricCard.appendChild(DOM.create('div', { className: 'metric-change' },
                `${metric.percentage_change > 0 ? '+' : ''}${metric.percentage_change}%`
            ));
            metricsGrid.appendChild(metricCard);
        });

        metricsDiv.appendChild(metricsGrid);
        return metricsDiv;
    }

    renderDataInsights() {
        const insights = this.state.dashboard.dataInsights || [];
        const insightsDiv = DOM.create('div', { className: 'data-insights' });
        insightsDiv.appendChild(DOM.create('h3', {}, 'Data Insights'));

        if (insights.length === 0) {
            insightsDiv.appendChild(DOM.create('p', { className: 'no-insights' }, 'No recent insights'));
        } else {
            const insightsList = DOM.create('ul', { className: 'insights-list' });
            insights.slice(0, 5).forEach(insight => {
                const listItem = DOM.create('li', { className: 'insight-item' });
                listItem.appendChild(DOM.create('div', { className: 'insight-content' },
                    DOM.create('strong', {}, insight.title),
                    DOM.create('p', {}, insight.description)
                ));
                insightsList.appendChild(listItem);
            });
            insightsDiv.appendChild(insightsList);
        }

        return insightsDiv;
    }

    renderPredictiveAlerts() {
        const predictions = this.state.dashboard.predictiveAnalytics || [];
        const predictionsDiv = DOM.create('div', { className: 'predictive-alerts' });
        predictionsDiv.appendChild(DOM.create('h3', {}, 'Predictive Analytics'));

        if (predictions.length === 0) {
            predictionsDiv.appendChild(DOM.create('p', { className: 'no-predictions' }, 'No active predictions'));
        } else {
            const predictionsList = DOM.create('ul', { className: 'predictions-list' });
            predictions.slice(0, 5).forEach(prediction => {
                const listItem = DOM.create('li', { className: 'prediction-item' });
                listItem.appendChild(DOM.create('div', { className: 'prediction-content' },
                    DOM.create('strong', {}, prediction.prediction_label),
                    DOM.create('span', {}, `Confidence: ${(prediction.confidence_level * 100).toFixed(1)}%`)
                ));
                predictionsList.appendChild(listItem);
            });
            predictionsDiv.appendChild(predictionsList);
        }

        return predictionsDiv;
    }

    renderRecentActivity() {
        const activityDiv = DOM.create('div', { className: 'recent-activity' });
        activityDiv.appendChild(DOM.create('h3', {}, 'Recent Activity'));
        activityDiv.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Activity feed coming soon...'));
        return activityDiv;
    }

    renderDashboards() {
        const dashboardsView = DOM.create('div', { className: 'dashboards-view' });

        // Toolbar
        const toolbar = this.renderToolbar('dashboards');
        dashboardsView.appendChild(toolbar);

        // Filters
        const filters = this.renderFilters();
        dashboardsView.appendChild(filters);

        // Dashboards grid
        const grid = this.renderDashboardsGrid();
        dashboardsView.appendChild(grid);

        return dashboardsView;
    }

    renderToolbar(viewType) {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedItems.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedItems.length} selected`
            ));

            const actions = ['bulk_delete', 'bulk_publish', 'bulk_archive', 'bulk_export'];
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
        const createButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showCreateModal(viewType.slice(0, -1)) // Remove 's' from plural
        });
        createButton.innerHTML = `<i class="fas fa-plus"></i> Create ${viewType.slice(0, -1)}`;
        rightSection.appendChild(createButton);

        const filterButton = DOM.create('button', {
            className: 'btn btn-outline-secondary',
            onclick: () => this.showFilterModal()
        });
        filterButton.innerHTML = '<i class="fas fa-filter"></i> Filters';
        rightSection.appendChild(filterButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search...',
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
        const categories = ['', 'executive', 'operational', 'financial', 'sales', 'marketing', 'hr', 'it', 'custom'];
        categories.forEach(category => {
            categorySelect.appendChild(DOM.create('option', { value: category },
                category === '' ? 'All Categories' : category.charAt(0).toUpperCase() + category.slice(1)
            ));
        });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        categoryGroup.appendChild(categorySelect);
        filters.appendChild(categoryGroup);

        return filters;
    }

    renderDashboardsGrid() {
        const grid = DOM.create('div', { className: 'dashboards-grid' });

        this.state.dashboards.forEach(dashboard => {
            const card = DOM.create('div', { className: 'dashboard-card' });

            // Checkbox
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                className: 'dashboard-checkbox',
                checked: this.state.selectedItems.includes(dashboard.id),
                onchange: (e) => this.handleItemSelect(dashboard.id, e.target.checked)
            });
            card.appendChild(checkbox);

            // Card content
            const cardContent = DOM.create('div', { className: 'card-content' });
            cardContent.appendChild(DOM.create('h4', { className: 'dashboard-name' }, dashboard.name));
            cardContent.appendChild(DOM.create('p', { className: 'dashboard-description' }, dashboard.description));

            // Card meta
            const cardMeta = DOM.create('div', { className: 'card-meta' });
            cardMeta.appendChild(DOM.create('span', { className: 'meta-item' },
                `<i class="fas fa-th-large"></i> ${dashboard.widget_count} widgets`
            ));
            cardMeta.appendChild(DOM.create('span', { className: 'meta-item' },
                `<i class="fas fa-eye"></i> ${dashboard.view_count} views`
            ));
            cardContent.appendChild(cardMeta);

            // Card actions
            const cardActions = DOM.create('div', { className: 'card-actions' });
            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.viewDashboard(dashboard)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            cardActions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.showCreateModal('dashboard', dashboard)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            cardActions.appendChild(editButton);

            cardContent.appendChild(cardActions);
            card.appendChild(cardContent);
            grid.appendChild(card);
        });

        return grid;
    }

    renderVisualizations() {
        const visualizationsView = DOM.create('div', { className: 'visualizations-view' });

        // Toolbar
        const toolbar = this.renderToolbar('visualizations');
        visualizationsView.appendChild(toolbar);

        // Visualization types tabs
        const typeTabs = this.renderVisualizationTypeTabs();
        visualizationsView.appendChild(typeTabs);

        // Visualizations grid
        const grid = this.renderVisualizationsGrid();
        visualizationsView.appendChild(grid);

        return visualizationsView;
    }

    renderVisualizationTypeTabs() {
        const types = ['charts', 'graphs', 'maps', 'custom'];
        const tabs = DOM.create('div', { className: 'visualization-type-tabs' });

        types.forEach(type => {
            const tab = DOM.create('button', {
                className: `tab-button ${this.state.activeVisualizationType === type ? 'active' : ''}`,
                onclick: () => this.setState({ activeVisualizationType: type })
            }, type.charAt(0).toUpperCase() + type.slice(1));
            tabs.appendChild(tab);
        });

        return tabs;
    }

    renderVisualizationsGrid() {
        const activeType = this.state.activeVisualizationType || 'charts';
        const visualizations = this.state.visualizations[activeType] || [];

        const grid = DOM.create('div', { className: 'visualizations-grid' });

        visualizations.forEach(visualization => {
            const card = DOM.create('div', { className: 'visualization-card' });

            // Chart canvas
            const canvas = DOM.create('canvas', {
                id: `chart-${visualization.id}`,
                className: 'visualization-canvas'
            });
            card.appendChild(canvas);

            // Card content
            const cardContent = DOM.create('div', { className: 'card-content' });
            cardContent.appendChild(DOM.create('h4', { className: 'visualization-name' }, visualization.name));
            cardContent.appendChild(DOM.create('p', { className: 'visualization-description' }, visualization.description));

            // Card actions
            const cardActions = DOM.create('div', { className: 'card-actions' });
            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.viewVisualization(visualization)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            cardActions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.showCreateModal('visualization', visualization)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            cardActions.appendChild(editButton);

            cardContent.appendChild(cardActions);
            card.appendChild(cardContent);
            grid.appendChild(card);
        });

        return grid;
    }

    renderPredictive() {
        const predictiveView = DOM.create('div', { className: 'predictive-view' });

        // Toolbar
        const toolbar = this.renderToolbar('predictive');
        predictiveView.appendChild(toolbar);

        // Models list
        const modelsList = this.renderPredictiveModelsList();
        predictiveView.appendChild(modelsList);

        // Model performance
        const performance = this.renderModelPerformance();
        predictiveView.appendChild(performance);

        return predictiveView;
    }

    renderPredictiveModelsList() {
        const models = this.state.predictiveModels.models || [];
        const modelsDiv = DOM.create('div', { className: 'predictive-models-list' });
        modelsDiv.appendChild(DOM.create('h3', {}, 'Predictive Models'));

        if (models.length === 0) {
            modelsDiv.appendChild(DOM.create('p', { className: 'no-models' }, 'No predictive models found'));
        } else {
            const modelsGrid = DOM.create('div', { className: 'models-grid' });

            models.forEach(model => {
                const modelCard = DOM.create('div', { className: 'model-card' });
                modelCard.appendChild(DOM.create('h4', { className: 'model-name' }, model.name));
                modelCard.appendChild(DOM.create('p', { className: 'model-description' }, model.description));
                modelCard.appendChild(DOM.create('div', { className: 'model-meta' },
                    DOM.create('span', {}, `Algorithm: ${model.algorithm}`),
                    DOM.create('span', {}, `Accuracy: ${(model.accuracy_score * 100).toFixed(1)}%`)
                ));

                const runButton = DOM.create('button', {
                    className: 'btn btn-primary btn-sm',
                    onclick: () => this.runPredictiveModel({ model_id: model.id })
                });
                runButton.innerHTML = '<i class="fas fa-play"></i> Run Prediction';
                modelCard.appendChild(runButton);

                modelsGrid.appendChild(modelCard);
            });

            modelsDiv.appendChild(modelsGrid);
        }

        return modelsDiv;
    }

    renderModelPerformance() {
        const performance = this.state.predictiveModels.performance || [];
        const performanceDiv = DOM.create('div', { className: 'model-performance' });
        performanceDiv.appendChild(DOM.create('h3', {}, 'Model Performance'));

        if (performance.length === 0) {
            performanceDiv.appendChild(DOM.create('p', { className: 'no-performance' }, 'No performance data available'));
        } else {
            const performanceTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Model', 'Algorithm', 'Accuracy', 'Precision', 'Recall', 'F1 Score', 'Evaluations'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            performance.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, item.model_name));
                row.appendChild(DOM.create('td', {}, item.algorithm));
                row.appendChild(DOM.create('td', {}, `${(item.avg_accuracy * 100).toFixed(1)}%`));
                row.appendChild(DOM.create('td', {}, `${(item.avg_precision * 100).toFixed(1)}%`));
                row.appendChild(DOM.create('td', {}, `${(item.avg_recall * 100).toFixed(1)}%`));
                row.appendChild(DOM.create('td', {}, `${(item.avg_f1_score * 100).toFixed(1)}%`));
                row.appendChild(DOM.create('td', {}, item.evaluation_count.toString()));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            performanceTable.appendChild(table);
            performanceDiv.appendChild(performanceTable);
        }

        return performanceDiv;
    }

    renderReports() {
        const reportsView = DOM.create('div', { className: 'reports-view' });

        // Toolbar
        const toolbar = this.renderToolbar('reports');
        reportsView.appendChild(toolbar);

        // Filters
        const filters = this.renderFilters();
        reportsView.appendChild(filters);

        // Reports table
        const table = this.renderReportsTable();
        reportsView.appendChild(table);

        return reportsView;
    }

    renderReportsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});
        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'name', label: 'Report Name' },
            { key: 'category', label: 'Category' },
            { key: 'type', label: 'Type' },
            { key: 'status', label: 'Status' },
            { key: 'last_run', label: 'Last Run' },
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

        this.state.reports.forEach(report => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedItems.includes(report.id),
                onchange: (e) => this.handleItemSelect(report.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Report Name
            row.appendChild(DOM.create('td', {}, report.name));

            // Category
            row.appendChild(DOM.create('td', {}, report.category));

            // Type
            row.appendChild(DOM.create('td', {}, report.type));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${report.status}`
            }, report.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Last Run
            row.appendChild(DOM.create('td', {}, report.last_run || 'Never'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const executeButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.executeReport(report.id)
            });
            executeButton.innerHTML = '<i class="fas fa-play"></i>';
            actions.appendChild(executeButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showCreateModal('report', report)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const exportButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.exportReport(report)
            });
            exportButton.innerHTML = '<i class="fas fa-download"></i>';
            actions.appendChild(exportButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderRealTime() {
        const realtimeView = DOM.create('div', { className: 'realtime-view' });

        // Live indicator
        const liveIndicator = DOM.create('div', { className: 'live-indicator' });
        liveIndicator.appendChild(DOM.create('span', { className: 'live-dot' }));
        liveIndicator.appendChild(DOM.create('span', {}, 'Live Data'));
        realtimeView.appendChild(liveIndicator);

        // Real-time metrics
        const metrics = this.renderRealTimeMetrics();
        realtimeView.appendChild(metrics);

        // Active alerts
        const alerts = this.renderRealTimeAlerts();
        realtimeView.appendChild(alerts);

        return realtimeView;
    }

    renderRealTimeMetrics() {
        const metrics = this.state.realTimeMetrics || [];
        const metricsDiv = DOM.create('div', { className: 'realtime-metrics' });
        metricsDiv.appendChild(DOM.create('h3', {}, 'Real-time Metrics'));

        const metricsGrid = DOM.create('div', { className: 'metrics-grid' });

        metrics.forEach(metric => {
            const metricCard = DOM.create('div', { className: `metric-card ${metric.trend}` });
            metricCard.appendChild(DOM.create('div', { className: 'metric-name' }, metric.metric_name));
            metricCard.appendChild(DOM.create('div', { className: 'metric-value' }, metric.metric_value.toString()));
            metricCard.appendChild(DOM.create('div', { className: 'metric-time' },
                `${metric.seconds_since_update}s ago`
            ));
            metricsGrid.appendChild(metricCard);
        });

        metricsDiv.appendChild(metricsGrid);
        return metricsDiv;
    }

    renderRealTimeAlerts() {
        const alerts = this.state.alerts || [];
        const alertsDiv = DOM.create('div', { className: 'realtime-alerts' });
        alertsDiv.appendChild(DOM.create('h3', {}, 'Active Alerts'));

        if (alerts.length === 0) {
            alertsDiv.appendChild(DOM.create('p', { className: 'no-alerts' }, 'No active alerts'));
        } else {
            const alertsList = DOM.create('ul', { className: 'alerts-list' });
            alerts.forEach(alert => {
                const listItem = DOM.create('li', { className: `alert-item ${alert.severity}` });
                listItem.appendChild(DOM.create('div', { className: 'alert-content' },
                    DOM.create('strong', {}, alert.alert_message),
                    DOM.create('span', {}, `Active for ${alert.minutes_active} minutes`)
                ));
                alertsList.appendChild(listItem);
            });
            alertsDiv.appendChild(alertsList);
        }

        return alertsDiv;
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderCreateModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content modal-lg' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, `Create ${this.state.modalType}`));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCreateModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(this.renderModalForm());
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const saveButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.saveItem(this.getFormData())
        });
        saveButton.innerHTML = '<i class="fas fa-save"></i> Save';
        footer.appendChild(saveButton);

        const cancelButton = DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideCreateModal()
        });
        cancelButton.innerHTML = 'Cancel';
        footer.appendChild(cancelButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);
        return modal;
    }

    renderModalForm() {
        const form = DOM.create('form', { className: 'modal-form' });

        // Basic fields for all types
        const nameGroup = DOM.create('div', { className: 'form-group' });
        nameGroup.appendChild(DOM.create('label', {}, 'Name:'));
        const nameInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            id: 'modal-name',
            required: true
        });
        if (this.state.editingItem) {
            nameInput.value = this.state.editingItem.name || '';
        }
        nameGroup.appendChild(nameInput);
        form.appendChild(nameGroup);

        const descriptionGroup = DOM.create('div', { className: 'form-group' });
        descriptionGroup.appendChild(DOM.create('label', {}, 'Description:'));
        const descriptionTextarea = DOM.create('textarea', {
            className: 'form-control',
            id: 'modal-description',
            rows: 3
        });
        if (this.state.editingItem) {
            descriptionTextarea.value = this.state.editingItem.description || '';
        }
        descriptionGroup.appendChild(descriptionTextarea);
        form.appendChild(descriptionGroup);

        // Type-specific fields
        switch (this.state.modalType) {
            case 'dashboard':
                form.appendChild(this.renderDashboardForm());
                break;
            case 'visualization':
                form.appendChild(this.renderVisualizationForm());
                break;
            case 'report':
                form.appendChild(this.renderReportForm());
                break;
        }

        return form;
    }

    renderDashboardForm() {
        const form = DOM.create('div', { className: 'type-specific-form' });

        // Category
        const categoryGroup = DOM.create('div', { className: 'form-group' });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        const categorySelect = DOM.create('select', {
            className: 'form-control',
            id: 'modal-category'
        });
        const categories = ['executive', 'operational', 'financial', 'sales', 'marketing', 'hr', 'it', 'custom'];
        categories.forEach(category => {
            categorySelect.appendChild(DOM.create('option', { value: category },
                category.charAt(0).toUpperCase() + category.slice(1)
            ));
        });
        if (this.state.editingItem) {
            categorySelect.value = this.state.editingItem.category || '';
        }
        categoryGroup.appendChild(categorySelect);
        form.appendChild(categoryGroup);

        // Type
        const typeGroup = DOM.create('div', { className: 'form-group' });
        typeGroup.appendChild(DOM.create('label', {}, 'Type:'));
        const typeSelect = DOM.create('select', {
            className: 'form-control',
            id: 'modal-type'
        });
        const types = ['realtime', 'historical', 'predictive', 'comparative', 'kpi', 'operational'];
        types.forEach(type => {
            typeSelect.appendChild(DOM.create('option', { value: type },
                type.charAt(0).toUpperCase() + type.slice(1)
            ));
        });
        if (this.state.editingItem) {
            typeSelect.value = this.state.editingItem.type || '';
        }
        typeGroup.appendChild(typeSelect);
        form.appendChild(typeGroup);

        return form;
    }

    renderVisualizationForm() {
        const form = DOM.create('div', { className: 'type-specific-form' });

        // Type
        const typeGroup = DOM.create('div', { className: 'form-group' });
        typeGroup.appendChild(DOM.create('label', {}, 'Visualization Type:'));
        const typeSelect = DOM.create('select', {
            className: 'form-control',
            id: 'modal-viz-type'
        });
        const types = ['bar', 'line', 'pie', 'scatter', 'area', 'heatmap'];
        types.forEach(type => {
            typeSelect.appendChild(DOM.create('option', { value: type },
                type.charAt(0).toUpperCase() + type.slice(1)
            ));
        });
        if (this.state.editingItem) {
            typeSelect.value = this.state.editingItem.type || '';
        }
        typeGroup.appendChild(typeSelect);
        form.appendChild(typeGroup);

        // Data Source
        const dataSourceGroup = DOM.create('div', { className: 'form-group' });
        dataSourceGroup.appendChild(DOM.create('label', {}, 'Data Source:'));
        const dataSourceInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            id: 'modal-data-source',
            placeholder: 'e.g., sales_orders, user_activity'
        });
        if (this.state.editingItem) {
            dataSourceInput.value = this.state.editingItem.data_source || '';
        }
        dataSourceGroup.appendChild(dataSourceInput);
        form.appendChild(dataSourceGroup);

        return form;
    }

    renderReportForm() {
        const form = DOM.create('div', { className: 'type-specific-form' });

        // Category
        const categoryGroup = DOM.create('div', { className: 'form-group' });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        const categorySelect = DOM.create('select', {
            className: 'form-control',
            id: 'modal-report-category'
        });
        const categories = ['financial', 'operational', 'sales', 'marketing', 'hr', 'inventory', 'custom'];
        categories.forEach(category => {
            categorySelect.appendChild(DOM.create('option', { value: category },
                category.charAt(0).toUpperCase() + category.slice(1)
            ));
        });
        if (this.state.editingItem) {
            categorySelect.value = this.state.editingItem.category || '';
        }
        categoryGroup.appendChild(categorySelect);
        form.appendChild(categoryGroup);

        // Type
        const typeGroup = DOM.create('div', { className: 'form-group' });
        typeGroup.appendChild(DOM.create('label', {}, 'Report Type:'));
        const typeSelect = DOM.create('select', {
            className: 'form-control',
            id: 'modal-report-type'
        });
        const types = ['summary', 'detailed', 'comparative', 'trend', 'forecast', 'dashboard'];
        types.forEach(type => {
            typeSelect.appendChild(DOM.create('option', { value: type },
                type.charAt(0).toUpperCase() + type.slice(1)
            ));
        });
        if (this.state.editingItem) {
            typeSelect.value = this.state.editingItem.type || '';
        }
        typeGroup.appendChild(typeSelect);
        form.appendChild(typeGroup);

        // Query
        const queryGroup = DOM.create('div', { className: 'form-group' });
        queryGroup.appendChild(DOM.create('label', {}, 'SQL Query:'));
        const queryTextarea = DOM.create('textarea', {
            className: 'form-control',
            id: 'modal-query',
            rows: 5,
            placeholder: 'SELECT * FROM table_name WHERE...'
        });
        if (this.state.editingItem) {
            queryTextarea.value = this.state.editingItem.query || '';
        }
        queryGroup.appendChild(queryTextarea);
        form.appendChild(queryGroup);

        return form;
    }

    renderFilterModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Advanced Filters'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideFilterModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(this.renderAdvancedFilters());
        modalContent.appendChild(body);

        const footer = DOM.create('div', { className: 'modal-footer' });
        const applyButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.applyFilters()
        });
        applyButton.innerHTML = '<i class="fas fa-check"></i> Apply Filters';
        footer.appendChild(applyButton);

        const resetButton = DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.resetFilters()
        });
        resetButton.innerHTML = 'Reset';
        footer.appendChild(resetButton);

        modalContent.appendChild(footer);
        modal.appendChild(modalContent);
        return modal;
    }

    renderAdvancedFilters() {
        const filters = DOM.create('div', { className: 'advanced-filters' });

        // Date range
        const dateGroup = DOM.create('div', { className: 'filter-row' });
        dateGroup.appendChild(DOM.create('label', {}, 'Date Range:'));

        const dateFromInput = DOM.create('input', {
            type: 'date',
            className: 'form-control',
            value: this.state.filters.date_from || '',
            onchange: (e) => this.handleFilterChange('date_from', e.target.value)
        });
        dateGroup.appendChild(dateFromInput);

        dateGroup.appendChild(DOM.create('span', { className: 'date-separator' }, 'to'));

        const dateToInput = DOM.create('input', {
            type: 'date',
            className: 'form-control',
            value: this.state.filters.date_to || '',
            onchange: (e) => this.handleFilterChange('date_to', e.target.value)
        });
        dateGroup.appendChild(dateToInput);

        filters.appendChild(dateGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-row' });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status || '',
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'active', 'inactive', 'draft', 'published', 'archived'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        return filters;
    }

    resetFilters() {
        this.setState({
            filters: {
                category: '',
                type: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            }
        });
    }

    getFormData() {
        const formData = {
            name: document.getElementById('modal-name')?.value || '',
            description: document.getElementById('modal-description')?.value || ''
        };

        // Add type-specific data
        switch (this.state.modalType) {
            case 'dashboard':
                formData.category = document.getElementById('modal-category')?.value || '';
                formData.type = document.getElementById('modal-type')?.value || '';
                break;
            case 'visualization':
                formData.type = document.getElementById('modal-viz-type')?.value || '';
                formData.data_source = document.getElementById('modal-data-source')?.value || '';
                break;
            case 'report':
                formData.category = document.getElementById('modal-report-category')?.value || '';
                formData.type = document.getElementById('modal-report-type')?.value || '';
                formData.query = document.getElementById('modal-query')?.value || '';
                break;
        }

        return formData;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    viewDashboard(dashboard) {
        // Implementation for viewing dashboard details
        App.showNotification({
            type: 'info',
            message: 'Dashboard viewer coming soon'
        });
    }

    viewVisualization(visualization) {
        // Implementation for viewing visualization details
        App.showNotification({
            type: 'info',
            message: 'Visualization viewer coming soon'
        });
    }

    exportReport(report) {
        // Implementation for exporting report
        App.showNotification({
            type: 'info',
            message: 'Report export coming soon'
        });
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    formatNumber(number, decimals = 2) {
        return Number(number).toFixed(decimals);
    }

    formatPercentage(value) {
        return `${(value * 100).toFixed(1)}%`;
    }
}

// Export the component
window.AdvancedAnalytics = AdvancedAnalytics;
