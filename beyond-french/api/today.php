<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/ios-content-override.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
$manualPush = beyond_ios_override_read('beyond-french');
$lesson = is_array($manualPush['content'] ?? null) ? $manualPush['content'] : todays_lesson();
if (!$lesson) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No daily lesson is available.']);
    exit;
}
echo json_encode([
    'ok' => true,
    'date' => (string)($lesson['date'] ?? date('Y-m-d')),
    'lesson' => $lesson,
    'delivery' => $manualPush ? 'manual_ios_push' : 'daily_schedule',
    'access' => [
        'dictionary' => 'free',
        'daily_lesson' => 'free',
        'academy_preview' => ['module' => 'greetings', 'lesson' => 1],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
