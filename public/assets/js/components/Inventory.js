/**
 * TPT Free ERP - Inventory Management Component (Refactored)
 * Main inventory dashboard and management interface
 * Uses shared utilities for reduced complexity and improved maintainability
 */

class Inventory extends BaseComponent {
    constructor(props = {}) {
        super(props);

        // Initialize table renderer for products
        this.tableRenderer = this.createTableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            pagination: true
        });

        // Setup table callbacks
        this.tableRenderer.setDataCallback(() => this.state.products || []);
        this.tableRenderer.setSelectionCallback((selectedIds) => {
            this.setState({ selectedProducts: selectedIds });
        });
        this.tableRenderer.setBulkActionCallback((action, selectedIds) => {
            this.handleBulkAction(action, selectedIds);
        });
        this.tableRenderer.setDataChangeCallback(() => {
            this.loadProducts();
        });
    }

    get bindMethods() {
        return [
            'loadOverview',
            'loadProducts',
            'loadStockMovements',
            'loadWarehouses',
            'loadSuppliers',
            'loadCategories',
            'handleViewChange',
            'handleFilterChange',
            'handleBulkAction',
            'showProductModal',
            'hideProductModal',
            'saveProduct',
            'deleteProduct',
            'showMovementModal',
            'hideMovementModal',
            'saveStockMovement'
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
                this.loadWarehouses(),
                this.loadSuppliers(),
                this.loadCategories()
            ]);

            // Load current view data
            await this.loadCurrentViewData();
        } catch (error) {
            console.error('Error loading inventory data:', error);
            this.showErrorNotification('Failed to load inventory data');
        } finally {
            this.setState({ loading: false });
        }
    }

    async loadCurrentViewData() {
        switch (this.state.currentView) {
            case 'dashboard':
                await this.loadOverview();
                break;
            case 'products':
                await this.loadProducts();
                break;
            case 'stock':
                await this.loadStockMovements();
                break;
            case 'warehouses':
                await this.loadWarehouses();
                break;
            case 'suppliers':
                await this.loadSuppliers();
                break;
        }
    }

    async loadOverview() {
        try {
            const response = await API.get('/inventory/overview');
            this.setState({ overview: response });
        } catch (error) {
            console.error('Error loading inventory overview:', error);
        }
    }

    async loadProducts() {
        try {
            const params = new URLSearchParams({
                ...this.state.filters,
                page: this.state.pagination.page,
                limit: this.state.pagination.limit
            });

            const response = await API.get(`/inventory/products?${params}`);
            this.setState({
                products: response.products,
                pagination: response.pagination
            });
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    async loadStockMovements() {
        try {
            const response = await API.get('/inventory/stock-movements');
            this.setState({ stockMovements: response.movements });
        } catch (error) {
            console.error('Error loading stock movements:', error);
        }
    }

    async loadWarehouses() {
        try {
            const response = await API.get('/inventory/warehouses');
            this.setState({ warehouses: response.warehouses });
        } catch (error) {
            console.error('Error loading warehouses:', error);
        }
    }

    async loadSuppliers() {
        try {
            const response = await API.get('/inventory/suppliers');
            this.setState({ suppliers: response.suppliers });
        } catch (error) {
            console.error('Error loading suppliers:', error);
        }
    }

    async loadCategories() {
        try {
            const response = await API.get('/inventory/categories');
            this.setState({ categories: response.categories });
        } catch (error) {
            console.error('Error loading categories:', error);
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
            if (this.state.currentView === 'products') {
                this.loadProducts();
            }
        });
    }

    handleProductSelect(productId, selected) {
        const selectedProducts = selected
            ? [...this.state.selectedProducts, productId]
            : this.state.selectedProducts.filter(id => id !== productId);

        this.setState({ selectedProducts });
    }

    async handleBulkAction(action, selectedIds = null) {
        const productsToUse = selectedIds || this.state.selectedProducts;

        if (productsToUse.length === 0) {
            this.showWarningNotification('Please select products first');
            return;
        }

        try {
            switch (action) {
                case 'delete':
                    if (await this.confirm(`Delete ${productsToUse.length} products?`)) {
                        await this.bulkDeleteProducts(productsToUse);
                    }
                    break;
                case 'update_status':
                    await this.showBulkUpdateModal('status', productsToUse);
                    break;
                case 'update_category':
                    await this.showBulkUpdateModal('category', productsToUse);
                    break;
                case 'export':
                    await this.exportProducts(productsToUse);
                    break;
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            this.showErrorNotification('Bulk action failed');
        }
    }

    async bulkDeleteProducts(productsToDelete) {
        // Implementation for bulk delete
        this.showInfoNotification('Bulk delete not yet implemented');
    }

    async showBulkUpdateModal(field, productsToUpdate) {
        // Implementation for bulk update modal
        this.showInfoNotification('Bulk update not yet implemented');
    }

    async exportProducts(productsToExport) {
        // Implementation for export
        this.showInfoNotification('Export not yet implemented');
    }

    showProductModal(product = null) {
        this.setState({
            showProductModal: true,
            editingProduct: product
        });
    }

    hideProductModal() {
        this.setState({
            showProductModal: false,
            editingProduct: null
        });
    }

    async saveProduct(productData) {
        try {
            if (this.state.editingProduct) {
                await this.apiRequest('PUT', `/inventory/products/${this.state.editingProduct.id}`, productData);
                this.showSuccessNotification('Product updated successfully');
            } else {
                await this.apiRequest('POST', '/inventory/products', productData);
                this.showSuccessNotification('Product created successfully');
            }

            this.hideProductModal();
            await this.loadProducts();
        } catch (error) {
            console.error('Error saving product:', error);
            this.showErrorNotification(error.message || 'Failed to save product');
        }
    }

    async deleteProduct(productId) {
        if (!(await this.confirm('Are you sure you want to delete this product?'))) {
            return;
        }

        try {
            await this.apiRequest('DELETE', `/inventory/products/${productId}`);
            this.showSuccessNotification('Product deleted successfully');
            await this.loadProducts();
        } catch (error) {
            console.error('Error deleting product:', error);
            this.showErrorNotification(error.message || 'Failed to delete product');
        }
    }

    showMovementModal(product = null) {
        this.setState({
            showMovementModal: true,
            selectedProduct: product
        });
    }

    hideMovementModal() {
        this.setState({
            showMovementModal: false,
            selectedProduct: null
        });
    }

    async saveStockMovement(movementData) {
        try {
            await this.apiRequest('POST', '/inventory/stock-movements', movementData);
            this.showSuccessNotification('Stock movement recorded successfully');
            this.hideMovementModal();
            await this.loadStockMovements();
            await this.loadOverview(); // Refresh overview data
        } catch (error) {
            console.error('Error saving stock movement:', error);
            this.showErrorNotification(error.message || 'Failed to record stock movement');
        }
    }

    render() {
        const { title, currentView } = this.props;
        const { loading, currentView: activeView } = this.state;

        const container = DOM.create('div', { className: 'inventory-container' });

        // Header
        const header = DOM.create('div', { className: 'inventory-header' });
        const titleElement = DOM.create('h1', { className: 'inventory-title' }, title);
        header.appendChild(titleElement);

        // Navigation tabs
        const navTabs = this.renderNavigationTabs();
        header.appendChild(navTabs);

        container.appendChild(header);

        // Content area
        const content = DOM.create('div', { className: 'inventory-content' });

        if (loading) {
            content.appendChild(this.renderLoading());
        } else {
            content.appendChild(this.renderCurrentView());
        }

        container.appendChild(content);

        // Modals
        if (this.state.showProductModal) {
            container.appendChild(this.renderProductModal());
        }

        if (this.state.showMovementModal) {
            container.appendChild(this.renderMovementModal());
        }

        return container;
    }

    renderNavigationTabs() {
        const tabs = [
            { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-tachometer-alt' },
            { id: 'products', label: 'Products', icon: 'fas fa-boxes' },
            { id: 'stock', label: 'Stock Tracking', icon: 'fas fa-chart-line' },
            { id: 'warehouses', label: 'Warehouses', icon: 'fas fa-warehouse' },
            { id: 'suppliers', label: 'Suppliers', icon: 'fas fa-truck' },
            { id: 'analytics', label: 'Analytics', icon: 'fas fa-chart-bar' }
        ];

        const nav = DOM.create('nav', { className: 'inventory-nav' });
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
            DOM.create('p', {}, 'Loading inventory data...')
        );
    }

    renderCurrentView() {
        switch (this.state.currentView) {
            case 'dashboard':
                return this.renderDashboard();
            case 'products':
                return this.renderProducts();
            case 'stock':
                return this.renderStockTracking();
            case 'warehouses':
                return this.renderWarehouses();
            case 'suppliers':
                return this.renderSuppliers();
            case 'analytics':
                return this.renderAnalytics();
            default:
                return this.renderDashboard();
        }
    }

    renderDashboard() {
        const dashboard = DOM.create('div', { className: 'inventory-dashboard' });

        // Overview cards
        const overviewCards = this.renderOverviewCards();
        dashboard.appendChild(overviewCards);

        // Charts and alerts
        const chartsSection = DOM.create('div', { className: 'dashboard-charts' });
        chartsSection.appendChild(this.renderStockAlerts());
        chartsSection.appendChild(this.renderUpcomingDeliveries());
        dashboard.appendChild(chartsSection);

        return dashboard;
    }

    renderOverviewCards() {
        const overview = this.state.overview.inventory_overview || {};
        const cards = DOM.create('div', { className: 'overview-cards' });

        const cardData = [
            {
                title: 'Total Products',
                value: overview.total_products || 0,
                icon: 'fas fa-boxes',
                color: 'primary'
            },
            {
                title: 'Total Stock Value',
                value: '$' + (overview.total_inventory_value || 0).toLocaleString(),
                icon: 'fas fa-dollar-sign',
                color: 'success'
            },
            {
                title: 'Low Stock Items',
                value: overview.low_stock_items || 0,
                icon: 'fas fa-exclamation-triangle',
                color: 'warning'
            },
            {
                title: 'Out of Stock',
                value: overview.out_of_stock_items || 0,
                icon: 'fas fa-times-circle',
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

    renderStockAlerts() {
        const alerts = this.state.overview.inventory_alerts || [];
        const alertsSection = DOM.create('div', { className: 'dashboard-section' });
        alertsSection.appendChild(DOM.create('h3', {}, 'Stock Alerts'));

        if (alerts.length === 0) {
            alertsSection.appendChild(DOM.create('p', { className: 'no-data' }, 'No active alerts'));
        } else {
            const alertsList = DOM.create('ul', { className: 'alerts-list' });
            alerts.slice(0, 5).forEach(alert => {
                const alertItem = DOM.create('li', { className: `alert-item ${alert.severity}` });
                alertItem.appendChild(DOM.create('span', { className: 'alert-message' }, alert.message));
                alertItem.appendChild(DOM.create('span', { className: 'alert-time' }, this.formatTimeAgo(alert.created_at)));
                alertsList.appendChild(alertItem);
            });
            alertsSection.appendChild(alertsList);
        }

        return alertsSection;
    }

    renderUpcomingDeliveries() {
        const deliveries = this.state.overview.upcoming_deliveries || [];
        const deliveriesSection = DOM.create('div', { className: 'dashboard-section' });
        deliveriesSection.appendChild(DOM.create('h3', {}, 'Upcoming Deliveries'));

        if (deliveries.length === 0) {
            deliveriesSection.appendChild(DOM.create('p', { className: 'no-data' }, 'No upcoming deliveries'));
        } else {
            const deliveriesList = DOM.create('ul', { className: 'deliveries-list' });
            deliveries.slice(0, 5).forEach(delivery => {
                const deliveryItem = DOM.create('li', { className: 'delivery-item' });
                deliveryItem.appendChild(DOM.create('span', { className: 'delivery-info' },
                    `${delivery.order_number} - ${delivery.supplier_name}`
                ));
                deliveryItem.appendChild(DOM.create('span', { className: `delivery-status ${delivery.delivery_status}` },
                    delivery.delivery_status.replace('_', ' ')
                ));
                deliveriesList.appendChild(deliveryItem);
            });
            deliveriesSection.appendChild(deliveriesList);
        }

        return deliveriesSection;
    }

    renderProducts() {
        const productsView = DOM.create('div', { className: 'products-view' });

        // Toolbar
        const toolbar = this.renderProductsToolbar();
        productsView.appendChild(toolbar);

        // Filters
        const filters = this.renderProductsFilters();
        productsView.appendChild(filters);

        // Products table
        const table = this.renderProductsTable();
        productsView.appendChild(table);

        // Pagination
        const pagination = this.renderPagination();
        productsView.appendChild(pagination);

        return productsView;
    }

    renderProductsToolbar() {
        const toolbar = DOM.create('div', { className: 'toolbar' });

        const leftSection = DOM.create('div', { className: 'toolbar-left' });

        // Bulk actions
        if (this.state.selectedProducts.length > 0) {
            const bulkActions = DOM.create('div', { className: 'bulk-actions' });
            bulkActions.appendChild(DOM.create('span', { className: 'selected-count' },
                `${this.state.selectedProducts.length} selected`
            ));

            const actions = ['update_status', 'update_category', 'export', 'delete'];
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
            onclick: () => this.showProductModal()
        });
        addButton.innerHTML = '<i class="fas fa-plus"></i> Add Product';
        rightSection.appendChild(addButton);

        toolbar.appendChild(leftSection);
        toolbar.appendChild(rightSection);

        return toolbar;
    }

    renderProductsFilters() {
        const filters = DOM.create('div', { className: 'filters' });

        // Search
        const searchGroup = DOM.create('div', { className: 'filter-group' });
        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control',
            placeholder: 'Search products...',
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
        this.state.categories.forEach(category => {
            categorySelect.appendChild(DOM.create('option', { value: category.id }, category.category_name));
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
        const statuses = ['', 'active', 'inactive', 'discontinued'];
        statuses.forEach(status => {
            statusSelect.appendChild(DOM.create('option', { value: status },
                status === '' ? 'All Statuses' : status.charAt(0).toUpperCase() + status.slice(1)
            ));
        });
        statusGroup.appendChild(DOM.create('label', {}, 'Status:'));
        statusGroup.appendChild(statusSelect);
        filters.appendChild(statusGroup);

        // Stock level filter
        const stockGroup = DOM.create('div', { className: 'filter-group' });
        const stockSelect = DOM.create('select', {
            className: 'form-control',
            value: this.state.filters.stock_level,
            onchange: (e) => this.handleFilterChange('stock_level', e.target.value)
        });
        const stockLevels = ['', 'out_of_stock', 'low_stock', 'normal', 'overstock'];
        stockLevels.forEach(level => {
            stockSelect.appendChild(DOM.create('option', { value: level },
                level === '' ? 'All Stock Levels' : level.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
            ));
        });
        stockGroup.appendChild(DOM.create('label', {}, 'Stock Level:'));
        stockGroup.appendChild(stockSelect);
        filters.appendChild(stockGroup);

        return filters;
    }

    renderProductsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'select', label: '', width: '40px' },
            { key: 'sku', label: 'SKU' },
            { key: 'product_name', label: 'Product Name' },
            { key: 'category', label: 'Category' },
            { key: 'stock_quantity', label: 'Stock' },
            { key: 'unit_cost', label: 'Cost' },
            { key: 'unit_price', label: 'Price' },
            { key: 'stock_status', label: 'Status' },
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

        this.state.products.forEach(product => {
            const row = DOM.create('tr', {});

            // Checkbox
            const checkboxCell = DOM.create('td', {});
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.state.selectedProducts.includes(product.id),
                onchange: (e) => this.handleProductSelect(product.id, e.target.checked)
            });
            checkboxCell.appendChild(checkbox);
            row.appendChild(checkboxCell);

            // SKU
            row.appendChild(DOM.create('td', {}, product.sku));

            // Product Name
            row.appendChild(DOM.create('td', {}, product.product_name));

            // Category
            row.appendChild(DOM.create('td', {}, product.category_name || 'N/A'));

            // Stock Quantity
            const stockCell = DOM.create('td', {});
            stockCell.appendChild(DOM.create('span', { className: 'stock-quantity' }, product.stock_quantity));
            if (product.reorder_point && product.stock_quantity <= product.reorder_point) {
                stockCell.appendChild(DOM.create('span', { className: 'low-stock-indicator' }, '!'));
            }
            row.appendChild(stockCell);

            // Unit Cost
            row.appendChild(DOM.create('td', {}, '$' + parseFloat(product.unit_cost).toFixed(2)));

            // Unit Price
            row.appendChild(DOM.create('td', {}, '$' + parseFloat(product.unit_price).toFixed(2)));

            // Stock Status
            const statusCell = DOM.create('td', {});
            const statusBadge = DOM.create('span', {
                className: `status-badge ${product.stock_status}`
            }, product.stock_status.replace('_', ' '));
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            // Actions
            const actionsCell = DOM.create('td', {});
            const actions = DOM.create('div', { className: 'table-actions' });

            const editButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.showProductModal(product)
            });
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            actions.appendChild(editButton);

            const movementButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-info',
                onclick: () => this.showMovementModal(product)
            });
            movementButton.innerHTML = '<i class="fas fa-exchange-alt"></i>';
            actions.appendChild(movementButton);

            const deleteButton = DOM.create('button', {
                className: 'btn btn-sm btn-outline-danger',
                onclick: () => this.deleteProduct(product.id)
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
            this.loadProducts();
        });
    }

    renderStockTracking() {
        const stockView = DOM.create('div', { className: 'stock-tracking-view' });

        // Toolbar
        const toolbar = DOM.create('div', { className: 'toolbar' });
        const addMovementButton = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: () => this.showMovementModal()
        });
        addMovementButton.innerHTML = '<i class="fas fa-plus"></i> Record Movement';
        toolbar.appendChild(addMovementButton);
        stockView.appendChild(toolbar);

        // Stock movements table
        const table = this.renderStockMovementsTable();
        stockView.appendChild(table);

        return stockView;
    }

    renderStockMovementsTable() {
        const table = DOM.create('div', { className: 'data-table-container' });
        const tableElement = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        const headers = [
            { key: 'movement_type', label: 'Type' },
            { key: 'product', label: 'Product' },
            { key: 'quantity', label: 'Quantity' },
            { key: 'warehouse', label: 'Warehouse' },
            { key: 'movement_date', label: 'Date' },
            { key: 'reason', label: 'Reason' },
            { key: 'processed_by', label: 'Processed By' }
        ];

        headers.forEach(header => {
            const th = DOM.create('th', {}, header.label);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody', {});

        this.state.stockMovements.forEach(movement => {
            const row = DOM.create('tr', {});

            // Movement Type
            const typeCell = DOM.create('td', {});
            const typeBadge = DOM.create('span', {
                className: `movement-type ${movement.movement_type}`
            }, movement.movement_type.replace('_', ' '));
            typeCell.appendChild(typeBadge);
            row.appendChild(typeCell);

            // Product
            row.appendChild(DOM.create('td', {}, movement.product_name));

            // Quantity
            const quantityCell = DOM.create('td', {});
            const quantitySpan = DOM.create('span', {
                className: movement.movement_type.includes('out') ? 'quantity-out' : 'quantity-in'
            }, (movement.movement_type.includes('out') ? '-' : '+') + movement.quantity);
            quantityCell.appendChild(quantitySpan);
            row.appendChild(quantityCell);

            // Warehouse
            row.appendChild(DOM.create('td', {}, movement.warehouse_name || 'N/A'));

            // Movement Date
            row.appendChild(DOM.create('td', {}, this.formatDate(movement.movement_date)));

            // Reason
            row.appendChild(DOM.create('td', {}, movement.reason));

            // Processed By
            row.appendChild(DOM.create('td', {}, movement.processed_by_first + ' ' + movement.processed_by_last));

            tbody.appendChild(row);
        });

        tableElement.appendChild(tbody);
        table.appendChild(tableElement);

        return table;
    }

    renderWarehouses() {
        const warehousesView = DOM.create('div', { className: 'warehouses-view' });

        const grid = DOM.create('div', { className: 'warehouses-grid' });

        this.state.warehouses.forEach(warehouse => {
            const card = DOM.create('div', { className: 'warehouse-card' });

            const header = DOM.create('div', { className: 'warehouse-header' });
            header.appendChild(DOM.create('h3', {}, warehouse.warehouse_name));
            header.appendChild(DOM.create('span', { className: 'warehouse-location' }, warehouse.location));
            card.appendChild(header);

            const stats = DOM.create('div', { className: 'warehouse-stats' });
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, warehouse.total_products || 0),
                DOM.create('span', { className: 'stat-label' }, 'Products')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, warehouse.total_stock || 0),
                DOM.create('span', { className: 'stat-label' }, 'Total Stock')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, (warehouse.utilization_percentage || 0) + '%'),
                DOM.create('span', { className: 'stat-label' }, 'Utilization')
            ));
            card.appendChild(stats);

            grid.appendChild(card);
        });

        warehousesView.appendChild(grid);
        return warehousesView;
    }

    renderSuppliers() {
        const suppliersView = DOM.create('div', { className: 'suppliers-view' });

        const grid = DOM.create('div', { className: 'suppliers-grid' });

        this.state.suppliers.forEach(supplier => {
            const card = DOM.create('div', { className: 'supplier-card' });

            const header = DOM.create('div', { className: 'supplier-header' });
            header.appendChild(DOM.create('h3', {}, supplier.supplier_name));
            header.appendChild(DOM.create('span', { className: 'supplier-code' }, supplier.supplier_code));
            card.appendChild(header);

            const stats = DOM.create('div', { className: 'supplier-stats' });
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, supplier.total_orders || 0),
                DOM.create('span', { className: 'stat-label' }, 'Orders')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, '$' + (supplier.total_order_value || 0).toLocaleString()),
                DOM.create('span', { className: 'stat-label' }, 'Total Value')
            ));
            stats.appendChild(DOM.create('div', { className: 'stat' },
                DOM.create('span', { className: 'stat-value' }, (supplier.rating || 0) + '/5'),
                DOM.create('span', { className: 'stat-label' }, 'Rating')
            ));
            card.appendChild(stats);

            grid.appendChild(card);
        });

        suppliersView.appendChild(grid);
        return suppliersView;
    }

    renderAnalytics() {
        const analyticsView = DOM.create('div', { className: 'analytics-view' });

        // Placeholder for analytics content
        analyticsView.appendChild(DOM.create('div', { className: 'analytics-placeholder' },
            DOM.create('h3', {}, 'Inventory Analytics'),
            DOM.create('p', {}, 'Analytics dashboard coming soon...')
        ));

        return analyticsView;
    }

    renderProductModal() {
        // Placeholder for product modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {},
            this.state.editingProduct ? 'Edit Product' : 'Add New Product'
        ));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideProductModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Product form coming soon...'));
        modalContent.appendChild(body);

        modal.appendChild(modalContent);
        return modal;
    }

    renderMovementModal() {
        // Placeholder for movement modal
        const modal = DOM.create('div', { className: 'modal-overlay' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        const header = DOM.create('div', { className: 'modal-header' });
        header.appendChild(DOM.create('h3', {}, 'Record Stock Movement'));
        const closeButton = DOM.create('button', {
            className: 'modal-close',
            onclick: () => this.hideMovementModal()
        }, '×');
        header.appendChild(closeButton);
        modalContent.appendChild(header);

        const body = DOM.create('div', { className: 'modal-body' });
        body.appendChild(DOM.create('p', {}, 'Stock movement form coming soon...'));
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
ComponentRegistry.register('Inventory', Inventory);

// Make globally available
if (typeof window !== 'undefined') {
    window.Inventory = Inventory;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Inventory;
}
