<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$requestId = bin2hex(random_bytes(12));
header('X-Request-Id: ' . $requestId);

function mobile_token_json(int $status, array $payload): never
{
    global $requestId;
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    mobile_token_json(405, ['ok' => false, 'error' => 'Method not allowed.', 'error_code' => 'method_not_allowed', 'request_id' => $requestId]);
}
$contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
if (!str_starts_with($contentType, 'application/json')) {
    mobile_token_json(415, ['ok' => false, 'error' => 'JSON requests only.', 'error_code' => 'json_required', 'request_id' => $requestId]);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    mobile_token_json(413, ['ok' => false, 'error' => 'Request body is too large.', 'error_code' => 'request_too_large', 'request_id' => $requestId]);
}
$limit = beyond_rate_limit_consume($pdo, 'mobile-token-exchange', '', 30, 300, 900);
if (!$limit['allowed']) {
    header('Retry-After: ' . $limit['retry_after']);
    mobile_token_json(429, ['ok' => false, 'error' => 'Too many token exchanges. Try again later.', 'error_code' => 'token_exchange_rate_limited', 'retry_after' => $limit['retry_after'], 'request_id' => $requestId]);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) mobile_token_json(400, ['ok' => false, 'error' => 'A JSON authorization exchange is required.', 'error_code' => 'invalid_exchange_request', 'request_id' => $requestId]);
$code = trim((string)($input['code'] ?? ''));
$verifier = trim((string)($input['code_verifier'] ?? ''));
if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $code) || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
    mobile_token_json(422, ['ok' => false, 'error' => 'Invalid authorization exchange.', 'error_code' => 'invalid_exchange_request', 'request_id' => $requestId]);
}
$deviceId = trim((string)($input['device_id'] ?? ''));
if ($deviceId !== '' && !preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $deviceId)) {
    mobile_token_json(422, ['ok' => false, 'error' => 'Invalid device identifier.', 'error_code' => 'invalid_device_id', 'request_id' => $requestId]);
}
$deviceId = $deviceId !== '' ? $deviceId : bin2hex(random_bytes(16));
$deviceName = preg_replace('/[\x00-\x1F\x7F]/u', '', trim((string)($input['device_name'] ?? 'Mobile device'))) ?? 'Mobile device';
$deviceName = trim(substr($deviceName, 0, 120)) ?: 'Mobile device';

try {
    $pdo->beginTransaction();
    $lookup = $pdo->prepare("SELECT c.user_id,c.audience,c.code_challenge,u.status FROM mobile_authorization_codes c INNER JOIN users u ON u.id=c.user_id WHERE c.code_hash=? AND c.used_at IS NULL AND c.expires_at>? LIMIT 1");
    $lookup->execute([hash('sha256', $code), date('Y-m-d H:i:s')]);
    $record = $lookup->fetch(PDO::FETCH_ASSOC);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    if (!$record || ($record['status'] ?? '') !== 'active' || !hash_equals((string)$record['code_challenge'], $challenge)) {
        $pdo->rollBack();
        mobile_token_json(401, ['ok' => false, 'error' => 'Authorization code is invalid or expired.', 'error_code' => 'authorization_code_rejected', 'request_id' => $requestId]);
    }
    $consume = $pdo->prepare('UPDATE mobile_authorization_codes SET used_at=? WHERE code_hash=? AND used_at IS NULL');
    $consume->execute([date('Y-m-d H:i:s'), hash('sha256', $code)]);
    if ($consume->rowCount() !== 1) {
        $pdo->rollBack();
        mobile_token_json(401, ['ok' => false, 'error' => 'Authorization code is already used.', 'error_code' => 'authorization_code_rejected', 'request_id' => $requestId]);
    }

    $audience = (string)$record['audience'];
    $client = beyond_api_client($audience);
    if ($client === []) throw new RuntimeException('Authorization client is no longer registered.');
    $appSlug = (string)($client['app_slug'] ?? '');
    $grantQuery = $pdo->prepare('SELECT permissions_json,revoked_at FROM connected_apps WHERE user_id=? AND app_slug=? LIMIT 1');
    $grantQuery->execute([(int)$record['user_id'], $appSlug]);
    $grant = $grantQuery->fetch(PDO::FETCH_ASSOC);
    $grantScopes = is_array($grant) ? json_decode((string)($grant['permissions_json'] ?? '[]'), true) : null;
    $scopes = is_array($grantScopes) ? array_values(array_intersect(beyond_mobile_scopes($audience), array_map('strval', $grantScopes))) : [];
    if (!is_array($grant) || !empty($grant['revoked_at']) || $scopes === []) {
        $pdo->rollBack();
        mobile_token_json(403, ['ok' => false, 'error' => 'App permissions are not approved. Sign in again and approve the requested access.', 'error_code' => 'consent_required', 'request_id' => $requestId]);
    }

    $now = date('Y-m-d H:i:s');
    $familyId = bin2hex(random_bytes(32));
    $familyExpiry = date('Y-m-d H:i:s', time() + 180 * 86400);
    $refreshExpiry = date('Y-m-d H:i:s', time() + 30 * 86400);
    $pdo->prepare('UPDATE mobile_token_families SET revoked_at=? WHERE user_id=? AND app_slug=? AND device_id=? AND revoked_at IS NULL')->execute([
        $now, (int)$record['user_id'], $appSlug, $deviceId,
    ]);
    $pdo->prepare('UPDATE mobile_refresh_tokens SET revoked_at=? WHERE family_id IN (SELECT family_id FROM mobile_token_families WHERE user_id=? AND app_slug=? AND device_id=? AND revoked_at=?) AND revoked_at IS NULL')->execute([
        $now, (int)$record['user_id'], $appSlug, $deviceId, $now,
    ]);
    $pdo->prepare('INSERT INTO mobile_token_families(family_id,user_id,audience,app_slug,device_id,device_name,scopes_json,created_at,last_used_at,expires_at,revoked_at) VALUES(?,?,?,?,?,?,?,?,?,?,NULL)')->execute([
        $familyId, (int)$record['user_id'], $audience, $appSlug, $deviceId, $deviceName,
        json_encode($scopes, JSON_THROW_ON_ERROR), $now, $now, $familyExpiry,
    ]);
    $refreshToken = beyond_mobile_base64url(random_bytes(48));
    $refreshHash = hash('sha256', $refreshToken);
    $pdo->prepare('INSERT INTO mobile_refresh_tokens(token_hash,family_id,expires_at,created_at) VALUES(?,?,?,?)')->execute([
        $refreshHash, $familyId, $refreshExpiry, $now,
    ]);
    $shortLivedSession = (string)($input['token_version'] ?? '') === '0.4';
    $accessTtl = $shortLivedSession ? 900 : 3600;
    $accessToken = beyond_mobile_issue_token((int)$record['user_id'], $accessTtl, $audience, $pdo, $familyId, $scopes);
    $pdo->commit();

    mobile_token_json(200, [
        'ok' => true,
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'expires_in' => $accessTtl,
        'refresh_expires_in' => 2592000,
        'audience' => $audience,
        'scope' => implode(' ', $scopes),
        'request_id' => $requestId,
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile token exchange failed request_id=' . $requestId . ' class=' . get_class($exception) . ': ' . $exception->getMessage());
    mobile_token_json(503, ['ok' => false, 'error' => 'Mobile sign-in is temporarily unavailable.', 'error_code' => 'token_service_unavailable', 'request_id' => $requestId]);
}
