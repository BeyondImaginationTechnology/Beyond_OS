<?php
declare(strict_types=1);

require_once __DIR__ . '/sacred-text.php';

function dailybreath_chat_tradition(string $guide): ?string
{
    return ['chris' => 'bible', 'dovi' => 'torah', 'moe' => 'quran'][$guide] ?? null;
}

function dailybreath_chat_guide_name(string $guide): string
{
    return ['chris' => 'Chris', 'dovi' => 'Dovi', 'moe' => 'Moe'][$guide] ?? 'Daily Breath guide';
}

/** @return array<string,string> */
function dailybreath_chat_copy(string $language): array
{
    $language = dailybreath_scripture_locale($language);
    $copy = [
        'en' => [
            'hello' => 'Hello! I’m %s, your Daily Breath %s guide. Ask me for a passage, a theme such as peace or courage, or help using the app.',
            'help' => 'I can quote passages from Daily Breath’s local sacred-text library, find readings by theme, answer book and surah order questions, and help with Daily Breath features. I stay inside Daily Breath and do not use GPU processing.',
            'scope' => 'I can help with Daily Breath and respectful sacred-text questions only. Try “John 3:16,” “Quran 2:255,” “find a passage about peace,” or “how do I add a note?”',
            'not_found' => 'I could not find that passage in Daily Breath’s local %s edition. Check the book or surah name and the chapter and verse numbers.',
            'search_none' => 'I could not find a local %s match for “%s.” Try one or two simpler theme words.',
        ],
        'fr' => [
            'hello' => 'Bonjour ! Je suis %s, votre guide %s de Daily Breath. Demandez-moi un passage, un thème comme la paix ou le courage, ou de l’aide pour utiliser l’application.',
            'help' => 'Je peux citer la bibliothèque locale de textes sacrés de Daily Breath, chercher des lectures par thème, répondre sur l’ordre des livres et des sourates, et expliquer les fonctions de Daily Breath. Je reste dans Daily Breath et je n’utilise aucun traitement GPU.',
            'scope' => 'Je peux seulement aider avec Daily Breath et des questions respectueuses sur les textes sacrés. Essayez « Jean 3:16 », « Coran 2:255 » ou « comment ajouter une note ? »',
            'not_found' => 'Je n’ai pas trouvé ce passage dans l’édition locale %s de Daily Breath. Vérifiez le nom du livre ou de la sourate et les numéros.',
            'search_none' => 'Je n’ai trouvé aucun résultat local dans %s pour « %s ». Essayez un ou deux mots plus simples.',
        ],
        'es' => [
            'hello' => '¡Hola! Soy %s, tu guía de %s en Daily Breath. Pídeme un pasaje, un tema como paz o valor, o ayuda para usar la aplicación.',
            'help' => 'Puedo citar la biblioteca local de textos sagrados de Daily Breath, buscar lecturas por tema, responder sobre el orden de libros y suras y explicar las funciones de Daily Breath. Permanezco en Daily Breath y no uso procesamiento GPU.',
            'scope' => 'Solo puedo ayudar con Daily Breath y preguntas respetuosas sobre textos sagrados. Prueba « Juan 3:16 », « Corán 2:255 » o « ¿cómo añado una nota? »',
            'not_found' => 'No encontré ese pasaje en la edición local de %s de Daily Breath. Revisa el nombre del libro o la sura y los números.',
            'search_none' => 'No encontré una coincidencia local en %s para « %s ». Prueba una o dos palabras más sencillas.',
        ],
    ];
    return $copy[$language];
}

function dailybreath_chat_scope_reply(string $guide, string $language): string
{
    return dailybreath_chat_copy($language)['scope'];
}

/** @return array<string,array<int,string>> */
function dailybreath_chat_book_aliases(string $language): array
{
    $aliases = [];
    foreach (array_keys(array_merge(...array_values(dailybreath_bible_book_groups()))) as $book) {
        $names = [$book, dailybreath_bible_book_name($book, $language), dailybreath_jewish_book_name($book)];
        if ($book === 'Psalms') $names[] = 'Psalm';
        if ($book === 'Song of Solomon') array_push($names, 'Song of Songs', 'Canticles');
        $aliases[$book] = array_values(array_unique(array_filter($names)));
    }
    return $aliases;
}

