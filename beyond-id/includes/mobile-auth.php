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
    if ($secret === '') {
        throw new RuntimeException('Mobile authentication is unavailable until security.jwt_secret is configured.');
    }
    return $secret;
}

function beyond_mobile_token_audience(string $token): string
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) throw new RuntimeException('Invalid mobile token.');
    [$payload, $signature] = $parts;
    $expected = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    if (!hash_equals($expected, $signature)) throw new RuntimeException('Invalid mobile token signature.');
    $json = beyond_mobile_base64url_decode($payload);
    $claims = is_string($json) ? json_decode($json, true) : null;
    $audience = (string)($claims['aud'] ?? '');
    if (!is_array($claims) || (int)($claims['exp'] ?? 0) < time() || !in_array($audience, beyond_mobile_audiences(), true)) {
        throw new RuntimeException('Invalid mobile token claims.');
    }
    return $audience;
}

function beyond_mobile_audiences(): array
{
    return ['beyond-music-ios', 'beyond-tv-ios', 'french-quest-ios', 'daily-breath-ios'];
}

function beyond_mobile_issue_token(int $userId, int $ttl = 300, string $audience = 'beyond-music-ios', ?PDO $pdo = null): string
{
    $allowedAudiences = beyond_mobile_audiences();
    if (!in_array($audience, $allowedAudiences, true)) {
        $audience = 'beyond-music-ios';
    }

    if ($pdo === null) throw new RuntimeException('Mobile token storage is unavailable.');
    $jti = bin2hex(random_bytes(32));
    $expiresAt = time() + $ttl;
    $pdo->prepare('INSERT INTO mobile_access_tokens(jti,user_id,audience,expires_at,created_at) VALUES (?,?,?,?,?)')->execute([
        $jti, $userId, $audience, date('Y-m-d H:i:s', $expiresAt), date('Y-m-d H:i:s')
    ]);
    $payload = beyond_mobile_base64url(json_encode([
        'sub' => $userId,
        'iat' => time(),
        'exp' => $expiresAt,
        'aud' => $audience,
        'jti' => $jti,
    ], JSON_THROW_ON_ERROR));
    $signature = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    return $payload . '.' . $signature;
}

function beyond_mobile_verify_token(string $token, ?string $requiredAudience = null, ?PDO $pdo = null): array
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
    if (!is_array($claims) || (int)($claims['exp'] ?? 0) < time()) throw new RuntimeException('Mobile token expired.');
    $userId = (int)($claims['sub'] ?? 0);
    $audience = (string)($claims['aud'] ?? '');
    $jti = (string)($claims['jti'] ?? '');
    if ($userId <= 0 || !preg_match('/^[a-f0-9]{64}$/', $jti) || !in_array($audience, beyond_mobile_audiences(), true)) {
        throw new RuntimeException('Invalid mobile token claims.');
    }
    if ($requiredAudience !== null && !hash_equals($requiredAudience, $audience)) {
        throw new RuntimeException('Mobile token is not valid for this app.');
    }
    if ($pdo === null) throw new RuntimeException('Mobile token storage is unavailable.');
    $active = $pdo->prepare('SELECT 1 FROM mobile_access_tokens WHERE jti=? AND user_id=? AND audience=? AND revoked_at IS NULL AND expires_at>? LIMIT 1');
    $active->execute([$jti, $userId, $audience, date('Y-m-d H:i:s')]);
    if (!$active->fetchColumn()) throw new RuntimeException('Mobile token is revoked or expired.');
    return ['user_id' => $userId, 'audience' => $audience, 'jti' => $jti];
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
