const CACHE_NAME = 'beyond-kitchen-0.0.1';
const APP_FILES = [
  './',
  './offline.html',
  './assets/css/app.css?v=0.0.1',
  './assets/js/recipe-library.js?v=0.0.1',
  './assets/js/app.js?v=0.0.1',
  './assets/kitchen-mark.svg',
  './data/recipes.json',
  './manifest.webmanifest'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_FILES)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key.startsWith('beyond-kitchen-') && key !== CACHE_NAME).map((key) => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put('./', copy));
          }
          return response;
        })
        .catch(async () => (await caches.match(request)) || (await caches.match('./')) || caches.match('./offline.html'))
    );
    return;
  }

  event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
});
