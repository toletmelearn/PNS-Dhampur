/**
 * Service Worker for Bell Timing Notifications
 * Handles push notifications, background sync, and offline functionality
 */

const CACHE_NAME = 'bell-timing-v1';
const urlsToCache = [
    '/',
    '/css/bell-timing.css',
    '/js/mobile-notification-service.js',
    '/js/sound-manager.js',
    '/js/bell-notifications.js',
    '/images/bell-icon.png',
    '/images/bell-badge.png',
    '/sounds/notification.mp3'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch(error => {
                console.error('Cache installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
            .catch(() => {
                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
            })
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', event => {
    console.log('Push event received:', event);
    
    let notificationData = {};
    
    if (event.data) {
        try {
            notificationData = event.data.json();
        } catch (error) {
            console.error('Error parsing push data:', error);
            notificationData = {
                title: 'Bell Timing Notification',
                body: event.data.text() || 'New notification received'
            };
        }
    }

    const options = {
        body: notificationData.body || 'Bell timing update',
        icon: notificationData.icon || '/images/bell-icon.png',
        badge: notificationData.badge || '/images/bell-badge.png',
        vibrate: getVibrationPattern(notificationData.type),
        data: {
            url: notificationData.url || '/',
            type: notificationData.type || 'info',
            timestamp: Date.now(),
            ...notificationData.data
        },
        actions: getNotificationActions(notificationData.type),
        requireInteraction: notificationData.priority === 'high',
        silent: notificationData.silent || false,
        tag: notificationData.tag || 'bell-timing',
        renotify: notificationData.renotify || false
    };

    event.waitUntil(
        self.registration.showNotification(
            notificationData.title || 'Bell Timing System',
            options
        )
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    const notificationData = event.notification.data || {};
    const action = event.action;
    
    event.waitUntil(
        handleNotificationClick(action, notificationData)
    );
});

// Notification close event
self.addEventListener('notificationclose', event => {
    console.log('Notification closed:', event);
    
    // Track notification dismissal
    const notificationData = event.notification.data || {};
    trackNotificationEvent('dismissed', notificationData);
});

// Background sync event
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'bell-timing-sync') {
        event.waitUntil(syncBellTimingData());
    }
});

// Message event - communication with main thread
self.addEventListener('message', event => {
    console.log('Service worker received message:', event.data);
    
    const { type, data } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
        case 'GET_VERSION':
            event.ports[0].postMessage({ version: CACHE_NAME });
            break;
        case 'CLEAR_CACHE':
            clearCache().then(() => {
                event.ports[0].postMessage({ success: true });
            });
            break;
        default:
            console.log('Unknown message type:', type);
    }
});

/**
 * Get vibration pattern based on notification type
 */
function getVibrationPattern(type) {
    const patterns = {
        bell: [200, 100, 200],
        warning: [100, 50, 100, 50, 100],
        emergency: [500, 200, 500, 200, 500],
        period: [150, 100, 150],
        break: [100, 50, 100],
        default: [100]
    };
    
    return patterns[type] || patterns.default;
}

/**
 * Get notification actions based on type
 */
function getNotificationActions(type) {
    const commonActions = [
        {
            action: 'view',
            title: 'View Details',
            icon: '/images/view-icon.png'
        }
    ];
    
    const typeSpecificActions = {
        bell: [
            {
                action: 'snooze',
                title: 'Snooze 5min',
                icon: '/images/snooze-icon.png'
            }
        ],
        emergency: [
            {
                action: 'acknowledge',
                title: 'Acknowledge',
                icon: '/images/ack-icon.png'
            }
        ],
        substitute: [
            {
                action: 'accept',
                title: 'Accept',
                icon: '/images/accept-icon.png'
            },
            {
                action: 'decline',
                title: 'Decline',
                icon: '/images/decline-icon.png'
            }
        ]
    };
    
    return [...commonActions, ...(typeSpecificActions[type] || [])];
}

/**
 * Handle notification click actions
 */
