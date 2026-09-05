<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$slug = (string)($_GET['course'] ?? $_POST['course'] ?? '');
$course = academy_course($slug);
if (!$course) {
    http_response_code(404);
    exit('Assessment not found.');
}
$progress = academy_progress($userId, $slug);
if ($progress['completed'] !== $progress['total']) {
    http_response_code(403);
    exit('Complete every pathway lesson before taking this assessment.');
}
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!academy_verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Please reload and try again.');
    }
    $result = academy_record_assessment($userId, $slug, (array)($_POST['answers'] ?? []));
}
bos_page_start('Beyond Academy', $course['title'] . ' Assessment', 'Complete the final Beyond Certificate assessment.');
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell lesson-content">
  <header class="academy-top"><a href="<?=e(beyond_url('academy/pathway.php?course=' . rawurlencode($slug)))?>"><strong>← <?=e($course['title'])?></strong></a></header>
  <section class="course-head" style="--course:<?=e($course['accent'])?>"><span class="academy-kicker">Final assessment</span><h1><?=e($course['title'])?></h1><p>Answer every question. A score of <?=ceil(count($course['questions']) * .8)?>/<?=count($course['questions'])?> is required to earn the certificate.</p></section>
  <?php if ($result): ?>
    <section class="result <?=$result['passed'] ? 'pass' : ''?>" role="status"><h2><?=$result['passed'] ? 'You passed!' : 'Review and try again.'?></h2><p>Your score: <strong><?=$result['score']?>/<?=$result['total']?></strong></p>
    <?php if ($result['credential']): ?>
      <?php if (($result['credential']['approval_status'] ?? 'pending') === 'approved'): ?><a class="academy-action" href="<?=e(beyond_url('academy/certificate.php?id=' . rawurlencode($result['credential']['credential_id'])))?>">Open your certificate</a>
      <?php else: ?><p><strong>Your certificate request is under review.</strong> You should receive your certificate within 24 hours.</p><?php endif; ?>
    <?php endif; ?></section>
    <section class="assessment-review" aria-label="Assessment answer review"><h2>Answer review</h2><?php foreach($result['review'] as $index=>$review):?><article class="<?=$review['correct']?'correct':'incorrect'?>"><span><?=$review['correct']?'✓':'Review'?></span><div><strong><?=($index+1)?>. <?=e($review['question'])?></strong><?php if(!$review['correct']):?><p>Your answer: <?=e($review['given']?:'No answer')?> · Correct answer: <b><?=e($review['answer'])?></b></p><?php else:?><p><?=e($review['answer'])?></p><?php endif;?></div></article><?php endforeach;?></section>
  <?php endif; ?>
  <?php if (!$result || !$result['passed']): ?><form class="assessment-form" method="post">
    <input type="hidden" name="csrf" value="<?=e(academy_csrf())?>"><input type="hidden" name="course" value="<?=e($slug)?>">
    <?php foreach ($course['questions'] as $index => $question): ?><fieldset><legend><?=($index + 1)?>. <?=e($question['q'])?></legend>
      <?php foreach ($question['options'] as $option): ?><label class="answer"><input required type="radio" name="answers[<?=$index?>]" value="<?=e($option)?>"><span><?=e($option)?></span></label><?php endforeach; ?>
    </fieldset><?php endforeach; ?>
    <button class="academy-action" type="submit">Submit assessment</button>
  </form><?php endif; ?>
</main>
<?php bos_page_end(); ?>
