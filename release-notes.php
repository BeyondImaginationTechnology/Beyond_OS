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
  <title>What’s New in Beyond OS</title>
  <meta name="description" content="The latest app and project updates included in Beyond OS.">
  <link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
</head>
<body class="bos-page">
<main class="bos-main whats-new-main">
  <section class="bos-hero whats-new-hero">
    <span class="bos-kicker">Beyond OS · August 2026</span>
    <h1>What’s new.</h1>
    <p>A clearer view of the work landing across Beyond apps and creator projects, based on the latest project READMEs.</p>
    <div class="bos-actions">
      <a class="bos-btn" href="#apps">Explore app updates</a>
      <a class="bos-btn secondary" href="#projects">See creator projects</a>
      <a class="bos-btn secondary" href="<?=e(beyond_url('app-store/'))?>">Open App Store</a>
    </div>
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
.whats-new-main{width:min(1240px,calc(100% - 28px))}.whats-new-hero{background:radial-gradient(circle at 85% 10%,rgba(81,219,120,.25),transparent 28%),radial-gradient(circle at 72% 85%,rgba(242,70,157,.22),transparent 32%),linear-gradient(135deg,#0a1830,#241044 58%,#121b29)}.whats-new-hero h1{max-width:880px}.whats-new-main .bos-section{scroll-margin-top:88px}.release-foundation{padding:clamp(24px,4vw,42px);border:1px solid var(--line);border-radius:24px;background:var(--panel)}
@media(max-width:560px){.whats-new-main{width:min(100% - 18px,1240px)}.whats-new-hero{padding:30px 18px}.whats-new-main .bos-actions{display:grid;grid-template-columns:1fr}.whats-new-main .bos-btn{width:100%}}
</style>
<?php bos_page_end(); ?>
