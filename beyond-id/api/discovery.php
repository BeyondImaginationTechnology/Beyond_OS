<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$issuer = beyond_mobile_issuer();
$clients = [];
foreach (beyond_api_clients() as $audience => $client) {
    $clients[] = [
        'client_id' => $audience,
        'name' => (string)($client['name'] ?? $audience),
        'callback_scheme' => (string)($client['scheme'] ?? ''),
        'scopes' => array_values($client['scopes'] ?? []),
    ];
}

echo json_encode([
    'issuer' => $issuer,
    'authorization_endpoint' => $issuer . '/auth/login.php',
    'social_authorization_endpoint' => $issuer . '/auth/oauth-start.php',
    'token_endpoint' => $issuer . '/api/mobile-token.php',
    'refresh_endpoint' => $issuer . '/api/mobile-token-refresh.php',
    'userinfo_endpoint' => $issuer . '/api/me.php',
    'userinfo_endpoint_v1' => $issuer . '/api/v1/me.php',
    'session_endpoint' => $issuer . '/api/mobile-session.php',
    'revocation_endpoint' => $issuer . '/api/mobile-token-revoke.php',
    'api_style' => 'Beyond ID first-party mobile API',
    'api_versions_supported' => ['0.3', '1.0'],
    'api_documentation' => $issuer . '/api/v1/openapi.yaml',
    'grant_types_supported' => ['authorization_code'],
    'code_challenge_methods_supported' => ['S256'],
    'token_endpoint_auth_methods_supported' => ['none'],
    'clients' => $clients,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
