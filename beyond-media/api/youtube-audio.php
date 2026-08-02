<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

function youtube_audio_base_url(): string
{
    try {
        $base = trim((string)beyond_config('music.youtube.audio_api_base_url', ''));
    } catch (Throwable $exception) {
        $base = '';
    }
    return rtrim($base, '/');
}

function youtube_audio_self_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'beyondimagination.co.technology');
    $path = strtok((string)($_SERVER['REQUEST_URI'] ?? '/beyond-media/api/youtube-audio.php'), '?');
    return $scheme . '://' . $host . $path;
}

function youtube_audio_fetch(string $url): array
{
    if (function_exists('curl_init')) {
        $headers = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$headers): int {
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($header);
            },
            CURLOPT_USERAGENT => 'BeyondMusic/1.1 iOS',
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [$status, is_string($body) ? $body : '', $headers, $error];
    }

    $body = @file_get_contents($url, false, stream_context_create([
        'http' => [
            'timeout' => 180,
            'user_agent' => 'BeyondMusic/1.1 iOS',
        ],
    ]));
    return [$body === false ? 502 : 200, is_string($body) ? $body : '', [], ''];
}

$remoteBase = youtube_audio_base_url();
if ($remoteBase === '') {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'YouTube audio conversion is not configured.',
        'detail' => 'Set music.youtube.audio_api_base_url in var/config/live.php to a Python/FFmpeg converter host.',
    ]);
    exit;
}

$youtubeURL = trim((string)($_GET['url'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));

if ($youtubeURL !== '') {
    [$status, $body, $headers, $error] = youtube_audio_fetch($remoteBase . '/?' . http_build_query(['url' => $youtubeURL]));
    $payload = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($payload)) {
        http_response_code($status >= 400 ? $status : 502);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Converter request failed.', 'detail' => $error ?: $body]);
        exit;
    }
    if (!empty($payload['token'])) {
        $payload['download_url'] = youtube_audio_self_url() . '?' . http_build_query(['token' => (string)$payload['token']]);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($token !== '') {
    [$status, $body, $headers, $error] = youtube_audio_fetch($remoteBase . '/download?' . http_build_query(['token' => $token]));
    http_response_code($status >= 100 ? $status : 502);
    header('Content-Type: ' . ($headers['content-type'] ?? 'audio/mpeg'));
    header('Cache-Control: private, max-age=0, no-store');
    if (!empty($headers['content-disposition'])) {
        header('Content-Disposition: ' . $headers['content-disposition']);
    } else {
        header('Content-Disposition: attachment; filename="beyond-music-youtube.mp3"');
    }
    echo $body !== '' ? $body : ($error ?: '');
    exit;
}

http_response_code(400);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => "Pass either 'url' or 'token'."]);
