<?php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/ecosystem.php';
$id = (int)($_GET['id'] ?? 0);
$lesson = $id ? lesson_by_id($id) : todays_lesson();
if ($lesson && !french_guest_daily_lesson_allowed($lesson)) {
    http_response_code(403);
    exit('Create a Beyond ID to practice archived lessons.');
}
if (!$lesson) {
    exit('No challenge found.');
}
$result='';$nextLesson=null;$reward=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!french_verify_csrf()){$result='Your session expired. Refresh and try again.';}
    else{$given=mb_strtolower(trim((string)($_POST['answer']??'')));$expected=mb_strtolower(trim((string)$lesson['answer']));if($given===$expected){$userId=(int)($_SESSION['user_id']??0);$nextLesson=french_mark_completed($userId,(int)$lesson['id']);$reward=beyond_award_reward($userId,'beyond-french','daily-lesson',(string)$lesson['id'],10,'Beyond French daily lesson completed — '.$lesson['english']);$result='Correct — lesson complete!'.(!empty($reward['awarded'])?' +10 bit$ earned.':'');}else{$result='Not quite. Listen once more and try again.';}}
}
$pageTitle = 'Conversation Challenge | Beyond French';
require __DIR__ . '/includes/header.php';
?>
<section class="section page-top challenge-page">
    <span class="eyebrow">SATURDAY CONVERSATION CHALLENGE</span>
    <h1>Put today's phrase into practice.</h1>
    <form class="challenge-card" method="post">
        <input type="hidden" name="csrf_token" value="<?= h(french_csrf_token()) ?>">
        <small>Today's prompt</small>
        <h2><?= h($lesson['challenge']) ?></h2>
        <input id="challenge-answer" name="answer" type="text" placeholder="Type your answer" required>
        <button id="check-answer" class="button primary">Check answer</button>
        <p id="challenge-result" class="result" aria-live="polite"><?= h($result) ?></p>
        <?php if($nextLesson):?><a class="button primary" href="lesson.php?id=<?= (int)$nextLesson['id'] ?>">Continue to next lesson →</a><?php endif;?>
        <details>
            <summary>Show answer</summary>
            <p><?= h($lesson['answer']) ?></p>
        </details>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
