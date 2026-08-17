<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

function beyond_mobile_base64url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function beyond_mobile_base64url_decode(string $value): string|false
{
    $padded = strtr($value, '-_', '+/');
    $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
    return base64_decode($padded, true);
}

function beyond_mobile_secret(): string
{
    $secret = (string)beyond_config('security.jwt_secret', '');
    return $secret !== '' ? $secret : hash('sha256', __DIR__);
}

function beyond_mobile_audiences(): array
{
    return ['beyond-music-ios', 'beyond-tv-ios', 'french-quest-ios'];
}

function beyond_mobile_issue_token(int $userId, int $ttl = 300, string $audience = 'beyond-music-ios'): string
{
    $allowedAudiences = beyond_mobile_audiences();
    if (!in_array($audience, $allowedAudiences, true)) {
        $audience = 'beyond-music-ios';
    }

    $payload = beyond_mobile_base64url(json_encode([
        'sub' => $userId,
        'iat' => time(),
        'exp' => time() + $ttl,
        'aud' => $audience,
    ], JSON_THROW_ON_ERROR));
    $signature = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    return $payload . '.' . $signature;
}

function beyond_mobile_verify_token(string $token, ?string $requiredAudience = null): int
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        throw new RuntimeException('Invalid mobile token.');
    }
    [$payload, $signature] = $parts;
    $expected = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    if (!hash_equals($expected, $signature)) {
        throw new RuntimeException('Invalid mobile token signature.');
    }
    $json = beyond_mobile_base64url_decode($payload);
    $claims = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($claims) || (int)($claims['exp'] ?? 0) < time()) {
        throw new RuntimeException('Mobile token expired.');
    }
    $userId = (int)($claims['sub'] ?? 0);
    $audience = (string)($claims['aud'] ?? '');
    if ($userId <= 0 || !in_array($audience, beyond_mobile_audiences(), true)) {
        throw new RuntimeException('Invalid mobile token claims.');
    }
    if ($requiredAudience !== null && !hash_equals($requiredAudience, $audience)) {
        throw new RuntimeException('Mobile token is not valid for this app.');
    }
    return $userId;
}

function beyond_mobile_bearer_token(): string
{
    $authorization = trim((string)(
        $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? ''
    ));
    if ($authorization === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authorization = trim((string)($headers['Authorization'] ?? $headers['authorization'] ?? ''));
    }
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        throw new RuntimeException('A mobile bearer token is required.');
    }
    return trim($matches[1]);
}
