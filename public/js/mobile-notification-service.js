/**
 * Mobile Notification Service for Bell Timing System
 * Handles push notifications, mobile-specific features, and responsive behavior
 */
class MobileNotificationService {
    constructor() {
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.isMobile = this.detectMobile();
        this.isOnline = navigator.onLine;
        this.notificationQueue = [];
        this.settings = this.loadSettings();
        this.vibrationPatterns = {
            bell: [200, 100, 200],
            warning: [100, 50, 100, 50, 100],
            emergency: [500, 200, 500, 200, 500],
            gentle: [100]
        };
        
        this.init();
    }

    /**
     * Initialize the notification service
     */
    async init() {
        if (!this.isSupported) {
            console.warn('Push notifications not supported');
            return;
        }

        // Register service worker
        await this.registerServiceWorker();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Setup periodic sync for offline support
        this.setupPeriodicSync();
        
        // Initialize notification permission status
        await this.checkPermissionStatus();
        
        console.log('Mobile Notification Service initialized');
    }

    /**
     * Detect if device is mobile
     */
    detectMobile() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        return /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent) ||
               (navigator.maxTouchPoints && navigator.maxTouchPoints > 2 && /MacIntel/.test(navigator.platform));
    }

    /**
     * Register service worker for push notifications
     */
    async registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register('/js/notification-sw.js');
            console.log('Service Worker registered:', registration);
            this.swRegistration = registration;
            return registration;
        } catch (error) {
            console.error('Service Worker registration failed:', error);
            throw error;
        }
    }

    /**
     * Setup event listeners for online/offline and visibility changes
     */
    setupEventListeners() {
        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.processQueuedNotifications();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
        });

        // Page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.handlePageVisible();
            }
        });

        // Mobile-specific events
        if (this.isMobile) {
            // Handle device orientation changes
            window.addEventListener('orientationchange', () => {
                setTimeout(() => this.adjustNotificationPosition(), 100);
            });

            // Handle touch events for notification interaction
            document.addEventListener('touchstart', this.handleTouchStart.bind(this));
        }
    }

    /**
     * Setup periodic background sync
     */
    async setupPeriodicSync() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('bell-timing-sync');
            } catch (error) {
                console.warn('Background sync not available:', error);
            }
        }
    }

    /**
     * Request notification permission
     */
    async requestPermission() {
        if (!this.isSupported) {
            throw new Error('Notifications not supported');
        }

        let permission = Notification.permission;

        if (permission === 'default') {
            // Show custom permission dialog for better UX on mobile
            if (this.isMobile) {
                const userConsent = await this.showCustomPermissionDialog();
                if (!userConsent) {
                    return 'denied';
                }
            }

            permission = await Notification.requestPermission();
        }

        this.settings.permission = permission;
        this.saveSettings();

        if (permission === 'granted') {
            await this.subscribeToPush();
        }

        return permission;
    }

    /**
     * Show custom permission dialog for mobile
     */
    showCustomPermissionDialog() {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = 'mobile-permission-dialog';
            dialog.innerHTML = `
                <div class="permission-content">
                    <div class="permission-icon">🔔</div>
                    <h3>Enable Bell Notifications</h3>
                    <p>Get notified about period changes, breaks, and important announcements even when the app is closed.</p>
                    <div class="permission-buttons">
                        <button class="btn-deny">Not Now</button>
                        <button class="btn-allow">Allow</button>
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);

            dialog.querySelector('.btn-allow').onclick = () => {
                document.body.removeChild(dialog);
                resolve(true);
            };

            dialog.querySelector('.btn-deny').onclick = () => {
                document.body.removeChild(dialog);
                resolve(false);
            };
        });
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPush() {
        try {
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(window.vapidPublicKey || '')
            });

            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            this.settings.subscribed = true;
            this.saveSettings();
            
            return subscription;
        } catch (error) {
            console.error('Push subscription failed:', error);
            throw error;
        }
    }

    /**
     * Send push subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('/api/push-subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    subscription: subscription,
                    device_type: this.isMobile ? 'mobile' : 'desktop',
                    user_agent: navigator.userAgent
                })
            });

            if (!response.ok) {
                throw new Error('Failed to save subscription');
            }
        } catch (error) {
            console.error('Error sending subscription to server:', error);
        }
    }

    /**
     * Show local notification
     */
    async showNotification(title, options = {}) {
        const defaultOptions = {
            icon: '/images/bell-icon.png',
            badge: '/images/bell-badge.png',
            vibrate: this.vibrationPatterns.gentle,
            requireInteraction: false,
            silent: false,
            ...options
        };

        // Mobile-specific adjustments
        if (this.isMobile) {
            defaultOptions.requireInteraction = options.priority === 'high';
            defaultOptions.vibrate = this.vibrationPatterns[options.type] || this.vibrationPatterns.gentle;
        }

        try {
            if (Notification.permission === 'granted') {
                if (this.swRegistration) {
                    // Use service worker for better mobile support
                    await this.swRegistration.showNotification(title, defaultOptions);
                } else {
                    // Fallback to regular notification
                    new Notification(title, defaultOptions);
                }

                // Add to notification history
                this.addToHistory(title, defaultOptions);
            } else {
                // Queue notification if permission not granted
                this.queueNotification(title, defaultOptions);
            }
        } catch (error) {
            console.error('Error showing notification:', error);
            // Fallback to in-app notification
            this.showInAppNotification(title, defaultOptions);
        }
    }

    /**
     * Show in-app notification as fallback
     */
    showInAppNotification(title, options) {
        const notification = document.createElement('div');
        notification.className = `in-app-notification ${options.type || 'info'}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${this.getNotificationIcon(options.type)}</div>
                <div class="notification-text">
                    <div class="notification-title">${title}</div>
                    <div class="notification-body">${options.body || ''}</div>
                </div>
                <button class="notification-close">&times;</button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after delay
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, options.duration || 5000);

        // Close button
        notification.querySelector('.notification-close').onclick = () => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        };

        // Vibrate on mobile
        if (this.isMobile && 'vibrate' in navigator) {
            navigator.vibrate(options.vibrate || this.vibrationPatterns.gentle);
        }
    }

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            bell: '🔔',
            warning: '⚠️',
            emergency: '🚨',
            info: 'ℹ️',
            success: '✅',
            period: '📚'
        };
        return icons[type] || icons.info;
    }

    /**
     * Schedule notification
     */
    scheduleNotification(title, options, delay) {
        setTimeout(() => {
            this.showNotification(title, options);
        }, delay);
    }

    /**
     * Queue notification for later delivery
     */
    queueNotification(title, options) {
        this.notificationQueue.push({
            title,
            options,
            timestamp: Date.now()
        });
    }

    /**
     * Process queued notifications
     */
    async processQueuedNotifications() {
        if (Notification.permission !== 'granted') {
            return;
        }

        while (this.notificationQueue.length > 0) {
            const notification = this.notificationQueue.shift();
            await this.showNotification(notification.title, notification.options);
            
            // Small delay between notifications
            await new Promise(resolve => setTimeout(resolve, 500));
        }
    }

    /**
     * Handle page becoming visible
     */
    handlePageVisible() {
        // Clear any pending notifications that are no longer relevant
        this.clearOldNotifications();
        
        // Update notification badge
        this.updateNotificationBadge();
    }

    /**
     * Handle touch start for mobile interactions
     */
    handleTouchStart(event) {
        // Handle swipe gestures on notifications
        if (event.target.closest('.in-app-notification')) {
            this.handleNotificationSwipe(event);
        }
    }

    /**
     * Handle notification swipe gestures
     */
    handleNotificationSwipe(event) {
        let startX = event.touches[0].clientX;
        let notification = event.target.closest('.in-app-notification');

        const handleTouchMove = (e) => {
            let currentX = e.touches[0].clientX;
            let diffX = startX - currentX;
            
            if (Math.abs(diffX) > 50) {
                notification.style.transform = `translateX(${-diffX}px)`;
            }
        };

        const handleTouchEnd = (e) => {
            let endX = e.changedTouches[0].clientX;
            let diffX = startX - endX;
            
            if (Math.abs(diffX) > 100) {
                // Swipe to dismiss
                notification.style.transform = 'translateX(-100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            } else {
                // Snap back
                notification.style.transform = 'translateX(0)';
            }
            
            document.removeEventListener('touchmove', handleTouchMove);
            document.removeEventListener('touchend', handleTouchEnd);
        };

        document.addEventListener('touchmove', handleTouchMove);
        document.addEventListener('touchend', handleTouchEnd);
    }

    /**
     * Adjust notification position for mobile
     */
    adjustNotificationPosition() {
        const notifications = document.querySelectorAll('.in-app-notification');
        notifications.forEach((notification, index) => {
            notification.style.top = `${20 + (index * 80)}px`;
        });
    }

    /**
     * Load settings from localStorage
     */
    loadSettings() {
        try {
            const saved = localStorage.getItem('bellNotificationSettings');
            return saved ? JSON.parse(saved) : {
                permission: 'default',
                subscribed: false,
                soundEnabled: true,
                vibrationEnabled: true,
                quietHours: { start: '22:00', end: '07:00' },
                notificationTypes: {
                    bell: true,
                    warning: true,
                    emergency: true,
                    period: true
                }
            };
        } catch (error) {
            console.error('Error loading settings:', error);
            return {};
        }
    }

    /**
     * Save settings to localStorage
     */
    saveSettings() {
        try {
            localStorage.setItem('bellNotificationSettings', JSON.stringify(this.settings));
        } catch (error) {
            console.error('Error saving settings:', error);
        }
    }

    /**
     * Check current permission status
     */
    async checkPermissionStatus() {
        this.settings.permission = Notification.permission;
        
        if (this.swRegistration) {
            try {
                const subscription = await this.swRegistration.pushManager.getSubscription();
                this.settings.subscribed = !!subscription;
            } catch (error) {
                console.error('Error checking subscription:', error);
                this.settings.subscribed = false;
            }
        }
        
        this.saveSettings();
    }

    /**
     * Add notification to history
     */
    addToHistory(title, options) {
        const history = JSON.parse(localStorage.getItem('notificationHistory') || '[]');
        history.unshift({
            title,
            body: options.body,
            type: options.type,
            timestamp: Date.now()
        });
        
        // Keep only last 50 notifications
        if (history.length > 50) {
            history.splice(50);
        }
        
        localStorage.setItem('notificationHistory', JSON.stringify(history));
    }

    /**
     * Clear old notifications
     */
    clearOldNotifications() {
        const cutoff = Date.now() - (24 * 60 * 60 * 1000); // 24 hours
        this.notificationQueue = this.notificationQueue.filter(n => n.timestamp > cutoff);
    }

    /**
     * Update notification badge
     */
    updateNotificationBadge() {
        const unreadCount = this.notificationQueue.length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
            badge.style.display = unreadCount > 0 ? 'block' : 'none';
        }
    }

    /**
     * Convert VAPID key
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Get notification settings
     */
    getSettings() {
        return { ...this.settings };
    }

    /**
     * Update notification settings
     */
    updateSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        this.saveSettings();
    }

    /**
     * Test notification
     */
    async testNotification() {
        await this.showNotification('Test Notification', {
            body: 'This is a test notification from the Bell Timing System',
            type: 'info',
            priority: 'normal'
        });
    }
}

// Initialize mobile notification service
window.mobileNotificationService = new MobileNotificationService();