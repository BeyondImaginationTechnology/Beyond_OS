<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../includes/space-schedule.php';
$state = beyond_space_schedule_state();
echo json_encode([
    'ok' => true,
    'state' => $state,
    'sources' => $state['sources'] ?? [],
    'start_offset' => $state['start_offset'] ?? 0,
    'player_url' => $state['player_url'] ?? null,
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
