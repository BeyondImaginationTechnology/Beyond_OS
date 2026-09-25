<?php
declare(strict_types=1);

// Legacy callers share the Beyond ID database configuration.
return require dirname(__DIR__, 2) . '/beyond-id/config/database.php';
