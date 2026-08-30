<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=600');

$date = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
$dbPath = dirname(__DIR__, 2) . '/var/daily-studio.sqlite';
$items = [];

if (is_file($dbPath)) {
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $query = $db->prepare("SELECT content_json FROM events WHERE channel_key='daily_space' AND content_type='daily_horoscope' AND status='published' AND date(scheduled_at)=? ORDER BY id ASC");
        $query->execute([$date]);
        foreach ($query->fetchAll() as $row) {
            $content = json_decode((string)$row['content_json'], true);
            if (is_array($content) && !empty($content['sign'])) {
                $items[] = $content;
            }
        }
    } catch (Throwable $error) {
        // The public app uses its built-in reading set when Daily Studio is unavailable.
    }
}

echo json_encode(['ok'=>true, 'date'=>$date, 'items'=>$items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
