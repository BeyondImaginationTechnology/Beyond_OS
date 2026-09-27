<?php
declare(strict_types=1);

// One-time migration of the Mateo recordings made before narration used private DB metadata.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }

require_once dirname(__DIR__) . '/beyond-french/includes/narration/NarrationApi.php';

const MATEO_VOICE_ID = '00uevfcKk0GtPlbU69ZH';
const MATEO_INSTRUCTIONS = 'Warm, clear, natural premium narration. Preserve scripture references and French pronunciation accurately.';

$root = dirname(__DIR__);
$source = $root . '/beyond-french/storage/narration/legacy/es-ES';
$baseUrl = '/beyond-french/storage/narration/legacy/es-ES/';
$lessons = array_values(array_filter(all_lessons(), static fn(array $lesson): bool =>
    (int)($lesson['id'] ?? 0) >= 66 && (int)($lesson['id'] ?? 0) <= 165
));
if (count($lessons) !== 100) throw new RuntimeException('Expected exactly 100 Pablo lessons.');

$records = [];
foreach ($lessons as $lesson) {
    $id = (int)$lesson['id'];
    $generation = (array)($lesson['audio_generation'] ?? []);
    $metadata = (array)($generation['es-ES'] ?? []);
    if (($metadata['voice_id'] ?? '') !== MATEO_VOICE_ID) {
        throw new RuntimeException("Lesson {$id} is not marked as Mateo audio.");
    }
    $files = glob($source . '/' . sprintf('%03d', $id) . '-*.mp3');
    if (!is_array($files) || count($files) !== 1 || !is_file($files[0])) {
        throw new RuntimeException("Expected one preserved MP3 for lesson {$id}.");
    }
    $audio = file_get_contents($files[0]);
    if (!is_string($audio) || !narration_valid_mp3($audio)) {
        throw new RuntimeException("Invalid preserved MP3 for lesson {$id}.");
    }
    $request = [
        'provider' => 'elevenlabs', 'voice' => MATEO_VOICE_ID, 'language' => 'es-ES',
        'text' => trim((string)($lesson['spanish'] ?? '')), 'instructions' => MATEO_INSTRUCTIONS,
        'speed' => 1.0, 'format' => 'mp3',
    ];
    if ($request['text'] === '') throw new RuntimeException("Lesson {$id} has no Spanish text.");
    $records[] = [$id, narration_content_hash($request), $baseUrl . basename($files[0])];
}

$pdo = sqlite_db();
$pdo->beginTransaction();
try {
    $select = $pdo->prepare('SELECT id, generation_status FROM french_lesson_audio WHERE lesson_id=? AND content_hash=?');
    $insert = $pdo->prepare("INSERT INTO french_lesson_audio (lesson_id,provider,voice,language,format,audio_path,content_hash,generation_status,created_by) VALUES (?,'elevenlabs',?,'es-ES','mp3',?,?,'ready',1)");
    $update = $pdo->prepare("UPDATE french_lesson_audio SET provider='elevenlabs',voice=?,language='es-ES',format='mp3',audio_path=?,generation_status='ready',error_code=NULL WHERE id=?");
    $imported = 0;
    $existing = 0;
    foreach ($records as [$id, $hash, $url]) {
        $select->execute([$id, $hash]);
        $row = $select->fetch();
        if ($row && $row['generation_status'] === 'ready') { $existing++; continue; }
        if ($row) $update->execute([MATEO_VOICE_ID, $url, (int)$row['id']]);
        else $insert->execute([$id, MATEO_VOICE_ID, $url, $hash]);
        $imported++;
    }
    $pdo->commit();
    echo "Mateo audio: {$imported} imported, {$existing} already ready.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}
