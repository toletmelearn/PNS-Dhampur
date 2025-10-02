/**
 * Attendance System Performance Optimizations
 * Provides lazy loading, debouncing, caching, and efficient data handling
 */

class AttendancePerformance {
    constructor() {
        this.cache = new Map();
        this.debounceTimers = new Map();
        this.intersectionObserver = null;
        this.lazyElements = new Set();
        
        this.init();
    }
    
    init() {
        this.setupLazyLoading();
        this.setupDebouncing();
        this.setupCaching();
        this.setupVirtualScrolling();
        this.setupImageOptimization();
        this.setupMemoryManagement();
    }
    
    /**
     * Setup lazy loading for charts, images, and heavy content
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            this.intersectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadElement(entry.target);
                        this.intersectionObserver.unobserve(entry.target);
                        this.lazyElements.delete(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });
            
            // Observe lazy elements
            this.observeLazyElements();
        } else {
            // Fallback for browsers without IntersectionObserver
            this.loadAllElements();
        }
    }
    
    /**
     * Observe elements that should be lazy loaded
     */
    observeLazyElements() {
        // Charts
        document.querySelectorAll('[data-lazy-chart]').forEach(element => {
            this.lazyElements.add(element);
            this.intersectionObserver.observe(element);
        });
        
        // Images
        document.querySelectorAll('img[data-src]').forEach(element => {
            this.lazyElements.add(element);
            this.intersectionObserver.observe(element);
        });
        
        // Heavy content sections
        document.querySelectorAll('[data-lazy-content]').forEach(element => {
            this.lazyElements.add(element);
            this.intersectionObserver.observe(element);
        });
    }
    
    /**
     * Load a lazy element
     */
    loadElement(element) {
        if (element.hasAttribute('data-lazy-chart')) {
            this.loadChart(element);
        } else if (element.hasAttribute('data-src')) {
            this.loadImage(element);
        } else if (element.hasAttribute('data-lazy-content')) {
            this.loadContent(element);
        }
    }
    
    /**
     * Load chart lazily
     */
    loadChart(element) {
        const chartType = element.getAttribute('data-chart-type');
        const chartData = element.getAttribute('data-chart-data');
        
        if (chartType && chartData) {
            try {
                const data = JSON.parse(chartData);
                this.createChart(element, chartType, data);
            } catch (error) {
                console.error('Error loading chart:', error);
            }
        }
    }
    
    /**
     * Load image lazily
     */
    loadImage(element) {
        const src = element.getAttribute('data-src');
        if (src) {
            element.src = src;
            element.removeAttribute('data-src');
            element.classList.add('loaded');
        }
    }
    
    /**
     * Load content lazily
     */
    loadContent(element) {
        const contentUrl = element.getAttribute('data-content-url');
        if (contentUrl) {
            this.fetchContent(contentUrl)
                .then(content => {
                    element.innerHTML = content;
                    element.removeAttribute('data-lazy-content');
                })
                .catch(error => {
                    console.error('Error loading content:', error);
                });
        }
    }
    
    /**
     * Setup debouncing for input events
     */
    setupDebouncing() {
        // Debounce search inputs
        document.addEventListener('input', (e) => {
            if (e.target.matches('[data-debounce]')) {
                const delay = parseInt(e.target.getAttribute('data-debounce')) || 300;
                const key = e.target.id || e.target.name || 'default';
                
                this.debounce(key, () => {
                    this.handleDebouncedInput(e.target);
                }, delay);
            }
        });
        
        // Debounce window resize
        window.addEventListener('resize', () => {
            this.debounce('window-resize', () => {
                this.handleWindowResize();
            }, 250);
        });
        
        // Debounce scroll events
        window.addEventListener('scroll', () => {
            this.debounce('window-scroll', () => {
                this.handleScroll();
            }, 100);
        });
    }
    
    /**
     * Debounce function execution
     */
    debounce(key, func, delay) {
        if (this.debounceTimers.has(key)) {
            clearTimeout(this.debounceTimers.get(key));
        }
        
        const timer = setTimeout(() => {
            func();
            this.debounceTimers.delete(key);
        }, delay);
        
        this.debounceTimers.set(key, timer);
    }
    
