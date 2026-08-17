<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/mobile-auth.php';

$requestedScheme = strtolower(trim((string)($_GET['scheme'] ?? 'beyondmusic')));
$mobileApps = [
    'beyondmusic' => 'beyond-music-ios',
    'beyondtv' => 'beyond-tv-ios',
    'frenchquest' => 'french-quest-ios',
];
$requestedScheme = array_key_exists($requestedScheme, $mobileApps) ? $requestedScheme : 'beyondmusic';
$scheme = $requestedScheme . '://auth';
$audience = $mobileApps[$requestedScheme];
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $scheme . '?error=' . rawurlencode('Beyond ID sign in was not completed.'));
    exit;
}

try {
    $token = beyond_mobile_issue_token((int)$_SESSION['user_id'], 2592000, $audience);
    header('Location: ' . $scheme . '?token=' . rawurlencode($token));
} catch (Throwable $exception) {
    error_log('Mobile OAuth completion failed: ' . $exception->getMessage());
    header('Location: ' . $scheme . '?error=' . rawurlencode('Could not create the mobile sign in token.'));
}
exit;
