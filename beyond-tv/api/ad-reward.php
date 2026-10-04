<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'message'=>'POST required']); exit;
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['ok'=>false,'message'=>'Sign in to earn 0.01 bit$ for a completed break.']); exit;
}
$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '')) {
    http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Invalid reward request origin.']); exit;
}
$payload = json_decode((string)file_get_contents('php://input'), true);
$action = is_array($payload) ? (string)($payload['action'] ?? '') : '';
$key = 'beyond_tv_ad_reward';

if ($action === 'start') {
    $token = bin2hex(random_bytes(24));
    $_SESSION[$key] = ['token_hash'=>hash('sha256',$token),'started_at'=>time(),'claimed'=>false];
    echo json_encode(['ok'=>true,'token'=>$token,'reward'=>0.01,'minimum_seconds'=>300]); exit;
}

if ($action !== 'complete') {
    http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Unknown reward action.']); exit;
}
$record = $_SESSION[$key] ?? null;
$token = is_array($payload) ? (string)($payload['token'] ?? '') : '';
$elapsed = is_array($record) ? time() - (int)($record['started_at'] ?? 0) : 0;
if (!is_array($record) || !hash_equals((string)($record['token_hash'] ?? ''), hash('sha256',$token)) || !empty($record['claimed']) || $elapsed < 300 || $elapsed > 3600) {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Watch the full five-minute break to earn this reward.']); exit;
}
$_SESSION[$key]['claimed'] = true;
$reward = beyond_award_reward((int)$_SESSION['user_id'], 'beyond-tv', 'five-minute-ad', $token, 0.01, 'Beyond TV five-minute ad break completed');
unset($_SESSION[$key]);
echo json_encode($reward, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
