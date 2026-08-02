<?php
require __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$uid = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare('SELECT u.first_name,u.last_name,u.email,u.role,u.email_verified,p.display_name,p.interests,p.goals,p.profile_completed_at,p.username,p.public_profile_enabled,p.creator_verified_at,p.seller_verified_at FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $stmt = $pdo->prepare('SELECT first_name,last_name,email,role,email_verified FROM users WHERE id=?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['profile_completed_at'] = null;
    $user['display_name'] = null;
}

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$name = $user['display_name'] ?: ($user['first_name'] ?: 'there');
$complete = !empty($user['profile_completed_at']);
$balance = 0;
$unreadCount = unread_notification_count($pdo, $uid);
$connectedApps = [];

try {
    $wallet = $pdo->prepare('SELECT balance FROM beyond_wallets WHERE user_id=?');
    $wallet->execute([$uid]);
    $balance = (float)$wallet->fetchColumn();
} catch (Throwable $exception) {
}

try {
    $apps = $pdo->prepare("SELECT app_slug,permissions_json,last_used_at,revoked_at FROM connected_apps WHERE user_id=? ORDER BY COALESCE(last_used_at,'') DESC LIMIT 6");
    $apps->execute([$uid]);
    $connectedApps = $apps->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}

$academyBadges = [];
try {
    require_once __DIR__ . '/../../includes/academy-certificates.php';
    $academyBadges = academy_badges($uid);
} catch (Throwable $exception) {
}

