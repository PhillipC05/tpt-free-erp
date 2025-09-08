/**
 * Table Renderer Utility
 * Provides reusable table rendering functionality for data tables
 */

class TableRenderer {
    constructor(options = {}) {
        this.options = {
            sortable: true,
            selectable: false,
            pagination: true,
            search: true,
            exportable: false,
            ...options
        };

        this.currentSort = { column: null, direction: 'asc' };
        this.selectedRows = new Set();
        this.currentPage = 1;
        this.pageSize = 50;
        this.searchTerm = '';
    }

    /**
     * Render a complete data table with all features
     */
    renderTable(container, data, columns, options = {}) {
        const tableOptions = { ...this.options, ...options };
        const tableContainer = DOM.create('div', { className: 'data-table-container' });

        // Search bar
        if (tableOptions.search) {
            const searchBar = this.renderSearchBar();
            tableContainer.appendChild(searchBar);
        }

        // Bulk actions toolbar
        if (tableOptions.selectable && this.selectedRows.size > 0) {
            const bulkToolbar = this.renderBulkActionsToolbar();
            tableContainer.appendChild(bulkToolbar);
        }

        // Table element
        const table = this.renderTableElement(data, columns, tableOptions);
        tableContainer.appendChild(table);

        // Pagination
        if (tableOptions.pagination && data.length > this.pageSize) {
            const pagination = this.renderPagination(data.length);
            tableContainer.appendChild(pagination);
        }

        // Export options
        if (tableOptions.exportable) {
            const exportBar = this.renderExportBar();
            tableContainer.appendChild(exportBar);
        }

        // Replace container content
        container.innerHTML = '';
        container.appendChild(tableContainer);

        return tableContainer;
    }

    /**
     * Render the table element with headers and data
     */
    renderTableElement(data, columns, options) {
        const table = DOM.create('table', { className: 'data-table' });

        // Table header
        const thead = this.renderTableHeader(columns, options);
        table.appendChild(thead);

        // Table body
        const tbody = this.renderTableBody(data, columns, options);
        table.appendChild(tbody);

        return table;
    }

