<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=3600');
require_once dirname(__DIR__) . '/includes/catalog-rotation.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$channels = [
    'vintage-cartoon-theater' => [
        'name' => 'Classic Cartoon Theater',
        'items' => [
            ['archive' => 'SnowWhiteWithBettyBoop1933', 'title' => 'Snow White (1933)', 'duration' => 397],
            ['url' => "https://commons.wikimedia.org/wiki/Special:Redirect/file/Betty_Boop%27s_Rise_to_Fame_%281934%29.webm", 'title' => "Betty Boop's Rise to Fame (1934)", 'duration' => 530],
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Betty_Boop_-_Poor_Cinderella_%281934%29_-_HD.webm', 'title' => 'Poor Cinderella (1934)', 'duration' => 620],
        ],
        'embed' => 'https://archive.org/embed/SnowWhiteWithBettyBoop1933',
    ],
    'saturday-morning-cartoons' => [
        'name' => 'Saturday Morning Cartoons',
        'items' => [
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Betty_Boop_-_Poor_Cinderella_%281934%29_-_HD.webm', 'title' => 'Poor Cinderella (1934)', 'duration' => 620],
            ['url' => "https://commons.wikimedia.org/wiki/Special:Redirect/file/Betty_Boop%27s_Rise_to_Fame_%281934%29.webm", 'title' => "Betty Boop's Rise to Fame (1934)", 'duration' => 530],
        ],
        'embed' => 'https://archive.org/embed/SnowWhiteWithBettyBoop1933',
    ],
    'classic-cinema' => [
        'name' => 'Beyond Movies',
        'items' => [
            ['archive' => 'HisGirlFriday1940_201505', 'title' => 'His Girl Friday (1940)', 'duration' => 5520],
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/His_Girl_Friday.webm', 'title' => 'His Girl Friday — Commons edition', 'duration' => 5520],
        ],
        'embed' => 'https://www.youtube-nocookie.com/embed/videoseries?list=PLdk1SI29-q9yrN9GFMnOAYmC_tcw5v59L',
    ],
    'midnight-movies' => [
        'name' => 'Midnight Movies',
        'items' => [
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Plan_9_from_Outer_Space_%281959%29.webm', 'title' => 'Plan 9 from Outer Space (1959)', 'duration' => 4740],
            ['archive' => 'TheLittleShopOfHorrors1960_765', 'title' => 'The Little Shop of Horrors (1960)', 'duration' => 4289],
        ],
        'embed' => 'https://archive.org/embed/TheLittleShopOfHorrors1960_765',
    ],
    'silent-film-theater' => [
        'name' => 'Silent Film Theater',
        'items' => [
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/The_General_%281926%29.webm', 'title' => 'The General (1926)', 'duration' => 4552],
            ['archive' => 'The_General_Buster_Keaton', 'title' => 'The General — archive edition', 'duration' => 4552],
        ],
        'embed' => 'https://archive.org/embed/The_General_Buster_Keaton',
    ],
    'family-movie-matinee' => [
        'name' => 'Family Movie Matinee',
        'items' => [
            ['archive' => 'SnowWhiteWithBettyBoop1933', 'title' => 'Vintage Animation Matinee', 'duration' => 397],
            ['url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/The_General_%281926%29.webm', 'title' => 'The General (1926)', 'duration' => 4552],
        ],
        'embed' => 'https://archive.org/embed/The_General_Buster_Keaton',
    ],
    'beyond-after-dark' => [
        'name' => 'Beyond After Dark',
        'episode_map' => dirname(__DIR__) . '/data/haunting-hour-library.json',
        'embed' => 'https://archive.org/embed/rl-stines-the-haunting-hour-full-series',
    ],

    'kreyol-lakay' => [
        'name' => 'Kreyòl Lakay',
        'items' => [
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/WIKITONGUES-_Castelline_speaking_Haitian_Creole.webm',
                'title' => 'Castelline Speaking Haitian Creole',
                'duration' => 238,
                'creator' => 'Wikitongues and Casteline Titus',
                'license' => 'CC BY-SA 4.0',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:WIKITONGUES-_Castelline_speaking_Haitian_Creole.webm',
            ],
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/VOA_Creole_Chief_Reacts_to_Kenyan_Police_Moving_to_Haiti.webm',
                'title' => 'VOA Creole: Haitian Voices',
                'duration' => 89,
                'creator' => 'Voice of America',
                'license' => 'U.S. public domain (VOA)',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:VOA_Creole_Chief_Reacts_to_Kenyan_Police_Moving_to_Haiti.webm',
            ],
        ],
        'embed' => 'https://commons.wikimedia.org/wiki/File:WIKITONGUES-_Castelline_speaking_Haitian_Creole.webm',
    ],
    'ayiti-caribbean' => [
        'name' => 'Ayiti & Caribbean Culture',
        'items' => [
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Merengue_Haitian,_Havana,_2012.webm',
                'title' => 'Haitian Méringue, Havana (2012)',
                'duration' => 167,
                'creator' => 'Nastya Yagushchenko',
                'license' => 'CC BY 3.0',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:Merengue_Haitian,_Havana,_2012.webm',
            ],
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/WIKITONGUES-_Castelline_speaking_Haitian_Creole.webm',
                'title' => 'Haitian Creole Language & Culture',
                'duration' => 238,
                'creator' => 'Wikitongues and Casteline Titus',
                'license' => 'CC BY-SA 4.0',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:WIKITONGUES-_Castelline_speaking_Haitian_Creole.webm',
            ],
        ],
        'embed' => 'https://commons.wikimedia.org/wiki/File:Merengue_Haitian,_Havana,_2012.webm',
    ],
    'cinema-francais-classique' => [
        'name' => 'Cinéma Français Classique',
        'items' => [
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Cinderella_(1899_film).webm',
                'title' => 'Cendrillon / Cinderella (1899)',
                'duration' => 341,
                'creator' => 'Georges Méliès',
                'license' => 'Public domain',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:Cinderella_(1899_film).webm',
            ],
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/1896-melies-une-partie-de-cartes.webm',
                'title' => 'Une partie de cartes (1896)',
                'duration' => 69,
                'creator' => 'Georges Méliès',
                'license' => 'Public domain',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:1896-melies-une-partie-de-cartes.webm',
            ],
            [
                'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Le_Cauchemar,_1896,_Méliès.webm',
                'title' => 'Le Cauchemar (1896)',
                'duration' => 65,
                'creator' => 'Georges Méliès',
                'license' => 'Public domain',
                'rights_url' => 'https://commons.wikimedia.org/wiki/File:Le_Cauchemar,_1896,_Méliès.webm',
            ],
        ],
        'embed' => 'https://commons.wikimedia.org/wiki/File:Cinderella_(1899_film).webm',
    ],

];

