<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin-check.php';

header('Cache-Control: private, no-store');
header('Location: /server/admin/daily-studio/', true, 302);
exit;
