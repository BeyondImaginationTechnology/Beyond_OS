<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
try {
    $userId = beyond_mobile_verify_token($token);
    $stmt = $pdo->prepare('SELECT u.id,u.name,u.first_name,u.last_name,u.email,u.role,u.status,u.preferred_locale,u.timezone,p.display_name,p.avatar,p.country,p.city,p.bio,p.interests,p.goals FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || ($user['status'] ?? 'active') !== 'active') {
        throw new RuntimeException('Account unavailable.');
    }
    $wallet = ['balance' => 0, 'currency' => 'BITS'];
    try {
        $w = $pdo->prepare('SELECT balance,currency,status FROM beyond_wallets WHERE user_id=? LIMIT 1');
        $w->execute([$userId]);
        $wallet = $w->fetch(PDO::FETCH_ASSOC) ?: $wallet;
    } catch (Throwable $exception) {}
    echo json_encode(['ok' => true, 'authenticated' => true, 'user' => $user, 'wallet' => $wallet], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'authenticated' => false, 'error' => $exception->getMessage()]);
}
