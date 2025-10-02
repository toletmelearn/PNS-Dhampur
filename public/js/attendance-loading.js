/**
 * Attendance System Loading States Manager
 * Handles all loading states and progress indicators for async operations
 */

class AttendanceLoading {
    constructor() {
        this.activeLoaders = new Set();
        this.progressBars = new Map();
        this.loadingOverlays = new Map();
        
        this.init();
    }
    
    init() {
        this.createGlobalLoader();
        this.setupProgressBars();
        this.setupSkeletonLoaders();
        this.setupButtonLoaders();
        this.setupTableLoaders();
        this.setupChartLoaders();
    }
    
    /**
     * Create global loading overlay
     */
    createGlobalLoader() {
        if (!document.getElementById('global-loader')) {
            const loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'global-loader';
            loader.innerHTML = `
                <div class="loader-backdrop"></div>
                <div class="loader-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="loader-text mt-3">Loading...</div>
                    <div class="progress mt-3" style="display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loader);
        }
    }
    
    /**
     * Show global loader
     */
    showGlobalLoader(text = 'Loading...', showProgress = false) {
        const loader = document.getElementById('global-loader');
        const textElement = loader.querySelector('.loader-text');
        const progressElement = loader.querySelector('.progress');
        
        textElement.textContent = text;
        progressElement.style.display = showProgress ? 'block' : 'none';
        
        loader.classList.add('active');
        document.body.classList.add('loading');
        
        this.activeLoaders.add('global');
        
        // Announce to screen readers
        this.announceLoading(text);
    }
    
    /**
     * Hide global loader
     */
    hideGlobalLoader() {
        const loader = document.getElementById('global-loader');
        loader.classList.remove('active');
        document.body.classList.remove('loading');
        
        this.activeLoaders.delete('global');
        
        // Announce completion to screen readers
        this.announceLoadingComplete();
    }
    
    /**
     * Update global loader progress
     */
    updateGlobalProgress(percent, text = null) {
        const loader = document.getElementById('global-loader');
        const progressBar = loader.querySelector('.progress-bar');
        const textElement = loader.querySelector('.loader-text');
        
        progressBar.style.width = `${percent}%`;
        progressBar.setAttribute('aria-valuenow', percent);
        
        if (text) {
            textElement.textContent = text;
        }
    }
    
    /**
     * Show loading overlay for specific element
     */
    showElementLoader(element, text = 'Loading...', type = 'spinner') {
        const elementId = element.id || `loader-${Date.now()}`;
        
        if (!element.id) {
            element.id = elementId;
        }
        
        // Remove existing loader
        this.hideElementLoader(element);
        
        const loader = document.createElement('div');
        loader.className = `element-loader ${type}-loader`;
        loader.setAttribute('data-loader-id', elementId);
        
        switch (type) {
            case 'spinner':
                loader.innerHTML = `
                    <div class="loader-backdrop"></div>
                    <div class="loader-content">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">${text}</span>
                        </div>
                        <div class="loader-text mt-2">${text}</div>
                    </div>
                `;
                break;
                
            case 'skeleton':
                loader.innerHTML = this.createSkeletonHTML(element);
                break;
                
            case 'progress':
                loader.innerHTML = `
                    <div class="loader-backdrop"></div>
                    <div class="loader-content">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="loader-text mt-2">${text}</div>
                    </div>
                `;
                break;
                
            case 'dots':
                loader.innerHTML = `
                    <div class="loader-backdrop"></div>
                    <div class="loader-content">
                        <div class="dots-loader">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                        <div class="loader-text mt-2">${text}</div>
                    </div>
                `;
                break;
        }
        
        // Position loader
        const rect = element.getBoundingClientRect();
        const isFixed = window.getComputedStyle(element).position === 'fixed';
        
        if (isFixed) {
            loader.style.position = 'fixed';
            loader.style.top = `${rect.top}px`;
            loader.style.left = `${rect.left}px`;
        } else {
            element.style.position = 'relative';
        }
        
        loader.style.width = `${rect.width}px`;
        loader.style.height = `${rect.height}px`;
        
        if (isFixed) {
            document.body.appendChild(loader);
        } else {
            element.appendChild(loader);
        }
        
        this.loadingOverlays.set(elementId, loader);
        this.activeLoaders.add(elementId);
        
        // Add loading class to element
        element.classList.add('loading');
        
        return elementId;
    }
    
    /**
     * Hide loading overlay for specific element
     */
    hideElementLoader(element) {
        const elementId = element.id;
        
        if (elementId && this.loadingOverlays.has(elementId)) {
            const loader = this.loadingOverlays.get(elementId);
            loader.remove();
            this.loadingOverlays.delete(elementId);
            this.activeLoaders.delete(elementId);
        }
        
        // Remove loading class
        element.classList.remove('loading');
        
        // Remove any existing loaders
        element.querySelectorAll('.element-loader').forEach(loader => {
            loader.remove();
        });
    }
    
    /**
     * Update element loader progress
     */
    updateElementProgress(element, percent, text = null) {
        const elementId = element.id;
        
        if (elementId && this.loadingOverlays.has(elementId)) {
            const loader = this.loadingOverlays.get(elementId);
            const progressBar = loader.querySelector('.progress-bar');
            const textElement = loader.querySelector('.loader-text');
            
            if (progressBar) {
                progressBar.style.width = `${percent}%`;
                progressBar.setAttribute('aria-valuenow', percent);
            }
            
            if (text && textElement) {
                textElement.textContent = text;
            }
        }
    }
    
    /**
     * Setup progress bars
     */
    setupProgressBars() {
        document.querySelectorAll('[data-progress-bar]').forEach(element => {
            this.initProgressBar(element);
        });
    }
    
    /**
     * Initialize progress bar
     */
    initProgressBar(element) {
        const progressBar = element.querySelector('.progress-bar');
        if (!progressBar) return;
        
        const id = element.id || `progress-${Date.now()}`;
        element.id = id;
        
        this.progressBars.set(id, {
            element: element,
            bar: progressBar,
            value: 0,
            max: 100
        });
    }
    
    /**
     * Update progress bar
     */
    updateProgressBar(id, value, text = null) {
        if (this.progressBars.has(id)) {
            const progress = this.progressBars.get(id);
            const percent = Math.min(100, Math.max(0, (value / progress.max) * 100));
            
            progress.bar.style.width = `${percent}%`;
            progress.bar.setAttribute('aria-valuenow', value);
            progress.value = value;
            
            if (text) {
                const textElement = progress.element.querySelector('.progress-text');
                if (textElement) {
                    textElement.textContent = text;
                }
            }
            
            // Add completion class when done
            if (percent >= 100) {
                progress.element.classList.add('complete');
                setTimeout(() => {
                    progress.element.classList.remove('complete');
                }, 2000);
            }
        }
    }
    
    /**
     * Setup skeleton loaders
     */
    setupSkeletonLoaders() {
        document.querySelectorAll('[data-skeleton]').forEach(element => {
            this.showSkeletonLoader(element);
        });
    }
    
    /**
     * Show skeleton loader
     */
    showSkeletonLoader(element) {
        const skeletonType = element.getAttribute('data-skeleton');
        const skeleton = this.createSkeletonElement(skeletonType);
        
        element.innerHTML = '';
        element.appendChild(skeleton);
        element.classList.add('skeleton-loading');
    }
    
    /**
     * Hide skeleton loader
     */
    hideSkeletonLoader(element, content = '') {
        element.classList.remove('skeleton-loading');
        
        if (content) {
            element.innerHTML = content;
        } else {
            element.querySelector('.skeleton')?.remove();
        }
    }
    
    /**
     * Create skeleton element
     */
    createSkeletonElement(type) {
        const skeleton = document.createElement('div');
        skeleton.className = 'skeleton';
        
        switch (type) {
            case 'text':
                skeleton.innerHTML = `
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line medium"></div>
                `;
                break;
                
            case 'card':
                skeleton.innerHTML = `
                    <div class="skeleton-header">
                        <div class="skeleton-avatar"></div>
                        <div class="skeleton-title">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div class="skeleton-content">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line medium"></div>
                    </div>
                `;
                break;
                
            case 'table':
                skeleton.innerHTML = `
                    <div class="skeleton-table">
                        ${Array.from({length: 5}, () => `
                            <div class="skeleton-row">
                                <div class="skeleton-cell"></div>
                                <div class="skeleton-cell"></div>
                                <div class="skeleton-cell"></div>
                                <div class="skeleton-cell"></div>
                            </div>
                        `).join('')}
                    </div>
                `;
                break;
                
            case 'chart':
                skeleton.innerHTML = `
                    <div class="skeleton-chart">
                        <div class="skeleton-chart-bars">
                            ${Array.from({length: 7}, (_, i) => `
                                <div class="skeleton-bar" style="height: ${20 + Math.random() * 60}%"></div>
                            `).join('')}
                        </div>
                        <div class="skeleton-chart-legend">
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                `;
                break;
                
            default:
                skeleton.innerHTML = '<div class="skeleton-line"></div>';
        }
        
        return skeleton;
    }
    
    /**
     * Setup button loaders
     */
    setupButtonLoaders() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-loading-button]')) {
                this.showButtonLoader(e.target);
            }
        });
    }
    
    /**
     * Show button loader
     */
    showButtonLoader(button, text = null) {
        if (button.classList.contains('loading')) return;
        
        const originalText = button.textContent;
        const loadingText = text || button.getAttribute('data-loading-text') || 'Loading...';
        
        button.classList.add('loading');
        button.disabled = true;
        
        // Store original content
        button.setAttribute('data-original-text', originalText);
        
        // Add spinner
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
            ${loadingText}
        `;
    }
    
    /**
     * Hide button loader
     */
    hideButtonLoader(button, newText = null) {
        if (!button.classList.contains('loading')) return;
        
        const originalText = button.getAttribute('data-original-text') || 'Submit';
        
        button.classList.remove('loading');
        button.disabled = false;
        button.textContent = newText || originalText;
        
        button.removeAttribute('data-original-text');
    }
    
    /**
     * Setup table loaders
     */
    setupTableLoaders() {
        document.querySelectorAll('table[data-loading]').forEach(table => {
            this.initTableLoader(table);
        });
    }
    
    /**
     * Show table loader
     */
    showTableLoader(table, rowCount = 5) {
        const tbody = table.querySelector('tbody');
        const columnCount = table.querySelectorAll('thead th').length || 4;
        
        tbody.innerHTML = '';
        
        for (let i = 0; i < rowCount; i++) {
            const row = document.createElement('tr');
            row.className = 'skeleton-row';
            
            for (let j = 0; j < columnCount; j++) {
                const cell = document.createElement('td');
                cell.innerHTML = '<div class="skeleton-line"></div>';
                row.appendChild(cell);
            }
            
            tbody.appendChild(row);
        }
        
        table.classList.add('loading');
    }
    
    /**
     * Hide table loader
     */
    hideTableLoader(table) {
        table.classList.remove('loading');
        table.querySelectorAll('.skeleton-row').forEach(row => row.remove());
    }
    
    /**
     * Setup chart loaders
     */
    setupChartLoaders() {
        document.querySelectorAll('[data-chart-loading]').forEach(element => {
            this.showChartLoader(element);
        });
    }
    
    /**
     * Show chart loader
     */
    showChartLoader(element) {
        const skeleton = this.createSkeletonElement('chart');
        element.innerHTML = '';
        element.appendChild(skeleton);
        element.classList.add('chart-loading');
    }
    
    /**
     * Hide chart loader
     */
    hideChartLoader(element) {
        element.classList.remove('chart-loading');
        element.querySelector('.skeleton')?.remove();
    }
    
    /**
     * Show loading for AJAX requests
     */
    showAjaxLoader(config = {}) {
        const {
            element = null,
            type = 'spinner',
            text = 'Loading...',
            showProgress = false,
            global = false
        } = config;
        
        if (global || !element) {
            this.showGlobalLoader(text, showProgress);
            return 'global';
        } else {
            return this.showElementLoader(element, text, type);
        }
    }
    
    /**
     * Hide loading for AJAX requests
     */
    hideAjaxLoader(loaderId) {
        if (loaderId === 'global') {
            this.hideGlobalLoader();
        } else {
            const element = document.getElementById(loaderId);
            if (element) {
                this.hideElementLoader(element);
            }
        }
    }
    
    /**
     * Handle form submission loading
     */
    handleFormLoading(form, options = {}) {
        const {
            showGlobal = false,
            buttonSelector = '[type="submit"]',
            loadingText = 'Processing...'
        } = options;
        
        const submitButton = form.querySelector(buttonSelector);
        
        if (showGlobal) {
            this.showGlobalLoader(loadingText);
        }
        
        if (submitButton) {
            this.showButtonLoader(submitButton, loadingText);
        }
        
        // Disable form inputs
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = true;
            input.classList.add('loading-disabled');
        });
        
