/**
 * TPT Free ERP - Field Service Component
 * Complete service call management, technician scheduling, and customer service interface
 */

class FieldService extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Field Service',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            serviceCalls: [],
            technicians: [],
            customers: [],
            serviceTypes: [],
            serviceSchedule: [],
            communicationHistory: [],
            customerFeedback: [],
            partsInventory: [],
            serviceContracts: [],
            serviceAnalytics: {},
            filters: {
                status: '',
                priority: '',
                technician: '',
                customer: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedServiceCalls: [],
            selectedTechnicians: [],
            showServiceCallModal: false,
            showTechnicianModal: false,
            showCommunicationModal: false,
            showPartsModal: false,
            showContractModal: false,
            editingServiceCall: null,
            editingTechnician: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            }
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadServiceCalls = this.loadServiceCalls.bind(this);
        this.loadTechnicians = this.loadTechnicians.bind(this);
        this.loadCustomers = this.loadCustomers.bind(this);
        this.loadServiceTypes = this.loadServiceTypes.bind(this);
        this.loadServiceSchedule = this.loadServiceSchedule.bind(this);
        this.loadCommunicationHistory = this.loadCommunicationHistory.bind(this);
        this.loadCustomerFeedback = this.loadCustomerFeedback.bind(this);
        this.loadPartsInventory = this.loadPartsInventory.bind(this);
        this.loadServiceContracts = this.loadServiceContracts.bind(this);
        this.loadServiceAnalytics = this.loadServiceAnalytics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleServiceCallSelect = this.handleServiceCallSelect.bind(this);
        this.handleTechnicianSelect = this.handleTechnicianSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showServiceCallModal = this.showServiceCallModal.bind(this);
        this.hideServiceCallModal = this.hideServiceCallModal.bind(this);
        this.saveServiceCall = this.saveServiceCall.bind(this);
        this.showTechnicianModal = this.showTechnicianModal.bind(this);
        this.hideTechnicianModal = this.hideTechnicianModal.bind(this);
        this.saveTechnician = this.saveTechnician.bind(this);
        this.assignTechnician = this.assignTechnician.bind(this);
        this.updateServiceStatus = this.updateServiceStatus.bind(this);
        this.sendCommunication = this.sendCommunication.bind(this);
        this.createPartsOrder = this.createPartsOrder.bind(this);
        this.createServiceContract = this.createServiceContract.bind(this);
        this.exportServiceCalls = this.exportServiceCalls.bind(this);
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
                this.loadTechnicians(),
                this.loadCustomers(),
                this.loadServiceTypes()
            ]);
        } catch (error) {
            console.error('Error loading field service data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load field service data'
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
            case 'service-calls':
                await this.loadServiceCalls();
                break;
            case 'technician-scheduling':
                await Promise.all([
                    this.loadServiceSchedule(),
                    this.loadTechnicians()
                ]);
                break;
            case 'customer-communication':
                await Promise.all([
                    this.loadCommunicationHistory(),
                    this.loadCustomerFeedback()
                ]);
                break;
            case 'parts-management':
                await this.loadPartsInventory();
                break;
            case 'service-contracts':
                await this.loadServiceContracts();
                break;
            case 'analytics':
                await this.loadServiceAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/field-service/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading field service overview:', error);
        }
    }

    async loadServiceCalls() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/field-service/service-calls?${params}`);
            this.setState({
                serviceCalls: response.service_calls,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading service calls:', error);
        }
    }

    async loadTechnicians() {
        try {
            const response = await API.get('/field-service/technicians');
            this.setState({ technicians: response });
        } catch (error) {
            console.error('Error loading technicians:', error);
        }
    }

    async loadCustomers() {
        try {
            const response = await API.get('/field-service/customers');
            this.setState({ customers: response });
        } catch (error) {
            console.error('Error loading customers:', error);
        }
    }

    async loadServiceTypes() {
        try {
            const response = await API.get('/field-service/service-types');
            this.setState({ serviceTypes: response });
        } catch (error) {
            console.error('Error loading service types:', error);
        }
    }

    async loadServiceSchedule() {
        try {
            const response = await API.get('/field-service/service-schedule');
            this.setState({ serviceSchedule: response });
        } catch (error) {
            console.error('Error loading service schedule:', error);
        }
    }

    async loadCommunicationHistory() {
        try {
            const response = await API.get('/field-service/communication-history');
            this.setState({ communicationHistory: response });
        } catch (error) {
            console.error('Error loading communication history:', error);
        }
    }

    async loadCustomerFeedback() {
        try {
            const response = await API.get('/field-service/customer-feedback');
            this.setState({ customerFeedback: response });
        } catch (error) {
            console.error('Error loading customer feedback:', error);
        }
    }

    async loadPartsInventory() {
        try {
            const response = await API.get('/field-service/parts-inventory');
            this.setState({ partsInventory: response });
        } catch (error) {
            console.error('Error loading parts inventory:', error);
        }
    }

    async loadServiceContracts() {
        try {
            const response = await API.get('/field-service/service-contracts');
            this.setState({ serviceContracts: response });
        } catch (error) {
            console.error('Error loading service contracts:', error);
        }
    }

    async loadServiceAnalytics() {
        try {
            const response = await API.get('/field-service/analytics');
            this.setState({ serviceAnalytics: response });
        } catch (error) {
            console.error('Error loading service analytics:', error);
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
            if (this.state.currentView === 'service-calls') {
                this.loadServiceCalls();
            }
        });
    }

    handleServiceCallSelect(serviceCallId, selected) {
        const selectedServiceCalls = selected
            ? [...this.state.selectedServiceCalls, serviceCallId]
            : this.state.selectedServiceCalls.filter(id => id !== serviceCallId);

        this.setState({ selectedServiceCalls });
    }

    handleTechnicianSelect(technicianId, selected) {
        const selectedTechnicians = selected
            ? [...this.state.selectedTechnicians, technicianId]
            : this.state.selectedTechnicians.filter(id => id !== technicianId);

        this.setState({ selectedTechnicians });
    }

    async handleBulkAction(action) {
        if (this.state.selectedServiceCalls.length === 0 && this.state.selectedTechnicians.length === 0) {
            App.showNotification({
                type: 'warning',
                message: 'Please select items first'
            });
            return;
        }

        try {
            switch (action) {
                case 'bulk_update':
                    await this.showBulkUpdateModal();
                    break;
                case 'bulk_assign':
                    await this.showBulkAssignModal();
                    break;
                case 'export_service_calls':
                    await this.exportServiceCalls();
                    break;
                case 'bulk_schedule':
                    await this.showBulkScheduleModal();
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

    async showBulkUpdateModal() {
        // Implementation for bulk update modal
        App.showNotification({
            type: 'info',
            message: 'Bulk update modal coming soon'
        });
    }

    async showBulkAssignModal() {
        // Implementation for bulk assign modal
        App.showNotification({
            type: 'info',
            message: 'Bulk assign modal coming soon'
        });
    }

    async exportServiceCalls() {
        try {
            const response = await API.get('/field-service/export-service-calls', null, 'blob');
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'service_calls_export.csv');
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            App.showNotification({
                type: 'success',
                message: 'Service calls exported successfully'
            });
        } catch (error) {
            console.error('Export failed:', error);
            App.showNotification({
                type: 'error',
                message: 'Export failed'
            });
        }
    }

    async showBulkScheduleModal() {
        // Implementation for bulk schedule modal
        App.showNotification({
            type: 'info',
            message: 'Bulk schedule modal coming soon'
        });
    }

    showServiceCallModal(serviceCall = null) {
        this.setState({
            showServiceCallModal: true,
            editingServiceCall: serviceCall
        });
    }

    hideServiceCallModal() {
        this.setState({
            showServiceCallModal: false,
            editingServiceCall: null
        });
    }

    async saveServiceCall(serviceCallData) {
        try {
            const method = this.state.editingServiceCall ? 'PUT' : 'POST';
            const url = this.state.editingServiceCall
                ? `/field-service/service-calls/${this.state.editingServiceCall.id}`
                : '/field-service/service-calls';

            await API.request(url, method, serviceCallData);

            App.showNotification({
                type: 'success',
                message: `Service call ${this.state.editingServiceCall ? 'updated' : 'created'} successfully`
            });

            this.hideServiceCallModal();
            await this.loadServiceCalls();
        } catch (error) {
            console.error('Error saving service call:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save service call'
            });
        }
    }

    showTechnicianModal(technician = null) {
        this.setState({
            showTechnicianModal: true,
            editingTechnician: technician
        });
    }

    hideTechnicianModal() {
        this.setState({
            showTechnicianModal: false,
            editingTechnician: null
        });
    }

    async saveTechnician(technicianData) {
        try {
            const method = this.state.editingTechnician ? 'PUT' : 'POST';
            const url = this.state.editingTechnician
                ? `/field-service/technicians/${this.state.editingTechnician.id}`
                : '/field-service/technicians';

            await API.request(url, method, technicianData);

            App.showNotification({
                type: 'success',
                message: `Technician ${this.state.editingTechnician ? 'updated' : 'created'} successfully`
            });

            this.hideTechnicianModal();
            await this.loadTechnicians();
        } catch (error) {
            console.error('Error saving technician:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save technician'
            });
        }
    }

    async assignTechnician(serviceCallId, technicianId) {
        try {
            await API.post(`/field-service/service-calls/${serviceCallId}/assign-technician`, {
                technician_id: technicianId
            });

            App.showNotification({
                type: 'success',
                message: 'Technician assigned successfully'
            });

            await this.loadServiceCalls();
        } catch (error) {
            console.error('Error assigning technician:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to assign technician'
            });
        }
    }

    async updateServiceStatus(serviceCallId, status) {
        try {
            await API.post(`/field-service/service-calls/${serviceCallId}/update-status`, {
                status: status
            });

            App.showNotification({
                type: 'success',
                message: 'Service call status updated successfully'
            });

            await this.loadServiceCalls();
        } catch (error) {
            console.error('Error updating service status:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to update service status'
            });
        }
    }

    async sendCommunication(communicationData) {
        try {
            await API.post('/field-service/communication', communicationData);

            App.showNotification({
                type: 'success',
                message: 'Communication sent successfully'
            });

            await this.loadCommunicationHistory();
        } catch (error) {
            console.error('Error sending communication:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to send communication'
            });
        }
    }

    async createPartsOrder(orderData) {
        try {
            await API.post('/field-service/parts-orders', orderData);

            App.showNotification({
                type: 'success',
                message: 'Parts order created successfully'
            });

            await this.loadPartsInventory();
        } catch (error) {
            console.error('Error creating parts order:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create parts order'
            });
        }
    }

    async createServiceContract(contractData) {
        try {
            await API.post('/field-service/service-contracts', contractData);

            App.showNotification({
                type: 'success',
                message: 'Service contract created successfully'
            });

            await this.loadServiceContracts();
        } catch (error) {
            console.error('Error creating service contract:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create service contract'
            });
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'field-service-container' });

        // Header
        const header = DOM.create('div', { className: 'field-service-header' });
        const titleElement = DOM.create('h1', { className: 'field-service-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'field-service-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showServiceCallModal) {
            container.appendChild(this.renderServiceCallModal());
        }

        if (this.state.showTechnicianModal) {
            container.appendChild(this.renderTechnicianModal());
        }

        if (this.state.showCommunicationModal) {
            container.appendChild(this.renderCommunicationModal());
        }

        if (this.state.showPartsModal) {
            container.appendChild(this.renderPartsModal());
        }

        if (this.state.showContractModal) {
            container.appendChild(this.renderContractModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'service-calls', label: 'Service Calls', icon: 'fas fa-tools' },
            { id: 'technician-scheduling', label: 'Technician Scheduling', icon: 'fas fa-calendar-alt' },
            { id: 'customer-communication', label: 'Customer Communication', icon: 'fas fa-comments' },
            { id: 'parts-management', label: 'Parts Management', icon: 'fas fa-cogs' },
            { id: 'service-contracts', label: 'Service Contracts', icon: 'fas fa-file-contract' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'field-service-nav' });
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
            DOM.create('p', {}, 'Loading field service data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'service-calls':
                return this.renderServiceCalls();
            case 'technician-scheduling':
                return this.renderTechnicianScheduling();
            case 'customer-communication':
                return this.renderCustomerCommunication();
            case 'parts-management':
                return this.renderPartsManagement();
            case 'service-contracts':
                return this.renderServiceContracts();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'field-service-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Service status chart
        const statusChart = this.renderServiceStatusChart();
        dashboard.appendChild(statusChart);

        // Technician status
        const technicianStatus = this.renderTechnicianStatus();
        dashboard.appendChild(technicianStatus);

        // Upcoming appointments
        const upcomingAppointments = this.renderUpcomingAppointments();
        dashboard.appendChild(upcomingAppointments);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview;
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Service Calls',
                value: overview.total_service_calls || 0,
                icon: 'fas fa-tools',
                color: 'primary'
            },
            {
                title: 'Open Service Calls',
                value: overview.open_service_calls || 0,
                icon: 'fas fa-clock',
                color: 'warning'
            },
            {
                title: 'Active Technicians',
                value: overview.active_technicians || 0,
                icon: 'fas fa-users',
                color: 'success'
            },
            {
                title: 'Customer Satisfaction',
                value: `${overview.customer_satisfaction_rate || 0}%`,
                icon: 'fas fa-star',
                color: 'info'
            },
            {
                title: 'Upcoming Appointments',
                value: overview.upcoming_appointments || 0,
                icon: 'fas fa-calendar-check',
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

    renderServiceStatusChart() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Service Call Status Distribution'));

        const chartContainer = DOM.create('div', { className: 'chart-container' });
        chartContainer.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Service status chart coming soon...'));
        section.appendChild(chartContainer);

        return section;
    }

    renderTechnicianStatus() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Technician Status'));

        if (this.state.technicians.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No technicians available'));
        } else {
            const technicianList = DOM.create('div', { className: 'technician-status-list' });
            this.state.technicians.slice(0, 5).forEach(technician => {
                const technicianItem = DOM.create('div', { className: 'technician-status-item' });
                technicianItem.appendChild(DOM.create('div', { className: 'technician-info' },
                    DOM.create('strong', {}, `${technician.first_name} ${technician.last_name}`),
                    DOM.create('span', {}, `${technician.active_assignments} active assignments`)
                ));
                technicianItem.appendChild(DOM.create('div', { className: 'technician-rating' },
                    DOM.create('span', { className: 'rating' }, `Rating: ${technician.avg_rating || 'N/A'}`)
                ));
                technicianList.appendChild(technicianItem);
            });
            section.appendChild(technicianList);
        }

        return section;
    }

    renderUpcomingAppointments() {
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Upcoming Appointments'));

        const appointments = this.state.overview.upcoming_appointments || [];
        if (appointments.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No upcoming appointments'));
        } else {
            const appointmentList = DOM.create('ul', { className: 'appointment-list' });
            appointments.slice(0, 5).forEach(appointment => {
                const listItem = DOM.create('li', { className: 'appointment-item' });
                listItem.appendChild(DOM.create('div', { className: 'appointment-info' },
                    DOM.create('strong', {}, appointment.service_number),
                    DOM.create('span', {}, `${appointment.customer_name} - ${this.formatDate(appointment.scheduled_date)}`)
                ));
                appointmentList.appendChild(listItem);
            });
            section.appendChild(appointmentList);
        }

        return section;
    }

    renderServiceCalls() {
        const serviceCallsView = DOM.create('div', { className: 'service-calls-view' });

        // Toolbar
        const toolbar = this.renderServiceCallsToolbar();
        serviceCallsView.appendChild(toolbar);

        // Filters
        const filters = this.renderServiceCallsFilters();
        serviceCallsView.appendChild(filters);

        // Service calls table
        const table = this.renderServiceCallsTable();
        serviceCallsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        serviceCallsView.appendChild(pagination);

        return serviceCallsView;
    }

    renderServiceCallsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedServiceCalls.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedServiceCalls.length} selected`
            ));

            const actions = ['bulk_update', 'bulk_assign', 'export_service_calls'];
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
            onclick: () => this.showServiceCallModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Service Call';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderServiceCallsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search service calls...',
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
        const statuses = ['', 'open', 'assigned', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
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
        const priorities = ['', 'low', 'medium', 'high', 'urgent', 'emergency'];
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

    renderServiceCallsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'service_number', label: 'Service Number' },
            { key: 'customer', label: 'Customer' },
            { key: 'service_type', label: 'Service Type' },
            { key: 'technician', label: 'Technician' },
            { key: 'scheduled_date', label: 'Scheduled Date' },
            { key: 'priority', label: 'Priority' },
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

        this.state.serviceCalls.forEach(serviceCall => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedServiceCalls.includes(serviceCall.id),
                onchange: (e) => this.handleServiceCallSelect(serviceCall.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Service Number
            row.appendChild(DOM.create('td', {}, serviceCall.service_number));

            // Customer
            row.appendChild(DOM.create('td', {}, serviceCall.customer_name || 'N/A'));

            // Service Type
            row.appendChild(DOM.create('td', {}, serviceCall.service_name || 'N/A'));

            // Technician
            row.appendChild(DOM.create('td', {},
                serviceCall.technician_first ? `${serviceCall.technician_first} ${serviceCall.technician_last}` : 'Unassigned'
            ));

            // Scheduled Date
            const scheduledCell = DOM.create('td', {});
            if (serviceCall.scheduled_date) {
                const daysUntil = serviceCall.days_until_scheduled;
                const dateText = this.formatDate(serviceCall.scheduled_date);
                const dateClass = daysUntil < 0 ? 'overdue' : daysUntil <= 1 ? 'due-soon' : 'normal';
                scheduledCell.appendChild(DOM.create('span', { className: `date ${dateClass}` }, dateText));
            } else {
                scheduledCell.appendChild(DOM.create('span', {}, 'Not scheduled'));
            }
            row.appendChild(scheduledCell);

            // Priority
            const priorityCell = DOM.create('td', {});
            const priorityBadge = DOM.create('span', {
                className: `priority-badge ${serviceCall.priority}`
            }, serviceCall.priority);
            priorityCell.appendChild(priorityBadge);
            row.appendChild(priorityCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${serviceCall.status}`
            }, serviceCall.status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewServiceCall(serviceCall)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showServiceCallModal(serviceCall)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            if (!serviceCall.technician_id) {
                const assignButton = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-success',
                    onclick: () => this.showAssignTechnicianModal(serviceCall)
                });
                assignButton.innerHTML = '<i class="fas fa-user-plus"></i>';
                actions.appendChild(assignButton);
            }

            const statusButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showUpdateStatusModal(serviceCall)
            });
            statusButton.innerHTML = '<i class="fas fa-play-circle"></i>';
            actions.appendChild(statusButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderTechnicianScheduling() {
        const schedulingView = DOM.create('div', { className: 'technician-scheduling-view' });

        // Technician workload
        const workload = this.renderTechnicianWorkload();
        schedulingView.appendChild(workload);

        // Service schedule
        const schedule = this.renderServiceSchedule();
        schedulingView.appendChild(schedule);

        // Route optimization
        const routes = this.renderRouteOptimization();
        schedulingView.appendChild(routes);

        return schedulingView;
    }

    renderTechnicianWorkload() {
        const section = DOM.create('div', { className: 'scheduling-section' });
        section.appendChild(DOM.create('h3', {}, 'Technician Workload Distribution'));

        if (this.state.technicians.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No technicians available'));
        } else {
            const workloadList = DOM.create('div', { className: 'workload-list' });
            this.state.technicians.forEach(technician => {
                const workloadItem = DOM.create('div', { className: 'workload-item' });
                workloadItem.appendChild(DOM.create('div', { className: 'technician-name' },
                    DOM.create('strong', {}, `${technician.first_name} ${technician.last_name}`)
                ));
                workloadItem.appendChild(DOM.create('div', { className: 'workload-stats' },
                    DOM.create('span', {}, `${technician.active_assignments} active assignments`),
                    DOM.create('span', {}, `${technician.total_estimated_hours}h estimated`)
                ));
                workloadItem.appendChild(DOM.create('div', { className: 'workload-efficiency' },
                    DOM.create('span', {}, `Efficiency: ${technician.efficiency_percentage}%`)
                ));
                workloadList.appendChild(workloadItem);
            });
            section.appendChild(workloadList);
        }

        return section;
    }

    renderServiceSchedule() {
        const section = DOM.create('div', { className: 'scheduling-section' });
        section.appendChild(DOM.create('h3', {}, 'Service Schedule'));

        if (this.state.serviceSchedule.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No scheduled services'));
        } else {
            const scheduleTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Service Number', 'Customer', 'Technician', 'Date', 'Time', 'Priority'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.serviceSchedule.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, item.service_number));
                row.appendChild(DOM.create('td', {}, item.customer_name));
                row.appendChild(DOM.create('td', {}, `${item.technician_first} ${item.technician_last}`));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.scheduled_date)));
                row.appendChild(DOM.create('td', {}, item.scheduled_time));
                const priorityCell = DOM.create('td', {});
                const priorityBadge = DOM.create('span', {
                    className: `priority-badge ${item.priority}`
                }, item.priority);
                priorityCell.appendChild(priorityBadge);
                row.appendChild(priorityCell);
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            scheduleTable.appendChild(table);
            section.appendChild(scheduleTable);
        }

        return section;
    }

    renderRouteOptimization() {
        const section = DOM.create('div', { className: 'scheduling-section' });
        section.appendChild(DOM.create('h3', {}, 'Route Optimization'));
        section.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Route optimization interface coming soon...'));
        return section;
    }

    renderCustomerCommunication() {
        const communicationView = DOM.create('div', { className: 'customer-communication-view' });

        // Communication history
        const history = this.renderCommunicationHistory();
        communicationView.appendChild(history);

        // Customer feedback
        const feedback = this.renderCustomerFeedback();
        communicationView.appendChild(feedback);

        return communicationView;
    }

    renderCommunicationHistory() {
        const section = DOM.create('div', { className: 'communication-section' });
        section.appendChild(DOM.create('h3', {}, 'Communication History'));

        if (this.state.communicationHistory.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No communication history'));
        } else {
            const historyTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Customer', 'Type', 'Subject', 'Sent At', 'Status'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            this.state.communicationHistory.slice(0, 10).forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, item.customer_name));
                row.appendChild(DOM.create('td', {}, item.communication_type));
                row.appendChild(DOM.create('td', {}, item.subject));
                row.appendChild(DOM.create('td', {}, this.formatDate(item.sent_at)));
                row.appendChild(DOM.create('td', {}, item.delivery_status || 'Sent'));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            historyTable.appendChild(table);
            section.appendChild(historyTable);
        }

        return section;
    }

    renderCustomerFeedback() {
        const section = DOM.create('div', { className: 'communication-section' });
        section.appendChild(DOM.create('h3', {}, 'Customer Feedback'));

        if (this.state.customerFeedback.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No customer feedback'));
        } else {
            const feedbackList = DOM.create('div', { className: 'feedback-list' });
            this.state.customerFeedback.slice(0, 5).forEach(feedback => {
                const feedbackItem = DOM.create('div', { className: 'feedback-item' });
                feedbackItem.appendChild(DOM.create('div', { className: 'feedback-header' },
                    DOM.create('strong', {}, feedback.customer_name),
                    DOM.create('span', {}, `Rating: ${feedback.rating}/5`)
                ));
                feedbackItem.appendChild(DOM.create('div', { className: 'feedback-content' },
                    DOM.create('p', {}, feedback.feedback_text)
                ));
                feedbackItem.appendChild(DOM.create('div', { className: 'feedback-date' },
                    this.formatDate(feedback.feedback_date)
                ));
                feedbackList.appendChild(feedbackItem);
            });
            section.appendChild(feedbackList);
        }

        return section;
    }

    renderPartsManagement() {
        const partsView = DOM.create('div', { className: 'parts-management-view' });
        partsView.appendChild(DOM.create('h3', {}, 'Parts Management'));
        partsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Parts management interface coming soon...'));
        return partsView;
    }

    renderServiceContracts() {
        const contractsView = DOM.create('div', { className: 'service-contracts-view' });
        contractsView.appendChild(DOM.create('h3', {}, 'Service Contracts'));
        contractsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Service contracts interface coming soon...'));
        return contractsView;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'Field Service Analytics'));
        analyticsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Field service analytics interface coming soon...'));
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
            if (this.state.currentView === 'service-calls') {
                this.loadServiceCalls();
            }
        });
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderServiceCallModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingServiceCall ? 'Edit Service Call' : 'Create Service Call'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideServiceCallModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Service call modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderTechnicianModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingTechnician ? 'Edit Technician' : 'Add Technician'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideTechnicianModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Technician modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderCommunicationModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Send Communication'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCommunicationModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Communication modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderPartsModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Parts Order'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hidePartsModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Parts order modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderContractModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Service Contract'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideContractModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Service contract modal coming soon...'));
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

    hideCommunicationModal() {
        this.setState({ showCommunicationModal: false });
    }

    hidePartsModal() {
        this.setState({ showPartsModal: false });
    }

    hideContractModal() {
        this.setState({ showContractModal: false });
    }

    viewServiceCall(serviceCall) {
        // Implementation for viewing service call details
        App.showNotification({
            type: 'info',
            message: 'Service call details view coming soon'
        });
    }

    showAssignTechnicianModal(serviceCall) {
        // Implementation for assign technician modal
        App.showNotification({
            type: 'info',
            message: 'Assign technician modal coming soon'
        });
    }

    showUpdateStatusModal(serviceCall) {
        // Implementation for update status modal
        App.showNotification({
            type: 'info',
            message: 'Update status modal coming soon'
        });
    }
}

// Export the component
window.FieldService = FieldService;
