/**
 * TPT Free ERP - Component Architecture
 * Reusable UI components system
 */

class Component {
    constructor(props = {}) {
        this.props = { ...props };
        this.state = {};
        this.element = null;
        this.children = [];
        this.eventListeners = new Map();
        this.isMounted = false;
        this.lifecycleHooks = {
            beforeMount: [],
            mounted: [],
            beforeUpdate: [],
            updated: [],
            beforeUnmount: [],
            unmounted: []
        };
    }

    /**
     * Set component properties
     */
    setProps(newProps) {
        const oldProps = { ...this.props };
        this.props = { ...this.props, ...newProps };
        this.onPropsChange(oldProps, this.props);
    }

    /**
     * Set component state
     */
    setState(newState) {
        const oldState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.onStateChange(oldState, this.state);
        this.update();
    }

    /**
     * Render component
     */
    render() {
        // Override in subclasses
        return DOM.create('div', { className: 'component' }, 'Component');
    }

    /**
     * Update component
     */
    update() {
        if (!this.isMounted) return;

        // Call beforeUpdate hooks
        this.lifecycleHooks.beforeUpdate.forEach(hook => hook());

        const newElement = this.render();
        if (this.element && this.element.parentNode) {
            this.element.parentNode.replaceChild(newElement, this.element);
        }
        this.element = newElement;

        // Call updated hooks
        this.lifecycleHooks.updated.forEach(hook => hook());
    }

    /**
     * Mount component to DOM
     */
    mount(container) {
        if (this.isMounted) return;

        // Call beforeMount hooks
        this.lifecycleHooks.beforeMount.forEach(hook => hook());

        this.element = this.render();
        container.appendChild(this.element);
        this.isMounted = true;

        // Setup event listeners
        this.setupEventListeners();

        // Call mounted hooks
        this.lifecycleHooks.mounted.forEach(hook => hook());
    }

    /**
     * Unmount component from DOM
     */
    unmount() {
        if (!this.isMounted) return;

        // Call beforeUnmount hooks
        this.lifecycleHooks.beforeUnmount.forEach(hook => hook());

        // Remove event listeners
        this.cleanupEventListeners();

        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }

        this.isMounted = false;
        this.element = null;

        // Call unmounted hooks
        this.lifecycleHooks.unmounted.forEach(hook => hook());
    }

    /**
     * Add lifecycle hook
     */
    addHook(type, callback) {
        if (this.lifecycleHooks[type]) {
            this.lifecycleHooks[type].push(callback);
        }
    }

    /**
     * Remove lifecycle hook
     */
    removeHook(type, callback) {
        if (this.lifecycleHooks[type]) {
            const index = this.lifecycleHooks[type].indexOf(callback);
            if (index > -1) {
                this.lifecycleHooks[type].splice(index, 1);
            }
        }
    }

    /**
     * Add event listener
     */
    addEventListener(event, selector, handler) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push({ selector, handler });
    }

    /**
     * Remove event listener
     */
    removeEventListener(event, selector, handler) {
        const listeners = this.eventListeners.get(event);
        if (listeners) {
            const index = listeners.findIndex(l => l.selector === selector && l.handler === handler);
            if (index > -1) {
                listeners.splice(index, 1);
            }
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        if (!this.element) return;

        for (const [event, listeners] of this.eventListeners) {
            for (const { selector, handler } of listeners) {
                DOM.on(event, selector, handler, this.element);
            }
        }
    }

    /**
     * Cleanup event listeners
     */
    cleanupEventListeners() {
        // Event listeners are automatically cleaned up when element is removed
        this.eventListeners.clear();
    }

    /**
     * Find child component
     */
    findChild(predicate) {
        return this.children.find(predicate);
    }

    /**
     * Find child components
     */
    findChildren(predicate) {
        return this.children.filter(predicate);
    }

    /**
     * Add child component
     */
    addChild(child) {
        this.children.push(child);
    }

    /**
     * Remove child component
     */
    removeChild(child) {
        const index = this.children.indexOf(child);
        if (index > -1) {
            this.children.splice(index, 1);
        }
    }

    /**
     * Props change handler
     */
    onPropsChange(oldProps, newProps) {
        // Override in subclasses
    }

    /**
     * State change handler
     */
    onStateChange(oldState, newState) {
        // Override in subclasses
    }

    /**
     * Destroy component
     */
    destroy() {
        this.unmount();
        this.children.forEach(child => child.destroy());
        this.children = [];
        this.eventListeners.clear();
        this.lifecycleHooks = {
            beforeMount: [],
            mounted: [],
            beforeUpdate: [],
            updated: [],
            beforeUnmount: [],
            unmounted: []
        };
    }
}

