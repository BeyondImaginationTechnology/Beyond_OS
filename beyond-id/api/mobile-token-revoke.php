<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed.']); exit; }
try {
    $token = beyond_mobile_bearer_token();
    $claims = beyond_mobile_verify_token($token, null, $pdo);
    $pdo->prepare('UPDATE mobile_access_tokens SET revoked_at=? WHERE jti=? AND user_id=? AND revoked_at IS NULL')->execute([date('Y-m-d H:i:s'), $claims['jti'], $claims['user_id']]);
    echo json_encode(['ok'=>true]);
} catch (Throwable $exception) {
    http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Mobile token is invalid or expired.']);
}
