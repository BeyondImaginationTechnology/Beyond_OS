<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';
require_once __DIR__ . '/includes/library-catalog.php';
$disableBeyondShell = true;
$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$pageTitle = 'Collections — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
$collections = bt_library_collections();
?>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Free stencils every day</span><span>◆ Premium quality</span><span>Artist focused</span><a href="<?= e($downloadFile) ?>" download>Free stencil packs →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" >Stencils</a><a href="collections.php" class="is-active">Collections</a><a href="studios.php">Studios</a><a href="about.php" >About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ CURATED SEASON ONE</p><h1>FOUR DISTINCT<br><strong>COLLECTIONS</strong></h1><p>Every library has its own visual language, release window and tattoo-ready pack structure. No automated artwork—each stencil is individually designed and reviewed.</p></div></section>
<section class="bt-page-section"><div class="bt-wrap bt-collection-detail-grid"><?php foreach($collections as $slug=>$collection): ?><article id="<?= e($slug) ?>" class="bt-collection-detail bt-collection-detail--<?= e($slug) ?>"><div class="bt-collection-detail-image"><img src="<?= e($collection['image']) ?>" alt="<?= e($collection['name']) ?>"><span><?= e($collection['dates']) ?></span></div><div class="bt-collection-detail-copy"><p><?= e((string)$collection['count']) ?> STENCILS</p><h2><?= e($collection['name']) ?></h2><p><?= e($collection['description']) ?></p><div class="bt-name-list"><?php foreach($collection['stencils'] as $i=>$item): ?><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?> · <?= e($item[0]) ?></span><?php endforeach; ?></div><a class="bt-outline-button" href="stencils.php#<?= e($slug) ?>">View release schedule →</a></div></article><?php endforeach; ?></div></section>
<section class="bt-pack-contents"><div class="bt-wrap"><div class="bt-section-heading-row"><h2>Included in every finished drop</h2></div><div class="bt-pack-grid"><div><b>◇</b><strong>Print-ready linework</strong><small>Clean black-and-white high-resolution PNG</small></div><div><b>↔</b><strong>Mirrored transfer</strong><small>Prepared for thermal stencil workflows</small></div><div><b>▣</b><strong>Studio PDF</strong><small>Printable US Letter and A4 presentation</small></div><div><b>✦</b><strong>Promotion assets</strong><small>Watermarked preview and Remotion media</small></div></div></div></section>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
