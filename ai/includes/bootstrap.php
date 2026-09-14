<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../../config/bootstrap.php';
header('Cache-Control: no-store, private');
try {
    beyond_live_config();
} catch (Throwable $error) {
    error_log('Jaguar private configuration unavailable.');
}
