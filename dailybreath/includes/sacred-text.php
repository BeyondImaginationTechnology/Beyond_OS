<?php
declare(strict_types=1);

function dailybreath_faith_tradition(?string $value): string
{
    return in_array($value, ['bible', 'torah', 'quran'], true) ? $value : 'bible';
}

function dailybreath_tradition_label(string $tradition): string
{
    $labels = ['bible'=>'Bible', 'torah'=>'Torah & Tanakh', 'quran'=>'Quran'];
    return $labels[dailybreath_faith_tradition($tradition)];
}

/** @return array<string,array<string,int>> */
function dailybreath_bible_book_groups(bool $torahOnly = false): array
{
    $groups = [
        'Hebrew Scriptures' => ['Genesis'=>50,'Exodus'=>40,'Leviticus'=>27,'Numbers'=>36,'Deuteronomy'=>34,'Joshua'=>24,'Judges'=>21,'Ruth'=>4,'1 Samuel'=>31,'2 Samuel'=>24,'1 Kings'=>22,'2 Kings'=>25,'1 Chronicles'=>29,'2 Chronicles'=>36,'Ezra'=>10,'Nehemiah'=>13,'Esther'=>10,'Job'=>42,'Psalms'=>150,'Proverbs'=>31,'Ecclesiastes'=>12,'Song of Solomon'=>8,'Isaiah'=>66,'Jeremiah'=>52,'Lamentations'=>5,'Ezekiel'=>48,'Daniel'=>12,'Hosea'=>14,'Joel'=>3,'Amos'=>9,'Obadiah'=>1,'Jonah'=>4,'Micah'=>7,'Nahum'=>3,'Habakkuk'=>3,'Zephaniah'=>3,'Haggai'=>2,'Zechariah'=>14,'Malachi'=>4],
        'New Testament' => ['Matthew'=>28,'Mark'=>16,'Luke'=>24,'John'=>21,'Acts'=>28,'Romans'=>16,'1 Corinthians'=>16,'2 Corinthians'=>13,'Galatians'=>6,'Ephesians'=>6,'Philippians'=>4,'Colossians'=>4,'1 Thessalonians'=>5,'2 Thessalonians'=>3,'1 Timothy'=>6,'2 Timothy'=>4,'Titus'=>3,'Philemon'=>1,'Hebrews'=>13,'James'=>5,'1 Peter'=>5,'2 Peter'=>3,'1 John'=>5,'2 John'=>1,'3 John'=>1,'Jude'=>1,'Revelation'=>22],
    ];
    return $torahOnly ? ['Torah & Tanakh' => $groups['Hebrew Scriptures']] : $groups;
}

/** @return array<string,string> */
function dailybreath_bible_book_codes(): array
{
    $books = array_merge(...array_values(dailybreath_bible_book_groups()));
    return array_combine(array_keys($books), ['GEN','EXO','LEV','NUM','DEU','JOS','JDG','RUT','1SA','2SA','1KI','2KI','1CH','2CH','EZR','NEH','EST','JOB','PSA','PRO','ECC','SOL','ISA','JER','LAM','EZE','DAN','HOS','JOE','AMO','OBA','JON','MIC','NAH','HAB','ZEP','HAG','ZEC','MAL','MAT','MAR','LUK','JOH','ACT','ROM','1CO','2CO','GAL','EPH','PHI','COL','1TH','2TH','1TI','2TI','TIT','PHM','HEB','JAM','1PE','2PE','1JO','2JO','3JO','JUD','REV']);
}

function dailybreath_quran_path(): string
{
    return dirname(__DIR__) . '/data/quran-pickthall-vpl.txt';
}

function dailybreath_quran_name(string $raw): string
{
    $name = trim(explode(' (', $raw, 2)[0]);
    if ($name === 'AL-E-IMRAN') return 'Al-Imran';
    return ucwords(strtolower(str_replace('AL-', 'Al-', $name)), " -'");
}

/** @param array<int,string> $terms */
function dailybreath_text_contains_terms(string $haystack, array $terms): bool
{
    foreach ($terms as $term) if (strpos($haystack, $term) === false) return false;
    return true;
}

/** @return array<int,string> */
function dailybreath_quran_surahs(): array
{
    static $surahs = null;
    if (is_array($surahs)) return $surahs;
    $surahs = [];
    $handle = @fopen(dailybreath_quran_path(), 'rb');
    if (!$handle) return $surahs;
    while (($line = fgets($handle)) !== false) {
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('|', trim($line), 4);
        if (count($parts) !== 4) continue;
        $number = (int)$parts[0];
        if ($number > 0 && !isset($surahs[$number])) $surahs[$number] = dailybreath_quran_name($parts[2]);
    }
    fclose($handle);
    ksort($surahs);
    return $surahs;
}

