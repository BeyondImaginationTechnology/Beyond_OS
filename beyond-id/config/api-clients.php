<?php
declare(strict_types=1);

/**
 * First-party public clients. These clients never receive a client secret;
 * native authorization is protected with PKCE and an exact callback scheme.
 */
return [
    'beyond-music-ios' => [
        'name' => 'Beyond Music for Apple',
        'app_slug' => 'beyond-music',
        'scheme' => 'beyondmusic',
        'scopes' => ['profile:read', 'email:read', 'wallet:read'],
    ],
    'beyond-tv-ios' => [
        'name' => 'Beyond TV for Apple',
        'app_slug' => 'beyond-tv',
        'scheme' => 'beyondtv',
        'scopes' => ['profile:read', 'email:read', 'watchlist:write'],
    ],
    'french-quest-ios' => [
        'name' => 'French Quest for Apple',
        'app_slug' => 'beyond-french',
        'scheme' => 'frenchquest',
        'scopes' => ['profile:read', 'email:read', 'progress:write'],
    ],
    'daily-breath-ios' => [
        'name' => 'DailyBreath for Apple',
        'app_slug' => 'dailybreath',
        'scheme' => 'dailybreath',
        'scopes' => ['profile:read', 'email:read', 'streaks:write'],
    ],
    'jaguar-ios' => [
        'name' => 'Llama Jaguar for Apple',
        'app_slug' => 'jaguar',
        'scheme' => 'jaguar',
        'scopes' => ['profile:read'],
    ],
];
