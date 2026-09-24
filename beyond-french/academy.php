<?php
declare(strict_types=1);
require __DIR__.'/includes/functions.php';
$groups=french_age_groups();$modules=french_academy_modules();$difficultyGroups=[];foreach($groups as $slug=>$group){$difficulty=strtolower((string)($group['level']??$slug));$difficultyGroups[$difficulty]=$slug;}
$requestedDifficulty=strtolower(trim((string)($_GET['difficulty']??'')));
$age=isset($difficultyGroups[$requestedDifficulty])?$difficultyGroups[$requestedDifficulty]:french_valid_age_group((string)($_GET['age']??($_SESSION['french_academy_age']??'kids')));
$_SESSION['french_academy_age']=$age;
$pageTitle='Academy | Beyond French';
$appShell = true;
require __DIR__.'/includes/header.php';
if($age==='kids'){$modules['greetings']['title']='French ABC Foundations';$modules['greetings']['description']='Start with the French alphabet through sound, movement, and repetition.';}
$tutorSets=[
  'kids'=>[
    'heading'=>'Meet your Kids French tutors',
    'intro'=>'Four friendly guides help students hear the same daily idea across French, Patois, Kreyòl, and Spanish.',
    'imagePath'=>'assets/images/tutors/',
  ],
  'teen'=>[
    'heading'=>'Meet your Teens French tutors',
    'intro'=>'Build confidence across four voices and cultures as you practice French in real-life situations.',
    'imagePath'=>'assets/images/tutors/high-school/',
  ],
  'adult'=>[
    'heading'=>'Meet your advanced French tutors',
    'intro'=>'Four expert guides support advanced French, cultural context, and confident conversation.',
    'imagePath'=>'assets/images/tutors/advanced/',
  ],
];
$tutors=[
  ['name'=>'Louis','language'=>'French','flag'=>'🇫🇷','greeting'=>'Bonjour !','image'=>'louis.jpg'],
  ['name'=>'Irie','language'=>'Jamaican Patois','flag'=>'🇯🇲','greeting'=>'Wah gwaan!','image'=>'irie.jpg'],
  ['name'=>'Jazzy','language'=>'Haitian Kreyòl','flag'=>'🇭🇹','greeting'=>'Sak pase!','image'=>'jazzy.jpg'],
  ['name'=>'Pablo','language'=>'Spanish','flag'=>'🇪🇸','greeting'=>'¡Hola!','image'=>'pablo.jpg'],
];
$tutorSet=$tutorSets[$age]??null;
?>
<?php
$starter=$modules['greetings'];$starterProgress=french_academy_module_progress($age,'greetings');
$totalLessons=0;$moduleProgress=[];$continueLesson=min(10,$starterProgress['lessons_passed']+1);$continueHref='academy-lesson.php?age='.h($age).'&amp;module=greetings&amp;lesson='.$continueLesson;$continueTitle=$starter['title'].' · Lesson '.$continueLesson;$continueCaption='Try it like a mini role-play.';
if($starterProgress['lessons_passed']>=10){$continueHref='academy-exam.php?age='.h($age).'&amp;module=greetings';$continueTitle=$starter['title'].' · Module exam';$continueCaption='You finished all 10 lessons. Take the exam.';}
foreach($modules as $slug=>$module){$moduleProgress[$slug]=french_academy_module_progress($age,$slug);$totalLessons+=$moduleProgress[$slug]['lessons_passed'];}
if($age==='kids'){$starter['title']='French ABC Foundations';$starter['description']='Start with French sounds and letters, then build confidence through short role-play.';}
?>
<div class="academy-wrap">
  <h1 class="academy-page-title">Academy</h1>
  <section class="academy-hero"><span class="academy-free-badge">✿ &nbsp; GREETINGS FREE</span><h2>Choose a path and start speaking.</h2><p>Learn through short role-play, listening, and speaking out loud.</p>
    <form class="academy-difficulty"><label for="academy-difficulty">Difficulty</label><select id="academy-difficulty" name="difficulty" onchange="this.form.submit()"><?php foreach($difficultyGroups as $level=>$slug):?><option value="<?=h($level)?>"<?=$slug===$age?' selected':''?>><?=h(ucfirst($level))?></option><?php endforeach;?></select><noscript><button type="submit">Choose</button></noscript></form>
  </section>
  <section class="academy-stats" aria-label="Your progress"><article class="academy-stat is-done"><span aria-hidden="true">✿</span><strong><?=$totalLessons?>/<?=count($modules)*10?></strong><small>LESSONS DONE</small></article><article class="academy-stat"><span aria-hidden="true">🔓</span><strong><?=count(array_filter($modules,static fn($module):bool=>!empty($module['free'])||french_academy_has_full_access()))?></strong><small>MODULES OPEN</small></article></section>
  <section class="academy-continue"><span>CONTINUE ACADEMY</span><a href="<?=$continueHref?>"><span aria-hidden="true">▶</span><span><strong><?=h($continueTitle)?></strong><small><?=h($continueCaption)?></small></span><b aria-hidden="true">›</b></a></section>
  <p class="academy-id-note"><span aria-hidden="true">✦</span> Save your progress with <strong>Beyond ID</strong>. <a href="../beyond-id/auth/login.php">Sign in</a> or <a href="../beyond-id/auth/register.php?app=beyond-french">create an account</a>.</p>
  <section class="academy-catalog" aria-label="Academy modules"><?php $number=0;foreach($modules as $slug=>$module):$number++;$progress=$moduleProgress[$slug];$accessible=french_academy_module_accessible($slug);if($slug==='greetings')$module=$starter;?><article class="academy-course-card<?=$accessible?'':' is-locked'?>"><header class="academy-course-head"><span class="academy-course-icon"><?=h($module['icon'])?></span><div><h2><?=h($module['title'])?></h2><p><?=h($module['description'])?></p></div><?php if(!$accessible):?><span class="academy-course-lock" aria-label="Locked">🔒</span><?php endif;?></header><div class="academy-course-meter"><span><i style="width:<?=min(100,$progress['lessons_passed']*10)?>%"></i></span><small><?=$progress['lessons_passed']?> / 10</small></div><div class="academy-lesson-list"><?php for($lessonNumber=1;$lessonNumber<=10;$lessonNumber++):$lesson=french_course_lesson($age,$slug,$lessonNumber);$passed=french_academy_lesson_passed($age,$slug,$lessonNumber);$unlocked=french_academy_lesson_unlocked($age,$slug,$lessonNumber);?><a class="academy-lesson-item<?=$unlocked?' is-open':' is-locked'?><?=$passed?' is-complete':''?>" href="<?=$unlocked?'academy-lesson.php?age='.h($age).'&amp;module='.h($slug).'&amp;lesson='.$lessonNumber:'#'?>"<?=$unlocked?'':' aria-disabled="true" tabindex="-1"'?><span class="academy-lesson-number"><?=$lessonNumber?></span><span class="academy-lesson-copy"><strong><?=h($lesson['title']??'Lesson '.$lessonNumber)?></strong><small>Try it like a mini role-play.</small></span><span class="academy-lesson-state" aria-hidden="true"><?=$passed?'✓':($unlocked?'›':'🔒')?></span></a><?php endfor;?></div></article><?php endforeach;?></section>
  <?php if($tutorSet):?><details class="academy-guides"><summary>Meet your four language guides</summary><div class="tutor-grid"><?php foreach($tutors as $tutor):?><article class="tutor-card"><img src="<?=h($frenchBase.$tutorSet['imagePath'].$tutor['image'])?>" alt="<?=h($tutor['name'].' — '.$tutor['language'].' tutor')?>" loading="lazy"><div><span><?=h($tutor['flag'].' '.$tutor['language'])?></span><h3><?=h($tutor['name'])?></h3><p><?=h($tutor['greeting'])?></p></div></article><?php endforeach;?></div></details><?php endif;?>
</div>
<?php require __DIR__.'/includes/footer.php';?>
