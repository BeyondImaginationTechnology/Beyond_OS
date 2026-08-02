<?php
require __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$uid = (int)$_SESSION['user_id'];
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf'] ?? null)) {
    $slug = beyond_profile_slug((string)($_POST['app_slug'] ?? ''));
    if ($slug !== '') {
        if (isset($_POST['revoke'])) {
            $pdo->prepare('UPDATE connected_apps SET revoked_at=' . ($driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()') . ' WHERE user_id=? AND app_slug=?')->execute([$uid, $slug]);
            $message = 'App access revoked.';
        } elseif (isset($_POST['connect'])) {
            $meta = beyond_app_meta($slug);
            $permissions = json_encode($meta['permissions'] ?? ['profile:read']);
            $now = date('Y-m-d H:i:s');
            if ($driver === 'sqlite') {
                $sql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON CONFLICT(user_id,app_slug) DO UPDATE SET permissions_json=excluded.permissions_json,last_used_at=excluded.last_used_at,revoked_at=NULL';
            } else {
                $sql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json),last_used_at=VALUES(last_used_at),revoked_at=NULL';
            }
            $pdo->prepare($sql)->execute([$uid, $slug, $permissions, $now]);
            $message = 'App connected.';
        }
    }
}

$connected = [];
try {
    $stmt = $pdo->prepare("SELECT app_slug,permissions_json,last_used_at,revoked_at FROM connected_apps WHERE user_id=? ORDER BY revoked_at IS NOT NULL, COALESCE(last_used_at,'') DESC, app_slug");
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $connected[(string)$row['app_slug']] = $row;
    }
} catch (Throwable $exception) {
}

$catalog = beyond_app_catalog();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connected Apps | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#e9e3ff,transparent 30%),#f7f8fc;color:#202231;font-family:system-ui}.shell{width:min(1100px,calc(100% - 32px));margin:auto;padding:32px 0 70px}.back{color:#6557c8;font-weight:850;text-decoration:none}.hero{display:flex;justify-content:space-between;gap:18px;align-items:end;margin:28px 0}.hero h1{font-size:clamp(42px,7vw,72px);letter-spacing:-.06em;line-height:.95;margin:8px 0}.muted{color:#6f7284}.metric{min-width:170px;padding:18px;border:1px solid #e0e2ea;border-radius:20px;background:#fff;box-shadow:0 12px 36px rgba(45,47,78,.07)}.metric strong{display:block;font-size:32px}.msg{padding:13px;border-radius:13px;background:#e4f7ed;color:#17623e;margin-bottom:16px}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.card{display:grid;grid-template-columns:56px 1fr auto;gap:16px;align-items:start;padding:18px;border:1px solid #e0e2ea;border-radius:20px;background:#fff;box-shadow:0 12px 36px rgba(45,47,78,.06)}.mark{display:grid;place-items:center;width:52px;height:52px;border-radius:16px;background:#f1efff;color:#5848b4;font-weight:950}.card h2{margin:0 0 6px}.permissions{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.pill{display:inline-flex;align-items:center;min-height:27px;padding:5px 8px;border-radius:999px;background:#f1f2f7;color:#616577;font-size:11px;font-weight:850}.status{background:#e6f7ee;color:#18744b}.revoked{background:#fde8ec;color:#8f2438}.actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.btn,button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 12px;border-radius:12px;border:1px solid #dfe1ea;background:#fff;color:#292b3b;text-decoration:none;font:inherit;font-size:13px;font-weight:850;cursor:pointer}.primary{border:0;color:#fff;background:linear-gradient(90deg,#5b6dff,#a044f2,#e9449f)}.danger{color:#8f2438}.section-title{margin:30px 0 12px;font-size:13px;font-weight:950;color:#74788b;letter-spacing:.11em}@media(max-width:760px){.shell{width:min(100% - 22px,1100px);padding-top:22px}.hero{display:block}.metric{margin-top:14px}.grid{grid-template-columns:1fr}.card{grid-template-columns:48px 1fr}.actions{grid-column:2;justify-content:flex-start}.mark{width:46px;height:46px}}
</style>
</head>
<body>
<main class="shell">
    <a class="back" href="index.php">Back to dashboard</a>
    <section class="hero"><div><span class="muted">PERMISSIONS & ACCESS</span><h1>Connected apps</h1><p class="muted">See which Beyond apps use your ID, what they can access, and jump back into them.</p></div><div class="metric"><strong><?= count(array_filter($connected, static fn($row) => empty($row['revoked_at']))) ?></strong><span class="muted">active connections</span></div></section>
    <?php if ($message): ?><div class="msg"><?= e($message) ?></div><?php endif; ?>

    <h2 class="section-title">YOUR CONNECTIONS</h2>
    <section class="grid">
        <?php foreach ($connected as $slug => $row): $meta = beyond_app_meta($slug); $permissions = json_decode((string)($row['permissions_json'] ?? '[]'), true) ?: ($meta['permissions'] ?? ['profile:read']); ?>
            <article class="card">
                <span class="mark"><?= e($meta['mark']) ?></span>
                <div><h2><?= e($meta['name']) ?></h2><p class="muted"><?= empty($row['revoked_at']) ? 'Connected' : 'Revoked' ?><?= $row['last_used_at'] ? ' - Last used ' . e(date('M j, Y', strtotime((string)$row['last_used_at']))) : '' ?></p><div class="permissions"><span class="pill <?= empty($row['revoked_at']) ? 'status' : 'revoked' ?>"><?= empty($row['revoked_at']) ? 'Active' : 'Revoked' ?></span><?php foreach ($permissions as $permission): ?><span class="pill"><?= e((string)$permission) ?></span><?php endforeach; ?></div></div>
                <div class="actions"><a class="btn primary" href="<?= e($meta['url']) ?>">Launch</a><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="app_slug" value="<?= e($slug) ?>"><?php if (empty($row['revoked_at'])): ?><button class="danger" name="revoke" value="1">Revoke</button><?php else: ?><button name="connect" value="1">Reconnect</button><?php endif; ?></form></div>
            </article>
        <?php endforeach; ?>
        <?php if (!$connected): ?><article class="card"><span class="mark">ID</span><div><h2>No apps connected yet</h2><p class="muted">Open a Beyond app while signed in and it will appear here.</p></div><div class="actions"><a class="btn primary" href="/app-store/">Browse apps</a></div></article><?php endif; ?>
    </section>

    <h2 class="section-title">AVAILABLE BEYOND APPS</h2>
    <section class="grid">
        <?php foreach ($catalog as $slug => $meta): if (isset($connected[$slug])) continue; ?>
            <article class="card">
                <span class="mark"><?= e($meta['mark']) ?></span>
                <div><h2><?= e($meta['name']) ?></h2><p class="muted">Ready to connect with your Beyond ID.</p><div class="permissions"><?php foreach ($meta['permissions'] as $permission): ?><span class="pill"><?= e($permission) ?></span><?php endforeach; ?></div></div>
                <div class="actions"><a class="btn" href="<?= e($meta['url']) ?>">Launch</a><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="app_slug" value="<?= e($slug) ?>"><button class="primary" name="connect" value="1">Connect</button></form></div>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
