/**
 * TPT Free ERP - Asset Management Component (Refactored)
 * Complete asset tracking, maintenance, depreciation, and lifecycle management interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class AssetManagement extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderers for different data types
        this.assetsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.maintenanceScheduleTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.maintenanceHistoryTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.depreciationScheduleTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.complianceRequirementsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.insurancePoliciesTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.assetsTableRenderer.setDataCallback(() => this.state.assets || []);
        this.assetsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedAssets: selectedIds });
        });
        this.assetsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.assetsTableRenderer.setDataChangeCallback(() => {
            this.loadAssets();
        });

        this.maintenanceScheduleTableRenderer.setDataCallback(() => this.state.maintenanceSchedule || []);
        this.maintenanceScheduleTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedWorkOrders: selectedIds });
        });
        this.maintenanceScheduleTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.maintenanceScheduleTableRenderer.setDataChangeCallback(() => {
            this.loadMaintenanceSchedule();
        });

        this.maintenanceHistoryTableRenderer.setDataCallback(() => this.state.maintenanceHistory || []);
        this.maintenanceHistoryTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedWorkOrders: selectedIds });
        });
        this.maintenanceHistoryTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.maintenanceHistoryTableRenderer.setDataChangeCallback(() => {
            this.loadMaintenanceHistory();
        });

        this.depreciationScheduleTableRenderer.setDataCallback(() => this.state.depreciationSchedule || []);
        this.depreciationScheduleTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedAssets: selectedIds });
        });
        this.depreciationScheduleTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.depreciationScheduleTableRenderer.setDataChangeCallback(() => {
            this.loadDepreciationSchedule();
        });

        this.complianceRequirementsTableRenderer.setDataCallback(() => this.state.complianceRequirements || []);
        this.complianceRequirementsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedAssets: selectedIds });
        });
        this.complianceRequirementsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.complianceRequirementsTableRenderer.setDataChangeCallback(() => {
            this.loadComplianceRequirements();
        });

        this.insurancePoliciesTableRenderer.setDataCallback(() => this.state.insurancePolicies || []);
        this.insurancePoliciesTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedAssets: selectedIds });
        });
        this.insurancePoliciesTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.insurancePoliciesTableRenderer.setDataChangeCallback(() => {
            this.loadInsurancePolicies();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadAssets',
            'loadAssetCategories',
            'loadAssetLocations',
            'loadAssetDepartments',
            'loadMaintenanceSchedule',
            'loadMaintenanceHistory',
            'loadDepreciationSchedule',
            'loadComplianceRequirements',
            'loadInsurancePolicies',
            'loadAssetAnalytics',
            'handleViewChange',
            'handleFilterChange',
            'handleAssetSelect',
            'handleWorkOrderSelect',
            'handleBulkAction',
            'showAssetModal',
            'hideAssetModal',
            'saveAsset',
            'showWorkOrderModal',
            'hideWorkOrderModal',
            'saveWorkOrder',
            'calculateDepreciation',
            'createAssetDisposal',
            'createComplianceAudit',
            'exportAssets'
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

            // Load common data
            await Promise.all([
                this.loadAssetCategories(),
                this.loadAssetLocations(),
                this.loadAssetDepartments()
            ]);
        } catch (error) {
            this.showNotification('Failed to load asset management data', 'error');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'assets':
                await this.loadAssets();
                break;
            case 'maintenance':
                await Promise.all([
                    this.loadMaintenanceSchedule(),
                    this.loadMaintenanceHistory()
                ]);
                break;
            case 'depreciation':
                await this.loadDepreciationSchedule();
                break;
            case 'lifecycle':
                await this.loadLifecycleStages();
                break;
            case 'compliance':
                await Promise.all([
                    this.loadComplianceRequirements(),
                    this.loadInsurancePolicies()
                ]);
                break;
            case 'analytics':
                await this.loadAssetAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await this.apiRequest('/asset-management/overview');
            this.setState({ overview: response });
        } catch (error) {
            this.showNotification('Failed to load asset overview', 'error');
        }
    }

    async loadAssets() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/asset-management/assets?${params}`);
            this.setState({
                assets: response.assets,
                pagination: response.pagination
            });
        } catch (error) {
            this.showNotification('Failed to load assets', 'error');
        }
    }

    async loadAssetCategories() {
        try {
            const response = await this.apiRequest('/asset-management/asset-categories');
            this.setState({ assetCategories: response });
        } catch (error) {
            this.showNotification('Failed to load asset categories', 'error');
        }
    }

    async loadAssetLocations() {
        try {
            const response = await this.apiRequest('/asset-management/asset-locations');
            this.setState({ assetLocations: response });
        } catch (error) {
            this.showNotification('Failed to load asset locations', 'error');
        }
    }

    async loadAssetDepartments() {
        try {
            const response = await this.apiRequest('/asset-management/asset-departments');
            this.setState({ assetDepartments: response });
        } catch (error) {
            this.showNotification('Failed to load asset departments', 'error');
        }
    }

    async loadMaintenanceSchedule() {
        try {
            const response = await this.apiRequest('/asset-management/maintenance-schedule');
            this.setState({ maintenanceSchedule: response });
        } catch (error) {
            this.showNotification('Failed to load maintenance schedule', 'error');
        }
    }

    async loadMaintenanceHistory() {
        try {
            const response = await this.apiRequest('/asset-management/maintenance-history');
            this.setState({ maintenanceHistory: response });
        } catch (error) {
            this.showNotification('Failed to load maintenance history', 'error');
        }
    }

    async loadDepreciationSchedule() {
        try {
            const response = await this.apiRequest('/asset-management/depreciation-schedule');
            this.setState({ depreciationSchedule: response });
        } catch (error) {
            this.showNotification('Failed to load depreciation schedule', 'error');
        }
    }

    async loadLifecycleStages() {
        try {
            const response = await this.apiRequest('/asset-management/lifecycle-stages');
            this.setState({ lifecycleStages: response });
        } catch (error) {
            this.showNotification('Failed to load lifecycle stages', 'error');
        }
    }

    async loadComplianceRequirements() {
        try {
            const response = await this.apiRequest('/asset-management/compliance-requirements');
            this.setState({ complianceRequirements: response });
        } catch (error) {
            this.showNotification('Failed to load compliance requirements', 'error');
        }
    }

    async loadInsurancePolicies() {
        try {
            const response = await this.apiRequest('/asset-management/insurance-policies');
            this.setState({ insurancePolicies: response });
        } catch (error) {
            this.showNotification('Failed to load insurance policies', 'error');
        }
    }

    async loadAssetAnalytics() {
        try {
            const response = await this.apiRequest('/asset-management/analytics');
            this.setState({ assetAnalytics: response });
        } catch (error) {
            this.showNotification('Failed to load asset analytics', 'error');
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
            if (this.state.currentView === 'assets') {
                this.loadAssets();
            }
        });
    }

    handleAssetSelect(assetId, selected) {
        const selectedAssets = selected
            ? [...this.state.selectedAssets, assetId]
            : this.state.selectedAssets.filter(id => id !== assetId);

        this.setState({ selectedAssets });
    }

    handleWorkOrderSelect(workOrderId, selected) {
        const selectedWorkOrders = selected
            ? [...this.state.selectedWorkOrders, workOrderId]
            : this.state.selectedWorkOrders.filter(id => id !== workOrderId);

        this.setState({ selectedWorkOrders });
    }

    async handleBulkAction(action, selectedIds) {
        if (!selectedIds || selectedIds.length === 0) {
            this.showNotification('Please select items first', 'warning');
            return;
        }

        try {
            switch (action) {
                case 'bulk_update':
                    await this.showBulkUpdateModal();
                    break;
                case 'export_assets':
                    await this.exportAssets();
                    break;
                case 'bulk_maintenance':
                    await this.showBulkMaintenanceModal();
                    break;
                case 'bulk_depreciation':
                    await this.showBulkDepreciationModal();
                    break;
            }
        } catch (error) {
            this.showNotification('Bulk action failed', 'error');
        }
    }

    async showBulkUpdateModal() {
        // Implementation for bulk update modal
        this.showNotification('Bulk update modal coming soon', 'info');
    }

    async exportAssets() {
        try {
            const response = await this.apiRequest('/asset-management/export-assets', null, 'blob');
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'assets_export.csv');
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            this.showNotification('Assets exported successfully', 'success');
        } catch (error) {
            this.showNotification('Export failed', 'error');
        }
    }

    async showBulkMaintenanceModal() {
        // Implementation for bulk maintenance modal
        this.showNotification('Bulk maintenance modal coming soon', 'info');
    }

    async showBulkDepreciationModal() {
        // Implementation for bulk depreciation modal
        this.showNotification('Bulk depreciation modal coming soon', 'info');
    }

    showAssetModal(asset = null) {
        this.setState({
            showAssetModal: true,
            editingAsset: asset
        });
    }

    hideAssetModal() {
        this.setState({
            showAssetModal: false,
            editingAsset: null
        });
    }

    async saveAsset(assetData) {
        try {
            const method = this.state.editingAsset ? 'PUT' : 'POST';
            const url = this.state.editingAsset
                ? `/asset-management/assets/${this.state.editingAsset.id}`
                : '/asset-management/assets';

            await this.apiRequest(url, method, assetData);

            this.showNotification(`Asset ${this.state.editingAsset ? 'updated' : 'created'} successfully`, 'success');

            this.hideAssetModal();
            await this.loadAssets();
        } catch (error) {
            this.showNotification(error.message || 'Failed to save asset', 'error');
        }
    }

    showWorkOrderModal(workOrder = null) {
        this.setState({
            showWorkOrderModal: true,
            editingWorkOrder: workOrder
        });
    }

    hideWorkOrderModal() {
        this.setState({
            showWorkOrderModal: false,
            editingWorkOrder: null
        });
    }

    async saveWorkOrder(workOrderData) {
        try {
            const method = this.state.editingWorkOrder ? 'PUT' : 'POST';
            const url = this.state.editingWorkOrder
                ? `/asset-management/maintenance-work-orders/${this.state.editingWorkOrder.id}`
                : '/asset-management/maintenance-work-orders';

            await this.apiRequest(url, method, workOrderData);

            this.showNotification(`Work order ${this.state.editingWorkOrder ? 'updated' : 'created'} successfully`, 'success');

            this.hideWorkOrderModal();
            await this.loadMaintenanceSchedule();
        } catch (error) {
            this.showNotification(error.message || 'Failed to save work order', 'error');
        }
    }

    async calculateDepreciation(assetId) {
        try {
            await this.apiRequest(`/asset-management/depreciation/${assetId}/calculate`, 'POST');

            this.showNotification('Depreciation calculated successfully', 'success');

            await this.loadDepreciationSchedule();
        } catch (error) {
            this.showNotification(error.message || 'Failed to calculate depreciation', 'error');
        }
    }

    async createAssetDisposal(disposalData) {
        try {
            await this.apiRequest('/asset-management/asset-disposal', 'POST', disposalData);

            this.showNotification('Asset disposal recorded successfully', 'success');

            await this.loadAssets();
        } catch (error) {
            this.showNotification(error.message || 'Failed to record asset disposal', 'error');
        }
    }

    async createComplianceAudit(auditData) {
        try {
            await this.apiRequest('/asset-management/compliance-audits', 'POST', auditData);

            this.showNotification('Compliance audit created successfully', 'success');

            await this.loadComplianceRequirements();
        } catch (error) {
            this.showNotification(error.message || 'Failed to create compliance audit', 'error');
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'asset-management-container' });

        // Header
        const header = DOM.create('div', { className: 'asset-management-header' });
        const titleElement = DOM.create('h1', { className: 'asset-management-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'asset-management-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showAssetModal) {
            container.appendChild(this.renderAssetModal());
        }

        if (this.state.showWorkOrderModal) {
            container.appendChild(this.renderWorkOrderModal());
        }

        if (this.state.showDepreciationModal) {
            container.appendChild(this.renderDepreciationModal());
        }

        if (this.state.showDisposalModal) {
            container.appendChild(this.renderDisposalModal());
        }

        if (this.state.showComplianceModal) {
            container.appendChild(this.renderComplianceModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'assets', label: 'Asset Tracking', icon: 'fas fa-boxes' },
            { id: 'maintenance', label: 'Maintenance', icon: 'fas fa-tools' },
            { id: 'depreciation', label: 'Depreciation', icon: 'fas fa-calculator' },
            { id: 'lifecycle', label: 'Lifecycle', icon: 'fas fa-recycle' },
            { id: 'compliance', label: 'Compliance', icon: 'fas fa-shield-alt' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'asset-management-nav' });
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
            DOM.create('p', {}, 'Loading asset management data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'assets':
                return this.renderAssets();
            case 'maintenance':
                return this.renderMaintenance();
            case 'depreciation':
                return this.renderDepreciation();
            case 'lifecycle':
                return this.renderLifecycle();
            case 'compliance':
                return this.renderCompliance();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'asset-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Asset status chart
        const statusChart = this.renderAssetStatusChart();
        dashboard.appendChild(statusChart);

        // Maintenance alerts
        const maintenanceAlerts = this.renderMaintenanceAlerts();
        dashboard.appendChild(maintenanceAlerts);

        // Recent activity
        const recentActivity = this.renderRecentActivity();
        dashboard.appendChild(recentActivity);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview;
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Assets',
                value: overview.total_assets || 0,
                icon: 'fas fa-boxes',
                color: 'primary'
            },
            {
                title: 'Active Assets',
                value: overview.active_assets || 0,
                icon: 'fas fa-check-circle',
                color: 'success'
            },
            {
                title: 'Total Value',
                value: `$${(overview.total_asset_value || 0).toLocaleString()}`,
                icon: 'fas fa-dollar-sign',
                color: 'info'
            },
            {
                title: 'Maintenance Due',
                value: overview.upcoming_maintenance || 0,
                icon: 'fas fa-tools',
                color: 'warning'
            },
            {
                title: 'Expiring Warranties',
                value: overview.expiring_warranties || 0,
                icon: 'fas fa-exclamation-triangle',
                color: 'danger'
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

    renderAssetStatusChart() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Asset Status Distribution'));

        const chartContainer = DOM.create('div', { className: 'chart-container' });
        chartContainer.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Asset status chart coming soon...'));
        section.appendChild(chartContainer);

        return section;
    }

    renderMaintenanceAlerts() {
        const alerts = this.state.overview.maintenance_alerts || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Maintenance Alerts'));

        if (alerts.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No maintenance alerts'));
        } else {
            const alertsList = DOM.create('ul', { className: 'alerts-list' });
            alerts.slice(0, 5).forEach(alert => {
                const listItem = DOM.create('li', { className: 'alert-item' });
                listItem.appendChild(DOM.create('div', { className: 'alert-message' }, alert.message));
                listItem.appendChild(DOM.create('div', { className: 'alert-meta' },
                    `${this.formatTimeAgo(alert.due_date)} • ${alert.severity}`
                ));
                listItem.classList.add(alert.severity.toLowerCase());
                alertsList.appendChild(listItem);
            });
            section.appendChild(alertsList);
        }

        return section;
    }

    renderRecentActivity() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Recent Activity'));
        section.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Recent activity feed coming soon...'));
        return section;
    }

    renderAssets() {
        const assetsView = DOM.create('div', { className: 'assets-view' });

        // Toolbar
        const toolbar = this.renderAssetsToolbar();
        assetsView.appendChild(toolbar);

        // Filters
        const filters = this.renderAssetsFilters();
        assetsView.appendChild(filters);

        // Assets table
        const table = this.renderAssetsTable();
        assetsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        assetsView.appendChild(pagination);

        return assetsView;
    }

    renderAssetsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedAssets.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedAssets.length} selected`
            ));

            const actions = ['bulk_update', 'export_assets', 'bulk_maintenance'];
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
            onclick: () => this.showAssetModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Asset';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderAssetsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search assets...',
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
        const statuses = ['', 'active', 'maintenance', 'retired', 'disposed'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Category filter
        const categoryGroup = DOM.create('div', { className: 'filter-group' });
        const categorySelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.category,
            onchange: (e) => this.handleFilterChange('category', e.target.value)
        });
        categorySelect.appendChild(DOM.create('option', { value: '' }, 'All Categories'));
        this.state.assetCategories.forEach(category => {
            categorySelect.appendChild(DOM.create('option', { value: category.id }, category.category_name));
        });
        categoryGroup.appendChild(DOM.create('label', {}, 'Category:'));
        categoryGroup.appendChild(categorySelect);
        filters.appendChild(categoryGroup);

        return filters;
    }

    renderAssetsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'asset_name', label: 'Asset Name' },
            { key: 'asset_tag', label: 'Asset Tag' },
            { key: 'category', label: 'Category' },
            { key: 'location', label: 'Location' },
            { key: 'status', label: 'Status' },
            { key: 'current_value', label: 'Current Value' },
            { key: 'next_maintenance', label: 'Next Maintenance' },
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

        this.state.assets.forEach(asset => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedAssets.includes(asset.id),
                onchange: (e) => this.handleAssetSelect(asset.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Asset Name
            row.appendChild(DOM.create('td', {}, asset.asset_name));

            // Asset Tag
            row.appendChild(DOM.create('td', {}, asset.asset_tag));

            // Category
            row.appendChild(DOM.create('td', {}, asset.category_name || 'N/A'));

            // Location
            row.appendChild(DOM.create('td', {}, asset.location_name || 'N/A'));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${asset.status}`
            }, asset.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Current Value
            row.appendChild(DOM.create('td', {}, `$${asset.current_value ? asset.current_value.toLocaleString() : '0'}`));

            // Next Maintenance
            const maintenanceCell = DOM.create('td', {});
            if (asset.next_maintenance_date) {
                const daysUntil = asset.days_until_maintenance;
                const maintenanceText = this.formatDate(asset.next_maintenance_date);
                const maintenanceClass = daysUntil < 0 ? 'overdue' : daysUntil <= 30 ? 'due-soon' : 'normal';
                maintenanceCell.appendChild(DOM.create('span', { className: `maintenance-date ${maintenanceClass}` }, maintenanceText));
            } else {
                maintenanceCell.appendChild(DOM.create('span', {}, 'N/A'));
            }
            row.appendChild(maintenanceCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewAsset(asset)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showAssetModal(asset)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const maintenanceButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showWorkOrderModal(null, asset)
            });
            maintenanceButton.innerHTML = '<i class="fas fa-tools"></i>';
            actions.appendChild(maintenanceButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderMaintenance() {
        const maintenanceView = DOM.create('div', { className: 'maintenance-view' });

        // Toolbar
        const toolbar = this.renderMaintenanceToolbar();
        maintenanceView.appendChild(toolbar);

        // Maintenance schedule
        const schedule = this.renderMaintenanceSchedule();
        maintenanceView.appendChild(schedule);

        // Maintenance history
        const history = this.renderMaintenanceHistory();
        maintenanceView.appendChild(history);

        return maintenanceView;
    }

    renderMaintenanceToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showWorkOrderModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Work Order';
        rightSection.appendChild(addButton);

        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderMaintenanceSchedule() {
        const section = DOM.create('div', { className: 'maintenance-section' });
        section.appendChild(DOM.create('h3', {}, 'Maintenance Schedule'));

        if (this.state.maintenanceSchedule.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No scheduled maintenance'));
        } else {
            const scheduleList = DOM.create('div', { className: 'maintenance-schedule-list' });
            this.state.maintenanceSchedule.forEach(item => {
                const scheduleItem = DOM.create('div', { className: 'maintenance-schedule-item' });
                scheduleItem.appendChild(DOM.create('div', { className: 'asset-info' },
                    DOM.create('strong', {}, item.asset_name),
                    DOM.create('span', {}, ` (${item.asset_tag})`)
                ));
                scheduleItem.appendChild(DOM.create('div', { className: 'maintenance-info' },
                    DOM.create('span', {}, item.maintenance_type),
                    DOM.create('span', {}, `Due: ${this.formatDate(item.scheduled_date)}`)
                ));
                scheduleItem.appendChild(DOM.create('div', { className: 'maintenance-priority' },
                    DOM.create('span', { className: `priority-badge ${item.priority}` }, item.priority)
                ));
                scheduleList.appendChild(scheduleItem);
            });
            section.appendChild(scheduleList);
        }

        return section;
    }

    renderMaintenanceHistory() {
        const section = DOM.create('div', { className: 'maintenance-section' });
        section.appendChild(DOM.create('h3', {}, 'Maintenance History'));

        if (this.state.maintenanceHistory.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No maintenance history'));
        } else {
            const historyTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Asset', 'Date', 'Type', 'Technician', 'Cost', 'Downtime'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.maintenanceHistory.slice(0, 10).forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, `${item.asset_name} (${item.asset_tag})`));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.maintenance_date)));
                row.appendChild(DOM.create('td', {}, item.maintenance_type));
                row.appendChild(DOM.create('td', {}, item.technician || 'N/A'));
                row.appendChild(DOM.create('td', {}, item.cost ? `$${item.cost.toLocaleString()}` : 'N/A'));
                row.appendChild(DOM.create('td', {}, item.downtime_hours ? `${item.downtime_hours}h` : 'N/A'));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            historyTable.appendChild(table);
            section.appendChild(historyTable);
        }

        return section;
    }

    renderDepreciation() {
        const depreciationView = DOM.create('div', { className: 'depreciation-view' });

        // Toolbar
        const toolbar = this.renderDepreciationToolbar();
        depreciationView.appendChild(toolbar);

        // Depreciation schedule
        const schedule = this.renderDepreciationSchedule();
        depreciationView.appendChild(schedule);

        return depreciationView;
    }

    renderDepreciationToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const calculateButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showDepreciationModal()
        });
        calculateButton.innerHTML = '<i class="fas fa-calculator"></i> Calculate Depreciation';
        rightSection.appendChild(calculateButton);

        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderDepreciationSchedule() {
        const section = DOM.create('div', { className: 'depreciation-section' });
        section.appendChild(DOM.create('h3', {}, 'Depreciation Schedule'));

        if (this.state.depreciationSchedule.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No depreciation schedule'));
        } else {
            const scheduleTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Asset', 'Method', 'Useful Life', 'Annual Depreciation', 'Current Value', 'Next Depreciation'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.depreciationSchedule.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, `${item.asset_name} (${item.asset_tag})`));
                row.appendChild(DOM.create('td', {}, item.depreciation_method));
                row.appendChild(DOM.create('td', {}, `${item.useful_life_years} years`));
                row.appendChild(DOM.create('td', {}, `$${item.annual_depreciation.toLocaleString()}`));
                row.appendChild(DOM.create('td', {}, `$${item.current_book_value.toLocaleString()}`));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.next_depreciation_date)));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            scheduleTable.appendChild(table);
            section.appendChild(scheduleTable);
        }

        return section;
    }

    renderLifecycle() {
        const lifecycleView = DOM.create('div', { className: 'lifecycle-view' });
        lifecycleView.appendChild(DOM.create('h3', {}, 'Asset Lifecycle Management'));
        lifecycleView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Lifecycle management interface coming soon...'));
        return lifecycleView;
    }

    renderCompliance() {
        const complianceView = DOM.create('div', { className: 'compliance-view' });

        // Compliance requirements
        const requirements = this.renderComplianceRequirements();
        complianceView.appendChild(requirements);

        // Insurance policies
        const insurance = this.renderInsurancePolicies();
        complianceView.appendChild(insurance);

        return complianceView;
    }

    renderComplianceRequirements() {
        const section = DOM.create('div', { className: 'compliance-section' });
        section.appendChild(DOM.create('h3', {}, 'Compliance Requirements'));

        if (this.state.complianceRequirements.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No compliance requirements'));
        } else {
            const requirementsTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Asset', 'Requirement', 'Last Check', 'Next Check', 'Status'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.complianceRequirements.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, `${item.asset_name} (${item.asset_tag})`));
                row.appendChild(DOM.create('td', {}, item.requirement_type));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.last_compliance_check)));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.next_compliance_check)));
                const statusCell = DOM.create('td', {});
                const statusBadge = DOM.create('span', {
                    className: `status-badge ${item.compliance_status}`
                }, item.compliance_status);
                statusCell.appendChild(statusBadge);
                row.appendChild(statusCell);
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            requirementsTable.appendChild(table);
            section.appendChild(requirementsTable);
        }

        return section;
    }

    renderInsurancePolicies() {
        const section = DOM.create('div', { className: 'insurance-section' });
        section.appendChild(DOM.create('h3', {}, 'Insurance Policies'));

        if (this.state.insurancePolicies.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No insurance policies'));
        } else {
            const policiesTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Asset', 'Policy Number', 'Provider', 'Coverage', 'Expiry Date'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.insurancePolicies.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, `${item.asset_name} (${item.asset_tag})`));
                row.appendChild(DOM.create('td', {}, item.policy_number));
                row.appendChild(DOM.create('td', {}, item.insurance_provider));
                row.appendChild(DOM.create('td', {}, `$${item.coverage_amount.toLocaleString()}`));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.expiry_date)));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            policiesTable.appendChild(table);
            section.appendChild(policiesTable);
        }

        return section;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'Asset Analytics'));
        analyticsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Asset analytics interface coming soon...'));
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
            if (this.state.currentView === 'assets') {
                this.loadAssets();
            }
        });
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderAssetModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingAsset ? 'Edit Asset' : 'Add Asset'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideAssetModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Asset modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderWorkOrderModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingWorkOrder ? 'Edit Work Order' : 'Create Work Order'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideWorkOrderModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Work order modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderDepreciationModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Calculate Depreciation'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDepreciationModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Depreciation calculation modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderDisposalModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Asset Disposal'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDisposalModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Asset disposal modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderComplianceModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Compliance Audit'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideComplianceModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Compliance audit modal coming soon...'));
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

    hideDepreciationModal() {
        this.setState({ showDepreciationModal: false });
    }

    hideDisposalModal() {
        this.setState({ showDisposalModal: false });
    }

    hideComplianceModal() {
        this.setState({ showComplianceModal: false });
    }

    viewAsset(asset) {
        // Implementation for viewing asset details
        this.showNotification('Asset details view coming soon', 'info');
    }
}

// Export the component
window.AssetManagement = AssetManagement;
