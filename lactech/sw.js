// =====================================================
// SERVICE WORKER - LACTECH SYSTEM
// PWA + Push Notifications + Offline Support
// Versão 2.0 - Superior ao FarmTell
// =====================================================

const CACHE_NAME = 'lactech-v2.0.0';
const STATIC_CACHE = 'lactech-static-v2';
const DYNAMIC_CACHE = 'lactech-dynamic-v2';

// Critical resources to cache immediately
const CRITICAL_RESOURCES = [
    '/',
    '/gerente.php',
    '/funcionario.php',
    '/veterinario.php',
    '/proprietario.php',
    '/assets/css/critical.css',
    '/assets/css/style.css',
    '/assets/css/dark-theme-fixes.css',
    // '/assets/css/loading-screen.css', DESABILITADO
    '/assets/js/config_mysql.js',
    '/assets/js/performance-optimizer.js',
    // '/assets/js/loading-screen.js', DESABILITADO
    '/assets/img/lactech-logo.png'
];

// Resources to cache on demand
const CACHE_ON_DEMAND = [
    '/api/',
    '/assets/js/',
    '/assets/css/',
    '/assets/img/'
];

// Install event - cache critical resources
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('📦 Caching critical resources');
                return cache.addAll(CRITICAL_RESOURCES);
            })
            .then(() => {
                console.log('✅ Critical resources cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('❌ Failed to cache critical resources:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('🗑️ Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('✅ Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip external requests
    if (url.origin !== location.origin) {
        return;
    }
    
    event.respondWith(
        caches.match(request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Fetch from network and cache
                return fetch(request)
                    .then((networkResponse) => {
                        // Don't cache non-successful responses
                        if (!networkResponse || networkResponse.status !== 200) {
                            return networkResponse;
                        }
                        
                        // Clone response for caching
                        const responseToCache = networkResponse.clone();
                        
                        // Cache based on resource type
                        if (shouldCache(request)) {
                            caches.open(getCacheName(request))
                                .then((cache) => {
                                    cache.put(request, responseToCache);
                                });
                        }
                        
                        return networkResponse;
                    })
                    .catch((error) => {
                        console.error('❌ Network request failed:', error);
                        
                        // Return offline page for navigation requests
                        if (request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                        
                        throw error;
                    });
            })
    );
});

// Determine if request should be cached
function shouldCache(request) {
    const url = new URL(request.url);
    
    // Cache API responses
    if (url.pathname.startsWith('/api/')) {
        return true;
    }
    
    // Cache static assets
    if (url.pathname.startsWith('/assets/')) {
        return true;
    }
    
    // Cache HTML pages
    if (url.pathname.endsWith('.php') || url.pathname === '/') {
        return true;
    }
    
    return false;
}

// Get appropriate cache name
function getCacheName(request) {
    const url = new URL(request.url);
    
    // Static assets go to static cache
    if (url.pathname.startsWith('/assets/')) {
        return STATIC_CACHE;
    }
    
    // Dynamic content goes to dynamic cache
    return DYNAMIC_CACHE;
}

// Background sync for offline data
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        console.log('🔄 Background sync triggered');
        
        event.waitUntil(
            // Sync offline data when connection is restored
            syncOfflineData()
        );
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    console.log('📱 Push notification received');
    
    const options = {
        body: event.data ? event.data.text() : 'Nova atualização disponível',
        icon: '/assets/img/lactech-logo.png',
        badge: '/assets/img/lactech-logo.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Ver detalhes',
                icon: '/assets/img/lactech-logo.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: '/assets/img/lactech-logo.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('LacTech System', options)
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    console.log('🔔 Notification clicked:', event.action);
    
    event.notification.close();
    
    const urlToOpen = event.notification.data?.url || '/gerente.php';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Verificar se já há uma janela aberta
                for (const client of windowClients) {
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Se não, abrir nova janela
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Push notification handler
self.addEventListener('push', (event) => {
    console.log('📩 Push notification received');
    
    let data = { title: 'LacTech', body: 'Nova notificação', icon: '/assets/img/lactech-logo.png' };
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body || 'Você tem uma nova notificação',
        icon: data.icon || '/assets/img/lactech-logo.png',
        badge: '/assets/img/lactech-logo.png',
        vibrate: [200, 100, 200],
        tag: data.tag || 'lactech-notification',
        requireInteraction: data.priority === 'urgent' || data.priority === 'critical',
        data: {
            url: data.url || '/gerente.php',
            timestamp: Date.now()
        },
        actions: [
            {
                action: 'view',
                title: 'Ver Agora',
                icon: '/assets/img/lactech-logo.png'
            },
            {
                action: 'dismiss',
                title: 'Dispensar'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'LacTech', options)
    );
});

// Helper function to sync offline data
async function syncOfflineData() {
    try {
        // Get offline data from IndexedDB
        const offlineData = await getOfflineData();
        
        if (offlineData.length > 0) {
            console.log('📤 Syncing offline data:', offlineData.length, 'items');
            
            // Send data to server
            for (const item of offlineData) {
                try {
                    await fetch(item.url, {
                        method: item.method,
                        headers: item.headers,
                        body: item.body
                    });
                    
                    // Remove from offline storage after successful sync
                    await removeOfflineData(item.id);
                } catch (error) {
                    console.error('❌ Failed to sync item:', error);
                }
            }
        }
    } catch (error) {
        console.error('❌ Background sync failed:', error);
    }
}

// Placeholder functions for offline data management
async function getOfflineData() {
    // Implementation would depend on your offline storage solution
    return [];
}

async function removeOfflineData(id) {
    // Implementation would depend on your offline storage solution
    return true;
}

console.log('✅ Service Worker loaded successfully');
