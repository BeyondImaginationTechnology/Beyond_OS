<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__, 3) . '/beyond-id/includes/db.php';

if (empty($_SESSION['verse_generator_csrf'])) {
    $_SESSION['verse_generator_csrf'] = bin2hex(random_bytes(32));
}

$view = file_get_contents(__DIR__ . '/generators/breath-generator-view.html');
if ($view === false) {
    http_response_code(500);
    exit('Daily Breath generator view is unavailable.');
}
echo str_replace(
    'content="__CSRF_TOKEN__"',
    'content="' . htmlspecialchars((string)$_SESSION['verse_generator_csrf'], ENT_QUOTES, 'UTF-8') . '"',
    $view
);
