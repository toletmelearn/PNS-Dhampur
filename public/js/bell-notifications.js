/**
 * Bell Timing System - Push Notifications & Real-time Features
 */

class BellNotificationSystem {
    constructor() {
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.isSubscribed = false;
        this.swRegistration = null;
        this.currentTime = null;
        this.scheduleData = null;
        this.preferences = {
            bell_notifications: true,
            period_changes: true,
            emergency_alerts: true,
            sound_enabled: true,
            vibration_enabled: true,
            show_on_lock_screen: true
        };
        
        this.init();
    }

    async init() {
        if (!this.isSupported) {
            console.warn('Push notifications are not supported in this browser');
            return;
        }

        try {
            // Register service worker
            await this.registerServiceWorker();
            
            // Load preferences
            await this.loadPreferences();
            
            // Initialize real-time clock
            this.initRealTimeClock();
            
            // Start checking for notifications
            this.startNotificationCheck();
            
            // Initialize UI event listeners
            this.initEventListeners();
            
            console.log('Bell Notification System initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Bell Notification System:', error);
        }
    }

    async registerServiceWorker() {
        try {
            this.swRegistration = await navigator.serviceWorker.register('/js/sw.js');
            console.log('Service Worker registered successfully');
            
            // Check if already subscribed
            const subscription = await this.swRegistration.pushManager.getSubscription();
            this.isSubscribed = !(subscription === null);
            
            if (this.isSubscribed) {
                console.log('User is already subscribed to push notifications');
            }
        } catch (error) {
            console.error('Service Worker registration failed:', error);
        }
    }

