<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../includes/modes.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) { http_response_code(403); echo json_encode(['error' => 'Your secure session expired. Refresh Jaguar and try again.']); exit; }
$signedIn = !empty($_SESSION['user_id']);
$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) { http_response_code(400); echo json_encode(['error' => 'Invalid request.']); exit; }
try {
    $limit = $signedIn
        ? beyond_rate_limit_consume(beyond_db(), 'jaguar-chat', (string) $_SESSION['user_id'], 20, 60, 60)
        : beyond_rate_limit_consume(beyond_db(), 'jaguar-guest-chat', '', 6, 300, 600);
    if (!$limit['allowed']) { http_response_code(429); header('Retry-After: ' . $limit['retry_after']); echo json_encode(['error' => 'Jaguar is resting for a moment. Please try again shortly.']); exit; }
} catch (Throwable $exception) {
    error_log('Jaguar rate limiter unavailable: ' . $exception->getMessage());
}
if (!$signedIn) {
    $turnstileSecret = trim((string) getenv('JAGUAR_TURNSTILE_SECRET_KEY'));
    if ($turnstileSecret === '') { http_response_code(503); echo json_encode(['error' => 'Guest verification is not configured yet. Sign in with Beyond ID or try again later.']); exit; }
    $turnstileToken = is_string($payload['turnstile_token'] ?? null) ? trim($payload['turnstile_token']) : '';
    if ($turnstileToken === '' || strlen($turnstileToken) > 2048) { http_response_code(403); echo json_encode(['error' => 'Complete the security check and try again.']); exit; }
    $verifyRequest = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($verifyRequest, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'], CURLOPT_POSTFIELDS => http_build_query(['secret' => $turnstileSecret, 'response' => $turnstileToken, 'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '')])]);
    $verifyResponse = curl_exec($verifyRequest);
    $verifyStatus = (int) curl_getinfo($verifyRequest, CURLINFO_RESPONSE_CODE);
    curl_close($verifyRequest);
    $verification = is_string($verifyResponse) ? json_decode($verifyResponse, true) : null;
    $expectedHostname = trim((string) getenv('JAGUAR_TURNSTILE_HOSTNAME'));
    if ($expectedHostname === '') { $expectedHostname = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')); }
    $validVerification = $verifyStatus >= 200 && $verifyStatus < 300 && is_array($verification) && ($verification['success'] ?? false) === true;
    $validAction = ($verification['action'] ?? '') === 'jaguar_guest_prompt';
    $validHostname = $expectedHostname === '' || hash_equals(strtolower($expectedHostname), strtolower((string) ($verification['hostname'] ?? '')));
    if (!$validVerification || !$validAction || !$validHostname) { http_response_code(403); echo json_encode(['error' => 'Security verification failed or expired. Please try again.']); exit; }
}
$mode = is_string($payload['mode'] ?? null) ? strtolower(trim($payload['mode'])) : 'explain';
$modeDefinition = jaguar_mode($mode);
if ($modeDefinition === null) { http_response_code(422); echo json_encode(['error' => 'Choose a valid Jaguar Thinking mode.']); exit; }
if (!jaguar_mode_is_enabled($mode)) { http_response_code(501); echo json_encode(['error' => 'Jaguar Thinking ' . $modeDefinition['label'] . ' is planned, not available in this preview yet.']); exit; }
$language = is_string($payload['language'] ?? null) ? strtolower(trim($payload['language'])) : 'en';
if (!in_array($language, ['en', 'fr', 'es'], true)) { http_response_code(422); echo json_encode(['error' => 'Choose English, French, or Spanish.']); exit; }
$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
if ($messages === [] || count($messages) > 24) { http_response_code(422); echo json_encode(['error' => 'Provide between 1 and 24 messages.']); exit; }
foreach ($messages as $message) {
    if (!is_array($message) || !in_array($message['role'] ?? '', ['user', 'assistant'], true) || !is_string($message['content'] ?? null) || trim($message['content']) === '' || mb_strlen($message['content']) > 8000) { http_response_code(422); echo json_encode(['error' => 'Invalid chat message.']); exit; }
}
$runtimeUrl = rtrim((string) getenv('JAGUAR_RUNTIME_URL'), '/');
if ($runtimeUrl === '' || !filter_var($runtimeUrl, FILTER_VALIDATE_URL)) { http_response_code(503); echo json_encode(['error' => 'Jaguar is not available yet.']); exit; }
$runtimeToken = trim((string) getenv('JAGUAR_RUNTIME_TOKEN'));
$headers = ['Content-Type: application/json'];
if ($runtimeToken !== '') { $headers[] = 'Authorization: Bearer ' . $runtimeToken; }
$request = curl_init($runtimeUrl . '/v1/chat');
curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 145, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode(['mode' => $mode, 'language' => $language, 'messages' => $messages], JSON_THROW_ON_ERROR)]);
$response = curl_exec($request); $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
if (!is_string($response) || $status < 200 || $status >= 300) { http_response_code(503); echo json_encode(['error' => 'Jaguar could not complete that request.']); exit; }
echo $response;
