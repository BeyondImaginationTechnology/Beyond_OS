<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';
require_once __DIR__ . '/includes/library-catalog.php';
$disableBeyondShell = true;
$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$outlineFile = $stencilDay['outline_png_url'] ?? $downloadFile;
$pageTitle = 'Stencils — Beyond Tattoo';
$pageDescription = 'Browse Beyond Tattoo stencil collections, placement references, print-ready artwork and studio transfer resources.';
$pageCanonical = 'https://beyondimagination.co.technology/beyond-tattoo/stencils.php';
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
        $candidates = [$file];
        if (str_ends_with($file, '.png') || str_ends_with($file, '.webp')) {
            $extensionPosition = strrpos($file, '.');
            if ($extensionPosition !== false) $candidates[] = substr($file, 0, $extensionPosition) . '.jpg';
        }
        foreach ($candidates as $candidate) {
            if (is_file(__DIR__ . '/' . $uploadedFolder . '/' . $candidate)) return $uploadedFolder . '/' . $candidate;
            if (is_file(__DIR__ . '/' . $bundledFolder . '/' . $candidate)) return $bundledFolder . '/' . $candidate;
        }
        return $bundledFolder . '/' . $file;
    };
    $metadataPath = is_file(__DIR__ . '/' . $uploadedFolder . '/metadata.json')
        ? __DIR__ . '/' . $uploadedFolder . '/metadata.json'
        : __DIR__ . '/' . $bundledFolder . '/metadata.json';
    $metadata = is_file($metadataPath) ? json_decode((string)file_get_contents($metadataPath), true) : [];
    $status = is_array($metadata) ? strtolower(trim((string)($metadata['status'] ?? 'draft'))) : 'draft';
    $assets = [
        'approved' => in_array($status, ['approved', 'published'], true),
        'metadata' => is_array($metadata) ? $metadata : [],
        'preview' => $asset('preview-watermarked.png'),
        'outline_png' => $asset('stencil-outline.png'),
        'print_png' => $asset('stencil-print-ready.png'),
        'print_pdf' => $asset('stencil-print-ready.pdf'),
        'transfer' => $asset('studio-transfer-template.png'),
        'reference' => $asset('reference-artwork.webp'),
        'detail' => $asset('detail-artwork.webp'),
        'placement' => $asset('placement-mockup.webp'),
        'pack' => $asset('premium-packaging.webp'),
        'lore' => $asset('lore-card.webp'),
        'style' => $asset('style-card.webp'),
    ];
    if (!is_file(__DIR__ . '/' . $assets['print_png']) && is_file(__DIR__ . '/' . $assets['outline_png'])) {
        $assets['print_png'] = $assets['outline_png'];
    }
    return $assets;
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
$filterStyle = mb_substr(strtolower(trim((string)($_GET['style'] ?? ''))), 0, 80);
$filterPlacement = mb_substr(strtolower(trim((string)($_GET['placement'] ?? ''))), 0, 80);
$filterDifficulty = mb_substr(strtolower(trim((string)($_GET['difficulty'] ?? ''))), 0, 40);
$filterCollection = mb_substr(strtolower(trim((string)($_GET['collection'] ?? ''))), 0, 80);
$filterFrom = trim((string)($_GET['from'] ?? ''));
$filterTo = trim((string)($_GET['to'] ?? ''));
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

function bt_stencil_matches_filters(string $title, string $collection, array $metadata, string $style, string $placement, string $difficulty, string $collectionFilter, string $from, string $to, string $releaseDate): bool
{
    $contains = static fn(string $value, string $needle): bool => $needle === '' || str_contains(strtolower($value), $needle);
    if (!$contains((string)($metadata['style'] ?? ''), $style)) return false;
    if (!$contains((string)($metadata['placement'] ?? ''), $placement)) return false;
    if (!$contains((string)($metadata['difficulty'] ?? ''), $difficulty)) return false;
    if ($collectionFilter !== '' && !str_contains(strtolower($collection), $collectionFilter)) return false;
    if ($from !== '' && $releaseDate < $from) return false;
    if ($to !== '' && $releaseDate > $to) return false;
    return true;
}

