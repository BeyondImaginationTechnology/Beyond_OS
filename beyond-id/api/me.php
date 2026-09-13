<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['ok'=>false,'authenticated'=>false,'error'=>'Method not allowed.']);
    exit;
}

$claims = null;
$userId = (int)($_SESSION['user_id'] ?? 0);
if (beyond_mobile_authorization_header() !== '') {
    try {
        $claims = beyond_mobile_verify_token(beyond_mobile_bearer_token(), null, $pdo);
        beyond_mobile_require_scope($claims, 'profile:read');
        $userId = (int)$claims['user_id'];
    } catch (Throwable $exception) {
        error_log('Beyond ID API token validation failed: ' . $exception->getMessage());
        http_response_code(401);
        header('WWW-Authenticate: Bearer realm="Beyond ID", error="invalid_token"');
        echo json_encode(['ok'=>false,'authenticated'=>false,'error'=>'Bearer token is invalid, expired, revoked, or missing the required scope.']);
        exit;
    }
}
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'authenticated'=>false]);
    exit;
}

$stmt=$pdo->prepare('SELECT u.id,u.name,u.first_name,u.last_name,u.email,u.role,u.status,u.preferred_locale,u.timezone,p.display_name,p.avatar,p.country,p.city,p.bio,p.interests,p.goals FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
$stmt->execute([$userId]);
$user=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || ($user['status'] ?? 'active') !== 'active') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'authenticated'=>false,'error'=>'Account unavailable.']);
    exit;
}
unset($user['status']);

$response=['ok'=>true,'authenticated'=>true,'user'=>$user];
if ($claims !== null) {
    if (!in_array('email:read', $claims['scopes'], true)) unset($response['user']['email']);
    unset($response['user']['role']);
    $response['api']=['audience'=>$claims['audience'],'scopes'=>$claims['scopes']];
}
if ($claims === null || in_array('wallet:read', $claims['scopes'], true)) {
    $wallet=['balance'=>0,'currency'=>'BITS'];
    try{$w=$pdo->prepare('SELECT balance,currency,status FROM beyond_wallets WHERE user_id=? LIMIT 1');$w->execute([$userId]);$wallet=$w->fetch(PDO::FETCH_ASSOC)?:$wallet;}catch(Throwable $e){}
    $response['wallet']=$wallet;
}
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
