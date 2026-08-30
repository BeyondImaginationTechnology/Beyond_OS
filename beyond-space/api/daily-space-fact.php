<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=600');
$date = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
$dbPath = dirname(__DIR__, 2) . '/var/daily-studio.sqlite';
$fact = null;
if (is_file($dbPath)) {
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $query = $db->prepare("SELECT content_json FROM events WHERE channel_key='daily_space' AND content_type='daily_space_fact' AND status='published' AND date(scheduled_at)=? ORDER BY id DESC LIMIT 1");
        $query->execute([$date]);
        $content = $query->fetchColumn();
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (is_array($decoded)) $fact = $decoded;
    } catch (Throwable $error) {}
}
echo json_encode(['ok'=>true, 'date'=>$date, 'fact'=>$fact], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
