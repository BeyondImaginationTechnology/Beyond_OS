<?php
declare(strict_types=1);

require_once __DIR__ . '/space-local-library.php';

function beyond_space_schedule_state(?DateTimeImmutable $now = null): array
{
    $tz = new DateTimeZone('America/Vancouver');
    $now = ($now ?? new DateTimeImmutable('now', $tz))->setTimezone($tz);
    $hour = (int)$now->format('G');

    // Official NASA or NASA Goddard uploads. Each block loops until the next one.
    $blocks = [
        ['key'=>'earth-from-orbit','start'=>0,'end'=>3,'title'=>'Earth from Orbit','lineup'=>'NASA Earth science and views from space','icon'=>'🌍','sources'=>[
            ['id'=>'iTHUUjTA-LI','title'=>'Seeing Earth as Only NASA Can','duration'=>83],
            ['id'=>'wQF61J0Yd54','title'=>'Synthesis: NASA Data Visualizations in Ultra-HD','duration'=>1065],
        ]],
        ['key'=>'solar-cinema','start'=>3,'end'=>6,'title'=>'Solar Cinema','lineup'=>'NASA Goddard Sun imagery and space visualizations','icon'=>'☀️','sources'=>[
            ['id'=>'6tmbeLTHC_0','title'=>'Thermonuclear Art: The Sun in Ultra-HD','duration'=>1824],
            ['id'=>'wQF61J0Yd54','title'=>'Synthesis: NASA Data Visualizations in Ultra-HD','duration'=>1065],
        ]],
        ['key'=>'nasa-morning','start'=>6,'end'=>9,'title'=>'NASA Morning','lineup'=>'NASA mission updates and Earth science','icon'=>'🌅','sources'=>[
            ['id'=>'uNuhG3kS7so','title'=>'NASA: 2025 Review and 2026 Preview','duration'=>161],
            ['id'=>'iTHUUjTA-LI','title'=>'Seeing Earth as Only NASA Can','duration'=>83],
        ]],
        ['key'=>'moon-missions','start'=>9,'end'=>12,'title'=>'Moon Missions','lineup'=>'NASA Artemis II and lunar exploration','icon'=>'🌕','sources'=>[
            ['id'=>'IfRqrrBbT-Y','title'=>'Artemis II Moon Mission Complete!','duration'=>213],
            ['id'=>'Vg-EQ7MOu6I','title'=>'Around the Moon for All Humanity: Artemis II','duration'=>75],
        ]],
        ['key'=>'mars-missions','start'=>12,'end'=>15,'title'=>'Mars Missions','lineup'=>'NASA Perseverance and the journey to the Red Planet','icon'=>'🔴','sources'=>[
            ['id'=>'4czjS9h4Fpg','title'=>'Perseverance Rover’s Descent and Touchdown on Mars','duration'=>205],
            ['id'=>'gm0b_ijaYMQ','title'=>'Watch NASA’s Perseverance Rover Land on Mars','duration'=>7904],
        ]],
        ['key'=>'webb-and-the-universe','start'=>15,'end'=>18,'title'=>'Webb and the Universe','lineup'=>'NASA Webb telescope discoveries and imagery','icon'=>'🔭','sources'=>[
            ['id'=>'zXyz1QtPqUY','title'=>'Designing Webb','duration'=>761],
            ['id'=>'hWAVey53AC4','title'=>'The Webb Telescope Sunshield','duration'=>153],
            ['id'=>'9fOsQ-BtWIs','title'=>'Webb Science Instruments Overview','duration'=>225],
            ['id'=>'GyDONOJ3_rw','title'=>'Webb’s First Full-Color Images Explained','duration'=>3161],
        ]],
        ['key'=>'space-exploration-prime-time','start'=>18,'end'=>21,'title'=>'Space Exploration Prime Time','lineup'=>'NASA Moon missions, Mars landings and mission highlights','icon'=>'🚀','sources'=>[
            ['id'=>'IfRqrrBbT-Y','title'=>'Artemis II Moon Mission Complete!','duration'=>213],
            ['id'=>'4czjS9h4Fpg','title'=>'Perseverance Rover’s Descent and Touchdown on Mars','duration'=>205],
            ['id'=>'uNuhG3kS7so','title'=>'NASA: 2025 Review and 2026 Preview','duration'=>161],
        ]],
        ['key'=>'deep-space-night','start'=>21,'end'=>24,'title'=>'Deep Space Night','lineup'=>'NASA Webb imagery and cosmic discovery','icon'=>'🌌','sources'=>[
            ['id'=>'gD4bWEuu4gw','title'=>'Fast and Furious Star Formation in Starburst Galaxies','duration'=>209],
            ['id'=>'O7VqM_Adn0A','title'=>'Webb’s Biggest Discoveries: Four Years of Science','duration'=>324],
            ['id'=>'wQF61J0Yd54','title'=>'Synthesis: NASA Data Visualizations in Ultra-HD','duration'=>1065],
        ]],
    ];

    $index = 0;
    foreach ($blocks as $i => $block) {
        if ($hour >= $block['start'] && $hour < $block['end']) { $index = $i; break; }
    }
    $current = $blocks[$index];
    $next = $blocks[($index + 1) % count($blocks)];
    $minuteOfDay = $hour * 60 + (int)$now->format('i');
    foreach ([8 * 60 + 45, 20 * 60 + 45] as $airtime) {
        if ($minuteOfDay < $airtime || $minuteOfDay >= $airtime + 5) {
            continue;
        }
        $start = $now->setTime(intdiv($airtime, 60), $airtime % 60, 0);
        $episode = [
            'id' => 'cosmic-compass-2026-10-03',
            'title' => 'Cosmic Compass: Daily Astrology',
            'program' => 'Cosmic Compass',
            'url' => '/beyond-tv/assets/media/space-tv/cosmic-compass-2026-10-03.mp4',
            'duration' => 300,
            'type' => 'video/mp4',
        ];
        $episodeCurrent = ['key'=>'cosmic-compass','start'=>intdiv($airtime, 60),'end'=>intdiv($airtime, 60),'title'=>'Cosmic Compass: Daily Astrology','lineup'=>'Today’s entertainment-only zodiac reflections','icon'=>'✨'];
        return [
            'timezone'=>'America/Vancouver','timezone_label'=>$now->format('T'),
            'time_label'=>$now->format('g:i A'),'date_label'=>$now->format('l, F j'),
            'current'=>$episodeCurrent,'next'=>$current,'blocks'=>$blocks,'playing'=>$episode,
            'sources'=>[$episode],'source_key'=>'cosmic-compass-' . $now->format('Y-m-d') . '-' . $airtime,
            'start_offset'=>max(0, $now->getTimestamp() - $start->getTimestamp()),
            'player_url'=>'/beyond-tv/embed-player.php?slug=space-tv',
            'server_time'=>$now->getTimestamp(),'source_label'=>'Beyond Space TV · Original silent visual episode',
        ];
    }
    $local = beyond_space_local_rotation($current, $now);
    if ($local !== null) {
        $localCurrent = $local['current'];
        $displayCurrent = $current;
        $displayCurrent['lineup'] = $localCurrent['program'] . ' · ' . $localCurrent['title'];
        return [
            'timezone'=>'America/Vancouver','timezone_label'=>$now->format('T'),
            'time_label'=>$now->format('g:i A'),'date_label'=>$now->format('l, F j'),
            'current'=>$displayCurrent,'next'=>$next,'blocks'=>$blocks,'playing'=>$localCurrent,
            'sources'=>$local['sources'],'source_key'=>$local['source_key'],
            'start_offset'=>$local['start_offset'],'player_url'=>'/beyond-tv/embed-player.php?slug=space-tv',
            'server_time'=>$now->getTimestamp(),'source_label'=>'NASA SVS · silent local archive',
        ];
    }
    $params = ['autoplay'=>1,'mute'=>1,'controls'=>1,'rel'=>0,'playsinline'=>1,'loop'=>1];
    $blockStart = $now->setTime((int)$current['start'], 0, 0);
    $elapsed = max(0, $now->getTimestamp() - $blockStart->getTimestamp());
    $startOffset = $elapsed;

    $sources = $current['sources'];
    $cycleDuration = array_sum(array_map(static fn(array $source): int => (int)$source['duration'], $sources));
    $position = $cycleDuration > 0 ? $elapsed % $cycleDuration : 0;
    $sourceIndex = 0;
    foreach ($sources as $candidateIndex => $source) {
        $duration = (int)$source['duration'];
        if ($position < $duration) { $sourceIndex = $candidateIndex; break; }
        $position -= $duration;
    }
    $orderedSources = array_merge(array_slice($sources, $sourceIndex), array_slice($sources, 0, $sourceIndex));
    $playing = ['type'=>'video_playlist'] + $orderedSources[0];
    $startOffset = $position;
    $params['start'] = $startOffset;
    // Start on the scheduled clip, then play the remaining clips and loop.
    $playlistIds = array_merge(array_column(array_slice($orderedSources, 1), 'id'), [$orderedSources[0]['id']]);
    $params['playlist'] = implode(',', $playlistIds);
    $embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($orderedSources[0]['id']) . '?' . http_build_query($params);

    return [
        'timezone'=>'America/Vancouver','timezone_label'=>$now->format('T'),
        'time_label'=>$now->format('g:i A'),'date_label'=>$now->format('l, F j'),
        'current'=>$current,'next'=>$next,'blocks'=>$blocks,'playing'=>$playing,
        'sources'=>[],'source_key'=>'youtube-' . $current['key'],
        'start_offset'=>$startOffset,'embed_url'=>$embed,'server_time'=>$now->getTimestamp(),
        'source_label'=>'NASA YouTube · scheduled fallback',
    ];
}
