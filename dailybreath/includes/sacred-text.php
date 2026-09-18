<?php
declare(strict_types=1);

function dailybreath_faith_tradition(?string $value): string
{
    return in_array($value, ['bible', 'torah', 'quran'], true) ? $value : 'bible';
}

function dailybreath_tradition_label(string $tradition): string
{
    $labels = ['bible'=>'Bible', 'torah'=>'Tanakh', 'quran'=>'Quran'];
    return $labels[dailybreath_faith_tradition($tradition)];
}

function dailybreath_scripture_locale(?string $locale): string
{
    $locale = strtolower((string)$locale);
    return in_array($locale, ['en', 'fr', 'es'], true) ? $locale : 'en';
}

function dailybreath_bible_path(string $tradition, string $locale): string
{
    $locale = dailybreath_scripture_locale($locale);
    $file = $locale === 'fr' ? 'fraLSG_vpl.txt' : ($locale === 'es' ? 'spaRV1909_vpl.txt' : 'engwebp_vpl.txt');
    return dirname(__DIR__) . '/data/' . $file;
}

/** @return Generator<int,array{code:string,chapter:int,verse:int,text:string}> */
function dailybreath_vpl_verses(string $path): Generator
{
    $handle = @fopen($path, 'rb');
    if (!$handle) return;
    $current = null;
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (preg_match('/^([A-Z0-9]{3}) (\d+):(\d+) (.+)$/u', $line, $match)) {
            if ($current !== null) yield $current;
            $current = ['code'=>$match[1], 'chapter'=>(int)$match[2], 'verse'=>(int)$match[3], 'text'=>$match[4]];
        } elseif ($current !== null && $line !== '' && $line[0] !== '#') {
            $current['text'] .= ' ' . $line;
        }
    }
    if ($current !== null) yield $current;
    fclose($handle);
}

function dailybreath_bible_book_name(string $book, string $locale): string
{
    $locale = dailybreath_scripture_locale($locale);
    $maps = [
        'fr' => [
            'Genesis'=>'Genèse','Exodus'=>'Exode','Leviticus'=>'Lévitique','Numbers'=>'Nombres','Deuteronomy'=>'Deutéronome','Joshua'=>'Josué','Judges'=>'Juges',
            '1 Kings'=>'1 Rois','2 Kings'=>'2 Rois','1 Chronicles'=>'1 Chroniques','2 Chronicles'=>'2 Chroniques','Nehemiah'=>'Néhémie','Psalms'=>'Psaumes',
            'Proverbs'=>'Proverbes','Ecclesiastes'=>'Ecclésiaste','Song of Solomon'=>'Cantique des Cantiques','Isaiah'=>'Ésaïe','Jeremiah'=>'Jérémie',
            'Ezekiel'=>'Ézékiel','Hosea'=>'Osée','Joel'=>'Joël','Obadiah'=>'Abdias','Jonah'=>'Jonas','Micah'=>'Michée','Habakkuk'=>'Habacuc',
            'Zephaniah'=>'Sophonie','Haggai'=>'Aggée','Zechariah'=>'Zacharie','Matthew'=>'Matthieu','Mark'=>'Marc','Luke'=>'Luc','John'=>'Jean',
            'Acts'=>'Actes','Romans'=>'Romains','1 Corinthians'=>'1 Corinthiens','2 Corinthians'=>'2 Corinthiens','Galatians'=>'Galates',
            'Ephesians'=>'Éphésiens','Philippians'=>'Philippiens','1 Thessalonians'=>'1 Thessaloniciens','2 Thessalonians'=>'2 Thessaloniciens',
            '1 Timothy'=>'1 Timothée','2 Timothy'=>'2 Timothée','Philemon'=>'Philémon','Hebrews'=>'Hébreux','James'=>'Jacques',
            '1 Peter'=>'1 Pierre','2 Peter'=>'2 Pierre','1 John'=>'1 Jean','2 John'=>'2 Jean','3 John'=>'3 Jean','Revelation'=>'Apocalypse',
        ],
        'es' => [
            'Genesis'=>'Génesis','Exodus'=>'Éxodo','Leviticus'=>'Levítico','Numbers'=>'Números','Deuteronomy'=>'Deuteronomio','Joshua'=>'Josué','Judges'=>'Jueces',
            '1 Kings'=>'1 Reyes','2 Kings'=>'2 Reyes','1 Chronicles'=>'1 Crónicas','2 Chronicles'=>'2 Crónicas','Ezra'=>'Esdras','Nehemiah'=>'Nehemías',
            'Psalms'=>'Salmos','Proverbs'=>'Proverbios','Ecclesiastes'=>'Eclesiastés','Song of Solomon'=>'Cantares','Isaiah'=>'Isaías','Jeremiah'=>'Jeremías',
            'Ezekiel'=>'Ezequiel','Hosea'=>'Oseas','Obadiah'=>'Abdías','Jonah'=>'Jonás','Micah'=>'Miqueas','Nahum'=>'Nahúm','Zephaniah'=>'Sofonías',
            'Haggai'=>'Hageo','Zechariah'=>'Zacarías','Matthew'=>'Mateo','Mark'=>'Marcos','Luke'=>'Lucas','John'=>'Juan','Acts'=>'Hechos',
            'Romans'=>'Romanos','1 Corinthians'=>'1 Corintios','2 Corinthians'=>'2 Corintios','Galatians'=>'Gálatas','Ephesians'=>'Efesios',
            'Philippians'=>'Filipenses','Colossians'=>'Colosenses','1 Thessalonians'=>'1 Tesalonicenses','2 Thessalonians'=>'2 Tesalonicenses',
            '1 Timothy'=>'1 Timoteo','2 Timothy'=>'2 Timoteo','Titus'=>'Tito','Philemon'=>'Filemón','Hebrews'=>'Hebreos','James'=>'Santiago',
            '1 Peter'=>'1 Pedro','2 Peter'=>'2 Pedro','1 John'=>'1 Juan','2 John'=>'2 Juan','3 John'=>'3 Juan','Jude'=>'Judas','Revelation'=>'Apocalipsis',
        ],
    ];
    return $maps[$locale][$book] ?? $book;
}

