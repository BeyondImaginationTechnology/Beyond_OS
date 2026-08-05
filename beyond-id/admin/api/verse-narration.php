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
function narration_error(int $s,string $m): void {http_response_code($s);header('Content-Type: application/json');echo json_encode(['error'=>$m]);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST') narration_error(405,'POST required.');
$csrf=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');if(empty($_SESSION['verse_generator_csrf'])||!hash_equals((string)$_SESSION['verse_generator_csrf'],$csrf)) narration_error(419,'Reload the generator and try again.');
$in=json_decode((string)file_get_contents('php://input'),true);$text=trim((string)($in['text']??''));$locale=(string)($in['locale']??'en-US');$provider=strtolower(trim((string)($in['provider']??'')));$voice=trim((string)($in['voice']??''));
if($provider!==''&&!in_array($provider,['openai','elevenlabs','azure'],true)) narration_error(422,'Narration provider is invalid.');
if($voice!==''&&!preg_match('/^[A-Za-z0-9:_-]+$/',$voice)) narration_error(422,'Narration voice is invalid.');
if($text===''||strlen($text)>10000||!in_array($locale,['en-US','fr-FR','fr-CA','es-ES','en-JM','ht-HT'],true)) narration_error(422,'Narration text or language is invalid.');
try{$r=studio_narration_generate($text,$locale,$provider,$voice);$audio=(string)$r['audio_content'];header('Content-Type: audio/mpeg');header('Content-Length: '.strlen($audio));header('Cache-Control: private, no-store');echo $audio;}catch(Throwable $e){error_log('Studio narration: '.$e->getMessage());narration_error(503,$e->getMessage());}