    async subscribeToPushNotifications() {
        if (!this.swRegistration) {
            console.error('Service Worker not registered');
            return false;
        }

        try {
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.getVapidPublicKey())
            });

            // Send subscription to server
            const response = await fetch('/api/bell-notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')))),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))))
                    }
                })
            });

            if (response.ok) {
                this.isSubscribed = true;
                console.log('Successfully subscribed to push notifications');
                this.showNotification('Success', 'Push notifications enabled successfully!', 'success');
                return true;
            } else {
                throw new Error('Failed to send subscription to server');
            }
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
            this.showNotification('Error', 'Failed to enable push notifications', 'error');
            return false;
        }
    }

    async unsubscribeFromPushNotifications() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
                
                // Notify server
                await fetch('/api/bell-notifications/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                this.isSubscribed = false;
                console.log('Successfully unsubscribed from push notifications');
                this.showNotification('Success', 'Push notifications disabled', 'info');
            }
        } catch (error) {
            console.error('Failed to unsubscribe from push notifications:', error);
        }
    }

    initRealTimeClock() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);
    }

    async updateClock() {
        try {
            const response = await fetch('/api/bell-notifications/schedule-status');
            const data = await response.json();
            
            if (data.success) {
                this.scheduleData = data.data;
                this.updateClockDisplay();
                this.updateScheduleDisplay();
                this.checkForPeriodChanges();
            }
        } catch (error) {
            console.error('Failed to fetch schedule status:', error);
        }
    }

    updateClockDisplay() {
        const clockElement = document.getElementById('real-time-clock');
        const dateElement = document.getElementById('current-date');
        const seasonElement = document.getElementById('current-season');
        
        if (clockElement && this.scheduleData) {
            clockElement.textContent = this.scheduleData.current_time;
        }
        
        if (dateElement && this.scheduleData) {
            dateElement.textContent = this.scheduleData.current_date;
        }
        
        if (seasonElement && this.scheduleData) {
            seasonElement.textContent = this.scheduleData.current_season.toUpperCase();
            seasonElement.className = `season-badge ${this.scheduleData.current_season}`;
        }
    }

    updateScheduleDisplay() {
        if (!this.scheduleData) return;

        // Update current period
        const currentPeriodElement = document.getElementById('current-period');
        if (currentPeriodElement) {
            if (this.scheduleData.current_period) {
                currentPeriodElement.innerHTML = `
                    <div class="period-info">
                        <h4>${this.scheduleData.current_period.name}</h4>
                        <p class="period-time">${this.scheduleData.current_period.time}</p>
                    </div>
                `;
            } else {
                currentPeriodElement.innerHTML = '<div class="no-period">No current period</div>';
            }
        }

        // Update next period
        const nextPeriodElement = document.getElementById('next-period');
        if (nextPeriodElement) {
            if (this.scheduleData.next_period) {
                const minutesToNext = this.scheduleData.minutes_to_next_bell;
                nextPeriodElement.innerHTML = `
                    <div class="period-info">
                        <h4>Next: ${this.scheduleData.next_period.name}</h4>
                        <p class="period-time">${this.scheduleData.next_period.time}</p>
                        ${minutesToNext !== null ? `<p class="countdown">in ${minutesToNext} minutes</p>` : ''}
                    </div>
                `;
            } else {
                nextPeriodElement.innerHTML = '<div class="no-period">No upcoming period</div>';
            }
        }

        // Update progress bar
        const progressElement = document.getElementById('period-progress');
        if (progressElement && this.scheduleData.period_progress !== null) {
            progressElement.style.width = `${this.scheduleData.period_progress}%`;
            progressElement.setAttribute('data-progress', `${this.scheduleData.period_progress}%`);
        }

        // Update school status
        const statusElement = document.getElementById('school-status');
        if (statusElement) {
            const status = this.scheduleData.is_school_hours ? 'School Hours' : 'After Hours';
            const statusClass = this.scheduleData.is_school_hours ? 'active' : 'inactive';
            statusElement.innerHTML = `<span class="status-badge ${statusClass}">${status}</span>`;
        }
    }

    checkForPeriodChanges() {
        if (!this.scheduleData || !this.preferences.period_changes) return;

        // Check if we're approaching a bell time (5 minutes before)
        const minutesToNext = this.scheduleData.minutes_to_next_bell;
        if (minutesToNext === 5) {
            this.showPeriodChangeWarning();
        } else if (minutesToNext === 1) {
            this.showPeriodChangeImminent();
        }
    }

    showPeriodChangeWarning() {
        if (this.preferences.sound_enabled) {
            this.playNotificationSound('warning');
        }
        
        this.showNotification(
            'Period Change Soon',
            `${this.scheduleData.next_period.name} starts in 5 minutes`,
            'warning'
        );
    }

    showPeriodChangeImminent() {
        if (this.preferences.sound_enabled) {
            this.playNotificationSound('bell');
        }
        
        this.showNotification(
            'Period Starting',
            `${this.scheduleData.next_period.name} is starting now!`,
            'info'
        );
    }

    async startNotificationCheck() {
        // Check for notifications every 30 seconds
        setInterval(async () => {
            await this.checkBellNotifications();
        }, 30000);
        
        // Initial check
        await this.checkBellNotifications();
    }

    async checkBellNotifications() {
        try {
            const response = await fetch('/api/bell-notifications/check');
            const data = await response.json();
            
            if (data.success && data.data.should_ring) {
                this.handleBellNotifications(data.data.bells_to_ring);
            }
        } catch (error) {
            console.error('Failed to check bell notifications:', error);
        }
    }

    handleBellNotifications(bells) {
        bells.forEach(bell => {
            if (this.preferences.sound_enabled) {
                this.playNotificationSound('bell');
            }
            
            if (this.preferences.vibration_enabled && 'vibrate' in navigator) {
                navigator.vibrate([200, 100, 200]);
            }
            
            this.showNotification(
                bell.name,
                `${bell.name} - ${bell.time}`,
                'info'
            );
        });
    }

    playNotificationSound(type = 'bell') {
        if (!this.preferences.sound_enabled) return;
        
        const audio = new Audio();
        switch (type) {
            case 'bell':
                audio.src = '/sounds/school-bell.mp3';
                break;
            case 'warning':
                audio.src = '/sounds/warning-beep.mp3';
                break;
            case 'notification':
                audio.src = '/sounds/notification.mp3';
                break;
            default:
                audio.src = '/sounds/default-bell.mp3';
        }
        
        audio.volume = 0.7;
        audio.play().catch(error => {
            console.warn('Could not play notification sound:', error);
        });
    }

    showNotification(title, message, type = 'info') {
        // Show browser notification if supported and subscribed
        if (this.isSupported && this.isSubscribed && this.preferences.bell_notifications) {
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: message,
                    icon: '/images/school-bell-icon.png',
                    badge: '/images/bell-badge.png',
                    tag: 'bell-notification',
                    requireInteraction: type === 'warning' || type === 'error',
                    silent: !this.preferences.sound_enabled
                });
            }
        }
        
        // Show in-app notification
        this.showInAppNotification(title, message, type);
    }

    showInAppNotification(title, message, type) {
        const container = document.getElementById('notification-container') || this.createNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <h4 class="notification-title">${title}</h4>
                <p class="notification-message">${message}</p>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
        return container;
    }

    async loadPreferences() {
        try {
            const response = await fetch('/api/bell-notifications/preferences');
            const data = await response.json();
            
            if (data.success) {
                this.preferences = { ...this.preferences, ...data.data };
            }
        } catch (error) {
            console.error('Failed to load preferences:', error);
        }
    }

    async updatePreferences(newPreferences) {
        try {
            const response = await fetch('/api/bell-notifications/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(newPreferences)
            });
            
            const data = await response.json();
            if (data.success) {
                this.preferences = { ...this.preferences, ...newPreferences };
                console.log('Preferences updated successfully');
            }
        } catch (error) {
            console.error('Failed to update preferences:', error);
        }
    }

    initEventListeners() {
        // Push notification toggle
        const pushToggle = document.getElementById('push-notifications-toggle');
        if (pushToggle) {
            pushToggle.addEventListener('change', async (e) => {
                if (e.target.checked) {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        await this.subscribeToPushNotifications();
                    } else {
                        e.target.checked = false;
                        this.showNotification('Permission Denied', 'Push notifications permission was denied', 'error');
                    }
                } else {
                    await this.unsubscribeFromPushNotifications();
                }
            });
        }

        // Preference toggles
        const preferenceToggles = document.querySelectorAll('.preference-toggle');
        preferenceToggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                const preference = e.target.dataset.preference;
                if (preference) {
                    this.updatePreferences({ [preference]: e.target.checked });
                }
            });
        });

        // Test notification button
        const testButton = document.getElementById('test-notification-btn');
        if (testButton) {
            testButton.addEventListener('click', () => {
                this.showNotification('Test Notification', 'This is a test notification from the bell timing system', 'info');
                if (this.preferences.sound_enabled) {
                    this.playNotificationSound('notification');
                }
            });
        }
    }

    // Utility functions
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

    getVapidPublicKey() {
        // This should be your VAPID public key
        // For now, using a placeholder - replace with actual key
        return 'BEl62iUYgUivxIkv69yViEuiBIa40HI0DLLuxazjqAKVXTJtkKGlXCB3BvI4xfilR1ilLx1vDFGTzHriA3jjBw8';
    }
}

// Initialize the system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.bellNotificationSystem = new BellNotificationSystem();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BellNotificationSystem;
}