<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

$requestedScheme = strtolower(trim((string)($_GET['scheme'] ?? 'beyondmusic')));
$mobileApps = [
    'beyondmusic' => 'beyond-music-ios',
    'beyondtv' => 'beyond-tv-ios',
    'frenchquest' => 'french-quest-ios',
    'dailybreath' => 'daily-breath-ios',
];
$requestedScheme = array_key_exists($requestedScheme, $mobileApps) ? $requestedScheme : 'beyondmusic';
$scheme = $requestedScheme . '://auth';
$audience = $mobileApps[$requestedScheme];
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $scheme . '?error=' . rawurlencode('Beyond ID sign in was not completed.'));
    exit;
}

try {
    $challenge = trim((string)($_GET['code_challenge'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge)) throw new RuntimeException('This app must be updated before it can sign in securely.');
    $code = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $pdo->prepare('INSERT INTO mobile_authorization_codes(code_hash,user_id,audience,code_challenge,expires_at,created_at) VALUES (?,?,?,?,?,?)')->execute([
        hash('sha256', $code), (int)$_SESSION['user_id'], $audience, $challenge,
        date('Y-m-d H:i:s', time() + 60), date('Y-m-d H:i:s')
    ]);
    header('Location: ' . $scheme . '?code=' . rawurlencode($code));
} catch (Throwable $exception) {
    error_log('Mobile OAuth completion failed: ' . $exception->getMessage());
    header('Location: ' . $scheme . '?error=' . rawurlencode('Could not create the mobile sign in token.'));
}
exit;
