<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30, stale-while-revalidate=120');

require_once dirname(__DIR__) . '/includes/movies-schedule.php';

$state = beyond_movies_schedule_state();
$sources = array_map(static fn(array $movie): array => [
    'provider' => 'Internet Archive',
    'title' => (string)$movie['title'],
    'url' => (string)$movie['url'],
    'duration' => (int)$movie['duration'],
    'type' => 'video/mp4',
    'license' => 'Archive-hosted source',
    'rights_url' => (string)$movie['rights_url'],
], $state['sources']);

echo json_encode([
    'ok' => true,
    'mode' => 'scheduled-movies',
    'state' => [
        'current' => array_merge($state['current'], [
            'icon' => '🎬',
            'lineup' => (string)$state['current']['year'] . ' · ' . (string)$state['current']['genre'],
        ]),
        'next' => [
            'title' => (string)$state['next']['title'],
        ],
        'label' => $state['label'],
        'player_url' => $state['player_url'],
        'source_key' => $state['source_key'],
        'slot_start' => $state['slot_start'],
        'slot_end' => $state['slot_end'],
        'weekday' => $state['weekday'],
        'slot_hours' => $state['slot_hours'],
    ],
    'sources' => $sources,
    'start_offset' => $state['start_offset'],
    'playlist_duration' => $state['playlist_duration'],
    'timezone' => $state['timezone'],
    'server_time' => $state['server_time'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
