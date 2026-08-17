<?php
declare(strict_types=1);

/**
 * Shared DailyBreath Verse of the Day loader.
 * Both the signed-in home and the Beyond ID login page use this source,
 * so publishing a verse in Daily Studio updates both screens.
 *
 * @return array{text:string,reference:string,book:string,chapter:int,verse:int,source:string}
 */
function dailybreath_verse_of_day(PDO $pdo, string $locale = 'en'): array
{
    $fallbacks = [
        ['Be still, and know that I am God.', 'Psalm 46:10'],
        ['Yahweh is my shepherd: I shall lack nothing.', 'Psalm 23:1'],
        ['Trust in Yahweh with all your heart, and don’t lean on your own understanding.', 'Proverbs 3:5'],
        ['I can do all things through Christ, who strengthens me.', 'Philippians 4:13'],
        ['For we walk by faith, not by sight.', '2 Corinthians 5:7'],
        ['Cast all your worries on him, because he cares for you.', '1 Peter 5:7'],
        ['The joy of Yahweh is your strength.', 'Nehemiah 8:10'],
        ['Let all that you do be done in love.', '1 Corinthians 16:14'],
        ['Yahweh is my light and my salvation. Whom shall I fear?', 'Psalm 27:1'],
        ['My grace is sufficient for you, for my power is made perfect in weakness.', '2 Corinthians 12:9'],
        ['In peace I will both lay myself down and sleep, for you alone, Yahweh, make me live in safety.', 'Psalm 4:8'],
        ['Those who wait for Yahweh will renew their strength.', 'Isaiah 40:31'],
        ['Don’t be anxious for anything, but in everything, by prayer and petition with thanksgiving, let your requests be made known to God.', 'Philippians 4:6'],
        ['We know that all things work together for good for those who love God.', 'Romans 8:28'],
    ];

    $result = dailybreath_recovery_verse_for_date(date('Y-m-d'), false);
    try {
        // Prefer the visitor's language, then use the managed English edition
        // until a localized post has been published for the same experience.
        $query = $pdo->prepare("SELECT verse_text, scripture_reference FROM verse_day_posts WHERE status='published' AND publish_date<=? AND locale IN (?, 'en') ORDER BY CASE WHEN locale=? THEN 0 ELSE 1 END, publish_date DESC, id DESC LIMIT 1");
        $query->execute([date('Y-m-d'), $locale, $locale]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row && trim((string)($row['verse_text'] ?? '')) !== '') {
            $result = [
                'text' => trim((string)$row['verse_text']),
                'reference' => trim((string)($row['scripture_reference'] ?? '')),
                'source' => 'verse_day_posts',
            ];
        }
    } catch (Throwable $exception) {
        // Older installs may not have this table yet.
    }

    if ($result === null) {
        try {
            $query = $pdo->prepare("SELECT title, body FROM beyond_content WHERE product='dailybreath' AND status='published' AND scheduled_for<=? ORDER BY scheduled_for DESC, id DESC LIMIT 1");
            $query->execute([date('Y-m-d')]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if ($row && trim((string)($row['title'] ?? '')) !== '') {
                $result = [
                    'text' => trim((string)$row['title']),
                    'reference' => trim((string)($row['body'] ?? '')),
                    'source' => 'beyond_content',
                ];
            }
        } catch (Throwable $exception) {
            // Fall through to the bundled daily rotation.
        }
    }

    if ($result === null) {
        $result = dailybreath_recovery_verse_for_date(date('Y-m-d'));
    }

    if ($result === null) {
        $webFallback = dailybreath_web_verse_fallback();
        if ($webFallback !== null) {
            $result = $webFallback;
        } else {
            $index = (int)(abs(crc32(date('Y-m-d'))) % count($fallbacks));
            $result = [
                'text' => $fallbacks[$index][0],
                'reference' => $fallbacks[$index][1],
                'source' => 'bundled_rotation',
            ];
        }
    }

    $location = dailybreath_reference_location($result['reference']);
    return $result + $location;
}

/** @return array{text:string,reference:string,source:string,audio_file:string}|null */
function dailybreath_recovery_verse_for_date(string $date, bool $rotate = true): ?array
{
    $source = dirname(__DIR__) . '/data/daily-verses.json';
    if (!is_file($source)) return null;
    $document = json_decode((string)file_get_contents($source), true);
    $entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
    if (!$entries) return null;

    $entry = null;
    foreach ($entries as $candidate) {
        if (($candidate['schedule_date'] ?? null) === $date) {
            $entry = $candidate;
            break;
        }
    }
    if ($entry === null && !$rotate) return null;
    if ($entry === null) {
        $entry = $entries[(int)(abs(crc32($date)) % count($entries))];
    }

    return [
        'text' => trim((string)($entry['text'] ?? '')),
        'reference' => trim((string)($entry['reference'] ?? '')),
        'source' => ($entry['schedule_date'] ?? null) === $date ? 'scheduled_recovery_library' : 'bundled_recovery_rotation',
        'audio_file' => basename((string)($entry['audio_file'] ?? '')),
    ];
}

/** @return array<string,mixed>|null */
function dailybreath_recovery_devotional_for_date(string $date, bool $rotate = true): ?array
{
    $source = dirname(__DIR__) . '/data/daily-devotionals.json';
    if (!is_file($source)) return null;
    $document = json_decode((string)file_get_contents($source), true);
    $entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
    if (!$entries) return null;

    foreach ($entries as $entry) if (($entry['schedule_date'] ?? null) === $date && ($entry['schedule_role'] ?? 'primary') === 'primary') return $entry;
    foreach ($entries as $entry) if (($entry['schedule_date'] ?? null) === $date) return $entry;
    if (!$rotate) return null;
    return $entries[(int)(abs(crc32($date)) % count($entries))];
}

/** @return array<string,mixed>|null */
function dailybreath_recovery_challenge_for_date(string $date): ?array
{
    $source = dirname(__DIR__) . '/data/recovery-challenges.json';
    if (!is_file($source)) return null;
    $document = json_decode((string)file_get_contents($source), true);
    $entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
    $active = array_values(array_filter($entries, static fn(array $entry): bool =>
        (string)($entry['starts_on'] ?? '') <= $date && (string)($entry['ends_on'] ?? '') >= $date
    ));
    if (!$active) return null;
    usort($active, static fn(array $left, array $right): int => strcmp((string)$right['starts_on'], (string)$left['starts_on']));
    return $active[0] + ['source' => 'bundled_recovery_challenge'];
}

/** @return array{text:string,reference:string,source:string}|null */
function dailybreath_web_verse_fallback(): ?array
{
    $source = dirname(__DIR__) . '/data/engwebp_vpl.txt';
    if (!is_file($source)) return null;
    $lines = file($source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return null;

    $bookNames = [
        'GEN'=>'Genesis','EXO'=>'Exodus','LEV'=>'Leviticus','NUM'=>'Numbers','DEU'=>'Deuteronomy','JOS'=>'Joshua','JDG'=>'Judges','RUT'=>'Ruth',
        '1SA'=>'1 Samuel','2SA'=>'2 Samuel','1KI'=>'1 Kings','2KI'=>'2 Kings','1CH'=>'1 Chronicles','2CH'=>'2 Chronicles','EZR'=>'Ezra','NEH'=>'Nehemiah',
        'EST'=>'Esther','JOB'=>'Job','PSA'=>'Psalms','PRO'=>'Proverbs','ECC'=>'Ecclesiastes','SOL'=>'Song of Solomon','ISA'=>'Isaiah','JER'=>'Jeremiah',
        'LAM'=>'Lamentations','EZE'=>'Ezekiel','DAN'=>'Daniel','HOS'=>'Hosea','JOE'=>'Joel','AMO'=>'Amos','OBA'=>'Obadiah','JON'=>'Jonah','MIC'=>'Micah',
        'NAH'=>'Nahum','HAB'=>'Habakkuk','ZEP'=>'Zephaniah','HAG'=>'Haggai','ZEC'=>'Zechariah','MAL'=>'Malachi','MAT'=>'Matthew','MAR'=>'Mark',
        'LUK'=>'Luke','JOH'=>'John','ACT'=>'Acts','ROM'=>'Romans','1CO'=>'1 Corinthians','2CO'=>'2 Corinthians','GAL'=>'Galatians','EPH'=>'Ephesians',
        'PHI'=>'Philippians','COL'=>'Colossians','1TH'=>'1 Thessalonians','2TH'=>'2 Thessalonians','1TI'=>'1 Timothy','2TI'=>'2 Timothy','TIT'=>'Titus',
        'PHM'=>'Philemon','HEB'=>'Hebrews','JAM'=>'James','1PE'=>'1 Peter','2PE'=>'2 Peter','1JO'=>'1 John','2JO'=>'2 John','3JO'=>'3 John','JUD'=>'Jude','REV'=>'Revelation',
    ];

    $start = (int)(abs(crc32(date('Y-m-d'))) % count($lines));
    for ($offset = 0, $total = count($lines); $offset < $total; $offset++) {
        $line = $lines[($start + $offset) % $total];
        if (!preg_match('/^([1-3]?[A-Z]{2,3})\s+(\d+):(\d+)\s+(.+)$/u', $line, $match)) continue;
        if (!isset($bookNames[$match[1]])) continue;
        $result = [
            'text' => trim($match[4]),
            'reference' => $bookNames[$match[1]] . ' ' . (int)$match[2] . ':' . (int)$match[3],
            'source' => 'web_bundled_bible',
        ];
        return $result;
    }

    return null;
}

/** @return array{book:string,chapter:int,verse:int} */
function dailybreath_reference_location(string $reference): array
{
    $book = 'Psalms';
    $chapter = 46;
    $verse = 1;

    if (preg_match('/^\s*((?:[1-3]\s*)?[A-Za-z]+(?:\s+[A-Za-z]+)*)\s+(\d+)(?::(\d+))?/u', $reference, $match)) {
        $book = trim($match[1]);
        $chapter = max(1, (int)$match[2]);
        $verse = isset($match[3]) ? max(1, (int)$match[3]) : 1;
    }

    $aliases = [
        'Psalm' => 'Psalms',
        'Psalms' => 'Psalms',
        'Song of Songs' => 'Song of Solomon',
        'Song of Solomon' => 'Song of Solomon',
        'Revelations' => 'Revelation',
    ];
    $book = $aliases[$book] ?? $book;

    return ['book' => $book, 'chapter' => $chapter, 'verse' => $verse];
}

function dailybreath_bible_url(array $verse, string $prefix = ''): string
{
    $base = rtrim($prefix, '/') . '/dailybreath/bible.php';
    if ($prefix === '') $base = 'bible.php';
    return $base
        . '?book=' . rawurlencode((string)($verse['book'] ?? 'Psalms'))
        . '&chapter=' . max(1, (int)($verse['chapter'] ?? 46))
        . '#verse-' . max(1, (int)($verse['verse'] ?? 1));
}
