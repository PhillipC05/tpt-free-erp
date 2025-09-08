/**
 * TPT Free ERP - Quality Management Component
 * Complete quality control, audit management, and compliance system interface
 */

class QualityManagement extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Quality Management',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            qualityChecks: [],
            audits: [],
            nonConformances: [],
            capaRecords: [],
            isoCompliance: {},
            qualityStandards: {},
            spcData: {},
            analytics: {},
            filters: {
                status: '',
                category_id: '',
                severity: '',
                reported_by: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedChecks: [],
            selectedAudits: [],
            selectedNCs: [],
            selectedCAPAs: [],
            showCheckModal: false,
            showAuditModal: false,
            showNCModal: false,
            showCAPAModal: false,
            showFindingModal: false,
            showRCAModal: false,
            editingCheck: null,
            editingAudit: null,
            editingNC: null,
            editingCAPA: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            }
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadQualityChecks = this.loadQualityChecks.bind(this);
        this.loadAudits = this.loadAudits.bind(this);
        this.loadNonConformances = this.loadNonConformances.bind(this);
        this.loadCAPA = this.loadCAPA.bind(this);
        this.loadISOCompliance = this.loadISOCompliance.bind(this);
        this.loadQualityStandards = this.loadQualityStandards.bind(this);
        this.loadSPC = this.loadSPC.bind(this);
        this.loadAnalytics = this.loadAnalytics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleCheckSelect = this.handleCheckSelect.bind(this);
        this.handleAuditSelect = this.handleAuditSelect.bind(this);
        this.handleNCSelect = this.handleNCSelect.bind(this);
        this.handleCAPASelect = this.handleCAPASelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showCheckModal = this.showCheckModal.bind(this);
        this.hideCheckModal = this.hideCheckModal.bind(this);
        this.saveQualityCheck = this.saveQualityCheck.bind(this);
        this.showAuditModal = this.showAuditModal.bind(this);
        this.hideAuditModal = this.hideAuditModal.bind(this);
        this.saveAudit = this.saveAudit.bind(this);
        this.showNCModal = this.showNCModal.bind(this);
        this.hideNCModal = this.hideNCModal.bind(this);
        this.saveNonConformance = this.saveNonConformance.bind(this);
        this.showCAPAModal = this.showCAPAModal.bind(this);
        this.hideCAPAModal = this.hideCAPAModal.bind(this);
        this.saveCAPA = this.saveCAPA.bind(this);
        this.createAuditFinding = this.createAuditFinding.bind(this);
        this.createRootCauseAnalysis = this.createRootCauseAnalysis.bind(this);
        this.createContainmentAction = this.createContainmentAction.bind(this);
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
            console.error('Error loading quality management data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load quality management data'
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
            case 'quality-control':
                await this.loadQualityChecks();
                break;
            case 'audit-management':
                await this.loadAudits();
                break;
            case 'non-conformance':
                await this.loadNonConformances();
                break;
            case 'capa':
                await this.loadCAPA();
                break;
            case 'iso-compliance':
                await this.loadISOCompliance();
                break;
            case 'quality-standards':
                await this.loadQualityStandards();
                break;
            case 'statistical-process':
                await this.loadSPC();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/quality-management/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading quality overview:', error);
        }
    }

    async loadQualityChecks() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/quality-management/quality-checks?${params}`);
            this.setState({
                qualityChecks: response.quality_checks,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading quality checks:', error);
        }
    }

    async loadAudits() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/quality-management/audits?${params}`);
            this.setState({
                audits: response.audits,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading audits:', error);
        }
    }

    async loadNonConformances() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/quality-management/non-conformances?${params}`);
            this.setState({
                nonConformances: response.non_conformances,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading non-conformances:', error);
        }
    }

    async loadCAPA() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/quality-management/capa?${params}`);
            this.setState({
                capaRecords: response.capa_records,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading CAPA records:', error);
        }
    }

    async loadISOCompliance() {
        try {
            const response = await API.get('/quality-management/iso-compliance');
            this.setState({ isoCompliance: response });
        } catch (error) {
            console.error('Error loading ISO compliance:', error);
        }
    }

    async loadQualityStandards() {
        try {
            const response = await API.get('/quality-management/standards');
            this.setState({ qualityStandards: response });
        } catch (error) {
            console.error('Error loading quality standards:', error);
        }
    }

    async loadSPC() {
        try {
            const response = await API.get('/quality-management/spc');
            this.setState({ spcData: response });
        } catch (error) {
            console.error('Error loading SPC data:', error);
        }
    }

    async loadAnalytics() {
        try {
            const response = await API.get('/quality-management/analytics');
            this.setState({ analytics: response });
        } catch (error) {
            console.error('Error loading quality analytics:', error);
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
            if (this.state.currentView === 'quality-control') {
                this.loadQualityChecks();
            } else if (this.state.currentView === 'audit-management') {
                this.loadAudits();
            } else if (this.state.currentView === 'non-conformance') {
                this.loadNonConformances();
            } else if (this.state.currentView === 'capa') {
                this.loadCAPA();
            }
        });
    }

    handleCheckSelect(checkId, selected) {
        const selectedChecks = selected
            ? [...this.state.selectedChecks, checkId]
            : this.state.selectedChecks.filter(id => id !== checkId);

        this.setState({ selectedChecks });
    }

    handleAuditSelect(auditId, selected) {
        const selectedAudits = selected
            ? [...this.state.selectedAudits, auditId]
            : this.state.selectedAudits.filter(id => id !== auditId);

        this.setState({ selectedAudits });
    }

    handleNCSelect(ncId, selected) {
        const selectedNCs = selected
            ? [...this.state.selectedNCs, ncId]
            : this.state.selectedNCs.filter(id => id !== ncId);

        this.setState({ selectedNCs });
    }

    handleCAPASelect(capaId, selected) {
        const selectedCAPAs = selected
            ? [...this.state.selectedCAPAs, capaId]
            : this.state.selectedCAPAs.filter(id => id !== capaId);

        this.setState({ selectedCAPAs });
    }

    async handleBulkAction(action) {
        if (this.state.selectedChecks.length === 0 &&
            this.state.selectedAudits.length === 0 &&
            this.state.selectedNCs.length === 0 &&
            this.state.selectedCAPAs.length === 0) {
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
                case 'export_selected':
                    await this.exportSelected();
                    break;
                case 'delete_selected':
                    await this.deleteSelected();
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

    showCheckModal(check = null) {
        this.setState({
            showCheckModal: true,
            editingCheck: check
        });
    }

    hideCheckModal() {
        this.setState({
            showCheckModal: false,
            editingCheck: null
        });
    }

    async saveQualityCheck(checkData) {
        try {
            await API.post('/quality-management/quality-checks', checkData);
            App.showNotification({
                type: 'success',
                message: 'Quality check created successfully'
            });
            this.hideCheckModal();
            await this.loadQualityChecks();
        } catch (error) {
            console.error('Error saving quality check:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save quality check'
            });
        }
    }

    showAuditModal(audit = null) {
        this.setState({
            showAuditModal: true,
            editingAudit: audit
        });
    }

    hideAuditModal() {
        this.setState({
            showAuditModal: false,
            editingAudit: null
        });
    }

    async saveAudit(auditData) {
        try {
            await API.post('/quality-management/audits', auditData);
            App.showNotification({
                type: 'success',
                message: 'Audit created successfully'
            });
            this.hideAuditModal();
            await this.loadAudits();
        } catch (error) {
            console.error('Error saving audit:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save audit'
            });
        }
    }

    showNCModal(nc = null) {
        this.setState({
            showNCModal: true,
            editingNC: nc
        });
    }

    hideNCModal() {
        this.setState({
            showNCModal: false,
            editingNC: null
        });
    }

    async saveNonConformance(ncData) {
        try {
            await API.post('/quality-management/non-conformances', ncData);
            App.showNotification({
                type: 'success',
                message: 'Non-conformance created successfully'
            });
            this.hideNCModal();
            await this.loadNonConformances();
        } catch (error) {
            console.error('Error saving non-conformance:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save non-conformance'
            });
        }
    }

    showCAPAModal(capa = null) {
        this.setState({
            showCAPAModal: true,
            editingCAPA: capa
        });
    }

    hideCAPAModal() {
        this.setState({
            showCAPAModal: false,
            editingCAPA: null
        });
    }

    async saveCAPA(capaData) {
        try {
            await API.post('/quality-management/capa', capaData);
            App.showNotification({
                type: 'success',
                message: 'CAPA record created successfully'
            });
            this.hideCAPAModal();
            await this.loadCAPA();
        } catch (error) {
            console.error('Error saving CAPA:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save CAPA record'
            });
        }
    }

    async createAuditFinding(auditId, findingData) {
        try {
            await API.post(`/quality-management/audits/${auditId}/findings`, findingData);
            App.showNotification({
                type: 'success',
                message: 'Audit finding created successfully'
            });
        } catch (error) {
            console.error('Error creating audit finding:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create audit finding'
            });
        }
    }

    async createRootCauseAnalysis(ncId, rcaData) {
        try {
            await API.post(`/quality-management/non-conformances/${ncId}/root-cause`, rcaData);
            App.showNotification({
                type: 'success',
                message: 'Root cause analysis created successfully'
            });
        } catch (error) {
            console.error('Error creating root cause analysis:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create root cause analysis'
            });
        }
    }

    async createContainmentAction(ncId, actionData) {
        try {
            await API.post(`/quality-management/non-conformances/${ncId}/containment`, actionData);
            App.showNotification({
                type: 'success',
                message: 'Containment action created successfully'
            });
        } catch (error) {
            console.error('Error creating containment action:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to create containment action'
            });
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'quality-management-container' });

        // Header
        const header = DOM.create('div', { className: 'quality-management-header' });
        const titleElement = DOM.create('h1', { className: 'quality-management-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'quality-management-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showCheckModal) {
            container.appendChild(this.renderCheckModal());
        }

        if (this.state.showAuditModal) {
            container.appendChild(this.renderAuditModal());
        }

        if (this.state.showNCModal) {
            container.appendChild(this.renderNCModal());
        }

        if (this.state.showCAPAModal) {
            container.appendChild(this.renderCAPAModal());
        }

        if (this.state.showFindingModal) {
            container.appendChild(this.renderFindingModal());
        }

        if (this.state.showRCAModal) {
            container.appendChild(this.renderRCAModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'quality-control', label: 'Quality Control', icon: 'fas fa-clipboard-check' },
            { id: 'audit-management', label: 'Audit Management', icon: 'fas fa-search' },
            { id: 'non-conformance', label: 'Non-Conformance', icon: 'fas fa-exclamation-triangle' },
            { id: 'capa', label: 'CAPA', icon: 'fas fa-tools' },
            { id: 'iso-compliance', label: 'ISO Compliance', icon: 'fas fa-certificate' },
            { id: 'quality-standards', label: 'Quality Standards', icon: 'fas fa-book' },
            { id: 'statistical-process', label: 'Statistical Process', icon: 'fas fa-chart-line' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'quality-management-nav' });
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
            DOM.create('p', {}, 'Loading quality management data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'quality-control':
                return this.renderQualityControl();
            case 'audit-management':
                return this.renderAuditManagement();
            case 'non-conformance':
                return this.renderNonConformance();
            case 'capa':
                return this.renderCAPA();
            case 'iso-compliance':
                return this.renderISOCompliance();
            case 'quality-standards':
                return this.renderQualityStandards();
            case 'statistical-process':
                return this.renderStatisticalProcess();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'quality-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Quality metrics
        const qualityMetrics = this.renderQualityMetrics();
        dashboard.appendChild(qualityMetrics);

        // Recent alerts
        const recentAlerts = this.renderRecentAlerts();
        dashboard.appendChild(recentAlerts);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.quality_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Quality Checks',
                value: overview.total_quality_checks || 0,
                icon: 'fas fa-clipboard-check',
                color: 'primary'
            },
            {
                title: 'Quality Rate',
                value: `${overview.quality_rate || 0}%`,
                icon: 'fas fa-percentage',
                color: 'success'
            },
            {
                title: 'Non-Conformances',
                value: overview.total_non_conformances || 0,
                icon: 'fas fa-exclamation-triangle',
                color: 'warning'
            },
            {
                title: 'Active CAPA',
                value: overview.total_capa || 0,
                icon: 'fas fa-tools',
                color: 'info'
            },
            {
                title: 'Scheduled Audits',
                value: overview.scheduled_audits || 0,
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

    renderQualityMetrics() {
        const metrics = this.state.overview.quality_metrics || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Quality Metrics Trend'));

        if (metrics.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No quality metrics available'));
        } else {
            const metricsChart = DOM.create('div', { className: 'metrics-chart' });
            metricsChart.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Quality metrics chart coming soon...'));
            section.appendChild(metricsChart);
        }

        return section;
    }

    renderRecentAlerts() {
        const alerts = this.state.overview.quality_alerts || [];
        const section = DOM.create('div', { className: 'dashboard-section' });
        section.appendChild(DOM.create('h3', {}, 'Recent Quality Alerts'));

        if (alerts.length === 0) {
            section.appendChild(DOM.create('p', { className: 'no-data' }, 'No recent alerts'));
        } else {
            const alertsList = DOM.create('ul', { className: 'alerts-list' });
            alerts.slice(0, 5).forEach(alert => {
                const listItem = DOM.create('li', { className: 'alert-item' });
                listItem.appendChild(DOM.create('div', { className: 'alert-message' }, alert.message));
                listItem.appendChild(DOM.create('div', { className: 'alert-meta' },
                    `${this.formatTimeAgo(alert.triggered_at)} • ${alert.severity}`
                ));
                listItem.classList.add(alert.severity.toLowerCase());
                alertsList.appendChild(listItem);
            });
            section.appendChild(alertsList);
        }

        return section;
    }

    renderQualityControl() {
        const qcView = DOM.create('div', { className: 'quality-control-view' });

        // Toolbar
        const toolbar = this.renderQCToolbar();
        qcView.appendChild(toolbar);

        // Filters
        const filters = this.renderQCFilters();
        qcView.appendChild(filters);

        // Quality checks table
        const table = this.renderQualityChecksTable();
        qcView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        qcView.appendChild(pagination);

        return qcView;
    }

    renderQCToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedChecks.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedChecks.length} selected`
            ));

            const actions = ['update_status', 'export_selected'];
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
            onclick: () => this.showCheckModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Quality Check';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderQCFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search quality checks...',
            value: this.state.filters.search,
            oninput: (e) => this.handleFilterChange('search', e.target.value)
        });
        searchGroup.appendChild(DOM.create('label', {}, 'Search:'));
        searchGroup.appendChild(searchInput);
        filters.appendChild(searchGroup);

        // Result filter
        const resultGroup = DOM.create('div', { className: 'filter-group' });
        const resultSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.status,
            onchange: (e) => this.handleFilterChange('status', e.target.value)
        });
        const results = ['', 'pass', 'fail', 'pending'];
        results.forEach(result => {
            resultSelect.appendChild(DOM.create('option', { value: result },
                result === '' ? 'All Results' : result.charAt(0).toUpperCase() + result.slice(1)
            ));
        });
        resultGroup.appendChild(DOM.create('label', {}, 'Result:'));
        resultGroup.appendChild(resultSelect);
        filters.appendChild(resultGroup);

        return filters;
    }

    renderQualityChecksTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'criteria_name', label: 'Inspection Criteria' },
            { key: 'inspector', label: 'Inspector' },
            { key: 'check_date', label: 'Check Date' },
            { key: 'result', label: 'Result' },
            { key: 'actual_value', label: 'Actual Value' },
            { key: 'defect_rate', label: 'Defect Rate' },
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

        this.state.qualityChecks.forEach(check => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedChecks.includes(check.id),
                onchange: (e) => this.handleCheckSelect(check.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Criteria Name
            row.appendChild(DOM.create('td', {}, check.criteria_name || 'N/A'));

            // Inspector
            row.appendChild(DOM.create('td', {},
                check.inspector_first ? `${check.inspector_first} ${check.inspector_last}` : 'N/A'
            ));

            // Check Date
            row.appendChild(DOM.create('td', {}, this.formatDate(check.check_date)));

            // Result
            const resultCell = DOM.create('td', {});
            const resultBadge = DOM.create('span', {
                className: `status-badge ${check.result}`
            }, check.result || 'pending');
            resultCell.appendChild(resultBadge);
            row.appendChild(resultCell);

            // Actual Value
            row.appendChild(DOM.create('td', {}, check.actual_value || 'N/A'));

            // Defect Rate
            row.appendChild(DOM.create('td', {}, check.defect_rate ? `${check.defect_rate}%` : 'N/A'));

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showCheckModal(check)
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

    renderAuditManagement() {
        const auditView = DOM.create('div', { className: 'audit-management-view' });

        // Toolbar
        const toolbar = this.renderAuditToolbar();
        auditView.appendChild(toolbar);

        // Filters
        const filters = this.renderAuditFilters();
        auditView.appendChild(filters);

        // Audits table
        const table = this.renderAuditsTable();
        auditView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        auditView.appendChild(pagination);

        return auditView;
    }

    renderAuditToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedAudits.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedAudits.length} selected`
            ));

            const actions = ['update_status', 'export_selected'];
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
            onclick: () => this.showAuditModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Audit';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderAuditFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search audits...',
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
        const statuses = ['', 'planned', 'scheduled', 'in_progress', 'completed', 'cancelled'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        return filters;
    }

    renderAuditsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'audit_title', label: 'Audit Title' },
            { key: 'audit_type_name', label: 'Audit Type' },
            { key: 'lead_auditor', label: 'Lead Auditor' },
            { key: 'scheduled_date', label: 'Scheduled Date' },
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

        this.state.audits.forEach(audit => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedAudits.includes(audit.id),
                onchange: (e) => this.handleAuditSelect(audit.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Audit Title
            row.appendChild(DOM.create('td', {}, audit.audit_title));

            // Audit Type
            row.appendChild(DOM.create('td', {}, audit.audit_type_name || 'N/A'));

            // Lead Auditor
            row.appendChild(DOM.create('td', {},
                audit.lead_auditor_first ? `${audit.lead_auditor_first} ${audit.lead_auditor_last}` : 'N/A'
            ));

            // Scheduled Date
            row.appendChild(DOM.create('td', {}, this.formatDate(audit.scheduled_date)));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${audit.status}`
            }, audit.status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewAudit(audit)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showAuditModal(audit)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const findingsButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showFindingModal(audit)
            });
            findingsButton.innerHTML = '<i class="fas fa-search"></i>';
            actions.appendChild(findingsButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderNonConformance() {
        const ncView = DOM.create('div', { className: 'non-conformance-view' });

        // Toolbar
        const toolbar = this.renderNCToolbar();
        ncView.appendChild(toolbar);

        // Filters
        const filters = this.renderNCFilters();
        ncView.appendChild(filters);

        // Non-conformances table
        const table = this.renderNCTable();
        ncView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        ncView.appendChild(pagination);

        return ncView;
    }

    renderNCToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedNCs.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedNCs.length} selected`
            ));

            const actions = ['update_status', 'export_selected'];
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
            onclick: () => this.showNCModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Report Non-Conformance';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderNCFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search non-conformances...',
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
        const statuses = ['', 'open', 'investigating', 'resolved', 'closed'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Severity filter
        const severityGroup = DOM.create('div', { className: 'filter-group' });
        const severitySelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.severity,
            onchange: (e) => this.handleFilterChange('severity', e.target.value)
        });
        const severities = ['', 'minor', 'major', 'critical'];
        severities.forEach(severity => {
            severitySelect.appendChild(DOM.create('option', { value: severity },
                severity === '' ? 'All Severities' : severity.charAt(0).toUpperCase() + severity.slice(1)
            ));
        });
        severityGroup.appendChild(DOM.create('label', {}, 'Severity:'));
        severityGroup.appendChild(severitySelect);
        filters.appendChild(severityGroup);

        return filters;
    }

    renderNCTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'description', label: 'Description' },
            { key: 'category', label: 'Category' },
            { key: 'severity', label: 'Severity' },
            { key: 'reported_by', label: 'Reported By' },
            { key: 'reported_date', label: 'Reported Date' },
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

        this.state.nonConformances.forEach(nc => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedNCs.includes(nc.id),
                onchange: (e) => this.handleNCSelect(nc.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Description
            row.appendChild(DOM.create('td', {}, nc.description));

            // Category
            row.appendChild(DOM.create('td', {}, nc.category_name || 'N/A'));

            // Severity
            const severityCell = DOM.create('td', {});
            const severityBadge = DOM.create('span', {
                className: `severity-badge ${nc.severity}`
            }, nc.severity);
            severityCell.appendChild(severityBadge);
            row.appendChild(severityCell);

            // Reported By
            row.appendChild(DOM.create('td', {},
                nc.reported_by_first ? `${nc.reported_by_first} ${nc.reported_by_last}` : 'N/A'
            ));

            // Reported Date
            row.appendChild(DOM.create('td', {}, this.formatDate(nc.reported_date)));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${nc.status}`
            }, nc.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewNC(nc)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const rcaButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showRCAModal(nc)
            });
            rcaButton.innerHTML = '<i class="fas fa-search"></i>';
            actions.appendChild(rcaButton);

            const containmentButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showContainmentModal(nc)
            });
            containmentButton.innerHTML = '<i class="fas fa-shield-alt"></i>';
            actions.appendChild(containmentButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderCAPA() {
        const capaView = DOM.create('div', { className: 'capa-view' });

        // Toolbar
        const toolbar = this.renderCAPAToolbar();
        capaView.appendChild(toolbar);

        // Filters
        const filters = this.renderCAPAFilters();
        capaView.appendChild(filters);

        // CAPA table
        const table = this.renderCAPATable();
        capaView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        capaView.appendChild(pagination);

        return capaView;
    }

    renderCAPAToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedCAPAs.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedCAPAs.length} selected`
            ));

            const actions = ['update_status', 'export_selected'];
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
            onclick: () => this.showCAPAModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create CAPA';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderCAPAFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search CAPA records...',
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
        const statuses = ['', 'open', 'in_progress', 'completed', 'cancelled'];
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

    renderCAPATable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'description', label: 'Description' },
            { key: 'capa_type', label: 'Type' },
            { key: 'priority', label: 'Priority' },
            { key: 'status', label: 'Status' },
            { key: 'progress', label: 'Progress' },
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

        this.state.capaRecords.forEach(capa => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedCAPAs.includes(capa.id),
                onchange: (e) => this.handleCAPASelect(capa.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Description
            row.appendChild(DOM.create('td', {}, capa.description));

            // Type
            row.appendChild(DOM.create('td', {}, capa.capa_type));

            // Priority
            const priorityCell = DOM.create('td', {});
            const priorityBadge = DOM.create('span', {
                className: `priority-badge ${capa.priority}`
            }, capa.priority);
            priorityCell.appendChild(priorityBadge);
            row.appendChild(priorityCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${capa.status}`
            }, capa.status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Progress
            const progressCell = DOM.create('td', {});
            const progressBar = DOM.create('div', { className: 'progress-bar small' });
            const progressFill = DOM.create('div', {
                className: 'progress-fill',
                style: `width: ${capa.progress_percentage}%`
            });
            progressFill.appendChild(DOM.create('span', { className: 'progress-text' },
                `${capa.progress_percentage}%`
            ));
            progressBar.appendChild(progressFill);
            progressCell.appendChild(progressBar);
            row.appendChild(progressCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showCAPAModal(capa)
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

    renderISOCompliance() {
        const isoView = DOM.create('div', { className: 'iso-compliance-view' });
        isoView.appendChild(DOM.create('h3', {}, 'ISO Compliance'));
        isoView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'ISO compliance interface coming soon...'));
        return isoView;
    }

    renderQualityStandards() {
        const standardsView = DOM.create('div', { className: 'quality-standards-view' });
        standardsView.appendChild(DOM.create('h3', {}, 'Quality Standards'));
        standardsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Quality standards interface coming soon...'));
        return standardsView;
    }

    renderStatisticalProcess() {
        const spcView = DOM.create('div', { className: 'statistical-process-view' });
        spcView.appendChild(DOM.create('h3', {}, 'Statistical Process Control'));
        spcView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'SPC interface coming soon...'));
        return spcView;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'Quality Analytics'));
        analyticsView.appendChild(DOM.create('p', { className: 'coming-soon' }, 'Quality analytics interface coming soon...'));
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
            if (this.state.currentView === 'quality-control') {
                this.loadQualityChecks();
            } else if (this.state.currentView === 'audit-management') {
                this.loadAudits();
            } else if (this.state.currentView === 'non-conformance') {
                this.loadNonConformances();
            } else if (this.state.currentView === 'capa') {
                this.loadCAPA();
            }
        });
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderCheckModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingCheck ? 'Edit Quality Check' : 'Create Quality Check'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCheckModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Quality check modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderAuditModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingAudit ? 'Edit Audit' : 'Create Audit'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideAuditModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Audit modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderNCModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingNC ? 'Edit Non-Conformance' : 'Report Non-Conformance'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideNCModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Non-conformance modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderCAPAModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingCAPA ? 'Edit CAPA' : 'Create CAPA'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideCAPAModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'CAPA modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderFindingModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Create Audit Finding'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideFindingModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Audit finding modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderRCAModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Root Cause Analysis'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideRCAModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Root cause analysis modal coming soon...'));
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

    hideFindingModal() {
        this.setState({ showFindingModal: false });
    }

    hideRCAModal() {
        this.setState({ showRCAModal: false });
    }

    showContainmentModal(nc) {
        // Implementation for containment modal
        App.showNotification({
            type: 'info',
            message: 'Containment modal coming soon'
        });
    }

    viewAudit(audit) {
        // Implementation for viewing audit details
        App.showNotification({
            type: 'info',
            message: 'Audit details view coming soon'
        });
    }

    viewNC(nc) {
        // Implementation for viewing non-conformance details
        App.showNotification({
            type: 'info',
            message: 'Non-conformance details view coming soon'
        });
    }
}

// Export the component
window.QualityManagement = QualityManagement;
