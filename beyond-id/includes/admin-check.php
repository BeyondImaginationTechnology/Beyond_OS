<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id']) || !beyond_refresh_browser_identity($pdo)) {
    $_SESSION['beyond_return_to'] = $_SERVER['REQUEST_URI'] ?? '/beyond-id/admin/';
    header('Location: /beyond-id/auth/login.php?required=1');
    exit;
}

$adminRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($adminRole, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo '403 Forbidden - Administrator access only.';
    exit;
}