async function handleNotificationClick(action, notificationData) {
    const clients = await self.clients.matchAll({
        type: 'window',
        includeUncontrolled: true
    });
    
    let targetUrl = notificationData.url || '/';
    
    // Handle specific actions
    switch (action) {
        case 'view':
            targetUrl = notificationData.url || '/bell-schedule/dashboard';
            break;
        case 'snooze':
            await handleSnoozeAction(notificationData);
            return;
        case 'acknowledge':
            await handleAcknowledgeAction(notificationData);
            targetUrl = '/bell-schedule/dashboard';
            break;
        case 'accept':
        case 'decline':
            await handleSubstituteAction(action, notificationData);
            targetUrl = '/substitution/dashboard';
            break;
        default:
            // Default click behavior
            targetUrl = notificationData.url || '/bell-schedule/dashboard';
    }
    
    // Focus existing window or open new one
    const existingClient = clients.find(client => 
        client.url.includes(new URL(targetUrl, self.location.origin).pathname)
    );
    
    if (existingClient) {
        await existingClient.focus();
        existingClient.postMessage({
            type: 'NOTIFICATION_CLICKED',
            data: notificationData
        });
    } else {
        await self.clients.openWindow(targetUrl);
    }
    
    // Track notification interaction
    trackNotificationEvent('clicked', notificationData, action);
}

/**
 * Handle snooze action
 */
async function handleSnoozeAction(notificationData) {
    // Schedule a new notification in 5 minutes
    setTimeout(() => {
        self.registration.showNotification(
            'Snoozed Reminder',
            {
                body: 'This is your snoozed bell timing reminder',
                icon: '/images/bell-icon.png',
                badge: '/images/bell-badge.png',
                tag: 'snoozed-' + Date.now(),
                data: notificationData
            }
        );
    }, 5 * 60 * 1000); // 5 minutes
}

/**
 * Handle acknowledge action
 */
async function handleAcknowledgeAction(notificationData) {
    try {
        await fetch('/api/notifications/acknowledge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notification_id: notificationData.id,
                timestamp: Date.now()
            })
        });
    } catch (error) {
        console.error('Failed to acknowledge notification:', error);
    }
}

/**
 * Handle substitute action (accept/decline)
 */
async function handleSubstituteAction(action, notificationData) {
    try {
        await fetch('/api/substitution/respond', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                substitution_id: notificationData.substitution_id,
                action: action,
                timestamp: Date.now()
            })
        });
    } catch (error) {
        console.error('Failed to respond to substitution:', error);
    }
}

/**
 * Sync bell timing data in background
 */
async function syncBellTimingData() {
    try {
        // Fetch latest schedule data
        const scheduleResponse = await fetch('/api/bell-timings/schedule/enhanced');
        if (scheduleResponse.ok) {
            const scheduleData = await scheduleResponse.json();
            
            // Store in cache for offline access
            const cache = await caches.open(CACHE_NAME);
            await cache.put('/api/bell-timings/schedule/enhanced', 
                new Response(JSON.stringify(scheduleData))
            );
        }
        
        // Fetch pending notifications
        const notificationsResponse = await fetch('/api/notifications/pending');
        if (notificationsResponse.ok) {
            const notifications = await notificationsResponse.json();
            
            // Process any pending notifications
            for (const notification of notifications) {
                await self.registration.showNotification(
                    notification.title,
                    {
                        body: notification.body,
                        icon: notification.icon || '/images/bell-icon.png',
                        data: notification.data
                    }
                );
            }
        }
        
        console.log('Background sync completed successfully');
    } catch (error) {
        console.error('Background sync failed:', error);
        throw error; // This will cause the sync to be retried
    }
}

/**
 * Track notification events for analytics
 */
function trackNotificationEvent(event, notificationData, action = null) {
    // Send tracking data to server (fire and forget)
    fetch('/api/notifications/track', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            event: event,
            notification_type: notificationData.type,
            action: action,
            timestamp: Date.now(),
            user_agent: navigator.userAgent
        })
    }).catch(error => {
        console.error('Failed to track notification event:', error);
    });
}

/**
 * Clear all caches
 */
async function clearCache() {
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
    console.log('All caches cleared');
}

/**
 * Periodic cleanup of old cached data
 */
setInterval(async () => {
    try {
        const cache = await caches.open(CACHE_NAME);
        const requests = await cache.keys();
        const now = Date.now();
        const maxAge = 24 * 60 * 60 * 1000; // 24 hours
        
        for (const request of requests) {
            const response = await cache.match(request);
            const dateHeader = response.headers.get('date');
            
            if (dateHeader) {
                const responseDate = new Date(dateHeader).getTime();
                if (now - responseDate > maxAge) {
                    await cache.delete(request);
                    console.log('Deleted old cache entry:', request.url);
                }
            }
        }
    } catch (error) {
        console.error('Cache cleanup failed:', error);
    }
}, 60 * 60 * 1000); // Run every hour