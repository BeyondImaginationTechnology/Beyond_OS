<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';

function beyond_private_root(): string
{
    $configured = getenv('BEYOND_VAR_PATH');
    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, DIRECTORY_SEPARATOR);
    }
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var';
}

/** Keep the private storage layout stable for the lifetime of each request. */
function beyond_private_migration_lock(): void
{
    static $handle;
    if (is_resource($handle)) {
        return;
    }
    $directory = beyond_private_root() . '/tmp';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Private storage lock directory is unavailable.');
    }
    $handle = fopen($directory . '/var-layout.lock', 'c');
    if ($handle === false || !flock($handle, LOCK_SH)) {
        throw new RuntimeException('Private storage layout is unavailable.');
    }
}

/** Resolve a private file during the move from the flat var layout. */
function beyond_private_file(string $relative, string $legacyRelative): string
{
    beyond_private_migration_lock();
    $root = beyond_private_root();
    $current = $root . '/' . ltrim($relative, '/');
    $legacy = $root . '/' . ltrim($legacyRelative, '/');
    if (is_file($current)) {
        return $current;
    }
    return is_file($legacy) ? $legacy : $current;
}

/** Resolve a private directory while its contents are being relocated. */
function beyond_private_directory(string $relative, string $legacyRelative): string
{
    beyond_private_migration_lock();
    $root = beyond_private_root();
    $current = $root . '/' . trim($relative, '/');
    $legacy = $root . '/' . trim($legacyRelative, '/');
    if (is_dir($current)) {
        return $current;
    }
    return is_dir($legacy) ? $legacy : $current;
}

function beyond_live_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }
    $file = beyond_private_root() . '/config/live.php';
    $defaultFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'live.php';
    if (!is_file($file)) {
        if ($defaultFile !== $file && is_file($defaultFile)) {
            error_log('Configured protected configuration file is unavailable; using the default private configuration path.');
            $file = $defaultFile;
        }
    }
    if (!is_file($file)) {
        throw new RuntimeException('Protected configuration is unavailable.');
    }
    $loaded = require $file;
    if (!is_array($loaded)) {
        throw new RuntimeException('Protected configuration is invalid.');
    }

    $hasOAuthCredentials = static function (array $candidate): bool {
        foreach (['google', 'github', 'apple', 'x'] as $provider) {
            $credentials = $candidate['oauth'][$provider] ?? null;
            if (!is_array($credentials)) {
                continue;
            }
            $clientId = trim((string)($credentials['client_id'] ?? $credentials['app_id'] ?? ''));
            $clientSecret = trim((string)($credentials['client_secret'] ?? $credentials['app_secret'] ?? ''));
            if ($clientId !== '' && $clientSecret !== '') {
                return true;
            }
        }
        return false;
    };
    if ($defaultFile !== $file && is_file($defaultFile) && !$hasOAuthCredentials($loaded)) {
        try {
            $defaultConfig = require $defaultFile;
            if (is_array($defaultConfig) && $hasOAuthCredentials($defaultConfig)) {
                $loaded['oauth'] = $defaultConfig['oauth'];
                error_log('OAuth configuration loaded from the default private configuration path.');
            }
        } catch (Throwable $exception) {
            error_log('Default private OAuth configuration could not be loaded: ' . $exception->getMessage());
        }
    }
    $config = $loaded;
    return $config;
}

function beyond_config(string $path, $default = null)
{
    $value = beyond_live_config();
    foreach (explode('.', $path) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/**
 * Read configuration for an optional integration or public-page preference.
 *
 * Public apps should remain available when the protected configuration mount
 * is temporarily unavailable. Authentication, database, and other required
 * services must continue to use beyond_config() so they fail closed.
 */
function beyond_optional_config(string $path, $default = null)
{
    try {
        return beyond_config($path, $default);
    } catch (Throwable $exception) {
        static $reported = false;
        if (!$reported) {
            error_log('Optional protected configuration is unavailable: ' . $exception->getMessage());
            $reported = true;
        }
        return $default;
    }
}
