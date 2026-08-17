<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';

function spaceRemotionError(int $status, string $message): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok'=>false,'error'=>$message], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function spaceClean(string $value, int $limit): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $limit);
}

function spaceLocalImage(string $source, string $root): string {
    $source = trim($source);
    if ($source === '' || preg_match('/^(blob:|data:|https?:\/\/)/i', $source)) {
        spaceRemotionError(422, 'MP4 export needs a saved local sign background URL, not an uploaded browser-only image.');
    }
    if ($source[0] !== '/') spaceRemotionError(422, 'Use a site-relative background URL for MP4 export.');
    $path = realpath($root . $source);
    if (!$path || !str_starts_with($path, $root) || !is_file($path)) {
        spaceRemotionError(422, 'The selected sign background could not be found on the server.');
    }
    $info = @getimagesize($path);
    if (!is_array($info) || !in_array((string)($info['mime'] ?? ''), ['image/png','image/jpeg','image/webp'], true)) {
        spaceRemotionError(422, 'The sign background must be PNG, JPG, or WebP.');
    }
    return $path;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') spaceRemotionError(405, 'POST required.');
if (!Auth::check()) spaceRemotionError(403, 'Administrator access required.');
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (
    empty($_SESSION['verse_generator_csrf'])
    || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)
) {
    spaceRemotionError(419, 'Reload the generator and try again.');
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) spaceRemotionError(400, 'Invalid Beyond Space render request.');

$root = dirname(__DIR__, 4);
$project = $root . '/tools/daily-stencil-video';
$remotion = $project . '/node_modules/.bin/remotion';
$runtimeDirectory = $project . '/public/runtime';
if (!is_file($remotion) || !is_executable($remotion)) {
    spaceRemotionError(503, 'The Remotion renderer is not installed. Run npm install in tools/daily-stencil-video.');
}
if (!function_exists('proc_open')) spaceRemotionError(503, 'This server does not allow the Remotion render process.');
if (!is_dir($runtimeDirectory) && !mkdir($runtimeDirectory, 0775, true) && !is_dir($runtimeDirectory)) {
    spaceRemotionError(500, 'The Remotion runtime folder could not be created.');
}

$sign = spaceClean((string)($input['sign'] ?? ''), 32);
$symbol = spaceClean((string)($input['symbol'] ?? '✦'), 8);
$season = spaceClean((string)($input['season'] ?? ''), 42);
$date = spaceClean((string)($input['date_label'] ?? date('l, F j, Y')), 70);
$headline = spaceClean((string)($input['headline'] ?? 'Cosmic Weather'), 90);
$mood = spaceClean((string)($input['mood'] ?? 'Open & Grounded'), 54);
$source = spaceClean((string)($input['source'] ?? 'Beyond Space'), 80);
$paragraphs = array_values(array_filter(array_map(
    fn($line) => spaceClean((string)$line, 230),
    (array)($input['paragraphs'] ?? [])
)));
if ($sign === '' || count($paragraphs) < 1) spaceRemotionError(422, 'Choose a sign with horoscope copy before exporting MP4.');

$backgroundPath = spaceLocalImage((string)($input['background_url'] ?? ''), $root);
if (!empty($input['audio_only'])) spaceRemotionError(410, 'Beyond Space narration has been removed.');

$job = bin2hex(random_bytes(10));
$ext = strtolower(pathinfo($backgroundPath, PATHINFO_EXTENSION)) ?: 'jpg';
$runtimeImage = $runtimeDirectory . '/' . $job . '.' . $ext;
$propsFile = sys_get_temp_dir() . '/beyond-space-' . $job . '.json';
$outputFile = sys_get_temp_dir() . '/beyond-space-' . $job . '.mp4';
$logFile = sys_get_temp_dir() . '/beyond-space-' . $job . '.log';

try {
    if (!copy($backgroundPath, $runtimeImage)) throw new RuntimeException('The sign background could not be staged for Remotion.');
    $props = [
        'sign'=>$sign,
        'symbol'=>$symbol,
        'season'=>$season,
        'date'=>$date,
        'headline'=>$headline,
        'paragraphs'=>array_slice($paragraphs, 0, 3),
        'mood'=>$mood,
        'source'=>$source,
        'backgroundImage'=>'runtime/' . basename($runtimeImage),
        'audioFile'=>'',
    ];
    $encoded = json_encode($props, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded) || file_put_contents($propsFile, $encoded . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('The Remotion horoscope configuration could not be written.');
    }
    $command = [
        $remotion,'render','src/index.ts','SpaceHoroscopeVideo',$outputFile,
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
        error_log('Beyond Space Remotion render failed: '.$details);
        throw new RuntimeException('The Beyond Space MP4 could not be rendered.');
    }
    $safeSign = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i','-', $sign), '-')) ?: 'horoscope';
    header('Content-Type: video/mp4');
    header('Content-Length: '.filesize($outputFile));
    header('Content-Disposition: attachment; filename="beyond-space-'.$safeSign.'-horoscope.mp4"');
    header('Cache-Control: private, no-store');
    header('X-Video-Renderer: Remotion');
    readfile($outputFile);
} catch (Throwable $error) {
    error_log('Beyond Space Remotion export: '.$error->getMessage());
    spaceRemotionError(503, $error->getMessage());
} finally {
    foreach ([$runtimeImage,$propsFile,$outputFile,$logFile] as $file) if (is_file($file)) @unlink($file);
}
