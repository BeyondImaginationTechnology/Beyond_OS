<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

function youtube_fetch_json(string $url): ?array
{
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'BeyondMusic/1.1 iOS',
        ]);
        $result = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if (is_string($result) && $status >= 200 && $status < 300) {
            $body = $result;
        }
    }

    if ($body === null && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $result = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'BeyondMusic/1.1 iOS',
            ],
        ]));
        if (is_string($result)) {
            $body = $result;
        }
    }

    $decoded = $body !== null ? json_decode($body, true) : null;
    return is_array($decoded) ? $decoded : null;
}

$query = trim((string)($_GET['q'] ?? ''));
if ($query === '' || strlen($query) > 120) {
    http_response_code(400);
    echo json_encode(['items' => [], 'error' => ['message' => 'Enter a search query up to 120 characters.']]);
    exit;
}

try {
    $apiKey = trim((string)beyond_config('music.youtube.data_api_key', ''));
} catch (Throwable $exception) {
    $apiKey = '';
}
if ($apiKey === '') {
    $youtubeConfigFile = __DIR__ . '/../../config/youtube.php';
    if (is_file($youtubeConfigFile)) {
        $youtubeConfig = require $youtubeConfigFile;
        $apiKeys = is_array($youtubeConfig) ? ($youtubeConfig['api_keys'] ?? []) : [];
        if (is_array($apiKeys) && isset($apiKeys[0])) {
            $apiKey = trim((string)$apiKeys[0]);
        }
    }
}
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(['items' => [], 'error' => ['message' => 'YouTube search is not configured.']]);
    exit;
}

$params = [
    'part' => 'snippet',
    'type' => 'video',
    'videoCategoryId' => '10',
    'maxResults' => '25',
    'q' => $query,
    'key' => $apiKey,
];

$payload = youtube_fetch_json('https://www.googleapis.com/youtube/v3/search?' . http_build_query($params));
if (!$payload) {
    http_response_code(502);
    echo json_encode(['items' => [], 'error' => ['message' => 'YouTube search is temporarily unavailable.']]);
    exit;
}

echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
