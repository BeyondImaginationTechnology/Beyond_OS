<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';
require_once __DIR__ . '/includes/library-catalog.php';
$disableBeyondShell = true;
$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$pageTitle = 'Stencils — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
$collections = bt_library_collections();
$today = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver'));

function bt_stencil_asset_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function bt_stencil_preview_assets(string $collectionSlug, int $collectionIndex, string $title): array
{
    $folder = sprintf('assets/stencils/%s/%02d-%s', $collectionSlug, $collectionIndex + 1, bt_stencil_asset_slug($title));
    return [
        'preview' => $folder . '/preview-watermarked.png',
        'print_png' => $folder . '/stencil-print-ready.png',
        'print_pdf' => $folder . '/stencil-print-ready.pdf',
        'transfer' => $folder . '/studio-transfer-template.png',
    ];
}

$categoryOptions = [
    'realism' => ['icon' => '☠', 'label' => 'Realism'],
    'black-grey' => ['icon' => '✿', 'label' => 'Black & Grey'],
    'japanese' => ['icon' => '〽', 'label' => 'Japanese'],
    'tribal' => ['icon' => '♜', 'label' => 'Tribal'],
    'minimalist' => ['icon' => '△', 'label' => 'Minimalist'],
    'sacred' => ['icon' => '◉', 'label' => 'Sacred'],
];
$activeCategory = isset($_GET['category']) ? strtolower(trim((string)$_GET['category'])) : '';
if (!isset($categoryOptions[$activeCategory])) {
    $activeCategory = '';
}

function bt_stencil_category_slugs(string $title, string $collection): array
{
    $categories = [];
    $haystack = strtolower($title . ' ' . $collection);

    if (in_array($collection, ['Divine Realism', 'Dark Realism'], true)
        || preg_match('/portrait|realism|statue|angel|reaper|skull|pharaoh|sek?hmet|isis|osiris|bastet/', $haystack)) {
        $categories[] = 'realism';
    }

    if (in_array($collection, ['Dark Realism', 'Divine Realism', 'Beyond Ancient'], true)
        || preg_match('/raven|smoke|clock|gothic|cross|praying|sacred|anubis|scarab/', $haystack)) {
        $categories[] = 'black-grey';
    }

    if ($collection === 'Japanese Legends') {
        $categories[] = 'japanese';
    }

    if (preg_match('/anubis|scarab|hieroglyphic|egyptian sacred symbols|ornamental egyptian frame|oni|hannya|tiger|dragon/', $haystack)) {
        $categories[] = 'tribal';
    }

    if (preg_match('/eye of horus|dove|crown and cross|sacred symbols|great wave|peony|cross|hourglass/', $haystack)) {
        $categories[] = 'minimalist';
    }

    if (in_array($collection, ['Divine Realism', 'Beyond Ancient'], true)
        || preg_match('/angel|cross|sacred|heaven|biblical|isis|osiris|anubis|horus|pharaoh/', $haystack)) {
        $categories[] = 'sacred';
    }

    return array_values(array_unique($categories));
}

