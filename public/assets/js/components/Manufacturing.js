/**
 * TPT Free ERP - Manufacturing Component
 * Complete production planning, work order management, and quality control interface
 */

class Manufacturing extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Manufacturing Management',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            productionPlans: [],
            boms: [],
            workOrders: [],
            qualityInspections: [],
            productionLines: [],
            shopFloorData: {},
            analytics: {},
            filters: {
                status: '',
                production_line: '',
                priority: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedWorkOrders: [],
            selectedBOMs: [],
            showPlanModal: false,
            showBOMModal: false,
            showWorkOrderModal: false,
            showInspectionModal: false,
            showProductionLineModal: false,
            editingPlan: null,
            editingBOM: null,
            editingWorkOrder: null,
            editingInspection: null,
            editingProductionLine: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            }
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadProductionPlans = this.loadProductionPlans.bind(this);
        this.loadBillsOfMaterials = this.loadBillsOfMaterials.bind(this);
        this.loadWorkOrders = this.loadWorkOrders.bind(this);
        this.loadQualityInspections = this.loadQualityInspections.bind(this);
        this.loadProductionLines = this.loadProductionLines.bind(this);
        this.loadShopFloorData = this.loadShopFloorData.bind(this);
        this.loadAnalytics = this.loadAnalytics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleWorkOrderSelect = this.handleWorkOrderSelect.bind(this);
        this.handleBOMSelect = this.handleBOMSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showPlanModal = this.showPlanModal.bind(this);
        this.hidePlanModal = this.hidePlanModal.bind(this);
        this.saveProductionPlan = this.saveProductionPlan.bind(this);
        this.showBOMModal = this.showBOMModal.bind(this);
        this.hideBOMModal = this.hideBOMModal.bind(this);
        this.saveBOM = this.saveBOM.bind(this);
        this.showWorkOrderModal = this.showWorkOrderModal.bind(this);
        this.hideWorkOrderModal = this.hideWorkOrderModal.bind(this);
        this.saveWorkOrder = this.saveWorkOrder.bind(this);
        this.updateWorkOrderStatus = this.updateWorkOrderStatus.bind(this);
        this.recordProductionData = this.recordProductionData.bind(this);
        this.showInspectionModal = this.showInspectionModal.bind(this);
        this.hideInspectionModal = this.hideInspectionModal.bind(this);
        this.saveQualityInspection = this.saveQualityInspection.bind(this);
        this.showProductionLineModal = this.showProductionLineModal.bind(this);
        this.hideProductionLineModal = this.hideProductionLineModal.bind(this);
        this.saveProductionLine = this.saveProductionLine.bind(this);
        this.recordDowntime = this.recordDowntime.bind(this);
    }

    async componentDidMount() {
        await this.loadInitialData();
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load basic data
            await Promise.all([
                this.loadProductionLines()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading manufacturing data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load manufacturing data'
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
            case 'production-planning':
                await this.loadProductionPlans();
                break;
            case 'boms':
                await this.loadBillsOfMaterials();
                break;
            case 'work-orders':
                await this.loadWorkOrders();
                break;
            case 'quality-control':
                await this.loadQualityInspections();
                break;
            case 'resource-planning':
                await this.loadProductionLines();
                break;
            case 'shop-floor':
                await this.loadShopFloorData();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/manufacturing/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading manufacturing overview:', error);
        }
    }

    async loadProductionPlans() {
        try {
            const response = await API.get('/manufacturing/production-plans');
            this.setState({ productionPlans: response.production_plans });
        } catch (error) {
            console.error('Error loading production plans:', error);
        }
    }

    async loadBillsOfMaterials() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/manufacturing/boms?${params}`);
            this.setState({
                boms: response.boms,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading bills of materials:', error);
        }
    }

    async loadWorkOrders() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/manufacturing/work-orders?${params}`);
            this.setState({
                workOrders: response.work_orders,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading work orders:', error);
        }
    }

    async loadQualityInspections() {
        try {
            const response = await API.get('/manufacturing/quality-inspections');
            this.setState({ qualityInspections: response.quality_inspections });
        } catch (error) {
            console.error('Error loading quality inspections:', error);
        }
    }

    async loadProductionLines() {
        try {
            const response = await API.get('/manufacturing/production-lines');
            this.setState({ productionLines: response.production_lines });
        } catch (error) {
            console.error('Error loading production lines:', error);
        }
    }

    async loadShopFloorData() {
        try {
            const response = await API.get('/manufacturing/shop-floor/monitoring');
            this.setState({ shopFloorData: response });
        } catch (error) {
            console.error('Error loading shop floor data:', error);
        }
    }

    async loadAnalytics() {
        try {
            const response = await API.get('/manufacturing/analytics');
            this.setState({ analytics: response });
        } catch (error) {
            console.error('Error loading manufacturing analytics:', error);
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
            if (this.state.currentView === 'boms') {
                this.loadBillsOfMaterials();
            } else if (this.state.currentView === 'work-orders') {
                this.loadWorkOrders();
            }
        });
    }

    handleWorkOrderSelect(workOrderId, selected) {
        const selectedWorkOrders = selected
            ? [...this.state.selectedWorkOrders, workOrderId]
            : this.state.selectedWorkOrders.filter(id => id !== workOrderId);

        this.setState({ selectedWorkOrders });
    }

    handleBOMSelect(bomId, selected) {
        const selectedBOMs = selected
            ? [...this.state.selectedBOMs, bomId]
            : this.state.selectedBOMs.filter(id => id !== bomId);

        this.setState({ selectedBOMs });
    }

    async handleBulkAction(action) {
        if (this.state.selectedWorkOrders.length === 0 && this.state.selectedBOMs.length === 0) {
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
                case 'update_priority':
                    await this.showBulkUpdateModal('priority');
                    break;
                case 'export_work_orders':
                    await this.exportWorkOrders();
                    break;
                case 'export_boms':
                    await this.exportBOMs();
                    break;
                case 'bulk_inspection':
                    await this.bulkInspection();
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

    async exportWorkOrders() {
        // Implementation for export
        App.showNotification({
            type: 'info',
            message: 'Export not yet implemented'
        });
    }

    async exportBOMs() {
        // Implementation for export
        App.showNotification({
            type: 'info',
            message: 'Export not yet implemented'
        });
    }

    async bulkInspection() {
        // Implementation for bulk inspection
        App.showNotification({
            type: 'info',
            message: 'Bulk inspection not yet implemented'
        });
    }

    showPlanModal(plan = null) {
        this.setState({
            showPlanModal: true,
            editingPlan: plan
        });
    }

    hidePlanModal() {
        this.setState({
            showPlanModal: false,
            editingPlan: null
        });
    }

    async saveProductionPlan(planData) {
        try {
            await API.post('/manufacturing/production-plans', planData);
            App.showNotification({
                type: 'success',
                message: 'Production plan created successfully'
            });
            this.hidePlanModal();
            await this.loadProductionPlans();
        } catch (error) {
            console.error('Error saving production plan:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save production plan'
            });
        }
    }

    showBOMModal(bom = null) {
        this.setState({
            showBOMModal: true,
            editingBOM: bom
        });
    }

    hideBOMModal() {
        this.setState({
            showBOMModal: false,
            editingBOM: null
        });
    }

    async saveBOM(bomData) {
        try {
            await API.post('/manufacturing/boms', bomData);
            App.showNotification({
                type: 'success',
                message: 'Bill of materials created successfully'
            });
            this.hideBOMModal();
            await this.loadBillsOfMaterials();
        } catch (error) {
            console.error('Error saving BOM:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save bill of materials'
            });
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
            await API.post('/manufacturing/work-orders', workOrderData);
            App.showNotification({
                type: 'success',
                message: 'Work order created successfully'
            });
            this.hideWorkOrderModal();
            await this.loadWorkOrders();
        } catch (error) {
            console.error('Error saving work order:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save work order'
            });
        }
    }

    async updateWorkOrderStatus(workOrderId, status) {
        try {
            await API.put(`/manufacturing/work-orders/${workOrderId}/status`, { status });
            App.showNotification({
                type: 'success',
                message: 'Work order status updated successfully'
            });
            await this.loadWorkOrders();
        } catch (error) {
            console.error('Error updating work order status:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to update work order status'
            });
        }
    }

    async recordProductionData(workOrderId, data) {
        try {
            await API.post(`/manufacturing/work-orders/${workOrderId}/production-data`, data);
            App.showNotification({
                type: 'success',
                message: 'Production data recorded successfully'
            });
            await this.loadWorkOrders();
        } catch (error) {
            console.error('Error recording production data:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to record production data'
            });
        }
    }

    showInspectionModal(inspection = null) {
        this.setState({
            showInspectionModal: true,
            editingInspection: inspection
        });
    }

    hideInspectionModal() {
        this.setState({
            showInspectionModal: false,
            editingInspection: null
        });
    }

    async saveQualityInspection(inspectionData) {
        try {
            await API.post('/manufacturing/quality-inspections', inspectionData);
            App.showNotification({
                type: 'success',
                message: 'Quality inspection created successfully'
            });
            this.hideInspectionModal();
            await this.loadQualityInspections();
        } catch (error) {
            console.error('Error saving quality inspection:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save quality inspection'
            });
        }
    }

    showProductionLineModal(productionLine = null) {
        this.setState({
            showProductionLineModal: true,
            editingProductionLine: productionLine
        });
    }

    hideProductionLineModal() {
        this.setState({
            showProductionLineModal: false,
            editingProductionLine: null
        });
    }

    async saveProductionLine(productionLineData) {
        try {
            await API.post('/manufacturing/production-lines', productionLineData);
            App.showNotification({
                type: 'success',
                message: 'Production line created successfully'
            });
            this.hideProductionLineModal();
            await this.loadProductionLines();
        } catch (error) {
            console.error('Error saving production line:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save production line'
            });
        }
    }

    async recordDowntime(downtimeData) {
        try {
            await API.post('/manufacturing/downtime', downtimeData);
            App.showNotification({
                type: 'success',
                message: 'Downtime recorded successfully'
            });
            await this.loadShopFloorData();
        } catch (error) {
            console.error('Error recording downtime:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to record downtime'
            });
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'manufacturing-container' });

        // Header
        const header = DOM.create('div', { className: 'manufacturing-header' });
        const titleElement = DOM.create('h1', { className: 'manufacturing-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'manufacturing-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showPlanModal) {
            container.appendChild(this.renderPlanModal());
        }

        if (this.state.showBOMModal) {
            container.appendChild(this.renderBOMModal());
        }

        if (this.state.showWorkOrderModal) {
            container.appendChild(this.renderWorkOrderModal());
        }

        if (this.state.showInspectionModal) {
            container.appendChild(this.renderInspectionModal());
        }

        if (this.state.showProductionLineModal) {
            container.appendChild(this.renderProductionLineModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'production-planning', label: 'Production Planning', icon: 'fas fa-calendar-alt' },
            { id: 'boms', label: 'Bill of Materials', icon: 'fas fa-list' },
            { id: 'work-orders', label: 'Work Orders', icon: 'fas fa-tasks' },
            { id: 'quality-control', label: 'Quality Control', icon: 'fas fa-check-circle' },
            { id: 'resource-planning', label: 'Resource Planning', icon: 'fas fa-cogs' },
            { id: 'shop-floor', label: 'Shop Floor', icon: 'fas fa-industry' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-line' }
        ];

        const nav = DOM.create('nav', { className: 'manufacturing-nav' });
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
            DOM.create('p', {}, 'Loading manufacturing data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'production-planning':
                return this.renderProductionPlanning();
            case 'boms':
                return this.renderBillsOfMaterials();
            case 'work-orders':
                return this.renderWorkOrders();
            case 'quality-control':
                return this.renderQualityControl();
            case 'resource-planning':
                return this.renderResourcePlanning();
            case 'shop-floor':
                return this.renderShopFloor();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'manufacturing-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Production metrics
        const metricsSection = this.renderProductionMetrics();
        dashboard.appendChild(metricsSection);

        // Recent activities
        const activitiesSection = this.renderRecentActivities();
        dashboard.appendChild(activitiesSection);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.production_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Work Orders',
                value: overview.total_work_orders || 0,
                icon: 'fas fa-tasks',
                color: 'primary'
            },
            {
                title: 'Active Production Lines',
                value: overview.active_production_lines || 0,
                icon: 'fas fa-industry',
                color: 'success'
            },
            {
                title: 'Total Produced',
                value: (overview.total_produced || 0).toLocaleString(),
                icon: 'fas fa-cubes',
                color: 'info'
            },
            {
                title: 'Production Efficiency',
                value: (overview.production_efficiency || 0) + '%',
                icon: 'fas fa-chart-line',
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

    renderProductionMetrics() {
        const metricsSection = DOM.create('div', { className: 'dashboard-section' });
        metricsSection.appendChild(DOM.create('h3', {}, 'Production Metrics'));

        const metrics = DOM.create('div', { className: 'production-metrics' });

        const metricData = [
            { label: 'Quality Rate', value: '98.5%', color: 'success' },
            { label: 'On-Time Delivery', value: '95%', color: 'info' },
            { label: 'Equipment Utilization', value: '87%', color: 'warning' },
            { label: 'Labor Efficiency', value: '92%', color: 'primary' }
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

    renderProductionPlanning() {
        const planningView = DOM.create('div', { className: 'production-planning-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showPlanModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Production Plan';
        toolbar.appendChild(addButton);
        planningView.appendChild(toolbar);

        // Production plans table
        const table = this.renderProductionPlansTable();
        planningView.appendChild(table);

        return planningView;
    }

    renderProductionPlansTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'plan_name', label: 'Plan Name' },
            { key: 'planning_period', label: 'Period' },
            { key: 'total_quantity_planned', label: 'Planned Quantity' },
            { key: 'total_quantity_produced', label: 'Produced Quantity' },
            { key: 'plan_completion', label: 'Completion %' },
            { key: 'start_date', label: 'Start Date' },
            { key: 'end_date', label: 'End Date' },
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

        this.state.productionPlans.forEach(plan => {
            const row = DOM.create('tr', {});

            // Plan Name
            row.appendChild(DOM.create('td', {}, plan.plan_name));

            // Period
            row.appendChild(DOM.create('td', {}, plan.planning_period));

            // Planned Quantity
            row.appendChild(DOM.create('td', {}, (plan.total_quantity_planned || 0).toLocaleString()));

            // Produced Quantity
            row.appendChild(DOM.create('td', {}, (plan.total_quantity_produced || 0).toLocaleString()));

            // Completion %
            row.appendChild(DOM.create('td', {}, (plan.plan_completion || 0) + '%'));

            // Start Date
            row.appendChild(DOM.create('td', {}, this.formatDate(plan.start_date)));

            // End Date
            row.appendChild(DOM.create('td', {}, this.formatDate(plan.end_date)));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${plan.status}`
            }, plan.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewPlanDetails(plan)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showPlanModal(plan)
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

    renderBillsOfMaterials() {
        const bomsView = DOM.create('div', { className: 'boms-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showBOMModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create BOM';
        toolbar.appendChild(addButton);
        bomsView.appendChild(toolbar);

        // Filters
        const filters = this.renderBOMFilters();
        bomsView.appendChild(filters);

        // BOMs table
        const table = this.renderBOMsTable();
        bomsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        bomsView.appendChild(pagination);

        return bomsView;
    }

    renderBOMFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search BOMs...',
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
        const statuses = ['', 'draft', 'active', 'obsolete'];
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

    renderBOMsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'product_name', label: 'Product' },
            { key: 'version_number', label: 'Version' },
            { key: 'component_count', label: 'Components' },
            { key: 'total_cost', label: 'Total Cost' },
            { key: 'effective_date', label: 'Effective Date' },
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

        this.state.boms.forEach(bom => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedBOMs.includes(bom.id),
                onchange: (e) => this.handleBOMSelect(bom.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Product
            row.appendChild(DOM.create('td', {}, bom.product_name));

            // Version
            row.appendChild(DOM.create('td', {}, bom.version_number));

            // Components
            row.appendChild(DOM.create('td', {}, bom.component_count || 0));

            // Total Cost
            row.appendChild(DOM.create('td', {}, '$' + (bom.total_cost || 0).toLocaleString()));

            // Effective Date
            row.appendChild(DOM.create('td', {}, this.formatDate(bom.effective_date)));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${bom.status}`
            }, bom.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewBOMDetails(bom)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showBOMModal(bom)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const copyButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.copyBOM(bom)
            });
            copyButton.innerHTML = '<i class="fas fa-copy"></i>';
            actions.appendChild(copyButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderWorkOrders() {
        const workOrdersView = DOM.create('div', { className: 'work-orders-view' });

        // Toolbar
        const toolbar = this.renderWorkOrdersToolbar();
        workOrdersView.appendChild(toolbar);

        // Filters
        const filters = this.renderWorkOrderFilters();
        workOrdersView.appendChild(filters);

        // Work orders table
        const table = this.renderWorkOrdersTable();
        workOrdersView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        workOrdersView.appendChild(pagination);

        return workOrdersView;
    }

    renderWorkOrdersToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedWorkOrders.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedWorkOrders.length} selected`
            ));

            const actions = ['update_status', 'update_priority', 'export_work_orders', 'bulk_inspection'];
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
            onclick: () => this.showWorkOrderModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Work Order';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderWorkOrderFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search work orders...',
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
        const statuses = ['', 'draft', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1)
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

    renderWorkOrdersTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'work_order_number', label: 'Work Order' },
            { key: 'product_name', label: 'Product' },
            { key: 'line_name', label: 'Production Line' },
            { key: 'quantity_planned', label: 'Planned' },
            { key: 'quantity_produced', label: 'Produced' },
            { key: 'completion_percentage', label: 'Progress' },
            { key: 'priority', label: 'Priority' },
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

        this.state.workOrders.forEach(workOrder => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedWorkOrders.includes(workOrder.id),
                onchange: (e) => this.handleWorkOrderSelect(workOrder.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Work Order Number
            row.appendChild(DOM.create('td', {}, workOrder.work_order_number));

            // Product
            row.appendChild(DOM.create('td', {}, workOrder.product_name));

            // Production Line
            row.appendChild(DOM.create('td', {}, workOrder.line_name || 'N/A'));

            // Planned
            row.appendChild(DOM.create('td', {}, (workOrder.quantity_planned || 0).toLocaleString()));

            // Produced
            row.appendChild(DOM.create('td', {}, (workOrder.quantity_produced || 0).toLocaleString()));

            // Progress
            const progressCell = DOM.create('td', {});
            const progressBar = DOM.create('div', { className: 'progress-bar' });
            progressBar.appendChild(DOM.create('div', {
                className: 'progress-fill',
                style: `width: ${workOrder.completion_percentage || 0}%`
            }, `${workOrder.completion_percentage || 0}%`));
            progressCell.appendChild(progressBar);
            row.appendChild(progressCell);

            // Priority
            const priorityCell = DOM.create('td', {});
            const priorityBadge = DOM.create('span', {
                className: `priority-badge priority-${workOrder.priority}`
            }, workOrder.priority);
            priorityCell.appendChild(priorityBadge);
            row.appendChild(priorityCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${workOrder.status}`
            }, workOrder.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewWorkOrderDetails(workOrder)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showWorkOrderModal(workOrder)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const productionButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.recordProductionData(workOrder.id, { data_type: 'quantity', value: 1 })
            });
            productionButton.innerHTML = '<i class="fas fa-plus-circle"></i>';
            actions.appendChild(productionButton);

            const inspectionButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showInspectionModal({ work_order_id: workOrder.id })
            });
            inspectionButton.innerHTML = '<i class="fas fa-check-circle"></i>';
            actions.appendChild(inspectionButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderQualityControl() {
        const qualityView = DOM.create('div', { className: 'quality-control-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showInspectionModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Inspection';
        toolbar.appendChild(addButton);
        qualityView.appendChild(toolbar);

        // Quality inspections table
        const table = this.renderQualityInspectionsTable();
        qualityView.appendChild(table);

        return qualityView;
    }

    renderQualityInspectionsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'work_order_number', label: 'Work Order' },
            { key: 'inspection_type', label: 'Type' },
            { key: 'specification', label: 'Specification' },
            { key: 'actual_value', label: 'Actual Value' },
            { key: 'result', label: 'Result' },
            { key: 'defect_rate', label: 'Defect Rate' },
            { key: 'inspection_date', label: 'Date' },
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

        this.state.qualityInspections.forEach(inspection => {
            const row = DOM.create('tr', {});

            // Work Order
            row.appendChild(DOM.create('td', {}, inspection.work_order_number || 'N/A'));

            // Type
            row.appendChild(DOM.create('td', {}, inspection.inspection_type));

            // Specification
            row.appendChild(DOM.create('td', {}, inspection.specification || 'N/A'));

            // Actual Value
            row.appendChild(DOM.create('td', {}, inspection.actual_value || 'N/A'));

            // Result
            const resultCell = DOM.create('td', {});
            const resultBadge = DOM.create
