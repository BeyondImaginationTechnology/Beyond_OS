<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', beyond_private_root() . '/logs/php-error.log');
date_default_timezone_set((string)beyond_optional_config('app.timezone', 'America/Vancouver'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Lax']);
    session_start();
}

define('APP_NAME', 'Beyond French | Daily Academy');
define('DATA_DIR', dirname(__DIR__) . '/data');
define('LESSONS_FILE', DATA_DIR . '/lessons.json');
define('FRENCH_ACADEMY_FILE', DATA_DIR . '/academy.json');
define('PRIVATE_DATA_DIR', beyond_private_root() . '/data');
define('SQLITE_FILE', PRIVATE_DATA_DIR . '/beyond.sqlite');
define('ADMIN_USERNAME', (string)beyond_optional_config('security.admin_username', 'admin'));
define('ADMIN_PASSWORD_HASH', (string)beyond_optional_config('security.admin_password_hash', ''));
