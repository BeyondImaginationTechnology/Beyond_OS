<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond App Store');
?>
<!doctype html><html lang="en"><head><script>(function(){try{var t=localStorage.getItem('beyond-theme');document.documentElement.dataset.theme=['dark','light','sunset','ocean','forest'].includes(t)?t:'dark';}catch(e){document.documentElement.dataset.theme='dark';}})();</script><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#32113d"><title>Beyond App Store | Beyond OS 2.3.4</title><meta name="description" content="Find and try every app in the Beyond ecosystem."><link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>"><link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>"></head><body class="bos-page">
<main class="bos-main app-store-main">
  <section class="bos-hero app-store-hero">
    <span class="bos-kicker">Release 2.3.4</span>
    <h1>Every Beyond app.<br>One store.</h1>
    <p>Browse the connected tools for living, learning, earning and exploring. Apps open directly—there is nothing extra to install.</p>
    <div class="bos-actions"><a class="bos-btn" href="<?=e(beyond_url('beyond-id/dashboard/wallet.php'))?>">Open Wallet</a><a class="bos-btn secondary" href="<?=e(beyond_url('beyond-market/'))?>">Explore Marketplace</a><a class="bos-btn secondary" href="#featured">Browse all apps</a></div>
  </section>

  <section class="bos-section" id="featured">
    <span class="bos-kicker">Live</span><h2>Health & daily life</h2><p>Daily guidance, wellness and creative self-expression.</p>
    <div class="bos-grid">
      <?=bos_app_card('Daily Breath','Daily verse, reflection and breathing practice.','dailybreath/bible.php?preview=1','DB','Live demo','assets/icons/app-store/daily-breath.jpg')?>
      <?=bos_app_card('Beyond Health','Health tools for mind, body and everyday care.','dailybreath/practices.php','BH','Open practices','assets/icons/app-store/beyond-health.jpg')?>
      <?=bos_app_card('Beyond Tattoo','AI-assisted tattoo ideas and daily stencils.','beyond-tattoo/','BT','Open app','assets/icons/app-store/beyond-tattoo.jpg')?>
      <?=bos_app_card('Baby Names','Explore names, origins and meanings.','beyond-baby-names/','BN','Open app','assets/icons/app-store/baby-names.jpg')?>
    </div>
  </section>

  <section class="bos-section" id="learn">
    <span class="bos-kicker">Learn</span><h2>Education & discovery</h2><p>Languages, numbers, history and the universe.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond French 2.0','Daily French, four-language practice, and a new iOS beta.','beyond-french/ios-beta.php','BF','Join iOS beta','assets/icons/app-store/beyond-french.jpg')?>
      <?=bos_app_card('Beyond Math Academy','5 modules · 10 lessons each · interactive lessons.','beyond-math/academy.php','BM','Open Academy','beyond-math/assets/img/beyond-math-logo.webp')?>
      <?=bos_app_card('Beyond Ancient Academy','50 narrated world-history lessons, artifact labs, source analysis, and exams.','beyond-ancient/academy.php','BA','Open Academy','assets/icons/app-store/beyond-ancient.jpg')?>
      <?=bos_app_card('Beyond Space','Space Academy, Solar System explorer, astronomy missions, and entertainment horoscopes.','beyond-space/','BS','Explore','assets/icons/app-store/beyond-space.jpg')?>
      <?=bos_app_card('Beyond Coding School','Web, iOS, Android, SVG, game and full-stack pathways.','coding-school/','CODE','Open School','@blank')?>
    </div>
  </section>

  <section class="bos-section">
    <span class="bos-kicker">Earn</span><h2>Wallet, market & work</h2><p>Manage bit$, shop assets, follow markets and build income.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond Wallet','Spend bit$, review activity and manage your Beyond finances.','beyond-id/dashboard/wallet.php','BW','Open Wallet','@blank')?>
      <?=bos_app_card('Beyond Marketplace','Discover and buy creator assets with bit$.','beyond-market/','MP','Open Marketplace','@blank')?>
      <?=bos_app_card('Beyond Finance','Review your wallet, bit$ rewards and transaction activity.','beyond-id/dashboard/wallet.php','BF','Open Wallet','@blank')?>
      <?=bos_app_card('Beyond Sell','List digital and physical products.','beyond-sell/','SELL','Open seller tools','@blank')?>
      <?=bos_app_card('Beyond Jobs','Match jobs to your pathway, build a résumé and cover letter, and plan free training.','beyond-jobs/','JOBS','Build my career','@blank')?>
      <?=bos_app_card('Career Pathways','Build job-ready skills through six Coding School pathways.','coding-school/','CAREER','Explore pathways','@blank')?>
    </div>
  </section>

  <section class="bos-section" id="games">
    <span class="bos-kicker">Beyond Games</span><h2>Original instant-play games</h2><p>Original Beyond worlds built for mobile and PC, with shared profiles, achievements and fair reward systems.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond Games','Explore the publisher hub and connected launch roadmap.','beyond-games/','GAMES','Explore publisher','@blank')?>
      <?=bos_app_card('Bit Runner','Run through Beyond OS, recover bit$ and defeat security viruses.','beyond-games/bit-runner.php','RUN','Play now','@blank')?>
      <?=bos_app_card('Beyond Skate','Tricks, custom parks and daily skating challenges.','beyond-games/game.php?slug=beyond-skate','SKATE','View game plan','@blank')?>
      <?=bos_app_card('Tattoo Master','A creative tattoo-studio simulator connected to Beyond Tattoo.','beyond-games/game.php?slug=tattoo-master','INK','View game plan','assets/icons/app-store/beyond-tattoo.jpg')?>
      <?=bos_app_card("Zak’s Kitchen Rush",'Prepare timed Haitian and American orders, build combos and upgrade the kitchen.','beyond-games/zaks-kitchen-rush.php','ZAK','Play now','@blank')?>
      <?=bos_app_card('Codebreaker Academy','Program robots with visual commands to escape puzzle chambers.','beyond-games/game.php?slug=codebreaker-academy','CODE','View game plan','@blank')?>
      <?=bos_app_card('Bit Drop','A polished physics-merging puzzle for quick daily play.','beyond-games/game.php?slug=bit-drop','DROP','View game plan','@blank')?>
    </div>
  </section>

  <section class="bos-section">
    <span class="bos-kicker">Explore</span><h2>Entertainment & creation</h2><p>Watch, listen, create and play across Beyond.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond TV','Live channels and an on-demand catalogue.','beyond-tv/','TV','Live demo','assets/icons/app-store/beyond-tv.jpg')?>
      <?=bos_app_card('Beyond Audio','Listen across the Beyond universe.','beyond-radio/','BA','Open radio','@blank')?>
      <?=bos_app_card('Beyond Media','Watch Beyond TV, hear Beyond Audio, preview media and find licensed downloads.','beyond-media/','MEDIA','Open media hub','@blank')?>
      <?=bos_app_card('Beyond Chromium','A controller-first desktop browser built around Beyond OS.','beyond-chromium/','BC','View desktop app','@blank')?>
      <?=bos_app_card('Canvas in Beyond Market','Customize mugs, posters, stickers, apparel and visual products.','beyond-market/#canvas-studio','CAN','Shop & create','@blank')?>
      <?=bos_app_card('Beyond Skate','Skate culture, media and community.','beyond-tv/browse.php','SK8','Browse media','@blank')?>
      <?=bos_app_card('Beyond Casino — Social Play','Demo bit$ games for entertainment only. No purchase necessary and no cash value.','beyond-casino/','BC','Play demo','@blank')?>
    </div>
  </section>
