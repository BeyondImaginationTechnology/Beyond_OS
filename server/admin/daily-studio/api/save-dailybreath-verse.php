<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function dailybreath_save_response(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function dailybreath_save_slug(string $value): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return trim(strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $ascii)), '-');
}
function dailybreath_write_json(string $path, array $document): void {
    $encoded = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temporary = $path . '.tmp';
    if ($encoded === false || file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false || !rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('The recovery schedule could not be saved.');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') dailybreath_save_response(['ok'=>false,'error'=>'POST required.'], 405);
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)) {
    dailybreath_save_response(['ok'=>false,'error'=>'Reload the generator and try again.'], 419);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) dailybreath_save_response(['ok'=>false,'error'=>'Invalid request.'], 400);

$date = trim((string)($input['publish_date'] ?? ''));
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
$text = trim((string)($input['verse_text'] ?? ''));
$reference = trim((string)($input['scripture_reference'] ?? ''));
if (!$dateObject || $dateObject->format('Y-m-d') !== $date || $text === '' || mb_strlen($text) > 2000 || $reference === '') {
    dailybreath_save_response(['ok'=>false,'error'=>'Date, verse text, and reference are required.'], 422);
}

$root = dirname(__DIR__, 4);
$webPath = $root . '/dailybreath/data/daily-verses.json';
$appPath = $root . '/DailyBreathApple/Resources/daily-verses.json';
$document = json_decode((string)file_get_contents($webPath), true);
$entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
$existingIndex = null;
foreach ($entries as $index => $entry) if (($entry['schedule_date'] ?? null) === $date) $existingIndex = $index;
if ($existingIndex !== null && empty($input['confirm_overwrite'])) {
    dailybreath_save_response(['ok'=>false,'error'=>'A recovery verse is already scheduled for this date.','requires_confirmation'=>true], 409);
}
$referenceSlug = dailybreath_save_slug($reference);
foreach ($entries as $index => $entry) {
    if ($index !== $existingIndex && dailybreath_save_slug((string)($entry['reference'] ?? '')) === $referenceSlug) {
        dailybreath_save_response(['ok'=>false,'error'=>'That Bible reference is already present in the recovery library. Choose an unused verse.'], 409);
    }
}

$provider = strtolower(trim((string)($input['narration_provider'] ?? '')));
$voice = trim((string)($input['narration_voice'] ?? ''));
try {
    $narration = studio_narration_generate($text . ' ' . $reference . '.', 'en-US', $provider, $voice);
    $audio = (string)($narration['audio_content'] ?? '');
    if (strlen($audio) < 128) throw new RuntimeException('The narration provider returned invalid audio.');
    $audioName = 'verse-' . $referenceSlug . '.mp3';
    $audioDirectory = $root . '/dailybreath/assets/audio/verses';
    if (!is_dir($audioDirectory) && !mkdir($audioDirectory, 0775, true) && !is_dir($audioDirectory)) throw new RuntimeException('The verse audio folder could not be created.');
    if (file_put_contents($audioDirectory . '/' . $audioName, $audio, LOCK_EX) === false) throw new RuntimeException('The prerecorded verse audio could not be saved.');

    $maxId = 0;
    foreach ($entries as $entry) if (preg_match('/(\d+)$/', (string)($entry['id'] ?? ''), $match)) $maxId = max($maxId, (int)$match[1]);
    $entry = array_merge($existingIndex === null ? [] : (array)$entries[$existingIndex], [
        'id'=>$existingIndex === null ? 'recovery-' . str_pad((string)($maxId + 1), 3, '0', STR_PAD_LEFT) : (string)$entries[$existingIndex]['id'],
        'text'=>$text,
        'reference'=>$reference,
        'theme'=>trim((string)($input['theme'] ?? 'spiritual support')) ?: 'spiritual support',
        'schedule_date'=>$date,
        'audio_file'=>$audioName,
        'translation'=>'WEB',
        'generated_batch'=>'studio-published',
        'generator'=>['version'=>'2.0.0','saved_by'=>(int)($_SESSION['user_id'] ?? 0),'saved_at'=>date(DATE_ATOM)],
    ]);
    if ($existingIndex === null) $entries[] = $entry; else $entries[$existingIndex] = $entry;
    usort($entries, static fn(array $left, array $right): int => strcmp((string)($left['id'] ?? ''), (string)($right['id'] ?? '')));
    $document['entries'] = $entries;
    $document['entry_count'] = count($entries);
    $scheduledDates = array_values(array_filter(array_map(static fn(array $item): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($item['schedule_date'] ?? '')) ? (string)$item['schedule_date'] : null, $entries)));
    sort($scheduledDates);
    $document['scheduled_batch']['starts_on'] = $scheduledDates[0] ?? $date;
    $document['scheduled_batch']['ends_on'] = $scheduledDates ? (string)end($scheduledDates) : $date;
    $document['scheduled_batch']['entry_count'] = count($scheduledDates);
    dailybreath_write_json($webPath, $document);
    if (is_file($appPath) && is_writable($appPath)) dailybreath_write_json($appPath, $document);
    dailybreath_save_response([
        'ok'=>true,'id'=>$entry['id'],'publish_date'=>$date,'updated'=>$existingIndex!==null,
        'audio_generated'=>true,'audio_url'=>'/dailybreath/assets/audio/verses/' . rawurlencode($audioName),
        'message'=>$existingIndex!==null?'The scheduled recovery verse and audio were replaced.':'The recovery verse was published with prerecorded audio.',
    ]);
} catch (Throwable $error) {
    error_log('Daily Breath Studio publish: ' . $error->getMessage());
    dailybreath_save_response(['ok'=>false,'error'=>$error->getMessage()], 503);
}
