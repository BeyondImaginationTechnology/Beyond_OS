<?php
declare(strict_types=1);

require_once __DIR__ . '/../beyond-id/includes/session.php';

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$configuredOrigin = rtrim((string)getenv('BEYOND_WEBS_ORIGIN'), '/');
$isDedicatedHost = $host === 'host.beyondimagination.co.technology';
$websOrigin = $configuredOrigin !== '' ? $configuredOrigin : ($isDedicatedHost ? 'https://host.beyondimagination.co.technology' : '');
$returnTo = $websOrigin !== '' ? $websOrigin . '/' : '/beyond-webs/';
$identityOrigin = 'https://beyondimagination.co.technology';
$signedIn = !empty($_SESSION['user_id']);
$name = trim((string)($_SESSION['name'] ?? ''));
if ($name === '') $name = strstr((string)($_SESSION['email'] ?? 'Builder'), '@', true) ?: 'Builder';
$loginUrl = $identityOrigin . '/beyond-id/auth/login.php?app=beyond-webs&return=' . rawurlencode($returnTo);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f4f7fb">
  <meta name="description" content="Configure a metered VPS session, choose the BIT OS environment to run inside it, and start with Beyond Webs.">
  <title>Beyond Webs - VPS session control</title>
  <link rel="stylesheet" href="assets/app.css?v=0.0.1">
  <link rel="stylesheet" href="assets/overrides.css?v=0.0.2">
