<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond Academy');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#11152b"><title>Beyond Academy | Beyond OS</title><meta name="description" content="Choose a live learning experience across Beyond OS."><link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>"></head><body class="bos-page">
<main class="bos-main academy-main">
  <section class="bos-hero academy-hero"><span class="bos-kicker">Beyond Academy</span><h1>Learn it.<br>Prove it.</h1><p>Build real skills, complete guided lessons, pass assessments and earn verifiable Beyond-issued certificates.</p><div class="bos-actions"><a class="bos-btn secondary" href="<?=e(beyond_url('academy/verify.php'))?>">Verify a certificate</a></div></section>
  <section class="bos-section"><span class="bos-kicker">Beyond Certificates</span><h2>Three pathways. Real evidence of learning.</h2><p>Begin with Essential Math, Web Development Foundations, or Personal Finance Foundations. Every pathway includes tracked lessons, a scored assessment, a Beyond ID badge, and a public verification record.</p><div class="bos-grid">
    <?=bos_app_card('Essential Math','Numbers, percentages, measurement and everyday problem solving.','academy/pathway.php?course=essential-math','∑','Start pathway','@atom')?>
    <?=bos_app_card('Web Development Foundations','HTML, CSS, JavaScript, responsive design and accessibility.','academy/pathway.php?course=web-development-foundations','CODE','Start pathway','@atom')?>
    <?=bos_app_card('Personal Finance Foundations','Budgeting, saving, credit, interest and fraud awareness.','academy/pathway.php?course=personal-finance-foundations','$','Start pathway','@atom')?>
  </div></section>
  <section class="bos-section" id="academies"><span class="bos-kicker">Live learning</span><h2>Choose your academy</h2><p>These learning experiences are connected and ready to open now.</p><div class="bos-grid">
    <?=bos_app_card('Beyond Math Academy','5 modules · 10 lessons each · interactive lessons.','beyond-math/academy.php','BM','Open Academy','beyond-math/assets/img/beyond-math-logo.webp')?>
    <?=bos_app_card('Beyond Coding School','Web, iOS, Android, SVG, game and full-stack pathways.','coding-school/','CODE','Open School','@atom')?>
    <?=bos_app_card('Beyond French Academy','Daily language practice, lessons, tests and conversation challenges.','beyond-french/academy.php','BF','Open Academy','assets/icons/app-store/beyond-french.jpg')?>
    <?=bos_app_card('Bible Academy','Teen and Adult Scripture pathways, narrated lessons, checks, and module exams.','dailybreath/academy.php','DB','Open Academy','@atom')?>
    <?=bos_app_card('Beyond Space Academy','50 narrated astronomy lessons, mission labs, saved progress, and module exams.','beyond-space/academy.php','BS','Open Academy','assets/icons/app-store/beyond-space.jpg')?>
    <?=bos_app_card('Beyond Ancient Academy','World archaeology, primary sources, civilizations, artifact labs, and historical research.','beyond-ancient/academy.php','BA','Open Academy','assets/icons/app-store/beyond-ancient.jpg')?>
  </div></section>
</main>
<style>.academy-main{width:min(1240px,calc(100% - 28px))}.academy-hero{background:radial-gradient(circle at 85% 12%,color-mix(in srgb,var(--accent) 24%,transparent),transparent 28%),linear-gradient(135deg,var(--surface-deep),var(--surface-strong) 58%,color-mix(in srgb,var(--primary) 32%,var(--surface-deep)))}.academy-main .bos-card-icon{width:62px;height:62px}</style>
<?php bos_page_end(); ?>
