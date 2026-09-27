'use strict';

const CACHE_PREFIX = 'beyond-tattoo-static-';
const CACHE_NAME = `${CACHE_PREFIX}1`;
const APP_SCOPE = new URL(self.registration.scope);
const OFFLINE_URL = new URL('offline.html', APP_SCOPE).href;
const CORE_ASSETS = [
  OFFLINE_URL,
  new URL('assets/css/app.css', APP_SCOPE).href,
  new URL('assets/css/theme-02.css', APP_SCOPE).href,
  new URL('assets/css/studio-upgrades.css', APP_SCOPE).href,
  new URL('assets/css/responsive.css', APP_SCOPE).href,
  new URL('assets/js/app.js', APP_SCOPE).href,
  new URL('assets/js/pwa.js', APP_SCOPE).href,
  new URL('assets/icons/beyond-tattoo-192.png', APP_SCOPE).href,
  new URL('assets/icons/beyond-tattoo-512.png', APP_SCOPE).href,
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(
    keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME).map((key) => caches.delete(key)),
  )).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || !url.pathname.startsWith(APP_SCOPE.pathname)) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  const pathWithinApp = url.pathname.slice(APP_SCOPE.pathname.length);
  if (!/^(assets\/(css|js|img|icons))\//.test(pathWithinApp)) return;

  event.respondWith(caches.open(CACHE_NAME).then(async (cache) => {
    const cached = await cache.match(request, { ignoreSearch: true });
    const update = fetch(request).then((response) => {
      if (response.ok && response.type === 'basic') cache.put(request, response.clone());
      return response;
    }).catch(() => null);
    if (cached) {
      event.waitUntil(update);
      return cached;
    }
    return (await update) || new Response('', { status: 503, statusText: 'Offline' });
  }));
});