        return {
            complete: () => {
                if (showGlobal) {
                    this.hideGlobalLoader();
                }
                
                if (submitButton) {
                    this.hideButtonLoader(submitButton);
                }
                
                // Re-enable form inputs
                inputs.forEach(input => {
                    input.disabled = false;
                    input.classList.remove('loading-disabled');
                });
            }
        };
    }
    
    /**
     * Announce loading to screen readers
     */
    announceLoading(text) {
        if (window.attendanceAccessibility) {
            window.attendanceAccessibility.announceToScreenReader(text);
        }
    }
    
    /**
     * Announce loading completion to screen readers
     */
    announceLoadingComplete() {
        if (window.attendanceAccessibility) {
            window.attendanceAccessibility.announceToScreenReader('Loading complete');
        }
    }
    
    /**
     * Check if any loaders are active
     */
    hasActiveLoaders() {
        return this.activeLoaders.size > 0;
    }
    
    /**
     * Get active loader count
     */
    getActiveLoaderCount() {
        return this.activeLoaders.size;
    }
    
    /**
     * Clear all loaders
     */
    clearAllLoaders() {
        // Hide global loader
        this.hideGlobalLoader();
        
        // Hide all element loaders
        this.loadingOverlays.forEach((loader, id) => {
            const element = document.getElementById(id);
            if (element) {
                this.hideElementLoader(element);
            }
        });
        
        // Clear all button loaders
        document.querySelectorAll('button.loading').forEach(button => {
            this.hideButtonLoader(button);
        });
        
        // Clear all table loaders
        document.querySelectorAll('table.loading').forEach(table => {
            this.hideTableLoader(table);
        });
        
        // Clear all chart loaders
        document.querySelectorAll('.chart-loading').forEach(element => {
            this.hideChartLoader(element);
        });
        
        this.activeLoaders.clear();
    }
}

// Initialize loading manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.attendanceLoading = new AttendanceLoading();
    });
} else {
    window.attendanceLoading = new AttendanceLoading();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AttendanceLoading;
}