function dailybreath_jewish_book_name(string $book): string
{
    $names = [
        'Genesis'=>'Bereshit', 'Exodus'=>'Shemot', 'Leviticus'=>'Vayikra', 'Numbers'=>'Bamidbar', 'Deuteronomy'=>'Devarim',
        'Joshua'=>'Yehoshua', 'Judges'=>'Shoftim', 'Ruth'=>'Ruth', '1 Samuel'=>'Shmuel I', '2 Samuel'=>'Shmuel II',
        '1 Kings'=>'Melakhim I', '2 Kings'=>'Melakhim II', '1 Chronicles'=>'Divrei Hayamim I', '2 Chronicles'=>'Divrei Hayamim II',
        'Ezra'=>'Ezra', 'Nehemiah'=>'Nechemyah', 'Esther'=>'Esther', 'Job'=>'Iyov', 'Psalms'=>'Tehillim',
        'Proverbs'=>'Mishlei', 'Ecclesiastes'=>'Kohelet', 'Song of Solomon'=>'Shir HaShirim', 'Isaiah'=>'Yeshayahu',
        'Jeremiah'=>'Yirmeyahu', 'Lamentations'=>'Eikhah', 'Ezekiel'=>'Yechezkel', 'Daniel'=>'Daniel', 'Hosea'=>'Hoshea',
        'Joel'=>'Yoel', 'Amos'=>'Amos', 'Obadiah'=>'Ovadiah', 'Jonah'=>'Yonah', 'Micah'=>'Mikhah', 'Nahum'=>'Nahum',
        'Habakkuk'=>'Habakkuk', 'Zephaniah'=>'Tzefaniah', 'Haggai'=>'Haggai', 'Zechariah'=>'Zekhariah', 'Malachi'=>'Malakhi',
    ];
    return $names[$book] ?? $book;
}

