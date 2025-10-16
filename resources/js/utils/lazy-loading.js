/**
 * Lazy Loading Utilities for PNS-Dhampur School Management System
 * Implements intersection observer for images, components, and content
 */

class LazyLoader {
    constructor(options = {}) {
        this.options = {
            rootMargin: '50px 0px',
            threshold: 0.01,
            loadingClass: 'lazy-loading',
            loadedClass: 'lazy-loaded',
            errorClass: 'lazy-error',
            ...options
        };

        this.observer = null;
        this.init();
    }

    init() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                {
                    rootMargin: this.options.rootMargin,
                    threshold: this.options.threshold
                }
            );
        }
    }

    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadElement(entry.target);
                this.observer.unobserve(entry.target);
            }
        });
    }

    loadElement(element) {
        const type = element.dataset.lazyType || 'image';
        
        switch (type) {
            case 'image':
                this.loadImage(element);
                break;
            case 'component':
                this.loadComponent(element);
                break;
            case 'iframe':
                this.loadIframe(element);
                break;
            case 'background':
                this.loadBackground(element);
                break;
            default:
                this.loadGeneric(element);
        }
    }

    loadImage(img) {
        img.classList.add(this.options.loadingClass);
        
        const src = img.dataset.lazySrc;
        const srcset = img.dataset.lazySrcset;
        
        if (!src && !srcset) return;

        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            if (src) img.src = src;
            if (srcset) img.srcset = srcset;
            
            img.classList.remove(this.options.loadingClass);
            img.classList.add(this.options.loadedClass);
            
            // Remove data attributes to clean up
            delete img.dataset.lazySrc;
            delete img.dataset.lazySrcset;
            
            // Trigger custom event
            img.dispatchEvent(new CustomEvent('lazyloaded'));
        };
        
        imageLoader.onerror = () => {
            img.classList.remove(this.options.loadingClass);
            img.classList.add(this.options.errorClass);
            
            // Set fallback image if provided
            const fallback = img.dataset.lazyFallback;
            if (fallback) {
                img.src = fallback;
            }
            
            img.dispatchEvent(new CustomEvent('lazyerror'));
        };
        
        imageLoader.src = src || srcset.split(' ')[0];
    }

    loadBackground(element) {
        element.classList.add(this.options.loadingClass);
        
        const bgImage = element.dataset.lazyBg;
        if (!bgImage) return;

        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            element.style.backgroundImage = `url(${bgImage})`;
            element.classList.remove(this.options.loadingClass);
            element.classList.add(this.options.loadedClass);
            
            delete element.dataset.lazyBg;
            element.dispatchEvent(new CustomEvent('lazyloaded'));
        };
        
        imageLoader.onerror = () => {
            element.classList.remove(this.options.loadingClass);
            element.classList.add(this.options.errorClass);
            element.dispatchEvent(new CustomEvent('lazyerror'));
        };
        
        imageLoader.src = bgImage;
    }

    loadIframe(iframe) {
        iframe.classList.add(this.options.loadingClass);
        
        const src = iframe.dataset.lazySrc;
        if (!src) return;

        iframe.onload = () => {
            iframe.classList.remove(this.options.loadingClass);
            iframe.classList.add(this.options.loadedClass);
            delete iframe.dataset.lazySrc;
            iframe.dispatchEvent(new CustomEvent('lazyloaded'));
        };
        
        iframe.onerror = () => {
            iframe.classList.remove(this.options.loadingClass);
            iframe.classList.add(this.options.errorClass);
            iframe.dispatchEvent(new CustomEvent('lazyerror'));
        };
        
        iframe.src = src;
    }

    async loadComponent(element) {
        element.classList.add(this.options.loadingClass);
        
        const componentName = element.dataset.lazyComponent;
        const componentData = element.dataset.lazyData ? 
            JSON.parse(element.dataset.lazyData) : {};
        
        try {
            // Dynamic import for component
            const module = await import(`../components/${componentName}.js`);
            const Component = module.default || module[componentName];
            
            if (Component) {
                const instance = new Component(element, componentData);
                await instance.render();
                
                element.classList.remove(this.options.loadingClass);
                element.classList.add(this.options.loadedClass);
                
                delete element.dataset.lazyComponent;
                delete element.dataset.lazyData;
                
                element.dispatchEvent(new CustomEvent('lazyloaded', {
                    detail: { component: instance }
                }));
            }
        } catch (error) {
            console.error('Failed to load component:', componentName, error);
            element.classList.remove(this.options.loadingClass);
            element.classList.add(this.options.errorClass);
            element.dispatchEvent(new CustomEvent('lazyerror', {
                detail: { error }
            }));
        }
    }

    loadGeneric(element) {
        element.classList.add(this.options.loadingClass);
        
        const callback = element.dataset.lazyCallback;
        if (callback && window[callback]) {
            try {
                window[callback](element);
                element.classList.remove(this.options.loadingClass);
                element.classList.add(this.options.loadedClass);
                element.dispatchEvent(new CustomEvent('lazyloaded'));
            } catch (error) {
                console.error('Lazy loading callback failed:', error);
                element.classList.add(this.options.errorClass);
                element.dispatchEvent(new CustomEvent('lazyerror'));
            }
        }
    }

    observe(element) {
        if (this.observer) {
            this.observer.observe(element);
        } else {
            // Fallback for browsers without IntersectionObserver
            this.loadElement(element);
        }
    }

    observeAll(selector = '[data-lazy-src], [data-lazy-component], [data-lazy-bg]') {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => this.observe(element));
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

