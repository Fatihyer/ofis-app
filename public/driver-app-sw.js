const CACHE_NAME = 'parisvia-driver-pwa-v4';
const CORE_ASSETS = [
  '/driver-app',
  '/driver-app.webmanifest',
  '/images/driver-icon-192.png',
  '/images/driver-icon-512.png',
  '/favicon.ico'
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(CORE_ASSETS)).catch(() => null));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))));
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  event.respondWith(
    fetch(request)
      .then(response => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(request, copy)).catch(() => null);
        return response;
      })
      .catch(() => caches.match(request).then(cached => cached || caches.match('/driver-app')))
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data?.url || '/driver-app'));
});
