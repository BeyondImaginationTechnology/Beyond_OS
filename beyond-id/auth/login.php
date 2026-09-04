<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/remember-me.php';
require_once __DIR__ . '/../includes/social-auth.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Keep the shared home link and Beyond OS label on opposite sides of the login header.
$instagramLoginHtml = '';
ob_start(static function (string $html) use (&$instagramLoginHtml): string {
    $layoutFix = '.page{position:relative}.story>a[aria-label="Back to home"]{z-index:5;display:inline-flex;align-items:center;min-height:44px;white-space:nowrap}.os{z-index:5;left:auto;right:34px;letter-spacing:.12em;white-space:nowrap;text-shadow:0 2px 12px rgba(0,0,0,.65)}@media(max-width:820px){.story>a[aria-label="Back to home"]{top:18px!important;left:20px!important;min-height:40px;padding:8px 12px!important;font-size:13px}.os{top:29px;right:20px;font-size:11px}body.dailybreath-login .story>a[aria-label="Back to home"]{left:14px!important}body.dailybreath-login .os{right:14px}}';
    $layoutFix .= "body.dailybreath-login{background-color:#092016;background-image:linear-gradient(115deg,rgba(1,21,12,.62),rgba(4,35,20,.38) 43%,rgba(2,20,11,.68)),radial-gradient(circle at 70% 14%,rgba(242,208,129,.24),transparent 31%),url('../../assets/dailybreath-login-background.webp');background-size:cover;background-position:center;background-attachment:fixed}body.dailybreath-login:before{content:'';position:fixed;inset:0;pointer-events:none;background:linear-gradient(180deg,rgba(0,0,0,.08),rgba(0,16,8,.34));backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)}body.dailybreath-login .page{position:relative;z-index:1}body.dailybreath-login .card{background:linear-gradient(145deg,rgba(255,255,255,.98),rgba(247,249,244,.94));box-shadow:0 28px 80px rgba(0,18,8,.48),inset 0 1px 0 #fff}.daily-login-hero{background:linear-gradient(145deg,rgba(20,70,42,.90),rgba(4,35,19,.84));box-shadow:0 25px 75px rgba(0,15,6,.52),inset 0 1px 0 rgba(255,255,255,.13)}";
    $html = str_replace(['BEYOND OS 2.4 BETA','BEYOND OS 2.4'], ['BEYOND OS · BETA','BEYOND OS'], $html);
    $html = str_replace('</style>', $layoutFix . '</style>', $html);
    return str_replace('<div class="divider">or use email</div>', $instagramLoginHtml . '<div class="divider">or use email</div>', $html);
});

