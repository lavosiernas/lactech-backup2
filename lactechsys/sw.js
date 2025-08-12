const CACHE_NAME = 'lactech-v1.0.0';
const urlsToCache = [
  '/',
  '/index.html',
  '/login.html',
  '/PrimeiroAcesso.html',
  '/funcionario.html',
  '/gerente.html',
  '/proprietario.html',
  '/veterinario.html',
  '/payment.html',
  '/playstore.html',
  '/supabase_config_fixed.js',
  '/config.js',
  '/payment_config.js',
  '/pdf-service.js',
  '/pix_payment_system.js',
  '/pix_qr_generator.js',
  '/assets/css/style.css',
  '/assets/js/console-guard.js',
  '/assets/js/pdf-generator.js',
  '/assets/templates/report-template.html',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
  'https://i.postimg.cc/vmrkgDcB/lactech.png'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
  console.log('Service Worker: Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Cache aberto');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('Service Worker: Todos os recursos foram cacheados');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('Service Worker: Erro ao fazer cache:', error);
      })
  );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Ativando...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Ativado com sucesso');
      return self.clients.claim();
    })
  );
});

// Interceptação de requisições
self.addEventListener('fetch', (event) => {
  // Ignorar requisições para APIs externas (Supabase)
  if (event.request.url.includes('supabase.co') || 
      event.request.url.includes('postgrest.org') ||
      event.request.url.includes('storage.googleapis.com')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Retorna o recurso do cache se disponível
        if (response) {
          console.log('Service Worker: Recuperando do cache:', event.request.url);
          return response;
        }

        // Se não estiver no cache, busca da rede
        console.log('Service Worker: Buscando da rede:', event.request.url);
        return fetch(event.request)
          .then((response) => {
            // Verifica se a resposta é válida
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clona a resposta para poder usá-la no cache
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(event.request, responseToCache);
                console.log('Service Worker: Novo recurso adicionado ao cache:', event.request.url);
              });

            return response;
          })
          .catch(() => {
            // Se a rede falhar, tenta retornar uma página offline
            if (event.request.destination === 'document') {
              return caches.match('/index.html');
            }
          });
      })
  );
});

// Sincronização em background
self.addEventListener('sync', (event) => {
  console.log('Service Worker: Sincronização em background:', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Aqui você pode adicionar lógica para sincronizar dados offline
      console.log('Sincronizando dados em background...')
    );
  }
});

// Notificações push
self.addEventListener('push', (event) => {
  console.log('Service Worker: Notificação push recebida');
  
  const options = {
    body: event.data ? event.data.text() : 'Nova notificação do LacTech',
    icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
    badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Abrir App',
        icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('LacTech', options)
  );
});

// Clique em notificação
self.addEventListener('notificationclick', (event) => {
  console.log('Service Worker: Notificação clicada');
  
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});

// Mensagens do app principal
self.addEventListener('message', (event) => {
  console.log('Service Worker: Mensagem recebida:', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
