<?php
declare(strict_types=1);

/**
 * Channel 1 library-driven linear schedule.
 * Schedule chooses a franchise; library manager chooses the episode/source.
 * Progress is advanced once per Vancouver programme block, never once per viewer.
 */
function beyond_classic_libraries(): array
{
    return [
        'x-men' => [
            'name' => 'X-Men: The Animated Series', 'icon' => '❌', 'episode_count' => 1,
            'lineup_label' => 'Complete remastered collection',
            'sources' => [
                ['type'=>'archive','id'=>'x-men-the-animated-series-1080p-ai-upscale_202204','label'=>'Internet Archive · remastered 1080p collection'],
            ],
        ],
        'fantastic-four' => [
            'name' => 'Fantastic Four (1996)', 'icon' => '4️⃣', 'episode_count' => 1,
            'lineup_label' => 'Complete series collection',
            'sources' => [
                ['type'=>'archive','id'=>'fantastic-four-1996-complete-series','label'=>'Internet Archive · complete series upload'],
            ],
        ],
        'spider-man' => [
            'name' => 'Spider-Man: The Animated Series', 'icon' => '🕷️', 'episode_count' => 1,
            'lineup_label' => 'Complete remastered collection',
            'sources' => [
                ['type'=>'archive','id'=>'spider-mantheanimatedseries','label'=>'Internet Archive · remastered 1080p collection'],
            ],
        ],
        'hulk' => [
            'name' => 'The Incredible Hulk (1966)', 'icon' => '💚', 'episode_count' => 1,
            'lineup_label' => 'Complete 1966 series collection',
            'sources' => [
                ['type'=>'archive','id'=>'the-incredble-hulk-1966-complete-series-english','label'=>'Internet Archive · complete English collection'],
            ],
        ],
        'batman-tas' => [
            'name' => 'Batman: The Animated Series', 'icon' => '🦇', 'episode_count' => 1,
            'lineup_label' => 'Animated series collection',
            'sources' => [
                ['type'=>'archive','id'=>'BatmanTASFull','label'=>'Internet Archive · animated series collection'],
            ],
        ],
        'the-batman' => [
            'name' => 'The Batman', 'icon' => '🦇', 'episode_count' => 1,
            'lineup_label' => 'Complete five-season collection',
            'sources' => [
                ['type'=>'archive','id'=>'the-batman-03x-01','label'=>'Internet Archive · complete series upload'],
            ],
        ],
    ];
}

function beyond_classic_blocks(): array
{
    return [
        ['start'=>0,  'end'=>3,  'library'=>'batman-tas',    'title'=>'Batman After Dark'],
        ['start'=>3,  'end'=>6,  'library'=>'x-men',         'title'=>'X-Men Overnight'],
        ['start'=>6,  'end'=>9,  'library'=>'spider-man',    'title'=>'Spider-Man Classics'],
        ['start'=>9,  'end'=>12, 'library'=>'fantastic-four','title'=>'Fantastic Four'],
        ['start'=>12, 'end'=>15, 'library'=>'hulk',          'title'=>'The Incredible Hulk'],
        ['start'=>15, 'end'=>18, 'library'=>'the-batman',    'title'=>'The Batman'],
        ['start'=>18, 'end'=>21, 'library'=>'x-men',         'title'=>'X-Men Prime Time'],
        ['start'=>21, 'end'=>24, 'library'=>'spider-man',    'title'=>'Spider-Man After Hours'],
    ];
}

function beyond_classic_state_file(): string
{
    $root = dirname(__DIR__, 3);
    $dir = $root . '/var/tv';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir . '/channel-1-library-state.json';
}

function beyond_classic_load_progress(): array
{
    $file = beyond_classic_state_file();
    if (!is_file($file)) return ['libraries'=>[], 'processed_blocks'=>[]];
    $decoded = json_decode((string)@file_get_contents($file), true);
    return is_array($decoded) ? array_merge(['libraries'=>[], 'processed_blocks'=>[]], $decoded) : ['libraries'=>[], 'processed_blocks'=>[]];
}

function beyond_classic_save_progress(array $state): void
{
    $file = beyond_classic_state_file();
    $state['updated_at'] = gmdate(DATE_ATOM);
    $state['processed_blocks'] = array_slice((array)($state['processed_blocks'] ?? []), -180, null, true);
    $json = json_encode($state, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if ($json === false) return;
    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) @rename($tmp, $file);
}

