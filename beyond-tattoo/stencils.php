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
        'violet_trace' => $asset('stencil-violet-trace.png'),
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
$sort = strtolower(trim((string)($_GET['sort'] ?? 'newest')));
if (!in_array($sort, ['newest', 'oldest', 'title'], true)) $sort = 'newest';
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

    if (in_array($collection, ['Japanese Legends', 'Beyond Originals · Japanese'], true)) {
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
<style>
.bt-library-toolbar{display:flex;align-items:center;gap:10px;margin:0 0 20px;padding:10px;border:1px solid #e3d9cc;border-radius:18px;background:#fff}.bt-library-search{display:flex;align-items:center;gap:9px;flex:1;min-width:0;padding:0 12px}.bt-library-search input{width:100%;min-width:0;border:0;outline:0;padding:10px 0;font:inherit;background:transparent}.bt-library-menu{position:relative}.bt-library-menu summary,.bt-library-sort{display:flex;align-items:center;gap:7px;min-height:42px;padding:0 13px;border:1px solid #e3d9cc;border-radius:11px;background:#fff;color:#211b17;font-weight:800;cursor:pointer;list-style:none}.bt-library-menu summary::-webkit-details-marker{display:none}.bt-library-menu-panel{position:absolute;z-index:6;right:0;top:50px;width:min(360px,calc(100vw - 42px));display:grid;gap:11px;padding:16px;border:1px solid #e3d9cc;border-radius:15px;background:#fffdf9;box-shadow:0 18px 42px rgba(31,20,11,.16)}.bt-library-menu-panel label{display:grid;gap:5px;font-size:.78rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em}.bt-library-menu-panel input,.bt-library-menu-panel select,.bt-library-sort select{padding:9px;border:1px solid #d8cfc3;border-radius:8px;background:#fff;font:inherit}.bt-library-menu-panel a{font-weight:800;color:#6645d7}.bt-library-sort{margin-left:auto}.bt-library-sort select{padding:0;border:0;outline:0;font-weight:800}.bt-stencil-schedule-grid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr))!important}.bt-schedule-card{display:flex!important;flex-direction:column;align-items:stretch!important;padding:0!important;overflow:hidden}.bt-stencil-card-art{display:grid;place-items:center;min-height:210px;padding:14px;background:#fff}.bt-stencil-card-art img{width:100%;height:230px;object-fit:contain;mix-blend-mode:multiply}.bt-schedule-card>div:not(.bt-stencil-card-art){padding:0 16px}.bt-schedule-card>span{padding:10px 16px 14px}.bt-stencil-card-format{display:block;margin-top:8px;color:#7256d4;font-weight:800;font-size:.73rem;text-transform:uppercase;letter-spacing:.04em}.bt-schedule-number{display:none!important}@media(max-width:640px){.bt-library-toolbar{gap:7px}.bt-library-menu summary span{display:none}.bt-library-search{padding-left:8px}.bt-library-sort{margin-left:0}.bt-library-sort select{max-width:90px}.bt-stencil-card-art{min-height:180px}}
.bt-stencil-viewer-copy{max-height:calc(100dvh - 32px);overflow-y:auto}@media(max-width:760px){.bt-stencil-viewer-copy{max-height:38dvh}}</style>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Asset-backed stencil library</span><span>◆ Pure black outlines</span><span>Violet Trace ready</span><a href="collections.php">Browse collections →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" class="is-active">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="collections.php">Browse collections</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ ASSET-BACKED LIBRARY</p><h1>EXPLORE<br><strong>STENCILS</strong></h1><p>Every card contains its actual pure-black stencil outline. Browse by collection, then filter and sort the approved library.</p><div class="bt-main-actions"><a class="bt-glow-button" href="collections.php">Browse collections</a></div></div></section>
<section class="bt-page-section"><div class="bt-wrap">
  <form class="bt-library-toolbar" method="get" role="search">
    <label class="bt-library-search" for="stencil-search"><span class="sr-only">Search stencil library</span><span aria-hidden="true">⌕</span><input id="stencil-search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search stencil library"></label>
    <details class="bt-library-menu"><summary aria-label="Open library filters" title="Filter library">⚲ <span>Filter</span></summary><div class="bt-library-menu-panel">
      <label>Style<select name="style"><option value="">All styles</option><option value="black" <?= str_contains($filterStyle, 'black') ? 'selected' : '' ?>>Blackwork</option><option value="realism" <?= $filterStyle === 'realism' ? 'selected' : '' ?>>Realism</option><option value="japanese" <?= $filterStyle === 'japanese' ? 'selected' : '' ?>>Japanese</option></select></label>
      <label>Difficulty<select name="difficulty"><option value="">Any difficulty</option><option value="intermediate" <?= $filterDifficulty === 'intermediate' ? 'selected' : '' ?>>Intermediate</option><option value="advanced" <?= $filterDifficulty === 'advanced' ? 'selected' : '' ?>>Advanced</option></select></label>
      <label>Placement<input name="placement" value="<?= e($filterPlacement) ?>" placeholder="Forearm, calf…"></label>
      <label>Collection<select name="collection"><option value="">All collections</option><?php foreach ($collections as $collectionOption): ?><option value="<?= e(strtolower($collectionOption['name'])) ?>" <?= $filterCollection === strtolower($collectionOption['name']) ? 'selected' : '' ?>><?= e($collectionOption['name']) ?></option><?php endforeach; ?></select></label>
      <button class="bt-outline-button" type="submit">Apply filters</button><a href="stencils.php">Clear</a>
    </div></details>
    <label class="bt-library-sort" title="Sort library"><span aria-hidden="true">↕</span><span class="sr-only">Sort library</span><select name="sort" onchange="this.form.submit()"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option><option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option><option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>A–Z</option></select></label>
  </form>
  <div class="bt-category-results"><strong><?= e((string)$availableCount) ?> / <?= e((string)$seasonTotal) ?> verified Season One stencils</strong><span><?= e((string)$visibleCount) ?> matching this view · Pure-black outlines · Violet Trace compatible</span></div>

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
      if ($hasScheduledAssets && $scheduledDate <= $today
          && ($activeCategory === '' || in_array($activeCategory, bt_stencil_category_slugs($item[0], $collection['name']), true))
          && bt_stencil_matches_search($item[0], $collection['name'], $scheduledAssets['metadata'], $searchQuery)
          && bt_stencil_matches_filters($item[0], $collection['name'], $scheduledAssets['metadata'], $filterStyle, $filterPlacement, $filterDifficulty, $filterCollection, $filterFrom, $filterTo, $item[1])) {
        $matchingItems[] = [$item, $itemNumber, $index];
      }
    }
    if (!$matchingItems) { if ($number > $seasonTotal) break; continue; }
    usort($matchingItems, static function (array $left, array $right) use ($sort): int {
      if ($sort === 'title') return strcasecmp($left[0][0], $right[0][0]);
      return $sort === 'oldest' ? strcmp($left[0][1], $right[0][1]) : strcmp($right[0][1], $left[0][1]);
    });
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
      data-stencil-violet-trace="<?= is_file(__DIR__ . '/' . $assets['violet_trace']) ? e($assets['violet_trace']) : '' ?>"
      data-stencil-zip="api/stencil-download.php?type=package&amp;id=<?= e(bt_stencil_asset_slug($item[0]) . '-' . $item[1]) ?>"
    <?php endif; ?>
  >
    <?php $cardArt = is_file(__DIR__ . '/' . $assets['outline_png']) ? $assets['outline_png'] : $assets['print_png']; ?><div class="bt-stencil-card-art"><img src="<?= e($cardArt) ?>" alt="<?= e($item[0]) ?> pure black outline" loading="lazy"></div>
    <div><time datetime="<?= e($item[1]) ?>"><?= e(bt_pretty_date($item[1])) ?></time><h3><?= e($item[0]) ?></h3><p><?= e((string)($assets['metadata']['style'] ?? $collection['name'])) ?> · <?= e((string)($assets['metadata']['placement'] ?? implode(' · ', array_map(static fn($cat) => $categoryOptions[$cat]['label'] ?? $cat, $itemCategories)))) ?></p><small class="bt-stencil-card-format">Pure black outline · Violet Trace ready</small></div>
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
.bt-stencil-carousel-dots{overflow-x:auto;justify-content:flex-start;scrollbar-width:thin;scrollbar-color:#d3a452 #25102f}.bt-stencil-carousel-dot{flex:none;width:92px;min-height:92px;padding:6px;border:2px solid transparent;border-radius:8px;background:#241b29;color:#fffdfa;cursor:pointer;font-size:.68rem;font-weight:800;line-height:1.2;text-align:left}.bt-stencil-carousel-dot img{display:block;width:100%;height:52px;object-fit:contain;border-radius:4px;background:#fff;margin-bottom:4px}.bt-stencil-carousel-dot[aria-current=true]{border-color:#d3a452;background:#46304f}
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
      <div class="bt-stencil-carousel-footer"><span data-stencil-carousel-label>Stencil preview</span><span class="bt-stencil-carousel-count" data-stencil-carousel-count aria-live="polite"></span><div class="bt-stencil-carousel-dots" data-stencil-carousel-dots aria-label="Choose a stencil asset"></div></div>
    </div>
    <div class="bt-stencil-viewer-copy">
      <p data-stencil-viewer-meta>Unlocked stencil</p>
      <h2 id="bt-stencil-viewer-title" data-stencil-viewer-title>Stencil preview</h2>
      <p class="bt-stencil-viewer-note">Available production assets rotate automatically. When a supporting card has not been created yet, its slot uses the approved stencil artwork instead of a placeholder. YouTube links are being added separately.</p>
      <div class="bt-stencil-viewer-actions">
        <button class="bt-glow-button" type="button" data-stencil-viewer-print hidden>⌘ Print stencil</button>
        <a class="bt-glow-button" href="#" download data-stencil-viewer-zip hidden>↓ Download all assets .ZIP</a>
        <a class="bt-glow-button" href="#" target="_blank" rel="noopener" data-stencil-viewer-play hidden>▶ Play in Tattoo Master</a>
        <a class="bt-outline-button" href="stencil-editor.php" data-stencil-viewer-edit>✎ Edit</a>
        <button class="bt-outline-button" type="button" data-stencil-viewer-share>↗ Share</button>
      </div>
    </div>
  </section>
</div>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="collections.php">Browse collections</a>
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
  const print = viewer.querySelector('[data-stencil-viewer-print]');
  const zipDownload = viewer.querySelector('[data-stencil-viewer-zip]');
  const play = viewer.querySelector('[data-stencil-viewer-play]');
  const edit = viewer.querySelector('[data-stencil-viewer-edit]');
  const share = viewer.querySelector('[data-stencil-viewer-share]');
  let currentCard = null;
  let slides = [];
  let activeSlide = 0;
  let pointerStart = null;
  let autoplayTimer = null;
  const stopAutoplay = () => {
    if (autoplayTimer !== null) window.clearInterval(autoplayTimer);
    autoplayTimer = null;
  };
  const startAutoplay = () => {
    stopAutoplay();
    if (slides.length > 1 && !viewer.hidden) autoplayTimer = window.setInterval(() => showSlide(activeSlide + 1), 5600);
  };
  const setLink = (link, url) => {
    if (!link) return;
    if (url) { link.href = url; link.hidden = false; }
    else { link.removeAttribute('href'); link.hidden = true; }
  };
  const showSlide = (index) => {
    if (!slides.length) return;
    activeSlide = (index + slides.length) % slides.length;
    const slide = slides[activeSlide];
    image.src = slide.url;
    image.alt = `${currentCard.dataset.stencilTitle || 'Stencil'} — ${slide.label}${slide.placeholder ? ' placeholder' : ''}`;
    label.textContent = slide.label;
    counter.textContent = `${activeSlide + 1} / ${slides.length}`;
    previous.disabled = slides.length < 2;
    next.disabled = slides.length < 2;
    dots.querySelectorAll('button').forEach((dot, dotIndex) => dot.setAttribute('aria-current', dotIndex === activeSlide ? 'true' : 'false'));
    dots.children[activeSlide]?.scrollIntoView({block: 'nearest', inline: 'nearest'});
  };
  const open = (card) => {
    currentCard = card;
    const titleText = card.dataset.stencilTitle || 'Stencil preview';
    const fallbackArtwork = card.dataset.stencilPreview || card.dataset.stencilOutline || card.dataset.stencilDownload || '';
    const productionSlot = (label, url, fallbackLabel = 'stencil preview') => [url ? label : `${label} · ${fallbackLabel}`, url || fallbackArtwork];
    const productionSlots = [
      ['Stencil preview', card.dataset.stencilPreview || fallbackArtwork],
      productionSlot('Style card', card.dataset.stencilStyle || ''),
      productionSlot('Lore card', card.dataset.stencilLore || ''),
      productionSlot('Packaging preview', card.dataset.stencilPack || ''),
      productionSlot('Mockup', card.dataset.stencilPlacement || ''),
      productionSlot('Reference', card.dataset.stencilReference || ''),
      ['Printer-ready stencil PNG', card.dataset.stencilDownload || card.dataset.stencilOutline || fallbackArtwork],
      productionSlot('Violet Trace', card.dataset.stencilVioletTrace || '', 'pure-black outline'),
    ];
    slides = productionSlots.filter(([, url]) => Boolean(url)).map(([assetLabel, url]) => ({label: assetLabel, url, placeholder: false}));
    title.textContent = titleText;
    meta.textContent = [card.dataset.stencilCollection, card.dataset.stencilDate, 'Unlocked'].filter(Boolean).join(' · ');
    print.dataset.stencilUrl = card.dataset.stencilOutline || card.dataset.stencilDownload || card.dataset.stencilPdf || '';
    print.hidden = !print.dataset.stencilUrl;
    setLink(zipDownload, card.dataset.stencilZip || '');
    const playAsset = card.dataset.stencilOutline || card.dataset.stencilDownload || card.dataset.stencilPreview || '';
    if (playAsset) {
      const masterUrl = new URL('../beyond-games/tattoo-master.php', window.location.href);
      masterUrl.searchParams.set('stencil', new URL(playAsset, window.location.href).href);
      masterUrl.searchParams.set('title', titleText);
      play.href = masterUrl.href;
      play.hidden = false;
    } else {
      play.removeAttribute('href');
      play.hidden = true;
    }
    edit.href = `stencil-editor.php?stencil=${encodeURIComponent(titleText)}`;
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
    startAutoplay();
  };
  const close = () => {
    stopAutoplay();
    viewer.hidden = true;
    viewer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('bt-modal-open');
    image.removeAttribute('src');
    currentCard?.focus();
    currentCard = null;
  };
  previous.addEventListener('click', () => showSlide(activeSlide - 1));
  next.addEventListener('click', () => showSlide(activeSlide + 1));
  print.addEventListener('click', () => {
    const url = print.dataset.stencilUrl || '';
    if (!url) return;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    printWindow.opener = null;
    printWindow.document.title = `${title.textContent || 'Beyond Tattoo'} stencil`;
    const stylesheet = printWindow.document.createElement('style');
    stylesheet.textContent = '@page{margin:0.35in}body{margin:0;text-align:center}img{display:block;max-width:100%;max-height:10in;margin:0 auto;object-fit:contain}';
    const printable = printWindow.document.createElement('img');
    printable.alt = title.textContent || 'Beyond Tattoo stencil';
    printable.src = url;
    printable.addEventListener('load', () => { printWindow.focus(); printWindow.print(); }, {once: true});
    printWindow.document.head.append(stylesheet);
    printWindow.document.body.append(printable);
  });
  share.addEventListener('click', async () => {
    const shareUrl = window.location.href.split('#')[0];
    const shareData = {title: title.textContent || 'Beyond Tattoo stencil', text: meta.textContent || 'Beyond Tattoo stencil', url: shareUrl};
    try {
      if (navigator.share) await navigator.share(shareData);
      else if (navigator.clipboard) { await navigator.clipboard.writeText(shareUrl); share.textContent = '✓ Link copied'; setTimeout(() => { share.textContent = '↗ Share'; }, 1800); }
    } catch (error) { if (error?.name !== 'AbortError') console.warn('Sharing is unavailable.', error); }
  });
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
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('mouseenter', stopAutoplay);
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('mouseleave', startAutoplay);
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('focusin', stopAutoplay);
  viewer.querySelector('[data-stencil-carousel-stage]').addEventListener('focusout', startAutoplay);
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
