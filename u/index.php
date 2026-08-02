<?php
declare(strict_types=1);
require_once __DIR__ . '/../beyond-id/includes/functions.php';
require_once __DIR__ . '/../beyond-id/includes/db.php';

$username = (string)($_GET['username'] ?? '');
if ($username === '') {
    $path = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
    $username = preg_replace('#^u/#', '', $path) ?? '';
}
$username = beyond_profile_slug($username);
if ($username === '') {
    http_response_code(404);
    exit('Beyond ID profile not found.');
}

$profile = null;
try {
    $stmt = $pdo->prepare("SELECT u.id,u.first_name,u.last_name,u.email_verified,u.role,p.display_name,p.username,p.public_profile_enabled,p.interests,p.goals,p.bio,p.creator_links,p.creator_verified_at,p.seller_verified_at,p.profile_completed_at FROM profiles p JOIN users u ON u.id=p.user_id WHERE p.username=? AND p.public_profile_enabled=1 AND u.status='active' LIMIT 1");
    $stmt->execute([$username]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $exception) {
}

if (!$profile) {
    http_response_code(404);
    exit('Beyond ID profile not found.');
}

$uid = (int)$profile['id'];
$academyBadges = [];
try {
    require_once __DIR__ . '/../includes/academy-certificates.php';
    $academyBadges = academy_badges($uid);
} catch (Throwable $exception) {
}

$connectedApps = [];
try {
    $apps = $pdo->prepare("SELECT app_slug,last_used_at FROM connected_apps WHERE user_id=? AND revoked_at IS NULL ORDER BY COALESCE(last_used_at,'') DESC LIMIT 8");
    $apps->execute([$uid]);
    $connectedApps = $apps->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}

$badges = beyond_badges_for_user($profile, $profile, $academyBadges);
$displayName = $profile['display_name'] ?: trim((string)(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')));
$interests = array_filter(array_map('trim', explode(',', (string)($profile['interests'] ?? ''))));
$links = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string)($profile['creator_links'] ?? '')) ?: []), static fn($url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($displayName) ?> | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,#e9e3ff,transparent 32%),#f7f8fc;color:#202231;font-family:system-ui}.shell{width:min(1040px,calc(100% - 32px));margin:auto;padding:30px 0 72px}.top{display:flex;justify-content:space-between;gap:14px;align-items:center}.top a{color:#6557c8;font-weight:850;text-decoration:none}.hero{margin-top:34px;padding:34px;border:1px solid #e0e2ea;border-radius:30px;background:#fff;box-shadow:0 18px 55px rgba(45,47,78,.08)}.avatar{display:grid;place-items:center;width:76px;height:76px;border-radius:24px;background:linear-gradient(135deg,#5b6dff,#a044f2,#e9449f);color:#fff;font-size:26px;font-weight:950}.hero h1{font-size:clamp(46px,8vw,82px);line-height:.92;letter-spacing:-.065em;margin:18px 0 8px}.muted{color:#6f7284}.badges,.chips,.links{display:flex;gap:8px;flex-wrap:wrap}.badges{margin-top:18px}.badge,.chip{display:inline-flex;min-height:32px;align-items:center;padding:7px 10px;border-radius:999px;background:#f1efff;color:#5542c8;font-size:12px;font-weight:850}.chip{background:#f1f2f7;color:#616577}.section{margin-top:28px}.section h2{font-size:28px;margin:0 0 12px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.card{min-height:138px;padding:19px;border:1px solid #e0e2ea;border-radius:20px;background:#fff;box-shadow:0 12px 36px rgba(45,47,78,.06);color:inherit;text-decoration:none;display:flex;flex-direction:column;justify-content:space-between}.mark{display:grid;place-items:center;width:44px;height:44px;border-radius:14px;background:#f1efff;color:#5848b4;font-weight:950}.link{display:inline-flex;min-height:42px;align-items:center;padding:10px 13px;border:1px solid #dfe1ea;border-radius:13px;background:#fff;color:#292b3b;text-decoration:none;font-weight:850}.empty{padding:22px;border:1px dashed #d8dae5;border-radius:20px;color:#6f7284}@media(max-width:760px){.shell{width:min(100% - 22px,1040px);padding-top:22px}.grid{grid-template-columns:1fr}.hero{padding:24px}.hero h1{font-size:46px}}
</style>
</head>
<body>
<main class="shell">
    <header class="top"><strong>BEYOND ID</strong><a href="/beyond-id/auth/register.php">Create Beyond ID</a></header>
    <section class="hero">
        <div class="avatar"><?= e(strtoupper(substr($displayName, 0, 1) ?: 'B')) ?></div>
        <h1><?= e($displayName) ?></h1>
        <p class="muted">@<?= e((string)$profile['username']) ?><?= !empty($profile['bio']) ? ' - ' . e((string)$profile['bio']) : '' ?></p>
        <div class="badges"><?php foreach ($badges as $badge): ?><span class="badge"><?= e($badge['label']) ?></span><?php endforeach; ?></div>
    </section>

    <?php if ($interests || !empty($profile['goals'])): ?><section class="section"><h2>Profile</h2><?php if ($interests): ?><div class="chips"><?php foreach ($interests as $interest): ?><span class="chip"><?= e($interest) ?></span><?php endforeach; ?></div><?php endif; ?><?php if (!empty($profile['goals'])): ?><p class="muted"><?= e((string)$profile['goals']) ?></p><?php endif; ?></section><?php endif; ?>

    <section class="section"><h2>Badges & certificates</h2><div class="grid">
        <?php if ($academyBadges): foreach ($academyBadges as $badge): ?><a class="card" href="/academy/verify.php?id=<?= urlencode((string)$badge['credential_id']) ?>"><span class="mark">AC</span><strong><?= e((string)$badge['title']) ?></strong><small class="muted">Verify credential</small></a><?php endforeach; else: ?><div class="empty">Public certificates will appear here after they are earned.</div><?php endif; ?>
    </div></section>

    <section class="section"><h2>Connected apps</h2><div class="grid">
        <?php if ($connectedApps): foreach ($connectedApps as $app): $meta = beyond_app_meta((string)$app['app_slug']); ?><a class="card" href="<?= e($meta['url']) ?>"><span class="mark"><?= e($meta['mark']) ?></span><strong><?= e($meta['name']) ?></strong><small class="muted">Beyond ID connected</small></a><?php endforeach; else: ?><div class="empty">Connected public apps will appear here.</div><?php endif; ?>
    </div></section>

    <?php if ($links): ?><section class="section"><h2>Creator links</h2><div class="links"><?php foreach ($links as $url): ?><a class="link" href="<?= e($url) ?>" rel="me noopener" target="_blank"><?= e(parse_url($url, PHP_URL_HOST) ?: $url) ?></a><?php endforeach; ?></div></section><?php endif; ?>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
