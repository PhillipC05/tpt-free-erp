/**
 * Modal Manager Utility
 * Provides centralized modal dialog management
 */

class ModalManager {
    constructor() {
        this.activeModals = new Map();
        this.modalStack = [];
        this.globalEventListeners = new Set();
        this.defaultOptions = {
            size: 'medium', // small, medium, large, fullscreen
            closable: true,
            backdrop: true,
            keyboard: true,
            focus: true,
            animation: true,
            zIndex: 1000
        };
    }

    /**
     * Show a modal dialog
     */
    show(modalId, options = {}) {
        const modalOptions = { ...this.defaultOptions, ...options };

        // Prevent duplicate modals
        if (this.activeModals.has(modalId)) {
            this.bringToFront(modalId);
            return;
        }

        // Create modal container
        const modalContainer = this.createModalContainer(modalId, modalOptions);
        document.body.appendChild(modalContainer);

        // Add to active modals
        this.activeModals.set(modalId, {
            container: modalContainer,
            options: modalOptions,
            created: Date.now()
        });

        // Add to modal stack
        this.modalStack.push(modalId);

        // Setup event listeners
        this.setupModalEvents(modalId, modalOptions);

        // Handle backdrop and keyboard events
        if (modalOptions.backdrop) {
            this.setupBackdropEvents(modalId);
        }

        if (modalOptions.keyboard) {
            this.setupKeyboardEvents(modalId);
        }

        // Focus management
        if (modalOptions.focus) {
            this.focusModal(modalId);
        }

        // Animation
        if (modalOptions.animation) {
            setTimeout(() => {
                modalContainer.classList.add('show');
            }, 10);
        }

        // Call onShow callback
        if (options.onShow) {
            options.onShow(modalContainer);
        }

        return modalContainer;
    }

