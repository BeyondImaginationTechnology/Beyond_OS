<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
function africaResponse(array $payload, int $status=200): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function africaWrite(string $file, array $items): void {
    $json=json_encode(array_values($items),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $tmp=$file.'.tmp';
    if($json===false||file_put_contents($tmp,$json.PHP_EOL,LOCK_EX)===false||!rename($tmp,$file)){@unlink($tmp);throw new RuntimeException('The Africa expansion library could not be saved.');}
}
function africaText(array $input,string $field,int $max): string {
    $value=trim((string)($input[$field]??''));
    if($value===''||mb_strlen($value)>$max) throw new InvalidArgumentException('Complete every required phrase field before saving.');
    return $value;
}
function africaAzureRequest(string $path, array $body): array {
    $key=trim((string)beyond_config('ai.azure_translator.api_key',''));
    $usingSpeechFallback=$key==='';
    $region=trim((string)beyond_config('ai.azure_translator.region',''));
    if($key==='')$key=trim((string)beyond_config('narration.azure.api_key',''));
    if($region===''&&$usingSpeechFallback)$region=trim((string)beyond_config('narration.azure.region',''));
    $endpoint=rtrim(trim((string)beyond_config('ai.azure_translator.endpoint','https://api.cognitive.microsofttranslator.com')),'/');
    if($key===''||($usingSpeechFallback&&$region===''))throw new RuntimeException('Configure the Azure Translator key, or the Azure Speech key and region, in Premium Voices.');
    if(!function_exists('curl_init'))throw new RuntimeException('The PHP cURL extension is required.');
    if(!preg_match('#^https://[a-z0-9.-]+$#i',$endpoint))throw new RuntimeException('The Azure Translator endpoint is invalid.');
    $prefix=str_contains($endpoint,'cognitive.microsofttranslator.com')?'':'/translator/text/v3.0';
    $headers=['Ocp-Apim-Subscription-Key: '.$key,'Content-Type: application/json'];if($region!==''&&strtolower($region)!=='global')$headers[]='Ocp-Apim-Subscription-Region: '.$region;
    $curl=curl_init($endpoint.$prefix.$path);curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>json_encode($body,JSON_THROW_ON_ERROR)]);
    $raw=curl_exec($curl);$error=curl_error($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);$decoded=is_string($raw)?json_decode($raw,true):null;
    if($status<200||$status>=300||!is_array($decoded))throw new RuntimeException($error?:(string)($decoded['error']['message']??'Azure translation failed.'));
    return $decoded;
}
function africaAzureTranslate(string $english): array {
    $translated=africaAzureRequest('/translate?api-version=3.0&from=en&to=ln&to=ar&to=sw',[['Text'=>$english]]);$values=[];
    foreach((array)($translated[0]['translations']??[]) as $item)$values[(string)($item['to']??'')]=trim((string)($item['text']??''));
    foreach(['ln','ar','sw'] as $language)if(($values[$language]??'')==='')throw new RuntimeException('Azure did not return every Africa expansion language.');
    $arabicLatin='';
    try{$romanized=africaAzureRequest('/transliterate?api-version=3.0&language=ar&fromScript=Arab&toScript=Latn',[['Text'=>$values['ar']]]);$arabicLatin=trim((string)($romanized[0]['text']??''));}catch(Throwable $error){error_log('Azure Arabic transliteration unavailable: '.$error->getMessage());}
    return ['lingala'=>$values['ln'],'lingala_pronunciation'=>$values['ln'],'darija'=>$values['ar'],'darija_transliteration'=>$arabicLatin,'egyptian_arabic'=>$values['ar'],'egyptian_transliteration'=>$arabicLatin,'swahili'=>$values['sw'],'swahili_pronunciation'=>$values['sw']];
}
function africaGenerateTracks(array $values,string $date): array {
    $audioUrls=[];$audioVoices=[];$audioProviders=[];
    $tracks=['ln'=>['locale'=>'ln-CD','text'=>$values['lingala'],'provider'=>'elevenlabs'],'ma'=>['locale'=>'ar-MA','text'=>$values['darija'],'provider'=>'azure'],'eg'=>['locale'=>'ar-EG','text'=>$values['egyptian_arabic'],'provider'=>'azure'],'sw'=>['locale'=>'sw-KE','text'=>$values['swahili'],'provider'=>'azure']];
    foreach($tracks as $key=>$track){$generated=studio_narration_generate($track['text'],$track['locale'],$track['provider']);$stored=studio_store_mp3((string)$generated['audio_content'],'beyond-french',$date,$track['locale'],$track['text']);$audioUrls[$key]=(string)$stored['url'];$audioVoices[$key]=(string)($generated['voice']??'');$audioProviders[$key]=(string)($generated['provider']??$track['provider']);}
    return ['urls'=>$audioUrls,'voices'=>$audioVoices,'providers'=>$audioProviders];
}

