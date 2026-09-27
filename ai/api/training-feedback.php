<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/training-feedback.php';
require_once __DIR__ . '/../../beyond-id/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your secure session expired. Refresh Jaguar and try again.']);
    exit;
}
$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload) || ($payload['consent'] ?? false) !== true) {
    http_response_code(422);
    echo json_encode(['error' => 'Explicit consent is required to submit a suggestion.']);
    exit;
}
$scope = is_string($payload['scope'] ?? null) ? $payload['scope'] : '';
$prompt = is_string($payload['prompt'] ?? null) ? trim($payload['prompt']) : '';
$answer = is_string($payload['answer'] ?? null) ? trim($payload['answer']) : '';
$correction = is_string($payload['correction'] ?? null) ? trim($payload['correction']) : '';
if (!in_array($scope, ['jaguar', 'beyond-tattoo'], true)
    || $prompt === '' || mb_strlen($prompt) > 8000
    || $answer === '' || mb_strlen($answer) > 12000
    || $correction === '' || mb_strlen($correction) > 5000) {
    http_response_code(422);
    echo json_encode(['error' => 'The selected exchange or suggested answer is invalid or too long.']);
    exit;
}
$mode = is_string($payload['mode'] ?? null) && in_array($payload['mode'], ['core', 'build'], true) ? $payload['mode'] : 'core';
try {
    $db = beyond_db();
    $identity = !empty($_SESSION['user_id'])
        ? 'user:' . (int)$_SESSION['user_id']
        : 'ip:' . hash('sha256', beyond_rate_limit_client_ip());
    $limit = beyond_rate_limit_consume($db, 'jaguar-training-feedback', $identity, 5, 3600, 3600);
    if (!$limit['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . (int)$limit['retry_after']);
        echo json_encode(['error' => 'You have reached the suggestion limit. Please try again later.']);
        exit;
    }
    jaguar_feedback_table($db);
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $statement = $db->prepare("INSERT INTO jaguar_feedback_submissions(scope,mode,prompt,answer,correction,user_id,consent_at,status) VALUES(?,?,?,?,?,?,?,'pending')");
    $statement->execute([$scope, $mode, $prompt, $answer, $correction, $userId, gmdate('Y-m-d H:i:s')]);
    echo json_encode(['ok' => true, 'message' => 'Saved for human review.']);
} catch (Throwable $exception) {
    error_log('Jaguar feedback submission failed: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Jaguar could not save the suggestion right now. Please try again later.']);
}
