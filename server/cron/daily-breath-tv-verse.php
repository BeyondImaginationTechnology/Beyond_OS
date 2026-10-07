<?php
declare(strict_types=1);

/**
 * Renders an approved daily Bible, Tanakh, or Quran reading as a Daily Breath
 * TV episode. Artwork, spoken intro, source credit, and language all follow
 * the selected tradition rather than only changing the background.
 */
function dailybreath_render_tv_verse(string $tradition = 'bible', string $locale = 'en'): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Daily Breath TV Verse of the Day generation must run from the CLI cron.');
    }

    $root = dirname(__DIR__, 2);
    require_once $root . '/dailybreath/includes/web-app.php';
    require_once $root . '/dailybreath/includes/verse-of-day.php';
    require_once __DIR__ . '/daily-breath-tv-narration.php';

    $timezone = new DateTimeZone('America/Vancouver');
    $now = new DateTimeImmutable('now', $timezone);
    $date = $now->format('Y-m-d');
    $label = $now->format('l, F j, Y');
    $pdo = beyond_db();
    $profiles = [
        'bible' => ['locale'=>'en', 'direction'=>'ltr', 'kind'=>'Verse', 'series'=>'Bible Verse of the Day', 'intro'=>'Here is your Bible Verse of the Day. Take a slow breath and receive these words.', 'reflection'=>'Let one word or phrase stay with you as you move through today.', 'closing'=>'Return whenever you need a breath.', 'source'=>'World English Bible (WEB)', 'source_url'=>'https://ebible.org/details.php?id=engwebp', 'notes'=>'Public-domain WEB scripture text.', 'palette'=>['background'=>'#24452F','foreground'=>'#FFFDF7','accent'=>'#E2BC63','muted'=>'#DDE4D7']],
        'torah' => ['locale'=>'he', 'direction'=>'rtl', 'kind'=>'Tanakh Passage', 'series'=>'Tanakh Passage of the Day', 'intro'=>'Here is today’s Tanakh passage. Take a slow breath and receive these words.', 'reflection'=>'Carry this passage into one thoughtful and honest choice today.', 'closing'=>'Return to this passage whenever you need steadiness.', 'source'=>'Tanakh daily reading', 'source_url'=>'https://www.sefaria.org/texts/Tanakh', 'notes'=>'Tanakh text and a respectful Daily Breath reflection.', 'palette'=>['background'=>'#0A1832','foreground'=>'#FFF9E9','accent'=>'#D9B45A','muted'=>'#D9D7CB']],
        'quran' => ['locale'=>'ar', 'direction'=>'rtl', 'kind'=>'Quran Ayah', 'series'=>'Quran Ayah of the Day', 'intro'=>'Here is today’s Quran ayah. Take a slow breath and receive these words.', 'reflection'=>'Carry this ayah into one thoughtful and honest choice today.', 'closing'=>'Return to this ayah whenever you need steadiness.', 'source'=>'Quran daily reading', 'source_url'=>'https://quran.com/', 'notes'=>'Quran text and a respectful Daily Breath reflection.', 'palette'=>['background'=>'#0C372E','foreground'=>'#FFF9E9','accent'=>'#DAB35A','muted'=>'#D8E2D7']],
    ];
    $tradition = $tradition === 'tanakh' ? 'torah' : $tradition;
    if (!isset($profiles[$tradition])) throw new InvalidArgumentException('Unsupported Daily Breath tradition.');
    $profile = $profiles[$tradition];
    $locale = $locale !== '' ? $locale : $profile['locale'];
    $content = dailybreath_published_content($pdo, $date, $tradition, $locale);
    if (!$content) {
        $verse = dailybreath_interfaith_verse_of_day($pdo, $tradition, $locale, $date);
        $devotional = null;
        if ($tradition === 'bible') try {
            $query = $pdo->prepare('SELECT title,excerpt,body,scripture_reference FROM devotionals WHERE is_published=1 AND locale=? AND publish_date=? ORDER BY id DESC LIMIT 1');
            $query->execute(['en', $date]);
            $devotional = $query->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $error) {
            $devotional = null;
        }
        $content = [
            'passage' => (string)($verse['text'] ?? ''),
            'reference' => (string)($devotional['scripture_reference'] ?? $verse['reference'] ?? ''),
            'reflection' => (string)($devotional['body'] ?? $devotional['excerpt'] ?? ''),
        ];
    }

    $passage = trim((string)($content['passage'] ?? ''));
    $reference = trim((string)($content['reference'] ?? ''));
    if ($passage === '' || $reference === '') {
        throw new RuntimeException('Daily Breath TV Verse of the Day content is missing its passage or reference.');
    }

    $project = $root . '/tools/daily-stencil-video';
    $renderer = $project . '/node_modules/.bin/remotion';
    $entry = $project . '/src/index.ts';
    if (!is_file($renderer) || !is_file($entry) || !function_exists('proc_open')) {
        throw new RuntimeException('The Daily Breath TV Remotion renderer is unavailable.');
    }

    $outputDirectory = $root . '/dailybreath/assets/videos/daily-breath-tv';
    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
        throw new RuntimeException('The Daily Breath TV output directory could not be created.');
    }
    $outputName = $date . '-' . $tradition . '-verse-of-the-day.mp4';
    $outputFile = $outputDirectory . '/' . $outputName;
    $outputUrl = '/dailybreath/assets/videos/daily-breath-tv/' . $outputName;
    $configPath = $project . '/public/daily-breath-story.json';
    $config = json_decode((string)@file_get_contents($configPath), true);
    if (!is_array($config)) {
        throw new RuntimeException('The Daily Breath TV story template is invalid.');
    }

    $config = [
        'brand' => 'Daily Breath',
        'series' => $profile['series'],
        'title' => $profile['kind'] . ' of the Day',
        'subtitle' => $reference . ' · ' . $label,
        'beats' => [
            ['id'=>'intro', 'label'=>'Daily Breath', 'startSeconds'=>0, 'durationSeconds'=>7, 'narration'=>$profile['intro'], 'onScreenText'=>$reference, 'visualPrompt'=>'Daily sacred-text opening.'],
            ['id'=>'reading', 'label'=>$profile['kind'] . ' of the Day', 'startSeconds'=>7, 'durationSeconds'=>20, 'narration'=>$passage, 'onScreenText'=>$passage, 'visualPrompt'=>'Sacred-text reading.'],
            ['id'=>'reflection', 'label'=>'Carry this with you', 'startSeconds'=>27, 'durationSeconds'=>12, 'narration'=>$profile['reflection'], 'onScreenText'=>'One reading. One breath. One faithful step.', 'visualPrompt'=>'Quiet reflection.'],
            ['id'=>'resolution', 'label'=>'Daily Breath', 'startSeconds'=>39, 'durationSeconds'=>7, 'narration'=>'This has been your Daily Breath.', 'onScreenText'=>$profile['closing'], 'visualPrompt'=>'Daily Breath closing.'],
        ],
        'sourceSeconds' => 5,
        'outroSeconds' => 6,
        'sources' => [[
            'citation' => $reference . ' · ' . $profile['source'],
            'url' => $profile['source_url'],
            'notes' => $profile['notes'],
        ]],
        'outroText' => 'Carry this reading with you.',
        'fps' => 30,
        'width' => 1920,
        'height' => 1080,
        'direction' => $profile['direction'],
        'palette' => $profile['palette'],
    ];

    $narration = dailybreath_tv_narration_audio($pdo, $date, $tradition, $locale, ['passage' => $passage, 'reference' => $reference], $project);
    $config['audioSegments'] = [['audioFile' => $narration['audioFile'], 'startSeconds' => 0]];
    $config['artworkFile'] = dailybreath_tv_stage_artwork($root, $project, $tradition);

    $lockPath = beyond_private_file('locks/dailybreath-tv-' . $tradition . '-verse.lock', 'dailybreath-tv-' . $tradition . '-verse.lock');
    if (!is_dir(dirname($lockPath))) mkdir(dirname($lockPath), 0750, true);
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) fclose($lock);
        return 'Daily Breath TV Verse of the Day skipped: another render is running.';
    }

    $token = bin2hex(random_bytes(12));
    $propsFile = sys_get_temp_dir() . '/dailybreath-tv-' . $token . '.json';
    $logFile = sys_get_temp_dir() . '/dailybreath-tv-' . $token . '.log';
    $temporaryOutput = $outputDirectory . '/.' . $date . '-' . $token . '.mp4';
    $process = null;
    try {
        $contentUpdatedAt = strtotime((string)($content['updated_at'] ?? '')) ?: 0;
        if (!is_file($outputFile) || filesize($outputFile) < 1024 || filemtime($outputFile) < $contentUpdatedAt) {
            $encoded = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if (!is_string($encoded) || file_put_contents($propsFile, $encoded, LOCK_EX) === false) {
                throw new RuntimeException('The Daily Breath TV render props could not be written.');
            }
            $command = [$renderer, 'render', 'src/index.ts', 'DailyBreathStory', $temporaryOutput, '--props=' . $propsFile, '--codec=h264', '--concurrency=2', '--log=error'];
            $process = proc_open($command, [0=>['pipe','r'], 1=>['file',$logFile,'a'], 2=>['file',$logFile,'a']], $pipes, $project);
            if (!is_resource($process)) throw new RuntimeException('The Daily Breath TV render process could not start.');
            fclose($pipes[0]);
            $started = microtime(true);
            do {
                $status = proc_get_status($process);
                if (!$status['running']) { $exitCode = (int)$status['exitcode']; break; }
                if (microtime(true) - $started > 300) { proc_terminate($process); throw new RuntimeException('The Daily Breath TV Verse of the Day render exceeded five minutes.'); }
                usleep(250000);
            } while (true);
            proc_close($process); $process = null;
            if (($exitCode ?? 1) !== 0 || !is_file($temporaryOutput) || filesize($temporaryOutput) < 1024) {
                throw new RuntimeException('The Daily Breath TV Verse of the Day MP4 could not be rendered.');
            }
            chmod($temporaryOutput, 0644);
            rename($temporaryOutput, $outputFile);
        }

        $manifestPath = $outputDirectory . '/rotation.json';
        $manifest = json_decode((string)@file_get_contents($manifestPath), true);
        $episodes = is_array($manifest['episodes'] ?? null) ? $manifest['episodes'] : [];
        $episodeId = 'daily-' . $tradition . '-verse-' . $date;
        $episodes = array_values(array_filter($episodes, static fn($episode): bool => is_array($episode) && ($episode['id'] ?? '') !== $episodeId));
        $programming = ['bible' => 'christian', 'torah' => 'judaism', 'quran' => 'muslim'][$tradition];
        $airings = match ($tradition) {
            'bible' => [['start' => '06:00', 'end' => '06:15'], ['start' => '07:00', 'end' => '08:00']],
            'torah' => [['start' => '06:00', 'end' => '06:15'], ['start' => '08:00', 'end' => '09:00']],
            'quran' => [['start' => '06:00', 'end' => '06:15'], ['start' => '12:00', 'end' => '13:00']],
        };
        array_unshift($episodes, [
            'id' => $episodeId,
            'title' => $profile['kind'] . ' of the Day · ' . $reference,
            'subtitle' => $profile['series'] . ' · ' . $label,
            'show' => 'Daily Breath',
            'tradition' => $tradition,
            'programming' => $programming,
            'airings' => $airings,
            'timezone' => 'America/Vancouver',
            'video_url' => $outputUrl,
            'duration_seconds' => 57,
            'published_at' => $now->format(DATE_ATOM),
            'voiceover' => 'ElevenLabs narration',
        ]);
        $episodes = array_slice($episodes, 0, 30);
        $newManifest = ['channel'=>'Daily Breath TV', 'updated_at'=>$now->format(DATE_ATOM), 'episodes'=>$episodes];
        $temporaryManifest = $outputDirectory . '/.rotation-' . $token . '.json';
        if (file_put_contents($temporaryManifest, json_encode($newManifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The Daily Breath TV rotation manifest could not be written.');
        }
        rename($temporaryManifest, $manifestPath);
        return 'Daily Breath TV Verse of the Day published: ' . $outputUrl;
    } finally {
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        foreach ([$propsFile, $logFile, $temporaryOutput] as $file) if (is_file($file)) unlink($file);
        flock($lock, LOCK_UN); fclose($lock);
    }
}

