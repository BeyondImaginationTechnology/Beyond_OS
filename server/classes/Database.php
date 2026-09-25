<?php
class Database {
    private static ?PDO $pdo = null;
    public static function conn(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;
        require_once dirname(__DIR__, 2) . '/includes/ecosystem.php';
        self::$pdo = beyond_db();
        return self::$pdo;
    }
}
