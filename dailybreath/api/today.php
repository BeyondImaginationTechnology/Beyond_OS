<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../includes/verse-of-day.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=3600');

$locale = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['locale'] ?? 'en')) ?: 'en';
$fallbackVerse = [
    'text' => 'Be still, and know that I am God.',
    'reference' => 'Psalm 46:10',
    'source' => 'bundled_fallback',
] + dailybreath_reference_location('Psalm 46:10');
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
$bundledDevotional = dailybreath_recovery_devotional_for_date(date('Y-m-d'), false)
    ?: dailybreath_recovery_devotional_for_date(date('Y-m-d'));
$devotional = $bundledDevotional ? [
    'title' => (string)$bundledDevotional['title'],
    'excerpt' => (string)$bundledDevotional['excerpt'],
    'body' => (string)$bundledDevotional['body'],
    'scripture' => (string)$bundledDevotional['scripture_reference'],
    'minutes' => (int)$bundledDevotional['duration_minutes'],
    'prayer' => (string)$bundledDevotional['prayer'],
    'practice' => (string)$bundledDevotional['practice'],
] : $fallbackDevotional;

try {
    $pdo = beyond_db();
    $verse = dailybreath_verse_of_day($pdo, $locale);

    if (dailybreath_recovery_devotional_for_date(date('Y-m-d'), false) === null) try {
        $query = $pdo->prepare('SELECT title,excerpt,body,scripture_reference,duration_minutes,prayer,practice FROM devotionals WHERE is_published=1 AND locale=? AND publish_date<=? ORDER BY publish_date DESC,id DESC LIMIT 1');
        $query->execute([$locale, date('Y-m-d')]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $devotional = [
                'title' => trim((string)($row['title'] ?? $fallbackDevotional['title'])),
                'excerpt' => trim((string)($row['excerpt'] ?? $fallbackDevotional['excerpt'])),
                'body' => trim((string)($row['body'] ?? $fallbackDevotional['body'])),
                'scripture' => trim((string)($row['scripture_reference'] ?? $fallbackDevotional['scripture'])),
                'minutes' => max(1, (int)($row['duration_minutes'] ?? $fallbackDevotional['minutes'])),
                'prayer' => trim((string)($row['prayer'] ?? $fallbackDevotional['prayer'])),
                'practice' => trim((string)($row['practice'] ?? $fallbackDevotional['practice'])),
            ];
        }
    } catch (Throwable $exception) {
        $devotional = $fallbackDevotional;
    }
} catch (Throwable $exception) {
    $verse = $fallbackVerse;
    $devotional = $fallbackDevotional;
}

echo json_encode([
    'ok' => true,
    'date' => date('Y-m-d'),
    'verse' => [
        'id' => 1,
        'text' => (string)($verse['text'] ?? $fallbackVerse['text']),
        'reference' => (string)($verse['reference'] ?? $fallbackVerse['reference']),
        'reflection' => 'Begin slowly. Make room for quiet, notice your breath, and let the next faithful step be enough for today.',
        'audio_url' => !empty($verse['audio_file'])
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
    'source' => (string)($verse['source'] ?? 'bundled_fallback'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
