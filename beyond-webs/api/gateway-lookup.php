<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function webs_gateway_reply(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    webs_gateway_reply(405, ['active' => false]);
}
$body = file_get_contents('php://input');
if (!is_string($body) || strlen($body) > 2048) webs_gateway_reply(413, ['active' => false]);
$secret = (string)getenv('BEYOND_WEBS_GATEWAY_SHARED_SECRET');
$timestamp = (string)($_SERVER['HTTP_X_BEYOND_TIMESTAMP'] ?? '');
$signature = (string)($_SERVER['HTTP_X_BEYOND_SIGNATURE'] ?? '');
$sentAt = ctype_digit($timestamp) ? (int)$timestamp : 0;
if ($secret === '' || $sentAt < time() - 60 || $sentAt > time() + 60
    || !hash_equals(hash_hmac('sha256', $timestamp . "\n" . $body, $secret), $signature)) {
    webs_gateway_reply(401, ['active' => false]);
}
$input = json_decode($body, true);
$requestId = is_array($input) ? (string)($input['request_id'] ?? '') : '';
$userId = is_array($input) ? (int)($input['user_id'] ?? 0) : 0;
if ($userId < 1 || !preg_match('/^[a-f0-9]{32}$/', $requestId)) webs_gateway_reply(400, ['active' => false]);

try {
    $pdo = beyond_db();
    $query = $pdo->prepare("SELECT provider_private_ip, started_at, session_runtime_seconds FROM beyond_webs_requests WHERE user_id=? AND request_id=? AND status='running' LIMIT 1");
    $query->execute([$userId, $requestId]);
    $session = $query->fetch(PDO::FETCH_ASSOC);
    $ip = is_array($session) ? (string)($session['provider_private_ip'] ?? '') : '';
    $startedAt = is_array($session) ? strtotime((string)$session['started_at'] . ' UTC') : false;
    $runtime = is_array($session) ? (int)$session['session_runtime_seconds'] : 0;
    if (!is_array($session) || $startedAt === false || $runtime < 1
        || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        webs_gateway_reply(403, ['active' => false]);
    }
    $expires = $startedAt + $runtime;
    if ($expires <= time()) webs_gateway_reply(403, ['active' => false]);
    webs_gateway_reply(200, ['active' => true, 'private_ip' => $ip, 'session_expires_at' => $expires]);
} catch (Throwable $exception) {
    error_log('Beyond Webs gateway lookup failed: ' . $exception->getMessage());
    webs_gateway_reply(503, ['active' => false]);
}
