(function () {
  'use strict';

  var installPrompt = null;

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    installPrompt = event;
    document.documentElement.dataset.pwaInstallReady = 'true';
  });

  window.beyondPwaInstall = function () {
    if (!installPrompt) return Promise.resolve(false);
    var prompt = installPrompt;
    installPrompt = null;
    document.documentElement.dataset.pwaInstallReady = 'false';
    return prompt.prompt().then(function () {
      return prompt.userChoice;
    });
  };
})();
