<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

try {
    $claims = beyond_mobile_verify_token(beyond_mobile_bearer_token(), null, $pdo);
    $source = match ((string)$claims['audience']) {
        'daily-breath-ios' => 'dailybreath-ios',
        'beyond-french-ios' => 'beyond-french-ios',
        'beyond-french-android' => 'beyond-french-android',
        default => throw new RuntimeException('Account deletion request is unavailable for this app.'),
    };
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body) || !hash_equals('DELETE', (string)($body['confirm'] ?? ''))) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Account deletion confirmation is required.']);
        exit;
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS account_deletion_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            source TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS account_deletion_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            source VARCHAR(64) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_account_deletion_status (status, requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    $userId = (int)$claims['user_id'];
    $existing = $pdo->prepare('SELECT status FROM account_deletion_requests WHERE user_id=? LIMIT 1');
    $existing->execute([$userId]);
    $status = $existing->fetchColumn();
    if ($status === false) {
        $insert = $pdo->prepare('INSERT INTO account_deletion_requests(user_id,source,status,requested_at,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
        $insert->execute([$userId, $source, 'pending']);
    } elseif (!hash_equals('completed', (string)$status)) {
        $update = $pdo->prepare('UPDATE account_deletion_requests SET source=?,status=?,requested_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE user_id=?');
        $update->execute([$source, 'pending', $userId]);
    }

    log_activity($pdo, $userId, 'account_deletion_requested_' . str_replace('-', '_', $source));
    echo json_encode([
        'ok' => true,
        'status' => 'pending',
        'message' => 'Your account deletion request was submitted. Processing may take up to 30 days.',
    ]);
} catch (Throwable $exception) {
    error_log('Account deletion request failed: ' . $exception->getMessage());
    header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Your session is invalid or expired. Please sign in again.']);
}
