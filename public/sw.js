const CACHE_NAME = 'noir-cache-v4';
const urlsToCache = [
  '/',
  '/assets/css/store.min.css?v=1.2.5',
  '/assets/css/responsive.min.css?v=1.2.5',
  '/assets/css/app.min.css?v=1.2.5',
  '/assets/js/lucide.min.js?v=1.0.0',
  '/assets/js/install.min.js',
  '/assets/js/storefront-motion.min.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache).catch(() => {}))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // Never intercept non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);
  // Only handle same-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // Do not cache API endpoints, dynamic AJAX, analytics, or admin areas
  if (url.pathname.startsWith('/api/') ||
      url.pathname.startsWith('/admin/') ||
      url.pathname.startsWith('/analytics/') ||
      url.pathname.startsWith('/ajax/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) {
        return cached;
      }
      return fetch(event.request).catch(() => {
        if (event.request.mode === 'navigate') {
          return caches.match('/').then(res => res || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/html' } }));
        }
        return new Response('', { status: 408 });
      });
    })
  );
});
