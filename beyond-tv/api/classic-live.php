<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
require_once __DIR__ . '/../includes/classic-schedule.php';
$state = beyond_classic_schedule_state();
echo json_encode([
    'ok'=>true,
    'mode'=>'youtube-library',
    'channel'=>['slug'=>'classic-cartoon-theater','name'=>'Beyond Animated Classics','access'=>'public','live'=>true],
    'state'=>$state,
    'sources'=>[],
    'start_offset'=>0,
    'embed_fallback'=>$state['embed_url'],
    'fallbacks'=>$state['fallbacks'],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
