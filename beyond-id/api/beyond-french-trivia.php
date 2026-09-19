<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function trivia_json(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function trivia_match_payload(array $match, int $viewerId): array {
    $isHost = (int)$match['host_user_id'] === $viewerId;
    return [
        'id' => (int)$match['id'],
        'join_code' => (string)$match['join_code'],
        'status' => (string)$match['status'],
        'question_index' => (int)$match['question_index'],
        'your_role' => $isHost ? 'host' : 'guest',
        'your_score' => $isHost ? (int)$match['host_score'] : (int)$match['guest_score'],
        'opponent_score' => $isHost ? (int)$match['guest_score'] : (int)$match['host_score'],
        'your_turn' => (int)($match['active_user_id'] ?? 0) === $viewerId,
        'state_version' => (int)$match['state_version'],
        'updated_at' => (string)$match['updated_at'],
    ];
}

function trivia_code(PDO $pdo): string {
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $check = $pdo->prepare('SELECT 1 FROM beyond_french_trivia_matches WHERE join_code=? LIMIT 1');
        $check->execute([$code]);
    } while ($check->fetchColumn());
    return $code;
}

function trivia_answers(): array {
    return [
        'math-1' => '56', 'science-1' => 'Mars', 'history-1' => 'George Washington',
        'language-1' => 'Run', 'geography-1' => 'Pacific', 'math-2' => '0.75',
        'science-2' => 'Photosynthesis', 'language-2' => 'Where are you going?',
    ];
}

try {
    $claims = beyond_mobile_verify_token(beyond_mobile_bearer_token(), 'beyond-french-ios', $pdo);
    beyond_mobile_require_scope($claims, 'trivia:play');
    $userId = (int)$claims['user_id'];
} catch (Throwable $exception) {
    header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
    trivia_json(401, ['ok' => false, 'error' => 'A valid Beyond ID token with trivia access is required.']);
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$action = (string)($_GET['action'] ?? $input['action'] ?? '');

try {
    if ($method === 'POST' && $action === 'create') {
        $now = date('Y-m-d H:i:s');
        $code = trivia_code($pdo);
        $pdo->prepare('INSERT INTO beyond_french_trivia_matches (join_code,host_user_id,status,question_index,host_score,guest_score,active_user_id,state_version,created_at,updated_at) VALUES (?,?,\'waiting\',0,0,0,NULL,1,?,?)')
            ->execute([$code, $userId, $now, $now]);
        $id = (int)$pdo->lastInsertId();
        $match = $pdo->prepare('SELECT * FROM beyond_french_trivia_matches WHERE id=?');
        $match->execute([$id]);
        trivia_json(201, ['ok' => true, 'match' => trivia_match_payload($match->fetch(PDO::FETCH_ASSOC), $userId)]);
    }

    if ($method === 'POST' && $action === 'join') {
        $code = strtoupper(trim((string)($input['join_code'] ?? '')));
        if (!preg_match('/^[A-F0-9]{6}$/', $code)) trivia_json(422, ['ok' => false, 'error' => 'Enter the six-character match code.']);
        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT * FROM beyond_french_trivia_matches WHERE join_code=? LIMIT 1');
        $statement->execute([$code]);
        $match = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$match || $match['status'] !== 'waiting') { $pdo->rollBack(); trivia_json(409, ['ok' => false, 'error' => 'That match is no longer available.']); }
        if ((int)$match['host_user_id'] === $userId) { $pdo->rollBack(); trivia_json(422, ['ok' => false, 'error' => 'You cannot join your own match.']); }
        $now = date('Y-m-d H:i:s');
        $pdo->prepare('UPDATE beyond_french_trivia_matches SET guest_user_id=?,status=\'active\',active_user_id=host_user_id,state_version=state_version+1,updated_at=? WHERE id=? AND status=\'waiting\'')
            ->execute([$userId, $now, $match['id']]);
        $pdo->commit();
        $statement->execute([$code]);
        trivia_json(200, ['ok' => true, 'match' => trivia_match_payload($statement->fetch(PDO::FETCH_ASSOC), $userId)]);
    }

    $matchId = max(0, (int)($_GET['match_id'] ?? $input['match_id'] ?? 0));
    if ($matchId < 1) trivia_json(422, ['ok' => false, 'error' => 'A match id is required.']);
    $statement = $pdo->prepare('SELECT * FROM beyond_french_trivia_matches WHERE id=? AND (host_user_id=? OR guest_user_id=?) LIMIT 1');
    $statement->execute([$matchId, $userId, $userId]);
    $match = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$match) trivia_json(404, ['ok' => false, 'error' => 'Match not found.']);

    if ($method === 'GET' && $action === 'state') trivia_json(200, ['ok' => true, 'match' => trivia_match_payload($match, $userId)]);

    if ($method === 'POST' && $action === 'answer') {
        $questionId = trim((string)($input['question_id'] ?? ''));
        $answer = trim((string)($input['answer'] ?? ''));
        $version = max(0, (int)($input['state_version'] ?? 0));
        $answers = trivia_answers();
        $expectedQuestionId = array_keys($answers)[(int)$match['question_index']] ?? null;
        if ($questionId !== $expectedQuestionId || !array_key_exists($questionId, $answers)) {
            trivia_json(422, ['ok' => false, 'error' => 'That is not the active trivia question.']);
        }
        if ($match['status'] !== 'active' || (int)$match['active_user_id'] !== $userId) trivia_json(409, ['ok' => false, 'error' => 'It is not your turn.']);
        if ((int)$match['state_version'] !== $version) trivia_json(409, ['ok' => false, 'error' => 'This match changed. Refresh and try again.']);
        $isHost = (int)$match['host_user_id'] === $userId;
        $scoreField = $isHost ? 'host_score' : 'guest_score';
        $nextUser = $isHost ? (int)$match['guest_user_id'] : (int)$match['host_user_id'];
        $nextQuestion = (int)$match['question_index'] + 1;
        $status = $nextQuestion >= 8 ? 'completed' : 'active';
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE beyond_french_trivia_matches SET {$scoreField}={$scoreField}+?,question_index=?,active_user_id=?,status=?,state_version=state_version+1,updated_at=? WHERE id=? AND state_version=?";
        $update = $pdo->prepare($sql);
        $update->execute([$answer === $answers[$questionId] ? 1 : 0, $nextQuestion, $status === 'completed' ? null : $nextUser, $status, $now, $matchId, $version]);
        if ($update->rowCount() !== 1) trivia_json(409, ['ok' => false, 'error' => 'This match changed. Refresh and try again.']);
        $statement->execute([$matchId, $userId, $userId]);
        trivia_json(200, ['ok' => true, 'match' => trivia_match_payload($statement->fetch(PDO::FETCH_ASSOC), $userId)]);
    }

    header('Allow: GET, POST');
    trivia_json(405, ['ok' => false, 'error' => 'Unsupported trivia action.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Beyond French trivia failed: ' . $exception->getMessage());
    trivia_json(500, ['ok' => false, 'error' => 'Trivia service is temporarily unavailable.']);
}
