<script src="<?= e(bt_app_url('assets/js/app.js')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/js/app.js') ?: '20260716')) ?>"></script>
<script>
document.querySelectorAll('form[method="post" i]').forEach(function (form) {
  if (form.querySelector('input[name="_csrf"]')) return;
  var token = document.createElement('input');
  token.type = 'hidden'; token.name = '_csrf'; token.value = <?= json_encode(bt_csrf_token()) ?>;
  form.appendChild(token);
});
</script>
<button class="bt-install-button" type="button" data-pwa-install hidden>
  <span class="bt-install-button-mark" aria-hidden="true">↓</span>
  <span>Install Beyond Tattoo</span>
</button>
<dialog class="bt-install-help" data-pwa-help aria-labelledby="bt-install-title">
  <form method="dialog"><button class="bt-install-help-close" aria-label="Close install instructions">×</button></form>
  <p class="bt-install-kicker">YOUR STUDIO, ONE CLICK AWAY</p>
  <h2 id="bt-install-title">Install Beyond Tattoo</h2>
  <p data-pwa-instructions>Install Beyond Tattoo as a web app from your browser. On Mac, use the browser’s install or Add to Dock option; on Windows, choose Install or Add to desktop. No separate macOS app is needed. Some features still need an internet connection.</p>
  <button class="bt-install-help-done" type="button" data-pwa-close>Got it</button>
</dialog>
<style>
.bt-needle-launch{position:fixed;right:20px;bottom:20px;z-index:35;min-height:52px;padding:4px 17px 4px 5px;display:flex;align-items:center;gap:9px;border:1px solid #c79bff88;border-radius:999px;color:#fff;background:linear-gradient(120deg,#261438,#170e22);box-shadow:0 12px 34px #0008,0 0 22px #a277ff25;font:800 13px system-ui;cursor:pointer}.bt-needle-launch img{width:40px;height:40px;border-radius:50%;object-fit:cover;object-position:center 34%;background:#100a17}.bt-needle-panel{position:fixed;right:20px;bottom:82px;z-index:34;width:min(410px,calc(100vw - 24px));height:min(630px,calc(100dvh - 105px));overflow:hidden;border:1px solid #a277ff88;border-radius:18px;background:#0b0712;box-shadow:0 20px 72px #000b}.bt-needle-panel[hidden]{display:none}.bt-needle-panel iframe{width:100%;height:100%;border:0;background:#0b0712}@media(max-width:480px){.bt-needle-launch{right:12px;bottom:12px}.bt-needle-panel{right:8px;bottom:74px;width:calc(100vw - 16px);height:calc(100dvh - 84px);border-radius:14px}}
</style>
<button class="bt-needle-launch" id="btNeedleOpen" type="button" aria-expanded="false" aria-controls="btNeedlePanel"><img src="<?= e(bt_app_url('assets/img/needle-bot-v2.png')) ?>" alt="" aria-hidden="true"><span>Ask Needle Bot</span></button>
<section class="bt-needle-panel" id="btNeedlePanel" aria-label="Needle Bot chat" hidden><iframe title="Needle Bot tattoo and stencil assistant" src="<?=e(bt_app_url('needle-bot.php?embed=1'))?>" loading="lazy"></iframe></section>
<script>
(()=>{const button=document.getElementById('btNeedleOpen'),panel=document.getElementById('btNeedlePanel');if(!button||!panel)return;button.addEventListener('click',()=>{panel.hidden=!panel.hidden;button.setAttribute('aria-expanded',String(!panel.hidden))});window.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden){panel.hidden=true;button.setAttribute('aria-expanded','false');button.focus()}})})();
</script>
<script src="<?= e(bt_app_url('assets/js/pwa.js')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/js/pwa.js') ?: '20260926')) ?>" data-service-worker="<?= e(bt_app_url('service-worker.js')) ?>" data-scope="<?= e(bt_app_url('/')) ?>"></script>
</body>
</html>
