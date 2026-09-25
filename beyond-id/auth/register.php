<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/social-auth.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/admin-alerts.php';
require_once __DIR__ . '/../../config/roles.php';
$error=(string)($_SESSION['oauth_error'] ?? ''); unset($_SESSION['oauth_error']); $success='';
$version=require __DIR__ . '/../config/version.php';
$returnTo=safe_return_path((string)($_GET['return'] ?? $_SESSION['beyond_return_to'] ?? ''), '');
if($returnTo!=='') $_SESSION['beyond_return_to']=$returnTo;
$providers=[
    'google'=>['Google','G'],
    'github'=>['GitHub','⌘'],
    'apple'=>['Apple','●'],
];
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
            create_notification($pdo,$uid,'Welcome to Beyond ID','Complete your profile to personalize your experience across BIT OS and Beyond apps.','/beyond-id/dashboard/profile.php','welcome');
            log_activity($pdo,$uid,'register_terms_accepted_v2.1-beta');
            send_beyond_id_admin_signup_alert(['id'=>$uid,'first_name'=>$first,'last_name'=>$last,'email'=>$email,'created_at'=>date('Y-m-d H:i:s')], 'Beyond ID web signup');
            if ($isSqlite) $success='Your local Beyond ID was created. You can sign in now.';
            elseif (send_verification_email($email,$token,'beyond_id',trim($first.' '.$last),$returnTo)) $success='Check your inbox. We sent a verification link to '.$email.'.';
            else $success='Your account was created, but the verification message could not be sent. Please contact support.';
        }
        }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Beyond ID</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% 15%,#2537a6 0,transparent 32%),radial-gradient(circle at 85% 80%,#8a1768 0,transparent 34%),#070711;color:#fff;font-family:Inter,system-ui,sans-serif}.page{min-height:100vh;display:grid;grid-template-columns:1fr 1fr;max-width:1250px;margin:auto;padding:38px;gap:50px;align-items:center}.story h1{font-size:clamp(54px,8vw,94px);line-height:.9;letter-spacing:-.07em;margin:18px 0}.story h1 span{display:block;background:linear-gradient(90deg,#6ec8ff,#a77bff,#ff63a9);color:transparent;background-clip:text}.story p{color:#c5c5d5;font-size:20px;line-height:1.6;max-width:520px}.logo{font-weight:900}.card{max-width:500px;width:100%;justify-self:end;padding:34px;border:1px solid #383849;border-radius:28px;background:rgba(17,17,31,.86);backdrop-filter:blur(22px)}.card h2{font-size:34px;margin:0 0 8px}.sub{color:#a7a7ba}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{margin:12px 0}.field label{font-size:13px;font-weight:800;display:block;margin-bottom:7px}.field input:not([type=checkbox]){width:100%;padding:15px;border-radius:13px;border:1px solid #39394e;background:#0b0b17;color:white;font-size:15px}.check{display:flex;gap:10px;align-items:flex-start;color:#b9b9ca;font-size:13px;line-height:1.5;margin:18px 0}.check input{margin-top:3px;accent-color:#8b5cf6}.check a,.login a{color:#c4a7ff}.social{display:grid;gap:10px;margin:18px 0}.social a{display:flex;align-items:center;justify-content:center;min-height:48px;padding:12px;border-radius:13px;text-decoration:none;font-weight:850}.social .google{background:#fff;color:#202124;border:1px solid #ddd}.social .disabled{opacity:.72;cursor:not-allowed;background:#262638;color:#b9b9ca;border-color:#45455a}.social-note{margin:-2px 0 14px;color:#aaaabd;font-size:12px;line-height:1.45;text-align:center}.social .meta{background:#1877f2;color:#fff}.divider{display:flex;align-items:center;gap:12px;color:#8f8fa3;font-size:12px;margin:16px 0}.divider:before,.divider:after{content:'';height:1px;flex:1;background:#343447}button{width:100%;padding:16px;border:0;border-radius:14px;background:linear-gradient(100deg,#5b8cff,#a044f2,#e9449f);color:white;font-weight:900;font-size:15px}.error,.success{padding:13px;border-radius:13px;margin:14px 0}.error{background:#651a25}.success{background:#124c38}.login{text-align:center;color:#9999ad;margin-top:18px}@media(max-width:800px){.page{grid-template-columns:1fr;padding:24px}.story h1{font-size:56px}.story p{font-size:17px}.card{justify-self:stretch}.grid{grid-template-columns:1fr}}
</style><style>.social a:focus-visible,button:focus-visible,.check input:focus-visible,.login a:focus-visible{outline:3px solid #c4b5fd;outline-offset:3px}.check a:focus-visible{outline:3px solid #c4b5fd;outline-offset:3px}.social-help{margin:-5px 0 16px;color:#aaaabd;font-size:13px;line-height:1.5;text-align:center}@media(max-width:600px){.page{gap:18px;padding:18px}.card{padding:24px 18px}.field input:not([type=checkbox]){font-size:16px}.social a,button{min-height:48px}.story p{font-size:16px}}</style></head><body><main class="page"><section class="story"><div class="logo">BEYOND ID · <?= e($version) ?></div><h1>One account.<span>A universe of apps.</span></h1><p>Create your Beyond ID, verify your email, and use it across BIT OS and Beyond apps.</p></section><section class="card"><h2>Create Beyond ID</h2><p class="sub">Your account for BIT OS and Beyond apps.</p><?php if($error): ?><div class="error" role="alert" aria-live="assertive"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="success" role="status" aria-live="polite"><?= e($success) ?></div><?php endif; ?><?php if(!$success): ?><div class="social"><?php foreach ($providers as $provider => [$label, $icon]): ?><?php if (beyond_social_enabled($provider)): ?><a class="<?= e($provider) ?>" href="oauth-start.php?provider=<?= e($provider) ?>&amp;return=<?= rawurlencode($returnTo) ?>"><?= e($icon) ?>&nbsp; Continue with <?= e($label) ?></a><?php else: ?><span class="disabled" aria-disabled="true"><?= e($icon) ?>&nbsp; Continue with <?= e($label) ?></span><?php endif; ?><?php endforeach; ?></div><p class="social-help">First-time provider sign-in creates a Beyond ID. If the provider confirms the same verified email as an existing account, it connects to that account.</p><?php if (array_filter(array_keys($providers), static fn(string $provider): bool => !beyond_social_enabled($provider))): ?><p class="social-note">Unavailable providers will activate when their client ID and secret are configured.</p><?php endif; ?><div class="divider">or create with email</div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="grid"><div class="field"><label for="first-name">First name</label><input id="first-name" name="first_name" autocomplete="given-name" value="<?= e($_POST['first_name'] ?? '') ?>" required></div><div class="field"><label for="last-name">Last name</label><input id="last-name" name="last_name" autocomplete="family-name" value="<?= e($_POST['last_name'] ?? '') ?>" required></div></div><div class="field"><label for="register-email">Email address</label><input id="register-email" type="email" name="email" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required></div><div class="field"><label for="register-password">Password</label><input id="register-password" type="password" name="password" autocomplete="new-password" minlength="8" required></div><div class="field"><label for="confirm-register-password">Confirm password</label><input id="confirm-register-password" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></div><label class="check"><input type="checkbox" name="accept_terms" value="1" required><span>I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and acknowledge the <a href="privacy.php" target="_blank">Privacy Policy</a>.</span></label><button>Create account and verify email →</button></form><?php endif; ?><p class="login"><a href="login.php<?= $returnTo !== '' ? '?return=' . rawurlencode($returnTo) : '' ?>">Already have a Beyond ID? Sign in</a></p></section></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
