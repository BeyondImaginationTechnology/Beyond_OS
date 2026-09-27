<?php
declare(strict_types=1);

function jaguar_feedback_table(PDO $db): void
{
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $db->exec("CREATE TABLE IF NOT EXISTS jaguar_feedback_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT, scope TEXT NOT NULL, mode TEXT NOT NULL,
            prompt TEXT NOT NULL, answer TEXT NOT NULL, correction TEXT NOT NULL,
            user_id INTEGER NULL, consent_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending',
            reviewed_by INTEGER NULL, reviewed_at TEXT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $db->exec('CREATE INDEX IF NOT EXISTS idx_jaguar_feedback_queue ON jaguar_feedback_submissions(status, scope, created_at)');
        return;
    }
    $db->exec("CREATE TABLE IF NOT EXISTS jaguar_feedback_submissions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        scope VARCHAR(40) NOT NULL, mode VARCHAR(30) NOT NULL,
        prompt LONGTEXT NOT NULL, answer LONGTEXT NOT NULL, correction LONGTEXT NOT NULL,
        user_id BIGINT NULL, consent_at DATETIME NOT NULL,
        status VARCHAR(16) NOT NULL DEFAULT 'pending', reviewed_by BIGINT NULL,
        reviewed_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_jaguar_feedback_queue (status, scope, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