$returnTo = (string)($_SESSION['beyond_return_to'] ?? '');
$requestedReturn = (string)($_GET['return'] ?? $_POST['return'] ?? '');
if ($requestedReturn !== '') {
    $returnTo = safe_return_path($requestedReturn, '');
    if ($returnTo !== '') $_SESSION['beyond_return_to'] = $returnTo;
}
$experiences = [
    'beyond-catering' => ['Beyond Catering','Your restaurant command center','🍽️','#ff7a18','#ffb347'],
    'beyond-math' => ['Beyond Math','Learn, solve and earn bit$','🧮','#19c6ff','#7357ff'],
    'coding-school' => ['Coding School','Build coding skills one project at a time','💻','#6d4aff','#20b8d8'],
    'dailybreath' => ['DailyBreath','A quiet space for faith and wellness','🌿','#76a83b','#3d7d55'],
    'beyond-baby-names' => ['Beyond Baby Names','Continue your name discovery','♡','#9d4edd','#ff6cae'],
    'beyond-health' => ['Beyond Health','Your connected wellness journey','❤️','#ff2638','#a50017'],
    'beyond-tv' => ['Beyond TV','Your channels, lists and discoveries','📺','#8b3dff','#247bff'],
    'beyond-french' => ['Beyond French','Your daily language journey','🇫🇷','#1f6fff','#ef3340'],
    'beyond-tattoo' => ['Beyond Tattoo','Your story, art and healing journey','✦','#9238ff','#ee42b7'],
    'beyond-space' => ['Beyond Space','Return to the universe','🚀','#3b82f6','#8b5cf6'],
    'beyond-ancient' => ['Beyond Ancient','Step back into living history','🏺','#d9a441','#704214'],
    'beyond-health/beyond-skate' => ['Beyond Skate','Learn tricks, upload tries and keep progressing','🛹','#28b9ff','#9658ff'],
    'api-hub' => ['Beyond API Hub','Build on the Beyond ecosystem','</>','#08b6a3','#246bfe'],
];
$experience = ['Beyond OS','One ID for every possibility','B','#6d66ff','#e044a7'];
$requestedApp = strtolower(trim((string)($_GET['app'] ?? $_POST['app'] ?? '')));
if ($requestedApp !== '' && isset($experiences[$requestedApp])) $experience = $experiences[$requestedApp];
foreach ($experiences as $slug => $candidate) if (str_contains($returnTo, '/' . $slug . '/')) { $experience = $candidate; break; }
[$product,$tagline,$mark,$accent,$accent2] = $experience;
$isDailyBreath = $product === 'DailyBreath';
$isBeyondFrench = $product === 'Beyond French';
$dailyVerse = null;
$dailyVerseBibleUrl = '../../dailybreath/bible.php';
if ($isDailyBreath) {
    require_once __DIR__ . '/../../dailybreath/includes/verse-of-day.php';
    $dailyVerse = dailybreath_verse_of_day($pdo, (string)($_SESSION['locale'] ?? 'en'));
    $dailyVerseBibleUrl = dailybreath_bible_url($dailyVerse, '../..');
}
$error = (string)($_SESSION['oauth_error'] ?? '');
unset($_SESSION['oauth_error']);
$googleEnabled = beyond_social_enabled('google');
$metaEnabled = beyond_social_enabled('meta');
$instagramEnabled = beyond_social_enabled('instagram');
$instagramHref = 'oauth-start.php?provider=instagram&amp;return=' . rawurlencode($returnTo);
$instagramLoginHtml = '<div class="social instagram-only">' . ($instagramEnabled
    ? '<a class="instagram" href="' . $instagramHref . '">◎&nbsp; Continue with Instagram</a>'
    : '<span class="instagram disabled" aria-disabled="true">◎&nbsp; Continue with Instagram</span>') . '</div>'
    . (!$instagramEnabled ? '<p class="social-note">Instagram sign-in activates after its App ID and secret are added to <code>var/config/live.php</code>.</p>' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $accountLimit = beyond_rate_limit_consume($pdo, 'web-login-account', $email, 5, 900, 900);
        $ipLimit = beyond_rate_limit_consume($pdo, 'web-login-ip', '', 30, 900, 1800);
        if (!$accountLimit['allowed'] || !$ipLimit['allowed']) {
            $retryAfter = max($accountLimit['retry_after'], $ipLimit['retry_after']);
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $error = 'Too many sign-in attempts. Please try again later.';
        } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $valid = false;
        $matchedHash = '';
        if ($user) {
            foreach (array_unique(array_filter([(string)($user['password_hash'] ?? ''), (string)($user['password'] ?? '')])) as $candidateHash) {
                if (password_verify((string)$password, $candidateHash)) { $valid = true; $matchedHash = $candidateHash; break; }
            }
        }
        if ($valid && ($matchedHash !== (string)($user['password_hash'] ?? '') || password_needs_rehash($matchedHash, PASSWORD_DEFAULT))) {
            $freshHash = password_hash((string)$password, PASSWORD_DEFAULT);
            try { $pdo->prepare('UPDATE users SET password_hash=?, password=? WHERE id=?')->execute([$freshHash,$freshHash,$user['id']]); }
            catch (Throwable $exception) { $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$freshHash,$user['id']]); }
        }
        if ($valid && empty($user['email_verified']) && empty($user['email_verified_at']) && !empty($user['verification_token'])) {
            $error = 'Verify your email before signing in. Check your inbox for the verification link.';
        } elseif ($valid && ($user['status'] ?? 'active') === 'active') {
            beyond_rate_limit_clear($pdo, 'web-login-account', $email);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['locale'] = $user['preferred_locale'] ?? 'en';
            $_SESSION['user'] = ['id'=>(int)$user['id'],'email'=>$user['email'],'role'=>$_SESSION['role']];
            register_session($pdo, (int)$user['id']);
            beyondRememberForget($pdo);
            beyondRememberIssue($pdo, (int)$user['id']);
            try { $pdo->prepare('UPDATE users SET last_login_at=?,last_login_ip=? WHERE id=?')->execute([date('Y-m-d H:i:s'),$_SERVER['REMOTE_ADDR'] ?? null,$user['id']]); } catch (Throwable $exception) {}
            log_activity($pdo, (int)$user['id'], 'login');
            $destination = safe_return_path($_SESSION['beyond_return_to'] ?? null, '../dashboard/');
            unset($_SESSION['beyond_return_to']);
            header('Location: ' . $destination);
            exit;
        } else {
            $socialAccount = false;
            if ($user) {
                try { $social = $pdo->prepare('SELECT 1 FROM social_identities WHERE user_id=? LIMIT 1'); $social->execute([(int)$user['id']]); $socialAccount = (bool)$social->fetchColumn(); }
                catch (Throwable $exception) {}
            }
            $error = $socialAccount
                ? 'This email uses social sign-in. Continue with the linked provider, or use Forgot password to create or replace your password.'
                : 'That email and password combination was not recognized.';
        }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in to <?= e($product) ?> | Beyond ID</title><style>
