'use strict';

(() => {
  const script = document.currentScript;
  const installButton = document.querySelector('[data-pwa-install]');
  const helpDialog = document.querySelector('[data-pwa-help]');
  const closeHelp = document.querySelector('[data-pwa-close]');
  const desktop = window.matchMedia('(min-width: 761px)');
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  let installPrompt = null;

  if ('serviceWorker' in navigator && script?.dataset.serviceWorker) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(script.dataset.serviceWorker, { scope: script.dataset.scope || './' }).catch(() => {});
    }, { once: true });
  }

  if (!installButton || !desktop.matches || standalone) return;
  installButton.hidden = false;

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    installButton.setAttribute('aria-label', 'Install Beyond Tattoo on this device');
  });

  installButton.addEventListener('click', async () => {
    if (!installPrompt) {
      helpDialog?.showModal();
      return;
    }

    const prompt = installPrompt;
    installPrompt = null;
    await prompt.prompt();
    const choice = await prompt.userChoice;
    if (choice.outcome === 'accepted') installButton.hidden = true;
  });

  closeHelp?.addEventListener('click', () => helpDialog?.close());
  helpDialog?.addEventListener('click', (event) => {
    if (event.target === helpDialog) helpDialog.close();
  });
  window.addEventListener('appinstalled', () => { installButton.hidden = true; });
})();