    /**
     * Handle debounced input events
     */
    handleDebouncedInput(input) {
        const action = input.getAttribute('data-action');
        
        switch (action) {
            case 'search-students':
                this.searchStudents(input.value);
                break;
            case 'filter-attendance':
                this.filterAttendance(input.value);
                break;
            case 'validate-field':
                this.validateField(input);
                break;
        }
    }
    
    /**
     * Setup caching system
     */
    setupCaching() {
        // Cache API responses
        this.apiCache = new Map();
        this.cacheExpiry = new Map();
        
        // Cache DOM queries
        this.domCache = new Map();
        
        // Setup cache cleanup
        setInterval(() => {
            this.cleanupCache();
        }, 5 * 60 * 1000); // Clean every 5 minutes
    }
    
    /**
     * Cache API response
     */
    cacheApiResponse(key, data, ttl = 5 * 60 * 1000) {
        this.apiCache.set(key, data);
        this.cacheExpiry.set(key, Date.now() + ttl);
    }
    
    /**
     * Get cached API response
     */
    getCachedApiResponse(key) {
        if (this.apiCache.has(key)) {
            const expiry = this.cacheExpiry.get(key);
            if (Date.now() < expiry) {
                return this.apiCache.get(key);
            } else {
                this.apiCache.delete(key);
                this.cacheExpiry.delete(key);
            }
        }
        return null;
    }
    
    /**
     * Cache DOM element
     */
    cacheDomElement(selector) {
        if (!this.domCache.has(selector)) {
            this.domCache.set(selector, document.querySelector(selector));
        }
        return this.domCache.get(selector);
    }
    
    /**
     * Cleanup expired cache entries
     */
    cleanupCache() {
        const now = Date.now();
        
        for (const [key, expiry] of this.cacheExpiry.entries()) {
            if (now >= expiry) {
                this.apiCache.delete(key);
                this.cacheExpiry.delete(key);
            }
        }
    }
    
    /**
     * Setup virtual scrolling for large lists
     */
    setupVirtualScrolling() {
        document.querySelectorAll('[data-virtual-scroll]').forEach(container => {
            this.initVirtualScroll(container);
        });
    }
    
    /**
     * Initialize virtual scrolling for a container
     */
    initVirtualScroll(container) {
        const itemHeight = parseInt(container.getAttribute('data-item-height')) || 50;
        const bufferSize = parseInt(container.getAttribute('data-buffer-size')) || 5;
        
        let allItems = [];
        let visibleItems = [];
        let startIndex = 0;
        let endIndex = 0;
        
        const viewport = container.querySelector('.virtual-scroll-viewport') || container;
        const content = container.querySelector('.virtual-scroll-content') || container;
        
        const updateVisibleItems = () => {
            const scrollTop = viewport.scrollTop;
            const viewportHeight = viewport.clientHeight;
            
            startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - bufferSize);
            endIndex = Math.min(allItems.length, Math.ceil((scrollTop + viewportHeight) / itemHeight) + bufferSize);
            
            visibleItems = allItems.slice(startIndex, endIndex);
            
            // Update content
            content.style.height = `${allItems.length * itemHeight}px`;
            content.style.paddingTop = `${startIndex * itemHeight}px`;
            
            // Render visible items
            this.renderVirtualItems(content, visibleItems, startIndex);
        };
        
        viewport.addEventListener('scroll', () => {
            this.debounce(`virtual-scroll-${container.id}`, updateVisibleItems, 16);
        });
        
