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
        'scopes' => ['profile:read', 'email:read', 'progress:read', 'progress:write', 'trivia:play'],
    ],
    'beyond-french-ios' => [
        'name' => 'Beyond French for Apple',
        'app_slug' => 'beyond-french',
        'scheme' => 'beyondfrench',
        'scopes' => ['profile:read', 'email:read', 'progress:read', 'progress:write', 'trivia:play', 'account:delete'],
    ],
    'beyond-french-android' => [
        'name' => 'Beyond French for Android',
        'app_slug' => 'beyond-french',
        'scheme' => 'beyondfrenchandroid',
        'scopes' => ['profile:read', 'email:read', 'progress:read', 'progress:write', 'trivia:play', 'account:delete'],
    ],
    'daily-breath-ios' => [
        'name' => 'DailyBreath for Apple',
        'app_slug' => 'dailybreath',
        'scheme' => 'dailybreath',
        'scopes' => ['profile:read', 'email:read', 'streaks:write', 'account:delete'],
    ],
    'jaguar-ios' => [
        'name' => 'Llama Jaguar for Apple',
        'app_slug' => 'jaguar',
        'scheme' => 'jaguar',
        'scopes' => ['profile:read'],
    ],
];
