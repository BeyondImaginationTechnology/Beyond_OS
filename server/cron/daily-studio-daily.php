<?php
if (PHP_SAPI!=='cli') { $token=getenv('DAILY_STUDIO_CRON_TOKEN')?:''; if(!$token || !hash_equals($token,$_GET['token']??'')){http_response_code(403);exit('Forbidden');}}
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once __DIR__ . '/daily-breath-video.php';
$breathVideoError = null;
if (PHP_SAPI === 'cli') {
    try {
        echo dailybreath_render_daily_video() . PHP_EOL;
    } catch (Throwable $error) {
        error_log('Daily Breath scheduled video generation failed: ' . $error->getMessage());
        fwrite(STDERR, 'Daily Breath video generation failed: ' . $error->getMessage() . PHP_EOL);
        $breathVideoError = $error;
    }
}
$dbPath=beyond_private_file('db/daily-studio.sqlite','daily-studio.sqlite');if(!file_exists($dbPath)){echo "Daily Studio database not initialized.\n";if($breathVideoError!==null)exit(1);exit;}$db=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$tomorrow=date('Y-m-d',strtotime('+1 day'));$channels=$db->query('SELECT channel_key,name FROM channels WHERE enabled=1')->fetchAll(PDO::FETCH_ASSOC);$missing=[];foreach($channels as $c){$s=$db->prepare('SELECT COUNT(*) FROM events WHERE channel_key=? AND date(scheduled_at)=?');$s->execute([$c['channel_key'],$tomorrow]);if(!(int)$s->fetchColumn())$missing[]=$c['name'];}$report=['date'=>$tomorrow,'missing_channels'=>$missing,'generated_at'=>date(DATE_ATOM)];$outDir=beyond_private_directory('data/daily-studio/published','daily-studio-published');if(!is_dir($outDir))mkdir($outDir,0755,true);file_put_contents($outDir.'/daily-check.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo count($missing)." missing channel(s) for $tomorrow.\n";
if ($breathVideoError !== null) {
    exit(1);
}
