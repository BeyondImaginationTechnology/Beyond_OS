<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$token = trim((string)beyond_config('dailybreath.local_worker_token', ''));
$given = preg_replace('/^Bearer\s+/i', '', (string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $token === '' || $given === '' || !hash_equals($token, $given)) {
    http_response_code(401); echo json_encode(['ok' => false]); exit;
}

$rawUpload = str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/octet-stream');
$metadata = [];
if ($rawUpload) {
    $encoded = (string)($_SERVER['HTTP_X_DAILYBREATH_METADATA'] ?? '');
    $decoded = base64_decode($encoded, true);
    $metadata = is_string($decoded) ? (json_decode($decoded, true) ?: []) : [];
}
$date = (string)($rawUpload ? ($metadata['date'] ?? '') : ($_POST['date'] ?? ''));
$tradition = (string)($rawUpload ? ($metadata['tradition'] ?? '') : ($_POST['tradition'] ?? ''));
$reference = trim((string)($rawUpload ? ($metadata['reference'] ?? '') : ($_POST['reference'] ?? '')));
$title = trim((string)($rawUpload ? ($metadata['title'] ?? '') : ($_POST['title'] ?? '')));
$voiceover = trim((string)($rawUpload ? ($metadata['voiceover'] ?? '') : ($_POST['voiceover'] ?? '')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($tradition, ['bible','torah','quran'], true) || $reference === '' || $title === '') {
    http_response_code(422); echo json_encode(['ok' => false, 'error' => 'Invalid episode metadata']); exit;
}

$root = dirname(__DIR__, 2);
$directory = $root . '/dailybreath/assets/videos/daily-breath-tv';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    http_response_code(500); echo json_encode(['ok' => false, 'error' => 'Video library unavailable']); exit;
}
$name = "$date-$tradition-verse-of-the-day.mp4";
$target = "$directory/$name";
$temp = "$target.part";

if ($rawUpload) {
    $body = file_get_contents('php://input');
    if (!is_string($body) || strlen($body) < 1024 || strlen($body) > 104857600 || file_put_contents($temp, $body, LOCK_EX) === false || !rename($temp, $target)) {
        @unlink($temp); http_response_code(500); echo json_encode(['ok' => false, 'error' => 'Video could not be stored']); exit;
    }
} elseif (!empty($_FILES['video']['tmp_name']) && is_uploaded_file($_FILES['video']['tmp_name']) && $_FILES['video']['size'] >= 1024 && $_FILES['video']['size'] <= 104857600 && move_uploaded_file($_FILES['video']['tmp_name'], $target)) {
    // Retain multipart support for trusted browser tooling.
} else {
    http_response_code(422); echo json_encode(['ok' => false, 'error' => 'Video upload missing']); exit;
}
@chmod($target, 0644);

$manifestFile = "$directory/rotation.json";
$manifest = json_decode((string)@file_get_contents($manifestFile), true) ?: [];
$id = "daily-$tradition-verse-$date";
$episodes = array_values(array_filter((array)($manifest['episodes'] ?? []), fn($episode) => is_array($episode) && ($episode['id'] ?? '') !== $id));
$programming = ['bible' => 'christian', 'torah' => 'judaism', 'quran' => 'muslim'][$tradition];
$airings = match ($tradition) {
    // The shared morning reading is followed by a separate tradition-specific block.
    'bible' => [['start' => '06:00', 'end' => '06:15'], ['start' => '07:00', 'end' => '08:00']],
    'torah' => [['start' => '06:00', 'end' => '06:15'], ['start' => '08:00', 'end' => '09:00']],
    'quran' => [['start' => '06:00', 'end' => '06:15'], ['start' => '12:00', 'end' => '13:00']],
};
array_unshift($episodes, ['id'=>$id,'title'=>$title,'subtitle'=>"Daily Breath · $date",'show'=>'Daily Breath','tradition'=>$tradition,'programming'=>$programming,'airings'=>$airings,'timezone'=>'America/Vancouver','video_url'=>'/dailybreath/assets/videos/daily-breath-tv/'.$name,'duration_seconds'=>57,'published_at'=>gmdate(DATE_ATOM),'voiceover'=>$voiceover!==''?$voiceover:'ElevenLabs narration']);
file_put_contents($manifestFile, json_encode(['channel'=>'Daily Breath originals','updated_at'=>gmdate(DATE_ATOM),'episodes'=>array_slice($episodes,0,30)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL, LOCK_EX);
echo json_encode(['ok' => true]);
