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
$instagramGraphVersion = preg_replace('/[^A-Za-z0-9.]/', '', $read('BEYOND_INSTAGRAM_GRAPH_VERSION', 'instagram', 'graph_version', 'v23.0')) ?: 'v23.0';

return [
    'google' => [
        'client_id' => $read('BEYOND_GOOGLE_CLIENT_ID', 'google', 'client_id'),
        'client_secret' => $read('BEYOND_GOOGLE_CLIENT_SECRET', 'google', 'client_secret'),
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'scopes' => ['openid', 'email', 'profile'],
    ],
    'meta' => [
        'client_id' => $read('BEYOND_META_APP_ID', 'meta', 'app_id'),
        'client_secret' => $read('BEYOND_META_APP_SECRET', 'meta', 'app_secret'),
        'authorize_url' => 'https://www.facebook.com/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/oauth/access_token',
        'userinfo_url' => 'https://graph.facebook.com/me',
        'scopes' => ['email', 'public_profile'],
    ],
    'instagram' => [
        'client_id' => $read('BEYOND_INSTAGRAM_APP_ID', 'instagram', 'app_id'),
        'client_secret' => $read('BEYOND_INSTAGRAM_APP_SECRET', 'instagram', 'app_secret'),
        'authorize_url' => 'https://www.instagram.com/oauth/authorize',
        'token_url' => 'https://api.instagram.com/oauth/access_token',
        'userinfo_url' => 'https://graph.instagram.com/' . $instagramGraphVersion . '/me',
        'scopes' => ['instagram_business_basic'],
    ],
];
