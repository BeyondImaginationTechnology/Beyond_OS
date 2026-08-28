<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';
require_once __DIR__ . '/includes/asset-library.php';
$disableBeyondShell = true;
$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$pageTitle = 'Collections — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
$catalogCollections = bt_library_collections();
$actualAssets = bt_asset_library();
$assetsByCollection = [];
foreach ($actualAssets as $asset) {
    $assetsByCollection[$asset['collection_slug']][] = $asset;
}
$collections = [];
foreach ($catalogCollections as $slug => $collection) {
    if (!empty($assetsByCollection[$slug])) {
        $collections[$slug] = $collection;
    }
}
?>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Asset library</span><span>◆ Verified assets only</span><span>Artist focused</span><a href="<?= e($downloadFile) ?>" download>Current stencil pack →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" >Stencils</a><a href="collections.php" class="is-active">Collections</a><a href="studios.php">Studios</a><a href="about.php" >About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ ASSET-BACKED LIBRARY</p><h1>REAL FILES.<br><strong>READY TO USE.</strong></h1><p><?= e((string)count($actualAssets)) ?> approved stencil packs are available now. A collection appears here only after its preview and print master exist.</p></div></section>
<section class="bt-page-section"><div class="bt-wrap bt-collection-detail-grid"><?php foreach($collections as $slug=>$collection): $collectionAssets = $assetsByCollection[$slug]; ?><article id="<?= e($slug) ?>" class="bt-collection-detail bt-collection-detail--<?= e($slug) ?>"><div class="bt-collection-detail-image"><img src="<?= e($collectionAssets[0]['preview_url']) ?>" alt="<?= e($collection['name']) ?> verified stencil preview"><span><?= e($collection['dates']) ?></span></div><div class="bt-collection-detail-copy"><p><?= e((string)count($collectionAssets)) ?> VERIFIED <?= count($collectionAssets) === 1 ? 'ASSET' : 'ASSETS' ?></p><h2><?= e($collection['name']) ?></h2><p><?= e($collection['description']) ?></p><div class="bt-name-list"><?php foreach($collectionAssets as $asset): ?><span><?= str_pad((string)$asset['sequence'],2,'0',STR_PAD_LEFT) ?> · <?= e($asset['title']) ?></span><?php endforeach; ?></div><a class="bt-outline-button" href="stencils.php#<?= e($slug) ?>">View actual assets →</a></div></article><?php endforeach; ?></div></section>
<section class="bt-pack-contents"><div class="bt-wrap"><div class="bt-section-heading-row"><h2>Verified library standard</h2></div><div class="bt-pack-grid"><div><b>◇</b><strong>Print-ready linework</strong><small>Every listed asset has a real high-resolution PNG master</small></div><div><b>↔</b><strong>Transfer resources</strong><small>Shown only when a transfer template is present</small></div><div><b>▣</b><strong>Studio documents</strong><small>PDF and placement resources appear when supplied</small></div><div><b>✦</b><strong>Reference assets</strong><small>Preview, lore and artwork files remain tied to their source pack</small></div></div></div></section>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
