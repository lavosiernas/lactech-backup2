// Service Worker para Web Push Notifications
const CACHE_NAME = 'lactech-maintenance-v1';
const urlsToCache = [
    '/',
    '/index.php',
    '/assets/css/style.css'
];

// Install event
self.addEventListener('install', function(event) {
    console.log('Service Worker: Install');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Service Worker: Caching files');
                return cache.addAll(urlsToCache);
            })
    );
});

// Activate event
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activate');
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Clearing old cache');
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Push event - NOTIFICAÇÕES NATIVAS
self.addEventListener('push', function(event) {
    console.log('Service Worker: Push received');
    
    let data = {
        title: 'LacTech - Sistema em Manutenção',
        body: 'Sistema ainda em manutenção. Continue acompanhando!',
        icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
        badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
        tag: 'lactech-maintenance',
        requireInteraction: true,
        actions: [
            {
                action: 'open',
                title: 'Abrir Sistema',
                icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png'
            }
        ]
    };
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        requireInteraction: data.requireInteraction,
        actions: data.actions,
        data: {
            url: '/index.php'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    console.log('Service Worker: Notification click received');
    
    event.notification.close();
    
    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow('/index.php')
        );
    } else if (event.action === 'close') {
        // Apenas fechar a notificação
        return;
    } else {
        // Click na notificação (não em ações)
        event.waitUntil(
            clients.openWindow('/index.php')
        );
    }
});

// Message event - receber mensagens do JavaScript
self.addEventListener('message', function(event) {
    console.log('🔔 Service Worker: Message received', event.data);
    
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        const { title, body, icon, badge, tag, requireInteraction, actions } = event.data;
        
        console.log('📱 Service Worker: Creating notification:', title);
        
        const options = {
            body: body,
            icon: icon,
            badge: badge,
            tag: tag,
            requireInteraction: requireInteraction,
            actions: actions,
            data: {
                url: '/index.php'
            }
        };
        
        event.waitUntil(
            self.registration.showNotification(title, options)
                .then(() => {
                    console.log('✅ Service Worker: Notification sent successfully');
                })
                .catch((error) => {
                    console.error('❌ Service Worker: Error sending notification:', error);
                })
        );
    }
});

// Background sync para notificações
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        console.log('Service Worker: Background sync');
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    return fetch('/api/notifications/sync')
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.notification) {
                return self.registration.showNotification(data.title, {
                    body: data.body,
                    icon: data.icon,
                    badge: data.badge,
                    tag: data.tag
                });
            }
        })
        .catch(function(error) {
            console.log('Background sync failed:', error);
        });
}