<?php
declare(strict_types=1);

// Old verification links now complete through Beyond ID's active SQLite flow.
$token = trim((string)($_GET['token'] ?? ''));
$destination = '/beyond-id/auth/verify-email.php';
if ($token !== '') {
    $destination .= '?token=' . rawurlencode($token);
}
header('Location: ' . $destination, true, 302);
exit;
