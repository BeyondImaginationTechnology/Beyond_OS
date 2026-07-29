<?php
declare(strict_types=1);
require_once __DIR__ . '/yugioh-live.php';
require_once __DIR__ . '/beyond-cartoons-schedule.php';
require_once __DIR__ . '/movies-schedule.php';

function beyond_tv_catalog_hourly_rows(string $slug): array {
    $catalog = json_decode((string)@file_get_contents(__DIR__ . '/../data/catalog.json'), true) ?: [];
    $items = [];
    foreach ($catalog as $entry) {
        if (!is_array($entry) || ($entry['channel_slug'] ?? '') !== $slug) continue;
        $map = trim((string)($entry['archive_episode_map'] ?? ''));
        if ($map !== '') {
            $episodes = json_decode((string)@file_get_contents(__DIR__ . '/../data/' . basename($map)), true) ?: [];
            $preferred = [];
            foreach ($episodes as $episode) {
                if (!is_array($episode)) continue;
                $number = (int)($episode['episode'] ?? count($preferred) + 1);
                $extension = strtolower(pathinfo((string)($episode['video_url'] ?? $episode['file'] ?? ''), PATHINFO_EXTENSION));
                if (!isset($preferred[$number]) || $extension === 'mp4') $preferred[$number] = $episode;
            }
            ksort($preferred);
            foreach ($preferred as $number => $episode) $items[] = ['title'=>(string)($episode['title'] ?? $entry['title']),'lineup'=>(string)$entry['title'].' · Episode '.$number,'duration'=>max(300,(int)($episode['runtime_seconds'] ?? 1380))];
        } else {
            $items[] = ['title'=>(string)($entry['title'] ?? 'Beyond TV'),'lineup'=>(string)($entry['genre'] ?? $entry['subtitle'] ?? 'Featured presentation'),'duration'=>max(900,(int)($entry['runtime_seconds'] ?? 5400))];
        }
    }
    if (!$items) return [];
    $total = array_sum(array_column($items, 'duration')); $day = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')); $rows = [];
    for ($hour=0; $hour<24; $hour++) {
        $position = $day->setTime($hour,0)->getTimestamp() % $total; $selected = $items[0];
        foreach ($items as $item) { if ($position < $item['duration']) { $selected=$item; break; } $position -= $item['duration']; }
        $rows[]=['start'=>$hour,'end'=>$hour+1,'icon'=>'▶','title'=>$selected['title'],'lineup'=>$selected['lineup']];
    }
    return $rows;
}

function beyond_tv_cartoon_hourly_rows(): array {
    $day = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')); $rows=[];
    for($hour=0;$hour<24;$hour++){
        $state=beyond_cartoons_schedule_state($day->setTime($hour,0)); $playing=$state['playing']; $episode=(int)$playing['index']+1;
        $rows[]=['start'=>$hour,'end'=>$hour+1,'icon'=>(string)($state['current']['icon']??'📺'),'title'=>(string)$playing['title'],'lineup'=>'Episode '.$episode.' · '.(string)$state['current']['title']];
    }
    return $rows;
}

function beyond_tv_movie_hourly_rows(): array {
    $day=new DateTimeImmutable('today',new DateTimeZone('America/Vancouver'));$rows=[];
    for($hour=0;$hour<24;$hour+=2){$state=beyond_movies_schedule_state($day->setTime($hour,0));$movie=$state['current'];$rows[]=['start'=>$hour,'end'=>$hour+2,'icon'=>'🎬','title'=>(string)$movie['title'],'lineup'=>(string)($movie['year']??'').' · '.(string)($movie['genre']??$state['label'])];}
    return $rows;
}

