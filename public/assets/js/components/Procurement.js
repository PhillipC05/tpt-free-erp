/**
 * TPT Free ERP - Procurement Component (Refactored)
 * Complete vendor management, purchase orders, and supplier evaluation interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class Procurement extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderers for different data types
        this.vendorsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.purchaseOrdersTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        this.requisitionsTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.vendorsTableRenderer.setDataCallback(() => this.state.vendors || []);
        this.vendorsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedVendors: selectedIds });
        });
        this.vendorsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.vendorsTableRenderer.setDataChangeCallback(() => {
            this.loadVendors();
        });

        this.purchaseOrdersTableRenderer.setDataCallback(() => this.state.purchaseOrders || []);
        this.purchaseOrdersTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedOrders: selectedIds });
        });
        this.purchaseOrdersTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.purchaseOrdersTableRenderer.setDataChangeCallback(() => {
            this.loadPurchaseOrders();
        });

        this.requisitionsTableRenderer.setDataCallback(() => this.state.requisitions || []);
        this.requisitionsTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedRequisitions: selectedIds });
        });
        this.requisitionsTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.requisitionsTableRenderer.setDataChangeCallback(() => {
            this.loadRequisitions();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadVendors',
            'loadPurchaseOrders',
            'loadRequisitions',
            'loadContracts',
            'loadSpendAnalysis',
            'loadSupplierEvaluations',
            'handleViewChange',
            'handleFilterChange',
            'handleVendorSelect',
            'handleOrderSelect',
            'handleRequisitionSelect',
            'handleBulkAction',
            'showVendorModal',
            'hideVendorModal',
            'saveVendor',
            'deleteVendor',
            'showOrderModal',
            'hideOrderModal',
            'savePurchaseOrder',
            'updateOrderStatus',
            'showRequisitionModal',
            'hideRequisitionModal',
            'saveRequisition',
            'approveRequisition',
            'showContractModal',
            'hideContractModal',
            'saveContract',
            'showEvaluationModal',
            'hideEvaluationModal',
            'saveSupplierEvaluation'
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
                this.loadVendors()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading procurement data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load procurement data'
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
            case 'vendors':
                await this.loadVendors();
                break;
            case 'purchase-orders':
                await this.loadPurchaseOrders();
                break;
            case 'requisitions':
                await this.loadRequisitions();
                break;
            case 'contracts':
                await this.loadContracts();
                break;
            case 'spend-analysis':
                await this.loadSpendAnalysis();
                break;
            case 'supplier-evaluations':
                await this.loadSupplierEvaluations();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await this.apiRequest('/procurement/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading procurement overview:', error);
            this.showNotification('Failed to load procurement overview', 'error');
        }
    }

    async loadVendors() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/procurement/vendors?${params}`);
            this.setState({
                vendors: response.vendors,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading vendors:', error);
            this.showNotification('Failed to load vendors', 'error');
        }
    }

    async loadPurchaseOrders() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await this.apiRequest(`/procurement/purchase-orders?${params}`);
            this.setState({
                purchaseOrders: response.purchase_orders,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading purchase orders:', error);
            this.showNotification('Failed to load purchase orders', 'error');
        }
    }

    async loadRequisitions() {
        try {
            const response = await this.apiRequest('/procurement/requisitions');
            this.setState({ requisitions: response.requisitions });
        } catch (error) {
            console.error('Error loading requisitions:', error);
            this.showNotification('Failed to load requisitions', 'error');
        }
    }

    async loadContracts() {
        try {
            const response = await this.apiRequest('/procurement/contracts');
            this.setState({ contracts: response.contracts });
        } catch (error) {
            console.error('Error loading contracts:', error);
            this.showNotification('Failed to load contracts', 'error');
        }
    }

    async loadSpendAnalysis() {
        try {
            const response = await this.apiRequest('/procurement/spend-analysis');
            this.setState({ spendAnalysis: response });
        } catch (error) {
            console.error('Error loading spend analysis:', error);
            this.showNotification('Failed to load spend analysis', 'error');
        }
    }

    async loadSupplierEvaluations() {
        try {
            const response = await this.apiRequest('/procurement/supplier-evaluations');
            this.setState({ supplierEvaluations: response.evaluations });
        } catch (error) {
            console.error('Error loading supplier evaluations:', error);
            this.showNotification('Failed to load supplier evaluations', 'error');
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
            if (this.state.currentView === 'vendors') {
                this.loadVendors();
            } else if (this.state.currentView === 'purchase-orders') {
                this.loadPurchaseOrders();
            }
        });
    }

    handleVendorSelect(vendorId, selected) {
        const selectedVendors = selected
            ? [...this.state.selectedVendors, vendorId]
            : this.state.selectedVendors.filter(id => id !== vendorId);

        this.setState({ selectedVendors });
    }

    handleOrderSelect(orderId, selected) {
        const selectedOrders = selected
            ? [...this.state.selectedOrders, orderId]
            : this.state.selectedOrders.filter(id => id !== orderId);

        this.setState({ selectedOrders });
    }

    handleRequisitionSelect(requisitionId, selected) {
        const selectedRequisitions = selected
            ? [...this.state.selectedRequisitions, requisitionId]
            : this.state.selectedRequisitions.filter(id => id !== requisitionId);

        this.setState({ selectedRequisitions });
    }

    async handleBulkAction(action) {
        if (this.state.selectedVendors.length === 0) {
            App.showNotification({
                type: 'warning',
                message: 'Please select vendors first'
            });
            return;
        }

        try {
            switch (action) {
                case 'update_category':
                    await this.showBulkUpdateModal('category');
                    break;
                case 'update_rating':
                    await this.showBulkUpdateModal('rating');
                    break;
                case 'send_notification':
                    await this.sendBulkNotification();
                    break;
                case 'update_status':
                    await this.showBulkUpdateModal('status');
                    break;
                case 'export_vendors':
                    await this.exportVendors();
                    break;
                case 'import_vendors':
                    await this.importVendors();
                    break;
                case 'bulk_evaluation':
                    await this.bulkEvaluation();
                    break;
                case 'update_payment_terms':
                    await this.showBulkUpdateModal('payment_terms');
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

    async sendBulkNotification() {
        // Implementation for bulk notification
        App.showNotification({
            type: 'info',
            message: 'Bulk notification not yet implemented'
        });
    }

    async exportVendors() {
        // Implementation for export
        App.showNotification({
            type: 'info',
            message: 'Export not yet implemented'
        });
    }

    async importVendors() {
        // Implementation for import
        App.showNotification({
            type: 'info',
            message: 'Import not yet implemented'
        });
    }

    async bulkEvaluation() {
        // Implementation for bulk evaluation
        App.showNotification({
            type: 'info',
            message: 'Bulk evaluation not yet implemented'
        });
    }

    showVendorModal(vendor = null) {
        this.setState({
            showVendorModal: true,
            editingVendor: vendor
        });
    }

    hideVendorModal() {
        this.setState({
            showVendorModal: false,
            editingVendor: null
        });
    }

    async saveVendor(vendorData) {
        try {
            if (this.state.editingVendor) {
                await this.apiRequest(`/procurement/vendors/${this.state.editingVendor.id}`, 'PUT', vendorData);
                this.showNotification('Vendor updated successfully', 'success');
            } else {
                await this.apiRequest('/procurement/vendors', 'POST', vendorData);
                this.showNotification('Vendor created successfully', 'success');
            }

            this.hideVendorModal();
            await this.loadVendors();
        } catch (error) {
            console.error('Error saving vendor:', error);
            this.showNotification(error.message || 'Failed to save vendor', 'error');
        }
    }

    async deleteVendor(vendorId) {
        if (!confirm('Are you sure you want to deactivate this vendor?')) {
            return;
        }

        try {
            await this.apiRequest(`/procurement/vendors/${vendorId}`, 'DELETE');
            this.showNotification('Vendor deactivated successfully', 'success');
            await this.loadVendors();
        } catch (error) {
            console.error('Error deactivating vendor:', error);
            this.showNotification(error.message || 'Failed to deactivate vendor', 'error');
        }
    }

    showOrderModal(order = null) {
        this.setState({
            showOrderModal: true,
            editingOrder: order
        });
    }

    hideOrderModal() {
        this.setState({
            showOrderModal: false,
            editingOrder: null
        });
    }

    async savePurchaseOrder(orderData) {
        try {
            await this.apiRequest('/procurement/purchase-orders', 'POST', orderData);
            this.showNotification('Purchase order created successfully', 'success');
            this.hideOrderModal();
            await this.loadPurchaseOrders();
        } catch (error) {
            console.error('Error saving purchase order:', error);
            this.showNotification(error.message || 'Failed to save purchase order', 'error');
        }
    }

    async updateOrderStatus(orderId, status) {
        try {
            await this.apiRequest(`/procurement/purchase-orders/${orderId}/status`, 'PUT', { status });
            this.showNotification('Purchase order status updated successfully', 'success');
            await this.loadPurchaseOrders();
        } catch (error) {
            console.error('Error updating order status:', error);
            this.showNotification(error.message || 'Failed to update order status', 'error');
        }
    }

    showRequisitionModal(requisition = null) {
        this.setState({
            showRequisitionModal: true,
            editingRequisition: requisition
        });
    }

    hideRequisitionModal() {
        this.setState({
            showRequisitionModal: false,
            editingRequisition: null
        });
    }

    async saveRequisition(requisitionData) {
        try {
            await API.post('/procurement/requisitions', requisitionData);
            App.showNotification({
                type: 'success',
                message: 'Requisition created successfully'
            });
            this.hideRequisitionModal();
            await this.loadRequisitions();
        } catch (error) {
            console.error('Error saving requisition:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save requisition'
            });
        }
    }

    async approveRequisition(requisitionId) {
        try {
            await API.put(`/procurement/requisitions/${requisitionId}/approve`);
            App.showNotification({
                type: 'success',
                message: 'Requisition approved successfully'
            });
            await this.loadRequisitions();
        } catch (error) {
            console.error('Error approving requisition:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to approve requisition'
            });
        }
    }

    showContractModal(contract = null) {
        this.setState({
            showContractModal: true,
            editingContract: contract
        });
    }

    hideContractModal() {
        this.setState({
            showContractModal: false,
            editingContract: null
        });
    }

    async saveContract(contractData) {
        try {
            await API.post('/procurement/contracts', contractData);
            App.showNotification({
                type: 'success',
                message: 'Contract created successfully'
            });
            this.hideContractModal();
            await this.loadContracts();
        } catch (error) {
            console.error('Error saving contract:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save contract'
            });
        }
    }

    showEvaluationModal(vendor = null) {
        this.setState({
            showEvaluationModal: true,
            selectedVendor: vendor
        });
    }

    hideEvaluationModal() {
        this.setState({
            showEvaluationModal: false,
            selectedVendor: null
        });
    }

    async saveSupplierEvaluation(evaluationData) {
        try {
            await API.post('/procurement/supplier-evaluations', evaluationData);
            App.showNotification({
                type: 'success',
                message: 'Supplier evaluation completed successfully'
            });
            this.hideEvaluationModal();
            await this.loadSupplierEvaluations();
        } catch (error) {
            console.error('Error saving supplier evaluation:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save supplier evaluation'
            });
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'procurement-container' });

        // Header
        const header = DOM.create('div', { className: 'procurement-header' });
        const titleElement = DOM.create('h1', { className: 'procurement-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'procurement-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showVendorModal) {
            container.appendChild(this.renderVendorModal());
        }

        if (this.state.showOrderModal) {
            container.appendChild(this.renderOrderModal());
        }

        if (this.state.showRequisitionModal) {
            container.appendChild(this.renderRequisitionModal());
        }

        if (this.state.showContractModal) {
            container.appendChild(this.renderContractModal());
        }

        if (this.state.showEvaluationModal) {
            container.appendChild(this.renderEvaluationModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'vendors', label: 'Vendors', icon: 'fas fa-building' },
            { id: 'purchase-orders', label: 'Purchase Orders', icon: 'fas fa-shopping-cart' },
            { id: 'requisitions', label: 'Requisitions', icon: 'fas fa-file-alt' },
            { id: 'contracts', label: 'Contracts', icon: 'fas fa-file-contract' },
            { id: 'spend-analysis', label: 'Spend Analysis', icon: 'fas fa-chart-bar' },
            { id: 'supplier-evaluations', label: 'Evaluations', icon: 'fas fa-star' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-line' }
        ];

        const nav = DOM.create('nav', { className: 'procurement-nav' });
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
            DOM.create('p', {}, 'Loading procurement data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'vendors':
                return this.renderVendors();
            case 'purchase-orders':
                return this.renderPurchaseOrders();
            case 'requisitions':
                return this.renderRequisitions();
            case 'contracts':
                return this.renderContracts();
            case 'spend-analysis':
                return this.renderSpendAnalysis();
            case 'supplier-evaluations':
                return this.renderSupplierEvaluations();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'procurement-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Procurement metrics
        const metricsSection = this.renderProcurementMetrics();
        dashboard.appendChild(metricsSection);

        // Recent activities
        const activitiesSection = this.renderRecentActivities();
        dashboard.appendChild(activitiesSection);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.procurement_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Vendors',
                value: overview.total_vendors || 0,
                icon: 'fas fa-building',
                color: 'primary'
            },
            {
                title: 'Active Orders',
                value: overview.total_purchase_orders || 0,
                icon: 'fas fa-shopping-cart',
                color: 'success'
            },
            {
                title: 'Total Spend',
                value: '$' + (overview.total_spend || 0).toLocaleString(),
                icon: 'fas fa-dollar-sign',
                color: 'info'
            },
            {
                title: 'Pending Approvals',
                value: overview.pending_approvals || 0,
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

    renderProcurementMetrics() {
        const metricsSection = DOM.create('div', { className: 'dashboard-section' });
        metricsSection.appendChild(DOM.create('h3', {}, 'Procurement Metrics'));

        const metrics = DOM.create('div', { className: 'procurement-metrics' });

        const metricData = [
            { label: 'Order Completion Rate', value: '95%', color: 'success' },
            { label: 'Average Lead Time', value: '7 days', color: 'info' },
            { label: 'Cost Savings', value: '$25K', color: 'success' },
            { label: 'Vendor Performance', value: '4.2/5', color: 'warning' }
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

    renderVendors() {
        const vendorsView = DOM.create('div', { className: 'vendors-view' });

        // Toolbar
        const toolbar = this.renderVendorsToolbar();
        vendorsView.appendChild(toolbar);

        // Filters
        const filters = this.renderVendorsFilters();
        vendorsView.appendChild(filters);

        // Vendors table
        const table = this.renderVendorsTable();
        vendorsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        vendorsView.appendChild(pagination);

        return vendorsView;
    }

    renderVendorsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedVendors.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedVendors.length} selected`
            ));

            const actions = ['update_category', 'update_rating', 'send_notification', 'update_status', 'export_vendors', 'bulk_evaluation', 'update_payment_terms'];
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
            onclick: () => this.showVendorModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Vendor';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderVendorsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search vendors...',
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
        const categories = ['raw_materials', 'finished_goods', 'services', 'equipment', 'software', 'consulting', 'maintenance', 'utilities', 'transportation', 'other'];
        categories.forEach(cat => {
            categorySelect.appendChild(DOM.create('option', { value: cat }, cat.replace('_', ' ')));
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
        const statuses = ['', 'active', 'inactive'];
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

    renderVendorsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'vendor_name', label: 'Vendor Name' },
            { key: 'category', label: 'Category' },
            { key: 'contact_person', label: 'Contact' },
            { key: 'email', label: 'Email' },
            { key: 'rating', label: 'Rating' },
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

        this.state.vendors.forEach(vendor => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedVendors.includes(vendor.id),
                onchange: (e) => this.handleVendorSelect(vendor.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Vendor Name
            row.appendChild(DOM.create('td', {}, vendor.vendor_name));

            // Category
            row.appendChild(DOM.create('td', {}, vendor.category ? vendor.category.replace('_', ' ') : 'N/A'));

            // Contact
            row.appendChild(DOM.create('td', {}, vendor.contact_person || 'N/A'));

            // Email
            row.appendChild(DOM.create('td', {}, vendor.email));

            // Rating
            const ratingCell = DOM.create('td', {});
            const ratingBadge = DOM.create('span', {
                className: `rating-badge rating-${Math.floor(vendor.rating || 0)}`
            }, `${vendor.rating || 0}/5`);
            ratingCell.appendChild(ratingBadge);
            row.appendChild(ratingCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${vendor.status}`
            }, vendor.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewVendorDetails(vendor)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showVendorModal(vendor)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const evaluationButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showEvaluationModal(vendor)
            });
            evaluationButton.innerHTML = '<i class="fas fa-star"></i>';
            actions.appendChild(evaluationButton);

            const deleteButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-danger',
                onclick: () => this.deleteVendor(vendor.id)
            });
            deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
            actions.appendChild(deleteButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderPurchaseOrders() {
        const ordersView = DOM.create('div', { className: 'purchase-orders-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showOrderModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Order';
        toolbar.appendChild(addButton);
        ordersView.appendChild(toolbar);

        // Orders table
        const table = this.renderOrdersTable();
        ordersView.appendChild(table);

        return ordersView;
    }

    renderOrdersTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'order_number', label: 'Order Number' },
            { key: 'vendor_name', label: 'Vendor' },
            { key: 'order_date', label: 'Order Date' },
            { key: 'total_amount', label: 'Total Amount' },
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

        this.state.purchaseOrders.forEach(order => {
            const row = DOM.create('tr', {});

            // Order Number
            row.appendChild(DOM.create('td', {}, order.order_number));

            // Vendor
            row.appendChild(DOM.create('td', {}, order.vendor_name));

            // Order Date
            row.appendChild(DOM.create('td', {}, this.formatDate(order.order_date)));

            // Total Amount
            row.appendChild(DOM.create('td', {}, '$' + (order.total_amount || 0).toLocaleString()));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${order.status}`
            }, order.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewOrderDetails(order)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showOrderModal(order)
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

    renderRequisitions() {
        const requisitionsView = DOM.create('div', { className: 'requisitions-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showRequisitionModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Requisition';
        toolbar.appendChild(addButton);
        requisitionsView.appendChild(toolbar);

        // Requisitions table
        const table = this.renderRequisitionsTable();
        requisitionsView.appendChild(table);

        return requisitionsView;
    }

    renderRequisitionsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'requisition_number', label: 'Requisition Number' },
            { key: 'department_name', label: 'Department' },
            { key: 'total_amount', label: 'Total Amount' },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Created' },
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

        this.state.requisitions.forEach(requisition => {
            const row = DOM.create('tr', {});

            // Requisition Number
            row.appendChild(DOM.create('td', {}, requisition.requisition_number));

            // Department
            row.appendChild(DOM.create('td', {}, requisition.department_name || 'N/A'));

            // Total Amount
            row.appendChild(DOM.create('td', {}, '$' + (requisition.total_amount || 0).toLocaleString()));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${requisition.status}`
            }, requisition.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Created
            row.appendChild(DOM.create('td', {}, this.formatDate(requisition.created_at)));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            if (requisition.status === 'pending_approval') {
                const approveButton = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-success',
                    onclick: () => this.approveRequisition(requisition.id)
                });
                approveButton.innerHTML = '<i class="fas fa-check"></i>';
                actions.appendChild(approveButton);
            }

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewRequisitionDetails(requisition)
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

    renderContracts() {
        const contractsView = DOM.create('div', { className: 'contracts-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showContractModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Contract';
        toolbar.appendChild(addButton);
        contractsView.appendChild(toolbar);

        // Contracts table
        const table = this.renderContractsTable();
        contractsView.appendChild(table);

        return contractsView;
    }

    renderContractsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'contract_number', label: 'Contract Number' },
            { key: 'vendor_name', label: 'Vendor' },
            { key: 'contract_type', label: 'Type' },
            { key: 'start_date', label: 'Start Date' },
            { key: 'end_date', label: 'End Date' },
            { key: 'total_value', label: 'Total Value' },
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

        this.state.contracts.forEach(contract => {
            const row = DOM.create('tr', {});

            // Contract Number
            row.appendChild(DOM.create('td', {}, contract.contract_number));

            // Vendor
            row.appendChild(DOM.create('td', {}, contract.vendor_name));

            // Type
            row.appendChild(DOM.create('td', {}, contract.contract_type));

            // Start Date
            row.appendChild(DOM.create('td', {}, this.formatDate(contract.start_date)));

            // End Date
            row.appendChild(DOM.create('td', {}, this.formatDate(contract.end_date)));

            // Total Value
            row.appendChild(DOM.create('td', {}, '$' + (contract.total_value || 0).toLocaleString()));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${contract.status}`
            }, contract.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewContractDetails(contract)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showContractModal(contract)
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

    renderSpendAnalysis() {
        const spendView = DOM.create('div', { className: 'spend-analysis-view' });

        // Spend overview
        const overviewSection = DOM.create('div', { className: 'spend-section' });
        overviewSection.appendChild(DOM.create('h3', {}, 'Spend Analysis Overview'));

        const spendData = this.state.spendAnalysis.spend_by_category || [];
        const spendChart = DOM.create('div', { className: 'spend-chart' });
        spendChart.appendChild(DOM.create('p', {}, 'Spend by Category Chart will be rendered here'));
        overviewSection.appendChild(spendChart);

        spendView.appendChild(overviewSection);

        // Top vendors
        const vendorsSection = DOM.create('div', { className: 'spend-section' });
        vendorsSection.appendChild(DOM.create('h3', {}, 'Top Vendors by Spend'));

        const topVendors = this.state.spendAnalysis.top_vendors || [];
        if (topVendors.length > 0) {
            const vendorsList = DOM.create('ul', { className: 'vendors-list' });
            topVendors.slice(0, 5).forEach(vendor => {
                const vendorItem = DOM.create('li', { className: 'vendor-item' });
                vendorItem.appendChild(DOM.create('span', { className: 'vendor-name' }, vendor.vendor_name));
                vendorItem.appendChild(DOM.create('span', { className: 'vendor-spend' }, '$' + vendor.total_spend.toLocaleString()));
                vendorsList.appendChild(vendorItem);
            });
            vendorsSection.appendChild(vendorsList);
        }

        spendView.appendChild(vendorsSection);

        return spendView;
    }

    renderSupplierEvaluations() {
        const evaluationsView = DOM.create('div', { className: 'supplier-evaluations-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showEvaluationModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> New Evaluation';
        toolbar.appendChild(addButton);
        evaluationsView.appendChild(toolbar);

        // Evaluations table
        const table = this.renderEvaluationsTable();
        evaluationsView.appendChild(table);

        return evaluationsView;
    }

    renderEvaluationsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'vendor_name', label: 'Vendor' },
            { key: 'evaluation_date', label: 'Evaluation Date' },
            { key: 'overall_rating', label: 'Overall Rating' },
            { key: 'quality_rating', label: 'Quality' },
            { key: 'delivery_rating', label: 'Delivery' },
            { key: 'service_rating', label: 'Service' },
            { key: 'evaluator_name', label: 'Evaluator' },
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

        this.state.supplierEvaluations.forEach(evaluation => {
            const row = DOM.create('tr', {});

            // Vendor
            row.appendChild(DOM.create('td', {}, evaluation.vendor_name));

            // Evaluation Date
            row.appendChild(DOM.create('td', {}, this.formatDate(evaluation.evaluation_date)));

            // Overall Rating
            const overallCell = DOM.create('td', {});
            const overallBadge = DOM.create('span', {
                className: `rating-badge rating-${Math.floor(evaluation.overall_rating || 0)}`
            }, `${evaluation.overall_rating || 0}/5`);
            overallCell.appendChild(overallBadge);
            row.appendChild(overallCell);

            // Quality Rating
            row.appendChild(DOM.create('td', {}, `${evaluation.quality_rating || 0}/5`));

            // Delivery Rating
            row.appendChild(DOM.create('td', {}, `${evaluation.delivery_rating || 0}/5`));

            // Service Rating
            row.appendChild(DOM.create('td', {}, `${evaluation.service_rating || 0}/5`));

            // Evaluator
            row.appendChild(DOM.create('td', {}, evaluation.evaluator_name || 'N/A'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewEvaluationDetails(evaluation)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showEvaluationModal(evaluation)
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

        // Analytics overview
        const overviewSection = DOM.create('div', { className: 'analytics-section' });
        overviewSection.appendChild(DOM.create('h3', {}, 'Procurement Analytics Overview'));

        const charts = DOM.create('div', { className: 'analytics-charts' });

        // Procurement trends chart placeholder
        const trendsChart = DOM.create('div', { className: 'chart-placeholder' });
        trendsChart.appendChild(DOM.create('h4', {}, 'Procurement Trends'));
        trendsChart.appendChild(DOM.create('div', { className: 'chart-canvas' }, 'Chart will be rendered here'));
        charts.appendChild(trendsChart);

        // Vendor performance chart placeholder
        const performanceChart = DOM.create('div', { className: 'chart-placeholder' });
        performanceChart.appendChild(DOM.create('h4', {}, 'Vendor Performance'));
        performanceChart.appendChild(DOM.create('div', { className: 'chart-canvas' }, 'Chart will be rendered here'));
        charts.appendChild(performanceChart);

        overviewSection.appendChild(charts);
        analyticsView.appendChild(overviewSection);

        return analyticsView;
    }

    renderPagination() {
        if (this.state.pagination.pages <= 1) {
            return DOM.create('div', {});
        }

        const pagination = DOM.create('div', { className: 'pagination' });

        const prevButton = DOM.create('button', {
            className: 'btn btn-outline-secondary',
            disabled: this.state.pagination.page <= 1,
            onclick: () => this.handlePageChange(this.state.pagination.page - 1)
        }, 'Previous');
        pagination.appendChild(prevButton);

        const pageInfo = DOM.create('span', { className: 'page-info' },
            `Page ${this.state.pagination.page} of ${this.state.pagination.pages}`
        );
        pagination.appendChild(pageInfo);

        const nextButton = DOM.create('button', {
            className: 'btn btn-outline-secondary',
            disabled: this.state.pagination.page >= this.state.pagination.pages,
            onclick: () => this.handlePageChange(this.state.pagination.page + 1)
        }, 'Next');
        pagination.appendChild(nextButton);

        return pagination;
    }

    // Modal rendering methods
    renderVendorModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        modalContent.appendChild(DOM.create('div', { className: 'modal-header' },
            DOM.create('h4', {}, this.state.editingVendor ? 'Edit Vendor' : 'Add Vendor'),
            DOM.create('button', {
                className: 'modal-close',
                onclick: () => this.hideVendorModal()
            }, '×')
        ));

        const form = DOM.create('div', { className: 'modal-body' });
        form.appendChild(DOM.create('p', {}, 'Vendor form will be implemented here'));
        modalContent.appendChild(form);

        const footer = DOM.create('div', { className: 'modal-footer' });
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideVendorModal()
        }, 'Cancel'));
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.saveVendor({})
        }, 'Save'));
        modalContent.appendChild(footer);

        modal.appendChild(modalContent);
        return modal;
    }

    renderOrderModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        modalContent.appendChild(DOM.create('div', { className: 'modal-header' },
            DOM.create('h4', {}, this.state.editingOrder ? 'Edit Purchase Order' : 'Create Purchase Order'),
            DOM.create('button', {
                className: 'modal-close',
                onclick: () => this.hideOrderModal()
            }, '×')
        ));

        const form = DOM.create('div', { className: 'modal-body' });
        form.appendChild(DOM.create('p', {}, 'Purchase order form will be implemented here'));
        modalContent.appendChild(form);

        const footer = DOM.create('div', { className: 'modal-footer' });
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideOrderModal()
        }, 'Cancel'));
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.savePurchaseOrder({})
        }, 'Save'));
        modalContent.appendChild(footer);

        modal.appendChild(modalContent);
        return modal;
    }

    renderRequisitionModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        modalContent.appendChild(DOM.create('div', { className: 'modal-header' },
            DOM.create('h4', {}, this.state.editingRequisition ? 'Edit Requisition' : 'Create Requisition'),
            DOM.create('button', {
                className: 'modal-close',
                onclick: () => this.hideRequisitionModal()
            }, '×')
        ));

        const form = DOM.create('div', { className: 'modal-body' });
        form.appendChild(DOM.create('p', {}, 'Requisition form will be implemented here'));
        modalContent.appendChild(form);

        const footer = DOM.create('div', { className: 'modal-footer' });
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideRequisitionModal()
        }, 'Cancel'));
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.saveRequisition({})
        }, 'Save'));
        modalContent.appendChild(footer);

        modal.appendChild(modalContent);
        return modal;
    }

    renderContractModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        modalContent.appendChild(DOM.create('div', { className: 'modal-header' },
            DOM.create('h4', {}, this.state.editingContract ? 'Edit Contract' : 'Create Contract'),
            DOM.create('button', {
                className: 'modal-close',
                onclick: () => this.hideContractModal()
            }, '×')
        ));

        const form = DOM.create('div', { className: 'modal-body' });
        form.appendChild(DOM.create('p', {}, 'Contract form will be implemented here'));
        modalContent.appendChild(form);

        const footer = DOM.create('div', { className: 'modal-footer' });
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideContractModal()
        }, 'Cancel'));
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.saveContract({})
        }, 'Save'));
        modalContent.appendChild(footer);

        modal.appendChild(modalContent);
        return modal;
    }

    renderEvaluationModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        modalContent.appendChild(DOM.create('div', { className: 'modal-header' },
            DOM.create('h4', {}, 'Supplier Evaluation'),
            DOM.create('button', {
                className: 'modal-close',
                onclick: () => this.hideEvaluationModal()
            }, '×')
        ));

        const form = DOM.create('div', { className: 'modal-body' });
        form.appendChild(DOM.create('p', {}, 'Supplier evaluation form will be implemented here'));
        modalContent.appendChild(form);

        const footer = DOM.create('div', { className: 'modal-footer' });
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: () => this.hideEvaluationModal()
        }, 'Cancel'));
        footer.appendChild(DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.saveSupplierEvaluation({})
        }, 'Save Evaluation'));
        modalContent.appendChild(footer);

        modal.appendChild(modalContent);
        return modal;
    }

    // Utility methods
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    formatTimeAgo(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 1) return '1 day ago';
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
        return `${Math.ceil(diffDays / 30)} months ago`;
    }

    handlePageChange(page) {
        this.setState({
            pagination: { ...this.state.pagination, page }
        }, () => {
            if (this.state.currentView === 'vendors') {
                this.loadVendors();
            } else if (this.state.currentView === 'purchase-orders') {
                this.loadPurchaseOrders();
            }
        });
    }

    // Placeholder methods for actions
    viewVendorDetails(vendor) {
        App.showNotification({
            type: 'info',
            message: 'View vendor details not yet implemented'
        });
    }

    viewOrderDetails(order) {
        App.showNotification({
            type: 'info',
            message: 'View order details not yet implemented'
        });
    }

    viewRequisitionDetails(requisition) {
        App.showNotification({
            type: 'info',
            message: 'View requisition details not yet implemented'
        });
    }

    viewContractDetails(contract) {
        App.showNotification({
            type: 'info',
            message: 'View contract details not yet implemented'
        });
    }

    viewEvaluationDetails(evaluation) {
        App.showNotification({
            type: 'info',
            message: 'View evaluation details not yet implemented'
        });
    }
}

// Export the component
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Procurement;
}
