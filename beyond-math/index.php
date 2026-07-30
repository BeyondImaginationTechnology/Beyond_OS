<?php
declare(strict_types=1);
session_start();
$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['beyond_user']);
$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Learner';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#07111f">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Beyond Math">
  <meta name="description" content="Beyond Math — step-by-step math lessons, adaptive practice, calculators and an AI learning coach inside Beyond Academy.">
  <title>Beyond Math — Math that meets you where you are</title>
  <link rel="manifest" href="/beyond-math/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/beyond-math/assets/img/beyond-math-logo.webp">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/beyond-math/assets/css/style.css?v=2.1">
</head>
<body class="math-entry">
<div class="math-grid-bg" aria-hidden="true"></div>
<header class="site-header">
  <a class="brand" href="/beyond-math/" aria-label="Beyond Math home">
    <img src="/beyond-math/assets/img/beyond-math-logo.webp" alt="Beyond Math logo">
    <span>Beyond Math <b>× Academy</b></span>
  </a>
  <nav class="nav" aria-label="Beyond Math navigation">
    <a href="#features">Features</a>
    <a href="#paths">Learning paths</a>
    <a href="/beyond-math/academy.php">Free lessons</a>
    <a href="/academy/">Beyond Academy</a>
  </nav>
</header>

<main>
  <section class="login-hero section-pad">
    <div class="login-hero-copy">
      <p class="eyebrow">Beyond Academy · Math for every level</p>
      <h1>Math that meets you where you are.</h1>
      <p class="hero-text">Build confidence through visual lessons, guided practice, real-life math and an AI coach that starts with hints instead of handing over the answer.</p>
      <div class="trust-row">
        <span>✓ Free lessons</span><span>✓ Guest tools</span><span>✓ Synced progress with Beyond ID</span>
      </div>
    </div>

    <aside class="login-panel" aria-label="Beyond Math access">
      <div class="login-panel-head">
        <span class="login-symbol">∑</span>
        <div><small>WELCOME TO</small><h2>Beyond Math</h2></div>
      </div>
      <?php if ($isLoggedIn): ?>
        <p class="login-copy">Welcome back, <?= htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8') ?>. Your learning path is ready.</p>
        <a class="btn primary wide" href="/beyond-math/dashboard.php">Continue learning</a>
      <?php else: ?>
        <p class="login-copy">Sign in with Beyond ID to sync progress, streaks, saved lessons and certificates.</p>
        <a class="btn primary wide" href="/beyond-id/auth/login.php?redirect=%2Fbeyond-math%2Fdashboard.php">Continue with Beyond ID</a>
      <?php endif; ?>
      <div class="login-divider"><span>or start free</span></div>
      <a class="btn secondary wide" href="/beyond-math/academy.php">Try a free lesson</a>
      <a class="btn ghost wide" href="/beyond-math/tools.php">Open calculator tools</a>
      <button class="btn ghost wide" id="installBeyondMath" type="button" hidden>Install Beyond Math</button>
      <p class="privacy-note">Guest access does not require an account. Beyond ID unlocks cloud-synced progress and personalized learning.</p>
    </aside>
  </section>

  <section id="features" class="section-pad">
    <div class="section-title"><p class="eyebrow">Built for understanding</p><h2>More than a calculator.</h2><p class="muted">Beyond Math combines structured lessons, practice and tools in one clear learning experience.</p></div>
    <div class="feature-grid login-features">
      <article><span>✦</span><h3>Step-by-step lessons</h3><p>Learn through examples, diagrams, short explanations and guided practice.</p></article>
      <article><span>◎</span><h3>AI Math Coach</h3><p>Ask for a hint, an easier explanation or a fresh practice question.</p></article>
      <article><span>↗</span><h3>Adaptive practice</h3><p>Practice adjusts to your accuracy, pace and skills that need more work.</p></article>
      <article><span>⌂</span><h3>Math for real life</h3><p>Budgeting, measurement, coding, careers, college preparation and everyday decisions.</p></article>
    </div>
  </section>

  <section id="paths" class="section-pad path-section">
    <div class="section-title left"><p class="eyebrow">One academy for everyone</p><h2>Choose a level, not an age label.</h2><p class="muted">Beyond Math is part of Beyond Academy and organizes learning by skill progression.</p></div>
    <div class="level-grid">
      <a href="/beyond-math/academy.php?age=preschool"><b>Early Learning</b><span>Numbers, shapes, patterns and time</span></a>
      <a href="/beyond-math/academy.php?age=kids"><b>Foundations</b><span>Operations, fractions and measurement</span></a>
      <a href="/beyond-math/academy.php?age=preteen"><b>Intermediate</b><span>Pre-algebra, ratios and geometry</span></a>
      <a href="/beyond-math/academy.php?age=teen"><b>Advanced</b><span>Algebra, functions, statistics and precalculus</span></a>
      <a href="/beyond-math/academy.php?age=adult"><b>Adult & Career</b><span>Everyday, financial and workplace math</span></a>
    </div>
  </section>
</main>
<footer class="footer"><span>© <?= date('Y') ?> Beyond Imagination Technology</span><a href="/academy/">Beyond Academy</a></footer>
<script src="/beyond-math/assets/js/app.js?v=2.3.3" defer></script><script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
