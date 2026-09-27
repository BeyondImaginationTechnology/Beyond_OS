<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app-layout.php';
beyond_nav_bootstrap('Beyond OS');

// Release cards use explicit status labels instead of the app catalog's inferred availability.
function whats_new_card(string $title, string $copy, string $href, string $action, string $status): string {
    return '<article class="release-card"><span class="release-status">'.e($status).'</span><h3>'.e($title).'</h3><p>'.e($copy).'</p><a href="'.e(beyond_url($href)).'">'.e($action).'<span aria-hidden="true"> →</span></a></article>';
}
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
    <span class="bos-kicker">Weekly update · <time datetime="2026-09-26">Saturday, September 26, 2026</time></span>
    <h1>What’s new.</h1>
    <p>This week across Beyond: app updates, creative tools, and OS milestones. Preview and development work are clearly marked.</p>
    <div class="bos-actions">
      <a class="bos-btn" href="#apps">Latest app updates</a>
      <a class="bos-btn secondary" href="#jaguar">Jaguar preview</a>
      <a class="bos-btn secondary" href="#core-v02">BIT OS Core v0.2</a>
      <a class="bos-btn secondary" href="#projects">See creator projects</a>
    </div>
  </section>

  <section class="bos-section jaguar-release" id="jaguar">
    <div class="jaguar-release-copy">
      <span class="bos-kicker">Jaguar AI · v0.4 web preview</span>
      <h2>Meet the Jaguar chat preview.</h2>
      <p>Jaguar now has a web chat experience with English, French, and Spanish interfaces. Explain offers quick utility answers and built-in guidance; Build introduces a preview for software ideas, planning, and coding questions.</p>
      <div class="bos-actions">
        <a class="bos-btn" href="<?=e(beyond_url('ai/chat.php'))?>">Open Jaguar chat</a>
        <a class="bos-btn secondary" href="#jaguar-progress-title">See build progress</a>
      </div>
    </div>
    <div class="jaguar-release-art"><img src="<?=e(beyond_url('ai/assets/jaguar-runner.jpg'))?>" alt="The cybernetic Jaguar visual identity moving through a digital landscape"></div>
  </section>

  <section class="bos-section jaguar-progress" aria-labelledby="jaguar-progress-title">
    <span class="bos-kicker">Build status</span>
    <h2 id="jaguar-progress-title">What you can use—and what’s next</h2>
    <div class="progress-grid">
      <article class="progress-card complete"><span>AVAILABLE</span><h3>Explain</h3><p>Quick utility answers, lookups, and concise built-in guidance. Explain uses a fast response path without GPU inference.</p></article>
      <article class="progress-card active"><span>PREVIEW</span><h3>Build</h3><p>Explore software ideas, plan an experience, and ask for coding guidance. This text preview does not edit your repository or generate images or video.</p></article>
      <article class="progress-card active"><span>APPLE APP PREVIEW</span><h3>Beyond-1 Draw Studio</h3><p>The native v0.5 preview adds touch and Apple Pencil sketching, undo and redo, and transparent PNG exports for the tattoo editor. Drawing stays on your device.</p></article>
      <article class="progress-card active"><span>ADMIN PREVIEW</span><h3>Code Thinking</h3><p>A separate workspace for authorized administrators supports development work with project context. It is separate from the public Build chat.</p></article>
      <article class="progress-card next"><span>PLANNED</span><h3>AI Draw and Video</h3><p>Image and video generation remain planned modes. The native Draw Studio preview is a manual sketching tool.</p></article>
      <article class="progress-card active"><span>IN DEVELOPMENT</span><h3>Beyond-1 model work</h3><p>Training and evaluation work continues around a Llama-based adapter. The small starter dataset exercises the training pipeline; it does not establish a production-ready model.</p></article>
    </div>
    <aside class="jaguar-note"><strong>Preview availability</strong><p>The chat shows which modes are enabled for your account. Preview features may change; the native app preview and model development milestones do not imply App Store availability or a completed Beyond foundation model.</p></aside>
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
    <aside class="core-release-note"><strong>Also in development: Home v0.1</strong><p>Home adds a graphical desktop, Files, Notes, and app launchers. It remains a development candidate with installation and hardware acceptance work outstanding.</p></aside>
  </section>

  <section class="bos-section" id="apps">
    <span class="bos-kicker">App updates · September 2026</span>
    <h2>Apps moving forward</h2>
    <p>More ways to read, reflect, learn, and create across web and native experiences.</p>
    <div class="bos-grid">
      <?=whats_new_card('DailyBreath Web','Daily readings, reflection, breathing practices, recovery challenges, and faith journeys come together in the installable web app.','dailybreath/','Open DailyBreath','WEB')?>
      <?=whats_new_card('DailyBreath for iOS 2.3','Multilingual Bible, Tanakh, and Quran reading; faith and recovery journeys; guide chat; and private journaling. Bundled readings work offline. Approved narration streams online.','app-store/','Explore the app catalog','IOS BUILD UPDATE')?>
      <?=whats_new_card('Beyond Tattoo 1.2','Browse released stencil drops, preview the available artwork, and download the files included with each drop. The native companion adds healing milestones and nearby Canadian studios.','beyond-tattoo/','Explore Beyond Tattoo','WEB + NATIVE UPDATE')?>
    </div>
  </section>

  <section class="bos-section" id="projects">
    <span class="bos-kicker">Creator updates</span>
    <h2>From sketch to studio</h2>
    <p>New tattoo workflows and connected storefront tools help creators prepare and share their work.</p>
    <div class="bos-grid">
      <?=whats_new_card('Tattoo Stencil Editor','Bring a sketch into the stencil workflow, including transparent PNG artwork exported from the Beyond-1 native Draw Studio preview.','beyond-tattoo/stencil-editor.php','Open Stencil Editor','CREATIVE TOOL')?>
      <?=whats_new_card('Studio consent tools','Download the tattoo procedure consent form. Studios can also send private signing links and manage completed consent records through their dashboard.','beyond-tattoo/downloads/tattoo-procedure-consent-bc.pdf','View consent form (PDF)','STUDIO UPDATE')?>
      <?=whats_new_card('Beyond Marketplace + Sell','Discover products and connect listings, checkout, digital fulfillment, and seller tools in the creator storefront.','beyond-market/','Open Marketplace','WEB')?>
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
.whats-new-hero,.jaguar-release-copy{color:#f7f7ff}.whats-new-hero .bos-kicker,.jaguar-release-copy .bos-kicker{color:#d7c7ff}.whats-new-hero p{color:#c9c4d9}.release-card{display:flex;flex-direction:column;min-width:0;padding:26px;border:1px solid var(--line);border-radius:20px;background:var(--panel)}.release-status{color:var(--accent-soft);font-size:11px;font-weight:850;letter-spacing:.08em}.release-card h3{margin:16px 0 9px;font-size:23px;line-height:1.2;letter-spacing:-.025em}.release-card p{margin:0 0 24px;color:var(--muted);line-height:1.7}.release-card>a{margin-top:auto;color:var(--accent-soft);font-weight:800;text-underline-offset:4px}.whats-new-main a:focus-visible{outline:3px solid var(--accent-soft);outline-offset:5px}.whats-new-main .jaguar-release>*,.whats-new-main .progress-card{min-width:0}.whats-new-main .core-release-note strong{color:var(--accent-soft)}html[data-theme="light"] .progress-card.complete span{color:#176b35;background:#e2f4e7}html[data-theme="light"] .progress-card.active span{color:#70329c;background:#f1e5fa}html[data-theme="light"] .progress-card.next span{color:#795000;background:#fff1d3}html[data-theme="light"] .jaguar-note strong{color:#795000}
.whats-new-main{width:min(1240px,calc(100% - 28px))}.whats-new-hero{background:radial-gradient(circle at 85% 10%,rgba(155,73,255,.32),transparent 28%),radial-gradient(circle at 72% 85%,rgba(242,70,157,.22),transparent 32%),linear-gradient(135deg,#0a1024,#251044 58%,#121322)}.whats-new-hero h1{max-width:880px}.whats-new-main .bos-section{scroll-margin-top:88px}.jaguar-release{display:grid;grid-template-columns:1.05fr .95fr;gap:18px;align-items:stretch}.jaguar-release-copy{padding:clamp(28px,5vw,55px);border:1px solid rgba(192,108,255,.42);border-radius:26px;background:radial-gradient(circle at 100% 0,rgba(224,80,255,.18),transparent 34%),linear-gradient(140deg,rgba(31,20,66,.96),rgba(10,12,31,.98))}.jaguar-release-copy h2{max-width:650px;margin:14px 0;font-size:clamp(38px,5vw,68px);line-height:.94;letter-spacing:-.06em}.jaguar-release-copy p{max-width:680px;color:#c9c4d9;font-size:16px;line-height:1.7}.jaguar-release-art{min-height:430px;overflow:hidden;border:1px solid rgba(192,108,255,.42);border-radius:26px;background:#090711}.jaguar-release-art img{display:block;width:100%;height:100%;object-fit:cover}.jaguar-progress>h2{margin-bottom:28px}.progress-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.progress-card{min-height:220px;padding:24px;border:1px solid var(--line);border-radius:20px;background:var(--panel)}.progress-card span{display:inline-flex;padding:6px 9px;border-radius:999px;font-size:9px;font-weight:950;letter-spacing:.12em}.progress-card.complete span{color:#8ff0ae;background:rgba(81,219,120,.13)}.progress-card.active span{color:#dfb2ff;background:rgba(174,92,255,.14)}.progress-card.next span{color:#ffd98c;background:rgba(255,191,50,.13)}.progress-card h3{margin:22px 0 9px;font-size:22px}.progress-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.65}.jaguar-note{display:grid;grid-template-columns:auto 1fr;gap:22px;align-items:center;margin-top:14px;padding:22px 24px;border:1px solid rgba(255,191,50,.3);border-radius:18px;background:rgba(255,191,50,.06)}.jaguar-note strong{color:#ffd98c}.jaguar-note p{margin:0;color:var(--muted);line-height:1.55}.release-foundation{padding:clamp(24px,4vw,42px);border:1px solid var(--line);border-radius:24px;background:var(--panel)}
.core-release-intro{max-width:850px;color:var(--muted);font-size:16px;line-height:1.7}.core-release-note{display:grid;grid-template-columns:auto 1fr;gap:22px;align-items:center;margin-top:14px;padding:22px 24px;border:1px solid rgba(69,231,255,.28);border-radius:18px;background:rgba(69,231,255,.05)}.core-release-note strong{color:var(--blue)}.core-release-note p{margin:0;color:var(--muted);line-height:1.55}
@media(max-width:900px){.jaguar-release{grid-template-columns:1fr}.jaguar-release-art{min-height:340px}.progress-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.whats-new-main{width:min(100% - 18px,1240px)}.whats-new-hero{padding:30px 18px}.whats-new-main .bos-actions{display:grid;grid-template-columns:1fr}.whats-new-main .bos-btn{width:100%}.progress-grid{grid-template-columns:1fr}.jaguar-note{grid-template-columns:1fr}.core-release-note{grid-template-columns:1fr}.jaguar-release-art{min-height:270px}}
</style>
<?php bos_page_end(); ?>