function beyond_classic_embed(array $source, int $episodeIndex): string
{
    if (($source['type'] ?? '') === 'archive') {
        $id = preg_replace('/[^A-Za-z0-9_.-]/', '', (string)($source['id'] ?? ''));
        return 'https://archive.org/embed/' . rawurlencode($id) . '?autoplay=1&playlist=1';
    }
    $base = 'https://www.youtube-nocookie.com/embed/';
    $params = ['autoplay'=>1,'mute'=>1,'controls'=>1,'rel'=>0,'playsinline'=>1,'modestbranding'=>1,'enablejsapi'=>1];
    if (($source['type'] ?? '') === 'playlist') {
        $params['list'] = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$source['id']);
        $params['index'] = max(0, $episodeIndex);
        return $base . 'videoseries?' . http_build_query($params);
    }
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($source['id'] ?? ''));
    if (!empty($source['start'])) $params['start'] = max(0, (int)$source['start']);
    return $base . $id . '?' . http_build_query($params);
}

function beyond_classic_schedule_state(?DateTimeImmutable $now = null): array
{
    $tz = new DateTimeZone('America/Vancouver');
    $now = ($now ?? new DateTimeImmutable('now', $tz))->setTimezone($tz);
    $hour = (int)$now->format('G');
    $blocks = beyond_classic_blocks();
    $libraries = beyond_classic_libraries();
    $currentIndex = 0;
    foreach ($blocks as $i=>$block) if ($hour >= $block['start'] && $hour < $block['end']) { $currentIndex=$i; break; }
    $current = $blocks[$currentIndex];
    $next = $blocks[($currentIndex+1)%count($blocks)];
    $libraryKey = $current['library'];
    $library = $libraries[$libraryKey];
    $start = $now->setTime((int)$current['start'],0,0);
    $end = $current['end']===24 ? $now->modify('+1 day')->setTime(0,0,0) : $now->setTime((int)$current['end'],0,0);
    $blockKey = $start->format('Y-m-d-H') . '-' . $libraryKey;

    $progress = beyond_classic_load_progress();
    $entry = array_merge(['source'=>0,'episode'=>-1,'previous_valid'=>null], (array)($progress['libraries'][$libraryKey] ?? []));
    if (empty($progress['processed_blocks'][$blockKey])) {
        $count = max(1, (int)($library['episode_count'] ?? 1));
        $entry['episode'] = ((int)$entry['episode'] + 1) % $count;
        $progress['libraries'][$libraryKey] = $entry;
        $progress['processed_blocks'][$blockKey] = $now->format(DATE_ATOM);
        beyond_classic_save_progress($progress);
    }
    $sourceIndex = min(max(0,(int)$entry['source']), count($library['sources'])-1);
    $fallbacks = [];
    foreach ($library['sources'] as $i=>$source) {
        $fallbacks[] = [
            'source_index'=>$i,
            'label'=>(string)$source['label'],
            'embed_url'=>beyond_classic_embed($source, (int)$entry['episode']),
        ];
    }
    if (is_array($entry['previous_valid']) && !empty($entry['previous_valid']['embed_url'])) {
        $fallbacks[] = ['source_index'=>'previous','label'=>'Previous valid episode','embed_url'=>(string)$entry['previous_valid']['embed_url']];
    }
    $fallbacks[] = ['source_index'=>'intermission','label'=>'Beyond TV intermission','embed_url'=>'/beyond-tv/intermission.php?channel=1'];
    $active = $fallbacks[$sourceIndex] ?? $fallbacks[0];

    $current['icon']=$library['icon'];
    $current['lineup']=$library['name'].' · '.(string)($library['lineup_label'] ?? ('Episode '.((int)$entry['episode']+1)));
    $current['library_name']=$library['name'];
    $current['episode_number']=(int)$entry['episode']+1;
    $nextLib=$libraries[$next['library']];
    $next['icon']=$nextLib['icon']; $next['lineup']=$nextLib['name'];

    return [
        'timezone'=>'America/Vancouver','timezone_label'=>$now->format('T'),'local_time'=>$now->format(DATE_ATOM),
        'date_label'=>$now->format('l, F j'),'time_label'=>$now->format('g:i A'),
        'current'=>$current,'next'=>$next,'block_key'=>$blockKey,'block_started_at'=>$start->format(DATE_ATOM),
        'block_ends_at'=>$end->format(DATE_ATOM),'seconds_into_block'=>max(0,$now->getTimestamp()-$start->getTimestamp()),
        'seconds_remaining'=>max(0,$end->getTimestamp()-$now->getTimestamp()),
        'library_key'=>$libraryKey,'episode_index'=>(int)$entry['episode'],'episode_number'=>(int)$entry['episode']+1,
        'source_index'=>$sourceIndex,'source_label'=>$active['label'],'embed_url'=>$active['embed_url'],
        'fallbacks'=>$fallbacks,'blocks'=>array_map(static function(array $block) use ($libraries): array { $lib=$libraries[$block['library']]; $block['icon']=$lib['icon']; $block['lineup']=$lib['name'].' · '.(string)($lib['lineup_label'] ?? 'library rotation'); return $block; }, $blocks),
    ];
}
