<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/conversations.php';
require_once __DIR__ . '/../../beyond-id/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

function conversation_json(int $status, array $body): never { http_response_code($status); echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }

if (empty($_SESSION['user_id'])) conversation_json(401, ['error' => 'Sign in with Beyond ID to save conversations.']);
$userId = (int)$_SESSION['user_id'];
$db = beyond_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false && $id !== null) {
        $conversation = jaguar_conversation_for_user($db, $userId, (int)$id);
        if ($conversation === null) conversation_json(404, ['error' => 'Conversation not found.']);
        conversation_json(200, ['conversation' => $conversation]);
    }
    conversation_json(200, ['conversations' => jaguar_conversation_list($db, $userId)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') conversation_json(405, ['error' => 'Method not allowed.']);
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) conversation_json(403, ['error' => 'Your secure session expired. Refresh Jaguar and try again.']);
$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) conversation_json(422, ['error' => 'Invalid conversation request.']);
$action = is_string($payload['action'] ?? null) ? $payload['action'] : '';

try {
    if ($action === 'create') {
        $language = is_string($payload['language'] ?? null) ? strtolower($payload['language']) : 'en';
        if (!in_array($language, ['en', 'fr', 'es'], true)) $language = 'en';
        $now = time();
        $statement = $db->prepare('INSERT INTO jaguar_conversations(user_id,title,language,created_at,updated_at) VALUES(?,?,?,?,?)');
        $statement->execute([$userId, jaguar_conversation_title((string)($payload['title'] ?? '')), $language, $now, $now]);
        conversation_json(201, ['conversation' => jaguar_conversation_for_user($db, $userId, (int)$db->lastInsertId())]);
    }
    $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null || jaguar_conversation_for_user($db, $userId, (int)$id) === null) conversation_json(404, ['error' => 'Conversation not found.']);
    if ($action === 'rename') {
        $db->prepare('UPDATE jaguar_conversations SET title=?,updated_at=? WHERE id=? AND user_id=?')->execute([jaguar_conversation_title((string)($payload['title'] ?? '')), time(), $id, $userId]);
        conversation_json(200, ['conversation' => jaguar_conversation_for_user($db, $userId, (int)$id)]);
    }
    if ($action === 'delete') {
        $db->prepare('DELETE FROM jaguar_conversations WHERE id=? AND user_id=?')->execute([$id, $userId]);
        conversation_json(200, ['ok' => true]);
    }
    if ($action === 'append') {
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        if ($messages === [] || count($messages) > 2) conversation_json(422, ['error' => 'Provide the completed exchange only.']);
        $insert = $db->prepare('INSERT INTO jaguar_conversation_messages(conversation_id,role,content,mode,created_at) VALUES(?,?,?,?,?)');
        $now = time();
        foreach ($messages as $message) {
            $role = is_array($message) ? (string)($message['role'] ?? '') : '';
            $content = is_array($message) ? trim((string)($message['content'] ?? '')) : '';
            if (!in_array($role, ['user', 'assistant'], true) || $content === '' || mb_strlen($content) > 12000) conversation_json(422, ['error' => 'Conversation message is invalid.']);
            $insert->execute([$id, $role, $content, $role === 'assistant' ? 'core' : 'user', $now]);
        }
        $db->prepare('UPDATE jaguar_conversations SET updated_at=? WHERE id=? AND user_id=?')->execute([$now, $id, $userId]);
        conversation_json(200, ['conversation' => jaguar_conversation_for_user($db, $userId, (int)$id)]);
    }
    conversation_json(422, ['error' => 'Unknown conversation action.']);
} catch (Throwable $exception) {
    error_log('Jaguar conversation request failed: ' . $exception->getMessage());
    conversation_json(503, ['error' => 'Jaguar conversations are temporarily unavailable.']);
}