/** @return array{book:string,chapter:int,verse:?int,end:?int}|null */
function dailybreath_chat_bible_reference(string $prompt, string $language, bool $tanakhOnly): ?array
{
    $allowed = array_merge(...array_values(dailybreath_bible_book_groups($tanakhOnly)));
    $candidates = [];
    foreach (dailybreath_chat_book_aliases($language) as $book => $aliases) {
        if (!isset($allowed[$book])) continue;
        foreach ($aliases as $alias) $candidates[] = ['book' => $book, 'alias' => $alias];
    }
    usort($candidates, static fn(array $a, array $b): int => mb_strlen($b['alias']) <=> mb_strlen($a['alias']));
    foreach ($candidates as $candidate) {
        $aliasPattern = str_replace('\\ ', '\\s+', preg_quote($candidate['alias'], '/'));
        if (!preg_match('/(?<!\p{L})' . $aliasPattern . '\s+(\d{1,3})(?:\s*:\s*(\d{1,3})(?:\s*[-–]\s*(\d{1,3}))?)?(?!\d)/iu', $prompt, $match)) continue;
        return [
            'book' => $candidate['book'],
            'chapter' => (int)$match[1],
            'verse' => isset($match[2]) && $match[2] !== '' ? (int)$match[2] : null,
            'end' => isset($match[3]) && $match[3] !== '' ? (int)$match[3] : null,
        ];
    }
    return null;
}

/** @return array{surah:int,verse:?int,end:?int}|null */
function dailybreath_chat_quran_reference(string $prompt): ?array
{
    if (preg_match('/(?:\b(?:qur[’\']?an|koran|surah|sura)\s*)?(\d{1,3})\s*:\s*(\d{1,3})(?:\s*[-–]\s*(\d{1,3}))?/iu', $prompt, $match)) {
        return ['surah' => (int)$match[1], 'verse' => (int)$match[2], 'end' => isset($match[3]) ? (int)$match[3] : null];
    }
    $surahs = dailybreath_quran_surahs('en');
    $candidates = [];
    foreach ($surahs as $number => $name) $candidates[] = ['surah' => (int)$number, 'name' => $name];
    usort($candidates, static fn(array $a, array $b): int => mb_strlen($b['name']) <=> mb_strlen($a['name']));
    foreach ($candidates as $candidate) {
        $namePattern = str_replace('\\ ', '\\s+', preg_quote($candidate['name'], '/'));
        if (!preg_match('/(?<!\p{L})' . $namePattern . '(?:\s+(?:ayah|aya|verse))?\s+(\d{1,3})(?:\s*[-–]\s*(\d{1,3}))?/iu', $prompt, $match)) continue;
        return ['surah' => $candidate['surah'], 'verse' => (int)$match[1], 'end' => isset($match[2]) ? (int)$match[2] : null];
    }
    if (preg_match('/\b(?:surah|sura)\s+(\d{1,3})(?!\s*:)(?:\s|[?.!,]|$)/iu', $prompt, $match)) {
        return ['surah' => (int)$match[1], 'verse' => null, 'end' => null];
    }
    return null;
}

/** @param array<int,array{verse_number:int,verse_text:string}> $chapter */
function dailybreath_chat_format_passage(array $chapter, int $start, int $end, string $reference, string $url): ?string
{
    $selected = [];
    foreach ($chapter as $verse) {
        if ($verse['verse_number'] < $start || $verse['verse_number'] > $end) continue;
        $selected[] = $verse['verse_number'] . '. ' . trim($verse['verse_text']);
    }
    if ($selected === []) return null;
    return $reference . "\n" . implode("\n", $selected) . "\n\nOpen in Daily Breath Scripture: " . $url;
}

