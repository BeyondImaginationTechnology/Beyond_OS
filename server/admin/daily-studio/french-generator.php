<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['verse_generator_csrf'])) {
    $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));
}

$view = file_get_contents(__DIR__ . '/generators/french-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Français du Jour generator view is unavailable.');
}
$rendererPath = __DIR__ . '/assets/beyond-french-remotion-renderer.js';
$rendererVersion = is_file($rendererPath) ? (string)filemtime($rendererPath) : 'missing';
$view = str_replace('__FRENCH_RENDERER_VERSION__', rawurlencode($rendererVersion), $view);

$providerLabels = [
    'openai' => 'OpenAI Speech · gpt-4o-mini-tts',
    'elevenlabs' => 'ElevenLabs Premium',
];
$provider = 'openai';
$providerConfigured = false;
$defaultNarrationProvider = 'openai';
$characterVoiceLocales = [];
$characterVoiceNames = [];
$characterVoiceIds = [];
try {
    $configuredProvider = strtolower((string)beyond_config('voice.provider', 'openai'));
    $openaiReady = trim((string)beyond_config('narration.openai.api_key', '')) !== '';
    $elevenLabsReady = trim((string)beyond_config('narration.elevenlabs.api_key', beyond_config('voice.api_key', ''))) !== '';
    $provider = in_array($configuredProvider, ['openai', 'elevenlabs'], true)
        ? $configuredProvider
        : ($elevenLabsReady ? 'elevenlabs' : 'openai');
    $defaultNarrationProvider = $provider;
    $providerConfigured = $provider === 'elevenlabs' ? $elevenLabsReady : $openaiReady;
    $characterProfiles = ['fr-FR' => 'Louis', 'es-ES' => 'Pablo', 'en-JM' => 'Irie', 'ht-HT' => 'Jazzy'];
    $configuredCharacterVoices = [];
    foreach ($characterProfiles as $locale => $character) {
        $voice = $locale === 'es-ES'
            ? beyond_config('narration.elevenlabs.character_voices.pablo', '00uevfcKk0GtPlbU69ZH')
            : beyond_config('narration.elevenlabs.voices.' . $locale, beyond_config('voice.voices.' . $locale, ''));
        $voice = is_string($voice) ? trim($voice) : '';
        $configuredCharacterVoices[$locale] = $voice;
    }
    $characterVoiceIds = $configuredCharacterVoices;
    foreach ($characterProfiles as $locale => $character) {
        $voice = $configuredCharacterVoices[$locale];
        $matches = $voice === '' ? [] : array_keys($configuredCharacterVoices, $voice, true);
        $characterVoiceLocales[$locale] = $elevenLabsReady && $voice !== '' && count($matches) === 1;
        if ($characterVoiceLocales[$locale]) $characterVoiceNames[] = $character;
    }
} catch (Throwable $error) {
    error_log('French generator voice status unavailable: ' . $error->getMessage());
}
if ($characterVoiceNames) {
    $characterList = count($characterVoiceNames) > 1
        ? implode(', ', array_slice($characterVoiceNames, 0, -1)) . ' & ' . end($characterVoiceNames)
        : $characterVoiceNames[0];
    $providerLabels[$provider] = $characterList . ' via ElevenLabs · English overview via ' . ($provider === 'openai' ? 'OpenAI Speech' : 'ElevenLabs Premium');
}
$characterVoiceData = htmlspecialchars(json_encode($characterVoiceLocales, JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES, 'UTF-8');
$characterVoiceIdData = htmlspecialchars(json_encode($characterVoiceIds, JSON_UNESCAPED_SLASHES) ?: '{}', ENT_QUOTES, 'UTF-8');
$view = str_replace(
    ['__VOICE_PROVIDER__', '__VOICE_STATUS__', '__VOICE_STATUS_CLASS__', '__CHARACTER_VOICE_LOCALES__', '__CHARACTER_VOICE_IDS__', '__DEFAULT_NARRATION_PROVIDER__'],
    [
        htmlspecialchars($providerLabels[$provider] ?? ucfirst($provider), ENT_QUOTES, 'UTF-8'),
        $providerConfigured ? 'Ready' : 'Needs configuration',
        $providerConfigured ? 'ready' : 'needs-config',
        $characterVoiceData,
        $characterVoiceIdData,
        htmlspecialchars($defaultNarrationProvider, ENT_QUOTES, 'UTF-8'),
    ],
    $view
);

echo str_replace(
    'content="__CSRF_TOKEN__"',
    'content="' . htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8') . '"',
    $view
);
