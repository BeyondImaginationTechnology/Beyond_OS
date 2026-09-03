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
  $azureDefaults=['en-US'=>'en-US-JennyNeural','fr-FR'=>'fr-FR-DeniseNeural','fr-CA'=>'fr-CA-SylvieNeural','es-ES'=>'es-ES-ElviraNeural','it-IT'=>'it-IT-IsabellaNeural','de-DE'=>'de-DE-KatjaNeural','ru-RU'=>'ru-RU-SvetlanaNeural','pt-PT'=>'pt-PT-RaquelNeural','ar-MA'=>'ar-MA-MounaNeural','ar-EG'=>'ar-EG-SalmaNeural','sw-KE'=>'sw-KE-ZuriNeural'];
  $fallback=$provider==='azure'?($azureDefaults[$locale]??''):beyond_config('voice.voices.'.$locale,'');
  $v=beyond_config('narration.'.$provider.'.voices.'.$locale,$fallback);
  if(is_array($v)) { $selected=''; foreach($v as $k=>$label){ $selected=is_string($k)?$k:(string)$label; break; } $v=$selected; }
  $v=trim((string)$v);
  if($provider==='azure' && $locale!=='en-US' && $v==='en-US-JennyMultilingualNeural') return $fallback;
  return $v;
}
function studio_assert_mp3(string $audio): void {
  if(strlen($audio)<128) throw new RuntimeException('The narration provider returned invalid audio.');
  if(substr($audio,0,3)==='ID3') return;
  $limit=min(strlen($audio)-1,4096);
  for($i=0;$i<$limit;$i++){
    $first=ord($audio[$i]);$second=ord($audio[$i+1]);
    if($first===0xff && ($second&0xe0)===0xe0 && ($second&0x18)!==0x08 && ($second&0x06)!==0) return;
  }
  throw new RuntimeException('The narration provider did not return a valid MP3. No audio file was saved; regenerate the narration.');
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
  // Azure has no native Haitian Kreyòl or Jamaican Patois voice. When Studio
  // default routing is used, prefer each locale's configured ElevenLabs
  // speaker instead of trying the active Azure provider and reporting that the
  // language is no longer configured.
  if($preferredProvider==='' && in_array($locale,['ht-HT','en-JM'],true)){
    $elevenConfig=(array)($cfg['providers']['elevenlabs']??[]);
    if(trim((string)($elevenConfig['api_key']??''))!=='' && studio_narration_voice('elevenlabs',$locale)!=='') $primary='elevenlabs';
  }
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
    if($provider==='azure' && $voice==='') {
      $lastError=new RuntimeException('Azure has no native voice selected for '.$locale.'. Use a matching ElevenLabs voice for Haitian Kreyol or Jamaican Patois.');
      continue;
    }
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
  studio_assert_mp3($audio);
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date=date('Y-m-d');
  $year=substr($date,0,4);$month=substr($date,5,2);
  $folder=$library==='daily-breath'?'dailybreath':$library;
  $base=dirname(__DIR__,2).'/'.$folder.'/assets/audio/'.$year.'/'.$month;
  if(!is_dir($base) && !mkdir($base,0775,true) && !is_dir($base)) throw new RuntimeException('The audio library could not be created. Check PHP write permissions for '.$folder.'/assets/audio.');
  if(!is_writable($base)) throw new RuntimeException('The audio library is not writable. Set '.$folder.'/assets/audio to 775 on the server.');
  $slug=$library==='beyond-french'?'francais-du-jour':($library==='beyond-space'?'beyond-space-horoscope':'daily-breath');
  $name=$slug.'-'.$date.'-'.strtolower(str_replace('-','_',$locale)).'-'.substr(hash('sha256',$text),0,10).'.mp3';
  $file=$base.'/'.$name;
  $write=!is_file($file);
  if(!$write){
    $existing=file_get_contents($file);
    try{studio_assert_mp3(is_string($existing)?$existing:'');}catch(RuntimeException){$write=true;}
  }
  if($write && file_put_contents($file,$audio,LOCK_EX)===false) throw new RuntimeException('The MP3 could not be saved.');
  @chmod($file,0644);
  return ['file'=>$file,'name'=>$name,'url'=>'/'.$folder.'/assets/audio/'.$year.'/'.$month.'/'.rawurlencode($name)];
}
