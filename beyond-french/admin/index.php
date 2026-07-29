<?php
declare(strict_types=1);
require __DIR__ . '/../includes/functions.php';
require_admin();

$lessons = all_lessons();
$pdo = sqlite_db();
$subscribers = $pdo->query('SELECT name,email,preferred_language,created_at FROM french_subscribers ORDER BY created_at DESC LIMIT 100')->fetchAll();
$audioRows = $pdo->query("SELECT id,lesson_id,provider,voice,language,audio_path,generation_status,created_at FROM french_lesson_audio ORDER BY id DESC LIMIT 20")->fetchAll();
$csrfToken = french_csrf_token();
$lessonPayload = array_map(static function (array $lesson): array {
    $english = trim((string)($lesson['english'] ?? ''));
    $french = trim((string)($lesson['french'] ?? ''));
    $kreyol = trim((string)($lesson['kreyol'] ?? ''));
    $spanish = trim((string)($lesson['spanish'] ?? ''));
    $patois = trim((string)($lesson['patois'] ?? ''));
    return [
        'id' => (int)($lesson['id'] ?? 0),
        'label' => (string)($lesson['date'] ?? '') . ' - ' . (string)($lesson['english'] ?? 'Lesson'),
        'text' => (string)($lesson['french'] ?? ''),
        'texts' => [
            'en-US' => $english,
            'fr-CA' => (string)($lesson['french'] ?? ''),
            'fr-FR' => (string)($lesson['french'] ?? ''),
            'ht-HT' => (string)($lesson['kreyol'] ?? ''),
            'en-JM' => (string)($lesson['patois'] ?? ''),
            'es-ES' => (string)($lesson['spanish'] ?? ''),
        ],
        'video_script' => "Today's phrase is: {$english} In French: {$french} In Haitian Creole: {$kreyol} In Spanish: {$spanish} In Jamaican Patois: {$patois} Practice it today, and go beyond French.",
        'language' => 'fr-CA',
    ];
}, $lessons);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= h($csrfToken) ?>">
<link rel="stylesheet" href="../assets/css/style.css?v=2.2.0">
<link rel="stylesheet" href="../assets/css/admin-narration.css?v=2.2.0">
<title>Beyond French Admin</title>
</head>
<body class="admin-body narration-admin">
<div class="admin-panel">
    <header class="admin-head">
        <div><span class="admin-kicker">Beyond French Academy</span><h1>Administration</h1><p>Lessons, subscribers, and production narration.</p></div>
        <nav><a href="../../beyond-id/admin/">Beyond ID Admin</a><a href="logout.php">Log out</a></nav>
    </header>

    <div class="stats">
        <div><strong><?= count($lessons) ?></strong><span>Lessons</span></div>
        <div><strong><?= count($subscribers) ?></strong><span>Subscribers</span></div>
        <div><strong><?= count(array_filter($audioRows, static fn(array $row): bool => ($row['generation_status'] ?? '') === 'ready')) ?></strong><span>Recent audio</span></div>
    </div>

    <section class="narration-studio" id="narration-studio">
        <div class="studio-heading">
            <div><span class="admin-kicker">Narration Studio</span><h2>Generate lesson audio</h2><p>Preview a provider, create the final MP3, and publish it to the selected lesson.</p></div>
            <span class="secure-pill">Private API keys</span>
        </div>

        <ol class="workflow-steps" aria-label="Narration workflow">
            <li class="active"><span>1</span>Provider</li>
            <li><span>2</span>Voice</li>
            <li><span>3</span>Preview</li>
            <li><span>4</span>Generate</li>
            <li><span>5</span>Publish</li>
        </ol>

        <form class="narration-form" data-narration-form data-voices-url="../api/narration/voices.php" data-preview-url="../api/narration/preview.php" data-generate-url="../api/narration/generate.php">
            <label>Lesson
                <select name="lesson_id" required>
                    <?php foreach ($lessonPayload as $lesson): ?>
                        <option value="<?= (int)$lesson['id'] ?>"><?= h($lesson['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Provider
                <select name="provider" required>
                    <option value="openai">OpenAI</option>
                    <option value="elevenlabs">ElevenLabs</option>
                    <option value="azure">Azure Speech</option>
                </select>
            </label>
            <label>Narration type
                <select name="narration_type" required>
                    <option value="phrase">Lesson phrase audio</option>
                    <option value="video">Daily video voiceover</option>
                </select>
            </label>
            <label>Language
                <select name="language" required>
                    <option value="en-US">English - Video narrator</option>
                    <option value="fr-CA" selected>French - Canada</option>
                    <option value="fr-FR">French - France</option>
                    <option value="ht-HT">Haitian Kreyol</option>
                    <option value="en-JM">Jamaican Patois</option>
                    <option value="es-ES">Spanish - Spain</option>
                </select>
            </label>
            <label>Voice
                <select name="voice" required><option value="">Loading voices...</option></select>
            </label>
            <label class="full-field">Narration text
                <textarea name="text" rows="5" maxlength="2000" required></textarea>
                <small><span data-text-count>0</span>/2000 characters</small>
            </label>
            <label class="full-field">Voice direction
                <textarea name="instructions" rows="3" maxlength="800">Speak like a warm and encouraging French teacher. Use natural pacing and clear pronunciation.</textarea>
            </label>
            <label class="speed-field">Speed <output data-speed-output>0.95x</output>
                <input name="speed" type="range" min="0.70" max="1.30" step="0.05" value="0.95">
            </label>
            <input type="hidden" name="format" value="mp3">

            <div class="narration-actions full-field">
                <button type="button" class="button secondary" data-preview-narration>Preview narration</button>
                <button type="submit" class="button primary">Generate, attach and publish</button>
            </div>
        </form>

        <div class="narration-result" data-narration-result hidden>
            <div><strong data-result-title>Narration preview</strong><p data-result-message>Ready to play.</p></div>
            <audio controls preload="metadata" data-narration-audio></audio>
            <a class="button secondary" data-audio-link href="#" target="_blank" rel="noopener" hidden>Open MP3</a>
        </div>
        <p class="narration-status" data-narration-status aria-live="polite">Choose a provider and voice to begin.</p>
    </section>

    <script type="application/json" id="french-lessons-data"><?= json_encode($lessonPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

    <section class="admin-section">
        <div class="studio-heading"><div><span class="admin-kicker">Library</span><h2>Recent lesson audio</h2></div></div>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>Lesson</th><th>Provider</th><th>Voice</th><th>Language</th><th>Status</th><th>Created</th></tr></thead><tbody>
        <?php if (!$audioRows): ?><tr><td colspan="7">No generated narration yet.</td></tr><?php endif; ?>
        <?php foreach ($audioRows as $row): ?><tr><td><?= (int)$row['id'] ?></td><td><?= (int)$row['lesson_id'] ?></td><td><?= h((string)$row['provider']) ?></td><td><?= h((string)$row['voice']) ?></td><td><?= h((string)$row['language']) ?></td><td><span class="audio-status <?= h((string)$row['generation_status']) ?>"><?= h((string)$row['generation_status']) ?></span></td><td><?= h((string)$row['created_at']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="admin-section">
        <div class="studio-heading"><div><span class="admin-kicker">Community</span><h2>Subscribers</h2></div></div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Language</th><th>Date</th></tr></thead><tbody>
        <?php if (!$subscribers): ?><tr><td colspan="4">No subscribers yet.</td></tr><?php endif; ?>
        <?php foreach ($subscribers as $subscriber): ?><tr><td><?= h((string)$subscriber['name']) ?></td><td><?= h((string)$subscriber['email']) ?></td><td><?= h((string)$subscriber['preferred_language']) ?></td><td><?= h((string)$subscriber['created_at']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
</div>
<script src="../assets/js/admin-narration.js?v=2.3.0" defer></script>
</body>
</html>
