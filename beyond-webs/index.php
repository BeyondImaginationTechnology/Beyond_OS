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
  <meta name="theme-color" content="#080b14">
  <meta name="description" content="Beyond Webs gives you a cloud seat for every BIT OS flavour.">
  <title>Beyond Webs — Your BIT OS cloud seat</title>
  <link rel="stylesheet" href="assets/app.css?v=0.1.0">
  <link rel="stylesheet" href="assets/overrides.css?v=0.1.0">
</head>
<body>
  <header class="site-header wrap">
    <a class="brand" href="<?= $websOrigin !== '' ? '/' : '/beyond-webs/' ?>" aria-label="Beyond Webs home"><span class="brand-mark">B</span><span>BEYOND <b>WEBS</b><small>BIT OS CLOUD</small></span></a>
    <nav aria-label="Primary navigation"><a href="#flavours">Flavours</a><a href="#plans">Hardware tiers</a><a href="#how">How it works</a></nav>
    <?php if ($signedIn): ?><a class="account" href="#launch"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> <span>↗</span></a><?php else: ?><a class="account" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Sign in with Beyond ID <span>↗</span></a><?php endif; ?>
  </header>

  <main>
    <section class="hero wrap">
      <div class="hero-copy">
        <p class="eyebrow"><i></i> Beyond Webs App · v0.1</p>
        <h1>Your <em>BIT OS.</em><br>Always ready.</h1>
        <p class="lede">A cloud hosting and development platform for BIT OS. Start a private cloud seat, pick your flavour, and work as naturally as you would on a local machine.</p>
        <div class="hero-actions"><a class="button primary" href="#launch">Configure your seat <span>→</span></a><a class="button ghost" href="#flavours">See BIT OS flavours</a></div>
        <p class="identity"><span>✦</span> One Beyond ID connects your seat, subscription, and workspace.</p>
      </div>
      <div class="workspace-preview" aria-label="BIT OS cloud workspace preview">
        <div class="preview-top"><span class="lights"><i></i><i></i><i></i></span><span>BEYOND WEBS / CLOUD SEAT</span><b>● ONLINE</b></div>
        <div class="preview-body">
          <aside><strong>B</strong><span class="selected">⌘</span><span>▦</span><span>◈</span><span>⚙</span></aside>
          <section><div class="terminal-line"><span>bit@cloud</span>:~$ session status</div><div class="checks"><p>✓ BIT OS Gaming is ready</p><p>✓ GPU stream attached</p><p>✓ 16 GB RAM allocated</p><p>✓ 256 GB SSD volume mounted</p></div><article class="session-card"><small>YOUR CLOUD SEAT</small><strong>victus-01</strong><div class="usage"><span style="width:61%"></span></div><p>West coast region · Low latency</p></article></section>
        </div>
        <div class="preview-bottom"><span>SESSION PROTECTED</span><span>BIT GAMING 0.1</span></div>
      </div>
    </section>

    <section class="proof wrap"><div><b>7</b><span>BIT OS flavours</span></div><div><b>1</b><span>Beyond ID</span></div><div><b>∞</b><span>Build from anywhere</span></div></section>

    <section class="section wrap" id="flavours">
      <div class="section-head"><div><p class="eyebrow">Choose your environment</p><h2>A seat shaped around your work.</h2></div><p>Each cloud seat starts from a focused BIT OS flavour. You choose the operating environment; your subscription determines its resources.</p></div>
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

    <section class="section plans-section" id="plans"><div class="wrap"><div class="section-head"><div><p class="eyebrow">Choose your power</p><h2>Scale the machine, keep the same workspace.</h2></div><p>Move to a higher tier when your projects need more memory, CPU, GPU, or SSD capacity.</p></div><div class="plan-grid"><button class="plan" data-plan="Launch" data-ram="8 GB" data-cpu="4 cores" data-gpu="Shared GPU" data-storage="128 GB SSD"><span>01 / START</span><strong>Launch</strong><p>For a personal desktop, learning, and small projects.</p><ul><li>8 GB RAM</li><li>4 CPU cores</li><li>128 GB SSD</li></ul></button><button class="plan selected" data-plan="Build" data-ram="16 GB" data-cpu="8 cores" data-gpu="Performance GPU" data-storage="256 GB SSD"><span>02 / RECOMMENDED</span><strong>Build</strong><p>For development, creation, and serious game sessions.</p><ul><li>16 GB RAM</li><li>8 CPU cores</li><li>Performance GPU</li><li>256 GB SSD</li></ul></button><button class="plan" data-plan="Power" data-ram="32 GB" data-cpu="12 cores" data-gpu="Dedicated GPU" data-storage="1 TB SSD"><span>03 / MAXIMUM</span><strong>Power</strong><p>For demanding builds, rendering, and high performance gaming.</p><ul><li>32 GB RAM</li><li>12 CPU cores</li><li>Dedicated GPU</li><li>1 TB SSD</li></ul></button></div></div></section>

    <section class="launch wrap" id="launch"><div class="launch-copy"><p class="eyebrow">Your cloud seat</p><h2>Ready when you are.</h2><p id="flavourDescription">A performance focused desktop for your games and play.</p><div class="chosen"><span id="chosenFlavour">Gaming</span><i></i><span id="chosenPlan">Build</span></div></div><div class="seat-summary"><div class="summary-top"><span>SEAT CONFIGURATION</span><b id="seatState">READY TO START</b></div><div class="resource"><span>RAM</span><strong id="ram">16 GB</strong></div><div class="resource"><span>CPU</span><strong id="cpu">8 cores</strong></div><div class="resource"><span>GPU</span><strong id="gpu">Performance GPU</strong></div><div class="resource"><span>STORAGE</span><strong id="storage">256 GB SSD</strong></div><?php if ($signedIn): ?><button class="start-seat" id="startSeat">Start cloud seat <span>→</span></button><?php else: ?><a class="start-seat" id="signInSeat" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Continue with Beyond ID <span>→</span></a><?php endif; ?></div></section>

    <section class="how" id="how"><div class="wrap"><p class="eyebrow">One simple connection</p><div class="how-grid"><h2>Sign in. Choose. Start.</h2><div><b>01</b><h3>Sign in with Beyond ID</h3><p>Your identity keeps your cloud seat and subscription connected.</p></div><div><b>02</b><h3>Choose a BIT OS flavour</h3><p>Start with the environment that matches what you want to do.</p></div><div><b>03</b><h3>Use it like it is local</h3><p>Open your desktop and get to work from wherever you are.</p></div></div></div></section>
  </main>
  <footer class="wrap"><span>© <?= date('Y') ?> Beyond Imagination Technology</span><span>Beyond Webs v0.1 · BIT OS cloud seats</span></footer>
  <script src="assets/app.js?v=0.1.0"></script>
</body>
</html>
