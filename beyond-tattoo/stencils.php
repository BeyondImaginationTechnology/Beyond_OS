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
    $folderName = sprintf('%02d-%s', $collectionIndex + 1, bt_stencil_asset_slug($title));
    $bundledFolder = sprintf('assets/stencils/%s/%s', $collectionSlug, $folderName);
    $uploadedFolder = sprintf('uploads/stencil-library/%s/%s', $collectionSlug, $folderName);
    $asset = static function (string $file) use ($bundledFolder, $uploadedFolder): string {
        return is_file(__DIR__ . '/' . $uploadedFolder . '/' . $file)
            ? $uploadedFolder . '/' . $file
            : $bundledFolder . '/' . $file;
    };
    $metadataPath = is_file(__DIR__ . '/' . $uploadedFolder . '/metadata.json')
        ? __DIR__ . '/' . $uploadedFolder . '/metadata.json'
        : __DIR__ . '/' . $bundledFolder . '/metadata.json';
    $metadata = is_file($metadataPath) ? json_decode((string)file_get_contents($metadataPath), true) : [];
    $status = is_array($metadata) ? strtolower(trim((string)($metadata['status'] ?? 'draft'))) : 'draft';
    return [
        'approved' => in_array($status, ['approved', 'published'], true),
        'metadata' => is_array($metadata) ? $metadata : [],
        'preview' => $asset('preview-watermarked.png'),
        'print_png' => $asset('stencil-print-ready.png'),
        'print_pdf' => $asset('stencil-print-ready.pdf'),
        'transfer' => $asset('studio-transfer-template.png'),
        'reference' => $asset('reference-artwork.webp'),
        'placement' => $asset('placement-mockup.webp'),
        'pack' => $asset('premium-packaging.webp'),
        'lore' => $asset('lore-card.webp'),
        'style' => $asset('style-card.webp'),
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
$searchQuery = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
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
        || preg_match('/raven|smoke|clock|gothic|cross|praying|sacred|scarab/', $haystack)) {
        $categories[] = 'black-grey';
    }

    if ($collection === 'Japanese Legends') {
        $categories[] = 'japanese';
    }

    if (preg_match('/scarab|hieroglyphic|egyptian sacred symbols|ornamental egyptian frame|oni|hannya|tiger|dragon/', $haystack)) {
        $categories[] = 'tribal';
    }

    if (preg_match('/solar eye|dove|crown and cross|sacred symbols|great wave|peony|cross|hourglass/', $haystack)) {
        $categories[] = 'minimalist';
    }

    if (in_array($collection, ['Divine Realism', 'Beyond Ancient'], true)
        || preg_match('/angel|cross|sacred|heaven|biblical|isis|osiris|pharaoh/', $haystack)) {
        $categories[] = 'sacred';
    }

    return array_values(array_unique($categories));
}

function bt_stencil_matches_search(string $title, string $collection, array $metadata, string $query): bool
{
    if ($query === '') return true;
    $searchable = strtolower(implode(' ', [
        $title,
        $collection,
        (string)($metadata['description'] ?? ''),
        (string)($metadata['style'] ?? ''),
        (string)($metadata['placement'] ?? ''),
        (string)($metadata['difficulty'] ?? ''),
        implode(' ', is_array($metadata['subjects'] ?? null) ? $metadata['subjects'] : []),
    ]));
    return str_contains($searchable, strtolower($query));
}

