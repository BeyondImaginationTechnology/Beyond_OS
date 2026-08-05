<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['verse_generator_csrf'])) {
    $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));
}

$view = file_get_contents(__DIR__ . '/generators/space-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Beyond Space horoscope generator view is unavailable.');
}
$view = str_replace('</head>', '<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css"></head>', $view);

echo str_replace(
    'content="__CSRF_TOKEN__"',
    'content="' . htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8') . '"',
    $view
);