function dailybreath_chat_passage_reply(string $guide, string $prompt, string $language): ?string
{
    $tradition = dailybreath_chat_tradition($guide);
    if ($tradition === null) return null;
    $copy = dailybreath_chat_copy($language);
    if ($tradition === 'quran') {
        $reference = dailybreath_chat_quran_reference($prompt);
        if ($reference === null) return null;
        $surah = $reference['surah'];
        $surahs = dailybreath_quran_surahs($language);
        $name = $surahs[$surah] ?? ('Surah ' . $surah);
        $chapter = $surah >= 1 && $surah <= 114 ? dailybreath_sacred_chapter('quran', (string)$surah, 1, $language) : [];
        if ($chapter === []) return sprintf($copy['not_found'], dailybreath_tradition_label($tradition));
        if ($reference['verse'] === null) {
            return 'Surah ' . $surah . ', ' . $name . ', contains ' . count($chapter) . ' ayahs. Open it in Daily Breath Scripture: /dailybreath/scripture.php?tradition=quran&book=' . $surah . '&chapter=1';
        }
        $start = $reference['verse'];
        $end = min($reference['end'] ?? $start, $start + 4);
        $label = $name . ' ' . $surah . ':' . $start . ($end > $start ? '–' . $end : '');
        $url = '/dailybreath/scripture.php?tradition=quran&book=' . $surah . '&chapter=1#verse-' . $start;
        return dailybreath_chat_format_passage($chapter, $start, $end, $label, $url)
            ?? sprintf($copy['not_found'], dailybreath_tradition_label($tradition));
    }

    $reference = dailybreath_chat_bible_reference($prompt, $language, $tradition === 'torah');
    if ($reference === null) return null;
    $book = $reference['book'];
    $chapterNumber = $reference['chapter'];
    $chapter = dailybreath_sacred_chapter($tradition, $book, $chapterNumber, $language);
    if ($chapter === []) return sprintf($copy['not_found'], dailybreath_tradition_label($tradition));
    $bookName = $tradition === 'torah' ? dailybreath_jewish_book_name($book) : dailybreath_bible_book_name($book, $language);
    if ($reference['verse'] === null) {
        return $bookName . ' ' . $chapterNumber . ' contains ' . count($chapter) . ' verses. Open it in Daily Breath Scripture: /dailybreath/scripture.php?tradition=' . $tradition . '&book=' . rawurlencode($book) . '&chapter=' . $chapterNumber;
    }
    $start = $reference['verse'];
    $end = min($reference['end'] ?? $start, $start + 4);
    $label = $bookName . ' ' . $chapterNumber . ':' . $start . ($end > $start ? '–' . $end : '');
    $url = '/dailybreath/scripture.php?tradition=' . $tradition . '&book=' . rawurlencode($book) . '&chapter=' . $chapterNumber . '#verse-' . $start;
    return dailybreath_chat_format_passage($chapter, $start, $end, $label, $url)
        ?? sprintf($copy['not_found'], dailybreath_tradition_label($tradition));
}

function dailybreath_chat_search_reply(string $guide, string $prompt, string $language): ?string
{
    if (!preg_match('/\b(?:find|show|give|suggest|search|verse|verses|passage|passages|ayah|ayahs)\b.{0,45}\b(?:about|on|for)\s+(.+?)[?.!]*$/iu', $prompt, $match)) return null;
    $query = trim($match[1]);
    $query = preg_replace('/\b(?:me|a|some|the|please|scripture|verse|verses|passage|passages|ayah|ayahs)\b/iu', ' ', $query) ?? $query;
    $terms = array_values(array_filter(preg_split('/\s+/u', trim($query)) ?: [], static fn(string $term): bool => mb_strlen($term) > 2));
    if ($terms === []) return null;
    $tradition = dailybreath_chat_tradition($guide);
    if ($tradition === null) return null;
    $results = dailybreath_search_sacred_text($tradition, implode(' ', array_slice($terms, 0, 3)), 3, $language);
    if ($results === [] && count($terms) > 1) $results = dailybreath_search_sacred_text($tradition, $terms[0], 3, $language);
    if ($results === []) return sprintf(dailybreath_chat_copy($language)['search_none'], dailybreath_tradition_label($tradition), trim($match[1]));
    $lines = ['Here are grounded matches from Daily Breath’s local ' . dailybreath_tradition_label($tradition) . ' edition:'];
    foreach ($results as $result) $lines[] = $result['reference'] . ' — ' . $result['text'];
    $lines[] = 'Open Scripture to read each passage in context.';
    return implode("\n\n", $lines);
}

