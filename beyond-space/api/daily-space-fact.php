<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=600');
$date = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$factsPath = dirname(__DIR__) . '/data/daily-space-facts.json';
$factBank = json_decode((string)@file_get_contents($factsPath), true);
$factBank = is_array($factBank) ? array_values(array_filter($factBank, 'is_array')) : [];
$fallbackFact = null;
if ($factBank) {
    $epoch = new DateTimeImmutable('2026-08-24', new DateTimeZone('America/Vancouver'));
    $today = new DateTimeImmutable($date, new DateTimeZone('America/Vancouver'));
    $offset = (int)$epoch->diff($today)->format('%r%a');
    $fallbackFact = $factBank[(($offset % count($factBank)) + count($factBank)) % count($factBank)];
}
$dbPath = beyond_private_file('db/daily-studio.sqlite', 'daily-studio.sqlite');
$fact = $fallbackFact;
if (is_file($dbPath)) {
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $query = $db->prepare("SELECT content_json FROM events WHERE channel_key='daily_space' AND content_type='daily_space_fact' AND status='published' AND date(scheduled_at)=? ORDER BY id DESC LIMIT 1");
        $query->execute([$date]);
        $content = $query->fetchColumn();
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (is_array($decoded)) {
            $number = (int)($decoded['number'] ?? 0);
            $base = null;
            foreach ($factBank as $candidate) {
                if ((int)($candidate['number'] ?? 0) === $number) { $base = $candidate; break; }
            }
            $fact = array_merge($base ?? [], $decoded);
        }
    } catch (Throwable $error) {}
}
$academy = is_array($fact['academy'] ?? null) ? $fact['academy'] : [];
$academy = array_merge(['age'=>'cosmic-explorer','module'=>'solar-system-planetary-science','lesson'=>1], $academy);
$fact['academy'] = $academy;
$query = http_build_query([
    'view' => 'lesson',
    'age' => (string)$academy['age'],
    'module' => (string)$academy['module'],
    'lesson' => max(1, (int)$academy['lesson']),
]);
$fact['academy_url'] = 'https://beyondimagination.co.technology/beyond-space/academy.php?' . $query;
$fact['distribution'] = [
    'space_tv_url' => 'https://beyondimagination.co.technology/beyond-tv/channel.php?slug=space-tv',
    'youtube_url' => (string)($fact['youtube_url'] ?? $fact['video_url'] ?? 'https://www.youtube.com/playlist?list=PLXBcsPKqNstB10447aKbDnkPEJdTV9sj-'),
];
$fact['content_date'] = $date;
echo json_encode(['ok'=>true, 'date'=>$date, 'fact'=>$fact], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
