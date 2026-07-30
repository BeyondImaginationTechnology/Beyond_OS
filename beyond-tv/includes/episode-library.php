<?php
declare(strict_types=1);

/**
 * Shared Beyond TV episode-library resolver.
 *
 * It unifies hand-curated maps, Internet Archive collections, YouTube
 * playlists, and catalogue-only episode records so every show title page has
 * one consistent episode list and a clearly identified current episode.
 */

if (is_file(dirname(__DIR__, 2) . '/config/bootstrap.php')) {
    require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
}

function beyond_tv_episode_cache_root(): string
{
    if (function_exists('beyond_private_root')) {
        return beyond_private_root() . '/cache/beyond-tv/archive-episodes';
    }
    $configured = getenv('BEYOND_VAR_PATH');
    $root = is_string($configured) && trim($configured) !== ''
        ? rtrim($configured, DIRECTORY_SEPARATOR)
        : dirname(__DIR__, 3) . '/var';
    return $root . '/cache/beyond-tv/archive-episodes';
}

function beyond_tv_fetch_json_cached(string $url, string $cacheKey, int $ttl = 259200): ?array
{
    $cacheDirectory = beyond_tv_episode_cache_root();
    $cacheFile = $cacheDirectory . '/' . hash('sha256', $cacheKey) . '.json';
    $cached = null;

    if (is_file($cacheFile)) {
        $decoded = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($decoded)) {
            $cached = $decoded;
            if ((time() - (int) @filemtime($cacheFile)) < $ttl) {
                return $cached;
            }
        }
    }

    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'BeyondTV/2.2 Episode Library',
        ]);
        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if (is_string($result) && $status >= 200 && $status < 300) {
            $body = $result;
        }
    }

    if ($body === null && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $result = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 8,
                'user_agent' => 'BeyondTV/2.2 Episode Library',
            ],
        ]));
        if (is_string($result)) {
            $body = $result;
        }
    }

    $decoded = $body !== null ? json_decode($body, true) : null;
    if (!is_array($decoded)) {
        return $cached;
    }

    try {
        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0750, true) && !is_dir($cacheDirectory)) {
            throw new RuntimeException('Unable to create Beyond TV episode cache.');
        }
        $temporary = $cacheFile . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($temporary, json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false) {
            @chmod($temporary, 0640);
            @rename($temporary, $cacheFile);
        }
    } catch (Throwable $exception) {
        error_log('Beyond TV episode cache: ' . $exception->getMessage());
    }

    return $decoded;
}

function beyond_tv_archive_download_url(string $archiveId, string $file): string
{
    $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $file)));
    return 'https://archive.org/download/' . rawurlencode($archiveId) . '/' . implode('/', $segments);
}

function beyond_tv_episode_source_score(array $row): int
{
    $name = strtolower((string) ($row['archive_file'] ?? $row['file'] ?? $row['video_url'] ?? ''));
    $score = (int) ($row['_size'] ?? 0);
    if (str_ends_with($name, '.mp4')) $score += 4000000000;
    elseif (str_ends_with($name, '.m4v')) $score += 3000000000;
    elseif (str_ends_with($name, '.webm')) $score += 2000000000;
    if (($row['_source'] ?? '') === 'original') $score += 500000000;
    return $score;
}

