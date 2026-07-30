<?php
declare(strict_types=1);
require __DIR__.'/includes/functions.php';
$age=french_valid_age_group((string)($_GET['age']??$_POST['age']??'kids'));
$module=french_valid_module((string)($_GET['module']??$_POST['module']??'greetings'));
$lessonNumber=max(1,min(10,(int)($_GET['lesson']??$_POST['lesson']??1)));
$lesson=french_course_lesson($age,$module,$lessonNumber);
if(!$lesson||!french_academy_lesson_unlocked($age,$module,$lessonNumber)){
    http_response_code(403);$pageTitle='Lesson locked | French Academy';require __DIR__.'/includes/header.php';
    echo '<div class="academy-wrap"><section class="locked-panel"><h1>Lesson locked</h1><p>Pass the previous lesson test to continue.</p><a class="academy-button" href="course.php?age='.h($age).'&module='.h($module).'">Return to module</a></section></div>';
    require __DIR__.'/includes/footer.php';exit;
}
$practiceError='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['practice_round'])){
    if(!french_verify_csrf())$practiceError='Your session expired. Reload and try again.';
    elseif(french_record_academy_practice($age,$module,$lessonNumber,(int)$_POST['practice_round'],(string)($_POST['practice_response']??''))){
        header('Location: academy-lesson.php?'.http_build_query(['age'=>$age,'module'=>$module,'lesson'=>$lessonNumber,'practice_saved'=>(int)$_POST['practice_round']]).'#practice-path');exit;
    }else $practiceError='Add a complete response before saving this practice.';
}
$practiceResponses=french_academy_practice_responses($age,$module,$lessonNumber);
$practicePrompts=[
    ['Listen, then write it','Type the French phrase from memory. Compare spelling and accents with the lesson above.'],
    ['Change the situation','Write a short situation where you would use this phrase. Include who you are speaking to.'],
    ['Say it your way','Write a mini two-line conversation that uses the phrase naturally. Read it aloud once.'],
];
$practiceReady=count($practiceResponses)>=3||french_academy_lesson_passed($age,$module,$lessonNumber);
$pageTitle=$lesson['title'].' | French Academy';
require __DIR__.'/includes/header.php';
?>
<div class="academy-wrap">
 <a class="academy-back" href="course.php?age=<?=h($age)?>&module=<?=h($module)?>">← <?=h($lesson['module_title'])?></a>
 <article class="reading-card">
  <span class="eyebrow"><?=h($lesson['age_title'])?> · MODULE <?=array_search($module,array_keys(french_academy_modules()),true)+1?> · LESSON <?=$lessonNumber?></span>
  <h1><?=h($lesson['title'])?></h1>
  <div class="phrase-panel"><small><?=h($lesson['english'])?></small><strong lang="fr"><?=h($lesson['french'])?></strong><em><?=h($lesson['pronunciation'])?></em><button class="academy-button secondary" type="button" onclick="speechSynthesis.cancel();let u=new SpeechSynthesisUtterance(<?=json_encode($lesson['french'])?>);u.lang='fr-FR';u.rate=.82;speechSynthesis.speak(u)">🔊 Listen</button></div>
  <div class="lesson-sections"><section><h2>Learn</h2><p><?=h($lesson['teaching'])?></p><p><strong>For <?=h($lesson['age_title'])?>:</strong> <?=h($lesson['age_guidance'])?></p></section><section><h2>Practice</h2><p><?=h($lesson['practice'])?></p><p><strong>Culture:</strong> <?=h($lesson['culture'])?></p></section></div>
  <section class="french-practice-path" id="practice-path">
   <header><div><span class="eyebrow">REQUIRED PRACTICE</span><h2>Use the phrase three different ways.</h2></div><strong><?=count($practiceResponses)?>/3 complete</strong></header>
   <?php if($practiceError!==''):?><p class="practice-notice"><?=h($practiceError)?></p><?php elseif(isset($_GET['practice_required'])):?><p class="practice-notice">Complete all three practices before taking the test.</p><?php elseif(isset($_GET['practice_saved'])):?><p class="practice-notice success">Practice saved. Keep speaking.</p><?php endif;?>
   <div class="french-practice-grid">
    <?php foreach($practicePrompts as $index=>$prompt):$round=$index+1;$complete=isset($practiceResponses[$round]);?>
    <form class="french-practice-card <?=$complete?'complete':''?>" method="post">
     <input type="hidden" name="csrf_token" value="<?=h(french_csrf_token())?>"><input type="hidden" name="age" value="<?=h($age)?>"><input type="hidden" name="module" value="<?=h($module)?>"><input type="hidden" name="lesson" value="<?=$lessonNumber?>"><input type="hidden" name="practice_round" value="<?=$round?>">
     <span>Practice <?=$round?> <?=$complete?'✓':''?></span><h3><?=h($prompt[0])?></h3><p><?=h($prompt[1])?></p>
     <textarea name="practice_response" rows="4" minlength="3" required><?=h((string)($practiceResponses[$round]??''))?></textarea>
     <button class="academy-button" type="submit"><?=$complete?'Update practice':'Complete practice'?></button>
    </form>
    <?php endforeach;?>
   </div>
  </section>
  <div class="module-actions">
   <?php if(french_is_guest()):?><small>This is your free Academy lesson. Your three practices are available now.</small><a class="academy-button" href="../beyond-id/auth/register.php?app=beyond-french">Create Beyond ID to continue →</a>
   <?php elseif($practiceReady):?><small>Practice complete. Score 8/10 to unlock the next lesson.</small><a class="academy-button" href="academy-test.php?age=<?=h($age)?>&module=<?=h($module)?>&lesson=<?=$lessonNumber?>">Take lesson test →</a>
   <?php else:?><small>Complete all three practice activities to unlock the lesson test.</small><a class="academy-button locked" href="#practice-path">Test locked</a><?php endif;?>
  </div>
 </article>
</div>
<?php require __DIR__.'/includes/footer.php';?>
