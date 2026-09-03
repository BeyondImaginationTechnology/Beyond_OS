<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/admin-alerts.php';
require_once __DIR__ . '/../../config/roles.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
if (!str_starts_with($contentType, 'application/json')) { http_response_code(415); echo json_encode(['ok'=>false,'error'=>'JSON requests only']); exit; }
$data=json_decode(file_get_contents('php://input'),true);
if (!is_array($data)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }
$first=trim((string)($data['first_name']??''));$last=trim((string)($data['last_name']??''));$email=strtolower(trim((string)($data['email']??'')));$password=(string)($data['password']??'');
if(!$first||!$last||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Valid name, email and password of 8+ characters required']);exit;}
$accountLimit=beyond_rate_limit_consume($pdo,'api-register-account',$email,3,3600,3600);
$ipLimit=beyond_rate_limit_consume($pdo,'api-register-ip','',10,3600,3600);
if(!$accountLimit['allowed']||!$ipLimit['allowed']){$retryAfter=max($accountLimit['retry_after'],$ipLimit['retry_after']);http_response_code(429);header('Retry-After: '.$retryAfter);echo json_encode(['ok'=>false,'error'=>'Too many registration attempts. Try again later.','retry_after'=>$retryAfter]);exit;}
try{
 $pdo->beginTransaction();
 $hash=password_hash($password,PASSWORD_DEFAULT);$token=bin2hex(random_bytes(32));$role=beyond_signup_role($email);
 $stmt=$pdo->prepare("INSERT INTO users(first_name,last_name,name,email,password,password_hash,email_verified,verification_token,verification_sent_at,role,status,preferred_locale) VALUES(?,?,?,?,?,?,0,?,NOW(),?,'active',?)");
 $stmt->execute([$first,$last,trim($first.' '.$last),$email,$hash,$hash,$token,$role,$data['locale']??'en']);$uid=(int)$pdo->lastInsertId();
 $pdo->prepare('INSERT INTO profiles(user_id,display_name) VALUES(?,?)')->execute([$uid,$first]);
 $pdo->prepare("INSERT INTO beyond_wallets(user_id,balance,currency,status) VALUES(?,0,'BITS','active')")->execute([$uid]);
 $pdo->prepare('INSERT INTO user_preferences(user_id) VALUES(?)')->execute([$uid]);
 create_notification($pdo,$uid,'Welcome to Beyond OS','Complete your profile to personalize every connected app and earn your first bit$.','/beyond-id/dashboard/profile.php','welcome');
 $pdo->commit();
 send_beyond_id_admin_signup_alert(['id'=>$uid,'first_name'=>$first,'last_name'=>$last,'email'=>$email,'created_at'=>date('Y-m-d H:i:s')], 'Beyond ID API signup');
 echo json_encode(['ok'=>true,'verification_required'=>true]);
}catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(409);echo json_encode(['ok'=>false,'error'=>'That email is already registered']);}
