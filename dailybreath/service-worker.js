const CACHE='dailybreath-1.7-shell-v3';
const SHELL=['/dailybreath/offline.html','/dailybreath/manifest.webmanifest','/dailybreath/assets/css/web-app.css','/dailybreath/assets/js/web-app.js','/dailybreath/assets/icons/dailybreath-mark-v2.png'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(SHELL)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('dailybreath-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  if(event.request.method!=='GET')return;
  if(event.request.mode==='navigate')event.respondWith(fetch(event.request).catch(()=>caches.match('/dailybreath/offline.html')));
  else if(new URL(event.request.url).origin===location.origin&&['style','script','image','font'].includes(event.request.destination))event.respondWith(caches.match(event.request).then(hit=>hit||fetch(event.request).then(response=>{if(response.ok){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(event.request,copy))}return response})));
});
