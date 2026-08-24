<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

const FRENCH_NATIVE_AUDIO_BATCH = 'native-speakers-2026-08';
const FRENCH_NATIVE_AUDIO_LANGUAGES = [
    'es-ES' => ['field' => 'spanish', 'provider' => 'azure', 'label' => 'Spanish'],
    'ht-HT' => ['field' => 'kreyol', 'provider' => 'elevenlabs', 'label' => 'Haitian Kreyòl'],
    'en-JM' => ['field' => 'patois', 'provider' => 'elevenlabs', 'label' => 'Jamaican Patois'],
];

function frenchNativeResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function frenchNativeWriteLessons(string $file, array $lessons): void
{
    $json = json_encode(array_values($lessons), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temporary = $file . '.tmp';
    if ($json === false || file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $file)) {
        @unlink($temporary);
        throw new RuntimeException('The French lesson library could not be updated.');
    }
}

function frenchNativeProgress(array $lessons): array
{
    $eligible = 0;
    $ready = 0;
    foreach ($lessons as $lesson) {
        $audioUrls = (array)($lesson['audio_urls'] ?? []);
        foreach (FRENCH_NATIVE_AUDIO_LANGUAGES as $locale => $_settings) {
            if (trim((string)($audioUrls[$locale] ?? '')) === '') continue;
            $eligible++;
            if ((string)($lesson['audio_generation'][$locale]['batch'] ?? '') === FRENCH_NATIVE_AUDIO_BATCH) $ready++;
        }
    }
    return ['ready' => $ready, 'target' => $eligible, 'complete' => $eligible > 0 && $ready >= $eligible];
}

$root = dirname(__DIR__, 4);
$lessonsFile = $root . '/beyond-french/data/lessons.json';
$lessons = json_decode((string)file_get_contents($lessonsFile), true);
if (!is_array($lessons)) frenchNativeResponse(['ok' => false, 'error' => 'The French lesson library is unavailable.'], 500);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    frenchNativeResponse(['ok' => true, ...frenchNativeProgress($lessons), 'batch' => FRENCH_NATIVE_AUDIO_BATCH]);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    frenchNativeResponse(['ok' => false, 'error' => 'Unsupported request.'], 405);
}
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    frenchNativeResponse(['ok' => false, 'error' => 'Reload the generator and try again.'], 419);
}
if (!function_exists('curl_init')) frenchNativeResponse(['ok' => false, 'error' => 'The PHP cURL extension is required.'], 503);

try {
    @set_time_limit(120);
    $selectedIndex = null;
    $selectedLocale = '';
    foreach ($lessons as $index => $lesson) {
        $audioUrls = (array)($lesson['audio_urls'] ?? []);
        foreach (FRENCH_NATIVE_AUDIO_LANGUAGES as $locale => $_settings) {
            if (trim((string)($audioUrls[$locale] ?? '')) === '') continue;
            if ((string)($lesson['audio_generation'][$locale]['batch'] ?? '') === FRENCH_NATIVE_AUDIO_BATCH) continue;
            $selectedIndex = $index;
            $selectedLocale = $locale;
            break 2;
        }
    }

    if ($selectedIndex === null) {
        frenchNativeResponse(['ok' => true, 'built' => null, ...frenchNativeProgress($lessons), 'batch' => FRENCH_NATIVE_AUDIO_BATCH]);
    }

    $settings = FRENCH_NATIVE_AUDIO_LANGUAGES[$selectedLocale];
    $lesson = (array)$lessons[$selectedIndex];
    $text = trim((string)($lesson[$settings['field']] ?? ''));
    if ($text === '') throw new RuntimeException('Lesson #' . (int)($lesson['id'] ?? 0) . ' has no ' . $settings['label'] . ' text.');

    $urlPath = (string)parse_url((string)$lesson['audio_urls'][$selectedLocale], PHP_URL_PATH);
    $requiredPrefix = '/beyond-french/assets/audio/lessons/' . $selectedLocale . '/';
    if (!str_starts_with($urlPath, $requiredPrefix) || !str_ends_with(strtolower($urlPath), '.mp3')) {
        throw new RuntimeException('Lesson #' . (int)($lesson['id'] ?? 0) . ' has an invalid audio destination.');
    }
    $destination = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($urlPath, '/'));
    if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0775, true) && !is_dir(dirname($destination))) {
        throw new RuntimeException('The native audio directory could not be created.');
    }

    $generated = studio_narration_generate($text, $selectedLocale, $settings['provider']);
    $audio = (string)($generated['audio_content'] ?? '');
    if (strlen($audio) < 128) throw new RuntimeException('The narration provider returned invalid audio.');
    $temporaryAudio = $destination . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($temporaryAudio, $audio, LOCK_EX) === false || !rename($temporaryAudio, $destination)) {
        @unlink($temporaryAudio);
        throw new RuntimeException('The regenerated MP3 could not be stored.');
    }
    @chmod($destination, 0644);

    $lessons[$selectedIndex]['audio_generation'][$selectedLocale] = [
        'batch' => FRENCH_NATIVE_AUDIO_BATCH,
        'provider' => $settings['provider'],
        'profile' => 'native-speaker',
        'generated_at' => date(DATE_ATOM),
    ];
    frenchNativeWriteLessons($lessonsFile, $lessons);
    $progress = frenchNativeProgress($lessons);
    frenchNativeResponse([
        'ok' => true,
        'built' => [
            'lesson_id' => (int)($lesson['id'] ?? 0),
            'locale' => $selectedLocale,
            'language' => $settings['label'],
            'bytes' => strlen($audio),
            'url' => $urlPath,
        ],
        ...$progress,
        'batch' => FRENCH_NATIVE_AUDIO_BATCH,
    ]);
} catch (Throwable $error) {
    error_log('Beyond French native audio regeneration: ' . $error->getMessage());
    frenchNativeResponse(['ok' => false, 'error' => $error->getMessage(), ...frenchNativeProgress($lessons)], 502);
}
