<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/my-list.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sign in to save to My List.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}
$token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($token === '' || !hash_equals(beyond_tv_my_list_token(), $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Reload the page and try again.']);
    exit;
}
$input = json_decode((string)file_get_contents('php://input'), true);
$type = (string)($input['type'] ?? '');
$slug = (string)($input['slug'] ?? '');
$saved = $input['saved'] ?? null;
if (!in_array($type, ['channel', 'title'], true) || !preg_match('/^[a-z0-9-]{1,128}$/D', $slug) || !is_bool($saved)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid list item.']);
    exit;
}
$file = __DIR__ . '/../data/' . ($type === 'channel' ? 'channels.json' : 'catalog.json');
$items = json_decode((string)@file_get_contents($file), true) ?: [];
$valid = false;
foreach ($items as $item) {
    if (($item['slug'] ?? '') === $slug) { $valid = true; break; }
}
if (!$valid) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'This item is no longer available.']);
    exit;
}
try {
    $pdo = beyond_tv_my_list_db();
    if ($saved) {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO tv_my_list (user_id, item_type, item_slug) VALUES (?, ?, ?)'
            : 'INSERT IGNORE INTO tv_my_list (user_id, item_type, item_slug) VALUES (?, ?, ?)';
        $pdo->prepare($sql)->execute([$userId, $type, $slug]);
    } else {
        $pdo->prepare('DELETE FROM tv_my_list WHERE user_id = ? AND item_type = ? AND item_slug = ?')->execute([$userId, $type, $slug]);
    }
    echo json_encode(['ok' => true, 'saved' => beyond_tv_my_list_has($userId, $type, $slug)]);
} catch (Throwable $error) {
    error_log('Beyond TV My List unavailable: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'My List is temporarily unavailable.']);
}
