<?php
declare(strict_types=1);

/**
 * Renders published English Bible content as a Daily Breath TV episode.
 * This worker never uses drafts: it uses an approved daily record first, then
 * the existing published devotional plus the local Verse of the Day.
 */
function dailybreath_render_tv_devotional(): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Daily Breath TV devotional generation must run from the CLI cron.');
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
    $content = dailybreath_published_content($pdo, $date, 'bible', 'en');
    if (!$content) {
        $verse = dailybreath_interfaith_verse_of_day($pdo, 'bible', 'en', $date);
        $devotional = null;
        try {
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
        throw new RuntimeException('Daily Breath TV devotional content is missing its passage or reference.');
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
    $outputName = $date . '-bible-devotional.mp4';
    $outputFile = $outputDirectory . '/' . $outputName;
    $outputUrl = '/dailybreath/assets/videos/daily-breath-tv/' . $outputName;
    $configPath = $project . '/public/daily-breath-story.json';
    $config = json_decode((string)@file_get_contents($configPath), true);
    if (!is_array($config)) {
        throw new RuntimeException('The Daily Breath TV story template is invalid.');
    }

    $reflection = trim((string)($content['reflection'] ?? ''));
    if ($reflection === '') {
        $reflection = 'Carry this passage with you today. Notice one word or phrase that gives you steadiness for the next faithful step.';
    }
    $config = [
        'brand' => 'Daily Breath',
        'series' => 'Daily Bible Devotional',
        'title' => 'Daily Breath',
        'subtitle' => $reference . ' · ' . $label,
        'beats' => [
            ['id'=>'intro', 'label'=>'Begin', 'startSeconds'=>0, 'durationSeconds'=>9, 'narration'=>'Welcome to Daily Breath. Take one slow breath and make room for today’s reading.', 'onScreenText'=>$reference, 'visualPrompt'=>'Daily Breath devotional opening.'],
            ['id'=>'reading', 'label'=>'Verse of the Day', 'startSeconds'=>9, 'durationSeconds'=>22, 'narration'=>$passage, 'onScreenText'=>$reference, 'visualPrompt'=>'Scripture reading.'],
            ['id'=>'reflection', 'label'=>'Reflect', 'startSeconds'=>31, 'durationSeconds'=>24, 'narration'=>$reflection, 'onScreenText'=>'Carry this with you today.', 'visualPrompt'=>'Quiet reflection.'],
            ['id'=>'practice', 'label'=>'Practice', 'startSeconds'=>55, 'durationSeconds'=>16, 'narration'=>'Pause for one breath. What is one honest and faithful next step you can take today?', 'onScreenText'=>'Pause. Breathe. Take one next step.', 'visualPrompt'=>'Gentle breathing pause.'],
            ['id'=>'resolution', 'label'=>'Carry this with you', 'startSeconds'=>71, 'durationSeconds'=>10, 'narration'=>'May this reading bring steadiness to your day. This has been Daily Breath.', 'onScreenText'=>'One faithful step at a time.', 'visualPrompt'=>'Daily Breath closing.'],
        ],
        'sourceSeconds' => 6,
        'outroSeconds' => 7,
        'sources' => [[
            'citation' => $reference . ' · World English Bible (WEB)',
            'url' => 'https://ebible.org/details.php?id=engwebp',
            'notes' => 'Public-domain WEB scripture text. Reflection and practice are approved Daily Breath editorial content.',
        ]],
        'outroText' => 'Carry this reading with you.',
        'fps' => 30,
        'width' => 1920,
        'height' => 1080,
        'palette' => $config['palette'] ?? ['background'=>'#10271F','foreground'=>'#F5F1E8','accent'=>'#B9D6A1','muted'=>'#B9C5BB'],
    ];

    $narration = dailybreath_tv_narration_audio(
        $pdo,
        $date,
        'bible',
        'en',
        ['passage' => $passage, 'reference' => $reference],
        $project,
        $reflection . "\n\nPause for one breath. What is one honest and faithful next step you can take today?\n\nMay this reading bring steadiness to your day. This has been Daily Breath."
    );
    $config['audioSegments'] = [['audioFile' => $narration['audioFile'], 'startSeconds' => 0]];
    $config['artworkFile'] = dailybreath_tv_stage_artwork($root, $project, 'bible');

    $lockPath = beyond_private_file('locks/dailybreath-tv-devotional.lock', 'dailybreath-tv-devotional.lock');
    if (!is_dir(dirname($lockPath))) mkdir(dirname($lockPath), 0750, true);
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) fclose($lock);
        return 'Daily Breath TV devotional skipped: another render is running.';
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
                if (microtime(true) - $started > 300) { proc_terminate($process); throw new RuntimeException('The Daily Breath TV devotional render exceeded five minutes.'); }
                usleep(250000);
            } while (true);
            proc_close($process); $process = null;
            if (($exitCode ?? 1) !== 0 || !is_file($temporaryOutput) || filesize($temporaryOutput) < 1024) {
                throw new RuntimeException('The Daily Breath TV devotional MP4 could not be rendered.');
            }
            chmod($temporaryOutput, 0644);
            rename($temporaryOutput, $outputFile);
        }

        $manifestPath = $outputDirectory . '/rotation.json';
        $manifest = json_decode((string)@file_get_contents($manifestPath), true);
        $episodes = is_array($manifest['episodes'] ?? null) ? $manifest['episodes'] : [];
        $episodes = array_values(array_filter($episodes, static fn($episode): bool => is_array($episode) && ($episode['id'] ?? '') !== 'daily-bible-' . $date));
        array_unshift($episodes, [
            'id' => 'daily-bible-' . $date,
            'title' => 'Daily Breath · ' . $reference,
            'subtitle' => 'Daily Bible devotional · ' . $label,
            'show' => 'Daily Breath',
            'tradition' => 'bible',
            'programming' => 'christian',
            'airings' => [['start' => '06:15', 'end' => '07:00'], ['start' => '07:00', 'end' => '08:00']],
            'timezone' => 'America/Vancouver',
            'video_url' => $outputUrl,
            'duration_seconds' => 94,
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
        return 'Daily Breath TV devotional published: ' . $outputUrl;
    } finally {
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        foreach ([$propsFile, $logFile, $temporaryOutput] as $file) if (is_file($file)) unlink($file);
        flock($lock, LOCK_UN); fclose($lock);
    }
}
