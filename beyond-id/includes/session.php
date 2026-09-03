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

    // A host-only cookie is the default. Cross-subdomain SSO requires an
    // explicit deployment setting and is limited to the Beyond parent domain.
    $configuredDomain = strtolower(trim((string)getenv('BEYOND_SESSION_COOKIE_DOMAIN')));
    $cookieDomain = $configuredDomain === '.beyondimagination.co.technology' ? $configuredDomain : '';

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
