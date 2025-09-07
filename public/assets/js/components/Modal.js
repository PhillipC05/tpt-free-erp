/**
 * TPT Free ERP - Modal Component
 * Advanced modal and dialog system with various types and configurations
 */

class Modal extends Component {
    constructor(props = {}) {
        super(props);
        this.props = {
            title: '',
            content: '',
            size: 'medium', // small, medium, large, fullscreen
            type: 'default', // default, confirm, alert, form, custom
            show: false,
            closable: true,
            backdrop: true,
            keyboard: true,
            focus: true,
            animation: true,
            position: 'center', // center, top, bottom
            zIndex: 1050,
            onShow: null,
            onHide: null,
            onConfirm: null,
            onCancel: null,
            onClose: null,
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            closeText: '×',
            confirmButton: {
                text: 'OK',
                className: 'btn btn-primary',
                disabled: false
            },
            cancelButton: {
                text: 'Cancel',
                className: 'btn btn-secondary',
                show: true
            },
            ...props
        };

        this.state = {
            isVisible: this.props.show,
            isAnimating: false,
            hasBeenShown: false
        };

        // Bind methods
        this.show = this.show.bind(this);
        this.hide = this.hide.bind(this);
        this.toggle = this.toggle.bind(this);
        this.handleBackdropClick = this.handleBackdropClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleConfirm = this.handleConfirm.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
        this.handleClose = this.handleClose.bind(this);
        this.createModalElement = this.createModalElement.bind(this);
        this.destroyModalElement = this.destroyModalElement.bind(this);
    }

    componentDidMount() {
        if (this.props.show) {
            this.show();
        }
    }

    componentDidUpdate(prevProps) {
        if (prevProps.show !== this.props.show) {
            if (this.props.show) {
                this.show();
            } else {
                this.hide();
            }
        }
    }

    componentWillUnmount() {
        this.destroyModalElement();
        this.removeEventListeners();
    }

    show() {
        if (this.state.isVisible) return;

        this.setState({
            isVisible: true,
            isAnimating: true,
            hasBeenShown: true
        });

        // Create modal element
        this.createModalElement();

        // Add event listeners
        this.addEventListeners();

        // Focus management
        if (this.props.focus) {
            this.focusModal();
        }

        // Call onShow callback
        if (this.props.onShow) {
            this.props.onShow();
        }

        // Animation end
        setTimeout(() => {
            this.setState({ isAnimating: false });
        }, 300);
    }

    hide() {
        if (!this.state.isVisible) return;

        this.setState({ isAnimating: true });

        // Call onHide callback
        if (this.props.onHide) {
            this.props.onHide();
        }

        // Animation end
        setTimeout(() => {
            this.setState({
                isVisible: false,
                isAnimating: false
            });
            this.destroyModalElement();
            this.removeEventListeners();
            this.restoreFocus();
        }, 300);
    }

    toggle() {
        if (this.state.isVisible) {
            this.hide();
        } else {
            this.show();
        }
    }

    createModalElement() {
        if (this.modalElement) return;

        // Create modal container
        this.modalElement = DOM.create('div', {
            className: `modal-overlay ${this.getModalClasses()}`,
            style: `z-index: ${this.props.zIndex}`
        });

        // Create modal dialog
        const modalDialog = DOM.create('div', {
            className: `modal-dialog modal-${this.props.size} modal-${this.props.position}`
        });

        // Create modal content
        const modalContent = DOM.create('div', { className: 'modal-content' });

        // Header
        if (this.props.title || this.props.closable) {
            const modalHeader = DOM.create('div', { className: 'modal-header' });

            if (this.props.title) {
                const titleElement = DOM.create('h3', { className: 'modal-title' }, this.props.title);
                modalHeader.appendChild(titleElement);
            }

            if (this.props.closable) {
                const closeButton = DOM.create('button', {
                    type: 'button',
                    className: 'modal-close',
                    'aria-label': 'Close modal',
                    onclick: this.handleClose
                });
                closeButton.innerHTML = this.props.closeText;
                modalHeader.appendChild(closeButton);
            }

            modalContent.appendChild(modalHeader);
        }

        // Body
        const modalBody = DOM.create('div', { className: 'modal-body' });

        if (this.props.type === 'confirm') {
            modalBody.appendChild(this.renderConfirmContent());
        } else if (this.props.type === 'alert') {
            modalBody.appendChild(this.renderAlertContent());
        } else if (this.props.type === 'form') {
            modalBody.appendChild(this.renderFormContent());
        } else {
            // Default content
            if (typeof this.props.content === 'string') {
                modalBody.innerHTML = this.props.content;
            } else if (this.props.content instanceof Node) {
                modalBody.appendChild(this.props.content);
            } else if (this.props.children) {
                if (typeof this.props.children === 'string') {
                    modalBody.innerHTML = this.props.children;
                } else if (this.props.children instanceof Node) {
                    modalBody.appendChild(this.props.children);
                }
            }
        }

        modalContent.appendChild(modalBody);

        // Footer
        if (this.needsFooter()) {
            const modalFooter = DOM.create('div', { className: 'modal-footer' });
            modalFooter.appendChild(this.renderFooterButtons());
            modalContent.appendChild(modalFooter);
        }

        modalDialog.appendChild(modalContent);
        this.modalElement.appendChild(modalDialog);

        // Add backdrop click handler
        if (this.props.backdrop) {
            this.modalElement.addEventListener('click', this.handleBackdropClick);
        }

        // Append to body
        document.body.appendChild(this.modalElement);
    }

