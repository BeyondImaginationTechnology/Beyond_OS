<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$slug = (string)($_GET['course'] ?? $_POST['course'] ?? '');
$course = academy_course($slug);
$lessonNumber = (int)($_GET['lesson'] ?? $_POST['lesson'] ?? 0);
if (!$course || $lessonNumber < 1 || $lessonNumber > count($course['lessons'])) {
    http_response_code(404);
    exit('Lesson not found.');
}
if (!academy_lesson_unlocked($userId, $slug, $lessonNumber)) {
    http_response_code(403);
    exit('Complete the previous lesson before continuing.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!academy_verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Please reload and try again.');
    }
    academy_complete_lesson($userId, $slug, $lessonNumber);
    $next = $lessonNumber < count($course['lessons'])
        ? 'lesson.php?course=' . rawurlencode($slug) . '&lesson=' . ($lessonNumber + 1)
        : 'pathway.php?course=' . rawurlencode($slug);
    header('Location: ' . $next);
    exit;
}
$lesson = $course['lessons'][$lessonNumber - 1];
$complete = in_array($lessonNumber, academy_completed_lessons($userId, $slug), true);
bos_page_start('Beyond Academy', $lesson[0], $lesson[1]);
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell lesson-content">
  <header class="academy-top"><a href="<?=e(beyond_url('academy/pathway.php?course=' . rawurlencode($slug)))?>"><strong>← <?=e($course['title'])?></strong></a><span>Lesson <?=$lessonNumber?> of <?=count($course['lessons'])?></span></header>
  <article>
    <span class="academy-kicker"><?=e($course['title'])?> · Lesson <?=$lessonNumber?></span>
    <h1><?=e($lesson[0])?></h1>
    <p><?=e($lesson[1])?></p>
    <h2>Put it into practice</h2>
    <p>Write down one example in your own words, complete a small real-life application, and check that you can explain why your approach works. This reflection prepares you for the final pathway assessment.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(academy_csrf())?>">
      <input type="hidden" name="course" value="<?=e($slug)?>">
      <input type="hidden" name="lesson" value="<?=$lessonNumber?>">
      <button class="academy-action" type="submit"><?=$complete ? 'Continue' : 'Mark lesson complete'?></button>
    </form>
  </article>
</main>
<?php bos_page_end(); ?>
