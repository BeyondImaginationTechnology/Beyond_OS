<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$requestId = bin2hex(random_bytes(12));
header('X-Request-Id: ' . $requestId);

function mobile_refresh_json(int $status, array $payload): never
{
    global $requestId;
    http_response_code($status);
    echo json_encode($payload + ['request_id' => $requestId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function mobile_revoke_family(PDO $pdo, string $familyId, string $now): void
{
    $pdo->prepare('UPDATE mobile_token_families SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
    $pdo->prepare('UPDATE mobile_refresh_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
    $pdo->prepare('UPDATE mobile_access_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    mobile_refresh_json(405, ['ok' => false, 'error' => 'Method not allowed.', 'error_code' => 'method_not_allowed']);
}
if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
    mobile_refresh_json(415, ['ok' => false, 'error' => 'JSON requests only.', 'error_code' => 'json_required']);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
    mobile_refresh_json(413, ['ok' => false, 'error' => 'Request body is too large.', 'error_code' => 'request_too_large']);
}
$limit = beyond_rate_limit_consume($pdo, 'mobile-token-refresh', '', 60, 300, 900);
if (!$limit['allowed']) {
    header('Retry-After: ' . $limit['retry_after']);
    mobile_refresh_json(429, ['ok' => false, 'error' => 'Too many refresh attempts. Try again later.', 'error_code' => 'token_refresh_rate_limited', 'retry_after' => $limit['retry_after']]);
}
$input = json_decode((string)file_get_contents('php://input'), true);
$refreshToken = is_array($input) ? trim((string)($input['refresh_token'] ?? '')) : '';
$audience = is_array($input) ? trim((string)($input['audience'] ?? '')) : '';
if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $refreshToken) || !preg_match('/^[a-z0-9][a-z0-9-]{1,63}$/', $audience)) {
    mobile_refresh_json(422, ['ok' => false, 'error' => 'A refresh token and registered audience are required.', 'error_code' => 'invalid_refresh_request']);
}

$now = date('Y-m-d H:i:s');
try {
    $pdo->beginTransaction();
    $lockSuffix = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
    $lookup = $pdo->prepare('SELECT r.token_hash,r.family_id,r.expires_at,r.used_at,r.revoked_at,f.user_id,f.audience,f.app_slug,f.scopes_json,f.expires_at AS family_expires_at,f.revoked_at AS family_revoked_at,u.status FROM mobile_refresh_tokens r INNER JOIN mobile_token_families f ON f.family_id=r.family_id INNER JOIN users u ON u.id=f.user_id WHERE r.token_hash=? LIMIT 1' . $lockSuffix);
    $lookup->execute([hash('sha256', $refreshToken)]);
    $record = $lookup->fetch(PDO::FETCH_ASSOC);
    if (!$record || !hash_equals((string)$record['audience'], $audience) || ($record['status'] ?? '') !== 'active'
        || !empty($record['family_revoked_at']) || $record['family_expires_at'] <= $now) {
        $pdo->rollBack();
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        mobile_refresh_json(401, ['ok' => false, 'error' => 'Refresh token is invalid or expired. Sign in again.', 'error_code' => 'refresh_token_rejected']);
    }

    if (!empty($record['used_at'])) {
        mobile_revoke_family($pdo, (string)$record['family_id'], $now);
        $pdo->commit();
        error_log('Mobile refresh token replay detected request_id=' . $requestId . ' family=' . substr((string)$record['family_id'], 0, 12));
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        mobile_refresh_json(401, ['ok' => false, 'error' => 'This session was signed out because a reused refresh token was detected. Sign in again.', 'error_code' => 'refresh_token_reuse_detected']);
    }
    if (!empty($record['revoked_at']) || $record['expires_at'] <= $now) {
        $pdo->rollBack();
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        mobile_refresh_json(401, ['ok' => false, 'error' => 'Refresh token is invalid or expired. Sign in again.', 'error_code' => 'refresh_token_rejected']);
    }

    $connectionQuery = $pdo->prepare('SELECT permissions_json,revoked_at FROM connected_apps WHERE user_id=? AND app_slug=? LIMIT 1');
    $connectionQuery->execute([(int)$record['user_id'], (string)$record['app_slug']]);
    $connection = $connectionQuery->fetch(PDO::FETCH_ASSOC);
    $granted = is_array($connection) && empty($connection['revoked_at'])
        ? json_decode((string)($connection['permissions_json'] ?? '[]'), true)
        : [];
    $familyScopes = json_decode((string)$record['scopes_json'], true);
    if (!is_array($granted) || !is_array($familyScopes)) {
        mobile_revoke_family($pdo, (string)$record['family_id'], $now);
        $pdo->commit();
        mobile_refresh_json(403, ['ok' => false, 'error' => 'App permissions are no longer active. Sign in again to review access.', 'error_code' => 'consent_required']);
    }
    $scopes = array_values(array_intersect(
        beyond_mobile_scopes($audience),
        array_map('strval', $familyScopes),
        array_map('strval', $granted)
    ));
    if ($scopes === []) {
        mobile_revoke_family($pdo, (string)$record['family_id'], $now);
        $pdo->commit();
        mobile_refresh_json(403, ['ok' => false, 'error' => 'App permissions are no longer active. Sign in again to review access.', 'error_code' => 'consent_required']);
    }
    $pdo->prepare('UPDATE mobile_token_families SET scopes_json=? WHERE family_id=?')->execute([json_encode($scopes, JSON_THROW_ON_ERROR), (string)$record['family_id']]);

    $nextToken = beyond_mobile_base64url(random_bytes(48));
    $nextHash = hash('sha256', $nextToken);
    $refreshExpiry = min(time() + 30 * 86400, strtotime((string)$record['family_expires_at']));
    $refreshExpiryText = date('Y-m-d H:i:s', $refreshExpiry);
    $consume = $pdo->prepare('UPDATE mobile_refresh_tokens SET used_at=?,replaced_by_hash=? WHERE token_hash=? AND used_at IS NULL AND revoked_at IS NULL');
    $consume->execute([$now, $nextHash, (string)$record['token_hash']]);
    if ($consume->rowCount() !== 1) {
        mobile_revoke_family($pdo, (string)$record['family_id'], $now);
        $pdo->commit();
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        mobile_refresh_json(401, ['ok' => false, 'error' => 'This session was signed out because the refresh token was already used.', 'error_code' => 'refresh_token_reuse_detected']);
    }
    $pdo->prepare('INSERT INTO mobile_refresh_tokens(token_hash,family_id,expires_at,created_at) VALUES(?,?,?,?)')->execute([
        $nextHash, (string)$record['family_id'], $refreshExpiryText, $now,
    ]);
    $pdo->prepare('UPDATE mobile_token_families SET last_used_at=? WHERE family_id=?')->execute([$now, (string)$record['family_id']]);
    $accessToken = beyond_mobile_issue_token((int)$record['user_id'], 900, $audience, $pdo, (string)$record['family_id'], $scopes);
    $pdo->commit();

    mobile_refresh_json(200, [
        'ok' => true,
        'access_token' => $accessToken,
        'refresh_token' => $nextToken,
        'token_type' => 'Bearer',
        'expires_in' => 900,
        'refresh_expires_in' => max(0, $refreshExpiry - time()),
        'audience' => $audience,
        'scope' => implode(' ', $scopes),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile token refresh failed request_id=' . $requestId . ' class=' . get_class($exception) . ': ' . $exception->getMessage());
    mobile_refresh_json(503, ['ok' => false, 'error' => 'Token refresh is temporarily unavailable.', 'error_code' => 'token_service_unavailable']);
}
