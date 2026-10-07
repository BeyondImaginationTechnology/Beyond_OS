<?php
declare(strict_types=1);

/**
 * Creates or reuses the approved English Daily Breath narration, then exposes
 * a private Remotion-project copy for the MP4 render. The API key remains in
 * config/live.php; no credential is placed in a render props file or manifest.
 */
function dailybreath_tv_narration_audio(PDO $pdo, string $date, string $tradition, string $locale, array $content, string $project, string $additionalNarration = ''): array
{
    require_once dirname(__DIR__, 2) . '/includes/narration/StudioNarration.php';

    $script = dailybreath_narration_script($content);
    if ($script === "\n\n") {
        throw new RuntimeException('Daily Breath TV narration requires a passage and reference.');
    }

    $additionalNarration = trim($additionalNarration);
    $isDailyReading = $additionalNarration === '';
    $renderScript = $isDailyReading ? $script : trim($script . "\n\n" . $additionalNarration);
    $audio = $isDailyReading ? dailybreath_audio_for_script($pdo, $date, $tradition, $locale, $script) : null;
    if (!$audio) {
        $voiceLocale = ['en' => 'en-US', 'he' => 'he-IL', 'ar' => 'ar-SA'][$locale] ?? '';
        $voice = $voiceLocale === '' ? '' : ($tradition === 'bible'
            ? studio_character_voice('chris', $voiceLocale, 'elevenlabs')
            : studio_narration_voice('elevenlabs', $voiceLocale));
        if ($voice === '') {
            throw new RuntimeException('No ElevenLabs voice is configured for the ' . $locale . ' Daily Breath stream.');
        }
        $generated = studio_narration_generate($renderScript, $voiceLocale, 'elevenlabs', $voice);
        $stored = studio_store_mp3((string)$generated['audio_content'], 'daily-breath', $date, $voiceLocale, $renderScript . "\n" . $voice);
        if ($isDailyReading) {
            dailybreath_ensure_audio_table($pdo);
            $values = [$date, $tradition, $locale, hash('sha256', $script), (string)$stored['url'], $voice, 'elevenlabs'];
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $query = $pdo->prepare('INSERT OR REPLACE INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?)');
            } else {
                $query = $pdo->prepare('INSERT INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE script_hash=VALUES(script_hash),audio_url=VALUES(audio_url),voice_id=VALUES(voice_id),provider=VALUES(provider),generated_at=CURRENT_TIMESTAMP');
            }
            $query->execute($values);
        }
        $audio = ['audio_url' => (string)$stored['url'], 'voice_id' => $voice, 'provider' => 'elevenlabs'];
    }

    $root = dirname(__DIR__, 2);
    $source = $root . (string)($audio['audio_url'] ?? '');
    if (!is_file($source) || filesize($source) < 128) {
        throw new RuntimeException('The Daily Breath narration MP3 is unavailable for video rendering.');
    }
    $filename = basename($source);
    $relative = 'generated/dailybreath/' . $filename;
    $target = $project . '/public/' . $relative;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
        throw new RuntimeException('The Remotion narration staging directory could not be created.');
    }
    if (!is_file($target) || filesize($target) !== filesize($source) || filemtime($target) < filemtime($source)) {
        if (!copy($source, $target)) throw new RuntimeException('The Daily Breath narration could not be staged for rendering.');
        @chmod($target, 0644);
    }
    return ['audioFile' => $relative, 'voiceId' => (string)($audio['voice_id'] ?? ''), 'provider' => 'elevenlabs'];
}

function dailybreath_tv_stage_artwork(string $root, string $project, string $tradition): string
{
    $image = ['bible' => 'bible', 'torah' => 'tanakh', 'quran' => 'quran'][$tradition] ?? 'bible';
    $source = $root . '/dailybreath/assets/images/' . $image . '-forest-portrait.png';
    if (!is_file($source)) throw new RuntimeException('The ' . $image . ' Daily Breath artwork is unavailable.');
    $relative = 'generated/dailybreath/' . $image . '-forest-portrait.png';
    $target = $project . '/public/' . $relative;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
        throw new RuntimeException('The Remotion artwork staging directory could not be created.');
    }
    if (!is_file($target) || filesize($target) !== filesize($source) || filemtime($target) < filemtime($source)) {
        if (!copy($source, $target)) throw new RuntimeException('The Daily Breath artwork could not be staged for rendering.');
        @chmod($target, 0644);
    }
    return $relative;
}
