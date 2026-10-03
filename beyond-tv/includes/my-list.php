<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';

function beyond_tv_my_list_db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) return $connection;
    $pdo = beyond_db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS tv_my_list (
        user_id BIGINT NOT NULL,
        item_type VARCHAR(16) NOT NULL,
        item_slug VARCHAR(128) NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, item_type, item_slug)
    )');
    $connection = $pdo;
    return $connection;
}

function beyond_tv_my_list_items(int $userId): array
{
    if ($userId <= 0) return [];
    $statement = beyond_tv_my_list_db()->prepare('SELECT item_type, item_slug FROM tv_my_list WHERE user_id = ? ORDER BY added_at DESC, item_slug');
    $statement->execute([$userId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function beyond_tv_my_list_has(int $userId, string $type, string $slug): bool
{
    if ($userId <= 0) return false;
    $statement = beyond_tv_my_list_db()->prepare('SELECT 1 FROM tv_my_list WHERE user_id = ? AND item_type = ? AND item_slug = ? LIMIT 1');
    $statement->execute([$userId, $type, $slug]);
    return (bool)$statement->fetchColumn();
}

function beyond_tv_my_list_token(): string
{
    if (empty($_SESSION['beyond_tv_my_list_token'])) {
        $_SESSION['beyond_tv_my_list_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['beyond_tv_my_list_token'];
}