    /**
     * Render table header with sorting
     */
    renderTableHeader(columns, options) {
        const thead = DOM.create('thead', {});
        const headerRow = DOM.create('tr', {});

        // Selection column
        if (options.selectable) {
            const selectAllTh = DOM.create('th', { className: 'select-column' });
            const selectAllCheckbox = DOM.create('input', {
                type: 'checkbox',
                checked: this.selectedRows.size === this.getFilteredData().length && this.getFilteredData().length > 0,
                onchange: (e) => this.handleSelectAll(e.target.checked)
            });
            selectAllTh.appendChild(selectAllCheckbox);
            headerRow.appendChild(selectAllTh);
        }

        // Data columns
        columns.forEach(column => {
            const th = DOM.create('th', {
                className: column.sortable !== false && options.sortable ? 'sortable' : '',
                style: column.width ? `width: ${column.width};` : '',
                onclick: column.sortable !== false && options.sortable ? () => this.handleSort(column.key) : null
            });

            // Column title
            const titleSpan = DOM.create('span', { className: 'column-title' }, column.label || column.key);

            // Sort indicator
            if (column.sortable !== false && options.sortable) {
                const sortIcon = DOM.create('i', {
                    className: `fas sort-icon ${
                        this.currentSort.column === column.key
                            ? (this.currentSort.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
                            : 'fa-sort'
                    }`
                });
                th.appendChild(sortIcon);
            }

            th.appendChild(titleSpan);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        return thead;
    }

    /**
     * Render table body with data rows
     */
    renderTableBody(data, columns, options) {
        const tbody = DOM.create('tbody', {});
        const filteredData = this.getFilteredData(data);

        if (filteredData.length === 0) {
            const emptyRow = DOM.create('tr', {});
            const emptyCell = DOM.create('td', {
                colspan: columns.length + (options.selectable ? 1 : 0),
                className: 'empty-state'
            }, 'No data available');
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
            return tbody;
        }

        const paginatedData = this.getPaginatedData(filteredData);

        paginatedData.forEach((row, index) => {
            const tr = DOM.create('tr', {
                className: this.selectedRows.has(row.id) ? 'selected' : ''
            });

            // Selection column
            if (options.selectable) {
                const selectCell = DOM.create('td', { className: 'select-column' });
                const checkbox = DOM.create('input', {
                    type: 'checkbox',
                    checked: this.selectedRows.has(row.id),
                    onchange: (e) => this.handleRowSelect(row.id, e.target.checked)
                });
                selectCell.appendChild(checkbox);
                tr.appendChild(selectCell);
            }

            // Data columns
            columns.forEach(column => {
                const cell = this.renderTableCell(row, column);
                tr.appendChild(cell);
            });

            tbody.appendChild(tr);
        });

        return tbody;
    }

    /**
     * Render individual table cell
     */
    renderTableCell(row, column) {
        const value = this.getNestedValue(row, column.key);
        const formattedValue = this.formatCellValue(value, column);

        const td = DOM.create('td', {
            className: column.className || '',
            style: column.cellStyle ? column.cellStyle(value, row) : ''
        });

        if (column.renderer) {
            // Custom renderer function
            const renderedContent = column.renderer(value, row, column);
            if (typeof renderedContent === 'string') {
                td.innerHTML = renderedContent;
            } else if (renderedContent instanceof Node) {
                td.appendChild(renderedContent);
            }
        } else {
            td.textContent = formattedValue;
        }

        return td;
    }

    /**
     * Render search bar
     */
    renderSearchBar() {
        const searchContainer = DOM.create('div', { className: 'table-search' });

        const searchInput = DOM.create('input', {
            type: 'text',
            className: 'form-control search-input',
            placeholder: 'Search...',
            value: this.searchTerm,
            oninput: (e) => this.handleSearch(e.target.value)
        });

        const searchIcon = DOM.create('i', { className: 'fas fa-search search-icon' });

        searchContainer.appendChild(searchIcon);
        searchContainer.appendChild(searchInput);

        return searchContainer;
    }

    /**
     * Render bulk actions toolbar
     */
    renderBulkActionsToolbar() {
        const toolbar = DOM.create('div', { className: 'bulk-actions-toolbar' });

        const selectedCount = DOM.create('span', { className: 'selected-count' },
            `${this.selectedRows.size} selected`
        );

        const actionsContainer = DOM.create('div', { className: 'bulk-actions' });

        // Default bulk actions - can be customized
        const defaultActions = ['delete', 'export', 'update'];

        defaultActions.forEach(action => {
            const button = DOM.create('button', {
                className: 'btn btn-sm btn-outline-secondary',
                onclick: () => this.handleBulkAction(action)
            }, action.charAt(0).toUpperCase() + action.slice(1));
            actionsContainer.appendChild(button);
        });

        toolbar.appendChild(selectedCount);
        toolbar.appendChild(actionsContainer);

        return toolbar;
    }

    /**
     * Render pagination controls
     */
    renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.pageSize);
        const pagination = DOM.create('div', { className: 'pagination' });

        // Previous button
        if (this.currentPage > 1) {
            const prevButton = DOM.create('button', {
                className: 'btn btn-outline-secondary',
                onclick: () => this.changePage(this.currentPage - 1)
            }, 'Previous');
            pagination.appendChild(prevButton);
        }

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageButton = DOM.create('button', {
                className: `btn ${i === this.currentPage ? 'btn-primary' : 'btn-outline-secondary'}`,
                onclick: () => this.changePage(i)
            }, i.toString());
            pagination.appendChild(pageButton);
        }

        // Next button
        if (this.currentPage < totalPages) {
            const nextButton = DOM.create('button', {
                className: 'btn btn-outline-secondary',
                onclick: () => this.changePage(this.currentPage + 1)
            }, 'Next');
            pagination.appendChild(nextButton);
        }

