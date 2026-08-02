<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/roles.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit;
}
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Valid email and password required']); exit;
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]); $user=$stmt->fetch(PDO::FETCH_ASSOC);
$valid = false;
$matchedHash = '';
if ($user) {
    foreach (array_unique(array_filter([(string)($user['password_hash'] ?? ''), (string)($user['password'] ?? '')])) as $candidateHash) {
        if (password_verify($password, $candidateHash)) {
            $valid = true;
            $matchedHash = $candidateHash;
            break;
        }
    }
}
if (!$valid) {
    http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Invalid email or password']); exit;
}
if ($matchedHash !== (string)($user['password_hash'] ?? '') || password_needs_rehash($matchedHash, PASSWORD_DEFAULT)) {
    $freshHash = password_hash($password, PASSWORD_DEFAULT);
    try { $pdo->prepare('UPDATE users SET password_hash=?, password=? WHERE id=?')->execute([$freshHash,$freshHash,$user['id']]); }
    catch (Throwable $exception) { $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$freshHash,$user['id']]); }
}
if (($user['status'] ?? 'active') !== 'active') {
    http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Account unavailable']); exit;
}
if (empty($user['email_verified']) && empty($user['email_verified_at']) && !empty($user['verification_token'])) {
    http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Email verification required']); exit;
}
session_regenerate_id(true);
$_SESSION['user_id']=(int)$user['id'];
$_SESSION['email']=$user['email'];
$_SESSION['name']=$user['name'] ?? trim(($user['first_name']??'').' '.($user['last_name']??''));
$_SESSION['role']=$user['role'] ?? 'user';
$_SESSION['locale']=$user['preferred_locale'] ?? 'en';
$requiredRole = beyond_signup_role($email, (string)$_SESSION['role']);
if ($requiredRole !== (string)$_SESSION['role']) {
    $_SESSION['role'] = $requiredRole;
    try { $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$requiredRole,$user['id']]); } catch (Throwable $exception) {}
}
register_session($pdo,(int)$user['id']);
$pdo->prepare('UPDATE users SET last_login_at=?,last_login_ip=? WHERE id=?')->execute([date('Y-m-d H:i:s'),$_SERVER['REMOTE_ADDR']??null,$user['id']]);
log_activity($pdo,(int)$user['id'],'login_api');
echo json_encode(['ok'=>true,'user'=>['id'=>(int)$user['id'],'email'=>$user['email'],'name'=>$_SESSION['name'],'role'=>$_SESSION['role'],'locale'=>$_SESSION['locale']]]);
