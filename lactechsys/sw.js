// Service Worker para LacTech
const CACHE_NAME = 'lactech-v2.0.0';
const APP_VERSION = '2.0.0';
const urlsToCache = [
  '/',
  '/xandria-store.html',
  '/gerente.html',
  '/funcionario.html',
  '/veterinario.html',
  '/login.html',
  '/acesso-bloqueado.html',
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

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Retorna do cache se disponível, senão busca da rede
        return response || fetch(event.request);
      })
  );
});

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
      clients.openWindow('/xandria-store.html')
    );
  } else if (event.action === 'close') {
    // Apenas fechar a notificação
    return;
  } else {
    // Clique padrão - abrir o LacTech
    event.waitUntil(
      clients.openWindow('/xandria-store.html')
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
          if (cacheName !== CACHE_NAME) {
            console.log('Deletando cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