function dailybreath_chat_app_help(string $prompt): ?string
{
    $lower = dailybreath_lower($prompt);
    $asksForAction = preg_match('/\b(how|where|open|use|start|change|choose|switch|add|create|save)\b/u', $lower) === 1;
    if ($asksForAction && preg_match('/\b(language|langue|idioma|english|fran[cç]ais|espa[nñ]ol)\b/u', $lower)) return 'Use the language control at the top of Daily Breath, then choose English, Français, or Español.';
    if (preg_match('/\b(change|choose|switch|tradition|bible|tanakh|quran)\b.{0,35}\b(tradition|faith|text)\b|\b(tradition|faith)\b.{0,35}\b(change|choose|switch)\b/u', $lower)) return 'On Home, use the Bible · Verse, Tanakh · Passage, or Quran · Ayah switch. Your choice stays on this device.';
    if ($asksForAction && preg_match('/\b(note|notes|highlight|save|favorite|favourite)\b/u', $lower)) return 'Open Scripture, choose a chapter, then use Save, Highlight, or Note beneath a verse. Notes and highlights stay privately in this browser.';
    if ($asksForAction && preg_match('/\b(breath|breathe|breathing)\b/u', $lower)) return 'Choose Breathe in the Daily Breath navigation to open the guided breathing practice.';
    if ($asksForAction && preg_match('/\b(journal|reflect|reflection)\b/u', $lower)) return 'Choose Reflect in Today’s rhythm, or open Practices and select the journal section.';
    if ($asksForAction && preg_match('/\b(devotional|devotionals)\b/u', $lower)) return 'Choose Devotionals in the Daily Breath navigation to open the reading library.';
    if ($asksForAction && preg_match('/\b(academy|course|courses|lesson|lessons)\b/u', $lower)) return 'Choose Academy in the Daily Breath navigation to browse courses and lessons.';
    return null;
}

function dailybreath_chat_order_reply(string $guide, string $prompt, string $language): ?string
{
    $direction = null;
    $subject = '';
    if (preg_match('/\b(?:what|which)\s+(?:book\s+|surah\s+)?comes\s+(after|before)\s+(?:the\s+(?:book\s+|surah\s+)?(?:of\s+)?)?(.+?)[?.!]*$/iu', $prompt, $match)
        || preg_match('/\b(?:comes|is)\s+(after|before)\s+(.+?)[?.!]*$/iu', $prompt, $match)) {
        $direction = strtolower($match[1]);
        $subject = trim($match[2]);
    }
    if ($direction === null || $subject === '') return null;
    if ($guide === 'moe') {
        $surahs = dailybreath_quran_surahs('en');
        $subjectKey = dailybreath_lower(preg_replace('/^surah\s+/iu', '', $subject) ?? $subject);
        $current = ctype_digit($subjectKey) ? (int)$subjectKey : 0;
        if ($current === 0) foreach ($surahs as $number => $name) if (dailybreath_lower($name) === $subjectKey) { $current = (int)$number; break; }
        $next = $current + ($direction === 'after' ? 1 : -1);
        if ($current < 1 || !isset($surahs[$next])) return null;
        return 'Surah ' . $next . ', ' . $surahs[$next] . ', comes ' . $direction . ' Surah ' . $current . ', ' . $surahs[$current] . ', in the Quran.';
    }
    $books = array_keys(array_merge(...array_values(dailybreath_bible_book_groups($guide === 'dovi'))));
    $subjectKey = dailybreath_lower(preg_replace('/^(?:the\s+)?book\s+of\s+/iu', '', $subject) ?? $subject);
    $current = null;
    foreach ($books as $index => $book) {
        foreach (dailybreath_chat_book_aliases($language)[$book] as $name) if (dailybreath_lower($name) === $subjectKey) { $current = $index; break 2; }
    }
    if ($current === null) return null;
    $next = $current + ($direction === 'after' ? 1 : -1);
    if (!isset($books[$next])) return null;
    $display = static fn(string $book): string => $guide === 'dovi' ? dailybreath_jewish_book_name($book) : dailybreath_bible_book_name($book, $language);
    return $display($books[$next]) . ' comes ' . $direction . ' ' . $display($books[$current]) . ' in the ' . ($guide === 'dovi' ? 'Tanakh' : 'Bible') . ' library.';
}