// Any curated catalog channel can become a synchronized live channel without
// duplicating its approved sources in this endpoint.
if (!isset($channels[$slug])) {
    $catalog = json_decode((string)@file_get_contents(dirname(__DIR__) . '/data/catalog.json'), true);
    $catalogItems = beyond_tv_catalog_entries_for_channel(is_array($catalog) ? $catalog : [], $slug);
    if ($catalogItems) {
        $items = []; $episodeMaps = [];
        foreach ($catalogItems as $item) {
            if (($item['source_type'] ?? '') === 'archive_episode_map' && !empty($item['archive_episode_map'])) {
                $episodeMaps[] = [
                    'path' => dirname(__DIR__) . '/data/' . basename((string)$item['archive_episode_map']),
                    'title' => (string)($item['title'] ?? 'Series'),
                    'creator' => (string)($item['title'] ?? 'Beyond TV library'),
                    'rights_url' => (string)($item['official_url'] ?? $item['rights_url'] ?? ''),
                ];
                continue;
            }
            $videoUrl = trim((string)($item['video_url'] ?? ''));
            $archiveId = trim((string)($item['archive_id'] ?? ''));
            if ($videoUrl === '' && $archiveId === '') continue;
            $items[] = [
                'url' => $videoUrl ?: null,
                'archive' => $archiveId ?: null,
                'title' => (string)($item['title'] ?? 'Beyond TV feature'),
                'duration' => max(300, (int)($item['runtime_seconds'] ?? 5400)),
            ];
        }
        $channels[$slug] = [
            'name' => (string)($catalogItems[0]['channel_name'] ?? ucwords(str_replace('-', ' ', $slug))),
            'items' => $items,
            'episode_maps' => $episodeMaps,
            'embed' => !empty($catalogItems[0]['archive_id']) ? 'https://archive.org/embed/' . rawurlencode((string)$catalogItems[0]['archive_id']) : '',
        ];
    }
}
if (!isset($channels[$slug])) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Unknown channel']); exit; }

