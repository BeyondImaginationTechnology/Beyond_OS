<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/social-auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$returnTo = (string)($_SESSION['beyond_return_to'] ?? '');
$requestedReturn = (string)($_GET['return'] ?? '');
if ($requestedReturn !== '') {
    $returnTo = safe_return_path($requestedReturn, '');
    if ($returnTo !== '') $_SESSION['beyond_return_to'] = $returnTo;
}

$experiences = [
    'beyond-catering' => ['Beyond Catering', 'Your restaurant command center', '🍽️', '#ff7a18', '#ffb347'],
    'beyond-math' => ['Beyond Math', 'Learn, solve and earn bit$', '🧮', '#19c6ff', '#7357ff'],
    'coding-school' => ['Coding School', 'Build coding skills one project at a time', '💻', '#6d4aff', '#20b8d8'],
    'dailybreath' => ['DailyBreath', 'A quiet space for faith and wellness', '🌿', '#76a83b', '#3d7d55'],
    'beyond-baby-names' => ['Beyond Baby Names', 'Continue your name discovery', '♡', '#9d4edd', '#ff6cae'],
    'beyond-health' => ['Beyond Health', 'Your connected wellness journey', '♥', '#ff2638', '#a50017'],
    'beyond-tv' => ['Beyond TV', 'Your channels, lists and discoveries', '📺', '#8b3dff', '#247bff'],
    'beyond-french' => ['Beyond French', 'Your daily language journey', '🇫🇷', '#1f6fff', '#ef3340'],
    'beyond-tattoo' => ['Beyond Tattoo', 'Your story, art and healing journey', '✦', '#9238ff', '#ee42b7'],
    'beyond-space' => ['Beyond Space', 'Return to the universe', '🚀', '#3b82f6', '#8b5cf6'],
    'beyond-ancient' => ['Beyond Ancient', 'Step back into living history', '🏺', '#d9a441', '#704214'],
    'beyond-health/beyond-skate' => ['Beyond Skate', 'Learn tricks, upload tries and keep progressing', '🛹', '#28b9ff', '#9658ff'],
    'api-hub' => ['Beyond API Hub', 'Build on the Beyond ecosystem', '</>', '#08b6a3', '#246bfe'],
    'beyond-ai' => ['Jaguar AI', 'Intelligence built beyond', 'J', '#b34cff', '#f65daa'],
    'jaguar' => ['Llama Jaguar', 'AI fuel for the BIT ecosystem', 'J', '#8f38f4', '#e83bc7'],
];

$experience = ['Beyond OS', 'One ID for every possibility', 'B', '#6d66ff', '#e044a7'];
$requestedApp = strtolower(trim((string)($_GET['app'] ?? '')));
if ($requestedApp !== '' && isset($experiences[$requestedApp])) $experience = $experiences[$requestedApp];
foreach ($experiences as $slug => $candidate) {
    if (str_contains($returnTo, '/' . $slug . '/')) {
        $experience = $candidate;
        break;
    }
}

[$product, $tagline, $mark, $accent, $accent2] = $experience;
$isBeyondFrench = $product === 'Beyond French';
$error = (string)($_SESSION['oauth_error'] ?? '');
unset($_SESSION['oauth_error']);
$version = require __DIR__ . '/../config/version.php';

