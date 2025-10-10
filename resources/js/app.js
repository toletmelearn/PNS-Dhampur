import './bootstrap';

/**
 * Advanced FormHandler Class for AJAX Form Submissions
 * Provides comprehensive form handling with validation, loading states, and user feedback
 */
class FormHandler {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.submitBtn = this.form.querySelector('button[type="submit"]');
        this.spinner = this.form.querySelector('.loading-spinner');
        this.btnText = this.form.querySelector('.btn-text');
        this.notificationArea = document.getElementById('notification-area');
        this.init();
    }

    init() {
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        this.setupValidation();
    }

    setupValidation() {
        // Setup real-time validation for all inputs
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this, input));
            input.addEventListener('input', this.clearFieldError.bind(this, input));
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';

        // Basic required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} is required`;
        }

        // Pattern validation
        if (isValid && field.pattern && value) {
            const pattern = new RegExp(field.pattern);
            if (!pattern.test(value)) {
                isValid = false;
                errorMessage = field.title || `Invalid ${this.getFieldLabel(field)} format`;
            }
        }

        // Length validation
        if (isValid && field.minLength && value.length < field.minLength) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must be at least ${field.minLength} characters`;
        }

        if (isValid && field.maxLength && value.length > field.maxLength) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(field)} must not exceed ${field.maxLength} characters`;
        }

        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        // Validate form first
        if (!this.validateForm()) {
            this.showError('Please fix validation errors before submitting');
            return;
        }

        // Show loading state
        this.setLoading(true);

        try {
            const formData = new FormData(this.form);
            const response = await fetch(this.form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const result = await response.json();

            if (response.ok) {
                this.showSuccess(result.message || 'Operation completed successfully');
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1500);
                } else if (result.reset) {
                    this.form.reset();
                }
            } else {
                if (result.errors) {
                    this.showValidationErrors(result.errors);
                } else {
                    this.showError(result.message || 'An error occurred');
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showError('Network error. Please check your connection and try again.');
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(loading) {
        if (loading) {
            this.submitBtn.disabled = true;
            if (this.spinner) this.spinner.style.display = 'inline-block';
            if (this.btnText) this.btnText.style.display = 'none';
            this.form.classList.add('loading');
        } else {
            this.submitBtn.disabled = false;
            if (this.spinner) this.spinner.style.display = 'none';
            if (this.btnText) this.btnText.style.display = 'inline-block';
            this.form.classList.remove('loading');
        }
    }

    showFieldValidation(field, isValid, message) {
        const errorDiv = document.getElementById(`${field.name}-error`);
        const successDiv = document.getElementById(`${field.name}-success`);

        field.classList.remove('is-valid', 'is-invalid');

        if (isValid) {
            field.classList.add('is-valid');
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }
            if (successDiv) {
                successDiv.style.display = 'block';
            }
        } else {
            field.classList.add('is-invalid');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
            if (successDiv) {
                successDiv.style.display = 'none';
            }
        }
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = document.getElementById(`${field.name}-error`);
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    showValidationErrors(errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.showFieldValidation(field, false, errors[fieldName][0]);
            }
        });
    }

    getFieldLabel(field) {
        const label = this.form.querySelector(`label[for="${field.id}"]`);
        return label ? label.textContent.replace('*', '').trim() : field.name;
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'danger');
    }

    showToast(message, type = 'info') {
        if (!this.notificationArea) return;

        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        this.notificationArea.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
}

/**
 * Validation Functions for Specific Fields
 */
window.validateAadhaar = function(input) {
    const value = input.value.replace(/\s/g, '');
    const isValid = /^[0-9]{12}$/.test(value);
    
    if (isValid) {
        // Basic Aadhaar validation (Verhoeff algorithm would be more accurate)
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        document.getElementById('aadhaar-error').style.display = 'none';
        document.getElementById('aadhaar-success').style.display = 'block';
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        document.getElementById('aadhaar-error').textContent = 'Please enter a valid 12-digit Aadhaar number';
        document.getElementById('aadhaar-error').style.display = 'block';
        document.getElementById('aadhaar-success').style.display = 'none';
    }
};

window.formatAadhaarInput = function(input) {
    let value = input.value.replace(/\s/g, '');
    if (value.length > 12) {
        value = value.substring(0, 12);
    }
    // Format as XXXX XXXX XXXX
    value = value.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
    input.value = value;
};

window.validateEmailFormat = function(input) {
    const email = input.value.trim();
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    
    if (email && !emailPattern.test(email)) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        document.getElementById('email-error').textContent = 'Please enter a valid email address';
        document.getElementById('email-error').style.display = 'block';
        document.getElementById('email-success').style.display = 'none';
    } else if (email) {
        input.classList.remove('is-invalid');
        // Don't show as valid yet - wait for availability check
    }
};

window.checkEmailAvailability = async function(input) {
    const email = input.value.trim();
    if (!email) return;

    // First validate format
    window.validateEmailFormat(input);
    if (input.classList.contains('is-invalid')) return;

    try {
        const response = await fetch('/api/check-email-availability', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ email: email })
        });

        const result = await response.json();

        if (result.available) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            document.getElementById('email-error').style.display = 'none';
            document.getElementById('email-success').style.display = 'block';
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            document.getElementById('email-error').textContent = 'This email is already registered';
            document.getElementById('email-error').style.display = 'block';
            document.getElementById('email-success').style.display = 'none';
        }
    } catch (error) {
        console.error('Email availability check failed:', error);
        // Don't show error to user for availability check failure
    }
};

