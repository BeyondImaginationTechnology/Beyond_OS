<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

const FRENCH_NATIVE_AUDIO_BATCH = 'native-speakers-2026-09-elevenlabs-v3';
const FRENCH_PABLO_MATEO_VOICE_ID = '00uevfcKk0GtPlbU69ZH';
const FRENCH_CHARACTER_VOICES = [
    'fr-FR' => 'Louis',
    'es-ES' => 'Pablo',
    'ht-HT' => 'Jazzy',
    'en-JM' => 'Irie',
];
const FRENCH_NATIVE_AUDIO_LANGUAGES = [
    'es-ES' => ['field' => 'spanish', 'provider' => 'elevenlabs', 'label' => 'Spanish'],
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

function frenchNativeCharacterVoices(): array
{
    $profiles = [];
    foreach (FRENCH_CHARACTER_VOICES as $locale => $character) {
        $voice = $locale === 'es-ES'
            ? beyond_config('narration.elevenlabs.character_voices.pablo', FRENCH_PABLO_MATEO_VOICE_ID)
            : beyond_config('narration.elevenlabs.voices.' . $locale, beyond_config('voice.voices.' . $locale, ''));
        $profiles[$locale] = ['character' => $character, 'voice_id' => trim((string)$voice)];
    }
    return $profiles;
}

function frenchNativeVoiceIssues(array $profiles): array
{
    $issues = [];
    $owners = [];
    foreach ($profiles as $profile) {
        $voiceId = trim((string)($profile['voice_id'] ?? ''));
        $character = (string)($profile['character'] ?? 'Character');
        if ($voiceId === '') {
            $issues[] = $character . ' needs an explicitly selected ElevenLabs voice.';
            continue;
        }
        $owners[$voiceId][] = $character;
    }
    foreach ($owners as $characters) {
        if (count($characters) > 1) $issues[] = implode(' and ', $characters) . ' share one ElevenLabs voice ID.';
    }
    return $issues;
}

function frenchNativeTrackMatchesSelectedVoice(array $lesson, string $locale, array $profiles, string $root): bool
{
    $profile = (array)($profiles[$locale] ?? []);
    $generation = (array)($lesson['audio_generation'][$locale] ?? []);
    $voiceId = trim((string)($profile['voice_id'] ?? ''));
    $recordedVoiceId = trim((string)($generation['voice_id'] ?? $generation['voice'] ?? ''));
    if ($voiceId === '' || $recordedVoiceId !== $voiceId || (string)($generation['provider'] ?? '') !== 'elevenlabs') return false;
    $urlPath = (string)parse_url((string)(((array)($lesson['audio_urls'] ?? []))[$locale] ?? ''), PHP_URL_PATH);
    $prefix = '/beyond-french/assets/audio/lessons/' . $locale . '/';
    if (!str_starts_with($urlPath, $prefix) || !preg_match('#^[A-Za-z0-9._-]+\.mp3$#i', substr($urlPath, strlen($prefix)))) return false;
    $destination = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($urlPath, '/'));
    return is_file($destination);
}

function frenchNativeProgress(array $lessons, array $profiles, string $root): array
{
    $eligible = 0;
    $ready = 0;
    foreach ($lessons as $lesson) {
        $audioUrls = (array)($lesson['audio_urls'] ?? []);
        foreach (FRENCH_NATIVE_AUDIO_LANGUAGES as $locale => $_settings) {
            if (trim((string)($audioUrls[$locale] ?? '')) === '') continue;
            $eligible++;
            if (frenchNativeTrackMatchesSelectedVoice((array)$lesson, $locale, $profiles, $root)) $ready++;
        }
    }
    return ['ready' => $ready, 'target' => $eligible, 'complete' => $eligible > 0 && $ready >= $eligible];
}

$root = dirname(__DIR__, 4);
$lessonsFile = $root . '/beyond-french/data/lessons.json';
$lessons = json_decode((string)file_get_contents($lessonsFile), true);
if (!is_array($lessons)) frenchNativeResponse(['ok' => false, 'error' => 'The French lesson library is unavailable.'], 500);
$characterVoices = frenchNativeCharacterVoices();
$voiceIssues = frenchNativeVoiceIssues($characterVoices);
$publicVoiceProfiles = array_map(static function (array $profile): array {
    return ['character' => $profile['character'], 'configured' => $profile['voice_id'] !== ''];
}, $characterVoices);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    frenchNativeResponse(['ok' => true, ...frenchNativeProgress($lessons, $characterVoices, $root), 'batch' => FRENCH_NATIVE_AUDIO_BATCH, 'character_voices' => $publicVoiceProfiles, 'configuration_ready' => !$voiceIssues, 'configuration_issues' => $voiceIssues]);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    frenchNativeResponse(['ok' => false, 'error' => 'Unsupported request.'], 405);
}
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    frenchNativeResponse(['ok' => false, 'error' => 'Reload the generator and try again.'], 419);
}
if ($voiceIssues) {
    frenchNativeResponse([
        'ok' => false,
        'error' => 'Audio generation was not started. ' . implode(' ', $voiceIssues) . ' Choose four distinct character voices in Premium Voices first.',
        ...frenchNativeProgress($lessons, $characterVoices, $root),
        'configuration_issues' => $voiceIssues,
    ], 422);
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
            if (frenchNativeTrackMatchesSelectedVoice((array)$lesson, $locale, $characterVoices, $root)) continue;
            $selectedIndex = $index;
            $selectedLocale = $locale;
            break 2;
        }
    }

    if ($selectedIndex === null) {
        frenchNativeResponse(['ok' => true, 'built' => null, ...frenchNativeProgress($lessons, $characterVoices, $root), 'batch' => FRENCH_NATIVE_AUDIO_BATCH]);
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

    $generated = studio_narration_generate(
        $text,
        $selectedLocale,
        $settings['provider'],
        $characterVoices[$selectedLocale]['voice_id']
    );
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
        'character' => $characterVoices[$selectedLocale]['character'],
        'voice_id' => (string)($generated['voice'] ?? $characterVoices[$selectedLocale]['voice_id']),
        'voice' => (string)($generated['voice'] ?? $characterVoices[$selectedLocale]['voice_id']),
        'generated_at' => date(DATE_ATOM),
    ];
    frenchNativeWriteLessons($lessonsFile, $lessons);
    $progress = frenchNativeProgress($lessons, $characterVoices, $root);
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
    frenchNativeResponse(['ok' => false, 'error' => $error->getMessage(), ...frenchNativeProgress($lessons, $characterVoices, $root)], 502);
}
