<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';

$elevenVoices = (array)beyond_config('narration.elevenlabs.voices', beyond_config('voice.voices', []));
$azureDefaults = [
    'en-US' => ['en-US-AriaNeural', 'Aria - English (United States)'],
    'fr-CA' => ['fr-CA-SylvieNeural', 'Sylvie - French (Canada)'],
    'fr-FR' => ['fr-FR-DeniseNeural', 'Denise - French (France)'],
    'es-ES' => ['es-ES-ElviraNeural', 'Elvira - Spanish (Spain)'],
    'it-IT' => ['it-IT-IsabellaNeural', 'Isabella - Italian (Italy)'],
    'de-DE' => ['de-DE-KatjaNeural', 'Katja - German (Germany)'],
    'ru-RU' => ['ru-RU-SvetlanaNeural', 'Svetlana - Russian (Russia)'],
    'pt-PT' => ['pt-PT-RaquelNeural', 'Raquel - Portuguese (Portugal)'],
    'ar-MA' => ['ar-MA-MounaNeural', 'Mouna - Moroccan Arabic (Darija)'],
    'ar-EG' => ['ar-EG-ShakirNeural', 'Shakir - Egyptian Arabic'],
    'sw-KE' => ['sw-KE-ZuriNeural', 'Zuri - Kiswahili (Kenya)'],
];
$configuredAzureVoices = (array)beyond_config('narration.azure.voices', []);
$azureVoices = [];
foreach ($azureDefaults as $azureLocale => [$defaultVoice, $defaultLabel]) {
    $configuredVoices = $configuredAzureVoices[$azureLocale] ?? [];
    if (is_string($configuredVoices) && trim($configuredVoices) !== '') {
        $configuredVoices = [$configuredVoices => $configuredVoices];
    }
    if (!is_array($configuredVoices)) {
        $configuredVoices = [];
    }
    // Retire the old one-speaker multilingual mapping for non-English locales.
    // It produced valid audio, but the accent and speaker did not match the
    // language shown in the dictionary.
    if ($azureLocale !== 'en-US') {
        unset($configuredVoices['en-US-JennyMultilingualNeural']);
    }
    $azureVoices[$azureLocale] = $configuredVoices + [$defaultVoice => $defaultLabel];
}

return [
    'allowed_providers' => ['openai', 'elevenlabs', 'azure'],
    'allowed_formats' => ['mp3'],
    'allowed_languages' => ['en-US', 'fr-CA', 'fr-FR', 'es-ES', 'it-IT', 'de-DE', 'ru-RU', 'pt-PT', 'ht-HT', 'en-JM', 'ln-CD', 'ar-MA', 'ar-EG', 'sw-KE'],
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
