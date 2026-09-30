<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/modes.php';
require_once __DIR__ . '/../includes/usage.php';
require_once __DIR__ . '/../../beyond-id/includes/mobile-auth.php';
require_once __DIR__ . '/../../dailybreath/includes/chat-guide.php';

/** A small cached utility layer for requests that never need the GPU runtime. */
function jaguar_utility_cache(string $key, int $ttl, callable $resolver): ?array
{
    try {
        $pdo = beyond_db();
        $pdo->exec('CREATE TABLE IF NOT EXISTS jaguar_utility_cache (cache_key VARCHAR(191) NOT NULL PRIMARY KEY, payload_json LONGTEXT NOT NULL, expires_at BIGINT NOT NULL, updated_at BIGINT NOT NULL)');
        $cached = $pdo->prepare('SELECT payload_json FROM jaguar_utility_cache WHERE cache_key = ? AND expires_at > ? LIMIT 1');
        $cached->execute([$key, time()]);
        $payload = $cached->fetchColumn();
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) return $decoded;
        }
        $result = $resolver();
        if (!is_array($result)) return null;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? 'INSERT INTO jaguar_utility_cache(cache_key,payload_json,expires_at,updated_at) VALUES(?,?,?,?) ON CONFLICT(cache_key) DO UPDATE SET payload_json=excluded.payload_json, expires_at=excluded.expires_at, updated_at=excluded.updated_at'
            : 'INSERT INTO jaguar_utility_cache(cache_key,payload_json,expires_at,updated_at) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json), expires_at=VALUES(expires_at), updated_at=VALUES(updated_at)';
        $now = time();
        $pdo->prepare($sql)->execute([$key, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $now + $ttl, $now]);
        return $result;
    } catch (Throwable $exception) {
        error_log('Jaguar utility cache unavailable: ' . $exception->getMessage());
        return $resolver();
    }
}

