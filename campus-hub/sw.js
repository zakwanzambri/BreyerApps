// Campus Hub Service Worker - v1.0
// Progressive Web App features for offline functionality

const CACHE_NAME = 'campus-hub-v1';
const URLS_TO_CACHE = [
    '/',
    '/campus-hub/',
    '/campus-hub/index.html',
    '/campus-hub/student-dashboard.html',
    '/campus-hub/user-login.html',
    '/campus-hub/news.html',
    '/campus-hub/services.html',
    '/campus-hub/academics.html',
    '/campus-hub/css/styles.css',
    '/campus-hub/css/full-integration.css',
    '/campus-hub/js/main.js',
    '/campus-hub/js/auth-manager.js',
    '/campus-hub/js/full-integration.js',
    '/campus-hub/js/header-functions.js',
    '/campus-hub/images/breyer-logo.png',
    '/campus-hub/images/favicon.ico'
];

// Install Service Worker
self.addEventListener('install', (event) => {
    console.log('🚀 Campus Hub Service Worker: Install event');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('📦 Campus Hub: Caching app shell');
                return cache.addAll(URLS_TO_CACHE);
            })
            .catch((error) => {
                console.error('❌ Campus Hub: Cache install failed:', error);
            })
    );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    console.log('✅ Campus Hub Service Worker: Activate event');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('🗑️ Campus Hub: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch Event - Network First with Cache Fallback
self.addEventListener('fetch', (event) => {
    const request = event.request;
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip API calls - always use network for fresh data
    if (request.url.includes('/api/') || request.url.includes('/php/')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful API responses for offline fallback
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Return cached version if network fails
                    return caches.match(request);
                })
        );
        return;
    }
    
    // For static resources - Cache First with Network Fallback
    event.respondWith(
        caches.match(request)
            .then((response) => {
                // Return cached version if available
                if (response) {
                    console.log('📱 Campus Hub: Serving from cache:', request.url);
                    return response;
                }
                
                // Fetch from network and cache
                return fetch(request)
                    .then((response) => {
                        // Don't cache if not a valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Cache the response
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // Return offline page for HTML requests
                        if (request.headers.get('accept').includes('text/html')) {
                            return caches.match('/campus-hub/index.html');
                        }
                    });
            })
    );
});

// Background Sync for notifications
self.addEventListener('sync', (event) => {
    if (event.tag === 'campus-hub-sync') {
        console.log('🔄 Campus Hub: Background sync triggered');
        event.waitUntil(syncData());
    }
});

// Sync function for offline data
async function syncData() {
    try {
        // Sync pending notifications when back online
        const cache = await caches.open(CACHE_NAME);
        const pendingRequests = await cache.keys();
        
        for (const request of pendingRequests) {
            if (request.url.includes('notifications')) {
                try {
                    await fetch(request);
                    console.log('✅ Campus Hub: Synced data for:', request.url);
                } catch (error) {
                    console.log('⚠️ Campus Hub: Sync failed for:', request.url);
                }
            }
        }
    } catch (error) {
        console.error('❌ Campus Hub: Background sync failed:', error);
    }
}

// Push notification handler
self.addEventListener('push', (event) => {
    console.log('🔔 Campus Hub: Push notification received');
    
    const options = {
        body: event.data ? event.data.text() : 'New notification from Campus Hub',
        icon: '/campus-hub/images/favicon.ico',
        badge: '/campus-hub/images/favicon.ico',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View Dashboard',
                icon: '/campus-hub/images/favicon.ico'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/campus-hub/images/favicon.ico'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('Campus Hub', options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    console.log('🔔 Campus Hub: Notification clicked');
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/campus-hub/student-dashboard.html')
        );
    }
});

console.log('🎉 Campus Hub Service Worker: Loaded successfully!');
