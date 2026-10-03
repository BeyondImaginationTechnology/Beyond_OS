<?php
declare(strict_types=1);

/**
 * Builds the reviewed local visual library for Space TV.
 *
 * Suggested server schedule (four runs per day, one source per run):
 * 17 */6 * * * /usr/bin/php /path/to/public_html/server/cron/space-tv-svs-sync.php >> /path/to/var/log/space-tv-svs-sync.log 2>&1
 *
 * NASA SVS sources are downloaded only from the reviewed seed manifest. The
 * published master contains video only; original source audio is never exposed.
 */

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

function space_tv_sync_run_process(array $command, int $timeoutSeconds, string $label): void
{
    if (!function_exists('proc_open')) {
        throw new RuntimeException($label . ' cannot run because this server disables process execution.');
    }
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException($label . ' could not start.');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $started = microtime(true);
    $output = '';
    do {
        $output .= stream_get_contents($pipes[1]);
        $output .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) - $started > $timeoutSeconds) {
            proc_terminate($process);
            throw new RuntimeException($label . ' exceeded its time limit.');
        }
        usleep(200000);
    } while (true);
    $output .= stream_get_contents($pipes[1]);
    $output .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new RuntimeException($label . ' failed: ' . trim($output));
    }
}

function space_tv_sync_download(string $url, string $destination, int $maximumBytes): void
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The cURL extension is required to download Space TV source media.');
    }
    $parsed = parse_url($url);
    if (!is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https' || ($parsed['host'] ?? '') !== 'svs.gsfc.nasa.gov' || !str_ends_with(strtolower((string)($parsed['path'] ?? '')), '.mp4')) {
        throw new RuntimeException('The reviewed source does not have an approved NASA SVS MP4 URL.');
    }
    $file = fopen($destination, 'xb');
    if ($file === false) {
        throw new RuntimeException('The temporary source file could not be created.');
    }
    $downloaded = 0;
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_FILE => $file,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_FAILONERROR => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_USERAGENT => 'BeyondSpaceTV/0.1 media-library-sync',
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => static function ($resource, float $total, float $now) use (&$downloaded, $maximumBytes): int {
            $downloaded = (int)$now;
            if (($total > 0 && $total > $maximumBytes) || $downloaded > $maximumBytes) {
                return 1;
            }
            return 0;
        },
    ]);
    $success = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    fclose($file);
    if ($success !== true || $status < 200 || $status >= 300 || !is_file($destination) || filesize($destination) < 1024 || $downloaded > $maximumBytes) {
        @unlink($destination);
        throw new RuntimeException('NASA SVS download failed: ' . ($error !== '' ? $error : 'HTTP ' . $status));
    }
}

