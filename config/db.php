<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/ecosystem.php';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $pdo = beyond_db();
        return $pdo;
    } catch (Throwable $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database service unavailable.');
    }
}
