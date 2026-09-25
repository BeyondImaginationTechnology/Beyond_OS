<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app-layout.php';
beyond_nav_bootstrap('Beyond OS');
?>
<!doctype html>
<html lang="en">
<head>
  <script>(function(){try{var t=localStorage.getItem('beyond-theme');document.documentElement.dataset.theme=['dark','light','sunset','ocean','forest'].includes(t)?t:'dark';}catch(e){document.documentElement.dataset.theme='dark';}})();</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#050817">
  <title>What’s New at Beyond Imagination</title>
  <meta name="description" content="The latest progress across Jaguar AI, Beyond OS, apps, and creator projects.">
  <link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
</head>
<body class="bos-page">
<main class="bos-main whats-new-main">
  <section class="bos-hero whats-new-hero">
    <span class="bos-kicker">Beyond Imagination · September 2026</span>
    <h1>What’s new.</h1>
    <p>A clearer view of the work landing across Jaguar AI, Beyond OS, connected apps, and creator projects.</p>
    <div class="bos-actions">
      <a class="bos-btn" href="#jaguar">Jaguar progress</a>
      <a class="bos-btn secondary" href="#core-v02">BIT OS Core v0.2</a>
      <a class="bos-btn secondary" href="#apps">Explore app updates</a>
      <a class="bos-btn secondary" href="#projects">See creator projects</a>
    </div>
  </section>

  <section class="bos-section jaguar-release" id="jaguar">
    <div class="jaguar-release-copy">
      <span class="bos-kicker">Jaguar AI · Foundation phase</span>
      <h2>Jaguar is becoming a real platform.</h2>
      <p>We have moved beyond the concept stage. Jaguar now has a dedicated AI destination, a defined product voice, an initial local inference runtime, and a secure path for connecting authenticated Beyond experiences to the model.</p>
      <div class="bos-actions">
        <a class="bos-btn" href="<?=e(beyond_url('ai/'))?>">Explore Jaguar</a>
        <a class="bos-btn secondary" href="#jaguar-progress-title">See build progress</a>
      </div>
    </div>
    <div class="jaguar-release-art"><img src="<?=e(beyond_url('ai/assets/jaguar-runner.jpg'))?>" alt="The cybernetic Jaguar visual identity moving through a digital landscape"></div>
  </section>

  <section class="bos-section jaguar-progress" aria-labelledby="jaguar-progress-title">
    <span class="bos-kicker">Build status</span>
    <h2 id="jaguar-progress-title">The path from identity to intelligence</h2>
    <div class="progress-grid">
      <article class="progress-card complete"><span>COMPLETE</span><h3>Product identity</h3><p>The Jaguar name, cyber-jaguar visual system, premium AI page, and AI navigation tab are now established.</p></article>
      <article class="progress-card complete"><span>COMPLETE</span><h3>Model access</h3><p>Access to Meta’s gated Llama 3.1 8B repositories has been approved through Hugging Face. The instruction-tuned model is the selected foundation for the first Jaguar runtime.</p></article>
      <article class="progress-card active"><span>IN PROGRESS</span><h3>Local runtime</h3><p>A Transformers-based service now defines Jaguar’s system voice, health endpoint, chat contract, generation limits, and environment-only credentials.</p></article>
      <article class="progress-card active"><span>IN PROGRESS</span><h3>Beyond integration</h3><p>An authenticated PHP proxy is in place so Beyond products can reach Jaguar without exposing the model service or Hugging Face token to visitors.</p></article>
      <article class="progress-card next"><span>NEXT</span><h3>First live inference</h3><p>Install the Python runtime, add a read-only Hugging Face token, download the weights, and validate performance on suitable GPU hardware.</p></article>
      <article class="progress-card next"><span>AFTER VALIDATION</span><h3>Guided experiences</h3><p>Connect Jaguar first to focused learning and creator workflows, then evaluate safety, quality, latency, and operating cost before wider release.</p></article>
    </div>
    <aside class="jaguar-note"><strong>What Jaguar is today</strong><p>Jaguar is an in-development Beyond AI platform built on an approved third-party foundation model. It is not yet a publicly available chatbot or a separately trained Beyond foundation model.</p></aside>
  </section>

  <section class="bos-section core-release" id="core-v02" aria-labelledby="core-v02-title">
    <span class="bos-kicker">BIT OS Core · v0.2 test candidate</span>
    <h2 id="core-v02-title">From UEFI installer to Core dashboard.</h2>
    <p class="core-release-intro">Core v0.2 now has published ISO and USB downloads, a Windows USB setup wizard, and a matching SHA-256 manifest. The installation paths were exercised end to end in a disposable QEMU virtual machine.</p>
    <div class="progress-grid">
      <article class="progress-card complete"><span>VALIDATED</span><h3>ISO and USB installs</h3><p>UEFI ISO and USB media both completed selected-partition and whole-disk installations on disposable QEMU disks.</p></article>
      <article class="progress-card complete"><span>VALIDATED</span><h3>Boot without installer media</h3><p>All four installed-disk combinations restarted without the ISO or USB attached and reached the Core dashboard.</p></article>
      <article class="progress-card complete"><span>AVAILABLE</span><h3>Windows USB wizard</h3><p>The wizard verifies the compressed USB image against its SHA-256 checksum, expands it, and prepares the raw image for USB writing.</p></article>
    </div>
    <aside class="core-release-note"><strong>Validation scope</strong><p>These results come from QEMU software emulation with UEFI firmware. Physical hardware compatibility and Secure Boot were not validated. Core v0.2 remains a test candidate.</p></aside>
    <div class="bos-actions"><a class="bos-btn" href="https://os.beyondimagination.co.technology/#core-downloads">Get Core v0.2 downloads and checksums</a></div>
  </section>

  <section class="bos-section" id="apps">
    <span class="bos-kicker">Latest app READMEs</span>
    <h2>Apps moving forward</h2>
    <p>Installable web experiences and native companions now carry richer offline, personal, and creator-focused features.</p>
    <div class="bos-grid">
      <?=bos_app_card('DailyBreath Web','Installable Scripture and wellness PWA with narration, encrypted reflection journaling, weekly challenges, history, and recovery support.','dailybreath/','DB','Open app','assets/icons/app-store/daily-breath.jpg')?>
      <?=bos_app_card('DailyBreath for Apple','Offline content, Bible search and narration, widgets, App Clip, private journaling, 45-day history, and optional encrypted iCloud sync.','DailyBreathApple/README.md','DB','Read README','assets/icons/app-store/daily-breath.jpg')?>
      <?=bos_app_card('Beyond Tattoo Apple','Asset-backed daily stencils, a real-download collection browser, healing milestones, reward bits, and a location-aware Canadian studio directory.','BeyondTattooApple/README.md','BT','Read README','assets/icons/app-store/beyond-tattoo.jpg')?>
    </div>
  </section>

  <section class="bos-section" id="projects">
    <span class="bos-kicker">Latest project READMEs</span>
    <h2>Creator tools and campaigns</h2>
    <p>New local production workflows make it easier to preview, render, and publish trusted creative work.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond Studio + Remotion','A local-only bridge that previews trusted Remotion or bundled HTML projects and renders H.264 video without exposing public-server execution.','tools/beyond-studio-remotion/README.md','VIDEO','Read README','@atom')?>
      <?=bos_app_card('Beyond French: African Expansion','A Remotion campaign kit with vertical Reels and feed compositions for Lingala, Darija, Masri, and Swahili creative.','tools/beyond-french-remotion-africa/README.md','BF','Read README','assets/icons/app-store/beyond-french.jpg')?>
      <?=bos_app_card('Beyond Marketplace + Sell','A connected creator storefront with product discovery, listings, checkout, digital fulfillment, and seller tooling.','beyond-market/','MARKET','Open Marketplace','@atom')?>
    </div>
  </section>

  <section class="bos-section release-foundation">
    <span class="bos-kicker">Also included</span>
    <h2>The foundation stays intact</h2>
    <p>Academy pathways, assessments, public certificate verification, and Beyond ID achievements remain part of Beyond OS.</p>
    <div class="bos-actions">
      <a class="bos-btn secondary" href="<?=e(beyond_url('academy/'))?>">Open Academy</a>
      <a class="bos-btn secondary" href="<?=e(beyond_url('academy/verify.php'))?>">Verify a certificate</a>
    </div>
  </section>
