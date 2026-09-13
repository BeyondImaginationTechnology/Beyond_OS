<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

function beyond_api_clients(): array
{
    static $clients;
    $clients ??= require __DIR__ . '/../config/api-clients.php';
    return is_array($clients) ? $clients : [];
}

function beyond_api_client(string $audience): array
{
    $client = beyond_api_clients()[$audience] ?? null;
    return is_array($client) ? $client : [];
}

function beyond_api_client_for_scheme(string $scheme): array
{
    foreach (beyond_api_clients() as $audience => $client) {
        if (is_array($client) && hash_equals((string)($client['scheme'] ?? ''), $scheme)) {
            return ['audience' => $audience] + $client;
        }
    }
    return [];
}

function beyond_api_audiences_for_app(string $appSlug): array
{
    $audiences = [];
    foreach (beyond_api_clients() as $audience => $client) {
        if (is_array($client) && hash_equals((string)($client['app_slug'] ?? ''), $appSlug)) {
            $audiences[] = (string)$audience;
        }
    }
    return $audiences;
}

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
    if (strlen($secret) < 32 || str_contains(strtolower($secret), 'replace-with')) {
        throw new RuntimeException('Mobile authentication is unavailable until a strong security.jwt_secret is configured.');
    }
    return $secret;
}

function beyond_mobile_issuer(): string
{
    $issuer = rtrim((string)beyond_config('security.api_issuer', 'https://beyondimagination.co.technology/beyond-id'), '/');
    $parts = parse_url($issuer);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        throw new RuntimeException('Mobile authentication requires a canonical HTTPS security.api_issuer.');
    }
    return $issuer;
}

function beyond_mobile_decode_token(string $token): array
{
    if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token)) {
        throw new RuntimeException('Invalid mobile token.');
    }
    [$payload, $signature] = explode('.', $token, 2);
    $expected = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    if (!hash_equals($expected, $signature)) throw new RuntimeException('Invalid mobile token signature.');
    $json = beyond_mobile_base64url_decode($payload);
    $claims = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($claims)) throw new RuntimeException('Invalid mobile token claims.');

    $now = time();
    $issuedAt = (int)($claims['iat'] ?? 0);
    $expiresAt = (int)($claims['exp'] ?? 0);
    $audience = (string)($claims['aud'] ?? '');
    if ($issuedAt <= 0 || $issuedAt > $now + 60 || $expiresAt <= $now || $expiresAt > $issuedAt + 86400
        || !in_array($audience, beyond_mobile_audiences(), true)) {
        throw new RuntimeException('Invalid mobile token claims.');
    }
    if (isset($claims['iss']) && !hash_equals(beyond_mobile_issuer(), (string)$claims['iss'])) {
        throw new RuntimeException('Invalid mobile token issuer.');
    }
    if (isset($claims['typ']) && !hash_equals('at+beyond-id', (string)$claims['typ'])) {
        throw new RuntimeException('Invalid mobile token type.');
    }
    return $claims;
}

function beyond_mobile_token_audience(string $token): string
{
    return (string)beyond_mobile_decode_token($token)['aud'];
}

function beyond_mobile_audiences(): array
{
    return array_keys(beyond_api_clients());
}

function beyond_mobile_scopes(string $audience): array
{
    $scopes = beyond_api_client($audience)['scopes'] ?? [];
    return is_array($scopes) ? array_values(array_unique(array_map('strval', $scopes))) : [];
}

function beyond_mobile_issue_token(int $userId, int $ttl = 300, string $audience = 'beyond-music-ios', ?PDO $pdo = null): string
{
    $allowedAudiences = beyond_mobile_audiences();
    if (!in_array($audience, $allowedAudiences, true)) {
        throw new RuntimeException('Unknown mobile token audience.');
    }

    if ($pdo === null) throw new RuntimeException('Mobile token storage is unavailable.');
    $ttl = max(60, min(3600, $ttl));
    $jti = bin2hex(random_bytes(32));
    $issuedAt = time();
    $expiresAt = $issuedAt + $ttl;
    $pdo->prepare('INSERT INTO mobile_access_tokens(jti,user_id,audience,expires_at,created_at) VALUES (?,?,?,?,?)')->execute([
        $jti, $userId, $audience, date('Y-m-d H:i:s', $expiresAt), date('Y-m-d H:i:s')
    ]);
    $payload = beyond_mobile_base64url(json_encode([
        'sub' => $userId,
        'iat' => $issuedAt,
        'exp' => $expiresAt,
        'aud' => $audience,
        'jti' => $jti,
        'iss' => beyond_mobile_issuer(),
        'typ' => 'at+beyond-id',
        'scp' => beyond_mobile_scopes($audience),
    ], JSON_THROW_ON_ERROR));
    $signature = beyond_mobile_base64url(hash_hmac('sha256', $payload, beyond_mobile_secret(), true));
    return $payload . '.' . $signature;
}

function beyond_mobile_verify_token(string $token, ?string $requiredAudience = null, ?PDO $pdo = null): array
{
    $claims = beyond_mobile_decode_token($token);
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
    $active = $pdo->prepare("SELECT 1 FROM mobile_access_tokens t INNER JOIN users u ON u.id=t.user_id WHERE t.jti=? AND t.user_id=? AND t.audience=? AND t.revoked_at IS NULL AND t.expires_at>? AND u.status='active' LIMIT 1");
    $active->execute([$jti, $userId, $audience, date('Y-m-d H:i:s')]);
    if (!$active->fetchColumn()) throw new RuntimeException('Mobile token is revoked or expired.');

    $registeredScopes = beyond_mobile_scopes($audience);
    $tokenScopes = is_array($claims['scp'] ?? null)
        ? array_values(array_filter($claims['scp'], 'is_string'))
        : $registeredScopes;
    $scopes = array_values(array_intersect(array_unique($tokenScopes), $registeredScopes));
    $client = beyond_api_client($audience);
    $appSlug = (string)($client['app_slug'] ?? '');
    if ($appSlug !== '') {
        $connection = $pdo->prepare('SELECT permissions_json,revoked_at FROM connected_apps WHERE user_id=? AND app_slug=? LIMIT 1');
        $connection->execute([$userId, $appSlug]);
        $connectionRow = $connection->fetch(PDO::FETCH_ASSOC);
        if (is_array($connectionRow) && !empty($connectionRow['revoked_at'])) {
            throw new RuntimeException('App access has been revoked.');
        }
        if (is_array($connectionRow)) {
            $grantedScopes = json_decode((string)($connectionRow['permissions_json'] ?? '[]'), true);
            if (is_array($grantedScopes)) {
                $scopes = array_values(array_intersect($scopes, array_map('strval', $grantedScopes)));
            }
        }
    }

    return ['user_id' => $userId, 'audience' => $audience, 'jti' => $jti, 'scopes' => $scopes];
}

function beyond_mobile_require_scope(array $claims, string $scope): void
{
    if (!in_array($scope, $claims['scopes'] ?? [], true)) {
        throw new RuntimeException('Mobile token does not grant the required scope.');
    }
}

function beyond_mobile_authorization_header(): string
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
    return $authorization;
}

function beyond_mobile_bearer_token(): string
{
    $authorization = beyond_mobile_authorization_header();
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        throw new RuntimeException('A mobile bearer token is required.');
    }
    return trim($matches[1]);
}
