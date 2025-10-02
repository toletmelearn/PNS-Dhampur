/**
 * Comprehensive Client-Side Validation System for Attendance Module
 * Provides real-time form validation with accessibility features
 */

class AttendanceValidator {
    constructor() {
        this.rules = {};
        this.messages = {};
        this.initializeDefaultMessages();
        this.bindEvents();
    }

    /**
     * Initialize default validation messages
     */
    initializeDefaultMessages() {
        this.messages = {
            required: 'This field is required.',
            email: 'Please enter a valid email address.',
            min: 'This field must be at least {min} characters long.',
            max: 'This field must not exceed {max} characters.',
            minValue: 'Value must be at least {min}.',
            maxValue: 'Value must not exceed {max}.',
            numeric: 'This field must be a number.',
            integer: 'This field must be an integer.',
            date: 'Please enter a valid date.',
            dateRange: 'End date must be after start date.',
            futureDate: 'Date cannot be in the future.',
            pastDate: 'Date cannot be in the past.',
            maxDaysOld: 'Date cannot be more than {days} days old.',
            pattern: 'This field format is invalid.',
            minSelected: 'Please select at least {min} option(s).',
            maxSelected: 'Please select no more than {max} option(s).',
            fileSize: 'File size must not exceed {size}MB.',
            fileType: 'Invalid file type. Allowed types: {types}.'
        };
    }

