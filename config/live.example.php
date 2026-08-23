<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Beyond OS',
        'env' => 'local',
        'url' => 'http://localhost:8080',
        'timezone' => 'America/Los_Angeles',
        'google_maps_api_key' => '',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'name' => 'beyond_os',
        'user' => 'beyond_os',
        'pass' => '',
    ],
    'smtp' => [
        'host' => '',
        'port' => 465,
        'secure' => 'ssl',
        'user' => '',
        'pass' => '',
        'from' => '',
        'from_name' => 'Beyond OS',
        'reply_to' => '',
    ],
    'stripe' => [
        'public_key' => '',
        'secret_key' => '',
        'webhook_secret' => '',
        'academy_webhook_secret' => '',
        'academy_price_id' => '',
    ],
    'security' => [
        'jwt_secret' => 'replace-with-a-long-random-local-secret',
    ],
    'remotion' => [
        'bridge_url' => 'https://render.beyondimagination.co.technology',
        'bridge_token' => 'replace-with-the-render-bridge-token',
    ],
    'narration' => [
        'openai' => [
            'api_key' => '',
        ],
        'azure' => [
            'api_key' => '',
            'region' => 'canadacentral',
            'voices' => [
                'en-US' => 'en-US-JennyNeural',
                'fr-FR' => 'fr-FR-DeniseNeural',
                'fr-CA' => 'fr-CA-SylvieNeural',
                'es-ES' => 'es-ES-ElviraNeural',
                'it-IT' => 'it-IT-IsabellaNeural',
                'de-DE' => 'de-DE-KatjaNeural',
                'ru-RU' => 'ru-RU-SvetlanaNeural',
                'pt-PT' => 'pt-PT-RaquelNeural',
            ],
        ],
        'elevenlabs' => [
            'api_key' => '',
            'model' => 'eleven_multilingual_v2',
            'voices' => [
                // Azure does not offer native ht-HT or en-JM voices.
                'ht-HT' => '',
                'en-JM' => '',
            ],
        ],
    ],
    'ai' => [
        'azure_image' => [
            'api_key' => '',
            'endpoint' => 'https://your-resource.services.ai.azure.com',
            'model' => 'MAI-Image-2.5',
            'width' => 768,
            'height' => 1365,
        ],
        'azure_translator' => [
            'api_key' => '',
            'endpoint' => 'https://api.cognitive.microsofttranslator.com',
            'region' => 'canadacentral',
        ],
    ],
    'music' => [
        'youtube' => [
            // Optional: leave blank to reuse the first key from config/youtube.php.
            'data_api_key' => '',
            // Required for YouTube MP3 downloads. Hostdeal can proxy this, but the
            // converter itself must run on Python + FFmpeg hosting.
            'audio_api_base_url' => '',
        ],
    ],
];
