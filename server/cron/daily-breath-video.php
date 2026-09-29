<?php
declare(strict_types=1);

function dailybreath_render_daily_video(): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Daily Breath video generation must run from the CLI cron.');
    }

    $root = dirname(__DIR__, 2);
    $project = $root . '/tools/daily-stencil-video';
    $renderer = $project . '/node_modules/.bin/remotion';
    $entry = $project . '/src/index.ts';
    $configPath = $project . '/public/daily-breath-video.json';
    $outputDirectory = $root . '/dailybreath/assets/videos/breathing';
    $lockPath = beyond_private_file('locks/dailybreath-video.lock', 'dailybreath-video.lock');

    if (!is_file($configPath)) {
        throw new RuntimeException('The Daily Breath Remotion JSON configuration is missing.');
    }
    if (!is_file($renderer) || !is_executable($renderer) || !is_file($entry)) {
        throw new RuntimeException(
            'The Daily Breath Remotion CLI is unavailable. Install tools/daily-stencil-video dependencies on the server.'
        );
    }
    if (!function_exists('proc_open')) {
        throw new RuntimeException('The server does not allow the Remotion render process.');
    }

    $config = json_decode((string)file_get_contents($configPath), true);
    if (!is_array($config) || !is_array($config['phases'] ?? null) || !$config['phases']) {
        throw new RuntimeException('The Daily Breath Remotion JSON configuration is invalid.');
    }
    $timezoneName = trim((string)($config['timezone'] ?? ''));
    if ($timezoneName === '') {
        throw new RuntimeException('The Daily Breath JSON configuration must include a timezone.');
    }
    try {
        $timezone = new DateTimeZone($timezoneName);
    } catch (Throwable $error) {
        throw new RuntimeException('The Daily Breath JSON configuration has an invalid timezone.', 0, $error);
    }

    foreach (['introSeconds', 'outroSeconds', 'cycleCount', 'fps', 'width', 'height'] as $field) {
        $minimum = in_array($field, ['introSeconds', 'outroSeconds'], true) ? 0 : 1;
        if (
            !isset($config[$field])
            || !is_numeric($config[$field])
            || (float)$config[$field] < $minimum
            || floor((float)$config[$field]) !== (float)$config[$field]
        ) {
            throw new RuntimeException('The Daily Breath JSON configuration has invalid video timing or dimensions.');
        }
    }
    foreach (['brand', 'title', 'subtitle', 'opening', 'closing', 'safetyNote'] as $field) {
        if (!isset($config[$field]) || !is_string($config[$field]) || trim($config[$field]) === '') {
            throw new RuntimeException('The Daily Breath JSON configuration is missing required video text.');
        }
    }

    foreach ($config['phases'] as $phase) {
        if (
            !is_array($phase)
            || !is_string($phase['label'] ?? null)
            || trim($phase['label']) === ''
            || !is_numeric($phase['durationSeconds'] ?? null)
            || (float)$phase['durationSeconds'] <= 0
            || floor((float)$phase['durationSeconds']) !== (float)$phase['durationSeconds']
            || !is_numeric($phase['fromScale'] ?? null)
            || (float)$phase['fromScale'] <= 0
            || !is_numeric($phase['toScale'] ?? null)
            || (float)$phase['toScale'] <= 0
        ) {
            throw new RuntimeException('The Daily Breath JSON configuration contains an invalid breathing phase.');
        }
    }
    for ($index = 0, $count = count($config['phases']); $index < $count; $index++) {
        $current = $config['phases'][$index];
        $next = $config['phases'][($index + 1) % $count];
        if ((float)$current['toScale'] !== (float)$next['fromScale']) {
            throw new RuntimeException('The Daily Breath JSON configuration has discontinuous breathing phases.');
        }
    }
    if (!is_array($config['palette'] ?? null)) {
        throw new RuntimeException('The Daily Breath JSON configuration is missing its video palette.');
    }

    $now = new DateTimeImmutable('now', $timezone);
    $date = $now->format('Y-m-d');
    $dateLabel = $now->format('l, F j, Y');
    $outputFile = $outputDirectory . '/' . $date . '.mp4';
    $publishManifest = static function () use ($outputDirectory, $date, $config): void {
        $manifest = [
            'date' => $date,
            'title' => $config['title'],
            'url' => '/dailybreath/assets/videos/breathing/' . $date . '.mp4',
            'duration_seconds' => (int)$config['introSeconds']
                + (int)$config['outroSeconds']
                + (int)$config['cycleCount'] * array_sum(array_map(
                    static fn(array $phase): int => (int)$phase['durationSeconds'],
                    $config['phases'],
                )),
            'generated_at' => date(DATE_ATOM),
        ];
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException('The Daily Breath video manifest could not be encoded.');
        }
        $manifestFile = $outputDirectory . '/latest.json';
        $temporaryManifest = $outputDirectory . '/.latest-' . bin2hex(random_bytes(8)) . '.json';
        try {
            if (file_put_contents($temporaryManifest, $encoded . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('The Daily Breath video manifest could not be written.');
            }
            if (!chmod($temporaryManifest, 0644) || !rename($temporaryManifest, $manifestFile)) {
                throw new RuntimeException('The Daily Breath video manifest could not be published.');
            }
        } finally {
            if (is_file($temporaryManifest)) {
                unlink($temporaryManifest);
            }
        }
    };
    if (!is_dir(dirname($lockPath)) && !mkdir(dirname($lockPath), 0750, true) && !is_dir(dirname($lockPath))) {
        throw new RuntimeException('The private Daily Breath render lock directory could not be created.');
    }
    $lock = fopen($lockPath, 'c');
    if ($lock === false) {
        throw new RuntimeException('The Daily Breath render lock could not be opened.');
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return 'Daily Breath video render skipped: another render is already running.';
    }

    $token = bin2hex(random_bytes(12));
    $propsFile = sys_get_temp_dir() . '/daily-breath-' . $token . '.json';
    $logFile = sys_get_temp_dir() . '/daily-breath-' . $token . '.log';
    $temporaryOutput = $outputDirectory . '/.' . $date . '-' . $token . '.mp4';
    $process = null;

    try {
        if (
            is_file($outputFile)
            && filesize($outputFile) >= 1024
            && filemtime($outputFile) >= filemtime($configPath)
        ) {
            $publishManifest();
            return 'Daily Breath video already exists: /dailybreath/assets/videos/breathing/' . $date . '.mp4';
        }
        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException('The Daily Breath video output directory could not be created.');
        }

        $config['date'] = $dateLabel;
        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || file_put_contents($propsFile, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The Daily Breath Remotion props could not be written.');
        }

        $command = [
            $renderer,
            'render',
            'src/index.ts',
            'DailyBreathVideo',
            $temporaryOutput,
            '--props=' . $propsFile,
            '--codec=h264',
            '--concurrency=2',
            '--log=error',
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ];
        $process = proc_open($command, $descriptors, $pipes, $project);
        if (!is_resource($process)) {
            throw new RuntimeException('The Daily Breath Remotion render process could not start.');
        }
        fclose($pipes[0]);
        @set_time_limit(300);
        $started = microtime(true);
        $exitCode = null;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = (int)$status['exitcode'];
                break;
            }
            if (microtime(true) - $started > 240) {
                proc_terminate($process);
                throw new RuntimeException('The Daily Breath Remotion render exceeded 240 seconds.');
            }
            usleep(250000);
        } while (true);

        $closeCode = proc_close($process);
        $process = null;
        if ($exitCode === null || $exitCode < 0) {
            $exitCode = $closeCode;
        }
        if ($exitCode !== 0 || !is_file($temporaryOutput) || filesize($temporaryOutput) < 1024) {
            $details = is_file($logFile) ? trim((string)file_get_contents($logFile)) : '';
            error_log('Daily Breath Remotion render failed: ' . $details);
            throw new RuntimeException('The Daily Breath MP4 could not be rendered.');
        }
        if (!chmod($temporaryOutput, 0644) || !rename($temporaryOutput, $outputFile)) {
            throw new RuntimeException('The completed Daily Breath MP4 could not be published.');
        }
        $publishManifest();

        return 'Daily Breath video generated: /dailybreath/assets/videos/breathing/' . $date . '.mp4';
    } finally {
        if (is_resource($process)) {
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }
            proc_close($process);
        }
        foreach ([$propsFile, $logFile, $temporaryOutput] as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
