/**
 * TPT Free ERP - Login Form Component
 * Handles user authentication with validation and error handling
 */

class LoginForm extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: 'Login to TPT ERP',
            subtitle: 'Enter your credentials to access your account',
            showRememberMe: true,
            showForgotPassword: true,
            showRegisterLink: true,
            redirectAfterLogin: '/dashboard',
            onLoginSuccess: null,
            onLoginError: null,
            ...props
        };

        this.state = {
            email: '',
            password: '',
            rememberMe: false,
            isLoading: false,
            errors: {},
            showPassword: false,
            loginAttempts: 0,
            lastLoginAttempt: null
        };

        // Bind methods
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.handleTogglePassword = this.handleTogglePassword.bind(this);
        this.handleForgotPassword = this.handleForgotPassword.bind(this);
        this.handleRegisterClick = this.handleRegisterClick.bind(this);
        this.validateForm = this.validateForm.bind(this);
        this.attemptLogin = this.attemptLogin.bind(this);
    }

    render() {
        const { title, subtitle, showRememberMe, showForgotPassword, showRegisterLink } = this.props;
        const { email, password, rememberMe, isLoading, errors, showPassword } = this.state;

        const form = DOM.create('div', { className: 'auth-form-container' });

        // Header
        const header = DOM.create('div', { className: 'auth-form-header' });
        const titleElement = DOM.create('h2', { className: 'auth-form-title' }, title);
        header.appendChild(titleElement);

        if (subtitle) {
            const subtitleElement = DOM.create('p', { className: 'auth-form-subtitle' }, subtitle);
            header.appendChild(subtitleElement);
        }
        form.appendChild(header);

        // Form
        const formElement = DOM.create('form', {
            className: 'auth-form',
            onsubmit: (e) => {
                e.preventDefault();
                this.handleSubmit(e);
            }
        });

        // Email field
        const emailGroup = this.renderFormGroup('email', 'Email', 'email', email, 'Enter your email address', errors.email, {
            type: 'email',
            required: true,
            autocomplete: 'email'
        });
        formElement.appendChild(emailGroup);

        // Password field
        const passwordGroup = this.renderFormGroup('password', 'Password', 'password', password, 'Enter your password', errors.password, {
            type: showPassword ? 'text' : 'password',
            required: true,
            autocomplete: 'current-password'
        });

        // Add password toggle button
        const passwordInput = passwordGroup.querySelector('input');
        if (passwordInput) {
            const toggleButton = DOM.create('button', {
                type: 'button',
                className: 'password-toggle-btn',
                'aria-label': showPassword ? 'Hide password' : 'Show password',
                onclick: this.handleTogglePassword
            });
            toggleButton.innerHTML = showPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            passwordGroup.appendChild(toggleButton);
        }
        formElement.appendChild(passwordGroup);

        // Remember me checkbox
        if (showRememberMe) {
            const rememberGroup = DOM.create('div', { className: 'form-group checkbox-group' });
            const label = DOM.create('label', { className: 'checkbox-label' });
            const checkbox = DOM.create('input', {
                type: 'checkbox',
                id: 'remember-me',
                name: 'rememberMe',
                checked: rememberMe,
                onchange: (e) => this.setState({ rememberMe: e.target.checked })
            });
            const checkboxText = DOM.create('span', { className: 'checkbox-text' }, 'Remember me');

            label.appendChild(checkbox);
            label.appendChild(checkboxText);
            rememberGroup.appendChild(label);
            formElement.appendChild(rememberGroup);
        }

        // Submit button
        const submitButton = DOM.create('button', {
            type: 'submit',
            className: `btn btn-primary btn-block ${isLoading ? 'loading' : ''}`,
            disabled: isLoading
        });

        if (isLoading) {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        } else {
            submitButton.textContent = 'Sign In';
        }
        formElement.appendChild(submitButton);

        form.appendChild(formElement);

        // Links
        const links = DOM.create('div', { className: 'auth-form-links' });

        if (showForgotPassword) {
            const forgotLink = DOM.create('a', {
                href: '#',
                className: 'auth-link',
                onclick: this.handleForgotPassword
            }, 'Forgot your password?');
            links.appendChild(forgotLink);
        }

        if (showRegisterLink) {
            const registerLink = DOM.create('a', {
                href: '#',
                className: 'auth-link',
                onclick: this.handleRegisterClick
            }, "Don't have an account? Sign up");
            links.appendChild(registerLink);
        }

        form.appendChild(links);

        return form;
    }

    renderFormGroup(name, label, type, value, placeholder, error, inputProps = {}) {
        const group = DOM.create('div', { className: `form-group ${error ? 'has-error' : ''}` });

        const labelElement = DOM.create('label', {
            for: name,
            className: 'form-label'
        }, label);

        const input = DOM.create('input', {
            id: name,
            name: name,
            type: type,
            value: value,
            placeholder: placeholder,
            className: 'form-control',
            oninput: (e) => this.handleInputChange(name, e.target.value),
            ...inputProps
        });

        group.appendChild(labelElement);
        group.appendChild(input);

        if (error) {
            const errorElement = DOM.create('div', { className: 'form-error' }, error);
            group.appendChild(errorElement);
        }

        return group;
    }

    handleInputChange(name, value) {
        this.setState({ [name]: value });

        // Clear error when user starts typing
        if (this.state.errors[name]) {
            const newErrors = { ...this.state.errors };
            delete newErrors[name];
            this.setState({ errors: newErrors });
        }
    }

    handleTogglePassword() {
        this.setState({ showPassword: !this.state.showPassword });
    }

    handleForgotPassword(e) {
        e.preventDefault();
        // Navigate to forgot password page or show modal
        Router.navigate('/forgot-password');
    }

    handleRegisterClick(e) {
        e.preventDefault();
        // Navigate to registration page
        Router.navigate('/register');
    }

    async handleSubmit(e) {
        e.preventDefault();

        if (this.state.isLoading) return;

        // Validate form
        const errors = this.validateForm();
        if (Object.keys(errors).length > 0) {
            this.setState({ errors });
            return;
        }

        // Check for too many login attempts
        if (this.isLoginThrottled()) {
            this.setState({
                errors: {
                    general: 'Too many login attempts. Please try again later.'
                }
            });
            return;
        }

        await this.attemptLogin();
    }

    validateForm() {
        const errors = {};
        const { email, password } = this.state;

        // Email validation
        if (!email) {
            errors.email = 'Email is required';
        } else if (!this.isValidEmail(email)) {
            errors.email = 'Please enter a valid email address';
        }

        // Password validation
        if (!password) {
            errors.password = 'Password is required';
        } else if (password.length < CONFIG.SECURITY.MIN_PASSWORD_LENGTH) {
            errors.password = `Password must be at least ${CONFIG.SECURITY.MIN_PASSWORD_LENGTH} characters`;
        }

        return errors;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isLoginThrottled() {
        const { loginAttempts, lastLoginAttempt } = this.state;
        const now = Date.now();
        const throttleTime = CONFIG.SECURITY.LOGIN_THROTTLE_TIME || 300000; // 5 minutes
        const maxAttempts = CONFIG.SECURITY.MAX_LOGIN_ATTEMPTS || 5;

        if (loginAttempts >= maxAttempts) {
            if (lastLoginAttempt && (now - lastLoginAttempt) < throttleTime) {
                return true;
            }
            // Reset attempts after throttle time
            this.setState({ loginAttempts: 0 });
        }

        return false;
    }

    async attemptLogin() {
        this.setState({ isLoading: true, errors: {} });

        try {
            const { email, password, rememberMe } = this.state;

            const response = await API.login({
                email: email.trim(),
                password,
                rememberMe
            });

            // Success
            this.setState({
                loginAttempts: 0,
                lastLoginAttempt: null
            });

            // Update application state
            State.set('user.isAuthenticated', true);
            State.set('user.currentUser', response.user);

            // Show success message
            App.showNotification({
                type: 'success',
                message: 'Login successful! Welcome back.'
            });

            // Call success callback
            if (this.props.onLoginSuccess) {
                this.props.onLoginSuccess(response);
            }

            // Redirect
            const redirectTo = this.props.redirectAfterLogin || '/dashboard';
            Router.navigate(redirectTo);

        } catch (error) {
            console.error('Login failed:', error);

            // Update login attempts
            const newAttempts = this.state.loginAttempts + 1;
            this.setState({
                loginAttempts: newAttempts,
                lastLoginAttempt: Date.now()
            });

            // Handle different error types
            let errorMessage = 'Login failed. Please try again.';

            if (error.status === 401) {
                errorMessage = 'Invalid email or password.';
            } else if (error.status === 429) {
                errorMessage = 'Too many login attempts. Please try again later.';
            } else if (error.message) {
                errorMessage = error.message;
            }

            this.setState({
                errors: { general: errorMessage }
            });

            // Call error callback
            if (this.props.onLoginError) {
                this.props.onLoginError(error);
            }

            // Show error notification
            App.showNotification({
                type: 'error',
                message: errorMessage
            });

        } finally {
            this.setState({ isLoading: false });
        }
    }

    // Public methods
    setEmail(email) {
        this.setState({ email });
    }

    setPassword(password) {
        this.setState({ password });
    }

    reset() {
        this.setState({
            email: '',
            password: '',
            rememberMe: false,
            errors: {},
            showPassword: false
        });
    }

    focus() {
        // Focus on email field
        setTimeout(() => {
            const emailInput = this.element?.querySelector('#email');
            if (emailInput) {
                emailInput.focus();
            }
        }, 100);
    }
}

// Register component
ComponentRegistry.register('LoginForm', LoginForm);

// Make globally available
window.LoginForm = LoginForm;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoginForm;
}
