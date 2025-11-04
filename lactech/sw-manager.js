/**
 * Service Worker para PWA do Gerente
 * Gerencia cache e funcionalidade offline
 */

const CACHE_NAME = 'lactech-manager-v1';
const RUNTIME_CACHE = 'lactech-runtime-v1';
const OFFLINE_PAGE = '/gerente-completo.php';

// Arquivos estáticos para cache
const STATIC_CACHE_FILES = [
    '/gerente-completo.php',
    '/assets/js/gerente-completo.js',
    '/assets/js/offline-manager.js',
    '/assets/css/styles.css',
    'https://cdn.tailwindcss.com'
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Instalando...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Armazenando arquivos em cache');
                return cache.addAll(STATIC_CACHE_FILES);
            })
            .catch((err) => {
                console.error('[Service Worker] Erro ao armazenar cache:', err);
            })
    );
    self.skipWaiting(); // Ativar imediatamente
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Ativando...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => {
                        return cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE;
                    })
                    .map((cacheName) => {
                        console.log('[Service Worker] Removendo cache antigo:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        })
    );
    return self.clients.claim(); // Assumir controle imediato
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorar requisições para APIs
    if (url.pathname.startsWith('/api/')) {
        // Verificar se é requisição de registro (POST)
        if (request.method === 'POST') {
            // Tentar fazer requisição real primeiro
            event.respondWith(
                fetch(request)
                    .then((response) => {
                        // Se sucesso, retornar resposta normal
                        if (response.ok) {
                            return response;
                        }
                        // Se erro, verificar se é offline ou erro de servidor
                        throw new Error('HTTP ' + response.status);
                    })
                    .catch((error) => {
                        // Se falhar (offline ou erro), retornar resposta simulada de sucesso offline
                        // O offline-manager.js vai gerenciar a fila
                        return new Response(
                            JSON.stringify({
                                success: true,
                                offline: true,
                                message: 'Registro salvo localmente. Será sincronizado quando a conexão for restaurada.'
                            }),
                            {
                                status: 200,
                                statusText: 'OK',
                                headers: { 'Content-Type': 'application/json' }
                            }
                        );
                    })
            );
        } else {
            // Para GET, tentar cache primeiro
            event.respondWith(
                fetch(request)
                    .then((response) => {
                        // Armazenar resposta no cache para uso offline
                        if (response.ok) {
                            const responseClone = response.clone();
                            caches.open(RUNTIME_CACHE).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Tentar buscar do cache
                        return caches.match(request).then((cachedResponse) => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            // Se não encontrar no cache, retornar resposta vazia
                            return new Response(
                                JSON.stringify({ success: false, error: 'Modo offline' }),
                                {
                                    status: 503,
                                    statusText: 'Service Unavailable',
                                    headers: { 'Content-Type': 'application/json' }
                                }
                            );
                        });
                    })
            );
        }
        return;
    }

    // Para outros recursos, usar estratégia Network First com fallback para cache
    event.respondWith(
        fetch(request)
            .then((response) => {
                // Se a resposta é válida, armazenar no cache
                if (response.ok && request.method === 'GET') {
                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Se a requisição falhar, tentar buscar do cache
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Se não encontrar no cache e for uma página HTML, redirecionar para offline
                    if (request.headers.get('accept').includes('text/html')) {
                        return caches.match(OFFLINE_PAGE);
                    }
                    return new Response('Recurso não disponível offline', {
                        status: 503,
                        statusText: 'Service Unavailable'
                    });
                });
            })
    );
});

// Ouvir mensagens do cliente (para controle manual do modo offline)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'FORCE_OFFLINE') {
        // Modo offline forçado - sempre retornar sucesso para POSTs
        self.forceOffline = true;
    }
    
    if (event.data && event.data.type === 'FORCE_ONLINE') {
        // Voltar ao modo normal
        self.forceOffline = false;
    }
});

