<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$courses = academy_courses();
$progressByCourse = [];
$completedCredentials = 0;
$completedLessons = 0;
foreach ($courses as $slug => $course) {
    $progressByCourse[$slug] = academy_progress($userId, $slug);
    $completedLessons += $progressByCourse[$slug]['completed'];
    $completedCredentials += $progressByCourse[$slug]['credential'] ? 1 : 0;
}
$badges = academy_badges($userId);
bos_page_start('Beyond Academy', 'Learner Dashboard', 'Track course progress, assessments, badges, and Beyond-issued certificates.');
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell">
  <header class="academy-top">
    <a href="<?=e(beyond_url('academy/'))?>"><strong>BEYOND ACADEMY</strong></a>
    <nav aria-label="Learner navigation">
      <a class="academy-pill" href="#pathways">Pathways</a>
      <a class="academy-pill" href="#badges">Badges</a>
      <a class="academy-pill" href="<?=e(beyond_url('beyond-id/dashboard/'))?>">Beyond ID</a>
    </nav>
  </header>
  <section class="academy-banner">
    <span class="academy-kicker">Your learner dashboard</span>
    <h1>Learn it.<br>Prove it.</h1>
    <p>Complete lessons, pass each pathway assessment with 80% or higher, and earn a verifiable Beyond-issued certificate connected to your Beyond ID.</p>
  </section>
  <section class="academy-summary" aria-label="Learning summary">
    <div class="academy-stat"><b><?=$completedLessons?>/15</b><span>lessons completed</span></div>
    <div class="academy-stat"><b><?=$completedCredentials?>/3</b><span>certificates earned</span></div>
    <div class="academy-stat"><b><?=count($badges)?></b><span>Beyond ID badges</span></div>
  </section>
  <p class="academy-notice"><strong>Credential statement:</strong> These are Beyond-issued skills certificates. They confirm completion and assessment performance but do not represent government accreditation, a degree, or a professional licence.</p>
  <section id="pathways" aria-labelledby="pathways-title">
    <span class="academy-kicker">Launch pathways</span>
    <h2 id="pathways-title">Choose what to build next.</h2>
    <div class="path-grid">
      <?php foreach ($courses as $slug => $course): $progress = $progressByCourse[$slug]; ?>
      <a class="path-card" style="--course:<?=e($course['accent'])?>" href="<?=e(beyond_url('academy/pathway.php?course=' . rawurlencode($slug)))?>">
        <span class="path-icon" aria-hidden="true"><?=e($course['icon'])?></span>
        <h2><?=e($course['title'])?></h2>
        <p><?=e($course['description'])?></p>
        <div class="path-progress" aria-label="<?=$progress['percent']?>% complete"><span style="width:<?=$progress['percent']?>%"></span></div>
        <footer><small><?=$progress['completed']?> of <?=$progress['total']?> lessons</small><b><?=$progress['credential'] ? 'Earned ✓' : ($progress['pending_credential'] ? 'Under review' : $progress['percent'] . '%')?></b></footer>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <section id="badges" style="margin-top:46px" aria-labelledby="badges-title">
    <span class="academy-kicker">Beyond ID achievements</span>
    <h2 id="badges-title">Your badges</h2>
    <?php if ($badges): ?><div class="badge-grid">
      <?php foreach ($badges as $badge): $course = academy_course((string)$badge['badge_slug']); ?>
      <article class="badge-card"><span class="path-icon" style="--course:<?=e($course['accent'] ?? '#8b5cf6')?>"><?=e($course['icon'] ?? '✓')?></span><b><?=e($badge['title'])?></b><small>Awarded <?=e(date('M j, Y', strtotime((string)$badge['awarded_at'])))?></small></article>
      <?php endforeach; ?>
    </div><?php else: ?><p>Complete your first pathway assessment to earn a badge.</p><?php endif; ?>
  </section>
</main>
<?php bos_page_end(); ?>
