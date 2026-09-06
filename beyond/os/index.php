<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/session.php';

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$configuredOrigin = rtrim((string)getenv('BEYOND_OS_ORIGIN'), '/');
$isDedicatedOsHost = $host === 'os.beyondimagination.co.technology';
$osOrigin = $configuredOrigin !== '' ? $configuredOrigin : ($isDedicatedOsHost ? 'https://os.beyondimagination.co.technology' : '');
$returnTo = $osOrigin !== '' ? $osOrigin . '/' : '/beyond/os/';
$platformOrigin = 'https://beyondimagination.co.technology';

if ($isDedicatedOsHost) {
    $downloads = $osOrigin . '/downloads/cyber/1.0/';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#080d19">
        <meta name="description" content="BIT OS Cyber 1.0 — a defensive Linux desktop for focused, authorized security work.">
        <title>BIT OS Cyber 1.0</title>
        <link rel="stylesheet" href="assets/landing.css?v=1.0.0-beta.1">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="/" aria-label="Beyond OS home"><span class="brand-mark">B</span><span><strong>BIT OS</strong><small>Cyber 1.0</small></span></a>
            <nav aria-label="Primary navigation">
                <a href="#downloads">Downloads</a>
                <a href="#guides">Guides</a>
                <a href="#companion">Companion</a>
                <a class="header-button" href="#downloads">Get BIT OS <span>↗</span></a>
            </nav>
        </header>

        <main>
            <section class="hero">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="pulse"></span> BIT OS Cyber · Release 1.0 Candidate</p>
                    <h1>A focused defensive desktop for authorized security work.</h1>
                    <p class="lede">An independent Linux system built with a minimal toolchain, a practical desktop, and a defensive network baseline.</p>
                    <div class="hero-actions"><a class="primary-button" href="#downloads">Explore downloads <span>↓</span></a><a class="text-link" href="#guides">Read the install guide <span>→</span></a></div>
                    <div class="trust-row"><span>Open source foundations</span><span>SHA-256 verified</span><span>Built for authorized testing</span></div>
                </div>
                <div class="hero-panel" aria-label="Beyond OS desktop preview">
                    <div class="panel-top"><span class="window-dots"><i></i><i></i><i></i></span><span>BIT OS / TERMINAL</span><span class="panel-status">● ONLINE</span></div>
                    <div class="terminal"><p><span class="muted">bit@cyber</span><b>:</b><span class="path">~</span><b>$</b> beyond-cyber doctor</p><p class="success">✓ desktop environment ready</p><p class="success">✓ defensive network baseline active</p><p class="success">✓ evidence workspace prepared</p><p><span class="muted">Next:</span> build something that matters.</p></div>
                    <div class="panel-footer"><span>XFCE / X11</span><span>BIT OS CYBER 1.0</span></div>
                </div>
            </section>

            <section class="section" id="downloads">
                <div class="section-heading"><div><p class="eyebrow">Choose your starting point</p><h2>Download BIT OS Cyber 1.0</h2></div><a class="subtle-link" href="<?= htmlspecialchars($downloads . 'SHA256SUMS', ENT_QUOTES, 'UTF-8') ?>">SHA-256 checksums ↗</a></div>
                <div class="download-grid">
                    <article class="download-card featured"><div class="card-icon">◈</div><div class="card-meta"><span class="tag">Recommended</span><span>AMD64 · 84 MB</span></div><h3>Bootable ISO</h3><p>Boot the installer from a USB drive, firmware menu, or a virtual machine.</p><a href="<?= htmlspecialchars($downloads . 'bitCyberos.iso', ENT_QUOTES, 'UTF-8') ?>">Download ISO <span>↓</span></a></article>
                    <article class="download-card"><div class="card-icon">▣</div><div class="card-meta"><span class="tag">Direct USB</span><span>GPT image · 2.1 GB</span></div><h3>USB Installer Image</h3><p>Write this verified GPT image directly to a USB drive with a compatible imaging tool.</p><a href="<?= htmlspecialchars($downloads . 'bit-os-cyber-1.0-installer.img', ENT_QUOTES, 'UTF-8') ?>">Download USB Installer Image <span>↓</span></a></article>
                    <article class="download-card"><div class="card-icon">▤</div><div class="card-meta"><span class="tag">Windows companion</span><span>Coming next</span></div><h3>Windows Installer</h3><p>Guided USB creation and install preparation for Windows systems. The signed executable is in development.</p><a href="<?= htmlspecialchars('#guides', ENT_QUOTES, 'UTF-8') ?>">Read install guide <span>→</span></a></article>
                </div>
            </section>

            <section class="split-section" id="companion">
                <div><p class="eyebrow">One ecosystem, wherever you are</p><h2>Keep your work close.</h2><p>Use the desktop as your focused base, then reach your systems from the Beyond OS companion apps for iPhone, iPad, and macOS.</p><a class="text-link" href="<?= htmlspecialchars($downloads . 'companion/', ENT_QUOTES, 'UTF-8') ?>">Get companion apps <span>→</span></a></div>
                <div class="platform-list"><div><strong>Linux Desktop</strong><span>ISO and USB Installer Images</span><b>Available now</b></div><div><strong>Windows Installer</strong><span>Guided USB creation</span><b>In progress</b></div><div><strong>iOS · macOS</strong><span>Secure companion</span><b>In progress</b></div></div>
            </section>

            <section class="section guide-section" id="guides">
                <div class="section-heading"><div><p class="eyebrow">Start safely</p><h2>Release notes and guides</h2></div></div>
                <div class="guide-grid"><a href="#downloads"><span>01</span><strong>Cyber 1.0 Candidate</strong><small>Release details and checksums →</small></a><a href="#downloads"><span>02</span><strong>Install from ISO</strong><small>USB, dual-boot, and bare-metal setup →</small></a><a href="#downloads"><span>03</span><strong>Write the USB image</strong><small>Direct USB installation image →</small></a></div>
            </section>
        </main>
        <footer><span>© <?= date('Y') ?> Beyond Imagination Technology</span><span>BIT OS Cyber 1.0 Candidate · Use only on systems you own or are authorized to test.</span></footer>
    </body>
    </html>
    <?php
    exit;
}

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
