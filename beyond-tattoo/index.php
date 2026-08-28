<?php
declare(strict_types=1);

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';

// The public storefront uses its own compact navigation instead of the full OS shell.
$disableBeyondShell = true;
$pageTitle = 'Beyond Tattoo — Free Stencil Drops';
require __DIR__ . '/includes/header.php';

$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$packImage = trim((string)($stencilDay['pack_image_url'] ?? '')) ?: $stencilDay['preview_url'];
$libraryAssets = bt_asset_library();
$libraryCatalog = bt_library_collections();
$libraryCounts = [];
$libraryPreviews = [];
foreach ($libraryAssets as $asset) {
    $libraryCounts[$asset['collection_slug']] = ($libraryCounts[$asset['collection_slug']] ?? 0) + 1;
    $libraryPreviews[$asset['collection_slug']] ??= $asset['preview_url'];
}

$featuredDateBadge = strtoupper($stencilDay['display_date'] ?? '');
if (!empty($stencilDay['iso_date'])) {
    try {
        $featuredDateBadge = strtoupper((new DateTimeImmutable($stencilDay['iso_date']))->format('M j, Y'));
    } catch (Throwable $e) {
        // Keep the configured display date if the ISO value is invalid.
    }
}
?>
<main class="bt-storefront" id="top">
  <div class="bt-announcement" aria-label="Store highlights">
    <div class="bt-wrap bt-announcement-inner">
      <span>✦ Asset library</span>
      <span>◆ Premium quality</span>
      <span>Studio ready</span>
      <a href="<?= e($downloadFile) ?>" download>Free stencil packs →</a>
    </div>
  </div>

  <header class="bt-site-header">
    <div class="bt-wrap bt-site-header-inner">
      <a class="bt-brand" href="#top" aria-label="Beyond Tattoo home">
        <span class="bt-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 64 64" role="img"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg>
        </span>
        <span><strong>BEYOND</strong><b>TATTOO</b></span>
      </a>

      <nav class="bt-desktop-nav" aria-label="Beyond Tattoo navigation">
        <a class="is-active" href="#top">Home</a>
        <a href="stencils.php">Stencils</a>
        <a href="collections.php">Collections</a>
        <a href="studios.php">Studios</a>
        <a href="about.php">About</a>
      </nav>

      <div class="bt-header-actions">
        <a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a>
        <a class="bt-login-link" href="login.php">Studio login</a>
        <details class="bt-mobile-menu">
          <summary aria-label="Open menu">☰</summary>
          <div>
            <a href="stencils.php">Stencils</a>
            <a href="collections.php">Collections</a>
            <a href="studios.php">Studios</a>
            <a href="about.php">About</a>
            <a href="login.php">Studio login</a>
          </div>
        </details>
      </div>
    </div>
  </header>

  <section class="bt-main-hero">
    <div class="bt-wrap bt-main-hero-grid">
      <div class="bt-main-copy">
        <p class="bt-gold-kicker">✦ FREE. EVERY. DAY.</p>
        <h1><span>BEYOND</span><strong>TATTOO</strong></h1>
        <p class="bt-stencil-drop">STENCIL DROP</p>
        <p class="bt-main-lead">Premium tattoo stencils plus a focused operating workspace for modern shop owners.</p>
        <div class="bt-main-actions">
          <a class="bt-glow-button" href="<?= e($downloadFile) ?>" download>↓ Download today’s stencil</a>
          <a class="bt-outline-button" href="login.php">Studio owner login</a>
        </div>
        <div class="bt-trust-row" aria-label="Stencil package features">
          <span><i>▣</i> Printer ready</span>
          <span><i>◇</i> Clean lines</span>
          <span><i>✦</i> Artist approved</span>
        </div>
      </div>

      <a class="bt-package-stage" href="<?= e($downloadFile) ?>" download aria-label="Download today's free stencil package">
        <span class="bt-package-glow" aria-hidden="true"></span>
        <img
          src="<?= e($packImage) ?>?v=<?= e((string)($stencilDay['updated_at'] ?: '1')) ?>"
          alt="<?= e($stencilDay['title']) ?> generated stencil package"
        >
        <span class="bt-package-cta">Download free stencil</span>
      </a>
    </div>
  </section>

  <section class="bt-category-section" aria-labelledby="category-title">
    <div class="bt-wrap bt-section-frame">
      <h2 id="category-title"><span>✦</span> Browse by category <span>✦</span></h2>
      <div class="bt-category-grid">
        <a href="stencils.php?category=realism"><b>☠</b><span>Realism</span></a>
        <a href="stencils.php?category=black-grey"><b>✿</b><span>Black &amp; Grey</span></a>
        <a href="stencils.php?category=japanese"><b>〽</b><span>Japanese</span></a>
        <a href="stencils.php?category=tribal"><b>♜</b><span>Tribal</span></a>
        <a href="stencils.php?category=minimalist"><b>△</b><span>Minimalist</span></a>
        <a href="stencils.php?category=sacred"><b>◉</b><span>Sacred</span></a>
        <a href="stencils.php"><b>▦</b><span>All stencils</span></a>
      </div>
    </div>
  </section>

  <section class="bt-daily-section" id="stencils">
    <div class="bt-wrap bt-daily-card">
      <div class="bt-daily-art">
        <img src="<?= e($stencilDay['preview_url']) ?>?v=<?= e((string)($stencilDay['updated_at'] ?: '1')) ?>" alt="<?= e($stencilDay['title']) ?> stencil preview">
        <span class="bt-stencil-day-orb">Stencil<br>of the<br>day</span>
        <span class="bt-image-date"><?= e($featuredDateBadge) ?></span>
      </div>
      <div class="bt-daily-copy">
        <p class="bt-purple-kicker">Stencil of the day</p>
        <h2><?= e($stencilDay['title']) ?></h2>
        <p class="bt-collection-tag"><?= e($stencilDay['collection']) ?></p>
        <div class="bt-daily-features">
          <span>◇ <?= e($stencilDay['description']) ?></span>
          <span>✦ Easy-transfer clean lines</span>
          <span>▣ Verified print-ready master</span>
        </div>
        <a class="bt-glow-button bt-full-button" href="<?= e($downloadFile) ?>" download>↓ Download free stencil pack</a>
        <small>Actual approved asset · 100% free · No login required</small>
      </div>
    </div>
  </section>

  <section class="bt-collections-section" id="collections">
    <div class="bt-wrap bt-section-frame">
      <div class="bt-section-heading-row">
        <h2>Explore collections</h2>
        <a href="stencils.php">View all →</a>
      </div>
      <div class="bt-collection-grid-new">
        <?php foreach ($libraryCatalog as $slug => $collection): $actualCount = $libraryCounts[$slug] ?? 0; if ($actualCount === 0) { continue; } ?>
          <a class="bt-collection-tile" href="collections.php#<?= e($slug) ?>" aria-label="Explore the <?= e($collection['name']) ?> collection">
            <img src="<?= e($libraryPreviews[$slug]) ?>" alt="<?= e($collection['name']) ?> verified stencil preview">
            <span class="bt-collection-date"><?= e($collection['dates']) ?></span>
            <div><h3><?= e($collection['name']) ?></h3><p><?= e((string)$actualCount) ?> verified <?= $actualCount === 1 ? 'asset' : 'assets' ?></p></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="bt-studio-product" aria-labelledby="studio-product-title">
    <div class="bt-wrap bt-studio-product-shell">
      <div class="bt-studio-product-copy">
        <p class="bt-gold-kicker">BEYOND TATTOO FOR STUDIOS</p>
        <h2 id="studio-product-title">Run the shop.<br><strong>Grow the roster.</strong></h2>
        <p>Beyond Tattoo gives shop owners one focused workspace for artist recruiting, studio discovery and production-ready stencil resources—all connected to an existing Beyond ID.</p>
        <div class="bt-main-actions">
          <a class="bt-glow-button" href="login.php">Open studio workspace</a>
          <a class="bt-outline-button" href="about.php">See how it works</a>
        </div>
        <small>No separate Tattoo account required.</small>
      </div>
      <div class="bt-studio-product-grid">
        <article><span>01</span><b>Studio profile</b><p>Keep your shop identity and service area current.</p></article>
        <article><span>02</span><b>Artist recruiting</b><p>Post opportunities and build a stronger roster.</p></article>
        <article><span>03</span><b>Stencil production</b><p>Use clean, transfer-ready assets in real workflows.</p></article>
        <article><span>04</span><b>Beyond network</b><p>Help clients discover the people behind the shop.</p></article>
      </div>
    </div>
  </section>

  <section class="bt-values-section" id="about">
    <div class="bt-wrap bt-values-grid">
      <div><b>🎁</b><span><strong>Free every day</strong><small>Fresh public stencil drops</small></span></div>
      <div><b>◇</b><span><strong>Premium quality</strong><small>Professional high-detail files</small></span></div>
      <div><b>✍</b><span><strong>For studios</strong><small>Built for real shop workflows</small></span></div>
      <div><b>◎</b><span><strong>Community driven</strong><small>Part of the Beyond ecosystem</small></span></div>
    </div>
  </section>

  <footer class="bt-store-footer">
    <div class="bt-wrap bt-store-footer-grid">
      <div class="bt-footer-brand">
        <span class="bt-brand-mark" aria-hidden="true">
          <svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg>
        </span>
        <div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div>
      </div>
      <div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div>
    </div>
  </footer>

  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
