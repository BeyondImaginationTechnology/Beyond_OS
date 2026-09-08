<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_MEMBERS = 2;

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array
{
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        respond(400, ['error' => 'invalid_json']);
    }
    return $decoded;
}

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $default = beyond_private_root() . '/beyond-baby-names/couples.sqlite';
    $path = getenv('BEYOND_BABY_NAMES_DB') ?: $default;
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        respond(500, ['error' => 'database_directory_unavailable']);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec((string) file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    $cleanup = $pdo->prepare('DELETE FROM couple_spaces WHERE expires_at <= :now');
    $cleanup->execute(['now' => gmdate('c')]);
    return $pdo;
}

function base64url(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function new_id(): string
{
    return bin2hex(random_bytes(16));
}

function new_token(): string
{
    return base64url(random_bytes(32));
}

function new_invite_code(): string
{
    return 'BBN-' . implode('-', str_split(strtoupper(bin2hex(random_bytes(8))), 4));
}

function normalize_invite_code(string $code): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($code)) ?? '');
}

function secret_hash(string $value): string
{
    return hash('sha256', $value);
}

function text_limit(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
}

function text_key(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function display_name(mixed $value): string
{
    return text_limit(trim(is_string($value) ? $value : ''), 60);
}

function bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+([A-Za-z0-9_-]{40,})$/', $header, $match)) {
        respond(401, ['error' => 'missing_member_token']);
    }
    return $match[1];
}

function member(PDO $pdo): array
{
    $statement = $pdo->prepare(
        'SELECT m.id, m.couple_id, m.role, m.display_name
         FROM couple_members m
         JOIN couple_spaces c ON c.id = m.couple_id
         WHERE m.auth_token_hash = :token AND c.status = \'active\' AND c.expires_at > :now'
    );
    $statement->execute(['token' => secret_hash(bearer_token()), 'now' => gmdate('c')]);
    $member = $statement->fetch();
    if (!$member) {
        respond(401, ['error' => 'invalid_or_expired_member_token']);
    }
    return $member;
}

