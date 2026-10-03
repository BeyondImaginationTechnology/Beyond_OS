<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';
require_once __DIR__ . '/../includes/web-app.php';
require_once __DIR__ . '/../../includes/narration/StudioNarration.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function dailybreath_narration_reply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    dailybreath_narration_reply(['ok'=>false,'error'=>'Use POST to prepare today’s narration.'], 405);
}

$request = json_decode((string)file_get_contents('php://input'), true);
$request = is_array($request) ? $request : [];
$tradition = strtolower(trim((string)($request['tradition'] ?? '')));
$tradition = $tradition === 'tanakh' ? 'torah' : $tradition;
$rawLocale = strtolower(trim((string)($request['locale'] ?? '')));
$locale = dailybreath_scripture_locale($rawLocale);
$expectedContentHash = strtolower(trim((string)($request['content_hash'] ?? '')));
$date = (string)($request['date'] ?? '');
$timezone = new DateTimeZone('America/Vancouver');
$todayDate = (new DateTimeImmutable('now', $timezone))->setTime(0, 0);
$requestedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);
$dateErrors = DateTimeImmutable::getLastErrors();
$validDate = $requestedDate !== false
    && $requestedDate->format('Y-m-d') === $date
    && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));
$supported = ['bible'=>['en','fr','es'], 'torah'=>['en','he'], 'quran'=>['en','ar']];

// Mobile devices derive their calendar date locally. Allow the adjacent day
// at the Vancouver date boundary while keeping requests tightly date-scoped.
if (!$validDate || $requestedDate < $todayDate->modify('-1 day') || $requestedDate > $todayDate->modify('+1 day')
    || $rawLocale !== $locale || !isset($supported[$tradition]) || !in_array($locale, $supported[$tradition], true)) {
    dailybreath_narration_reply(['ok'=>false,'error'=>'Narration is available for the current daily Bible, Tanakh, or Quran reading only.'], 422);
}

function dailybreath_narration_allow_generation(string $date): bool
{
    $directory = beyond_private_root() . '/tmp';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Private narration usage storage is unavailable.');
    }
    $path = $directory . '/dailybreath-narration-usage-' . $date . '.json';
    $handle = fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) fclose($handle);
        throw new RuntimeException('Private narration usage storage is unavailable.');
    }
    try {
        rewind($handle);
        $usage = json_decode((string)stream_get_contents($handle), true);
        $usage = is_array($usage) ? $usage : [];
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ipKey = hash('sha256', $ip);
        $ipCounts = is_array($usage['ips'] ?? null) ? $usage['ips'] : [];
        $globalCount = max(0, (int)($usage['total'] ?? 0));
        $ipCount = max(0, (int)($ipCounts[$ipKey] ?? 0));

        // Audio is shared and cached by reading. These caps protect provider
        // credits while still allowing one caller to prepare each locale.
        if ($globalCount >= 21 || $ipCount >= 10) return false;

        $ipCounts[$ipKey] = $ipCount + 1;
        $payload = json_encode(['total'=>$globalCount + 1, 'ips'=>$ipCounts], JSON_UNESCAPED_SLASHES);
        if ($payload === false) throw new RuntimeException('Could not record narration usage.');
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $payload) === false || !fflush($handle)) {
            throw new RuntimeException('Could not record narration usage.');
        }
        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

