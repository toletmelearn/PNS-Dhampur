/**
 * PNS Dhampur School Management System
 * Frontend Validation System
 * 
 * Provides comprehensive client-side validation with:
 * - Real-time validation
 * - Custom validation rules
 * - Security checks
 * - User-friendly error messages
 * - Form sanitization
 */

class ValidationSystem {
    constructor() {
        this.forms = new Map();
        this.validators = new Map();
        this.errorMessages = new Map();
        this.init();
    }

    init() {
        this.setupDefaultValidators();
        this.setupDefaultMessages();
        this.bindEvents();
        this.initializeForms();
    }

    /**
     * Setup default validation rules
     */
    setupDefaultValidators() {
        // Required field validation
        this.validators.set('required', (value) => {
            return value !== null && value !== undefined && value.toString().trim() !== '';
        });

        // Email validation
        this.validators.set('email', (value) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return !value || emailRegex.test(value);
        });

        // Phone number validation (Indian format)
        this.validators.set('phone', (value) => {
            const phoneRegex = /^[6-9]\d{9}$/;
            return !value || phoneRegex.test(value.replace(/\D/g, ''));
        });

        // Numeric validation
        this.validators.set('numeric', (value) => {
            return !value || /^\d+$/.test(value);
        });

        // Alphabetic validation
        this.validators.set('alpha', (value) => {
            return !value || /^[a-zA-Z\s]+$/.test(value);
        });

        // Alphanumeric validation
        this.validators.set('alphanumeric', (value) => {
            return !value || /^[a-zA-Z0-9\s]+$/.test(value);
        });

        // Minimum length validation
        this.validators.set('min', (value, min) => {
            return !value || value.toString().length >= parseInt(min);
        });

        // Maximum length validation
        this.validators.set('max', (value, max) => {
            return !value || value.toString().length <= parseInt(max);
        });

        // Date validation
        this.validators.set('date', (value) => {
            if (!value) return true;
            const date = new Date(value);
            return date instanceof Date && !isNaN(date);
        });

        // Password strength validation
        this.validators.set('password', (value) => {
            if (!value) return true;
            // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/.test(value);
        });

        // Confirm password validation
        this.validators.set('confirmed', (value, fieldName) => {
            const confirmField = document.querySelector(`[name="${fieldName}"]`);
            return !value || !confirmField || value === confirmField.value;
        });

        // URL validation
        this.validators.set('url', (value) => {
            if (!value) return true;
            try {
                new URL(value);
                return true;
            } catch {
                return false;
            }
        });

        // File size validation (in KB)
        this.validators.set('max_file_size', (file, maxSize) => {
            if (!file || !file.files || file.files.length === 0) return true;
            const fileSizeKB = file.files[0].size / 1024;
            return fileSizeKB <= parseInt(maxSize);
        });

        // File type validation
        this.validators.set('file_types', (file, allowedTypes) => {
            if (!file || !file.files || file.files.length === 0) return true;
            const fileExtension = file.files[0].name.split('.').pop().toLowerCase();
            const allowed = allowedTypes.split(',').map(type => type.trim().toLowerCase());
            return allowed.includes(fileExtension);
        });

        // XSS prevention
        this.validators.set('no_xss', (value) => {
            if (!value) return true;
            const xssPatterns = [
                /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
                /javascript:/gi,
                /on\w+\s*=/gi,
                /<iframe/gi,
                /<object/gi,
                /<embed/gi,
                /<link/gi,
                /<meta/gi
            ];
            return !xssPatterns.some(pattern => pattern.test(value));
        });

