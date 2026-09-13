<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

$requestedScheme = strtolower(trim((string)($_GET['scheme'] ?? 'beyondmusic')));
$client = beyond_api_client_for_scheme($requestedScheme);
if ($client === []) {
    $requestedScheme = 'beyondmusic';
    $client = beyond_api_client_for_scheme($requestedScheme);
}
$scheme = $requestedScheme . '://auth';
$audience = (string)$client['audience'];
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $scheme . '?error=' . rawurlencode('Beyond ID sign in was not completed.'));
    exit;
}

try {
    $challenge = trim((string)($_GET['code_challenge'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge)) throw new RuntimeException('This app must be updated before it can sign in securely.');
    $code = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO mobile_authorization_codes(code_hash,user_id,audience,code_challenge,expires_at,created_at) VALUES (?,?,?,?,?,?)')->execute([
        hash('sha256', $code), (int)$_SESSION['user_id'], $audience, $challenge,
        date('Y-m-d H:i:s', time() + 60), date('Y-m-d H:i:s')
    ]);
    $appSlug = (string)($client['app_slug'] ?? '');
    $permissions = json_encode($client['scopes'] ?? [], JSON_THROW_ON_ERROR);
    $now = date('Y-m-d H:i:s');
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $connectionSql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON CONFLICT(user_id,app_slug) DO UPDATE SET permissions_json=excluded.permissions_json,last_used_at=excluded.last_used_at,revoked_at=NULL';
    } else {
        $connectionSql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json),last_used_at=VALUES(last_used_at),revoked_at=NULL';
    }
    $pdo->prepare($connectionSql)->execute([(int)$_SESSION['user_id'], $appSlug, $permissions, $now]);
    $pdo->commit();
    header('Location: ' . $scheme . '?code=' . rawurlencode($code));
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Mobile OAuth completion failed: ' . $exception->getMessage());
    header('Location: ' . $scheme . '?error=' . rawurlencode('Could not create the mobile sign in token.'));
}
exit;
