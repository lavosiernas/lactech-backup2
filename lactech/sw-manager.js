/**
 * Service Worker para PWA do LacTech
 * Suporta Gerente e Funcionário com filtro de usuário
 * Gerencia cache e funcionalidade offline
 */

// Detectar tipo de usuário baseado na URL
function getUserType(url) {
    if (url.includes('gerente-completo.php')) {
        return 'manager';
    } else if (url.includes('funcionario.php')) {
        return 'employee';
    }
    return 'manager'; // Default
}

// Configurações por tipo de usuário
const USER_CONFIGS = {
    manager: {
        CACHE_NAME: 'lactech-manager-v2.1.0',
        RUNTIME_CACHE: 'lactech-runtime-v2.1.0',
        IMAGE_CACHE: 'lactech-images-v2.1.0',
        MAIN_PAGE: './gerente-completo.php',
        STATIC_FILES: [
            './assets/js/gerente-completo.js',
            './assets/js/offline-manager.js',
            './manifest.json',
            './assets/img/lactech-logo.png'
        ]
    },
    employee: {
        CACHE_NAME: 'lactech-employee-v2.1.0',
        RUNTIME_CACHE: 'lactech-runtime-employee-v2.1.0',
        IMAGE_CACHE: 'lactech-images-employee-v2.1.0',
        MAIN_PAGE: './funcionario.php',
        STATIC_FILES: [
            './assets/js/offline-manager.js',
            './manifest.json',
            './assets/img/lactech-logo.png'
        ]
    }
};

// Variável global para armazenar tipo de usuário atual
let currentUserType = 'manager';

