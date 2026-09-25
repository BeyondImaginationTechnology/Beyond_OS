<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/social-auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');

$provider = strtolower(trim((string)($_GET['provider'] ?? '')));
if ($provider === 'facebook') $provider = 'meta';
if (!in_array($provider, ['google', 'github', 'apple', 'x'], true) || !beyond_social_enabled($provider)) {
    $_SESSION['oauth_error'] = 'That social sign-in provider is not configured yet.';
    header('Location: login.php');
    exit;
}
$returnTo = safe_return_path((string)($_GET['return'] ?? ''), '');
$requestedScheme = strtolower(trim((string)($_GET['scheme'] ?? '')));
$codeChallenge = '';
if ($requestedScheme === '' && $returnTo !== '') {
    parse_str((string)parse_url($returnTo, PHP_URL_QUERY), $returnQuery);
    $requestedScheme = strtolower(trim((string)($returnQuery['scheme'] ?? '')));
    $codeChallenge = trim((string)($returnQuery['code_challenge'] ?? ''));
}
$mobileScheme = beyond_api_client_for_scheme($requestedScheme) !== [] ? $requestedScheme : '';
if ($codeChallenge !== '' && !preg_match('/^[A-Za-z0-9_-]{43,128}$/', $codeChallenge)) $codeChallenge = '';
if ($returnTo !== '') $_SESSION['beyond_return_to'] = $returnTo;
$state = bin2hex(random_bytes(32));
$verifier = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
$isSecureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if ($provider === 'apple' && !$isSecureRequest) {
    $_SESSION['oauth_error'] = 'Apple sign-in requires HTTPS.';
    header('Location: login.php');
    exit;
}
$_SESSION['oauth_flow'] = [
    'provider' => $provider,
    'state' => $state,
    'verifier' => $verifier,
    'created_at' => time(),
    'mobile_scheme' => $mobileScheme,
    'mobile_code_challenge' => $codeChallenge,
];
if ($provider === 'apple') {
    // Apple returns authorization data with a cross-site POST. Reissue this
    // short-lived session cookie with SameSite=None so the state check survives.
    setcookie(session_name(), session_id(), [
        'expires' => 0,
        'path' => '/',
        'domain' => (string)ini_get('session.cookie_domain'),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}
header('Location: ' . beyond_social_authorization_url($provider, $state, $challenge));
exit;
