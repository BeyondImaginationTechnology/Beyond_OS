<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';
require_once dirname(__DIR__, 4) . '/beyond-french/includes/narration/NarrationApi.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

const FRENCH_NATIVE_AUDIO_BATCH = 'native-speakers-2026-09-elevenlabs-v3';
const FRENCH_LOUIS_AUDIO_BATCH = 'louis-florian-2026-09-elevenlabs-v3';
const FRENCH_PABLO_MATEO_VOICE_ID = '00uevfcKk0GtPlbU69ZH';
const FRENCH_JAZZY_HAITIAN_CREOLE_VOICE_ID = 'ELf3eScSrJr0jn1jDw8T';
const FRENCH_IRIE_ANNAKAY_VOICE_ID = 'RRIjxt3K1iKEkfsLGRXU';
const FRENCH_CHARACTER_VOICES = [
    'fr-FR' => 'Louis',
    'es-ES' => 'Pablo',
    'ht-HT' => 'Jazzy',
    'en-JM' => 'Irie',
];
const FRENCH_NATIVE_NARRATION_INSTRUCTIONS = 'Warm, clear, natural premium narration. Preserve scripture references and French pronunciation accurately.';
const FRENCH_NATIVE_AUDIO_LANGUAGES = [
    'es-ES' => ['field' => 'spanish', 'provider' => 'elevenlabs', 'label' => 'Spanish'],
    'ht-HT' => ['field' => 'kreyol', 'provider' => 'elevenlabs', 'label' => 'Haitian Kreyòl'],
    'en-JM' => ['field' => 'patois', 'provider' => 'elevenlabs', 'label' => 'Jamaican Patois'],
];
const FRENCH_LOUIS_AUDIO_LANGUAGE = [
    'fr-FR' => ['field' => 'french', 'provider' => 'elevenlabs', 'label' => 'French'],
];

function frenchNativeResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function frenchNativeNarrationRequest(array $lesson, string $locale, array $settings, array $profiles): array
{
    return [
        'lesson_id' => (int)($lesson['id'] ?? 0),
        'provider' => $settings['provider'],
        'voice' => (string)($profiles[$locale]['voice_id'] ?? ''),
        'language' => $locale,
        'text' => trim((string)($lesson[$settings['field']] ?? '')),
        'instructions' => FRENCH_NATIVE_NARRATION_INSTRUCTIONS,
        'speed' => 1.0,
        'format' => 'mp3',
    ];
}

