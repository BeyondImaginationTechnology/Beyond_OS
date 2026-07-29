<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=3600');
$lesson = todays_lesson();
if (!$lesson) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No daily lesson is available.']);
    exit;
}
echo json_encode([
    'ok' => true,
    'date' => (string)($lesson['date'] ?? date('Y-m-d')),
    'lesson' => $lesson,
    'access' => [
        'dictionary' => 'free',
        'daily_lesson' => 'free',
        'academy_preview' => ['module' => 'greetings', 'lesson' => 1],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