/** @return array<string,array<string,int>> */
function dailybreath_bible_book_groups(bool $torahOnly = false): array
{
    if ($torahOnly) {
        return [
            'Torah' => ['Genesis'=>50,'Exodus'=>40,'Leviticus'=>27,'Numbers'=>36,'Deuteronomy'=>34],
            'Nevi’im · Prophets' => ['Joshua'=>24,'Judges'=>21,'1 Samuel'=>31,'2 Samuel'=>24,'1 Kings'=>22,'2 Kings'=>25,'Isaiah'=>66,'Jeremiah'=>52,'Ezekiel'=>48,'Hosea'=>14,'Joel'=>3,'Amos'=>9,'Obadiah'=>1,'Jonah'=>4,'Micah'=>7,'Nahum'=>3,'Habakkuk'=>3,'Zephaniah'=>3,'Haggai'=>2,'Zechariah'=>14,'Malachi'=>4],
            'Ketuvim · Writings' => ['Psalms'=>150,'Proverbs'=>31,'Job'=>42,'Song of Solomon'=>8,'Ruth'=>4,'Lamentations'=>5,'Ecclesiastes'=>12,'Esther'=>10,'Daniel'=>12,'Ezra'=>10,'Nehemiah'=>13,'1 Chronicles'=>29,'2 Chronicles'=>36],
        ];
    }
    $groups = [
        'Hebrew Scriptures' => ['Genesis'=>50,'Exodus'=>40,'Leviticus'=>27,'Numbers'=>36,'Deuteronomy'=>34,'Joshua'=>24,'Judges'=>21,'Ruth'=>4,'1 Samuel'=>31,'2 Samuel'=>24,'1 Kings'=>22,'2 Kings'=>25,'1 Chronicles'=>29,'2 Chronicles'=>36,'Ezra'=>10,'Nehemiah'=>13,'Esther'=>10,'Job'=>42,'Psalms'=>150,'Proverbs'=>31,'Ecclesiastes'=>12,'Song of Solomon'=>8,'Isaiah'=>66,'Jeremiah'=>52,'Lamentations'=>5,'Ezekiel'=>48,'Daniel'=>12,'Hosea'=>14,'Joel'=>3,'Amos'=>9,'Obadiah'=>1,'Jonah'=>4,'Micah'=>7,'Nahum'=>3,'Habakkuk'=>3,'Zephaniah'=>3,'Haggai'=>2,'Zechariah'=>14,'Malachi'=>4],
        'New Testament' => ['Matthew'=>28,'Mark'=>16,'Luke'=>24,'John'=>21,'Acts'=>28,'Romans'=>16,'1 Corinthians'=>16,'2 Corinthians'=>13,'Galatians'=>6,'Ephesians'=>6,'Philippians'=>4,'Colossians'=>4,'1 Thessalonians'=>5,'2 Thessalonians'=>3,'1 Timothy'=>6,'2 Timothy'=>4,'Titus'=>3,'Philemon'=>1,'Hebrews'=>13,'James'=>5,'1 Peter'=>5,'2 Peter'=>3,'1 John'=>5,'2 John'=>1,'3 John'=>1,'Jude'=>1,'Revelation'=>22],
    ];
    return $groups;
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

/** @return array<int,array<string,mixed>> */
function dailybreath_quran_arabic(): array
{
    static $chapters = null;
    if (is_array($chapters)) return $chapters;
    $decoded = json_decode((string)@file_get_contents(dirname(__DIR__) . '/data/quran-ar.json'), true);
    $chapters = is_array($decoded) ? $decoded : [];
    return $chapters;
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

function dailybreath_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

/** @return array<int,string> */
function dailybreath_quran_surahs(string $locale = 'en'): array
{
    static $cache = [];
    $locale = dailybreath_scripture_locale($locale);
    if (isset($cache[$locale])) return $cache[$locale];
    $surahs = [];
    if ($locale !== 'en') {
        foreach (dailybreath_quran_arabic() as $chapter) $surahs[(int)$chapter['id']] = (string)$chapter['name'];
        return $cache[$locale] = $surahs;
    }
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
    return $cache[$locale] = $surahs;
}

/** @return array<int,array{verse_number:int,verse_text:string}> */
function dailybreath_sacred_chapter(string $tradition, string $book, int $chapter, string $locale = 'en'): array
{
    $tradition = dailybreath_faith_tradition($tradition);
    $locale = dailybreath_scripture_locale($locale);
    $verses = [];
    if ($tradition === 'quran') {
        $surah = max(1, min(114, (int)$book));
        if ($locale !== 'en') {
            foreach (dailybreath_quran_arabic() as $item) {
                if ((int)$item['id'] !== $surah) continue;
                foreach (($item['verses'] ?? []) as $verse) $verses[] = ['verse_number'=>(int)$verse['id'], 'verse_text'=>(string)$verse['text']];
                return $verses;
            }
            return [];
        }
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
    $started = false;
    foreach (dailybreath_vpl_verses(dailybreath_bible_path($tradition, $locale)) as $item) {
        if ($item['code'] === $codes[$book] && $item['chapter'] === $chapter) {
            $started = true;
            $verses[] = ['verse_number'=>$item['verse'], 'verse_text'=>$item['text']];
        } elseif ($started) break;
    }
    return $verses;
}

/** @return array<int,array{reference:string,text:string,url:string}> */
function dailybreath_search_sacred_text(string $tradition, string $query, int $limit = 75, string $locale = 'en'): array
{
    $tradition = dailybreath_faith_tradition($tradition);
    $locale = dailybreath_scripture_locale($locale);
    $terms = array_values(array_filter(preg_split('/\s+/u', dailybreath_lower(trim($query))) ?: []));
    if (!$terms) return [];
    $matches = [];
    if ($tradition === 'quran') {
        $surahs = dailybreath_quran_surahs($locale);
        if ($locale !== 'en') {
            foreach (dailybreath_quran_arabic() as $chapter) foreach (($chapter['verses'] ?? []) as $verse) {
                if (count($matches) >= $limit) break 2;
                $surah = (int)$chapter['id'];
                $number = (int)$verse['id'];
                $reference = ($surahs[$surah] ?? ('Surah ' . $surah)) . ' ' . $surah . ':' . $number;
                $haystack = dailybreath_lower($reference . ' ' . (string)$verse['text']);
                if (!dailybreath_text_contains_terms($haystack, $terms)) continue;
                $matches[] = ['reference'=>$reference, 'text'=>(string)$verse['text'], 'url'=>'?tradition=quran&book='.$surah.'&chapter=1#verse-'.$number];
            }
            return $matches;
        }
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
    foreach (dailybreath_vpl_verses(dailybreath_bible_path($tradition, $locale)) as $item) {
        if (count($matches) >= $limit) break;
        if (!isset($allowedCodes[$item['code']])) continue;
        $name = $namesByCode[$item['code']];
        $displayName = $tradition === 'torah' ? dailybreath_jewish_book_name($name) : $name;
        if ($tradition === 'bible') $displayName = dailybreath_bible_book_name($name, $locale);
        $reference = $displayName . ' ' . $item['chapter'] . ':' . $item['verse'];
        $haystack = dailybreath_lower($reference . ' ' . $item['text']);
        if (!dailybreath_text_contains_terms($haystack, $terms)) continue;
        $matches[] = ['reference'=>$reference, 'text'=>$item['text'], 'url'=>'?tradition='.$tradition.'&book='.rawurlencode($name).'&chapter='.$item['chapter'].'#verse-'.$item['verse']];
    }
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
            $verses = dailybreath_sacred_chapter('torah', $book, (int)$bible['chapter'], $locale);
            foreach ($verses as $verse) if ($verse['verse_number'] === (int)$bible['verse']) {
                return ['text'=>$verse['verse_text'],'reference'=>dailybreath_jewish_book_name($book).' '.(int)$bible['chapter'].':'.(int)$bible['verse'],'book'=>$book,'chapter'=>(int)$bible['chapter'],'verse'=>(int)$bible['verse'],'reader_book'=>$book,'reader_chapter'=>(int)$bible['chapter'],'source'=>'matched_torah_theme','tradition'=>'torah'];
            }
        }
        $pools = [
            'courage'=>[['Deuteronomy',31,6],['Joshua',1,9],['Psalms',27,14],['Isaiah',41,10]],
            'peace'=>[['Psalms',4,8],['Psalms',23,4],['Isaiah',26,3],['Proverbs',3,5]],
            'recovery'=>[['Psalms',40,1],['Psalms',107,14],['Isaiah',43,2],['Proverbs',24,16]],
        ];
        [$book,$chapter,$number] = $pools[$theme][abs(crc32($date)) % count($pools[$theme])];
        $verses = dailybreath_sacred_chapter('torah', $book, $chapter, $locale);
        if (!$verses) {
            return ['text'=>'Be still, and know that I am God.','reference'=>'Tehillim 46:10','book'=>'Psalms','chapter'=>46,'verse'=>10,'reader_book'=>'Psalms','reader_chapter'=>46,'source'=>'tanakh_emergency_fallback','tradition'=>'torah'];
        }
        $selected = array_values(array_filter($verses, static fn(array $verse): bool => $verse['verse_number'] === $number))[0] ?? $verses[0];
        return ['text'=>$selected['verse_text'],'reference'=>dailybreath_jewish_book_name($book).' '.$chapter.':'.$selected['verse_number'],'book'=>$book,'chapter'=>$chapter,'verse'=>$selected['verse_number'],'reader_book'=>$book,'reader_chapter'=>$chapter,'source'=>'torah_theme_match','tradition'=>'torah'];
    }

    $pools = [
        'courage'=>[[3,200],[2,286],[9,40],[94,5],[65,3]],
        'peace'=>[[13,28],[2,153],[39,23],[89,27],[48,4]],
        'recovery'=>[[39,53],[12,87],[3,139],[5,90],[94,6]],
    ];
    [$surah,$number] = $pools[$theme][abs(crc32($date)) % count($pools[$theme])];
    $verses = dailybreath_sacred_chapter('quran', (string)$surah, 1, $locale);
    if (!$verses) {
        return ['text'=>'Who have believed and whose hearts have rest in the remembrance of Allah. Verily in the remembrance of Allah do hearts find rest!','reference'=>"Ar-Ra'd 13:28",'book'=>'13','chapter'=>1,'verse'=>28,'reader_book'=>'13','reader_chapter'=>1,'source'=>'quran_emergency_fallback','tradition'=>'quran'];
    }
    $selected = array_values(array_filter($verses, static fn(array $verse): bool => $verse['verse_number'] === $number))[0] ?? $verses[0];
    $name = dailybreath_quran_surahs($locale)[$surah] ?? ('Surah '.$surah);
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
