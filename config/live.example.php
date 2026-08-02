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
    'narration' => [
        'openai' => [
            'api_key' => '',
        ],
    ],
    'music' => [
        'jamendo' => [
            'application' => 'Beyond Music',
            'client_id' => '',
            'client_secret' => '',
            'redirect_url' => '',
            'platform' => 'ios',
            'usage' => 'Non-commercial',
            'home_page' => 'https://beyondimagination.co.technology',
        ],
    ],
];
