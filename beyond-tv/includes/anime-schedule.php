<?php
declare(strict_types=1);

/**
 * Beyond Anime rotates its full library by weekday in synchronized two-hour
 * blocks. Every viewer resolves the same program from Vancouver server time.
 */
function beyond_anime_schedule_state(?DateTimeImmutable $now = null): array
{
    $timezone = new DateTimeZone('America/Vancouver');
    $now = ($now ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
    $blockIndex = intdiv((int)$now->format('G'), 2);
    $blockStart = $now->setTime($blockIndex * 2, 0, 0);
    $elapsed = max(0, $now->getTimestamp() - $blockStart->getTimestamp());
    $current = beyond_anime_program_for_block($blockStart, $blockIndex, $elapsed);

    $nextBlockIndex = ($blockIndex + 1) % 12;
    $nextDay = $nextBlockIndex === 0 ? $blockStart->modify('+1 day') : $blockStart;
    $next = beyond_anime_program_for_block(
        $nextDay->setTime($nextBlockIndex * 2, 0, 0),
        $nextBlockIndex,
        0
    );

    $state = [
        'timezone' => 'America/Vancouver',
        'timezone_label' => $now->format('T'),
        'server_time' => $now->format(DATE_ATOM),
        'label' => 'LIVE · 2-HOUR ROTATION',
        'current' => beyond_anime_program_metadata($current),
        'next' => beyond_anime_program_metadata($next),
        'playing' => [
            'title' => $current['episode_title'],
            'series' => $current['series'],
            'episode_number' => $current['episode_number'],
            'source_type' => $current['source_type'],
        ],
        'source_key' => $current['source_key'],
        'start_offset' => $current['start_offset'],
        'blocks' => beyond_anime_guide_rows($blockStart),
        'sources' => [],
    ];

    if ($current['source_type'] === 'youtube') {
        $state['embed_url'] = $current['url'];
    } else {
        $state['player_url'] = $current['url'];
        $state['sources'] = [[
            'provider' => 'Internet Archive',
            'title' => $current['series'] . ' · ' . $current['episode_title'],
            'url' => $current['url'],
            'duration' => $current['duration'],
            'type' => 'video',
            'license' => 'Internet Archive item',
        ]];
    }

    return $state;
}

function beyond_anime_guide_rows(?DateTimeImmutable $day = null): array
{
    $timezone = new DateTimeZone('America/Vancouver');
    $day = ($day ?? new DateTimeImmutable('today', $timezone))->setTimezone($timezone);
    $rows = [];
    for ($blockIndex = 0; $blockIndex < 12; $blockIndex++) {
        $program = beyond_anime_program_for_block(
            $day->setTime($blockIndex * 2, 0, 0),
            $blockIndex,
            0
        );
        $rows[] = beyond_anime_program_metadata($program);
    }
    return $rows;
}

function beyond_anime_program_metadata(array $program): array
{
    return [
        'start' => $program['start'],
        'end' => $program['end'],
        'icon' => $program['icon'],
        'title' => $program['series'],
        'lineup' => $program['lineup'],
        'episode_number' => $program['episode_number'],
        'provider' => beyond_anime_provider_label($program),
        'source_key' => $program['source_key'],
    ];
}

function beyond_anime_provider_label(array $program): string
{
    if (($program['source_type'] ?? '') === 'youtube') {
        return 'Official YouTube embed';
    }

    return 'Internet Archive';
}

function beyond_anime_program_for_block(
    DateTimeImmutable $blockStart,
    int $blockIndex,
    int $elapsed
): array {
    $baseLineup = [
        'dragon-ball',
        'kirby',
        'dragon-ball-kai',
        'death-note',
        'dragon-ball-z',
        'yugioh',
        'zatch-bell',
        'digimon',
        'pokemon',
        'kirby',
        'death-note',
        'dragon-ball-z',
    ];
    $weekdayOffset = ((int)$blockStart->format('N') - 1) % count($baseLineup);
    $lineup = array_merge(
        array_slice($baseLineup, $weekdayOffset),
        array_slice($baseLineup, 0, $weekdayOffset)
    );
    $key = $lineup[$blockIndex % count($lineup)];
    $duration = in_array($key, ['zatch-bell', 'digimon', 'pokemon'], true) ? 1320 : 1440;
    if (in_array($key, ['dragon-ball', 'kirby', 'death-note'], true)) $duration = 1380;

    $dayIndex = (int)$blockStart->format('z');
    $episodeAdvance = intdiv(max(0, $elapsed), $duration);
    $seed = ($dayIndex * 3) + ($blockIndex * 5) + $episodeAdvance;
    $startOffset = max(0, $elapsed) % $duration;
    $episode = beyond_anime_episode($key, $seed, $duration, $startOffset);

    return array_merge($episode, [
        'start' => $blockIndex * 2,
        'end' => ($blockIndex + 1) * 2,
        'start_offset' => $startOffset,
        'duration' => $duration,
        'source_key' => $key . ':' . $episode['episode_number'],
    ]);
}

function beyond_anime_episode(string $key, int $seed, int $duration, int $startOffset): array
{
    if ($key === 'kirby') {
        $library = beyond_anime_playable_library('kirby-library.json');
        $episode = $library[$seed % max(1, count($library))] ?? [];
        $number = (int)($episode['episode'] ?? 1);
        $title = (string)($episode['title'] ?? ('Episode ' . $number));
        $file = (string)($episode['archive_file'] ?? $episode['file'] ?? '');
        return [
            'series' => 'Kirby: Right Back at Ya!',
            'icon' => '⭐',
            'episode_number' => $number,
            'episode_title' => $title,
            'lineup' => 'S1 E' . $number . ' · ' . $title,
            'source_type' => 'archive',
            'url' => 'https://archive.org/download/kirby-right-back-at-ya-high-quality-original-format-uncensored/'
                . rawurlencode($file),
        ];
    }

    if ($key === 'death-note') {
        $library = beyond_anime_playable_library('death-note-library.json');
        $episode = $library[$seed % max(1, count($library))] ?? [];
        $number = (int)($episode['episode'] ?? 1);
        $title = (string)($episode['title'] ?? ('Episode ' . $number));
        $file = (string)($episode['archive_file'] ?? $episode['file'] ?? '');
        return [
            'series' => 'Death Note',
            'icon' => '📓',
            'episode_number' => $number,
            'episode_title' => $title,
            'lineup' => 'S1 E' . $number . ' · ' . $title,
            'source_type' => 'archive',
            'url' => 'https://archive.org/download/death-note-complete-2006-2007/'
                . rawurlencode($file),
        ];
    }

    if ($key === 'dragon-ball') {
        $library = beyond_anime_playable_library('dragon-ball-library.json');
        $episode = $library[$seed % max(1, count($library))] ?? [];
        $number = (int)($episode['episode'] ?? 1);
        $title = (string)($episode['title'] ?? ('Episode ' . $number));
        return [
            'series' => 'Dragon Ball',
            'icon' => '🐉',
            'episode_number' => $number,
            'episode_title' => $title,
            'lineup' => 'S1 E' . $number . ' · ' . $title,
            'source_type' => 'archive',
            'url' => (string)($episode['video_url'] ?? ''),
        ];
    }

    if ($key === 'dragon-ball-kai') {
        $number = ($seed % 167) + 1;
        return [
            'series' => 'Dragon Ball Kai',
            'icon' => '🐲',
            'episode_number' => $number,
            'episode_title' => 'Episode ' . $number,
            'lineup' => 'Episode ' . $number,
            'source_type' => 'archive',
            'url' => 'https://archive.org/download/dbkai/Dragon%20Ball%20Kai%20-%20'
                . str_pad((string)$number, 2, '0', STR_PAD_LEFT) . '.mp4',
        ];
    }

    if ($key === 'dragon-ball-z') {
        $library = beyond_anime_playable_library('dbz-westwood-sd-library.json');
        $episode = $library[$seed % max(1, count($library))] ?? [];
        $number = (int)($episode['episode'] ?? 1);
        $title = (string)($episode['title'] ?? ('Episode ' . $number));
        return [
            'series' => 'Dragon Ball Z',
            'icon' => '🔥',
            'episode_number' => $number,
            'episode_title' => $title,
            'lineup' => 'SD · Episode ' . $number . ' · ' . $title,
            'source_type' => 'archive',
            'url' => (string)($episode['video_url'] ?? ''),
        ];
    }

    if ($key === 'yugioh') {
        $number = ($seed % 49) + 1;
        return [
            'series' => 'Yu-Gi-Oh! Duel Monsters',
            'icon' => '🃏',
            'episode_number' => $number,
            'episode_title' => 'Season 1 · Episode ' . $number,
            'lineup' => 'Season 1 · Episode ' . $number,
            'source_type' => 'youtube',
            'url' => 'https://www.youtube-nocookie.com/embed/videoseries?'
                . http_build_query([
                    'list' => 'PLXBcsPKqNstB10447aKbDnkPEJdTV9sj-',
                    'index' => $number - 1,
                    'start' => $startOffset,
                    'autoplay' => 1,
                    'mute' => 1,
                    'controls' => 1,
                    'rel' => 0,
                    'playsinline' => 1,
                    'enablejsapi' => 1,
                ]),
        ];
    }

    if ($key === 'zatch-bell') {
        $number = ($seed % 50) + 1;
        return [
            'series' => 'Zatch Bell!',
            'icon' => '⚡',
            'episode_number' => $number,
            'episode_title' => 'Season 1 · Episode ' . $number,
            'lineup' => 'Season 1 · Episode ' . $number,
            'source_type' => 'archive',
            'url' => 'https://archive.org/download/zatch-bell-collection/Zatch%20Bell/Season%201/'
                . 'Zatch%20Bell%20S01E' . str_pad((string)$number, 2, '0', STR_PAD_LEFT) . '.mp4',
        ];
    }

    if ($key === 'digimon') {
        $library = beyond_anime_playable_library('digimon-library.json');
        $episode = $library[$seed % max(1, count($library))] ?? [];
        $number = (int)($episode['episode'] ?? 1);
        $title = (string)($episode['title'] ?? ('Episode ' . $number));
        return [
            'series' => 'Digimon: Digital Monsters',
            'icon' => '🔷',
            'episode_number' => $number,
            'episode_title' => $title,
            'lineup' => 'S' . (int)($episode['season'] ?? 1) . ' E' . $number . ' · ' . $title,
            'source_type' => 'archive',
            'url' => 'https://archive.org/download/digimon-digital-monsters-the-complete-collection-saban-entertainment-edited-version/'
                . rawurlencode((string)($episode['file'] ?? '')),
        ];
    }

    $library = beyond_anime_playable_library('pokemon-library.json');
    $episode = $library[$seed % max(1, count($library))] ?? [];
    $number = (int)($episode['episode'] ?? 1);
    $title = (string)($episode['title'] ?? ('Episode ' . $number));
    return [
        'series' => 'Pokémon: Indigo League',
        'icon' => '⚡',
        'episode_number' => $number,
        'episode_title' => $title,
        'lineup' => 'S1 E' . $number . ' · ' . $title,
        'source_type' => 'archive',
        'url' => 'https://archive.org/download/pokemon-indigo-league-season-1-1998/'
            . rawurlencode((string)($episode['file'] ?? '')),
    ];
}

function beyond_anime_library(string $filename): array
{
    static $libraries = [];
    if (!array_key_exists($filename, $libraries)) {
        $path = __DIR__ . '/../data/' . basename($filename);
        $libraries[$filename] = json_decode((string)@file_get_contents($path), true) ?: [];
    }
    return $libraries[$filename];
}

function beyond_anime_playable_library(string $filename): array
{
    return array_values(array_filter(
        beyond_anime_library($filename),
        static fn(array $episode): bool => ($episode['playable'] ?? true) !== false
            && ($episode['status'] ?? 'available') !== 'unavailable'
    ));
}
