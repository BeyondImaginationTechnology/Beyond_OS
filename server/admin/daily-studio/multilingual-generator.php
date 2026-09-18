<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['verse_generator_csrf'])) {
    $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));
}

$view = file_get_contents(__DIR__ . '/generators/multilingual-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Euro Expansion generator view is unavailable.');
}

$view = str_replace('</head>', '<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"></head>', $view);
$rendererPath = __DIR__ . '/assets/beyond-french-remotion-renderer.js';
$rendererVersion = is_file($rendererPath) ? (string)filemtime($rendererPath) : 'missing';
$view = str_replace('__FRENCH_RENDERER_VERSION__', rawurlencode($rendererVersion), $view);

$elevenLabsReady = false;
try {
    $elevenLabsReady = trim((string)beyond_config('narration.elevenlabs.api_key', beyond_config('voice.api_key', ''))) !== '';
    foreach (['fr-FR', 'it-IT', 'de-DE', 'ru-RU', 'pt-PT'] as $locale) {
        $voice = beyond_config('narration.elevenlabs.voices.' . $locale, beyond_config('voice.voices.' . $locale, ''));
        if (!is_string($voice) || trim($voice) === '') $elevenLabsReady = false;
    }
} catch (Throwable $error) {
    error_log('Euro Expansion generator ElevenLabs status unavailable: ' . $error->getMessage());
}

$view = str_replace(
    ['__VOICE_PROVIDER__', '__VOICE_STATUS__', '__VOICE_STATUS_CLASS__'],
    ['ElevenLabs Premium', $elevenLabsReady ? 'Ready' : 'Needs voice IDs', $elevenLabsReady ? 'ready' : 'needs-config'],
    $view
);

echo str_replace(
    'content="__CSRF_TOKEN__"',
    'content="' . htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8') . '"',
    $view
);