try {
    $pdo = beyond_db();
    $content = dailybreath_published_content($pdo, $date, $tradition, $locale);
    $reading = $content ?? dailybreath_interfaith_verse_of_day($pdo, $tradition, $locale, $date);
    if (trim((string)($reading['passage'] ?? $reading['text'] ?? '')) === ''
        || trim((string)($reading['reference'] ?? '')) === '') {
        dailybreath_narration_reply(['ok'=>false,'error'=>'Today’s reading is not available for narration.'], 404);
    }
    $script = trim((string)($reading['passage'] ?? $reading['text'] ?? ''))
        . "\n\n" . trim((string)($reading['reference'] ?? ''));
    if (preg_match_all('/./us', $script, $characters) === false || count($characters[0]) > 2000) {
        dailybreath_narration_reply(['ok'=>false,'error'=>'This reading is too long to narrate.'], 422);
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $expectedContentHash)
        || !hash_equals(hash('sha256', $script), $expectedContentHash)) {
        dailybreath_narration_reply(['ok'=>false,'error'=>'Today’s reading changed. Refresh the reading and try again.'], 409);
    }
    $cached = dailybreath_audio_for_script($pdo, $date, $tradition, $locale, $script);
    $origin = 'https://beyondimagination.co.technology';
    if ($cached) {
        dailybreath_narration_reply([
            'ok'=>true,
            'audio_url'=>$origin . (string)$cached['audio_url'],
            'cached'=>true,
            'date'=>$date,
            'tradition'=>$tradition,
            'locale'=>$locale,
        ]);
    }

    // Serialize generation per reading so simultaneous app taps do not spend
    // ElevenLabs credits generating the same MP3 more than once.
    $lockDirectory = beyond_private_root() . '/tmp';
    if (!is_dir($lockDirectory) && !mkdir($lockDirectory, 0750, true) && !is_dir($lockDirectory)) {
        throw new RuntimeException('Private narration lock storage is unavailable.');
    }
    $lockPath = $lockDirectory . '/dailybreath-narration-' . hash('sha256', $date . '|' . $tradition . '|' . $locale) . '.lock';
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Narration generation is busy.');
    }

    try {
        $cached = dailybreath_audio_for_script($pdo, $date, $tradition, $locale, $script);
        if ($cached) {
            dailybreath_narration_reply([
                'ok'=>true,
                'audio_url'=>$origin . (string)$cached['audio_url'],
                'cached'=>true,
                'date'=>$date,
                'tradition'=>$tradition,
                'locale'=>$locale,
            ]);
        }

        $voiceLocale = ['en'=>'en-US','fr'=>'fr-FR','es'=>'es-ES','he'=>'he-IL','ar'=>'ar-SA'][$locale];
        $config = studio_narration_config();
        $eleven = (array)($config['providers']['elevenlabs'] ?? []);
        // Respect the voice selected in Premium Voices. If none is saved,
        // the shared resolver discovers a voice verified for this language.
        $voice = studio_narration_voice('elevenlabs', $voiceLocale);
        if (trim((string)($eleven['api_key'] ?? '')) === '' || $voice === '') {
            dailybreath_narration_reply(['ok'=>false,'error'=>'A server-side ElevenLabs key and voice are required for this language.'], 503);
        }
        if (!dailybreath_narration_allow_generation($todayDate->format('Y-m-d'))) {
            dailybreath_narration_reply(['ok'=>false,'error'=>'Daily narration preparation has reached its limit. Please try again tomorrow.'], 429);
        }

        $generated = studio_narration_generate($script, $voiceLocale, 'elevenlabs', $voice);
        $stored = studio_store_mp3((string)$generated['audio_content'], 'daily-breath', $date, $voiceLocale, $script . "\n" . $voice);
        dailybreath_ensure_audio_table($pdo);
        $values = [$date, $tradition, $locale, hash('sha256', $script), (string)$stored['url'], $voice, 'elevenlabs'];
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $query = $pdo->prepare('INSERT OR REPLACE INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?)');
        } else {
            $query = $pdo->prepare('INSERT INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE script_hash=VALUES(script_hash),audio_url=VALUES(audio_url),voice_id=VALUES(voice_id),provider=VALUES(provider),generated_at=CURRENT_TIMESTAMP');
        }
        $query->execute($values);
        dailybreath_narration_reply([
            'ok'=>true,
            'audio_url'=>$origin . (string)$stored['url'],
            'cached'=>false,
            'date'=>$date,
            'tradition'=>$tradition,
            'locale'=>$locale,
        ]);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
} catch (Throwable $exception) {
    error_log('Daily Breath on-demand narration failed: ' . $exception->getMessage());
    dailybreath_narration_reply(['ok'=>false,'error'=>'Narration is temporarily unavailable. Check the connection and try again.'], 503);
}
