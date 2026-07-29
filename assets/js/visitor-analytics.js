(() => {
  'use strict';
  if (window.__beyondVisitorAnalyticsLoaded) return;
  window.__beyondVisitorAnalyticsLoaded = true;
  if (navigator.doNotTrack === '1' || window.doNotTrack === '1') return;

  const path = window.location.pathname || '/';
  const blocked = /^\/(?:api|server\/admin|beyond-id\/admin|beyond-french\/admin|dailybreath\/admin|admin|assets|sql|tools|docs)(?:\/|$)/i;
  if (blocked.test(path) || /\.(?:css|js|json|xml|txt|zip|pdf|png|jpe?g|gif|webp|svg|ico|mp3|mp4|webm)$/i.test(path)) return;

  let clientTimezone = '';
  try {
    clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
  } catch (_) {}

  const details = {
    path,
    title: document.title || '',
    referrer: document.referrer || '',
    viewport_width: Math.round(window.innerWidth || window.screen?.width || 0),
    language: navigator.language || '',
    client_timezone: clientTimezone,
  };

  const send = (eventType = 'page_view') => {
    fetch('/api/analytics/track.php', {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({...details, event_type: eventType}),
    }).catch(() => {});
  };

  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(() => send('page_view'), {timeout: 1800});
  } else {
    window.setTimeout(() => send('page_view'), 450);
  }

  window.setInterval(() => {
    if (document.visibilityState === 'visible') send('heartbeat');
  }, 60_000);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') send('heartbeat');
  });
  window.addEventListener('pagehide', () => send('heartbeat'), {once: true});
})();
