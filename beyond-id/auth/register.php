<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/social-auth.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/admin-alerts.php';
require_once __DIR__ . '/../../config/roles.php';
$instagramRegisterHtml = '';
ob_start(static function (string $html) use (&$instagramRegisterHtml): string {
    $html = str_replace('BEYOND OS 2.4 BETA', 'BEYOND OS · BETA', $html);
    $html = str_replace('Sign up with Facebook / Instagram', 'Sign up with Facebook', $html);
    return str_replace('<div class="divider">or create with email</div>', $instagramRegisterHtml . '<div class="divider">or create with email</div>', $html);
});
$error=(string)($_SESSION['oauth_error'] ?? ''); unset($_SESSION['oauth_error']); $success='';
$googleEnabled=beyond_social_enabled('google'); $metaEnabled=beyond_social_enabled('meta'); $instagramEnabled=beyond_social_enabled('instagram');
$instagramRegisterHtml='<div class="social">'.($instagramEnabled
    ? '<a href="oauth-start.php?provider=instagram" style="color:#fff;background:linear-gradient(90deg,#833ab4,#fd1d1d,#fcb045)">◎&nbsp; Sign up with Instagram</a>'
    : '<span class="disabled" aria-disabled="true" style="display:flex;align-items:center;justify-content:center;min-height:48px;padding:12px;border-radius:13px">◎&nbsp; Sign up with Instagram</span>').'</div>'
    .(!$instagramEnabled?'<p class="social-note">Instagram sign-up activates after its App ID and secret are added to <code>var/config/live.php</code>.</p>':'');