function enforce_join_limit(PDO $pdo): void
{
    $window = intdiv(time(), 600) * 600;
    $client = secret_hash((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $pdo->prepare(
            'DELETE FROM couple_join_limits WHERE window_started < :oldest'
        )->execute(['oldest' => $window - 1200]);
        $upsert = $pdo->prepare(
            'INSERT INTO couple_join_limits (client_hash, window_started, attempts)
             VALUES (:client, :window, 1)
             ON CONFLICT(client_hash, window_started)
             DO UPDATE SET attempts = attempts + 1'
        );
        $upsert->execute(['client' => $client, 'window' => $window]);
        $count = $pdo->prepare(
            'SELECT attempts FROM couple_join_limits
             WHERE client_hash = :client AND window_started = :window'
        );
        $count->execute(['client' => $client, 'window' => $window]);
        $attempts = (int) $count->fetchColumn();
        $pdo->commit();
        if ($attempts > 20) {
            header('Retry-After: ' . max(1, ($window + 600) - time()));
            respond(429, ['error' => 'join_rate_limited']);
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function create_space(PDO $pdo, array $input): never
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $inviteCode = new_invite_code();
        $token = new_token();
        $coupleId = new_id();
        try {
            $pdo->beginTransaction();
            $space = $pdo->prepare(
                'INSERT INTO couple_spaces (id, invite_code_hash, created_at, expires_at)
                 VALUES (:id, :invite, :created, :expires)'
            );
            $space->execute([
                'id' => $coupleId,
                'invite' => secret_hash(normalize_invite_code($inviteCode)),
                'created' => gmdate('c'),
                'expires' => gmdate('c', time() + 180 * 86400),
            ]);
            $member = $pdo->prepare(
                'INSERT INTO couple_members (id, couple_id, role, display_name, auth_token_hash, joined_at)
                 VALUES (:id, :couple, \'creator\', :name, :token, :joined)'
            );
            $member->execute([
                'id' => new_id(),
                'couple' => $coupleId,
                'name' => display_name($input['displayName'] ?? ''),
                'token' => secret_hash($token),
                'joined' => gmdate('c'),
            ]);
            $pdo->commit();
            respond(201, [
                'coupleId' => $coupleId,
                'inviteCode' => $inviteCode,
                'memberToken' => $token,
                'expiresInDays' => 180,
            ]);
        } catch (PDOException $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $error->getCode() !== '23000') {
                throw $error;
            }
        }
    }
    respond(503, ['error' => 'identifier_generation_failed']);
}

function join_space(PDO $pdo, array $input): never
{
    enforce_join_limit($pdo);
    $normalized = normalize_invite_code((string) ($input['inviteCode'] ?? ''));
    if (strlen($normalized) !== 19 || !str_starts_with($normalized, 'BBN')) {
        respond(422, ['error' => 'invalid_invite_code']);
    }

    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $spaceQuery = $pdo->prepare(
            'SELECT id FROM couple_spaces
             WHERE invite_code_hash = :invite AND status = \'active\' AND expires_at > :now'
        );
        $spaceQuery->execute(['invite' => secret_hash($normalized), 'now' => gmdate('c')]);
        $space = $spaceQuery->fetch();
        if (!$space) {
            $pdo->rollBack();
            respond(404, ['error' => 'invite_not_found']);
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM couple_members WHERE couple_id = :couple');
        $count->execute(['couple' => $space['id']]);
        if ((int) $count->fetchColumn() >= MAX_MEMBERS) {
            $pdo->rollBack();
            respond(409, ['error' => 'couple_space_full']);
        }
        $token = new_token();
        $insert = $pdo->prepare(
            'INSERT INTO couple_members (id, couple_id, role, display_name, auth_token_hash, joined_at)
             VALUES (:id, :couple, \'partner\', :name, :token, :joined)'
        );
        $insert->execute([
            'id' => new_id(),
            'couple' => $space['id'],
            'name' => display_name($input['displayName'] ?? ''),
            'token' => secret_hash($token),
            'joined' => gmdate('c'),
        ]);
        $pdo->commit();
        respond(200, ['coupleId' => $space['id'], 'memberToken' => $token]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function save_pick(PDO $pdo, array $input): never
{
    $current = member($pdo);
    $label = text_limit(trim((string) ($input['name'] ?? '')), 80);
    $decision = (string) ($input['decision'] ?? '');
    if ($label === '' || !in_array($decision, ['pass', 'maybe', 'love'], true)) {
        respond(422, ['error' => 'invalid_pick']);
    }
    $key = text_key($label);
    $statement = $pdo->prepare(
        'INSERT INTO couple_picks (couple_id, member_id, name_key, name_label, decision, updated_at)
         VALUES (:couple, :member, :key, :label, :decision, :updated)
         ON CONFLICT(member_id, name_key) DO UPDATE SET
             name_label = excluded.name_label,
             decision = excluded.decision,
             updated_at = excluded.updated_at'
    );
    $statement->execute([
        'couple' => $current['couple_id'],
        'member' => $current['id'],
        'key' => $key,
        'label' => $label,
        'decision' => $decision,
        'updated' => gmdate('c'),
    ]);
    respond(200, ['saved' => true]);
}

function close_space(PDO $pdo): never
{
    $current = member($pdo);
    $statement = $pdo->prepare('DELETE FROM couple_spaces WHERE id = :couple');
    $statement->execute(['couple' => $current['couple_id']]);
    respond(200, ['closed' => true]);
}

function get_state(PDO $pdo): never
{
    $current = member($pdo);
    $own = $pdo->prepare(
        'SELECT name_label AS name, decision, updated_at AS updatedAt
         FROM couple_picks WHERE member_id = :member ORDER BY updated_at DESC'
    );
    $own->execute(['member' => $current['id']]);
    $matches = $pdo->prepare(
        'SELECT MIN(name_label) AS name
         FROM couple_picks
         WHERE couple_id = :couple AND decision = \'love\'
         GROUP BY name_key
         HAVING COUNT(DISTINCT member_id) >= 2
         ORDER BY name'
    );
    $matches->execute(['couple' => $current['couple_id']]);
    $members = $pdo->prepare('SELECT COUNT(*) FROM couple_members WHERE couple_id = :couple');
    $members->execute(['couple' => $current['couple_id']]);
    respond(200, [
        'coupleId' => $current['couple_id'],
        'memberCount' => (int) $members->fetchColumn(),
        'ownPicks' => $own->fetchAll(),
        'matches' => array_column($matches->fetchAll(), 'name'),
    ]);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        respond(405, ['error' => 'method_not_allowed']);
    }
    $input = body();
    $action = (string) ($input['action'] ?? '');
    $pdo = database();
    match ($action) {
        'create' => create_space($pdo, $input),
        'join' => join_space($pdo, $input),
        'savePick' => save_pick($pdo, $input),
        'state' => get_state($pdo),
        'close' => close_space($pdo),
        default => respond(422, ['error' => 'unknown_action']),
    };
} catch (Throwable $error) {
    error_log('Beyond Baby Names couples API: ' . $error->getMessage());
    respond(500, ['error' => 'internal_error']);
}
