(function () {
  'use strict';
  var root = document.documentElement;
  var themes = ['dark', 'light', 'sunset', 'ocean', 'forest'];
  var icons = { dark: '\uD83C\uDF19', light: '\u2600\uFE0F', sunset: '\uD83C\uDF05', ocean: '\uD83C\uDF0A', forest: '\uD83C\uDF32' };
  var labels = { dark: 'Dark', light: 'Light', sunset: 'Sunset', ocean: 'Ocean', forest: 'Forest' };
  function validTheme(theme) { return themes.indexOf(theme) !== -1; }
  function defaultTheme() { var preferred = root.getAttribute('data-default-theme'); return validTheme(preferred) ? preferred : 'dark'; }
  function savedTheme() { try { var saved = localStorage.getItem('beyond-theme'); return validTheme(saved) ? saved : defaultTheme(); } catch (error) { return defaultTheme(); } }
  function each(selector, callback) { var nodes = document.querySelectorAll(selector); for (var index = 0; index < nodes.length; index += 1) callback(nodes[index]); }
  function applyTheme(theme) {
    if (!validTheme(theme)) theme = defaultTheme();
    root.setAttribute('data-theme', theme);
    var next = themes[(themes.indexOf(theme) + 1) % themes.length];
    each('.theme-toggle', function (button) {
      button.textContent = icons[theme];
      button.title = labels[theme] + ' theme - switch to ' + labels[next];
      button.setAttribute('aria-label', 'Current theme ' + labels[theme] + '. Switch to ' + labels[next] + ' theme');
    });
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'light' ? '#f4f6fc' : theme === 'sunset' ? '#32113d' : theme === 'ocean' ? '#071E2E' : theme === 'forest' ? '#101C16' : '#050817');
  }
  function closestByClass(node, className) { while (node && node !== document) { if (node.classList && node.classList.contains(className)) return node; node = node.parentNode; } return null; }
  function emitLocale(locale) {
    var event;
    if (typeof CustomEvent === 'function') event = new CustomEvent('beyond:locale-change', { detail: { locale: locale } });
    else { event = document.createEvent('CustomEvent'); event.initCustomEvent('beyond:locale-change', true, false, { locale: locale }); }
    document.dispatchEvent(event);
  }
  function initializeControls() {
    applyTheme(savedTheme());
    each('#localePicker', function (picker) {
      var locale = 'en';
      try { locale = localStorage.getItem('beyond-locale') || 'en'; } catch (error) {}
      for (var index = 0; index < picker.options.length; index += 1) if (picker.options[index].value === locale) picker.value = locale;
      root.lang = picker.value;
    });
  }
  var lastThemeTouch = 0;
  function activateTheme(event) {
    var button = closestByClass(event.target, 'bos-theme-toggle');
    if (!button) return false;
    event.preventDefault();
    var current = validTheme(root.getAttribute('data-theme')) ? root.getAttribute('data-theme') : defaultTheme();
    var next = themes[(themes.indexOf(current) + 1) % themes.length];
    try { localStorage.setItem('beyond-theme', next); } catch (error) {}
    applyTheme(next);
    return true;
  }
  document.addEventListener('touchend', function (event) {
    if (activateTheme(event)) lastThemeTouch = Date.now();
  }, false);
  document.addEventListener('click', function (event) {
    if (Date.now() - lastThemeTouch < 700 && closestByClass(event.target, 'bos-theme-toggle')) {
      event.preventDefault();
      return;
    }
    activateTheme(event);
  });
  document.addEventListener('change', function (event) {
    var picker = event.target;
    if (!picker || picker.id !== 'localePicker') return;
    root.lang = picker.value;
    try { localStorage.setItem('beyond-locale', picker.value); } catch (error) {}
    emitLocale(picker.value);
  });
  applyTheme(savedTheme());
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeControls);
  else initializeControls();
})();
