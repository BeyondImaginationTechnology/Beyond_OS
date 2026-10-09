<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/source-reports.php';
require_once __DIR__ . '/../includes/ecosystem.php';

header('Cache-Control: private, no-store');
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$categories = [
    'rights' => 'Rights or license concern',
    'suspicious' => 'Suspicious link or redirect',
    'playback' => 'Playback problem',
    'other' => 'Other source safety issue',
];
$inputString = static fn(mixed $value): string => is_string($value) ? trim($value) : '';
$token = $_SESSION['tv_source_report_token'] ??= bin2hex(random_bytes(32));
$fields = [
    'category' => $inputString($_POST['category'] ?? 'rights'),
    'channel_slug' => $inputString($_POST['channel_slug'] ?? $_GET['channel'] ?? ''),
    'title' => $inputString($_POST['title'] ?? $_GET['title'] ?? ''),
    'source_url' => $inputString($_POST['source_url'] ?? $_GET['source'] ?? ''),
    'page_url' => $inputString($_POST['page_url'] ?? $_GET['page'] ?? ''),
    'details' => $inputString($_POST['details'] ?? ''),
];
$error = '';
$receipt = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > 16000) {
        $error = 'This report is too long.';
    } elseif (!hash_equals($token, $inputString($_POST['csrf'] ?? ''))) {
        $error = 'Please reload the page and try again.';
    } elseif ($inputString($_POST['website'] ?? '') !== '') {
        $error = 'The report could not be submitted.';
    } elseif (!isset($categories[$fields['category']])) {
        $error = 'Choose a report category.';
    } elseif (!preg_match('/^[a-z0-9-]{0,128}$/D', $fields['channel_slug'])) {
        $error = 'The channel reference is invalid.';
    } elseif (mb_strlen($fields['title']) > 200 || mb_strlen($fields['details']) > 2000) {
        $error = 'The title or details are too long.';
    } else {
        foreach (['source_url', 'page_url'] as $name) {
            $value = trim($fields[$name]);
            if ($value === '' || strlen($value) > 2048 || !filter_var($value, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string)parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $error = 'Enter a valid source and page link beginning with https:// or http://.';
                break;
            }
            $fields[$name] = $value;
        }
        if ($error === '' && !in_array(strtolower((string)parse_url($fields['page_url'], PHP_URL_HOST)),
            ['beyondimagination.co.technology', 'tv.beyondimagination.co.technology'], true)) {
            $error = 'Enter a Beyond TV page link.';
        }
    }
    if ($error === '' && time() - (int)($_SESSION['tv_source_report_last'] ?? 0) < 30) {
        $error = 'Please wait a moment before sending another report.';
    }
    if ($error === '') {
        try {
            $db = beyond_tv_source_reports_db();
            $insert = $db->prepare('INSERT INTO source_reports (created_at, category, channel_slug, title, source_url, page_url, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([
                gmdate('c'), $fields['category'], $fields['channel_slug'], trim($fields['title']),
                $fields['source_url'], $fields['page_url'], trim($fields['details']),
            ]);
            $receipt = (int)$db->lastInsertId();
            $_SESSION['tv_source_report_last'] = time();
            $_SESSION['tv_source_report_token'] = $token = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            error_log('Beyond TV source report failed: ' . $exception->getMessage());
            $error = 'Reporting is temporarily unavailable. Please contact support instead.';
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Source Safety | Beyond TV</title><link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.6">
<style>body{background:#090a16;color:#f7f7fb}.safety{width:min(760px,calc(100% - 32px));margin:40px auto 80px}.safety h1{font-size:clamp(2.2rem,6vw,4rem);margin:.3em 0}.safety p{line-height:1.65;color:#c2c5d4}.safety-card{border:1px solid #34384f;border-radius:20px;background:#151726;padding:24px;margin:20px 0}.safety-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.safety-grid div{padding:15px;border:1px solid #34384f;border-radius:14px}.safety-grid strong{display:block;color:#d6b6ff}.safety-grid small{display:block;margin-top:6px;line-height:1.45;color:#c2c5d4}.safety label{display:grid;gap:7px;margin:14px 0;font-weight:700}.safety input,.safety select,.safety textarea{width:100%;padding:12px;border:1px solid #4a4e68;border-radius:10px;background:#090a16;color:#fff;font:inherit}.safety textarea{min-height:120px}.safety button{padding:12px 20px;border:0;border-radius:10px;background:#a765ff;color:#fff;font-weight:800;cursor:pointer}.safety .quiet{font-size:.9rem}.safety .message{padding:14px;border-radius:10px;background:#23334c;color:#fff}.safety .trap{position:absolute;left:-9999px}@media(max-width:650px){.safety-grid{grid-template-columns:1fr}}</style>
</head><body>
<?php require __DIR__ . '/partials/header.php'; ?>
<main class="safety">
  <a href="/beyond-tv/">← Beyond TV</a>
  <p class="eyebrow">SOURCE SAFETY</p><h1>Check and report a source</h1>
  <?php if (in_array(strtolower((string)($_SESSION['role'] ?? '')), ['admin', 'super_admin'], true)): ?><p><a href="/server/admin/beyond-tv-source-reports.php">Review source reports →</a></p><?php endif; ?>
  <p>Help us identify questionable video links, rights claims, and playback issues. A working video link proves availability, not permission to stream it. Reports enter a private review queue; they do not trigger automatic takedowns.</p>
  <div class="safety-grid" aria-label="Review process">
    <div><strong>1. Source verification</strong><small>Identify the provider, exact file or item, and its stated license.</small></div>
    <div><strong>2. Evidence</strong><small>Record the source link, Beyond TV page, title, and what you observed.</small></div>
    <div><strong>3. Abuse handling</strong><small>Review reports, investigate concerns, and decide whether to disable a source.</small></div>
  </div>
  <?php if ($receipt !== null): ?><p class="message" role="status">Report TV-<?=$receipt?> received for review. Thank you.</p><?php else: ?>
  <?php if ($error !== ''): ?><p class="message" role="alert"><?=$escape($error)?></p><?php endif; ?>
  <form class="safety-card" method="post" action="/beyond-tv/source-safety.php">
    <h2>Report a source</h2>
    <input type="hidden" name="csrf" value="<?=$escape($token)?>">
    <label class="trap" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
    <label>Concern<select name="category" required><?php foreach ($categories as $key => $label): ?><option value="<?=$escape($key)?>"<?=$fields['category'] === $key ? ' selected' : ''?>><?=$escape($label)?></option><?php endforeach; ?></select></label>
    <label>Source link<input type="url" name="source_url" maxlength="2048" required value="<?=$escape($fields['source_url'])?>" placeholder="https://archive.org/details/..."></label>
    <label>Beyond TV page<input type="url" name="page_url" maxlength="2048" required value="<?=$escape($fields['page_url'])?>" placeholder="https://beyondimagination.co.technology/beyond-tv/..."></label>
    <label>Channel slug (optional)<input name="channel_slug" maxlength="128" value="<?=$escape($fields['channel_slug'])?>"></label>
    <label>Video or item title<input name="title" maxlength="200" value="<?=$escape($fields['title'])?>"></label>
    <label>What did you observe?<textarea name="details" maxlength="2000" placeholder="Include a timestamp, unexpected redirect, or why the rights claim may be wrong."><?=$escape($fields['details'])?></textarea></label>
    <button type="submit">Send for review</button>
    <p class="quiet">Do not include passwords or private personal information. For a formal rights notice, email <a href="mailto:support@beyondimagination.co.technology">support@beyondimagination.co.technology</a>.</p>
  </form><?php endif; ?>
  <p class="quiet">Internet Archive says it does not guarantee the copyright status or rights information on item pages. <a href="https://archivesupport.zendesk.com/hc/en-us/articles/360014759692-Rights" target="_blank" rel="noopener noreferrer">Read its rights guidance</a>.</p>
</main><script src="/beyond-tv/assets/js/app.js?v=1.1.1"></script></body></html>
