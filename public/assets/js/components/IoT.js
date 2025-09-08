/**
 * TPT Free ERP - IoT & Device Integration Component
 * Complete device management, sensor data collection, real-time monitoring, and predictive maintenance interface
 */

class IoT extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'IoT & Device Integration',
            currentView: 'dashboard',
            ...props
        };

        this.state = {
            loading: false,
            currentView: this.props.currentView,
            overview: {},
            devices: [],
            sensors: [],
            monitoring: {},
            predictive: {},
            alerts: [],
            analytics: {},
            filters: {
                status: '',
                type: '',
                location: '',
                category: '',
                date_from: '',
                date_to: '',
                search: '',
                page: 1,
                limit: 50
            },
            selectedDevices: [],
            selectedAlerts: [],
            showDeviceModal: false,
            showSensorModal: false,
            showAlertModal: false,
            showFirmwareModal: false,
            editingDevice: null,
            editingSensor: null,
            editingAlert: null,
            pagination: {
                page: 1,
                limit: 50,
                total: 0,
                pages: 0
            },
            liveDataInterval: null,
            alertPollingInterval: null
        };

        // Bind methods
        this.loadOverview = this.loadOverview.bind(this);
        this.loadDevices = this.loadDevices.bind(this);
        this.loadSensors = this.loadSensors.bind(this);
        this.loadMonitoring = this.loadMonitoring.bind(this);
        this.loadPredictive = this.loadPredictive.bind(this);
        this.loadAlerts = this.loadAlerts.bind(this);
        this.loadAnalytics = this.loadAnalytics.bind(this);
        this.handleViewChange = this.handleViewChange.bind(this);
        this.handleFilterChange = this.handleFilterChange.bind(this);
        this.handleDeviceSelect = this.handleDeviceSelect.bind(this);
        this.handleAlertSelect = this.handleAlertSelect.bind(this);
        this.handleBulkAction = this.handleBulkAction.bind(this);
        this.showDeviceModal = this.showDeviceModal.bind(this);
        this.hideDeviceModal = this.hideDeviceModal.bind(this);
        this.saveDevice = this.saveDevice.bind(this);
        this.showSensorModal = this.showSensorModal.bind(this);
        this.hideSensorModal = this.hideSensorModal.bind(this);
        this.saveSensor = this.saveSensor.bind(this);
        this.showAlertModal = this.showAlertModal.bind(this);
        this.hideAlertModal = this.hideAlertModal.bind(this);
        this.saveAlert = this.saveAlert.bind(this);
        this.updateDeviceStatus = this.updateDeviceStatus.bind(this);
        this.submitSensorReading = this.submitSensorReading.bind(this);
        this.acknowledgeAlert = this.acknowledgeAlert.bind(this);
        this.resolveAlert = this.resolveAlert.bind(this);
        this.scheduleFirmwareUpdate = this.scheduleFirmwareUpdate.bind(this);
        this.bulkUpdateDevices = this.bulkUpdateDevices.bind(this);
        this.bulkRebootDevices = this.bulkRebootDevices.bind(this);
        this.exportDevices = this.exportDevices.bind(this);
        this.startLiveDataUpdates = this.startLiveDataUpdates.bind(this);
        this.stopLiveDataUpdates = this.stopLiveDataUpdates.bind(this);
        this.startAlertPolling = this.startAlertPolling.bind(this);
        this.stopAlertPolling = this.stopAlertPolling.bind(this);
    }

    async componentDidMount() {
        await this.loadInitialData();

        // Start real-time updates for monitoring view
        if (this.state.currentView === 'monitoring') {
            this.startLiveDataUpdates();
        }

        // Start alert polling for alerts view
        if (this.state.currentView === 'alerts') {
            this.startAlertPolling();
        }
    }

    componentWillUnmount() {
        this.stopLiveDataUpdates();
        this.stopAlertPolling();
    }

    async loadInitialData() {
        this.setState({ loading: true });

        try {
            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading IoT data:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to load IoT data'
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
            case 'devices':
                await this.loadDevices();
                break;
            case 'sensors':
                await this.loadSensors();
                break;
            case 'monitoring':
                await this.loadMonitoring();
                break;
            case 'predictive':
                await this.loadPredictive();
                break;
            case 'alerts':
                await this.loadAlerts();
                break;
            case 'analytics':
                await this.loadAnalytics();
                break;
        }
    }

    async loadOverview() {
        try {
            const [overview, sensorStatus, dataCollection, realTimeMonitoring, predictiveMaintenance, alertSystem, deviceAnalytics, systemHealth] = await Promise.all([
                API.get('/iot/overview'),
                API.get('/iot/sensor-status'),
                API.get('/iot/data-collection'),
                API.get('/iot/real-time-monitoring'),
                API.get('/iot/predictive-maintenance'),
                API.get('/iot/alert-system'),
                API.get('/iot/device-analytics'),
                API.get('/iot/system-health')
            ]);

            this.setState({
                overview: {
                    ...overview,
                    sensorStatus,
                    dataCollection,
                    realTimeMonitoring,
                    predictiveMaintenance,
                    alertSystem,
                    deviceAnalytics,
                    systemHealth
                }
            });
        } catch (error) {
            console.error('Error loading IoT overview:', error);
        }
    }

    async loadDevices() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/iot/devices?${params}`);
            this.setState({
                devices: response.devices,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading devices:', error);
        }
    }

    async loadSensors() {
        try {
            const response = await API.get('/iot/sensor-data');
            this.setState({ sensors: response });
        } catch (error) {
            console.error('Error loading sensors:', error);
        }
    }

    async loadMonitoring() {
        try {
            const [liveData, dashboards, thresholdAlerts, performanceMetrics, systemStatus] = await Promise.all([
                API.get('/iot/live-data'),
                API.get('/iot/monitoring-dashboards'),
                API.get('/iot/threshold-alerts'),
                API.get('/iot/performance-metrics'),
                API.get('/iot/system-status')
            ]);

            this.setState({
                monitoring: {
                    liveData,
                    dashboards,
                    thresholdAlerts,
                    performanceMetrics,
                    systemStatus
                }
            });
        } catch (error) {
            console.error('Error loading monitoring data:', error);
        }
    }

    async loadPredictive() {
        try {
            const [predictions, failureAnalysis, maintenanceSchedules, anomalyDetection] = await Promise.all([
                API.get('/iot/maintenance-predictions'),
                API.get('/iot/failure-analysis'),
                API.get('/iot/maintenance-schedules'),
                API.get('/iot/anomaly-detection')
            ]);

            this.setState({
                predictive: {
                    predictions,
                    failureAnalysis,
                    maintenanceSchedules,
                    anomalyDetection
                }
            });
        } catch (error) {
            console.error('Error loading predictive data:', error);
        }
    }

    async loadAlerts() {
        try {
            const [activeAlerts, alertHistory] = await Promise.all([
                API.get('/iot/active-alerts'),
                API.get('/iot/alert-history')
            ]);

            this.setState({
                alerts: {
                    active: activeAlerts,
                    history: alertHistory
                }
            });
        } catch (error) {
            console.error('Error loading alerts:', error);
        }
    }

    async loadAnalytics() {
        try {
            const [devicePerformance, dataInsights] = await Promise.all([
                API.get('/iot/device-performance'),
                API.get('/iot/data-insights')
            ]);

            this.setState({
                analytics: {
                    devicePerformance,
                    dataInsights
                }
            });
        } catch (error) {
            console.error('Error loading analytics:', error);
        }
    }

    handleViewChange(view) {
        // Stop previous real-time updates
        this.stopLiveDataUpdates();
        this.stopAlertPolling();

        this.setState({ currentView: view }, async () => {
            await this.loadCurrentViewData();

            // Start new real-time updates if needed
            if (view === 'monitoring') {
                this.startLiveDataUpdates();
            }
            if (view === 'alerts') {
                this.startAlertPolling();
            }
        });
    }

    handleFilterChange(filterName, value) {
        const newFilters = { ...this.state.filters, [filterName]: value };
        this.setState({
            filters: newFilters,
            pagination: { ...this.state.pagination, page: 1 }
        }, () => {
            if (this.state.currentView === 'devices') {
                this.loadDevices();
            }
        });
    }

    handleDeviceSelect(deviceId, selected) {
        const selectedDevices = selected
            ? [...this.state.selectedDevices, deviceId]
            : this.state.selectedDevices.filter(id => id !== deviceId);

        this.setState({ selectedDevices });
    }

    handleAlertSelect(alertId, selected) {
        const selectedAlerts = selected
            ? [...this.state.selectedAlerts, alertId]
            : this.state.selectedAlerts.filter(id => id !== alertId);

        this.setState({ selectedAlerts });
    }

    async handleBulkAction(action) {
        if (this.state.selectedDevices.length === 0 && this.state.selectedAlerts.length === 0) {
            App.showNotification({
                type: 'warning',
                message: 'Please select items first'
            });
            return;
        }

        try {
            switch (action) {
                case 'bulk_update_devices':
                    await this.bulkUpdateDevices();
                    break;
                case 'bulk_reboot_devices':
                    await this.bulkRebootDevices();
                    break;
                case 'export_devices':
                    await this.exportDevices();
                    break;
                case 'bulk_acknowledge_alerts':
                    await this.bulkAcknowledgeAlerts();
                    break;
                case 'bulk_resolve_alerts':
                    await this.bulkResolveAlerts();
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

    async bulkAcknowledgeAlerts() {
        for (const alertId of this.state.selectedAlerts) {
            try {
                await API.post(`/iot/alerts/${alertId}/acknowledge`);
            } catch (e) {
                console.error(`Failed to acknowledge alert ${alertId}:`, e);
            }
        }

        App.showNotification({
            type: 'success',
            message: `${this.state.selectedAlerts.length} alerts acknowledged`
        });

        this.setState({ selectedAlerts: [] });
        await this.loadAlerts();
    }

    async bulkResolveAlerts() {
        for (const alertId of this.state.selectedAlerts) {
            try {
                await API.post(`/iot/alerts/${alertId}/resolve`, {
                    root_cause: 'Bulk resolution',
                    preventive_action: 'Monitor closely'
                });
            } catch (e) {
                console.error(`Failed to resolve alert ${alertId}:`, e);
            }
        }

        App.showNotification({
            type: 'success',
            message: `${this.state.selectedAlerts.length} alerts resolved`
        });

        this.setState({ selectedAlerts: [] });
        await this.loadAlerts();
    }

    showDeviceModal(device = null) {
        this.setState({
            showDeviceModal: true,
            editingDevice: device
        });
    }

    hideDeviceModal() {
        this.setState({
            showDeviceModal: false,
            editingDevice: null
        });
    }

    async saveDevice(deviceData) {
        try {
            await API.post('/iot/devices', deviceData);

            App.showNotification({
                type: 'success',
                message: `Device ${this.state.editingDevice ? 'updated' : 'registered'} successfully`
            });

            this.hideDeviceModal();
            await this.loadDevices();
        } catch (error) {
            console.error('Error saving device:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save device'
            });
        }
    }

    showSensorModal(sensor = null) {
        this.setState({
            showSensorModal: true,
            editingSensor: sensor
        });
    }

    hideSensorModal() {
        this.setState({
            showSensorModal: false,
            editingSensor: null
        });
    }

    async saveSensor(sensorData) {
        try {
            await API.post('/iot/sensor-data', sensorData);

            App.showNotification({
                type: 'success',
                message: 'Sensor reading submitted successfully'
            });

            this.hideSensorModal();
            await this.loadSensors();
        } catch (error) {
            console.error('Error saving sensor:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save sensor'
            });
        }
    }

    showAlertModal(alert = null) {
        this.setState({
            showAlertModal: true,
            editingAlert: alert
        });
    }

    hideAlertModal() {
        this.setState({
            showAlertModal: false,
            editingAlert: null
        });
    }

    async saveAlert(alertData) {
        try {
            if (this.state.editingAlert) {
                await API.post(`/iot/alerts/${this.state.editingAlert.id}/resolve`, alertData);
            }

            App.showNotification({
                type: 'success',
                message: `Alert ${this.state.editingAlert ? 'resolved' : 'created'} successfully`
            });

            this.hideAlertModal();
            await this.loadAlerts();
        } catch (error) {
            console.error('Error saving alert:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to save alert'
            });
        }
    }

    async updateDeviceStatus(deviceId, status) {
        try {
            await API.post(`/iot/devices/${deviceId}/status`, { status });

            App.showNotification({
                type: 'success',
                message: 'Device status updated successfully'
            });

            await this.loadDevices();
        } catch (error) {
            console.error('Error updating device status:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to update device status'
            });
        }
    }

    async submitSensorReading(readingData) {
        try {
            await API.post('/iot/sensor-data', readingData);

            App.showNotification({
                type: 'success',
                message: 'Sensor reading submitted successfully'
            });

            await this.loadSensors();
        } catch (error) {
            console.error('Error submitting sensor reading:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to submit sensor reading'
            });
        }
    }

    async acknowledgeAlert(alertId) {
        try {
            await API.post(`/iot/alerts/${alertId}/acknowledge`);

            App.showNotification({
                type: 'success',
                message: 'Alert acknowledged successfully'
            });

            await this.loadAlerts();
        } catch (error) {
            console.error('Error acknowledging alert:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to acknowledge alert'
            });
        }
    }

    async resolveAlert(alertId) {
        try {
            await API.post(`/iot/alerts/${alertId}/resolve`, {
                root_cause: 'User resolution',
                preventive_action: 'Monitor closely'
            });

            App.showNotification({
                type: 'success',
                message: 'Alert resolved successfully'
            });

            await this.loadAlerts();
        } catch (error) {
            console.error('Error resolving alert:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to resolve alert'
            });
        }
    }

    async scheduleFirmwareUpdate(updateData) {
        try {
            await API.post('/iot/firmware-updates', updateData);

            App.showNotification({
                type: 'success',
                message: 'Firmware update scheduled successfully'
            });

            await this.loadDevices();
        } catch (error) {
            console.error('Error scheduling firmware update:', error);
            App.showNotification({
                type: 'error',
                message: error.message || 'Failed to schedule firmware update'
            });
        }
    }

    async bulkUpdateDevices() {
        // Implementation for bulk device update modal
        App.showNotification({
            type: 'info',
            message: 'Bulk device update modal coming soon'
        });
    }

    async bulkRebootDevices() {
        try {
            await API.post('/iot/devices/bulk-reboot', {
                device_ids: this.state.selectedDevices
            });

            App.showNotification({
                type: 'success',
                message: `${this.state.selectedDevices.length} devices reboot commands sent`
            });

            this.setState({ selectedDevices: [] });
        } catch (error) {
            console.error('Error rebooting devices:', error);
            App.showNotification({
                type: 'error',
                message: 'Failed to reboot devices'
            });
        }
    }

    async exportDevices() {
        try {
            const response = await API.get('/iot/export-devices', null, 'blob');
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', 'devices_export.csv');
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            App.showNotification({
                type: 'success',
                message: 'Devices exported successfully'
            });
        } catch (error) {
            console.error('Export failed:', error);
            App.showNotification({
                type: 'error',
                message: 'Export failed'
            });
        }
    }

    startLiveDataUpdates() {
        this.liveDataInterval = setInterval(async () => {
            try {
                const liveData = await API.get('/iot/live-data');
                this.setState(prevState => ({
                    monitoring: {
                        ...prevState.monitoring,
                        liveData
                    }
                }));
            } catch (error) {
                console.error('Error updating live data:', error);
            }
        }, 5000); // Update every 5 seconds
    }

    stopLiveDataUpdates() {
        if (this.liveDataInterval) {
            clearInterval(this.liveDataInterval);
            this.liveDataInterval = null;
        }
    }

    startAlertPolling() {
        this.alertPollingInterval = setInterval(async () => {
            try {
                const activeAlerts = await API.get('/iot/active-alerts');
                this.setState(prevState => ({
                    alerts: {
                        ...prevState.alerts,
                        active: activeAlerts
                    }
                }));
            } catch (error) {
                console.error('Error polling alerts:', error);
            }
        }, 10000); // Poll every 10 seconds
    }

    stopAlertPolling() {
        if (this.alertPollingInterval) {
            clearInterval(this.alertPollingInterval);
            this.alertPollingInterval = null;
        }
    }

    render() {
        const { title } = this.props;
        const { loading, currentView } = this.state;

        const container = DOM.create('div', { className: 'iot-container' });

        // Header
        const header = DOM.create('div', { className: 'iot-header' });
        const titleElement = DOM.create('h1', { className: 'iot-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'iot-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showDeviceModal) {
            container.appendChild(this.renderDeviceModal());
        }

        if (this.state.showSensorModal) {
            container.appendChild(this.renderSensorModal());
        }

        if (this.state.showAlertModal) {
            container.appendChild(this.renderAlertModal());
        }

        if (this.state.showFirmwareModal) {
            container.appendChild(this.renderFirmwareModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'devices', label: 'Devices', icon: 'fas fa-microchip' },
            { id: 'sensors', label: 'Sensors', icon: 'fas fa-thermometer-half' },
            { id: 'monitoring', label: 'Monitoring', icon: 'fas fa-desktop' },
            { id: 'predictive', label: 'Predictive', icon: 'fas fa-brain' },
            { id: 'alerts', label: 'Alerts', icon: 'fas fa-exclamation-triangle' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'iot-nav' });
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
            DOM.create('p', {}, 'Loading IoT data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'devices':
                return this.renderDevices();
            case 'sensors':
                return this.renderSensors();
            case 'monitoring':
                return this.renderMonitoring();
            case 'predictive':
                return this.renderPredictive();
            case 'alerts':
                return this.renderAlerts();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'iot-dashboard' });

        // System health overview
        const systemHealth = this.renderSystemHealth();
        dashboard.appendChild(systemHealth);

        // Device status overview
        const deviceStatus = this.renderDeviceStatusOverview();
        dashboard.appendChild(deviceStatus);

        // Active alerts
        const activeAlerts = this.renderActiveAlerts();
        dashboard.appendChild(activeAlerts);

        // Predictive maintenance alerts
        const predictiveAlerts = this.renderPredictiveAlerts();
        dashboard.appendChild(predictiveAlerts);

        return dashboard;
    }

    renderSystemHealth() {
        const health = this.state.overview.systemHealth || {};
        const healthDiv = DOM.create('div', { className: 'system-health' });
        healthDiv.appendChild(DOM.create('h3', {}, 'System Health'));

        const healthCards = DOM.create('div', { className: 'health-cards' });

        const metrics = [
            { label: 'Online Devices', value: health.online_devices || 0, status: 'success' },
            { label: 'Offline Devices', value: health.offline_devices || 0, status: 'danger' },
            { label: 'Active Alerts', value: health.active_alerts || 0, status: 'warning' },
            { label: 'System Uptime', value: `${health.system_uptime || 0}%`, status: 'info' }
        ];

        metrics.forEach(metric => {
            const card = DOM.create('div', { className: `health-card ${metric.status}` });
            card.appendChild(DOM.create('div', { className: 'metric-value' }, metric.value.toString()));
            card.appendChild(DOM.create('div', { className: 'metric-label' }, metric.label));
            healthCards.appendChild(card);
        });

        healthDiv.appendChild(healthCards);
        return healthDiv;
    }

    renderDeviceStatusOverview() {
        const overview = this.state.overview;
        const statusDiv = DOM.create('div', { className: 'device-status-overview' });
        statusDiv.appendChild(DOM.create('h3', {}, 'Device Status Overview'));

        const statusCards = DOM.create('div', { className: 'status-cards' });

        const statuses = [
            { label: 'Total Devices', value: overview.total_devices || 0, icon: 'fas fa-microchip' },
            { label: 'Online', value: overview.online_devices || 0, icon: 'fas fa-circle', color: 'success' },
            { label: 'Offline', value: overview.offline_devices || 0, icon: 'fas fa-circle', color: 'danger' },
            { label: 'Maintenance', value: overview.maintenance_devices || 0, icon: 'fas fa-tools', color: 'warning' }
        ];

        statuses.forEach(status => {
            const card = DOM.create('div', { className: `status-card ${status.color || ''}` });
            const icon = DOM.create('i', { className: status.icon });
            const content = DOM.create('div', { className: 'status-content' });
            content.appendChild(DOM.create('div', { className: 'status-value' }, status.value.toString()));
            content.appendChild(DOM.create('div', { className: 'status-label' }, status.label));

            card.appendChild(icon);
            card.appendChild(content);
            statusCards.appendChild(card);
        });

        statusDiv.appendChild(statusCards);
        return statusDiv;
    }

    renderActiveAlerts() {
        const alerts = this.state.overview.alertSystem || [];
        const alertsDiv = DOM.create('div', { className: 'active-alerts' });
        alertsDiv.appendChild(DOM.create('h3', {}, 'Active Alerts'));

        if (alerts.length === 0) {
            alertsDiv.appendChild(DOM.create('p', { className: 'no-alerts' }, 'No active alerts'));
        } else {
            const alertsList = DOM.create('ul', { className: 'alerts-list' });
            alerts.slice(0, 5).forEach(alert => {
                const listItem = DOM.create('li', { className: `alert-item ${alert.severity}` });
                listItem.appendChild(DOM.create('div', { className: 'alert-info' },
                    DOM.create('strong', {}, alert.alert_type),
                    DOM.create('span', {}, alert.message)
                ));
                alertsList.appendChild(listItem);
            });
            alertsDiv.appendChild(alertsList);
        }

        return alertsDiv;
    }

    renderPredictiveAlerts() {
        const predictions = this.state.overview.predictiveMaintenance || [];
        const predictionsDiv = DOM.create('div', { className: 'predictive-alerts' });
        predictionsDiv.appendChild(DOM.create('h3', {}, 'Predictive Maintenance'));

        if (predictions.length === 0) {
            predictionsDiv.appendChild(DOM.create('p', { className: 'no-predictions' }, 'No maintenance predictions'));
        } else {
            const predictionsList = DOM.create('ul', { className: 'predictions-list' });
            predictions.slice(0, 5).forEach(prediction => {
                const listItem = DOM.create('li', { className: 'prediction-item' });
                listItem.appendChild(DOM.create('div', { className: 'prediction-info' },
                    DOM.create('strong', {}, prediction.device_name),
                    DOM.create('span', {}, `${prediction.prediction_type} - ${prediction.days_until_failure} days`)
                ));
                predictionsList.appendChild(listItem);
            });
            predictionsDiv.appendChild(predictionsList);
        }

        return predictionsDiv;
    }

    renderDevices() {
        const devicesView = DOM.create('div', { className: 'devices-view' });

        // Toolbar
        const toolbar = this.renderDevicesToolbar();
        devicesView.appendChild(toolbar);

        // Filters
        const filters = this.renderDevicesFilters();
        devicesView.appendChild(filters);

        // Devices table
        const table = this.renderDevicesTable();
        devicesView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        devicesView.appendChild(pagination);

        return devicesView;
    }

    renderDevicesToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedDevices.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedDevices.length} selected`
            ));

            const actions = ['bulk_update_devices', 'bulk_reboot_devices', 'export_devices'];
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
            onclick: () => this.showDeviceModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Register Device';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderDevicesFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search devices...',
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
        const statuses = ['', 'online', 'offline', 'maintenance', 'error'];
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

    renderDevicesTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'device_name', label: 'Device Name' },
            { key: 'device_id', label: 'Device ID' },
            { key: 'type', label: 'Type' },
            { key: 'location', label: 'Location' },
            { key: 'status', label: 'Status' },
            { key: 'last_seen', label: 'Last Seen' },
            { key: 'uptime', label: 'Uptime' },
            { key: 'battery', label: 'Battery' },
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

        this.state.devices.forEach(device => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedDevices.includes(device.id),
                onchange: (e) => this.handleDeviceSelect(device.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // Device Name
            row.appendChild(DOM.create('td', {}, device.device_name));

            // Device ID
            row.appendChild(DOM.create('td', {}, device.device_id));

            // Type
            row.appendChild(DOM.create('td', {}, device.type_name || 'N/A'));

            // Location
            row.appendChild(DOM.create('td', {}, device.location_name || 'N/A'));

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${device.status}`
            }, device.status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Last Seen
            const lastSeenCell = DOM.create('td', {});
            if (device.minutes_since_last_seen !== null) {
                const timeAgo = device.minutes_since_last_seen < 60
                    ? `${device.minutes_since_last_seen}m ago`
                    : `${Math.floor(device.minutes_since_last_seen / 60)}h ago`;
                lastSeenCell.appendChild(DOM.create('span', { className: 'time-ago' }, timeAgo));
            } else {
                lastSeenCell.appendChild(DOM.create('span', {}, 'Never'));
            }
            row.appendChild(lastSeenCell);

            // Uptime
            const uptimeCell = DOM.create('td', {});
            if (device.uptime_percentage) {
                uptimeCell.appendChild(DOM.create('span', { className: 'uptime' },
                    `${device.uptime_percentage}%`
                ));
            } else {
                uptimeCell.appendChild(DOM.create('span', {}, 'N/A'));
            }
            row.appendChild(uptimeCell);

            // Battery
            const batteryCell = DOM.create('td', {});
            if (device.battery_level) {
                const batteryClass = device.battery_level < 20 ? 'low' : device.battery_level < 50 ? 'medium' : 'high';
                batteryCell.appendChild(DOM.create('span', { className: `battery ${batteryClass}` },
                    `${device.battery_level}%`
                ));
            } else {
                batteryCell.appendChild(DOM.create('span', {}, 'N/A'));
            }
            row.appendChild(batteryCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const viewButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.viewDevice(device)
            });
            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            actions.appendChild(viewButton);

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showDeviceModal(device)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const statusButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-warning',
                onclick: () => this.showStatusModal(device)
            });
            statusButton.innerHTML = '<i class="fas fa-play-circle"></i>';
            actions.appendChild(statusButton);

            const rebootButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-danger',
                onclick: () => this.rebootDevice(device)
            });
            rebootButton.innerHTML = '<i class="fas fa-power-off"></i>';
            actions.appendChild(rebootButton);

            actionsCell.appendChild(actions);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderSensors() {
        const sensorsView = DOM.create('div', { className: 'sensors-view' });

        // Toolbar
        const toolbar = this.renderSensorsToolbar();
        sensorsView.appendChild(toolbar);

        // Sensor data table
        const table = this.renderSensorsTable();
        sensorsView.appendChild(table);

        return sensorsView;
    }

    renderSensorsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const rightSection = DOM.create('div', { className: 'toolbar-right' });
        const addButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showSensorModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Submit Reading';
        rightSection.appendChild(addButton);

        toolbar.appendChild(rightSection);
        return toolbar;
    }

    renderSensorsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'sensor_name', label: 'Sensor' },
            { key: 'device_name', label: 'Device' },
            { key: 'reading_value', label: 'Value' },
            { key: 'units', label: 'Units' },
            { key: 'reading_timestamp', label: 'Timestamp' },
            { key: 'data_quality', label: 'Quality' },
            { key: 'status', label: 'Status' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {}, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.sensors.forEach(sensor => {
            const row = DOM.create('tr', {});

            // Sensor Name
            row.appendChild(DOM.create('td', {}, sensor.sensor_name));

            // Device Name
            row.appendChild(DOM.create('td', {}, sensor.device_name));

            // Reading Value
            row.appendChild(DOM.create('td', {}, sensor.reading_value));

            // Units
            row.appendChild(DOM.create('td', {}, sensor.units || 'N/A'));

            // Timestamp
            row.appendChild(DOM.create('td', {}, this.formatDate(sensor.reading_timestamp)));

            // Data Quality
            const qualityCell = DOM.create('td', {});
            const qualityBadge = DOM.create('span', {
                className: `quality-badge ${sensor.data_quality_score >= 80 ? 'good' : sensor.data_quality_score >= 60 ? 'fair' : 'poor'}`
            }, `${sensor.data_quality_score}%`);
            qualityCell.appendChild(qualityBadge);
            row.appendChild(qualityCell);

            // Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${sensor.reading_status || 'normal'}`
            }, sensor.reading_status || 'Normal');
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderMonitoring() {
        const monitoringView = DOM.create('div', { className: 'monitoring-view' });

        // Live data indicator
        const liveIndicator = DOM.create('div', { className: 'live-indicator' });
        liveIndicator.appendChild(DOM.create('span', { className: 'live-dot' }));
        liveIndicator.appendChild(DOM.create('span', {}, 'Live Data'));
        monitoringView.appendChild(liveIndicator);

        // Live data table
        const liveDataTable = this.renderLiveDataTable();
        monitoringView.appendChild(liveDataTable);

        // System status
        const systemStatus = this.renderSystemStatus();
        monitoringView.appendChild(systemStatus);

        return monitoringView;
    }

    renderLiveDataTable() {
        const liveData = this.state.monitoring.liveData || [];
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table live-data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'device_name', label: 'Device' },
            { key: 'sensor_name', label: 'Sensor' },
            { key: 'reading_value', label: 'Value' },
            { key: 'units', label: 'Units' },
            { key: 'threshold_status', label: 'Status' },
            { key: 'seconds_ago', label: 'Updated' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {}, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        liveData.forEach(data => {
            const row = DOM.create('tr', {});

            // Device Name
            row.appendChild(DOM.create('td', {}, data.device_name));

            // Sensor Name
            row.appendChild(DOM.create('td', {}, data.sensor_name));

            // Reading Value
            row.appendChild(DOM.create('td', {}, data.reading_value));

            // Units
            row.appendChild(DOM.create('td', {}, data.units || 'N/A'));

            // Threshold Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${data.threshold_status}`
            }, data.threshold_status);
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Updated
            row.appendChild(DOM.create('td', {}, `${data.seconds_ago}s ago`));

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderSystemStatus() {
        const status = this.state.monitoring.systemStatus || {};
        const statusDiv = DOM.create('div', { className: 'system-status' });
        statusDiv.appendChild(DOM.create('h3', {}, 'System Status'));

        const statusCards = DOM.create('div', { className: 'status-cards' });

        const metrics = [
            { label: 'Online Devices', value: status.online_devices || 0, icon: 'fas fa-circle', color: 'success' },
            { label: 'Offline Devices', value: status.offline_devices || 0, icon: 'fas fa-circle', color: 'danger' },
            { label: 'Active Alerts', value: status.active_alerts || 0, icon: 'fas fa-exclamation-triangle', color: 'warning' },
            { label: 'High Risk Predictions', value: status.high_risk_predictions || 0, icon: 'fas fa-exclamation-circle', color: 'danger' }
        ];

        metrics.forEach(metric => {
            const card = DOM.create('div', { className: `status-card ${metric.color}` });
            const icon = DOM.create('i', { className: metric.icon });
            const content = DOM.create('div', { className: 'status-content' });
            content.appendChild(DOM.create('div', { className: 'status-value' }, metric.value.toString()));
            content.appendChild(DOM.create('div', { className: 'status-label' }, metric.label));

            card.appendChild(icon);
            card.appendChild(content);
            statusCards.appendChild(card);
        });

        statusDiv.appendChild(statusCards);
        return statusDiv;
    }

    renderPredictive() {
        const predictiveView = DOM.create('div', { className: 'predictive-view' });

        // Maintenance predictions
        const predictions = this.renderMaintenancePredictions();
        predictiveView.appendChild(predictions);

        // Failure analysis
        const analysis = this.renderFailureAnalysis();
        predictiveView.appendChild(analysis);

        return predictiveView;
    }

    renderMaintenancePredictions() {
        const predictions = this.state.predictive.predictions || [];
        const predictionsDiv = DOM.create('div', { className: 'maintenance-predictions' });
        predictionsDiv.appendChild(DOM.create('h3', {}, 'Maintenance Predictions'));

        if (predictions.length === 0) {
            predictionsDiv.appendChild(DOM.create('p', { className: 'no-data' }, 'No maintenance predictions'));
        } else {
            const predictionsTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Device', 'Prediction Type', 'Risk Level', 'Days Until Failure', 'Recommended Action'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            predictions.forEach(prediction => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, prediction.device_name));
                row.appendChild(DOM.create('td', {}, prediction.prediction_type));
                const riskCell = DOM.create('td', {});
                const riskBadge = DOM.create('span', {
                    className: `risk-badge ${prediction.risk_level}`
                }, prediction.risk_level);
                riskCell.appendChild(riskBadge);
                row.appendChild(riskCell);
                row.appendChild(DOM.create('td', {}, `${prediction.days_until_failure} days`));
                row.appendChild(DOM.create('td', {}, prediction.recommended_action));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            predictionsTable.appendChild(table);
            predictionsDiv.appendChild(predictionsTable);
        }

        return predictionsDiv;
    }

    renderFailureAnalysis() {
        const analysis = this.state.predictive.failureAnalysis || [];
        const analysisDiv = DOM.create('div', { className: 'failure-analysis' });
        analysisDiv.appendChild(DOM.create('h3', {}, 'Failure Analysis'));

        if (analysis.length === 0) {
            analysisDiv.appendChild(DOM.create('p', { className: 'no-data' }, 'No failure analysis data'));
        } else {
            const analysisTable = DOM.create('div', { className: 'data-table-container' });
            const table = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Failure Type', 'Root Cause', 'Occurrences', 'Avg Downtime', 'Prevention Measures'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            analysis.forEach(item => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, item.failure_type));
                row.appendChild(DOM.create('td', {}, item.root_cause));
                row.appendChild(DOM.create('td', {}, item.occurrence_count.toString()));
                row.appendChild(DOM.create('td', {}, `${item.avg_downtime}h`));
                row.appendChild(DOM.create('td', {}, item.prevention_measures));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            analysisTable.appendChild(table);
            analysisDiv.appendChild(analysisTable);
        }

        return analysisDiv;
    }

    renderAlerts() {
        const alertsView = DOM.create('div', { className: 'alerts-view' });

        // Toolbar
        const toolbar = this.renderAlertsToolbar();
        alertsView.appendChild(toolbar);

        // Active alerts
        const activeAlerts = this.renderActiveAlertsTable();
        alertsView.appendChild(activeAlerts);

        // Alert history
        const alertHistory = this.renderAlertHistoryTable();
        alertsView.appendChild(alertHistory);

        return alertsView;
    }

    renderAlertsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedAlerts.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedAlerts.length} selected`
            ));

            const actions = ['bulk_acknowledge_alerts', 'bulk_resolve_alerts'];
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
            onclick: () => this.showAlertModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Create Alert';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderActiveAlertsTable() {
        const activeAlerts = this.state.alerts.active || [];
        const alertsDiv = DOM.create('div', { className: 'active-alerts-table' });
        alertsDiv.appendChild(DOM.create('h3', {}, 'Active Alerts'));

        if (activeAlerts.length === 0) {
            alertsDiv.appendChild(DOM.create('p', { className: 'no-alerts' }, 'No active alerts'));
        } else {
            const table = DOM.create('div', { className: 'data-table-container' });
            const tableElement = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            const headers = [
                { key: 'select', label: '', width: '40px' },
                { key: 'severity', label: 'Severity' },
                { key: 'alert_type', label: 'Type' },
                { key: 'message', label: 'Message' },
                { key: 'device', label: 'Device' },
                { key: 'created_at', label: 'Created' },
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
            activeAlerts.forEach(alert => {
                const row = DOM.create('tr', {});

                // Checkbox
                const checkboxCell = DOM.create('td', {});
                const checkbox = DOM.create('input', {
                    type: 'checkbox',
                    checked: this.state.selectedAlerts.includes(alert.id),
                    onchange: (e) => this.handleAlertSelect(alert.id, e.target.checked)
                });
                checkboxCell.appendChild(checkbox);
                row.appendChild(checkboxCell);

                // Severity
                const severityCell = DOM.create('td', {});
                const severityBadge = DOM.create('span', {
                    className: `severity-badge ${alert.severity}`
                }, alert.severity);
                severityCell.appendChild(severityBadge);
                row.appendChild(severityCell);

                // Type
                row.appendChild(DOM.create('td', {}, alert.alert_type));

                // Message
                row.appendChild(DOM.create('td', {}, alert.message));

                // Device
                row.appendChild(DOM.create('td', {}, alert.device_name || 'N/A'));

                // Created
                row.appendChild(DOM.create('td', {}, this.formatDate(alert.created_at)));

                // Actions
                const actionsCell = DOM.create('td', {});
                const actions = DOM.create('div', { className: 'table-actions' });

                const acknowledgeButton = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-warning',
                    onclick: () => this.acknowledgeAlert(alert.id)
                });
                acknowledgeButton.innerHTML = '<i class="fas fa-check"></i>';
                actions.appendChild(acknowledgeButton);

                const resolveButton = DOM.create('button', {
                    className: 'btn btn-sm btn-outline-success',
                    onclick: () => this.resolveAlert(alert.id)
                });
                resolveButton.innerHTML = '<i class="fas fa-check-circle"></i>';
                actions.appendChild(resolveButton);

                actionsCell.appendChild(actions);
                row.appendChild(actionsCell);

                tbody.appendChild(row);
            });

            tableElement.appendChild(tbody);
            table.appendChild(tableElement);
            alertsDiv.appendChild(table);
        }

        return alertsDiv;
    }

    renderAlertHistoryTable() {
        const alertHistory = this.state.alerts.history || [];
        const historyDiv = DOM.create('div', { className: 'alert-history-table' });
        historyDiv.appendChild(DOM.create('h3', {}, 'Alert History'));

        if (alertHistory.length === 0) {
            historyDiv.appendChild(DOM.create('p', { className: 'no-history' }, 'No alert history'));
        } else {
            const table = DOM.create('div', { className: 'data-table-container' });
            const tableElement = DOM.create('table', { className: 'data-table' });

            // Table header
            const thead = DOM.create('thead', {});
            const headerRow = DOM.create('tr', {});
            ['Type', 'Severity', 'Message', 'Device', 'Created', 'Resolved', 'Resolution Time'].forEach(header => {
                headerRow.appendChild(DOM.create('th', {}, header));
            });
            thead.appendChild(headerRow);
            tableElement.appendChild(thead);

            // Table body
            const tbody = DOM.create('tbody', {});
            alertHistory.forEach(alert => {
                const row = DOM.create('tr', {});
                row.appendChild(DOM.create('td', {}, alert.alert_type));
                const severityCell = DOM.create('td', {});
                const severityBadge = DOM.create('span', {
                    className: `severity-badge ${alert.severity}`
                }, alert.severity);
                severityCell.appendChild(severityBadge);
                row.appendChild(severityCell);
                row.appendChild(DOM.create('td', {}, alert.message));
                row.appendChild(DOM.create('td', {}, alert.device_name || 'N/A'));
                row.appendChild(DOM.create('td', {}, this.formatDate(alert.created_at)));
                row.appendChild(DOM.create('td', {}, this.formatDate(alert.resolved_at)));
                row.appendChild(DOM.create('td', {}, `${alert.resolution_time_minutes} min`));
                tbody.appendChild(row);
            });
            table.appendChild(tbody);
            table.appendChild(tableElement);
            historyDiv.appendChild(table);
        }

        return historyDiv;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });
        analyticsView.appendChild(DOM.create('h3', {}, 'IoT Analytics'));
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
            if (this.state.currentView === 'devices') {
                this.loadDevices();
            }
        });
    }

    // ============================================================================
    // MODAL RENDERING METHODS
    // ============================================================================

    renderDeviceModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingDevice ? 'Edit Device' : 'Register Device'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideDeviceModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Device modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderSensorModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Submit Sensor Reading'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideSensorModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Sensor reading modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderAlertModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, this.state.editingAlert ? 'Edit Alert' : 'Create Alert'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideAlertModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Alert modal coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderFirmwareModal() {
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Firmware Update'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideFirmwareModal()
        });
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Firmware update modal coming soon...'));
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

    hideFirmwareModal() {
        this.setState({ showFirmwareModal: false });
    }

    viewDevice(device) {
        // Implementation for viewing device details
        App.showNotification({
            type: 'info',
            message: 'Device details view coming soon'
        });
    }

    showStatusModal(device) {
        // Implementation for status modal
        App.showNotification({
            type: 'info',
            message: 'Device status modal coming soon'
        });
    }

    rebootDevice(device) {
        // Implementation for rebooting device
        App.showNotification({
            type: 'info',
            message: 'Device reboot functionality coming soon'
        });
    }

    viewAlert(alert) {
        // Implementation for viewing alert details
        App.showNotification({
            type: 'info',
            message: 'Alert details view coming soon'
        });
    }
}

// Export the component
window.IoT = IoT;
