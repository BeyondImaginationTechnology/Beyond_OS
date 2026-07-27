<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/movie-art.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$catalog = json_decode((string)@file_get_contents(__DIR__ . '/../data/catalog.json'), true) ?: [];
$item = null;
foreach ($catalog as $candidate) {
    if (($candidate['slug'] ?? '') === $slug) {
        $item = $candidate;
        break;
    }
}

if (!is_array($item)) {
    http_response_code(404);
    exit;
}

$poster = beyond_tv_poster_url($item);
$host = strtolower((string)parse_url((string)$poster, PHP_URL_HOST));
$allowedHosts = ['image.tmdb.org', 'm.media-amazon.com', 'ia.media-imdb.com'];
if (!$poster || !in_array($host, $allowedHosts, true)) {
    header('Cache-Control: no-store');
    http_response_code(404);
    exit;
}

header('Cache-Control: public, max-age=604800, stale-while-revalidate=2592000');
header('Location: ' . $poster, true, 302);
exit;
