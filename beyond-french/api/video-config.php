<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=3600');
header('X-Content-Type-Options: nosniff');

$lessonId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$lesson = $lessonId ? lesson_by_id((int)$lessonId) : todays_lesson();

if (!$lesson) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No daily lesson is available.']);
    exit;
}

$audioMap = lesson_audio_map((int)$lesson['id']);
$audioFile = (string)($audioMap['en-US'] ?? '');

if ($audioFile !== '' && !preg_match('#^https?://#i', $audioFile)) {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
        $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
        $audioFile = ($isHttps ? 'https://' : 'http://') . $host . '/' . ltrim($audioFile, '/');
    }
}

$english = trim((string)($lesson['english'] ?? ''));
$french = trim((string)($lesson['french'] ?? ''));
$kreyol = trim((string)($lesson['kreyol'] ?? ''));
$spanish = trim((string)($lesson['spanish'] ?? ''));
$patois = trim((string)($lesson['patois'] ?? ''));

echo json_encode([
    'ok' => true,
    'video' => [
        'lessonId' => (int)$lesson['id'],
        'english' => $english,
        'french' => $french,
        'kreyol' => $kreyol,
        'spanish' => $spanish,
        'patois' => $patois,
        'category' => (string)($lesson['category'] ?? 'Daily'),
        'audioFile' => $audioFile,
        'narrationScript' => "Today's phrase is: {$english} In French: {$french} In Haitian Creole: {$kreyol} In Spanish: {$spanish} In Jamaican Patois: {$patois} Practice it today, and go beyond French.",
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
