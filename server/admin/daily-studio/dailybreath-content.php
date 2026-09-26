<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__, 3) . '/beyond-id/includes/db.php';
require_once dirname(__DIR__, 3) . '/dailybreath/includes/verse-of-day.php';
header('Cache-Control: private, no-store');

$targetDate = (string)($_POST['publish_date'] ?? $_GET['date'] ?? date('Y-m-d'));
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $targetDate);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $targetDate) {
    $targetDate = date('Y-m-d');
    $parsedDate = new DateTimeImmutable($targetDate);
}
$weekStart = $parsedDate->modify('monday this week')->format('Y-m-d');
$weekEnd = $parsedDate->modify('sunday this week')->format('Y-m-d');
$weekSlug = 'week-' . $parsedDate->format('o-W');
$message = '';
$messageTone = 'success';
$activeEditor = (string)($_POST['action'] ?? 'save_verse');

function managerRow(PDO $pdo, string $sql, array $parameters): ?array {
    $query = $pdo->prepare($sql);
    $query->execute($parameters);
    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}
function managerField(string $name): string {
    return trim((string)($_POST[$name] ?? ''));
}
function managerRevision(PDO $pdo, string $type, string $key, ?array $before, array $after): void {
    $query = $pdo->prepare('INSERT INTO dailybreath_content_revisions(content_type,content_key,action,payload_json,created_by) VALUES(?,?,?,?,?)');
    $query->execute([$type, $key, 'correction_published', json_encode(['before' => $before, 'after' => $after], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (int)($_SESSION['user_id'] ?? 0)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    try {
        $pdo->beginTransaction();
        switch ((string)($_POST['action'] ?? '')) {
            case 'save_verse':
                $values = ['verse_text' => managerField('verse_text'), 'scripture_reference' => managerField('reference'), 'translation_code' => managerField('translation'), 'footer_message' => managerField('footer')];
                if ($values['verse_text'] === '' || $values['scripture_reference'] === '') throw new RuntimeException('Verse text and reference are required.');
                $before = managerRow($pdo, 'SELECT * FROM verse_day_posts WHERE publish_date=? AND locale=? LIMIT 1', [$targetDate, 'en']);
                if ($before) {
                    $query = $pdo->prepare("UPDATE verse_day_posts SET verse_text=?,scripture_reference=?,translation_code=?,footer_message=?,status='published',published_at=COALESCE(published_at,CURRENT_TIMESTAMP),updated_at=CURRENT_TIMESTAMP WHERE id=?");
                    $query->execute([$values['verse_text'], $values['scripture_reference'], $values['translation_code'], $values['footer_message'], $before['id']]);
                } else {
                    $query = $pdo->prepare("INSERT INTO verse_day_posts(publish_date,locale,translation_code,heading,verse_text,scripture_reference,footer_message,background_asset_url,status,created_by,published_at) VALUES(?,'en',?,'VERSE OF THE DAY',?,?,?,'/assets/dailybreath-login-background.webp','published',?,CURRENT_TIMESTAMP)");
                    $query->execute([$targetDate, $values['translation_code'], $values['verse_text'], $values['scripture_reference'], $values['footer_message'], (int)($_SESSION['user_id'] ?? 0)]);
                }
                managerRevision($pdo, 'verse', $targetDate, $before, $values);
                $message = 'Verse correction is live for ' . $targetDate . '.';
                break;
            case 'save_devotional':
                $values = ['title' => managerField('devotional_title'), 'excerpt' => managerField('devotional_excerpt'), 'body' => managerField('devotional_body'), 'scripture_reference' => managerField('devotional_reference')];
                if ($values['title'] === '' || $values['body'] === '') throw new RuntimeException('Devotional title and body are required.');
                $before = managerRow($pdo, 'SELECT * FROM devotionals WHERE locale=? AND publish_date>=? AND publish_date<=? ORDER BY is_published DESC,publish_date ASC,id ASC LIMIT 1', ['en', $weekStart, $weekEnd]);
                if ($before) {
                    $query = $pdo->prepare('UPDATE devotionals SET title=?,excerpt=?,body=?,scripture_reference=?,is_published=1,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                    $query->execute([$values['title'], $values['excerpt'], $values['body'], $values['scripture_reference'], $before['id']]);
                } else {
                    $query = $pdo->prepare("INSERT INTO devotionals(slug,locale,title,excerpt,body,scripture_reference,duration_minutes,publish_date,is_published) VALUES(?,'en',?,?,?,?,5,?,1)");
                    $query->execute(['weekly-' . $weekSlug, $values['title'], $values['excerpt'], $values['body'], $values['scripture_reference'], $weekStart]);
                }
                managerRevision($pdo, 'devotional', $weekSlug, $before, $values);
                $message = 'Weekly devotional correction is live for ' . $weekStart . '–' . $weekEnd . '.';
                break;
            case 'save_challenge':
                $values = ['title' => managerField('challenge_title'), 'description' => managerField('challenge_description'), 'scripture_reference' => managerField('challenge_reference')];
                if ($values['title'] === '' || $values['description'] === '') throw new RuntimeException('Challenge title and description are required.');
                $before = managerRow($pdo, 'SELECT * FROM weekly_challenges WHERE locale=? AND starts_on<=? AND ends_on>=? ORDER BY is_published DESC,starts_on DESC,id DESC LIMIT 1', ['en', $targetDate, $targetDate]);
                if ($before) {
                    $query = $pdo->prepare('UPDATE weekly_challenges SET title=?,description=?,scripture_reference=?,is_published=1,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                    $query->execute([$values['title'], $values['description'], $values['scripture_reference'], $before['id']]);
                } else {
                    $query = $pdo->prepare("INSERT INTO weekly_challenges(slug,locale,title,description,scripture_reference,starts_on,ends_on,target_count,is_published) VALUES(?,'en',?,?,?,?,?,7,1)");
                    $query->execute([$weekSlug, $values['title'], $values['description'], $values['scripture_reference'], $weekStart, $weekEnd]);
                }
                managerRevision($pdo, 'challenge', $weekSlug, $before, $values);
                $message = 'Challenge correction is live for the selected week.';
                break;
            default:
                throw new RuntimeException('Unknown content action.');
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('DailyBreath content correction: ' . $error->getMessage());
        $message = $error instanceof RuntimeException ? $error->getMessage() : 'The correction could not be saved.';
        $messageTone = 'error';
    }
}

$verseOverride = managerRow($pdo, 'SELECT * FROM verse_day_posts WHERE publish_date=? AND locale=? LIMIT 1', [$targetDate, 'en']);
$devotionalOverride = managerRow($pdo, 'SELECT * FROM devotionals WHERE locale=? AND publish_date>=? AND publish_date<=? AND is_published=1 ORDER BY publish_date ASC,id ASC LIMIT 1', ['en', $weekStart, $weekEnd]);
$challengeOverride = managerRow($pdo, 'SELECT * FROM weekly_challenges WHERE locale=? AND starts_on<=? AND ends_on>=? ORDER BY is_published DESC,starts_on DESC,id DESC LIMIT 1', ['en', $targetDate, $targetDate]);
$verseLive = $verseOverride && $verseOverride['status'] === 'published';
$devotionalLive = $devotionalOverride && (int)$devotionalOverride['is_published'] === 1;
$challengeLive = $challengeOverride && (int)$challengeOverride['is_published'] === 1;
$verse = $verseLive ? ['text' => $verseOverride['verse_text'], 'reference' => $verseOverride['scripture_reference']] : (dailybreath_recovery_verse_for_date($targetDate, false) ?: dailybreath_recovery_verse_for_date($targetDate) ?: ['text' => 'Be still, and know that I am God.', 'reference' => 'Psalm 46:10']);
$devotional = $devotionalLive ? $devotionalOverride : (dailybreath_recovery_devotional_for_date($targetDate, false) ?: dailybreath_recovery_devotional_for_date($targetDate) ?: []);
$challenge = $challengeLive ? $challengeOverride : (dailybreath_recovery_challenge_for_date($targetDate) ?: []);
$revisions = $pdo->query('SELECT content_type,content_key,action,created_at FROM dailybreath_content_revisions ORDER BY id DESC LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
require dirname(__DIR__) . '/_header.php';
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css"><link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"><link rel="stylesheet" href="/server/admin/daily-studio/content-manager.css?v=<?= (int)filemtime(__DIR__ . '/content-manager.css') ?>">
<div class="manager-head"><div><p class="studio-eyebrow">DailyBreath · emergency controls</p><h1>Content corrections</h1><p class="muted">Automated content runs on a daily or weekly schedule. Use this page to correct the selected day or week.</p></div><div class="manager-actions"><form method="get"><label for="manager-date">Content date</label><input class="input compact" id="manager-date" type="date" name="date" value="<?= DailyStudio::esc($targetDate) ?>" onchange="this.form.submit()"></form><a class="btn compact" href="/dailybreath/" target="_blank" rel="noopener">Open app ↗</a></div></div>
<?php if ($message): ?><div class="manager-alert <?= $messageTone ?>" role="status"><?= DailyStudio::esc($message) ?></div><?php endif; ?>
<p class="manager-help">Select a date, open an item, make the correction, then save. The verse applies to that date; the devotional and challenge apply to the whole week shown below.</p>
<section class="manager-editors" aria-label="Content corrections">
  <details class="manager-card correction-card" <?= $activeEditor === 'save_verse' ? 'open' : '' ?>><summary><span><span class="manager-kicker">VERSE OF THE DAY</span><strong><?= DailyStudio::esc((string)$verse['reference']) ?></strong></span><span class="state-pill <?= $verseLive ? 'live' : '' ?>"><?= $verseLive ? 'Live correction' : 'Automated' ?></span></summary>
    <form method="post"><input type="hidden" name="csrf" value="<?= DailyStudio::esc(Auth::csrf()) ?>"><input type="hidden" name="publish_date" value="<?= DailyStudio::esc($targetDate) ?>"><div class="field"><label for="verse-text">Verse text</label><textarea class="input" id="verse-text" name="verse_text" rows="5" required><?= DailyStudio::esc((string)$verse['text']) ?></textarea></div><div class="two"><div class="field"><label for="verse-reference">Reference</label><input class="input" id="verse-reference" name="reference" value="<?= DailyStudio::esc((string)$verse['reference']) ?>" required></div><div class="field"><label for="verse-translation">Translation</label><input class="input" id="verse-translation" name="translation" value="<?= DailyStudio::esc((string)($verseOverride['translation_code'] ?? 'WEB')) ?>"></div></div><div class="field"><label for="verse-footer">Footer message</label><input class="input" id="verse-footer" name="footer" value="<?= DailyStudio::esc((string)($verseOverride['footer_message'] ?? '')) ?>"></div><button class="btn" name="action" value="save_verse">Save live correction</button></form>
  </details>
  <details class="manager-card correction-card" <?= $activeEditor === 'save_devotional' ? 'open' : '' ?>><summary><span><span class="manager-kicker">WEEKLY DEVOTIONAL · <?= DailyStudio::esc($weekStart) ?>–<?= DailyStudio::esc($weekEnd) ?></span><strong><?= DailyStudio::esc((string)($devotional['title'] ?? 'No scheduled devotional')) ?></strong></span><span class="state-pill <?= $devotionalLive ? 'live' : '' ?>"><?= $devotionalLive ? 'Live correction' : ($devotional ? 'Automated' : 'No schedule') ?></span></summary>
    <form method="post"><input type="hidden" name="csrf" value="<?= DailyStudio::esc(Auth::csrf()) ?>"><input type="hidden" name="publish_date" value="<?= DailyStudio::esc($targetDate) ?>"><div class="field"><label for="devotional-title">Title</label><input class="input" id="devotional-title" name="devotional_title" value="<?= DailyStudio::esc((string)($devotional['title'] ?? '')) ?>" required></div><div class="field"><label for="devotional-excerpt">Short description</label><textarea class="input" id="devotional-excerpt" name="devotional_excerpt" rows="2"><?= DailyStudio::esc((string)($devotional['excerpt'] ?? '')) ?></textarea></div><div class="field"><label for="devotional-body">Full devotional</label><textarea class="input" id="devotional-body" name="devotional_body" rows="9" required><?= DailyStudio::esc((string)($devotional['body'] ?? '')) ?></textarea></div><div class="field"><label for="devotional-reference">Scripture reference</label><input class="input" id="devotional-reference" name="devotional_reference" value="<?= DailyStudio::esc((string)($devotional['scripture_reference'] ?? '')) ?>"></div><button class="btn" name="action" value="save_devotional">Save weekly correction</button></form>
  </details>
  <details class="manager-card correction-card" <?= $activeEditor === 'save_challenge' ? 'open' : '' ?>><summary><span><span class="manager-kicker">WEEKLY CHALLENGE · <?= DailyStudio::esc($weekStart) ?>–<?= DailyStudio::esc($weekEnd) ?></span><strong><?= DailyStudio::esc((string)($challenge['title'] ?? 'No scheduled challenge')) ?></strong></span><span class="state-pill <?= $challengeLive ? 'live' : '' ?>"><?= $challengeLive ? 'Live correction' : ($challenge ? 'Automated' : 'No schedule') ?></span></summary>
    <form method="post"><input type="hidden" name="csrf" value="<?= DailyStudio::esc(Auth::csrf()) ?>"><input type="hidden" name="publish_date" value="<?= DailyStudio::esc($targetDate) ?>"><div class="field"><label for="challenge-title">Title</label><input class="input" id="challenge-title" name="challenge_title" value="<?= DailyStudio::esc((string)($challenge['title'] ?? '')) ?>" required></div><div class="field"><label for="challenge-description">Description</label><textarea class="input" id="challenge-description" name="challenge_description" rows="6" required><?= DailyStudio::esc((string)($challenge['description'] ?? '')) ?></textarea></div><div class="field"><label for="challenge-reference">Scripture reference</label><input class="input" id="challenge-reference" name="challenge_reference" value="<?= DailyStudio::esc((string)($challenge['scripture_reference'] ?? '')) ?>"></div><button class="btn" name="action" value="save_challenge">Save live correction</button></form>
  </details>
</section>
<details class="manager-card revision-card"><summary><span><span class="manager-kicker">AUDIT TRAIL</span><strong>Recent corrections and publishing activity</strong></span></summary><?php if (!$revisions): ?><p class="muted">No changes recorded yet.</p><?php else: ?><div class="revision-list"><?php foreach ($revisions as $revision): ?><article><span class="revision-type"><?= DailyStudio::esc((string)$revision['content_type']) ?></span><div><strong><?= DailyStudio::esc(str_replace('_', ' ', (string)$revision['action'])) ?></strong><small><?= DailyStudio::esc((string)$revision['content_key']) ?> · <?= DailyStudio::esc(date('M j, g:i A', strtotime((string)$revision['created_at']))) ?></small></div></article><?php endforeach; ?></div><?php endif; ?></details>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
