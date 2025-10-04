/**
 * Service Worker for Bell Timing System Push Notifications
 */

const CACHE_NAME = 'bell-timing-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/js/bell-notifications.js',
    '/sounds/school-bell.mp3',
    '/sounds/warning-beep.mp3',
    '/sounds/notification.mp3',
    '/images/school-bell-icon.png',
    '/images/bell-badge.png'
];

// Install event - cache resources
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('Push event received:', event);
    
    let notificationData = {
        title: 'Bell Timing Notification',
        body: 'A bell timing event has occurred',
        icon: '/images/school-bell-icon.png',
        badge: '/images/bell-badge.png',
        tag: 'bell-notification',
        requireInteraction: false,
        actions: [
            {
                action: 'view',
                title: 'View Schedule',
                icon: '/images/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/images/dismiss-icon.png'
            }
        ],
        data: {
            url: '/bell-timing',
            timestamp: Date.now()
        }
    };

    // Parse notification data if available
    if (event.data) {
        try {
            const pushData = event.data.json();
            notificationData = {
                ...notificationData,
                ...pushData
            };
        } catch (error) {
            console.error('Error parsing push data:', error);
            notificationData.body = event.data.text() || notificationData.body;
        }
    }

    // Determine notification options based on type
    if (notificationData.type) {
        switch (notificationData.type) {
            case 'bell':
                notificationData.requireInteraction = true;
                notificationData.silent = false;
                notificationData.vibrate = [200, 100, 200, 100, 200];
                break;
            case 'warning':
                notificationData.requireInteraction = true;
                notificationData.silent = false;
                notificationData.vibrate = [300, 200, 300];
                break;
            case 'emergency':
                notificationData.requireInteraction = true;
                notificationData.silent = false;
                notificationData.vibrate = [500, 200, 500, 200, 500];
                notificationData.tag = 'emergency-notification';
                break;
            case 'period-change':
                notificationData.requireInteraction = false;
                notificationData.silent = true;
                notificationData.vibrate = [100, 50, 100];
                break;
            default:
                notificationData.silent = true;
        }
    }

    const promiseChain = self.registration.showNotification(
        notificationData.title,
        notificationData
    );

    event.waitUntil(promiseChain);
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);
    
    event.notification.close();

    const action = event.action;
    const notificationData = event.notification.data || {};
    
    let urlToOpen = notificationData.url || '/bell-timing';

    // Handle different actions
    switch (action) {
        case 'view':
            urlToOpen = '/bell-timing';
            break;
        case 'dismiss':
            // Just close the notification
            return;
        default:
            // Default click action
            urlToOpen = notificationData.url || '/bell-timing';
    }

    // Open or focus the app window
    const promiseChain = clients.matchAll({
        type: 'window',
        includeUncontrolled: true
    }).then((clientList) => {
        // Check if there's already a window/tab open with the target URL
        for (let i = 0; i < clientList.length; i++) {
            const client = clientList[i];
            if (client.url.includes(urlToOpen) && 'focus' in client) {
                return client.focus();
            }
        }
        
        // If no existing window/tab, open a new one
        if (clients.openWindow) {
            return clients.openWindow(urlToOpen);
        }
    });

    event.waitUntil(promiseChain);
});

// Background sync for offline notifications
self.addEventListener('sync', (event) => {
    console.log('Background sync event:', event);
    
    if (event.tag === 'bell-notification-sync') {
        event.waitUntil(syncBellNotifications());
    }
});

// Sync bell notifications when back online
async function syncBellNotifications() {
    try {
        // Check for pending notifications
        const response = await fetch('/api/bell-notifications/pending');
        const data = await response.json();
        
        if (data.success && data.data.total_count > 0) {
            // Show notifications that were missed while offline
            data.data.web_notifications.forEach(notification => {
                self.registration.showNotification(notification.title, {
                    body: notification.message,
                    icon: '/images/school-bell-icon.png',
                    badge: '/images/bell-badge.png',
                    tag: 'sync-notification',
                    data: notification
                });
            });
        }
    } catch (error) {
        console.error('Error syncing notifications:', error);
    }
}

// Message event - handle messages from main thread
self.addEventListener('message', (event) => {
    console.log('Service worker received message:', event.data);
    
    if (event.data && event.data.type) {
        switch (event.data.type) {
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
        }
    }
});

// Clear old caches
async function clearCache() {
    const cacheNames = await caches.keys();
    const oldCaches = cacheNames.filter(name => name !== CACHE_NAME);
    
    return Promise.all(
        oldCaches.map(name => caches.delete(name))
    );
}

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service worker activated');
    
    event.waitUntil(
        clearCache().then(() => {
            return self.clients.claim();
        })
    );
});

// Periodic background sync (if supported)
self.addEventListener('periodicsync', (event) => {
    console.log('Periodic sync event:', event);
    
    if (event.tag === 'bell-schedule-update') {
        event.waitUntil(updateBellSchedule());
    }
});

// Update bell schedule in background
async function updateBellSchedule() {
    try {
        const response = await fetch('/api/bell-notifications/schedule-status');
        const data = await response.json();
        
        if (data.success) {
            // Store updated schedule data
            const cache = await caches.open(CACHE_NAME);
            await cache.put('/api/bell-schedule-cache', new Response(JSON.stringify(data)));
        }
    } catch (error) {
        console.error('Error updating bell schedule:', error);
    }
}

console.log('Bell Timing Service Worker loaded');