/**
 * Global Loading State Management
 * Provides consistent loading indicators across the application
 */

// Global loading utilities
window.LoadingManager = {
    // Show loading state for any button
    showButtonLoading: function(buttonSelector, loadingText = 'Loading...') {
        const button = document.querySelector(buttonSelector);
        if (button) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${loadingText}`;
            button.classList.add('loading-state');
        }
        return button;
    },

    // Hide loading state and restore original text
    hideButtonLoading: function(buttonSelector) {
        const button = document.querySelector(buttonSelector);
        if (button && button.dataset.originalText) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText;
            button.classList.remove('loading-state');
            delete button.dataset.originalText;
        }
        return button;
    },

    // Show success state temporarily
    showButtonSuccess: function(buttonSelector, successText = 'Success!', duration = 2000) {
        const button = document.querySelector(buttonSelector);
        if (button) {
            const originalClass = button.className;
            button.innerHTML = `<i class="fas fa-check me-2"></i>${successText}`;
            button.classList.remove('btn-primary', 'btn-primary-custom');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                    button.className = originalClass;
                    delete button.dataset.originalText;
                }
            }, duration);
        }
        return button;
    },

    // Show error state temporarily
    showButtonError: function(buttonSelector, errorText = 'Error!', duration = 2000) {
        const button = document.querySelector(buttonSelector);
        if (button) {
            const originalClass = button.className;
            button.innerHTML = `<i class="fas fa-times me-2"></i>${errorText}`;
            button.classList.remove('btn-primary', 'btn-primary-custom');
            button.classList.add('btn-danger');
            
            setTimeout(() => {
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                    button.className = originalClass;
                    button.disabled = false;
                    delete button.dataset.originalText;
                }
            }, duration);
        }
        return button;
    },

    // Generic form submission handler with loading states
    handleFormSubmission: function(formSelector, options = {}) {
        const form = document.querySelector(formSelector);
        const submitButton = form?.querySelector('button[type="submit"], input[type="submit"]');
        
        if (!form || !submitButton) return;

        const defaultOptions = {
            loadingText: 'Saving...',
            successText: 'Saved!',
            errorText: 'Error!',
            preventMultipleSubmissions: true,
            showSuccessState: true,
            successDuration: 1000
        };

        const config = { ...defaultOptions, ...options };

        form.addEventListener('submit', function(e) {
            if (config.preventMultipleSubmissions && submitButton.disabled) {
                e.preventDefault();
                return;
            }

            // Show loading state
            LoadingManager.showButtonLoading(
                `#${submitButton.id}` || 'button[type="submit"]', 
                config.loadingText
            );
        });

        return { form, submitButton };
    }
};

// Initialize FormHandler for all AJAX forms
document.addEventListener('DOMContentLoaded', function() {
    // Initialize FormHandler for forms with data-ajax="true"
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    ajaxForms.forEach(form => {
        if (form.id) {
            new FormHandler(form.id);
        }
    });

    // Add CSS for loading states and validation
    if (!document.querySelector('#enhanced-form-styles')) {
        const style = document.createElement('style');
        style.id = 'enhanced-form-styles';
        style.textContent = `
            .loading-state {
                position: relative;
                pointer-events: none;
            }
            
            .loading-state .fas.fa-spinner {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .btn.loading-state {
                opacity: 0.8;
            }

            .form-control.is-valid {
                border-color: #28a745;
                padding-right: calc(1.5em + 0.75rem);
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.88 1.88 3.75-3.75.94.94-4.69 4.69z'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right calc(0.375em + 0.1875rem) center;
                background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            }

            .form-control.is-invalid {
                border-color: #dc3545;
                padding-right: calc(1.5em + 0.75rem);
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right calc(0.375em + 0.1875rem) center;
                background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            }

            .valid-feedback {
                display: none;
                width: 100%;
                margin-top: 0.25rem;
                font-size: 0.875em;
                color: #28a745;
            }

            .invalid-feedback {
                display: none;
                width: 100%;
                margin-top: 0.25rem;
                font-size: 0.875em;
                color: #dc3545;
            }

            .form-control.is-valid ~ .valid-feedback,
            .form-control.is-invalid ~ .invalid-feedback {
                display: block;
            }

            .loading-spinner {
                display: none;
            }

            .form.loading {
                opacity: 0.8;
                pointer-events: none;
            }

            #notification-area {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
                max-width: 400px;
            }

            #notification-area .alert {
                margin-bottom: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
        `;
        document.head.appendChild(style);
    }
});

// Enhanced jQuery integration for existing code
if (typeof jQuery !== 'undefined') {
    jQuery.fn.showLoading = function(text = 'Loading...') {
        return this.each(function() {
            LoadingManager.showButtonLoading(`#${this.id}` || this.tagName.toLowerCase(), text);
        });
    };

    jQuery.fn.hideLoading = function() {
        return this.each(function() {
            LoadingManager.hideButtonLoading(`#${this.id}` || this.tagName.toLowerCase());
        });
    };

    jQuery.fn.showSuccess = function(text = 'Success!', duration = 2000) {
        return this.each(function() {
            LoadingManager.showButtonSuccess(`#${this.id}` || this.tagName.toLowerCase(), text, duration);
        });
    };
}