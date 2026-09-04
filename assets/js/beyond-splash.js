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
    return { name: 'BIT', accent: '#a99cff' };
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
      + '<defs><linearGradient id="beyondSplashBeam" x1="102" y1="72" x2="320" y2="348" gradientUnits="userSpaceOnUse"><stop stop-color="#ffffff"/><stop offset=".4" stop-color="var(--beyond-splash-accent)"/><stop offset="1" stop-color="#a47cff"/></linearGradient><radialGradient id="beyondSplashGlass" cx="38%" cy="30%" r="76%"><stop stop-color="#ffffff" stop-opacity=".09"/><stop offset=".55" stop-color="var(--beyond-splash-accent)" stop-opacity=".035"/><stop offset="1" stop-color="#050713" stop-opacity=".08"/></radialGradient><filter id="beyondSplashGlow" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="7" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>'
      + '<circle class="beyond-splash-halo" cx="210" cy="210" r="112" fill="url(#beyondSplashGlass)" stroke="rgba(255,255,255,.12)" stroke-width="1"/>'
      + '<g class="beyond-splash-ring"><ellipse cx="210" cy="210" rx="163" ry="61" fill="none" stroke="url(#beyondSplashBeam)" stroke-width="2.5"/><circle cx="373" cy="210" r="4.5" fill="var(--beyond-splash-accent)"/></g>'
      + '<g class="beyond-splash-ring alt"><ellipse cx="210" cy="210" rx="72" ry="162" fill="none" stroke="rgba(255,255,255,.34)" stroke-width="1.75"/><circle cx="210" cy="48" r="3.5" fill="#fff"/></g>'
      + '<g class="beyond-splash-ring third"><ellipse cx="210" cy="210" rx="151" ry="78" transform="rotate(58 210 210)" fill="none" stroke="rgba(174,139,255,.3)" stroke-width="1.5"/></g>'
      + '<g class="beyond-splash-pulse"><circle cx="210" cy="210" r="82" fill="none" stroke="var(--beyond-splash-accent)" stroke-opacity=".24" stroke-width="1"/><path class="beyond-splash-keyhole" d="M210 151a40 40 0 0 0-20.5 74l-17.5 50h76l-17.5-50A40 40 0 0 0 210 151Z" fill="none" stroke="url(#beyondSplashBeam)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" filter="url(#beyondSplashGlow)"/><path d="M190 160a39 39 0 0 1 38-4" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".72"/></g>'
      + '<circle class="beyond-splash-spark" cx="93" cy="143" r="3.5" fill="#fff"/><circle class="beyond-splash-spark two" cx="316" cy="110" r="3" fill="var(--beyond-splash-accent)"/><circle class="beyond-splash-spark three" cx="327" cy="306" r="3.5" fill="#b78cff"/>'
      + '</svg>'
      + '<h1 class="beyond-splash-title">Beyond Imagination</h1>'
      + '<p class="beyond-splash-company">Technology · BIT</p>'
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