$badges = beyond_badges_for_user($user, $user, $academyBadges);
$publicUrl = !empty($user['username']) ? '/u/' . rawurlencode((string)$user['username']) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Home | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#e9e3ff,transparent 30%),#f7f8fc;color:#202231;font-family:system-ui}.shell{max-width:1180px;margin:auto;padding:22px}.top{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:8px 0 30px}.brand{font-weight:900}.actions{min-width:0;display:flex;gap:9px;overflow-x:auto;scrollbar-width:none}.actions::-webkit-scrollbar{display:none}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:11px 15px;border-radius:13px;background:#fff;color:#292b3b;border:1px solid #dfe1ea;box-shadow:0 5px 18px rgba(37,39,68,.06);text-decoration:none;font-weight:800}.primary{color:#fff;border:0;background:linear-gradient(90deg,#5b6dff,#a044f2,#e9449f)}.hero{display:grid;grid-template-columns:1.25fr .75fr;gap:16px}.welcome,.panel,.app{border:1px solid #e0e2ea;background:#fff;box-shadow:0 18px 55px rgba(45,47,78,.08)}.welcome{padding:32px;border-radius:28px}.welcome h1{font-size:clamp(42px,7vw,72px);letter-spacing:-.06em;line-height:.95;margin:10px 0}.muted{color:#6f7284}.wallet{padding:28px;border-radius:28px;color:#fff;background:linear-gradient(145deg,#182c55,#6f2b82 58%,#c93574);box-shadow:0 18px 55px rgba(98,43,137,.18)}.wallet strong{font-size:46px;display:block;margin:18px 0}.progress{height:9px;border-radius:99px;background:#e5e6ee;overflow:hidden;margin:18px 0}.progress span{display:block;width:<?= $complete ? '100' : '45' ?>%;height:100%;background:linear-gradient(90deg,#65c7ff,#b66cff,#ff64aa)}.quick{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px}.panel{padding:18px;border-radius:20px;text-decoration:none;color:inherit}.panel strong{display:block;font-size:30px;line-height:1}.panel span{color:#6f7284;font-size:12px;font-weight:850}.badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.badge{display:inline-flex;min-height:32px;align-items:center;padding:7px 10px;border-radius:999px;background:#f1efff;color:#5542c8;font-size:12px;font-weight:850}.section{margin-top:30px}.section-head{display:flex;justify-content:space-between;align-items:end;gap:12px}.section h2{font-size:28px;margin:4px 0 0}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.app{min-height:145px;padding:20px;border-radius:20px;color:#202231;text-decoration:none;display:flex;flex-direction:column;justify-content:space-between}.app:hover{border-color:#8d7cff;box-shadow:0 14px 34px rgba(89,72,193,.12)}.mark{display:grid;place-items:center;width:44px;height:44px;border-radius:14px;background:#f1efff;color:#5848b4;font-weight:900}.app small{color:#707386}.status{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.dot{width:9px;height:9px;border-radius:999px;background:#17a56b}@media(max-width:760px){.shell{width:100%;max-width:100vw;padding:16px;overflow:hidden}.hero,.quick{grid-template-columns:1fr}.grid{grid-template-columns:repeat(2,1fr)}.welcome,.wallet{padding:23px}.top{align-items:flex-start;padding-bottom:22px;flex-direction:column}.actions{width:100%;padding-bottom:4px}.btn{flex:0 0 auto}.brand{font-size:14px}}@media(max-width:520px){.grid{grid-template-columns:1fr}.welcome h1{font-size:44px}}
</style>
</head>
<body>
<main class="shell">
    <header class="top">
        <div class="brand">BEYOND ID 2.1 BETA</div>
        <div class="actions">
            <?php if (in_array(strtolower((string)($user['role'] ?? '')), ['admin','super_admin'], true)): ?><a class="btn admin" href="../admin/review.php">Admin</a><?php endif; ?>
            <?php if ($publicUrl): ?><a class="btn" href="<?= e($publicUrl) ?>">Public profile</a><?php endif; ?>
            <a class="btn" href="connected-apps.php">Connected apps</a>
            <a class="btn" href="notifications.php">Notifications</a>
            <a class="btn" href="security.php">Security</a>
            <a class="btn" href="settings.php">Settings</a>
            <a class="btn" href="../auth/logout.php">Sign out</a>
        </div>
    </header>

    <section class="hero">
        <div class="welcome">
            <span class="muted">YOUR BEYOND HOME</span>
            <h1>Welcome, <?= e($name) ?>.</h1>
            <p class="muted">Your profile, security, apps, credentials, and bit$ all start here.</p>
            <div class="badges"><?php foreach (array_slice($badges, 0, 6) as $badge): ?><span class="badge"><?= e($badge['label']) ?></span><?php endforeach; ?></div>
            <?php if (!$complete): ?>
                <div class="progress"><span></span></div>
                <p><strong>Profile 45% complete</strong> - Complete onboarding to earn 100 bit$.</p>
                <a class="btn primary" href="profile.php">Continue profile</a>
            <?php else: ?>
                <p class="muted">Your core Beyond ID profile is ready across connected apps.</p>
                <a class="btn primary" href="profile.php">Manage profile</a>
            <?php endif; ?>
        </div>
        <aside class="wallet">
            <span>BEYOND WALLET</span>
            <strong><?= number_format($balance, 0) ?> BIT$</strong>
            <p>Your shared reward balance across every app.</p>
            <a class="btn" href="wallet.php">Open wallet</a>
        </aside>
    </section>

    <section class="quick" aria-label="Beyond ID summary">
        <a class="panel" href="profile.php"><strong><?= $complete ? '100%' : '45%' ?></strong><span>PROFILE</span></a>
        <a class="panel" href="connected-apps.php"><strong><?= count($connectedApps) ?></strong><span>CONNECTED APPS</span></a>
        <a class="panel" href="notifications.php"><strong><?= $unreadCount ?></strong><span>UNREAD UPDATES</span></a>
        <a class="panel" href="security.php"><strong><?= !empty($user['email_verified']) ? 'ON' : 'CHECK' ?></strong><span>EMAIL VERIFIED</span></a>
    </section>

    <section class="section">
        <div class="section-head"><div><span class="muted">APPS USING YOUR BEYOND ID</span><h2>Connected apps</h2></div><a class="btn primary" href="connected-apps.php">Manage apps</a></div>
        <div class="grid">
            <?php if ($connectedApps): foreach ($connectedApps as $connected): $meta = beyond_app_meta((string)$connected['app_slug']); ?>
                <a class="app" href="<?= e($meta['url']) ?>"><span class="mark"><?= e($meta['mark']) ?></span><strong><?= e($meta['name']) ?></strong><small class="status"><span class="dot"></span><?= $connected['revoked_at'] ? 'Revoked' : 'Connected' ?><?= $connected['last_used_at'] ? ' - Last used ' . e(date('M j', strtotime((string)$connected['last_used_at']))) : '' ?></small></a>
            <?php endforeach; else: ?>
                <a class="app" href="/app-store/"><span class="mark">OS</span><strong>Launch a Beyond app</strong><small>Connected apps appear here after use.</small></a>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-head"><div><span class="muted">LEARNING & CREDENTIALS</span><h2>Beyond Academy</h2></div><a class="btn primary" href="/academy/dashboard.php">Learner dashboard</a></div>
        <div class="grid">
            <?php if ($academyBadges): foreach ($academyBadges as $badge): ?>
                <a class="app" href="/academy/certificate.php?id=<?= urlencode((string)$badge['credential_id']) ?>"><span class="mark">AC</span><strong><?= e((string)$badge['title']) ?></strong><small>Beyond-issued - Verify credential</small></a>
            <?php endforeach; else: ?>
                <a class="app" href="/academy/dashboard.php"><span class="mark">LA</span><strong>Earn your first badge</strong><small>Choose one of three certificate pathways.</small></a>
            <?php endif; ?>
        </div>
    </section>

</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
