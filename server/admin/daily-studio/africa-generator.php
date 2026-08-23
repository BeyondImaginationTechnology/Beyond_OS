<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (empty($_SESSION['verse_generator_csrf'])) $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));

$view = file_get_contents(__DIR__ . '/generators/africa-generator-view.html');
if ($view === false) { http_response_code(500); exit('Africa expansion generator view is unavailable.'); }
$azureReady = trim((string)beyond_config('narration.azure.api_key', '')) !== ''
    && trim((string)beyond_config('narration.azure.region', '')) !== '';
$lingalaVoice = beyond_config('narration.elevenlabs.voices.ln-CD', beyond_config('voice.voices.ln-CD', ''));
$lingalaReady = trim(is_string($lingalaVoice) ? $lingalaVoice : '') !== ''
    && trim((string)beyond_config('narration.elevenlabs.api_key', beyond_config('voice.api_key', ''))) !== '';
$view = str_replace('</head>', '<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"></head>', $view);
$view = str_replace(
    ['__CSRF_TOKEN__','__AZURE_STATUS__','__AZURE_CLASS__','__LINGALA_STATUS__','__LINGALA_CLASS__'],
    [htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8'), $azureReady?'Ready':'Needs configuration', $azureReady?'ready':'needs', $lingalaReady?'Ready':'Needs native voice', $lingalaReady?'ready':'needs'],
    $view
);
echo $view;

