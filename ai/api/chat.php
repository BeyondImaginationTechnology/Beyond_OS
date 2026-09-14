<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../includes/modes.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Sign in with Beyond ID to use Jaguar.']); exit; }
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) { http_response_code(403); echo json_encode(['error' => 'Your secure session expired. Refresh Jaguar and try again.']); exit; }
try {
    $limit = beyond_rate_limit_consume(beyond_db(), 'jaguar-chat', (string)$_SESSION['user_id'], 20, 60, 60);
    if (!$limit['allowed']) { http_response_code(429); header('Retry-After: ' . $limit['retry_after']); echo json_encode(['error' => 'Jaguar is resting for a moment. Please try again shortly.']); exit; }
} catch (Throwable $exception) {
    error_log('Jaguar rate limiter unavailable: ' . $exception->getMessage());
}
$payload = json_decode((string) file_get_contents('php://input'), true);
$mode = is_string($payload['mode'] ?? null) ? strtolower(trim($payload['mode'])) : 'explain';
$modeDefinition = jaguar_mode($mode);
if ($modeDefinition === null) { http_response_code(422); echo json_encode(['error' => 'Choose a valid Jaguar Thinking mode.']); exit; }
if (!jaguar_mode_is_enabled($mode)) { http_response_code(501); echo json_encode(['error' => 'Jaguar Thinking ' . $modeDefinition['label'] . ' is planned, not available in this preview yet.']); exit; }
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
curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode(['mode' => $mode, 'messages' => $messages], JSON_THROW_ON_ERROR)]);
$response = curl_exec($request); $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
if (!is_string($response) || $status < 200 || $status >= 300) { http_response_code(503); echo json_encode(['error' => 'Jaguar could not complete that request.']); exit; }
echo $response;
