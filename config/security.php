<?php
declare(strict_types=1);

function beyond_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function beyond_security_bootstrap(): void
{
    static $started = false;
    if ($started || PHP_SAPI === 'cli') return;
    $started = true;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (beyond_is_https()) ini_set('session.cookie_secure', '1');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>beyond_is_https(),'httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self' https: data: blob:; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self' https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob:; font-src 'self' https: data:; media-src 'self' https: data: blob:; connect-src 'self' https: wss:; frame-src 'self' https:; worker-src 'self' blob:; manifest-src 'self'");
    if (beyond_is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

function beyond_csrf_token(): string
{
    beyond_security_bootstrap();
    if (empty($_SESSION['_beyond_csrf'])) $_SESSION['_beyond_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['_beyond_csrf'];
}

function beyond_verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['_beyond_csrf']) && hash_equals((string)$_SESSION['_beyond_csrf'], $token);
}

function beyond_require_csrf(?string $token = null): void
{
    $token ??= is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null;
    $token ??= is_string($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
    if (!beyond_verify_csrf($token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('The security token is invalid or expired. Reload the page and try again.');
    }
}

beyond_security_bootstrap();
