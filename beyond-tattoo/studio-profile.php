<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
$slug = trim((string)($_GET['slug'] ?? ''));
$studio = bt_get_studio($slug);
if (!$studio) { http_response_code(404); $pageTitle='Studio not found — Beyond Tattoo'; require __DIR__.'/includes/header.php'; echo '<main class="container dashboard"><div class="panel"><h1>Studio not found</h1><a href="studios.php">Back to studios</a></div></main>'; require __DIR__.'/includes/footer.php'; exit; }
$artists = bt_list_artists((int)$studio['id']);
$pageTitle = $studio['name'] . ' — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
$mapQuery = rawurlencode(trim($studio['address_line1'] . ', ' . $studio['city'] . ', ' . $studio['province'] . ' ' . $studio['postal_code']));
$studioWebsite = trim((string)($studio['website_url'] ?? '')) ?: (string)$studio['instagram_url'];
?>
<div class="app-shell"><header class="app-header"><div class="container app-header-inner"><a class="btn btn-secondary" href="studios.php">← Studios</a><span class="brand">Studio profile</span></div></header>
<main class="container dashboard">
  <section class="artist-profile-hero panel"><div class="artist-avatar large"><?= e(strtoupper(substr($studio['name'],0,1))) ?></div><div><span class="eyebrow">Founding Beyond Tattoo studio</span><h1><?= e($studio['name']) ?></h1><p class="section-copy"><?= e($studio['description']) ?></p><div class="chip-row"><?php if((int)$studio['walk_ins']===1): ?><span>Walk-ins accepted</span><?php endif; ?><span><?= e($studio['services']) ?></span></div></div></section>
  <div class="dashboard-grid" style="margin-top:18px"><section class="panel"><h2>Visit or contact</h2><p><strong><?= e($studio['address_line1']) ?></strong><br><?= e($studio['city']) ?>, <?= e($studio['province']) ?> <?= e($studio['postal_code']) ?></p><?php if (trim((string)$studio['phone']) !== ''): ?><p><a href="tel:<?= e(preg_replace('/[^0-9+]/','',$studio['phone'])) ?>"><?= e($studio['phone']) ?></a></p><?php endif; ?><div class="artist-actions"><a class="btn btn-primary" href="<?= e($studioWebsite) ?>" target="_blank" rel="noopener">Contact studio ↗</a><a class="btn btn-secondary" href="https://www.google.com/maps/search/?api=1&amp;query=<?= $mapQuery ?>" target="_blank" rel="noopener">Directions ↗</a></div></section>
  <aside class="panel"><h2>Studio contact</h2><p><strong><?= e($studio['owner_display_name'] ?: 'Studio team') ?></strong><br><span class="meta">Confirm availability and artist fit directly with the studio.</span></p><a class="btn btn-secondary" href="<?= e($studio['owner_instagram_url']) ?>" target="_blank" rel="noopener">Studio website ↗</a></aside></div>
  <section class="panel" style="margin-top:18px"><h2>Artists at <?= e($studio['name']) ?></h2><?php if ($artists): ?><div class="artist-grid"><?php foreach($artists as $artist): ?><article class="artist-card"><div class="artist-avatar"><?= e(strtoupper(substr($artist['display_name'],0,1))) ?></div><div class="artist-card-body"><h3><?= e($artist['display_name']) ?></h3><p class="meta"><?= e($artist['instagram_handle']) ?></p><p><?= e($artist['styles']) ?></p><div class="artist-actions"><a class="btn btn-primary" href="artist-profile.php?slug=<?= urlencode($artist['slug']) ?>">View profile</a><a class="btn btn-secondary" href="<?= e($artist['instagram_url']) ?>" target="_blank" rel="noopener">Portfolio ↗</a></div></div></article><?php endforeach; ?></div><?php else: ?><p class="meta">The studio roster has not been imported yet. Use the studio contact link to review current artists and booking availability.</p><?php endif; ?></section>
</main></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
