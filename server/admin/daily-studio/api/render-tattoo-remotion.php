<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

function tattooRemotionError(int $status, string $message): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok'=>false,'error'=>$message], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') tattooRemotionError(405, 'POST required.');
if (!Auth::check()) tattooRemotionError(403, 'Administrator access required.');
if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) tattooRemotionError(419, 'Reload the served Studio and try again.');

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) tattooRemotionError(400, 'Invalid Remotion render request.');

$token = strtolower(trim((string)($input['render_token'] ?? '')));
if (!preg_match('/^[a-f0-9]{36}$/', $token) || empty($_SESSION['stencil_render_tokens'][$token])) {
    tattooRemotionError(422, 'Generate or regenerate the stencil before exporting its video.');
}
if ((int)$_SESSION['stencil_render_tokens'][$token] < time() - 7200) {
    unset($_SESSION['stencil_render_tokens'][$token]);
    tattooRemotionError(410, 'The generated stencil preview expired. Generate it again.');
}

$fieldLimits = [
    'stencilTitle'=>100,
    'collectionName'=>100,
    'suggestedPlacement'=>140,
    'caption'=>500,
    'style'=>100,
    'date'=>50,
];
$props = [];
foreach ($fieldLimits as $field=>$limit) {
    $value = trim((string)($input[$field] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '' || $length > $limit) tattooRemotionError(422, 'Complete the stencil details before rendering.');
    $props[$field] = $value;
}

$privateImage = beyond_private_root() . '/tmp/stencil-generations/' . $token . '.png';
if (!is_file($privateImage) || filesize($privateImage) < 1024) {
    tattooRemotionError(410, 'The generated stencil preview is no longer available.');
}
$imageInfo = @getimagesize($privateImage);
if (!is_array($imageInfo) || ($imageInfo['mime'] ?? '') !== 'image/png') {
    tattooRemotionError(422, 'The generated stencil preview is invalid.');
}

$includeNarration = !array_key_exists('includeNarration', $input) || (bool)$input['includeNarration'];
$audioOnly = !empty($input['audio_only']);

if ($audioOnly) {
    if (!$includeNarration) tattooRemotionError(422, 'Narration was not requested.');
    try {
        $script = sprintf(
            "Today's Beyond Tattoo stencil: %s. %s, designed for %s. Download the transfer-ready pack.",
            $props['stencilTitle'],
            $props['style'],
            $props['suggestedPlacement']
        );
        $narration = studio_narration_generate($script, 'en-US');
        $audio = (string)($narration['audio_content'] ?? '');
        if (strlen($audio) < 128) throw new RuntimeException('The narration service returned empty audio.');
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . strlen($audio));
        header('Cache-Control: private, no-store');
        header('X-Tattoo-Narration: OpenAI');
        echo $audio;
        exit;
    } catch (Throwable $error) {
        error_log('Beyond Tattoo narration export: ' . $error->getMessage());
        tattooRemotionError(503, $error->getMessage());
    }
}

$root = dirname(__DIR__, 4);
$project = $root . '/tools/daily-stencil-video';
$remotion = $project . '/node_modules/.bin/remotion';
$runtimeDirectory = $project . '/public/runtime';
if (!is_file($remotion) || !is_executable($remotion)) {
    tattooRemotionError(503, 'The Beyond Tattoo Remotion renderer is not installed. Run npm install in tools/daily-stencil-video.');
}
if (!function_exists('proc_open')) tattooRemotionError(503, 'This server does not allow the Remotion render process.');
if (!is_dir($runtimeDirectory) && !mkdir($runtimeDirectory, 0775, true) && !is_dir($runtimeDirectory)) {
    tattooRemotionError(500, 'The Remotion runtime folder could not be created.');
}

$job = bin2hex(random_bytes(10));
$runtimeImage = $runtimeDirectory . '/' . $job . '.png';
$runtimeAudio = $runtimeDirectory . '/' . $job . '.mp3';
$propsFile = sys_get_temp_dir() . '/beyond-tattoo-' . $job . '.json';
$outputFile = sys_get_temp_dir() . '/beyond-tattoo-' . $job . '.mp4';
$logFile = sys_get_temp_dir() . '/beyond-tattoo-' . $job . '.log';

try {
    if (!copy($privateImage, $runtimeImage)) throw new RuntimeException('The stencil artwork could not be staged for Remotion.');
    $props['mainArtwork'] = 'runtime/' . basename($runtimeImage);
    $props['studioTransfer'] = $props['mainArtwork'];
    $props['downloadUrl'] = 'https://beyondimagination.co.technology/beyond-tattoo/stencil-of-day.php';
    $props['showQrCode'] = true;
    $props['audioFile'] = '';

    if ($includeNarration) {
        $script = sprintf(
            "Today's Beyond Tattoo stencil: %s. %s, designed for %s. Download the transfer-ready pack.",
            $props['stencilTitle'],
            $props['style'],
            $props['suggestedPlacement']
        );
        $narration = studio_narration_generate($script, 'en-US');
        $audio = (string)($narration['audio_content'] ?? '');
        if (strlen($audio) < 128 || file_put_contents($runtimeAudio, $audio, LOCK_EX) === false) {
            throw new RuntimeException('The narration MP3 could not be prepared.');
        }
        $props['audioFile'] = 'runtime/' . basename($runtimeAudio);
    }

    $encoded = json_encode($props, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || file_put_contents($propsFile, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('The Remotion stencil configuration could not be written.');
    }

    $command = [
        $remotion,'render','src/index.ts','DailyStencilPack',$outputFile,
        '--props='.$propsFile,'--codec=h264','--concurrency=2','--log=error',
    ];
    $descriptors = [0=>['pipe','r'],1=>['file',$logFile,'a'],2=>['file',$logFile,'a']];
    $process = proc_open($command, $descriptors, $pipes, $project);
    if (!is_resource($process)) throw new RuntimeException('The Remotion render process could not start.');
    fclose($pipes[0]);
    @set_time_limit(210);
    $started = microtime(true);
    $exitCode = null;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) { $exitCode = (int)$status['exitcode']; break; }
        if (microtime(true) - $started > 180) {
            proc_terminate($process);
            throw new RuntimeException('The Remotion render exceeded 180 seconds.');
        }
        usleep(250000);
    } while (true);
    $closeCode = proc_close($process);
    if ($exitCode === null || $exitCode < 0) $exitCode = $closeCode;
    if ($exitCode !== 0 || !is_file($outputFile) || filesize($outputFile) < 1024) {
        $details = is_file($logFile) ? trim((string)file_get_contents($logFile)) : '';
        error_log('Beyond Tattoo Remotion render failed: '.$details);
        throw new RuntimeException('The animated Beyond Tattoo video could not be rendered.');
    }

    $safeTitle = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i','-',$props['stencilTitle']),'-')) ?: 'daily-stencil';
    header('Content-Type: video/mp4');
    header('Content-Length: '.filesize($outputFile));
    header('Content-Disposition: attachment; filename="beyond-tattoo-'.$safeTitle.'-remotion.mp4"');
    header('Cache-Control: private, no-store');
    header('X-Video-Renderer: Remotion');
    readfile($outputFile);
} catch (Throwable $error) {
    error_log('Beyond Tattoo Remotion export: '.$error->getMessage());
    foreach ([$runtimeImage,$runtimeAudio,$propsFile,$outputFile,$logFile] as $file) if (is_file($file)) @unlink($file);
    tattooRemotionError(503, $error->getMessage());
} finally {
    foreach ([$runtimeImage,$runtimeAudio,$propsFile,$outputFile,$logFile] as $file) if (is_file($file)) @unlink($file);
}
