<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../../config/bootstrap.php';
header('Cache-Control: no-store, private');

/** Read Jaguar endpoints from environment first, then protected live.php. */
function jaguar_runtime_config(string $key): string
{
    $environmentNames = [
        'runtime_url' => 'JAGUAR_RUNTIME_URL',
        'draw_runtime_url' => 'JAGUAR_DRAW_RUNTIME_URL',
        'runtime_token' => 'JAGUAR_RUNTIME_TOKEN',
        'gemini_api_key' => 'GEMINI_API_KEY',
        'gemini_model' => 'JAGUAR_GEMINI_MODEL',
    ];
    $environmentName = $environmentNames[$key] ?? null;
    if ($environmentName !== null) {
        $environmentValue = getenv($environmentName);
        if (is_string($environmentValue) && trim($environmentValue) !== '') return trim($environmentValue);
    }
    return trim((string)beyond_optional_config('jaguar.' . $key, ''));
}

/**
 * Read one provider setting. Provider-specific names are preferred, while the
 * earlier Jaguar keys remain supported so an existing live.php keeps working
 * during the migration to the provider registry.
 */
function jaguar_provider_config(string $provider, string $key, string $legacyKey = ''): string
{
    $provider = strtolower(trim($provider));
    $key = strtolower(trim($key));
    if (!preg_match('/^[a-z0-9_-]{1,40}$/', $provider) || !preg_match('/^[a-z0-9_-]{1,40}$/', $key)) return '';
    $environmentName = 'JAGUAR_' . strtoupper(str_replace('-', '_', $provider)) . '_' . strtoupper(str_replace('-', '_', $key));
    $environmentValue = getenv($environmentName);
    if (is_string($environmentValue) && trim($environmentValue) !== '') return trim($environmentValue);
    $configProvider = str_replace('-', '_', $provider);
    $configured = trim((string)beyond_optional_config('jaguar.providers.' . $configProvider . '.' . $key, ''));
    if ($configured !== '') return $configured;
    return $legacyKey === '' ? '' : jaguar_runtime_config($legacyKey);
}

try {
    beyond_live_config();
} catch (Throwable $error) {
    error_log('Jaguar private configuration unavailable.');
}
