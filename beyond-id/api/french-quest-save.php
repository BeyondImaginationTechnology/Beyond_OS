<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function french_quest_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $userId = beyond_mobile_verify_token(beyond_mobile_bearer_token(), 'french-quest-ios', $pdo)['user_id'];
} catch (Throwable $exception) {
    french_quest_json(401, ['ok' => false, 'error' => $exception->getMessage()]);
}

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET') {
        $statement = $pdo->prepare('SELECT completed_challenge_ids,xp,hearts,streak,theme,schema_version,updated_at FROM french_quest_saves WHERE user_id=? LIMIT 1');
        $statement->execute([$userId]);
        $save = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$save) french_quest_json(200, ['ok' => true, 'save' => null]);

        $completed = json_decode((string)$save['completed_challenge_ids'], true);
        french_quest_json(200, ['ok' => true, 'save' => [
            'completed_challenge_ids' => is_array($completed) ? array_values($completed) : [],
            'xp' => (int)$save['xp'],
            'hearts' => (int)$save['hearts'],
            'streak' => (int)$save['streak'],
            'theme' => (string)$save['theme'],
            'schema_version' => (int)$save['schema_version'],
            'updated_at' => (string)$save['updated_at'],
        ]]);
    }

    if ($method !== 'PUT') {
        header('Allow: GET, PUT');
        french_quest_json(405, ['ok' => false, 'error' => 'Method not allowed.']);
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) french_quest_json(400, ['ok' => false, 'error' => 'A JSON save snapshot is required.']);

    $completed = $input['completed_challenge_ids'] ?? [];
    if (!is_array($completed) || count($completed) > 500) {
        french_quest_json(422, ['ok' => false, 'error' => 'Invalid completed challenge list.']);
    }
    $completed = array_values(array_unique(array_filter(array_map(
        static fn($value): string => substr(trim((string)$value), 0, 120),
        $completed
    ), static fn(string $value): bool => $value !== '')));
    $xp = max(0, min(100000000, (int)($input['xp'] ?? 0)));
    $hearts = max(0, min(5, (int)($input['hearts'] ?? 5)));
    $streak = max(0, min(1000000, (int)($input['streak'] ?? 0)));
    $allowedThemes = ['night', 'riviera', 'market', 'garden'];
    $theme = in_array((string)($input['theme'] ?? ''), $allowedThemes, true) ? (string)$input['theme'] : 'riviera';
    $completedJson = json_encode($completed, JSON_THROW_ON_ERROR);
    $now = date('Y-m-d H:i:s');

    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $sql = 'INSERT INTO french_quest_saves (user_id,completed_challenge_ids,xp,hearts,streak,theme,schema_version,created_at,updated_at) VALUES (?,?,?,?,?,?,1,?,?) ON CONFLICT(user_id) DO UPDATE SET completed_challenge_ids=excluded.completed_challenge_ids,xp=excluded.xp,hearts=excluded.hearts,streak=excluded.streak,theme=excluded.theme,schema_version=excluded.schema_version,updated_at=excluded.updated_at';
    } else {
        $sql = 'INSERT INTO french_quest_saves (user_id,completed_challenge_ids,xp,hearts,streak,theme,schema_version,created_at,updated_at) VALUES (?,?,?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE completed_challenge_ids=VALUES(completed_challenge_ids),xp=VALUES(xp),hearts=VALUES(hearts),streak=VALUES(streak),theme=VALUES(theme),schema_version=VALUES(schema_version),updated_at=VALUES(updated_at)';
    }
    $pdo->prepare($sql)->execute([$userId, $completedJson, $xp, $hearts, $streak, $theme, $now, $now]);
    french_quest_json(200, ['ok' => true, 'updated_at' => $now]);
} catch (Throwable $exception) {
    error_log('French Quest cloud save failed: ' . $exception->getMessage());
    french_quest_json(500, ['ok' => false, 'error' => 'Cloud save is temporarily unavailable.']);
}
