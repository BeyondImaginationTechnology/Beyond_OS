<?php
declare(strict_types=1);

function beyond_ios_override_path(string $product): string
{
    $safe = preg_replace('/[^a-z0-9-]/', '', strtolower($product)) ?: 'content';
    return dirname(__DIR__) . '/var/ios-content-overrides/' . $safe . '.json';
}

function beyond_ios_override_write(string $product, array $content): void
{
    $path = beyond_ios_override_path($product);
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The iOS override directory could not be created.');
    }
    $now = new DateTimeImmutable('now');
    $payload = [
        'product' => $product,
        'pushed_at' => $now->format(DATE_ATOM),
        'expires_at' => $now->modify('tomorrow')->setTime(0, 0)->format(DATE_ATOM),
        'content' => $content,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temporary = $path . '.tmp';
    if ($json === false || file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('The iOS override could not be saved.');
    }
}

function beyond_ios_override_read(string $product): ?array
{
    $path = beyond_ios_override_path($product);
    if (!is_file($path)) return null;
    $payload = json_decode((string)file_get_contents($path), true);
    if (!is_array($payload) || !is_array($payload['content'] ?? null)) return null;
    $expires = strtotime((string)($payload['expires_at'] ?? ''));
    if ($expires === false || $expires <= time()) return null;
    return $payload;
}
