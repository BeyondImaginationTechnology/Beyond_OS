<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

function french_schedule_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$parsed || $parsed->format('Y-m-d') !== $date) {
    french_schedule_response(['ok' => false, 'error' => 'Choose a valid schedule date.'], 422);
}

$root = dirname(__DIR__, 4);
$lessons = json_decode((string)file_get_contents($root . '/beyond-french/data/lessons.json'), true);
if (!is_array($lessons)) {
    french_schedule_response(['ok' => false, 'error' => 'The French lesson schedule is unavailable.'], 500);
}

$scheduled = [];
foreach ($lessons as $lesson) {
    $lessonDate = trim((string)($lesson['date'] ?? ''));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $lessonDate)) {
        $scheduled[$lessonDate] = $lesson;
    }
}
ksort($scheduled);
$dates = array_keys($scheduled);
$previous = null;
$next = null;
foreach ($dates as $scheduledDate) {
    if ($scheduledDate < $date) $previous = $scheduledDate;
    if ($scheduledDate > $date) {
        $next = $scheduledDate;
        break;
    }
}
$lastDate = $dates ? (string)end($dates) : date('Y-m-d');
$nextAvailable = (new DateTimeImmutable($lastDate))->modify('+1 day')->format('Y-m-d');

french_schedule_response([
    'ok' => true,
    'date' => $date,
    'item' => $scheduled[$date] ?? null,
    'navigation' => [
        'previous' => $previous,
        'today' => date('Y-m-d'),
        'next' => $next,
        'first' => $dates[0] ?? null,
        'last' => $dates ? (string)end($dates) : null,
        'next_available' => $nextAvailable,
    ],
    'counts' => [
        'scheduled' => count($scheduled),
        'with_audio' => count(array_filter($scheduled, static fn(array $lesson): bool => trim((string)($lesson['audio_url'] ?? '')) !== '')),
    ],
]);