function jaguar_utility_fetch(string $url): ?array
{
    if (!function_exists('curl_init')) return null;
    $request = curl_init($url);
    curl_setopt_array($request, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 7, CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Beyond-Jaguar/0.3 Utility Fast Lane']]);
    $response = curl_exec($request);
    $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    curl_close($request);
    $decoded = is_string($response) && $status >= 200 && $status < 300 ? json_decode($response, true) : null;
    return is_array($decoded) ? $decoded : null;
}

function jaguar_geocode(string $place): ?array
{
    $place = trim(preg_replace('/\s+/', ' ', $place) ?? '');
    if (mb_strlen($place) < 2 || mb_strlen($place) > 80 || preg_match('/[<>\\x00]/', $place)) return null;
    return jaguar_utility_cache('geocode:' . hash('sha256', mb_strtolower($place)), 86400, static function () use ($place): ?array {
        $states = ['alabama','alaska','arizona','arkansas','california','colorado','connecticut','delaware','florida','georgia','hawaii','idaho','illinois','indiana','iowa','kansas','kentucky','louisiana','maine','maryland','massachusetts','michigan','minnesota','mississippi','missouri','montana','nebraska','nevada','new hampshire','new jersey','new mexico','new york','north carolina','north dakota','ohio','oklahoma','oregon','pennsylvania','rhode island','south carolina','south dakota','tennessee','texas','utah','vermont','virginia','washington','west virginia','wisconsin','wyoming'];
        $query = $place;
        $expectedState = '';
        $lowerPlace = mb_strtolower($place);
        foreach ($states as $state) {
            if ($lowerPlace === $state || !str_ends_with($lowerPlace, ' ' . $state)) continue;
            $city = trim(mb_substr($place, 0, mb_strlen($place) - mb_strlen($state)));
            if ($city !== '') {
                $query = $city . ', ' . ucwords($state);
                $expectedState = $state;
            }
            break;
        }
        $response = jaguar_utility_fetch('https://geocoding-api.open-meteo.com/v1/search?' . http_build_query(['name' => $query, 'count' => 10, 'language' => 'en', 'format' => 'json']));
        $results = is_array($response['results'] ?? null) ? $response['results'] : [];
        $result = $results[0] ?? null;
        if ($expectedState !== '') {
            foreach ($results as $candidate) {
                if (!is_array($candidate)) continue;
                if (mb_strtolower((string)($candidate['admin1'] ?? '')) === $expectedState && strtoupper((string)($candidate['country_code'] ?? '')) === 'US') {
                    $result = $candidate;
                    break;
                }
            }
        }
        if (!is_array($result) || !isset($result['latitude'], $result['longitude'], $result['name'])) return null;
        return [
            'name' => (string) $result['name'], 'country' => (string) ($result['country'] ?? ''), 'admin1' => (string) ($result['admin1'] ?? ''),
            'latitude' => (float) $result['latitude'], 'longitude' => (float) $result['longitude'], 'timezone' => (string) ($result['timezone'] ?? 'UTC'),
        ];
    });
}

function jaguar_weather_label(int $code, string $language): string
{
    $labels = [
        'en' => [0 => 'clear sky', 1 => 'mostly clear', 2 => 'partly cloudy', 3 => 'overcast', 45 => 'foggy', 48 => 'rime fog', 51 => 'light drizzle', 53 => 'drizzle', 55 => 'heavy drizzle', 61 => 'light rain', 63 => 'rain', 65 => 'heavy rain', 71 => 'light snow', 73 => 'snow', 75 => 'heavy snow', 80 => 'rain showers', 81 => 'rain showers', 82 => 'heavy showers', 95 => 'thunderstorm'],
        'fr' => [0 => 'ciel dégagé', 1 => 'plutôt dégagé', 2 => 'partiellement nuageux', 3 => 'couvert', 45 => 'brouillard', 48 => 'brouillard givrant', 51 => 'bruine légère', 53 => 'bruine', 55 => 'forte bruine', 61 => 'pluie légère', 63 => 'pluie', 65 => 'forte pluie', 71 => 'neige légère', 73 => 'neige', 75 => 'forte neige', 80 => 'averses', 81 => 'averses', 82 => 'fortes averses', 95 => 'orage'],
        'es' => [0 => 'cielo despejado', 1 => 'mayormente despejado', 2 => 'parcialmente nublado', 3 => 'cubierto', 45 => 'niebla', 48 => 'niebla con escarcha', 51 => 'llovizna ligera', 53 => 'llovizna', 55 => 'llovizna intensa', 61 => 'lluvia ligera', 63 => 'lluvia', 65 => 'lluvia intensa', 71 => 'nieve ligera', 73 => 'nieve', 75 => 'nieve intensa', 80 => 'chubascos', 81 => 'chubascos', 82 => 'chubascos intensos', 95 => 'tormenta'],
    ];
    return $labels[$language][$code] ?? $labels[$language][3];
}
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
$isDailyBreathChat = ($_SERVER['HTTP_X_DAILYBREATH_CHAT'] ?? '') === '1';
$mobileClaims = null;
$authorization = beyond_mobile_authorization_header();
if ($authorization !== '') {
    try {
        $token = beyond_mobile_bearer_token();
        $mobileAudience = $isDailyBreathChat ? 'daily-breath-ios' : 'jaguar-ios';
        $mobileClaims = beyond_mobile_verify_token($token, $mobileAudience, beyond_db());
        beyond_mobile_require_scope($mobileClaims, 'profile:read');
        $_SESSION['user_id'] = (int)$mobileClaims['user_id'];
    } catch (Throwable $exception) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Jaguar", error="invalid_token"');
        echo json_encode(['error' => 'Beyond ID sign-in is invalid or expired.']);
        exit;
    }
} elseif (!$isDailyBreathChat && !verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403); echo json_encode(['error' => 'Your secure session expired. Refresh Jaguar and try again.']); exit;
}
$signedIn = $mobileClaims !== null || !empty($_SESSION['user_id']);
$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) { http_response_code(400); echo json_encode(['error' => 'Invalid request.']); exit; }
$mode = is_string($payload['mode'] ?? null) ? strtolower(trim($payload['mode'])) : 'core';
$modeDefinition = jaguar_mode($mode);
if ($modeDefinition === null) { http_response_code(422); echo json_encode(['error' => 'Choose a valid Jaguar Thinking mode.']); exit; }
if (!jaguar_mode_is_enabled($mode)) { http_response_code(501); echo json_encode(['error' => 'Jaguar Thinking ' . $modeDefinition['label'] . ' is planned, not available in this preview yet.']); exit; }
if (in_array($mode, ['draw', 'video'], true)) {
    if ($mode === 'draw') {
        if (!$signedIn) { http_response_code(401); echo json_encode(['error' => 'Sign in to use Draw. A successful image uses 10 BIT$ from your Beyond wallet.']); exit; }
        try {
            $drawRate = beyond_rate_limit_consume(beyond_db(), 'jaguar-draw', (string)$_SESSION['user_id'], 3, 300, 600);
            if (!$drawRate['allowed']) { http_response_code(429); header('Retry-After: ' . $drawRate['retry_after']); echo json_encode(['error' => 'Draw is resting for a moment. Please try again shortly.']); exit; }
        } catch (Throwable $exception) { error_log('Jaguar Draw rate limiter unavailable: ' . $exception->getMessage()); }
        $drawMessages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $drawLast = $drawMessages === [] ? null : $drawMessages[array_key_last($drawMessages)];
        $drawPrompt = is_array($drawLast) && is_string($drawLast['content'] ?? null) ? trim((string)$drawLast['content']) : '';
        if ($drawPrompt === '' || mb_strlen($drawPrompt) > 2000) { http_response_code(422); echo json_encode(['error' => 'Provide an image prompt of 1–2,000 characters.']); exit; }
        $drawLanguage = (string)($payload['language'] ?? 'en');
        if (!in_array($drawLanguage, ['en', 'fr', 'es'], true)) { http_response_code(422); echo json_encode(['error' => 'Choose a supported Draw language.']); exit; }
        if (preg_match('/\b(?:porn|nudes?|nudity|naked|explicit sexual|child.{0,60}(?:sex|nude|porn)|minor.{0,60}(?:sex|nude|porn))\b/iu', $drawPrompt) === 1) { http_response_code(422); echo json_encode(['error' => 'Jaguar Draw cannot help with explicit sexual content.']); exit; }
        $drawPrice = 10.0;
        $drawUrl = rtrim(jaguar_runtime_config('draw_runtime_url'), '/');
        $drawBalance = null;
        try { $drawBalance = jaguar_wallet_bit_balance(beyond_db(), (int)$_SESSION['user_id']); } catch (Throwable $exception) {}
        if ($drawBalance === null) { http_response_code(503); echo json_encode(['error' => 'Your BIT$ wallet is temporarily unavailable. No BIT$ was charged.']); exit; }
        if ($drawBalance < $drawPrice) { http_response_code(402); echo json_encode(['error' => 'You need 10 BIT$ to generate an image. Add BIT$ to your Beyond wallet, then try again.', 'wallet_bit_balance' => $drawBalance]); exit; }
        if ($drawUrl === '' || !filter_var($drawUrl, FILTER_VALIDATE_URL) || !function_exists('curl_init')) { http_response_code(503); echo json_encode(['error' => 'Jaguar Draw is not connected to its GPU image worker yet. No BIT$ was charged.', 'wallet_bit_balance' => $drawBalance]); exit; }
        $drawToken = jaguar_runtime_config('runtime_token');
        if ($drawToken === '') { http_response_code(503); echo json_encode(['error' => 'Jaguar Draw authentication is not configured. No BIT$ was charged.', 'wallet_bit_balance' => $drawBalance]); exit; }
        $drawUserId = (int)$_SESSION['user_id'];
        $drawKey = 'draw:v1:u' . $drawUserId . ':' . bin2hex(random_bytes(16));
        $drawHold = jaguar_wallet_reserve(beyond_db(), $drawUserId, $drawPrice, $drawKey);
        if (!$drawHold['ok']) {
            http_response_code($drawHold['balance'] !== null ? 402 : 503);
            echo json_encode(['error' => $drawHold['balance'] !== null ? 'You need 10 BIT$ to generate an image.' : 'Your BIT$ wallet could not reserve this image. No BIT$ was charged.', 'wallet_bit_balance' => $drawHold['balance'] ?? $drawBalance]);
            exit;
        }
        register_shutdown_function(static function () use ($drawKey, $drawUserId): void {
            try { jaguar_wallet_release(beyond_db(), $drawUserId, $drawKey); } catch (Throwable $exception) { error_log('Jaguar Draw shutdown release failed: ' . $exception->getMessage()); }
        });
        $drawRequest = curl_init($drawUrl . '/v1/draw');
        curl_setopt_array($drawRequest, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 180, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $drawToken], CURLOPT_POSTFIELDS => json_encode(['prompt' => $drawPrompt, 'language' => $drawLanguage], JSON_THROW_ON_ERROR)]);
        $drawResponse = curl_exec($drawRequest); $drawStatus = (int)curl_getinfo($drawRequest, CURLINFO_RESPONSE_CODE); curl_close($drawRequest);
        $drawResult = is_string($drawResponse) && strlen($drawResponse) <= 15 * 1024 * 1024 ? json_decode($drawResponse, true) : null;
        $imageUrl = is_array($drawResult) && is_string($drawResult['image_url'] ?? null) ? trim($drawResult['image_url']) : '';
        $isDataImage = preg_match('/^data:image\/(?:png|jpeg|webp);base64,[A-Za-z0-9+\/=]+$/', $imageUrl) === 1 && strlen($imageUrl) <= 12 * 1024 * 1024;
        $isHttpsImage = filter_var($imageUrl, FILTER_VALIDATE_URL) !== false && strtolower((string)parse_url($imageUrl, PHP_URL_SCHEME)) === 'https';
        if ($drawStatus < 200 || $drawStatus >= 300 || (!$isDataImage && !$isHttpsImage)) {
            $released = jaguar_wallet_release(beyond_db(), $drawUserId, $drawKey);
            http_response_code(503); echo json_encode(['error' => $released ? 'Jaguar Draw could not generate an image. Your 10 BIT$ hold was released.' : 'Jaguar Draw could not generate an image. The temporary BIT$ hold is pending recovery.', 'draw_receipt' => jaguar_wallet_draw_receipt_safe(beyond_db(), $drawUserId, $drawKey)]); exit;
        }
        $capture = jaguar_wallet_capture(beyond_db(), $drawUserId, $drawKey, 'Jaguar Draw image generation');
        if (!$capture['ok']) { jaguar_wallet_release(beyond_db(), $drawUserId, $drawKey); http_response_code(503); echo json_encode(['error' => 'The image was generated, but the wallet charge could not be recorded. The image was not delivered.', 'draw_receipt' => jaguar_wallet_draw_receipt_safe(beyond_db(), $drawUserId, $drawKey)]); exit; }
        echo json_encode(['model' => 'jaguar-draw-gpu', 'adapter' => $drawResult['adapter'] ?? null, 'mode' => 'draw', 'message' => 'Your Jaguar Draw image is ready.', 'image_url' => $imageUrl, 'wallet_bit_balance' => $capture['balance'], 'charged_bit_dollars' => $drawPrice, 'draw_receipt' => jaguar_wallet_draw_receipt_safe(beyond_db(), $drawUserId, $drawKey)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    http_response_code(501);
    echo json_encode(['error' => 'Jaguar Video is not available in this preview. No BIT$ was charged.']);
    exit;
}
try {
    $limit = $signedIn
        ? beyond_rate_limit_consume(beyond_db(), 'jaguar-chat', (string) $_SESSION['user_id'], 20, 60, 60)
        : beyond_rate_limit_consume(beyond_db(), 'jaguar-guest-chat', '', 6, 300, 600);
    if (!$limit['allowed']) { http_response_code(429); header('Retry-After: ' . $limit['retry_after']); echo json_encode(['error' => 'Jaguar is resting for a moment. Please try again shortly.']); exit; }
} catch (Throwable $exception) {
    error_log('Jaguar rate limiter unavailable: ' . $exception->getMessage());
}
if (!$signedIn) {
    $proof = is_array($payload['proof'] ?? null) ? $payload['proof'] : [];
    $challenge = is_array($_SESSION['jaguar_guest_challenge'] ?? null) ? $_SESSION['jaguar_guest_challenge'] : [];
    $challengeValue = is_string($proof['challenge'] ?? null) ? trim($proof['challenge']) : '';
    $counter = is_string($proof['counter'] ?? null) ? trim($proof['counter']) : '';
    $difficulty = max(1, min(20, (int)($challenge['difficulty'] ?? 16)));
    $requiredPrefix = str_repeat('0', (int)ceil($difficulty / 4));
    $validShape = preg_match('/^[a-f0-9]{36}$/', $challengeValue) === 1 && preg_match('/^\d{1,12}$/', $counter) === 1;
    $validChallenge = $validShape && empty($challenge['used']) && (int)($challenge['expires'] ?? 0) >= time()
        && hash_equals((string)($challenge['challenge'] ?? ''), $challengeValue);
    $validWork = $validChallenge && str_starts_with(hash('sha256', $challengeValue . ':' . $counter), $requiredPrefix);
    if (!$validWork) { http_response_code(403); echo json_encode(['error' => 'Security check failed or expired. Please try again.']); exit; }
    $_SESSION['jaguar_guest_challenge']['used'] = true;
}
if (($payload['knowledge_scope'] ?? '') === 'beyond-tattoo') {
    http_response_code(422);
    echo json_encode(['error' => 'Beyond Tattoo stencil-editor guidance is reserved for Needle Bot, the Beyond Tattoo companion.']);
    exit;
}
$language = is_string($payload['language'] ?? null) ? strtolower(trim($payload['language'])) : 'en';
if (!in_array($language, ['en', 'fr', 'es'], true)) { http_response_code(422); echo json_encode(['error' => 'Choose English, French, or Spanish.']); exit; }
$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
if ($messages === [] || count($messages) > 24) { http_response_code(422); echo json_encode(['error' => 'Provide between 1 and 24 messages.']); exit; }
foreach ($messages as $message) {
    if (!is_array($message) || !in_array($message['role'] ?? '', ['user', 'assistant'], true) || !is_string($message['content'] ?? null) || trim($message['content']) === '' || mb_strlen($message['content']) > 8000) { http_response_code(422); echo json_encode(['error' => 'Invalid chat message.']); exit; }
}
$lastMessage = $messages[array_key_last($messages)];
$originalPrompt = trim((string) $lastMessage['content']);
$simplePrompt = mb_strtolower($originalPrompt);
$guide = is_string($payload['guide'] ?? null) ? strtolower(trim($payload['guide'])) : '';
if ($isDailyBreathChat && ($mode !== 'core' || !in_array($guide, ['chris', 'dovi', 'moe'], true))) {
    http_response_code(422);
    echo json_encode(['error' => 'Daily Breath chat is limited to its sacred-text guides.']);
    exit;
}
$guideProfiles = [
    'chris' => 'You are Chris, a warm Christian Bible study guide. Answer Bible questions respectfully, distinguish quoted text from interpretation, and avoid presenting theology as settled fact when traditions differ.',
    'dovi' => 'You are Dovi, a thoughtful Tanakh study guide. Answer Jewish scripture questions respectfully, note when interpretations vary, and avoid claiming to speak for every Jewish tradition.',
    'moe' => 'You are Moe, a thoughtful Quran study guide. Answer Quran questions respectfully, distinguish translation from interpretation, and acknowledge differences among Islamic traditions.',
];
if (isset($guideProfiles[$guide])) {
    $messages[array_key_last($messages)]['content'] = $guideProfiles[$guide] . "\n\nUser question:\n" . (string)$lastMessage['content'];
    $lastMessage = $messages[array_key_last($messages)];
    // Keep utility and greeting detection based on the user's actual text;
    // the guide profile is only context for the model-backed response.
    $simplePrompt = mb_strtolower($originalPrompt);
}

// This is a deliberately conservative first safety gate. It runs on the server
// before fast-lane handling and before the GPU runtime is contacted, so clients
// cannot bypass it by modifying the browser code.
$explicitPatterns = [
    '/\b(?:porn(?:ography|ographic)?|xxx|nudes?|nudity|naked|onlyfans|blowjob|handjob|masturbat(?:e|ion|ing)|sex(?:ual)?\s+(?:roleplay|story|chat|scene|image|photo|video|content)|explicit(?:ly)?\s+(?:sexual|erotic)|graphic(?:ally)?\s+(?:sexual|erotic))\b/iu',
    '/\b(?:child|minor|underage|teen(?:ager)?|kid)\b.{0,80}\b(?:sex|sexual|nude|naked|porn|explicit|erotic)\b/iu',
    '/\b(?:sex|sexual|nude|naked|porn|explicit|erotic)\b.{0,80}\b(?:child|minor|underage|teen(?:ager)?|kid)\b/iu',
];
foreach ($explicitPatterns as $pattern) {
    if (preg_match($pattern, (string) $lastMessage['content']) === 1) {
        http_response_code(422);
        echo json_encode(['error' => [
            'en' => 'Jaguar cannot help with explicit sexual content.',
            'fr' => 'Jaguar ne peut pas aider avec du contenu sexuel explicite.',
            'es' => 'Jaguar no puede ayudar con contenido sexual explícito.',
        ][$language]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
$dailyBreathReply = dailybreath_chat_local_reply($guide, $originalPrompt, $language);
if ($dailyBreathReply !== null) {
    echo json_encode(['model' => 'jaguar-dailybreath-fast-lane', 'adapter' => 'local-sacred-text', 'mode' => $mode, 'message' => $dailyBreathReply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($isDailyBreathChat) {
    echo json_encode(['model' => 'jaguar-dailybreath-fast-lane', 'adapter' => 'local-scope', 'mode' => $mode, 'message' => dailybreath_chat_scope_reply($guide, $language)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$simpleReply = null;
$simpleCopy = [
    'en' => ['hello' => 'Hello! I’m Jaguar. What would you like to explore?', 'thanks' => 'You’re welcome. What should we explore next?', 'acknowledgement' => 'I’m here when you’re ready. What should we explore?', 'help' => 'I’m Jaguar, Beyond’s AI assistant. Explain teaches ideas; Build preview turns product ideas into scoped plans. Build cannot inspect repositories or change files. Draw is intended for Jaguar image generation; the stencil editor has separate canvas drawing tools.', 'version' => 'You’re using Jaguar v0.5.1 Preview.'],
    'fr' => ['hello' => 'Bonjour ! Je suis Jaguar. Qu’aimeriez-vous explorer ?', 'thanks' => 'Avec plaisir. Qu’allons-nous explorer ensuite ?', 'acknowledgement' => 'Je suis là quand vous êtes prêt. Qu’allons-nous explorer ?', 'help' => 'Je suis Jaguar, l’assistant IA de Beyond. Explain enseigne des idées ; Build transforme les idées de produit en plans structurés. Build ne peut ni consulter des dépôts ni modifier des fichiers. Draw est prévu pour la génération d’images Jaguar ; l’éditeur de pochoirs possède ses propres outils de dessin.', 'version' => 'Vous utilisez Jaguar v0.5.1 Preview.'],
    'es' => ['hello' => '¡Hola! Soy Jaguar. ¿Qué te gustaría explorar?', 'thanks' => 'De nada. ¿Qué exploramos ahora?', 'acknowledgement' => 'Estoy aquí cuando estés listo. ¿Qué exploramos?', 'help' => 'Soy Jaguar, el asistente de IA de Beyond. Explain enseña ideas; Build convierte ideas de producto en planes concretos. Build no puede consultar repositorios ni cambiar archivos. Draw está pensado para la generación de imágenes de Jaguar; el editor de plantillas tiene sus propias herramientas de dibujo.', 'version' => 'Estás usando Jaguar v0.5.1 Preview.'],
];
// Keep common greeting variations off the scale-to-zero runtime. In particular,
// "Hello world" is a normal first message, not a request that needs a GPU cold start.
if (preg_match('/^(hi|hello|hey|bonjour|salut|allo|hola|buenas)(?:[\s,]+(?:there|jaguar|world|monde|mundo))?[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['hello'];
} elseif (preg_match('/^(thanks|thank you|merci|gracias)[\s!.?]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['thanks'];
} elseif (preg_match('/^(ok|okay|alright|d[’\']accord|bien|vale|perfecto)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['acknowledgement'];
} elseif (preg_match('/^(what can you do|who are you|what[’\']?s your name|what is your name|help|que peux-tu faire|qui es-tu|comment tu t[’\']appelles|qué puedes hacer|quién eres|cómo te llamas)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['help'];
} elseif (preg_match('/^(what version is this|version|quelle version|qué versión)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = $simpleCopy[$language]['version'];
} elseif (preg_match('/^(how old are you|what(?:[’\']s| is) your age|when were you (?:made|created|born)|quel âge as-tu|cuántos años tienes)[\s!.?¿¡]*$/u', $simplePrompt)) {
    $simpleReply = [
        'en' => 'I don’t have a human age. I’m Llama Jaguar v0.5.1 Preview, an AI system being built for the BIT ecosystem.',
        'fr' => 'Je n’ai pas d’âge humain. Je suis Llama Jaguar v0.5.1 Preview, un système d’IA conçu pour l’écosystème BIT.',
        'es' => 'No tengo una edad humana. Soy Llama Jaguar v0.5.1 Preview, un sistema de IA creado para el ecosistema BIT.',
    ][$language];
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
if ($mode === 'core') {
    $previousUserPrompt = '';
    for ($messageIndex = count($messages) - 2; $messageIndex >= 0; $messageIndex--) {
        if (($messages[$messageIndex]['role'] ?? '') !== 'user') continue;
        $previousUserPrompt = mb_strtolower(trim((string)$messages[$messageIndex]['content']));
        break;
    }
    if (!preg_match('/\b(weather|temperature|temp|m[ée]t[ée]o|tiempo)\b/iu', $simplePrompt)
        && preg_match('/\b(weather|temperature|temp|m[ée]t[ée]o|tiempo)\b/iu', $previousUserPrompt)
        && preg_match('/^[\p{L} .,’\'-]{2,80}$/u', $originalPrompt)) {
        $simplePrompt = 'weather in ' . $simplePrompt;
    }
    $formatNumber = static fn(float $number): string => rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    $conversionPatterns = [
        '/^(-?\d+(?:\.\d+)?)\s*(?:°\s*)?(?:c|celsius)\s+(?:to|in)\s+(?:°\s*)?(?:f|fahrenheit)[\s?!.]*$/iu' => static fn(float $value): array => [$value * 9 / 5 + 32, '°F'],
        '/^(-?\d+(?:\.\d+)?)\s*(?:°\s*)?(?:f|fahrenheit)\s+(?:to|in)\s+(?:°\s*)?(?:c|celsius)[\s?!.]*$/iu' => static fn(float $value): array => [($value - 32) * 5 / 9, '°C'],
        '/^(-?\d+(?:\.\d+)?)\s*(?:km|kilometers?|kilometres?)\s+(?:to|in)\s+(?:mi|miles?)[\s?!.]*$/iu' => static fn(float $value): array => [$value * 0.621371, 'mi'],
        '/^(-?\d+(?:\.\d+)?)\s*(?:mi|miles?)\s+(?:to|in)\s+(?:km|kilometers?|kilometres?)[\s?!.]*$/iu' => static fn(float $value): array => [$value / 0.621371, 'km'],
        '/^(-?\d+(?:\.\d+)?)\s*(?:kg|kilograms?)\s+(?:to|in)\s+(?:lb|lbs|pounds?)[\s?!.]*$/iu' => static fn(float $value): array => [$value * 2.20462262, 'lb'],
        '/^(-?\d+(?:\.\d+)?)\s*(?:lb|lbs|pounds?)\s+(?:to|in)\s+(?:kg|kilograms?)[\s?!.]*$/iu' => static fn(float $value): array => [$value / 2.20462262, 'kg'],
    ];
    foreach ($conversionPatterns as $pattern => $convert) {
        if (preg_match($pattern, $simplePrompt, $conversion)) {
            [$value, $unit] = $convert((float) $conversion[1]);
            $simpleReply = $formatNumber($value) . ' ' . $unit;
            break;
        }
    }
    if ($simpleReply !== null) {
        echo json_encode(['model' => 'jaguar-utility-fast-lane', 'adapter' => 'local', 'mode' => $mode, 'message' => $simpleReply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $weatherPattern = '/^(?:what(?:[’\']s| is)?\s+)?(?:the\s+)?(?:weather|temperature|temp|m[ée]t[ée]o|tiempo)(?:\s+(?:like\s+)?(?:in|for|à|en))\s+(.+?)[\s?!.]*$/iu';
    $timePattern = '/^(?:what\s+time\s+is\s+it|time|quelle\s+heure\s+est-il|hora)\s+(?:in|à|en)\s+(.+?)[\s?!.]*$/iu';
    $placePattern = '/^(?:where\s+is|find|locate|o[ùu]\s+est|d[óo]nde\s+est[áa])\s+(.+?)[\s?!.]*$/iu';
    if (preg_match($weatherPattern, $simplePrompt, $utilityMatch)) {
        $weatherPlace = trim($utilityMatch[1]);
        $stateOnly = in_array(mb_strtolower($weatherPlace), ['alabama','alaska','arizona','arkansas','california','colorado','connecticut','delaware','florida','georgia','hawaii','idaho','illinois','indiana','iowa','kansas','kentucky','louisiana','maine','maryland','massachusetts','michigan','minnesota','mississippi','missouri','montana','nebraska','nevada','new hampshire','new jersey','new mexico','new york','north carolina','north dakota','ohio','oklahoma','oregon','pennsylvania','rhode island','south carolina','south dakota','tennessee','texas','utah','vermont','virginia','washington','west virginia','wisconsin','wyoming'], true);
        $place = $stateOnly ? null : jaguar_geocode($weatherPlace);
        if ($stateOnly) {
            $simpleReply = match ($language) {
                'fr' => 'La météo varie beaucoup dans cet État. Indiquez une ville, par exemple « météo à Los Angeles, Californie ».',
                'es' => 'El tiempo varía mucho dentro de ese estado. Indica una ciudad, por ejemplo « tiempo en Los Ángeles, California ».',
                default => 'Weather varies widely across that state. Ask for a city, such as “weather in Los Angeles, California.”',
            };
        } elseif ($place === null) {
            $simpleReply = ['en' => 'I could not find that place. Try a city and country, such as “weather in Vancouver, Canada.”', 'fr' => 'Je n’ai pas trouvé ce lieu. Essayez une ville et un pays, par exemple « météo à Vancouver, Canada ».', 'es' => 'No pude encontrar ese lugar. Prueba una ciudad y país, por ejemplo « tiempo en Vancouver, Canadá ».'][$language];
        } else {
            $weather = jaguar_utility_cache('weather:' . $place['latitude'] . ':' . $place['longitude'], 600, static function () use ($place): ?array {
                return jaguar_utility_fetch('https://api.open-meteo.com/v1/forecast?' . http_build_query(['latitude' => $place['latitude'], 'longitude' => $place['longitude'], 'current' => 'temperature_2m,apparent_temperature,weather_code,wind_speed_10m', 'temperature_unit' => 'fahrenheit', 'wind_speed_unit' => 'mph', 'timezone' => 'auto']));
            });
            $current = is_array($weather['current'] ?? null) ? $weather['current'] : null;
            if ($current === null || !isset($current['temperature_2m'], $current['apparent_temperature'], $current['weather_code'], $current['wind_speed_10m'])) {
                $simpleReply = ['en' => 'Weather lookup is temporarily unavailable. Try again shortly.', 'fr' => 'La météo est temporairement indisponible. Réessayez bientôt.', 'es' => 'La consulta del tiempo no está disponible temporalmente. Inténtalo pronto.'][$language];
            } else {
                $locationLabel = $place['name'] . ($place['admin1'] !== '' ? ', ' . $place['admin1'] : '') . ($place['country'] !== '' ? ', ' . $place['country'] : '');
                $condition = jaguar_weather_label((int) $current['weather_code'], $language);
                $simpleReply = match ($language) {
                    'fr' => $locationLabel . ' : ' . $condition . ', ' . $formatNumber((float) $current['temperature_2m']) . ' °F (ressenti ' . $formatNumber((float) $current['apparent_temperature']) . ' °F), vent ' . $formatNumber((float) $current['wind_speed_10m']) . ' mph.',
                    'es' => $locationLabel . ': ' . $condition . ', ' . $formatNumber((float) $current['temperature_2m']) . ' °F (sensación ' . $formatNumber((float) $current['apparent_temperature']) . ' °F), viento ' . $formatNumber((float) $current['wind_speed_10m']) . ' mph.',
                    default => $locationLabel . ': ' . $condition . ', ' . $formatNumber((float) $current['temperature_2m']) . '°F (feels like ' . $formatNumber((float) $current['apparent_temperature']) . '°F), wind ' . $formatNumber((float) $current['wind_speed_10m']) . ' mph.',
                };
            }
        }
    } elseif (preg_match($timePattern, $simplePrompt, $utilityMatch) || preg_match($placePattern, $simplePrompt, $utilityMatch)) {
        $isTimeRequest = preg_match($timePattern, $simplePrompt) === 1;
        $place = jaguar_geocode($utilityMatch[1]);
        if ($place === null) {
            $simpleReply = ['en' => 'I could not find that place. Try a city and country.', 'fr' => 'Je n’ai pas trouvé ce lieu. Essayez une ville et un pays.', 'es' => 'No pude encontrar ese lugar. Prueba una ciudad y país.'][$language];
        } elseif ($isTimeRequest) {
            try {
                $clock = new DateTimeImmutable('now', new DateTimeZone($place['timezone']));
                $simpleReply = match ($language) {
                    'fr' => 'Il est ' . $clock->format('H:i') . ' à ' . $place['name'] . ' (' . $clock->format('T') . ').',
                    'es' => 'Son las ' . $clock->format('H:i') . ' en ' . $place['name'] . ' (' . $clock->format('T') . ').',
                    default => 'It is ' . $clock->format('g:i A') . ' in ' . $place['name'] . ' (' . $clock->format('T') . ').',
                };
            } catch (Throwable) {
                $simpleReply = ['en' => 'Time lookup is temporarily unavailable. Try again shortly.', 'fr' => 'L’heure est temporairement indisponible. Réessayez bientôt.', 'es' => 'La consulta de hora no está disponible temporalmente. Inténtalo pronto.'][$language];
            }
        } else {
            $parts = array_filter([$place['name'], $place['admin1'], $place['country']]);
            $simpleReply = implode(', ', $parts) . ' — ' . $formatNumber($place['latitude']) . '°, ' . $formatNumber($place['longitude']) . '°.';
        }
    }
    if ($simpleReply !== null) {
        echo json_encode(['model' => 'jaguar-utility-fast-lane', 'adapter' => 'open-meteo', 'mode' => $mode, 'message' => $simpleReply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

}
if ($mode === 'core' && $guide !== '') {
        $guideNames = ['chris' => 'Chris', 'dovi' => 'Dovi', 'moe' => 'Moe'];
        $guideName = $guideNames[$guide] ?? 'Daily Breath guide';
        echo json_encode(['model' => 'jaguar-dailybreath-fast-lane', 'adapter' => 'local', 'mode' => $mode, 'message' => $guideName . ' is available here for Daily Breath and sacred-text questions only. This no-GPU chat can offer concise, best-effort guidance from Jaguar’s built-in knowledge; for a specific passage, include its book, chapter, and verse.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
$runtimeUrl = rtrim(jaguar_runtime_config('runtime_url'), '/');
if ($runtimeUrl === '' || !filter_var($runtimeUrl, FILTER_VALIDATE_URL) || !function_exists('curl_init')) { http_response_code(503); echo json_encode(['error' => 'Jaguar is not available yet.']); exit; }
$runtimeToken = jaguar_runtime_config('runtime_token');
if ($runtimeToken === '') { http_response_code(503); echo json_encode(['error' => 'Jaguar runtime authentication is not configured.']); exit; }
$headers = ['Content-Type: application/json'];
$headers[] = 'Authorization: Bearer ' . $runtimeToken;
$request = curl_init($runtimeUrl . '/v1/chat');
// Leave enough time for a warm runtime, but return a usable error before the
// browser can appear permanently stuck while a cold runtime is unavailable.
$runtimeMode = (string)($modeDefinition['runtime'] ?? 'explain');
$runtimePayload = ['mode' => $runtimeMode, 'language' => $language, 'messages' => $messages];
try {
    $usageIdentity = jaguar_usage_identity($signedIn);
    $usageReservation = jaguar_usage_reserve(beyond_db(), $usageIdentity);
} catch (Throwable $exception) {
    error_log('Jaguar monthly usage ledger unavailable: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Jaguar usage protection is temporarily unavailable. Please try again later.']);
    exit;
}
if (!$usageReservation['allowed']) {
    http_response_code(429);
    echo json_encode([
        'error' => 'You have reached this month’s Jaguar model request allowance. Your usage resets next month.',
        'monthly_limit' => true,
        'usage' => jaguar_usage_public($usageReservation),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$releaseUsageReservation = static function () use ($usageIdentity, $usageReservation): void {
    try { jaguar_usage_release(beyond_db(), $usageIdentity, (string)$usageReservation['period']); }
    catch (Throwable $exception) { error_log('Jaguar monthly usage reservation release failed: ' . $exception->getMessage()); }
};
curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 105, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode($runtimePayload, JSON_THROW_ON_ERROR)]);
$response = curl_exec($request); $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
if (!is_string($response) || $status < 200 || $status >= 300) {
    $releaseUsageReservation();
    http_response_code(503);
    $error = $mode === 'build' && $status === 422
        ? 'Build preview is not enabled on the Jaguar runtime yet. Please try Explain or contact the Jaguar administrator.'
        : 'Jaguar could not complete that request.';
    echo json_encode(['error' => $error]);
    exit;
}
$runtimeResult = json_decode($response, true);
$runtimeUsage = is_array($runtimeResult) && is_array($runtimeResult['usage'] ?? null) ? $runtimeResult['usage'] : null;
if (!is_array($runtimeResult) || $runtimeUsage === null
    || !isset($runtimeUsage['input_tokens'], $runtimeUsage['output_tokens'], $runtimeUsage['gpu_seconds'])
    || !is_numeric($runtimeUsage['input_tokens']) || !is_numeric($runtimeUsage['output_tokens']) || !is_numeric($runtimeUsage['gpu_seconds'])
    || !is_finite((float)$runtimeUsage['gpu_seconds'])
    || (float)$runtimeUsage['gpu_seconds'] < 0 || (float)$runtimeUsage['gpu_seconds'] > 105
    || (float)$runtimeUsage['input_tokens'] < 0 || (float)$runtimeUsage['input_tokens'] > 8192
    || (float)$runtimeUsage['output_tokens'] < 0 || (float)$runtimeUsage['output_tokens'] > 1024) {
    try { jaguar_usage_consume_unmetered(beyond_db(), $usageIdentity, (string)$usageReservation['period']); }
    catch (Throwable $exception) { error_log('Jaguar completed request could not be recorded: ' . $exception->getMessage()); }
    http_response_code(503);
    echo json_encode(['error' => 'Jaguar usage metering is not ready on the model runtime. This completed model request counts toward your monthly allowance; contact support if this continues.']);
    exit;
}
try {
    $usage = jaguar_usage_settle(
        beyond_db(),
        $usageIdentity,
        (string)$usageReservation['period'],
        (int)$runtimeUsage['input_tokens'],
        (int)$runtimeUsage['output_tokens'],
        (float)$runtimeUsage['gpu_seconds']
    );
} catch (Throwable $exception) {
    error_log('Jaguar monthly usage settlement failed: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Jaguar could not record this request’s token usage. Your monthly request allowance was reserved.']);
    exit;
}
$walletBalance = null;
if ($signedIn) {
    try { $walletBalance = jaguar_wallet_bit_balance(beyond_db(), (int)$_SESSION['user_id']); }
    catch (Throwable $exception) { error_log('Jaguar BIT$ wallet lookup failed: ' . $exception->getMessage()); }
}
$runtimeResult['usage'] = jaguar_usage_public($usage, $walletBalance);
echo json_encode($runtimeResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
