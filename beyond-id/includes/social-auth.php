<?php
declare(strict_types=1);
require_once __DIR__ . '/remember-me.php';

function beyond_social_config(string $provider): array
{
    static $config;
    $config ??= require __DIR__ . '/../config/social-auth.php';
    return is_array($config[$provider] ?? null) ? $config[$provider] : [];
}

function beyond_social_enabled(string $provider): bool
{
    $config = beyond_social_config($provider);
    return ($config['client_id'] ?? '') !== '' && ($config['client_secret'] ?? '') !== '';
}

function beyond_social_callback_url(string $provider): string
{
    $app = require __DIR__ . '/../config/app.php';
    $callback = rtrim((string)$app['url'], '/') . '/auth/oauth-callback.php';
    return $provider === 'instagram' ? $callback : $callback . '?provider=' . rawurlencode($provider);
}

function beyond_social_http(string $url, array $options = []): array
{
    if (!extension_loaded('curl')) throw new RuntimeException('The cURL PHP extension is required for social sign-in.');
    $curl = curl_init($url);
    $headers = ['Accept: application/json'];
    if (!empty($options['access_token'])) $headers[] = 'Authorization: Bearer ' . $options['access_token'];
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => !empty($options['post']),
        CURLOPT_POSTFIELDS => !empty($options['post']) ? http_build_query($options['post'], '', '&', PHP_QUERY_RFC3986) : null,
    ]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $error !== '') throw new RuntimeException('Social provider request failed.');
    $json = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($json)) {
        $message = is_array($json) ? (string)($json['error_description'] ?? $json['error']['message'] ?? 'Provider rejected the request.') : 'Provider returned an invalid response.';
        throw new RuntimeException($message);
    }
    return $json;
}

function beyond_social_authorization_url(string $provider, string $state, string $codeChallenge): string
{
    $config = beyond_social_config($provider);
    $parameters = [
        'client_id' => $config['client_id'],
        'redirect_uri' => beyond_social_callback_url($provider),
        'response_type' => 'code',
        'scope' => implode(' ', $config['scopes'] ?? []),
        'state' => $state,
    ];
    if ($provider === 'google') {
        $parameters['code_challenge'] = $codeChallenge;
        $parameters['code_challenge_method'] = 'S256';
        $parameters['prompt'] = 'select_account';
    } elseif ($provider === 'instagram') {
        $parameters['scope'] = implode(',', $config['scopes'] ?? []);
        $parameters['enable_fb_login'] = '0';
    }
    return $config['authorize_url'] . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
}

function beyond_social_exchange_code(string $provider, string $code, string $codeVerifier): array
{
    $config = beyond_social_config($provider);
    $post = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => beyond_social_callback_url($provider),
        'code' => $code,
        'grant_type' => 'authorization_code',
    ];
    if ($provider === 'google') $post['code_verifier'] = $codeVerifier;
    return beyond_social_http($config['token_url'], ['post' => $post]);
}

function beyond_social_profile(string $provider, string $accessToken, array $tokens = []): array
{
    $config = beyond_social_config($provider);
    if ($provider === 'instagram') {
        // Instagram Login returns an Instagram-scoped user_id, but the profile
        // lookup is intentionally made through /me. Calling /{user_id} with
        // this token can return Meta's misleading "Unsupported post request".
        $url = rtrim((string)$config['userinfo_url'], '/')
            . '?' . http_build_query(['fields' => 'user_id,username,account_type'], '', '&', PHP_QUERY_RFC3986);
        $profile = beyond_social_http($url, ['access_token' => $accessToken]);
        $username = trim((string)($profile['username'] ?? ''));
        return [
            'subject' => (string)($profile['user_id'] ?? $profile['id'] ?? $tokens['user_id'] ?? ''),
            'email' => '',
            'email_verified' => false,
            'name' => $username !== '' ? '@' . $username : 'Instagram member',
            'first_name' => $username,
            'last_name' => '',
            'username' => $username,
            'account_type' => trim((string)($profile['account_type'] ?? '')),
        ];
    }
    if ($provider === 'meta') {
        $url = $config['userinfo_url'] . '?' . http_build_query(['fields' => 'id,name,first_name,last_name,email'], '', '&', PHP_QUERY_RFC3986);
        $profile = beyond_social_http($url, ['access_token' => $accessToken]);
        return [
            'subject' => (string)($profile['id'] ?? ''),
            'email' => strtolower(trim((string)($profile['email'] ?? ''))),
            'email_verified' => !empty($profile['email']),
            'name' => trim((string)($profile['name'] ?? '')),
            'first_name' => trim((string)($profile['first_name'] ?? '')),
            'last_name' => trim((string)($profile['last_name'] ?? '')),
        ];
    }
    $profile = beyond_social_http($config['userinfo_url'], ['access_token' => $accessToken]);
    return [
        'subject' => (string)($profile['sub'] ?? ''),
        'email' => strtolower(trim((string)($profile['email'] ?? ''))),
        'email_verified' => filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'name' => trim((string)($profile['name'] ?? '')),
        'first_name' => trim((string)($profile['given_name'] ?? '')),
        'last_name' => trim((string)($profile['family_name'] ?? '')),
    ];
}

function beyond_social_login_session(PDO $pdo, array $user, string $provider, ?string $destinationOverride = null): never
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['email'] = (string)$user['email'];
    $_SESSION['name'] = (string)($user['name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
    $_SESSION['role'] = (string)($user['role'] ?? 'user');
    $_SESSION['locale'] = (string)($user['preferred_locale'] ?? 'en');
    $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => (string)$user['email'], 'role' => $_SESSION['role']];
    register_session($pdo, (int)$user['id']);
    beyondRememberForget($pdo);
    beyondRememberIssue($pdo, (int)$user['id']);
    try { $pdo->prepare('UPDATE users SET last_login_at=?,last_login_ip=? WHERE id=?')->execute([date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? null, $user['id']]); } catch (Throwable $exception) {}
    log_activity($pdo, (int)$user['id'], 'oauth_login_' . $provider);
    $destination = $destinationOverride !== null
        ? safe_return_path($destinationOverride, '../dashboard/')
        : safe_return_path($_SESSION['beyond_return_to'] ?? null, '../dashboard/');
    unset($_SESSION['beyond_return_to']);
    header('Location: ' . $destination);
    exit;
}
