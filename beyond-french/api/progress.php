<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/mobile-auth.php';
require_once __DIR__ . '/../../beyond-id/includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function native_progress_reply(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    native_progress_reply(405, ['ok' => false, 'error' => 'Method not allowed.']);
}

try {
    $token = beyond_mobile_bearer_token();
    $audience = beyond_mobile_token_audience($token);
    if (!in_array($audience, ['beyond-french-ios', 'beyond-french-android'], true)) {
        throw new RuntimeException('Token is not for Beyond French.');
    }
    $claims = beyond_mobile_verify_token($token, $audience, $pdo);
    beyond_mobile_require_scope($claims, $method === 'GET' ? 'progress:read' : 'progress:write');
    $userId = (int)$claims['user_id'];
} catch (Throwable $error) {
    error_log('Beyond French progress authorization failed: ' . $error->getMessage());
    header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
    native_progress_reply(401, ['ok' => false, 'error' => 'Sign in again to sync French progress.']);
}

function native_progress_read(PDO $db, int $userId): array {
    $query = $db->prepare('SELECT completed_lesson_ids_json,completed_daily_lesson_ids_json,correct_practice_count FROM french_native_progress WHERE user_id=? LIMIT 1');
    $query->execute([$userId]);
    $row = $query->fetch(PDO::FETCH_ASSOC) ?: [];
    $lessons = json_decode((string)($row['completed_lesson_ids_json'] ?? '[]'), true);
    $daily = json_decode((string)($row['completed_daily_lesson_ids_json'] ?? '[]'), true);
    return [
        'completed_lesson_ids' => is_array($lessons) ? array_values($lessons) : [],
        'completed_daily_lesson_ids' => is_array($daily) ? array_values($daily) : [],
        'correct_practice_count' => (int)($row['correct_practice_count'] ?? 0),
    ];
}

try {
    $db = sqlite_db();
    if ($method === 'GET') {
        native_progress_reply(200, ['ok' => true] + native_progress_read($db, $userId));
    }

    if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
        native_progress_reply(415, ['ok' => false, 'error' => 'JSON requests only.']);
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) {
        native_progress_reply(413, ['ok' => false, 'error' => 'Progress snapshot is too large.']);
    }
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body) || !is_array($body['completed_lesson_ids'] ?? null)
        || !is_array($body['completed_daily_lesson_ids'] ?? null)
        || !isset($body['correct_practice_count'])) {
        native_progress_reply(422, ['ok' => false, 'error' => 'A complete progress snapshot is required.']);
    }
    $lessons = $body['completed_lesson_ids'];
    $daily = $body['completed_daily_lesson_ids'];
    $count = $body['correct_practice_count'];
    if (count($lessons) > 500 || count($daily) > 2000 || !is_int($count) || $count < 0 || $count > 1000000) {
        native_progress_reply(422, ['ok' => false, 'error' => 'Invalid progress values.']);
    }
    foreach ($lessons as $id) {
        if (!is_string($id) || !preg_match('/^[a-z0-9-]{1,120}$/', $id)) {
            native_progress_reply(422, ['ok' => false, 'error' => 'Invalid lesson ID.']);
        }
    }
    foreach ($daily as $id) {
        if (!is_int($id) || $id < 1 || $id > 1000000000) {
            native_progress_reply(422, ['ok' => false, 'error' => 'Invalid daily lesson ID.']);
        }
    }

    $db->exec('BEGIN IMMEDIATE');
    $saved = native_progress_read($db, $userId);
    $merged = [
        'completed_lesson_ids' => array_values(array_unique(array_merge($saved['completed_lesson_ids'], $lessons))),
        'completed_daily_lesson_ids' => array_values(array_unique(array_merge($saved['completed_daily_lesson_ids'], $daily))),
        'correct_practice_count' => max($saved['correct_practice_count'], $count),
    ];
    $insert = $db->prepare("INSERT INTO french_native_progress(user_id,completed_lesson_ids_json,completed_daily_lesson_ids_json,correct_practice_count,updated_at)
        VALUES(?,?,?,?,CURRENT_TIMESTAMP)
        ON CONFLICT(user_id) DO UPDATE SET completed_lesson_ids_json=excluded.completed_lesson_ids_json,
        completed_daily_lesson_ids_json=excluded.completed_daily_lesson_ids_json,
        correct_practice_count=excluded.correct_practice_count,updated_at=CURRENT_TIMESTAMP");
    $insert->execute([
        $userId,
        json_encode($merged['completed_lesson_ids'], JSON_THROW_ON_ERROR),
        json_encode($merged['completed_daily_lesson_ids'], JSON_THROW_ON_ERROR),
        $merged['correct_practice_count'],
    ]);
    $db->commit();
    native_progress_reply(200, ['ok' => true] + $merged);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Beyond French progress sync failed: ' . $error->getMessage());
    native_progress_reply(503, ['ok' => false, 'error' => 'French progress sync is temporarily unavailable.']);
}
