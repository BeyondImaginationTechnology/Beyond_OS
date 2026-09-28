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
    ];
    $environmentName = $environmentNames[$key] ?? null;
    if ($environmentName !== null) {
        $environmentValue = getenv($environmentName);
        if (is_string($environmentValue) && trim($environmentValue) !== '') return trim($environmentValue);
    }
    return trim((string)beyond_optional_config('jaguar.' . $key, ''));
}

try {
    beyond_live_config();
} catch (Throwable $error) {
    error_log('Jaguar private configuration unavailable.');
}