$file=dirname(__DIR__,4).'/beyond-french/data/africa-expansion.json';
$items=is_file($file)?json_decode((string)file_get_contents($file),true):[];
if(!is_array($items))$items=[];
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
    usort($items,static fn(array $a,array $b):int=>strcmp((string)($b['publish_date']??''),(string)($a['publish_date']??'')));
    $complete=count(array_filter($items,static fn(array $item):bool=>count((array)($item['audio_urls']??[]))===4));
    africaResponse(['ok'=>true,'items'=>array_slice($items,0,50),'counts'=>['total'=>count($items),'accepted'=>count(array_filter($items,static fn(array $item):bool=>!empty($item['azure_translation_accepted']))),'prerecorded'=>$complete],'target'=>100]);
}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')africaResponse(['ok'=>false,'error'=>'Unsupported request.'],405);
if(empty($_SESSION['verse_generator_csrf'])||!hash_equals((string)$_SESSION['verse_generator_csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'')))africaResponse(['ok'=>false,'error'=>'Reload the generator and try again.'],419);
$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))africaResponse(['ok'=>false,'error'=>'Invalid request.'],400);

try{
    if(strtolower((string)($input['action']??''))==='translate'){
        $english=africaText($input,'english',220);$translations=africaAzureTranslate($english);
        africaResponse(['ok'=>true,'translations'=>$translations,'review_required'=>false,'message'=>'Azure Batch v1 translation accepted and ready to prerecord.']);
    }
    if(strtolower((string)($input['action']??''))==='build'){
        @set_time_limit(240);$source=json_decode((string)file_get_contents(dirname(__DIR__,4).'/beyond-french/data/lessons.json'),true);if(!is_array($source))throw new RuntimeException('The source phrase bank is unavailable.');
        if(count($items)>=100)africaResponse(['ok'=>true,'complete'=>true,'ready'=>count($items),'target'=>100,'message'=>'The Azure Batch v1 phrase bank is complete.']);
        $used=array_fill_keys(array_map(static fn(array $item):string=>(string)($item['source_id']??''),$items),true);$lesson=null;
        foreach($source as $candidate){$sourceId=(string)($candidate['id']??sha1((string)($candidate['english']??'')));if(trim((string)($candidate['english']??''))!==''&&!isset($used[$sourceId])){$lesson=$candidate;break;}}
        if($lesson===null)africaResponse(['ok'=>true,'complete'=>true,'ready'=>count($items),'target'=>100,'message'=>'The Azure Batch v1 phrase bank is complete.']);
        $sourceId=(string)($lesson['id']??sha1((string)$lesson['english']));$date=(string)($lesson['date']??'');if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=(new DateTimeImmutable('today'))->modify('+'.count($items).' days')->format('Y-m-d');
        $translated=africaAzureTranslate((string)$lesson['english']);if(($translated['darija_transliteration']??'')==='')$translated['darija_transliteration']=$translated['darija'];if(($translated['egyptian_transliteration']??'')==='')$translated['egyptian_transliteration']=$translated['egyptian_arabic'];
        $values=['english'=>(string)$lesson['english'],'french_bridge'=>(string)($lesson['french']??''),'meaning'=>(string)($lesson['meaning']??'A practical phrase for everyday conversation.'),...$translated,'culture_note'=>(string)($lesson['culture_note']??'Practice this phrase aloud in an everyday conversation.')];
        $maxId=0;foreach($items as $existingItem)$maxId=max($maxId,(int)($existingItem['id']??0));
        $audio=africaGenerateTracks($values,$date);$item=[...$values,'id'=>$maxId+1,'source_id'=>$sourceId,'pack'=>'africa-v1','publish_date'=>$date,'translation_policy'=>'azure-batch-v1','azure_translation_accepted'=>true,'native_reviewed'=>false,'audio_urls'=>$audio['urls'],'audio_voices'=>$audio['voices'],'audio_providers'=>$audio['providers'],'generator'=>['version'=>'1.1.0','translation'=>'azure','saved_at'=>date(DATE_ATOM)]];$items[]=$item;africaWrite($file,$items);
        africaResponse(['ok'=>true,'built'=>$item,'ready'=>count($items),'target'=>100,'complete'=>count($items)>=100,'message'=>'Azure translated and prerecorded phrase '.count($items).' of 100.']);
    }
    $date=trim((string)($input['publish_date']??''));$parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if(!$parsed||$parsed->format('Y-m-d')!==$date)throw new InvalidArgumentException('Choose a valid publication date.');
    $values=[
        'english'=>africaText($input,'english',220),'french_bridge'=>africaText($input,'french_bridge',220),'meaning'=>africaText($input,'meaning',600),
        'lingala'=>africaText($input,'lingala',240),'lingala_pronunciation'=>africaText($input,'lingala_pronunciation',240),
        'darija'=>africaText($input,'darija',240),'darija_transliteration'=>africaText($input,'darija_transliteration',240),
        'egyptian_arabic'=>africaText($input,'egyptian_arabic',240),'egyptian_transliteration'=>africaText($input,'egyptian_transliteration',240),
        'swahili'=>africaText($input,'swahili',240),'swahili_pronunciation'=>africaText($input,'swahili_pronunciation',240),
        'culture_note'=>africaText($input,'culture_note',800),
    ];
    $prerecord=!empty($input['prerecord']);
    $existingIndex=null;$maxId=0;
    foreach($items as $index=>$item){$maxId=max($maxId,(int)($item['id']??0));if((string)($item['publish_date']??'')===$date)$existingIndex=$index;}
    if($existingIndex!==null&&empty($input['confirm_overwrite']))africaResponse(['ok'=>false,'error'=>'An Africa expansion phrase already uses this date.','requires_confirmation'=>true],409);
    $existing=$existingIndex===null?[]:(array)$items[$existingIndex];$audioUrls=(array)($existing['audio_urls']??[]);$audioVoices=(array)($existing['audio_voices']??[]);$audioProviders=(array)($existing['audio_providers']??[]);
    $trackFields=['ln'=>'lingala','ma'=>'darija','eg'=>'egyptian_arabic','sw'=>'swahili'];
    foreach($trackFields as $key=>$field){
        if((string)($existing[$field]??'')!==$values[$field]){unset($audioUrls[$key],$audioVoices[$key],$audioProviders[$key]);}
    }
    if($prerecord){
        @set_time_limit(240);
        $audio=africaGenerateTracks($values,$date);$audioUrls=$audio['urls'];$audioVoices=$audio['voices'];$audioProviders=$audio['providers'];
    }
    $item=[...$existing,...$values,'id'=>$existingIndex===null?$maxId+1:(int)($existing['id']??$maxId+1),'pack'=>'africa-v1','publish_date'=>$date,'translation_policy'=>'azure-batch-v1','azure_translation_accepted'=>true,'native_reviewed'=>false,'audio_urls'=>$audioUrls,'audio_voices'=>$audioVoices,'audio_providers'=>$audioProviders,'generator'=>['version'=>'1.1.0','translation'=>'azure','saved_by'=>(int)($_SESSION['user_id']??0),'saved_at'=>date(DATE_ATOM)]];
    if($existingIndex===null)$items[]=$item;else$items[$existingIndex]=$item;africaWrite($file,$items);
    africaResponse(['ok'=>true,'item'=>$item,'audio_generated'=>$prerecord&&count($audioUrls)===4,'message'=>$prerecord?'Africa phrase saved with four prerecorded MP3 tracks.':'Africa phrase saved under the Azure Batch v1 policy.']);
}catch(InvalidArgumentException $error){africaResponse(['ok'=>false,'error'=>$error->getMessage()],422);}catch(Throwable $error){error_log('Africa expansion generator: '.$error->getMessage());africaResponse(['ok'=>false,'error'=>'The phrase or one of its audio tracks could not be saved: '.$error->getMessage()],502);}