function dailybreath_chat_quran_history_reply(string $guide, string $prompt, string $language): ?string
{
    if ($guide !== 'moe' || !preg_match('/\b(?:when|what year|how long|date|made|created|written|revealed|compiled|collected|standardized)\b.{0,50}\b(?:qur[\'’]?an|koran|revelation|scripture)\b|\b(?:qur[\'’]?an|koran)\b.{0,50}\b(?:when|what year|how long|made|created|written|revealed|compiled|collected|standardized)\b/iu', $prompt)) {
        return null;
    }

    return match (dailybreath_scripture_locale($language)) {
        'fr' => "Le Coran a ete revele au prophete Muhammad sur environ 23 ans, vers 610-632 de notre ere. Dans la tradition musulmane, il s'agit d'une revelation d'Allah. Historiquement, son texte a ete rassemble en codex peu apres la mort de Muhammad, puis des copies de reference ont ete standardisees sous le calife Uthman, vers 650. Ainsi, selon le sens de votre question, la revelation date de 610-632, la compilation vient apres 632 et la standardisation date d'environ 650.",
        'es' => "El Coran fue revelado al profeta Muhammad durante unos 23 anos, aproximadamente entre 610 y 632 d. C. En la tradicion musulmana, es una revelacion de Allah. Historicamente, su texto fue reunido en un codice poco despues de la muerte de Muhammad, y se estandarizaron copias de referencia durante el califato de Uthman, hacia el ano 650. Asi que, segun lo que quieras decir, la revelacion fue entre 610 y 632, la recopilacion ocurrio despues de 632 y la estandarizacion alrededor de 650.",
        default => "The Quran was revealed to the Prophet Muhammad over about 23 years, beginning around 610 CE and ending in 632 CE. In Islamic belief, it is revelation from Allah. Historically, its text was collected into a written codex soon after Muhammad's death, and reference copies were standardized during Caliph Uthman's caliphate, around 650 CE. So the answer depends on what you mean: revelation (610-632), compilation (after 632), or standardization (about 650).",
    };
}