$seasonTotal = 55;
$availableCount = 0;
$visibleCount = 0;
$scheduleNumber = 0;
foreach ($collections as $collectionSlug => $collection) {
    foreach ($collection['stencils'] as $collectionIndex => $item) {
        $isOpeningBonus = $collectionSlug === 'season-one-opening' && $collectionIndex >= 4;
        if (!$isOpeningBonus) {
            if ($scheduleNumber >= $seasonTotal) break 2;
            $scheduleNumber++;
        }
        $releaseDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
        if ($releaseDate > $today) continue;
        $assets = bt_stencil_preview_assets($collectionSlug, $collectionIndex, $item[0]);
        if (!$assets['approved'] || !is_file(__DIR__ . '/' . $assets['preview']) || !is_file(__DIR__ . '/' . $assets['print_png'])) continue;
        $availableCount++;
        if (($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))
            && bt_stencil_matches_search($item[0], $collection['name'], $assets['metadata'], $searchQuery)
            && bt_stencil_matches_filters($item[0], $collection['name'], $assets['metadata'], $filterStyle, $filterPlacement, $filterDifficulty, $filterCollection, $filterFrom, $filterTo, $item[1])) {
            $visibleCount++;
        }
    }
}
?>
<style>.bt-stencil-viewer-copy{max-height:calc(100dvh - 32px);overflow-y:auto}@media(max-width:760px){.bt-stencil-viewer-copy{max-height:38dvh}}</style>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Asset library</span><span>◆ Verified assets only</span><span>Artist focused</span><a href="<?= e($outlineFile) ?>" target="_blank" rel="noopener">Print current outline →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" class="is-active">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($outlineFile) ?>" target="_blank" rel="noopener">⌘ Print outline</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ ASSET-BACKED LIBRARY</p><h1><?= e((string)$availableCount) ?> VERIFIED<br><strong>STENCIL DROPS</strong></h1><p>Browse approved designs with real preview and print-master files. Season One has 55 numbered drops, plus two bonus opening designs; only populated assets appear in this library.</p><div class="bt-main-actions"><a class="bt-glow-button" href="<?= e($outlineFile) ?>" target="_blank" rel="noopener">⌘ Print current outline</a><a class="bt-outline-button" href="collections.php">Browse collections</a></div></div></section>
<section class="bt-page-section"><div class="bt-wrap">
  <form class="filter-row" method="get" role="search" style="margin-bottom:18px"><label class="sr-only" for="stencil-search">Search approved stencils</label><input class="input" id="stencil-search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search subject, style, placement, or difficulty"><?php if ($activeCategory !== ''): ?><input type="hidden" name="category" value="<?= e($activeCategory) ?>"><?php endif; ?><select class="input" name="style" aria-label="Filter by style"><option value="">All styles</option><option value="black-and-grey" <?= $filterStyle === 'black-and-grey' ? 'selected' : '' ?>>Black &amp; grey</option><option value="realism" <?= $filterStyle === 'realism' ? 'selected' : '' ?>>Realism</option><option value="japanese" <?= $filterStyle === 'japanese' ? 'selected' : '' ?>>Japanese</option></select><select class="input" name="difficulty" aria-label="Filter by difficulty"><option value="">All difficulty</option><option value="intermediate" <?= $filterDifficulty === 'intermediate' ? 'selected' : '' ?>>Intermediate</option><option value="advanced" <?= $filterDifficulty === 'advanced' ? 'selected' : '' ?>>Advanced</option></select><input class="input" name="placement" value="<?= e($filterPlacement) ?>" placeholder="Placement"><input class="input" name="collection" value="<?= e($filterCollection) ?>" placeholder="Collection"><input class="input" type="date" name="from" value="<?= e($filterFrom) ?>" aria-label="Release date from"><input class="input" type="date" name="to" value="<?= e($filterTo) ?>" aria-label="Release date to"><button class="bt-outline-button" type="submit">Filter library</button></form>
  <?php if (($stencilDay['updated_at'] ?? '') !== '' && $searchQuery === '' && $activeCategory === ''): ?>
  <section class="bt-library-group" id="studio-release"><div class="bt-library-heading"><div><p>BEYOND STUDIO RELEASE</p><h2>Latest published stencil</h2></div><span>Live now</span></div><div class="bt-stencil-schedule-grid"><article class="bt-schedule-card is-current is-unlocked" role="button" tabindex="0" aria-haspopup="dialog" aria-label="View <?= e($stencilDay['title']) ?> stencil" data-stencil-preview="<?= e($stencilDay['preview_url']) ?>" data-stencil-title="<?= e($stencilDay['title']) ?>" data-stencil-collection="<?= e($stencilDay['collection']) ?>" data-stencil-date="<?= e($stencilDay['display_date']) ?>" data-stencil-download="<?= e($stencilDay['transfer_png_url']) ?>" data-stencil-outline="<?= e($stencilDay['outline_png_url'] ?? '') ?>" data-stencil-pdf="<?= e($stencilDay['transfer_pdf_url'] ?? '') ?>" data-stencil-reference="<?= e($stencilDay['reference_image_url'] ?? '') ?>" data-stencil-placement="<?= e($stencilDay['placement_image_url'] ?? '') ?>" data-stencil-pack="<?= ($stencilDay['pack_image_url'] ?? '') !== ($stencilDay['preview_url'] ?? '') ? e($stencilDay['pack_image_url'] ?? '') : '' ?>" data-stencil-lore="<?= e($stencilDay['lore_card_url'] ?? '') ?>" data-stencil-style="<?= e($stencilDay['style_card_url'] ?? '') ?>" data-stencil-zip="<?= e($stencilDay['package_url'] ?? '') ?>"><div class="bt-schedule-number">AI</div><div><time datetime="<?= e($stencilDay['iso_date']) ?>"><?= e($stencilDay['display_date']) ?></time><h3><?= e($stencilDay['title']) ?></h3><p><?= e($stencilDay['description']) ?></p></div><span>View stencil</span></article></div></section>
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
      $isOpeningBonus = $slug === 'season-one-opening' && $index >= 4;
      if ($isOpeningBonus) {
        $itemNumber = 0;
      } else {
        if ($number > $seasonTotal) break 2;
        $itemNumber = $number++;
      }
      $scheduledDate = new DateTimeImmutable($item[1], new DateTimeZone('America/Vancouver'));
      $scheduledAssets = bt_stencil_preview_assets($slug, $index, $item[0]);
      $hasScheduledAssets = $scheduledAssets['approved'] && is_file(__DIR__ . '/' . $scheduledAssets['preview']) && is_file(__DIR__ . '/' . $scheduledAssets['print_png']);
      if ($scheduledDate <= $today
          && ($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))
          && bt_stencil_matches_search($item[0], $collection['name'], $scheduledAssets['metadata'], $searchQuery)
          && bt_stencil_matches_filters($item[0], $collection['name'], $scheduledAssets['metadata'], $filterStyle, $filterPlacement, $filterDifficulty, $filterCollection, $filterFrom, $filterTo, $item[1])) {
        $matchingItems[] = [$item, $itemNumber, $index];
      }
    }
    if (!$matchingItems) { if ($number > $seasonTotal) break; continue; }
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
      data-stencil-outline="<?= is_file(__DIR__ . '/' . $assets['outline_png']) ? e($assets['outline_png']) : '' ?>"
      data-stencil-pdf="<?= is_file(__DIR__ . '/' . $assets['print_pdf']) ? e($assets['print_pdf']) : '' ?>"
      data-stencil-reference="<?= is_file(__DIR__ . '/' . $assets['reference']) ? e($assets['reference']) : '' ?>"
      data-stencil-detail="<?= is_file(__DIR__ . '/' . $assets['detail']) ? e($assets['detail']) : '' ?>"
      data-stencil-placement="<?= is_file(__DIR__ . '/' . $assets['placement']) ? e($assets['placement']) : '' ?>"
      data-stencil-pack="<?= is_file(__DIR__ . '/' . $assets['pack']) ? e($assets['pack']) : '' ?>"
      data-stencil-lore="<?= is_file(__DIR__ . '/' . $assets['lore']) ? e($assets['lore']) : '' ?>"
      data-stencil-style="<?= is_file(__DIR__ . '/' . $assets['style']) ? e($assets['style']) : '' ?>"
      data-stencil-zip="api/stencil-download.php?type=package&amp;id=<?= e(bt_stencil_asset_slug($item[0]) . '-' . $item[1]) ?>"
    <?php endif; ?>
  >
    <div class="bt-schedule-number"><?= $itemNumber > 0 ? str_pad((string)max(1,(int)($assets['metadata']['season_drop'] ?? $itemNumber)),2,'0',STR_PAD_LEFT) : 'OPENING BONUS' ?></div>
    <div><time datetime="<?= e($item[1]) ?>"><?= e(bt_pretty_date($item[1])) ?></time><h3><?= e($item[0]) ?></h3><p><?= e((string)($assets['metadata']['style'] ?? $collection['name'])) ?> · <?= e((string)($assets['metadata']['placement'] ?? implode(' · ', array_map(static fn($cat) => $categoryOptions[$cat]['label'] ?? $cat, $itemCategories)))) ?> · <?= e((string)($assets['metadata']['license'] ?? 'Professional use')) ?></p></div>
    <span><?= $isUnlocked?'View stencil':($releaseDate > $today ? 'Upcoming' : 'Available') ?></span><?php if ($isUnlocked): ?><button class="bt-save-stencil" type="button" data-save-stencil="<?= e($item[0]) ?>" aria-label="Save <?= e($item[0]) ?>">☆ Save</button><?php endif; ?>
  </article><?php endforeach; ?>
  </div></section><?php endforeach; ?>
