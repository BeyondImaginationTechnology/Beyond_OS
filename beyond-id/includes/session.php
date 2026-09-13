<?php
declare(strict_types=1);

/** Shared Beyond ID session for all apps under the same host. */
function beyond_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // Keep local and custom-host installs host-only. Production Beyond hosts
    // share the Beyond ID session across the parent domain and its apps.
    $configuredDomain = strtolower(trim((string)getenv('BEYOND_SESSION_COOKIE_DOMAIN')));
    $requestHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')) ?? '');
    $isBeyondHost = $requestHost === 'beyondimagination.co.technology'
        || str_ends_with($requestHost, '.beyondimagination.co.technology');
    $cookieDomain = $configuredDomain === '.beyondimagination.co.technology' || ($configuredDomain === '' && $isBeyondHost)
        ? '.beyondimagination.co.technology'
        : '';

    session_name('BEYOND_ID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

beyond_start_session();