// Versão do cache - incrementar quando houver mudanças significativas
const CACHE_VERSION = 6; // Versão 2.1.0 - Limpeza de cache melhorada

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
    /\/api\/.*\/delete_all/,
    /mais-opcoes\.php/,
    /relatorios\.php/,
    /config\.php/
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Instalando versão', CACHE_VERSION);
    
    // Detectar tipo de usuário baseado na URL do cliente
    event.waitUntil(
        self.clients.matchAll().then(clients => {
            if (clients.length > 0) {
                const clientUrl = clients[0].url;
                currentUserType = getUserType(clientUrl);
                console.log('[Service Worker] Tipo de usuário detectado:', currentUserType);
            }
            
            const config = USER_CONFIGS[currentUserType];
            
            return Promise.all([
                // Cache de arquivos estáticos
                caches.open(config.CACHE_NAME).then((cache) => {
                    return Promise.allSettled(
                        config.STATIC_FILES.map(url => {
                            return cache.add(url).catch(err => {
                                console.warn('[Service Worker] Não foi possível cachear:', url, err);
                            });
                        })
                    );
                }),
                // Criar caches adicionais para ambos os tipos
                caches.open(USER_CONFIGS.manager.RUNTIME_CACHE),
                caches.open(USER_CONFIGS.manager.IMAGE_CACHE),
                caches.open(USER_CONFIGS.employee.RUNTIME_CACHE),
                caches.open(USER_CONFIGS.employee.IMAGE_CACHE)
            ]).then(() => {
                console.log('[Service Worker] Cache instalado com sucesso para', currentUserType);
            }).catch((err) => {
                console.error('[Service Worker] Erro ao instalar cache:', err);
            });
        })
    );
    self.skipWaiting(); // Ativar imediatamente
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Ativando versão', CACHE_VERSION);
    event.waitUntil(
        Promise.all([
            // Limpar caches antigos (exceto os atuais de ambos os tipos)
            caches.keys().then((cacheNames) => {
                const validCaches = [
                    USER_CONFIGS.manager.CACHE_NAME,
                    USER_CONFIGS.manager.RUNTIME_CACHE,
                    USER_CONFIGS.manager.IMAGE_CACHE,
                    USER_CONFIGS.employee.CACHE_NAME,
                    USER_CONFIGS.employee.RUNTIME_CACHE,
                    USER_CONFIGS.employee.IMAGE_CACHE
                ];
                
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => !validCaches.includes(cacheName))
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
    
    // Detectar tipo de usuário baseado na URL da requisição ou referrer
    let userType = 'manager'; // Default
    if (url.pathname.includes('funcionario.php') || 
        (request.referrer && request.referrer.includes('funcionario.php'))) {
        userType = 'employee';
    } else if (url.pathname.includes('gerente-completo.php') || 
               (request.referrer && request.referrer.includes('gerente-completo.php'))) {
        userType = 'manager';
    }
    
    const config = USER_CONFIGS[userType];

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
                            message: 'Registro salvo localmente. Sera sincronizado quando a conexao for restaurada.'
                        }),
                        {
                            status: 200,
                            statusText: 'OK',
                            headers: { 
                                'Content-Type': 'application/json; charset=UTF-8'
                            }
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
                                message: 'Registro salvo localmente. Sera sincronizado quando a conexao for restaurada.'
                            }),
                            {
                                status: 200,
                                statusText: 'OK',
                                headers: { 
                                    'Content-Type': 'application/json; charset=UTF-8'
                                }
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
                            caches.open(config.RUNTIME_CACHE).then((cache) => {
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
                                    headers: { 
                                        'Content-Type': 'application/json; charset=UTF-8'
                                    }
                                }
                            );
                        });
                    })
            );
        }
        return;
    }

    // Verificar se deve cachear este recurso
    // Não cachear páginas PHP dinâmicas (exceto a principal quando offline)
    const isPHPPage = url.pathname.endsWith('.php') && 
                     !url.pathname.includes('gerente-completo.php') && 
                     !url.pathname.includes('funcionario.php');
    const shouldCache = !isPHPPage && 
                       RUNTIME_CACHE_PATTERNS.some(pattern => pattern.test(url.pathname)) &&
                       !NO_CACHE_PATTERNS.some(pattern => pattern.test(request.url)) &&
                       request.method === 'GET';

    // Determinar qual cache usar
    const isImage = /\.(?:png|jpg|jpeg|gif|svg|webp)$/i.test(url.pathname);
    const cacheToUse = isImage ? config.IMAGE_CACHE : config.RUNTIME_CACHE;

    // Para páginas HTML, verificar se é a página principal ou outra página
    const isHTML = request.headers.get('accept') && request.headers.get('accept').includes('text/html');
    const isMainPage = url.pathname.includes('gerente-completo.php') || 
                      url.pathname.includes('funcionario.php') || 
                      url.pathname === '/' || 
                      url.pathname.endsWith('/');
    
    if (isHTML) {
        // Para páginas diferentes da principal, usar Network First (não cachear navegação)
        if (!isMainPage) {
            event.respondWith(
                fetch(request.clone())
                    .then((response) => {
                        // Não cachear outras páginas PHP - permitir navegação normal
                        return response;
                    })
                    .catch(() => {
                        // Se offline e não for página principal, retornar erro
                        return new Response('Página não disponível offline', {
                            status: 503,
                            statusText: 'Service Unavailable',
                            headers: { 
                                'Content-Type': 'text/plain; charset=UTF-8'
                            }
                        });
                    })
            );
            return;
        }
        
        // Para página principal, usar Network First com fallback para cache (apenas quando offline)
        event.respondWith(
            fetch(request.clone())
                .then((response) => {
                    // Se online, sempre buscar da rede e atualizar cache em background
                    if (response.ok && shouldCache) {
                        const responseClone = response.clone();
                        caches.open(cacheToUse).then((cache) => {
                            cache.put(request, responseClone).catch(() => {});
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Só usar cache se realmente estiver offline
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            console.log('[Service Worker] Servindo página principal do cache (offline)');
                            return cachedResponse;
                        }
                        
                        // Tentar variações de URL apenas se realmente offline
                        const urlWithoutQuery = new URL(request.url);
                        urlWithoutQuery.search = '';
                        const cleanUrl = urlWithoutQuery.toString();
                        
                        const searchUrls = [
                            config.MAIN_PAGE,
                            './gerente-completo.php',
                            '/gerente-completo.php',
                            'gerente-completo.php',
                            './funcionario.php',
                            '/funcionario.php',
                            'funcionario.php',
                            cleanUrl
                        ];
                        
                        return caches.keys().then((cacheNames) => {
                            return Promise.all(
                                cacheNames.map((cacheName) => {
                                    return caches.open(cacheName).then((cache) => {
                                        return Promise.all(
                                            searchUrls.map(url => cache.match(url))
                                        ).then((matches) => {
                                            return matches.find(m => m !== undefined);
                                        });
                                    });
                                })
                            ).then((responses) => {
                                const foundResponse = responses.find(r => r !== undefined);
                                if (foundResponse) {
                                    console.log('[Service Worker] Página encontrada no cache (offline)');
                                    return foundResponse;
                                }
                                
                                // Se não encontrar, retornar erro
                                return new Response('Página não disponível offline', {
                                    status: 503,
                                    statusText: 'Service Unavailable',
                                    headers: { 
                                        'Content-Type': 'text/plain; charset=UTF-8'
                                    }
                                });
                            });
                        });
                    });
                })
        );
        return;
    }

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
                    
                    return new Response('Recurso nao disponivel offline', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: { 
                            'Content-Type': 'text/plain; charset=UTF-8'
                        }
                    });
                });
            })
    );
});