        // SQL injection prevention
        this.validators.set('no_sql_injection', (value) => {
            if (!value) return true;
            const sqlPatterns = [
                /(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/gi,
                /(--|\/\*|\*\/|;|'|"|`)/g,
                /(\bOR\b|\bAND\b).*[=<>]/gi
            ];
            return !sqlPatterns.some(pattern => pattern.test(value));
        });
    }

    /**
     * Setup default error messages
     */
    setupDefaultMessages() {
        this.errorMessages.set('required', 'This field is required.');
        this.errorMessages.set('email', 'Please enter a valid email address.');
        this.errorMessages.set('phone', 'Please enter a valid 10-digit phone number.');
        this.errorMessages.set('numeric', 'This field must contain only numbers.');
        this.errorMessages.set('alpha', 'This field must contain only letters.');
        this.errorMessages.set('alphanumeric', 'This field must contain only letters and numbers.');
        this.errorMessages.set('min', 'This field must be at least {min} characters long.');
        this.errorMessages.set('max', 'This field must not exceed {max} characters.');
        this.errorMessages.set('date', 'Please enter a valid date.');
        this.errorMessages.set('password', 'Password must be at least 8 characters with uppercase, lowercase, and number.');
        this.errorMessages.set('confirmed', 'The confirmation does not match.');
        this.errorMessages.set('url', 'Please enter a valid URL.');
        this.errorMessages.set('max_file_size', 'File size must not exceed {max_file_size}KB.');
        this.errorMessages.set('file_types', 'Only {file_types} files are allowed.');
        this.errorMessages.set('no_xss', 'Invalid characters detected. Please remove any script tags or HTML.');
        this.errorMessages.set('no_sql_injection', 'Invalid characters detected. Please avoid SQL keywords.');
    }

    /**
     * Bind events to forms and fields
     */
    bindEvents() {
        // Form submission
        document.addEventListener('submit', (e) => {
            if (e.target.hasAttribute('data-validate')) {
                if (!this.validateForm(e.target)) {
                    e.preventDefault();
                    this.focusFirstError(e.target);
                }
            }
        });

        // Real-time validation on input
        document.addEventListener('input', (e) => {
            if (e.target.hasAttribute('data-validate-rules')) {
                this.validateField(e.target);
            }
        });

        // Real-time validation on blur
        document.addEventListener('blur', (e) => {
            if (e.target.hasAttribute('data-validate-rules')) {
                this.validateField(e.target);
            }
        }, true);

        // File input validation
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.hasAttribute('data-validate-rules')) {
                this.validateField(e.target);
            }
        });
    }

    /**
     * Initialize all forms with validation
     */
    initializeForms() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            this.initializeForm(form);
        });
    }

    /**
     * Initialize a specific form
     */
    initializeForm(form) {
        const formId = form.id || `form_${Date.now()}`;
        if (!form.id) form.id = formId;

        this.forms.set(formId, {
            element: form,
            fields: new Map(),
            isValid: false
        });

        // Initialize form fields
        form.querySelectorAll('[data-validate-rules]').forEach(field => {
            this.initializeField(field, formId);
        });
    }

    /**
     * Initialize a specific field
     */
    initializeField(field, formId) {
        const fieldName = field.name || field.id;
        const rules = this.parseRules(field.getAttribute('data-validate-rules'));
        
        const fieldConfig = {
            element: field,
            rules: rules,
            isValid: false,
            errorElement: null
        };

        // Create error display element
        this.createErrorElement(field, fieldConfig);

        // Store field configuration
        const form = this.forms.get(formId);
        if (form) {
            form.fields.set(fieldName, fieldConfig);
        }
    }

    /**
     * Parse validation rules from data attribute
     */
    parseRules(rulesString) {
        const rules = new Map();
        
        rulesString.split('|').forEach(rule => {
            const [name, ...params] = rule.split(':');
            rules.set(name.trim(), params.length > 0 ? params.join(':').split(',') : []);
        });

        return rules;
    }

    /**
     * Create error display element for a field
     */
    createErrorElement(field, fieldConfig) {
        const errorId = `${field.name || field.id}_error`;
        let errorElement = document.getElementById(errorId);

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = errorId;
            errorElement.className = 'validation-error text-danger small mt-1';
            errorElement.style.display = 'none';

            // Insert after the field or its parent container
            const container = field.closest('.form-group') || field.closest('.mb-3') || field.parentNode;
            container.appendChild(errorElement);
        }

        fieldConfig.errorElement = errorElement;
    }

    /**
     * Validate a single field
     */
    validateField(field) {
        const formId = field.closest('form').id;
        const form = this.forms.get(formId);
        
        if (!form) return true;

        const fieldName = field.name || field.id;
        const fieldConfig = form.fields.get(fieldName);
        
        if (!fieldConfig) return true;

        const value = this.getFieldValue(field);
        const errors = [];

        // Apply security sanitization
        if (field.type !== 'file') {
            this.sanitizeField(field);
        }

        // Validate each rule
        fieldConfig.rules.forEach((params, ruleName) => {
            const validator = this.validators.get(ruleName);
            
            if (validator) {
                let isValid;
                
                if (field.type === 'file') {
                    isValid = validator(field, ...params);
                } else {
                    isValid = validator(value, ...params);
                }

                if (!isValid) {
                    let message = this.errorMessages.get(ruleName) || `Validation failed for ${ruleName}`;
                    
                    // Replace placeholders in message
                    params.forEach((param, index) => {
                        message = message.replace(`{${ruleName}}`, param);
                        message = message.replace(`{${index}}`, param);
                    });

                    errors.push(message);
                }
            }
        });

        // Update field validation state
        fieldConfig.isValid = errors.length === 0;
        this.displayFieldErrors(field, fieldConfig, errors);

        // Update form validation state
        this.updateFormValidationState(formId);

        return fieldConfig.isValid;
    }

    /**
     * Get field value based on field type
     */
    getFieldValue(field) {
        switch (field.type) {
            case 'checkbox':
                return field.checked;
            case 'radio':
                const radioGroup = document.querySelectorAll(`[name="${field.name}"]`);
                const checked = Array.from(radioGroup).find(radio => radio.checked);
                return checked ? checked.value : '';
            case 'select-multiple':
                return Array.from(field.selectedOptions).map(option => option.value);
            case 'file':
                return field.files;
            default:
                return field.value;
        }
    }

    /**
     * Sanitize field input to prevent XSS
     */
    sanitizeField(field) {
        if (field.type === 'text' || field.type === 'textarea') {
            // Remove potentially dangerous characters
            field.value = field.value
                .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                .replace(/javascript:/gi, '')
                .replace(/on\w+\s*=/gi, '');
        }
    }

    /**
     * Display field validation errors
     */
    displayFieldErrors(field, fieldConfig, errors) {
        const errorElement = fieldConfig.errorElement;
        
        if (errors.length > 0) {
            errorElement.innerHTML = errors.join('<br>');
            errorElement.style.display = 'block';
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
        } else {
            errorElement.style.display = 'none';
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    }

    /**
     * Validate entire form
     */
    validateForm(form) {
        const formId = form.id;
        const formConfig = this.forms.get(formId);
        
        if (!formConfig) return true;

        let isFormValid = true;

        // Validate all fields
        formConfig.fields.forEach((fieldConfig, fieldName) => {
            const isFieldValid = this.validateField(fieldConfig.element);
            if (!isFieldValid) {
                isFormValid = false;
            }
        });

        formConfig.isValid = isFormValid;
        return isFormValid;
    }

    /**
     * Update form validation state
     */
    updateFormValidationState(formId) {
        const form = this.forms.get(formId);
        if (!form) return;

        let isFormValid = true;
        form.fields.forEach(fieldConfig => {
            if (!fieldConfig.isValid) {
                isFormValid = false;
            }
        });

        form.isValid = isFormValid;

        // Update submit button state
        const submitButton = form.element.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = !isFormValid;
            submitButton.classList.toggle('btn-secondary', !isFormValid);
            submitButton.classList.toggle('btn-primary', isFormValid);
        }
    }

    /**
     * Focus on first field with error
     */
    focusFirstError(form) {
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Add custom validator
     */
    addValidator(name, validator, message) {
        this.validators.set(name, validator);
        if (message) {
            this.errorMessages.set(name, message);
        }
    }

    /**
     * Remove field validation
     */
    removeFieldValidation(field) {
        field.classList.remove('is-valid', 'is-invalid');
        const errorElement = document.getElementById(`${field.name || field.id}_error`);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * Reset form validation
     */
    resetFormValidation(form) {
        const formId = form.id;
        const formConfig = this.forms.get(formId);
        
        if (formConfig) {
            formConfig.fields.forEach(fieldConfig => {
                this.removeFieldValidation(fieldConfig.element);
                fieldConfig.isValid = false;
            });
            formConfig.isValid = false;
        }
    }

    /**
     * Get form validation state
     */
    isFormValid(form) {
        const formConfig = this.forms.get(form.id);
        return formConfig ? formConfig.isValid : false;
    }

    /**
     * Show validation summary
     */
    showValidationSummary(form, errors) {
        let summaryElement = form.querySelector('.validation-summary');
        
        if (!summaryElement) {
            summaryElement = document.createElement('div');
            summaryElement.className = 'validation-summary alert alert-danger';
            form.insertBefore(summaryElement, form.firstChild);
        }

        if (errors.length > 0) {
            summaryElement.innerHTML = `
                <h6>Please correct the following errors:</h6>
                <ul class="mb-0">
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            `;
            summaryElement.style.display = 'block';
        } else {
            summaryElement.style.display = 'none';
        }
    }
}

// Initialize validation system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.validationSystem = new ValidationSystem();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ValidationSystem;
}