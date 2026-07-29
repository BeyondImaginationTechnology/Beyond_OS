<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

if (empty($_SESSION['verse_generator_csrf'])) {
    $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));
}

$view = file_get_contents(__DIR__ . '/generators/french-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Français du Jour generator view is unavailable.');
}
$view = str_replace('</head>', '<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"></head>', $view);

$providerLabels = [
    'openai' => 'OpenAI Speech · gpt-4o-mini-tts',
    'elevenlabs' => 'ElevenLabs Premium',
    'azure' => 'Azure Speech',
];
$provider = 'openai';
$providerConfigured = false;
try {
    $provider = strtolower((string)beyond_config('voice.provider', 'openai'));
    $providerConfigured = match ($provider) {
        'openai' => trim((string)beyond_config('narration.openai.api_key', '')) !== '',
        'elevenlabs' => trim((string)beyond_config('narration.elevenlabs.api_key', '')) !== '',
        'azure' => trim((string)beyond_config('narration.azure.api_key', '')) !== ''
            && trim((string)beyond_config('narration.azure.region', '')) !== '',
        default => false,
    };
} catch (Throwable $error) {
    error_log('French generator voice status unavailable: ' . $error->getMessage());
}
$view = str_replace(
    ['__VOICE_PROVIDER__', '__VOICE_STATUS__', '__VOICE_STATUS_CLASS__'],
    [
        htmlspecialchars($providerLabels[$provider] ?? ucfirst($provider), ENT_QUOTES, 'UTF-8'),
        $providerConfigured ? 'Ready' : 'Needs configuration',
        $providerConfigured ? 'ready' : 'needs-config',
    ],
    $view
);

echo str_replace(
    'content="__CSRF_TOKEN__"',
    'content="' . htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8') . '"',
    $view
);
