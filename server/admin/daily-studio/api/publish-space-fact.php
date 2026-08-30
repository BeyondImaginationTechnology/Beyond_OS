<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function publishFactJson(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!Auth::check()) publishFactJson(['ok'=>false, 'error'=>'Administrator access required.'], 403);
    $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)) {
        publishFactJson(['ok'=>false, 'error'=>'Reload the generator and try again.'], 419);
    }
    $fact = json_decode((string)($_POST['fact'] ?? ''), true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($fact) || trim((string)($fact['world'] ?? '')) === '' || trim((string)($fact['title'] ?? '')) === '') {
        publishFactJson(['ok'=>false, 'error'=>'A subject and headline are required.'], 422);
    }
    $number = max(1, min(55, (int)($fact['number'] ?? 1)));
    $date = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    $root = dirname(__DIR__, 4);
    $assetDir = $root . '/beyond-space/assets/img/daily-facts/' . $date;
    if (!is_dir($assetDir) && !mkdir($assetDir, 0755, true) && !is_dir($assetDir)) throw new RuntimeException('Could not create the daily fact asset folder.');
    $assets = [];
    foreach ((array)($_FILES['assets'] ?? []) as $key => $values) {
        if (!is_array($values) || $key !== 'name') continue;
    }
    $files = $_FILES['assets'] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) publishFactJson(['ok'=>false, 'error'=>'Upload at least one image asset.'], 422);
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $tmp = (string)($files['tmp_name'][$index] ?? '');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $extensions = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
        if (!isset($extensions[$mime])) continue;
        $safe = sprintf('%02d-%s-%d.%s', $number, preg_replace('/[^a-z0-9-]+/i', '-', strtolower((string)$fact['world'])) ?: 'space-fact', $index + 1, $extensions[$mime]);
        if (!move_uploaded_file($tmp, $assetDir . '/' . $safe)) throw new RuntimeException('An uploaded asset could not be stored.');
        $assets[] = '/beyond-space/assets/img/daily-facts/' . $date . '/' . $safe;
    }
    if (!$assets) publishFactJson(['ok'=>false, 'error'=>'No supported PNG, JPG, or WebP asset was uploaded.'], 422);
    $fact['number'] = $number;
    $fact['publish_date'] = $date;
    $fact['asset_url'] = $assets[0];
    $fact['assets'] = $assets;
    $fact['source'] = 'Meta AI asset via Beyond Studio';
    $db = DailyStudio::db();
    $title = 'Daily Space Fact: ' . (string)$fact['world'];
    $scheduled = $date . ' 08:00:00';
    $json = json_encode($fact, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $select = $db->prepare("SELECT id FROM events WHERE channel_key='daily_space' AND content_type='daily_space_fact' AND title=? AND scheduled_at=? ORDER BY id DESC LIMIT 1");
    $select->execute([$title, $scheduled]);
    $id = (int)$select->fetchColumn();
    if ($id > 0) {
        $statement = $db->prepare("UPDATE events SET content_json=?, timezone='America/Vancouver', status='published', requires_approval=0, published_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $statement->execute([$json, $id]);
    } else {
        $statement = $db->prepare("INSERT INTO events(channel_key,title,content_type,content_json,scheduled_at,timezone,status,requires_approval,created_by,published_at,updated_at) VALUES('daily_space',?,?,?,?,?,'published',0,?,?,CURRENT_TIMESTAMP)");
        $statement->execute([$title, 'daily_space_fact', $json, $scheduled, 'America/Vancouver', (int)($_SESSION['user_id'] ?? 0), date('Y-m-d H:i:s')]);
    }
    publishFactJson(['ok'=>true, 'number'=>$number, 'publish_date'=>$date, 'asset_url'=>$assets[0], 'assets'=>$assets]);
} catch (Throwable $error) {
    publishFactJson(['ok'=>false, 'error'=>$error->getMessage()], 500);
}
