<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
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
    if ($turnstileSecret === '') { http_response_code(503); echo json_encode(['error' => 'Verification is temporarily unavailable. Please try again later.']); exit; }
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
$lastMessage = $messages[array_key_last($messages)];
$simplePrompt = mb_strtolower(trim((string) $lastMessage['content']));
$simpleReply = null;
$simpleCopy = [
    'en' => ['hello' => 'Hello! I’m Jaguar. What would you like to explore?', 'thanks' => 'You’re welcome. What should we explore next?', 'acknowledgement' => 'I’m here when you’re ready. What should we explore?', 'help' => 'I’m Jaguar, Beyond’s AI assistant. I can explain ideas, help shape plans, work through code, and teach difficult topics in plain language.', 'version' => 'You’re using Llama-Jaguar v0.2 Preview.'],
    'fr' => ['hello' => 'Bonjour ! Je suis Jaguar. Qu’aimeriez-vous explorer ?', 'thanks' => 'Avec plaisir. Qu’allons-nous explorer ensuite ?', 'acknowledgement' => 'Je suis là quand vous êtes prêt. Qu’allons-nous explorer ?', 'help' => 'Je suis Jaguar, l’assistant IA de Beyond. Je peux expliquer des idées, structurer des projets, travailler sur du code et simplifier des sujets difficiles.', 'version' => 'Vous utilisez Llama-Jaguar v0.2 Preview.'],
    'es' => ['hello' => '¡Hola! Soy Jaguar. ¿Qué te gustaría explorar?', 'thanks' => 'De nada. ¿Qué exploramos ahora?', 'acknowledgement' => 'Estoy aquí cuando estés listo. ¿Qué exploramos?', 'help' => 'Soy Jaguar, el asistente de IA de Beyond. Puedo explicar ideas, organizar proyectos, trabajar con código y enseñar temas difíciles con palabras sencillas.', 'version' => 'Estás usando Llama-Jaguar v0.2 Preview.'],
];
// Keep common greeting variations off the scale-to-zero runtime. In particular,
// "Hello world" is a normal first message, not a request that needs a GPU cold start.
if (preg_match('/^(hi|hello|hey|bonjour|salut|hola|buenas)(?:[\s,]+(?:there|jaguar|world|monde|mundo))?[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['hello'];
} elseif (preg_match('/^(thanks|thank you|merci|gracias)[\s!.?]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['thanks'];
} elseif (preg_match('/^(ok|okay|alright|d[’\']accord|bien|vale|perfecto)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['acknowledgement'];
} elseif (preg_match('/^(what can you do|who are you|what[’\']?s your name|what is your name|help|que peux-tu faire|qui es-tu|comment tu t[’\']appelles|qué puedes hacer|quién eres|cómo te llamas)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['help'];
} elseif (preg_match('/^(what version is this|version|quelle version|qué versión)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['version'];
} elseif (preg_match('/^(-?\d+(?:\.\d+)?)\s*([+\-*\/])\s*(-?\d+(?:\.\d+)?)\s*(?:=|\?)?$/', $simplePrompt, $math)) {
    $left = (float) $math[1];
    $right = (float) $math[3];
    $result = match ($math[2]) {
        '+' => $left + $right,
        '-' => $left - $right,
        '*' => $left * $right,
        '/' => $right == 0.0 ? null : $left / $right,
    };
    $simpleReply = $result === null ? ['en' => 'Division by zero is undefined.', 'fr' => 'La division par zéro est indéfinie.', 'es' => 'La división por cero no está definida.'][$language] : rtrim(rtrim(number_format($result, 8, '.', ''), '0'), '.');
}
if ($simpleReply !== null) {
    echo json_encode(['model' => 'jaguar-fast-lane', 'adapter' => null, 'mode' => $mode, 'message' => $simpleReply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$runtimeUrl = rtrim((string) getenv('JAGUAR_RUNTIME_URL'), '/');
if ($runtimeUrl === '' || !filter_var($runtimeUrl, FILTER_VALIDATE_URL)) { http_response_code(503); echo json_encode(['error' => 'Jaguar is not available yet.']); exit; }
$runtimeToken = trim((string) getenv('JAGUAR_RUNTIME_TOKEN'));
$headers = ['Content-Type: application/json'];
if ($runtimeToken !== '') { $headers[] = 'Authorization: Bearer ' . $runtimeToken; }
$request = curl_init($runtimeUrl . '/v1/chat');
// Leave enough time for a warm runtime, but return a usable error before the
// browser can appear permanently stuck while a cold runtime is unavailable.
curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 105, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode(['mode' => $mode, 'language' => $language, 'messages' => $messages], JSON_THROW_ON_ERROR)]);
$response = curl_exec($request); $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
if (!is_string($response) || $status < 200 || $status >= 300) { http_response_code(503); echo json_encode(['error' => 'Jaguar could not complete that request.']); exit; }
echo $response;