/**
 * Component Registry
 */
class ComponentRegistry {
    constructor() {
        this.components = new Map();
    }

    /**
     * Register component
     */
    register(name, componentClass) {
        this.components.set(name, componentClass);
    }

    /**
     * Unregister component
     */
    unregister(name) {
        this.components.delete(name);
    }

    /**
     * Create component instance
     */
    create(name, props = {}) {
        const ComponentClass = this.components.get(name);
        if (!ComponentClass) {
            throw new Error(`Component "${name}" not registered`);
        }
        return new ComponentClass(props);
    }

    /**
     * Check if component is registered
     */
    has(name) {
        return this.components.has(name);
    }

    /**
     * Get all registered components
     */
    getAll() {
        return Array.from(this.components.keys());
    }
}

// Global component registry
const ComponentRegistry = new ComponentRegistry();

/**
 * Button Component
 */
class Button extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'button',
            variant: 'primary',
            size: 'medium',
            disabled: false,
            loading: false,
            icon: null,
            ...props
        };
    }

    render() {
        const { type, variant, size, disabled, loading, icon, children, onClick, ...otherProps } = this.props;

        const className = StringUtils.kebabCase(`btn btn-${variant} btn-${size} ${loading ? 'loading' : ''} ${disabled ? 'disabled' : ''}`);

        const button = DOM.create('button', {
            type,
            className,
            disabled: disabled || loading,
            ...otherProps
        });

        if (icon) {
            const iconElement = DOM.create('i', { className: icon });
            button.appendChild(iconElement);
        }

        if (loading) {
            const spinner = DOM.create('i', { className: 'fas fa-spinner fa-spin' });
            button.appendChild(spinner);
        }

        if (children) {
            if (typeof children === 'string') {
                button.appendChild(document.createTextNode(children));
            } else if (children instanceof Node) {
                button.appendChild(children);
            }
        }

        if (onClick) {
            this.addEventListener('click', 'button', onClick);
        }

        return button;
    }
}

/**
 * Input Component
 */
class Input extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'text',
            placeholder: '',
            value: '',
            disabled: false,
            required: false,
            error: null,
            ...props
        };
    }

    render() {
        const { type, placeholder, value, disabled, required, error, onChange, onFocus, onBlur, ...otherProps } = this.props;

        const className = `input ${error ? 'error' : ''} ${disabled ? 'disabled' : ''}`;

        const container = DOM.create('div', { className: 'input-container' });

        const input = DOM.create('input', {
            type,
            className,
            placeholder,
            value,
            disabled,
            required,
            ...otherProps
        });

        container.appendChild(input);

        if (error) {
            const errorElement = DOM.create('div', { className: 'input-error' }, error);
            container.appendChild(errorElement);
        }

        // Event listeners
        if (onChange) {
            input.addEventListener('input', (e) => onChange(e.target.value, e));
        }
        if (onFocus) {
            input.addEventListener('focus', onFocus);
        }
        if (onBlur) {
            input.addEventListener('blur', onBlur);
        }

        return container;
    }
}

/**
 * Modal Component
 */
class Modal extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: '',
            size: 'medium',
            closable: true,
            backdrop: true,
            ...props
        };
    }

    render() {
        const { title, size, closable, backdrop, children, onClose } = this.props;

        const modal = DOM.create('div', { className: 'modal-overlay' });

        if (backdrop) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal && onClose) {
                    onClose();
                }
            });
        }

        const modalDialog = DOM.create('div', { className: `modal-dialog modal-${size}` });
        modal.appendChild(modalDialog);

        const modalContent = DOM.create('div', { className: 'modal-content' });
        modalDialog.appendChild(modalContent);

        if (title || closable) {
            const modalHeader = DOM.create('div', { className: 'modal-header' });
            modalContent.appendChild(modalHeader);

            if (title) {
                const titleElement = DOM.create('h3', { className: 'modal-title' }, title);
                modalHeader.appendChild(titleElement);
            }

            if (closable) {
                const closeButton = DOM.create('button', {
                    className: 'modal-close',
                    'aria-label': 'Close modal'
                });
                closeButton.innerHTML = '&times;';
                closeButton.addEventListener('click', () => {
                    if (onClose) onClose();
                });
                modalHeader.appendChild(closeButton);
            }
        }

        const modalBody = DOM.create('div', { className: 'modal-body' });
        modalContent.appendChild(modalBody);

        if (children) {
            if (typeof children === 'string') {
                modalBody.innerHTML = children;
            } else if (children instanceof Node) {
                modalBody.appendChild(children);
            } else if (Array.isArray(children)) {
                children.forEach(child => {
                    if (child instanceof Node) {
                        modalBody.appendChild(child);
                    }
                });
            }
        }

        return modal;
    }
}

