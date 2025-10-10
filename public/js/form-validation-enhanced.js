/**
 * Enhanced Form Validation System
 * Provides comprehensive client-side validation with real-time feedback
 */

class FormValidationEnhanced {
    constructor() {
        this.init();
    }

    init() {
        // Initialize validation on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupValidation());
        } else {
            this.setupValidation();
        }
    }

    setupValidation() {
        // Add validation to all forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => this.enhanceForm(form));

        // Add real-time validation listeners
        this.addEventListeners();
    }

    enhanceForm(form) {
        // Add novalidate to prevent browser default validation
        form.setAttribute('novalidate', 'novalidate');

        // Add Bootstrap validation classes
        form.classList.add('needs-validation');

        // Enhance form fields
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => this.enhanceField(field));

        // Add form submit handler
        form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    enhanceField(field) {
        // Add validation feedback containers if not present
        if (!field.parentNode.querySelector('.invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }

        if (!field.parentNode.querySelector('.valid-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'valid-feedback';
            feedback.textContent = 'Looks good!';
            field.parentNode.appendChild(feedback);
        }

        // Add ARIA attributes
        field.setAttribute('aria-describedby', `${field.id || field.name}-feedback`);
    }

    addEventListeners() {
        // Real-time validation on input
        document.addEventListener('input', (e) => {
            if (this.isValidationTarget(e.target)) {
                this.validateField(e.target);
            }
        });

        // Validation on blur
        document.addEventListener('blur', (e) => {
            if (this.isValidationTarget(e.target)) {
                this.validateField(e.target);
            }
        }, true);

        // Password confirmation validation
        document.addEventListener('input', (e) => {
            if (e.target.name === 'password_confirmation' || e.target.id === 'confirmPassword') {
                this.validatePasswordConfirmation(e.target);
            }
        });
    }

    isValidationTarget(element) {
        return element.matches('input, select, textarea') && 
               !element.disabled && 
               !element.readOnly;
    }

    validateField(field) {
        const value = field.value.trim();
        const isValid = this.performValidation(field, value);

        this.updateFieldUI(field, isValid);
        return isValid;
    }

    performValidation(field, value) {
        // Check HTML5 validity first
        if (!field.checkValidity()) {
            this.setFieldError(field, field.validationMessage);
            return false;
        }

        // Custom validation rules
        const validationRules = [
            this.validateRequired,
            this.validateEmail,
            this.validatePhone,
            this.validateAadhaar,
            this.validatePassword,
            this.validateFileUpload
        ];

        for (const rule of validationRules) {
            const result = rule.call(this, field, value);
            if (result !== true) {
                this.setFieldError(field, result);
                return false;
            }
        }

        return true;
    }

    validateRequired(field, value) {
        if (field.hasAttribute('required') && !value) {
            return 'This field is required.';
        }
        return true;
    }

    validateEmail(field, value) {
        if (field.type === 'email' && value) {
            const emailRegex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
            if (!emailRegex.test(value)) {
                return 'Please enter a valid email address.';
            }
        }
        return true;
    }

    validatePhone(field, value) {
        if (field.type === 'tel' && value) {
            const phoneRegex = /^[+]?[0-9]{10,15}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                return 'Please enter a valid phone number (10-15 digits).';
            }
        }
        return true;
    }

    validateAadhaar(field, value) {
        if ((field.name === 'aadhaar' || field.id === 'aadhaar' || field.id === 'aadhaarNumber') && value) {
            const aadhaarRegex = /^[0-9]{12}$/;
            const cleanValue = value.replace(/\s/g, '');
            
            if (!aadhaarRegex.test(cleanValue)) {
                return 'Please enter a valid 12-digit Aadhaar number.';
            }

            // Verhoeff algorithm validation
            if (!this.verhoeffCheck(cleanValue)) {
                return 'Please enter a valid Aadhaar number.';
            }
        }
        return true;
    }

    validatePassword(field, value) {
        if (field.type === 'password' && field.name === 'password' && value) {
            if (value.length < 8) {
                return 'Password must be at least 8 characters long.';
            }

            const hasUppercase = /[A-Z]/.test(value);
            const hasLowercase = /[a-z]/.test(value);
            const hasNumbers = /\d/.test(value);
            const hasSpecialChar = /[@$!%*?&]/.test(value);

            if (!hasUppercase || !hasLowercase || !hasNumbers || !hasSpecialChar) {
                return 'Password must contain uppercase, lowercase, number and special character.';
            }
        }
        return true;
    }

    validateFileUpload(field, value) {
        if (field.type === 'file' && field.files.length > 0) {
            const file = field.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (file.size > maxSize) {
                return 'File size must be less than 2MB.';
            }

            if (field.accept) {
                const allowedTypes = field.accept.split(',').map(type => type.trim());
                const fileType = file.type;
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                const isAllowed = allowedTypes.some(type => {
                    if (type.startsWith('.')) {
                        return type === fileExtension;
                    }
                    return fileType.match(new RegExp(type.replace('*', '.*')));
                });

                if (!isAllowed) {
                    return 'Please select a valid file type.';
                }
            }
        }
        return true;
    }

    validatePasswordConfirmation(field) {
        const passwordField = document.querySelector('input[name="password"], #password');
        if (passwordField && field.value !== passwordField.value) {
            this.setFieldError(field, 'Passwords do not match.');
            this.updateFieldUI(field, false);
            return false;
        }
        
        this.updateFieldUI(field, true);
        return true;
    }

    verhoeffCheck(aadhaar) {
        // Verhoeff algorithm implementation for Aadhaar validation
        const d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];

        const p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];

        let c = 0;
        const myArray = aadhaar.split('').reverse();
        
        for (let i = 0; i < myArray.length; i++) {
            c = d[c][p[((i + 1) % 8)][parseInt(myArray[i])]];
        }
        
        return c === 0;
    }

    updateFieldUI(field, isValid) {
        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        field.setAttribute('aria-invalid', !isValid);
    }

    setFieldError(field, message) {
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
        }
    }

    handleFormSubmit(e) {
        const form = e.target;
        const fields = form.querySelectorAll('input, select, textarea');
        let isFormValid = true;
        let firstInvalidField = null;

        // Validate all fields
        fields.forEach(field => {
            if (this.isValidationTarget(field)) {
                const isValid = this.validateField(field);
                if (!isValid && !firstInvalidField) {
                    firstInvalidField = field;
                }
                isFormValid = isFormValid && isValid;
            }
        });

        // Prevent submission if invalid
        if (!isFormValid) {
            e.preventDefault();
            e.stopPropagation();

            // Focus first invalid field
            if (firstInvalidField) {
                firstInvalidField.focus();
                firstInvalidField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }

            // Show error alert
            this.showValidationAlert();
        }

        form.classList.add('was-validated');
    }

    showValidationAlert() {
        // Create or show validation alert
        let alert = document.querySelector('.validation-alert');
        
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'alert alert-danger validation-alert';
            alert.innerHTML = `
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Validation Error:</strong> Please correct the highlighted fields and try again.
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;
            
            // Insert at top of form or body
            const form = document.querySelector('form');
            if (form) {
                form.insertBefore(alert, form.firstChild);
            } else {
                document.body.insertBefore(alert, document.body.firstChild);
            }
        }

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Initialize enhanced form validation
new FormValidationEnhanced();

// Export for use in other scripts
window.FormValidationEnhanced = FormValidationEnhanced;