<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$requestId = bin2hex(random_bytes(12));
header('X-Request-Id: ' . $requestId);

function mobile_revoke_reply(int $status, array $body): never
{
    global $requestId;
    http_response_code($status);
    echo json_encode($body + ['request_id' => $requestId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    mobile_revoke_reply(405, ['ok' => false, 'error' => 'Method not allowed.', 'error_code' => 'method_not_allowed']);
}
$body = json_decode((string)file_get_contents('php://input'), true);
$refreshToken = is_array($body) ? trim((string)($body['refresh_token'] ?? '')) : '';
$token = '';
$claims = null;
try {
    $token = beyond_mobile_bearer_token();
    $claims = beyond_mobile_verify_token($token, null, $pdo);
} catch (Throwable $exception) {
    // A refresh token can revoke its family after the access token expires.
}

$familyId = is_array($claims) ? (string)($claims['family_id'] ?? '') : '';
if ($familyId === '' && preg_match('/^[A-Za-z0-9_-]{43,128}$/', $refreshToken)) {
    $lookup = $pdo->prepare('SELECT family_id FROM mobile_refresh_tokens WHERE token_hash=? LIMIT 1');
    $lookup->execute([hash('sha256', $refreshToken)]);
    $familyId = (string)($lookup->fetchColumn() ?: '');
}
if (!is_array($claims) && !preg_match('/^[a-f0-9]{64}$/', $familyId)) {
    header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
    mobile_revoke_reply(401, ['ok' => false, 'error' => 'A valid access or refresh token is required.', 'error_code' => 'invalid_token']);
}

try {
    $now = date('Y-m-d H:i:s');
    $pdo->beginTransaction();
    if (preg_match('/^[a-f0-9]{64}$/', $familyId)) {
        $owner = $pdo->prepare('SELECT user_id FROM mobile_token_families WHERE family_id=? LIMIT 1');
        $owner->execute([$familyId]);
        $familyUserId = $owner->fetchColumn();
        if ($familyUserId === false || (is_array($claims) && (int)$familyUserId !== (int)$claims['user_id'])) {
            $pdo->rollBack();
            mobile_revoke_reply(401, ['ok' => false, 'error' => 'Mobile session was not found.', 'error_code' => 'invalid_token']);
        }
        $pdo->prepare('UPDATE mobile_token_families SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
        $pdo->prepare('UPDATE mobile_refresh_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
        $pdo->prepare('UPDATE mobile_access_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
    } elseif (is_array($claims)) {
        $pdo->prepare('UPDATE mobile_access_tokens SET revoked_at=? WHERE jti=? AND user_id=? AND revoked_at IS NULL')->execute([$now, $claims['jti'], $claims['user_id']]);
    }
    $pdo->commit();
    mobile_revoke_reply(200, ['ok' => true]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile token revocation failed request_id=' . $requestId . ' class=' . get_class($exception));
    mobile_revoke_reply(503, ['ok' => false, 'error' => 'Mobile session revocation is temporarily unavailable.', 'error_code' => 'revocation_unavailable']);
}
