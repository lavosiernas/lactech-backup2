// Service Worker para LacTech - Versão Offline com Notificações
const CACHE_NAME = 'lactech-offline-v2.0.2';
const APP_VERSION = '2.0.2';
const OFFLINE_CACHE = 'lactech-offline-data-v2.0.2';

const urlsToCache = [
  '/',
  '/xandria-store.php',
  '/gerente.php',
  '/funcionario.php',
  '/veterinario.php',
  '/proprietario.php',
  '/login.php',
  '/acesso-bloqueado.html',
  '/assets/js/offline-manager.js',
  '/assets/css/style.css',
  '/assets/css/dark-theme-fixes.css',
  '/assets/img/lactech-logo.png',
  '/assets/img/xandria-preta.png',
  'https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://unpkg.com/@supabase/supabase-js@2'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  console.log('Service Worker instalando versão:', APP_VERSION);
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache aberto para versão:', APP_VERSION);
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        // Notificar sobre nova versão
        self.registration.showNotification('LacTech Atualizado!', {
          body: `Nova versão ${APP_VERSION} instalada com sucesso!`,
          icon: 'assets/img/lactech-logo.png',
          badge: 'assets/img/lactech-logo.png',
          tag: 'version-update',
          requireInteraction: true,
          actions: [
            {
              action: 'open',
              title: 'Abrir LacTech'
            },
            {
              action: 'close',
              title: 'Fechar'
            }
          ]
        });
      })
  );
});

// Interceptação de requisições - Estratégia Offline First
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Estratégia para diferentes tipos de recursos
  if (request.method === 'GET') {
    event.respondWith(handleRequest(request));
  } else if (request.method === 'POST' || request.method === 'PUT' || request.method === 'DELETE') {
    // Para requisições de dados, usar estratégia Network First com fallback offline
    event.respondWith(handleDataRequest(request));
  }
});

// Manipular requisições GET (recursos estáticos)
async function handleRequest(request) {
  try {
    // Cache First para recursos estáticos
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    // Se não estiver no cache, buscar da rede
    const networkResponse = await fetch(request);
    
    // Cachear a resposta para uso futuro
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Erro na requisição:', request.url, error);
    
    // Fallback para páginas HTML
    if (request.destination === 'document') {
      const fallbackResponse = await caches.match('/offline.html');
      return fallbackResponse || new Response('Página não disponível offline', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'text/html' }
      });
    }
    
    return new Response('Recurso não disponível offline', { status: 503 });
  }
}

// Manipular requisições de dados (POST, PUT, DELETE)
async function handleDataRequest(request) {
  try {
    // Network First para dados
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Notificar que a requisição foi bem-sucedida
      self.clients.matchAll().then(clients => {
        clients.forEach(client => {
          client.postMessage({
            type: 'DATA_SYNC_SUCCESS',
            url: request.url,
            method: request.method
          });
        });
      });
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Erro na requisição de dados:', request.url, error);
    
    // Em caso de erro, notificar para armazenar offline
    self.clients.matchAll().then(clients => {
      clients.forEach(client => {
        client.postMessage({
          type: 'DATA_SYNC_FAILED',
          url: request.url,
          method: request.method,
          error: error.message
        });
      });
    });
    
    // Retornar resposta de sucesso para não quebrar a interface
    return new Response(JSON.stringify({ 
      success: false, 
      offline: true, 
      message: 'Dados salvos localmente, serão sincronizados quando a conexão for restaurada' 
    }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Gerenciamento de notificações push
self.addEventListener('push', (event) => {
  console.log('Push event recebido:', event);
  
  let options = {
    body: 'Nova atualização disponível no LacTech!',
    icon: 'assets/img/lactech-logo.png',
    badge: 'assets/img/lactech-logo.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Abrir LacTech',
        icon: 'assets/img/lactech-logo.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: 'assets/img/lactech-logo.png'
      }
    ]
  };

  // Se há dados na notificação push, usar eles
  if (event.data) {
    const data = event.data.json();
    options = {
      ...options,
      title: data.title || 'LacTech',
      body: data.body || options.body,
      icon: data.icon || options.icon,
      badge: data.badge || options.badge,
      data: {
        ...options.data,
        ...data.data
      }
    };
  }

  event.waitUntil(
    self.registration.showNotification('LacTech', options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
  console.log('Notificação clicada:', event);
  
  event.notification.close();

  if (event.action === 'explore') {
    // Abrir o LacTech
    event.waitUntil(
      clients.openWindow('/xandria-store.php')
    );
  } else if (event.action === 'close') {
    // Apenas fechar a notificação
    return;
  } else {
    // Clique padrão - abrir o LacTech
    event.waitUntil(
      clients.openWindow('/xandria-store.php')
    );
  }
});

// Foco na janela
self.addEventListener('notificationclose', (event) => {
  console.log('Notificação fechada:', event);
});

// Atualização do Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
            console.log('Deletando cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Listener para mensagens do cliente
self.addEventListener('message', (event) => {
  const { type, data } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
    case 'CACHE_DATA':
      // Cachear dados offline
      event.waitUntil(
        caches.open(OFFLINE_CACHE).then(cache => {
          return cache.put(data.url, new Response(JSON.stringify(data.data)));
        })
      );
      break;
    case 'GET_CACHED_DATA':
      // Obter dados do cache
      event.waitUntil(
        caches.open(OFFLINE_CACHE).then(cache => {
          return cache.match(data.url).then(response => {
            if (response) {
              return response.json().then(data => {
                event.ports[0].postMessage({ success: true, data });
              });
            } else {
              event.ports[0].postMessage({ success: false, data: null });
            }
          });
        })
      );
      break;
    case 'CLEAR_OFFLINE_CACHE':
      // Limpar cache offline
      event.waitUntil(
        caches.delete(OFFLINE_CACHE).then(() => {
          event.ports[0].postMessage({ success: true });
        })
      );
      break;
  }
});

// Notificações Push
self.addEventListener('push', (event) => {
  console.log('Push notification recebida:', event);
  
  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = { title: 'LacTech', body: event.data.text() };
    }
  }
  
  const options = {
    body: data.body || 'Nova notificação do LacTech',
    icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
    badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
    tag: data.tag || 'lactech-notification',
    requireInteraction: data.requireInteraction || false,
    silent: data.silent || false,
    data: data.data || {},
    actions: data.actions || []
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'LacTech', options)
  );
});

// Clique em notificação
self.addEventListener('notificationclick', (event) => {
  console.log('Notificação clicada:', event);
  
  event.notification.close();
  
  const urlToOpen = event.notification.data.url || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Verificar se já existe uma janela aberta
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url.includes(urlToOpen) && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Abrir nova janela se não existir
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Fechar notificação
self.addEventListener('notificationclose', (event) => {
  console.log('Notificação fechada:', event);
});

// Background Sync para notificações offline
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync-notifications') {
    event.waitUntil(
      // Sincronizar notificações pendentes quando voltar online
      fetch('/api/sync-notifications')
        .then(response => response.json())
        .then(data => {
          console.log('Notificações sincronizadas:', data);
        })
        .catch(error => {
          console.error('Erro ao sincronizar notificações:', error);
        })
    );
  }
});
