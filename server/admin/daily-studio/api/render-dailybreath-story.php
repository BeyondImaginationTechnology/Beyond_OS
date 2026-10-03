<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

function dailyBreathStoryRenderError(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: private, no-store');
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    dailyBreathStoryRenderError(405, 'POST required.');
}
if (!Auth::check()) dailyBreathStoryRenderError(403, 'Administrator access required.');
if (!Auth::verifyCsrf((string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    dailyBreathStoryRenderError(419, 'Reload the Story Builder and try again.');
}

$raw = file_get_contents('php://input', false, null, 0, 65537);
$input = is_string($raw) && strlen($raw) <= 65536 ? json_decode($raw, true) : null;
if (!is_array($input)) dailyBreathStoryRenderError(400, 'Send a valid story configuration under 64 KB.');

$project = dirname(__DIR__, 4) . '/tools/daily-stencil-video';
$remotion = $project . '/node_modules/.bin/remotion';
if (!is_file($remotion) || !is_executable($remotion)) {
    dailyBreathStoryRenderError(503, 'The Remotion renderer is not installed. Run npm ci in tools/daily-stencil-video on the render host.');
}
if (!function_exists('proc_open')) dailyBreathStoryRenderError(503, 'This server does not allow the Remotion render process.');

$title = trim((string)($input['title'] ?? ''));
$subtitle = trim((string)($input['subtitle'] ?? ''));
$outroText = trim((string)($input['outroText'] ?? ''));
$recordVoiceover = ($input['recordVoiceover'] ?? false) === true;
$publishToDailyBreathTv = ($input['publishToDailyBreathTv'] ?? false) === true;
$beats = $input['beats'] ?? null;
$sources = $input['sources'] ?? null;
if ($title === '' || mb_strlen($title) > 100 || $subtitle === '' || mb_strlen($subtitle) > 140 || $outroText === '' || mb_strlen($outroText) > 180) {
    dailyBreathStoryRenderError(422, 'Review the title, subtitle, and outro before rendering.');
}
if (!is_array($beats) || count($beats) < 1 || count($beats) > 12 || !is_array($sources) || count($sources) < 1 || count($sources) > 4) {
    dailyBreathStoryRenderError(422, 'An episode needs one to twelve beats and one to four credited sources.');
}
$orderedBeats = [];
$beatIds = [];
$narrationWords = 0;
$previousEnd = 0.0;
foreach ($beats as $beat) {
    if (!is_array($beat)) {
        dailyBreathStoryRenderError(422, 'An episode beat is invalid.');
    }
    $id = trim((string)($beat['id'] ?? ''));
    $startSeconds = filter_var($beat['startSeconds'] ?? null, FILTER_VALIDATE_FLOAT);
    $durationSeconds = filter_var($beat['durationSeconds'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($id === '' || mb_strlen($id) > 64 || isset($beatIds[$id])
        || $startSeconds === false || $durationSeconds === false
        || $startSeconds < $previousEnd || $durationSeconds <= 0
        || $startSeconds > 1800 || $durationSeconds > 1800
        || $startSeconds + $durationSeconds > 1800) {
        dailyBreathStoryRenderError(422, 'Episode beats need unique IDs and non-overlapping timings under 30 minutes.');
    }
    $clean = ['id' => $id];
    foreach (['label' => 70, 'narration' => 600, 'onScreenText' => 90, 'visualPrompt' => 600] as $field => $limit) {
        $value = trim((string)($beat[$field] ?? ''));
        if ($value === '' || mb_strlen($value) > $limit) dailyBreathStoryRenderError(422, 'Each beat needs valid label, narration, screen text, and visual direction.');
        if ($field === 'onScreenText' && (preg_match_all('/[\p{L}\p{N}]+/u', $value) ?: 0) > 8) {
            dailyBreathStoryRenderError(422, 'On-screen text must be eight words or fewer for readability.');
        }
        if ($field === 'narration') {
            $narrationWords += preg_match_all("/[\\p{L}\\p{N}]+(?:[’'-][\\p{L}\\p{N}]+)*/u", $value) ?: 0;
        }
        $clean[$field] = $value;
    }
    $clean['startSeconds'] = (float)$startSeconds;
    $clean['durationSeconds'] = (float)$durationSeconds;
    $beatIds[$id] = true;
    $orderedBeats[] = $clean;
    $previousEnd = $clean['startSeconds'] + $clean['durationSeconds'];
}
if ($narrationWords > 1200) dailyBreathStoryRenderError(422, 'Keep the spoken script to 1,200 words or fewer.');
$cleanSources = [];
foreach ($sources as $source) {
    if (!is_array($source)) dailyBreathStoryRenderError(422, 'Source credits are invalid.');
    $citation = trim((string)($source['citation'] ?? ''));
    $url = trim((string)($source['url'] ?? ''));
    $notes = trim((string)($source['notes'] ?? ''));
    if ($citation === '' || mb_strlen($citation) > 200 || mb_strlen($url) > 1000 || mb_strlen($notes) > 4000) {
        dailyBreathStoryRenderError(422, 'A source credit is missing or too long.');
    }
    if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true))) {
        dailyBreathStoryRenderError(422, 'Source URLs must be valid HTTP or HTTPS links.');
    }
    $cleanSources[] = ['citation' => $citation, 'url' => $url, 'notes' => $notes];
}

$default = json_decode((string)file_get_contents($project . '/public/daily-breath-story.json'), true);
if (!is_array($default)) dailyBreathStoryRenderError(500, 'The Daily Breath Remotion template configuration is unavailable.');
$props = [
    'brand' => 'Daily Breath',
    'series' => trim((string)($default['series'] ?? 'A story to carry with you')),
    'title' => $title,
    'subtitle' => $subtitle,
    'outroText' => $outroText,
    'beats' => $orderedBeats,
    'sourceSeconds' => max(1, min(60, (float)($default['sourceSeconds'] ?? 8))),
    'outroSeconds' => max(1, min(60, (float)($default['outroSeconds'] ?? 8))),
    'sources' => $cleanSources,
    'audioSegments' => [],
    'fps' => 30,
    'width' => 1080,
    'height' => 1920,
    'palette' => (array)($default['palette'] ?? []),
];
$job = bin2hex(random_bytes(12));
$propsFile = sys_get_temp_dir() . '/daily-breath-story-' . $job . '.json';
$outputFile = sys_get_temp_dir() . '/daily-breath-story-' . $job . '.mp4';
$logFile = sys_get_temp_dir() . '/daily-breath-story-' . $job . '.log';
$audioFiles = [];
try {
    if ($recordVoiceover) {
        @set_time_limit(900);
        foreach ($orderedBeats as $index => $beat) {
            $result = studio_narration_generate($beat['narration'], 'en-US', 'elevenlabs');
            $audio = (string)($result['audio_content'] ?? '');
            studio_assert_mp3($audio);
            $audioFile = 'daily-breath-story-' . $job . '-' . $index . '.mp3';
            $audioPath = $project . '/public/' . $audioFile;
            if (file_put_contents($audioPath, $audio, LOCK_EX) === false) {
                throw new RuntimeException('ElevenLabs audio could not be prepared for the video render.');
            }
            @chmod($audioPath, 0600);
            $audioFiles[] = $audioPath;
            $props['audioSegments'][] = ['audioFile' => $audioFile, 'startSeconds' => $beat['startSeconds']];
        }
    }
    $encoded = json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($propsFile, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('The story configuration could not be prepared for Remotion.');
    }
    $process = proc_open(
        [$remotion, 'render', 'src/index.ts', 'DailyBreathStory', $outputFile, '--props=' . $propsFile, '--codec=h264', '--concurrency=2', '--log=error'],
        [0 => ['pipe', 'r'], 1 => ['file', $logFile, 'a'], 2 => ['file', $logFile, 'a']],
        $pipes,
        $project
    );
    if (!is_resource($process)) throw new RuntimeException('The Remotion render process could not start.');
    fclose($pipes[0]);
    @set_time_limit(210);
    $started = microtime(true);
    $exitCode = null;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            $exitCode = (int)$status['exitcode'];
            break;
        }
        if (microtime(true) - $started > 600) {
            proc_terminate($process);
            throw new RuntimeException('The Remotion story render exceeded 600 seconds.');
        }
        usleep(250000);
    } while (true);
    $closeCode = proc_close($process);
    if ($exitCode === null || $exitCode < 0) $exitCode = $closeCode;
    if ($exitCode !== 0 || !is_file($outputFile) || filesize($outputFile) < 1024) {
        $details = is_file($logFile) ? trim((string)file_get_contents($logFile)) : '';
        error_log('Daily Breath Remotion story render failed: ' . $details);
        throw new RuntimeException('The Daily Breath story video could not be rendered.');
    }
    if ($publishToDailyBreathTv) {
        $rotationDirectory = dirname(__DIR__, 4) . '/dailybreath/assets/videos/daily-breath-tv';
        if (!is_dir($rotationDirectory) && !mkdir($rotationDirectory, 0775, true) && !is_dir($rotationDirectory)) {
            throw new RuntimeException('The Daily Breath TV video library could not be created.');
        }
        if (!is_writable($rotationDirectory)) throw new RuntimeException('The Daily Breath TV video library is not writable.');
        $episodeId = trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
        $episodeId = $episodeId !== '' ? $episodeId : 'daily-breath-episode';
        $publishedFile = $rotationDirectory . '/' . $episodeId . '.mp4';
        if (!@rename($outputFile, $publishedFile)) {
            if (!@copy($outputFile, $publishedFile) || !@unlink($outputFile)) {
                throw new RuntimeException('The completed episode could not be placed in the Daily Breath TV rotation.');
            }
        }
        @chmod($publishedFile, 0644);
        $outputFile = '';
        $manifestFile = $rotationDirectory . '/rotation.json';
        $manifest = is_file($manifestFile) ? json_decode((string)file_get_contents($manifestFile), true) : [];
        $manifest = is_array($manifest) ? $manifest : [];
        $episodes = array_values(array_filter((array)($manifest['episodes'] ?? []), static fn($episode): bool => is_array($episode) && ($episode['id'] ?? '') !== $episodeId));
        array_unshift($episodes, [
            'id' => $episodeId,
            'title' => $title,
            'subtitle' => $subtitle,
            'show' => 'Daily Breath',
            'video_url' => '/dailybreath/assets/videos/daily-breath-tv/' . rawurlencode($episodeId) . '.mp4',
            'duration_seconds' => (int)ceil(max(array_map(static fn(array $beat): float => (float)$beat['startSeconds'] + (float)$beat['durationSeconds'], $orderedBeats)) + $props['sourceSeconds'] + $props['outroSeconds']),
            'published_at' => gmdate(DATE_ATOM),
            'voiceover' => $recordVoiceover ? 'elevenlabs' : 'none',
        ]);
        $manifest['channel'] = 'Daily Breath TV';
        $manifest['updated_at'] = gmdate(DATE_ATOM);
        $manifest['episodes'] = $episodes;
        $temporaryManifest = $manifestFile . '.tmp';
        if (file_put_contents($temporaryManifest, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false || !rename($temporaryManifest, $manifestFile)) {
            @unlink($temporaryManifest);
            throw new RuntimeException('The Daily Breath TV rotation manifest could not be updated.');
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: private, no-store');
        echo json_encode(['ok' => true, 'episode' => $episodes[0]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    header('Content-Type: video/mp4');
    header('Content-Length: ' . filesize($outputFile));
    header('Content-Disposition: attachment; filename="daily-breath-story.mp4"');
    header('Cache-Control: private, no-store');
    header('X-Video-Renderer: Remotion');
    readfile($outputFile);
} catch (Throwable $error) {
    error_log('Daily Breath Remotion story export: ' . $error->getMessage());
    dailyBreathStoryRenderError(503, $error->getMessage());
} finally {
    foreach (array_merge([$propsFile, $outputFile, $logFile], $audioFiles) as $file) {
        if (is_file($file)) @unlink($file);
    }
}