</main>
<style>
.whats-new-main{width:min(1240px,calc(100% - 28px))}.whats-new-hero{background:radial-gradient(circle at 85% 10%,rgba(155,73,255,.32),transparent 28%),radial-gradient(circle at 72% 85%,rgba(242,70,157,.22),transparent 32%),linear-gradient(135deg,#0a1024,#251044 58%,#121322)}.whats-new-hero h1{max-width:880px}.whats-new-main .bos-section{scroll-margin-top:88px}.jaguar-release{display:grid;grid-template-columns:1.05fr .95fr;gap:18px;align-items:stretch}.jaguar-release-copy{padding:clamp(28px,5vw,55px);border:1px solid rgba(192,108,255,.42);border-radius:26px;background:radial-gradient(circle at 100% 0,rgba(224,80,255,.18),transparent 34%),linear-gradient(140deg,rgba(31,20,66,.96),rgba(10,12,31,.98))}.jaguar-release-copy h2{max-width:650px;margin:14px 0;font-size:clamp(38px,5vw,68px);line-height:.94;letter-spacing:-.06em}.jaguar-release-copy p{max-width:680px;color:#c9c4d9;font-size:16px;line-height:1.7}.jaguar-release-art{min-height:430px;overflow:hidden;border:1px solid rgba(192,108,255,.42);border-radius:26px;background:#090711}.jaguar-release-art img{display:block;width:100%;height:100%;object-fit:cover}.jaguar-progress>h2{margin-bottom:28px}.progress-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.progress-card{min-height:220px;padding:24px;border:1px solid var(--line);border-radius:20px;background:var(--panel)}.progress-card span{display:inline-flex;padding:6px 9px;border-radius:999px;font-size:9px;font-weight:950;letter-spacing:.12em}.progress-card.complete span{color:#8ff0ae;background:rgba(81,219,120,.13)}.progress-card.active span{color:#dfb2ff;background:rgba(174,92,255,.14)}.progress-card.next span{color:#ffd98c;background:rgba(255,191,50,.13)}.progress-card h3{margin:22px 0 9px;font-size:22px}.progress-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.65}.jaguar-note{display:grid;grid-template-columns:auto 1fr;gap:22px;align-items:center;margin-top:14px;padding:22px 24px;border:1px solid rgba(255,191,50,.3);border-radius:18px;background:rgba(255,191,50,.06)}.jaguar-note strong{color:#ffd98c}.jaguar-note p{margin:0;color:var(--muted);line-height:1.55}.release-foundation{padding:clamp(24px,4vw,42px);border:1px solid var(--line);border-radius:24px;background:var(--panel)}
.core-release-intro{max-width:850px;color:var(--muted);font-size:16px;line-height:1.7}.core-release-note{display:grid;grid-template-columns:auto 1fr;gap:22px;align-items:center;margin-top:14px;padding:22px 24px;border:1px solid rgba(69,231,255,.28);border-radius:18px;background:rgba(69,231,255,.05)}.core-release-note strong{color:var(--blue)}.core-release-note p{margin:0;color:var(--muted);line-height:1.55}
@media(max-width:900px){.jaguar-release{grid-template-columns:1fr}.jaguar-release-art{min-height:340px}.progress-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.whats-new-main{width:min(100% - 18px,1240px)}.whats-new-hero{padding:30px 18px}.whats-new-main .bos-actions{display:grid;grid-template-columns:1fr}.whats-new-main .bos-btn{width:100%}.progress-grid{grid-template-columns:1fr}.jaguar-note{grid-template-columns:1fr}.core-release-note{grid-template-columns:1fr}.jaguar-release-art{min-height:270px}}
</style>
<?php bos_page_end(); ?>