</main>
<style>
.app-store-main{width:min(1320px,calc(100% - 28px))}.app-store-hero{background:radial-gradient(circle at 90% 8%,rgba(68,140,255,.35),transparent 28%),linear-gradient(135deg,#10163b,#261150 58%,#3c1036)}.app-store-hero h1{max-width:900px}.app-store-main .bos-section{scroll-margin-top:88px}.app-store-main .bos-section>p{max-width:680px}.app-store-main .bos-card{min-height:154px}.app-store-main .bos-card-icon{width:64px;height:64px;font-size:19px;font-weight:950}.app-store-main .bos-card-icon-blank{background:transparent}
@media(max-width:560px){.app-store-main{width:min(100% - 18px,1320px)}.app-store-hero{padding:30px 18px}.app-store-hero h1{font-size:clamp(2.45rem,13vw,4rem)}.app-store-main .bos-card{min-height:128px}.app-store-main .bos-actions{display:grid;grid-template-columns:1fr}.app-store-main .bos-btn{width:100%}}html[data-theme="sunset"]{--bos-bg:#1a0d24;--bos-panel:#32133f;--bos-line:rgba(255,204,176,.2);--bos-text:#fff7f2;--bos-muted:#e5bdb5;--bos-purple:#ff8a62;--bos-pink:#c44c88}html[data-theme="sunset"] .bos-page{background:radial-gradient(circle at 80% 0,rgba(255,111,97,.3),transparent 30%),radial-gradient(circle at 12% 35%,rgba(255,179,71,.16),transparent 34%),linear-gradient(180deg,#32113d,#1d102b 48%,#0d1021)}html[data-theme="sunset"] .app-store-hero{background:radial-gradient(circle at 88% 8%,rgba(255,179,71,.28),transparent 30%),linear-gradient(135deg,#5f214e,#3a183f 58%,#27162f)}html[data-theme="sunset"] .bos-card{background:rgba(75,29,64,.76);border-color:rgba(255,204,176,.18)}html[data-theme="sunset"] .bos-btn.secondary{background:rgba(103,40,72,.56)}</style>
<?php bos_page_end(); ?>
