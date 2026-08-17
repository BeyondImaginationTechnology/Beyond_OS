<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

function dailybreath_schedule_response(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function dailybreath_schedule_reference_key(string $reference): string {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $reference);
    return trim(strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $ascii === false ? $reference : $ascii)), '-');
}

$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$parsed || $parsed->format('Y-m-d') !== $date) {
    dailybreath_schedule_response(['ok' => false, 'error' => 'Choose a valid schedule date.'], 422);
}

$root = dirname(__DIR__, 4);
$libraryPath = $root . '/dailybreath/data/daily-verses.json';
$document = json_decode((string)file_get_contents($libraryPath), true);
$entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
if (!$entries) dailybreath_schedule_response(['ok' => false, 'error' => 'The Daily Breath recovery schedule is unavailable.'], 500);

if (($_GET['draft'] ?? '') === '1') {
    $used = [];
    foreach ($entries as $entry) $used[dailybreath_schedule_reference_key((string)($entry['reference'] ?? ''))] = true;
    $bookNames = [
        'GEN'=>'Genesis','EXO'=>'Exodus','DEU'=>'Deuteronomy','JOS'=>'Joshua','1SA'=>'1 Samuel','2SA'=>'2 Samuel','1KI'=>'1 Kings','2KI'=>'2 Kings',
        '2CH'=>'2 Chronicles','NEH'=>'Nehemiah','JOB'=>'Job','PSA'=>'Psalm','PRO'=>'Proverbs','ECC'=>'Ecclesiastes','ISA'=>'Isaiah','JER'=>'Jeremiah',
        'LAM'=>'Lamentations','EZE'=>'Ezekiel','DAN'=>'Daniel','HOS'=>'Hosea','JOE'=>'Joel','MIC'=>'Micah','NAH'=>'Nahum','ZEP'=>'Zephaniah','ZEC'=>'Zechariah',
        'MAT'=>'Matthew','MAR'=>'Mark','LUK'=>'Luke','JOH'=>'John','ACT'=>'Acts','ROM'=>'Romans','1CO'=>'1 Corinthians','2CO'=>'2 Corinthians',
        'GAL'=>'Galatians','EPH'=>'Ephesians','PHI'=>'Philippians','COL'=>'Colossians','1TH'=>'1 Thessalonians','2TH'=>'2 Thessalonians',
        '1TI'=>'1 Timothy','2TI'=>'2 Timothy','TIT'=>'Titus','HEB'=>'Hebrews','JAM'=>'James','1PE'=>'1 Peter','2PE'=>'2 Peter','1JO'=>'1 John','REV'=>'Revelation',
    ];
    $candidates = [];
    $biblePath = $root . '/dailybreath/data/engwebp_vpl.txt';
    foreach (file($biblePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (!preg_match('/^([1-3]?[A-Z]{2,3})\s+(\d+):(\d+)\s+(.+)$/u', $line, $match) || !isset($bookNames[$match[1]])) continue;
        $text = trim($match[4]);
        if (!preg_match('/\b(peace|hope|strength|strong|help|refuge|deliver|free|freedom|grace|mercy|faith|trust|fear|afraid|courage|endure|persever|tempt|self-control|renew|rest|comfort|heal|restore|wait)\w*\b/i', $text)) continue;
        $reference = $bookNames[$match[1]] . ' ' . (int)$match[2] . ':' . (int)$match[3];
        if (isset($used[dailybreath_schedule_reference_key($reference)])) continue;
        $candidates[] = ['id'=>$match[1].' '.$match[2].':'.$match[3], 'verse'=>$text, 'reference'=>$reference, 'translation'=>'WEB'];
    }
    if (!$candidates) dailybreath_schedule_response(['ok'=>false,'error'=>'No unused recovery-focused verses remain.'], 404);
    $item = $candidates[random_int(0, count($candidates) - 1)] + ['footer'=>'BREATHE THROUGH THE CRAVING. GOD IS WITH YOU.'];
    dailybreath_schedule_response(['ok'=>true,'item'=>$item,'remaining'=>count($candidates)]);
}

$scheduled = [];
foreach ($entries as $entry) {
    $entryDate = trim((string)($entry['schedule_date'] ?? ''));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) $scheduled[$entryDate] = $entry;
}
ksort($scheduled);
$dates = array_keys($scheduled);
$previous = null;
$next = null;
foreach ($dates as $scheduledDate) {
    if ($scheduledDate < $date) $previous = $scheduledDate;
    if ($scheduledDate > $date) { $next = $scheduledDate; break; }
}
$lastDate = $dates ? (string)end($dates) : date('Y-m-d');
$nextAvailable = (new DateTimeImmutable($lastDate))->modify('+1 day')->format('Y-m-d');
$item = $scheduled[$date] ?? null;
if ($item) {
    $audioFile = basename((string)($item['audio_file'] ?? ''));
    $item['verse'] = (string)($item['text'] ?? '');
    $item['audio_url'] = $audioFile !== '' ? '/dailybreath/assets/audio/verses/' . rawurlencode($audioFile) : '';
    $item['status'] = 'scheduled';
}

dailybreath_schedule_response([
    'ok'=>true,
    'date'=>$date,
    'item'=>$item,
    'navigation'=>[
        'previous'=>$previous,
        'today'=>date('Y-m-d'),
        'next'=>$next,
        'first'=>$dates[0] ?? null,
        'last'=>$dates ? (string)end($dates) : null,
        'next_available'=>$nextAvailable,
    ],
    'counts'=>[
        'library'=>count($entries),
        'scheduled'=>count($scheduled),
        'with_audio'=>count(array_filter($entries, static fn(array $entry): bool => trim((string)($entry['audio_file'] ?? '')) !== '')),
    ],
]);
