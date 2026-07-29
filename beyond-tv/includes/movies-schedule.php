<?php
declare(strict_types=1);

function beyond_movies_catalog(): array {
    return [
        [
            'title' => 'Little Fockers', 'year' => '2010', 'genre' => 'Comedy · Family',
            'runtime' => '1 hr 38 min', 'duration' => 5861, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Little%20Fockers%20%282010%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Tropic Thunder', 'year' => '2008', 'genre' => 'Comedy · Action',
            'runtime' => '2 hr 1 min', 'duration' => 7269, 'rating' => 'R',
            'url' => 'https://archive.org/download/ben-stiller-movies/Tropic%20Thunder%20%282008%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'The Heartbreak Kid', 'year' => '2007', 'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 56 min', 'duration' => 6933, 'rating' => 'R',
            'url' => 'https://archive.org/download/ben-stiller-movies/The%20Heartbreak%20Kid%20%282007%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'School for Scoundrels', 'year' => '2006', 'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 48 min', 'duration' => 6462, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/School%20For%20Scoundrels%20%282006%29%20%28MIRAMAX%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Madagascar', 'year' => '2005', 'genre' => 'Animation · Comedy · Family',
            'runtime' => '1 hr 26 min', 'duration' => 5155, 'rating' => 'PG',
            'url' => 'https://archive.org/download/ben-stiller-movies/Madagascar%20%282005%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Dodgeball: A True Underdog Story', 'year' => '2004', 'genre' => 'Comedy · Sports',
            'runtime' => '1 hr 32 min', 'duration' => 5522, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Dodgeball%20-%20A%20True%20Underdog%20Story%20%282004%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Along Came Polly', 'year' => '2004', 'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 30 min', 'duration' => 5410, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Along%20Came%20Polly%20%282004%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Starsky & Hutch', 'year' => '2004', 'genre' => 'Comedy · Crime · Action',
            'runtime' => '1 hr 41 min', 'duration' => 6031, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Starsky%20%26%20Hutch%20%282004%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Meet the Fockers', 'year' => '2004', 'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 55 min', 'duration' => 6917, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Meet%20The%20Fockers%20%282004%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Envy', 'year' => '2004', 'genre' => 'Comedy',
            'runtime' => '1 hr 39 min', 'duration' => 5956, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Envy%20%282004%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Duplex', 'year' => '2003', 'genre' => 'Comedy · Dark Comedy',
            'runtime' => '1 hr 29 min', 'duration' => 5350, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Duplex%20%282003%29%20%28MIRAMAX%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Orange County', 'year' => '2002', 'genre' => 'Comedy',
            'runtime' => '1 hr 19 min', 'duration' => 4728, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Orange%20County%20%282002%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Zoolander', 'year' => '2001', 'genre' => 'Comedy',
            'runtime' => '1 hr 29 min', 'duration' => 5352, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Zoolander%20%282001%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
        [
            'title' => 'Meet the Parents', 'year' => '2000', 'genre' => 'Comedy · Romance',
            'runtime' => '1 hr 48 min', 'duration' => 6460, 'rating' => 'PG-13',
            'url' => 'https://archive.org/download/ben-stiller-movies/Meet%20The%20Parents%20%282000%29.mp4',
            'rights_url' => 'https://archive.org/details/ben-stiller-movies',
        ],
    ];
}

function beyond_movies_schedule_state(?DateTimeImmutable $now = null): array {
    $timezone = new DateTimeZone('America/Vancouver');
    $now = $now?->setTimezone($timezone) ?? new DateTimeImmutable('now', $timezone);
    $movies = beyond_movies_catalog();
    $slotSeconds = 2 * 60 * 60;
    $weekday = (int)$now->format('N');
    $slot = intdiv((int)$now->format('G'), 2);
    $dailyOffset = ($weekday - 1) * 5;
    $currentIndex = $movies ? ($dailyOffset + $slot) % count($movies) : 0;
    $nextIndex = $movies ? ($currentIndex + 1) % count($movies) : 0;
    $slotStart = $now->setTime($slot * 2, 0, 0);
    $elapsedInSlot = max(0, $now->getTimestamp() - $slotStart->getTimestamp());
    $currentDuration = max(1, (int)($movies[$currentIndex]['duration'] ?? 1));
    $startOffset = $elapsedInSlot % $currentDuration;
    $ordered = $movies
        ? array_merge(array_slice($movies, $currentIndex), array_slice($movies, 0, $currentIndex))
        : [];
    $current = $movies[$currentIndex] ?? [];
    $next = $movies[$nextIndex] ?? [];
    $sourceKey = $current ? sha1($now->format('N') . '|' . $slot . '|' . (string)$current['url']) : '';

    return [
        'current' => $current,
        'next' => $next,
        'label' => strtoupper($now->format('l')) . ' MOVIES · 2-HOUR BLOCKS',
        'embed_url' => (string)($current['url'] ?? ''),
        'player_url' => '/beyond-tv/movie-player.php',
        'source_key' => $sourceKey,
        'start_offset' => $startOffset,
        'playlist_duration' => $slotSeconds,
        'slot_start' => $slotStart->format(DATE_ATOM),
        'slot_end' => $slotStart->modify('+2 hours')->format(DATE_ATOM),
        'weekday' => $now->format('l'),
        'slot_hours' => 2,
        'movies' => $movies,
        'sources' => $ordered,
        'timezone' => 'America/Vancouver',
        'server_time' => $now->format(DATE_ATOM),
    ];
}
