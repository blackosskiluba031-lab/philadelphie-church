// sw.js - Service Worker PWA pour Église App
const CACHE_NAME = 'eglise-app-v2.0';
const STATIC_ASSETS = [
  './',
  './index.php',
  './assets/css/theme.css',
  './assets/js/theme-switcher.js',
  './assets/js/app.js',
  './manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS).catch(err => console.log('Cache addAll non-bloquant:', err));
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  // Stratégie Network-First avec fallback cache pour une fraîcheur maximale
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Met en cache la réponse valide pour le mode hors-ligne
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then(cached => {
          if (cached) return cached;
          if (event.request.headers.get('accept').includes('text/html')) {
            return caches.match('./index.php');
          }
        });
      })
  );
});

// Écoute des notifications Push
self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : { title: 'Église de la Grâce', body: 'Nouvelle notification paroissiale' };
  const options = {
    body: data.body,
    icon: './assets/icons/icon-192.png',
    badge: './assets/icons/icon-192.png',
    data: { url: data.url || './' },
    vibrate: [200, 100, 200]
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});