function fetch_json(string $url): ?array {
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>7,CURLOPT_USERAGENT=>'BeyondTV/2.1']);
        $result=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
        if(is_string($result)&&$status>=200&&$status<300)$body=$result;
    }
    if($body===null&&filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN)){
        $result=@file_get_contents($url,false,stream_context_create(['http'=>['timeout'=>7,'user_agent'=>'BeyondTV/2.1']]));
        if(is_string($result))$body=$result;
    }
    $decoded=$body!==null?json_decode($body,true):null; return is_array($decoded)?$decoded:null;
}
function archive_url(string $id,string $file): string { return 'https://archive.org/download/'.rawurlencode($id).'/'.implode('/',array_map('rawurlencode',explode('/',$file))); }
function archive_cache_file(string $id): string {
    $cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'beyond-tv-archive-cache';
    return $cacheDir . DIRECTORY_SEPARATOR . sha1($id) . '.json';
}
function archive_cache_get(string $id): ?string {
    $cacheFile = archive_cache_file($id);
    if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile)) < 604800) {
        $cached = json_decode((string)@file_get_contents($cacheFile), true);
        if (is_array($cached) && filter_var($cached['url'] ?? '', FILTER_VALIDATE_URL)) return (string)$cached['url'];
    }
    return null;
}
function archive_cache_set(string $id, string $url): void {
    $cacheFile = archive_cache_file($id);
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
    if (is_dir($cacheDir)) @file_put_contents($cacheFile, json_encode(['url'=>$url], JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function archive_url_from_metadata(string $id, ?array $metadata): ?string {
    if(!$metadata||!is_array($metadata['files']??null))return null;
    $c=[]; foreach($metadata['files'] as $f){ if(!is_array($f))continue; $n=(string)($f['name']??''); $fmt=strtolower((string)($f['format']??''));
        if(!preg_match('/\.(mp4|m4v|webm)$/i',$n)||str_contains($fmt,'thumbnail')||str_contains(strtolower($n),'thumb'))continue;
        $score=(int)($f['size']??0); if(preg_match('/\.mp4$/i',$n))$score+=2000000000; $c[]=['n'=>$n,'s'=>$score]; }
    usort($c,fn($a,$b)=>$b['s']<=>$a['s']);
    return $c ? archive_url($id, $c[0]['n']) : null;
}
function resolve_archive(string $id): ?string {
    $cached = archive_cache_get($id);
    if ($cached) return $cached;
    $metadata=fetch_json('https://archive.org/metadata/'.rawurlencode($id));
    $url=archive_url_from_metadata($id,$metadata);
    if($url)archive_cache_set($id,$url);
    return $url;
}
function resolve_archives(array $ids): array {
    $resolved=[];$missing=[];
    foreach(array_values(array_unique(array_filter(array_map('strval',$ids)))) as $id){
        $cached=archive_cache_get($id);
        if($cached)$resolved[$id]=$cached;else $missing[]=$id;
    }
    if(!$missing)return $resolved;
    if(!function_exists('curl_multi_init')){
        foreach($missing as $id){$url=resolve_archive($id);if($url)$resolved[$id]=$url;}
        return $resolved;
    }
    $multi=curl_multi_init();$handles=[];
    foreach($missing as $id){
        $handle=curl_init('https://archive.org/metadata/'.rawurlencode($id));
        curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_USERAGENT=>'BeyondTV/2.1']);
        curl_multi_add_handle($multi,$handle);$handles[spl_object_id($handle)]=['id'=>$id,'handle'=>$handle];
    }
    do{$status=curl_multi_exec($multi,$active);if($active&&$status===CURLM_OK&&curl_multi_select($multi,1.0)===-1)usleep(10000);}while($active&&$status===CURLM_OK);
    foreach($handles as $entry){
        $handle=$entry['handle'];$id=$entry['id'];$body=curl_multi_getcontent($handle);$http=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);
        $metadata=is_string($body)&&$http>=200&&$http<300?json_decode($body,true):null;
        $url=archive_url_from_metadata($id,is_array($metadata)?$metadata:null);
        if($url){archive_cache_set($id,$url);$resolved[$id]=$url;}
        curl_multi_remove_handle($multi,$handle);curl_close($handle);
    }
    curl_multi_close($multi);
    return $resolved;
}
$config=$channels[$slug];
if ($slug === 'classic-cinema') {
    require_once dirname(__DIR__) . '/includes/movies-schedule.php';
    $movieState = beyond_movies_schedule_state();
    $config['items'] = array_map(static fn(array $movie): array => [
        'url' => (string)$movie['url'],
        'title' => (string)$movie['title'],
        'duration' => (int)$movie['duration'],
        'creator' => '',
        'license' => 'Public-domain source edition',
        'rights_url' => (string)$movie['rights_url'],
    ], $movieState['movies']);
    $config['embed'] = (string)$movieState['player_url'];
}
if (!empty($config['episode_map'])) {
    $config['episode_maps'][] = [
        'path' => (string)$config['episode_map'],
        'title' => (string)($config['name'] ?? 'Series'),
        'creator' => (string)($config['name'] ?? 'Beyond TV library'),
        'rights_url' => '',
    ];
}
foreach ((array)($config['episode_maps'] ?? []) as $episodeMap) {
    $episodeRows = json_decode((string)@file_get_contents((string)($episodeMap['path'] ?? '')), true);
    $preferred = [];
    foreach (is_array($episodeRows) ? $episodeRows : [] as $episode) {
        if (!is_array($episode) || empty($episode['video_url'])) continue;
        $number = (int)($episode['episode'] ?? 0);
        $extension = strtolower(pathinfo((string)($episode['video_url'] ?? ''), PATHINFO_EXTENSION));
        if (!isset($preferred[$number]) || $extension === 'mp4') $preferred[$number] = $episode;
    }
    ksort($preferred);
    $seriesTitle = (string)($episodeMap['title'] ?? $config['name'] ?? 'Series');
    $seriesCreator = (string)($episodeMap['creator'] ?? $seriesTitle);
    $seriesRightsUrl = (string)($episodeMap['rights_url'] ?? '');
    $mappedItems = array_map(static fn(array $episode): array => [
        'url' => (string)$episode['video_url'],
        'title' => $seriesTitle . ' · S' . (int)($episode['season'] ?? 1) . ' E' . (int)($episode['episode'] ?? 0) . ' · ' . (string)($episode['title'] ?? 'Episode'),
        'duration' => max(60, (int)($episode['runtime_seconds'] ?? 1380)),
        'creator' => $seriesCreator,
        'license' => 'Owner-verified archive source',
        'rights_url' => $seriesRightsUrl,
    ], array_values($preferred));
    $config['items'] = array_merge((array)($config['items'] ?? []), $mappedItems);
}
if ($slug === 'beyond-after-dark') {
    $goosebumpsRows = json_decode((string)@file_get_contents(dirname(__DIR__) . '/data/goosebumps-library.json'), true) ?: [];
    foreach ($goosebumpsRows as $episode) {
        if (!is_array($episode) || empty($episode['video_url'])) continue;
        $config['items'][] = [
            'url' => (string)$episode['video_url'],
            'title' => 'Goosebumps · S1 E' . (int)($episode['episode'] ?? 0) . ' · ' . (string)($episode['title'] ?? 'Episode'),
            'duration' => max(60, (int)($episode['runtime_seconds'] ?? 1320)),
            'creator' => 'Goosebumps',
            'license' => 'Owner-verified archive source',
            'rights_url' => 'https://archive.org/details/goosebumps-s01',
        ];
    }
}
$archiveIds=[];foreach($config['items'] as $item){if(empty($item['url'])&&!empty($item['archive']))$archiveIds[]=(string)$item['archive'];}
$archiveUrls=resolve_archives($archiveIds);
$resolved=[];
foreach($config['items'] as $item){ $url=$item['url']??null; if(!$url&&!empty($item['archive']))$url=$archiveUrls[(string)$item['archive']]??null; if(!$url)continue;
    $provider = !empty($item['archive']) || str_contains((string)$url, 'archive.org/') ? 'Internet Archive' : 'Wikimedia Commons';
    $resolved[]=['provider'=>$provider,'title'=>$item['title'],'url'=>$url,'duration'=>(int)$item['duration'],'type'=>str_contains($url,'.webm')?'video/webm':'video/mp4','creator'=>(string)($item['creator']??''),'license'=>(string)($item['license']??''),'rights_url'=>(string)($item['rights_url']??'')]; }
