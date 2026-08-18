(function () {
  'use strict';

  if (window.__beyondSplashLoaded || document.documentElement.dataset.beyondSplash === 'off') return;
  window.__beyondSplashLoaded = true;

  var apps = [
    ['/beyond-health/', 'Beyond Health', '#68f0b0'],
    ['/dailybreath/', 'DailyBreath', '#f0cb77'],
    ['/beyond-french/', 'Beyond French', '#7ca7ff'],
    ['/beyond-tv/', 'Beyond TV', '#ff73ba'],
    ['/beyond-tattoo/', 'Beyond Tattoo', '#d8ab52'],
    ['/beyond-math/', 'Beyond Math', '#5bdb45'],
    ['/beyond-space/', 'Beyond Space', '#7fe6ff'],
    ['/beyond-ancient/', 'Beyond Ancient', '#d9ad63'],
    ['/beyond-baby-names/', 'Beyond Baby Names', '#ff89cc'],
    ['/beyond-games/', 'Beyond Games', '#c69cff'],
    ['/beyond-market/', 'Beyond Market', '#70a7ff'],
    ['/beyond-sell/', 'Beyond Sell', '#70a7ff'],
    ['/beyond-media/', 'Beyond Media', '#ff73ba'],
    ['/beyond-jobs/', 'Beyond Jobs', '#ffd16b'],
    ['/beyond-casino/', 'Beyond Casino', '#f7c948'],
    ['/beyond-chromium/', 'Beyond Chromium', '#91a7ff'],
    ['/app-store/', 'Beyond App Store', '#c69cff'],
    ['/academy/', 'Beyond Academy', '#ffd16b'],
    ['/coding-school/', 'Beyond Coding School', '#8ee5ff'],
    ['/dashboard/', 'Beyond Dashboard', '#a99cff']
  ];

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
    });
  }

  function appConfig() {
    var body = document.body;
    var path = window.location.pathname || '/';
    var explicit = body && body.dataset ? body.dataset.splashApp : '';
    var accent = body && body.dataset ? body.dataset.splashAccent : '';
    if (explicit) return { name: explicit, accent: accent || '#68f0b0' };
    for (var i = 0; i < apps.length; i += 1) {
      if (path.indexOf(apps[i][0]) === 0) return { name: apps[i][1], accent: apps[i][2] };
    }
    return { name: 'Beyond OS', accent: '#a99cff' };
  }

  function hideLegacySplash() {
    document.querySelectorAll('.splash, .french-splash, [data-legacy-splash]').forEach(function (node) {
      node.classList.add('hidden');
      node.setAttribute('aria-hidden', 'true');
    });
  }

  function mountSplash() {
    if (!document.body || document.querySelector('.beyond-splash-screen')) return;

    hideLegacySplash();
    var config = appConfig();
    var key = 'beyond-splash:' + config.name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    try {
      if (sessionStorage.getItem(key) === '1') return;
      sessionStorage.setItem(key, '1');
    } catch (error) {}

    var splash = document.createElement('div');
    splash.className = 'beyond-splash-screen';
    splash.style.setProperty('--beyond-splash-accent', config.accent);
    splash.setAttribute('role', 'status');
    splash.setAttribute('aria-live', 'polite');
    splash.innerHTML = '<div class="beyond-splash-panel">'
      + '<svg class="beyond-splash-mark" viewBox="0 0 420 420" aria-hidden="true">'
      + '<defs><linearGradient id="beyondSplashBeam" x1="88" y1="66" x2="326" y2="348" gradientUnits="userSpaceOnUse"><stop stop-color="#ffffff"/><stop offset=".45" stop-color="var(--beyond-splash-accent)"/><stop offset="1" stop-color="#b78cff"/></linearGradient></defs>'
      + '<circle cx="210" cy="210" r="162" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="2"/>'
      + '<g class="beyond-splash-ring"><ellipse cx="210" cy="210" rx="166" ry="64" fill="none" stroke="url(#beyondSplashBeam)" stroke-width="4"/><circle cx="376" cy="210" r="8" fill="var(--beyond-splash-accent)"/></g>'
      + '<g class="beyond-splash-ring alt"><ellipse cx="210" cy="210" rx="78" ry="166" fill="none" stroke="rgba(255,255,255,.44)" stroke-width="3"/><circle cx="210" cy="44" r="6" fill="#fff"/></g>'
      + '<g class="beyond-splash-pulse"><circle cx="210" cy="210" r="88" fill="rgba(255,255,255,.045)" stroke="var(--beyond-splash-accent)" stroke-opacity=".42" stroke-width="2"/><circle cx="210" cy="188" r="34" fill="url(#beyondSplashBeam)"/><path d="M193 211h34l17 74c2 9-4 17-13 17h-42c-9 0-15-8-13-17l17-74Z" fill="url(#beyondSplashBeam)"/><circle cx="210" cy="188" r="14" fill="#080b18"/><path d="M203 199h14l9 76h-32l9-76Z" fill="#080b18"/><circle cx="210" cy="210" r="72" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="2" stroke-dasharray="3 10"/></g>'
      + '<circle class="beyond-splash-spark" cx="92" cy="143" r="5" fill="#fff"/><circle class="beyond-splash-spark two" cx="316" cy="110" r="4" fill="var(--beyond-splash-accent)"/><circle class="beyond-splash-spark three" cx="327" cy="306" r="6" fill="#b78cff"/>'
      + '</svg>'
      + '<h1 class="beyond-splash-title">Beyond Imagination</h1>'
      + '<p class="beyond-splash-company">Corp. Technology</p>'
      + '<p class="beyond-splash-app">' + escapeHtml(config.name) + '</p>'
      + '<button class="beyond-splash-skip" type="button">Enter</button>'
      + '</div>';
    document.body.prepend(splash);

    function dismiss() {
      splash.classList.add('is-hiding');
      window.setTimeout(function () {
        splash.remove();
      }, 480);
    }

    splash.querySelector('button').addEventListener('click', dismiss);
    window.setTimeout(dismiss, 1500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountSplash, { once: true });
  } else {
    mountSplash();
  }
})();