:root{--a:<?= e($accent) ?>;--b:<?= e($accent2) ?>}*{box-sizing:border-box}body{margin:0;min-height:100vh;color:#fff;font-family:system-ui;background:radial-gradient(circle at 15% 12%,color-mix(in srgb,var(--a) 25%,transparent),transparent 34%),radial-gradient(circle at 85% 82%,color-mix(in srgb,var(--b) 22%,transparent),transparent 36%),#070711}.page{min-height:100vh;display:grid;grid-template-columns:1.05fr .95fr;max-width:1260px;margin:auto;padding:34px}.story{display:flex;flex-direction:column;justify-content:center;padding:30px 5vw 30px 20px}.os{position:absolute;top:34px;font-weight:900}.mark{width:82px;height:82px;border-radius:26px;background:linear-gradient(135deg,var(--a),var(--b));display:grid;place-items:center;font-size:34px}.story h1{font-size:clamp(50px,7vw,84px);line-height:.94;letter-spacing:-.06em;margin:25px 0 18px}.story h1 span{display:block;color:var(--a)}.story p{font-size:20px;color:#c5c5d5;line-height:1.55}.side{display:grid;place-items:center;padding:30px}.card{width:min(100%,480px);padding:34px;border:1px solid #383849;border-radius:28px;background:rgba(17,17,31,.92)}.card h2{font-size:34px;margin:0}.sub{color:#a9a9bd}.field{margin:14px 0}.field label{display:block;font-size:13px;font-weight:800;margin-bottom:7px}.field input{width:100%;padding:14px;border-radius:13px;border:1px solid #3b3b50;background:#0d0d19;color:#fff;font:inherit}.remember{display:flex;align-items:center;gap:9px;margin:12px 0 18px;color:#d9d9e6;font-size:13px;font-weight:750}.remember input{width:17px;height:17px;accent-color:var(--a)}.submit{width:100%;padding:15px;border:0;border-radius:13px;background:linear-gradient(90deg,var(--a),var(--b));color:#fff;font-weight:900}.social{display:grid;gap:10px;margin:18px 0}.social a{display:flex;align-items:center;justify-content:center;gap:10px;min-height:48px;padding:12px;border-radius:13px;border:1px solid #44445a;color:#fff;text-decoration:none;font-weight:850;background:#151523}.social .google{background:#fff;color:#202124;border-color:#ddd}.social .disabled{opacity:.72;cursor:not-allowed;background:#262638;color:#b9b9ca;border-color:#45455a}.social-note{margin:-2px 0 14px;color:#aaaabd;font-size:12px;line-height:1.45;text-align:center}.social .meta{background:#1877f2;border-color:#1877f2}.social .instagram{background:linear-gradient(90deg,#833ab4,#fd1d1d,#fcb045);border-color:#d62976}.divider{display:flex;align-items:center;gap:12px;color:#8f8fa3;font-size:12px;margin:16px 0}.divider:before,.divider:after{content:'';height:1px;flex:1;background:#343447}.links{display:flex;justify-content:space-between;margin-top:18px}.links a{color:#c4b5fd}.error,.success{padding:12px;border-radius:12px;margin:12px 0}.error{background:#641b29}.success{background:#14523e}.newsletter{margin-top:24px;padding-top:22px;border-top:1px solid #343447}.newsletter h3{margin:0}.newsletter p{color:#a9a9bd;font-size:14px}.honeypot{position:absolute;left:-9999px}body.dailybreath-login{background-image:linear-gradient(90deg,rgba(0,19,8,.36),rgba(0,19,8,.53)),url('../../assets/dailybreath-login-background.webp');background-size:cover;background-position:center top;background-attachment:fixed}body.dailybreath-login .page{grid-template-columns:minmax(0,1.08fr) minmax(360px,.82fr);max-width:1180px;padding:26px}body.dailybreath-login .story{visibility:visible;justify-content:flex-end;padding:90px 3vw 34px 12px}body.dailybreath-login .card{padding:28px;color:#202820;background:rgba(255,255,255,.95);border-color:rgba(255,255,255,.82);box-shadow:0 28px 80px rgba(0,18,8,.5)}body.dailybreath-login .sub,body.dailybreath-login .newsletter p{color:#68736c}body.dailybreath-login .field{margin:11px 0}body.dailybreath-login .field input{color:#202820;background:#fff;border-color:#d7dfd9}body.dailybreath-login .remember{color:#33483b}body.dailybreath-login .newsletter{margin-top:19px;padding-top:17px;border-color:#dce3dd}.daily-login-hero{width:min(100%,650px);padding:27px;border:1px solid rgba(233,255,237,.34);border-radius:28px;background:linear-gradient(145deg,rgba(12,58,38,.78),rgba(7,38,24,.70));box-shadow:0 25px 75px rgba(0,15,6,.48),inset 0 1px 0 rgba(255,255,255,.12);backdrop-filter:blur(20px) saturate(130%);-webkit-backdrop-filter:blur(20px) saturate(130%)}.daily-kicker{display:flex;align-items:center;gap:9px;color:#f2d081;font-size:11px;font-weight:950;letter-spacing:.13em;text-transform:uppercase}.daily-kicker:before{content:'✦';display:grid;place-items:center;width:32px;height:32px;border-radius:11px;color:#153f2b;background:#f2d081}.daily-login-hero h1{margin:17px 0 0;font:500 clamp(39px,6vw,67px)/.96 Georgia,serif;letter-spacing:-.045em}.daily-login-hero blockquote{margin:25px 0 15px;font:500 clamp(25px,3.5vw,40px)/1.28 Georgia,serif;letter-spacing:-.025em;text-shadow:0 3px 18px #00190c}.daily-reference{display:block;color:#f2d081;font-size:14px;font-weight:900}.daily-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.daily-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 18px;border-radius:999px;text-decoration:none;font-size:13px;font-weight:950}.daily-actions .test-bible{color:#153f2b;background:linear-gradient(90deg,#f4d98f,#d7a94e);box-shadow:0 13px 32px rgba(0,0,0,.25)}.daily-actions .read-verse{color:#fff;border:1px solid rgba(255,255,255,.30);background:rgba(255,255,255,.10)}.daily-note{margin:12px 0 0;color:#d6e5d9;font-size:12px;line-height:1.5}.daily-login-hero .os-mobile{display:none}@media(max-width:820px){.page{grid-template-columns:1fr;padding:20px}.story{padding:55px 8px 10px}.side{padding:10px 0}.card{padding:25px}body.dailybreath-login .page{grid-template-columns:1fr;padding:14px}body.dailybreath-login .story{min-height:0;padding:74px 0 8px}body.dailybreath-login{background-position:53% top;background-attachment:scroll}body.dailybreath-login .side{padding:8px 0 24px}body.dailybreath-login .card{padding:22px;border-radius:23px}.daily-login-hero{padding:21px;border-radius:23px}.daily-login-hero h1{font-size:39px}.daily-login-hero blockquote{font-size:25px;margin:20px 0 12px}.daily-actions{display:grid;grid-template-columns:1fr}.daily-actions a{width:100%}.daily-note{font-size:11px}}
</style></head><body class="<?= $isDailyBreath ? 'dailybreath-login' : '' ?>"><main class="page"><section class="story"><a href="../../" aria-label="Back to home" style="position:absolute;top:34px;left:34px;color:#fff;text-decoration:none;font-weight:850;padding:11px 15px;border:1px solid rgba(255,255,255,.24);border-radius:999px;background:rgba(7,7,17,.52);backdrop-filter:blur(10px)">← Back to Home</a><div class="os">BEYOND OS</div><?php if ($isDailyBreath && $dailyVerse): ?><div class="daily-login-hero"><span class="daily-kicker">Verse of the Day</span><h1>Begin with<br>the Word.</h1><blockquote>“<?= e($dailyVerse['text']) ?>”</blockquote><span class="daily-reference"><?= e($dailyVerse['reference']) ?></span><div class="daily-actions"><a class="test-bible" href="../../dailybreath/bible.php?preview=1">Test the Bible — no login</a><a class="read-verse" href="<?= e($dailyVerseBibleUrl) ?>">Read today’s chapter →</a></div><p class="daily-note">Browse all 66 books, switch chapters, and test narration before creating a Beyond ID.</p></div><?php elseif ($isBeyondFrench): ?><div class="mark"><?= e($mark) ?></div><h1>Welcome to <span>Beyond French</span></h1><p>Use Beyond ID for saved progress and premium lessons, or continue free to the public dictionary and Bible.</p><div class="daily-actions"><a class="test-bible" href="../../beyond-french/dictionary.php">Open free Dictionary + Bible</a><a class="read-verse" href="../../beyond-french/">Back to Beyond French</a></div><p class="daily-note">No Beyond ID is required for written translation, dictionary search, pronunciation guides, or Bible access.</p><?php else: ?><div class="mark"><?= e($mark) ?></div><h1>Welcome to <span><?= e($product) ?></span></h1><p><?= e($tagline) ?>. Your Beyond ID keeps your profile, progress, and bit$ connected.</p><?php endif; ?></section><section class="side"><div class="card"><h2>Sign in</h2><p class="sub">Use your Beyond ID across the ecosystem.</p><?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><div class="social"><?php if ($googleEnabled): ?><a class="google" href="oauth-start.php?provider=google&amp;return=<?= rawurlencode($returnTo) ?>" aria-label="Continue with Google">G&nbsp; Continue with Google</a><?php else: ?><span class="google disabled" aria-disabled="true">G&nbsp; Continue with Google</span><?php endif; ?><?php if ($metaEnabled): ?><a class="meta" href="oauth-start.php?provider=meta&amp;return=<?= rawurlencode($returnTo) ?>">f&nbsp; Continue with Facebook</a><?php endif; ?></div><?php if (!$googleEnabled): ?><p class="social-note">Google sign-in will activate after the Google client ID and secret are added to <code>var/config/live.php</code>.</p><?php endif; ?><div class="divider">or use email</div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="app" value="<?= e($requestedApp) ?>"><input type="hidden" name="return" value="<?= e($returnTo) ?>"><div class="field"><label>Email address</label><input type="email" name="email" autocomplete="email" required></div><div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div><p class="remember">You’ll stay signed in on this device until you sign out.</p><button class="submit">Continue to <?= e($product) ?> →</button></form><div class="links"><a href="register.php">Create Beyond ID</a><a href="forgot-password.php">Forgot password?</a></div></div></section></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
