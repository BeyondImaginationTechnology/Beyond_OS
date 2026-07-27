<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

function beyond_tv_poster_config(): array
{
    $value = static function (string $envName, string $configPath): string {
        $environment = getenv($envName);
        if (is_string($environment) && trim($environment) !== '') {
            return trim($environment);
        }
        try {
            return trim((string)beyond_config($configPath, ''));
        } catch (Throwable $exception) {
            return '';
        }
    };

    return [
        'tmdb_read_token' => $value('TMDB_API_READ_TOKEN', 'media.tmdb.api_read_token'),
        'tmdb_api_key' => $value('TMDB_API_KEY', 'media.tmdb.api_key'),
        'omdb_api_key' => $value('OMDB_API_KEY', 'media.omdb.api_key'),
    ];
}

function beyond_tv_poster_provider_configured(): bool
{
    $config = beyond_tv_poster_config();
    return $config['tmdb_read_token'] !== ''
        || $config['tmdb_api_key'] !== ''
        || $config['omdb_api_key'] !== '';
}

function beyond_tv_poster_cache_path(): string
{
    return beyond_private_root() . '/cache/tmdb-posters.json';
}

function beyond_tv_poster_cache(): array
{
    $path = beyond_tv_poster_cache_path();
    if (!is_file($path)) {
        return [];
    }
    $decoded = json_decode((string)@file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function beyond_tv_save_poster_cache(array $cache): void
{
    $path = beyond_tv_poster_cache_path();
    $directory = dirname($path);
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }
    $handle = @fopen($path, 'c+');
    if (!is_resource($handle)) {
        return;
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            return;
        }
        rewind($handle);
        $existing = json_decode((string)stream_get_contents($handle), true);
        if (is_array($existing)) {
            $cache = array_replace($existing, $cache);
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string)json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

function beyond_tv_poster_json(string $url, array $headers = []): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 6,
            'ignore_errors' => true,
            'header' => implode("\r\n", array_merge([
                'Accept: application/json',
                'User-Agent: Beyond-TV/2.3',
            ], $headers)),
        ],
    ]);
    $payload = @file_get_contents($url, false, $context);
    if (!is_string($payload) || $payload === '') {
        return null;
    }
    $decoded = json_decode($payload, true);
    return is_array($decoded) ? $decoded : null;
}

function beyond_tv_tmdb_poster(array $item, array $config): ?string
{
    if ($config['tmdb_read_token'] === '' && $config['tmdb_api_key'] === '') {
        return null;
    }

    $type = ($item['type'] ?? '') === 'movie' ? 'movie' : 'tv';
    $title = trim((string)($item['title'] ?? ''));
    $title = preg_replace('/\s*[·:]\s*Season\s+\d+.*$/iu', '', $title) ?: $title;
    if ($title === '') {
        return null;
    }

    preg_match('/\b(18|19|20)\d{2}\b/', (string)($item['year'] ?? ''), $yearMatch);
    $query = [
        'query' => $title,
        'include_adult' => 'false',
        'language' => 'en-US',
        'page' => 1,
    ];
    if (!empty($yearMatch[0])) {
        $query[$type === 'movie' ? 'primary_release_year' : 'first_air_date_year'] = $yearMatch[0];
    }
    if ($config['tmdb_api_key'] !== '') {
        $query['api_key'] = $config['tmdb_api_key'];
    }

    $headers = $config['tmdb_read_token'] !== ''
        ? ['Authorization: Bearer ' . $config['tmdb_read_token']]
        : [];
    $response = beyond_tv_poster_json(
        'https://api.themoviedb.org/3/search/' . $type . '?' . http_build_query($query),
        $headers
    );
    foreach ((array)($response['results'] ?? []) as $result) {
        $path = trim((string)($result['poster_path'] ?? ''));
        if ($path !== '') {
            return 'https://image.tmdb.org/t/p/w500' . $path;
        }
    }
    return null;
}

function beyond_tv_omdb_poster(array $item, array $config): ?string
{
    if ($config['omdb_api_key'] === '') {
        return null;
    }
    $title = trim((string)($item['title'] ?? ''));
    $title = preg_replace('/\s*[·:]\s*Season\s+\d+.*$/iu', '', $title) ?: $title;
    if ($title === '') {
        return null;
    }
    preg_match('/\b(18|19|20)\d{2}\b/', (string)($item['year'] ?? ''), $yearMatch);
    $query = [
        'apikey' => $config['omdb_api_key'],
        't' => $title,
        'type' => ($item['type'] ?? '') === 'movie' ? 'movie' : 'series',
        'r' => 'json',
    ];
    if (!empty($yearMatch[0])) {
        $query['y'] = $yearMatch[0];
    }
    $response = beyond_tv_poster_json('https://www.omdbapi.com/?' . http_build_query($query));
    $poster = trim((string)($response['Poster'] ?? ''));
    return $poster !== '' && strcasecmp($poster, 'N/A') !== 0 ? $poster : null;
}

function beyond_tv_poster_url(array $item): ?string
{
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($item['slug'] ?? '')));
    if ($slug === '') {
        return null;
    }

    $cache = beyond_tv_poster_cache();
    $cached = is_array($cache[$slug] ?? null) ? $cache[$slug] : [];
    $checkedAt = (int)($cached['checked_at'] ?? 0);
    $ttl = !empty($cached['url']) ? 2592000 : 86400;
    if ($checkedAt > time() - $ttl) {
        return !empty($cached['url']) ? (string)$cached['url'] : null;
    }

    $config = beyond_tv_poster_config();
    $poster = beyond_tv_tmdb_poster($item, $config)
        ?? beyond_tv_omdb_poster($item, $config);
    $cache[$slug] = [
        'url' => $poster,
        'checked_at' => time(),
    ];
    beyond_tv_save_poster_cache($cache);
    return $poster;
}
