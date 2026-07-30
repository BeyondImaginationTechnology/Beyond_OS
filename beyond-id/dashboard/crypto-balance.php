<?php
declare(strict_types=1);
require __DIR__.'/../includes/auth-check.php';
require_once __DIR__.'/../includes/crypto-watch.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'A wallet account is required.']);
    exit;
}
try {
    $statement = $pdo->prepare('SELECT id,network,public_address FROM crypto_watch_accounts WHERE id=? AND user_id=? LIMIT 1');
    $statement->execute([$id, (int)$_SESSION['user_id']]);
    $account = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Wallet account not found.']);
        exit;
    }
    $balance = beyond_crypto_balance((string)$account['network'], (string)$account['public_address']);
    echo json_encode(['ok'=>true,'id'=>$id,'network'=>$account['network'],'balance'=>$balance], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    error_log('Crypto watch balance unavailable: '.$exception->getMessage());
    http_response_code(503);
    echo json_encode(['ok'=>false,'error'=>'Live balance temporarily unavailable.']);
}
