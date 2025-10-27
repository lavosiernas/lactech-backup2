/**
 * Service Worker - Lactech
 * Cache e funcionalidades offline
 */

const CACHE_NAME = 'lactech-v1';
const urlsToCache = [
    '/',
    '/gerente.php',
    '/assets/css/style.css',
    '/assets/css/tailwind.css',
    '/assets/js/gerente.js',
    '/assets/js/api-config.js',
    '/assets/js/gerente-api-fixes.js',
    '/assets/js/system-cleanup.js',
    '/assets/js/performance-optimizer.js',
    '/assets/js/console-guard.js',
    '/assets/js/native-notifications.js',
    '/assets/js/offline-manager.js',
    '/assets/js/offline-sync.js',
    '/assets/js/pdf-generator.js',
    '/assets/js/push-notifications.js',
    '/assets/img/lactech-logo.png'
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker instalando...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('📦 Cache aberto');
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                console.log('✅ Service Worker instalado');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('❌ Erro ao instalar Service Worker:', error);
            })
    );
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker ativando...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('🗑️ Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
        }).then(() => {
            console.log('✅ Service Worker ativado');
                return self.clients.claim();
            })
    );
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
    // Ignorar requisições que não são GET
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Ignorar requisições de API
    if (event.request.url.includes('/api/')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Retornar do cache se disponível
                if (response) {
                    console.log('📦 Servindo do cache:', event.request.url);
                    return response;
                }
                
                // Buscar da rede
                return fetch(event.request).then((response) => {
                    // Verificar se a resposta é válida
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clonar a resposta
                    const responseToCache = response.clone();
                    
                    // Adicionar ao cache
                    caches.open(CACHE_NAME)
                                .then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                    });
            })
            .catch(() => {
                // Retornar página offline se disponível
                if (event.request.destination === 'document') {
                    return caches.match('/gerente.php');
                }
            })
    );
});

// Interceptar mensagens push
self.addEventListener('push', (event) => {
    console.log('🔔 Push message recebida');
    
    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = { title: 'Lactech', body: event.data.text() };
        }
    }
    
    const options = {
        body: data.body || 'Nova notificação',
        icon: data.icon || '/assets/img/lactech-logo.png',
        badge: data.badge || '/assets/img/lactech-logo.png',
        tag: data.tag || 'lactech-push',
        data: data.data || {},
        actions: data.actions || []
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Lactech', options)
    );
});

// Interceptar clique em notificação
self.addEventListener('notificationclick', (event) => {
    console.log('👆 Notificação clicada');
    
    event.notification.close();
    
    if (event.action) {
        // Tratar ação específica
        console.log('Ação clicada:', event.action);
    } else {
        // Abrir a aplicação
        event.waitUntil(
            clients.openWindow('/gerente.php')
        );
    }
});

// Interceptar fechamento de notificação
self.addEventListener('notificationclose', (event) => {
    console.log('❌ Notificação fechada');
});

// Interceptar mensagens do cliente
self.addEventListener('message', (event) => {
    console.log('📨 Mensagem recebida:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Interceptar erros
self.addEventListener('error', (event) => {
    console.error('❌ Erro no Service Worker:', event.error);
});

// Interceptar rejeições de promessa
self.addEventListener('unhandledrejection', (event) => {
    console.error('❌ Promessa rejeitada no Service Worker:', event.reason);
});

console.log('🔧 Service Worker carregado');