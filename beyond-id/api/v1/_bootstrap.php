<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mobile-auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

$beyondV1RequestId = bin2hex(random_bytes(12));
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Request-Id: ' . $beyondV1RequestId);

function beyond_v1_json(int $status, array $payload): never
{
    global $beyondV1RequestId;
    http_response_code($status);
    $payload['request_id'] = $beyondV1RequestId;
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function beyond_v1_error(int $status, string $code, string $message, array $extra = []): never
{
    beyond_v1_json($status, ['ok' => false, 'error' => ['code' => $code, 'message' => $message]] + $extra);
}

function beyond_v1_bearer(PDO $pdo, string $scope): array
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
        header('Allow: GET');
        beyond_v1_error(405, 'method_not_allowed', 'This endpoint only accepts GET requests.');
    }
    try {
        $token = beyond_mobile_bearer_token();
        $audience = trim((string)($_SERVER['HTTP_X_BEYOND_APP'] ?? ''));
        if ($audience === '') beyond_v1_error(401, 'app_audience_required', 'The Beyond app audience header is required.');
        $claims = beyond_mobile_verify_token($token, $audience, $pdo);
    } catch (Throwable $exception) {
        error_log('Beyond ID v1 authentication failed request_id=' . ($GLOBALS['beyondV1RequestId'] ?? 'unknown') . ' class=' . get_class($exception));
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        beyond_v1_error(401, 'invalid_token', 'The access token is invalid, expired, or revoked.');
    }
    if (!in_array($scope, $claims['scopes'] ?? [], true)) {
        beyond_v1_error(403, 'insufficient_scope', 'This app has not been granted the required permission.', ['required_scope' => $scope]);
    }
    $endpoint = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'v1-api'));
    $limit = beyond_rate_limit_consume($pdo, 'beyond-v1-' . $endpoint, (string)$claims['user_id'], 120, 300, 900);
    if (!$limit['allowed']) {
        header('Retry-After: ' . $limit['retry_after']);
        beyond_v1_error(429, 'rate_limited', 'This endpoint is temporarily rate limited.', ['retry_after' => $limit['retry_after']]);
    }
    return $claims;
}
