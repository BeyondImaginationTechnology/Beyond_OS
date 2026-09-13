<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function mobile_token_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    mobile_token_json(405, ['ok' => false, 'error' => 'Method not allowed.']);
}
$contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
if (!str_starts_with($contentType, 'application/json')) {
    mobile_token_json(415, ['ok' => false, 'error' => 'JSON requests only.']);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    mobile_token_json(413, ['ok' => false, 'error' => 'Request body is too large.']);
}
$limit = beyond_rate_limit_consume($pdo, 'mobile-token-exchange', '', 30, 300, 900);
if (!$limit['allowed']) {
    header('Retry-After: ' . $limit['retry_after']);
    mobile_token_json(429, ['ok' => false, 'error' => 'Too many token exchanges. Try again later.', 'retry_after' => $limit['retry_after']]);
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) mobile_token_json(400, ['ok' => false, 'error' => 'A JSON authorization exchange is required.']);
$code = trim((string)($input['code'] ?? ''));
$verifier = trim((string)($input['code_verifier'] ?? ''));
if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $code) || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
    mobile_token_json(422, ['ok' => false, 'error' => 'Invalid authorization exchange.']);
}

try {
    $pdo->beginTransaction();
    $lookup = $pdo->prepare("SELECT c.user_id,c.audience,c.code_challenge FROM mobile_authorization_codes c INNER JOIN users u ON u.id=c.user_id WHERE c.code_hash=? AND c.used_at IS NULL AND c.expires_at>? AND u.status='active' LIMIT 1");
    $lookup->execute([hash('sha256', $code), date('Y-m-d H:i:s')]);
    $record = $lookup->fetch(PDO::FETCH_ASSOC);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    if (!$record || !hash_equals((string)$record['code_challenge'], $challenge)) {
        $pdo->rollBack();
        mobile_token_json(401, ['ok' => false, 'error' => 'Authorization code is invalid or expired.']);
    }
    $consume = $pdo->prepare('UPDATE mobile_authorization_codes SET used_at=? WHERE code_hash=? AND used_at IS NULL');
    $consume->execute([date('Y-m-d H:i:s'), hash('sha256', $code)]);
    if ($consume->rowCount() !== 1) {
        $pdo->rollBack();
        mobile_token_json(401, ['ok' => false, 'error' => 'Authorization code is already used.']);
    }
    $token = beyond_mobile_issue_token((int)$record['user_id'], 3600, (string)$record['audience'], $pdo);
    $pdo->commit();
    $scopes = beyond_mobile_scopes((string)$record['audience']);
    mobile_token_json(200, [
        'ok' => true,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'audience' => (string)$record['audience'],
        'scope' => implode(' ', $scopes),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile token exchange failed: ' . $exception->getMessage());
    mobile_token_json(503, ['ok' => false, 'error' => 'Mobile sign-in is temporarily unavailable.']);
}
