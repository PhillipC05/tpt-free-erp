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
            case 'textarea':
                return DOM.create('textarea', {
                    ...commonProps,
                    value: value || '',
                    rows: field.rows || 3,
                    oninput: (e) => this.handleChange(field.name, e.target.value, field)
                });

            case 'select':
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

            case 'checkbox':
                const checkboxContainer = DOM.create('div', { className: 'checkbox-container' });
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

                checkboxContainer.appendChild(checkbox);
                checkboxContainer.appendChild(checkboxLabel);
                return checkboxContainer;

            case 'radio':
                const radioContainer = DOM.create('div', { className: 'radio-container' });

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
                        radioContainer.appendChild(radioOption);
                    });
                }

                return radioContainer;

            case 'file':
                const fileInput = DOM.create('input', {
                    ...commonProps,
                    type: 'file',
                    accept: field.accept || '',
                    multiple: field.multiple || false,
                    onchange: (e) => this.handleFileChange(field.name, e.target.files, field)
                });
                return fileInput;

            default: // text, email, password, number, date, etc.
                return DOM.create('input', {
                    ...commonProps,
                    type: field.type || 'text',
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
