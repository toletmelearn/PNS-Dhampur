import './bootstrap';

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

// Add CSS for loading states
document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('#loading-states-css')) {
        const style = document.createElement('style');
        style.id = 'loading-states-css';
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