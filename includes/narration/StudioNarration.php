<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/config/bootstrap.php';
require_once dirname(__DIR__,2) . '/beyond-french/includes/narration/NarrationProvider.php';
require_once dirname(__DIR__,2) . '/beyond-french/includes/narration/NarrationService.php';
require_once dirname(__DIR__,2) . '/beyond-french/includes/narration/OpenAIProvider.php';
require_once dirname(__DIR__,2) . '/beyond-french/includes/narration/ElevenLabsProvider.php';
require_once dirname(__DIR__,2) . '/beyond-french/includes/narration/AzureSpeechProvider.php';

function studio_narration_config(): array { return require dirname(__DIR__,2) . '/beyond-french/config/narration.php'; }

function studio_elevenlabs_first_voice(array $providerConfig): string {
  $apiKey=trim((string)($providerConfig['api_key']??''));
  if($apiKey===''||!function_exists('curl_init')) return '';
  $ch=curl_init('https://api.elevenlabs.io/v2/voices?page_size=20');
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>25,CURLOPT_HTTPHEADER=>['xi-api-key: '.$apiKey,'Accept: application/json']]);
  $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
  if($status<200||$status>=300||!is_string($body)){error_log('ElevenLabs voice discovery failed: HTTP '.$status.' '.$err);return '';}
  $data=json_decode($body,true);if(!is_array($data))return '';
  $voices=$data['voices']??[];if(!is_array($voices)||!$voices)return '';
  foreach($voices as $voice){$id=trim((string)($voice['voice_id']??''));if($id!=='')return $id;}
  return '';
}
function studio_narration_provider(): string { return strtolower((string)beyond_config('voice.provider','openai')); }
function studio_narration_voice(string $provider,string $locale): string {
  if($provider==='openai') return (string)beyond_config('narration.openai.voices.'.$locale,beyond_config('voice.openai_voice','coral'));
  $azureDefaults=['en-US'=>'en-US-JennyMultilingualNeural','fr-FR'=>'en-US-JennyMultilingualNeural','fr-CA'=>'en-US-JennyMultilingualNeural','es-ES'=>'en-US-JennyMultilingualNeural','en-JM'=>'en-US-JennyMultilingualNeural','ht-HT'=>'en-US-JennyMultilingualNeural'];
  $fallback=$provider==='azure'?($azureDefaults[$locale]??''):beyond_config('voice.voices.'.$locale,'');
  $v=beyond_config('narration.'.$provider.'.voices.'.$locale,$fallback);
  if(is_array($v)) { foreach($v as $k=>$label){ return is_string($k)?$k:(string)$label; } return ''; }
  return (string)$v;
}
function studio_narration_generate(string $text,string $locale,string $preferredProvider='',string $preferredVoice=''): array {
  $cfg=studio_narration_config();
  $service=new NarrationService([
    'openai'=>new OpenAIProvider((array)$cfg['providers']['openai']),
    'elevenlabs'=>new ElevenLabsProvider((array)$cfg['providers']['elevenlabs']),
    'azure'=>new AzureSpeechProvider((array)$cfg['providers']['azure']),
  ]);
  $preferredProvider=strtolower(trim($preferredProvider));
  $primary=in_array($preferredProvider,['openai','elevenlabs','azure'],true)?$preferredProvider:studio_narration_provider();
  // Only use the selected provider and explicitly configured fallbacks. OpenAI
  // used to be appended unconditionally, which made an Azure export end with a
  // misleading OpenAI quota error whenever Azure also needed attention.
  $queue=$preferredProvider!==''?[$primary]:array_values(array_unique(array_merge([$primary],(array)($cfg['fallback_providers']??[]))));
  $lastError=null;
  foreach($queue as $provider){
    $provider=strtolower(trim((string)$provider));
    $providerCfg=(array)($cfg['providers'][$provider]??[]);
    if($provider==='openai' && trim((string)($providerCfg['api_key']??''))==='') continue;
    if($provider==='elevenlabs' && trim((string)($providerCfg['api_key']??''))==='') continue;
    if($provider==='azure' && (trim((string)($providerCfg['api_key']??''))===''||trim((string)($providerCfg['region']??''))==='')) continue;
    $voice=trim($preferredVoice)!==''?trim($preferredVoice):studio_narration_voice($provider,$locale);
    if($provider==='openai' && $voice==='') $voice='coral';
    if($provider==='elevenlabs' && $voice==='') {
      $lastError=new RuntimeException('No ElevenLabs voice is selected for '.$locale.'. Choose the original speaker in Premium Voices.');
      continue;
    }
    try{
      return $service->generate($provider,[
        'text'=>$text,'language'=>$locale,'voice'=>$voice,'format'=>'mp3','speed'=>1.0,
        'instructions'=>'Warm, clear, natural premium narration. Preserve scripture references and French pronunciation accurately.'
      ]);
    }catch(Throwable $error){
      $lastError=$error;
      error_log('Studio narration provider '.$provider.' failed: '.$error->getMessage());
    }
  }
  if($lastError instanceof Throwable) throw $lastError;
  throw new RuntimeException('No narration provider is fully configured. Add an ElevenLabs, OpenAI, or Azure Speech key in Premium Voices.');
}
function studio_store_mp3(string $audio,string $library,string $date,string $locale,string $text): array {
  if(strlen($audio)<128) throw new RuntimeException('The narration provider returned invalid audio.');
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date=date('Y-m-d');
  $year=substr($date,0,4);$month=substr($date,5,2);
  $folder=$library==='daily-breath'?'dailybreath':$library;
  $base=dirname(__DIR__,2).'/'.$folder.'/assets/audio/'.$year.'/'.$month;
  if(!is_dir($base) && !mkdir($base,0775,true) && !is_dir($base)) throw new RuntimeException('The audio library could not be created. Check PHP write permissions for '.$folder.'/assets/audio.');
  if(!is_writable($base)) throw new RuntimeException('The audio library is not writable. Set '.$folder.'/assets/audio to 775 on the server.');
  $slug=$library==='beyond-french'?'francais-du-jour':'daily-breath';
  $name=$slug.'-'.$date.'-'.strtolower(str_replace('-','_',$locale)).'-'.substr(hash('sha256',$text),0,10).'.mp3';
  $file=$base.'/'.$name;
  if(!is_file($file) && file_put_contents($file,$audio,LOCK_EX)===false) throw new RuntimeException('The MP3 could not be saved.');
  @chmod($file,0644);
  return ['file'=>$file,'name'=>$name,'url'=>'/'.$folder.'/assets/audio/'.$year.'/'.$month.'/'.rawurlencode($name)];
}
