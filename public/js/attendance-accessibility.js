/**
 * Attendance System Accessibility Enhancements
 * Provides keyboard navigation, screen reader support, and ARIA attributes
 */

class AttendanceAccessibility {
    constructor() {
        this.focusableElements = [
            'button',
            'input',
            'select',
            'textarea',
            'a[href]',
            '[tabindex]:not([tabindex="-1"])'
        ].join(',');
        
        this.init();
    }
    
    init() {
        this.setupKeyboardNavigation();
        this.setupAriaLabels();
        this.setupFocusManagement();
        this.setupScreenReaderAnnouncements();
        this.setupSkipLinks();
        this.setupHighContrastMode();
    }
    
    /**
     * Setup keyboard navigation for attendance controls
     */
    setupKeyboardNavigation() {
        // Handle arrow key navigation in attendance grids
        document.addEventListener('keydown', (e) => {
            if (e.target.closest('.attendance-grid, .student-list')) {
                this.handleGridNavigation(e);
            }
            
            // Handle escape key for modals and dropdowns
            if (e.key === 'Escape') {
                this.handleEscapeKey(e);
            }
            
            // Handle enter/space for custom buttons
            if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('attendance-btn')) {
                e.preventDefault();
                e.target.click();
            }
        });
        
        // Setup tab trapping for modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    this.trapFocus(e, modal);
                }
            }
        });
    }
    
    /**
     * Handle arrow key navigation in grids
     */
    handleGridNavigation(e) {
        const currentElement = e.target;
        const grid = currentElement.closest('.attendance-grid, .student-list');
        const focusableElements = Array.from(grid.querySelectorAll(this.focusableElements));
        const currentIndex = focusableElements.indexOf(currentElement);
        
        let nextIndex = currentIndex;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                nextIndex = Math.min(currentIndex + 1, focusableElements.length - 1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                nextIndex = Math.max(currentIndex - 1, 0);
                break;
            case 'Home':
                e.preventDefault();
                nextIndex = 0;
                break;
            case 'End':
                e.preventDefault();
                nextIndex = focusableElements.length - 1;
                break;
        }
        
        if (nextIndex !== currentIndex && focusableElements[nextIndex]) {
            focusableElements[nextIndex].focus();
        }
    }
    
    /**
     * Handle escape key for closing modals and dropdowns
     */
    handleEscapeKey(e) {
        // Close open modals
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const closeButton = openModal.querySelector('[data-bs-dismiss="modal"]');
            if (closeButton) {
                closeButton.click();
            }
        }
        
        // Close open dropdowns
        const openDropdown = document.querySelector('.dropdown-menu.show');
        if (openDropdown) {
            const dropdownToggle = document.querySelector('[data-bs-toggle="dropdown"][aria-expanded="true"]');
            if (dropdownToggle) {
                dropdownToggle.click();
            }
        }
        
        // Close SweetAlert if open
        if (window.Swal && Swal.isVisible()) {
            Swal.close();
        }
    }
    
    /**
     * Trap focus within modals
     */
    trapFocus(e, modal) {
        const focusableElements = Array.from(modal.querySelectorAll(this.focusableElements));
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
    
    /**
     * Setup ARIA labels and descriptions
     */
    setupAriaLabels() {
        // Add ARIA labels to attendance buttons
        document.querySelectorAll('.attendance-btn').forEach(btn => {
            const status = btn.textContent.trim().toLowerCase();
            const studentName = btn.closest('.student-row, .student-card-mobile')?.querySelector('.student-name')?.textContent;
            
            if (studentName) {
                btn.setAttribute('aria-label', `Mark ${studentName} as ${status}`);
            }
        });
        
        // Add ARIA labels to form controls
        document.querySelectorAll('input, select, textarea').forEach(input => {
            if (!input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    input.setAttribute('aria-labelledby', label.id || this.generateId('label'));
                    if (!label.id) {
                        label.id = input.getAttribute('aria-labelledby');
                    }
                }
            }
        });
        
        // Add ARIA descriptions to complex controls
        document.querySelectorAll('.late-minutes-input').forEach(input => {
            input.setAttribute('aria-describedby', 'late-minutes-help');
            
            if (!document.getElementById('late-minutes-help')) {
                const helpText = document.createElement('div');
                helpText.id = 'late-minutes-help';
                helpText.className = 'sr-only';
                helpText.textContent = 'Enter the number of minutes the student was late (0-300)';
                input.parentNode.appendChild(helpText);
            }
        });
        
        // Add ARIA labels to charts
        document.querySelectorAll('canvas[role="img"]').forEach(canvas => {
            if (!canvas.getAttribute('aria-label')) {
                const chartTitle = canvas.closest('.chart-container')?.querySelector('.chart-title')?.textContent;
                if (chartTitle) {
                    canvas.setAttribute('aria-label', `Chart: ${chartTitle}`);
                }
            }
        });
    }
    
    /**
     * Setup focus management
     */
    setupFocusManagement() {
        // Store focus before opening modals
        document.addEventListener('show.bs.modal', (e) => {
            this.previousFocus = document.activeElement;
        });
        
        // Restore focus after closing modals
        document.addEventListener('hidden.bs.modal', (e) => {
            if (this.previousFocus && this.previousFocus.focus) {
                setTimeout(() => {
                    this.previousFocus.focus();
                }, 100);
            }
        });
        
        // Focus first element when modal opens
        document.addEventListener('shown.bs.modal', (e) => {
            const modal = e.target;
            const firstFocusable = modal.querySelector(this.focusableElements);
            if (firstFocusable) {
                firstFocusable.focus();
            }
        });
        
        // Manage focus for dynamic content
        this.setupDynamicFocusManagement();
    }
    
    /**
     * Setup focus management for dynamically added content
     */
    setupDynamicFocusManagement() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Add ARIA labels to new attendance buttons
                        const newButtons = node.querySelectorAll?.('.attendance-btn') || [];
                        newButtons.forEach(btn => {
                            const status = btn.textContent.trim().toLowerCase();
                            const studentName = btn.closest('.student-row, .student-card-mobile')?.querySelector('.student-name')?.textContent;
                            
                            if (studentName) {
                                btn.setAttribute('aria-label', `Mark ${studentName} as ${status}`);
                            }
                        });
                        
                        // Setup keyboard navigation for new elements
                        if (node.classList?.contains('student-row') || node.classList?.contains('student-card-mobile')) {
                            this.setupRowKeyboardNavigation(node);
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Setup keyboard navigation for individual student rows
     */
    setupRowKeyboardNavigation(row) {
        const buttons = row.querySelectorAll('.attendance-btn');
        
        buttons.forEach((btn, index) => {
            btn.addEventListener('keydown', (e) => {
                let nextIndex = index;
                
                switch (e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        nextIndex = Math.max(index - 1, 0);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        nextIndex = Math.min(index + 1, buttons.length - 1);
                        break;
                }
                
                if (nextIndex !== index) {
                    buttons[nextIndex].focus();
                }
            });
        });
    }
    
    /**
     * Setup screen reader announcements
     */
    setupScreenReaderAnnouncements() {
        // Create live region for announcements
        if (!document.getElementById('sr-live-region')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'sr-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
        
        // Create assertive live region for urgent announcements
        if (!document.getElementById('sr-live-region-assertive')) {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'sr-live-region-assertive';
            liveRegion.setAttribute('aria-live', 'assertive');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
    }
    
    /**
     * Announce message to screen readers
     */
    announce(message, urgent = false) {
        const regionId = urgent ? 'sr-live-region-assertive' : 'sr-live-region';
        const liveRegion = document.getElementById(regionId);
        
        if (liveRegion) {
            liveRegion.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    }
    
    /**
     * Setup skip links for keyboard navigation
     */
    setupSkipLinks() {
        if (!document.querySelector('.skip-links')) {
            const skipLinks = document.createElement('div');
            skipLinks.className = 'skip-links';
            skipLinks.innerHTML = `
                <a href="#main-content" class="skip-link">Skip to main content</a>
                <a href="#navigation" class="skip-link">Skip to navigation</a>
            `;
            
            // Add CSS for skip links
            const style = document.createElement('style');
            style.textContent = `
                .skip-links {
                    position: absolute;
                    top: -40px;
                    left: 6px;
                    z-index: 1000;
                }
                
                .skip-link {
                    position: absolute;
                    top: -40px;
                    left: 6px;
                    background: #000;
                    color: #fff;
                    padding: 8px;
                    text-decoration: none;
                    border-radius: 4px;
                    z-index: 1001;
                }
                
                .skip-link:focus {
                    top: 6px;
                }
            `;
            document.head.appendChild(style);
            document.body.insertBefore(skipLinks, document.body.firstChild);
        }
        
        // Add main content landmark if not exists
        if (!document.getElementById('main-content')) {
            const mainContent = document.querySelector('main, .main-content, .container-fluid');
            if (mainContent) {
                mainContent.id = 'main-content';
            }
        }
    }
    
    /**
     * Setup high contrast mode detection and support
     */
    setupHighContrastMode() {
        // Detect high contrast mode
        const isHighContrast = window.matchMedia('(prefers-contrast: high)').matches ||
                              window.matchMedia('(-ms-high-contrast: active)').matches;
        
        if (isHighContrast) {
            document.body.classList.add('high-contrast');
        }
        
        // Listen for changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
                if (e.matches) {
                    document.body.classList.add('high-contrast');
                } else {
                    document.body.classList.remove('high-contrast');
                }
            });
        }
    }
    
    /**
     * Generate unique ID
     */
    generateId(prefix = 'id') {
        return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
    }
    
    /**
     * Update attendance status with accessibility announcement
     */
    updateAttendanceStatus(studentName, status, announce = true) {
        if (announce) {
            this.announce(`${studentName} marked as ${status}`);
        }
    }
    
    /**
     * Announce loading state changes
     */
    announceLoadingState(isLoading, message = '') {
        if (isLoading) {
            this.announce(`Loading ${message}`, true);
        } else {
            this.announce(`Finished loading ${message}`);
        }
    }
    
    /**
     * Announce form validation errors
     */
    announceValidationErrors(errors) {
        if (errors.length > 0) {
            const message = `Form has ${errors.length} error${errors.length > 1 ? 's' : ''}: ${errors.join(', ')}`;
            this.announce(message, true);
        }
    }
    
    /**
     * Setup accessible tooltips
     */
    setupAccessibleTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            element.setAttribute('aria-describedby', element.getAttribute('data-bs-target') || this.generateId('tooltip'));
        });
    }
    
    /**
     * Make tables more accessible
     */
    enhanceTableAccessibility() {
        document.querySelectorAll('table').forEach(table => {
            // Add table caption if missing
            if (!table.querySelector('caption')) {
                const caption = document.createElement('caption');
                caption.className = 'sr-only';
                caption.textContent = 'Attendance data table';
                table.insertBefore(caption, table.firstChild);
            }
            
            // Add scope attributes to headers
            table.querySelectorAll('th').forEach(th => {
                if (!th.getAttribute('scope')) {
                    th.setAttribute('scope', 'col');
                }
            });
            
            // Add row headers
            table.querySelectorAll('tbody tr').forEach(row => {
                const firstCell = row.querySelector('td');
                if (firstCell && !firstCell.getAttribute('scope')) {
                    firstCell.setAttribute('scope', 'row');
                }
            });
        });
    }
}

// Initialize accessibility enhancements when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.attendanceAccessibility = new AttendanceAccessibility();
    });
} else {
    window.attendanceAccessibility = new AttendanceAccessibility();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AttendanceAccessibility;
}