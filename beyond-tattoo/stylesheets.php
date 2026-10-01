<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/asset-library.php';

$campaignStart = new DateTimeImmutable('2026-10-01', new DateTimeZone('America/Vancouver'));
$today = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver'));
$stylesheet = [
    'sequence' => 1,
    'title' => 'Pumpkin Harvest',
    'release_date' => $campaignStart,
    'style' => 'Autumn illustrative blackwork',
    'palette' => 'Burnt orange, pumpkin gold, warm ivory, deep plum and ink black.',
    'linework' => 'Use a confident outer contour, varied interior line weights and open gaps between overlapping leaves and vines.',
    'contrast' => 'Keep the pumpkin as the brightest focal point. Reserve deep black for the carved face, cast shadows and a few structural accents.',
    'texture' => 'Layer curved rib hatching with sparse stipple; keep highlights clean and avoid dense texture in small transfer areas.',
    'composition' => 'Build a compact, vertical pumpkin focal with a curling stem, two or three autumn leaves and restrained moonlit accents.',
    'placement' => 'Forearm, calf or upper arm; ask the artist to adjust scale and detail to the chosen placement.',
    'transfer' => 'Separate the outer silhouette, facial features, ribs and foliage into clear line groups. Confirm minimum line spacing at final print size.',
];

if (($_GET['download'] ?? '') === '01') {
    $download = "BEYOND TATTOO — AUTUMN INK\nSTYLESHEET 01/31: {$stylesheet['title']}\nRelease date: " . $stylesheet['release_date']->format('F j, Y') . "\n\n"
        . "STYLE\n{$stylesheet['style']}\n\nPALETTE\n{$stylesheet['palette']}\n\nLINEWORK\n{$stylesheet['linework']}\n\nCONTRAST\n{$stylesheet['contrast']}\n\nTEXTURE\n{$stylesheet['texture']}\n\nCOMPOSITION\n{$stylesheet['composition']}\n\nPLACEMENT\n{$stylesheet['placement']}\n\nTRANSFER NOTES\n{$stylesheet['transfer']}\n";
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="beyond-tattoo-stylesheet-01-pumpkin-harvest.txt"');
    echo $download;
    exit;
}

