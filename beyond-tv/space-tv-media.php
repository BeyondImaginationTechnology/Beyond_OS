<?php
declare(strict_types=1);

// The media directory is owned by the hosting account and may be deployed with
// restrictive file modes. This fixed endpoint exposes only approved Space TV
// masters and never accepts a filesystem path from the request.
$episodes = [
    'cosmic-compass-2026-10-03' => __DIR__ . '/assets/media/space-tv/cosmic-compass-2026-10-03.mp4',
];
$episode = $_GET['episode'] ?? '';
$path = is_string($episode) ? ($episodes[$episode] ?? null) : null;

if ($path === null || !is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

$size = filesize($path);
if ($size === false) {
    http_response_code(500);
    exit;
}

header('Content-Type: video/mp4');
header('Content-Length: ' . $size);
header('Accept-Ranges: none');
header('Cache-Control: public, max-age=300');
readfile($path);
