<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/config.php';
$stylesheetData = json_decode(
    (string)file_get_contents(__DIR__ . '/data/autumn-ink-stylesheets.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
if (!is_array($stylesheetData)
    || !is_array($stylesheetData['campaign'] ?? null)
    || !is_array($stylesheetData['releases'] ?? null)) {
    throw new RuntimeException('The Autumn Ink stylesheet catalog is invalid.');
}
$campaign = $stylesheetData['campaign'];
$totalReleases = (int)($campaign['total_releases'] ?? 0);
if ($totalReleases < 1 || $totalReleases > 31) {
    throw new RuntimeException('The Autumn Ink release count must be between 1 and 31.');
}
$timezone = new DateTimeZone((string)($campaign['timezone'] ?? 'America/Vancouver'));
$campaignStart = new DateTimeImmutable((string)($campaign['start_date'] ?? ''), $timezone);
$today = new DateTimeImmutable('today', $timezone);
$availableReleases = [];
$configuredSequences = [];
foreach ($stylesheetData['releases'] as $release) {
    if (!is_array($release)) {
        throw new RuntimeException('An Autumn Ink stylesheet release is invalid.');
    }
    $sequence = (int)($release['sequence'] ?? 0);
    $releaseDate = new DateTimeImmutable((string)($release['date'] ?? ''), $timezone);
    $asset = (string)($release['asset'] ?? '');
    $expectedDate = $campaignStart->modify('+' . ($sequence - 1) . ' days');
    if ($sequence < 1 || $sequence > $totalReleases || isset($configuredSequences[$sequence])
        || $releaseDate->format('Y-m-d') !== $expectedDate->format('Y-m-d')
        || trim((string)($release['title'] ?? '')) === ''
        || preg_match('~\Aassets/stylesheets/[A-Za-z0-9][A-Za-z0-9._-]*\z~', $asset) !== 1) {
        throw new RuntimeException('An Autumn Ink release must have one correctly numbered, dated stylesheet asset.');
    }
    $configuredSequences[$sequence] = true;
    if ($releaseDate <= $today && is_file(__DIR__ . '/' . $asset)) {
        $release['release_date'] = $releaseDate;
        $availableReleases[$sequence] = $release;
    }
}
$campaignPoster = (string)($campaign['poster'] ?? '');
$campaignArt = bt_app_url($campaignPoster);
$availableCount = count($availableReleases);

$disableBeyondShell = true;
$pageTitle = 'Autumn Ink Stylesheets — Beyond Tattoo';
$pageDescription = 'Browse and download one dedicated stylesheet asset for each published release in Beyond Tattoo’s Autumn Ink 31-day series.';
$pageCanonical = 'https://beyondimagination.co.technology/beyond-tattoo/stylesheets.php';
require __DIR__ . '/includes/header.php';
?>
<style>
.bt-stylesheet-hero{position:relative;overflow:hidden;padding:clamp(42px,8vw,86px) 0 48px;background:radial-gradient(circle at 78% 42%,rgba(255,116,31,.19),transparent 31%),linear-gradient(145deg,#17101d,#09070d 68%)}.bt-stylesheet-hero-grid{display:grid;gap:28px;align-items:center}.bt-stylesheet-hero h1{margin:10px 0 18px;font-size:clamp(2.7rem,8vw,5.3rem);line-height:.92;letter-spacing:-.06em}.bt-stylesheet-hero h1 strong{color:#ff9a36}.bt-stylesheet-hero p{max-width:630px;color:#c7bdcc;line-height:1.7}.bt-stylesheet-poster{width:min(100%,390px);margin:auto;border:1px solid rgba(255,157,63,.35);border-radius:22px;box-shadow:0 24px 75px rgba(0,0,0,.4),0 0 45px rgba(255,107,31,.12)}.bt-stylesheet-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}.bt-stylesheet-section{padding:42px 0}.bt-stylesheet-section h2{margin:0 0 8px;font-size:clamp(1.8rem,5vw,2.7rem);letter-spacing:-.04em}.bt-stylesheet-section-lead{margin:0;color:#aaa4b8;line-height:1.6}.bt-sheet-feature{display:grid;gap:22px;margin-top:24px;padding:clamp(20px,4vw,34px);border:1px solid rgba(255,154,54,.34);border-radius:24px;background:linear-gradient(145deg,rgba(55,29,20,.74),rgba(17,13,19,.94));box-shadow:0 20px 60px rgba(0,0,0,.24)}.bt-sheet-feature-art{position:relative;overflow:hidden;border-radius:20px;background:#faf8f5;border:1px solid rgba(255,154,54,.35);box-shadow:0 16px 45px rgba(0,0,0,.35),inset 0 0 0 1px rgba(0,0,0,.08)}.bt-sheet-feature-art img{display:block;width:100%;height:auto;max-height:850px;object-fit:contain;background:#faf8f5;border-radius:18px}.bt-sheet-badge{position:absolute;top:12px;left:12px;padding:8px 11px;border:1px solid rgba(255,190,102,.44);border-radius:999px;background:rgba(20,12,11,.86);color:#ffd18c;font-size:.75rem;font-weight:900;letter-spacing:.08em}.bt-sheet-feature h3{margin:8px 0;font-size:clamp(1.6rem,4vw,2.3rem)}.bt-sheet-feature-copy>p{color:#c7bdcc;line-height:1.65}.bt-sheet-specs{display:grid;gap:11px;margin:20px 0}.bt-sheet-specs div{padding:12px 14px;border:1px solid rgba(255,255,255,.08);border-radius:13px;background:rgba(255,255,255,.025)}.bt-sheet-specs dt{color:#ffbd73;font-size:.72rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.bt-sheet-specs dd{margin:5px 0 0;color:#e2dce8;font-size:.9rem;line-height:1.55}.bt-sheet-calendar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:22px}.bt-sheet-day{display:flex;align-items:center;gap:12px;min-height:72px;padding:12px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025)}.bt-sheet-day.is-available{border-color:rgba(255,154,54,.46);background:linear-gradient(120deg,rgba(113,53,20,.26),rgba(255,255,255,.025))}.bt-sheet-day-number{display:grid;flex:0 0 42px;width:42px;height:42px;place-items:center;border-radius:13px;background:rgba(255,151,54,.12);color:#ffb45d;font-weight:950}.bt-sheet-day-copy{min-width:0}.bt-sheet-day-copy strong,.bt-sheet-day-copy small{display:block}.bt-sheet-day-copy strong{font-size:.85rem}.bt-sheet-day-copy small{margin-top:4px;color:#aaa4b8;font-size:.75rem}.bt-sheet-release-list{display:grid;gap:14px;margin-top:24px}.bt-sheet-print{display:none}
@media(min-width:760px){.bt-stylesheet-hero-grid{grid-template-columns:1.1fr .9fr}.bt-sheet-feature{grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);align-items:start}.bt-sheet-calendar{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media print{body *{visibility:hidden!important}.bt-sheet-print,.bt-sheet-print *{visibility:visible!important}.bt-sheet-print{display:block;position:fixed;inset:0;padding:24px;background:#fff;color:#111}.bt-sheet-print h1{font-size:28px}.bt-sheet-print h2{margin-top:4px}.bt-sheet-print img{display:block;width:100%;max-height:65vh;object-fit:contain}.bt-sheet-print p{line-height:1.5}.bt-sheet-print dl{display:grid;grid-template-columns:130px 1fr;gap:8px}.bt-sheet-print dt{font-weight:bold}.bt-sheet-print dd{margin:0}}
</style>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Autumn Ink</span><span>31 days · 31 stylesheets</span><span>Needle Bot · Beyond Tattoo</span><a href="stencils.php">Browse stencil library →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav" aria-label="Beyond Tattoo navigation"><a href="index.php">Home</a><a href="stencils.php">Stencils</a><a href="stylesheets.php" class="is-active">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="stencils.php">Browse stencils</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary aria-label="Open menu">☰</summary><div><a href="index.php">Home</a><a href="stencils.php">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

  <section class="bt-stylesheet-hero"><div class="bt-wrap bt-stylesheet-hero-grid"><div><p class="bt-gold-kicker">✦ OCTOBER · AUTUMN INK</p><h1>31 DAYS.<br><strong>31 STYLESHEETS.</strong></h1><p>One original stylesheet asset per daily release. Browse the campaign calendar, download a published release, and check back as new assets go live.</p><div class="bt-stylesheet-actions"><a class="bt-glow-button" href="#releases">Browse releases · <?= (int)$availableCount ?> available</a><a class="bt-outline-button" href="stencils.php">Browse stencils</a></div></div><img class="bt-stylesheet-poster" src="<?= e($campaignArt) ?>" alt="Autumn Ink Halloween 31/31 campaign poster announcing 31 days and 31 stylesheets starting October 1" width="1200" height="1200"></div></section>

  <?php if ($availableReleases !== []): ?>
    <section class="bt-stylesheet-section" id="releases" aria-labelledby="releases-title"><div class="bt-wrap"><p class="bt-purple-kicker">PUBLISHED RELEASE ASSETS</p><h2 id="releases-title">One stylesheet per release</h2><p class="bt-stylesheet-section-lead">Each release has one dedicated stylesheet image. The same source asset is used for preview, download and print.</p><div class="bt-sheet-release-list">
      <?php foreach ($availableReleases as $sequence => $release): $releaseAnchor = 'stylesheet-' . sprintf('%02d', $sequence); $assetUrl = bt_app_url($release['asset']); ?>
        <article class="bt-sheet-feature" id="<?= e($releaseAnchor) ?>">
          <div class="bt-sheet-feature-art"><img src="<?= e($assetUrl) ?>" alt="Stylesheet <?= sprintf('%02d', $sequence) ?>/<?= (int)$totalReleases ?> — <?= e($release['title']) ?>" loading="lazy"><span class="bt-sheet-badge">STYLESHEET <?= sprintf('%02d', $sequence) ?>/<?= (int)$totalReleases ?></span></div>
          <div class="bt-sheet-feature-copy"><p class="bt-purple-kicker"><?= e($campaign['name']) ?> · <?= e($release['release_date']->format('F j')) ?></p><h3><?= e($release['title']) ?></h3><p><?= e($release['style']) ?>. Review and adapt the supplied sheet with a qualified tattoo artist.</p>
            <dl class="bt-sheet-specs">
              <?php foreach (['palette' => 'Palette', 'linework' => 'Linework', 'contrast' => 'Contrast', 'texture' => 'Texture', 'composition' => 'Composition', 'placement' => 'Placement', 'transfer' => 'Transfer notes'] as $field => $label): ?>
                <?php if (isset($release[$field])): ?><div><dt><?= e($label) ?></dt><dd><?= e($release[$field]) ?></dd></div><?php endif; ?>
              <?php endforeach; ?>
            </dl>
            <div class="bt-stylesheet-actions"><a class="bt-glow-button" href="<?= e($assetUrl) ?>" download>↓ Download stylesheet asset</a><button class="bt-outline-button" type="button" onclick="window.print()">Print stylesheet</button></div>
          </div>
          <div class="bt-sheet-print" aria-hidden="true"><p><?= e(strtoupper((string)$campaign['name'])) ?> · <?= sprintf('%02d', $sequence) ?>/<?= (int)$totalReleases ?> · <?= e($release['release_date']->format('F j, Y')) ?></p><h1><?= e($release['title']) ?></h1><img src="<?= e($assetUrl) ?>" alt=""><h2><?= e($release['style']) ?></h2><dl><?php foreach (['palette' => 'Palette', 'linework' => 'Linework', 'contrast' => 'Contrast', 'texture' => 'Texture', 'composition' => 'Composition', 'placement' => 'Placement', 'transfer' => 'Transfer notes'] as $field => $label): ?><?php if (isset($release[$field])): ?><dt><?= e($label) ?></dt><dd><?= e($release[$field]) ?></dd><?php endif; ?><?php endforeach; ?></dl><p>Review and adapt this design with a qualified tattoo artist before tattooing.</p></div>
        </article>
      <?php endforeach; ?>
    </div></div></section>
  <?php endif; ?>

  <section class="bt-stylesheet-section" aria-labelledby="calendar-title"><div class="bt-wrap"><p class="bt-purple-kicker">AUTUMN INK RELEASE CALENDAR</p><h2 id="calendar-title">The 31-day series</h2><p class="bt-stylesheet-section-lead">New numbered stylesheet drops are scheduled daily from October 1 through October 31. This page marks a sheet available only when its guide is published.</p><div class="bt-sheet-calendar">
    <?php for ($day = 1; $day <= $totalReleases; $day++): $releaseDate = $campaignStart->modify('+' . ($day - 1) . ' days'); $release = $availableReleases[$day] ?? null; $releaseStatus = $release !== null ? 'Asset available' : ($releaseDate <= $today ? 'Awaiting asset' : 'Scheduled'); ?>
      <article class="bt-sheet-day <?= $release !== null ? 'is-available' : '' ?>"><span class="bt-sheet-day-number"><?= sprintf('%02d', $day) ?></span><div class="bt-sheet-day-copy"><strong><?= $release !== null ? e($release['title']) : 'Stylesheet release' ?></strong><small><?= e($releaseDate->format('M j')) ?> · <?= e($releaseStatus) ?></small></div></article>
    <?php endfor; ?>
  </div></div></section>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="stencils.php">Stencil library</a><a href="stylesheets.php">Autumn Ink stylesheets</a><a href="index.php">Beyond Tattoo home</a><a href="../">Beyond OS</a></div></div></footer>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
