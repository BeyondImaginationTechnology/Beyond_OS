<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mobile-auth.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/mail.php';

$token = trim((string)($_GET['token'] ?? ''));
$returnTo = safe_return_path((string)($_GET['return'] ?? ''), '');
$ok = false;
$message = 'This link is invalid, expired, or has already been used.';
$continueHref = 'login.php';
$continueLabel = 'Continue to sign in';

/** A verified email link may complete a first-time native sign-in. */
function beyond_mobile_completion_return(string $returnTo): bool
{
    $parts = parse_url($returnTo);
    if (!is_array($parts) || ($parts['path'] ?? '') !== '/beyond-id/auth/mobile-complete.php') return false;
    parse_str((string)($parts['query'] ?? ''), $query);
    $scheme = strtolower(trim((string)($query['scheme'] ?? '')));
    $challenge = trim((string)($query['code_challenge'] ?? ''));
    return beyond_api_client_for_scheme($scheme) !== []
        && preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge) === 1;
}

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT id,email,first_name,last_name,name,role,status FROM users WHERE verification_token=? AND email_verified=0 AND verification_sent_at>=? LIMIT 1');
    $stmt->execute([$token, gmdate('Y-m-d H:i:s', time() - 86400)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $pdo->prepare('UPDATE users SET email_verified=1,email_verified_at=CURRENT_TIMESTAMP,verification_token=NULL WHERE id=?')->execute([$user['id']]);
        log_activity($pdo, (int)$user['id'], 'email_verified');
        send_welcome_email((string)$user['email'], trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? '')));
        $ok = true;

        if ($returnTo !== '' && beyond_mobile_completion_return($returnTo)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email'] = (string)$user['email'];
            $_SESSION['name'] = (string)($user['name'] ?? trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? '')));
            $_SESSION['role'] = (string)($user['role'] ?? 'user');
            $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => (string)$user['email'], 'role' => $_SESSION['role']];
            register_session($pdo, (int)$user['id']);
            $continueHref = $returnTo;
            $continueLabel = 'Continue to Daily Breath';
            $message = 'Your email is verified. Continue to finish Daily Breath sign-in.';
        } elseif ($returnTo !== '') {
            $continueHref .= '?return=' . rawurlencode($returnTo);
            $message = 'Your email is verified. Sign in to continue to the app you came from.';
        } else {
            $message = 'Your email is verified. Sign in to complete your profile and earn 100 bit$.';
        }
    }
}
?><!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verify email | Beyond ID</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#2f216a,#080812 55%);color:#fff;font-family:system-ui}.card{max-width:520px;margin:24px;padding:38px;border:1px solid #39394e;border-radius:28px;background:#141421;text-align:center}.mark{width:70px;height:70px;margin:auto;display:grid;place-items:center;border-radius:50%;background:<?=$ok?'#1e9e69':'#933446'?>;font-size:32px}h1{font-size:34px}p{color:#b8b8ca;line-height:1.6}a{display:inline-block;margin:8px;padding:14px 22px;border-radius:999px;background:linear-gradient(90deg,#5b8cff,#a044f2,#e9449f);color:white;text-decoration:none;font-weight:800}</style></head><body><main class="card"><div class="mark"><?=$ok?'✓':'!'?></div><h1><?=$ok?'You’re verified':'Verification unavailable'?></h1><p><?=e($message)?></p><a href="<?=$ok?e($continueHref):'resend-verification.php'?>"><?=$ok?e($continueLabel):'Request a new link'?> →</a></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
