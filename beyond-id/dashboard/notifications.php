<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth-check.php';

$uid = (int)$_SESSION['user_id'];
$filter = ($_GET['filter'] ?? 'all') === 'unread' ? 'unread' : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf'] ?? null)) {
    if (isset($_POST['read_all'])) {
        $pdo->prepare('UPDATE user_notifications SET read_at=COALESCE(read_at,CURRENT_TIMESTAMP) WHERE user_id=?')->execute([$uid]);
    } elseif (!empty($_POST['notification_id'])) {
        $pdo->prepare('UPDATE user_notifications SET read_at=COALESCE(read_at,CURRENT_TIMESTAMP) WHERE id=? AND user_id=?')->execute([(int)$_POST['notification_id'], $uid]);
    }
    header('Location: notifications.php?filter=' . $filter);
    exit;
}

$memberName = trim((string)($_SESSION['name'] ?? ''));
try {
    $memberStmt = $pdo->prepare('SELECT u.first_name,u.last_name,p.display_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
    $memberStmt->execute([$uid]);
    $member = $memberStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $memberName = trim((string)($member['display_name'] ?: $member['first_name'] ?: $memberName));
} catch (Throwable $e) {
}
if ($memberName === '') $memberName = 'there';

$items = [];
try {
    $sql = 'SELECT * FROM user_notifications WHERE user_id=?' . ($filter === 'unread' ? ' AND read_at IS NULL' : '') . ' ORDER BY created_at DESC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$unreadCount = 0;
try {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND read_at IS NULL');
    $countStmt->execute([$uid]);
    $unreadCount = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
}

function notification_icon(string $type): string {
    return match (strtolower($type)) {
        'wallet', 'bits', 'reward' => '&#9670;',
        'security', 'account' => '&#128274;',
        'community', 'social' => '&#128101;',
        'app', 'product', 'update' => '&#10024;',
        default => '&#128276;',
    };
}

function notification_time(string $value): string {
    $time = strtotime($value);
    if (!$time) return $value;
    $delta = time() - $time;
    if ($delta < 60) return 'Just now';
    if ($delta < 3600) return (int)floor($delta / 60) . ' min ago';
    if ($delta < 86400) return (int)floor($delta / 3600) . ' hr ago';
    if ($delta < 172800) return 'Yesterday';
    return date('M j, Y \a\t g:i A', $time);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $unreadCount ? $unreadCount . ' unread | ' : '' ?>Notifications | Beyond ID</title>
<style>
:root{--bg:#f6f7fc;--panel:#fff;--ink:#202231;--muted:#707386;--line:#e0e2ea;--violet:#6d5dfc;--pink:#e9449f;--gold:#b77900}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#e9e3ff,transparent 30%),var(--bg);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.shell{width:min(980px,calc(100% - 32px));margin:auto;padding:34px 0 70px}.back{display:inline-flex;align-items:center;gap:8px;color:#565a70;text-decoration:none;font-size:13px;font-weight:850}.hero{margin-top:30px;padding:34px;border:1px solid var(--line);border-radius:28px;background:linear-gradient(135deg,#fff,#f7f4ff);box-shadow:0 18px 55px rgba(45,47,78,.08);display:grid;grid-template-columns:1fr auto;align-items:end;gap:24px}.eyebrow{color:#7567ef;font-size:11px;font-weight:950;letter-spacing:.13em}.hero h1{margin:12px 0 8px;font-size:clamp(38px,7vw,66px);line-height:.95;letter-spacing:-.06em}.hero p{margin:0;color:var(--muted);font-size:16px}.summary{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px}.metric{padding:17px;border:1px solid var(--line);border-radius:16px;background:#fff}.metric strong{display:block;font-size:28px}.metric span{color:var(--muted);font-size:11px;font-weight:800}.toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:26px 0 13px}.filters{display:flex;gap:8px}.filter{display:inline-flex;min-height:42px;padding:0 16px;align-items:center;border:1px solid var(--line);border-radius:999px;background:#fff;color:#53566a;text-decoration:none;font-size:13px;font-weight:850}.filter.active{border-color:transparent;background:linear-gradient(100deg,var(--violet),var(--pink));color:#fff}.read-all{border:0;border-radius:12px;padding:12px 16px;background:#282a3b;color:#fff;font:inherit;font-size:13px;font-weight:850;cursor:pointer}.feed{overflow:hidden;border:1px solid var(--line);border-radius:24px;background:var(--panel);box-shadow:0 14px 45px rgba(45,47,78,.06)}.notice{display:grid;grid-template-columns:50px 1fr auto;gap:16px;padding:22px;border-bottom:1px solid var(--line);position:relative}.notice:last-child{border-bottom:0}.notice.unread{background:linear-gradient(90deg,rgba(109,93,252,.06),transparent 72%)}.notice.unread:before{content:"";position:absolute;left:0;top:18px;bottom:18px;width:3px;border-radius:0 4px 4px 0;background:linear-gradient(var(--violet),var(--pink))}.icon{width:48px;height:48px;border-radius:15px;background:#f1efff;color:#6557e9;display:grid;place-items:center;font-size:21px}.notice h2{margin:2px 0 7px;font-size:17px;letter-spacing:-.02em}.notice p{margin:0;color:var(--muted);font-size:14px;line-height:1.55}.meta{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-top:11px}.meta time{color:#9295a4;font-size:12px}.type{padding:5px 8px;border-radius:999px;background:#f0f1f6;color:#6b6e80;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.07em}.open{color:#6557e9;text-decoration:none;font-size:12px;font-weight:900}.mark{align-self:center;border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:#fff;color:#565a70;font:inherit;font-size:11px;font-weight:850;cursor:pointer}.read{opacity:.72}.empty{padding:58px 24px;text-align:center}.empty-icon{width:68px;height:68px;margin:auto;border-radius:22px;background:linear-gradient(145deg,#eeeaff,#fdebf6);display:grid;place-items:center;font-size:30px}.empty h2{margin:18px 0 8px}.empty p{margin:0;color:var(--muted)}@media(max-width:700px){.shell{width:min(100% - 22px,980px);padding-top:22px}.hero{grid-template-columns:1fr;padding:25px}.summary{width:100%}.toolbar{align-items:flex-start;flex-direction:column}.notice{grid-template-columns:43px 1fr;padding:18px 16px;gap:12px}.icon{width:42px;height:42px}.notice form{grid-column:2}.mark{width:100%}.read-all{width:100%}.filters{width:100%}.filter{flex:1;justify-content:center}}
</style>
</head>
<body>
<main class="shell">
    <a class="app-back" href="index.php">Back to dashboard</a>
    <section class="hero">
        <div><span class="eyebrow">YOUR BEYOND UPDATES</span><h1>Hi, <?= e($memberName) ?>.</h1><p><?= $unreadCount ? 'You have ' . $unreadCount . ' update' . ($unreadCount === 1 ? '' : 's') . ' waiting for you.' : 'You are completely caught up.' ?></p></div>
        <div class="summary"><div class="metric"><strong><?= $unreadCount ?></strong><span>UNREAD</span></div><div class="metric"><strong><?= count($items) ?></strong><span><?= $filter === 'unread' ? 'SHOWN' : 'RECENT' ?></span></div></div>
    </section>
    <div class="toolbar">
        <nav class="filters" aria-label="Notification filters"><a class="filter <?= $filter === 'all' ? 'active' : '' ?>" href="notifications.php">All</a><a class="filter <?= $filter === 'unread' ? 'active' : '' ?>" href="notifications.php?filter=unread">Unread<?= $unreadCount ? ' (' . $unreadCount . ')' : '' ?></a></nav>
        <?php if ($unreadCount): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="read-all" name="read_all" value="1">Mark everything read</button></form><?php endif; ?>
    </div>
    <section class="feed" aria-label="Notifications">
        <?php if (!$items): ?><div class="empty"><div class="empty-icon">&#10024;</div><h2><?= $filter === 'unread' ? 'No unread notifications' : 'Nothing new right now' ?></h2><p><?= $filter === 'unread' ? 'You have read every update. Nice work.' : 'New rewards, security alerts and app updates will appear here.' ?></p></div><?php endif; ?>
        <?php foreach ($items as $n): ?>
            <article class="notice <?= $n['read_at'] ? 'read' : 'unread' ?>">
                <span class="icon" aria-hidden="true"><?= notification_icon((string)($n['type'] ?? 'system')) ?></span>
                <div><h2><?= e($n['title']) ?></h2><p><?= e($n['body']) ?></p><div class="meta"><span class="type"><?= e($n['type'] ?? 'Update') ?></span><time datetime="<?= e($n['created_at']) ?>"><?= e(notification_time((string)$n['created_at'])) ?></time><?php if (!empty($n['action_url'])): ?><a class="open" href="<?= e($n['action_url']) ?>">Open update &rarr;</a><?php endif; ?></div></div>
                <?php if (!$n['read_at']): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>"><button class="mark">Mark read</button></form><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
