<?php
declare(strict_types=1);

/**
 * Provider-neutral text routing. A provider declares its capabilities and
 * returns the same response shape regardless of its upstream API.
 */
function jaguar_text_provider_order(): array
{
    $configured = getenv('JAGUAR_TEXT_PROVIDER_ORDER');
    if (!is_string($configured) || trim($configured) === '') {
        $configured = (string)beyond_optional_config('jaguar.text_provider_order', 'google,private-runtime');
    }
    $providers = array_values(array_unique(array_filter(array_map(
        static fn(string $value): string => strtolower(trim($value)),
        preg_split('/[\s,]+/', $configured) ?: []
    ), static fn(string $value): bool => in_array($value, ['google', 'private-runtime'], true))));
    return $providers === [] ? ['private-runtime'] : $providers;
}

function jaguar_provider_catalog(): array
{
    return [
        'google' => [
            'capabilities' => ['grounded-text'],
            'model' => jaguar_provider_config('google', 'model', 'gemini_model') ?: 'gemini-2.5-flash',
        ],
        'private-runtime' => [
            'capabilities' => ['model-reasoning', 'build', 'code'],
            'model' => jaguar_provider_config('private-runtime', 'model') ?: 'configured private model',
        ],
    ];
}

/** Returns a normalized Google grounded answer, or null when unavailable. */
function jaguar_google_grounded_answer(string $prompt, string $language): ?array
{
    $key = jaguar_provider_config('google', 'api_key', 'gemini_api_key');
    if ($key === '' || !function_exists('curl_init')) return null;
    $model = jaguar_provider_config('google', 'model', 'gemini_model') ?: 'gemini-2.5-flash';
    if (!preg_match('/^[A-Za-z0-9._-]{1,100}$/', $model)) return null;
    $payload = [
        'systemInstruction' => ['parts' => [['text' => 'Answer the user clearly and concisely. Use Google Search when it improves accuracy. Do not claim to be Jaguar or invent sources.']]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'tools' => [['google_search' => (object)[]]],
        'generationConfig' => ['temperature' => 0.35, 'maxOutputTokens' => 700],
    ];
    $request = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($key));
    curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    $body = curl_exec($request); $status = (int)curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
    if (!is_string($body) || $status < 200 || $status >= 300) return null;
    $decoded = json_decode($body, true);
    $candidate = is_array($decoded['candidates'][0] ?? null) ? $decoded['candidates'][0] : null;
    $parts = is_array($candidate['content']['parts'] ?? null) ? $candidate['content']['parts'] : [];
    $text = trim(implode("\n", array_values(array_filter(array_map(static fn($part): string => is_array($part) ? trim((string)($part['text'] ?? '')) : '', $parts)))));
    if ($text === '') return null;
    $citations = [];
    foreach (($candidate['groundingMetadata']['groundingChunks'] ?? []) as $chunk) {
        $web = is_array($chunk['web'] ?? null) ? $chunk['web'] : [];
        $url = trim((string)($web['uri'] ?? ''));
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) $citations[] = ['title' => trim((string)($web['title'] ?? $url)), 'url' => $url];
    }
    return ['provider' => 'google', 'model' => $model, 'message' => $text, 'citations' => array_values(array_unique($citations, SORT_REGULAR))];
}

/**
 * Try non-metered providers only. GPU/private-runtime calls stay in chat.php
 * where request reservations and usage settlement are enforced.
 */
function jaguar_provider_answer(string $capability, string $prompt, string $language): ?array
{
    foreach (jaguar_text_provider_order() as $provider) {
        if ($provider === 'google' && $capability === 'grounded-text') {
            $answer = jaguar_google_grounded_answer($prompt, $language);
            if ($answer !== null) return $answer;
        }
    }
    return null;
}

function jaguar_private_runtime_endpoint(): array
{
    return [
        'url' => jaguar_provider_config('private-runtime', 'url', 'runtime_url'),
        'token' => jaguar_provider_config('private-runtime', 'token', 'runtime_token'),
        'provider' => 'private-runtime',
    ];
}
