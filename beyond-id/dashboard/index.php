<?php
require __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$uid = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare('SELECT u.first_name,u.last_name,u.email,u.role,p.display_name,p.interests,p.goals,p.profile_completed_at FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $stmt = $pdo->prepare('SELECT first_name,last_name,email,role FROM users WHERE id=?');
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

$name = $user['display_name'] ?: $user['first_name'];
$complete = !empty($user['profile_completed_at']);
$balance = 0;
try {
    $wallet = $pdo->prepare('SELECT balance FROM beyond_wallets WHERE user_id=?');
    $wallet->execute([$uid]);
    $balance = (float)$wallet->fetchColumn();
} catch (Throwable $exception) {
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Home | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#25165c,transparent 28%),#090912;color:#fff;font-family:system-ui}.shell{max-width:1180px;margin:auto;padding:22px}.top{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:8px 0 34px}.brand{font-weight:900}.actions{min-width:0;display:flex;gap:9px;overflow-x:auto;scrollbar-width:none}.actions::-webkit-scrollbar{display:none}.btn{display:inline-flex;align-items:center;min-height:44px;padding:11px 15px;border-radius:13px;background:#262638;color:white;text-decoration:none;font-weight:800}.primary{background:linear-gradient(90deg,#5b8cff,#ad44ed,#e9449f)}.hero{display:grid;grid-template-columns:1.3fr .7fr;gap:16px}.welcome{padding:32px;border:1px solid #35354a;border-radius:28px;background:linear-gradient(135deg,#18182a,#11111e)}.welcome h1{font-size:clamp(42px,7vw,72px);letter-spacing:-.06em;line-height:.95;margin:10px 0}.muted{color:#a4a4b8}.wallet{padding:28px;border-radius:28px;background:linear-gradient(145deg,#2a2363,#702579 58%,#b2306f)}.wallet strong{font-size:46px;display:block;margin:20px 0}.progress{height:9px;border-radius:99px;background:#313144;overflow:hidden;margin:18px 0}.progress span{display:block;width:<?= $complete ? '100' : '45' ?>%;height:100%;background:linear-gradient(90deg,#65c7ff,#b66cff,#ff64aa)}.section{margin-top:30px}.section-head{display:flex;justify-content:space-between;align-items:end;gap:12px}.section h2{font-size:28px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.app{min-height:145px;padding:20px;border:1px solid #303043;border-radius:20px;background:#13131f;color:white;text-decoration:none;display:flex;flex-direction:column;justify-content:space-between}.app:hover{border-color:#7575a6}.mark{font-size:28px}.app small{color:#9696aa}@media(max-width:760px){.shell{width:100%;max-width:100vw;padding:16px;overflow:hidden}.hero{grid-template-columns:1fr}.grid{grid-template-columns:repeat(2,1fr)}.welcome,.wallet{padding:23px}.top{align-items:flex-start;padding-bottom:22px;flex-direction:column}.actions{width:100%;padding-bottom:4px}.actions .admin{display:inline-flex;position:sticky;left:0;z-index:2;color:#fff;border:0;background:linear-gradient(90deg,#5b6dff,#a044f2,#e9449f)}.btn{flex:0 0 auto}.brand{font-size:14px}}@media(max-width:440px){.grid{grid-template-columns:1fr}.welcome h1{font-size:44px}}
body{background:radial-gradient(circle at 90% 0,#e9e3ff,transparent 30%),#f7f8fc;color:#202231}.btn{background:#fff;color:#292b3b;border:1px solid #dfe1ea;box-shadow:0 5px 18px rgba(37,39,68,.06)}.primary{color:#fff;border:0;background:linear-gradient(90deg,#5b6dff,#a044f2,#e9449f)}.welcome{border-color:#e0e2ea;background:#fff;box-shadow:0 18px 55px rgba(45,47,78,.08)}.muted{color:#6f7284}.wallet{color:#fff;box-shadow:0 18px 55px rgba(98,43,137,.18)}.progress{background:#e5e6ee}.app{border-color:#e0e2ea;background:#fff;color:#202231;box-shadow:0 10px 30px rgba(45,47,78,.06)}.app:hover{border-color:#8d7cff;box-shadow:0 14px 34px rgba(89,72,193,.12)}.app small{color:#707386}
</style>
</head>
<body>
<main class="shell">
    <header class="top">
        <div class="brand">BEYOND ID 2.1 BETA</div>
        <div class="actions">
            <?php if (in_array(strtolower((string)($user['role'] ?? '')), ['admin','super_admin'], true)): ?><a class="btn admin" href="../admin/review.php">Admin</a><?php endif; ?>
            <a class="btn" href="notifications.php">Notifications</a>
            <a class="btn" href="settings.php">Settings</a>
            <a class="btn" href="../auth/logout.php">Sign out</a>
        </div>
    </header>

    <section class="hero">
        <div class="welcome">
            <span class="muted">YOUR BEYOND HOME</span>
            <h1>Welcome, <?= e($name) ?>.</h1>
            <p class="muted">Pick up where you left off, discover something new, or keep building your profile.</p>
            <?php if (!$complete): ?>
                <div class="progress"><span></span></div>
                <p><strong>Profile 45% complete</strong> · Complete onboarding to earn 100 bit$.</p>
                <a class="btn primary" href="profile.php">Continue profile →</a>
            <?php else: ?>
                <a class="btn primary" href="profile.php">View profile</a>
            <?php endif; ?>
        </div>
        <aside class="wallet">
            <span>BEYOND WALLET</span>
            <strong><?= number_format($balance, 0) ?> BIT$</strong>
            <p>Your shared reward balance across every app.</p>
            <a class="btn" href="wallet.php">Open wallet →</a>
        </aside>
    </section>

    <?php
    $academyBadges = [];
    try {
        require_once __DIR__ . '/../../includes/academy-certificates.php';
        $academyBadges = academy_badges($uid);
    } catch (Throwable $exception) {
        $academyBadges = [];
    }
    ?>
    <section class="section">
        <div class="section-head"><div><span class="muted">LEARNING & CREDENTIALS</span><h2>Beyond Academy</h2></div><a class="btn primary" href="/academy/dashboard.php">Learner dashboard</a></div>
        <div class="grid">
            <?php if ($academyBadges): foreach ($academyBadges as $badge): ?>
                <a class="app" href="/academy/certificate.php?id=<?= urlencode((string)$badge['credential_id']) ?>"><span class="mark">🏅</span><strong><?= e((string)$badge['title']) ?></strong><small>Beyond-issued · Verify credential</small></a>
            <?php endforeach; else: ?>
                <a class="app" href="/academy/dashboard.php"><span class="mark">🎓</span><strong>Earn your first badge</strong><small>Choose one of three certificate pathways.</small></a>
            <?php endif; ?>
        </div>
    </section>

</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
