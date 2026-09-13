<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['ok' => false, 'authenticated' => false, 'error' => 'Method not allowed.']);
    exit;
}

try {
    $token = beyond_mobile_bearer_token();
    $audience = beyond_mobile_token_audience($token);
    $claimedAudience = trim((string)($_SERVER['HTTP_X_BEYOND_APP'] ?? ''));
    if ($claimedAudience === '' || !hash_equals($audience, $claimedAudience)) throw new RuntimeException('Mobile token is not valid for this app.');
    $claims = beyond_mobile_verify_token($token, $audience, $pdo);
    beyond_mobile_require_scope($claims, 'profile:read');
    $userId = $claims['user_id'];
    $stmt = $pdo->prepare('SELECT u.id,u.name,u.first_name,u.last_name,u.email,u.status,u.preferred_locale,p.display_name,p.avatar FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || ($user['status'] ?? 'active') !== 'active') {
        throw new RuntimeException('Account unavailable.');
    }
    if (!in_array('email:read', $claims['scopes'], true)) {
        unset($user['email']);
    }
    echo json_encode([
        'ok' => true,
        'authenticated' => true,
        'audience' => $claims['audience'],
        'scopes' => $claims['scopes'],
        'user' => $user,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Mobile session validation failed: ' . $exception->getMessage());
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
    echo json_encode(['ok' => false, 'authenticated' => false, 'error' => 'Mobile token is invalid, expired, or revoked.']);
}
