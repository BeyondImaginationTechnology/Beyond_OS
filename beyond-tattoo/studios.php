<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
$query = trim((string)($_GET['q'] ?? ''));
$studios = bt_list_studios($query);
$featuredStudio = $query === '' ? bt_get_studio('beyond-studio-nanaimo') : null;
$pageTitle = 'Studios — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
  <header class="app-header"><div class="container app-header-inner"><a class="brand" href="index.php"><span class="brand-badge">B</span><span>Studios</span></a><?php if(is_logged_in()): ?><a class="btn btn-secondary" href="dashboard.php">Dashboard</a><?php endif; ?></div></header>
  <main class="container dashboard">
    <section class="panel studios-hero"><span class="eyebrow">Beyond Studio • Nanaimo, BC</span><h1>Find your studio</h1><p class="section-copy">Discover verified tattoo studios, meet the artists behind the work, and start your next piece with a clear booking inquiry.</p>
      <form class="filter-row" method="get"><label class="sr-only" for="studio-search">Search studios</label><input id="studio-search" class="input" name="q" value="<?= e($query) ?>" placeholder="Search city, province, studio, or service"><button class="btn btn-primary" type="submit">Search</button></form>
      <div class="chip-row" aria-label="Quick studio filters"><a href="studios.php?q=Nanaimo">Nanaimo, BC</a><a href="studios.php?q=Ottawa">Ottawa</a><a href="studios.php?q=Ontario">Ontario</a><a href="studios.php?q=Quebec">Quebec</a><a href="studios.php?q=Alberta">Alberta</a><a href="studios.php?q=Canada">All Canada</a><?php if ($query !== ''): ?><a href="studios.php">Clear filter</a><?php endif; ?></div>
    </section>
    <?php if ($featuredStudio): ?>
    <section class="studio-feature panel" aria-labelledby="featured-studio-title">
      <div class="studio-feature-art" aria-hidden="true"><span>BEYOND<br>STUDIO</span><i>⌁</i></div>
      <div class="studio-feature-copy"><span class="eyebrow">01 / Founding studio</span><h2 id="featured-studio-title"><?= e($featuredStudio['name']) ?></h2><p><?= e($featuredStudio['description']) ?></p><div class="chip-row"><span>⌖ <?= e($featuredStudio['city']) ?>, <?= e($featuredStudio['province']) ?></span><span><?= e($featuredStudio['services']) ?></span></div><div class="artist-actions"><a class="btn btn-primary" href="studio-profile.php?slug=<?= urlencode($featuredStudio['slug']) ?>">Explore studio</a><a class="btn btn-secondary" href="book.php?studio=<?= urlencode($featuredStudio['slug']) ?>">Start a booking inquiry</a></div></div>
    </section>
    <?php endif; ?>
    <section class="artist-grid" aria-live="polite">
      <?php foreach ($studios as $studio): ?>
      <article class="artist-card">
        <div class="artist-avatar"><?= e(strtoupper(substr((string)$studio['name'], 0, 1))) ?></div>
        <div class="artist-card-body"><div class="artist-heading"><div><h2><?= e($studio['name']) ?></h2><p class="meta"><?= e($studio['city']) ?><?= $studio['province'] ? ', ' . e($studio['province']) : '' ?></p></div><div class="chip-row"><?php if(($studio['verification_status'] ?? '') === 'verified'): ?><span class="verified">Verified</span><?php endif; ?><?php if((int)$studio['walk_ins']===1): ?><span class="verified">Walk-ins</span><?php endif; ?></div></div>
          <p><?= e($studio['description']) ?></p><div class="chip-row"><span><?= (int)$studio['artist_count'] ?> listed artists</span><span><?= e($studio['services']) ?></span></div>
          <div class="artist-actions"><a class="btn btn-primary" href="studio-profile.php?slug=<?= urlencode($studio['slug']) ?>">View studio</a><a class="btn btn-secondary" href="<?= e($studio['website_url'] ?? $studio['instagram_url']) ?>" target="_blank" rel="noopener">Studio link ↗</a></div>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (!$studios): ?><div class="panel"><h2>No studios matched.</h2><p class="meta">Try a city or service such as Ottawa, tattooing, or piercing.</p></div><?php endif; ?>
    </section>
  </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