function space_tv_sync(): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Space TV media synchronization must run from the CLI cron.');
    }
    $root = dirname(__DIR__, 2);
    $seedPath = $root . '/beyond-tv/data/space-tv-svs-library.json';
    $publicDirectory = $root . '/beyond-tv/assets/media/space-tv';
    $manifestPath = $publicDirectory . '/library.json';
    $privateDirectory = beyond_private_directory('media/space-tv', 'space-tv-media');
    $lockPath = beyond_private_file('locks/space-tv-svs-sync.lock', 'space-tv-svs-sync.lock');
    $ffmpeg = trim((string)(getenv('BEYOND_FFMPEG_BIN') ?: 'ffmpeg'));

    if (!is_file($seedPath)) {
        throw new RuntimeException('The Space TV SVS source manifest is missing.');
    }
    $seed = json_decode((string)file_get_contents($seedPath), true);
    if (!is_array($seed) || !is_array($seed['sources'] ?? null)) {
        throw new RuntimeException('The Space TV SVS source manifest is invalid.');
    }
    if (!is_dir($publicDirectory) && !mkdir($publicDirectory, 0755, true) && !is_dir($publicDirectory)) {
        throw new RuntimeException('The public Space TV media directory could not be created.');
    }
    if (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0750, true) && !is_dir($privateDirectory)) {
        throw new RuntimeException('The private Space TV media directory could not be created.');
    }
    if (!is_dir(dirname($lockPath)) && !mkdir(dirname($lockPath), 0750, true) && !is_dir(dirname($lockPath))) {
        throw new RuntimeException('The Space TV synchronization lock directory could not be created.');
    }
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Another Space TV media synchronization is already running.');
    }

    $rawPath = null;
    $temporaryMaster = null;
    try {
        $existing = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : [];
        $ready = is_array($existing['items'] ?? null) ? $existing['items'] : [];
        $readyIds = [];
        foreach ($ready as $item) {
            if (is_array($item) && ($item['status'] ?? '') === 'ready' && is_string($item['id'] ?? null)) {
                $readyIds[$item['id']] = true;
            }
        }
        $source = null;
        foreach ($seed['sources'] as $candidate) {
            if (!is_array($candidate) || empty($candidate['enabled']) || isset($readyIds[$candidate['id'] ?? ''])) {
                continue;
            }
            $id = (string)($candidate['id'] ?? '');
            $url = (string)($candidate['download_url'] ?? '');
            if (preg_match('/^[a-z0-9-]{3,80}$/', $id) === 1 && $url !== '') {
                $source = $candidate;
                break;
            }
        }
        if ($source === null) {
            return 'Space TV SVS library is already up to date.';
        }

        $id = (string)$source['id'];
        $limit = max(1, min(500, (int)($source['maximum_megabytes'] ?? 100))) * 1024 * 1024;
        $token = bin2hex(random_bytes(8));
        $rawPath = $privateDirectory . '/.' . $id . '-' . $token . '.source.mp4';
        $temporaryMaster = $publicDirectory . '/.' . $id . '-' . $token . '.mp4';
        $publishedMaster = $publicDirectory . '/' . $id . '.mp4';

        space_tv_sync_download((string)$source['download_url'], $rawPath, $limit);
        space_tv_sync_run_process([$ffmpeg, '-y', '-i', $rawPath, '-map', '0:v:0', '-c:v', 'copy', '-an', '-movflags', '+faststart', $temporaryMaster], 240, 'FFmpeg audio removal');
        if (!is_file($temporaryMaster) || filesize($temporaryMaster) < 1024) {
            throw new RuntimeException('FFmpeg did not create a usable silent Space TV master.');
        }
        if (!chmod($temporaryMaster, 0644) || !rename($temporaryMaster, $publishedMaster)) {
            throw new RuntimeException('The silent Space TV master could not be published.');
        }
        $ready[] = [
            'id' => $id,
            'status' => 'ready',
            'title' => (string)$source['title'],
            'program' => (string)$source['program'],
            'blocks' => array_values($source['blocks']),
            'asset_url' => '/beyond-tv/assets/media/space-tv/' . $id . '.mp4',
            'duration_seconds' => max(1, (int)($source['fallback_duration_seconds'] ?? 60)),
            'credit' => (string)$source['credit'],
            'source_url' => (string)$source['svs_page'],
            'download_url' => (string)$source['download_url'],
            'rights_status' => (string)$source['rights_status'],
            'audio_policy' => 'removed_from_published_master',
            'downloaded_at' => gmdate(DATE_ATOM),
        ];
        $manifest = ['schema_version' => 1, 'channel_slug' => 'space-tv', 'updated_at' => gmdate(DATE_ATOM), 'items' => $ready];
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded) || file_put_contents($manifestPath . '.tmp', $encoded . PHP_EOL, LOCK_EX) === false || !rename($manifestPath . '.tmp', $manifestPath)) {
            throw new RuntimeException('The Space TV media library manifest could not be published.');
        }
        @chmod($manifestPath, 0644);
        return 'Published silent Space TV NASA SVS master: ' . $id;
    } finally {
        foreach ([$rawPath, $temporaryMaster] as $temporary) {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

try {
    echo space_tv_sync() . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Space TV SVS sync failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