$isSqlite=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite';
$insertIgnore=static fn(string $sql):string=>$isSqlite?str_replace('INSERT INTO','INSERT OR IGNORE INTO',$sql):str_replace('INSERT INTO','INSERT IGNORE INTO',$sql);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) { http_response_code(403); $error='The security token expired. Reload the page and try again.'; }
    $first=trim($_POST['first_name'] ?? ''); $last=trim($_POST['last_name'] ?? '');
    $email=strtolower(trim($_POST['email'] ?? '')); $password=$_POST['password'] ?? ''; $confirm=$_POST['confirm_password'] ?? '';
    $accepted=isset($_POST['accept_terms']);
    if ($error) {}
    elseif (!$first || !$last || !$email || !$password) $error='Please complete all required fields.';
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Please enter a valid email.';
    elseif ($password !== $confirm) $error='Passwords do not match.';
    elseif (strlen($password)<8) $error='Password must be at least 8 characters.';
    elseif (!$accepted) $error='You must accept the Terms and Privacy Policy to create a Beyond ID.';
    else {
        $accountLimit=beyond_rate_limit_consume($pdo,'web-register-account',$email,3,3600,3600);
        $ipLimit=beyond_rate_limit_consume($pdo,'web-register-ip','',10,3600,3600);
        if(!$accountLimit['allowed']||!$ipLimit['allowed']){
            $retryAfter=max($accountLimit['retry_after'],$ipLimit['retry_after']);
            http_response_code(429);header('Retry-After: '.$retryAfter);
            $error='Too many registration attempts. Please try again later.';
        } else {
        $check=$pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $check->execute([$email]);
        if ($check->fetch()) $error='Account already exists.';
        else {
            $hash=password_hash($password,PASSWORD_DEFAULT); $token=bin2hex(random_bytes(32)); $role=beyond_signup_role($email);
            $stmt=$pdo->prepare("INSERT INTO users (first_name,last_name,name,email,password,password_hash,email_verified,verification_token,verification_sent_at,role,status) VALUES (?,?,?,?,?,?,?,?,?,?,'active')");
            $stmt->execute([$first,$last,trim($first.' '.$last),$email,$hash,$hash,$isSqlite?1:0,$isSqlite?null:$token,$isSqlite?null:date('Y-m-d H:i:s'),$role]);
            $uid=(int)$pdo->lastInsertId();
            try { $pdo->prepare("UPDATE users SET terms_accepted_at=?,terms_version='2.1-beta' WHERE id=?")->execute([date('Y-m-d H:i:s'),$uid]); } catch(Throwable $e) {}
            try { $pdo->prepare("INSERT INTO profiles (user_id) VALUES (?)")->execute([$uid]); } catch(Throwable $e) {}
            try { $pdo->prepare($insertIgnore("INSERT INTO beyond_wallets (user_id,balance,currency,status) VALUES (?,0,'BITS','active')"))->execute([$uid]); } catch(Throwable $e) {}
            try { $pdo->prepare($insertIgnore("INSERT INTO user_preferences (user_id) VALUES (?)"))->execute([$uid]); } catch(Throwable $e) {}
            create_notification($pdo,$uid,'Welcome to Beyond OS','Complete your Beyond profile to personalize every connected app and earn your first bit$.','/beyond-id/dashboard/profile.php','welcome');
            log_activity($pdo,$uid,'register_terms_accepted_v2.1-beta');
            send_beyond_id_admin_signup_alert(['id'=>$uid,'first_name'=>$first,'last_name'=>$last,'email'=>$email,'created_at'=>date('Y-m-d H:i:s')], 'Beyond ID web signup');
            if ($isSqlite) $success='Your local Beyond ID was created. You can sign in now.';
            elseif (send_verification_email($email,$token,'beyond_id',trim($first.' '.$last))) $success='Check your inbox. We sent a verification link to '.$email.'.';
            else $success='Your account was created, but the verification message could not be sent. Please contact support.';
        }
        }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Beyond ID</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% 15%,#2537a6 0,transparent 32%),radial-gradient(circle at 85% 80%,#8a1768 0,transparent 34%),#070711;color:#fff;font-family:Inter,system-ui,sans-serif}.page{min-height:100vh;display:grid;grid-template-columns:1fr 1fr;max-width:1250px;margin:auto;padding:38px;gap:50px;align-items:center}.story h1{font-size:clamp(54px,8vw,94px);line-height:.9;letter-spacing:-.07em;margin:18px 0}.story h1 span{display:block;background:linear-gradient(90deg,#6ec8ff,#a77bff,#ff63a9);color:transparent;background-clip:text}.story p{color:#c5c5d5;font-size:20px;line-height:1.6;max-width:520px}.logo{font-weight:900}.card{max-width:500px;width:100%;justify-self:end;padding:34px;border:1px solid #383849;border-radius:28px;background:rgba(17,17,31,.86);backdrop-filter:blur(22px)}.card h2{font-size:34px;margin:0 0 8px}.sub{color:#a7a7ba}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{margin:12px 0}.field label{font-size:13px;font-weight:800;display:block;margin-bottom:7px}.field input:not([type=checkbox]){width:100%;padding:15px;border-radius:13px;border:1px solid #39394e;background:#0b0b17;color:white;font-size:15px}.check{display:flex;gap:10px;align-items:flex-start;color:#b9b9ca;font-size:13px;line-height:1.5;margin:18px 0}.check input{margin-top:3px;accent-color:#8b5cf6}.check a,.login a{color:#c4a7ff}.social{display:grid;gap:10px;margin:18px 0}.social a{display:flex;align-items:center;justify-content:center;min-height:48px;padding:12px;border-radius:13px;text-decoration:none;font-weight:850}.social .google{background:#fff;color:#202124;border:1px solid #ddd}.social .disabled{opacity:.72;cursor:not-allowed;background:#262638;color:#b9b9ca;border-color:#45455a}.social-note{margin:-2px 0 14px;color:#aaaabd;font-size:12px;line-height:1.45;text-align:center}.social .meta{background:#1877f2;color:#fff}.divider{display:flex;align-items:center;gap:12px;color:#8f8fa3;font-size:12px;margin:16px 0}.divider:before,.divider:after{content:'';height:1px;flex:1;background:#343447}button{width:100%;padding:16px;border:0;border-radius:14px;background:linear-gradient(100deg,#5b8cff,#a044f2,#e9449f);color:white;font-weight:900;font-size:15px}.error,.success{padding:13px;border-radius:13px;margin:14px 0}.error{background:#651a25}.success{background:#124c38}.login{text-align:center;color:#9999ad;margin-top:18px}@media(max-width:800px){.page{grid-template-columns:1fr;padding:24px}.story h1{font-size:56px}.story p{font-size:17px}.card{justify-self:stretch}.grid{grid-template-columns:1fr}}
</style></head><body><main class="page"><section class="story"><div class="logo">BEYOND OS · BETA</div><h1>One account.<span>A universe of apps.</span></h1><p>Create your Beyond ID, verify your email, and carry your profile and bit$ wallet through every experience.</p></section><section class="card"><h2>Create Beyond ID</h2><p class="sub">Your secure key to the ecosystem.</p><?php if($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?><?php if(!$success): ?><div class="social"><?php if ($googleEnabled): ?><a class="google" href="oauth-start.php?provider=google" aria-label="Sign up with Google">G&nbsp; Sign up with Google</a><?php else: ?><span class="google disabled" aria-disabled="true">G&nbsp; Sign up with Google</span><?php endif; ?><?php if ($metaEnabled): ?><a class="meta" href="oauth-start.php?provider=meta">f&nbsp; Sign up with Facebook / Instagram</a><?php endif; ?></div><?php if (!$googleEnabled): ?><p class="social-note">Google sign-up will activate after the Google client ID and secret are added to <code>var/config/live.php</code>.</p><?php endif; ?><div class="divider">or create with email</div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="grid"><div class="field"><label>First name</label><input name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" required></div><div class="field"><label>Last name</label><input name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" required></div></div><div class="field"><label>Email address</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div><div class="field"><label>Password</label><input type="password" name="password" minlength="8" required></div><div class="field"><label>Confirm password</label><input type="password" name="confirm_password" minlength="8" required></div><label class="check"><input type="checkbox" name="accept_terms" value="1" required><span>I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and acknowledge the <a href="privacy.php" target="_blank">Privacy Policy</a>.</span></label><button>Create account and verify email →</button></form><?php endif; ?><p class="login"><a href="login.php">Already have a Beyond ID? Sign in</a></p></section></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
