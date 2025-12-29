/**
 * Service Worker para PWA do Gerente
 * Gerencia cache e funcionalidade offline
 */

const CACHE_NAME = 'lactech-manager-v3';
const RUNTIME_CACHE = 'lactech-runtime-v3';
const IMAGE_CACHE = 'lactech-images-v3';
const OFFLINE_PAGE = '/gerente-completo.php';

// Versão do cache - incrementar quando houver mudanças significativas
const CACHE_VERSION = 3;

// Arquivos estáticos críticos para cache inicial
const STATIC_CACHE_FILES = [
    '/gerente-completo.php',
    '/assets/js/gerente-completo.js',
    '/assets/js/offline-manager.js',
    '/manifest.json',
    '/assets/img/lactech-logo.png'
];

// Recursos que devem ser cacheados em runtime
const RUNTIME_CACHE_PATTERNS = [
    /^\/api\//,
    /^\/assets\//,
    /\.(?:js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/
];

// Recursos que nunca devem ser cacheados
const NO_CACHE_PATTERNS = [
    /chrome-extension:/,
    /\/api\/.*\/delete/,
    /\/api\/.*\/delete_all/
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Instalando versão', CACHE_VERSION);
    event.waitUntil(
        Promise.all([
            // Cache de arquivos estáticos
            caches.open(CACHE_NAME).then((cache) => {
                return Promise.allSettled(
                    STATIC_CACHE_FILES.map(url => {
                        return cache.add(url).catch(err => {
                            console.warn('[Service Worker] Não foi possível cachear:', url, err);
                        });
                    })
                );
            }),
            // Criar caches adicionais
            caches.open(RUNTIME_CACHE),
            caches.open(IMAGE_CACHE)
        ]).then(() => {
            console.log('[Service Worker] Cache instalado com sucesso');
        }).catch((err) => {
            console.error('[Service Worker] Erro ao instalar cache:', err);
        })
    );
    self.skipWaiting(); // Ativar imediatamente
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Ativando versão', CACHE_VERSION);
    event.waitUntil(
        Promise.all([
            // Limpar caches antigos
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            return cacheName !== CACHE_NAME && 
                                   cacheName !== RUNTIME_CACHE && 
                                   cacheName !== IMAGE_CACHE;
                        })
                        .map((cacheName) => {
                            console.log('[Service Worker] Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            }),
            // Assumir controle imediato de todas as páginas
            self.clients.claim()
        ])
    );
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignorar requisições de chrome-extension
    if (url.protocol.includes('chrome-extension')) {
        return;
    }

    // Tratar requisições para APIs
    if (url.pathname.startsWith('/api/')) {
        // Verificar se é requisição de registro (POST)
        if (request.method === 'POST') {
            // Verificar se está em modo offline forçado
            const forceOffline = self.forceOffline || false;
            
            if (forceOffline) {
                // Modo offline forçado - retornar sucesso imediatamente
                // O offline-manager.js vai gerenciar a fila
                return event.respondWith(
                    new Response(
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
                    )
                );
            }
            
            // Tentar fazer requisição real primeiro
            event.respondWith(
                fetch(request.clone())
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
            // Para GET, usar estratégia Network First com fallback para cache
            event.respondWith(
                fetch(request.clone())
                    .then((response) => {
                        // Armazenar resposta no cache para uso offline (apenas se não for chrome-extension)
                        if (response.ok && !url.protocol.includes('chrome-extension')) {
                            const responseClone = response.clone();
                            caches.open(RUNTIME_CACHE).then((cache) => {
                                cache.put(request, responseClone).catch(() => {
                                    // Ignorar erros de cache
                                });
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

    // Verificar se deve cachear este recurso
    const shouldCache = RUNTIME_CACHE_PATTERNS.some(pattern => pattern.test(url.pathname)) &&
                       !NO_CACHE_PATTERNS.some(pattern => pattern.test(request.url)) &&
                       request.method === 'GET';

    // Determinar qual cache usar
    const isImage = /\.(?:png|jpg|jpeg|gif|svg|webp)$/i.test(url.pathname);
    const cacheToUse = isImage ? IMAGE_CACHE : RUNTIME_CACHE;

    // Para outros recursos, usar estratégia Network First com fallback para cache
    event.respondWith(
        fetch(request.clone())
            .then((response) => {
                // Se a resposta é válida e deve ser cacheada, armazenar
                if (response.ok && shouldCache && !url.protocol.includes('chrome-extension')) {
                    const responseClone = response.clone();
                    caches.open(cacheToUse).then((cache) => {
                        cache.put(request, responseClone).catch((err) => {
                            console.warn('[Service Worker] Erro ao cachear:', request.url, err);
                        });
                    });
                }
                return response;
            })
            .catch(() => {
                // Se a requisição falhar, tentar buscar do cache
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        console.log('[Service Worker] Servindo do cache:', request.url);
                        return cachedResponse;
                    }
                    // Se não encontrar no cache e for uma página HTML, redirecionar para offline
                    if (request.headers.get('accept') && request.headers.get('accept').includes('text/html')) {
                        return caches.match(OFFLINE_PAGE).then((offlinePage) => {
                            return offlinePage || new Response('Modo offline - Página não disponível', {
                                status: 503,
                                statusText: 'Service Unavailable',
                                headers: { 'Content-Type': 'text/html' }
                            });
                        });
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
        // Notificar todos os clientes
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({ type: 'OFFLINE_MODE_ACTIVATED' });
            });
        });
    }
    
    if (event.data && event.data.type === 'FORCE_ONLINE') {
        // Voltar ao modo normal
        self.forceOffline = false;
        // Notificar todos os clientes
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({ type: 'ONLINE_MODE_ACTIVATED' });
            });
        });
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        // Limpar todos os caches
        caches.keys().then(cacheNames => {
            return Promise.all(cacheNames.map(cacheName => caches.delete(cacheName)));
        }).then(() => {
            console.log('[Service Worker] Todos os caches foram limpos');
        });
    }
});

// Background Sync (quando disponível)
if ('sync' in self.registration) {
    self.addEventListener('sync', (event) => {
        if (event.tag === 'sync-offline-queue') {
            event.waitUntil(
                // Notificar cliente para sincronizar
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({ type: 'SYNC_REQUESTED' });
                    });
                })
            );
        }
    });
}

