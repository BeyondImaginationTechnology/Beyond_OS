<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$catalogPath = dirname(__DIR__, 3) . '/beyond-tattoo/data/autumn-ink-stylesheets.json';
$assetDirectory = dirname(__DIR__, 3) . '/beyond-tattoo/assets/stylesheets';
$notice = '';
$error = '';

function stylesheetPublisherText(mixed $value, int $limit = 1200): string
{
    $text = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
}

function stylesheetPublisherSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function stylesheetPublisherCatalog(string $path): array
{
    $catalog = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($catalog) || !is_array($catalog['campaign'] ?? null) || !is_array($catalog['releases'] ?? null)) {
        throw new RuntimeException('The stylesheet catalog is invalid.');
    }
    return $catalog;
}

$catalog = stylesheetPublisherCatalog($catalogPath);
$campaign = $catalog['campaign'];
$timezone = new DateTimeZone((string) ($campaign['timezone'] ?? 'America/Vancouver'));
$start = new DateTimeImmutable((string) ($campaign['start_date'] ?? ''), $timezone);
$total = (int) ($campaign['total_releases'] ?? 31);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (!Auth::verifyCsrf($_POST['_csrf'] ?? null)) {
            throw new RuntimeException('Your session check expired. Reload the page and try again.');
        }
        $sequence = (int) ($_POST['sequence'] ?? 0);
        if ($sequence < 1 || $sequence > $total) {
            throw new RuntimeException('Choose a stylesheet number from 1 to ' . $total . '.');
        }
        $expectedDate = $start->modify('+' . ($sequence - 1) . ' days')->format('Y-m-d');
        $title = stylesheetPublisherText($_POST['title'] ?? '', 120);
        $style = stylesheetPublisherText($_POST['style'] ?? '', 180);
        if ($title === '' || $style === '') {
            throw new RuntimeException('Add both a title and style label.');
        }
        $existing = null;
        foreach ($catalog['releases'] as $release) {
            if ((int) ($release['sequence'] ?? 0) === $sequence) {
                $existing = $release;
                break;
            }
        }
        $asset = (string) ($existing['asset'] ?? '');
        if (isset($_FILES['stylesheet']) && (int) ($_FILES['stylesheet']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['stylesheet'];
            if ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
                throw new RuntimeException('The stylesheet image did not finish uploading.');
            }
            if ((int) ($upload['size'] ?? 0) < 1 || (int) ($upload['size'] ?? 0) > 20 * 1024 * 1024) {
                throw new RuntimeException('Stylesheet images must be 20 MB or smaller.');
            }
            $bytes = file_get_contents((string) $upload['tmp_name']);
            $image = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
            $mime = is_array($image) ? (string) ($image['mime'] ?? '') : '';
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensions[$mime])) {
                throw new RuntimeException('Upload a valid JPG, PNG or WebP stylesheet image.');
            }
            $width = (int) ($image[0] ?? 0);
            $height = (int) ($image[1] ?? 0);
            if ($width < 900 || $height < 900 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
                throw new RuntimeException('Use an image from 900px to 12,000px per edge and no more than 40 megapixels.');
            }
            $filename = sprintf('autumn-ink-%02d-%s.%s', $sequence, stylesheetPublisherSlug($title), $extensions[$mime]);
            if (!is_dir($assetDirectory) && !mkdir($assetDirectory, 0775, true) && !is_dir($assetDirectory)) {
                throw new RuntimeException('The stylesheet asset folder could not be created.');
            }
            if (file_put_contents($assetDirectory . '/' . $filename, $bytes, LOCK_EX) === false) {
                throw new RuntimeException('The stylesheet image could not be saved.');
            }
            @chmod($assetDirectory . '/' . $filename, 0664);
            $asset = 'assets/stylesheets/' . $filename;
        }
        if ($asset === '') {
            throw new RuntimeException('Choose a stylesheet image to publish.');
        }
        $release = [
            'sequence' => $sequence,
            'date' => $expectedDate,
            'title' => $title,
            'asset' => $asset,
            'style' => $style,
            'palette' => stylesheetPublisherText($_POST['palette'] ?? '', 500),
            'linework' => stylesheetPublisherText($_POST['linework'] ?? '', 900),
            'contrast' => stylesheetPublisherText($_POST['contrast'] ?? '', 900),
            'texture' => stylesheetPublisherText($_POST['texture'] ?? '', 900),
            'composition' => stylesheetPublisherText($_POST['composition'] ?? '', 900),
            'placement' => stylesheetPublisherText($_POST['placement'] ?? '', 700),
            'transfer' => stylesheetPublisherText($_POST['transfer'] ?? '', 900),
        ];
        $updated = false;
        foreach ($catalog['releases'] as $index => $item) {
            if ((int) ($item['sequence'] ?? 0) === $sequence) {
                $catalog['releases'][$index] = $release;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $catalog['releases'][] = $release;
        }
        usort($catalog['releases'], static fn(array $a, array $b): int => ((int) $a['sequence']) <=> ((int) $b['sequence']));
        if (file_put_contents($catalogPath, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) {
            throw new RuntimeException('The stylesheet catalog could not be updated.');
        }
        $notice = sprintf('Stylesheet %02d · %s is now published for %s.', $sequence, $title, (new DateTimeImmutable($expectedDate, $timezone))->format('F j, Y'));
        $catalog = stylesheetPublisherCatalog($catalogPath);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$published = [];
foreach ($catalog['releases'] as $release) {
    $sequence = (int) ($release['sequence'] ?? 0);
    $asset = ltrim((string) ($release['asset'] ?? ''), '/');
    if ($sequence && $asset !== '' && is_file(dirname(__DIR__, 3) . '/beyond-tattoo/' . $asset)) {
        $published[$sequence] = $release;
    }
}
$today = new DateTimeImmutable('today', $timezone);
$todaySequence = max(1, min($total, (int) $start->diff($today)->format('%r%a') + 1));
require dirname(__DIR__) . '/_header.php';
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css?v=<?= (int) filemtime(__DIR__ . '/studio.css') ?>">
<style>
.stylesheet-publisher{max-width:1120px;margin:0 auto;padding:28px}.stylesheet-publisher h1{margin:6px 0}.stylesheet-publisher .muted{max-width:760px;line-height:1.6}.publisher-card{margin-top:24px;padding:24px;border:1px solid #d9c8aa;border-radius:18px;background:#fffaf0;box-shadow:0 10px 28px rgba(51,35,15,.08)}.publisher-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.publisher-grid label{display:grid;gap:7px;font-weight:800}.publisher-grid input,.publisher-grid textarea,.publisher-grid select{width:100%;padding:11px;border:1px solid #b9aa95;border-radius:10px;background:#fff;color:#20180f;font:inherit}.publisher-grid textarea{min-height:95px;resize:vertical}.publisher-wide{grid-column:1/-1}.publisher-note{padding:14px;border-radius:12px;background:#f5ecd9;color:#5e4523}.publisher-notice{padding:14px 16px;border-radius:12px;margin-top:16px}.publisher-notice.ok{background:#e5f3df;color:#244a28}.publisher-notice.error{background:#ffe6e1;color:#7a2317}.publisher-release-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:24px}.publisher-release{padding:14px;border:1px solid #d8c9b1;border-radius:13px;background:#fff}.publisher-release b,.publisher-release small{display:block}.publisher-release small{margin-top:6px;color:#665c50}@media(max-width:720px){.stylesheet-publisher{padding:18px}.publisher-grid{grid-template-columns:1fr}.publisher-wide{grid-column:auto}}
</style>
<section class="stylesheet-publisher">
  <a class="btn secondary" href="/server/admin/daily-studio/">← Studio Home</a>
  <p class="console-eyebrow">Beyond Tattoo · Autumn Ink</p><h1>Publish today’s stylesheet</h1>
  <p class="muted">Upload one finished stylesheet image, add its practical tattoo notes, and publish the matching numbered Autumn Ink release. The public Stylesheets page reads this catalog directly.</p>
  <?php if ($notice !== ''): ?><div class="publisher-notice ok"><?= DailyStudio::esc($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="publisher-notice error"><?= DailyStudio::esc($error) ?></div><?php endif; ?>
  <form class="publisher-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= DailyStudio::esc(Auth::csrf()) ?>">
    <div class="publisher-grid">
      <label>Stylesheet number<select name="sequence" id="sequence"><?php for ($number=1; $number<=$total; $number++): ?><option value="<?= $number ?>"<?= $number === $todaySequence ? ' selected' : '' ?>><?= sprintf('%02d', $number) ?> · <?= $start->modify('+' . ($number - 1) . ' days')->format('M j') ?><?= isset($published[$number]) ? ' · published' : '' ?></option><?php endfor; ?></select></label>
      <label>Stylesheet image<input name="stylesheet" type="file" accept="image/jpeg,image/png,image/webp"><small>Required for a new release. JPG, PNG or WebP; 900px+; 20 MB maximum.</small></label>
      <label class="publisher-wide">Release title<input name="title" maxlength="120" placeholder="Example: Moonlit Orchard Flash" required></label>
      <label>Style label<input name="style" maxlength="180" value="Autumn illustrative blackwork" required></label>
      <label>Palette<input name="palette" maxlength="500" value="Pure black linework on a clean white field."></label>
      <label class="publisher-wide">Linework<textarea name="linework" required>Use clean outer contours with enough open spacing for reliable transfer at the intended tattoo size.</textarea></label>
      <label>Contrast<textarea name="contrast" required>Keep the primary focal motif darkest and preserve readable open skin around small details.</textarea></label>
      <label>Texture<textarea name="texture" required>Use restrained hatching and stipple; avoid dense texture in small transfer areas.</textarea></label>
      <label class="publisher-wide">Composition<textarea name="composition" required>A balanced flash sheet of independent tattoo motifs with clear hierarchy and breathing room.</textarea></label>
      <label>Placement<textarea name="placement" required>Wrist, ankle, forearm, calf or upper arm; adapt scale and detail with the artist.</textarea></label>
      <label>Transfer notes<textarea name="transfer" required>Choose one motif per transfer and simplify the smallest accents when final size requires it.</textarea></label>
    </div>
    <p class="publisher-note">Publishing writes the image into <code>beyond-tattoo/assets/stylesheets</code> and updates the numbered public catalog. Re-publish an existing day with a replacement image to update it.</p>
    <button class="btn" type="submit">Publish stylesheet →</button>
  </form>
  <h2>Published files</h2><div class="publisher-release-grid"><?php foreach ($published as $number => $release): ?><article class="publisher-release"><b><?= sprintf('%02d', $number) ?> · <?= DailyStudio::esc((string) $release['title']) ?></b><small><?= DailyStudio::esc((string) $release['date']) ?></small></article><?php endforeach; ?></div>
</section>
<?php require dirname(__DIR__) . '/_footer.php'; ?>