<?php
require_once __DIR__.'/../includes/session.php';
require_once __DIR__.'/../includes/functions.php';
require __DIR__.'/../includes/db.php';
require_once __DIR__.'/../../config/mail.php';
$message='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf']??null)){
        http_response_code(403);
        $error='Your session expired. Please try again.';
    }else{
        $email=strtolower(trim($_POST['email']??''));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error='Enter a valid email address.';
        }else{
            $accountLimit=beyond_rate_limit_consume($pdo,'verification-email-account',$email,3,3600,3600);
            $ipLimit=beyond_rate_limit_consume($pdo,'verification-email-ip','',10,3600,3600);
            if(!$accountLimit['allowed']||!$ipLimit['allowed']){
                $retryAfter=max($accountLimit['retry_after'],$ipLimit['retry_after']);
                http_response_code(429);
                header('Retry-After: '.$retryAfter);
                $error='Too many verification requests. Please try again later.';
            }else{
                $stmt=$pdo->prepare('SELECT id,first_name,last_name,email_verified,verification_sent_at FROM users WHERE email=? LIMIT 1');
                $stmt->execute([$email]);
                $u=$stmt->fetch(PDO::FETCH_ASSOC);
                if($u&&!$u['email_verified']){
                    if(!empty($u['verification_sent_at'])&&strtotime($u['verification_sent_at'])>time()-300){
                        $error='Please wait five minutes before requesting another message.';
                    }else{
                        $token=bin2hex(random_bytes(32));
                        $pdo->prepare('UPDATE users SET verification_token=?,verification_sent_at=NOW() WHERE id=?')->execute([$token,$u['id']]);
                        send_verification_email($email,$token,'beyond_id',trim(($u['first_name']??'').' '.($u['last_name']??'')));
                    }
                }
                if(!$error)$message='If an unverified account exists, a fresh verification link has been sent.';
            }
        }
    }
}
?><!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resend verification | Beyond ID</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#30206b,#090912 55%);color:white;font-family:system-ui}.card{width:min(440px,calc(100% - 32px));padding:32px;border:1px solid #3b3b50;border-radius:26px;background:#151522}h1{font-size:34px}p{color:#b2b2c3;line-height:1.55}input,button{box-sizing:border-box;width:100%;padding:15px;border-radius:13px;font-size:15px}input{background:#0b0b15;border:1px solid #414157;color:white}button{margin-top:12px;border:0;background:linear-gradient(90deg,#5b8cff,#a044f2,#e9449f);color:white;font-weight:900}.msg,.err{padding:12px;border-radius:12px}.msg{background:#14503b}.err{background:#651d29}a{color:#c4b5fd}.app-back{display:inline-flex;align-items:center;min-height:42px;padding:9px 15px;border:1px solid rgba(167,139,250,.4);border-radius:999px;background:rgba(167,139,250,.1);color:inherit!important;font-weight:800;text-decoration:none}.app-back:hover{background:rgba(167,139,250,.2)}</style></head><body><main class="card"><h1>Verify your Beyond ID</h1><p>Verification links expire after 24 hours.</p><?php if($message):?><div class="msg"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="err"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><p><label>Email address</label></p><input type="email" name="email" required><button>Send verification email</button></form><p><a class="app-back" href="login.php">← Back to sign in</a></p></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