        // Store reference for external updates
        container._virtualScroll = {
            setItems: (items) => {
                allItems = items;
                updateVisibleItems();
            },
            refresh: updateVisibleItems
        };
    }
    
    /**
     * Render virtual scroll items
     */
    renderVirtualItems(container, items, startIndex) {
        const fragment = document.createDocumentFragment();
        
        items.forEach((item, index) => {
            const element = this.createVirtualItem(item, startIndex + index);
            fragment.appendChild(element);
        });
        
        // Clear and append new items
        const existingItems = container.querySelectorAll('.virtual-item');
        existingItems.forEach(item => item.remove());
        
        container.appendChild(fragment);
    }
    
    /**
     * Create virtual scroll item element
     */
    createVirtualItem(data, index) {
        const element = document.createElement('div');
        element.className = 'virtual-item';
        element.setAttribute('data-index', index);
        
        // Customize based on data type
        if (data.type === 'student') {
            element.innerHTML = this.createStudentItemHTML(data);
        } else {
            element.textContent = data.text || data.toString();
        }
        
        return element;
    }
    
    /**
     * Setup image optimization
     */
    setupImageOptimization() {
        // Use WebP format when supported
        const supportsWebP = this.checkWebPSupport();
        
        if (supportsWebP) {
            document.querySelectorAll('img[data-webp]').forEach(img => {
                img.src = img.getAttribute('data-webp');
            });
        }
        
        // Setup responsive images
        this.setupResponsiveImages();
    }
    
    /**
     * Check WebP support
     */
    checkWebPSupport() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }
    
    /**
     * Setup responsive images
     */
    setupResponsiveImages() {
        const updateImageSources = () => {
            const screenWidth = window.innerWidth;
            
            document.querySelectorAll('img[data-responsive]').forEach(img => {
                const sources = JSON.parse(img.getAttribute('data-responsive'));
                let selectedSource = sources.default;
                
                for (const breakpoint in sources) {
                    if (breakpoint !== 'default' && screenWidth >= parseInt(breakpoint)) {
                        selectedSource = sources[breakpoint];
                    }
                }
                
                if (img.src !== selectedSource) {
                    img.src = selectedSource;
                }
            });
        };
        
        window.addEventListener('resize', () => {
            this.debounce('responsive-images', updateImageSources, 250);
        });
        
        updateImageSources();
    }
    
    /**
     * Setup memory management
     */
    setupMemoryManagement() {
        // Clean up event listeners on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
        
        // Monitor memory usage (if available)
        if ('memory' in performance) {
            setInterval(() => {
                this.checkMemoryUsage();
            }, 30000); // Check every 30 seconds
        }
    }
    
    /**
     * Check memory usage and cleanup if needed
     */
    checkMemoryUsage() {
        if ('memory' in performance) {
            const memory = performance.memory;
            const usedPercent = (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100;
            
            if (usedPercent > 80) {
                console.warn('High memory usage detected, performing cleanup');
                this.performMemoryCleanup();
            }
        }
    }
    
    /**
     * Perform memory cleanup
     */
    performMemoryCleanup() {
        // Clear caches
        this.apiCache.clear();
        this.cacheExpiry.clear();
        this.domCache.clear();
        
        // Clear debounce timers
        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        
        // Trigger garbage collection if available
        if (window.gc) {
            window.gc();
        }
    }
    
    /**
     * Handle window resize events
     */
    handleWindowResize() {
        // Update chart sizes
        if (window.Chart) {
            Chart.helpers.each(Chart.instances, (instance) => {
                instance.resize();
            });
        }
        
        // Update virtual scroll containers
        document.querySelectorAll('[data-virtual-scroll]').forEach(container => {
            if (container._virtualScroll) {
                container._virtualScroll.refresh();
            }
        });
    }
    
    /**
     * Handle scroll events
     */
    handleScroll() {
        // Update sticky elements
        this.updateStickyElements();
        
        // Load more content if needed
        this.checkInfiniteScroll();
    }
    
    /**
     * Update sticky elements
     */
    updateStickyElements() {
        document.querySelectorAll('[data-sticky]').forEach(element => {
            const rect = element.getBoundingClientRect();
            const threshold = parseInt(element.getAttribute('data-sticky-threshold')) || 0;
            
            if (rect.top <= threshold) {
                element.classList.add('stuck');
            } else {
                element.classList.remove('stuck');
            }
        });
    }
    
    /**
     * Check for infinite scroll
     */
    checkInfiniteScroll() {
        document.querySelectorAll('[data-infinite-scroll]').forEach(container => {
            const rect = container.getBoundingClientRect();
            const threshold = parseInt(container.getAttribute('data-scroll-threshold')) || 100;
            
            if (rect.bottom <= window.innerHeight + threshold) {
                const loadMore = container.getAttribute('data-load-more');
                if (loadMore && typeof window[loadMore] === 'function') {
                    window[loadMore]();
                }
            }
        });
    }
    
    /**
     * Optimize table rendering for large datasets
     */
    optimizeTableRendering(table, data, pageSize = 50) {
        const tbody = table.querySelector('tbody');
        const totalRows = data.length;
        let currentPage = 0;
        
        const renderPage = (page) => {
            const start = page * pageSize;
            const end = Math.min(start + pageSize, totalRows);
            const pageData = data.slice(start, end);
            
            const fragment = document.createDocumentFragment();
            pageData.forEach(rowData => {
                const row = this.createTableRow(rowData);
                fragment.appendChild(row);
            });
            
            tbody.innerHTML = '';
            tbody.appendChild(fragment);
        };
        
        // Initial render
        renderPage(0);
        
        // Add pagination controls
        this.addTablePagination(table, totalRows, pageSize, renderPage);
    }
    
    /**
     * Add pagination controls to table
     */
    addTablePagination(table, totalRows, pageSize, renderCallback) {
        const totalPages = Math.ceil(totalRows / pageSize);
        
        if (totalPages <= 1) return;
        
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'table-pagination';
        
        const pagination = document.createElement('nav');
        pagination.innerHTML = `
            <ul class="pagination pagination-sm justify-content-center">
                <li class="page-item">
                    <button class="page-link" data-page="prev">&laquo;</button>
                </li>
                ${Array.from({length: totalPages}, (_, i) => `
                    <li class="page-item ${i === 0 ? 'active' : ''}">
                        <button class="page-link" data-page="${i}">${i + 1}</button>
                    </li>
                `).join('')}
                <li class="page-item">
                    <button class="page-link" data-page="next">&raquo;</button>
                </li>
            </ul>
        `;
        
        paginationContainer.appendChild(pagination);
        table.parentNode.insertBefore(paginationContainer, table.nextSibling);
        
        // Handle pagination clicks
        let currentPage = 0;
        pagination.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                const page = e.target.getAttribute('data-page');
                
                if (page === 'prev' && currentPage > 0) {
                    currentPage--;
                } else if (page === 'next' && currentPage < totalPages - 1) {
                    currentPage++;
                } else if (!isNaN(page)) {
                    currentPage = parseInt(page);
                }
                
                renderCallback(currentPage);
                this.updatePaginationState(pagination, currentPage, totalPages);
            }
        });
    }
    
    /**
     * Update pagination state
     */
    updatePaginationState(pagination, currentPage, totalPages) {
        const items = pagination.querySelectorAll('.page-item');
        
        items.forEach((item, index) => {
            item.classList.remove('active');
            
            if (index === currentPage + 1) { // +1 because of prev button
                item.classList.add('active');
            }
        });
        
        // Update prev/next button states
        const prevBtn = pagination.querySelector('[data-page="prev"]').parentNode;
        const nextBtn = pagination.querySelector('[data-page="next"]').parentNode;
        
        prevBtn.classList.toggle('disabled', currentPage === 0);
        nextBtn.classList.toggle('disabled', currentPage === totalPages - 1);
    }
    
    /**
     * Cleanup resources
     */
    cleanup() {
        // Disconnect intersection observer
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }
        
        // Clear all caches
        this.cache.clear();
        this.apiCache.clear();
        this.cacheExpiry.clear();
        this.domCache.clear();
        
        // Clear timers
        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        
        // Clear lazy elements
        this.lazyElements.clear();
    }
}

// Initialize performance optimizations when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.attendancePerformance = new AttendancePerformance();
    });
} else {
    window.attendancePerformance = new AttendancePerformance();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AttendancePerformance;
}