const CACHE = 'beyond-math-2.3.3';
const APP_SHELL = ['/beyond-math/','/beyond-math/academy.php','/beyond-math/tools.php','/beyond-math/offline.html','/beyond-math/academy.css','/beyond-math/assets/css/style.css?v=2.1','/beyond-math/assets/js/app.js?v=2.3.3','/beyond-math/assets/img/beyond-math-logo.webp'];
self.addEventListener('install', event => event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(APP_SHELL)).then(() => self.skipWaiting())));
self.addEventListener('activate', event => event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim())));
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) return;
  event.respondWith(fetch(event.request).then(response => {
    if (response.ok) caches.open(CACHE).then(cache => cache.put(event.request, response.clone()));
    return response;
  }).catch(async () => (await caches.match(event.request)) || (event.request.mode === 'navigate' ? caches.match('/beyond-math/offline.html') : Response.error())));
});