function beyond_tv_confirmed_presentation_rows(string $slug, array $fallbackRows): array {
    $channels=json_decode((string)@file_get_contents(__DIR__.'/../data/channels.json'),true)?:[];$channel=null;
    foreach($channels as $candidate)if(($candidate['slug']??'')===$slug)$channel=$candidate;
    if(!$channel)return $fallbackRows;$title=(string)($channel['youtube_title']??$channel['now']??'');if($title==='')return $fallbackRows;
    $rows=[];foreach($fallbackRows as $row){$rows[]=['start'=>(int)$row['start'],'end'=>(int)$row['end'],'icon'=>(string)($row['icon']??'▶'),'title'=>$title,'lineup'=>(string)($row['title']??$channel['up_next']??'Featured presentation')];}return $rows;
}

function beyond_tv_after_dark_hourly_rows(): array {
    $hauntingEpisodes = json_decode((string)@file_get_contents(__DIR__ . '/../data/haunting-hour-library.json'), true) ?: [];
    $preferred = [];
    foreach ($hauntingEpisodes as $episode) {
        if (!is_array($episode) || empty($episode['video_url'])) continue;
        $number = (int)($episode['episode'] ?? 0);
        $extension = strtolower(pathinfo((string)$episode['video_url'], PATHINFO_EXTENSION));
        $episode['series'] = 'The Haunting Hour';
        if (!isset($preferred[$number]) || $extension === 'mp4') $preferred[$number] = $episode;
    }
    ksort($preferred); $playlist = array_values($preferred);
    $goosebumps = json_decode((string)@file_get_contents(__DIR__ . '/../data/goosebumps-library.json'), true) ?: [];
    foreach ($goosebumps as $episode) { if (is_array($episode) && !empty($episode['video_url'])) $playlist[] = $episode; }
    $durations = array_map(static fn(array $episode): int => max(60, (int)($episode['runtime_seconds'] ?? 1380)), $playlist);
    $total = array_sum($durations);
    if (!$playlist || $total < 1) return [];
    $day = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver'));
    $rows = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $position = $day->setTime($hour, 0)->getTimestamp() % $total; $index = 0;
        foreach ($durations as $candidate => $duration) { if ($position < $duration) { $index = $candidate; break; } $position -= $duration; }
        $episode = $playlist[$index];
        $series = (string)($episode['series'] ?? 'The Haunting Hour');
        $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>$series === 'Goosebumps' ? '👻' : '🌙','title'=>$series,'lineup'=>'S1 E'.(int)($episode['episode'] ?? ($index+1)).' · '.(string)($episode['title'] ?? 'Episode')];
    }
    return $rows;
}

function beyond_tv_yugioh_hourly_rows(): array {
    $day = new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')); $rows = [];
    $digimon = json_decode((string)@file_get_contents(__DIR__ . '/../data/digimon-library.json'), true) ?: [];
    $pokemon = json_decode((string)@file_get_contents(__DIR__ . '/../data/pokemon-library.json'), true) ?: [];
    $dragonBall = json_decode((string)@file_get_contents(__DIR__ . '/../data/dragon-ball-library.json'), true) ?: [];
    $dragonBallZ = json_decode((string)@file_get_contents(__DIR__ . '/../data/dbz-westwood-sd-library.json'), true) ?: [];
    for ($hour = 0; $hour < 24; $hour++) {
        $state = beyond_yugioh_live_state($day->setTime($hour, 0)->getTimestamp());
        if ($hour >= 0 && $hour < 3 && $dragonBall) {
            $episode = $dragonBall[(($hour * 2) + (int)$day->format('z')) % count($dragonBall)];
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'🐉','title'=>'Dragon Ball','lineup'=>'S1 E'.(int)$episode['episode'].' · '.$episode['title']];
        } elseif ($hour >= 3 && $hour < 6) {
            $episode = (($hour * 2 + (int)$day->format('z')) % 167) + 1;
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'🐲','title'=>'Dragon Ball Kai','lineup'=>'Episode '.$episode];
        } elseif ((($hour >= 6 && $hour < 9) || $hour >= 21) && $dragonBallZ) {
            $episode = $dragonBallZ[(($hour * 2) + (int)$day->format('z')) % count($dragonBallZ)];
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'🔥','title'=>'Dragon Ball Z','lineup'=>'SD · Episode '.(int)$episode['episode'].' · '.$episode['title']];
        } elseif ($hour >= 12 && $hour < 15) {
            $episode = (($hour * 2 + (int)$day->format('z')) % 50) + 1;
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'⚡','title'=>'Zatch Bell!','lineup'=>'Season 1 · Episodes '.$episode.'–'.min(50,$episode+1)];
        } elseif ($hour >= 15 && $hour < 18 && $digimon) {
            $episode = $digimon[(($hour - 15) * 2 + (int)$day->format('z')) % count($digimon)];
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'🔷','title'=>'Digimon: Digital Monsters','lineup'=>'S'.(int)$episode['season'].' E'.(int)$episode['episode'].' · '.$episode['title']];
        } elseif ($hour >= 18 && $hour < 21 && $pokemon) {
            $episode = $pokemon[(($hour - 18) * 2 + (int)$day->format('z')) % count($pokemon)];
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'⚡','title'=>'Pokémon: Indigo League','lineup'=>'S1 E'.(int)$episode['episode'].' · '.$episode['title']];
        } else {
            $rows[] = ['start'=>$hour,'end'=>$hour+1,'icon'=>'🃏','title'=>'Yu-Gi-Oh! Duel Monsters','lineup'=>'Season 1 · Episode '.(int)$state['episode_number']];
        }
    }
    return $rows;
}