// Lazy loading for tables with large datasets
class LazyTable {
    constructor(tableElement, options = {}) {
        this.table = tableElement;
        this.options = {
            rowsPerPage: 50,
            loadMoreThreshold: 10,
            loadingText: 'Loading more rows...',
            ...options
        };
        
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMoreData = true;
        
        this.init();
    }

    init() {
        this.tbody = this.table.querySelector('tbody');
        this.setupScrollListener();
        this.loadInitialData();
    }

    setupScrollListener() {
        const scrollContainer = this.table.closest('.table-responsive') || window;
        
        scrollContainer.addEventListener('scroll', () => {
            if (this.shouldLoadMore()) {
                this.loadMoreRows();
            }
        });
    }

    shouldLoadMore() {
        if (this.isLoading || !this.hasMoreData) return false;
        
        const scrollContainer = this.table.closest('.table-responsive');
        if (scrollContainer) {
            const { scrollTop, scrollHeight, clientHeight } = scrollContainer;
            return scrollTop + clientHeight >= scrollHeight - 100;
        } else {
            return window.innerHeight + window.scrollY >= document.body.offsetHeight - 100;
        }
    }

    async loadInitialData() {
        const dataSource = this.table.dataset.lazySource;
        if (dataSource) {
            await this.loadData(dataSource, 1);
        }
    }

    async loadMoreRows() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingIndicator();
        
        try {
            const dataSource = this.table.dataset.lazySource;
            const nextPage = this.currentPage + 1;
            
            const hasData = await this.loadData(dataSource, nextPage);
            
            if (hasData) {
                this.currentPage = nextPage;
            } else {
                this.hasMoreData = false;
            }
        } catch (error) {
            console.error('Failed to load more table rows:', error);
        } finally {
            this.isLoading = false;
            this.hideLoadingIndicator();
        }
    }

    async loadData(source, page) {
        try {
            const response = await fetch(`${source}?page=${page}&per_page=${this.options.rowsPerPage}`);
            const data = await response.json();
            
            if (data.data && data.data.length > 0) {
                this.appendRows(data.data);
                return data.has_more_pages || data.data.length === this.options.rowsPerPage;
            }
            
            return false;
        } catch (error) {
            console.error('Failed to fetch table data:', error);
            return false;
        }
    }

    appendRows(rowsData) {
        const fragment = document.createDocumentFragment();
        
        rowsData.forEach(rowData => {
            const row = this.createRow(rowData);
            fragment.appendChild(row);
        });
        
        this.tbody.appendChild(fragment);
        
        // Trigger event for any additional processing
        this.table.dispatchEvent(new CustomEvent('rowsLoaded', {
            detail: { rowsData, currentPage: this.currentPage }
        }));
    }

    createRow(rowData) {
        const row = document.createElement('tr');
        const template = this.table.dataset.rowTemplate;
        
        if (template) {
            // Use template if provided
            row.innerHTML = this.processTemplate(template, rowData);
        } else {
            // Default row creation
            Object.values(rowData).forEach(cellData => {
                const cell = document.createElement('td');
                cell.textContent = cellData;
                row.appendChild(cell);
            });
        }
        
        return row;
    }

    processTemplate(template, data) {
        return template.replace(/\{\{(\w+)\}\}/g, (match, key) => {
            return data[key] || '';
        });
    }

    showLoadingIndicator() {
        if (!this.loadingRow) {
            this.loadingRow = document.createElement('tr');
            this.loadingRow.className = 'lazy-loading-row';
            
            const cell = document.createElement('td');
            cell.colSpan = this.table.querySelectorAll('thead th').length;
            cell.textContent = this.options.loadingText;
            cell.className = 'text-center py-3';
            
            this.loadingRow.appendChild(cell);
        }
        
        this.tbody.appendChild(this.loadingRow);
    }

    hideLoadingIndicator() {
        if (this.loadingRow && this.loadingRow.parentNode) {
            this.loadingRow.parentNode.removeChild(this.loadingRow);
        }
    }
}

// Initialize lazy loading when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize image lazy loading
    const lazyLoader = new LazyLoader();
    lazyLoader.observeAll();
    
    // Initialize lazy tables
    const lazyTables = document.querySelectorAll('[data-lazy-table]');
    lazyTables.forEach(table => {
        new LazyTable(table);
    });
    
    // Re-initialize lazy loading for dynamically added content
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    const lazyElements = node.querySelectorAll('[data-lazy-src], [data-lazy-component], [data-lazy-bg]');
                    lazyElements.forEach(element => lazyLoader.observe(element));
                    
                    const lazyTables = node.querySelectorAll('[data-lazy-table]');
                    lazyTables.forEach(table => new LazyTable(table));
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Export for use in other modules
export { LazyLoader, LazyTable };