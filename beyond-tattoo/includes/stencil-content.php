<?php
declare(strict_types=1);

function bt_stencil_defaults(): array
{
    $source = require dirname(__DIR__) . '/config/stencil-day.php';
    if (!is_array($source)) throw new RuntimeException('The Beyond Tattoo stencil source is invalid.');
    $source['updated_at'] ??= '';
    return $source;
}

function bt_stencil_data_file(): string
{
    if (function_exists('beyond_private_root')) return beyond_private_root() . '/data/beyond-tattoo-stencil-day.json';
    return dirname(__DIR__, 3) . '/var/data/beyond-tattoo-stencil-day.json';
}

function bt_stencil_content(): array
{
    $data = bt_stencil_defaults();
    $file = bt_stencil_data_file();
    if (is_file($file)) {
        $decoded = json_decode((string)file_get_contents($file), true);
        $approvedIDs = array_column(bt_asset_library(), 'id');
        if (is_array($decoded)
            && ($decoded['library_version'] ?? '') === '1.2'
            && in_array((string)($decoded['slug'] ?? ''), $approvedIDs, true)) {
            $data = array_replace($data, $decoded);
        }
    }
    return $data;
}

function bt_stencil_save(array $data): void
{
    $file = bt_stencil_data_file();
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Could not create the stencil data directory.');
    $payload = array_replace(bt_stencil_defaults(), $data);
    $payload['library_version'] = '1.2';
    $payload['updated_at'] = gmdate('c');
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($file, $json, LOCK_EX) === false) throw new RuntimeException('Could not save stencil settings. Check private data permissions.');
}
