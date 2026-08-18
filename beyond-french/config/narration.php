<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';

$elevenVoices = (array)beyond_config('narration.elevenlabs.voices', beyond_config('voice.voices', []));
$azureDailyVoice = 'en-US-JennyMultilingualNeural';
$azureVoices = (array)beyond_config('narration.azure.voices', [
    'en-US' => [$azureDailyVoice => 'Jenny Multilingual - English/French/Spanish'],
    'fr-CA' => [$azureDailyVoice => 'Jenny Multilingual - French fallback'],
    'fr-FR' => [$azureDailyVoice => 'Jenny Multilingual - French'],
    'es-ES' => [$azureDailyVoice => 'Jenny Multilingual - Spanish'],
    'it-IT' => [$azureDailyVoice => 'Jenny Multilingual - Italian'],
    'de-DE' => [$azureDailyVoice => 'Jenny Multilingual - German'],
    'ru-RU' => [$azureDailyVoice => 'Jenny Multilingual - Russian'],
    'pt-PT' => [$azureDailyVoice => 'Jenny Multilingual - Portuguese'],
    // Azure does not publish dedicated en-JM or ht-HT TTS voices. These
    // controlled fallbacks keep batch exports narrated instead of failing.
    'en-JM' => [$azureDailyVoice => 'Jenny Multilingual - Patois fallback'],
    'ht-HT' => [$azureDailyVoice => 'Jenny Multilingual - Kreyòl fallback'],
]);
foreach (['en-US', 'fr-CA', 'fr-FR', 'es-ES', 'it-IT', 'de-DE', 'ru-RU', 'pt-PT', 'en-JM', 'ht-HT'] as $azureLocale) {
    $configuredVoices = $azureVoices[$azureLocale] ?? [];
    if (is_string($configuredVoices) && trim($configuredVoices) !== '') {
        $configuredVoices = [$configuredVoices => $configuredVoices];
    }
    if (!is_array($configuredVoices)) {
        $configuredVoices = [];
    }
    $azureVoices[$azureLocale] = [$azureDailyVoice => 'Jenny Multilingual - English/French/Spanish'] + $configuredVoices;
}

return [
    'allowed_providers' => ['openai', 'elevenlabs', 'azure'],
    'allowed_formats' => ['mp3'],
    'allowed_languages' => ['en-US', 'fr-CA', 'fr-FR', 'es-ES', 'it-IT', 'de-DE', 'ru-RU', 'pt-PT', 'ht-HT', 'en-JM'],
    // Azure is the controlled server-side fallback for Studio exports.
    // Do not automatically retry quota-limited OpenAI or ElevenLabs accounts.
    'fallback_providers' => ['azure'],
    'max_text_length' => 2000,
    'max_instructions_length' => 800,
    'rate_limits' => [
        'preview' => ['requests' => 30, 'window_seconds' => 3600],
        'generate' => ['requests' => 15, 'window_seconds' => 3600],
        'voices' => ['requests' => 120, 'window_seconds' => 3600],
    ],
    'public_storage_path' => dirname(__DIR__) . '/storage/narration',
    'public_storage_url' => '/beyond-french/storage/narration',
    'providers' => [
        'openai' => [
            'api_key' => (string)beyond_config('narration.openai.api_key', getenv('OPENAI_API_KEY') ?: ''),
            'endpoint' => 'https://api.openai.com/v1/audio/speech',
            'model' => (string)beyond_config('narration.openai.model', 'gpt-4o-mini-tts'),
            'timeout' => 45,
        ],
        'elevenlabs' => [
            'api_key' => (string)beyond_config('narration.elevenlabs.api_key', beyond_config('voice.api_key', '')),
            'endpoint' => 'https://api.elevenlabs.io/v1/text-to-speech',
            'model' => (string)beyond_config('narration.elevenlabs.model', beyond_config('voice.model_id', 'eleven_multilingual_v2')),
            'output_format' => 'mp3_44100_128',
            'voices' => $elevenVoices,
            'timeout' => 45,
        ],
        'azure' => [
            'api_key' => (string)beyond_config('narration.azure.api_key', getenv('AZURE_SPEECH_KEY') ?: ''),
            'region' => (string)beyond_config('narration.azure.region', getenv('AZURE_SPEECH_REGION') ?: ''),
            'output_format' => 'audio-24khz-48kbitrate-mono-mp3',
            'voices' => $azureVoices,
            'timeout' => 45,
        ],
    ],
];
