<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$liveOauth = [];
try {
    $live = beyond_live_config();
    if (isset($live['oauth']) && is_array($live['oauth'])) {
        $liveOauth = $live['oauth'];
    }
} catch (Throwable $exception) {
    // Environment variables are sufficient for local/test installations.
}

$read = static function (string $env, string $provider, string $key, string $default = '') use ($liveOauth): string {
    $environment = getenv($env);
    if (is_string($environment) && trim($environment) !== '') return trim($environment);
    return trim((string)($liveOauth[$provider][$key] ?? $default));
};
return [
    'google' => [
        'client_id' => $read('BEYOND_GOOGLE_CLIENT_ID', 'google', 'client_id'),
        'client_secret' => $read('BEYOND_GOOGLE_CLIENT_SECRET', 'google', 'client_secret'),
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'scopes' => ['openid', 'email', 'profile'],
    ],
    'github' => [
        'client_id' => $read('BEYOND_GITHUB_CLIENT_ID', 'github', 'client_id'),
        'client_secret' => $read('BEYOND_GITHUB_CLIENT_SECRET', 'github', 'client_secret'),
        'authorize_url' => 'https://github.com/login/oauth/authorize',
        'token_url' => 'https://github.com/login/oauth/access_token',
        'userinfo_url' => 'https://api.github.com/user',
        'emails_url' => 'https://api.github.com/user/emails',
        'scopes' => ['read:user', 'user:email'],
    ],
    'apple' => [
        'client_id' => $read('BEYOND_APPLE_CLIENT_ID', 'apple', 'client_id'),
        // Apple calls this a client secret, but it is a signed JWT generated
        // from the Sign in with Apple private key and must be rotated.
        'client_secret' => $read('BEYOND_APPLE_CLIENT_SECRET', 'apple', 'client_secret'),
        'authorize_url' => 'https://appleid.apple.com/auth/authorize',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'userinfo_url' => '',
        'scopes' => ['name', 'email'],
    ],
];
