<?php
declare(strict_types=1);

function beyond_movies_catalog(): array {
    return [
        [
            'title' => "Gulliver's Travels",
            'year' => '1939',
            'genre' => 'Animation · Fantasy · Family',
            'runtime' => '1 hr 16 min',
            'duration' => 4581,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Gullivers%20Travels%20%281939%29.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:Gullivers_Travels_(1939).webm',
        ],
        [
            'title' => 'The Kid',
            'year' => '1921',
            'genre' => 'Comedy · Family · Drama',
            'runtime' => '1 hr 8 min',
            'duration' => 4100,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/The%20Kid%20%281921%29%20by%20Charlie%20Chaplin.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:The_Kid_(1921)_by_Charlie_Chaplin.webm',
        ],
        [
            'title' => 'Sherlock Jr.',
            'year' => '1924',
            'genre' => 'Comedy · Mystery · Family',
            'runtime' => '44 min',
            'duration' => 2646,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Sherlock%20Jr.%20%281924%29.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:Sherlock_Jr._(1924).webm',
        ],
        [
            'title' => 'The General',
            'year' => '1926',
            'genre' => 'Comedy · Adventure · Family',
            'runtime' => '1 hr 16 min',
            'duration' => 4552,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/The%20General%20%281926%29.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:The_General_(1926).webm',
        ],
        [
            'title' => 'The Little Princess',
            'year' => '1939',
            'genre' => 'Family · Musical · Drama',
            'runtime' => '1 hr 33 min',
            'duration' => 5569,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/The%20Little%20Princess%20%281939%29.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:The_Little_Princess_(1939).webm',
        ],
        [
            'title' => 'His Girl Friday',
            'year' => '1940',
            'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 32 min',
            'duration' => 5518,
            'rating' => 'General audiences',
            'url' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/His%20Girl%20Friday%20%281940%29.webm',
            'rights_url' => 'https://commons.wikimedia.org/wiki/File:His_Girl_Friday_(1940).webm',
        ],
    ];
}

function beyond_movies_schedule_state(?DateTimeImmutable $now = null): array {
    $timezone = new DateTimeZone('America/Vancouver');
    $now = $now?->setTimezone($timezone) ?? new DateTimeImmutable('now', $timezone);
    $movies = beyond_movies_catalog();
    $total = array_sum(array_column($movies, 'duration'));
    $position = $total > 0 ? $now->getTimestamp() % $total : 0;
    $currentIndex = 0;
    $startOffset = 0;

    foreach ($movies as $index => $movie) {
        if ($position < (int)$movie['duration']) {
            $currentIndex = (int)$index;
            $startOffset = $position;
            break;
        }
        $position -= (int)$movie['duration'];
    }

    $ordered = array_merge(array_slice($movies, $currentIndex), array_slice($movies, 0, $currentIndex));
    $current = $movies[$currentIndex] ?? [];
    $next = $movies[($currentIndex + 1) % max(1, count($movies))] ?? [];
    $sourceKey = $current ? sha1((string)$current['url'] . '|' . $currentIndex) : '';

    return [
        'current' => $current,
        'next' => $next,
        'label' => 'GENERAL AUDIENCE · LIVE ROTATION',
        'embed_url' => (string)($current['url'] ?? ''),
        'player_url' => '/beyond-tv/movie-player.php',
        'source_key' => $sourceKey,
        'start_offset' => $startOffset,
        'playlist_duration' => $total,
        'movies' => $movies,
        'sources' => $ordered,
        'timezone' => 'America/Vancouver',
        'server_time' => $now->format(DATE_ATOM),
    ];
}
