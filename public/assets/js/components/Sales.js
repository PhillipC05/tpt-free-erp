/**
 * TPT Free ERP - Sales & CRM Component (Refactored)
 * Main sales dashboard and CRM management interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class Sales extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderer for customers
        this.customersTableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks for customers
        this.customersTableRenderer.setDataCallback(() => this.state.customers || []);
        this.customersTableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedCustomers: selectedIds });
        });
        this.customersTableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.customersTableRenderer.setDataChangeCallback(() => {
            this.loadCustomers();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadCustomers',
            'loadLeads',
            'loadOpportunities',
            'loadOrders',
            'loadPipelineStages',
            'loadCustomerSegments',
            'loadLeadSources',
            'handleViewChange',
            'handleFilterChange',
            'handleBulkAction',
            'showCustomerModal',
            'hideCustomerModal',
            'saveCustomer',
            'deleteCustomer',
            'showLeadModal',
            'hideLeadModal',
            'saveLead',
            'showOpportunityModal',
            'hideOpportunityModal',
            'saveOpportunity',
            'updateOpportunityStage',
            'showOrderModal',
            'hideOrderModal',
            'saveOrder'
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
                this.loadPipelineStages(),
                this.loadCustomerSegments(),
                this.loadLeadSources()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading sales data:', error);
            this.showErrorNotification('Failed to load sales data');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'customers':
                await this.loadCustomers();
                break;
            case 'leads':
                await this.loadLeads();
                break;
            case 'opportunities':
                await this.loadOpportunities();
                break;
            case 'orders':
                await this.loadOrders();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/sales/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading sales overview:', error);
        }
    }

    async loadCustomers() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/sales/customers?${params}`);
            this.setState({
                customers: response.customers,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading customers:', error);
        }
    }

    async loadLeads() {
        try {
            const response = await API.get('/sales/leads');
            this.setState({ leads: response.leads });
        } catch (error) {
            console.error('Error loading leads:', error);
        }
    }

    async loadOpportunities() {
        try {
            const response = await API.get('/sales/opportunities');
            this.setState({ opportunities: response.opportunities });
        } catch (error) {
            console.error('Error loading opportunities:', error);
        }
    }

    async loadOrders() {
        try {
            const response = await API.get('/sales/orders');
            this.setState({ orders: response.orders });
        } catch (error) {
            console.error('Error loading orders:', error);
        }
    }

    async loadPipelineStages() {
        try {
            const response = await API.get('/sales/pipeline/stages');
            this.setState({ pipelineStages: response.stages });
        } catch (error) {
            console.error('Error loading pipeline stages:', error);
        }
    }

    async loadCustomerSegments() {
        try {
            const response = await API.get('/sales/customer-segments');
            this.setState({ customerSegments: response.segments });
        } catch (error) {
            console.error('Error loading customer segments:', error);
        }
    }

    async loadLeadSources() {
        try {
            const response = await API.get('/sales/lead-sources');
            this.setState({ leadSources: response.sources });
        } catch (error) {
            console.error('Error loading lead sources:', error);
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
            if (this.state.currentView === 'customers') {
                this.loadCustomers();
            }
        });
    }

    handleCustomerSelect(customerId, selected) {
        const selectedCustomers = selected
            ? [...this.state.selectedCustomers, customerId]
            : this.state.selectedCustomers.filter(id => id !== customerId);

        this.setState({ selectedCustomers });
    }

    async handleBulkAction(action, selectedIds = null) {
        const customersToUse = selectedIds || this.state.selectedCustomers;

        if (customersToUse.length === 0) {
            this.showWarningNotification('Please select customers first');
            return;
        }

        try {
            switch (action) {
                case 'delete':
                    if (await this.confirm(`Delete ${customersToUse.length} customers?`)) {
                        await this.bulkDeleteCustomers(customersToUse);
                    }
                    break;
                case 'update_segment':
                    await this.showBulkUpdateModal('segment', customersToUse);
                    break;
                case 'update_status':
                    await this.showBulkUpdateModal('status', customersToUse);
                    break;
                case 'send_email':
                    await this.sendBulkEmail(customersToUse);
                    break;
                case 'export':
                    await this.exportCustomers(customersToUse);
                    break;
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            this.showErrorNotification('Bulk action failed');
        }
    }

    async bulkDeleteCustomers(customersToDelete) {
        // Implementation for bulk delete
        this.showInfoNotification('Bulk delete not yet implemented');
    }

    async showBulkUpdateModal(field, customersToUpdate) {
        // Implementation for bulk update modal
        this.showInfoNotification('Bulk update not yet implemented');
    }

    async sendBulkEmail(customersToEmail) {
        // Implementation for bulk email
        this.showInfoNotification('Bulk email not yet implemented');
    }

    async exportCustomers(customersToExport) {
        // Implementation for export
        this.showInfoNotification('Export not yet implemented');
    }

    showCustomerModal(customer = null) {
        this.setState({
            showCustomerModal: true,
            editingCustomer: customer
        });
    }

    hideCustomerModal() {
        this.setState({
            showCustomerModal: false,
            editingCustomer: null
        });
    }

    async saveCustomer(customerData) {
        try {
            if (this.state.editingCustomer) {
                await this.apiRequest('PUT', `/sales/customers/${this.state.editingCustomer.id}`, customerData);
                this.showSuccessNotification('Customer updated successfully');
            } else {
                await this.apiRequest('POST', '/sales/customers', customerData);
                this.showSuccessNotification('Customer created successfully');
            }

            this.hideCustomerModal();
            await this.loadCustomers();
        } catch (error) {
            console.error('Error saving customer:', error);
            this.showErrorNotification(error.message || 'Failed to save customer');
        }
    }

    async deleteCustomer(customerId) {
        if (!(await this.confirm('Are you sure you want to delete this customer?'))) {
            return;
        }

        try {
            await this.apiRequest('DELETE', `/sales/customers/${customerId}`);
            this.showSuccessNotification('Customer deleted successfully');
            await this.loadCustomers();
        } catch (error) {
            console.error('Error deleting customer:', error);
            this.showErrorNotification(error.message || 'Failed to delete customer');
        }
    }

    showLeadModal(lead = null) {
        this.setState({
            showLeadModal: true,
            editingLead: lead
        });
    }

    hideLeadModal() {
        this.setState({
            showLeadModal: false,
            editingLead: null
        });
    }

    async saveLead(leadData) {
        try {
            await this.apiRequest('POST', '/sales/leads', leadData);
            this.showSuccessNotification('Lead created successfully');
            this.hideLeadModal();
            await this.loadLeads();
        } catch (error) {
            console.error('Error saving lead:', error);
            this.showErrorNotification(error.message || 'Failed to save lead');
        }
    }

    showOpportunityModal(opportunity = null) {
        this.setState({
            showOpportunityModal: true,
            editingOpportunity: opportunity
        });
    }

    hideOpportunityModal() {
        this.setState({
            showOpportunityModal: false,
            editingOpportunity: null
        });
    }

    async saveOpportunity(opportunityData) {
        try {
            await this.apiRequest('POST', '/sales/opportunities', opportunityData);
            this.showSuccessNotification('Opportunity created successfully');
            this.hideOpportunityModal();
            await this.loadOpportunities();
        } catch (error) {
            console.error('Error saving opportunity:', error);
            this.showErrorNotification(error.message || 'Failed to save opportunity');
        }
    }

    async updateOpportunityStage(opportunityId, stageId) {
        try {
            await this.apiRequest('PUT', `/sales/opportunities/${opportunityId}/stage`, { stage_id: stageId });
            this.showSuccessNotification('Opportunity stage updated successfully');
            await this.loadOpportunities();
        } catch (error) {
            console.error('Error updating opportunity stage:', error);
            this.showErrorNotification(error.message || 'Failed to update opportunity stage');
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

    async saveOrder(orderData) {
        try {
            await this.apiRequest('POST', '/sales/orders', orderData);
            this.showSuccessNotification('Sales order created successfully');
            this.hideOrderModal();
            await this.loadOrders();
        } catch (error) {
            console.error('Error saving order:', error);
            this.showErrorNotification(error.message || 'Failed to save order');
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'sales-container' });

        // Header
        const header = DOM.create('div', { className: 'sales-header' });
        const titleElement = DOM.create('h1', { className: 'sales-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'sales-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showCustomerModal) {
            container.appendChild(this.renderCustomerModal());
        }

        if (this.state.showLeadModal) {
            container.appendChild(this.renderLeadModal());
        }

        if (this.state.showOpportunityModal) {
            container.appendChild(this.renderOpportunityModal());
        }

        if (this.state.showOrderModal) {
            container.appendChild(this.renderOrderModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'customers', label: 'Customers', icon: 'fas fa-users' },
            { id: 'leads', label: 'Leads', icon: 'fas fa-user-plus' },
            { id: 'opportunities', label: 'Opportunities', icon: 'fas fa-chart-line' },
            { id: 'orders', label: 'Orders', icon: 'fas fa-shopping-cart' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'sales-nav' });
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
            DOM.create('p', {}, 'Loading sales data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'customers':
                return this.renderCustomers();
            case 'leads':
                return this.renderLeads();
            case 'opportunities':
                return this.renderOpportunities();
            case 'orders':
                return this.renderOrders();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'sales-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Pipeline visualization
        const pipelineSection = this.renderPipelineVisualization();
        dashboard.appendChild(pipelineSection);

        // Recent activities
        const activitiesSection = this.renderRecentActivities();
        dashboard.appendChild(activitiesSection);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.sales_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Customers',
                value: overview.total_customers || 0,
                icon: 'fas fa-users',
                color: 'primary'
            },
            {
                title: 'Total Revenue',
                value: '$' + (overview.total_revenue || 0).toLocaleString(),
                icon: 'fas fa-dollar-sign',
                color: 'success'
            },
            {
                title: 'Active Opportunities',
                value: overview.total_opportunities || 0,
                icon: 'fas fa-chart-line',
                color: 'info'
            },
            {
                title: 'Conversion Rate',
                value: (overview.order_completion_rate || 0) + '%',
                icon: 'fas fa-percentage',
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

    renderPipelineVisualization() {
        const pipeline = this.state.overview.sales_pipeline || [];
        const pipelineSection = DOM.create('div', { className: 'dashboard-section' });
        pipelineSection.appendChild(DOM.create('h3', {}, 'Sales Pipeline'));

        if (pipeline.length === 0) {
            pipelineSection.appendChild(DOM.create('p', { className: 'no-data' }, 'No pipeline data available'));
        } else {
            const pipelineContainer = DOM.create('div', { className: 'pipeline-container' });

            pipeline.forEach(stage => {
                const stageElement = DOM.create('div', { className: 'pipeline-stage' });
                stageElement.appendChild(DOM.create('h4', {}, stage.stage_name));
                stageElement.appendChild(DOM.create('div', { className: 'stage-count' }, stage.opportunities_count));
                stageElement.appendChild(DOM.create('div', { className: 'stage-value' }, '$' + stage.total_value.toLocaleString()));

                pipelineContainer.appendChild(stageElement);
            });

            pipelineSection.appendChild(pipelineContainer);
        }

        return pipelineSection;
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
                activityItem.appendChild(DOM.create('span', { className: 'activity-time' }, this.formatTimeAgo(activity.activity_date)));
                activitiesList.appendChild(activityItem);
            });
            activitiesSection.appendChild(activitiesList);
        }

        return activitiesSection;
    }

    renderCustomers() {
        const customersView = DOM.create('div', { className: 'customers-view' });

        // Toolbar
        const toolbar = this.renderCustomersToolbar();
        customersView.appendChild(toolbar);

        // Filters
        const filters = this.renderCustomersFilters();
        customersView.appendChild(filters);

        // Customers table
        const table = this.renderCustomersTable();
        customersView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        customersView.appendChild(pagination);

        return customersView;
    }

    renderCustomersToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedCustomers.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedCustomers.length} selected`
            ));

            const actions = ['update_segment', 'update_status', 'send_email', 'export', 'delete'];
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
            onclick: () => this.showCustomerModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Customer';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderCustomersFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search customers...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Segment filter
        const segmentGroup = DOM.create('div', { className: 'filter-group' });
        const segmentSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.segment,
            onchange: (e) => this.handleFilterChange('segment', e.target.value)
        });
        segmentSelect.appendChild(DOM.create('option', { value: '' }, 'All Segments'));
        this.state.customerSegments.forEach(segment => {
            segmentSelect.appendChild(DOM.create('option', { value: segment.segment_name }, segment.segment_name));
        });
        segmentGroup.appendChild(DOM.create('label', {}, 'Segment:'));
        segmentGroup.appendChild(segmentSelect);
        filters.appendChild(segmentGroup);

        // Status filter
        const statusGroup = DOM.create('div', { className: 'filter-group' });
        const statusSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const statuses = ['', 'active', 'inactive', 'prospect', 'churned'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Value tier filter
        const valueGroup = DOM.create('div', { className: 'filter-group' });
        const valueSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.value_tier,
            onchange: (e) => this.handleFilterChange('value_tier', e.target.value)
        });
        const valueTiers = ['', 'high', 'medium', 'low'];
        valueTiers.forEach(tier => {
            valueSelect.appendChild(DOM.create('option', { value: tier },
                tier === '' ? 'All Value Tiers' : tier.charAt(0).toUpperCase() + tier.slice(1) + ' Value'
            ));
        });
        valueGroup.appendChild(DOM.create('label', {}, 'Value Tier:'));
        valueGroup.appendChild(valueSelect);
        filters.appendChild(valueGroup);

        return filters;
    }

    renderCustomersTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'customer_name', label: 'Customer Name' },
            { key: 'email', label: 'Email' },
            { key: 'segment', label: 'Segment' },
            { key: 'total_orders', label: 'Orders' },
            { key: 'total_revenue', label: 'Revenue' },
            { key: 'customer_status', label: 'Status' },
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

        this.state.customers.forEach(customer => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedCustomers.includes(customer.id),
                onchange: (e) => this.handleCustomerSelect(customer.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Customer Name
            row.appendChild(DOM.create('td', {}, customer.customer_name));

            // Email
            row.appendChild(DOM.create('td', {}, customer.email));

            // Segment
            row.appendChild(DOM.create('td', {}, customer.customer_segment || 'N/A'));

            // Total Orders
            row.appendChild(DOM.create('td', {}, customer.total_orders || 0));

            // Total Revenue
            row.appendChild(DOM.create('td', {}, '$' + (customer.total_revenue || 0).toLocaleString()));

            // Customer Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${customer.customer_status}`
            }, customer.customer_status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showCustomerModal(customer)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const deleteButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-danger',
                onclick: () => this.deleteCustomer(customer.id)
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

    renderLeads() {
        const leadsView = DOM.create('div', { className: 'leads-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showLeadModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Lead';
        toolbar.appendChild(addButton);
        leadsView.appendChild(toolbar);

        // Leads table
        const table = this.renderLeadsTable();
        leadsView.appendChild(table);

        return leadsView;
    }

    renderLeadsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'lead_name', label: 'Lead Name' },
            { key: 'email', label: 'Email' },
            { key: 'company', label: 'Company' },
            { key: 'lead_score', label: 'Score' },
            { key: 'lead_temperature', label: 'Temperature' },
            { key: 'lead_status', label: 'Status' },
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

        this.state.leads.forEach(lead => {
            const row = DOM.create('tr', {});

            // Lead Name
            row.appendChild(DOM.create('td', {}, lead.lead_name));

            // Email
            row.appendChild(DOM.create('td', {}, lead.email));

            // Company
            row.appendChild(DOM.create('td', {}, lead.company || 'N/A'));

            // Lead Score
            row.appendChild(DOM.create('td', {}, lead.lead_score));

            // Lead Temperature
            const tempCell = DOM.create('td', {});
            const tempBadge = DOM.create('span', {
                className: `temperature-badge ${lead.lead_temperature}`
            }, lead.lead_temperature.toUpperCase());
            tempCell.appendChild(tempBadge);
            row.appendChild(tempCell);

            // Lead Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${lead.lead_status}`
            }, lead.lead_status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const convertButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-success',
                onclick: () => this.convertLeadToOpportunity(lead)
            });
            convertButton.innerHTML = '<i class="fas fa-exchange-alt"></i>';
            actions.appendChild(convertButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showLeadModal(lead)
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

    renderOpportunities() {
        const opportunitiesView = DOM.create('div', { className: 'opportunities-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showOpportunityModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Opportunity';
        toolbar.appendChild(addButton);
        opportunitiesView.appendChild(toolbar);

        // Opportunities table
        const table = this.renderOpportunitiesTable();
        opportunitiesView.appendChild(table);

        return opportunitiesView;
    }

    renderOpportunitiesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'opportunity_name', label: 'Opportunity Name' },
            { key: 'customer_name', label: 'Customer' },
            { key: 'stage_name', label: 'Stage' },
            { key: 'expected_value', label: 'Value' },
            { key: 'probability_percentage', label: 'Probability' },
            { key: 'weighted_value', label: 'Weighted Value' },
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

        this.state.opportunities.forEach(opportunity => {
            const row = DOM.create('tr', {});

            // Opportunity Name
            row.appendChild(DOM.create('td', {}, opportunity.opportunity_name));

            // Customer Name
            row.appendChild(DOM.create('td', {}, opportunity.customer_name));

            // Stage
            const stageCell = DOM.create('td', {});
            const stageSelect = DOM.create('select', {
                className: 'form-control form-control-sm',
                value: opportunity.stage_id,
                onchange: (e) => this.updateOpportunityStage(opportunity.id, e.target.value)
            });
            this.state.pipelineStages.forEach(stage => {
                stageSelect.appendChild(DOM.create('option', { value: stage.id }, stage.stage_name));
            });
            stageCell.appendChild(stageSelect);
            row.appendChild(stageCell);

            // Expected Value
            row.appendChild(DOM.create('td', {}, '$' + opportunity.expected_value.toLocaleString()));

            // Probability
            row.appendChild(DOM.create('td', {}, opportunity.probability_percentage + '%'));

            // Weighted Value
            row.appendChild(DOM.create('td', {}, '$' + opportunity.weighted_value.toLocaleString()));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showOpportunityModal(opportunity)
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

    renderOrders() {
        const ordersView = DOM.create('div', { className: 'orders-view' });

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
            { key: 'customer_name', label: 'Customer' },
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

        this.state.orders.forEach(order => {
            const row = DOM.create('tr', {});

            // Order Number
            row.appendChild(DOM.create('td', {}, order.order_number));

            // Customer Name
            row.appendChild(DOM.create('td', {}, order.customer_name));

            // Order Date
            row.appendChild(DOM.create('td', {}, this.formatDate(order.order_date)));

            // Total Amount
            row.appendChild(DOM.create('td', {}, '$' + order.total_amount.toLocaleString()));

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

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });

        // Placeholder for analytics content
        analyticsView.appendChild(DOM.create('div', { className: 'analytics-placeholder' },
            DOM.create('h3', {}, 'Sales Analytics'),
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
            this.loadCustomers();
        });
    }

    renderCustomerModal() {
        // Placeholder for customer modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {},
            this.state.editingCustomer ? 'Edit Customer' : 'Add New Customer'
        ));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCustomerModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Customer form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderLeadModal() {
        // Placeholder for lead modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Add New Lead'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideLeadModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Lead form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderOpportunityModal() {
        // Placeholder for opportunity modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Add New Opportunity'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideOpportunityModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Opportunity form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderOrderModal() {
        // Placeholder for order modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Create Sales Order'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideOrderModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Order form coming soon...'));
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
ComponentRegistry.register('Sales', Sales);

// Make globally available
if (typeof window !== 'undefined') {
    window.Sales = Sales;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Sales;
}
