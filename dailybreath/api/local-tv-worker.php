<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../includes/web-app.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';
require_once __DIR__ . '/../../includes/narration/StudioNarration.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$token=trim((string)beyond_config('dailybreath.local_worker_token',''));
$given=preg_replace('/^Bearer\s+/i','',(string)($_SERVER['HTTP_AUTHORIZATION']??''));
if($token===''||$given===''||!hash_equals($token,$given)){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Unauthorized']);exit;}
$tz=new DateTimeZone('America/Vancouver');$now=new DateTimeImmutable('now',$tz);$date=$now->format('Y-m-d');
$requestedDate=trim((string)($_GET['date']??''));
if($requestedDate!==''){
  $requested=DateTimeImmutable::createFromFormat('!Y-m-d',$requestedDate,$tz);
  $errors=DateTimeImmutable::getLastErrors();
  if(!$requested||($errors!==false&&($errors['warning_count']>0||$errors['error_count']>0))||$requested->format('Y-m-d')!==$requestedDate){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'date must use YYYY-MM-DD']);exit;}
  $date=$requestedDate;
}
$tradition=(string)($_GET['tradition']??'bible');$tradition=$tradition==='tanakh'?'torah':$tradition;
$profiles=['bible'=>['locale'=>'en','kind'=>'Verse','series'=>'Bible Verse of the Day','direction'=>'ltr'],'torah'=>['locale'=>'he','kind'=>'Tanakh Passage','series'=>'Tanakh Passage of the Day','direction'=>'rtl'],'quran'=>['locale'=>'ar','kind'=>'Quran Ayah','series'=>'Quran Ayah of the Day','direction'=>'rtl']];
if(!isset($profiles[$tradition])){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Unsupported tradition']);exit;}
$profile=$profiles[$tradition];$pdo=beyond_db();$content=dailybreath_published_content($pdo,$date,$tradition,$profile['locale'])??dailybreath_interfaith_verse_of_day($pdo,$tradition,$profile['locale'],$date);
$passage=trim((string)($content['passage']??$content['text']??''));$reference=trim((string)($content['reference']??''));
if($passage===''||$reference===''){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Reading unavailable']);exit;}
$script=dailybreath_narration_script(['passage'=>$passage,'reference'=>$reference]);
$voiceLocale=['en'=>'en-US','he'=>'he-IL','ar'=>'ar-SA'][$profile['locale']]??'';
$voice=$voiceLocale===''?'':($tradition==='bible'?studio_character_voice('chris',$voiceLocale):studio_narration_voice('elevenlabs',$voiceLocale));
if($voice===''){http_response_code(503);echo json_encode(['ok'=>false,'error'=>'A dedicated ElevenLabs voice is required for this stream. Configure Chris for English Bible narration and language-matched voices for Tanakh and Quran.']);exit;}
$audio=dailybreath_audio_for_script($pdo,$date,$tradition,$profile['locale'],$script);
if(!is_array($audio)||trim((string)($audio['voice_id']??''))!==$voice){
  try{
    $generated=studio_narration_generate($script,$voiceLocale,'elevenlabs',$voice);
    $stored=studio_store_mp3((string)$generated['audio_content'],'daily-breath',$date,$voiceLocale,$script."\n".$voice);
    dailybreath_ensure_audio_table($pdo);
    $values=[$date,$tradition,$profile['locale'],hash('sha256',$script),(string)$stored['url'],$voice,'elevenlabs'];
    if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'){$query=$pdo->prepare('INSERT OR REPLACE INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?)');}
    else{$query=$pdo->prepare('INSERT INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE script_hash=VALUES(script_hash),audio_url=VALUES(audio_url),voice_id=VALUES(voice_id),provider=VALUES(provider),generated_at=CURRENT_TIMESTAMP');}
    $query->execute($values);
    $audio=['audio_url'=>(string)$stored['url'],'voice_id'=>$voice,'provider'=>'elevenlabs'];
  }catch(Throwable $error){http_response_code(502);echo json_encode(['ok'=>false,'error'=>'Narration generation failed. '.$error->getMessage()]);exit;}
}
echo json_encode(['ok'=>true,'date'=>$date,'label'=>$now->format('l, F j, Y'),'tradition'=>$tradition,'locale'=>$profile['locale'],'kind'=>$profile['kind'],'series'=>$profile['series'],'direction'=>$profile['direction'],'passage'=>$passage,'reference'=>$reference,'audio_url'=>(string)$audio['audio_url'],'voice_id'=>$voice,'guide_name'=>$tradition==='bible'?'Chris':null,'content_hash'=>hash('sha256',$passage."\n\n".$reference)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
