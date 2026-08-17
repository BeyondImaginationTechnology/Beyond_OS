<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

function remotion_error(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    remotion_error(405, 'POST required.');
}

$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (
    empty($_SESSION['verse_generator_csrf'])
    || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)
) {
    remotion_error(419, 'Reload the served generator and try again.');
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    remotion_error(400, 'Invalid render request.');
}

$limits = [
    'english' => 180,
    'french' => 220,
    'kreyol' => 220,
    'spanish' => 220,
    'patois' => 220,
    'category' => 80,
];
$props = [];
foreach ($limits as $field => $limit) {
    $value = trim((string)($input[$field] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '' || $length > $limit) {
        remotion_error(422, 'Complete every phrase field before rendering.');
    }
    $props[$field] = $value;
}

$includeNarration = !array_key_exists('include_narration', $input)
    || (bool)$input['include_narration'];
$narrationProvider = strtolower(trim((string)($input['narration_provider'] ?? '')));
if ($narrationProvider !== '' && !in_array($narrationProvider, ['openai', 'elevenlabs', 'azure'], true)) {
    remotion_error(422, 'The selected narration provider is invalid.');
}
$narrationVoice = trim((string)($input['narration_voice'] ?? ''));
if ($narrationVoice !== '' && !preg_match('/^[A-Za-z0-9:_-]+$/', $narrationVoice)) {
    remotion_error(422, 'The selected narration voice is invalid.');
}
$publishDate = trim((string)($input['publish_date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishDate)) {
    $publishDate = date('Y-m-d');
}
$prerecordedAudioUrl = trim((string)($input['prerecorded_audio_url'] ?? ''));
if ($prerecordedAudioUrl !== '' && !preg_match('#^/beyond-french/assets/audio/french/[A-Za-z0-9._-]+\.mp3$#', $prerecordedAudioUrl)) {
    remotion_error(422, 'The prerecorded audio path is invalid.');
}

$root = dirname(__DIR__, 4);
$project = $root . '/tools/hello-world-app-preview';
$remotion = $project . '/node_modules/.bin/remotion';
$entry = $project . '/src/index.ts';
$runtimeDirectory = $project . '/public/beyond-french/runtime';

if (!is_file($remotion) || !is_executable($remotion) || !is_file($entry)) {
    remotion_error(
        503,
        'The Remotion renderer is not installed on this server. Run npm install in tools/hello-world-app-preview.'
    );
}
if (!function_exists('proc_open')) {
    remotion_error(503, 'This server does not allow the Remotion render process.');
}
if (
    !is_dir($runtimeDirectory)
    && !mkdir($runtimeDirectory, 0775, true)
    && !is_dir($runtimeDirectory)
) {
    remotion_error(500, 'The Remotion runtime folder could not be created.');
}

$token = bin2hex(random_bytes(10));
$audioFile = $runtimeDirectory . '/' . $token . '.mp3';
$propsFile = sys_get_temp_dir() . '/beyond-french-' . $token . '.json';
$outputFile = sys_get_temp_dir() . '/beyond-french-' . $token . '.mp4';
$logFile = sys_get_temp_dir() . '/beyond-french-' . $token . '.log';

try {
    if ($includeNarration) {
        $audio = '';
        if ($prerecordedAudioUrl !== '') {
            $sourceAudio = $root . str_replace('/', DIRECTORY_SEPARATOR, $prerecordedAudioUrl);
            $resolvedAudio = realpath($sourceAudio);
            $allowedAudioRoot = realpath($root . '/beyond-french/assets/audio/french');
            if ($resolvedAudio === false || $allowedAudioRoot === false || !str_starts_with($resolvedAudio, $allowedAudioRoot . DIRECTORY_SEPARATOR)) {
                remotion_error(404, 'The scheduled prerecorded audio is unavailable.');
            }
            $audio = (string)file_get_contents($resolvedAudio);
        } else {
            $script = sprintf(
                "Today's phrase: %s French: %s Haitian Creole: %s Spanish: %s Jamaican Patois: %s Go beyond French.",
                $props['english'],
                $props['french'],
                $props['kreyol'],
                $props['spanish'],
                $props['patois']
            );
            $narration = studio_narration_generate($script, 'en-US', $narrationProvider, $narrationVoice);
            $audio = (string)($narration['audio_content'] ?? '');
        }
        if (strlen($audio) < 128 || file_put_contents($audioFile, $audio, LOCK_EX) === false) {
            throw new RuntimeException('The narration MP3 could not be prepared.');
        }
        $props['audioFile'] = 'beyond-french/runtime/' . basename($audioFile);
    } else {
        $props['audioFile'] = '';
    }

    $encoded = json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || file_put_contents($propsFile, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('The Remotion lesson configuration could not be written.');
    }

    $command = [
        $remotion,
        'render',
        'src/index.ts',
        'BeyondFrenchPreview',
        $outputFile,
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
        throw new RuntimeException('The Remotion render process could not start.');
    }
    fclose($pipes[0]);
    @set_time_limit(180);
    $started = microtime(true);
    $exitCode = null;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            $exitCode = (int)$status['exitcode'];
            break;
        }
        if (microtime(true) - $started > 150) {
            proc_terminate($process);
            throw new RuntimeException('The Remotion render exceeded 150 seconds.');
        }
        usleep(250000);
    } while (true);
    $closeCode = proc_close($process);
    if ($exitCode === null || $exitCode < 0) $exitCode = $closeCode;
    if ($exitCode !== 0 || !is_file($outputFile) || filesize($outputFile) < 1024) {
        $details = is_file($logFile) ? trim((string)file_get_contents($logFile)) : '';
        error_log('Beyond French Remotion render failed: ' . $details);
        throw new RuntimeException('The animated Remotion video could not be rendered.');
    }

    $filename = 'beyond-french-' . $publishDate . '-remotion.mp4';
    header('Content-Type: video/mp4');
    header('Content-Length: ' . filesize($outputFile));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, no-store');
    header('X-Video-Renderer: Remotion');
    readfile($outputFile);
} catch (Throwable $error) {
    error_log('Beyond French Remotion export: ' . $error->getMessage());
    foreach ([$audioFile, $propsFile, $outputFile, $logFile] as $temporaryFile) {
        if (is_file($temporaryFile)) @unlink($temporaryFile);
    }
    remotion_error(503, $error->getMessage());
} finally {
    foreach ([$audioFile, $propsFile, $outputFile, $logFile] as $temporaryFile) {
        if (is_file($temporaryFile)) @unlink($temporaryFile);
    }
}
