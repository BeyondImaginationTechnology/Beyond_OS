<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = bos_page_start(
    'Beyond Chromium',
    'Beyond Chromium',
    'A controller-first desktop browser for Beyond OS.'
);
?>
<main class="bos-main chromium-main">
  <section class="bos-hero chromium-hero">
    <span class="bos-kicker">Desktop app · Windows prototype</span>
    <h1>Browse Beyond.<br>From your controller.</h1>
    <p>Beyond Chromium brings the connected Beyond OS experience to the desktop with gamepad navigation, a virtual pointer and an on-screen keyboard.</p>
    <div class="bos-actions">
      <a class="bos-btn" href="https://github.com/gregoirerosier/site/tree/main/beyond-chromium">View source on GitHub</a>
      <a class="bos-btn secondary" href="#controls">See the controls</a>
    </div>
  </section>

  <section class="bos-section">
    <span class="bos-kicker">Built for the living room</span>
    <h2>A browser that feels at home with Beyond OS.</h2>
    <p>Use a controller to move the pointer, scroll pages, navigate history and type without leaving the experience. Keyboard and mouse controls remain available.</p>
    <div class="bos-grid">
      <?=bos_app_card('Controller pointer','Move naturally across any web page with the left stick.','beyond-chromium/#controls','L','Left stick','@atom')?>
      <?=bos_app_card('Comfortable scrolling','Read and explore with smooth right-stick scrolling.','beyond-chromium/#controls','R','Right stick','@atom')?>
      <?=bos_app_card('On-screen keyboard','Search, enter addresses and type with controller-friendly keys.','beyond-chromium/#controls','KEY','Press X / Square','@atom')?>
    </div>
  </section>

  <section class="bos-section" id="controls">
    <span class="bos-kicker">Default controls</span>
    <h2>Everything important is one button away.</h2>
    <div class="control-grid" role="list">
      <div role="listitem"><kbd>Left stick</kbd><span>Move pointer</span></div>
      <div role="listitem"><kbd>Right stick</kbd><span>Scroll page</span></div>
      <div role="listitem"><kbd>A / Cross</kbd><span>Click or type</span></div>
      <div role="listitem"><kbd>B / Circle</kbd><span>Back or close keyboard</span></div>
      <div role="listitem"><kbd>X / Square</kbd><span>Open keyboard</span></div>
      <div role="listitem"><kbd>Y / Triangle</kbd><span>Focus address bar</span></div>
      <div role="listitem"><kbd>Menu / Options</kbd><span>Refresh page</span></div>
    </div>
  </section>
</main>
<style>
.chromium-main{width:min(1180px,calc(100% - 28px))}
.chromium-hero{background:radial-gradient(circle at 84% 18%,rgba(68,140,255,.38),transparent 25%),radial-gradient(circle at 72% 80%,rgba(242,70,157,.28),transparent 31%),linear-gradient(135deg,#0b1534,#251052 58%,#35122e)}
.chromium-hero h1{max-width:900px}
.control-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:24px}
.control-grid div{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:18px 20px;border:1px solid var(--bos-line);border-radius:16px;background:var(--bos-panel)}
.control-grid kbd{color:var(--bos-text);font:900 13px/1 Inter,system-ui,sans-serif;letter-spacing:.02em}
.control-grid span{color:var(--bos-muted);font-size:14px;text-align:right}
html[data-theme="sunset"] .chromium-hero{background:radial-gradient(circle at 84% 18%,rgba(255,179,71,.28),transparent 27%),linear-gradient(135deg,#5f214e,#3a183f 58%,#27162f)}
@media(max-width:680px){.chromium-main{width:min(100% - 18px,1180px)}.control-grid{grid-template-columns:1fr}.control-grid div{padding:16px}.chromium-hero{padding:30px 18px}.chromium-hero h1{font-size:clamp(2.45rem,13vw,4rem)}}
</style>
<?php bos_page_end(); ?>
