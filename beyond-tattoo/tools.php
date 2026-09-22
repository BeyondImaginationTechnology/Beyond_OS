<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
$pageTitle = 'Tools — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<div class="app-shell">
  <header class="app-header"><div class="container app-header-inner"><a class="brand" href="index.php"><span class="brand-badge">B</span><span>Beyond <b>Tools</b></span></a><a class="btn btn-secondary" href="index.php">Back to library</a></div></header>
  <main class="container dashboard">
    <section class="panel"><span class="eyebrow">Beyond Tattoo 0.2</span><h1 class="section-title">Studio tools.</h1><p class="section-copy">Prepare, compare, practice and deliver stencil work from one focused workspace.</p></section>
    <section class="cards four" style="margin-top:18px">
      <a class="card" href="stencil-editor.php"><div class="card-icon">✎</div><h3>Stencil Editor</h3><p>Resize, rotate, mirror, mark up and prepare a stencil for transfer.</p></a>
      <a class="card" href="../beyond-games/tattoo-master.php"><div class="card-icon">✦</div><h3>Tattoo Master</h3><p>Practice tracing, pressure, placement and client comfort.</p></a>
      <a class="card" href="stencils.php"><div class="card-icon">▦</div><h3>Release Calendar</h3><p>Browse the first 55 drops by collection, date, status and asset availability.</p></a>
      <a class="card" href="stencil-of-day.php"><div class="card-icon">↓</div><h3>Print &amp; Download</h3><p>Open the current stencil pack and prepare it for studio use.</p></a>
    </section>
  </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
