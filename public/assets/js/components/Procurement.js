/**
 * TPT Free ERP - Procurement Component
 * Complete vendor management, purchase orders, and supplier evaluation interface
 */

class Procurement extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Procurement Management',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            vendors: [],
            purchaseOrders: [],
            requisitions: [],
            contracts: [],
            spendAnalysis: {},
            supplierEvaluations: [],
            filters: {
                status: '',
                category: '',
                vendor: '',
                date_from: '',
                date_to: '',
                amount_min: '',
                amount_max: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedVendors: [],
            selectedOrders: [],
            selectedRequisitions: [],
            showVendorModal: false,
            showOrderModal: false,
            showRequisitionModal: false,
            showContractModal: false,
            showEvaluationModal: false,
            editingVendor: null,
            editingOrder: null,
            editingRequisition: null,
            editingContract: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            }
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadVendors = this.loadVendors.bind(this);
        this.loadPurchaseOrders = this.loadPurchaseOrders.bind(this);
        this.loadRequisitions = this.loadRequisitions.bind(this);
        this.loadContracts = this.loadContracts.bind(this);
        this.loadSpendAnalysis = this.loadSpendAnalysis.bind(this);
        this.loadSupplierEvaluations = this.loadSupplierEvaluations.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleVendorSelect = this.handleVendorSelect.bind(this);
        this.handleOrderSelect = this.handleOrderSelect.bind(this);
        this.handleRequisitionSelect = this.handleRequisitionSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showVendorModal = this.showVendorModal.bind(this);
        this.hideVendorModal = this.hideVendorModal.bind(this);
        this.saveVendor = this.saveVendor.bind(this);
        this.deleteVendor = this.deleteVendor.bind(this);
        this.showOrderModal = this.showOrderModal.bind(this);
        this.hideOrderModal = this.hideOrderModal.bind(this);
        this.savePurchaseOrder = this.savePurchaseOrder.bind(this);
        this.updateOrderStatus = this.updateOrderStatus.bind(this);
        this.showRequisitionModal = this.showRequisitionModal.bind(this);
        this.hideRequisitionModal = this.hideRequisitionModal.bind(this);
        this.saveRequisition = this.saveRequisition.bind(this);
        this.approveRequisition = this.approveRequisition.bind(this);
        this.showContractModal = this.showContractModal.bind(this);
        this.hideContractModal = this.hideContractModal.bind(this);
        this.saveContract = this.saveContract.bind(this);
        this.showEvaluationModal = this.showEvaluationModal.bind(this);
        this.hideEvaluationModal = this.hideEvaluationModal.bind(this);
        this.saveSupplierEvaluation = this.saveSupplierEvaluation.bind(this);
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
            const response = await API.get('/procurement/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading procurement overview:', error);
        }
    }

    async loadVendors() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/procurement/vendors?${params}`);
            this.setState({
                vendors: response.vendors,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading vendors:', error);
        }
    }

    async loadPurchaseOrders() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/procurement/purchase-orders?${params}`);
            this.setState({
                purchaseOrders: response.purchase_orders,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading purchase orders:', error);
        }
    }

    async loadRequisitions() {
        try {
            const response = await API.get('/procurement/requisitions');
            this.setState({ requisitions: response.requisitions });
        } catch (error) {
            console.error('Error loading requisitions:', error);
        }
    }

    async loadContracts() {
        try {
            const response = await API.get('/procurement/contracts');
            this.setState({ contracts: response.contracts });
        } catch (error) {
            console.error('Error loading contracts:', error);
        }
    }

    async loadSpendAnalysis() {
        try {
            const response = await API.get('/procurement/spend-analysis');
            this.setState({ spendAnalysis: response });
        } catch (error) {
            console.error('Error loading spend analysis:', error);
        }
    }

    async loadSupplierEvaluations() {
        try {
            const response = await API.get('/procurement/supplier-evaluations');
            this.setState({ supplierEvaluations: response.evaluations });
        } catch (error) {
            console.error('Error loading supplier evaluations:', error);
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
                await API.put(`/procurement/vendors/${this.state.editingVendor.id}`, vendorData);
                App.showNotification({
                    type: 'success',
                    message: 'Vendor updated successfully'
                });
            } else {
                await API.post('/procurement/vendors', vendorData);
                App.showNotification({
                    type: 'success',
                    message: 'Vendor created successfully'
                });
            }

            this.hideVendorModal();
            await this.loadVendors();
        } catch (error) {
            console.error('Error saving vendor:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save vendor'
            });
        }
    }

    async deleteVendor(vendorId) {
        if (!confirm('Are you sure you want to deactivate this vendor?')) {
            return;
        }

        try {
            await API.delete(`/procurement/vendors/${vendorId}`);
            App.showNotification({
                type: 'success',
                message: 'Vendor deactivated successfully'
            });
            await this.loadVendors();
        } catch (error) {
            console.error('Error deactivating vendor:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to deactivate vendor'
            });
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
            await API.post('/procurement/purchase-orders', orderData);
            App.showNotification({
                type: 'success',
                message: 'Purchase order created successfully'
            });
            this.hideOrderModal();
            await this.loadPurchaseOrders();
        } catch (error) {
            console.error('Error saving purchase order:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save purchase order'
            });
        }
    }

    async updateOrderStatus(orderId, status) {
        try {
            await API.put(`/procurement/purchase-orders/${orderId}/status`, { status });
            App.showNotification({
                type: 'success',
                message: 'Purchase order status updated successfully'
            });
            await this.loadPurchaseOrders();
        } catch (error) {
            console.error('Error updating order status:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to update order status'
            });
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