$providers = [
    'google' => ['Google'],
    'github' => ['GitHub'],
    'apple' => ['Apple'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in to <?= e($product) ?> | Beyond ID</title>
<style>
:root{--a:<?= e($accent) ?>;--b:<?= e($accent2) ?>}*{box-sizing:border-box}body{margin:0;min-height:100vh;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:radial-gradient(circle at 15% 12%,color-mix(in srgb,var(--a) 25%,transparent),transparent 34%),radial-gradient(circle at 85% 82%,color-mix(in srgb,var(--b) 22%,transparent),transparent 36%),#070711}.page{position:relative;min-height:100vh;display:grid;grid-template-columns:1.05fr .95fr;max-width:1260px;margin:auto;padding:34px}.back{position:absolute;top:34px;left:34px;z-index:5;display:inline-flex;align-items:center;min-height:44px;padding:11px 15px;border:1px solid rgba(255,255,255,.24);border-radius:999px;background:rgba(7,7,17,.52);backdrop-filter:blur(10px);color:#fff;text-decoration:none;font-weight:850;white-space:nowrap}.os{position:absolute;top:46px;right:34px;z-index:5;font-size:13px;font-weight:900;letter-spacing:.12em;white-space:nowrap;text-shadow:0 2px 12px rgba(0,0,0,.65)}.story{display:flex;flex-direction:column;justify-content:center;padding:30px 5vw 30px 20px}.mark{width:82px;height:82px;border-radius:26px;background:linear-gradient(135deg,var(--a),var(--b));display:grid;place-items:center;font-size:34px}.story h1{font-size:clamp(50px,7vw,84px);line-height:.94;letter-spacing:-.06em;margin:25px 0 18px}.story h1 span{display:block;color:var(--a)}.story p{font-size:20px;color:#c5c5d5;line-height:1.55}.side{display:grid;place-items:center;padding:30px}.card{width:min(100%,480px);padding:34px;border:1px solid #383849;border-radius:28px;background:rgba(17,17,31,.92);box-shadow:0 24px 80px rgba(0,0,0,.2)}.card h2{font-size:34px;margin:0}.sub{margin:8px 0 22px;color:#a9a9bd;line-height:1.5}.providers{display:grid;gap:12px}.provider{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;min-height:52px;padding:13px 18px;border:1px solid #44445a;border-radius:14px;color:#fff;text-decoration:none;font-size:15px;font-weight:850;line-height:1.2}.provider-icon{display:grid;width:22px;place-items:center;font-size:17px;font-weight:900}.provider-icon img{display:block;width:22px;height:22px;object-fit:contain}.provider.google{border-color:#ddd;background:#fff;color:#202124}.provider.instagram{border-color:#d62976;background:linear-gradient(90deg,#833ab4,#fd1d1d,#fcb045)}.provider.github{border-color:#4b4b58;background:#24242d}.provider.apple{padding:0;border:0;background:transparent}.provider.apple img{display:block;width:min(100%,375px);height:auto}.provider.apple.disabled{border:0;background:transparent}.provider.disabled{border-color:#45455a;background:#262638;color:#b9b9ca;cursor:not-allowed;opacity:.72}.setup-note{margin:18px 0 0;color:#8f8fa3;font-size:12px;line-height:1.5;text-align:center}.legal{margin:22px 0 0;padding-top:18px;border-top:1px solid #343447;color:#8f8fa3;font-size:12px;line-height:1.55;text-align:center}.legal a{color:#c4b5fd}.error{padding:12px;margin:0 0 16px;border-radius:12px;background:#641b29}.daily-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.daily-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 18px;border-radius:999px;color:#fff;text-decoration:none;font-size:13px;font-weight:950}.daily-actions .test-bible{background:linear-gradient(90deg,var(--a),var(--b))}.daily-actions .read-verse{border:1px solid #44445a;background:#151523}.daily-note{color:#a9a9bd;font-size:12px;line-height:1.5}@media(max-width:820px){.page{grid-template-columns:1fr;padding:20px}.back{top:18px;left:20px;min-height:40px;padding:8px 12px;font-size:13px}.os{top:29px;right:20px;font-size:11px}.story{padding:72px 8px 10px}.story h1{font-size:clamp(46px,14vw,64px)}.side{padding:10px 0 28px}.card{padding:25px}}
</style>
</head>
<body>
<main class="page">
<a class="back" href="../../" aria-label="Back to home">← Back to Home</a>
<div class="os">BEYOND ID <?= e($version) ?></div>
<section class="story">
<div class="mark"><?= e($mark) ?></div>
<?php if ($isBeyondFrench): ?>
<h1>Welcome to <span>Beyond French</span></h1>
<p>Learn French as a guest, then use Beyond ID to sync your progress across devices. All Academy lessons are free in the 1.2 beta.</p>
<div class="daily-actions"><a class="test-bible" href="../../beyond-french/dictionary.php">Open free Dictionary + Bible</a><a class="read-verse" href="../../beyond-french/">Back to Beyond French</a></div>
<p class="daily-note">No Beyond ID is required for written translation, dictionary search, pronunciation guides, or Bible access.</p>
<?php else: ?>
<h1>Welcome to <span><?= e($product) ?></span></h1>
<p><?= e($tagline) ?>. Your Beyond ID keeps your profile, progress, and bit$ connected.</p>
<?php endif; ?>
</section>
<section class="side">
<div class="card">
<h2>Sign in</h2>
<p class="sub">Choose an account to continue to <?= e($product) ?>.</p>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<div class="providers">
<?php foreach ($providers as $provider => [$label]): ?>
<?php $icon = match ($provider) {
    'github' => '<img src="../assets/icons/github-invertocat-white.png" alt="">',
    'apple' => '<img src="../assets/icons/apple-continue-button.png" alt="Continue with Apple">',
    default => 'G',
}; ?>
<?php if (beyond_social_enabled($provider)): ?>
<a class="provider <?= e($provider) ?>" href="oauth-start.php?provider=<?= e($provider) ?>&amp;return=<?= rawurlencode($returnTo) ?>"><?php if ($provider === 'apple'): ?><?= $icon ?><?php else: ?><span class="provider-icon" aria-hidden="true"><?= $icon ?></span>Continue with <?= e($label) ?><?php endif; ?></a>
<?php else: ?>
<span class="provider <?= e($provider) ?> disabled" aria-disabled="true"><?php if ($provider === 'apple'): ?><?= $icon ?><?php else: ?><span class="provider-icon" aria-hidden="true"><?= $icon ?></span>Continue with <?= e($label) ?><?php endif; ?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php if (array_filter(array_keys($providers), static fn(string $provider): bool => !beyond_social_enabled($provider))): ?>
<p class="setup-note">Unavailable providers will activate when their app credentials are configured.</p>
<?php endif; ?>
<p class="legal">Your first sign-in creates your Beyond ID. By continuing, you agree to the <a href="terms.php">Terms</a> and acknowledge the <a href="privacy.php">Privacy Policy</a>.</p>
</div>
</section>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script>
</body>
</html>
