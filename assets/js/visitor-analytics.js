(() => {
  'use strict';

  // Public legal links belong on every web app, independently of analytics or
  // Do Not Track preferences. Add them to an existing footer, or supply a
  // compact footer for pages that did not previously have one.
  const legalAppNames = {
    'dailybreath': 'Daily Breath', 'beyond-kitchen': 'Beyond Kitchen',
    'beyond-health': 'Beyond Health', 'beyond-tattoo': 'Beyond Tattoo',
    'beyond-baby-names': 'Baby Names', 'beyond-french': 'Beyond French',
    'beyond-math': 'Beyond Math', 'beyond-ancient': 'Beyond Ancient',
    'beyond-space': 'Beyond Space', 'coding-school': 'Beyond Coding School',
    'beyond-market': 'Beyond Marketplace', 'beyond-sell': 'Beyond Sell',
    'beyond-jobs': 'Beyond Jobs', 'beyond-games': 'Beyond Games',
    'beyond-tv': 'Beyond TV', 'beyond-media': 'Beyond Media',
    'beyond-casino': 'Beyond Casino', 'beyond-webs': 'Beyond Webs',
    'ai': 'Jaguar AI', 'beyond': 'BIT OS'
  };
  const segment = (window.location.pathname || '/').split('/').filter(Boolean)[0] || '';
  const legalApp = legalAppNames[segment] || 'Beyond OS';
  const addLegalLinks = () => {
    if (document.querySelector('.app-legal-links')) return;
    let footer = document.querySelector('footer');
    if (!footer) {
      footer = document.createElement('footer');
      footer.className = 'app-legal-footer';
      document.body.append(footer);
    }
    const nav = document.createElement('nav');
    nav.className = 'app-legal-links';
    nav.setAttribute('aria-label', `${legalApp} legal information`);
    const query = encodeURIComponent(legalApp);
    nav.innerHTML = `<a href="/legal/privacy.php?app=${query}">Privacy</a><a href="/legal/terms.php?app=${query}">Terms</a>`;
    footer.append(nav);
    if (!document.getElementById('app-legal-link-styles')) {
      const style = document.createElement('style');
      style.id = 'app-legal-link-styles';
      style.textContent = '.app-legal-footer{margin:32px auto 0;padding:20px max(18px,calc((100% - 1180px)/2));border-top:1px solid currentColor;color:#7d8494;font:700 12px/1.4 system-ui,sans-serif}.app-legal-links{display:flex;gap:16px;flex-wrap:wrap;align-items:center}.app-legal-links a{color:inherit;text-decoration:none}.app-legal-links a:hover,.app-legal-links a:focus-visible{text-decoration:underline}@media(max-width:560px){.app-legal-links a{min-height:32px;display:inline-flex;align-items:center}}';
      document.head.append(style);
    }
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addLegalLinks, {once: true});
  else addLegalLinks();

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
