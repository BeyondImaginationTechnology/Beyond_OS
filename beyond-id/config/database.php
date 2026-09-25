<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

$liveDatabase = [];
try {
    $live = beyond_live_config();
    if (isset($live['database']) && is_array($live['database'])) {
        $liveDatabase = $live['database'];
    }
} catch (Throwable $exception) {
    // Environment-only and local SQLite installations do not require live.php.
}

$environmentDriver = trim((string)(getenv('BEYOND_DB_DRIVER') ?: ''));
$configuredName = (string)(getenv('BEYOND_DB_NAME') ?: ($liveDatabase['name'] ?? ''));
$configuredUser = (string)(getenv('BEYOND_DB_USER') ?: ($liveDatabase['user'] ?? ''));
$driver = strtolower($environmentDriver !== '' ? $environmentDriver : 'sqlite');

return [
    'driver' => $driver,
    'host' => (string)(getenv('BEYOND_DB_HOST') ?: ($liveDatabase['host'] ?? 'localhost')),
    'database' => $configuredName,
    'username' => $configuredUser,
    'password' => (string)(getenv('BEYOND_DB_PASSWORD') ?: ($liveDatabase['pass'] ?? '')),
    'charset' => (string)(getenv('BEYOND_DB_CHARSET') ?: ($liveDatabase['charset'] ?? 'utf8mb4')),
    'sqlite_path' => (string)(getenv('BEYOND_SQLITE_PATH') ?: beyond_private_file('db/beyond-os.sqlite', 'beyond-os.sqlite')),
];