function beyond_tv_clean_episode_title(string $name, ?array $match = null): string
{
    $title = pathinfo(basename(str_replace('\\', '/', html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'))), PATHINFO_FILENAME);
    $title = preg_replace('/\[[^\]]*(?:480p|720p|1080p|x26[45]|webrip|bluray|dvdrip|pseudo|aac|h\.?264)[^\]]*\]/i', ' ', $title) ?: $title;
    $title = preg_replace('/\b(?:S\d{1,2}\s*E\d{1,3}|\d{1,2}x\d{1,3}|season\s*\d+\s*episode\s*\d+|episode\s*\d+)\b/i', ' ', $title) ?: $title;
    $title = preg_replace('/(^|[\s._-])\d{3,4}([\s._-]|$)/', ' ', $title) ?: $title;
    $title = preg_replace('/\b(?:480p|720p|1080p|x26[45]|webrip|bluray|dvdrip|hdtv|aac|mp4)\b/i', ' ', $title) ?: $title;
    $title = preg_replace('/^(?:that\s*70\s*[\'’]?\s*s\s*show|2\s*broke\s*girls|the\s*o\.?c\.?|malcolm\s*in\s*the\s*middle|reaper|clueless|tales\s*from\s*the\s*darkside)\s*[-_.:]*/i', '', $title) ?: $title;
    $title = preg_replace('/^[\s._-]+|[\s._-]+$/', '', $title) ?: '';
    $title = preg_replace('/[._]+/', ' ', $title) ?: $title;
    $title = preg_replace('/\s*[-–—]+\s*/', ' — ', $title) ?: $title;
    $title = preg_replace('/^(?:\s*—\s*)+|(?:\s*—\s*)+$/', '', $title) ?: $title;
    $title = preg_replace('/(?:\s*—\s*){2,}/', ' — ', $title) ?: $title;
    $title = preg_replace('/\s+/', ' ', trim($title)) ?: '';
    return $title !== '' ? $title : 'Episode';
}

function beyond_tv_parse_episode_identity(string $name, int $fallbackIndex, int $forcedSeason = 0): array
{
    $decoded = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $season = $forcedSeason > 0 ? $forcedSeason : 1;
    $episode = $fallbackIndex;

    if (preg_match('/(?:^|[^a-z0-9])s(\d{1,2})[\s._-]*e(\d{1,3})(?:[^a-z0-9]|$)/i', $decoded, $match)) {
        $season = (int) $match[1];
        $episode = (int) $match[2];
    } elseif (preg_match('/(?:^|[^0-9])(\d{1,2})x(\d{1,3})(?:[^0-9]|$)/i', $decoded, $match)) {
        $season = (int) $match[1];
        $episode = (int) $match[2];
    } elseif (preg_match('/\b(?:season|saison)\s*(\d{1,2}).*?\b(?:episode|ep)\s*(\d{1,3})\b/i', $decoded, $match)) {
        $season = (int) $match[1];
        $episode = (int) $match[2];
    } elseif (preg_match('/(?:^|[\s._-])(\d{3,4})(?:[\s._-]|$)/', $decoded, $match)) {
        $code = $match[1];
        if (strlen($code) === 3) {
            $season = (int) $code[0];
            $episode = (int) substr($code, 1);
        } else {
            $season = (int) substr($code, 0, 2);
            $episode = (int) substr($code, 2);
        }
    } elseif (preg_match('/\b(?:episode|ep)\s*(\d{1,3})\b/i', $decoded, $match)) {
        $episode = (int) $match[1];
    } elseif (preg_match('/^\s*(\d{1,3})(?:\s|[-_.])/', basename($decoded), $match)) {
        $episode = (int) $match[1];
    }

    if ($forcedSeason > 0 && $season === 1 && !preg_match('/s\d{1,2}e\d{1,3}|\d{1,2}x\d{1,3}/i', $decoded)) {
        $season = $forcedSeason;
    }

    return [
        'season' => max(1, $season),
        'episode' => max(1, $episode),
        'title' => beyond_tv_clean_episode_title($decoded),
    ];
}

function beyond_tv_forced_season(array $title): int
{
    if (!empty($title['season_number'])) return max(1, (int) $title['season_number']);
    foreach ([(string) ($title['title'] ?? ''), (string) ($title['slug'] ?? ''), (string) ($title['subtitle'] ?? '')] as $value) {
        if (preg_match('/(?:season|saison)[\s-]*(\d{1,2})/i', $value, $match)) return (int) $match[1];
    }
    return 0;
}

function beyond_tv_normalize_episode_map(array $rows, string $archiveId, int $forcedSeason = 0): array
{
    $episodes = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) continue;
        $file = trim((string) ($row['archive_file'] ?? $row['file'] ?? ''));
        $url = trim((string) ($row['video_url'] ?? ''));
        if ($url === '' && $archiveId !== '' && $file !== '') {
            $url = beyond_tv_archive_download_url($archiveId, $file);
        }
        $identity = [
            'season' => max(1, (int) ($row['season'] ?? ($forcedSeason ?: 1))),
            'episode' => max(1, (int) ($row['episode'] ?? ($index + 1))),
            'title' => trim((string) ($row['title'] ?? '')),
        ];
        if ($identity['title'] === '') {
            $identity['title'] = beyond_tv_clean_episode_title($file !== '' ? $file : ('Episode ' . $identity['episode']));
        }
        $normalized = [
            'season' => $identity['season'],
            'episode' => $identity['episode'],
            'title' => $identity['title'],
            'video_url' => $url,
            'archive_file' => $file,
            'runtime_seconds' => max(0, (int) ($row['runtime_seconds'] ?? 0)),
            'runtime' => (string) ($row['runtime'] ?? ''),
            'synopsis' => (string) ($row['synopsis'] ?? ''),
            'status' => $url !== '' ? 'Full Episode' : (string) ($row['status'] ?? 'Unavailable'),
            'playable' => $url !== '',
            'provider' => 'Internet Archive',
            '_size' => (int) ($row['_size'] ?? 0),
            '_source' => (string) ($row['_source'] ?? ''),
        ];
        $key = $normalized['season'] . '-' . $normalized['episode'];
        if (!isset($episodes[$key]) || beyond_tv_episode_source_score($normalized) > beyond_tv_episode_source_score($episodes[$key])) {
            $episodes[$key] = $normalized;
        }
    }
    return array_values($episodes);
}

function beyond_tv_discover_archive_episodes(string $archiveId, int $forcedSeason = 0): array
{
    if ($archiveId === '') return [];
    $metadata = beyond_tv_fetch_json_cached(
        'https://archive.org/metadata/' . rawurlencode($archiveId),
        'archive:' . $archiveId
    );
    if (!$metadata || !is_array($metadata['files'] ?? null)) return [];

    $candidates = [];
    foreach ($metadata['files'] as $file) {
        if (!is_array($file)) continue;
        $name = trim((string) ($file['name'] ?? ''));
        if ($name === '' || !preg_match('/\.(?:mp4|m4v|webm)$/i', $name)) continue;
        $lower = strtolower($name);
        if (preg_match('/(?:^|[\/_-])(?:thumb|thumbnail|sample|trailer|preview)(?:[\/_-]|$)/i', $lower)) continue;
        $identity = beyond_tv_parse_episode_identity($name, count($candidates) + 1, $forcedSeason);
        $candidates[] = [
            'season' => $identity['season'],
            'episode' => $identity['episode'],
            'title' => $identity['title'],
            'video_url' => beyond_tv_archive_download_url($archiveId, $name),
            'archive_file' => $name,
            'runtime_seconds' => (int) round((float) ($file['length'] ?? 0)),
            'runtime' => '',
            'synopsis' => '',
            'status' => 'Full Episode',
            'playable' => true,
            'provider' => 'Internet Archive',
            '_size' => (int) ($file['size'] ?? 0),
            '_source' => (string) ($file['source'] ?? ''),
        ];
    }

    usort($candidates, static function (array $a, array $b): int {
        return [$a['season'], $a['episode'], $a['archive_file']] <=> [$b['season'], $b['episode'], $b['archive_file']];
    });

    $deduped = [];
    foreach ($candidates as $candidate) {
        $key = $candidate['season'] . '-' . $candidate['episode'];
        if (!isset($deduped[$key]) || beyond_tv_episode_source_score($candidate) > beyond_tv_episode_source_score($deduped[$key])) {
            $deduped[$key] = $candidate;
        }
    }
    return array_values($deduped);
}

function beyond_tv_merge_episode_rows(array ...$libraries): array
{
    $merged = [];
    foreach ($libraries as $library) {
        foreach ($library as $row) {
            if (!is_array($row)) continue;
            $season = max(1, (int) ($row['season'] ?? 1));
            $episode = max(1, (int) ($row['episode'] ?? 1));
            $key = $season . '-' . $episode;
            if (!isset($merged[$key])) {
                $merged[$key] = $row;
                continue;
            }
            $current = $merged[$key];
            if (empty($current['video_url']) && !empty($row['video_url'])) $current['video_url'] = $row['video_url'];
            if (empty($current['playable']) && !empty($row['playable'])) $current['playable'] = true;
            if (($current['title'] ?? '') === '' || preg_match('/^Episode\s+\d+$/i', (string) $current['title'])) {
                if (!empty($row['title'])) $current['title'] = $row['title'];
            }
            foreach (['runtime_seconds', 'runtime', 'synopsis', 'status', 'provider', 'youtube_id', 'playlist_index'] as $field) {
                if (empty($current[$field]) && !empty($row[$field])) $current[$field] = $row[$field];
            }
            if (beyond_tv_episode_source_score($row) > beyond_tv_episode_source_score($current) && !empty($row['video_url'])) {
                $current['video_url'] = $row['video_url'];
                $current['archive_file'] = $row['archive_file'] ?? '';
            }
            $merged[$key] = $current;
        }
    }
    $rows = array_values($merged);
    usort($rows, static fn(array $a, array $b): int => [(int) $a['season'], (int) $a['episode']] <=> [(int) $b['season'], (int) $b['episode']]);
    return $rows;
}

function beyond_tv_build_episode_library(array $title, array $catalogEpisodes, string $dataDirectory): array
{
    if (($title['type'] ?? '') !== 'show') return [];

    $sourceType = (string) ($title['source_type'] ?? 'none');
    $archiveId = trim((string) ($title['archive_id'] ?? ''));
    $forcedSeason = beyond_tv_forced_season($title);
    $mapped = [];
    $discovered = [];
    $generated = [];
    $catalogued = [];

    $mapFile = basename((string) ($title['archive_episode_map'] ?? ''));
    if ($mapFile !== '' && is_file($dataDirectory . '/' . $mapFile)) {
        $mapRows = json_decode((string) @file_get_contents($dataDirectory . '/' . $mapFile), true);
        $mapped = beyond_tv_normalize_episode_map(is_array($mapRows) ? $mapRows : [], $archiveId, $forcedSeason);
    }

    $archiveCount = max(0, (int) ($title['archive_episode_count'] ?? 0));
    $template = (string) ($title['archive_file_template'] ?? '');

    $seasonEpisodeCounts = $title['season_episode_counts'] ?? [];
    if (is_array($seasonEpisodeCounts)) {
        foreach ($seasonEpisodeCounts as $seasonNumber => $episodeCount) {
            $seasonNumber = max(1, (int) $seasonNumber);
            $episodeCount = max(0, (int) $episodeCount);
            for ($number = 1; $number <= $episodeCount; $number++) {
                $generated[] = [
                    'season' => $seasonNumber,
                    'episode' => $number,
                    'title' => 'Episode ' . $number,
                    'video_url' => '',
                    'runtime_seconds' => 0,
                    'runtime' => '',
                    'synopsis' => '',
                    'status' => 'Episode listed',
                    'playable' => false,
                    'provider' => '',
                ];
            }
        }
    }
    if ($archiveId !== '' && $template !== '' && $archiveCount > 0) {
        for ($number = 1; $number <= $archiveCount; $number++) {
            $file = sprintf($template, $number);
            $generated[] = [
                'season' => $forcedSeason ?: 1,
                'episode' => $number,
                'title' => 'Episode ' . $number,
                'video_url' => beyond_tv_archive_download_url($archiveId, $file),
                'archive_file' => $file,
                'runtime_seconds' => 0,
                'runtime' => '',
                'synopsis' => '',
                'status' => 'Full Episode',
                'playable' => true,
                'provider' => 'Internet Archive',
            ];
        }
    }

    $archiveDiscoveryEnabled = !array_key_exists('archive_discovery', $title) || !empty($title['archive_discovery']);
    $needsArchiveDiscovery = $archiveDiscoveryEnabled
        && $archiveId !== ''
        && in_array($sourceType, ['archive_embed', 'archive_episode_map', 'archive_collection'], true)
        && ($template === '' || count($mapped) < max(1, $archiveCount));
    if ($needsArchiveDiscovery) {
        $discovered = beyond_tv_discover_archive_episodes($archiveId, $forcedSeason);
    }

    if ($sourceType === 'youtube_playlist_embed') {
        $count = max(0, (int) ($title['playlist_episode_count'] ?? 0));
        for ($number = 1; $number <= $count; $number++) {
            $generated[] = [
                'season' => $forcedSeason ?: 1,
                'episode' => $number,
                'title' => 'Episode ' . $number,
                'video_url' => '',
                'runtime_seconds' => 0,
                'runtime' => '',
                'synopsis' => '',
                'status' => 'Full Episode',
                'playable' => true,
                'provider' => 'YouTube',
                'playlist_index' => $number - 1,
            ];
        }
    }

    foreach ($catalogEpisodes as $index => $episode) {
        if (!is_array($episode)) continue;
        $youtubeId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($episode['youtube_id'] ?? ''));
        $videoUrl = trim((string) ($episode['video_url'] ?? ''));
        $playable = $youtubeId !== '' || ($videoUrl !== '' && !str_contains($videoUrl, '/playlist?'));
        $catalogued[] = [
            'season' => max(1, (int) ($episode['season'] ?? 1)),
            'episode' => max(1, (int) ($episode['episode'] ?? ($index + 1))),
            'title' => (string) ($episode['title'] ?? ('Episode ' . ($index + 1))),
            'video_url' => $videoUrl,
            'youtube_id' => $youtubeId,
            'runtime_seconds' => 0,
            'runtime' => (string) ($episode['runtime'] ?? ''),
            'synopsis' => (string) ($episode['synopsis'] ?? ''),
            'status' => (string) ($episode['status'] ?? ($playable ? 'Full Episode' : 'Unavailable')),
            'playable' => $playable,
            'provider' => (string) ($episode['provider'] ?? ''),
        ];
    }

    $library = beyond_tv_merge_episode_rows($mapped, $generated, $discovered, $catalogued);

    if (!$library && $archiveCount > 0) {
        for ($number = 1; $number <= $archiveCount; $number++) {
            $library[] = [
                'season' => $forcedSeason ?: 1,
                'episode' => $number,
                'title' => 'Episode ' . $number,
                'video_url' => '',
                'runtime_seconds' => 0,
                'runtime' => '',
                'synopsis' => '',
                'status' => 'Episode listed',
                'playable' => false,
                'provider' => '',
            ];
        }
    }

    if (!$library && in_array($sourceType, ['direct_video', 'youtube_embed'], true)) {
        $library[] = [
            'season' => $forcedSeason ?: 1,
            'episode' => 1,
            'title' => $sourceType === 'direct_video' ? 'Full compilation' : 'Featured programme',
            'video_url' => (string) ($title['video_url'] ?? ''),
            'youtube_id' => (string) ($title['youtube_id'] ?? ''),
            'runtime_seconds' => 0,
            'runtime' => (string) ($title['runtime'] ?? ''),
            'synopsis' => (string) ($title['description'] ?? ''),
            'status' => 'Full Episode',
            'playable' => true,
            'provider' => (string) ($title['source_label'] ?? ''),
        ];
    }

    return $library;
}

function beyond_tv_select_episode(array $library, int $requestedSeason, int $requestedEpisode): ?array
{
    foreach ($library as $row) {
        if ((int) ($row['season'] ?? 0) === $requestedSeason && (int) ($row['episode'] ?? 0) === $requestedEpisode) {
            return $row;
        }
    }
    foreach ($library as $row) {
        if (!empty($row['playable'])) return $row;
    }
    return $library[0] ?? null;
}
