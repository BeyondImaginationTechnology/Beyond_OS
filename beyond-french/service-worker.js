const CACHE = "beyond-french-1.2.0";
const OFFLINE_ASSETS = [
  "./offline.html",
  "./assets/css/style.css",
  "./assets/css/academy.css",
  "./assets/js/app.js",
  "./assets/images/beyond-french-logo.webp",
  "./assets/app-store/AppIcon-192.png",
  "./assets/app-store/AppIcon-512.png"
];

self.addEventListener("install", (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(OFFLINE_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith("beyond-french-") && key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;
  if (event.request.mode === "navigate") {
    event.respondWith(fetch(event.request, { cache: "no-store" }).catch(() => caches.match("./offline.html")));
    return;
  }
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin || !url.pathname.includes("/beyond-french/")) return;
  event.respondWith(caches.match(event.request, { ignoreSearch: true }).then((cached) => cached || fetch(event.request)));
});