$availableCount = 0;
$visibleCount = 0;
foreach ($collections as $collection) {
    foreach ($collection['stencils'] as $item) {
        $releaseDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
        if ($releaseDate > $today) continue;
        $availableCount++;
        if ($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true)) {
            $visibleCount++;
        }
    }
}
?>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Free stencils every day</span><span>◆ Premium quality</span><span>Artist focused</span><a href="<?= e($downloadFile) ?>" download>Free stencil packs →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" class="is-active">Stencils</a><a href="collections.php" >Collections</a><a href="studios.php">Studios</a><a href="about.php" >About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ SEASON ONE LIBRARY</p><h1><?= e((string)$availableCount) ?> AVAILABLE<br><strong>STENCIL DROPS</strong></h1><p>Browse every design released through <?= e($today->format('F j, Y')) ?>. New Season One stencils unlock daily through September 9 with clean linework, printable files, placement guidance and matching media.</p><div class="bt-main-actions"><a class="bt-glow-button" href="<?= e($downloadFile) ?>" download>↓ Download today’s stencil</a><a class="bt-outline-button" href="collections.php">Browse collections</a></div></div></section>
<section class="bt-page-section"><div class="bt-wrap">
  <?php if (($stencilDay['updated_at'] ?? '') !== ''): ?>
  <section class="bt-library-group" id="studio-release"><div class="bt-library-heading"><div><p>BEYOND STUDIO RELEASE</p><h2>Latest published stencil</h2></div><span>Live now</span></div><div class="bt-stencil-schedule-grid"><article class="bt-schedule-card is-current is-unlocked" role="button" tabindex="0" aria-haspopup="dialog" aria-label="View <?= e($stencilDay['title']) ?> stencil" data-stencil-preview="<?= e($stencilDay['preview_url']) ?>" data-stencil-title="<?= e($stencilDay['title']) ?>" data-stencil-collection="<?= e($stencilDay['collection']) ?>" data-stencil-date="<?= e($stencilDay['display_date']) ?>" data-stencil-download="<?= e($stencilDay['transfer_png_url']) ?>"><div class="bt-schedule-number">AI</div><div><time datetime="<?= e($stencilDay['iso_date']) ?>"><?= e($stencilDay['display_date']) ?></time><h3><?= e($stencilDay['title']) ?></h3><p><?= e($stencilDay['description']) ?></p></div><span>View stencil</span></article></div></section>
  <?php endif; ?>
  <div class="bt-category-browser" aria-label="Browse stencils by category">
    <a class="<?= $activeCategory === '' ? 'is-active' : '' ?>" href="stencils.php"><b>▦</b><span>All</span><small><?= e((string)$availableCount) ?></small></a>
    <?php foreach ($categoryOptions as $slug => $option): ?>
      <a class="<?= $activeCategory === $slug ? 'is-active' : '' ?>" href="stencils.php?category=<?= e($slug) ?>"><b><?= e($option['icon']) ?></b><span><?= e($option['label']) ?></span></a>
    <?php endforeach; ?>
  </div>
  <div class="bt-category-results"><strong><?= e((string)$visibleCount) ?> available stencil<?= $visibleCount === 1 ? '' : 's' ?></strong><?php if ($activeCategory !== ''): ?><span>in <?= e($categoryOptions[$activeCategory]['label']) ?></span><a href="stencils.php">Clear filter ×</a><?php else: ?><span>released through <?= e($today->format('M j')) ?></span><?php endif; ?></div>

  <?php $number=1; foreach($collections as $slug=>$collection):
    $matchingItems = [];
    foreach ($collection['stencils'] as $index => $item) {
      $itemNumber = $number++;
      $scheduledDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
      if ($scheduledDate <= $today && ($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))) {
        $matchingItems[] = [$item, $itemNumber, $index];
      }
    }
    if (!$matchingItems) { continue; }
  ?>
  <section class="bt-library-group" id="<?= e($slug) ?>"><div class="bt-library-heading"><div><p><?= e($collection['dates']) ?></p><h2><?= e($collection['name']) ?></h2></div><span><?= e((string)count($matchingItems)) ?> shown</span></div><div class="bt-stencil-schedule-grid">
  <?php foreach($matchingItems as $matching):
    $item=$matching[0];
    $itemNumber=$matching[1];
    $collectionIndex=$matching[2];
    $itemCategories=bt_stencil_category_slugs($item[0], $collection['name']);
    $releaseDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
    $assets = bt_stencil_preview_assets($slug, $collectionIndex, $item[0]);
    $hasPreview = is_file(__DIR__ . '/' . $assets['preview']);
    $isUnlocked = $releaseDate <= $today && $hasPreview;
    $isCurrent = $item[0] === $stencilDay['title'];
  ?>
  <article
    class="bt-schedule-card <?= $isCurrent?'is-current':'' ?> <?= $isUnlocked?'is-unlocked':'' ?>"
    <?php if ($isUnlocked): ?>
      role="button"
      tabindex="0"
      aria-haspopup="dialog"
      aria-label="View <?= e($item[0]) ?> stencil"
      data-stencil-preview="<?= e($assets['preview']) ?>"
      data-stencil-title="<?= e($item[0]) ?>"
      data-stencil-collection="<?= e($collection['name']) ?>"
      data-stencil-date="<?= e(bt_pretty_date($item[1])) ?>"
      data-stencil-download="<?= is_file(__DIR__ . '/' . $assets['print_png']) ? e($assets['print_png']) : '' ?>"
      data-stencil-pdf="<?= is_file(__DIR__ . '/' . $assets['print_pdf']) ? e($assets['print_pdf']) : '' ?>"
    <?php endif; ?>
  >
    <div class="bt-schedule-number"><?= str_pad((string)$itemNumber,2,'0',STR_PAD_LEFT) ?></div>
    <div><time datetime="<?= e($item[1]) ?>"><?= e(bt_pretty_date($item[1])) ?></time><h3><?= e($item[0]) ?></h3><p><?= e($collection['name']) ?> · <?= e(implode(' · ', array_map(static fn($cat) => $categoryOptions[$cat]['label'] ?? $cat, $itemCategories))) ?></p></div>
    <span><?= $isUnlocked?'View stencil':'Available' ?></span>
  </article><?php endforeach; ?>
  </div></section><?php endforeach; ?>
</div></section>

<div class="bt-stencil-viewer" id="bt-stencil-viewer" hidden aria-hidden="true">
  <button class="bt-stencil-viewer-backdrop" type="button" data-stencil-close aria-label="Close stencil preview"></button>
  <section class="bt-stencil-viewer-dialog" role="dialog" aria-modal="true" aria-labelledby="bt-stencil-viewer-title">
    <button class="bt-stencil-viewer-close" type="button" data-stencil-close aria-label="Close stencil preview">×</button>
    <div class="bt-stencil-viewer-art"><img src="" alt="" data-stencil-viewer-image></div>
    <div class="bt-stencil-viewer-copy">
      <p data-stencil-viewer-meta>Unlocked stencil</p>
      <h2 id="bt-stencil-viewer-title" data-stencil-viewer-title>Stencil preview</h2>
      <p class="bt-stencil-viewer-note">Watermarked preview. Open the print-ready file for studio use.</p>
      <div class="bt-stencil-viewer-actions">
        <a class="bt-glow-button" href="#" download data-stencil-viewer-download hidden>↓ Download PNG</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-pdf hidden>Open printable PDF</a>
      </div>
    </div>
  </section>
</div>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
