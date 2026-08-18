<?php
declare(strict_types=1);
function studio_mp3_fatal_catcher() {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array('error' => 'PHP narration fatal: ' . basename($e['file']) . ' line ' . $e['line'] . ' — ' . $e['message']));
}
register_shutdown_function('studio_mp3_fatal_catcher');

require __DIR__ . '/../../includes/admin-check.php';
require_once __DIR__ . '/../../../includes/narration/StudioNarration.php';
function failx(int $s,string $m): void {http_response_code($s);header('Content-Type: application/json');echo json_encode(['error'=>$m]);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') failx(405,'POST required.');
$csrf=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');if(empty($_SESSION['verse_generator_csrf'])||!hash_equals((string)$_SESSION['verse_generator_csrf'],$csrf)) failx(419,'Reload the generator and try again.');
$in=json_decode((string)file_get_contents('php://input'),true);$text=trim((string)($in['text']??''));$locale=(string)($in['locale']??'en-US');$date=(string)($in['publish_date']??date('Y-m-d'));$provider=strtolower(trim((string)($in['provider']??'')));$voice=trim((string)($in['voice']??''));$library='beyond-french';
if($provider!==''&&!in_array($provider,['openai','elevenlabs','azure'],true)) failx(422,'Invalid narration provider.');
if($voice!==''&&!preg_match('/^[A-Za-z0-9:_-]+$/',$voice)) failx(422,'Invalid narration voice.');
if(!in_array($library,['daily-breath','beyond-french'],true)||$text===''||strlen($text)>10000||!in_array($locale,['en-US','fr-FR','fr-CA','es-ES','it-IT','de-DE','ru-RU','pt-PT','en-JM','ht-HT'],true)) failx(422,'Invalid MP3 export request.');
try{$r=studio_narration_generate($text,$locale,$provider,$voice);$saved=studio_store_mp3((string)$r['audio_content'],$library,$date,$locale,$text);header('Content-Type: audio/mpeg');header('Content-Length: '.filesize($saved['file']));header('Content-Disposition: attachment; filename="'.$saved['name'].'"');header('X-Saved-Audio-Url: '.$saved['url']);header('Cache-Control: private, no-store');readfile($saved['file']);}catch(Throwable $e){error_log('Studio MP3 export: '.$e->getMessage());failx(503,$e->getMessage());}