/** Kept under the original function name for backwards compatibility. */
function beyond_tv_eight_channel_guide(array $classicState, array $cartoonState): array {
    $featured = json_decode((string) @file_get_contents(__DIR__ . '/../data/featured-channels.json'), true) ?: [];
    $schedules = json_decode((string) @file_get_contents(__DIR__ . '/../data/channel-schedules.json'), true) ?: [];
    $guide = [];
    foreach ($featured as $channel) {
        $slug = (string)($channel['slug'] ?? '');
        $rows = $schedules[$slug] ?? [];
        if ($slug === 'classic-cartoon-theater' && !empty($classicState['blocks'])) { $rows = $classicState['blocks']; }
        if ($slug === 'beyond-cartoons' && !empty($cartoonState['blocks'])) { $rows = $cartoonState['blocks']; }
        if ($slug === 'beyond-after-dark') { $rows = beyond_tv_after_dark_hourly_rows(); }
        if ($slug === 'yugioh-tv') { $rows = beyond_tv_yugioh_hourly_rows(); }
        if ($slug === 'beyond-cartoons') { $rows = beyond_tv_cartoon_hourly_rows(); }
        if ($slug === 'classic-cinema') { $rows = beyond_tv_movie_hourly_rows(); }
        if (in_array($slug, ['bubble-guppies','preschool-francais','beyond-comedy','beyond-family'], true)) { $catalogRows=beyond_tv_catalog_hourly_rows($slug); if($catalogRows)$rows=$catalogRows; }
        if (in_array($slug, ['space-tv','beyond-ancient','beyond-french','beyond-health'], true)) { $rows=beyond_tv_confirmed_presentation_rows($slug,$rows); }
        if (!$rows) { continue; }
        $guide[] = [
            'slug' => $slug,
            'name' => (string)($channel['name'] ?? $slug),
            'icon' => (string)($channel['icon'] ?? '📺'),
            'access' => 'Free · Live library',
            'rows' => $rows,
        ];
    }
    return $guide;
}

function beyond_tv_guide_block(array $rows, int $hour): array {
    foreach ($rows as $row) {
        if ($hour >= (int)($row['start'] ?? 0) && $hour < (int)($row['end'] ?? 0)) { return $row; }
    }
    return $rows[0] ?? ['start'=>0,'end'=>24,'icon'=>'▶','title'=>'Beyond TV','lineup'=>'Curated library'];
}
