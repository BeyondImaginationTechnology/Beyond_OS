<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$slug = (string)($_GET['course'] ?? '');
$course = academy_course($slug);
if (!$course) {
    http_response_code(404);
    exit('Pathway not found.');
}
$progress = academy_progress($userId, $slug);
bos_page_start('Beyond Academy', $course['title'], $course['description']);
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell">
  <header class="academy-top"><a href="<?=e(beyond_url('academy/dashboard.php'))?>"><strong>← Learner dashboard</strong></a></header>
  <section class="course-head" style="--course:<?=e($course['accent'])?>">
    <span class="academy-kicker">Beyond Certificate Pathway</span>
    <h1><?=e($course['title'])?></h1>
    <p><?=e($course['description'])?></p>
    <div class="skill-list"><?php foreach ($course['skills'] as $skill): ?><span><?=e($skill)?></span><?php endforeach; ?></div>
  </section>
  <section class="lesson-list" aria-label="Course lessons">
    <?php foreach ($course['lessons'] as $index => $lesson): $number = $index + 1; $complete = in_array($number, $progress['completed_lessons'], true); $unlocked = academy_lesson_unlocked($userId, $slug, $number); ?>
    <a class="lesson-row" <?=$unlocked ? '' : 'aria-disabled="true"'?> href="<?=$unlocked ? e(beyond_url('academy/lesson.php?course=' . rawurlencode($slug) . '&lesson=' . $number)) : '#'?>">
      <span><?=$complete ? '✓' : $number?></span><div><h2><?=e(academy_lesson_title($lesson))?></h2><p><?=e(academy_lesson_summary($lesson))?></p><?php if(!empty($lesson['duration'])):?><small><?=e((string)$lesson['duration'])?> · narrated lesson · interactive practice</small><?php endif;?></div><b><?=$complete ? 'Completed' : ($unlocked ? 'Start →' : 'Locked')?></b>
    </a>
    <?php endforeach; ?>
  </section>
  <?php if(!empty($course['project'])):$project=$course['project'];?>
  <section class="academy-project">
    <div><span class="academy-kicker">Capstone project</span><h2><?=e((string)$project['title'])?></h2><p><?=e((string)$project['brief'])?></p></div>
    <ul><?php foreach((array)$project['deliverables'] as $deliverable):?><li><?=e((string)$deliverable)?></li><?php endforeach;?></ul>
  </section>
  <?php endif;?>
  <section class="assessment-card">
    <span class="academy-kicker">Final assessment</span><h2>Pass with 80% or higher.</h2>
    <p><?=$progress['completed']?> of <?=$progress['total']?> lessons completed. Your best assessment score is <?=$progress['best_score']?>/<?=count($course['questions'])?>.</p>
    <?php if ($progress['credential']): ?>
      <a class="academy-action" href="<?=e(beyond_url('academy/certificate.php?id=' . rawurlencode($progress['credential']['credential_id'])))?>">View certificate</a>
    <?php else: ?>
      <a class="academy-action" <?=$progress['completed'] !== $progress['total'] ? 'aria-disabled="true"' : ''?> href="<?=e(beyond_url('academy/assessment.php?course=' . rawurlencode($slug)))?>"><?=$progress['completed'] === $progress['total'] ? 'Take assessment' : 'Complete all lessons'?></a>
    <?php endif; ?>
  </section>
</main>
<?php bos_page_end(); ?>