function dailybreath_chat_learning_reply(string $guide, string $prompt, string $language): ?string
{
    $history = preg_match('/\b(?:when|what year|how long|date|made|created|written|compiled|collected|preserved)\b.{0,55}\b(?:bible|tanakh|torah|scripture|testament|gospel|nevi.?im|ketuvim)\b|\b(?:bible|tanakh|torah|scripture)\b.{0,55}\b(?:when|what year|how long|made|created|written|compiled|collected|preserved)\b/iu', $prompt) === 1;
    if ($history) {
        if ($guide === 'chris' && preg_match('/\b(?:bible|testament|gospel)\b/iu', $prompt)) return "The Bible was written and compiled over many centuries, roughly from the first millennium BCE through the first century CE. It is a collection of books written by different authors in different historical settings, not a single book written on one date. Christian traditions also differ somewhat in which books they include.";
        if ($guide === 'dovi' && preg_match('/\b(?:tanakh|torah|nevi.?im|ketuvim)\b/iu', $prompt)) return "The Tanakh was written and compiled over many centuries. Its books are traditionally grouped as Torah, Nevi'im (Prophets), and Ketuvim (Writings). The Torah is traditionally connected with Moses, while Jewish historical and scholarly discussions recognize a longer process of composition, editing, and preservation across Israelite and Jewish history.";
    }

    if ($guide === 'moe' && preg_match('/\b(?:what is|define|explain|meaning of)\b.{0,30}\b(?:tawhid|ramadan|salah|salat|prayer|mercy)\b|\b(?:tawhid|ramadan|salah|salat)\b/iu', $prompt)) {
        if (preg_match('/\btawhid\b/iu', $prompt)) return 'Tawhid is the Islamic concept of the oneness and uniqueness of Allah. It is central to Islamic belief and is expressed through worship directed to Allah alone.';
        if (preg_match('/\bramadan\b/iu', $prompt)) return 'Ramadan is the ninth month of the Islamic lunar calendar. Muslims commonly fast from dawn to sunset during it, while emphasizing prayer, generosity, Quran reading, self-discipline, and care for others. Practices and exemptions vary, so people should follow trusted local guidance.';
        if (preg_match('/\b(?:salah|salat|prayer)\b/iu', $prompt)) return 'Salah is the formal ritual prayer in Islam. Muslims pray at prescribed times, with bodily movements and Quran recitation, as an act of worship and remembrance of Allah.';
        return 'Mercy is a major theme in the Quran and Islamic teaching. Quran passages often connect Allah\'s mercy with repentance, forgiveness, compassion, justice, and hope.';
    }
    if ($guide === 'chris' && preg_match('/\b(?:what is|define|explain|meaning of)\b.{0,30}\b(?:gospel|psalm|epistle|salvation|testament)\b|\b(?:gospel|psalm|epistle|salvation|testament)\b/iu', $prompt)) {
        if (preg_match('/\bgospel\b/iu', $prompt)) return 'Gospel means good news. In the New Testament it can refer to the message about Jesus and also to the four books traditionally associated with Matthew, Mark, Luke, and John.';
        if (preg_match('/\bpsalm\b/iu', $prompt)) return 'A psalm is a sacred song or poem. The biblical Book of Psalms includes prayers and poetry expressing praise, grief, trust, confession, and hope.';
        if (preg_match('/\bepistle\b/iu', $prompt)) return 'An epistle is a letter. In the New Testament, many epistles address early Christian communities and discuss belief, ethics, worship, and community life.';
        if (preg_match('/\bsalvation\b/iu', $prompt)) return 'Salvation is a central Christian concept, but its meaning and emphasis vary among Christian traditions. It commonly concerns rescue from sin and restored relationship with God through faith and grace.';
        return 'The Bible has two major sections in most Christian traditions: the Old Testament and the New Testament. Their names, ordering, and included books can vary among Christian communities.';
    }
    if ($guide === 'dovi' && preg_match('/\b(?:what is|define|explain|meaning of)\b.{0,30}\b(?:tanakh|torah|nevi.?im|ketuvim|shabbat|teshuvah|parashah)\b|\b(?:tanakh|torah|nevi.?im|ketuvim|shabbat|teshuvah|parashah)\b/iu', $prompt)) {
        if (preg_match('/\btanakh\b/iu', $prompt)) return 'Tanakh is an acronym for Torah, Nevi\'im (Prophets), and Ketuvim (Writings), the three traditional divisions of Jewish Scripture.';
        if (preg_match('/\b(?:nevi.?im|ketuvim)\b/iu', $prompt)) return 'Nevi\'im means Prophets and Ketuvim means Writings. Together with Torah, they form the three divisions of the Tanakh.';
        if (preg_match('/\bshabbat\b/iu', $prompt)) return 'Shabbat is the Jewish Sabbath, a weekly period of holiness and rest from before sunset Friday until after nightfall Saturday. Observance differs among Jewish communities.';
        if (preg_match('/\bteshuvah\b/iu', $prompt)) return 'Teshuvah is often translated as repentance or return. It involves recognizing harm, turning away from wrongdoing, making repair where possible, and returning toward a better path.';
        if (preg_match('/\bparashah\b/iu', $prompt)) return 'A parashah is a section of the Torah selected for public reading, usually as part of the weekly synagogue reading cycle.';
        return 'The Torah is the first division of the Tanakh. It refers to the Five Books of Moses and is central to Jewish learning, practice, and communal reading.';
    }
    if (preg_match('/\b(?:what is|define|explain|meaning of)\b.{0,30}\b(?:prayer|mercy|compassion)\b|\b(?:prayer|mercy|compassion)\b/iu', $prompt)) {
        if ($guide === 'chris') return 'Prayer is communication with God and may include praise, thanksgiving, confession, lament, and requests. Christian traditions differ in their forms of prayer. Mercy means compassionate forgiveness and care toward people in need.';
        if ($guide === 'dovi') return 'Prayer is a way of turning toward God through words, song, study, gratitude, and petition. Jewish traditions use different forms and languages of prayer. Mercy and compassion are recurring ethical and spiritual themes in Jewish Scripture.';
    }

    if (preg_match('/\b(?:breathe|breathing|calm down|panic|overwhelmed|anxious|anxiety|stressed|stress)\b/iu', $prompt)) return 'Try this now: inhale gently for 4 counts, exhale slowly for 6 counts, and repeat five times. Keep your shoulders relaxed and stop if you feel uncomfortable. If you are in immediate danger or having a medical emergency, contact local emergency services.';
    if (preg_match('/\b(?:reflect|reflection|journal|today\'s reflection|give me a reflection)\b/iu', $prompt)) return 'Take one quiet minute: notice one word or idea from today\'s reading, name one feeling it brings up, and choose one small action that expresses wisdom, compassion, honesty, or gratitude.';
    if (preg_match('/\b(?:relapse|relapsed|craving|cravings|using again|used again|addiction|recovery)\b/iu', $prompt)) return 'You do not have to handle this moment alone. Pause, move away from immediate access to harm, contact a trusted person or recovery support, and take the next safe step. If you may hurt yourself or someone else, contact local emergency services or a crisis line now. Daily Breath is not a substitute for professional care.';
    if (preg_match('/\b(?:show|find|give|suggest)\b.{0,35}\b(?:passage|verse|ayah|reading)\b.{0,35}\b(?:peace|patience|hope|grief|forgiveness|mercy|courage|wisdom|anxiety)\b/iu', $prompt)) return null;
    return null;
}