</head>
<body>
  <header class="site-header wrap">
    <a class="brand" href="<?= $websOrigin !== '' ? '/' : '/beyond-webs/' ?>" aria-label="Beyond Webs home"><span class="brand-mark">B</span><span>BEYOND <b>WEBS</b><small>VPS SESSION CONTROL</small></span></a>
    <nav aria-label="Primary navigation"><a href="#flavours">Environments</a><a href="#plans">Session sizes</a><a href="#how">How it works</a></nav>
    <?php if ($signedIn): ?><a class="account" href="#launch"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> <span>↗</span></a><?php else: ?><a class="account" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Sign in with Beyond ID <span>↗</span></a><?php endif; ?>
  </header>

  <main>
    <section class="hero wrap">
      <div class="hero-copy">
        <p class="eyebrow"><i></i> VPS SESSION CONTROL</p>
        <h1>Configure a VPS.<br><em>Run your work.</em></h1>
        <p class="lede">Choose a session size and the BIT OS environment to run inside it, then start your VPS session. Beyond Webs is the hosting control point; BIT OS is the workspace inside the VPS.</p>
        <div class="hero-actions"><a class="button primary" href="#launch">Configure a session <span>→</span></a><a class="button ghost" href="#plans">Compare session sizes</a></div>
        <p class="identity"><span>✦</span> Sign in with Beyond ID · VPS usage is metered by the hour.</p>
      </div>
      <div class="workspace-preview" aria-label="Illustrative VPS session configuration preview">
        <div class="preview-top"><span class="lights"><i></i><i></i><i></i></span><span>BEYOND WEBS / SESSION</span><b id="previewState">NOT STARTED</b></div>
        <div class="preview-body">
          <aside><strong>B</strong><span class="selected">01</span><span>02</span><span>03</span></aside>
          <section>
            <div class="terminal-line"><span>SESSION SETUP</span> / VPS</div>
            <div class="checks"><p><b>01</b> Configure your session</p><p><b>02</b> Start when you’re ready</p><p><b>03</b> Monitor hourly usage</p><p><b>04</b> Manage your session</p></div>
            <article class="session-card">
              <small>CONFIGURATION PREVIEW · NOT A LIVE VPS</small>
              <strong><span id="previewFlavour">Gaming</span> <i>·</i> <span id="previewPlan">Build</span></strong>
              <div class="resource"><span>RAM</span><b id="previewRam">16 GB</b></div>
              <div class="resource"><span>CPU</span><b id="previewCpu">8 cores</b></div>
              <div class="resource"><span>GPU</span><b id="previewGpu">Performance GPU</b></div>
              <div class="resource"><span>STORAGE</span><b id="previewStorage">256 GB SSD</b></div>
              <p>Usage is metered by the hour · Rates are not displayed here</p>
            </article>
          </section>
        </div>
        <div class="preview-bottom"><span>SESSION CONTROL</span><span>ILLUSTRATIVE PREVIEW</span></div>
      </div>
    </section>

    <section class="proof wrap"><div><b>07</b><span>BIT OS environments</span></div><div><b>BY HOUR</b><span>Metered VPS usage</span></div><div><b>ID</b><span>Beyond ID sign-in</span></div></section>

    <section class="section wrap" id="flavours">
      <div class="section-head"><div><p class="eyebrow">BIT OS runs inside the VPS</p><h2>Choose the environment for your session.</h2></div><p>Select the operating environment for your VPS. Your choice appears in the session configuration before you start.</p></div>
      <div class="flavour-grid">
        <button class="flavour" data-flavour="Home" data-desc="A comfortable personal desktop for everyday life."><b>01</b><span class="flavour-icon">⌂</span><strong>Home</strong><small>Private · Familiar</small></button>
        <button class="flavour" data-flavour="Core" data-desc="A lean foundation for custom systems and virtual machines."><b>02</b><span class="flavour-icon">◌</span><strong>Core</strong><small>Small · Stable</small></button>
        <button class="flavour" data-flavour="Creator" data-desc="A production workspace for design, code, music, and media."><b>03</b><span class="flavour-icon">✦</span><strong>Creator</strong><small>Make · Ship</small></button>
        <button class="flavour" data-flavour="Academy" data-desc="A focused environment for learning and teaching."><b>04</b><span class="flavour-icon">▤</span><strong>Academy</strong><small>Learn · Grow</small></button>
        <button class="flavour" data-flavour="Cyber" data-desc="A controlled workspace for authorized defensive security work."><b>05</b><span class="flavour-icon">◇</span><strong>Cyber</strong><small>Assess · Report</small></button>
        <button class="flavour" data-flavour="Sentinel" data-desc="A long term, fleet aware environment for organizations."><b>06</b><span class="flavour-icon">◉</span><strong>Sentinel</strong><small>See · Coordinate</small></button>
        <button class="flavour selected" data-flavour="Gaming" data-desc="A performance focused desktop for your games and play."><b>07</b><span class="flavour-icon">⌁</span><strong>Gaming</strong><small>Play · Perform</small></button>
      </div>
    </section>

    <section class="section plans-section" id="plans"><div class="wrap"><div class="section-head"><div><p class="eyebrow">VPS session configuration</p><h2>Choose a session size.</h2></div><p>Compare the listed resources before starting. VPS usage is metered by the hour; hourly rates are not displayed on this page.</p></div><div class="plan-grid"><button class="plan" data-plan="Launch" data-ram="8 GB" data-cpu="4 cores" data-gpu="Shared GPU" data-storage="128 GB SSD"><span>01 / HOURLY</span><strong>Launch</strong><p>For a personal desktop, learning, and small projects.</p><ul><li>8 GB RAM</li><li>4 CPU cores</li><li>128 GB SSD</li><li>Hourly VPS usage</li></ul></button><button class="plan selected" data-plan="Build" data-ram="16 GB" data-cpu="8 cores" data-gpu="Performance GPU" data-storage="256 GB SSD"><span>02 / HOURLY</span><strong>Build</strong><p>For development, creation, and serious game sessions.</p><ul><li>16 GB RAM</li><li>8 CPU cores</li><li>Performance GPU</li><li>256 GB SSD</li></ul></button><button class="plan" data-plan="Power" data-ram="32 GB" data-cpu="12 cores" data-gpu="Dedicated GPU plug" data-storage="1 TB SSD"><span>03 / HOURLY</span><strong>Power</strong><p>For demanding builds, rendering, and high performance gaming.</p><ul><li>32 GB RAM</li><li>12 CPU cores</li><li>Dedicated GPU plug</li><li>1 TB SSD</li></ul></button></div></div></section>

    <section class="launch wrap" id="launch"><div class="launch-copy"><p class="eyebrow">VPS session</p><h2>Review your configuration.</h2><p id="flavourDescription">A performance focused desktop for your games and play.</p><div class="chosen"><span id="chosenFlavour">Gaming</span><i></i><span id="chosenPlan">Build</span></div></div><div class="seat-summary"><div class="summary-top"><span>HOURLY VPS CONFIGURATION</span><b id="seatState">READY TO START</b></div><div class="resource"><span>RAM</span><strong id="ram">16 GB</strong></div><div class="resource"><span>CPU</span><strong id="cpu">8 cores</strong></div><div class="resource"><span>GPU</span><strong id="gpu">Performance GPU</strong></div><div class="resource"><span>STORAGE</span><strong id="storage">256 GB SSD</strong></div><div class="resource"><span>BILLING</span><strong>Metered by the hour</strong></div><?php if ($signedIn): ?><button class="start-seat" id="startSeat">Start VPS session <span>→</span></button><?php else: ?><a class="start-seat" id="signInSeat" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Continue with Beyond ID <span>→</span></a><?php endif; ?></div></section>

    <section class="how" id="how"><div class="wrap"><p class="eyebrow">The VPS session flow</p><div class="how-grid"><h2>Configure. Start. Manage.</h2><div><b>01</b><h3>Sign in</h3><p>Continue with Beyond ID to keep your VPS session configuration with your account.</p></div><div><b>02</b><h3>Configure</h3><p>Choose a BIT OS environment and review the session resources and hourly usage model.</p></div><div><b>03</b><h3>Start your session</h3><p>Submit your VPS session request from the configuration summary.</p></div></div></div></section>
  </main>
  <footer class="wrap"><span>© <?= date('Y') ?> Beyond Imagination Technology</span><span>Beyond Webs v0.0.1 · BIT OS VPS sessions</span></footer>
  <script src="assets/app.js?v=0.0.2"></script>
</body>
</html>
