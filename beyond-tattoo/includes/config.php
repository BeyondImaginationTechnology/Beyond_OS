<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/ecosystem.php';
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

define('APP_NAME', 'Beyond Tattoo');
define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('UPLOAD_DIR', beyond_private_root() . '/uploads/beyond-tattoo/healing');
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/google-places.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function json_read(string $file): array {
    if (!is_file($file)) {
        return [];
    }
    $contents = file_get_contents($file);
    if ($contents === false || trim($contents) === '') {
        return [];
    }
    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : [];
}

function json_write(string $file, array $data): bool {
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }
    return file_put_contents($file, $encoded, LOCK_EX) !== false;
}

if (!function_exists('redirect')) {
    function redirect(string $path): never {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool { return bt_current_user_id() > 0; }
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['beyond_return_to'] = beyond_return_url();
        redirect(beyond_url('beyond-id/auth/login.php?required=1&app=beyond-tattoo&return=' . rawurlencode(beyond_return_url())));
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        bt_require_csrf();
    }
}

function bt_csrf_token(): string { return beyond_csrf_token(); }
function bt_require_csrf(): void { beyond_require_csrf(); }

function current_user_email(): string {
    return (string)($_SESSION['email'] ?? '');
}

function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($message) ? $message : null;
}