// Ouvir mensagens do cliente (para controle manual do modo offline)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        // Notificar clientes sobre atualização
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({ type: 'SW_UPDATED' });
            });
        });
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

// Push Notifications - NOTIFICAÇÕES NATIVAS (funcionam mesmo com app fechado)
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push notification recebida');
    
    let data = {
        title: 'LacTech - Nova Notificação',
        body: 'Você tem uma nova notificação',
        icon: './assets/img/lactech-logo.png',
        badge: './assets/img/lactech-logo.png',
        tag: 'lactech-push',
        requireInteraction: false,
        data: {
            url: './gerente-completo.php' // Será atualizado baseado no tipo de usuário
        }
    };
    
    // Tentar parsear dados do push
    if (event.data) {
        try {
            const pushData = event.data.json();
            data = {
                title: pushData.title || data.title,
                body: pushData.body || pushData.message || data.body,
                icon: pushData.icon || data.icon,
                badge: pushData.badge || data.badge,
                tag: pushData.tag || data.tag,
                requireInteraction: pushData.requireInteraction !== undefined ? pushData.requireInteraction : data.requireInteraction,
                data: {
                    url: pushData.url || pushData.link || data.data.url,
                    notificationId: pushData.notificationId || pushData.id,
                    action: pushData.action
                }
            };
        } catch (e) {
            // Se não for JSON, usar como texto
            data.body = event.data.text() || data.body;
        }
    }
    
    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        requireInteraction: data.requireInteraction,
        vibrate: [200, 100, 200], // Vibração no mobile
        data: data.data,
        actions: [
            {
                action: 'open',
                title: 'Abrir',
                icon: './assets/img/lactech-logo.png'
            },
            {
                action: 'close',
                title: 'Fechar'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification Click - quando usuário clica na notificação
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notificação clicada:', event.notification);
    
    event.notification.close();
    
    const notificationData = event.notification.data || {};
    const action = event.action || notificationData.action;
    
    if (action === 'close') {
        // Apenas fechar
        return;
    }
    
    // Abrir ou focar na aplicação
    // Detectar tipo de usuário baseado na URL do cliente
    let defaultUrl = './gerente-completo.php';
    if (event.notification.data && event.notification.data.userType === 'employee') {
        defaultUrl = './funcionario.php';
    }
    const urlToOpen = notificationData.url || defaultUrl;
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            // Verificar se já existe uma janela aberta
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Se não houver janela aberta, abrir nova
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

