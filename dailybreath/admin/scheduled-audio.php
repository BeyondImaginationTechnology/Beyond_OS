<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/admin-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/web-app.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';

$pdo = db();
$dateToday = date('Y-m-d');
$error = '';
$notice = '';

function scheduled_audio_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function scheduled_audio_entries(PDO $pdo, string $today): array
{
    $path = dirname(__DIR__) . '/data/daily-verses.json';
    $document = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
    $entries = is_array($document['entries'] ?? null) ? $document['entries'] : [];
    $scheduled = [];
    foreach ($entries as $entry) {
        $date = (string)($entry['schedule_date'] ?? '');
        if ($date < $today || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
        $localReading = dailybreath_interfaith_verse_of_day($pdo, 'bible', 'en', $date);
        if (($localReading['source'] ?? '') !== 'scheduled_recovery_library'
            || trim((string)($localReading['text'] ?? '')) !== trim((string)($entry['text'] ?? ''))
            || trim((string)($localReading['reference'] ?? '')) !== trim((string)($entry['reference'] ?? ''))) {
            $scheduled[] = $entry + ['audio_state' => 'Skipped · another reading is active for this date'];
            continue;
        }
        $script = dailybreath_narration_script($localReading);
        $audio = dailybreath_audio_for_script($pdo, $date, 'bible', 'en', $script);
        $scheduled[] = $entry + [
            'audio_state' => $audio ? 'Ready to stream' : 'Not recorded',
            'audio_url' => $audio['audio_url'] ?? '',
        ];
    }
    usort($scheduled, static fn(array $left, array $right): int => strcmp((string)$left['schedule_date'], (string)$right['schedule_date']));
    return $scheduled;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beyond_require_csrf();
    $date = (string)($_POST['date'] ?? '');
    $entry = null;
    foreach (scheduled_audio_entries($pdo, $dateToday) as $candidate) {
        if (($candidate['schedule_date'] ?? '') === $date) { $entry = $candidate; break; }
    }
    if (!$entry) {
        $error = 'That date is not in the upcoming scheduled Bible verse library.';
    } elseif (str_starts_with((string)$entry['audio_state'], 'Skipped')) {
        $error = 'The scheduled verse is overridden for that date, so its audio was not generated.';
    } else {
        $reading = dailybreath_interfaith_verse_of_the_day($pdo, 'bible', 'en', $date);
        $script = dailybreath_narration_script($reading);
        $audio = dailybreath_audio_for_script($pdo, $date, 'bible', 'en', $script);
        if ($audio) {
            $notice = 'This verse already has matching narration.';
        } else {
            try {
                require_once __DIR__ . '/../../includes/narration/StudioNarration.php';
                $narrationConfig = studio_narration_config();
                $voice = studio_narration_voice('elevenlabs', 'en-US');
                if ($voice === '') {
                    $voice = studio_elevenlabs_first_voice((array)$narrationConfig['providers']['elevenlabs']);
                }
                if ($voice === '') throw new RuntimeException('No available ElevenLabs voice.');
                $generated = studio_narration_generate($script, 'en-US', 'elevenlabs', $voice);
                $stored = studio_store_mp3(
                    (string)$generated['audio_content'],
                    'daily-breath',
                    $date,
                    'en-US',
                    $script . "\n" . $voice
                );
                dailybreath_ensure_audio_table($pdo);
                $values = [$date, 'bible', 'en', hash('sha256', $script), (string)$stored['url'], $voice, 'elevenlabs'];
                if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                    $query = $pdo->prepare('INSERT OR REPLACE INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?)');
                } else {
                    $query = $pdo->prepare('INSERT INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE script_hash=VALUES(script_hash),audio_url=VALUES(audio_url),voice_id=VALUES(voice_id),provider=VALUES(provider),generated_at=CURRENT_TIMESTAMP');
                }
                $query->execute($values);
                $notice = 'Narration saved and ready for online playback.';
            } catch (Throwable $exception) {
                error_log('Daily Breath scheduled narration failed for ' . $date . ': ' . $exception->getMessage());
                $error = 'Narration generation failed. Check the ElevenLabs voice, credits, and audio storage.';
            }
        }
    }
}

$scheduled = scheduled_audio_entries($pdo, $dateToday);
$ready = count(array_filter($scheduled, static fn(array $entry): bool => ($entry['audio_state'] ?? '') === 'Ready to stream'));
$eligible = count(array_filter($scheduled, static fn(array $entry): bool => ($entry['audio_state'] ?? '') !== 'Skipped · another reading is active for this date'));
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Scheduled narration · Daily Breath</title>
<style>body{margin:0;background:#eef3ec;color:#1b2b21;font:15px/1.5 system-ui,sans-serif}.shell{width:min(1050px,calc(100% - 28px));margin:auto;padding:28px 0 60px}header{display:flex;justify-content:space-between;gap:16px;align-items:center}h1{font:500 clamp(28px,5vw,42px) Georgia,serif}a{color:#1d6540;font-weight:750}.panel{margin-top:18px;padding:20px;border:1px solid #d6e0d7;border-radius:18px;background:#fff}.status{padding:10px 12px;border-radius:10px;background:#e9f6ec}.error{background:#ffeded;color:#812525}.muted{color:#607166}.entry{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #e3e9e3}.entry:last-child{border:0}.entry p{margin:3px 0}.entry button{min-height:40px;padding:9px 13px;border:0;border-radius:9px;background:#1d6540;color:#fff;font:700 13px system-ui;cursor:pointer}.entry audio{max-width:220px}.metrics{font-weight:750}@media(max-width:560px){header{align-items:flex-start;flex-direction:column}.entry{grid-template-columns:1fr}.entry button,.entry audio{justify-self:start}}</style></head>
<body><main class="shell"><header><div><h1>Scheduled narration</h1><p class="muted">Generate online English audio for the dated Verse of the Day readings.</p></div><nav><a href="daily-content.php">Daily content admin</a> · <a href="../index.php">Daily Breath</a></nav></header>
<section class="panel"><p class="metrics"><?=count($scheduled)?> scheduled verses · <?=$ready?> ready · <?=max(0, $eligible - $ready)?> remaining</p><p class="muted">Each recording uses the passage and reference only. Audio is stored on the Daily Breath server for streaming; no audio is bundled offline. A date with a different published reading is skipped.</p><?php if($notice!==''):?><p class="status" role="status"><?=scheduled_audio_e($notice)?></p><?php endif?><?php if($error!==''):?><p class="status error" role="alert"><?=scheduled_audio_e($error)?></p><?php endif?>
<?php foreach($scheduled as $entry):?><article class="entry"><div><strong><?=scheduled_audio_e($entry['schedule_date']??'')?> · <?=scheduled_audio_e($entry['reference']??'')?></strong><p><?=scheduled_audio_e($entry['text']??'')?></p><span class="muted"><?=scheduled_audio_e($entry['audio_state']??'Not recorded')?></span></div><?php if(($entry['audio_state']??'')==='Ready to stream'):?><audio controls preload="none" src="<?=scheduled_audio_e($entry['audio_url'])?>" aria-label="Narration for <?=scheduled_audio_e($entry['reference']??'')?>"></audio><?php elseif(!str_starts_with((string)($entry['audio_state']??''),'Skipped')):?><form method="post"><input type="hidden" name="_csrf" value="<?=scheduled_audio_e(beyond_csrf_token())?>"><input type="hidden" name="date" value="<?=scheduled_audio_e($entry['schedule_date']??'')?>"><button type="submit">Generate narration</button></form><?php endif?></article><?php endforeach?></section></main></body></html>
