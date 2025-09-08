/**
 * TPT Free ERP - Form Builder Component
 * Interactive form builder with drag-and-drop functionality
 */

class FormBuilder extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            onFormChange: null,
            onFormSave: null,
            onFormPreview: null,
            availableElements: this.getAvailableElements(),
            ...props
        };

        this.state = {
            formElements: [],
            selectedElement: null,
            formSettings: {
                title: 'New Form',
                description: '',
                layout: 'vertical',
                theme: 'default'
            },
            isDragging: false,
            dragElement: null
        };

        // Bind methods
        this.handleElementDragStart = this.handleElementDragStart.bind(this);
        this.handleElementDragEnd = this.handleElementDragEnd.bind(this);
        this.handleElementDrop = this.handleElementDrop.bind(this);
        this.handleElementSelect = this.handleElementSelect.bind(this);
        this.handleElementDelete = this.handleElementDelete.bind(this);
        this.handleElementMove = this.handleElementMove.bind(this);
        this.handleFormSettingsChange = this.handleFormSettingsChange.bind(this);
        this.handleFormSave = this.handleFormSave.bind(this);
        this.handleFormPreview = this.handleFormPreview.bind(this);
        this.renderElementPalette = this.renderElementPalette.bind(this);
        this.renderFormCanvas = this.renderFormCanvas.bind(this);
        this.renderElementProperties = this.renderElementProperties.bind(this);
    }

    getAvailableElements() {
        return [
            // Basic Input Types
            { type: 'text', label: 'Text Input', icon: 'fas fa-font', category: 'Basic' },
            { type: 'password', label: 'Password', icon: 'fas fa-lock', category: 'Basic' },
            { type: 'email', label: 'Email', icon: 'fas fa-envelope', category: 'Basic' },
            { type: 'url', label: 'URL', icon: 'fas fa-link', category: 'Basic' },
            { type: 'search', label: 'Search', icon: 'fas fa-search', category: 'Basic' },
            { type: 'tel', label: 'Phone', icon: 'fas fa-phone', category: 'Basic' },
            { type: 'textarea', label: 'Textarea', icon: 'fas fa-align-left', category: 'Basic' },

            // Selection Elements
            { type: 'select', label: 'Select Dropdown', icon: 'fas fa-chevron-down', category: 'Selection' },
            { type: 'multiselect', label: 'Multi-Select', icon: 'fas fa-list', category: 'Selection' },
            { type: 'radio', label: 'Radio Buttons', icon: 'fas fa-dot-circle', category: 'Selection' },
            { type: 'checkbox', label: 'Checkboxes', icon: 'fas fa-check-square', category: 'Selection' },
            { type: 'autocomplete', label: 'Autocomplete', icon: 'fas fa-magic', category: 'Selection' },

            // Numeric Inputs
            { type: 'number', label: 'Number', icon: 'fas fa-hashtag', category: 'Numeric' },
            { type: 'range', label: 'Range Slider', icon: 'fas fa-sliders-h', category: 'Numeric' },
            { type: 'currency', label: 'Currency', icon: 'fas fa-dollar-sign', category: 'Numeric' },
            { type: 'percentage', label: 'Percentage', icon: 'fas fa-percent', category: 'Numeric' },
            { type: 'calculator', label: 'Calculator', icon: 'fas fa-calculator', category: 'Numeric' },

            // Date & Time
            { type: 'date', label: 'Date', icon: 'fas fa-calendar', category: 'Date/Time' },
            { type: 'time', label: 'Time', icon: 'fas fa-clock', category: 'Date/Time' },
            { type: 'datetime', label: 'Date & Time', icon: 'fas fa-calendar-alt', category: 'Date/Time' },
            { type: 'month', label: 'Month', icon: 'fas fa-calendar-week', category: 'Date/Time' },
            { type: 'week', label: 'Week', icon: 'fas fa-calendar-day', category: 'Date/Time' },

            // File Uploads
            { type: 'file', label: 'File Upload', icon: 'fas fa-upload', category: 'Files' },
            { type: 'image', label: 'Image Upload', icon: 'fas fa-image', category: 'Files' },
            { type: 'video', label: 'Video Upload', icon: 'fas fa-video', category: 'Files' },
            { type: 'audio', label: 'Audio Upload', icon: 'fas fa-music', category: 'Files' },

            // Advanced Inputs
            { type: 'address', label: 'Address', icon: 'fas fa-map-marker-alt', category: 'Advanced' },
            { type: 'location', label: 'Location', icon: 'fas fa-map', category: 'Advanced' },
            { type: 'color', label: 'Color Picker', icon: 'fas fa-palette', category: 'Advanced' },
            { type: 'rating', label: 'Rating', icon: 'fas fa-star', category: 'Advanced' },
            { type: 'slider', label: 'Slider', icon: 'fas fa-sliders-h', category: 'Advanced' },
            { type: 'likert', label: 'Likert Scale', icon: 'fas fa-chart-bar', category: 'Advanced' },
            { type: 'nps', label: 'NPS Score', icon: 'fas fa-tachometer-alt', category: 'Advanced' },

            // Rich Content
            { type: 'richtext', label: 'Rich Text', icon: 'fas fa-edit', category: 'Content' },
            { type: 'code', label: 'Code Editor', icon: 'fas fa-code', category: 'Content' },
            { type: 'markdown', label: 'Markdown', icon: 'fas fa-markdown', category: 'Content' },

            // Special Purpose
            { type: 'signature', label: 'Signature', icon: 'fas fa-signature', category: 'Special' },
            { type: 'barcode', label: 'Barcode Scanner', icon: 'fas fa-barcode', category: 'Special' },
            { type: 'qr', label: 'QR Code', icon: 'fas fa-qrcode', category: 'Special' },
            { type: 'masked', label: 'Masked Input', icon: 'fas fa-mask', category: 'Special' },
            { type: 'tags', label: 'Tags Input', icon: 'fas fa-tags', category: 'Special' },

            // Business Specific
            { type: 'country', label: 'Country', icon: 'fas fa-globe', category: 'Business' },
            { type: 'timezone', label: 'Timezone', icon: 'fas fa-clock', category: 'Business' },
            { type: 'language', label: 'Language', icon: 'fas fa-language', category: 'Business' },
            { type: 'creditcard', label: 'Credit Card', icon: 'fas fa-credit-card', category: 'Business' },
            { type: 'ssn', label: 'SSN', icon: 'fas fa-id-card', category: 'Business' },
            { type: 'taxid', label: 'Tax ID', icon: 'fas fa-file-invoice-dollar', category: 'Business' },

            // Matrix and Grid
            { type: 'matrix', label: 'Matrix', icon: 'fas fa-table', category: 'Grid' },
            { type: 'table', label: 'Dynamic Table', icon: 'fas fa-th', category: 'Grid' },

            // System
            { type: 'hidden', label: 'Hidden Field', icon: 'fas fa-eye-slash', category: 'System' },
            { type: 'formula', label: 'Formula', icon: 'fas fa-function', category: 'System' },
            { type: 'conditional', label: 'Conditional Field', icon: 'fas fa-code-branch', category: 'System' }
        ];
    }

    handleElementDragStart(e, element) {
        this.setState({
            isDragging: true,
            dragElement: element
        });
        e.dataTransfer.setData('text/plain', JSON.stringify(element));
    }

    handleElementDragEnd() {
        this.setState({
            isDragging: false,
            dragElement: null
        });
    }

    handleElementDrop(e) {
        e.preventDefault();
        const elementData = JSON.parse(e.dataTransfer.getData('text/plain'));

        // Create new form element
        const newElement = {
            id: Date.now().toString(),
            type: elementData.type,
            name: `${elementData.type}_${Date.now()}`,
            label: elementData.label,
            placeholder: '',
            required: false,
            validation: {},
            options: elementData.type === 'select' || elementData.type === 'radio' || elementData.type === 'checkbox' ? [] : undefined,
            ...this.getDefaultElementProperties(elementData.type)
        };

        this.setState({
            formElements: [...this.state.formElements, newElement],
            selectedElement: newElement
        });

        if (this.props.onFormChange) {
            this.props.onFormChange(this.state.formElements);
        }
    }

    getDefaultElementProperties(type) {
        const defaults = {
            text: { maxlength: 255 },
            textarea: { rows: 3, maxlength: 1000 },
            number: { min: 0, max: 999999, step: 1 },
            range: { min: 0, max: 100, step: 1 },
            currency: { currency_symbol: '$', min: 0 },
            percentage: { min: 0, max: 100, step: 0.1 },
            rating: { max_rating: 5, shape: 'star' },
            slider: { min: 0, max: 100, step: 1, orientation: 'horizontal' },
            likert: { scale_points: 5, labels: {} },
            nps: {},
            richtext: { rows: 10 },
            code: { language: 'javascript', rows: 15 },
            markdown: { rows: 10 },
            signature: { width: 400, height: 200 },
            barcode: {},
            qr: {},
            masked: { mask: '' },
            tags: {},
            country: {},
            timezone: {},
            language: {},
            creditcard: {},
            ssn: {},
            taxid: {},
            matrix: { rows: [], columns: [] },
            table: { columns: [] },
            formula: { formula: '' },
            conditional: { condition: '' }
        };

        return defaults[type] || {};
    }

    handleElementSelect(element) {
        this.setState({ selectedElement: element });
    }

    handleElementDelete(elementId) {
        const newElements = this.state.formElements.filter(el => el.id !== elementId);
        this.setState({
            formElements: newElements,
            selectedElement: this.state.selectedElement?.id === elementId ? null : this.state.selectedElement
        });

        if (this.props.onFormChange) {
            this.props.onFormChange(newElements);
        }
    }

    handleElementMove(fromIndex, toIndex) {
        const elements = [...this.state.formElements];
        const [movedElement] = elements.splice(fromIndex, 1);
        elements.splice(toIndex, 0, movedElement);

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    handleFormSettingsChange(settings) {
        this.setState({ formSettings: { ...this.state.formSettings, ...settings } });
    }

    handleFormSave() {
        const formData = {
            settings: this.state.formSettings,
            elements: this.state.formElements
        };

        if (this.props.onFormSave) {
            this.props.onFormSave(formData);
        }
    }

    handleFormPreview() {
        const formData = {
            settings: this.state.formSettings,
            elements: this.state.formElements
        };

        if (this.props.onFormPreview) {
            this.props.onFormPreview(formData);
        }
    }

    render() {
        const container = DOM.create('div', { className: 'form-builder' });

        // Header
        const header = DOM.create('div', { className: 'form-builder-header' });
        const title = DOM.create('h2', {}, 'Form Builder');
        const actions = DOM.create('div', { className: 'form-builder-actions' });

        const previewBtn = DOM.create('button', {
            className: 'btn btn-secondary',
            onclick: this.handleFormPreview
        }, 'Preview');

        const saveBtn = DOM.create('button', {
            className: 'btn btn-primary',
            onclick: this.handleFormSave
        }, 'Save Form');

        actions.appendChild(previewBtn);
        actions.appendChild(saveBtn);
        header.appendChild(title);
        header.appendChild(actions);
        container.appendChild(header);

        // Main content
        const main = DOM.create('div', { className: 'form-builder-main' });

        // Element palette
        const palette = this.renderElementPalette();
        main.appendChild(palette);

        // Form canvas
        const canvas = this.renderFormCanvas();
        main.appendChild(canvas);

        // Properties panel
        const properties = this.renderElementProperties();
        main.appendChild(properties);

        container.appendChild(main);

        return container;
    }

    renderElementPalette() {
        const palette = DOM.create('div', { className: 'element-palette' });
        const title = DOM.create('h3', {}, 'Form Elements');

        // Group elements by category
        const categories = {};
        this.props.availableElements.forEach(element => {
            if (!categories[element.category]) {
                categories[element.category] = [];
            }
            categories[element.category].push(element);
        });

        const content = DOM.create('div', { className: 'palette-content' });

        Object.keys(categories).forEach(category => {
            const categoryDiv = DOM.create('div', { className: 'element-category' });
            const categoryTitle = DOM.create('h4', {}, category);
            const elementsDiv = DOM.create('div', { className: 'category-elements' });

            categories[category].forEach(element => {
                const elementDiv = DOM.create('div', {
                    className: 'palette-element',
                    draggable: true,
                    ondragstart: (e) => this.handleElementDragStart(e, element),
                    ondragend: this.handleElementDragEnd
                });

                const icon = DOM.create('i', { className: element.icon });
                const label = DOM.create('span', {}, element.label);

                elementDiv.appendChild(icon);
                elementDiv.appendChild(label);
                elementsDiv.appendChild(elementDiv);
            });

            categoryDiv.appendChild(categoryTitle);
            categoryDiv.appendChild(elementsDiv);
            content.appendChild(categoryDiv);
        });

        palette.appendChild(title);
        palette.appendChild(content);

        return palette;
    }

    renderFormCanvas() {
        const canvas = DOM.create('div', { className: 'form-canvas' });
        const title = DOM.create('h3', {}, 'Form Canvas');

        const canvasContent = DOM.create('div', {
            className: 'canvas-content',
            ondrop: this.handleElementDrop,
            ondragover: (e) => e.preventDefault()
        });

        // Form settings
        const settings = DOM.create('div', { className: 'form-settings' });
        const titleInput = DOM.create('input', {
            type: 'text',
            placeholder: 'Form Title',
            value: this.state.formSettings.title,
            oninput: (e) => this.handleFormSettingsChange({ title: e.target.value })
        });

        const descInput = DOM.create('textarea', {
            placeholder: 'Form Description',
            value: this.state.formSettings.description,
            oninput: (e) => this.handleFormSettingsChange({ description: e.target.value })
        });

        settings.appendChild(titleInput);
        settings.appendChild(descInput);
        canvasContent.appendChild(settings);

        // Form elements
        const elementsContainer = DOM.create('div', { className: 'canvas-elements' });

        if (this.state.formElements.length === 0) {
            const emptyState = DOM.create('div', { className: 'empty-canvas' });
            emptyState.innerHTML = `
                <i class="fas fa-plus-circle"></i>
                <p>Drag elements here to build your form</p>
            `;
            elementsContainer.appendChild(emptyState);
        } else {
            this.state.formElements.forEach((element, index) => {
                const elementDiv = DOM.create('div', {
                    className: `canvas-element ${this.state.selectedElement?.id === element.id ? 'selected' : ''}`,
                    onclick: () => this.handleElementSelect(element)
                });

                const elementHeader = DOM.create('div', { className: 'element-header' });
                const elementIcon = DOM.create('i', { className: this.getElementIcon(element.type) });
                const elementLabel = DOM.create('span', {}, element.label || element.type);
                const elementActions = DOM.create('div', { className: 'element-actions' });

                const deleteBtn = DOM.create('button', {
                    className: 'delete-element',
                    onclick: (e) => {
                        e.stopPropagation();
                        this.handleElementDelete(element.id);
                    }
                }, '×');

                elementActions.appendChild(deleteBtn);
                elementHeader.appendChild(elementIcon);
                elementHeader.appendChild(elementLabel);
                elementHeader.appendChild(elementActions);
                elementDiv.appendChild(elementHeader);

                // Element preview
                const preview = this.renderElementPreview(element);
                elementDiv.appendChild(preview);

                elementsContainer.appendChild(elementDiv);
            });
        }

        canvasContent.appendChild(elementsContainer);
        canvas.appendChild(title);
        canvas.appendChild(canvasContent);

        return canvas;
    }

    renderElementProperties() {
        const properties = DOM.create('div', { className: 'element-properties' });
        const title = DOM.create('h3', {}, 'Properties');

        if (!this.state.selectedElement) {
            const emptyState = DOM.create('div', { className: 'empty-properties' });
            emptyState.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <p>Select an element to edit its properties</p>
            `;
            properties.appendChild(title);
            properties.appendChild(emptyState);
            return properties;
        }

        const content = DOM.create('div', { className: 'properties-content' });
        const element = this.state.selectedElement;

        // Basic properties
        const basicProps = DOM.create('div', { className: 'property-group' });
        const basicTitle = DOM.create('h4', {}, 'Basic Properties');

        const labelInput = this.createPropertyInput('Label', element.label, (value) => {
            this.updateElementProperty(element.id, 'label', value);
        });

        const nameInput = this.createPropertyInput('Name', element.name, (value) => {
            this.updateElementProperty(element.id, 'name', value);
        });

        const placeholderInput = this.createPropertyInput('Placeholder', element.placeholder, (value) => {
            this.updateElementProperty(element.id, 'placeholder', value);
        });

        const requiredCheckbox = this.createPropertyCheckbox('Required', element.required, (value) => {
            this.updateElementProperty(element.id, 'required', value);
        });

        basicProps.appendChild(basicTitle);
        basicProps.appendChild(labelInput);
        basicProps.appendChild(nameInput);
        basicProps.appendChild(placeholderInput);
        basicProps.appendChild(requiredCheckbox);
        content.appendChild(basicProps);

        // Type-specific properties
        const typeProps = this.renderTypeSpecificProperties(element);
        if (typeProps) {
            content.appendChild(typeProps);
        }

        // Validation properties
        const validationProps = this.renderValidationProperties(element);
        if (validationProps) {
            content.appendChild(validationProps);
        }

        properties.appendChild(title);
        properties.appendChild(content);

        return properties;
    }

    createPropertyInput(label, value, onChange) {
        const container = DOM.create('div', { className: 'property-item' });
        const labelEl = DOM.create('label', {}, label);
        const input = DOM.create('input', {
            type: 'text',
            value: value || '',
            oninput: (e) => onChange(e.target.value)
        });

        container.appendChild(labelEl);
        container.appendChild(input);
        return container;
    }

    createPropertyCheckbox(label, value, onChange) {
        const container = DOM.create('div', { className: 'property-item' });
        const labelEl = DOM.create('label', { className: 'checkbox-label' });
        const checkbox = DOM.create('input', {
            type: 'checkbox',
            checked: value || false,
            onchange: (e) => onChange(e.target.checked)
        });
        const span = DOM.create('span', {}, label);

        labelEl.appendChild(checkbox);
        labelEl.appendChild(span);
        container.appendChild(labelEl);
        return container;
    }

    renderTypeSpecificProperties(element) {
        const container = DOM.create('div', { className: 'property-group' });
        const title = DOM.create('h4', {}, 'Type-specific Properties');

        switch (element.type) {
            case 'textarea':
                const rowsInput = this.createPropertyInput('Rows', element.rows, (value) => {
                    this.updateElementProperty(element.id, 'rows', parseInt(value));
                });
                container.appendChild(rowsInput);
                break;

            case 'number':
            case 'range':
                const minInput = this.createPropertyInput('Min', element.min, (value) => {
                    this.updateElementProperty(element.id, 'min', parseFloat(value));
                });
                const maxInput = this.createPropertyInput('Max', element.max, (value) => {
                    this.updateElementProperty(element.id, 'max', parseFloat(value));
                });
                const stepInput = this.createPropertyInput('Step', element.step, (value) => {
                    this.updateElementProperty(element.id, 'step', parseFloat(value));
                });
                container.appendChild(minInput);
                container.appendChild(maxInput);
                container.appendChild(stepInput);
                break;

            case 'select':
            case 'radio':
            case 'checkbox':
            case 'multiselect':
                const optionsContainer = DOM.create('div', { className: 'options-container' });
                const optionsLabel = DOM.create('label', {}, 'Options');

                if (element.options) {
                    element.options.forEach((option, index) => {
                        const optionDiv = DOM.create('div', { className: 'option-item' });
                        const optionInput = DOM.create('input', {
                            type: 'text',
                            value: option.label,
                            placeholder: 'Option label',
                            oninput: (e) => this.updateElementOption(element.id, index, 'label', e.target.value)
                        });
                        const valueInput = DOM.create('input', {
                            type: 'text',
                            value: option.value,
                            placeholder: 'Option value',
                            oninput: (e) => this.updateElementOption(element.id, index, 'value', e.target.value)
                        });
                        const deleteBtn = DOM.create('button', {
                            className: 'delete-option',
                            onclick: () => this.removeElementOption(element.id, index)
                        }, '×');

                        optionDiv.appendChild(optionInput);
                        optionDiv.appendChild(valueInput);
                        optionDiv.appendChild(deleteBtn);
                        optionsContainer.appendChild(optionDiv);
                    });
                }

                const addOptionBtn = DOM.create('button', {
                    className: 'add-option',
                    onclick: () => this.addElementOption(element.id)
                }, '+ Add Option');

                container.appendChild(optionsLabel);
                container.appendChild(optionsContainer);
                container.appendChild(addOptionBtn);
                break;

            case 'rating':
                const maxRatingInput = this.createPropertyInput('Max Rating', element.max_rating, (value) => {
                    this.updateElementProperty(element.id, 'max_rating', parseInt(value));
                });
                container.appendChild(maxRatingInput);
                break;

            case 'signature':
                const widthInput = this.createPropertyInput('Width', element.width, (value) => {
                    this.updateElementProperty(element.id, 'width', parseInt(value));
                });
                const heightInput = this.createPropertyInput('Height', element.height, (value) => {
                    this.updateElementProperty(element.id, 'height', parseInt(value));
                });
                container.appendChild(widthInput);
                container.appendChild(heightInput);
                break;
        }

        return container.children.length > 1 ? container : null;
    }

    renderValidationProperties(element) {
        const container = DOM.create('div', { className: 'property-group' });
        const title = DOM.create('h4', {}, 'Validation');

        // Add common validation properties
        const minLengthInput = this.createPropertyInput('Min Length', element.validation?.minLength, (value) => {
            this.updateElementValidation(element.id, 'minLength', value ? parseInt(value) : null);
        });

        const maxLengthInput = this.createPropertyInput('Max Length', element.validation?.maxLength, (value) => {
            this.updateElementValidation(element.id, 'maxLength', value ? parseInt(value) : null);
        });

        const patternInput = this.createPropertyInput('Pattern', element.validation?.pattern, (value) => {
            this.updateElementValidation(element.id, 'pattern', value);
        });

        container.appendChild(title);
        container.appendChild(minLengthInput);
        container.appendChild(maxLengthInput);
        container.appendChild(patternInput);

        return container;
    }

    renderElementPreview(element) {
        const preview = DOM.create('div', { className: 'element-preview' });

        // Simple preview based on element type
        switch (element.type) {
            case 'text':
            case 'email':
            case 'password':
                const input = DOM.create('input', {
                    type: element.type,
                    placeholder: element.placeholder || 'Preview',
                    disabled: true
                });
                preview.appendChild(input);
                break;

            case 'textarea':
                const textarea = DOM.create('textarea', {
                    placeholder: element.placeholder || 'Preview',
                    rows: element.rows || 3,
                    disabled: true
                });
                preview.appendChild(textarea);
                break;

            case 'select':
                const select = DOM.create('select', { disabled: true });
                const placeholder = DOM.create('option', {}, element.placeholder || 'Select...');
                select.appendChild(placeholder);
                preview.appendChild(select);
                break;

            case 'checkbox':
            case 'radio':
                const inputType = element.type === 'checkbox' ? 'checkbox' : 'radio';
                const inputEl = DOM.create('input', { type: inputType, disabled: true });
                const label = DOM.create('label', {}, element.label || 'Option');
                preview.appendChild(inputEl);
                preview.appendChild(label);
                break;

            default:
                const defaultInput = DOM.create('input', {
                    type: 'text',
                    placeholder: `${element.type} preview`,
                    disabled: true
                });
                preview.appendChild(defaultInput);
        }

        return preview;
    }

    getElementIcon(type) {
        const element = this.props.availableElements.find(el => el.type === type);
        return element ? element.icon : 'fas fa-question';
    }

    updateElementProperty(elementId, property, value) {
        const elements = this.state.formElements.map(el => {
            if (el.id === elementId) {
                return { ...el, [property]: value };
            }
            return el;
        });

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    updateElementValidation(elementId, validationType, value) {
        const elements = this.state.formElements.map(el => {
            if (el.id === elementId) {
                const validation = { ...el.validation, [validationType]: value };
                return { ...el, validation };
            }
            return el;
        });

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    addElementOption(elementId) {
        const elements = this.state.formElements.map(el => {
            if (el.id === elementId) {
                const options = el.options || [];
                const newOption = { label: `Option ${options.length + 1}`, value: `option_${options.length + 1}` };
                return { ...el, options: [...options, newOption] };
            }
            return el;
        });

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    removeElementOption(elementId, optionIndex) {
        const elements = this.state.formElements.map(el => {
            if (el.id === elementId) {
                const options = el.options.filter((_, index) => index !== optionIndex);
                return { ...el, options };
            }
            return el;
        });

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    updateElementOption(elementId, optionIndex, property, value) {
        const elements = this.state.formElements.map(el => {
            if (el.id === elementId) {
                const options = el.options.map((opt, index) => {
                    if (index === optionIndex) {
                        return { ...opt, [property]: value };
                    }
                    return opt;
                });
                return { ...el, options };
            }
            return el;
        });

        this.setState({ formElements: elements });

        if (this.props.onFormChange) {
            this.props.onFormChange(elements);
        }
    }

    // Public API methods
    getFormData() {
        return {
            settings: this.state.formSettings,
            elements: this.state.formElements
        };
    }

    setFormData(data) {
        this.setState({
            formSettings: data.settings || this.state.formSettings,
            formElements: data.elements || []
        });
    }

    clearForm() {
        this.setState({
            formElements: [],
            selectedElement: null
        });
    }
}

// Register component
ComponentRegistry.register('FormBuilder', FormBuilder);

// Make globally available
window.FormBuilder = FormBuilder;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormBuilder;
}