function dailybreath_chat_local_reply(string $guide, string $prompt, string $language): ?string
{
    $prompt = trim($prompt);
    $tradition = dailybreath_chat_tradition($guide);
    if ($tradition === null || $prompt === '') return null;
    $simple = dailybreath_lower($prompt);
    $copy = dailybreath_chat_copy($language);
    $guideName = dailybreath_chat_guide_name($guide);
    $traditionLabel = dailybreath_tradition_label($tradition);
    if (preg_match('/^(hi|hello|hey|bonjour|salut|hola|buenas)[\s!.?¿¡]*$/u', $simple)) return sprintf($copy['hello'], $guideName, $traditionLabel);
    if (preg_match('/^(thanks|thank you|merci|gracias)[\s!.?¿¡]*$/u', $simple)) return 'You’re welcome. Ask me another Daily Breath or sacred-text question anytime.';
    if (preg_match('/^(ok|okay|alright|d[’\']accord|bien|vale|perfecto)[\s!.?¿¡]*$/u', $simple)) return 'I’m here when you’re ready for another Daily Breath or sacred-text question.';
    if (preg_match('/\b(what can you do|who are you|help|que peux-tu faire|qui es-tu|qué puedes hacer|quién eres)\b/u', $simple)) return $copy['help'];
    if (preg_match('/\b(how many|number of)\b.{0,20}\b(books|surahs|suras)\b/u', $simple)) {
        $count = $tradition === 'quran' ? 114 : count(array_merge(...array_values(dailybreath_bible_book_groups($tradition === 'torah'))));
        if ($tradition === 'torah') return 'Daily Breath lists 39 split-book entries for the Tanakh, corresponding to the traditional 24-book Jewish count.';
        return 'Daily Breath’s ' . $traditionLabel . ' library contains ' . $count . ($tradition === 'quran' ? ' surahs.' : ' books.');
    }
    return dailybreath_chat_app_help($prompt)
        ?? dailybreath_chat_order_reply($guide, $prompt, $language)
        ?? dailybreath_chat_quran_history_reply($guide, $prompt, $language)
        ?? dailybreath_chat_learning_reply($guide, $prompt, $language)
        ?? dailybreath_chat_passage_reply($guide, $prompt, $language)
        ?? dailybreath_chat_search_reply($guide, $prompt, $language);
}
