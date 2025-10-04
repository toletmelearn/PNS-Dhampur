/**
 * Substitute Notification Manager
 * Handles mobile notifications for substitute teacher assignments
 */
class SubstituteNotificationManager {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: options.apiEndpoint || '/api/substitute/notifications',
            updateInterval: options.updateInterval || 30000,
            enableVibration: options.enableVibration !== false,
            enableSound: options.enableSound !== false,
            enableReminders: options.enableReminders !== false,
            maxNotifications: options.maxNotifications || 10,
            ...options
        };

        this.notifications = [];
        this.isInitialized = false;
        this.updateTimer = null;
        this.notificationPermission = 'default';
        
        // Bind methods
        this.init = this.init.bind(this);
        this.checkPermissions = this.checkPermissions.bind(this);
        this.fetchNotifications = this.fetchNotifications.bind(this);
        this.displayNotifications = this.displayNotifications.bind(this);
        this.handleNotificationAction = this.handleNotificationAction.bind(this);
    }

    /**
     * Initialize the notification manager
     */
    async init() {
        if (this.isInitialized) return;

        try {
            // Check notification permissions
            await this.checkPermissions();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Load settings from localStorage
            this.loadSettings();
            
            // Initial fetch
            await this.fetchNotifications();
            
            // Start periodic updates
            this.startPeriodicUpdates();
            
            this.isInitialized = true;
            console.log('Substitute Notification Manager initialized');
            
        } catch (error) {
            console.error('Failed to initialize Substitute Notification Manager:', error);
        }
    }

    /**
     * Check and request notification permissions
     */
    async checkPermissions() {
        if (!('Notification' in window)) {
            console.warn('This browser does not support notifications');
            return false;
        }

        this.notificationPermission = Notification.permission;

        if (this.notificationPermission === 'default') {
            this.showPermissionBanner();
        } else if (this.notificationPermission === 'denied') {
            this.showPermissionDeniedMessage();
        }

        return this.notificationPermission === 'granted';
    }

    /**
     * Request notification permission
     */
    async requestPermission() {
        if (!('Notification' in window)) return false;

        try {
            const permission = await Notification.requestPermission();
            this.notificationPermission = permission;
            
            if (permission === 'granted') {
                this.hidePermissionBanner();
                this.showSuccessMessage('Notifications enabled successfully!');
            } else {
                this.showErrorMessage('Notification permission denied');
            }
            
            return permission === 'granted';
        } catch (error) {
            console.error('Error requesting notification permission:', error);
            return false;
        }
    }

    /**
     * Fetch notifications from the server
     */
    async fetchNotifications() {
        try {
            const response = await fetch(this.options.apiEndpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.processNotifications(data.notifications || []);
            } else {
                console.error('Failed to fetch notifications:', data.message);
            }
            
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    /**
     * Process new notifications
     */
    processNotifications(newNotifications) {
        const existingIds = this.notifications.map(n => n.id);
        const freshNotifications = newNotifications.filter(n => !existingIds.includes(n.id));
        
        // Add new notifications
        freshNotifications.forEach(notification => {
            this.notifications.unshift(notification);
            
            // Show browser notification for new items
            if (this.notificationPermission === 'granted' && notification.show_browser_notification) {
                this.showBrowserNotification(notification);
            }
            
            // Vibrate for high priority notifications
            if (this.options.enableVibration && notification.priority === 'high' && 'vibrate' in navigator) {
                navigator.vibrate([200, 100, 200]);
            }
        });

        // Limit notifications count
        if (this.notifications.length > this.options.maxNotifications) {
            this.notifications = this.notifications.slice(0, this.options.maxNotifications);
        }

        // Update display
        this.displayNotifications();
        this.updateNotificationCount();
    }

    /**
     * Display notifications in the UI
     */
    displayNotifications() {
        const container = document.getElementById('active-notifications');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div class="no-notifications"><p class="text-muted text-center py-3">No new notifications</p></div>';
            return;
        }

        const template = document.getElementById('notification-template');
        if (!template) return;

        container.innerHTML = '';

        this.notifications.forEach(notification => {
            const notificationHtml = this.renderNotification(notification, template.innerHTML);
            const notificationElement = document.createElement('div');
            notificationElement.innerHTML = notificationHtml;
            container.appendChild(notificationElement.firstElementChild);
        });
    }

    /**
     * Render a single notification
     */
    renderNotification(notification, template) {
        let html = template;
        
        // Replace template variables
        html = html.replace(/\{\{id\}\}/g, notification.id);
        html = html.replace(/\{\{type\}\}/g, notification.type);
        html = html.replace(/\{\{title\}\}/g, this.escapeHtml(notification.title));
        html = html.replace(/\{\{message\}\}/g, this.escapeHtml(notification.message));
        html = html.replace(/\{\{time\}\}/g, this.formatTime(notification.created_at));
        html = html.replace(/\{\{priority\}\}/g, notification.priority || 'medium');
        html = html.replace(/\{\{icon\}\}/g, this.getNotificationIcon(notification.type));

        // Handle actions
        if (notification.actions && notification.actions.length > 0) {
            let actionsHtml = '';
            notification.actions.forEach(action => {
                actionsHtml += `
                    <button class="btn btn-sm ${action.class}" data-action="${action.action}" data-id="${notification.id}">
                        <i class="${action.icon}"></i> ${action.text}
                    </button>
                `;
            });
            html = html.replace(/\{\{#if actions\}\}.*?\{\{\/if\}\}/s, actionsHtml);
        } else {
            html = html.replace(/\{\{#if actions\}\}.*?\{\{\/if\}\}/s, '');
        }

        return html;
    }

    /**
     * Show browser notification
     */
    showBrowserNotification(notification) {
        if (this.notificationPermission !== 'granted') return;

        const options = {
            body: notification.message,
            icon: '/images/notification-icons/substitute.png',
            badge: '/images/notification-icons/badge.png',
            tag: `substitute-${notification.id}`,
            requireInteraction: notification.priority === 'high',
            silent: !this.options.enableSound,
            data: {
                id: notification.id,
                type: notification.type,
                url: notification.action_url
            }
        };

        // Add vibration pattern for mobile
        if (this.options.enableVibration && notification.priority === 'high') {
            options.vibrate = [200, 100, 200, 100, 200];
        }

        const browserNotification = new Notification(notification.title, options);

        // Handle notification click
        browserNotification.onclick = (event) => {
            event.preventDefault();
            window.focus();
            
            if (notification.action_url) {
                window.location.href = notification.action_url;
            }
            
            browserNotification.close();
        };

        // Auto close after 10 seconds for non-high priority
        if (notification.priority !== 'high') {
            setTimeout(() => {
                browserNotification.close();
            }, 10000);
        }
    }

    /**
     * Handle notification actions (confirm, decline, etc.)
     */
    async handleNotificationAction(action, notificationId) {
        try {
            const response = await fetch(`${this.options.apiEndpoint}/${notificationId}/action`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ action })
            });

            const data = await response.json();
            
            if (data.success) {
                // Remove notification from list
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.displayNotifications();
                this.updateNotificationCount();
                
                this.showSuccessMessage(data.message || 'Action completed successfully');
            } else {
                this.showErrorMessage(data.message || 'Action failed');
            }
            
        } catch (error) {
            console.error('Error handling notification action:', error);
            this.showErrorMessage('Failed to process action');
        }
    }

    /**
     * Dismiss notification
     */
    async dismissNotification(notificationId) {
        try {
            const response = await fetch(`${this.options.apiEndpoint}/${notificationId}/dismiss`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (response.ok) {
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.displayNotifications();
                this.updateNotificationCount();
            }
            
        } catch (error) {
            console.error('Error dismissing notification:', error);
        }
    }

    /**
     * Clear all notifications
     */
    async clearAllNotifications() {
        try {
            const response = await fetch(`${this.options.apiEndpoint}/clear`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (response.ok) {
                this.notifications = [];
                this.displayNotifications();
                this.updateNotificationCount();
                this.showSuccessMessage('All notifications cleared');
            }
            
        } catch (error) {
            console.error('Error clearing notifications:', error);
        }
    }

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Permission banner
        const enableBtn = document.getElementById('enable-notifications-btn');
        const dismissBtn = document.getElementById('dismiss-banner-btn');
        
        if (enableBtn) {
            enableBtn.addEventListener('click', () => this.requestPermission());
        }
        
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => this.hidePermissionBanner());
        }

        // FAB menu
        const fabBtn = document.getElementById('fab-main-btn');
        const fabMenu = document.getElementById('fab-menu');
        
        if (fabBtn) {
            fabBtn.addEventListener('click', () => {
                const isVisible = fabMenu.style.display !== 'none';
                fabMenu.style.display = isVisible ? 'none' : 'block';
            });
        }

        // FAB menu items
        document.getElementById('fab-settings-btn')?.addEventListener('click', () => this.toggleSettings());
        document.getElementById('fab-refresh-btn')?.addEventListener('click', () => this.fetchNotifications());
        document.getElementById('fab-clear-btn')?.addEventListener('click', () => this.clearAllNotifications());

        // Settings
        document.getElementById('close-settings-btn')?.addEventListener('click', () => this.hideSettings());
        document.getElementById('save-settings-btn')?.addEventListener('click', () => this.saveSettings());

        // Notification actions (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action]')) {
                const action = e.target.getAttribute('data-action');
                const id = e.target.getAttribute('data-id');
                this.handleNotificationAction(action, id);
            }
            
            if (e.target.matches('.notification-dismiss') || e.target.closest('.notification-dismiss')) {
                const dismissBtn = e.target.closest('.notification-dismiss');
                const id = dismissBtn.getAttribute('data-id');
                this.dismissNotification(id);
            }
        });
    }

    /**
     * Start periodic updates
     */
    startPeriodicUpdates() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            this.fetchNotifications();
        }, this.options.updateInterval);
    }

    /**
     * Stop periodic updates
     */
    stopPeriodicUpdates() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }

    /**
     * Update notification count badge
     */
    updateNotificationCount() {
        const badge = document.getElementById('notification-count');
        if (!badge) return;

        const count = this.notifications.length;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count.toString();
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    /**
     * Show/hide permission banner
     */
    showPermissionBanner() {
        const banner = document.getElementById('notification-permission-banner');
        if (banner) banner.style.display = 'block';
    }

    hidePermissionBanner() {
        const banner = document.getElementById('notification-permission-banner');
        if (banner) banner.style.display = 'none';
    }

    /**
     * Toggle settings panel
     */
    toggleSettings() {
        const panel = document.getElementById('notification-settings');
        if (!panel) return;
        
        const isVisible = panel.style.display !== 'none';
        panel.style.display = isVisible ? 'none' : 'block';
    }

    hideSettings() {
        const panel = document.getElementById('notification-settings');
        if (panel) panel.style.display = 'none';
    }

    /**
     * Load settings from localStorage
     */
    loadSettings() {
        const settings = localStorage.getItem('substitute-notification-settings');
        if (settings) {
            const parsed = JSON.parse(settings);
            this.options = { ...this.options, ...parsed };
            
            // Update UI
            document.getElementById('vibration-enabled').checked = this.options.enableVibration;
            document.getElementById('sound-enabled').checked = this.options.enableSound;
            document.getElementById('reminder-enabled').checked = this.options.enableReminders;
            document.getElementById('emergency-priority').checked = this.options.emergencyPriority !== false;
        }
    }

    /**
     * Save settings to localStorage
     */
    saveSettings() {
        const settings = {
            enableVibration: document.getElementById('vibration-enabled').checked,
            enableSound: document.getElementById('sound-enabled').checked,
            enableReminders: document.getElementById('reminder-enabled').checked,
            emergencyPriority: document.getElementById('emergency-priority').checked
        };
        
        this.options = { ...this.options, ...settings };
        localStorage.setItem('substitute-notification-settings', JSON.stringify(settings));
        
        this.hideSettings();
        this.showSuccessMessage('Settings saved successfully');
    }

    /**
     * Utility methods
     */
    getNotificationIcon(type) {
        const icons = {
            'substitution_assignment': 'fas fa-chalkboard-teacher',
            'substitute_confirmed': 'fas fa-check-circle',
            'substitution_reminder': 'fas fa-clock',
            'substitution_cancelled': 'fas fa-times-circle',
            'emergency_assignment': 'fas fa-exclamation-triangle'
        };
        
        return icons[type] || 'fas fa-bell';
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
        
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    showErrorMessage(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show with animation
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stopPeriodicUpdates();
        this.isInitialized = false;
    }
}

// Export for use
window.SubstituteNotificationManager = SubstituteNotificationManager;