    destroyModalElement() {
        if (this.modalElement && this.modalElement.parentNode) {
            this.modalElement.parentNode.removeChild(this.modalElement);
            this.modalElement = null;
        }
    }

    getModalClasses() {
        const classes = [
            this.state.isVisible ? 'show' : '',
            this.state.isAnimating ? 'animating' : '',
            this.props.animation ? 'animated' : '',
            `modal-${this.props.type}`
        ].filter(Boolean);

        return classes.join(' ');
    }

    needsFooter() {
        return this.props.type === 'confirm' ||
               this.props.type === 'alert' ||
               this.props.confirmButton.show ||
               this.props.cancelButton.show;
    }

    renderConfirmContent() {
        const content = DOM.create('div', { className: 'confirm-content' });

        const icon = DOM.create('div', { className: 'confirm-icon' });
        icon.innerHTML = '<i class="fas fa-question-circle"></i>';
        content.appendChild(icon);

        const message = DOM.create('div', { className: 'confirm-message' });
        message.textContent = this.props.content || 'Are you sure you want to proceed?';
        content.appendChild(message);

        return content;
    }

    renderAlertContent() {
        const content = DOM.create('div', { className: 'alert-content' });

        const icon = DOM.create('div', { className: 'alert-icon' });
        const iconClass = this.getAlertIconClass();
        icon.innerHTML = `<i class="${iconClass}"></i>`;
        content.appendChild(icon);

        const message = DOM.create('div', { className: 'alert-message' });
        message.textContent = this.props.content || 'Alert message';
        content.appendChild(message);

        return content;
    }

    renderFormContent() {
        const content = DOM.create('div', { className: 'form-content' });

        if (this.props.content) {
            if (typeof this.props.content === 'string') {
                content.innerHTML = this.props.content;
            } else if (this.props.content instanceof Node) {
                content.appendChild(this.props.content);
            }
        }

        return content;
    }

    renderFooterButtons() {
        const buttons = DOM.create('div', { className: 'modal-buttons' });

        // Cancel button
        if (this.props.cancelButton.show) {
            const cancelBtn = DOM.create('button', {
                type: 'button',
                className: this.props.cancelButton.className,
                onclick: this.handleCancel
            }, this.props.cancelButton.text);
            buttons.appendChild(cancelBtn);
        }

        // Confirm button
        if (this.props.confirmButton.show !== false) {
            const confirmBtn = DOM.create('button', {
                type: 'button',
                className: this.props.confirmButton.className,
                disabled: this.props.confirmButton.disabled,
                onclick: this.handleConfirm
            }, this.props.confirmButton.text);
            buttons.appendChild(confirmBtn);
        }

        return buttons;
    }

