<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30, stale-while-revalidate=120');

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$channels = json_decode((string)file_get_contents(dirname(__DIR__) . '/data/channels.json'), true) ?: [];
$schedules = json_decode((string)file_get_contents(dirname(__DIR__) . '/data/channel-schedules.json'), true) ?: [];
$channel = null;
foreach ($channels as $candidate) {
    if (($candidate['slug'] ?? '') === $slug) { $channel = $candidate; break; }
}
if (!$channel) {
    http_response_code(404);
    echo json_encode(['ok'=>false, 'error'=>'Unknown channel']);
    exit;
}

$timezone = new DateTimeZone('America/Vancouver');
$now = new DateTimeImmutable('now', $timezone);
$hour = (int)$now->format('G');
$schedule = is_array($schedules[$slug] ?? null) ? $schedules[$slug] : [];
if (in_array($slug, ['beyond-after-dark', 'beyond-comedy', 'beyond-family'], true)) {
    require_once dirname(__DIR__) . '/includes/eight-channel-guide.php';
    if ($slug === 'beyond-after-dark') {
        $schedule = beyond_tv_after_dark_hourly_rows();
    } else {
        $dynamicSchedule = beyond_tv_catalog_hourly_rows($slug);
        if ($dynamicSchedule) $schedule = $dynamicSchedule;
    }
}
$currentIndex = 0;
foreach ($schedule as $index => $block) {
    $start = (int)($block['start'] ?? 0);
    $end = (int)($block['end'] ?? 24);
    $matches = $end > $start ? ($hour >= $start && $hour < $end) : ($hour >= $start || $hour < $end);
    if ($matches) { $currentIndex = (int)$index; break; }
}
$fallbackCurrent = ['icon'=>$channel['icon'] ?? '▶', 'title'=>$channel['now'] ?? 'Live now', 'lineup'=>$channel['now'] ?? 'Live now'];
$fallbackNext = ['title'=>$channel['up_next'] ?? 'Next scheduled program'];
$current = $schedule[$currentIndex] ?? $fallbackCurrent;
$next = $schedule ? ($schedule[($currentIndex + 1) % count($schedule)] ?? $fallbackNext) : $fallbackNext;
$embedUrl = null;
if (($channel['source_type'] ?? '') === 'youtube_playlist_embed' && !empty($channel['youtube_playlist_id'])) {
    $query = [
        'list' => (string)$channel['youtube_playlist_id'],
        'autoplay' => 1,
        'mute' => 1,
        'controls' => 1,
        'rel' => 0,
        'playsinline' => 1,
        'enablejsapi' => 1,
    ];
    $embedUrl = 'https://www.youtube-nocookie.com/embed/videoseries?' . http_build_query($query);
}
if ($embedUrl === null && ($channel['source_type'] ?? '') === 'youtube_embed' && !empty($channel['youtube_id'])) {
    $youtubeId = (string)$channel['youtube_id'];
    $playlistId = trim((string)($channel['youtube_playlist_id'] ?? ''));
    $query = [
        'autoplay'=>!empty($channel['youtube_autoplay']) ? 1 : 0,
        'mute'=>!empty($channel['youtube_muted']) ? 1 : 0,
        'controls'=>1,
        'rel'=>0,
        'playsinline'=>1,
        'enablejsapi'=>1,
    ];
    if ($playlistId !== '') $query['list'] = $playlistId;
    $embedUrl = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($youtubeId)
        . '?' . http_build_query($query);
}

echo json_encode([
    'ok'=>true,
    'mode'=>'scheduled-live',
    'timezone'=>'America/Vancouver',
    'server_time'=>$now->format(DATE_ATOM),
    'channel'=>['slug'=>$slug, 'name'=>$channel['name'] ?? $slug],
    'state'=>array_filter([
        'current'=>$current,
        'next'=>$next,
        'embed_url'=>$embedUrl,
    ], static fn($value): bool => $value !== null),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