</div></section>

<style>
.bt-stencil-viewer-art{position:relative;display:flex!important;flex-direction:column;align-items:stretch!important;justify-content:center;gap:12px;min-width:0}
.bt-stencil-carousel-stage{position:relative;display:grid;place-items:center;min-height:0;flex:1;touch-action:pan-y;cursor:grab}
.bt-stencil-carousel-stage img{width:100%;height:100%;max-height:68dvh;object-fit:contain;background:#fff;border-radius:8px;box-shadow:0 18px 55px rgba(0,0,0,.45);user-select:none;-webkit-user-drag:none}
.bt-stencil-carousel-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:2;width:42px;height:42px;border:1px solid rgba(255,255,255,.35);border-radius:50%;background:rgba(5,3,8,.82);color:#fff;font-size:1.7rem;line-height:1;cursor:pointer}
.bt-stencil-carousel-nav:disabled{opacity:.42;cursor:default}.bt-stencil-carousel-prev{left:10px}.bt-stencil-carousel-next{right:10px}
.bt-stencil-carousel-footer{display:grid;grid-template-columns:1fr auto;gap:5px 12px;align-items:center;color:#fff;font-size:.78rem;font-weight:800}
.bt-stencil-carousel-count{color:#c9b9cf;font-size:.7rem;font-weight:700}.bt-stencil-carousel-dots{grid-column:1/-1;display:flex;justify-content:center;gap:7px;padding:4px}
.bt-stencil-carousel-dots{overflow-x:auto;justify-content:flex-start;scrollbar-width:thin;scrollbar-color:#d3a452 #25102f}.bt-stencil-carousel-dot{flex:none;width:74px;min-height:78px;padding:4px;border:2px solid transparent;border-radius:8px;background:#241b29;color:#fff;cursor:pointer;font-size:.61rem;line-height:1.1}.bt-stencil-carousel-dot img{width:100%;height:48px;object-fit:cover;border-radius:4px}.bt-stencil-carousel-dot[aria-current=true]{border-color:#d3a452;background:#46304f}
.bt-stencil-viewer-actions [hidden]{display:none!important}
@media(max-width:760px){.bt-stencil-carousel-stage img{max-height:48dvh}.bt-stencil-carousel-nav{width:38px;height:38px}.bt-stencil-viewer-art{height:54dvh!important;padding:14px!important}}
</style>
<div class="bt-stencil-viewer" id="bt-stencil-viewer" hidden aria-hidden="true">
  <button class="bt-stencil-viewer-backdrop" type="button" data-stencil-close aria-label="Close stencil preview"></button>
  <section class="bt-stencil-viewer-dialog" role="dialog" aria-modal="true" aria-labelledby="bt-stencil-viewer-title">
    <button class="bt-stencil-viewer-close" type="button" data-stencil-close aria-label="Close stencil preview">×</button>
    <div class="bt-stencil-viewer-art" role="region" aria-label="Stencil asset carousel" aria-roledescription="carousel">
      <div class="bt-stencil-carousel-stage" data-stencil-carousel-stage>
        <button class="bt-stencil-carousel-nav bt-stencil-carousel-prev" type="button" data-stencil-carousel-prev aria-label="Previous asset">‹</button>
        <img src="" alt="" data-stencil-viewer-image>
        <button class="bt-stencil-carousel-nav bt-stencil-carousel-next" type="button" data-stencil-carousel-next aria-label="Next asset">›</button>
      </div>
      <div class="bt-stencil-carousel-footer"><span data-stencil-carousel-label>Official outline stencil</span><span class="bt-stencil-carousel-count" data-stencil-carousel-count aria-live="polite"></span><div class="bt-stencil-carousel-dots" data-stencil-carousel-dots aria-label="Scroll assets and choose one"></div></div>
    </div>
    <div class="bt-stencil-viewer-copy">
      <p data-stencil-viewer-meta>Unlocked stencil</p>
      <h2 id="bt-stencil-viewer-title" data-stencil-viewer-title>Stencil preview</h2>
      <p class="bt-stencil-viewer-note">Official outline first, followed by the other available stencil assets.</p>
      <div class="bt-stencil-viewer-actions">
        <a class="bt-glow-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-download hidden>Open print-ready stencil</a>
        <a class="bt-glow-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-outline hidden>⌘ Print outline stencil</a>
        <a class="bt-glow-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-current hidden>Open current asset</a>
        <a class="bt-glow-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-zip hidden>Open asset package</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-pdf hidden>Open printable PDF</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-reference hidden>View reference artwork</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-detail hidden>View detail artwork</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-placement hidden>View placement mockup</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-pack hidden>View packaging</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-lore hidden>View lore card</a>
        <a class="bt-outline-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-style hidden>View style card</a>
      </div>
    </div>
  </section>
</div>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($outlineFile) ?>" target="_blank" rel="noopener">⌘ Print today’s outline</a>
</main>
<script>
(() => {
  const viewer = document.getElementById('bt-stencil-viewer');
  if (!viewer) return;
  const image = viewer.querySelector('[data-stencil-viewer-image]');
  const title = viewer.querySelector('[data-stencil-viewer-title]');
  const meta = viewer.querySelector('[data-stencil-viewer-meta]');
  const label = viewer.querySelector('[data-stencil-carousel-label]');
  const counter = viewer.querySelector('[data-stencil-carousel-count]');
  const dots = viewer.querySelector('[data-stencil-carousel-dots]');
  const previous = viewer.querySelector('[data-stencil-carousel-prev]');
  const next = viewer.querySelector('[data-stencil-carousel-next]');
  const download = viewer.querySelector('[data-stencil-viewer-download]');
  const outlineDownload = viewer.querySelector('[data-stencil-viewer-outline]');
  const pdfDownload = viewer.querySelector('[data-stencil-viewer-pdf]');
  const currentDownload = viewer.querySelector('[data-stencil-viewer-current]');
  const zipDownload = viewer.querySelector('[data-stencil-viewer-zip]');
  const links = ['outline', 'reference', 'detail', 'placement', 'pack', 'lore', 'style'];
  let currentCard = null;
  let slides = [];
  let activeSlide = 0;
  let pointerStart = null;
  const setLink = (link, url) => {
    if (!link) return;
    if (url) { link.href = url; link.hidden = false; }
    else { link.removeAttribute('href'); link.hidden = true; }
  };
  const prepareAssetLinks = (card) => links.forEach((name) => {
    const link = viewer.querySelector(`[data-stencil-viewer-${name}]`);
    const url = card.dataset[`stencil${name[0].toUpperCase()}${name.slice(1)}`] || '';
    setLink(link, url);
  });
  const showSlide = (index) => {
    if (!slides.length) return;
    activeSlide = (index + slides.length) % slides.length;
    const slide = slides[activeSlide];
    image.src = slide.url;
    image.alt = `${currentCard.dataset.stencilTitle || 'Stencil'} — ${slide.label}`;
    label.textContent = slide.label;
    setLink(currentDownload, slide.url);
    counter.textContent = `${activeSlide + 1} / ${slides.length}`;
    previous.disabled = slides.length < 2;
    next.disabled = slides.length < 2;
    dots.querySelectorAll('button').forEach((dot, dotIndex) => dot.setAttribute('aria-current', dotIndex === activeSlide ? 'true' : 'false'));
    dots.children[activeSlide]?.scrollIntoView({block: 'nearest', inline: 'nearest'});
  };
  const open = (card) => {
    currentCard = card;
    const titleText = card.dataset.stencilTitle || 'Stencil preview';
    const urls = [
      ['Official outline stencil', card.dataset.stencilOutline],
      ['Print-ready stencil', card.dataset.stencilDownload],
      ['Preview', card.dataset.stencilPreview],
      ['Reference artwork', card.dataset.stencilReference],
      ['Detail artwork', card.dataset.stencilDetail],
      ['Placement mockup', card.dataset.stencilPlacement],
      ['Premium packaging', card.dataset.stencilPack],
      ['Lore card', card.dataset.stencilLore],
      ['Style card', card.dataset.stencilStyle],
    ];
    const seen = new Set();
    slides = urls.filter(([assetLabel, url]) => {
      if (!url || seen.has(url)) return false;
      seen.add(url);
      return true;
    }).map(([assetLabel, url]) => ({label: assetLabel, url}));
    title.textContent = titleText;
    meta.textContent = [card.dataset.stencilCollection, card.dataset.stencilDate, 'Unlocked'].filter(Boolean).join(' · ');
    setLink(download, card.dataset.stencilDownload || '');
    setLink(outlineDownload, card.dataset.stencilOutline || '');
    setLink(pdfDownload, card.dataset.stencilPdf || '');
    setLink(zipDownload, card.dataset.stencilZip || '');
    prepareAssetLinks(card);
    dots.replaceChildren(...slides.map((slide, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'bt-stencil-carousel-dot';
      dot.setAttribute('aria-label', `Show ${slide.label}`);
      const thumbnail = document.createElement('img');
      thumbnail.src = slide.url;
      thumbnail.alt = '';
      thumbnail.loading = 'lazy';
      dot.append(thumbnail, document.createTextNode(slide.label));
      dot.addEventListener('click', () => showSlide(index));
      return dot;
    }));
    showSlide(0);
    viewer.hidden = false;
    viewer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('bt-modal-open');
    viewer.querySelector('[data-stencil-close]')?.focus();
  };
  const close = () => {
    viewer.hidden = true;
    viewer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('bt-modal-open');
    image.removeAttribute('src');
    currentCard?.focus();
    currentCard = null;
  };
  previous.addEventListener('click', () => showSlide(activeSlide - 1));
  next.addEventListener('click', () => showSlide(activeSlide + 1));
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('pointerdown', (event) => {
    pointerStart = {x: event.clientX, y: event.clientY};
  });
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('pointerup', (event) => {
    if (!pointerStart) return;
    const dx = event.clientX - pointerStart.x;
    const dy = event.clientY - pointerStart.y;
    pointerStart = null;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) showSlide(activeSlide + (dx < 0 ? 1 : -1));
  });
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('wheel', (event) => {
    if (slides.length < 2 || Math.abs(event.deltaX) <= Math.abs(event.deltaY)) return;
    event.preventDefault();
    showSlide(activeSlide + (event.deltaX > 0 ? 1 : -1));
  }, {passive: false});
  document.querySelectorAll('[data-stencil-preview]').forEach((card) => {
    card.addEventListener('click', (event) => { event.stopImmediatePropagation(); open(card); });
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); event.stopImmediatePropagation(); open(card); }
    });
  });
  viewer.querySelectorAll('[data-stencil-close]').forEach((button) => button.addEventListener('click', (event) => { event.stopImmediatePropagation(); close(); }));
  document.addEventListener('keydown', (event) => {
    if (viewer.hidden) return;
    if (event.key === 'ArrowLeft') { event.preventDefault(); showSlide(activeSlide - 1); }
    else if (event.key === 'ArrowRight') { event.preventDefault(); showSlide(activeSlide + 1); }
    else if (event.key === 'Escape') { event.stopImmediatePropagation(); close(); }
  });
  const savedKey = 'beyond-tattoo-saved-stencils';
  const saved = new Set(JSON.parse(localStorage.getItem(savedKey) || '[]'));
  document.querySelectorAll('[data-save-stencil]').forEach((button) => {
    const title = button.dataset.saveStencil || '';
    const sync = () => { button.textContent = saved.has(title) ? '★ Saved' : '☆ Save'; button.setAttribute('aria-pressed', saved.has(title) ? 'true' : 'false'); };
    sync();
    button.addEventListener('click', (event) => { event.stopPropagation(); saved.has(title) ? saved.delete(title) : saved.add(title); localStorage.setItem(savedKey, JSON.stringify([...saved])); sync(); });
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
