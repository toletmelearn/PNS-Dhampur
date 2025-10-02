/**
 * Comprehensive Notification System for Attendance Module
 * Provides toast notifications, SweetAlert2 integration, and loading states
 */

class AttendanceNotifications {
    constructor() {
        this.toastContainer = null;
        this.initializeToastContainer();
        this.initializeSweetAlert();
    }

    /**
     * Initialize toast container
     */
    initializeToastContainer() {
        // Create toast container if it doesn't exist
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
        }
        this.toastContainer = container;
    }

    /**
     * Initialize SweetAlert2 defaults
     */
    initializeSweetAlert() {
        if (typeof Swal !== 'undefined') {
            Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        }
    }

    /**
     * Show toast notification
     * @param {string} message - The message to display
     * @param {string} type - Type of toast (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds (default: 5000)
     */
    showToast(message, type = 'info', duration = 5000) {
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const colorMap = {
            success: 'text-success',
            error: 'text-danger',
            warning: 'text-warning',
            info: 'text-info'
        };

        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="${iconMap[type]} ${colorMap[type]} me-2"></i>
                    <strong class="me-auto">${this.capitalizeFirst(type)}</strong>
                    <small class="text-muted">just now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        this.toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: duration
        });

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        toast.show();
        return toast;
    }

    /**
     * Show success toast
     */
    success(message, duration = 5000) {
        return this.showToast(message, 'success', duration);
    }

    /**
     * Show error toast
     */
    error(message, duration = 7000) {
        return this.showToast(message, 'error', duration);
    }

    /**
     * Show warning toast
     */
    warning(message, duration = 6000) {
        return this.showToast(message, 'warning', duration);
    }

    /**
     * Show info toast
     */
    info(message, duration = 5000) {
        return this.showToast(message, 'info', duration);
    }

    /**
     * Show loading alert with progress
     * @param {string} title - Loading title
     * @param {string} text - Loading text
     * @returns {Promise} SweetAlert2 promise
     */
    showLoading(title = 'Loading...', text = 'Please wait while we process your request.') {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 not loaded');
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    /**
     * Show loading with progress bar
     * @param {string} title - Loading title
     * @param {number} progress - Progress percentage (0-100)
     */
    showLoadingProgress(title = 'Processing...', progress = 0) {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 not loaded');
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            html: `
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: ${progress}%"
                         aria-valuenow="${progress}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${progress}%
                    </div>
                </div>
                <p class="mb-0 text-muted">Please wait while we process your request...</p>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
    }

    /**
     * Update loading progress
     * @param {number} progress - Progress percentage (0-100)
     * @param {string} message - Optional message to update
     */
    updateProgress(progress, message = null) {
        const progressBar = document.querySelector('.swal2-html-container .progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
            progressBar.textContent = `${progress}%`;
        }

        if (message) {
            const messageElement = document.querySelector('.swal2-html-container p');
            if (messageElement) {
                messageElement.textContent = message;
            }
        }
    }

    /**
     * Show confirmation dialog
     * @param {string} title - Dialog title
     * @param {string} text - Dialog text
     * @param {string} confirmText - Confirm button text
     * @param {string} cancelText - Cancel button text
     * @param {string} icon - Icon type (warning, question, info)
     */
    confirm(title, text, confirmText = 'Yes', cancelText = 'Cancel', icon = 'warning') {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 not loaded');
            return Promise.resolve({ isConfirmed: false });
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true
        });
    }

    /**
     * Show success alert
     */
    successAlert(title, text = '', confirmText = 'OK') {
        if (typeof Swal === 'undefined') {
            this.success(title);
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: 'success',
            confirmButtonText: confirmText
        });
    }

    /**
     * Show error alert
     */
    errorAlert(title, text = '', confirmText = 'OK') {
        if (typeof Swal === 'undefined') {
            this.error(title);
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: 'error',
            confirmButtonText: confirmText
        });
    }

    /**
     * Show warning alert
     */
    warningAlert(title, text = '', confirmText = 'OK') {
        if (typeof Swal === 'undefined') {
            this.warning(title);
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            confirmButtonText: confirmText
        });
    }

    /**
     * Show info alert
     */
    infoAlert(title, text = '', confirmText = 'OK') {
        if (typeof Swal === 'undefined') {
            this.info(title);
            return Promise.resolve();
        }

        return Swal.fire({
            title: title,
            text: text,
            icon: 'info',
            confirmButtonText: confirmText
        });
    }

    /**
     * Close any open SweetAlert2 dialog
     */
    close() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    }

    /**
     * Show form validation errors
     * @param {Object} errors - Object containing field errors
     */
    showValidationErrors(errors) {
        let errorMessage = '<ul class="text-start mb-0">';
        
        Object.keys(errors).forEach(field => {
            if (Array.isArray(errors[field])) {
                errors[field].forEach(error => {
                    errorMessage += `<li>${error}</li>`;
                });
            } else {
                errorMessage += `<li>${errors[field]}</li>`;
            }
        });
        
        errorMessage += '</ul>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Validation Errors',
                html: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            this.error('Please fix the validation errors and try again.');
        }
    }

    /**
     * Show network error
     */
    showNetworkError() {
        const message = 'Network error occurred. Please check your internet connection and try again.';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Network Error',
                text: message,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Retry',
                cancelButtonText: 'Cancel'
            });
        } else {
            this.error(message);
        }
    }

    /**
     * Show server error
     */
    showServerError(statusCode = 500) {
        const message = `Server error (${statusCode}). Please try again later or contact support if the problem persists.`;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Server Error',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            this.error(message);
        }
    }

    /**
     * Utility function to capitalize first letter
     */
    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Add loading overlay to an element
     * @param {HTMLElement|string} element - Element or selector
     * @param {string} message - Loading message
     */
    addLoadingOverlay(element, message = 'Loading...') {
        const targetElement = typeof element === 'string' ? document.querySelector(element) : element;
        
        if (!targetElement) return;

        // Remove existing overlay
        this.removeLoadingOverlay(targetElement);

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="loading-spinner mb-2"></div>
                <div class="text-muted">${message}</div>
            </div>
        `;

        targetElement.style.position = 'relative';
        targetElement.appendChild(overlay);
    }

    /**
     * Remove loading overlay from an element
     * @param {HTMLElement|string} element - Element or selector
     */
    removeLoadingOverlay(element) {
        const targetElement = typeof element === 'string' ? document.querySelector(element) : element;
        
        if (!targetElement) return;

        const overlay = targetElement.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Show skeleton loading for tables
     * @param {HTMLElement|string} tableBody - Table body element or selector
     * @param {number} rows - Number of skeleton rows
     * @param {number} columns - Number of skeleton columns
     */
    showTableSkeleton(tableBody, rows = 5, columns = 4) {
        const tbody = typeof tableBody === 'string' ? document.querySelector(tableBody) : tableBody;
        
        if (!tbody) return;

        tbody.innerHTML = '';
        
        for (let i = 0; i < rows; i++) {
            const row = document.createElement('tr');
            
            for (let j = 0; j < columns; j++) {
                const cell = document.createElement('td');
                cell.innerHTML = '<div class="skeleton"></div>';
                row.appendChild(cell);
            }
            
            tbody.appendChild(row);
        }
    }
}

// Create global instance
window.attendanceNotifications = new AttendanceNotifications();

// Backward compatibility aliases
window.showToast = (message, type, duration) => window.attendanceNotifications.showToast(message, type, duration);
window.showLoading = (title, text) => window.attendanceNotifications.showLoading(title, text);
window.showSuccess = (title, text) => window.attendanceNotifications.successAlert(title, text);
window.showError = (title, text) => window.attendanceNotifications.errorAlert(title, text);
window.showConfirm = (title, text, confirmText, cancelText) => window.attendanceNotifications.confirm(title, text, confirmText, cancelText);