<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/marqeta.php';

function marqeta_webhook_response(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    marqeta_webhook_response(405, ['ok' => false]);
}

$config = beyond_marqeta_config();
if (!$config['webhook_configured']) {
    marqeta_webhook_response(503, ['ok' => false, 'error' => 'Webhook is not configured.']);
}

$username = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
$password = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
if ($username === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization = (string)$_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($authorization, 'Basic ') === 0) {
        $decoded = base64_decode(substr($authorization, 6), true);
        if (is_string($decoded) && str_contains($decoded, ':')) {
            [$username, $password] = explode(':', $decoded, 2);
        }
    }
}
if (!hash_equals((string)$config['webhook_username'], $username)
    || !hash_equals((string)$config['webhook_password'], $password)) {
    header('WWW-Authenticate: Basic realm="Beyond Marqeta Webhook"');
    marqeta_webhook_response(401, ['ok' => false]);
}

$raw = file_get_contents('php://input');
if (!is_string($raw) || $raw === '' || strlen($raw) > 1048576) {
    marqeta_webhook_response(400, ['ok' => false, 'error' => 'Invalid body.']);
}
$signature = trim((string)($_SERVER['HTTP_X_MARQETA_SIGNATURE'] ?? ''));
$expected = hash_hmac('sha256', $raw, (string)$config['webhook_secret']);
if ($signature === '' || !hash_equals($expected, strtolower($signature))) {
    marqeta_webhook_response(401, ['ok' => false, 'error' => 'Invalid signature.']);
}

$payload = json_decode($raw, true);
if (!is_array($payload)) marqeta_webhook_response(400, ['ok' => false, 'error' => 'Invalid JSON.']);

$ping = $payload['pings'][0] ?? null;
if (is_array($ping)
    && ($ping['token'] ?? '') === 'marqeta'
    && ($ping['payload'] ?? '') === 'healthcheck') {
    marqeta_webhook_response(200, ['status' => 'alive']);
}

try {
    beyond_card_process_webhook($pdo, $payload);
    marqeta_webhook_response(200, ['ok' => true]);
} catch (Throwable $exception) {
    error_log('Marqeta webhook failed: ' . $exception->getMessage());
    marqeta_webhook_response(500, ['ok' => false]);
}
