<?php
class Activity
{
    public static function log(?int $userId, string $action, string $details = ''): void
    {
        try {
            $pdo = Database::conn();
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, detail, ip_address, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
    }
}
