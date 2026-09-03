<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond App Store');
?>
<!doctype html><html lang="en"><head><script>document.documentElement.dataset.theme='light';</script><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#f4f6fc"><title>Beyond App Store | Beyond OS</title><meta name="description" content="Find and try every app in the Beyond ecosystem."><link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>"><link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>"></head><body class="bos-page">
<main class="bos-main app-store-main">
  <section class="bos-hero app-store-hero">
    <span class="bos-kicker">Beyond ecosystem</span>
    <h1>Every Beyond app.<br>One store.</h1>
    <p>Browse the connected tools for living, learning, earning and exploring. Open apps instantly, and install supported web apps on your device.</p>
    <div class="bos-actions"><a class="bos-btn" href="<?=e(beyond_url('beyond-id/dashboard/wallet.php'))?>">Open Wallet</a><a class="bos-btn secondary" href="<?=e(beyond_url('beyond-market/'))?>">Explore Marketplace</a><a class="bos-btn secondary" href="#featured">Browse all apps</a></div>
  </section>

  <section class="bos-section" id="featured">
    <span class="bos-kicker">Live</span><h2>Health & daily life</h2><p>Daily guidance, wellness and creative self-expression.</p>
    <div class="bos-grid">
      <?=bos_app_card('DailyBreath','Bible, Torah, and Quran daily readings with complete local libraries, recovery, breathing, and private reflection.','dailybreath/','DB','Open web app','dailybreath/assets/icons/dailybreath-mark-v2.png?v=20260903-1')?>
      <?=bos_app_card('Beyond Health','Health tools for mind, body and everyday care.','beyond-health/','BH','Open demo','assets/icons/app-store/beyond-health.jpg')?>
      <?=bos_app_card('Beyond Tattoo','AI-assisted tattoo ideas and daily stencils.','beyond-tattoo/','BT','Open app','assets/icons/app-store/beyond-tattoo.jpg')?>
      <?=bos_app_card('Baby Names','Explore names, origins and meanings.','beyond-baby-names/','BN','Open app','assets/icons/app-store/baby-names.jpg')?>
    </div>
  </section>

  <section class="bos-section" id="learn">
    <span class="bos-kicker">Learn</span><h2>Education & discovery</h2><p>Languages, numbers, history and the universe.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond French','Daily lessons, four-language prerecorded audio, dictionary, practice, and Academy.','beyond-french/','BF','Open web app','assets/icons/app-store/beyond-french.jpg')?>
      <?=bos_app_card('Beyond Math Academy','5 modules · 10 lessons each · interactive lessons.','beyond-math/academy.php','BM','Open Academy','beyond-math/assets/img/beyond-math-logo.webp')?>
      <?=bos_app_card('Beyond Ancient Academy','50 narrated world-history lessons, artifact labs, source analysis, and exams.','beyond-ancient/academy.php','BA','Open Academy','assets/icons/app-store/beyond-ancient.jpg')?>
      <?=bos_app_card('Beyond Space','Space Academy, Solar System explorer, astronomy missions, and entertainment horoscopes.','beyond-space/','BS','Explore','assets/icons/app-store/beyond-space.jpg')?>
      <?=bos_app_card('Beyond Coding School','Web, iOS, Android, SVG, game and full-stack pathways.','coding-school/','CODE','Open School','@blank')?>
    </div>
  </section>

  <section class="bos-section">
    <span class="bos-kicker">Earn</span><h2>Wallet, market & work</h2><p>Manage bit$, shop assets, follow markets and build income.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond Wallet','Spend bit$, review activity and manage your Beyond finances.','beyond-id/dashboard/wallet.php','BW','Open Wallet','assets/icons/app-store/beyond-wallet.png')?>
      <?=bos_app_card('Beyond Marketplace','Discover and buy creator assets with bit$.','beyond-market/','MP','Open Marketplace','assets/icons/app-store/beyond-marketplace.png')?>
      <?=bos_app_card('Beyond Finance','Review your wallet, bit$ rewards and transaction activity.','beyond-id/dashboard/wallet.php','BF','Open Wallet','assets/icons/app-store/beyond-wallet.png')?>
      <?=bos_app_card('Beyond Sell','List digital and physical products.','beyond-sell/','SELL','Open seller tools','assets/icons/app-store/beyond-marketplace.png')?>
      <?=bos_app_card('Beyond Jobs','Match jobs to your pathway, build a résumé and cover letter, and plan free training.','beyond-jobs/','JOBS','Build my career','assets/icons/app-store/beyond-work.png')?>
      <?=bos_app_card('Career Pathways','Build job-ready skills through six Coding School pathways.','coding-school/','CAREER','Explore pathways','assets/icons/app-store/beyond-work.png')?>
    </div>
  </section>

  <section class="bos-section" id="games">
    <span class="bos-kicker">Beyond Games</span><h2>Original instant-play games</h2><p>Original Beyond worlds built for mobile and PC, with shared profiles, achievements and fair reward systems.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond Games','Explore the publisher hub and connected launch roadmap.','beyond-games/','GAMES','Explore publisher','@blank')?>
      <?=bos_app_card('Bit Runner','Run through Beyond OS, recover bit$ and defeat security viruses.','beyond-games/bit-runner.php','RUN','Play now','@blank')?>
      <?=bos_locked_app_card('Beyond Skate','Tricks, custom parks and daily skating challenges.','SKATE','@blank')?>
      <?=bos_app_card('Tattoo Master','A creative tattoo-studio simulator connected to Beyond Tattoo.','beyond-games/game.php?slug=tattoo-master','INK','View game plan','assets/icons/app-store/beyond-tattoo.jpg')?>
      <?=bos_app_card("Zak’s Kitchen Rush",'Prepare timed Haitian and American orders, build combos and upgrade the kitchen.','beyond-games/zaks-kitchen-rush.php','ZAK','Play now','@blank')?>
      <?=bos_locked_app_card('Codebreaker Academy','Program robots with visual commands to escape puzzle chambers.','CODE','@blank')?>
      <?=bos_locked_app_card('Bit Drop','A polished physics-merging puzzle for quick daily play.','DROP','@blank')?>
    </div>
  </section>

  <section class="bos-section">
    <span class="bos-kicker">Explore</span><h2>Entertainment & creation</h2><p>Watch, listen, create and play across Beyond.</p>
    <div class="bos-grid">
      <?=bos_app_card('Beyond TV','Live channels and an on-demand catalogue.','beyond-tv/','TV','Live demo','assets/icons/app-store/beyond-tv.jpg')?>
      <?=bos_locked_app_card('Beyond Audio','Listen across the Beyond universe.','BA','@blank')?>
      <?=bos_app_card('Beyond Media','Watch Beyond TV, preview private media and find licensed downloads.','beyond-media/','MEDIA','Open media hub','@blank')?>
      <?=bos_locked_app_card('Beyond Chromium','A controller-first desktop browser built around Beyond OS.','BC','@blank')?>
      <?=bos_app_card('Canvas in Beyond Market','Customize mugs, posters, stickers, apparel and visual products.','beyond-market/#canvas-studio','CAN','Shop & create','@blank')?>
      <?=bos_locked_app_card('Beyond Skate','Skate culture, media and community.','SK8','@blank')?>
      <?=bos_app_card('Beyond Casino — Social Play','Demo bit$ games for entertainment only. No purchase necessary and no cash value.','beyond-casino/','BC','Play demo','@blank')?>
    </div>
  </section>
