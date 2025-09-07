/**
 * TPT Free ERP - DataTable Component
 * Advanced data table with sorting, filtering, pagination, and export capabilities
 */

class DataTable extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            columns: [],
            data: [],
            sortable: true,
            filterable: true,
            paginated: true,
            selectable: false,
            exportable: true,
            searchable: true,
            pageSize: 25,
            pageSizeOptions: [10, 25, 50, 100],
            emptyMessage: 'No data available',
            loadingMessage: 'Loading data...',
            height: null,
            striped: true,
            bordered: true,
            hover: true,
            compact: false,
            responsive: true,
            onRowClick: null,
            onRowSelect: null,
            onSort: null,
            onFilter: null,
            onPageChange: null,
            onExport: null,
            ...props
        };

        this.state = {
            filteredData: [],
            sortedData: [],
            currentPage: 1,
            pageSize: this.props.pageSize,
            sortColumn: null,
            sortDirection: 'asc',
            filters: {},
            searchQuery: '',
            selectedRows: new Set(),
            isLoading: false,
            expandedRows: new Set()
        };

        // Bind methods
        this.handleSort = this.handleSort.bind(this);
        this.handleFilter = this.handleFilter.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handlePageChange = this.handlePageChange.bind(this);
        this.handlePageSizeChange = this.handlePageSizeChange.bind(this);
        this.handleRowClick = this.handleRowClick.bind(this);
        this.handleRowSelect = this.handleRowSelect.bind(this);
        this.handleSelectAll = this.handleSelectAll.bind(this);
        this.handleExport = this.handleExport.bind(this);
        this.handleRowExpand = this.handleRowExpand.bind(this);
        this.applyFiltersAndSort = this.applyFiltersAndSort.bind(this);
        this.getPaginatedData = this.getPaginatedData.bind(this);
        this.renderCell = this.renderCell.bind(this);
    }

    componentDidMount() {
        this.applyFiltersAndSort();
    }

    componentDidUpdate(prevProps) {
        if (prevProps.data !== this.props.data ||
            prevProps.columns !== this.props.columns) {
            this.applyFiltersAndSort();
        }
    }

    applyFiltersAndSort() {
        let data = [...this.props.data];

        // Apply search filter
        if (this.state.searchQuery) {
            const query = this.state.searchQuery.toLowerCase();
            data = data.filter(row =>
                this.props.columns.some(column => {
                    const value = this.getCellValue(row, column);
                    return String(value).toLowerCase().includes(query);
                })
            );
        }

        // Apply column filters
        Object.entries(this.state.filters).forEach(([columnKey, filterValue]) => {
            if (filterValue) {
                data = data.filter(row => {
                    const value = this.getCellValue(row, columnKey);
                    return String(value).toLowerCase().includes(filterValue.toLowerCase());
                });
            }
        });

        // Apply sorting
        if (this.state.sortColumn) {
            data.sort((a, b) => {
                const aValue = this.getCellValue(a, this.state.sortColumn);
                const bValue = this.getCellValue(b, this.state.sortColumn);

                let result = 0;
                if (aValue < bValue) result = -1;
                if (aValue > bValue) result = 1;

                return this.state.sortDirection === 'desc' ? -result : result;
            });
        }

        this.setState({
            filteredData: data,
            sortedData: data,
            currentPage: 1 // Reset to first page when data changes
        });
    }

    getCellValue(row, columnKey) {
        if (typeof columnKey === 'string') {
            return row[columnKey];
        }
        return columnKey.accessor ? columnKey.accessor(row) : row[columnKey.key];
    }

    getPaginatedData() {
        const { filteredData, currentPage, pageSize } = this.state;
        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        return filteredData.slice(startIndex, endIndex);
    }

    handleSort(columnKey) {
        const { sortColumn, sortDirection } = this.state;

        let newDirection = 'asc';
        if (sortColumn === columnKey && sortDirection === 'asc') {
            newDirection = 'desc';
        }

        this.setState({
            sortColumn: columnKey,
            sortDirection: newDirection
        }, () => {
            this.applyFiltersAndSort();
            if (this.props.onSort) {
                this.props.onSort(columnKey, newDirection);
            }
        });
    }

    handleFilter(columnKey, value) {
        const newFilters = { ...this.state.filters };
        if (value) {
            newFilters[columnKey] = value;
        } else {
            delete newFilters[columnKey];
        }

        this.setState({ filters: newFilters }, () => {
            this.applyFiltersAndSort();
            if (this.props.onFilter) {
                this.props.onFilter(newFilters);
            }
        });
    }

    handleSearch(query) {
        this.setState({ searchQuery: query }, () => {
            this.applyFiltersAndSort();
        });
    }

    handlePageChange(page) {
        this.setState({ currentPage: page });
        if (this.props.onPageChange) {
            this.props.onPageChange(page);
        }
    }

    handlePageSizeChange(pageSize) {
        this.setState({
            pageSize: pageSize,
            currentPage: 1
        });
    }

    handleRowClick(row, index, event) {
        if (this.props.onRowClick) {
            this.props.onRowClick(row, index, event);
        }
    }

    handleRowSelect(row, index, selected) {
        const newSelectedRows = new Set(this.state.selectedRows);
        if (selected) {
            newSelectedRows.add(index);
        } else {
            newSelectedRows.delete(index);
        }

        this.setState({ selectedRows: newSelectedRows });
        if (this.props.onRowSelect) {
            this.props.onRowSelect(row, selected, newSelectedRows);
        }
    }

    handleSelectAll(selected) {
        const newSelectedRows = new Set();
        if (selected) {
            this.getPaginatedData().forEach((_, index) => {
                newSelectedRows.add((this.state.currentPage - 1) * this.state.pageSize + index);
            });
        }
        this.setState({ selectedRows: newSelectedRows });
    }

    handleExport(format) {
        const data = this.state.filteredData;
        const filename = `export_${new Date().toISOString().split('T')[0]}`;

        switch (format) {
            case 'csv':
                this.exportToCSV(data, filename);
                break;
            case 'excel':
                this.exportToExcel(data, filename);
                break;
            case 'pdf':
                this.exportToPDF(data, filename);
                break;
            default:
                console.warn(`Unsupported export format: ${format}`);
        }

        if (this.props.onExport) {
            this.props.onExport(format, data);
        }
    }

    handleRowExpand(rowIndex, expanded) {
        const newExpandedRows = new Set(this.state.expandedRows);
        if (expanded) {
            newExpandedRows.add(rowIndex);
        } else {
            newExpandedRows.delete(rowIndex);
        }
        this.setState({ expandedRows: newExpandedRows });
    }

    exportToCSV(data, filename) {
        const headers = this.props.columns.map(col => col.title || col.key).join(',');
        const rows = data.map(row =>
            this.props.columns.map(col => {
                const value = this.getCellValue(row, col);
                return `"${String(value).replace(/"/g, '""')}"`;
            }).join(',')
        );

        const csv = [headers, ...rows].join('\n');
        this.downloadFile(csv, `${filename}.csv`, 'text/csv');
    }

    exportToExcel(data, filename) {
        // This would require a library like SheetJS
        console.log('Excel export not implemented - requires additional library');
        App.showNotification({
            type: 'warning',
            message: 'Excel export requires additional library'
        });
    }

    exportToPDF(data, filename) {
        // This would require a library like jsPDF
        console.log('PDF export not implemented - requires additional library');
        App.showNotification({
            type: 'warning',
            message: 'PDF export requires additional library'
        });
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    render() {
        const {
            columns,
            selectable,
            searchable,
            paginated,
            exportable,
            height,
            striped,
            bordered,
            hover,
            compact,
            responsive
        } = this.props;

        const {
            filteredData,
            currentPage,
            pageSize,
            sortColumn,
            sortDirection,
            searchQuery,
            selectedRows,
            isLoading
        } = this.state;

        const tableClasses = [
            'data-table',
            striped && 'table-striped',
            bordered && 'table-bordered',
            hover && 'table-hover',
            compact && 'table-compact'
        ].filter(Boolean).join(' ');

        const wrapperClasses = [
            'data-table-wrapper',
            responsive && 'table-responsive'
        ].filter(Boolean).join(' ');

        const wrapper = DOM.create('div', { className: wrapperClasses });

        // Toolbar
        if (searchable || exportable) {
            const toolbar = DOM.create('div', { className: 'data-table-toolbar' });

            if (searchable) {
                const searchGroup = DOM.create('div', { className: 'search-group' });
                const searchInput = DOM.create('input', {
                    type: 'text',
                    className: 'form-control search-input',
                    placeholder: 'Search...',
                    value: searchQuery,
                    oninput: (e) => this.handleSearch(e.target.value)
                });
                const searchIcon = DOM.create('i', { className: 'fas fa-search search-icon' });

                searchGroup.appendChild(searchInput);
                searchGroup.appendChild(searchIcon);
                toolbar.appendChild(searchGroup);
            }

            if (exportable) {
                const exportGroup = DOM.create('div', { className: 'export-group' });
                const exportBtn = DOM.create('button', {
                    className: 'btn btn-secondary btn-sm export-btn',
                    onclick: () => this.showExportMenu()
                });
                exportBtn.innerHTML = '<i class="fas fa-download"></i> Export';

                exportGroup.appendChild(exportBtn);
                toolbar.appendChild(exportGroup);
            }

            wrapper.appendChild(toolbar);
        }

        // Table container
        const tableContainer = DOM.create('div', {
            className: 'table-container',
            style: height ? `height: ${height}; overflow: auto;` : ''
        });

        const table = DOM.create('table', { className: tableClasses });

        // Table header
        const thead = DOM.create('thead');
        const headerRow = DOM.create('tr');

        // Selection column
        if (selectable) {
            const selectAllCell = DOM.create('th', { className: 'select-column' });
            const selectAllCheckbox = DOM.create('input', {
                type: 'checkbox',
                className: 'select-all-checkbox',
                onchange: (e) => this.handleSelectAll(e.target.checked),
                checked: this.isAllSelected(),
                indeterminate: this.isPartiallySelected()
            });
            selectAllCell.appendChild(selectAllCheckbox);
            headerRow.appendChild(selectAllCell);
        }

        // Data columns
        columns.forEach(column => {
            const th = DOM.create('th', {
                className: column.sortable !== false && this.props.sortable ? 'sortable' : '',
                'data-column': column.key,
                onclick: column.sortable !== false && this.props.sortable ?
                    () => this.handleSort(column.key) : null
            });

            const headerContent = DOM.create('div', { className: 'header-content' });

            // Column title
            const title = DOM.create('span', { className: 'column-title' },
                column.title || column.key);

            // Sort indicator
            if (column.sortable !== false && this.props.sortable) {
                const sortIcon = DOM.create('i', {
                    className: `sort-icon fas ${
                        sortColumn === column.key ?
                            (sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') :
                            'fa-sort'
                    }`
                });
                headerContent.appendChild(sortIcon);
            }

            // Filter input
            if (column.filterable !== false && this.props.filterable) {
                const filterInput = DOM.create('input', {
                    type: 'text',
                    className: 'column-filter',
                    placeholder: `Filter ${column.title || column.key}`,
                    value: this.state.filters[column.key] || '',
                    oninput: (e) => this.handleFilter(column.key, e.target.value)
                });
                headerContent.appendChild(filterInput);
            }

            headerContent.insertBefore(title, headerContent.firstChild);
            th.appendChild(headerContent);
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Table body
        const tbody = DOM.create('tbody');

        if (isLoading) {
            const loadingRow = DOM.create('tr');
            const loadingCell = DOM.create('td', {
                colspan: columns.length + (selectable ? 1 : 0),
                className: 'loading-cell'
            });
            loadingCell.innerHTML = `
                <div class="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>${this.props.loadingMessage}</span>
                </div>
            `;
            loadingRow.appendChild(loadingCell);
            tbody.appendChild(loadingRow);
        } else if (filteredData.length === 0) {
            const emptyRow = DOM.create('tr');
            const emptyCell = DOM.create('td', {
                colspan: columns.length + (selectable ? 1 : 0),
                className: 'empty-cell'
            });
            emptyCell.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>${this.props.emptyMessage}</p>
                </div>
            `;
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
        } else {
            const paginatedData = this.getPaginatedData();
            paginatedData.forEach((row, index) => {
                const actualIndex = (currentPage - 1) * pageSize + index;
                const tr = DOM.create('tr', {
                    className: selectedRows.has(actualIndex) ? 'selected' : '',
                    onclick: (e) => this.handleRowClick(row, actualIndex, e)
                });

                // Selection column
                if (selectable) {
                    const selectCell = DOM.create('td', { className: 'select-column' });
                    const checkbox = DOM.create('input', {
                        type: 'checkbox',
                        className: 'row-checkbox',
                        checked: selectedRows.has(actualIndex),
                        onchange: (e) => this.handleRowSelect(row, actualIndex, e.target.checked)
                    });
                    selectCell.appendChild(checkbox);
                    tr.appendChild(selectCell);
                }

                // Data columns
                columns.forEach(column => {
                    const cell = this.renderCell(row, column, actualIndex);
                    tr.appendChild(cell);
                });

                tbody.appendChild(tr);

                // Expanded row
                if (this.state.expandedRows.has(actualIndex) && column.expandable) {
                    const expandedRow = DOM.create('tr', { className: 'expanded-row' });
                    const expandedCell = DOM.create('td', {
                        colspan: columns.length + (selectable ? 1 : 0)
                    });
                    const expandedContent = DOM.create('div', { className: 'expanded-content' });

                    if (column.renderExpanded) {
                        const content = column.renderExpanded(row, actualIndex);
                        if (typeof content === 'string') {
                            expandedContent.innerHTML = content;
                        } else if (content instanceof Node) {
                            expandedContent.appendChild(content);
                        }
                    }

                    expandedCell.appendChild(expandedContent);
                    expandedRow.appendChild(expandedCell);
                    tbody.appendChild(expandedRow);
                }
            });
        }

        table.appendChild(tbody);
        tableContainer.appendChild(table);
        wrapper.appendChild(tableContainer);

        // Pagination
        if (paginated && filteredData.length > pageSize) {
            const pagination = this.renderPagination();
            wrapper.appendChild(pagination);
        }

        // Selection info
        if (selectable && selectedRows.size > 0) {
            const selectionInfo = DOM.create('div', { className: 'selection-info' });
            selectionInfo.textContent = `${selectedRows.size} row(s) selected`;
            wrapper.appendChild(selectionInfo);
        }

        return wrapper;
    }

    renderCell(row, column, rowIndex) {
        const td = DOM.create('td', {
            className: column.className || '',
            'data-column': column.key
        });

        let content;
        if (column.render) {
            content = column.render(this.getCellValue(row, column), row, rowIndex);
        } else {
            const value = this.getCellValue(row, column);
            content = value !== null && value !== undefined ? String(value) : '';
        }

        if (typeof content === 'string') {
            td.textContent = content;
        } else if (content instanceof Node) {
            td.appendChild(content);
        }

        return td;
    }

    renderPagination() {
        const { filteredData, currentPage, pageSize } = this.state;
        const totalPages = Math.ceil(filteredData.length / pageSize);

        const pagination = DOM.create('div', { className: 'data-table-pagination' });

        // Page size selector
        const pageSizeGroup = DOM.create('div', { className: 'page-size-group' });
        const pageSizeLabel = DOM.create('span', { className: 'page-size-label' }, 'Show:');
        const pageSizeSelect = DOM.create('select', {
            className: 'page-size-select',
            onchange: (e) => this.handlePageSizeChange(parseInt(e.target.value))
        });

        this.props.pageSizeOptions.forEach(size => {
            const option = DOM.create('option', {
                value: size,
                selected: size === pageSize
            }, size.toString());
            pageSizeSelect.appendChild(option);
        });

        pageSizeGroup.appendChild(pageSizeLabel);
        pageSizeGroup.appendChild(pageSizeSelect);
        pagination.appendChild(pageSizeGroup);

        // Page info
        const startItem = (currentPage - 1) * pageSize + 1;
        const endItem = Math.min(currentPage * pageSize, filteredData.length);
        const pageInfo = DOM.create('div', { className: 'page-info' });
        pageInfo.textContent = `Showing ${startItem} to ${endItem} of ${filteredData.length} entries`;
        pagination.appendChild(pageInfo);

        // Page navigation
        const pageNav = DOM.create('div', { className: 'page-navigation' });

        // Previous button
        const prevBtn = DOM.create('button', {
            className: 'btn btn-secondary btn-sm page-btn',
            disabled: currentPage === 1,
            onclick: () => this.handlePageChange(currentPage - 1)
        }, 'Previous');
        pageNav.appendChild(prevBtn);

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            const firstBtn = DOM.create('button', {
                className: 'btn btn-secondary btn-sm page-btn',
                onclick: () => this.handlePageChange(1)
            }, '1');
            pageNav.appendChild(firstBtn);

            if (startPage > 2) {
                const ellipsis = DOM.create('span', { className: 'page-ellipsis' }, '...');
                pageNav.appendChild(ellipsis);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = DOM.create('button', {
                className: `btn btn-sm page-btn ${i === currentPage ? 'active' : 'btn-secondary'}`,
                onclick: () => this.handlePageChange(i)
            }, i.toString());
            pageNav.appendChild(pageBtn);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = DOM.create('span', { className: 'page-ellipsis' }, '...');
                pageNav.appendChild(ellipsis);
            }

            const lastBtn = DOM.create('button', {
                className: 'btn btn-secondary btn-sm page-btn',
                onclick: () => this.handlePageChange(totalPages)
            }, totalPages.toString());
            pageNav.appendChild(lastBtn);
        }

        // Next button
        const nextBtn = DOM.create('button', {
            className: 'btn btn-secondary btn-sm page-btn',
            disabled: currentPage === totalPages,
            onclick: () => this.handlePageChange(currentPage + 1)
        }, 'Next');
        pageNav.appendChild(nextBtn);

        pagination.appendChild(pageNav);

        return pagination;
    }

    showExportMenu() {
        const menu = DOM.create('div', { className: 'export-menu' });
        const csvBtn = DOM.create('button', {
            className: 'export-option',
            onclick: () => this.handleExport('csv')
        }, 'Export as CSV');

        const excelBtn = DOM.create('button', {
            className: 'export-option',
            onclick: () => this.handleExport('excel')
        }, 'Export as Excel');

        const pdfBtn = DOM.create('button', {
            className: 'export-option',
            onclick: () => this.handleExport('pdf')
        }, 'Export as PDF');

        menu.appendChild(csvBtn);
        menu.appendChild(excelBtn);
        menu.appendChild(pdfBtn);

        // Position and show menu
        // This would need more implementation for proper positioning
        document.body.appendChild(menu);
    }

    isAllSelected() {
        const paginatedData = this.getPaginatedData();
        return paginatedData.length > 0 &&
               paginatedData.every((_, index) =>
                   this.state.selectedRows.has((this.state.currentPage - 1) * this.state.pageSize + index)
               );
    }

    isPartiallySelected() {
        const paginatedData = this.getPaginatedData();
        const selectedCount = paginatedData.filter((_, index) =>
            this.state.selectedRows.has((this.state.currentPage - 1) * this.state.pageSize + index)
        ).length;
        return selectedCount > 0 && selectedCount < paginatedData.length;
    }

    // Public API methods
    setData(data) {
        this.props.data = data;
        this.applyFiltersAndSort();
    }

    getSelectedRows() {
        return Array.from(this.state.selectedRows).map(index => this.props.data[index]);
    }

    clearSelection() {
        this.setState({ selectedRows: new Set() });
    }

    setPage(page) {
        this.handlePageChange(page);
    }

    setPageSize(pageSize) {
        this.handlePageSizeChange(pageSize);
    }

    sort(columnKey, direction = 'asc') {
        this.setState({
            sortColumn: columnKey,
            sortDirection: direction
        }, () => this.applyFiltersAndSort());
    }

    filter(filters) {
        this.setState({ filters }, () => this.applyFiltersAndSort());
    }

    search(query) {
        this.handleSearch(query);
    }

    refresh() {
        this.applyFiltersAndSort();
    }
}

// Register component
ComponentRegistry.register('DataTable', DataTable);

// Make globally available
window.DataTable = DataTable;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataTable;
}