    /**
     * Bind validation events to forms
     */
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-validate forms with data-validate attribute
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => this.initializeForm(form));

            // Real-time validation on input
            document.addEventListener('input', (e) => {
                if (e.target.hasAttribute('data-validate')) {
                    this.validateField(e.target);
                }
            });

            // Validation on blur
            document.addEventListener('blur', (e) => {
                if (e.target.hasAttribute('data-validate')) {
                    this.validateField(e.target);
                }
            }, true);

            // Form submission validation
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.hasAttribute('data-validate')) {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            });
        });
    }

    /**
     * Initialize form for validation
     * @param {HTMLFormElement} form - Form element
     */
    initializeForm(form) {
        const fields = form.querySelectorAll('[data-validate]');
        fields.forEach(field => {
            this.setupField(field);
        });
    }

    /**
     * Setup individual field for validation
     * @param {HTMLElement} field - Form field element
     */
    setupField(field) {
        // Add ARIA attributes for accessibility
        if (!field.hasAttribute('aria-describedby')) {
            const errorId = `${field.id || field.name}-error`;
            field.setAttribute('aria-describedby', errorId);
        }

        // Create error message container if it doesn't exist
        this.createErrorContainer(field);
    }

    /**
     * Create error message container for a field
     * @param {HTMLElement} field - Form field element
     */
    createErrorContainer(field) {
        const errorId = field.getAttribute('aria-describedby');
        let errorContainer = document.getElementById(errorId);

        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = errorId;
            errorContainer.className = 'invalid-feedback';
            errorContainer.setAttribute('role', 'alert');
            errorContainer.setAttribute('aria-live', 'polite');

            // Insert after the field or its parent wrapper
            const wrapper = field.closest('.form-group, .mb-3, .col') || field.parentNode;
            wrapper.appendChild(errorContainer);
        }
    }

    /**
     * Validate entire form
     * @param {HTMLFormElement} form - Form element
     * @returns {boolean} - Validation result
     */
    validateForm(form) {
        const fields = form.querySelectorAll('[data-validate]');
        let isValid = true;
        let firstInvalidField = null;

        fields.forEach(field => {
            const fieldValid = this.validateField(field);
            if (!fieldValid && !firstInvalidField) {
                firstInvalidField = field;
            }
            isValid = isValid && fieldValid;
        });

        // Focus on first invalid field
        if (firstInvalidField) {
            firstInvalidField.focus();
            
            // Scroll to field if needed
            firstInvalidField.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        return isValid;
    }

    /**
     * Validate individual field
     * @param {HTMLElement} field - Form field element
     * @returns {boolean} - Validation result
     */
    validateField(field) {
        const rules = this.parseRules(field.getAttribute('data-validate'));
        const value = this.getFieldValue(field);
        const errors = [];

        // Skip validation if field is disabled or readonly
        if (field.disabled || field.readOnly) {
            this.clearFieldError(field);
            return true;
        }

        // Validate each rule
        for (const rule of rules) {
            const error = this.validateRule(value, rule, field);
            if (error) {
                errors.push(error);
                break; // Stop at first error
            }
        }

        // Display errors or clear them
        if (errors.length > 0) {
            this.showFieldError(field, errors[0]);
            return false;
        } else {
            this.clearFieldError(field);
            return true;
        }
    }

    /**
     * Parse validation rules from string
     * @param {string} rulesString - Rules string (e.g., "required|min:3|max:50")
     * @returns {Array} - Array of rule objects
     */
    parseRules(rulesString) {
        if (!rulesString) return [];

        return rulesString.split('|').map(rule => {
            const [name, ...params] = rule.split(':');
            return {
                name: name.trim(),
                params: params.length > 0 ? params.join(':').split(',').map(p => p.trim()) : []
            };
        });
    }

    /**
     * Get field value based on field type
     * @param {HTMLElement} field - Form field element
     * @returns {*} - Field value
     */
    getFieldValue(field) {
        switch (field.type) {
            case 'checkbox':
                return field.checked;
            case 'radio':
                const radioGroup = document.querySelectorAll(`input[name="${field.name}"]`);
                const checked = Array.from(radioGroup).find(radio => radio.checked);
                return checked ? checked.value : '';
            case 'file':
                return field.files;
            case 'select-multiple':
                return Array.from(field.selectedOptions).map(option => option.value);
            default:
                return field.value.trim();
        }
    }

    /**
     * Validate a single rule
     * @param {*} value - Field value
     * @param {Object} rule - Rule object
     * @param {HTMLElement} field - Form field element
     * @returns {string|null} - Error message or null if valid
     */
    validateRule(value, rule, field) {
        const { name, params } = rule;

        switch (name) {
            case 'required':
                return this.validateRequired(value, field);
            
            case 'min':
                return this.validateMin(value, parseInt(params[0]));
            
            case 'max':
                return this.validateMax(value, parseInt(params[0]));
            
            case 'minValue':
                return this.validateMinValue(value, parseFloat(params[0]));
            
            case 'maxValue':
                return this.validateMaxValue(value, parseFloat(params[0]));
            
            case 'numeric':
                return this.validateNumeric(value);
            
            case 'integer':
                return this.validateInteger(value);
            
            case 'email':
                return this.validateEmail(value);
            
            case 'date':
                return this.validateDate(value);
            
            case 'futureDate':
                return this.validateFutureDate(value);
            
            case 'pastDate':
                return this.validatePastDate(value);
            
            case 'maxDaysOld':
                return this.validateMaxDaysOld(value, parseInt(params[0]));
            
            case 'pattern':
                return this.validatePattern(value, params[0]);
            
            case 'minSelected':
                return this.validateMinSelected(value, parseInt(params[0]));
            
            case 'maxSelected':
                return this.validateMaxSelected(value, parseInt(params[0]));
            
            case 'fileSize':
                return this.validateFileSize(value, parseFloat(params[0]));
            
            case 'fileType':
                return this.validateFileType(value, params);
            
            default:
                return null;
        }
    }

    /**
     * Validation methods
     */
    validateRequired(value, field) {
        if (field.type === 'checkbox') {
            return value ? null : this.messages.required;
        }
        
        if (Array.isArray(value)) {
            return value.length > 0 ? null : this.messages.required;
        }
        
        if (value instanceof FileList) {
            return value.length > 0 ? null : this.messages.required;
        }
        
        return value !== '' ? null : this.messages.required;
    }

    validateMin(value, min) {
        if (value === '') return null;
        return value.length >= min ? null : this.messages.min.replace('{min}', min);
    }

    validateMax(value, max) {
        if (value === '') return null;
        return value.length <= max ? null : this.messages.max.replace('{max}', max);
    }

    validateMinValue(value, min) {
        if (value === '') return null;
        const num = parseFloat(value);
        return !isNaN(num) && num >= min ? null : this.messages.minValue.replace('{min}', min);
    }

    validateMaxValue(value, max) {
        if (value === '') return null;
        const num = parseFloat(value);
        return !isNaN(num) && num <= max ? null : this.messages.maxValue.replace('{max}', max);
    }

    validateNumeric(value) {
        if (value === '') return null;
        return !isNaN(parseFloat(value)) && isFinite(value) ? null : this.messages.numeric;
    }

    validateInteger(value) {
        if (value === '') return null;
        return Number.isInteger(parseFloat(value)) ? null : this.messages.integer;
    }

    validateEmail(value) {
        if (value === '') return null;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value) ? null : this.messages.email;
    }

    validateDate(value) {
        if (value === '') return null;
        const date = new Date(value);
        return !isNaN(date.getTime()) ? null : this.messages.date;
    }

    validateFutureDate(value) {
        if (value === '') return null;
        const date = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return date <= today ? null : this.messages.futureDate;
    }

    validatePastDate(value) {
        if (value === '') return null;
        const date = new Date(value);
        const today = new Date();
        today.setHours(23, 59, 59, 999);
        return date >= today ? null : this.messages.pastDate;
    }

    validateMaxDaysOld(value, maxDays) {
        if (value === '') return null;
        const date = new Date(value);
        const today = new Date();
        const diffTime = today - date;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays <= maxDays ? null : this.messages.maxDaysOld.replace('{days}', maxDays);
    }

    validatePattern(value, pattern) {
        if (value === '') return null;
        const regex = new RegExp(pattern);
        return regex.test(value) ? null : this.messages.pattern;
    }

    validateMinSelected(value, min) {
        if (!Array.isArray(value)) return null;
        return value.length >= min ? null : this.messages.minSelected.replace('{min}', min);
    }

    validateMaxSelected(value, max) {
        if (!Array.isArray(value)) return null;
        return value.length <= max ? null : this.messages.maxSelected.replace('{max}', max);
    }

    validateFileSize(files, maxSizeMB) {
        if (!files || files.length === 0) return null;
        
        for (let file of files) {
            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > maxSizeMB) {
                return this.messages.fileSize.replace('{size}', maxSizeMB);
            }
        }
        return null;
    }

    validateFileType(files, allowedTypes) {
        if (!files || files.length === 0) return null;
        
        for (let file of files) {
            const extension = file.name.split('.').pop().toLowerCase();
            if (!allowedTypes.includes(extension)) {
                return this.messages.fileType.replace('{types}', allowedTypes.join(', '));
            }
        }
        return null;
    }

    /**
     * Show field error
     * @param {HTMLElement} field - Form field element
     * @param {string} message - Error message
     */
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        
        const errorId = field.getAttribute('aria-describedby');
        const errorContainer = document.getElementById(errorId);
        
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * Clear field error
     * @param {HTMLElement} field - Form field element
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        field.setAttribute('aria-invalid', 'false');
        
        const errorId = field.getAttribute('aria-describedby');
        const errorContainer = document.getElementById(errorId);
        
        if (errorContainer) {
            errorContainer.textContent = '';
            errorContainer.style.display = 'none';
        }
    }

    /**
     * Add custom validation rule
     * @param {string} name - Rule name
     * @param {Function} validator - Validator function
     * @param {string} message - Error message
     */
    addRule(name, validator, message) {
        this.rules[name] = validator;
        this.messages[name] = message;
    }

    /**
     * Set custom message for a rule
     * @param {string} rule - Rule name
     * @param {string} message - Error message
     */
    setMessage(rule, message) {
        this.messages[rule] = message;
    }

    /**
     * Validate date range (start and end date fields)
     * @param {HTMLElement} startField - Start date field
     * @param {HTMLElement} endField - End date field
     * @returns {boolean} - Validation result
     */
    validateDateRange(startField, endField) {
        const startDate = new Date(startField.value);
        const endDate = new Date(endField.value);
        
        if (startField.value && endField.value && startDate >= endDate) {
            this.showFieldError(endField, this.messages.dateRange);
            return false;
        } else {
            this.clearFieldError(endField);
            return true;
        }
    }

    /**
     * Reset form validation state
     * @param {HTMLFormElement} form - Form element
     */
    resetForm(form) {
        const fields = form.querySelectorAll('[data-validate]');
        fields.forEach(field => {
            field.classList.remove('is-invalid', 'is-valid');
            field.removeAttribute('aria-invalid');
            this.clearFieldError(field);
        });
    }
}

// Create global instance
window.attendanceValidator = new AttendanceValidator();

// Utility functions for common validation scenarios
window.validateAttendanceForm = (form) => window.attendanceValidator.validateForm(form);
window.validateField = (field) => window.attendanceValidator.validateField(field);
window.validateDateRange = (startField, endField) => window.attendanceValidator.validateDateRange(startField, endField);