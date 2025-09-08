/**
 * TPT Free ERP - Form Validator Utility
 * Comprehensive form validation with custom rules, error handling, and user feedback
 */

class FormValidator {
    constructor(options = {}) {
        this.options = {
            validateOnChange: true,
            validateOnBlur: true,
            showErrors: true,
            errorClass: 'error',
            successClass: 'success',
            errorContainerClass: 'error-message',
            ...options
        };

        this.rules = new Map();
        this.customValidators = new Map();
        this.fieldStates = new Map();
        this.formState = {
            isValid: true,
            errors: new Map(),
            validatedFields: new Set()
        };

        this.init();
    }

    init() {
        this.addDefaultRules();
    }

    // ============================================================================
    // DEFAULT VALIDATION RULES
    // ============================================================================

    addDefaultRules() {
        // Required field validation
        this.addRule('required', (value, params) => {
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                return params.message || 'This field is required';
            }
            return true;
        });

        // Email validation
        this.addRule('email', (value, params) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return params.message || 'Please enter a valid email address';
            }
            return true;
        });

        // Minimum length validation
        this.addRule('minLength', (value, params) => {
            if (value.length < params.length) {
                return params.message || `Minimum length is ${params.length} characters`;
            }
            return true;
        });

        // Maximum length validation
        this.addRule('maxLength', (value, params) => {
            if (value.length > params.length) {
                return params.message || `Maximum length is ${params.length} characters`;
            }
            return true;
        });

        // Numeric validation
        this.addRule('numeric', (value, params) => {
            if (isNaN(value) || value === '') {
                return params.message || 'Please enter a valid number';
            }
            return true;
        });

        // Integer validation
        this.addRule('integer', (value, params) => {
            if (!Number.isInteger(Number(value))) {
                return params.message || 'Please enter a valid integer';
            }
            return true;
        });

        // Minimum value validation
        this.addRule('min', (value, params) => {
            if (Number(value) < params.value) {
                return params.message || `Minimum value is ${params.value}`;
            }
            return true;
        });

        // Maximum value validation
        this.addRule('max', (value, params) => {
            if (Number(value) > params.value) {
                return params.message || `Maximum value is ${params.value}`;
            }
            return true;
        });

        // Pattern validation
        this.addRule('pattern', (value, params) => {
            const regex = new RegExp(params.pattern);
            if (!regex.test(value)) {
                return params.message || 'Invalid format';
            }
            return true;
        });

        // URL validation
        this.addRule('url', (value, params) => {
            try {
                new URL(value);
                return true;
            } catch {
                return params.message || 'Please enter a valid URL';
            }
        });

        // Phone validation
        this.addRule('phone', (value, params) => {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
                return params.message || 'Please enter a valid phone number';
            }
            return true;
        });

        // Date validation
        this.addRule('date', (value, params) => {
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return params.message || 'Please enter a valid date';
            }
            return true;
        });

        // Future date validation
        this.addRule('futureDate', (value, params) => {
            const inputDate = new Date(value);
            const now = new Date();
            if (inputDate <= now) {
                return params.message || 'Date must be in the future';
            }
            return true;
        });

        // Past date validation
        this.addRule('pastDate', (value, params) => {
            const inputDate = new Date(value);
            const now = new Date();
            if (inputDate >= now) {
                return params.message || 'Date must be in the past';
            }
            return true;
        });

        // Password strength validation
        this.addRule('password', (value, params) => {
            const minLength = params.minLength || 8;
            const requireUppercase = params.requireUppercase !== false;
            const requireLowercase = params.requireLowercase !== false;
            const requireNumbers = params.requireNumbers !== false;
            const requireSpecial = params.requireSpecial !== false;

            if (value.length < minLength) {
                return params.message || `Password must be at least ${minLength} characters long`;
            }

            if (requireUppercase && !/[A-Z]/.test(value)) {
                return params.message || 'Password must contain at least one uppercase letter';
            }

            if (requireLowercase && !/[a-z]/.test(value)) {
                return params.message || 'Password must contain at least one lowercase letter';
            }

            if (requireNumbers && !/\d/.test(value)) {
                return params.message || 'Password must contain at least one number';
            }

            if (requireSpecial && !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)) {
                return params.message || 'Password must contain at least one special character';
            }

            return true;
        });

        // Confirm password validation
        this.addRule('confirmPassword', (value, params) => {
            const originalPassword = params.originalPassword;
            if (value !== originalPassword) {
                return params.message || 'Passwords do not match';
            }
            return true;
        });
    }

    // ============================================================================
    // RULE MANAGEMENT
    // ============================================================================

    addRule(name, validator) {
        this.rules.set(name, validator);
    }

    removeRule(name) {
        this.rules.delete(name);
    }

    addCustomValidator(name, validator) {
        this.customValidators.set(name, validator);
    }

    removeCustomValidator(name) {
        this.customValidators.delete(name);
    }

    // ============================================================================
    // VALIDATION METHODS
    // ============================================================================

    validateField(fieldName, value, rules) {
        const errors = [];
        let isValid = true;

        for (const rule of rules) {
            const ruleName = typeof rule === 'string' ? rule : rule.name;
            const ruleParams = typeof rule === 'string' ? {} : rule.params || {};

            const validator = this.rules.get(ruleName) || this.customValidators.get(ruleName);

            if (validator) {
                const result = validator(value, ruleParams);
                if (result !== true) {
                    errors.push(result);
                    isValid = false;
                }
            }
        }

        this.fieldStates.set(fieldName, {
            isValid,
            errors,
            value
        });

        this.formState.validatedFields.add(fieldName);

        if (errors.length > 0) {
            this.formState.errors.set(fieldName, errors);
        } else {
            this.formState.errors.delete(fieldName);
        }

        this.updateFormValidity();

        return {
            isValid,
            errors
        };
    }

    validateForm(formData, validationRules) {
        const results = {};
        let formIsValid = true;

        for (const [fieldName, rules] of Object.entries(validationRules)) {
            const value = formData[fieldName];
            const fieldResult = this.validateField(fieldName, value, rules);
            results[fieldName] = fieldResult;

            if (!fieldResult.isValid) {
                formIsValid = false;
            }
        }

        this.formState.isValid = formIsValid;
        return {
            isValid: formIsValid,
            results
        };
    }

    validateFieldAsync(fieldName, value, rules) {
        return new Promise((resolve) => {
            const result = this.validateField(fieldName, value, rules);
            resolve(result);
        });
    }

    validateFormAsync(formData, validationRules) {
        return new Promise((resolve) => {
            const result = this.validateForm(formData, validationRules);
            resolve(result);
        });
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    updateFormValidity() {
        this.formState.isValid = this.formState.errors.size === 0;
    }

    getFieldState(fieldName) {
        return this.fieldStates.get(fieldName) || {
            isValid: true,
            errors: [],
            value: null
        };
    }

    getFormState() {
        return {
            isValid: this.formState.isValid,
            errors: Object.fromEntries(this.formState.errors),
            validatedFields: Array.from(this.formState.validatedFields)
        };
    }

    resetField(fieldName) {
        this.fieldStates.delete(fieldName);
        this.formState.errors.delete(fieldName);
        this.formState.validatedFields.delete(fieldName);
        this.updateFormValidity();
    }

    resetForm() {
        this.fieldStates.clear();
        this.formState.errors.clear();
        this.formState.validatedFields.clear();
        this.formState.isValid = true;
    }

    // ============================================================================
    // DOM INTEGRATION METHODS
    // ============================================================================

    attachToField(fieldElement, rules, options = {}) {
        const fieldName = fieldElement.name || fieldElement.id;
        if (!fieldName) {
            console.warn('Field element must have a name or id attribute');
            return;
        }

        const config = {
            validateOnChange: this.options.validateOnChange,
            validateOnBlur: this.options.validateOnBlur,
            ...options
        };

        const validateField = () => {
            const value = fieldElement.value;
            const result = this.validateField(fieldName, value, rules);
            this.updateFieldUI(fieldElement, result);
        };

        if (config.validateOnChange) {
            fieldElement.addEventListener('input', validateField);
        }

        if (config.validateOnBlur) {
            fieldElement.addEventListener('blur', validateField);
        }

        // Store field configuration for later use
        fieldElement._formValidator = {
            fieldName,
            rules,
            validateField
        };
    }

    detachFromField(fieldElement) {
        if (fieldElement._formValidator) {
            const { fieldName } = fieldElement._formValidator;
            this.resetField(fieldName);
            delete fieldElement._formValidator;
        }
    }

    updateFieldUI(fieldElement, result) {
        const container = fieldElement.closest('.form-group') || fieldElement.parentElement;
        if (!container) return;

        // Remove existing error messages
        const existingErrors = container.querySelectorAll(`.${this.options.errorContainerClass}`);
        existingErrors.forEach(error => error.remove());

        // Update field classes
        fieldElement.classList.remove(this.options.errorClass, this.options.successClass);

        if (result.errors.length > 0) {
            fieldElement.classList.add(this.options.errorClass);

            // Add error messages
            if (this.options.showErrors) {
                result.errors.forEach(error => {
                    const errorElement = DOM.create('div', {
                        className: this.options.errorContainerClass
                    }, error);
                    container.appendChild(errorElement);
                });
            }
        } else if (fieldElement.value && this.formState.validatedFields.has(fieldElement.name || fieldElement.id)) {
            fieldElement.classList.add(this.options.successClass);
        }
    }

    // ============================================================================
    // PRESET VALIDATION RULES
    // ============================================================================

    static getPresets() {
        return {
            // User registration
            userRegistration: {
                username: ['required', { name: 'minLength', params: { length: 3 } }, { name: 'maxLength', params: { length: 50 } }],
                email: ['required', 'email'],
                password: [{ name: 'password', params: { minLength: 8, requireUppercase: true, requireNumbers: true } }],
                confirmPassword: [{ name: 'confirmPassword', params: { originalPassword: 'password' } }]
            },

            // Login form
            login: {
                username: ['required'],
                password: ['required']
            },

            // Contact form
            contact: {
                name: ['required', { name: 'minLength', params: { length: 2 } }],
                email: ['required', 'email'],
                phone: ['phone'],
                message: ['required', { name: 'minLength', params: { length: 10 } }]
            },

            // Product form
            product: {
                name: ['required', { name: 'minLength', params: { length: 2 } }],
                sku: ['required', { name: 'pattern', params: { pattern: '^[A-Z0-9-]+$' } }],
                price: ['required', 'numeric', { name: 'min', params: { value: 0 } }],
                quantity: ['required', 'integer', { name: 'min', params: { value: 0 } }]
            },

            // Address form
            address: {
                street: ['required'],
                city: ['required'],
                state: ['required'],
                zipCode: ['required', { name: 'pattern', params: { pattern: '^[0-9]{5}(-[0-9]{4})?$' } }],
                country: ['required']
            }
        };
    }

    // ============================================================================
    // SANITIZATION METHODS
    // ============================================================================

    static sanitize(value, type = 'string') {
        if (!value) return value;

        switch (type) {
            case 'string':
                return String(value).trim();
            case 'email':
                return String(value).trim().toLowerCase();
            case 'number':
                return Number(value);
            case 'integer':
                return parseInt(value, 10);
            case 'float':
                return parseFloat(value);
            case 'boolean':
                return Boolean(value);
            default:
                return value;
        }
    }

    // ============================================================================
    // EXPORT METHODS
    // ============================================================================

    toJSON() {
        return {
            options: this.options,
            rules: Array.from(this.rules.keys()),
            customValidators: Array.from(this.customValidators.keys()),
            formState: {
                isValid: this.formState.isValid,
                errors: Object.fromEntries(this.formState.errors),
                validatedFields: Array.from(this.formState.validatedFields)
            }
        };
    }

    static fromJSON(data) {
        const validator = new FormValidator(data.options);

        // Restore custom validators if needed
        // Note: Functions cannot be serialized, so custom validators need to be re-added

        return validator;
    }
}

// Export the utility
window.FormValidator = FormValidator;
