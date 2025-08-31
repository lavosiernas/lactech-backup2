// Service Worker para Xandria Store
const CACHE_NAME = 'xandria-store-v1';
const urlsToCache = [
  '/',
  '/xandria-store.html',
  'https://cdn.tailwindcss.com',
  'https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
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
    body: 'Nova atualização disponível na Xandria Store!',
    icon: '/icon-192x192.png',
    badge: '/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver apps',
        icon: '/icon-192x192.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: '/icon-192x192.png'
      }
    ]
  };

  // Se há dados na notificação push, usar eles
  if (event.data) {
    const data = event.data.json();
    options = {
      ...options,
      title: data.title || 'Xandria Store',
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
    self.registration.showNotification('Xandria Store', options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
  console.log('Notificação clicada:', event);
  
  event.notification.close();

  if (event.action === 'explore') {
    // Abrir a Xandria Store
    event.waitUntil(
      clients.openWindow('/xandria-store.html')
    );
  } else if (event.action === 'close') {
    // Apenas fechar a notificação
    return;
  } else {
    // Clique padrão - abrir a Xandria Store
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
