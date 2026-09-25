<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    beyond_v1_error(405, 'method_not_allowed', 'Use GET to read or PUT to update French progress.');
}
$scope = $method === 'GET' ? 'progress:read' : 'progress:write';
$claims = beyond_v1_bearer($pdo, $scope);
if (!in_array($claims['audience'], ['beyond-french-ios', 'beyond-french-android', 'french-quest-ios'], true)) {
    beyond_v1_error(403, 'unsupported_app', 'French progress is not enabled for this app.');
}
$limit = beyond_rate_limit_consume($pdo, 'v1-french-progress-' . strtolower($method), (string)$claims['user_id'], $method === 'GET' ? 120 : 30, 300, 900);
if (!$limit['allowed']) {
    header('Retry-After: ' . $limit['retry_after']);
    beyond_v1_error(429, 'rate_limited', 'Too many progress requests. Try again later.', ['retry_after' => $limit['retry_after']]);
}

$db = sqlite_db();
$db->exec('CREATE TABLE IF NOT EXISTS french_native_progress (user_id INTEGER NOT NULL PRIMARY KEY,completed_lesson_ids_json TEXT NOT NULL DEFAULT "[]",completed_daily_lesson_ids_json TEXT NOT NULL DEFAULT "[]",correct_practice_count INTEGER NOT NULL DEFAULT 0,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$db->exec('CREATE TABLE IF NOT EXISTS french_progress_idempotency (user_id INTEGER NOT NULL, idempotency_key TEXT NOT NULL, request_hash TEXT NOT NULL, response_json TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,idempotency_key))');

function v1_french_progress_read(PDO $db, int $userId): array
{
    $query = $db->prepare('SELECT completed_lesson_ids_json,completed_daily_lesson_ids_json,correct_practice_count,updated_at FROM french_native_progress WHERE user_id=? LIMIT 1');
    $query->execute([$userId]);
    $row = $query->fetch(PDO::FETCH_ASSOC) ?: [];
    $lessons = json_decode((string)($row['completed_lesson_ids_json'] ?? '[]'), true);
    $daily = json_decode((string)($row['completed_daily_lesson_ids_json'] ?? '[]'), true);
    $data = [
        'completed_lesson_ids' => is_array($lessons) ? array_values($lessons) : [],
        'completed_daily_lesson_ids' => is_array($daily) ? array_values($daily) : [],
        'correct_practice_count' => (int)($row['correct_practice_count'] ?? 0),
        'updated_at' => !empty($row['updated_at']) ? gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['updated_at'])) : null,
    ];
    $revision = '"' . hash('sha256', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) . '"';
    return [$data, $revision];
}

[$snapshot, $revision] = v1_french_progress_read($db, (int)$claims['user_id']);
header('ETag: ' . $revision);
if ($method === 'GET') beyond_v1_json(200, ['ok' => true, 'data' => $snapshot, 'revision' => trim($revision, '"')]);

$idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''));
$ifMatch = trim((string)($_SERVER['HTTP_IF_MATCH'] ?? ''));
if (!preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $idempotencyKey)) {
    beyond_v1_error(422, 'idempotency_key_required', 'PUT requires an Idempotency-Key header.');
}
if (!preg_match('/^"?[a-f0-9]{64}"?$/', $ifMatch)) {
    beyond_v1_error(428, 'revision_required', 'PUT requires the revision from the latest GET response in If-Match.');
}
if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
    beyond_v1_error(415, 'json_required', 'Send the progress update as JSON.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) beyond_v1_error(413, 'request_too_large', 'The progress update is too large.');
$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body) || !is_array($body['completed_lesson_ids'] ?? null) || !is_array($body['completed_daily_lesson_ids'] ?? null)
    || !isset($body['correct_practice_count'])) {
    beyond_v1_error(422, 'invalid_progress', 'A complete French progress snapshot is required.');
}
$lessons = $body['completed_lesson_ids'];
$daily = $body['completed_daily_lesson_ids'];
$count = $body['correct_practice_count'];
if (count($lessons) > 500 || count($daily) > 2000 || !is_int($count) || $count < 0 || $count > 1000000) {
    beyond_v1_error(422, 'invalid_progress', 'French progress values exceed the supported limits.');
}
foreach ($lessons as $id) if (!is_string($id) || !preg_match('/^[a-z0-9-]{1,120}$/', $id)) beyond_v1_error(422, 'invalid_progress', 'A lesson ID is invalid.');
foreach ($daily as $id) if (!is_int($id) || $id < 1 || $id > 1000000000) beyond_v1_error(422, 'invalid_progress', 'A daily lesson ID is invalid.');
$requestHash = hash('sha256', json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

$db->exec('BEGIN IMMEDIATE');
try {
    $db->exec("DELETE FROM french_progress_idempotency WHERE created_at < datetime('now','-30 days')");
    $prior = $db->prepare('SELECT request_hash,response_json FROM french_progress_idempotency WHERE user_id=? AND idempotency_key=?');
    $prior->execute([(int)$claims['user_id'], $idempotencyKey]);
    $priorRow = $prior->fetch(PDO::FETCH_ASSOC);
    if (is_array($priorRow)) {
        if (!hash_equals((string)$priorRow['request_hash'], $requestHash)) {
            $db->rollBack();
            beyond_v1_error(409, 'idempotency_conflict', 'That Idempotency-Key was already used with a different request.');
        }
        $stored = json_decode((string)$priorRow['response_json'], true);
        $db->commit();
        if (is_array($stored) && is_string($stored['revision'] ?? null)) header('ETag: "' . $stored['revision'] . '"');
        beyond_v1_json(200, is_array($stored) ? $stored : ['ok' => true, 'data' => $snapshot, 'revision' => trim($revision, '"')]);
    }
    [$current, $currentRevision] = v1_french_progress_read($db, (int)$claims['user_id']);
    if (!hash_equals(trim($currentRevision, '"'), trim($ifMatch, '"'))) {
        $db->rollBack();
        beyond_v1_error(409, 'revision_conflict', 'French progress changed on another device. Fetch the latest revision and merge before retrying.', ['current_revision' => trim($currentRevision, '"')]);
    }
    $merged = [
        'completed_lesson_ids' => array_values(array_unique(array_merge($current['completed_lesson_ids'], $lessons))),
        'completed_daily_lesson_ids' => array_values(array_unique(array_merge($current['completed_daily_lesson_ids'], $daily))),
        'correct_practice_count' => max($current['correct_practice_count'], $count),
    ];
    $save = $db->prepare('INSERT INTO french_native_progress(user_id,completed_lesson_ids_json,completed_daily_lesson_ids_json,correct_practice_count,updated_at) VALUES(?,?,?,?,CURRENT_TIMESTAMP) ON CONFLICT(user_id) DO UPDATE SET completed_lesson_ids_json=excluded.completed_lesson_ids_json,completed_daily_lesson_ids_json=excluded.completed_daily_lesson_ids_json,correct_practice_count=excluded.correct_practice_count,updated_at=CURRENT_TIMESTAMP');
    $save->execute([(int)$claims['user_id'], json_encode($merged['completed_lesson_ids'], JSON_THROW_ON_ERROR), json_encode($merged['completed_daily_lesson_ids'], JSON_THROW_ON_ERROR), $merged['correct_practice_count']]);
    [$updated, $updatedRevision] = v1_french_progress_read($db, (int)$claims['user_id']);
    $response = ['ok' => true, 'data' => $updated, 'revision' => trim($updatedRevision, '"')];
    $remember = $db->prepare('INSERT INTO french_progress_idempotency(user_id,idempotency_key,request_hash,response_json) VALUES(?,?,?,?)');
    $remember->execute([(int)$claims['user_id'], $idempotencyKey, $requestHash, json_encode($response, JSON_THROW_ON_ERROR)]);
    $db->commit();
    header('ETag: ' . $updatedRevision);
    beyond_v1_json(200, $response);
} catch (Throwable $exception) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Beyond ID v1 French progress failed request_id=' . $beyondV1RequestId . ' class=' . get_class($exception));
    beyond_v1_error(503, 'sync_unavailable', 'French progress sync is temporarily unavailable.');
}