$availableCount = 0;
$visibleCount = 0;
foreach ($collections as $collectionSlug => $collection) {
    foreach ($collection['stencils'] as $collectionIndex => $item) {
        $releaseDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
        if ($releaseDate > $today) continue;
        $assets = bt_stencil_preview_assets($collectionSlug, $collectionIndex, $item[0]);
        if (!$assets['approved'] || !is_file(__DIR__ . '/' . $assets['preview']) || !is_file(__DIR__ . '/' . $assets['print_png'])) continue;
        $availableCount++;
        if (($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))
            && bt_stencil_matches_search($item[0], $collection['name'], $assets['metadata'], $searchQuery)) {
            $visibleCount++;
        }
    }
}
?>
<style>.bt-stencil-viewer-copy{max-height:calc(100dvh - 32px);overflow-y:auto}@media(max-width:760px){.bt-stencil-viewer-copy{max-height:38dvh}}</style>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Asset library</span><span>◆ Verified assets only</span><span>Artist focused</span><a href="<?= e($downloadFile) ?>" download>Current stencil pack →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" class="is-active">Stencils</a><a href="collections.php" >Collections</a><a href="studios.php">Studios</a><a href="about.php" >About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ ASSET-BACKED LIBRARY</p><h1><?= e((string)$availableCount) ?> VERIFIED<br><strong>STENCIL DROPS</strong></h1><p>Browse approved designs with real preview and print-master files. The 55-slot Season One schedule remains the publishing plan; only populated assets appear in this library.</p><div class="bt-main-actions"><a class="bt-glow-button" href="<?= e($downloadFile) ?>" download>↓ Download current stencil</a><a class="bt-outline-button" href="collections.php">Browse collections</a></div></div></section>
<section class="bt-page-section"><div class="bt-wrap">
  <form class="filter-row" method="get" role="search" style="margin-bottom:18px"><label class="sr-only" for="stencil-search">Search approved stencils</label><input class="input" id="stencil-search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search subject, style, placement, or difficulty"><?php if ($activeCategory !== ''): ?><input type="hidden" name="category" value="<?= e($activeCategory) ?>"><?php endif; ?><button class="bt-outline-button" type="submit">Search library</button></form>
  <?php if (($stencilDay['updated_at'] ?? '') !== '' && $searchQuery === '' && $activeCategory === ''): ?>
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
      $scheduledAssets = bt_stencil_preview_assets($slug, $index, $item[0]);
      $hasScheduledAssets = $scheduledAssets['approved'] && is_file(__DIR__ . '/' . $scheduledAssets['preview']) && is_file(__DIR__ . '/' . $scheduledAssets['print_png']);
      if ($scheduledDate <= $today && $hasScheduledAssets
          && ($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))
          && bt_stencil_matches_search($item[0], $collection['name'], $scheduledAssets['metadata'], $searchQuery)) {
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
      data-stencil-reference="<?= is_file(__DIR__ . '/' . $assets['reference']) ? e($assets['reference']) : '' ?>"
      data-stencil-placement="<?= is_file(__DIR__ . '/' . $assets['placement']) ? e($assets['placement']) : '' ?>"
      data-stencil-pack="<?= is_file(__DIR__ . '/' . $assets['pack']) ? e($assets['pack']) : '' ?>"
      data-stencil-lore="<?= is_file(__DIR__ . '/' . $assets['lore']) ? e($assets['lore']) : '' ?>"
      data-stencil-style="<?= is_file(__DIR__ . '/' . $assets['style']) ? e($assets['style']) : '' ?>"
    <?php endif; ?>
  >
    <div class="bt-schedule-number"><?= str_pad((string)$itemNumber,2,'0',STR_PAD_LEFT) ?></div>
    <div><time datetime="<?= e($item[1]) ?>"><?= e(bt_pretty_date($item[1])) ?></time><h3><?= e($item[0]) ?></h3><p><?= e((string)($assets['metadata']['style'] ?? $collection['name'])) ?> · <?= e((string)($assets['metadata']['placement'] ?? implode(' · ', array_map(static fn($cat) => $categoryOptions[$cat]['label'] ?? $cat, $itemCategories)))) ?></p></div>
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
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-reference hidden>Reference artwork</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-placement hidden>Placement mockup</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-pack hidden>Premium packaging</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-lore hidden>Lore card</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-style hidden>Style card</a>
      </div>
    </div>
  </section>
</div>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main>
<script>
(() => {
  const viewer = document.getElementById('bt-stencil-viewer');
  if (!viewer) return;
  const links = ['reference', 'placement', 'pack', 'lore', 'style'];
  const prepareAssetLinks = (card) => links.forEach((name) => {
    const link = viewer.querySelector(`[data-stencil-viewer-${name}]`);
    const url = card.dataset[`stencil${name[0].toUpperCase()}${name.slice(1)}`] || '';
    if (!link) return;
    if (url) { link.href = url; link.hidden = false; }
    else { link.removeAttribute('href'); link.hidden = true; }
  });
  document.querySelectorAll('[data-stencil-preview]').forEach((card) => {
    card.addEventListener('click', () => prepareAssetLinks(card));
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') prepareAssetLinks(card);
    });
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
