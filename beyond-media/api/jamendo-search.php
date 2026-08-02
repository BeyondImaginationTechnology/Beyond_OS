<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

function jamendo_fetch_json(string $url): ?array
{
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 12,
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
                'timeout' => 12,
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
$page = max(1, min(100, (int)($_GET['page'] ?? 1)));
if ($query === '' || strlen($query) > 120) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Enter a search query up to 120 characters.']);
    exit;
}

try {
    $clientId = trim((string)beyond_config('music.jamendo.client_id', ''));
} catch (Throwable $exception) {
    $clientId = '';
}
if ($clientId === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Jamendo is not configured.']);
    exit;
}

$components = [
    'client_id' => $clientId,
    'format' => 'json',
    'limit' => '15',
    'offset' => (string)(($page - 1) * 15),
    'search' => $query,
    'include' => 'licenses+musicinfo',
    'audioformat' => 'mp32',
    'order' => $page % 2 === 0 ? 'popularity_month' : 'relevance',
];

$payload = jamendo_fetch_json('https://api.jamendo.com/v3.0/tracks/?' . http_build_query($components));
if (!$payload) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Jamendo search is temporarily unavailable.']);
    exit;
}

echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
