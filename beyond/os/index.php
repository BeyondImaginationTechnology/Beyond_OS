<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/session.php';

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$configuredOrigin = rtrim((string)getenv('BEYOND_OS_ORIGIN'), '/');
$isDedicatedOsHost = $host === 'os.beyondimagination.co.technology';
$osOrigin = $configuredOrigin !== '' ? $configuredOrigin : ($isDedicatedOsHost ? 'https://os.beyondimagination.co.technology' : '');
$returnTo = $osOrigin !== '' ? $osOrigin . '/' : '/beyond/os/';
$platformOrigin = 'https://beyondimagination.co.technology';
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (empty($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
    $_SESSION['beyond_return_to'] = $returnTo;
    $login = $osOrigin !== ''
        ? 'https://beyondimagination.co.technology/beyond-id/auth/login.php?app=beyond-os&required=1&return=' . rawurlencode($returnTo)
        : '/beyond-id/auth/login.php?app=beyond-os&required=1';
    header('Location: ' . $login);
    exit;
}

$name = trim((string)($_SESSION['name'] ?? ''));
if ($name === '') $name = strstr((string)($_SESSION['email'] ?? 'Operator'), '@', true) ?: 'Operator';
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#080d19"><title>Beyond OS</title><link rel="stylesheet" href="assets/os.css?v=1.0.0-beta.1"></head>
<body><aside class="sidebar"><a class="brand" href="./" aria-label="Beyond OS overview"><span class="brand-mark">B</span><span><strong>Beyond OS</strong><small>Beta 1.0</small></span></a><nav aria-label="Beyond OS navigation"><a class="active" href="./">Overview</a><a href="<?= $platformOrigin ?>/server/admin/daily-studio/">Studio</a><a href="<?= $platformOrigin ?>/beyond-id/admin/apps.php">Products</a><a href="<?= $platformOrigin ?>/beyond-id/admin/users.php">People</a><a href="<?= $platformOrigin ?>/beyond-id/admin/analytics.php">Analytics</a><a href="<?= $platformOrigin ?>/beyond-id/admin/system.php">System</a></nav><div class="sidebar-foot"><span class="live-dot"></span><span>All systems operational</span></div></aside>
<main><header class="topbar"><div><p>BEYOND IMAGINATION TECHNOLOGY 3.0</p><h1>Good to see you, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>.</h1></div><a class="profile" href="<?= $platformOrigin ?>/beyond-id/dashboard/">Beyond ID ↗</a></header><section class="priority"><div><p class="eyebrow">Today’s focus</p><h2>Run the ecosystem with clarity.</h2><p>Review what needs attention, then enter a focused workspace to make it happen.</p></div><a class="primary-action" href="<?= $platformOrigin ?>/server/admin/daily-studio/">Open Beyond Studio <span>→</span></a></section>
<section class="metric-grid" aria-label="Ecosystem status"><article><span>●</span><strong>6</strong><small>Connected products</small></article><article><span>↗</span><strong>Live</strong><small>Publishing system</small></article><article><span>✓</span><strong>Good</strong><small>System health</small></article><article><span>◌</span><strong>0</strong><small>Blocked tasks</small></article></section>
<section class="workspace-grid"><article class="panel"><div class="panel-heading"><div><p class="eyebrow">Work queue</p><h2>Continue working</h2></div><a href="<?= $platformOrigin ?>/server/admin/daily-studio/">View Studio</a></div><a class="queue-item" href="<?= $platformOrigin ?>/server/admin/daily-studio/dailybreath-content.php"><span class="app-icon breath">🌿</span><span><strong>DailyBreath publishing</strong><small>Review today’s verse and devotional</small></span><b>→</b></a><a class="queue-item" href="<?= $platformOrigin ?>/server/admin/daily-studio/french-generator.php"><span class="app-icon french">🇫🇷</span><span><strong>Beyond French</strong><small>Prepare the next language lesson</small></span><b>→</b></a><a class="queue-item" href="<?= $platformOrigin ?>/server/admin/daily-studio/video-templates.php"><span class="app-icon video">✦</span><span><strong>Production media</strong><small>Open the video template library</small></span><b>→</b></a></article><article class="panel"><div class="panel-heading"><div><p class="eyebrow">OS map</p><h2>Platform layers</h2></div></div><ol class="layer-map"><li><b>Beyond ID</b><span>Identity and access</span></li><li><b>Beyond OS</b><span>Overview and operations</span></li><li><b>Beyond Studio</b><span>Creation and publishing</span></li><li><b>Beyond products</b><span>Public experiences</span></li></ol></article></section></main></body></html>