$disableBeyondShell = true;
$pageTitle = 'Autumn Ink Stylesheets — Beyond Tattoo';
$pageDescription = 'Browse the Autumn Ink 31-day stylesheet series, download the Pumpkin Harvest style guide and explore published tattoo style cards.';
$pageCanonical = 'https://beyondimagination.co.technology/beyond-tattoo/stylesheets.php';
require __DIR__ . '/includes/header.php';
$publishedStyles = array_values(array_filter(
    bt_asset_library(),
    static fn(array $asset): bool => $asset['style_card_url'] !== ''
));
$campaignArt = bt_app_url('assets/img/campaign/autumn-ink-halloween-31-square.png');
?>
<style>
.bt-stylesheet-hero{position:relative;overflow:hidden;padding:clamp(42px,8vw,86px) 0 48px;background:radial-gradient(circle at 78% 42%,rgba(255,116,31,.19),transparent 31%),linear-gradient(145deg,#17101d,#09070d 68%)}.bt-stylesheet-hero-grid{display:grid;gap:28px;align-items:center}.bt-stylesheet-hero h1{margin:10px 0 18px;font-size:clamp(2.7rem,8vw,5.3rem);line-height:.92;letter-spacing:-.06em}.bt-stylesheet-hero h1 strong{color:#ff9a36}.bt-stylesheet-hero p{max-width:630px;color:#c7bdcc;line-height:1.7}.bt-stylesheet-poster{width:min(100%,390px);margin:auto;border:1px solid rgba(255,157,63,.35);border-radius:22px;box-shadow:0 24px 75px rgba(0,0,0,.4),0 0 45px rgba(255,107,31,.12)}.bt-stylesheet-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}.bt-stylesheet-section{padding:42px 0}.bt-stylesheet-section h2{margin:0 0 8px;font-size:clamp(1.8rem,5vw,2.7rem);letter-spacing:-.04em}.bt-stylesheet-section-lead{margin:0;color:#aaa4b8;line-height:1.6}.bt-sheet-feature{display:grid;gap:22px;margin-top:24px;padding:clamp(20px,4vw,34px);border:1px solid rgba(255,154,54,.34);border-radius:24px;background:linear-gradient(145deg,rgba(55,29,20,.74),rgba(17,13,19,.94));box-shadow:0 20px 60px rgba(0,0,0,.24)}.bt-sheet-feature-art{position:relative;overflow:hidden;border-radius:17px;background:#08070b}.bt-sheet-feature-art img{width:100%;height:100%;max-height:430px;object-fit:cover}.bt-sheet-badge{position:absolute;top:12px;left:12px;padding:8px 11px;border:1px solid rgba(255,190,102,.44);border-radius:999px;background:rgba(20,12,11,.86);color:#ffd18c;font-size:.75rem;font-weight:900;letter-spacing:.08em}.bt-sheet-feature h3{margin:8px 0;font-size:clamp(1.6rem,4vw,2.3rem)}.bt-sheet-feature-copy>p{color:#c7bdcc;line-height:1.65}.bt-sheet-specs{display:grid;gap:11px;margin:20px 0}.bt-sheet-specs div{padding:12px 14px;border:1px solid rgba(255,255,255,.08);border-radius:13px;background:rgba(255,255,255,.025)}.bt-sheet-specs dt{color:#ffbd73;font-size:.72rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.bt-sheet-specs dd{margin:5px 0 0;color:#e2dce8;font-size:.9rem;line-height:1.55}.bt-sheet-calendar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:22px}.bt-sheet-day{display:flex;align-items:center;gap:12px;min-height:72px;padding:12px;border:1px solid rgba(255,255,255,.09);border-radius:15px;background:rgba(255,255,255,.025)}.bt-sheet-day.is-available{border-color:rgba(255,154,54,.46);background:linear-gradient(120deg,rgba(113,53,20,.26),rgba(255,255,255,.025))}.bt-sheet-day-number{display:grid;flex:0 0 42px;width:42px;height:42px;place-items:center;border-radius:13px;background:rgba(255,151,54,.12);color:#ffb45d;font-weight:950}.bt-sheet-day-copy{min-width:0}.bt-sheet-day-copy strong,.bt-sheet-day-copy small{display:block}.bt-sheet-day-copy strong{font-size:.85rem}.bt-sheet-day-copy small{margin-top:4px;color:#aaa4b8;font-size:.75rem}.bt-published-sheets{display:grid;gap:14px;margin-top:22px}.bt-published-sheet{overflow:hidden;border:1px solid rgba(255,255,255,.09);border-radius:18px;background:rgba(255,255,255,.025)}.bt-published-sheet img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#08070b}.bt-published-sheet-copy{padding:16px}.bt-published-sheet-copy h3{margin:0 0 5px;font-size:1rem}.bt-published-sheet-copy p{margin:0 0 12px;color:#aaa4b8;font-size:.82rem}.bt-sheet-print{display:none}
@media(min-width:760px){.bt-stylesheet-hero-grid{grid-template-columns:1.1fr .9fr}.bt-sheet-feature{grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr);align-items:start}.bt-sheet-calendar{grid-template-columns:repeat(4,minmax(0,1fr))}.bt-published-sheets{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media print{body *{visibility:hidden!important}.bt-sheet-print,.bt-sheet-print *{visibility:visible!important}.bt-sheet-print{display:block;position:fixed;inset:0;padding:24px;background:#fff;color:#111}.bt-sheet-print h1{font-size:28px}.bt-sheet-print h2{margin-top:4px}.bt-sheet-print p{line-height:1.5}.bt-sheet-print dl{display:grid;grid-template-columns:130px 1fr;gap:8px}.bt-sheet-print dt{font-weight:bold}.bt-sheet-print dd{margin:0}}
</style>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Autumn Ink</span><span>31 days · 31 stylesheets</span><span>Needle Bot · Beyond Tattoo</span><a href="stencils.php">Browse stencil library →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav" aria-label="Beyond Tattoo navigation"><a href="index.php">Home</a><a href="stencils.php">Stencils</a><a href="stylesheets.php" class="is-active">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="stencils.php">Browse stencils</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary aria-label="Open menu">☰</summary><div><a href="index.php">Home</a><a href="stencils.php">Stencils</a><a href="stylesheets.php">Stylesheets</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

  <section class="bt-stylesheet-hero"><div class="bt-wrap bt-stylesheet-hero-grid"><div><p class="bt-gold-kicker">✦ OCTOBER · AUTUMN INK</p><h1>31 DAYS.<br><strong>31 STYLESHEETS.</strong></h1><p>A daily collection of tattoo style directions for artists: palette, contrast, texture, composition and transfer notes. Start with stylesheet 01/31, Pumpkin Harvest.</p><div class="bt-stylesheet-actions"><a class="bt-glow-button" href="#stylesheet-01">View stylesheet 01/31</a><a class="bt-outline-button" href="stencils.php">Browse stencils</a></div></div><img class="bt-stylesheet-poster" src="<?= e($campaignArt) ?>" alt="Autumn Ink Halloween 31/31 campaign poster announcing 31 days and 31 stylesheets starting October 1" width="1200" height="1200"></div></section>

  <section class="bt-stylesheet-section" id="stylesheet-01"><div class="bt-wrap"><p class="bt-purple-kicker">01 / 31 · OCTOBER 1</p><h2>Pumpkin Harvest</h2><p class="bt-stylesheet-section-lead">A practical style guide for autumnal pumpkin linework, ready to review with a tattoo artist and adapt to the final placement.</p>
    <article class="bt-sheet-feature">
      <div class="bt-sheet-feature-art"><img src="<?= e($campaignArt) ?>" alt="Autumn Ink pumpkin and Halloween campaign artwork" width="1200" height="1200" loading="lazy"><span class="bt-sheet-badge">STYLESHEET 01/31</span></div>
      <div class="bt-sheet-feature-copy"><p class="bt-purple-kicker">AUTUMN INK · DAY ONE</p><h3><?= e($stylesheet['title']) ?></h3><p><?= e($stylesheet['style']) ?>. Use the guide as a starting point; the tattoo artist should adapt line density, scale and placement to the client.</p>
        <dl class="bt-sheet-specs">
          <div><dt>Palette</dt><dd><?= e($stylesheet['palette']) ?></dd></div>
          <div><dt>Linework</dt><dd><?= e($stylesheet['linework']) ?></dd></div>
          <div><dt>Contrast</dt><dd><?= e($stylesheet['contrast']) ?></dd></div>
          <div><dt>Texture</dt><dd><?= e($stylesheet['texture']) ?></dd></div>
          <div><dt>Composition</dt><dd><?= e($stylesheet['composition']) ?></dd></div>
          <div><dt>Placement</dt><dd><?= e($stylesheet['placement']) ?></dd></div>
          <div><dt>Transfer notes</dt><dd><?= e($stylesheet['transfer']) ?></dd></div>
        </dl>
        <div class="bt-stylesheet-actions"><a class="bt-glow-button" href="stylesheets.php?download=01">↓ Download style guide</a><button class="bt-outline-button" type="button" onclick="window.print()">Print stylesheet</button></div>
      </div>
      <div class="bt-sheet-print" aria-hidden="true"><p>BEYOND TATTOO · AUTUMN INK · 01/31</p><h1>Pumpkin Harvest</h1><h2><?= e($stylesheet['style']) ?></h2><dl><dt>Palette</dt><dd><?= e($stylesheet['palette']) ?></dd><dt>Linework</dt><dd><?= e($stylesheet['linework']) ?></dd><dt>Contrast</dt><dd><?= e($stylesheet['contrast']) ?></dd><dt>Texture</dt><dd><?= e($stylesheet['texture']) ?></dd><dt>Composition</dt><dd><?= e($stylesheet['composition']) ?></dd><dt>Placement</dt><dd><?= e($stylesheet['placement']) ?></dd><dt>Transfer notes</dt><dd><?= e($stylesheet['transfer']) ?></dd></dl><p>Review and adapt this direction with a qualified tattoo artist before tattooing.</p></div>
    </article>
  </div></section>

  <section class="bt-stylesheet-section" aria-labelledby="calendar-title"><div class="bt-wrap"><p class="bt-purple-kicker">AUTUMN INK RELEASE CALENDAR</p><h2 id="calendar-title">The 31-day series</h2><p class="bt-stylesheet-section-lead">New numbered stylesheet drops are scheduled daily from October 1 through October 31. This page marks a sheet available only when its guide is published.</p><div class="bt-sheet-calendar">
    <?php for ($day = 1; $day <= 31; $day++): $releaseDate = $campaignStart->modify('+' . ($day - 1) . ' days'); $available = $day === 1; $releaseStatus = $available ? 'Available now' : ($releaseDate <= $today ? 'Awaiting sheet' : 'Drop scheduled'); ?>
      <article class="bt-sheet-day <?= $available ? 'is-available' : '' ?>"><span class="bt-sheet-day-number"><?= sprintf('%02d', $day) ?></span><div class="bt-sheet-day-copy"><strong><?= $available ? 'Pumpkin Harvest' : 'Daily stylesheet' ?></strong><small><?= e($releaseDate->format('M j')) ?> · <?= e($releaseStatus) ?></small></div></article>
    <?php endfor; ?>
  </div></div></section>

  <?php if ($publishedStyles !== []): ?><section class="bt-stylesheet-section" aria-labelledby="published-styles-title"><div class="bt-wrap"><p class="bt-purple-kicker">FROM THE VERIFIED STENCIL LIBRARY</p><h2 id="published-styles-title">Published style cards</h2><p class="bt-stylesheet-section-lead">These style cards are included with published stencil packs and can be downloaded individually.</p><div class="bt-published-sheets"><?php foreach ($publishedStyles as $asset): ?><article class="bt-published-sheet"><a href="<?= e(bt_app_url($asset['style_card_url'])) ?>" download><img src="<?= e(bt_app_url($asset['style_card_url'])) ?>" alt="<?= e($asset['title']) ?> style card" loading="lazy"></a><div class="bt-published-sheet-copy"><h3><?= e($asset['title']) ?></h3><p><?= e($asset['style']) ?> · <?= e($asset['collection']) ?></p><a class="bt-outline-button" href="<?= e(bt_app_url($asset['style_card_url'])) ?>" download>↓ Download style card</a></div></article><?php endforeach; ?></div></div></section><?php endif; ?>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="stencils.php">Stencil library</a><a href="stylesheets.php">Autumn Ink stylesheets</a><a href="index.php">Beyond Tattoo home</a><a href="../">Beyond OS</a></div></div></footer>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
