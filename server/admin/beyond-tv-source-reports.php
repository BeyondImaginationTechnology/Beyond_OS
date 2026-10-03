<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../beyond-id/includes/admin-check.php';
require_once __DIR__ . '/../../beyond-tv/includes/source-reports.php';

header('Cache-Control: private, no-store');
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$inputString = static fn(mixed $value): string => is_string($value) ? trim($value) : '';
$token = $_SESSION['tv_report_review_token'] ??= bin2hex(random_bytes(32));
$statuses = ['new', 'reviewing', 'actioned', 'closed'];
$message = '';
$error = '';
try {
    $db = beyond_tv_source_reports_db();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = $inputString($_POST['status'] ?? '');
        $note = $inputString($_POST['review_note'] ?? '');
        if (!hash_equals($token, $inputString($_POST['csrf'] ?? '')) || !$id
            || !in_array($status, $statuses, true) || mb_strlen($note) > 2000) {
            http_response_code(400);
            $error = 'Review update was rejected.';
        } else {
            $update = $db->prepare('UPDATE source_reports SET status = ?, review_note = ?, reviewed_at = ? WHERE id = ?');
            $update->execute([$status, $note, gmdate('c'), $id]);
            $message = $update->rowCount() ? 'Review updated.' : 'Report not found.';
        }
    }
    $rows = $db->query('SELECT * FROM source_reports ORDER BY id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    error_log('Beyond TV report review failed: ' . $exception->getMessage());
    http_response_code(503);
    $rows = [];
    $error = 'Source reports are temporarily unavailable.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Beyond TV source reports</title>
<style>body{margin:0;background:#10131e;color:#f5f6fa;font:16px system-ui,sans-serif}.shell{width:min(1050px,calc(100% - 32px));margin:40px auto}a{color:#bfa3ff}.card{border:1px solid #3c4158;border-radius:16px;background:#1b2030;padding:22px;margin:18px 0}.meta{color:#b9bdca;font-size:.9rem}.url{overflow-wrap:anywhere;padding:8px;background:#10131e;border-radius:8px}label{display:grid;gap:6px;margin:12px 0}select,textarea,button{font:inherit}select,textarea{padding:9px;border:1px solid #606783;border-radius:8px;background:#10131e;color:#fff}textarea{min-height:68px}button{padding:10px 16px;border:0;border-radius:8px;background:#9c69f8;color:white;font-weight:700}.notice{padding:12px;border-radius:8px;background:#283552}</style>
</head><body><main class="shell"><a href="/beyond-id/admin/">← Admin</a><h1>Beyond TV source reports</h1>
<p>Review source links and evidence before changing a channel. User reports are leads, not proof of a rights violation. Links below are shown as text so they cannot open accidentally.</p>
<?php if ($message !== ''): ?><p class="notice" role="status"><?=$escape($message)?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice" role="alert"><?=$escape($error)?></p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="card">
  <h2>TV-<?=$escape((string)$row['id'])?> · <?=$escape((string)$row['category'])?> · <?=$escape((string)$row['status'])?></h2>
  <p class="meta">Received <?=$escape((string)$row['created_at'])?> · Channel <?=$escape((string)$row['channel_slug'])?> · <?=$escape((string)$row['title'])?></p>
  <strong>Source</strong><p class="url"><?=$escape((string)$row['source_url'])?></p>
  <strong>Beyond TV page</strong><p class="url"><?=$escape((string)$row['page_url'])?></p>
  <strong>Observation</strong><p><?=nl2br($escape((string)$row['details']))?></p>
  <form method="post"><input type="hidden" name="csrf" value="<?=$escape($token)?>"><input type="hidden" name="id" value="<?=$escape((string)$row['id'])?>">
    <label>Status<select name="status"><?php foreach ($statuses as $status): ?><option value="<?=$escape($status)?>"<?=$row['status'] === $status ? ' selected' : ''?>><?=$escape(ucfirst($status))?></option><?php endforeach; ?></select></label>
    <label>Review note<textarea name="review_note" maxlength="2000"><?=$escape((string)$row['review_note'])?></textarea></label><button type="submit">Save review</button>
  </form>
</article><?php endforeach; ?>
<?php if (!$rows && $error === ''): ?><p>No reports yet.</p><?php endif; ?>
</main></body></html>
