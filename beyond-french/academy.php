<?php
declare(strict_types=1);
require __DIR__.'/includes/functions.php';
$age=french_valid_age_group((string)($_GET['age']??($_SESSION['french_academy_age']??'kids')));$_SESSION['french_academy_age']=$age;
$groups=french_age_groups();$modules=french_academy_modules();$pageTitle='French Academy | Beyond French';
require __DIR__.'/includes/header.php';
?>
<div class="academy-wrap">
  <section class="academy-hero"><span class="eyebrow">BEYOND FRENCH ACADEMY</span><h1>Speak it. Test it. Keep going.</h1><p>Choose an age path and complete five practical course modules. Every module contains 10 guided lessons, a 10-question test after each lesson, and one final module exam.</p><div class="academy-metrics"><div><strong>5</strong><span>AGE PATHS</span></div><div><strong>5</strong><span>COURSE MODULES</span></div><div><strong>50</strong><span>LESSONS PER PATH</span></div><div><strong>55</strong><span>ASSESSMENTS PER PATH</span></div></div></section>
  <nav class="age-tabs" aria-label="Age group"><?php foreach($groups as $slug=>$group):?><a class="age-tab<?= $slug===$age?' active':''?>" href="?age=<?=h($slug)?>"><?=h($group['icon'].' '.$group['title'])?><small> <?=h($group['ages'])?></small></a><?php endforeach;?></nav>
  <p class="age-note"><strong><?=h($groups[$age]['title'])?> path:</strong> <?=h($groups[$age]['guidance'])?> <?=french_is_guest()?'Guests receive Lesson 1 of Greetings free; a Beyond ID unlocks the full Academy.':'Your Beyond ID unlocks the complete Academy.'?></p>
  <div class="module-grid"><?php $number=0;foreach($modules as $slug=>$module):$number++;$progress=french_academy_module_progress($age,$slug);$accessible=french_academy_module_accessible($slug);?>
    <article class="module-card"><div class="module-top"><span class="module-icon"><?=h($module['icon'])?></span><span class="academy-badge <?=!empty($module['free'])?'free':($accessible?'':'locked')?>"><?=french_is_guest()&&$slug==='greetings'?'1 free lesson':($accessible?'Full Academy':'Beyond ID')?></span></div><small>MODULE <?=$number?></small><h2><?=h($module['title'])?></h2><p><?=h($module['description'])?></p><div class="progress-track"><span style="width:<?=min(100,$progress['lessons_passed']*10)?>%"></span></div><div class="module-actions"><small><?=french_is_guest()&&$slug==='greetings'?'Lesson 1 included':$progress['lessons_passed'].'/10 lessons · '.($progress['exam_passed']?'Exam passed':'Exam pending')?></small><a class="academy-button <?=$accessible?'':'locked'?>" href="course.php?age=<?=h($age)?>&module=<?=h($slug)?>"><?=$accessible?'Open module':'Locked'?></a></div></article>
  <?php endforeach;?></div>
</div>
<?php require __DIR__.'/includes/footer.php';?>