    getAlertIconClass() {
        const type = this.props.alertType || 'info';
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    handleBackdropClick(e) {
        if (e.target === this.modalElement && this.props.closable) {
            this.handleClose();
        }
    }

    handleKeyDown(e) {
        if (!this.props.keyboard) return;

        if (e.key === 'Escape' && this.props.closable) {
            this.handleClose();
        } else if (e.key === 'Enter' && this.props.type === 'confirm') {
            this.handleConfirm();
        }
    }

    handleConfirm() {
        if (this.props.onConfirm) {
            const result = this.props.onConfirm();
            if (result !== false) {
                this.hide();
            }
        } else {
            this.hide();
        }
    }

    handleCancel() {
        if (this.props.onCancel) {
            const result = this.props.onCancel();
            if (result !== false) {
                this.hide();
            }
        } else {
            this.hide();
        }
    }

    handleClose() {
        if (this.props.onClose) {
            const result = this.props.onClose();
            if (result !== false) {
                this.hide();
            }
        } else {
            this.hide();
        }
    }

    addEventListeners() {
        if (this.props.keyboard) {
            document.addEventListener('keydown', this.handleKeyDown);
        }
    }

    removeEventListeners() {
        document.removeEventListener('keydown', this.handleKeyDown);
    }

    focusModal() {
        if (this.modalElement) {
            const focusableElements = this.modalElement.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            } else {
                this.modalElement.focus();
            }
        }
    }

    restoreFocus() {
        // Restore focus to previously focused element
        // This would need to be implemented based on focus management needs
    }

    render() {
        // Modal is rendered directly to body, not as part of component tree
        return null;
    }

    // Public API methods
    isVisible() {
        return this.state.isVisible;
    }

    setTitle(title) {
        this.props.title = title;
        this.updateModalContent();
    }

    setContent(content) {
        this.props.content = content;
        this.updateModalContent();
    }

    updateModalContent() {
        if (this.modalElement) {
            // Re-render modal content
            this.destroyModalElement();
            if (this.state.isVisible) {
                this.createModalElement();
            }
        }
    }

    setZIndex(zIndex) {
        this.props.zIndex = zIndex;
        if (this.modalElement) {
            this.modalElement.style.zIndex = zIndex;
        }
    }

    // Static methods for creating common modal types
    static confirm(options = {}) {
        const modal = new Modal({
            type: 'confirm',
            size: 'small',
            title: options.title || 'Confirm Action',
            content: options.message || 'Are you sure you want to proceed?',
            confirmText: options.confirmText || 'Yes',
            cancelText: options.cancelText || 'No',
            onConfirm: options.onConfirm,
            onCancel: options.onCancel,
            ...options
        });

        modal.show();
        return modal;
    }

    static alert(options = {}) {
        const modal = new Modal({
            type: 'alert',
            size: 'small',
            title: options.title || 'Alert',
            content: options.message || 'Alert message',
            alertType: options.type || 'info',
            confirmText: options.confirmText || 'OK',
            cancelButton: { show: false },
            onConfirm: options.onConfirm,
            ...options
        });

        modal.show();
        return modal;
    }

    static form(options = {}) {
        const modal = new Modal({
            type: 'form',
            size: options.size || 'medium',
            title: options.title || 'Form',
            content: options.content,
            confirmText: options.submitText || 'Submit',
            cancelText: options.cancelText || 'Cancel',
            onConfirm: options.onSubmit,
            onCancel: options.onCancel,
            ...options
        });

        modal.show();
        return modal;
    }

    static custom(options = {}) {
        const modal = new Modal({
            type: 'custom',
            ...options
        });

        modal.show();
        return modal;
    }
}

// Modal Manager for handling multiple modals
class ModalManager {
    constructor() {
        this.modals = new Map();
        this.stack = [];
        this.baseZIndex = 1050;
    }

    create(options = {}) {
        const modal = new Modal({
            ...options,
            zIndex: this.baseZIndex + this.stack.length
        });

        const id = `modal_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        this.modals.set(id, modal);
        this.stack.push(id);

        return modal;
    }

    show(options = {}) {
        const modal = this.create(options);
        modal.show();
        return modal;
    }

    hide(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.hide();
            this.stack = this.stack.filter(modalId => modalId !== id);
            this.modals.delete(id);
        }
    }

    hideAll() {
        this.stack.forEach(id => {
            const modal = this.modals.get(id);
            if (modal) {
                modal.hide();
            }
        });
        this.modals.clear();
        this.stack = [];
    }

    getActiveModal() {
        const activeId = this.stack[this.stack.length - 1];
        return activeId ? this.modals.get(activeId) : null;
    }

    confirm(options = {}) {
        return Modal.confirm(options);
    }

    alert(options = {}) {
        return Modal.alert(options);
    }

    form(options = {}) {
        return Modal.form(options);
    }
}

// Global modal manager instance
const ModalManagerInstance = new ModalManager();

// Register component
ComponentRegistry.register('Modal', Modal);

// Make globally available
window.Modal = Modal;
window.ModalManager = ModalManagerInstance;

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Modal, ModalManager };
}
