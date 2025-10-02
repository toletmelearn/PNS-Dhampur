/**
 * PNS Dhampur School Management System - Main JavaScript
 * Cross-browser compatibility and performance optimizations
 */

(function() {
    'use strict';

    // Polyfills for older browsers
    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector || 
                                  Element.prototype.webkitMatchesSelector;
    }

    if (!Element.prototype.closest) {
        Element.prototype.closest = function(s) {
            var el = this;
            do {
                if (Element.prototype.matches.call(el, s)) return el;
                el = el.parentElement || el.parentNode;
            } while (el !== null && el.nodeType === 1);
            return null;
        };
    }

    // Utility functions
    const Utils = {
        // Debounce function for performance
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        // Throttle function for performance
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // Safe DOM ready function
        ready: function(fn) {
            if (document.readyState !== 'loading') {
                fn();
            } else {
                document.addEventListener('DOMContentLoaded', fn);
            }
        },

        // Safe element selector
        $: function(selector, context) {
            context = context || document;
            return context.querySelector(selector);
        },

        // Safe multiple element selector
        $$: function(selector, context) {
            context = context || document;
            return Array.from(context.querySelectorAll(selector));
        },

        // Format number with commas
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        // Format currency
        formatCurrency: function(amount, currency = '₹') {
            return currency + ' ' + this.formatNumber(amount);
        },

        // Validate email
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        // Validate phone number
        isValidPhone: function(phone) {
            const re = /^[\+]?[1-9][\d]{0,15}$/;
            return re.test(phone.replace(/\s/g, ''));
        },

        // Show loading state
        showLoading: function(element) {
            if (element) {
                element.classList.add('loading');
                element.disabled = true;
            }
        },

        // Hide loading state
        hideLoading: function(element) {
            if (element) {
                element.classList.remove('loading');
                element.disabled = false;
            }
        },

        // Show toast notification
        showToast: function(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" aria-label="Close">&times;</button>
                </div>
            `;

            // Add toast styles if not already present
            if (!document.querySelector('#toast-styles')) {
                const styles = document.createElement('style');
                styles.id = 'toast-styles';
                styles.textContent = `
                    .toast {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 1060;
                        min-width: 300px;
                        padding: 1rem;
                        border-radius: 6px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        animation: slideInRight 0.3s ease-out;
                    }
                    .toast-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
                    .toast-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
                    .toast-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
                    .toast-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
                    .toast-content { display: flex; justify-content: space-between; align-items: center; }
                    .toast-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; opacity: 0.7; }
                    .toast-close:hover { opacity: 1; }
                    @keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
                `;
                document.head.appendChild(styles);
            }

            document.body.appendChild(toast);

            // Auto remove
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.animation = 'slideInRight 0.3s ease-out reverse';
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);

            // Manual close
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => {
                toast.style.animation = 'slideInRight 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            });
        }
    };

    // Modal functionality
    const Modal = {
        init: function() {
            // Modal triggers
            Utils.$$('[data-toggle="modal"]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = trigger.getAttribute('data-target');
                    this.show(target);
                });
            });

            // Modal close buttons
            Utils.$$('.modal .close, [data-dismiss="modal"]').forEach(closeBtn => {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modal = closeBtn.closest('.modal');
                    this.hide(modal);
                });
            });

            // Close modal on backdrop click
            Utils.$$('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.hide(modal);
                    }
                });
            });

            // Close modal on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const openModal = Utils.$('.modal.show');
                    if (openModal) {
                        this.hide(openModal);
                    }
                }
            });
        },

        show: function(modalSelector) {
            const modal = typeof modalSelector === 'string' ? Utils.$(modalSelector) : modalSelector;
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Focus management for accessibility
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                }
            }
        },

        hide: function(modal) {
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
    };

    // Form validation
    const FormValidator = {
        init: function() {
            Utils.$$('form[data-validate]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                    }
                });

                // Real-time validation
                Utils.$$(form, 'input, select, textarea').forEach(field => {
                    field.addEventListener('blur', () => {
                        this.validateField(field);
                    });
                });
            });
        },

        validateForm: function(form) {
            let isValid = true;
            const fields = Utils.$$(form, 'input[required], select[required], textarea[required]');
            
            fields.forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        validateField: function(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            let message = '';

            // Remove existing error
            this.clearFieldError(field);

            // Required validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'This field is required';
            }
            // Email validation
            else if (type === 'email' && value && !Utils.isValidEmail(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
            // Phone validation
            else if (type === 'tel' && value && !Utils.isValidPhone(value)) {
                isValid = false;
                message = 'Please enter a valid phone number';
            }
            // Min length validation
            else if (field.hasAttribute('minlength')) {
                const minLength = parseInt(field.getAttribute('minlength'));
                if (value.length < minLength) {
                    isValid = false;
                    message = `Minimum ${minLength} characters required`;
                }
            }

            if (!isValid) {
                this.showFieldError(field, message);
            }

            return isValid;
        },

        showFieldError: function(field, message) {
            field.classList.add('is-invalid');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            
            field.parentNode.appendChild(errorDiv);
        },

        clearFieldError: function(field) {
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    };

    // DataTable enhancement
    const DataTableEnhancer = {
        init: function() {
            Utils.$$('table[data-enhance]').forEach(table => {
                this.enhanceTable(table);
            });
        },

        enhanceTable: function(table) {
            // Add search functionality
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'form-control mb-3';
            searchInput.placeholder = 'Search...';
            
            table.parentNode.insertBefore(searchInput, table);

            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.filterTable(table, e.target.value);
            }, 300));

            // Add sorting functionality
            const headers = Utils.$$(table, 'th[data-sortable]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header);
                });
            });
        },

        filterTable: function(table, searchTerm) {
            const rows = Utils.$$(table, 'tbody tr');
            const term = searchTerm.toLowerCase();

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        },

        sortTable: function(table, header) {
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const rows = Array.from(Utils.$$(table, 'tbody tr'));
            const isAscending = !header.classList.contains('sort-asc');

            // Clear all sort classes
            Utils.$$(table, 'th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
            });

            // Add sort class to current header
            header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');

            // Sort rows
            rows.sort((a, b) => {
                const aText = a.children[columnIndex].textContent.trim();
                const bText = b.children[columnIndex].textContent.trim();
                
                // Try to parse as numbers
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                } else {
                    return isAscending ? 
                        aText.localeCompare(bText) : 
                        bText.localeCompare(aText);
                }
            });

            // Reorder rows in DOM
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        }
    };

    // Counter animation
    const CounterAnimation = {
        init: function() {
            const counters = Utils.$$('[data-counter]');
            
            if (counters.length > 0) {
                // Use Intersection Observer for performance
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.animateCounter(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                });

                counters.forEach(counter => observer.observe(counter));
            }
        },

        animateCounter: function(element) {
            const target = parseInt(element.getAttribute('data-counter'));
            const duration = parseInt(element.getAttribute('data-duration')) || 2000;
            const start = 0;
            const increment = target / (duration / 16); // 60fps
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        }
    };

    // AJAX helper
    const Ajax = {
        request: function(options) {
            const defaults = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                timeout: 10000
            };

            const config = Object.assign({}, defaults, options);

            // Add CSRF token if available
            const csrfToken = Utils.$('meta[name="csrf-token"]');
            if (csrfToken) {
                config.headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                xhr.timeout = config.timeout;
                xhr.ontimeout = () => reject(new Error('Request timeout'));
                xhr.onerror = () => reject(new Error('Network error'));
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                resolve(response);
                            } catch (e) {
                                resolve(xhr.responseText);
                            }
                        } else {
                            reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                        }
                    }
                };

                xhr.open(config.method, config.url);
                
                // Set headers
                Object.keys(config.headers).forEach(key => {
                    xhr.setRequestHeader(key, config.headers[key]);
                });

                // Send request
                if (config.data) {
                    xhr.send(typeof config.data === 'string' ? config.data : JSON.stringify(config.data));
                } else {
                    xhr.send();
                }
            });
        },

        get: function(url, options = {}) {
            return this.request(Object.assign({ method: 'GET', url }, options));
        },

        post: function(url, data, options = {}) {
            return this.request(Object.assign({ method: 'POST', url, data }, options));
        },

        put: function(url, data, options = {}) {
            return this.request(Object.assign({ method: 'PUT', url, data }, options));
        },

        delete: function(url, options = {}) {
            return this.request(Object.assign({ method: 'DELETE', url }, options));
        }
    };

    // Performance monitoring
    const Performance = {
        init: function() {
            // Monitor page load time
            window.addEventListener('load', () => {
                if (window.performance && window.performance.timing) {
                    const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                    console.log(`Page load time: ${loadTime}ms`);
                }
            });

            // Monitor long tasks (if supported)
            if ('PerformanceObserver' in window) {
                try {
                    const observer = new PerformanceObserver((list) => {
                        list.getEntries().forEach((entry) => {
                            if (entry.duration > 50) {
                                console.warn(`Long task detected: ${entry.duration}ms`);
                            }
                        });
                    });
                    observer.observe({ entryTypes: ['longtask'] });
                } catch (e) {
                    // PerformanceObserver not fully supported
                }
            }
        }
    };

    // Accessibility enhancements
    const Accessibility = {
        init: function() {
            // Skip to main content
            this.addSkipLink();
            
            // Keyboard navigation for custom elements
            this.enhanceKeyboardNavigation();
            
            // ARIA live regions for dynamic content
            this.setupLiveRegions();
        },

        addSkipLink: function() {
            if (!Utils.$('.skip-to-main')) {
                const skipLink = document.createElement('a');
                skipLink.href = '#main-content';
                skipLink.className = 'skip-to-main';
                skipLink.textContent = 'Skip to main content';
                document.body.insertBefore(skipLink, document.body.firstChild);
            }
        },

        enhanceKeyboardNavigation: function() {
            // Custom dropdown keyboard support
            Utils.$$('[data-toggle="dropdown"]').forEach(trigger => {
                trigger.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        trigger.click();
                    }
                });
            });

            // Tab trap for modals
            Utils.$$('.modal').forEach(modal => {
                modal.addEventListener('keydown', (e) => {
                    if (e.key === 'Tab') {
                        const focusableElements = modal.querySelectorAll(
                            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                        );
                        const firstElement = focusableElements[0];
                        const lastElement = focusableElements[focusableElements.length - 1];

                        if (e.shiftKey && document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        } else if (!e.shiftKey && document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                });
            });
        },

        setupLiveRegions: function() {
            // Create live region for announcements
            if (!Utils.$('#live-region')) {
                const liveRegion = document.createElement('div');
                liveRegion.id = 'live-region';
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.style.position = 'absolute';
                liveRegion.style.left = '-10000px';
                liveRegion.style.width = '1px';
                liveRegion.style.height = '1px';
                liveRegion.style.overflow = 'hidden';
                document.body.appendChild(liveRegion);
            }
        },

        announce: function(message) {
            const liveRegion = Utils.$('#live-region');
            if (liveRegion) {
                liveRegion.textContent = message;
            }
        }
    };

    // Initialize everything when DOM is ready
    Utils.ready(() => {
        Modal.init();
        FormValidator.init();
        DataTableEnhancer.init();
        CounterAnimation.init();
        Performance.init();
        Accessibility.init();

        // Global error handler
        window.addEventListener('error', (e) => {
            console.error('JavaScript error:', e.error);
            Utils.showToast('An error occurred. Please try again.', 'error');
        });

        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
            Utils.showToast('An error occurred. Please try again.', 'error');
        });

        console.log('PNS Dhampur School Management System initialized successfully');
    });

    // Expose utilities globally
    window.PNS = {
        Utils,
        Modal,
        FormValidator,
        Ajax,
        Accessibility
    };

})();