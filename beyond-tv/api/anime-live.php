<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

require_once __DIR__ . '/../includes/anime-schedule.php';

$state = beyond_anime_schedule_state();
echo json_encode([
    'ok' => true,
    'channel' => 'yugioh-tv',
    'mode' => 'scheduled-anime',
    'timezone' => $state['timezone'],
    'state' => $state,
    'sources' => $state['sources'],
    'start_offset' => $state['start_offset'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
