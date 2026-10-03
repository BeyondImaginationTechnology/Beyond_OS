<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/session.php';
require_once __DIR__ . '/../../includes/ecosystem.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function webs_reply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['user_id'])) {
    webs_reply(401, ['error' => 'Sign in with Beyond ID to view your session request.']);
}

$userId = (int)$_SESSION['user_id'];
if ($userId < 1) {
    webs_reply(401, ['error' => 'Your Beyond ID session is unavailable.']);
}

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    webs_reply(405, ['error' => 'Method not allowed.']);
}

try {
    $pdo = beyond_db();
    if ($method === 'GET') {
        $query = $pdo->prepare('SELECT request_id, flavour, plan, work_mode, status, requested_at, updated_at FROM beyond_webs_requests WHERE user_id = ?');
        $query->execute([$userId]);
        webs_reply(200, ['request' => $query->fetch(PDO::FETCH_ASSOC) ?: null]);
    }

    $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
    if ($contentType !== 'application/json') {
        webs_reply(415, ['error' => 'Send JSON to save a session request.']);
    }
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || strlen($raw) > 4096) {
        webs_reply(413, ['error' => 'The request is too large.']);
    }
    $input = json_decode($raw, true);
    if (!is_array($input) || array_is_list($input)) {
        webs_reply(400, ['error' => 'Invalid request.']);
    }
    $csrf = $input['csrf'] ?? null;
    if (!is_string($csrf) || !is_string($_SESSION['beyond_webs_csrf'] ?? null) || !hash_equals($_SESSION['beyond_webs_csrf'], $csrf)) {
        webs_reply(403, ['error' => 'Refresh the page and try again.']);
    }
    $flavour = $input['flavour'] ?? null;
    $plan = $input['plan'] ?? null;
    $workMode = $input['work_mode'] ?? null;
    if (!is_string($flavour) || !in_array($flavour, ['Home', 'Core', 'Creator', 'Academy', 'Cyber', 'Sentinel', 'Gaming'], true)
        || !is_string($plan) || !in_array($plan, ['Launch', 'Build', 'Power'], true)
        || !is_string($workMode) || !in_array($workMode, ['developer', 'creative', 'gaming'], true)) {
        webs_reply(422, ['error' => 'Choose a listed BIT OS flavour, session size, and work mode.']);
    }

    $requestId = bin2hex(random_bytes(16));
    $now = gmdate('Y-m-d H:i:s');
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = $driver === 'mysql'
        ? 'INSERT INTO beyond_webs_requests (user_id, request_id, flavour, plan, work_mode, status, requested_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE flavour = IF(status = \'requested\', VALUES(flavour), flavour), plan = IF(status = \'requested\', VALUES(plan), plan), work_mode = IF(status = \'requested\', VALUES(work_mode), work_mode), updated_at = IF(status = \'requested\', VALUES(updated_at), updated_at)'
        : 'INSERT INTO beyond_webs_requests (user_id, request_id, flavour, plan, work_mode, status, requested_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id) DO UPDATE SET flavour = CASE WHEN status = \'requested\' THEN excluded.flavour ELSE flavour END, plan = CASE WHEN status = \'requested\' THEN excluded.plan ELSE plan END, work_mode = CASE WHEN status = \'requested\' THEN excluded.work_mode ELSE work_mode END, updated_at = CASE WHEN status = \'requested\' THEN excluded.updated_at ELSE updated_at END';
    $pdo->prepare($sql)->execute([$userId, $requestId, $flavour, $plan, $workMode, 'requested', $now, $now]);
    $query = $pdo->prepare('SELECT request_id, flavour, plan, work_mode, status, requested_at, updated_at FROM beyond_webs_requests WHERE user_id = ?');
    $query->execute([$userId]);
    $saved = $query->fetch(PDO::FETCH_ASSOC);
    if (!is_array($saved)) {
        throw new RuntimeException('Saved request could not be read.');
    }
    if ($saved['status'] !== 'requested') {
        webs_reply(409, ['error' => 'This session request can no longer be edited.', 'request' => $saved]);
    }
    webs_reply(200, ['request' => $saved]);
} catch (Throwable $exception) {
    error_log('Beyond Webs request unavailable: ' . $exception->getMessage());
    webs_reply(503, ['error' => 'Session requests are temporarily unavailable. Please try again later.']);
}