/** @return array<int,array{verse_number:int,verse_text:string}> */
function dailybreath_sacred_chapter(string $tradition, string $book, int $chapter): array
{
    $tradition = dailybreath_faith_tradition($tradition);
    $verses = [];
    if ($tradition === 'quran') {
        $surah = max(1, min(114, (int)$book));
        $handle = @fopen(dailybreath_quran_path(), 'rb');
        if (!$handle) return [];
        while (($line = fgets($handle)) !== false) {
            if ($line === '' || $line[0] === '#') continue;
            $parts = explode('|', trim($line), 4);
            if (count($parts) !== 4 || (int)$parts[0] !== $surah) continue;
            $verses[] = ['verse_number'=>(int)$parts[1], 'verse_text'=>$parts[3]];
        }
        fclose($handle);
        return $verses;
    }

    $codes = dailybreath_bible_book_codes();
    if (!isset($codes[$book])) return [];
    $handle = @fopen(dirname(__DIR__) . '/data/engwebp_vpl.txt', 'rb');
    if (!$handle) return [];
    $started = false;
    while (($line = fgets($handle)) !== false) {
        if (!preg_match('/^([A-Z0-9]{3}) (\d+):(\d+) (.+)$/u', trim($line), $match)) continue;
        if ($match[1] === $codes[$book] && (int)$match[2] === $chapter) {
            $started = true;
            $verses[] = ['verse_number'=>(int)$match[3], 'verse_text'=>$match[4]];
        } elseif ($started) break;
    }
    fclose($handle);
    return $verses;
}

/** @return array<int,array{reference:string,text:string,url:string}> */
function dailybreath_search_sacred_text(string $tradition, string $query, int $limit = 75): array
{
    $tradition = dailybreath_faith_tradition($tradition);
    $terms = array_values(array_filter(preg_split('/\s+/u', strtolower(trim($query))) ?: []));
    if (!$terms) return [];
    $matches = [];
    if ($tradition === 'quran') {
        $surahs = dailybreath_quran_surahs();
        $handle = @fopen(dailybreath_quran_path(), 'rb');
        if (!$handle) return [];
        while (($line = fgets($handle)) !== false && count($matches) < $limit) {
            if ($line === '' || $line[0] === '#') continue;
            $parts = explode('|', trim($line), 4);
            if (count($parts) !== 4) continue;
            $surah = (int)$parts[0];
            $verse = (int)$parts[1];
            $reference = ($surahs[$surah] ?? ('Surah ' . $surah)) . ' ' . $surah . ':' . $verse;
            $haystack = strtolower($reference . ' ' . $parts[3]);
            if (!dailybreath_text_contains_terms($haystack, $terms)) continue;
            $matches[] = ['reference'=>$reference, 'text'=>$parts[3], 'url'=>'?tradition=quran&book='.$surah.'&chapter=1#verse-'.$verse];
        }
        fclose($handle);
        return $matches;
    }

    $groups = dailybreath_bible_book_groups($tradition === 'torah');
    $allowedCodes = array_flip(array_intersect_key(dailybreath_bible_book_codes(), array_merge(...array_values($groups))));
    $namesByCode = array_flip(dailybreath_bible_book_codes());
    $handle = @fopen(dirname(__DIR__) . '/data/engwebp_vpl.txt', 'rb');
    if (!$handle) return [];
    while (($line = fgets($handle)) !== false && count($matches) < $limit) {
        if (!preg_match('/^([A-Z0-9]{3}) (\d+):(\d+) (.+)$/u', trim($line), $match) || !isset($allowedCodes[$match[1]])) continue;
        $name = $namesByCode[$match[1]];
        $reference = $name . ' ' . (int)$match[2] . ':' . (int)$match[3];
        $haystack = strtolower($reference . ' ' . $match[4]);
        if (!dailybreath_text_contains_terms($haystack, $terms)) continue;
        $matches[] = ['reference'=>$reference, 'text'=>$match[4], 'url'=>'?tradition='.$tradition.'&book='.rawurlencode($name).'&chapter='.(int)$match[2].'#verse-'.(int)$match[3]];
    }
    fclose($handle);
    return $matches;
}

