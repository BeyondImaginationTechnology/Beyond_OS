<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$view = file_get_contents(__DIR__ . '/generators/history-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Beyond Ancient history generator view is unavailable.');
}

echo str_replace('</head>', '<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"></head>', $view);

