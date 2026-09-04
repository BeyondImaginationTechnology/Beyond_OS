const CACHE = "bit-platform-offline-v1";
const OFFLINE_ASSETS = ["offline.html", "assets/img/beyond-logo.png"];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE)
      .then(cache => cache.addAll(OFFLINE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", event => {
  if (event.request.method !== "GET" || event.request.mode !== "navigate") return;

  // Always request live HTML. The cache is used only when the network fails.
  event.respondWith(
    fetch(event.request, { cache: "no-store" })
      .catch(() => caches.match("offline.html"))
  );
});
