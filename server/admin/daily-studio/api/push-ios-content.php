<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/ios-content-override.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function iosPushResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') iosPushResponse(['ok'=>false,'error'=>'POST required.'], 405);
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)) {
    iosPushResponse(['ok'=>false,'error'=>'Reload the generator and try again.'], 419);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input) || ($input['product'] ?? '') !== 'beyond-french') {
    iosPushResponse(['ok'=>false,'error'=>'Invalid iOS push request.'], 422);
}
$date = trim((string)($input['publish_date'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) iosPushResponse(['ok'=>false,'error'=>'Choose a valid lesson date.'], 422);
$lessons = json_decode((string)file_get_contents(dirname(__DIR__, 4) . '/beyond-french/data/lessons.json'), true);
$lesson = null;
foreach (is_array($lessons) ? $lessons : [] as $candidate) {
    if (($candidate['date'] ?? '') === $date) $lesson = $candidate;
}
if (!is_array($lesson)) iosPushResponse(['ok'=>false,'error'=>'Save the French lesson before pushing it to iOS.'], 404);

try {
    beyond_ios_override_write('beyond-french', $lesson);
    iosPushResponse(['ok'=>true,'message'=>'French lesson pushed to the iOS feed now. The normal daily schedule resumes at midnight Pacific.']);
} catch (Throwable $error) {
    error_log('iOS French push failed: ' . $error->getMessage());
    iosPushResponse(['ok'=>false,'error'=>'The French lesson could not be pushed to iOS.'], 500);
}
