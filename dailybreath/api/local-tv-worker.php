<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../includes/web-app.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$token=trim((string)beyond_config('dailybreath.local_worker_token',''));
$given=preg_replace('/^Bearer\s+/i','',(string)($_SERVER['HTTP_AUTHORIZATION']??''));
if($token===''||$given===''||!hash_equals($token,$given)){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Unauthorized']);exit;}
$tz=new DateTimeZone('America/Vancouver');$now=new DateTimeImmutable('now',$tz);$date=$now->format('Y-m-d');
$tradition=(string)($_GET['tradition']??'bible');$tradition=$tradition==='tanakh'?'torah':$tradition;
$profiles=['bible'=>['locale'=>'en','kind'=>'Verse','series'=>'Bible Verse of the Day','direction'=>'ltr'],'torah'=>['locale'=>'he','kind'=>'Tanakh Passage','series'=>'Tanakh Passage of the Day','direction'=>'rtl'],'quran'=>['locale'=>'ar','kind'=>'Quran Ayah','series'=>'Quran Ayah of the Day','direction'=>'rtl']];
if(!isset($profiles[$tradition])){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Unsupported tradition']);exit;}
$profile=$profiles[$tradition];$pdo=beyond_db();$content=dailybreath_published_content($pdo,$date,$tradition,$profile['locale'])??dailybreath_interfaith_verse_of_day($pdo,$tradition,$profile['locale'],$date);
$passage=trim((string)($content['passage']??$content['text']??''));$reference=trim((string)($content['reference']??''));
if($passage===''||$reference===''){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Reading unavailable']);exit;}
echo json_encode(['ok'=>true,'date'=>$date,'label'=>$now->format('l, F j, Y'),'tradition'=>$tradition,'locale'=>$profile['locale'],'kind'=>$profile['kind'],'series'=>$profile['series'],'direction'=>$profile['direction'],'passage'=>$passage,'reference'=>$reference,'content_hash'=>hash('sha256',$passage."\n\n".$reference)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);