    /**
     * Hide a modal dialog
     */
    hide(modalId, options = {}) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container, options: modalOptions } = modalData;

        // Call onHide callback
        if (modalOptions.onHide) {
            modalOptions.onHide(container);
        }

        // Animation
        if (modalOptions.animation) {
            container.classList.remove('show');
            setTimeout(() => {
                this.removeModal(modalId);
            }, 300); // Match CSS transition duration
        } else {
            this.removeModal(modalId);
        }

        // Handle options
        if (options.force) {
            this.removeModal(modalId);
        }
    }

    /**
     * Hide all modals
     */
    hideAll() {
        const modalIds = [...this.activeModals.keys()];
        modalIds.forEach(id => this.hide(id, { force: true }));
    }

    /**
     * Update modal content
     */
    update(modalId, content, options = {}) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container } = modalData;
        const modalBody = container.querySelector('.modal-body');

        if (modalBody && content) {
            if (typeof content === 'string') {
                modalBody.innerHTML = content;
            } else if (content instanceof Node) {
                modalBody.innerHTML = '';
                modalBody.appendChild(content);
            }
        }

        // Update title if provided
        if (options.title) {
            const modalTitle = container.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = options.title;
            }
        }

        // Call onUpdate callback
        if (options.onUpdate) {
            options.onUpdate(container);
        }
    }

    /**
     * Bring modal to front
     */
    bringToFront(modalId) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container, options } = modalData;
        const highestZIndex = this.getHighestZIndex();
        container.style.zIndex = highestZIndex + 1;

        // Move to top of stack
        const index = this.modalStack.indexOf(modalId);
        if (index > -1) {
            this.modalStack.splice(index, 1);
            this.modalStack.push(modalId);
        }
    }

    /**
     * Create modal container
     */
    createModalContainer(modalId, options) {
        const container = DOM.create('div', {
            className: `modal-overlay ${options.size} ${options.animation ? 'fade' : ''}`,
            id: `modal-${modalId}`,
            style: `z-index: ${this.getHighestZIndex() + 1};`
        });

        // Backdrop
        if (options.backdrop) {
            const backdrop = DOM.create('div', { className: 'modal-backdrop' });
            container.appendChild(backdrop);
        }

        // Modal dialog
        const modalDialog = DOM.create('div', { className: 'modal-dialog' });
        const modalContent = DOM.create('div', { className: 'modal-content' });

        // Modal header
        if (options.title || options.closable) {
            const modalHeader = DOM.create('div', { className: 'modal-header' });

            if (options.title) {
                const modalTitle = DOM.create('h3', { className: 'modal-title' }, options.title);
                modalHeader.appendChild(modalTitle);
            }

            if (options.closable) {
                const closeButton = DOM.create('button', {
                    className: 'modal-close',
                    type: 'button',
                    onclick: () => this.hide(modalId)
                });
                closeButton.innerHTML = '<i class="fas fa-times"></i>';
                modalHeader.appendChild(closeButton);
            }

            modalContent.appendChild(modalHeader);
        }

        // Modal body
        const modalBody = DOM.create('div', { className: 'modal-body' });
        if (options.content) {
            if (typeof options.content === 'string') {
                modalBody.innerHTML = options.content;
            } else if (options.content instanceof Node) {
                modalBody.appendChild(options.content);
            }
        }
        modalContent.appendChild(modalBody);

        // Modal footer
        if (options.footer || options.buttons) {
            const modalFooter = DOM.create('div', { className: 'modal-footer' });

            if (options.footer) {
                if (typeof options.footer === 'string') {
                    modalFooter.innerHTML = options.footer;
                } else if (options.footer instanceof Node) {
                    modalFooter.appendChild(options.footer);
                }
            } else if (options.buttons) {
                options.buttons.forEach(buttonConfig => {
                    const button = DOM.create('button', {
                        className: `btn ${buttonConfig.class || 'btn-secondary'}`,
                        type: 'button',
                        onclick: buttonConfig.onClick || (() => this.hide(modalId))
                    }, buttonConfig.text || buttonConfig.label);
                    modalFooter.appendChild(button);
                });
            }

            modalContent.appendChild(modalFooter);
        }

        modalDialog.appendChild(modalContent);
        container.appendChild(modalDialog);

        return container;
    }

    /**
     * Setup modal-specific events
     */
    setupModalEvents(modalId, options) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container } = modalData;

        // Close button event
        const closeButton = container.querySelector('.modal-close');
        if (closeButton) {
            const closeHandler = () => this.hide(modalId);
            closeButton.addEventListener('click', closeHandler);
            this.globalEventListeners.add({ element: closeButton, event: 'click', handler: closeHandler });
        }

        // Auto-focus first input
        if (options.focus) {
            const firstInput = container.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    /**
     * Setup backdrop click events
     */
    setupBackdropEvents(modalId) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container } = modalData;

        const backdropHandler = (e) => {
            if (e.target === container || e.target.classList.contains('modal-backdrop')) {
                this.hide(modalId);
            }
        };

        container.addEventListener('click', backdropHandler);
        this.globalEventListeners.add({ element: container, event: 'click', handler: backdropHandler });
    }

    /**
     * Setup keyboard events
     */
    setupKeyboardEvents(modalId) {
        const keyboardHandler = (e) => {
            if (e.key === 'Escape') {
                // Only close the topmost modal
                if (this.modalStack[this.modalStack.length - 1] === modalId) {
                    this.hide(modalId);
                }
            }
        };

        document.addEventListener('keydown', keyboardHandler);
        this.globalEventListeners.add({ element: document, event: 'keydown', handler: keyboardHandler });
    }

    /**
     * Focus modal
     */
    focusModal(modalId) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container } = modalData;
        const focusableElements = container.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }

    /**
     * Remove modal from DOM and cleanup
     */
    removeModal(modalId) {
        const modalData = this.activeModals.get(modalId);
        if (!modalData) return;

        const { container } = modalData;

        // Remove from DOM
        if (container.parentNode) {
            container.parentNode.removeChild(container);
        }

        // Remove from active modals
        this.activeModals.delete(modalId);

        // Remove from modal stack
        const stackIndex = this.modalStack.indexOf(modalId);
        if (stackIndex > -1) {
            this.modalStack.splice(stackIndex, 1);
        }

        // Cleanup event listeners
        this.cleanupEventListeners();

        // Call onClose callback
        if (modalData.options.onClose) {
            modalData.options.onClose();
        }
    }

    /**
     * Cleanup event listeners
     */
    cleanupEventListeners() {
        this.globalEventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.globalEventListeners.clear();
    }

    /**
     * Get highest z-index
     */
    getHighestZIndex() {
        let highest = this.defaultOptions.zIndex;
        this.activeModals.forEach(modal => {
            const zIndex = parseInt(modal.container.style.zIndex) || 0;
            if (zIndex > highest) {
                highest = zIndex;
            }
        });
        return highest;
    }

    /**
     * Check if modal is active
     */
    isActive(modalId) {
        return this.activeModals.has(modalId);
    }

    /**
     * Get active modal count
     */
    getActiveCount() {
        return this.activeModals.size;
    }

    /**
     * Get modal data
     */
    getModal(modalId) {
        return this.activeModals.get(modalId);
    }

    // ============================================================================
    // STATIC METHODS FOR COMMON MODAL TYPES
    // ============================================================================

    /**
     * Show confirmation dialog
     */
    static confirm(options = {}) {
        const {
            title = 'Confirm Action',
            message = 'Are you sure?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            onConfirm,
            onCancel,
            type = 'warning'
        } = options;

        const content = DOM.create('div', { className: 'confirm-dialog' });
        const icon = DOM.create('div', { className: `confirm-icon ${type}` });
        icon.innerHTML = `<i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'times-circle' : 'info-circle'}"></i>`;
        content.appendChild(icon);

        const messageDiv = DOM.create('div', { className: 'confirm-message' }, message);
        content.appendChild(messageDiv);

        const modalManager = new ModalManager();
        return modalManager.show('confirm', {
            title,
            content,
            size: 'small',
            closable: true,
            buttons: [
                {
                    text: cancelText,
                    class: 'btn-secondary',
                    onClick: () => {
                        modalManager.hide('confirm');
                        onCancel && onCancel();
                    }
                },
                {
                    text: confirmText,
                    class: `btn-${type === 'danger' ? 'danger' : 'primary'}`,
                    onClick: () => {
                        modalManager.hide('confirm');
                        onConfirm && onConfirm();
                    }
                }
            ]
        });
    }

    /**
     * Show alert dialog
     */
    static alert(options = {}) {
        const {
            title = 'Alert',
            message = 'Message',
            buttonText = 'OK',
            onClose,
            type = 'info'
        } = options;

        const content = DOM.create('div', { className: 'alert-dialog' });
        const icon = DOM.create('div', { className: `alert-icon ${type}` });
        icon.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'times-circle' : 'info-circle'}"></i>`;
        content.appendChild(icon);

        const messageDiv = DOM.create('div', { className: 'alert-message' }, message);
        content.appendChild(messageDiv);

        const modalManager = new ModalManager();
        return modalManager.show('alert', {
            title,
            content,
            size: 'small',
            closable: true,
            buttons: [
                {
                    text: buttonText,
                    class: `btn-${type === 'danger' ? 'danger' : 'primary'}`,
                    onClick: () => {
                        modalManager.hide('alert');
                        onClose && onClose();
                    }
                }
            ]
        });
    }

    /**
     * Show form modal
     */
    static form(options = {}) {
        const {
            title = 'Form',
            fields = [],
            onSubmit,
            onCancel,
            submitText = 'Submit',
            cancelText = 'Cancel'
        } = options;

        const form = DOM.create('form', { className: 'modal-form' });

        fields.forEach(field => {
            const formGroup = DOM.create('div', { className: 'form-group' });

            if (field.label) {
                const label = DOM.create('label', {}, field.label);
                formGroup.appendChild(label);
            }

            let input;
            switch (field.type) {
                case 'textarea':
                    input = DOM.create('textarea', {
                        className: 'form-control',
                        name: field.name,
                        placeholder: field.placeholder,
                        required: field.required,
                        rows: field.rows || 3
                    });
                    break;
                case 'select':
                    input = DOM.create('select', {
                        className: 'form-control',
                        name: field.name,
                        required: field.required
                    });
                    if (field.options) {
                        field.options.forEach(option => {
                            const optionElement = DOM.create('option', {
                                value: option.value,
                                selected: option.selected
                            }, option.label);
                            input.appendChild(optionElement);
                        });
                    }
                    break;
                default:
                    input = DOM.create('input', {
                        type: field.type || 'text',
                        className: 'form-control',
                        name: field.name,
                        placeholder: field.placeholder,
                        required: field.required,
                        value: field.value || ''
                    });
            }

            formGroup.appendChild(input);
            form.appendChild(formGroup);
        });

        const modalManager = new ModalManager();
        return modalManager.show('form', {
            title,
            content: form,
            size: 'medium',
            closable: true,
            buttons: [
                {
                    text: cancelText,
                    class: 'btn-secondary',
                    onClick: () => {
                        modalManager.hide('form');
                        onCancel && onCancel();
                    }
                },
                {
                    text: submitText,
                    class: 'btn-primary',
                    onClick: () => {
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData.entries());
                        onSubmit && onSubmit(data);
                        modalManager.hide('form');
                    }
                }
            ]
        });
    }

    /**
     * Show loading modal
     */
    static loading(options = {}) {
        const {
            message = 'Loading...',
            title = 'Please Wait'
        } = options;

        const content = DOM.create('div', { className: 'loading-dialog' });
        const spinner = DOM.create('div', { className: 'spinner' });
        content.appendChild(spinner);

        const messageDiv = DOM.create('div', { className: 'loading-message' }, message);
        content.appendChild(messageDiv);

        const modalManager = new ModalManager();
        return modalManager.show('loading', {
            title,
            content,
            size: 'small',
            closable: false,
            backdrop: 'static'
        });
    }
}

// Create global instance
const modalManager = new ModalManager();

// Make globally available
if (typeof window !== 'undefined') {
    window.ModalManager = ModalManager;
    window.modalManager = modalManager;
}

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalManager;
}
