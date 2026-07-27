<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond Hoop');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
  <meta name="theme-color" content="#12091e">
  <title>Beyond Hoop | Beyond Games</title>
  <meta name="description" content="Hold, aim and release to sink rooftop shots in Beyond Hoop, an original skill-based basketball game from Beyond Games.">
  <link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
  <link rel="stylesheet" href="/beyond-games/assets/css/beyond-hoop.css?v=20260727-1">
</head>
<body class="bos-page hoop-page">
<main class="hoop-shell">
  <header class="hoop-header">
    <a href="/beyond-games/">← Beyond Games</a>
    <div><span>🏀</span><p><b>BEYOND HOOP</b><small>Rooftop shooting prototype</small></p></div>
    <button id="hoopSound" type="button" aria-pressed="true">Sound: On</button>
  </header>

  <section class="hoop-stage" id="hoopStage" aria-label="Beyond Hoop basketball game">
    <div class="hoop-hud">
      <span>SCORE <b id="hoopScore">0</b></span>
      <span>MADE <b id="hoopMade">0 / 0</b></span>
      <span>STREAK <b id="hoopStreak">0</b></span>
      <span>TIME <b id="hoopTime">60</b></span>
      <span>BEST <b id="hoopBest">0</b></span>
    </div>
    <canvas id="hoopGame" width="1100" height="650" aria-label="Interactive rooftop basketball court. Hold to build power, aim, then release to shoot.">Your browser needs canvas support to play Beyond Hoop.</canvas>
    <div class="shot-readout" aria-hidden="true">
      <span>AIM <b id="aimReadout">CENTRE</b></span>
      <div class="power-track"><i id="powerFill"></i><em></em></div>
      <span>POWER <b id="powerReadout">HOLD</b></span>
    </div>
    <p class="hoop-feedback" id="hoopFeedback" aria-live="polite">Hold the ball to charge your shot.</p>
    <div class="hoop-overlay" id="hoopOverlay">
      <div class="hoop-logo">B<span>🏀</span></div>
      <span>BEYOND GAMES · FIRST PLAYABLE BUILD</span>
      <h1 id="hoopOverlayTitle">Beyond Hoop</h1>
      <p id="hoopOverlayCopy">Own the rooftop. Hold to build power, aim through the city wind and release inside the gold zone.</p>
      <div class="hoop-how">
        <span><b>1</b> Hold / Space</span>
        <span><b>2</b> Aim left or right</span>
        <span><b>3</b> Release in gold</span>
      </div>
      <button id="hoopStart" type="button">Start shootaround</button>
      <small>Touch, mouse and keyboard supported · No purchase necessary</small>
    </div>
  </section>

  <section class="hoop-lower">
    <article>
      <span class="bos-kicker">Prototype modes</span>
      <h2>The first court is live.</h2>
      <div class="hoop-mode-grid">
        <div class="active"><span>01</span><b>Rooftop Rush</b><small>60-second score attack</small></div>
        <div><span>02</span><b>Corner King</b><small>Next build · five spots</small></div>
        <div><span>03</span><b>Beyond City</b><small>Roadmap · street tournament</small></div>
      </div>
    </article>
    <aside>
      <span class="bos-kicker">Local player profile</span>
      <h2><b id="hoopRewards">0</b> / 60 demo bit$</h2>
      <p>Earn prototype rewards from skill milestones. Progress stays on this device for now.</p>
      <div id="hoopAchievements" class="hoop-achievements"></div>
      <button id="hoopReset" type="button">Reset local progress</button>
    </aside>
  </section>
</main>
<script src="/beyond-games/assets/js/beyond-hoop.js?v=20260727-1"></script>
<?php bos_page_end(); ?>
