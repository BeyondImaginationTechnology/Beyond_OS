<?php
declare(strict_types=1);

function jaguar_conversation_title(string $value): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    if ($value === '') return 'New conversation';
    return mb_substr($value, 0, 180);
}

function jaguar_conversation_list(PDO $db, int $userId): array
{
    $statement = $db->prepare('SELECT c.id,c.title,c.language,c.created_at,c.updated_at, (SELECT content FROM jaguar_conversation_messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) AS preview FROM jaguar_conversations c WHERE c.user_id=? ORDER BY c.updated_at DESC LIMIT 40');
    $statement->execute([$userId]);
    return array_map(static fn(array $row): array => [
        'id' => (int)$row['id'], 'title' => (string)$row['title'], 'language' => (string)$row['language'],
        'created_at' => (int)$row['created_at'], 'updated_at' => (int)$row['updated_at'],
        'preview' => mb_substr(trim((string)($row['preview'] ?? '')), 0, 180),
    ], $statement->fetchAll(PDO::FETCH_ASSOC));
}

function jaguar_conversation_for_user(PDO $db, int $userId, int $conversationId): ?array
{
    $statement = $db->prepare('SELECT id,title,language,created_at,updated_at FROM jaguar_conversations WHERE id=? AND user_id=? LIMIT 1');
    $statement->execute([$conversationId, $userId]);
    $conversation = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($conversation)) return null;
    $messages = $db->prepare('SELECT id,role,content,mode,created_at FROM jaguar_conversation_messages WHERE conversation_id=? ORDER BY id DESC LIMIT 48');
    $messages->execute([$conversationId]);
    $conversation['messages'] = array_reverse(array_map(static fn(array $row): array => [
        'id' => (int)$row['id'], 'role' => (string)$row['role'], 'content' => (string)$row['content'],
        'mode' => (string)$row['mode'], 'created_at' => (int)$row['created_at'],
    ], $messages->fetchAll(PDO::FETCH_ASSOC)));
    $conversation['id'] = (int)$conversation['id'];
    $conversation['created_at'] = (int)$conversation['created_at'];
    $conversation['updated_at'] = (int)$conversation['updated_at'];
    return $conversation;
}