function frenchNativeCharacterVoices(): array
{
    $profiles = [];
    foreach (FRENCH_CHARACTER_VOICES as $locale => $character) {
        $voice = match ($locale) {
            'es-ES' => beyond_config('narration.elevenlabs.character_voices.pablo', FRENCH_PABLO_MATEO_VOICE_ID),
            'ht-HT' => beyond_config('narration.elevenlabs.character_voices.jazzy', FRENCH_JAZZY_HAITIAN_CREOLE_VOICE_ID),
            'en-JM' => beyond_config('narration.elevenlabs.character_voices.irie', FRENCH_IRIE_ANNAKAY_VOICE_ID),
            default => beyond_config('narration.elevenlabs.voices.' . $locale, beyond_config('voice.voices.' . $locale, '')),
        };
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

function frenchNativeTrackMatchesSelectedVoice(array $lesson, string $locale, array $profiles, array $languages, PDO $pdo): bool
{
    $settings = (array)($languages[$locale] ?? []);
    $request = frenchNativeNarrationRequest($lesson, $locale, $settings, $profiles);
    if ($request['lesson_id'] < 1 || $request['text'] === '' || $request['voice'] === '') return false;
    $cached = narration_cached_audio($pdo, $request['lesson_id'], narration_content_hash($request));
    return is_array($cached)
        && (string)($cached['provider'] ?? '') === 'elevenlabs'
        && (string)($cached['voice'] ?? '') === $request['voice'];
}

function frenchNativeProgress(array $lessons, array $profiles, array $languages, PDO $pdo): array
{
    $eligible = 0;
    $ready = 0;
    foreach ($lessons as $lesson) {
        $audioUrls = (array)($lesson['audio_urls'] ?? []);
        foreach ($languages as $locale => $_settings) {
            if (trim((string)($audioUrls[$locale] ?? '')) === '') continue;
            $eligible++;
            if (frenchNativeTrackMatchesSelectedVoice((array)$lesson, $locale, $profiles, $languages, $pdo)) $ready++;
        }
    }
    return ['ready' => $ready, 'target' => $eligible, 'complete' => $eligible > 0 && $ready >= $eligible];
}

$root = dirname(__DIR__, 4);
$lessonsFile = $root . '/beyond-french/data/lessons.json';
$lessons = json_decode((string)file_get_contents($lessonsFile), true);
if (!is_array($lessons)) frenchNativeResponse(['ok' => false, 'error' => 'The French lesson library is unavailable.'], 500);
$pdo = sqlite_db();
$characterVoices = frenchNativeCharacterVoices();
$voiceIssues = frenchNativeVoiceIssues($characterVoices);
$requestedLocale = trim((string)($_GET['locale'] ?? ''));
$allLanguages = [...FRENCH_NATIVE_AUDIO_LANGUAGES, ...FRENCH_LOUIS_AUDIO_LANGUAGE];
if ($requestedLocale !== '' && !isset($allLanguages[$requestedLocale])) {
    frenchNativeResponse(['ok' => false, 'error' => 'Unsupported character voice batch.'], 422);
}
$selectedLanguages = $requestedLocale === ''
    ? FRENCH_NATIVE_AUDIO_LANGUAGES
    : [$requestedLocale => $allLanguages[$requestedLocale]];
$batch = $requestedLocale === 'fr-FR' ? FRENCH_LOUIS_AUDIO_BATCH : FRENCH_NATIVE_AUDIO_BATCH;
$publicVoiceProfiles = array_map(static function (array $profile): array {
    return ['character' => $profile['character'], 'configured' => $profile['voice_id'] !== ''];
}, $characterVoices);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    frenchNativeResponse(['ok' => true, ...frenchNativeProgress($lessons, $characterVoices, $selectedLanguages, $pdo), 'batch' => $batch, 'character_voices' => $publicVoiceProfiles, 'configuration_ready' => !$voiceIssues, 'configuration_issues' => $voiceIssues]);
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
        ...frenchNativeProgress($lessons, $characterVoices, $selectedLanguages, $pdo),
        'batch' => $batch,
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
        foreach ($selectedLanguages as $locale => $_settings) {
            if (trim((string)($audioUrls[$locale] ?? '')) === '') continue;
            if (frenchNativeTrackMatchesSelectedVoice((array)$lesson, $locale, $characterVoices, $selectedLanguages, $pdo)) continue;
            $selectedIndex = $index;
            $selectedLocale = $locale;
            break 2;
        }
    }

    if ($selectedIndex === null) {
        frenchNativeResponse(['ok' => true, 'built' => null, ...frenchNativeProgress($lessons, $characterVoices, $selectedLanguages, $pdo), 'batch' => $batch]);
    }

    $settings = $allLanguages[$selectedLocale];
    $lesson = (array)$lessons[$selectedIndex];
    $text = trim((string)($lesson[$settings['field']] ?? ''));
    if ($text === '') throw new RuntimeException('Lesson #' . (int)($lesson['id'] ?? 0) . ' has no ' . $settings['label'] . ' text.');
    $request = frenchNativeNarrationRequest($lesson, $selectedLocale, $settings, $characterVoices);
    $contentHash = narration_content_hash($request);
    $lessonId = (int)$request['lesson_id'];
    $audioId = narration_processing_record($pdo, $request, $contentHash, max(1, (int)($_SESSION['user_id'] ?? 1)));
    $savedFile = '';

    try {
        $generated = studio_narration_generate($text, $selectedLocale, $settings['provider'], $characterVoices[$selectedLocale]['voice_id']);
        $audio = (string)($generated['audio_content'] ?? '');
        if (!narration_valid_mp3($audio)) throw new RuntimeException('The narration provider returned an invalid MP3.');
        if ((string)($generated['provider'] ?? '') !== 'elevenlabs' || (string)($generated['voice'] ?? '') !== $request['voice']) {
            throw new RuntimeException('The generated track did not use the selected ElevenLabs character voice.');
        }
        $stored = narration_store_mp3($audio, $lessonId, $audioId);
        $savedFile = (string)$stored['file'];
        $update = $pdo->prepare("UPDATE french_lesson_audio SET provider=?, voice=?, format='mp3', audio_path=?, generation_status='ready', error_code=NULL WHERE id=?");
        $update->execute(['elevenlabs', $request['voice'], (string)$stored['url'], $audioId]);
    } catch (Throwable $generationError) {
        if ($savedFile !== '' && is_file($savedFile)) @unlink($savedFile);
        $failed = $pdo->prepare("UPDATE french_lesson_audio SET generation_status='failed', error_code=? WHERE id=?");
        $failed->execute(['generation_failed', $audioId]);
        throw $generationError;
    }
    $progress = frenchNativeProgress($lessons, $characterVoices, $selectedLanguages, $pdo);
    frenchNativeResponse([
        'ok' => true,
        'built' => [
            'lesson_id' => (int)($lesson['id'] ?? 0),
            'locale' => $selectedLocale,
            'language' => $settings['label'],
            'bytes' => strlen($audio),
            'url' => (string)$stored['url'],
        ],
        ...$progress,
        'batch' => $batch,
    ]);
} catch (Throwable $error) {
    error_log('Beyond French native audio regeneration: ' . $error->getMessage());
    frenchNativeResponse(['ok' => false, 'error' => $error->getMessage(), ...frenchNativeProgress($lessons, $characterVoices, $selectedLanguages, $pdo)], 502);
}
