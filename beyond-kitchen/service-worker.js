const CACHE_NAME = 'beyond-kitchen-0.0.2-assets-v5';
const APP_FILES = [
  './',
  './offline.html',
  './assets/css/app.css?v=0.0.5',
  './assets/js/recipe-library.js?v=0.0.2',
  './assets/js/app.js?v=0.0.5',
  './assets/kitchen-mark.svg',
  './assets/images/recipes/lemon-chickpea-bowls.png',
  './assets/images/recipes/tomato-butter-beans.jpg',
  './assets/images/recipes/ginger-salmon-tray.jpg',
  './assets/images/recipes/green-goddess-toast.jpg',
  './assets/images/recipes/crispy-chicken-couscous.jpg',
  './assets/images/recipes/peach-oat-crumble.jpg',
  './assets/images/recipes/haitian-griot-plate.jpg',
  './assets/images/recipes/haitian-diri-djon-djon.jpg',
  './assets/images/recipes/haitian-tassot-plantains.jpg',
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
