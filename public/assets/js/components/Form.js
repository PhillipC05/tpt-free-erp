/**
 * TPT Free ERP - Form Component
 * Advanced form component with validation, dynamic fields, and submission handling
 */

class Form extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            fields: [],
            initialData: {},
            validationRules: {},
            layout: 'vertical', // vertical, horizontal, inline
            submitButton: {
                text: 'Submit',
                className: 'btn btn-primary',
                disabled: false
            },
            cancelButton: {
                text: 'Cancel',
                className: 'btn btn-secondary',
                show: false
            },
            resetButton: {
                text: 'Reset',
                className: 'btn btn-link',
                show: false
            },
            showProgress: false,
            autoSave: false,
            autoSaveDelay: 1000,
            onSubmit: null,
            onChange: null,
            onValidate: null,
            onError: null,
            onSuccess: null,
            onCancel: null,
            onReset: null,
            ...props
        };

        this.state = {
            data: { ...this.props.initialData },
            errors: {},
            touched: {},
            isSubmitting: false,
            isValid: true,
            submitCount: 0,
            lastSaved: null,
            autoSaveTimer: null
        };

        // Bind methods
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.handleBlur = this.handleBlur.bind(this);
        this.handleFocus = this.handleFocus.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
        this.handleReset = this.handleReset.bind(this);
        this.validateField = this.validateField.bind(this);
        this.validateForm = this.validateForm.bind(this);
        this.setFieldValue = this.setFieldValue.bind(this);
        this.getFieldValue = this.getFieldValue.bind(this);
        this.setFieldError = this.setFieldError.bind(this);
        this.clearFieldError = this.clearFieldError.bind(this);
        this.startAutoSave = this.startAutoSave.bind(this);
        this.stopAutoSave = this.stopAutoSave.bind(this);
        this.saveFormData = this.saveFormData.bind(this);
    }

    componentDidMount() {
        if (this.props.autoSave) {
            this.startAutoSave();
        }
    }

    componentWillUnmount() {
        this.stopAutoSave();
    }

    startAutoSave() {
        if (this.props.autoSave && this.props.autoSaveDelay > 0) {
            this.state.autoSaveTimer = setInterval(() => {
                this.saveFormData();
            }, this.props.autoSaveDelay);
        }
    }

    stopAutoSave() {
        if (this.state.autoSaveTimer) {
            clearInterval(this.state.autoSaveTimer);
            this.state.autoSaveTimer = null;
        }
    }

    async saveFormData() {
        try {
            // This would typically save to localStorage or send to server
            const formData = {
                data: this.state.data,
                timestamp: new Date().toISOString()
            };

            localStorage.setItem(`form_autosave_${this.props.id || 'default'}`, JSON.stringify(formData));
            this.setState({ lastSaved: new Date() });

            if (this.props.onAutoSave) {
                this.props.onAutoSave(formData);
            }
        } catch (error) {
            console.warn('Auto-save failed:', error);
        }
    }

    handleSubmit(e) {
        e.preventDefault();

        if (this.state.isSubmitting) return;

        this.setState({ submitCount: this.state.submitCount + 1 });

        // Mark all fields as touched
        const touched = {};
        this.props.fields.forEach(field => {
            touched[field.name] = true;
        });
        this.setState({ touched });

        // Validate form
        const errors = this.validateForm();
        const isValid = Object.keys(errors).length === 0;

        this.setState({ errors, isValid });

        if (!isValid) {
            if (this.props.onError) {
                this.props.onError(errors);
            }
            return;
        }

        // Submit form
        this.submitForm();
    }

    async submitForm() {
        this.setState({ isSubmitting: true });

        try {
            if (this.props.onSubmit) {
                const result = await this.props.onSubmit(this.state.data, this);

                if (result && result.errors) {
                    this.setState({ errors: result.errors, isValid: false });
                    if (this.props.onError) {
                        this.props.onError(result.errors);
                    }
                } else {
                    // Success
                    this.setState({ errors: {}, isValid: true });
                    if (this.props.onSuccess) {
                        this.props.onSuccess(result, this.state.data);
                    }

                    // Clear auto-saved data on successful submit
                    if (this.props.autoSave) {
                        localStorage.removeItem(`form_autosave_${this.props.id || 'default'}`);
                    }
                }
            }
        } catch (error) {
            console.error('Form submission failed:', error);
            const errors = { general: error.message || 'Submission failed' };
            this.setState({ errors, isValid: false });

            if (this.props.onError) {
                this.props.onError(errors);
            }
        } finally {
            this.setState({ isSubmitting: false });
        }
    }

    handleChange(fieldName, value, field) {
        // Update field value
        const newData = { ...this.state.data };
        newData[fieldName] = value;
        this.setState({ data: newData });

        // Clear field error if it exists
        if (this.state.errors[fieldName]) {
            const newErrors = { ...this.state.errors };
            delete newErrors[fieldName];
            this.setState({ errors: newErrors });
        }

        // Validate field if configured to validate on change
        if (field && field.validateOnChange) {
            this.validateField(fieldName, value, field);
        }

        // Call onChange callback
        if (this.props.onChange) {
            this.props.onChange(fieldName, value, this.state.data);
        }
    }

    handleBlur(fieldName, value, field) {
        // Mark field as touched
        const touched = { ...this.state.touched };
        touched[fieldName] = true;
        this.setState({ touched });

        // Validate field
        this.validateField(fieldName, value, field);
    }

    handleFocus(fieldName) {
        // Clear field error on focus if configured
        const field = this.props.fields.find(f => f.name === fieldName);
        if (field && field.clearErrorOnFocus && this.state.errors[fieldName]) {
            const newErrors = { ...this.state.errors };
            delete newErrors[fieldName];
            this.setState({ errors: newErrors });
        }
    }

    handleCancel() {
        if (this.props.onCancel) {
            this.props.onCancel(this.state.data);
        }
    }

    handleReset() {
        const initialData = { ...this.props.initialData };
        this.setState({
            data: initialData,
            errors: {},
            touched: {},
            isValid: true
        });

        if (this.props.onReset) {
            this.props.onReset(initialData);
        }
    }

    validateField(fieldName, value, field) {
        const rules = this.props.validationRules[fieldName] || (field ? field.validation : null);
        if (!rules) return;

        const errors = [];

        // Required validation
        if (rules.required && (value === null || value === undefined || value === '')) {
            errors.push(rules.requiredMessage || `${field.label || fieldName} is required`);
        }

        // Type validation
        if (value && rules.type) {
            switch (rules.type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        errors.push(rules.emailMessage || 'Please enter a valid email address');
                    }
                    break;
                case 'number':
                    if (isNaN(value)) {
                        errors.push(rules.numberMessage || 'Please enter a valid number');
                    }
                    break;
                case 'date':
                    if (isNaN(Date.parse(value))) {
                        errors.push(rules.dateMessage || 'Please enter a valid date');
                    }
                    break;
            }
        }

        // Pattern validation
        if (value && rules.pattern) {
            const regex = new RegExp(rules.pattern);
            if (!regex.test(value)) {
                errors.push(rules.patternMessage || 'Invalid format');
            }
        }

        // Length validation
        if (value && typeof value === 'string') {
            if (rules.minLength && value.length < rules.minLength) {
                errors.push(rules.minLengthMessage || `Minimum ${rules.minLength} characters required`);
            }
            if (rules.maxLength && value.length > rules.maxLength) {
                errors.push(rules.maxLengthMessage || `Maximum ${rules.maxLength} characters allowed`);
            }
        }

        // Number range validation
        if (value && typeof value === 'number') {
            if (rules.min !== undefined && value < rules.min) {
                errors.push(rules.minMessage || `Value must be at least ${rules.min}`);
            }
            if (rules.max !== undefined && value > rules.max) {
                errors.push(rules.maxMessage || `Value must be at most ${rules.max}`);
            }
        }

        // Custom validation
        if (rules.custom && typeof rules.custom === 'function') {
            const customError = rules.custom(value, this.state.data);
            if (customError) {
                errors.push(customError);
            }
        }

        // Update errors
        const newErrors = { ...this.state.errors };
        if (errors.length > 0) {
            newErrors[fieldName] = errors[0]; // Show first error
        } else {
            delete newErrors[fieldName];
        }

        this.setState({ errors: newErrors });

        // Call validation callback
        if (this.props.onValidate) {
            this.props.onValidate(fieldName, errors.length === 0, errors);
        }

        return errors.length === 0;
    }

    validateForm() {
        const errors = {};

        this.props.fields.forEach(field => {
            const value = this.getFieldValue(field.name);
            const fieldErrors = this.validateField(field.name, value, field);
            if (!fieldErrors && this.state.errors[field.name]) {
                errors[field.name] = this.state.errors[field.name];
            }
        });

        return errors;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    setFieldValue(fieldName, value) {
        this.handleChange(fieldName, value);
    }

    getFieldValue(fieldName) {
        return this.state.data[fieldName];
    }

    setFieldError(fieldName, error) {
        const newErrors = { ...this.state.errors };
        newErrors[fieldName] = error;
        this.setState({ errors: newErrors });
    }

    clearFieldError(fieldName) {
        const newErrors = { ...this.state.errors };
        delete newErrors[fieldName];
        this.setState({ errors: newErrors });
    }

    render() {
        const { fields, layout, submitButton, cancelButton, resetButton, showProgress } = this.props;
        const { data, errors, touched, isSubmitting, isValid, submitCount, lastSaved } = this.state;

        const formClasses = [
            'dynamic-form',
            `form-${layout}`,
            isSubmitting && 'form-submitting',
            !isValid && 'form-invalid'
        ].filter(Boolean).join(' ');

        const form = DOM.create('form', {
            className: formClasses,
            onsubmit: this.handleSubmit
        });

        // Progress indicator
        if (showProgress && submitCount > 0) {
            const progress = DOM.create('div', { className: 'form-progress' });
            const progressBar = DOM.create('div', {
                className: 'progress-bar',
                style: `width: ${isValid ? '100' : '0'}%`
            });
            progress.appendChild(progressBar);
            form.appendChild(progress);
        }

        // Auto-save indicator
        if (this.props.autoSave && lastSaved) {
            const autoSave = DOM.create('div', { className: 'auto-save-indicator' });
            autoSave.innerHTML = `<i class="fas fa-check"></i> Auto-saved ${this.formatTimeAgo(lastSaved)}`;
            form.appendChild(autoSave);
        }

        // Form fields
        const fieldsContainer = DOM.create('div', { className: 'form-fields' });

        fields.forEach(field => {
            const fieldElement = this.renderField(field, data[field.name], errors[field.name], touched[field.name]);
            fieldsContainer.appendChild(fieldElement);
        });

        form.appendChild(fieldsContainer);

        // Form actions
        const actions = DOM.create('div', { className: 'form-actions' });

        // Submit button
        const submitBtn = DOM.create('button', {
            type: 'submit',
            className: `${submitButton.className} ${isSubmitting ? 'loading' : ''}`,
            disabled: submitButton.disabled || isSubmitting
        });

        if (isSubmitting) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        } else {
            submitBtn.textContent = submitButton.text;
        }
        actions.appendChild(submitBtn);

        // Cancel button
        if (cancelButton.show) {
            const cancelBtn = DOM.create('button', {
                type: 'button',
                className: cancelButton.className,
                onclick: this.handleCancel
            }, cancelButton.text);
            actions.appendChild(cancelBtn);
        }

        // Reset button
        if (resetButton.show) {
            const resetBtn = DOM.create('button', {
                type: 'button',
                className: resetButton.className,
                onclick: this.handleReset
            }, resetButton.text);
            actions.appendChild(resetBtn);
        }

        form.appendChild(actions);

        return form;
    }

    renderField(field, value, error, touched) {
        const fieldContainer = DOM.create('div', {
            className: `form-field ${field.type || 'text'}-field ${error ? 'has-error' : ''} ${touched ? 'touched' : ''}`
        });

        // Field label
        if (field.label && field.type !== 'checkbox' && field.type !== 'radio') {
            const label = DOM.create('label', {
                for: field.id || field.name,
                className: 'field-label'
            }, field.label);

            if (field.required) {
                const required = DOM.create('span', { className: 'required-indicator' }, '*');
                label.appendChild(required);
            }

            fieldContainer.appendChild(label);
        }

        // Field input
        const inputElement = this.renderFieldInput(field, value);
        fieldContainer.appendChild(inputElement);

        // Field description
        if (field.description) {
            const description = DOM.create('div', { className: 'field-description' }, field.description);
            fieldContainer.appendChild(description);
        }

        // Field error
        if (error) {
            const errorElement = DOM.create('div', { className: 'field-error' }, error);
            fieldContainer.appendChild(errorElement);
        }

        return fieldContainer;
    }

    renderFieldInput(field, value) {
        const commonProps = {
            id: field.id || field.name,
            name: field.name,
            className: `field-input ${field.className || ''}`,
            placeholder: field.placeholder || '',
            disabled: field.disabled || false,
            readonly: field.readonly || false,
            required: field.required || false,
            onfocus: () => this.handleFocus(field.name),
            onblur: (e) => this.handleBlur(field.name, e.target.value, field)
        };

        switch (field.type) {
            // Basic Input Types
            case 'text':
            case 'password':
            case 'email':
            case 'url':
            case 'search':
            case 'tel':
                return DOM.create('input', {
                    ...commonProps,
                    type: field.type,
                    value: value || '',
                    maxlength: field.maxlength,
                    pattern: field.pattern,
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });

            case 'textarea':
                return DOM.create('textarea', {
                    ...commonProps,
                    value: value || '',
                    rows: field.rows || 3,
                    maxlength: field.maxlength,
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });

            // Selection Elements
            case 'select':
                return this.renderSelectField(field, value, commonProps);

            case 'multiselect':
                return this.renderMultiSelectField(field, value, commonProps);

            case 'radio':
                return this.renderRadioField(field, value, commonProps);

            case 'checkbox':
                return this.renderCheckboxField(field, value, commonProps);

            case 'autocomplete':
                return this.renderAutocompleteField(field, value, commonProps);

            // Numeric Inputs
            case 'number':
                return DOM.create('input', {
                    ...commonProps,
                    type: 'number',
                    value: value || '',
                    min: field.min,
                    max: field.max,
                    step: field.step || 1,
                    oninput: (e) => this.handleChange(field.name, parseFloat(e.target.value) || 0, field)
                });

            case 'range':
                return this.renderRangeField(field, value, commonProps);

            case 'currency':
                return this.renderCurrencyField(field, value, commonProps);

            case 'percentage':
                return this.renderPercentageField(field, value, commonProps);

            case 'calculator':
                return this.renderCalculatorField(field, value, commonProps);

            // Date & Time
            case 'date':
            case 'time':
            case 'datetime':
            case 'month':
            case 'week':
                return DOM.create('input', {
                    ...commonProps,
                    type: field.type,
                    value: value || '',
                    min: field.min_date || field.min,
                    max: field.max_date || field.max,
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });

            // File Uploads
            case 'file':
                return this.renderFileField(field, value, commonProps);

            case 'image':
                return this.renderImageField(field, value, commonProps);

            case 'video':
                return this.renderVideoField(field, value, commonProps);

            case 'audio':
                return this.renderAudioField(field, value, commonProps);

            // Advanced Inputs
            case 'address':
                return this.renderAddressField(field, value, commonProps);

            case 'location':
                return this.renderLocationField(field, value, commonProps);

            case 'color':
                return DOM.create('input', {
                    ...commonProps,
                    type: 'color',
                    value: value || '#000000',
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });

            case 'rating':
                return this.renderRatingField(field, value, commonProps);

            case 'slider':
                return this.renderSliderField(field, value, commonProps);

            case 'likert':
                return this.renderLikertField(field, value, commonProps);

            case 'nps':
                return this.renderNPSField(field, value, commonProps);

            // Rich Content
            case 'richtext':
                return this.renderRichTextField(field, value, commonProps);

            case 'code':
                return this.renderCodeField(field, value, commonProps);

            case 'markdown':
                return this.renderMarkdownField(field, value, commonProps);

            // Special Purpose
            case 'signature':
                return this.renderSignatureField(field, value, commonProps);

            case 'barcode':
                return this.renderBarcodeField(field, value, commonProps);

            case 'qr':
                return this.renderQRField(field, value, commonProps);

            case 'masked':
                return this.renderMaskedField(field, value, commonProps);

            case 'tags':
                return this.renderTagsField(field, value, commonProps);

            // Business Specific
            case 'country':
                return this.renderCountryField(field, value, commonProps);

            case 'timezone':
                return this.renderTimezoneField(field, value, commonProps);

            case 'language':
                return this.renderLanguageField(field, value, commonProps);

            case 'creditcard':
                return this.renderCreditCardField(field, value, commonProps);

            case 'ssn':
                return this.renderSSNField(field, value, commonProps);

            case 'taxid':
                return this.renderTaxIdField(field, value, commonProps);

            // Matrix and Grid
            case 'matrix':
                return this.renderMatrixField(field, value, commonProps);

            case 'table':
                return this.renderTableField(field, value, commonProps);

            // Hidden and System
            case 'hidden':
                return DOM.create('input', {
                    ...commonProps,
                    type: 'hidden',
                    value: value || field.value || ''
                });

            case 'formula':
                return this.renderFormulaField(field, value, commonProps);

            case 'conditional':
                return this.renderConditionalField(field, value, commonProps);

            default:
                return DOM.create('input', {
                    ...commonProps,
                    type: 'text',
                    value: value || '',
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });
        }
    }

    handleFileChange(fieldName, files, field) {
        if (field.multiple) {
            this.handleChange(fieldName, Array.from(files), field);
        } else {
            this.handleChange(fieldName, files[0], field);
        }
    }

    // ============================================================================
    // ADVANCED FIELD RENDER METHODS
    // ============================================================================

    renderSelectField(field, value, commonProps) {
        const select = DOM.create('select', {
            ...commonProps,
            value: value || '',
            onchange: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Add placeholder option
        if (field.placeholder) {
            const placeholderOption = DOM.create('option', {
                value: '',
                disabled: true,
                selected: !value
            }, field.placeholder);
            select.appendChild(placeholderOption);
        }

        // Add options
        if (field.options) {
            field.options.forEach(option => {
                const optionElement = DOM.create('option', {
                    value: option.value,
                    selected: option.value === value
                }, option.label);
                select.appendChild(optionElement);
            });
        }

        return select;
    }

    renderMultiSelectField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'multiselect-container' });
        const selectedContainer = DOM.create('div', { className: 'selected-tags' });

        // Show selected values as tags
        if (value && Array.isArray(value)) {
            value.forEach((selectedValue, index) => {
                const tag = DOM.create('span', { className: 'tag' });
                const option = field.options?.find(opt => opt.value === selectedValue);
                tag.textContent = option ? option.label : selectedValue;

                const removeBtn = DOM.create('button', {
                    type: 'button',
                    className: 'tag-remove',
                    onclick: () => {
                        const newValue = value.filter((_, i) => i !== index);
                        this.handleChange(field.name, newValue, field);
                    }
                }, '×');
                tag.appendChild(removeBtn);
                selectedContainer.appendChild(tag);
            });
        }

        const select = DOM.create('select', {
            ...commonProps,
            multiple: true,
            size: field.size || 5,
            onchange: (e) => {
                const selectedOptions = Array.from(e.target.selectedOptions).map(opt => opt.value);
                this.handleChange(field.name, selectedOptions, field);
            }
        });

        // Add options
        if (field.options) {
            field.options.forEach(option => {
                const optionElement = DOM.create('option', {
                    value: option.value,
                    selected: value && Array.isArray(value) && value.includes(option.value)
                }, option.label);
                select.appendChild(optionElement);
            });
        }

        container.appendChild(selectedContainer);
        container.appendChild(select);
        return container;
    }

    renderRadioField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'radio-container' });

        if (field.options) {
            field.options.forEach(option => {
                const radioOption = DOM.create('div', { className: 'radio-option' });
                const radio = DOM.create('input', {
                    type: 'radio',
                    id: `${field.name}_${option.value}`,
                    name: field.name,
                    value: option.value,
                    checked: option.value === value,
                    onchange: (e) => this.handleChange(field.name, e.target.value, field)
                });
                const radioLabel = DOM.create('label', {
                    for: `${field.name}_${option.value}`,
                    className: 'radio-label'
                }, option.label);

                radioOption.appendChild(radio);
                radioOption.appendChild(radioLabel);
                container.appendChild(radioOption);
            });
        }

        return container;
    }

    renderCheckboxField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'checkbox-container' });

        if (field.options && field.options.length > 1) {
            // Multiple checkboxes
            field.options.forEach(option => {
                const checkboxOption = DOM.create('div', { className: 'checkbox-option' });
                const checkbox = DOM.create('input', {
                    type: 'checkbox',
                    id: `${field.name}_${option.value}`,
                    name: `${field.name}[]`,
                    value: option.value,
                    checked: value && Array.isArray(value) && value.includes(option.value),
                    onchange: (e) => {
                        const currentValue = Array.isArray(value) ? value : [];
                        let newValue;
                        if (e.target.checked) {
                            newValue = [...currentValue, option.value];
                        } else {
                            newValue = currentValue.filter(v => v !== option.value);
                        }
                        this.handleChange(field.name, newValue, field);
                    }
                });
                const checkboxLabel = DOM.create('label', {
                    for: `${field.name}_${option.value}`,
                    className: 'checkbox-label'
                }, option.label);

                checkboxOption.appendChild(checkbox);
                checkboxOption.appendChild(checkboxLabel);
                container.appendChild(checkboxOption);
            });
        } else {
            // Single checkbox
            const checkbox = DOM.create('input', {
                ...commonProps,
                type: 'checkbox',
                checked: value || false,
                onchange: (e) => this.handleChange(field.name, e.target.checked, field)
            });
            const checkboxLabel = DOM.create('label', {
                for: field.id || field.name,
                className: 'checkbox-label'
            }, field.label || field.name);

            container.appendChild(checkbox);
            container.appendChild(checkboxLabel);
        }

        return container;
    }

    renderAutocompleteField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'autocomplete-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleAutocompleteInput(field.name, e.target.value, field),
            onfocus: () => this.showAutocompleteDropdown(field.name, field),
            onblur: () => setTimeout(() => this.hideAutocompleteDropdown(field.name), 200)
        });

        const dropdown = DOM.create('div', {
            className: 'autocomplete-dropdown',
            style: 'display: none;'
        });

        container.appendChild(input);
        container.appendChild(dropdown);
        return container;
    }

    renderRangeField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'range-container' });

        const range = DOM.create('input', {
            ...commonProps,
            type: 'range',
            value: value || field.min || 0,
            min: field.min || 0,
            max: field.max || 100,
            step: field.step || 1,
            oninput: (e) => this.handleChange(field.name, parseFloat(e.target.value), field)
        });

        const valueDisplay = DOM.create('span', { className: 'range-value' }, value || field.min || 0);

        container.appendChild(range);
        container.appendChild(valueDisplay);
        return container;
    }

    renderCurrencyField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'currency-container' });

        const currencySymbol = DOM.create('span', { className: 'currency-symbol' }, field.currency_symbol || '$');

        const input = DOM.create('input', {
            ...commonProps,
            type: 'number',
            value: value || '',
            min: field.min,
            max: field.max,
            step: field.step || 0.01,
            oninput: (e) => this.handleChange(field.name, parseFloat(e.target.value) || 0, field)
        });

        container.appendChild(currencySymbol);
        container.appendChild(input);
        return container;
    }

    renderPercentageField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'percentage-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'number',
            value: value || '',
            min: field.min || 0,
            max: field.max || 100,
            step: field.step || 0.1,
            oninput: (e) => this.handleChange(field.name, parseFloat(e.target.value) || 0, field)
        });

        const percentSymbol = DOM.create('span', { className: 'percent-symbol' }, '%');

        container.appendChild(input);
        container.appendChild(percentSymbol);
        return container;
    }

    renderCalculatorField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'calculator-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleCalculatorInput(field.name, e.target.value, field)
        });

        const calcBtn = DOM.create('button', {
            type: 'button',
            className: 'calculator-btn',
            onclick: () => this.showCalculator(field.name, field)
        }, '🧮');

        container.appendChild(input);
        container.appendChild(calcBtn);
        return container;
    }

    renderFileField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'file-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'file',
            accept: field.accept || '',
            multiple: field.multiple || false,
            onchange: (e) => this.handleFileChange(field.name, e.target.files, field)
        });

        // File preview/info
        if (value) {
            const fileInfo = DOM.create('div', { className: 'file-info' });
            if (Array.isArray(value)) {
                fileInfo.textContent = `${value.length} files selected`;
            } else {
                fileInfo.textContent = value.name;
            }
            container.appendChild(fileInfo);
        }

        container.appendChild(input);
        return container;
    }

    renderImageField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'image-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'file',
            accept: 'image/*',
            multiple: field.multiple || false,
            onchange: (e) => this.handleImageChange(field.name, e.target.files, field)
        });

        // Image preview
        if (value) {
            const preview = DOM.create('div', { className: 'image-preview' });
            if (Array.isArray(value)) {
                value.forEach(file => {
                    const img = DOM.create('img', {
                        src: URL.createObjectURL(file),
                        alt: file.name,
                        style: 'max-width: 100px; max-height: 100px; margin: 5px;'
                    });
                    preview.appendChild(img);
                });
            } else {
                const img = DOM.create('img', {
                    src: URL.createObjectURL(value),
                    alt: value.name,
                    style: 'max-width: 200px; max-height: 200px;'
                });
                preview.appendChild(img);
            }
            container.appendChild(preview);
        }

        container.appendChild(input);
        return container;
    }

    renderVideoField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'video-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'file',
            accept: 'video/*',
            multiple: field.multiple || false,
            onchange: (e) => this.handleFileChange(field.name, e.target.files, field)
        });

        container.appendChild(input);
        return container;
    }

    renderAudioField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'audio-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'file',
            accept: 'audio/*',
            multiple: field.multiple || false,
            onchange: (e) => this.handleFileChange(field.name, e.target.files, field)
        });

        container.appendChild(input);
        return container;
    }

    renderAddressField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'address-container' });

        // Street address
        const streetInput = DOM.create('input', {
            ...commonProps,
            type: 'text',
            name: `${field.name}[street]`,
            placeholder: 'Street Address',
            value: value?.street || '',
            oninput: (e) => this.handleAddressChange(field.name, 'street', e.target.value, field)
        });

        // City, State, ZIP
        const cityInput = DOM.create('input', {
            type: 'text',
            name: `${field.name}[city]`,
            placeholder: 'City',
            value: value?.city || '',
            oninput: (e) => this.handleAddressChange(field.name, 'city', e.target.value, field)
        });

        const stateInput = DOM.create('input', {
            type: 'text',
            name: `${field.name}[state]`,
            placeholder: 'State',
            value: value?.state || '',
            oninput: (e) => this.handleAddressChange(field.name, 'state', e.target.value, field)
        });

        const zipInput = DOM.create('input', {
            type: 'text',
            name: `${field.name}[zip]`,
            placeholder: 'ZIP Code',
            value: value?.zip || '',
            oninput: (e) => this.handleAddressChange(field.name, 'zip', e.target.value, field)
        });

        container.appendChild(streetInput);
        container.appendChild(cityInput);
        container.appendChild(stateInput);
        container.appendChild(zipInput);
        return container;
    }

    renderLocationField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'location-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            placeholder: 'Enter location',
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        const mapBtn = DOM.create('button', {
            type: 'button',
            className: 'location-btn',
            onclick: () => this.showMapPicker(field.name, field)
        }, '📍');

        container.appendChild(input);
        container.appendChild(mapBtn);
        return container;
    }

    renderRatingField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'rating-container' });
        const maxRating = field.max_rating || 5;

        for (let i = 1; i <= maxRating; i++) {
            const star = DOM.create('button', {
                type: 'button',
                className: `rating-star ${i <= (value || 0) ? 'active' : ''}`,
                onclick: () => this.handleChange(field.name, i, field)
            }, field.shape === 'heart' ? '❤️' : '⭐');

            if (field.allow_half && i === Math.ceil(value || 0)) {
                star.className += ' half';
            }

            container.appendChild(star);
        }

        return container;
    }

    renderSliderField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'slider-container' });

        const slider = DOM.create('input', {
            ...commonProps,
            type: 'range',
            value: value || field.min || 0,
            min: field.min || 0,
            max: field.max || 100,
            step: field.step || 1,
            orient: field.orientation || 'horizontal',
            oninput: (e) => this.handleChange(field.name, parseFloat(e.target.value), field)
        });

        const valueDisplay = DOM.create('span', { className: 'slider-value' }, value || field.min || 0);

        container.appendChild(slider);
        container.appendChild(valueDisplay);
        return container;
    }

    renderLikertField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'likert-container' });
        const scalePoints = field.scale_points || 5;
        const labels = field.labels || {};

        for (let i = 1; i <= scalePoints; i++) {
            const option = DOM.create('label', { className: 'likert-option' });

            const radio = DOM.create('input', {
                type: 'radio',
                name: field.name,
                value: i,
                checked: i === value,
                onchange: (e) => this.handleChange(field.name, parseInt(e.target.value), field)
            });

            const label = DOM.create('span', { className: 'likert-label' }, labels[i] || i);

            option.appendChild(radio);
            option.appendChild(label);
            container.appendChild(option);
        }

        return container;
    }

    renderNPSField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'nps-container' });

        for (let i = 0; i <= 10; i++) {
            const btn = DOM.create('button', {
                type: 'button',
                className: `nps-btn ${i === value ? 'active' : ''}`,
                onclick: () => this.handleChange(field.name, i, field)
            }, i.toString());

            container.appendChild(btn);
        }

        return container;
    }

    renderRichTextField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'richtext-container' });

        const textarea = DOM.create('textarea', {
            ...commonProps,
            value: value || '',
            rows: field.rows || 10,
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Basic toolbar (can be enhanced with a rich text editor library)
        const toolbar = DOM.create('div', { className: 'richtext-toolbar' });
        ['bold', 'italic', 'underline'].forEach(format => {
            const btn = DOM.create('button', {
                type: 'button',
                className: `format-btn format-${format}`,
                onclick: () => this.applyRichTextFormat(field.name, format)
            }, format);
            toolbar.appendChild(btn);
        });

        container.appendChild(toolbar);
        container.appendChild(textarea);
        return container;
    }

    renderCodeField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'code-container' });

        const textarea = DOM.create('textarea', {
            ...commonProps,
            value: value || '',
            rows: field.rows || 15,
            className: `${commonProps.className} code-editor`,
            spellcheck: false,
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Language indicator
        if (field.language) {
            const langIndicator = DOM.create('div', { className: 'code-language' }, field.language);
            container.appendChild(langIndicator);
        }

        container.appendChild(textarea);
        return container;
    }

    renderMarkdownField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'markdown-container' });

        const textarea = DOM.create('textarea', {
            ...commonProps,
            value: value || '',
            rows: field.rows || 10,
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Preview toggle
        const previewBtn = DOM.create('button', {
            type: 'button',
            className: 'preview-toggle',
            onclick: () => this.toggleMarkdownPreview(field.name)
        }, 'Preview');

        container.appendChild(previewBtn);
        container.appendChild(textarea);
        return container;
    }

    renderSignatureField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'signature-container' });

        const canvas = DOM.create('canvas', {
            id: `${field.name}_canvas`,
            className: 'signature-canvas',
            width: field.width || 400,
            height: field.height || 200
        });

        const clearBtn = DOM.create('button', {
            type: 'button',
            className: 'signature-clear',
            onclick: () => this.clearSignature(field.name)
        }, 'Clear');

        container.appendChild(canvas);
        container.appendChild(clearBtn);

        // Initialize signature pad (would need signature pad library)
        setTimeout(() => this.initSignaturePad(field.name), 100);

        return container;
    }

    renderBarcodeField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'barcode-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        const scanBtn = DOM.create('button', {
            type: 'button',
            className: 'barcode-scan',
            onclick: () => this.scanBarcode(field.name, field)
        }, '📱 Scan');

        container.appendChild(input);
        container.appendChild(scanBtn);
        return container;
    }

    renderQRField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'qr-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });

        const generateBtn = DOM.create('button', {
            type: 'button',
            className: 'qr-generate',
            onclick: () => this.generateQRCode(field.name, field)
        }, 'Generate QR');

        container.appendChild(input);
        container.appendChild(generateBtn);
        return container;
    }

    renderMaskedField(field, value, commonProps) {
        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleMaskedInput(field.name, e.target.value, field),
            onkeydown: (e) => this.handleMaskedKeydown(field.name, e, field)
        });

        return input;
    }

    renderTagsField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'tags-container' });

        // Display existing tags
        const tagsContainer = DOM.create('div', { className: 'tags-display' });
        if (value && Array.isArray(value)) {
            value.forEach((tag, index) => {
                const tagElement = DOM.create('span', { className: 'tag' }, tag);
                const removeBtn = DOM.create('button', {
                    type: 'button',
                    className: 'tag-remove',
                    onclick: () => {
                        const newValue = value.filter((_, i) => i !== index);
                        this.handleChange(field.name, newValue, field);
                    }
                }, '×');
                tagElement.appendChild(removeBtn);
                tagsContainer.appendChild(tagElement);
            });
        }

        // Input for new tags
        const input = DOM.create('input', {
            type: 'text',
            className: 'tags-input',
            placeholder: field.placeholder || 'Add tags...',
            onkeydown: (e) => this.handleTagsInput(field.name, e, field)
        });

        container.appendChild(tagsContainer);
        container.appendChild(input);
        return container;
    }

    renderCountryField(field, value, commonProps) {
        const select = DOM.create('select', {
            ...commonProps,
            value: value || '',
            onchange: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Add countries (simplified list)
        const countries = [
            { value: 'US', label: 'United States' },
            { value: 'CA', label: 'Canada' },
            { value: 'UK', label: 'United Kingdom' },
            { value: 'AU', label: 'Australia' },
            { value: 'DE', label: 'Germany' },
            { value: 'FR', label: 'France' }
        ];

        countries.forEach(country => {
            const option = DOM.create('option', {
                value: country.value,
                selected: country.value === value
            }, country.label);
            select.appendChild(option);
        });

        return select;
    }

    renderTimezoneField(field, value, commonProps) {
        const select = DOM.create('select', {
            ...commonProps,
            value: value || '',
            onchange: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Add timezones
        const timezones = [
            { value: 'America/New_York', label: 'Eastern Time' },
            { value: 'America/Chicago', label: 'Central Time' },
            { value: 'America/Denver', label: 'Mountain Time' },
            { value: 'America/Los_Angeles', label: 'Pacific Time' },
            { value: 'Europe/London', label: 'GMT' },
            { value: 'Europe/Paris', label: 'CET' },
            { value: 'Asia/Tokyo', label: 'JST' }
        ];

        timezones.forEach(tz => {
            const option = DOM.create('option', {
                value: tz.value,
                selected: tz.value === value
            }, tz.label);
            select.appendChild(option);
        });

        return select;
    }

    renderLanguageField(field, value, commonProps) {
        const select = DOM.create('select', {
            ...commonProps,
            value: value || '',
            onchange: (e) => this.handleChange(field.name, e.target.value, field)
        });

        // Add languages
        const languages = [
            { value: 'en', label: 'English' },
            { value: 'es', label: 'Spanish' },
            { value: 'fr', label: 'French' },
            { value: 'de', label: 'German' },
            { value: 'it', label: 'Italian' },
            { value: 'pt', label: 'Portuguese' },
            { value: 'zh', label: 'Chinese' },
            { value: 'ja', label: 'Japanese' }
        ];

        languages.forEach(lang => {
            const option = DOM.create('option', {
                value: lang.value,
                selected: lang.value === value
            }, lang.label);
            select.appendChild(option);
        });

        return select;
    }

    renderCreditCardField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'creditcard-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            placeholder: '1234 5678 9012 3456',
            maxlength: 19,
            oninput: (e) => this.handleCreditCardInput(field.name, e.target.value, field)
        });

        // Card type indicator
        const cardType = DOM.create('span', { className: 'card-type' }, this.detectCardType(value));

        container.appendChild(input);
        container.appendChild(cardType);
        return container;
    }

    renderSSNField(field, value, commonProps) {
        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            placeholder: 'XXX-XX-XXXX',
            maxlength: 11,
            oninput: (e) => this.handleSSNInput(field.name, e.target.value, field)
        });

        return input;
    }

    renderTaxIdField(field, value, commonProps) {
        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            placeholder: 'XX-XXXXXXX',
            maxlength: 10,
            oninput: (e) => this.handleTaxIdInput(field.name, e.target.value, field)
        });

        return input;
    }

    renderMatrixField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'matrix-container' });
        const rows = field.rows || [];
        const columns = field.columns || [];

        // Header row
        const headerRow = DOM.create('div', { className: 'matrix-header' });
        headerRow.appendChild(DOM.create('div', { className: 'matrix-cell header-cell' }, ''));

        columns.forEach(col => {
            headerRow.appendChild(DOM.create('div', { className: 'matrix-cell header-cell' }, col.label));
        });
        container.appendChild(headerRow);

        // Data rows
        rows.forEach(row => {
            const dataRow = DOM.create('div', { className: 'matrix-row' });
            dataRow.appendChild(DOM.create('div', { className: 'matrix-cell row-label' }, row.label));

            columns.forEach(col => {
                const cellValue = value?.[`${row.value}_${col.value}`] || '';
                const radio = DOM.create('input', {
                    type: 'radio',
                    name: `${field.name}_${row.value}`,
                    value: col.value,
                    checked: cellValue === col.value,
                    onchange: (e) => this.handleMatrixChange(field.name, `${row.value}_${col.value}`, e.target.value, field)
                });
                const cell = DOM.create('div', { className: 'matrix-cell' });
                cell.appendChild(radio);
                dataRow.appendChild(cell);
            });

            container.appendChild(dataRow);
        });

        return container;
    }

    renderTableField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'table-container' });

        const table = DOM.create('table', { className: 'dynamic-table' });

        // Table header
        if (field.columns) {
            const thead = DOM.create('thead');
            const headerRow = DOM.create('tr');

            field.columns.forEach(col => {
                const th = DOM.create('th', {}, col.label);
                headerRow.appendChild(th);
            });

            // Add actions column
            const actionsTh = DOM.create('th', {}, 'Actions');
            headerRow.appendChild(actionsTh);
            thead.appendChild(headerRow);
            table.appendChild(thead);
        }

        // Table body
        const tbody = DOM.create('tbody');

        if (value && Array.isArray(value)) {
            value.forEach((row, rowIndex) => {
                const tr = DOM.create('tr');

                if (field.columns) {
                    field.columns.forEach(col => {
                        const td = DOM.create('td');
                        const input = DOM.create('input', {
                            type: col.type || 'text',
                            value: row[col.name] || '',
                            placeholder: col.placeholder || '',
                            oninput: (e) => this.handleTableCellChange(field.name, rowIndex, col.name, e.target.value, field)
                        });
                        td.appendChild(input);
                        tr.appendChild(td);
                    });
                }

                // Actions column
                const actionsTd = DOM.create('td');
                const deleteBtn = DOM.create('button', {
                    type: 'button',
                    className: 'table-delete-btn',
                    onclick: () => this.removeTableRow(field.name, rowIndex, field)
                }, 'Delete');
                actionsTd.appendChild(deleteBtn);
                tr.appendChild(actionsTd);

                tbody.appendChild(tr);
            });
        }

        table.appendChild(tbody);

        // Add row button
        const addBtn = DOM.create('button', {
            type: 'button',
            className: 'table-add-btn',
            onclick: () => this.addTableRow(field.name, field)
        }, 'Add Row');

        container.appendChild(table);
        container.appendChild(addBtn);
        return container;
    }

    renderFormulaField(field, value, commonProps) {
        const container = DOM.create('div', { className: 'formula-container' });

        const input = DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            readonly: true
        });

        const formulaDisplay = DOM.create('div', { className: 'formula-display' }, field.formula || '');

        container.appendChild(input);
        container.appendChild(formulaDisplay);
        return container;
    }

    renderConditionalField(field, value, commonProps) {
        // This would be conditionally rendered based on other field values
        // For now, render as a regular input
        return DOM.create('input', {
            ...commonProps,
            type: 'text',
            value: value || '',
            oninput: (e) => this.handleChange(field.name, e.target.value, field)
        });
    }

    // ============================================================================
    // EVENT HANDLERS FOR ADVANCED FIELDS
    // ============================================================================

    handleAutocompleteInput(fieldName, inputValue, field) {
        this.handleChange(fieldName, inputValue, field);
        // Implement autocomplete logic here
    }

    showAutocompleteDropdown(fieldName, field) {
        // Implement dropdown show logic
    }

    hideAutocompleteDropdown(fieldName) {
        // Implement dropdown hide logic
    }

    handleImageChange(fieldName, files, field) {
        this.handleFileChange(fieldName, files, field);
        // Additional image processing logic
    }

    handleAddressChange(fieldName, part, value, field) {
        const currentValue = this.getFieldValue(fieldName) || {};
        currentValue[part] = value;
        this.handleChange(fieldName, currentValue, field);
    }

    showMapPicker(fieldName, field) {
        // Implement map picker logic
        console.log('Map picker for', fieldName);
    }

    handleCalculatorInput(fieldName, inputValue, field) {
        // Implement calculator logic
        this.handleChange(fieldName, inputValue, field);
    }

    showCalculator(fieldName, field) {
        // Implement calculator popup
        console.log('Calculator for', fieldName);
    }

    applyRichTextFormat(fieldName, format) {
        // Implement rich text formatting
        console.log('Apply format', format, 'to', fieldName);
    }

    toggleMarkdownPreview(fieldName) {
        // Implement markdown preview toggle
        console.log('Toggle preview for', fieldName);
    }

    initSignaturePad(fieldName) {
        // Initialize signature pad library
        console.log('Initialize signature pad for', fieldName);
    }

    clearSignature(fieldName) {
        // Clear signature
        console.log('Clear signature for', fieldName);
    }

    scanBarcode(fieldName, field) {
        // Implement barcode scanning
        console.log('Scan barcode for', fieldName);
    }

    generateQRCode(fieldName, field) {
        // Implement QR code generation
        console.log('Generate QR code for', fieldName);
    }

    handleMaskedInput(fieldName, inputValue, field) {
        // Implement input masking
        this.handleChange(fieldName, inputValue, field);
    }

    handleMaskedKeydown(fieldName, event, field) {
        // Handle masked input keydown
    }

    handleTagsInput(fieldName, event, field) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            const input = event.target;
            const tagValue = input.value.trim();

            if (tagValue) {
                const currentValue = this.getFieldValue(fieldName) || [];
                if (!currentValue.includes(tagValue)) {
                    this.handleChange(fieldName, [...currentValue, tagValue], field);
                }
                input.value = '';
            }
        }
    }

    handleCreditCardInput(fieldName, inputValue, field) {
        // Format credit card number
        const formatted = inputValue.replace(/\s+/g, '').replace(/(\d{4})/g, '$1 ').trim();
        this.handleChange(fieldName, formatted, field);
    }

    detectCardType(cardNumber) {
        if (!cardNumber) return '';

        const number = cardNumber.replace(/\s+/g, '');
        if (number.startsWith('4')) return 'Visa';
        if (number.startsWith('5') || number.startsWith('2')) return 'Mastercard';
        if (number.startsWith('3')) return 'American Express';
        if (number.startsWith('6')) return 'Discover';
        return 'Unknown';
    }

    handleSSNInput(fieldName, inputValue, field) {
        // Format SSN
        const formatted = inputValue.replace(/\D/g, '').replace(/(\d{3})(\d{2})(\d{4})/, '$1-$2-$3');
        this.handleChange(fieldName, formatted, field);
    }

    handleTaxIdInput(fieldName, inputValue, field) {
        // Format Tax ID
        const formatted = inputValue.replace(/\D/g, '').replace(/(\d{2})(\d{7})/, '$1-$2');
        this.handleChange(fieldName, formatted, field);
    }

    handleMatrixChange(fieldName, cellKey, cellValue, field) {
        const currentValue = this.getFieldValue(fieldName) || {};
        currentValue[cellKey] = cellValue;
        this.handleChange(fieldName, currentValue, field);
    }

    handleTableCellChange(fieldName, rowIndex, columnName, cellValue, field) {
        const currentValue = this.getFieldValue(fieldName) || [];
        if (!currentValue[rowIndex]) {
            currentValue[rowIndex] = {};
        }
        currentValue[rowIndex][columnName] = cellValue;
        this.handleChange(fieldName, currentValue, field);
    }

    addTableRow(fieldName, field) {
        const currentValue = this.getFieldValue(fieldName) || [];
        const newRow = {};

        if (field.columns) {
            field.columns.forEach(col => {
                newRow[col.name] = '';
            });
        }

        this.handleChange(fieldName, [...currentValue, newRow], field);
    }

    removeTableRow(fieldName, rowIndex, field) {
        const currentValue = this.getFieldValue(fieldName) || [];
        const newValue = currentValue.filter((_, index) => index !== rowIndex);
        this.handleChange(fieldName, newValue, field);
    }

    formatTimeAgo(timestamp) {
        if (!timestamp) return '';

        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;

        const minutes = Math.floor(diff / 60000);
        if (minutes < 1) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;

        const hours = Math.floor(diff / 3600000);
        if (hours < 24) return `${hours}h ago`;

        const days = Math.floor(diff / 86400000);
        return `${days}d ago`;
    }

    // Public API methods
    setData(data) {
        this.setState({ data: { ...data } });
    }

    getData() {
        return { ...this.state.data };
    }

    setErrors(errors) {
        this.setState({ errors: { ...errors } });
    }

    getErrors() {
        return { ...this.state.errors };
    }

    reset() {
        this.handleReset();
    }

    submit() {
        this.handleSubmit(new Event('submit'));
    }

    isFormValid() {
        return this.state.isValid;
    }

    getFieldError(fieldName) {
        return this.state.errors[fieldName];
    }

    focusField(fieldName) {
        setTimeout(() => {
            const field = this.element?.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.focus();
            }
        }, 100);
    }
}

// Register component
ComponentRegistry.register('Form', Form);

// Make globally available
window.Form = Form;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Form;
}
