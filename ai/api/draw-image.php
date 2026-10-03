<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/draw-images.php';
require_once __DIR__ . '/../../beyond-id/includes/mobile-auth.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; sandbox");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$authorization = beyond_mobile_authorization_header();
if ($authorization !== '') {
    try {
        $claims = beyond_mobile_verify_token(beyond_mobile_bearer_token(), 'jaguar-ios', beyond_db());
        beyond_mobile_require_scope($claims, 'profile:read');
        $userId = (int)$claims['user_id'];
    } catch (Throwable $exception) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Jaguar", error="invalid_token"');
        exit;
    }
}

$receiptId = is_string($_GET['receipt'] ?? null) ? strtolower(trim($_GET['receipt'])) : '';
if ($userId < 1 || !preg_match('/^[a-f0-9]{16}$/', $receiptId)) {
    http_response_code($userId < 1 ? 401 : 404);
    exit;
}

try {
    $image = jaguar_draw_image_load(beyond_db(), $userId, $receiptId);
} catch (Throwable $exception) {
    error_log('Jaguar Draw image retrieval failed.');
    $image = null;
}
if ($image === null) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . strlen($image['bytes']));
echo $image['bytes'];