</main>
<style>
.app-store-main{width:min(1320px,calc(100% - 28px))}.app-store-hero{background:radial-gradient(circle at 90% 8%,rgba(112,143,255,.28),transparent 28%),linear-gradient(135deg,#ffffff,#eef1ff 58%,#fcecf5);box-shadow:0 24px 70px rgba(48,59,106,.16)}.app-store-hero h1{max-width:900px;color:#171a26}.app-store-hero p{color:#4f5870}.app-store-main .bos-section{scroll-margin-top:88px}.app-store-main .bos-section>p{max-width:680px}.app-store-main .bos-card{min-height:126px;padding:14px;gap:11px;border-radius:16px;box-shadow:0 8px 22px rgba(35,48,84,.09)}.app-store-main .bos-card-icon{width:50px;height:50px;font-size:16px;font-weight:950;border-radius:12px}.app-store-main .bos-card-icon-blank{background:transparent}.app-store-main .bos-btn.secondary{background:#fff;border-color:#cfd5e3;color:#171a26}.app-store-main .bos-section-head{margin-bottom:12px}.app-store-main .bos-section-head h2{font-size:clamp(25px,3.5vw,38px)}.app-carousel{position:relative}.app-carousel .bos-grid{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:4px 3px 14px;margin-top:0;scrollbar-width:thin;scrollbar-color:#aab4d8 transparent}.app-carousel .bos-card{flex:0 0 clamp(245px,28vw,330px);scroll-snap-align:start}.app-carousel-controls{display:flex;gap:7px;position:absolute;right:0;top:-52px}.app-carousel-controls button{width:34px;height:34px;border:1px solid #cfd5e3;border-radius:50%;background:#fff;color:#313b59;font-size:1rem;cursor:pointer;box-shadow:0 4px 12px rgba(35,48,84,.08)}.app-carousel-controls button:hover{border-color:#5968db;color:#5968db}.app-carousel-hint{display:none;color:#7b8498;font-size:.72rem;margin:0 0 4px}@media(max-width:850px){.app-carousel .bos-card{flex-basis:clamp(240px,43vw,300px)}}
@media(max-width:560px){.app-store-main{width:min(100% - 18px,1320px)}.app-store-hero{padding:30px 18px}.app-store-hero h1{font-size:clamp(2.45rem,13vw,4rem)}.app-store-main .bos-card{min-height:118px}.app-store-main .bos-actions{display:grid;grid-template-columns:1fr}.app-store-main .bos-btn{width:100%}.app-carousel-controls{display:none}.app-carousel-hint{display:block}}html[data-theme="sunset"]{--bos-bg:#1a0d24;--bos-panel:#32133f;--bos-line:rgba(255,204,176,.2);--bos-text:#fff7f2;--bos-muted:#e5bdb5;--bos-purple:#ff8a62;--bos-pink:#c44c88}html[data-theme="sunset"] .bos-page{background:radial-gradient(circle at 80% 0,rgba(255,111,97,.3),transparent 30%),radial-gradient(circle at 12% 35%,rgba(255,179,71,.16),transparent 34%),linear-gradient(180deg,#32113d,#1d102b 48%,#0d1021)}html[data-theme="sunset"] .app-store-hero{background:radial-gradient(circle at 88% 8%,rgba(255,179,71,.28),transparent 30%),linear-gradient(135deg,#5f214e,#3a183f 58%,#27162f)}html[data-theme="sunset"] .bos-card{background:rgba(75,29,64,.76);border-color:rgba(255,204,176,.18)}html[data-theme="sunset"] .bos-btn.secondary{background:rgba(103,40,72,.56)}</style>
<script>
document.querySelectorAll('.app-store-main .bos-section').forEach((section,index)=>{
  const grid=section.querySelector('.bos-grid');
  if(!grid || grid.children.length < 3) return;
  const carousel=document.createElement('div'); carousel.className='app-carousel';
  grid.parentNode.insertBefore(carousel,grid); carousel.appendChild(grid);
  const controls=document.createElement('div'); controls.className='app-carousel-controls';
  controls.innerHTML='<button type="button" aria-label="Previous apps">←</button><button type="button" aria-label="Next apps">→</button>';
  carousel.appendChild(controls);
  const hint=document.createElement('p'); hint.className='app-carousel-hint'; hint.textContent='Swipe to browse apps →'; carousel.insertBefore(hint,grid);
  const amount=()=>Math.max(260,Math.round(grid.clientWidth*.72));
  controls.children[0].addEventListener('click',()=>grid.scrollBy({left:-amount(),behavior:'smooth'}));
  controls.children[1].addEventListener('click',()=>grid.scrollBy({left:amount(),behavior:'smooth'}));
});
</script>
<?php bos_page_end(); ?>
