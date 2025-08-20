// =====================================================
// SERVICE WORKER VAZIO - PARA EVITAR ERRO 404
// =====================================================

// Versão do cache
const CACHE_VERSION = '1.0.0';
const CACHE_NAME = `lactech-cache-${CACHE_VERSION}`;

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker instalado');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('✅ Cache aberto');
                return cache.addAll([
                    '/',
                    '/index.html',
                    '/gerente.html',
                    '/funcionario.html',
                    '/veterinario.html',
                    '/proprietario.html'
                ]);
            })
            .catch((error) => {
                console.warn('⚠️ Erro ao abrir cache:', error);
            })
    );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker ativado');
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
        })
    );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
    // Para requisições de API, não usar cache
    if (event.request.url.includes('supabase') || 
        event.request.url.includes('api') ||
        event.request.method !== 'GET') {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Retornar do cache se disponível
                if (response) {
                    return response;
                }
                
                // Caso contrário, buscar da rede
                return fetch(event.request)
                    .then((response) => {
                        // Não armazenar em cache se não for uma resposta válida
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clonar a resposta para armazenar no cache
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // Em caso de erro de rede, retornar página offline se disponível
                        if (event.request.destination === 'document') {
                            return caches.match('/offline.html');
                        }
                    });
            })
    );
});

// Mensagens do Service Worker
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('✅ Service Worker carregado com sucesso');
