<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function frenchSaveResponse(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    frenchSaveResponse(['ok' => false, 'error' => 'POST required.'], 405);
}

$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (
    empty($_SESSION['verse_generator_csrf'])
    || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)
) {
    frenchSaveResponse(['ok' => false, 'error' => 'Reload the generator and try again.'], 419);
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    frenchSaveResponse(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$date = trim((string)($input['publish_date'] ?? ''));
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
    frenchSaveResponse(['ok' => false, 'error' => 'Choose a valid publication date.'], 422);
}

$fieldLimits = [
    'english' => 180,
    'meaning' => 500,
    'french' => 220,
    'french_pronunciation' => 220,
    'patois' => 220,
    'patois_pronunciation' => 220,
    'kreyol' => 220,
    'kreyol_pronunciation' => 220,
    'spanish' => 220,
    'spanish_pronunciation' => 220,
    'culture_note' => 600,
];
$values = [];
foreach ($fieldLimits as $field => $limit) {
    $value = trim((string)($input[$field] ?? ''));
    if ($value === '' || mb_strlen($value) > $limit) {
        frenchSaveResponse(['ok' => false, 'error' => 'Complete every phrase field before saving.'], 422);
    }
    $values[$field] = $value;
}

$root = dirname(__DIR__, 4);
$lessonsFile = $root . '/beyond-french/data/lessons.json';
$lessons = json_decode((string)file_get_contents($lessonsFile), true);
if (!is_array($lessons)) {
    frenchSaveResponse(['ok' => false, 'error' => 'The French lesson library could not be read.'], 500);
}

$existingIndex = null;
$maxId = 0;
foreach ($lessons as $index => $lesson) {
    $maxId = max($maxId, (int)($lesson['id'] ?? 0));
    if (($lesson['date'] ?? '') === $date) {
        $existingIndex = $index;
    }
}

$id = $existingIndex === null ? $maxId + 1 : (int)($lessons[$existingIndex]['id'] ?? $maxId + 1);
$lesson = [
    'id' => $id,
    'date' => $date,
    'category' => 'Daily Phrase',
    'module' => (string)($input['module'] ?? 'greetings'),
    ...$values,
    'challenge' => 'How would you say “' . $values['english'] . '” in French?',
    'answer' => $values['french'],
    'generator' => [
        'version' => '2.0.0',
        'saved_by' => (int)($_SESSION['user_id'] ?? 0),
        'saved_at' => date(DATE_ATOM),
        'export_language' => (string)($input['export_language'] ?? 'en'),
        'narrator_locale' => (string)($input['narrator_locale'] ?? 'fr-FR'),
    ],
];

if ($existingIndex === null) {
    $lessons[] = $lesson;
} else {
    $lessons[$existingIndex] = $lesson;
}

$encoded = json_encode($lessons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$tempFile = $lessonsFile . '.tmp';
if (
    $encoded === false
    || file_put_contents($tempFile, $encoded . PHP_EOL, LOCK_EX) === false
    || !rename($tempFile, $lessonsFile)
) {
    @unlink($tempFile);
    frenchSaveResponse(['ok' => false, 'error' => 'The daily phrase could not be saved.'], 500);
}

frenchSaveResponse([
    'ok' => true,
    'id' => $id,
    'publish_date' => $date,
    'updated' => $existingIndex !== null,
    'url' => '/beyond-french/',
    'message' => $date === date('Y-m-d')
        ? 'Today’s phrase is now live in Beyond French.'
        : 'The phrase is saved for ' . $date . '.',
]);
