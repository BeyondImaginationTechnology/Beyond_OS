<?php
declare(strict_types=1);
require_once __DIR__ . '/remember-me.php';
require_once __DIR__ . '/mobile-auth.php';

final class BeyondSocialUserException extends RuntimeException
{
}

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
    $headers = ['Accept: application/json', 'User-Agent: Beyond-ID/0.3'];
    if (!empty($options['access_token'])) $headers[] = 'Authorization: Bearer ' . $options['access_token'];
    foreach (($options['headers'] ?? []) as $header) {
        if (is_string($header) && $header !== '') $headers[] = $header;
    }
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
    } elseif ($provider === 'x') {
        $parameters['code_challenge'] = $codeChallenge;
        $parameters['code_challenge_method'] = 'S256';
    } elseif ($provider === 'instagram') {
        $parameters['scope'] = implode(',', $config['scopes'] ?? []);
        $parameters['enable_fb_login'] = '0';
    } elseif ($provider === 'apple') {
        $parameters['response_mode'] = 'form_post';
        $parameters['nonce'] = hash('sha256', $state);
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
    if (in_array($provider, ['google', 'x'], true)) $post['code_verifier'] = $codeVerifier;
    $options = ['post' => $post];
    if ($provider === 'x') {
        $options['headers'] = ['Authorization: Basic ' . base64_encode((string)$config['client_id'] . ':' . (string)$config['client_secret'])];
        unset($post['client_secret']);
        $options['post'] = $post;
    }
    return beyond_social_http($config['token_url'], $options);
}