/**
 * Table Component
 */
class Table extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            columns: [],
            data: [],
            sortable: true,
            selectable: false,
            loading: false,
            ...props
        };
        this.sortColumn = null;
        this.sortDirection = 'asc';
    }

    render() {
        const { columns, data, sortable, selectable, loading } = this.props;

        const table = DOM.create('div', { className: 'table-container' });

        if (loading) {
            const loadingElement = DOM.create('div', { className: 'table-loading' }, 'Loading...');
            table.appendChild(loadingElement);
            return table;
        }

        const tableElement = DOM.create('table', { className: 'data-table' });
        table.appendChild(tableElement);

        // Table header
        const thead = DOM.create('thead');
        tableElement.appendChild(thead);

        const headerRow = DOM.create('tr');
        thead.appendChild(headerRow);

        if (selectable) {
            const selectAllCell = DOM.create('th', { className: 'select-cell' });
            const selectAllCheckbox = DOM.create('input', { type: 'checkbox' });
            selectAllCell.appendChild(selectAllCheckbox);
            headerRow.appendChild(selectAllCell);
        }

        columns.forEach(column => {
            const th = DOM.create('th', {
                className: sortable && column.sortable !== false ? 'sortable' : '',
                'data-column': column.key
            });

            if (column.title) {
                th.textContent = column.title;
            }

            if (sortable && column.sortable !== false) {
                const sortIcon = DOM.create('i', { className: 'fas fa-sort sort-icon' });
                th.appendChild(sortIcon);

                th.addEventListener('click', () => this.handleSort(column.key));
            }

            headerRow.appendChild(th);
        });

        // Table body
        const tbody = DOM.create('tbody');
        tableElement.appendChild(tbody);

        data.forEach((row, index) => {
            const tr = DOM.create('tr', { 'data-row-index': index });
            tbody.appendChild(tr);

            if (selectable) {
                const selectCell = DOM.create('td', { className: 'select-cell' });
                const checkbox = DOM.create('input', { type: 'checkbox' });
                selectCell.appendChild(checkbox);
                tr.appendChild(selectCell);
            }

            columns.forEach(column => {
                const td = DOM.create('td');

                if (column.render) {
                    const content = column.render(row[column.key], row, index);
                    if (typeof content === 'string') {
                        td.innerHTML = content;
                    } else if (content instanceof Node) {
                        td.appendChild(content);
                    }
                } else {
                    const value = row[column.key];
                    td.textContent = value !== null && value !== undefined ? String(value) : '';
                }

                tr.appendChild(td);
            });
        });

        return table;
    }

    handleSort(columnKey) {
        if (this.sortColumn === columnKey) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnKey;
            this.sortDirection = 'asc';
        }

        // Update sort icons
        const headers = this.element.querySelectorAll('th.sortable');
        headers.forEach(header => {
            const icon = header.querySelector('.sort-icon');
            if (header.dataset.column === columnKey) {
                icon.className = `fas fa-sort-${this.sortDirection === 'asc' ? 'up' : 'down'} sort-icon`;
            } else {
                icon.className = 'fas fa-sort sort-icon';
            }
        });

        // Sort data
        const sortedData = ArrayUtils.sortBy(this.props.data, columnKey, this.sortDirection);
        this.setProps({ data: sortedData });
    }
}

/**
 * Notification Component
 */
