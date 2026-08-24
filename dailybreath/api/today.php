<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$locale = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['locale'] ?? 'en')) ?: 'en';
$tradition = dailybreath_faith_tradition((string)($_GET['tradition'] ?? 'bible'));
$contentDate = (string)($_GET['date'] ?? date('Y-m-d'));
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $contentDate);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $contentDate) {
    $contentDate = date('Y-m-d');
}

$emergencyVerse = [
    'text' => 'Be still, and know that I am God.',
    'reference' => 'Psalm 46:10',
    'source' => 'emergency_fallback',
] + dailybreath_reference_location('Psalm 46:10');
$fallbackVerse = dailybreath_recovery_verse_for_date($contentDate) ?: $emergencyVerse;
$fallbackDevotional = [
    'title' => 'Walk in Quiet Confidence',
    'excerpt' => 'Make room for stillness and remember that God is present before your next step.',
    'body' => 'Stillness is not empty time. It is a faithful pause where you remember that God is already present, already attentive, and already enough for the road in front of you. Begin today by slowing your pace before you solve everything. Let confidence grow from trust, not hurry.',
    'scripture' => 'Psalm 46:10',
    'minutes' => 5,
    'prayer' => 'Lord, quiet my heart and steady my thoughts. Help me move through today with trust, patience, and courage.',
    'practice' => 'Before your next task, take three slow breaths and name one thing you can entrust to God.',
];

$verse = $fallbackVerse;
$bundledDevotional = dailybreath_recovery_devotional_for_date($contentDate, false)
    ?: dailybreath_recovery_devotional_for_date($contentDate);
$devotional = $bundledDevotional ? [
    'title' => (string)$bundledDevotional['title'],
    'excerpt' => (string)$bundledDevotional['excerpt'],
    'body' => (string)$bundledDevotional['body'],
    'scripture' => (string)$bundledDevotional['scripture_reference'],
    'minutes' => (int)$bundledDevotional['duration_minutes'],
    'prayer' => (string)$bundledDevotional['prayer'],
    'practice' => (string)$bundledDevotional['practice'],
] : $fallbackDevotional;
$bundledChallenge = dailybreath_recovery_challenge_for_date($contentDate);
$challenge = $bundledChallenge ?: [
    'id' => 'weekly-faith-in-action',
    'title' => 'Faith in Action',
    'description' => 'Choose one quiet act of faith this week and make it concrete.',
    'scripture_reference' => 'James 2:17',
    'steps' => [
        'Choose when and where you will practice this challenge.',
        'Complete one intentional step each day.',
        'Record what changed and who you can encourage.',
    ],
    'target_count' => 7,
    'starts_on' => date('Y-m-d', strtotime('monday this week', strtotime($contentDate))),
    'ends_on' => date('Y-m-d', strtotime('sunday this week', strtotime($contentDate))),
];

try {
    $pdo = beyond_db();
    $verse = dailybreath_interfaith_verse_of_day($pdo, $tradition, $locale, $contentDate);

    try {
        $query = $pdo->prepare('SELECT id,title,excerpt,body,scripture_reference,duration_minutes FROM devotionals WHERE is_published=1 AND locale=? AND publish_date=? ORDER BY id DESC LIMIT 1');
        $query->execute([$locale, $contentDate]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $devotional = [
                'title' => trim((string)($row['title'] ?? $fallbackDevotional['title'])),
                'excerpt' => trim((string)($row['excerpt'] ?? $fallbackDevotional['excerpt'])),
                'body' => trim((string)($row['body'] ?? $fallbackDevotional['body'])),
                'scripture' => trim((string)($row['scripture_reference'] ?? $fallbackDevotional['scripture'])),
                'minutes' => max(1, (int)($row['duration_minutes'] ?? $fallbackDevotional['minutes'])),
                'prayer' => (string)$devotional['prayer'],
                'practice' => (string)$devotional['practice'],
            ];
        }
    } catch (Throwable $exception) {
        // Keep the bundled devotional when the database is unavailable.
    }

    try {
        $query = $pdo->prepare('SELECT id,slug,title,description,scripture_reference,starts_on,ends_on,target_count FROM weekly_challenges WHERE is_published=1 AND locale=? AND starts_on<=? AND ends_on>=? ORDER BY starts_on DESC,id DESC LIMIT 1');
        $query->execute([$locale, $contentDate, $contentDate]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $challenge = [
                'id' => trim((string)($row['slug'] ?? 'challenge-' . $row['id'])),
                'title' => trim((string)$row['title']),
                'description' => trim((string)$row['description']),
                'scripture_reference' => trim((string)($row['scripture_reference'] ?? '')),
                'steps' => [
                    'Choose when and where you will practice this challenge.',
                    'Complete one intentional step each day.',
                    'Record what changed and who you can encourage.',
                ],
                'target_count' => max(1, (int)$row['target_count']),
                'starts_on' => (string)$row['starts_on'],
                'ends_on' => (string)$row['ends_on'],
            ];
        }
    } catch (Throwable $exception) {
        // Keep the bundled challenge when the database is unavailable.
    }
} catch (Throwable $exception) {
    $verse = $fallbackVerse;
}

echo json_encode([
    'ok' => true,
    'date' => $contentDate,
    'tradition' => $tradition,
    'verse' => [
        'id' => 1,
        'text' => (string)($verse['text'] ?? $fallbackVerse['text']),
        'reference' => (string)($verse['reference'] ?? $fallbackVerse['reference']),
        'reflection' => 'Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today.',
        'reader_url' => dailybreath_scripture_url($verse, 'https://beyondimagination.co.technology'),
        'audio_url' => $tradition === 'bible' && !empty($verse['audio_file'])
            ? 'https://beyondimagination.co.technology/dailybreath/assets/audio/verses/' . rawurlencode(basename((string)$verse['audio_file']))
            : null,
    ],
    'devotional' => [
        'id' => 1,
        'title' => $devotional['title'],
        'excerpt' => $devotional['excerpt'],
        'body' => $devotional['body'],
        'scripture' => $devotional['scripture'],
        'minutes' => $devotional['minutes'],
        'prayer' => $devotional['prayer'],
        'practice' => $devotional['practice'],
    ],
    'challenge' => [
        'id' => (string)$challenge['id'],
        'title' => (string)$challenge['title'],
        'description' => (string)$challenge['description'],
        'scripture_reference' => (string)$challenge['scripture_reference'],
        'steps' => array_values(array_map('strval', (array)$challenge['steps'])),
        'target_count' => max(1, (int)$challenge['target_count']),
        'starts_on' => (string)$challenge['starts_on'],
        'ends_on' => (string)$challenge['ends_on'],
    ],
    'source' => (string)($verse['source'] ?? 'emergency_fallback'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