function beyond_social_profile(string $provider, string $accessToken, array $tokens = []): array
{
    $config = beyond_social_config($provider);
    if ($provider === 'apple') {
        $claims = beyond_social_verify_apple_id_token(
            (string)($tokens['id_token'] ?? ''),
            (string)$config['client_id'],
            (string)($tokens['_expected_nonce'] ?? '')
        );
        $email = strtolower(trim((string)($claims['email'] ?? '')));
        return [
            'subject' => (string)($claims['sub'] ?? ''),
            'email' => $email,
            'email_verified' => filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => $email !== '' ? strtok($email, '@') : 'Apple member',
            'first_name' => '',
            'last_name' => '',
        ];
    }
    if ($provider === 'github') {
        $profile = beyond_social_http($config['userinfo_url'], ['access_token' => $accessToken]);
        $email = strtolower(trim((string)($profile['email'] ?? '')));
        $verified = false;
        if ($email !== '') {
            $emails = beyond_social_http($config['emails_url'], ['access_token' => $accessToken]);
            foreach ($emails as $candidate) {
                if (!is_array($candidate) || empty($candidate['verified'])) continue;
                $candidateEmail = strtolower(trim((string)($candidate['email'] ?? '')));
                if ($candidateEmail === $email) { $verified = true; break; }
            }
        } else {
            $emails = beyond_social_http($config['emails_url'], ['access_token' => $accessToken]);
            foreach ($emails as $candidate) {
                if (!is_array($candidate) || empty($candidate['verified'])) continue;
                $candidateEmail = strtolower(trim((string)($candidate['email'] ?? '')));
                if ($candidateEmail !== '' && (!empty($candidate['primary']) || $email === '')) $email = $candidateEmail;
                if (!empty($candidate['primary'])) break;
            }
            $verified = $email !== '';
        }
        $name = trim((string)($profile['name'] ?? $profile['login'] ?? 'GitHub member'));
        $parts = preg_split('/\s+/', $name, 2) ?: [];
        return [
            'subject' => (string)($profile['id'] ?? ''),
            'email' => $email,
            'email_verified' => $verified,
            'name' => $name,
            'first_name' => (string)($parts[0] ?? ''),
            'last_name' => (string)($parts[1] ?? ''),
        ];
    }
    if ($provider === 'instagram') {
        // Instagram Login returns an Instagram-scoped user_id, but the profile
        // lookup is intentionally made through /me. Calling /{user_id} with
        // this token can return Meta's misleading "Unsupported post request".
        // Keep this to the stable Instagram Login fields. Some newer API
        // versions reject account_type even though the account is valid.
        $url = rtrim((string)$config['userinfo_url'], '/')
            . '?' . http_build_query(['fields' => 'user_id,username'], '', '&', PHP_QUERY_RFC3986);
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
    if ($provider === 'x') {
        $profile = beyond_social_http($config['userinfo_url'] . '?' . http_build_query(['user.fields' => 'id,name,username,confirmed_email'], '', '&', PHP_QUERY_RFC3986), ['access_token' => $accessToken]);
        $data = is_array($profile['data'] ?? null) ? $profile['data'] : [];
        $username = trim((string)($data['username'] ?? ''));
        $email = strtolower(trim((string)($data['confirmed_email'] ?? '')));
        $emailValid = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        return [
            'subject' => (string)($data['id'] ?? ''),
            'email' => $emailValid ? $email : '',
            'email_verified' => $emailValid,
            'name' => trim((string)($data['name'] ?? '')) ?: ($username !== '' ? '@' . $username : 'X member'),
            'first_name' => '',
            'last_name' => '',
            'username' => $username,
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

function beyond_social_base64url_decode(string $value): string
{
    $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
    if ($decoded === false) throw new RuntimeException('The identity token is malformed.');
    return $decoded;
}

function beyond_social_asn1_length(int $length): string
{
    if ($length < 128) return chr($length);
    $bytes = ltrim(pack('N', $length), "\0");
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function beyond_social_asn1(string $tag, string $value): string
{
    return $tag . beyond_social_asn1_length(strlen($value)) . $value;
}

function beyond_social_rsa_jwk_pem(array $jwk): string
{
    $modulus = ltrim(beyond_social_base64url_decode((string)($jwk['n'] ?? '')), "\0");
    $exponent = ltrim(beyond_social_base64url_decode((string)($jwk['e'] ?? '')), "\0");
    if ($modulus === '' || $exponent === '') throw new RuntimeException('Apple returned an invalid signing key.');
    if ((ord($modulus[0]) & 0x80) !== 0) $modulus = "\0" . $modulus;
    if ((ord($exponent[0]) & 0x80) !== 0) $exponent = "\0" . $exponent;
    $rsa = beyond_social_asn1("\x30", beyond_social_asn1("\x02", $modulus) . beyond_social_asn1("\x02", $exponent));
    $algorithm = hex2bin('300d06092a864886f70d0101010500');
    $subjectPublicKey = beyond_social_asn1("\x30", $algorithm . beyond_social_asn1("\x03", "\0" . $rsa));
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($subjectPublicKey), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function beyond_social_verify_apple_id_token(string $token, string $clientId, string $expectedNonce): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) throw new BeyondSocialUserException('Apple did not return a valid identity token.');
    $header = json_decode(beyond_social_base64url_decode($parts[0]), true);
    $claims = json_decode(beyond_social_base64url_decode($parts[1]), true);
    if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256') {
        throw new BeyondSocialUserException('Apple returned an invalid identity token.');
    }
    $keys = beyond_social_http('https://appleid.apple.com/auth/keys');
    $matchingKey = null;
    foreach (($keys['keys'] ?? []) as $key) {
        if (is_array($key) && ($key['kid'] ?? '') === ($header['kid'] ?? '')) { $matchingKey = $key; break; }
    }
    if (!is_array($matchingKey) || openssl_verify($parts[0] . '.' . $parts[1], beyond_social_base64url_decode($parts[2]), beyond_social_rsa_jwk_pem($matchingKey), OPENSSL_ALGO_SHA256) !== 1) {
        throw new BeyondSocialUserException('Apple identity verification failed.');
    }
    $audience = $claims['aud'] ?? '';
    $audienceValid = is_array($audience) ? in_array($clientId, $audience, true) : hash_equals($clientId, (string)$audience);
    $nonceValid = $expectedNonce !== '' && hash_equals($expectedNonce, (string)($claims['nonce'] ?? ''));
    if (($claims['iss'] ?? '') !== 'https://appleid.apple.com' || !$audienceValid || !$nonceValid || (int)($claims['exp'] ?? 0) < time()) {
        throw new BeyondSocialUserException('Apple returned an expired or invalid identity token.');
    }
    return $claims;
}

function beyond_social_destination(array $flow, ?string $returnTo = null): string
{
    $mobileScheme = strtolower(trim((string)($flow['mobile_scheme'] ?? '')));
    if (beyond_api_client_for_scheme($mobileScheme) !== []) {
        $destination = '/beyond-id/auth/mobile-complete.php?scheme=' . rawurlencode($mobileScheme);
        $challenge = trim((string)($flow['mobile_code_challenge'] ?? ''));
        if ($challenge !== '' && preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge)) {
            $destination .= '&code_challenge=' . rawurlencode($challenge);
        }
        return $destination;
    }

    if ($returnTo === null) {
        $returnTo = is_string($_SESSION['beyond_return_to'] ?? null)
            ? $_SESSION['beyond_return_to']
            : null;
    }
    return safe_return_path($returnTo, '../dashboard/');
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