class Notification extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            type: 'info',
            title: '',
            message: '',
            duration: 5000,
            closable: true,
            ...props
        };
    }

    render() {
        const { type, title, message, closable } = this.props;

        const notification = DOM.create('div', { className: `notification notification-${type}` });

        const content = DOM.create('div', { className: 'notification-content' });
        notification.appendChild(content);

        const icon = DOM.create('i', { className: this.getIconClass(type) });
        content.appendChild(icon);

        const text = DOM.create('div', { className: 'notification-text' });
        content.appendChild(text);

        if (title) {
            const titleElement = DOM.create('div', { className: 'notification-title' }, title);
            text.appendChild(titleElement);
        }

        if (message) {
            const messageElement = DOM.create('div', { className: 'notification-message' }, message);
            text.appendChild(messageElement);
        }

        if (closable) {
            const closeButton = DOM.create('button', {
                className: 'notification-close',
                'aria-label': 'Close notification'
            });
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', () => this.close());
            notification.appendChild(closeButton);
        }

        // Auto-close after duration
        if (this.props.duration > 0) {
            setTimeout(() => this.close(), this.props.duration);
        }

        return notification;
    }

    getIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    close() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
        this.destroy();
    }
}

/**
 * Form Component
 */
class Form extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            fields: [],
            data: {},
            errors: {},
            onSubmit: null,
            onChange: null,
            ...props
        };
        this.formData = { ...this.props.data };
    }

    render() {
        const { fields, errors, onSubmit } = this.props;

        const form = DOM.create('form', { className: 'form' });

        fields.forEach(field => {
            const fieldElement = this.renderField(field, errors[field.name]);
            form.appendChild(fieldElement);
        });

        const submitButton = DOM.create('button', {
            type: 'submit',
            className: 'btn btn-primary'
        }, 'Submit');

        form.appendChild(submitButton);

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (onSubmit) {
                onSubmit(this.formData);
            }
        });

        return form;
    }

    renderField(field, error) {
        const fieldContainer = DOM.create('div', { className: 'form-field' });

        if (field.label) {
            const label = DOM.create('label', {
                className: 'form-label',
                for: field.name
            }, field.label);
            fieldContainer.appendChild(label);
        }

        let input;
        switch (field.type) {
            case 'textarea':
                input = DOM.create('textarea', {
                    id: field.name,
                    name: field.name,
                    className: `form-control ${error ? 'error' : ''}`,
                    placeholder: field.placeholder || '',
                    value: this.formData[field.name] || ''
                });
                break;
            case 'select':
                input = DOM.create('select', {
                    id: field.name,
                    name: field.name,
                    className: `form-control ${error ? 'error' : ''}`
                });

                if (field.options) {
                    field.options.forEach(option => {
                        const optionElement = DOM.create('option', {
                            value: option.value
                        }, option.label);
                        input.appendChild(optionElement);
                    });
                }
                break;
            default:
                input = DOM.create('input', {
                    id: field.name,
                    name: field.name,
                    type: field.type || 'text',
                    className: `form-control ${error ? 'error' : ''}`,
                    placeholder: field.placeholder || '',
                    value: this.formData[field.name] || ''
                });
        }

        input.addEventListener('input', (e) => {
            this.formData[field.name] = e.target.value;
            if (this.props.onChange) {
                this.props.onChange(field.name, e.target.value);
            }
        });

        fieldContainer.appendChild(input);

        if (error) {
            const errorElement = DOM.create('div', { className: 'form-error' }, error);
            fieldContainer.appendChild(errorElement);
        }

        return fieldContainer;
    }

    setData(data) {
        this.formData = { ...data };
        this.update();
    }

    getData() {
        return { ...this.formData };
    }

    validate() {
        const errors = {};
        this.props.fields.forEach(field => {
            if (field.required && !this.formData[field.name]) {
                errors[field.name] = `${field.label || field.name} is required`;
            }
            if (field.pattern && this.formData[field.name] && !field.pattern.test(this.formData[field.name])) {
                errors[field.name] = `${field.label || field.name} format is invalid`;
            }
        });
        this.setProps({ errors });
        return Object.keys(errors).length === 0;
    }
}

// Register components
ComponentRegistry.register('Button', Button);
ComponentRegistry.register('Input', Input);
ComponentRegistry.register('Modal', Modal);
ComponentRegistry.register('Table', Table);
ComponentRegistry.register('Notification', Notification);
ComponentRegistry.register('Form', Form);

// Make components globally available
window.Component = Component;
window.ComponentRegistry = ComponentRegistry;
window.Button = Button;
window.Input = Input;
window.Modal = Modal;
window.Table = Table;
window.Notification = Notification;
window.Form = Form;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        Component,
        ComponentRegistry,
        Button,
        Input,
        Modal,
        Table,
        Notification,
        Form
    };
}