        return pagination;
    }

    /**
     * Render export options
     */
    renderExportBar() {
        const exportBar = DOM.create('div', { className: 'export-bar' });

        const exportFormats = ['csv', 'excel', 'pdf'];

        exportFormats.forEach(format => {
            const button = DOM.create('button', {
                className: 'btn btn-sm btn-outline-primary',
                onclick: () => this.handleExport(format)
            });
            button.innerHTML = `<i class="fas fa-file-${format}"></i> Export ${format.toUpperCase()}`;
            exportBar.appendChild(button);
        });

        return exportBar;
    }

    // ============================================================================
    // EVENT HANDLERS
    // ============================================================================

    handleSearch(term) {
        this.searchTerm = term;
        this.currentPage = 1;
        this.onDataChange && this.onDataChange();
    }

    handleSort(columnKey) {
        if (this.currentSort.column === columnKey) {
            this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSort.column = columnKey;
            this.currentSort.direction = 'asc';
        }
        this.onDataChange && this.onDataChange();
    }

    handleSelectAll(selected) {
        if (selected) {
            const filteredData = this.getFilteredData();
            filteredData.forEach(item => this.selectedRows.add(item.id));
        } else {
            this.selectedRows.clear();
        }
        this.onSelectionChange && this.onSelectionChange([...this.selectedRows]);
    }

    handleRowSelect(rowId, selected) {
        if (selected) {
            this.selectedRows.add(rowId);
        } else {
            this.selectedRows.delete(rowId);
        }
        this.onSelectionChange && this.onSelectionChange([...this.selectedRows]);
    }

    handleBulkAction(action) {
        this.onBulkAction && this.onBulkAction(action, [...this.selectedRows]);
    }

    handleExport(format) {
        this.onExport && this.onExport(format, this.getFilteredData());
    }

    changePage(page) {
        this.currentPage = page;
        this.onDataChange && this.onDataChange();
    }

    // ============================================================================
    // DATA PROCESSING METHODS
    // ============================================================================

    getFilteredData(data = null) {
        const sourceData = data || (this.onGetData ? this.onGetData() : []);
        if (!this.searchTerm) return sourceData;

        return sourceData.filter(item => {
            return Object.values(item).some(value =>
                String(value).toLowerCase().includes(this.searchTerm.toLowerCase())
            );
        });
    }

    getPaginatedData(data) {
        const startIndex = (this.currentPage - 1) * this.pageSize;
        const endIndex = startIndex + this.pageSize;
        return data.slice(startIndex, endIndex);
    }

    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }

    formatCellValue(value, column) {
        if (value === null || value === undefined) return '';

        if (column.formatter) {
            return column.formatter(value);
        }

        // Default formatters
        if (column.type === 'date' && value) {
            return new Date(value).toLocaleDateString();
        }

        if (column.type === 'datetime' && value) {
            return new Date(value).toLocaleString();
        }

        if (column.type === 'currency' && typeof value === 'number') {
            return '$' + value.toLocaleString();
        }

        if (column.type === 'percentage' && typeof value === 'number') {
            return value + '%';
        }

        return String(value);
    }

    // ============================================================================
    // CONFIGURATION METHODS
    // ============================================================================

    setDataCallback(callback) {
        this.onGetData = callback;
    }

    setSelectionCallback(callback) {
        this.onSelectionChange = callback;
    }

    setBulkActionCallback(callback) {
        this.onBulkAction = callback;
    }

    setExportCallback(callback) {
        this.onExport = callback;
    }

    setDataChangeCallback(callback) {
        this.onDataChange = callback;
    }

    setPageSize(size) {
        this.pageSize = size;
        this.currentPage = 1;
    }

    getSelectedRows() {
        return [...this.selectedRows];
    }

    clearSelection() {
        this.selectedRows.clear();
        this.onSelectionChange && this.onSelectionChange([]);
    }

    // ============================================================================
    // STATIC METHODS FOR COMMON USE CASES
    // ============================================================================

    static createEmployeeTable(container, employees, options = {}) {
        const columns = [
            { key: 'employee_id', label: 'Employee ID', width: '120px' },
            { key: 'first_name', label: 'Name', renderer: (value, row) => `${row.first_name} ${row.last_name}` },
            { key: 'department_name', label: 'Department' },
            { key: 'position_title', label: 'Position' },
            { key: 'hire_date', label: 'Hire Date', type: 'date' },
            { key: 'employment_status', label: 'Status', renderer: (value) => {
                const badge = DOM.create('span', { className: `status-badge ${value}` });
                badge.textContent = value;
                return badge;
            }}
        ];

        const renderer = new TableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            ...options
        });

        renderer.setDataCallback(() => employees);

        return renderer.renderTable(container, employees, columns);
    }

    static createProjectTable(container, projects, options = {}) {
        const columns = [
            { key: 'project_name', label: 'Project Name' },
            { key: 'manager_name', label: 'Manager' },
            { key: 'status', label: 'Status', renderer: (value) => {
                const badge = DOM.create('span', { className: `status-badge ${value}` });
                badge.textContent = value.replace('_', ' ');
                return badge;
            }},
            { key: 'progress_percentage', label: 'Progress', renderer: (value) => {
                const progress = DOM.create('div', { className: 'progress-bar small' });
                const fill = DOM.create('div', { className: 'progress-fill', style: `width: ${value}%` });
                fill.textContent = `${value}%`;
                progress.appendChild(fill);
                return progress;
            }},
            { key: 'start_date', label: 'Start Date', type: 'date' },
            { key: 'end_date', label: 'End Date', type: 'date' },
            { key: 'budget', label: 'Budget', type: 'currency' }
        ];

        const renderer = new TableRenderer({
            selectable: true,
            sortable: true,
            search: true,
            exportable: true,
            ...options
        });

        renderer.setDataCallback(() => projects);

        return renderer.renderTable(container, projects, columns);
    }
}

// Make globally available
if (typeof window !== 'undefined') {
    window.TableRenderer = TableRenderer;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TableRenderer;
}
