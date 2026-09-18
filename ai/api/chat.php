<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/modes.php';
require_once __DIR__ . '/../../beyond-id/includes/mobile-auth.php';

function jaguar_nonce_secret(): string
{
    return trim((string) (getenv('JAGUAR_NONCE_SECRET') ?: beyond_config('security.jaguar_nonce_secret', beyond_config('security.jwt_secret', ''))));
}

function jaguar_rotate_nonce(): void
{
    $value = bin2hex(random_bytes(24));
    $issuedAt = time();
    $_SESSION['jaguar_nonce'] = ['value' => $value, 'issued_at' => $issuedAt, 'used' => false];
    $secret = jaguar_nonce_secret();
    if ($secret === '') return;
    header('X-Jaguar-Nonce: ' . $value);
    header('X-Jaguar-Nonce-Issued-At: ' . $issuedAt);
    header('X-Jaguar-Nonce-Signature: ' . hash_hmac('sha256', $value . ':' . $issuedAt, $secret));
}

function jaguar_verify_nonce(array $payload): bool
{
    $secret = jaguar_nonce_secret();
    $nonce = is_string($payload['nonce'] ?? null) ? trim($payload['nonce']) : '';
    $signature = is_string($payload['nonce_signature'] ?? null) ? trim($payload['nonce_signature']) : '';
    $issuedAt = filter_var($payload['nonce_issued_at'] ?? null, FILTER_VALIDATE_INT);
    $sessionNonce = is_array($_SESSION['jaguar_nonce'] ?? null) ? $_SESSION['jaguar_nonce'] : [];
    if ($secret === '' || !is_string($issuedAt) && !is_int($issuedAt) || $nonce === '' || !preg_match('/^[a-f0-9]{48}$/', $nonce) || !preg_match('/^[a-f0-9]{64}$/', $signature)) return false;
    if ((int)$issuedAt < time() - 900 || (int)$issuedAt > time() + 60 || !empty($sessionNonce['used']) || !hash_equals((string)($sessionNonce['value'] ?? ''), $nonce) || (int)($sessionNonce['issued_at'] ?? 0) !== (int)$issuedAt) return false;
    $valid = hash_equals(hash_hmac('sha256', $nonce . ':' . $issuedAt, $secret), $signature);
    if ($valid) $_SESSION['jaguar_nonce']['used'] = true;
    return $valid;
}

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
        $response = jaguar_utility_fetch('https://geocoding-api.open-meteo.com/v1/search?' . http_build_query(['name' => $place, 'count' => 1, 'language' => 'en', 'format' => 'json']));
        $result = $response['results'][0] ?? null;
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
$mobileClaims = null;
$authorization = beyond_mobile_authorization_header();
if ($authorization !== '') {
    try {
        $token = beyond_mobile_bearer_token();
        $mobileClaims = beyond_mobile_verify_token($token, 'daily-breath-ios', beyond_db());
        beyond_mobile_require_scope($mobileClaims, 'profile:read');
        $_SESSION['user_id'] = (int)$mobileClaims['user_id'];
    } catch (Throwable $exception) {
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Jaguar", error="invalid_token"');
        echo json_encode(['error' => 'DailyBreath sign-in is invalid or expired.']);
        exit;
    }
} elseif (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403); echo json_encode(['error' => 'Your secure session expired. Refresh Jaguar and try again.']); exit;
}
$signedIn = $mobileClaims !== null || !empty($_SESSION['user_id']);
if (!$signedIn && jaguar_nonce_secret() === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Guest chat is temporarily unavailable. Sign in with Beyond ID to continue.']);
    exit;
}
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
    if (!jaguar_verify_nonce($payload)) { http_response_code(403); echo json_encode(['error' => 'Security check expired. Refresh Jaguar and try again.']); exit; }
    jaguar_rotate_nonce();
}
$mode = is_string($payload['mode'] ?? null) ? strtolower(trim($payload['mode'])) : 'core';
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
$originalPrompt = trim((string) $lastMessage['content']);
$simplePrompt = mb_strtolower($originalPrompt);
$guide = is_string($payload['guide'] ?? null) ? strtolower(trim($payload['guide'])) : '';
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
$simpleReply = null;
$simpleCopy = [
    'en' => ['hello' => 'Hello! I’m Jaguar. What would you like to explore?', 'thanks' => 'You’re welcome. What should we explore next?', 'acknowledgement' => 'I’m here when you’re ready. What should we explore?', 'help' => 'I’m Jaguar, Beyond’s AI assistant. Core can explain ideas, shape plans, work through code, and teach difficult topics in plain language. Build, Draw, and Video are locked while we finish them.', 'version' => 'You’re using Jaguar v0.3 Preview.'],
    'fr' => ['hello' => 'Bonjour ! Je suis Jaguar. Qu’aimeriez-vous explorer ?', 'thanks' => 'Avec plaisir. Qu’allons-nous explorer ensuite ?', 'acknowledgement' => 'Je suis là quand vous êtes prêt. Qu’allons-nous explorer ?', 'help' => 'Je suis Jaguar, l’assistant IA de Beyond. Core peut expliquer des idées, structurer des projets, travailler sur du code et simplifier des sujets difficiles. Build, Dessiner et Vidéo restent verrouillés pendant leur préparation.', 'version' => 'Vous utilisez Jaguar v0.3 Preview.'],
    'es' => ['hello' => '¡Hola! Soy Jaguar. ¿Qué te gustaría explorar?', 'thanks' => 'De nada. ¿Qué exploramos ahora?', 'acknowledgement' => 'Estoy aquí cuando estés listo. ¿Qué exploramos?', 'help' => 'Soy Jaguar, el asistente de IA de Beyond. Core puede explicar ideas, organizar proyectos, trabajar con código y enseñar temas difíciles con palabras sencillas. Build, Dibujar y Video permanecen bloqueados mientras los terminamos.', 'version' => 'Estás usando Jaguar v0.3 Preview.'],
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
        $place = jaguar_geocode($utilityMatch[1]);
        if ($place === null) {
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

    $coreCopy = [
        'en' => [
            'json' => 'JSON is a lightweight text format for structured data. Example: {"name":"Jaguar","mode":"Core"}.',
            'loop' => 'A loop repeats work. In JavaScript: for (let i = 0; i < 3; i++) { console.log(i); }',
            'variable' => 'A variable stores a value you can reuse. In JavaScript: const name = "Jaguar";.',
            'api' => 'An API is a defined way for software to request data or an action from another service.',
            'html' => 'HTML gives a web page its structure and content, such as headings, paragraphs, and buttons.',
            'css' => 'CSS controls how a web page looks: layout, colors, spacing, and responsive design.',
            'javascript' => 'JavaScript makes a web page interactive: it can respond to clicks, update content, and call APIs.',
            'sql' => 'SQL is a language for reading and changing data in relational databases.',
            'git' => 'Git tracks changes to code so people can review, share, and safely restore versions.',
            'url' => 'A URL is a web address that points to a page or resource, such as https://example.com.',
            'boolean' => 'A Boolean is a value with only two states: true or false.',
            'array' => 'An array is an ordered list of values. In JavaScript: ["Core", "Build", "Draw"].',
            'function' => 'A function is reusable named code that performs a task, often using inputs and returning a result.',
        ],
        'fr' => [
            'json' => 'JSON est un format texte léger pour des données structurées. Exemple : {"nom":"Jaguar","mode":"Core"}.',
            'loop' => 'Une boucle répète une action. En JavaScript : for (let i = 0; i < 3; i++) { console.log(i); }',
            'variable' => 'Une variable stocke une valeur réutilisable. En JavaScript : const nom = "Jaguar";.',
            'api' => 'Une API est une manière définie pour un logiciel de demander des données ou une action à un autre service.',
            'html' => 'HTML donne à une page web sa structure et son contenu : titres, paragraphes et boutons.',
            'css' => 'CSS contrôle l’apparence d’une page web : mise en page, couleurs, espacements et adaptation mobile.',
            'javascript' => 'JavaScript rend une page web interactive : clics, contenu dynamique et appels API.',
            'sql' => 'SQL est un langage pour lire et modifier des données dans des bases relationnelles.',
            'git' => 'Git suit les changements du code afin de les relire, partager et restaurer des versions.',
            'url' => 'Une URL est une adresse web qui pointe vers une page ou une ressource, par exemple https://example.com.',
            'boolean' => 'Un booléen ne possède que deux états : vrai ou faux.',
            'array' => 'Un tableau est une liste ordonnée de valeurs. En JavaScript : ["Core", "Build", "Draw"].',
            'function' => 'Une fonction est du code réutilisable nommé qui exécute une tâche, souvent avec des entrées et un résultat.',
        ],
        'es' => [
            'json' => 'JSON es un formato de texto ligero para datos estructurados. Ejemplo: {"nombre":"Jaguar","modo":"Core"}.',
            'loop' => 'Un bucle repite una tarea. En JavaScript: for (let i = 0; i < 3; i++) { console.log(i); }',
            'variable' => 'Una variable guarda un valor reutilizable. En JavaScript: const nombre = "Jaguar";.',
            'api' => 'Una API es una forma definida para que un programa solicite datos o una acción a otro servicio.',
            'html' => 'HTML da a una página web su estructura y contenido: títulos, párrafos y botones.',
            'css' => 'CSS controla cómo se ve una página web: diseño, colores, espaciado y adaptación a pantallas.',
            'javascript' => 'JavaScript vuelve una página web interactiva: responde a clics, actualiza contenido y llama APIs.',
            'sql' => 'SQL es un lenguaje para leer y modificar datos en bases de datos relacionales.',
            'git' => 'Git registra cambios de código para revisarlos, compartirlos y recuperar versiones con seguridad.',
            'url' => 'Una URL es una dirección web que apunta a una página o recurso, como https://example.com.',
            'boolean' => 'Un booleano solo tiene dos estados: verdadero o falso.',
            'array' => 'Un arreglo es una lista ordenada de valores. En JavaScript: ["Core", "Build", "Draw"].',
            'function' => 'Una función es código reutilizable con nombre que realiza una tarea, a menudo recibe entradas y devuelve un resultado.',
        ],
    ];
    $definitionTerms = [
        'json' => 'json', 'loop' => '(?:a\\s+)?(?:loop|for loop)', 'variable' => '(?:a\\s+)?variable',
        'api' => '(?:an?\\s+)?api', 'html' => 'html', 'css' => 'css', 'javascript' => 'javascript',
        'sql' => 'sql', 'git' => 'git', 'url' => '(?:a\\s+)?url', 'boolean' => '(?:a\\s+)?boolean',
        'array' => '(?:an?\\s+)?array', 'function' => '(?:a\\s+)?function',
    ];
    foreach ($definitionTerms as $term => $expression) {
        if (preg_match('/^(?:what(?:\\s+is)?|define|explain)\\s+' . $expression . '[\\s?!.]*$/iu', $simplePrompt)) {
            $simpleReply = $coreCopy[$language][$term];
            break;
        }
    }
    if ($simpleReply === null && preg_match('/^what does https? mean[\s?!.]*$/iu', $simplePrompt)) {
        $simpleReply = [
            'en' => 'HTTPS is the secure version of HTTP. It encrypts the connection between your browser and a website.',
            'fr' => 'HTTPS est la version sécurisée de HTTP. Il chiffre la connexion entre votre navigateur et un site web.',
            'es' => 'HTTPS es la versión segura de HTTP. Cifra la conexión entre tu navegador y un sitio web.',
        ][$language];
    } elseif ($simpleReply === null && preg_match('/\b(401|403|404|500|503)\b/', $simplePrompt, $httpStatus)) {
        $statusHelp = [
            '401' => ['en' => '401 means authentication is required or invalid.', 'fr' => '401 signifie que l’authentification est requise ou invalide.', 'es' => '401 significa que la autenticación es obligatoria o no es válida.'],
            '403' => ['en' => '403 means the server understood the request but refuses access.', 'fr' => '403 signifie que le serveur refuse l’accès.', 'es' => '403 significa que el servidor rechaza el acceso.'],
            '404' => ['en' => '404 means the requested page or API route was not found.', 'fr' => '404 signifie que la page ou route API demandée est introuvable.', 'es' => '404 significa que no se encontró la página o ruta de API solicitada.'],
            '500' => ['en' => '500 means the server hit an unexpected internal error.', 'fr' => '500 signifie que le serveur a rencontré une erreur interne inattendue.', 'es' => '500 significa que el servidor encontró un error interno inesperado.'],
            '503' => ['en' => '503 means the service is temporarily unavailable; retry shortly.', 'fr' => '503 signifie que le service est temporairement indisponible ; réessayez bientôt.', 'es' => '503 significa que el servicio no está disponible temporalmente; inténtalo pronto.'],
        ];
        $simpleReply = $statusHelp[$httpStatus[1]][$language];
    }
    if ($simpleReply !== null) {
        echo json_encode(['model' => 'jaguar-core-fast-lane', 'adapter' => null, 'mode' => $mode, 'message' => $simpleReply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
if ($mode === 'core') {
    echo json_encode(['model' => 'jaguar-core-fast-lane', 'adapter' => 'local', 'mode' => $mode, 'message' => 'Explain handles fast-lane utilities and concise built-in guidance. Deep thinking is available in a separate Jaguar mode.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
$runtimeMode = $mode === 'core' ? 'explain' : $mode;
curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 105, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode(['mode' => $runtimeMode, 'language' => $language, 'messages' => $messages], JSON_THROW_ON_ERROR)]);
$response = curl_exec($request); $status = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
if (!is_string($response) || $status < 200 || $status >= 300) { http_response_code(503); echo json_encode(['error' => 'Jaguar could not complete that request.']); exit; }
echo $response;