/** @return array<string,mixed> */
function dailybreath_interfaith_verse_of_day(PDO $pdo, string $tradition, string $locale = 'en', ?string $date = null): array
{
    $date = $date ?: date('Y-m-d');
    $tradition = dailybreath_faith_tradition($tradition);
    $bible = dailybreath_verse_of_day($pdo, $locale, $date);
    if ($tradition === 'bible') return $bible + ['tradition'=>'bible', 'reader_book'=>$bible['book'], 'reader_chapter'=>$bible['chapter']];

    $text = strtolower((string)($bible['reference'] ?? '') . ' ' . (string)($bible['text'] ?? ''));
    $theme = preg_match('/peace|still|rest|anxious|sleep|quiet|calm/u', $text) ? 'peace'
        : (preg_match('/recover|mercy|forgiv|return|free|strength|tempt|rise/u', $text) ? 'recovery' : 'courage');

    if ($tradition === 'torah') {
        $groups = dailybreath_bible_book_groups(true);
        $flat = array_merge(...array_values($groups));
        $book = (string)($bible['book'] ?? 'Psalms');
        if (isset($flat[$book])) {
            $verses = dailybreath_sacred_chapter('torah', $book, (int)$bible['chapter']);
            foreach ($verses as $verse) if ($verse['verse_number'] === (int)$bible['verse']) {
                return ['text'=>$verse['verse_text'],'reference'=>$book.' '.(int)$bible['chapter'].':'.(int)$bible['verse'],'book'=>$book,'chapter'=>(int)$bible['chapter'],'verse'=>(int)$bible['verse'],'reader_book'=>$book,'reader_chapter'=>(int)$bible['chapter'],'source'=>'matched_torah_theme','tradition'=>'torah'];
            }
        }
        $pools = [
            'courage'=>[['Deuteronomy',31,6],['Joshua',1,9],['Psalms',27,14],['Isaiah',41,10]],
            'peace'=>[['Psalms',4,8],['Psalms',23,4],['Isaiah',26,3],['Proverbs',3,5]],
            'recovery'=>[['Psalms',40,1],['Psalms',107,14],['Isaiah',43,2],['Proverbs',24,16]],
        ];
        [$book,$chapter,$number] = $pools[$theme][abs(crc32($date)) % count($pools[$theme])];
        $verses = dailybreath_sacred_chapter('torah', $book, $chapter);
        $selected = array_values(array_filter($verses, static fn(array $verse): bool => $verse['verse_number'] === $number))[0] ?? $verses[0];
        return ['text'=>$selected['verse_text'],'reference'=>$book.' '.$chapter.':'.$selected['verse_number'],'book'=>$book,'chapter'=>$chapter,'verse'=>$selected['verse_number'],'reader_book'=>$book,'reader_chapter'=>$chapter,'source'=>'torah_theme_match','tradition'=>'torah'];
    }

    $pools = [
        'courage'=>[[3,200],[2,286],[9,40],[94,5],[65,3]],
        'peace'=>[[13,28],[2,153],[39,23],[89,27],[48,4]],
        'recovery'=>[[39,53],[12,87],[3,139],[5,90],[94,6]],
    ];
    [$surah,$number] = $pools[$theme][abs(crc32($date)) % count($pools[$theme])];
    $verses = dailybreath_sacred_chapter('quran', (string)$surah, 1);
    $selected = array_values(array_filter($verses, static fn(array $verse): bool => $verse['verse_number'] === $number))[0] ?? $verses[0];
    $name = dailybreath_quran_surahs()[$surah] ?? ('Surah '.$surah);
    return ['text'=>$selected['verse_text'],'reference'=>$name.' '.$surah.':'.$selected['verse_number'],'book'=>$name,'chapter'=>$surah,'verse'=>$selected['verse_number'],'reader_book'=>(string)$surah,'reader_chapter'=>1,'source'=>'quran_theme_match','tradition'=>'quran'];
}

function dailybreath_scripture_url(array $verse, string $prefix = ''): string
{
    $base = rtrim($prefix, '/') . '/dailybreath/scripture.php';
    if ($prefix === '') $base = 'scripture.php';
    return $base . '?tradition=' . rawurlencode((string)($verse['tradition'] ?? 'bible'))
        . '&book=' . rawurlencode((string)($verse['reader_book'] ?? $verse['book'] ?? 'Psalms'))
        . '&chapter=' . max(1, (int)($verse['reader_chapter'] ?? $verse['chapter'] ?? 1))
        . '#verse-' . max(1, (int)($verse['verse'] ?? 1));
}
