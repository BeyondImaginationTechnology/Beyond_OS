<?php
require_once dirname(__DIR__, 2) . '/config/security.php';
require_once dirname(__DIR__, 2) . '/includes/ecosystem.php';

$adminPassword = getenv('DAILYBREATH_ADMIN_PASSWORD');
define('ADMIN_PASSWORD', is_string($adminPassword) ? trim($adminPassword) : '');

function db(): PDO {
    static $pdo;
    if (!($pdo instanceof PDO)) $pdo = beyond_db();
    return $pdo;
}