$total=array_sum(array_column($resolved,'duration')); $position=$total>0?time()%$total:0; $current=0; $offset=0;
foreach($resolved as $i=>$item){ if($position<$item['duration']){$current=$i;$offset=$position;break;} $position-=$item['duration']; }
$ordered=$resolved; if($resolved){$ordered=array_merge(array_slice($resolved,$current),array_slice($resolved,0,$current));}
$currentItem = $resolved[$current] ?? [];
$nextItem = $resolved ? $resolved[($current + 1) % count($resolved)] : [];
$sourceKey = $currentItem ? sha1((string)($currentItem['url'] ?? '') . '|' . $current) : '';
$playerUrl = '/beyond-tv/embed-player.php?slug=' . rawurlencode($slug);
echo json_encode([
    'ok'=>true,
    'channel'=>['slug'=>$slug,'name'=>$config['name'],'mode'=>'pseudo-live','programme'=>$currentItem['title']??'Unavailable'],
    'state'=>[
        'current'=>['title'=>$currentItem['title']??'Unavailable','provider'=>$currentItem['provider']??'','source_key'=>$sourceKey],
        'next'=>['title'=>$nextItem['title']??'Next scheduled program'],
        'player_url'=>$playerUrl,
        'source_key'=>$sourceKey,
    ],
    'sources'=>$ordered,
    'start_offset'=>$offset,
    'playlist_duration'=>$total,
    'library_count'=>count($resolved),
    'library_hours'=>round($total / 3600, 1),
    'server_time'=>time(),
    'player_url'=>$playerUrl,
    'embed_fallback'=>$config['embed'],
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
