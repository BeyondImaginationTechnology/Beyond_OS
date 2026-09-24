<?php
declare(strict_types=1);
require __DIR__.'/includes/functions.php';
$age=french_valid_age_group((string)($_GET['age']??($_SESSION['french_academy_age']??'kids')));$_SESSION['french_academy_age']=$age;
$groups=french_age_groups();$modules=french_academy_modules();$pageTitle='French Academy | Beyond French';
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
<?php $starter=$modules['greetings'];$starterProgress=french_academy_module_progress($age,'greetings'); ?>
<div class="academy-wrap">
  <section class="academy-hero"><span class="eyebrow">BEYOND FRENCH ACADEMY</span><h1>Ready for your next lesson?</h1><p>Choose your learning path and jump straight into French.</p></section>
  <nav class="age-tabs" aria-label="Choose your learning path"><?php foreach($groups as $slug=>$group):?><a class="age-tab<?= $slug===$age?' active':''?>" href="?age=<?=h($slug)?>"><?=h($group['icon'].' '.$group['title'])?><small><?=h($group['ages'])?></small></a><?php endforeach;?></nav>
  <p class="academy-id-note"><span aria-hidden="true">✦</span> Save your progress with <strong>Beyond ID</strong>. <a href="../beyond-id/auth/login.php">Sign in</a> or <a href="../beyond-id/auth/register.php?app=beyond-french">create an account</a>.</p>
  <section class="academy-start-card" aria-labelledby="academy-start-title"><div><span class="academy-start-kicker">YOUR FIRST MODULE · 10 LESSONS</span><h2 id="academy-start-title"><?=h($starter['title'])?></h2><p><?=h($starter['description'])?></p><div class="academy-start-progress"><span class="progress-track"><span style="width:<?=min(100,$starterProgress['lessons_passed']*10)?>%"></span></span><small><?=$starterProgress['lessons_passed']?> / 10 lessons</small></div></div><a class="academy-button" href="course.php?age=<?=h($age)?>&amp;module=greetings"><?= $starterProgress['lessons_passed']>0 ? 'Continue learning' : 'Start learning' ?> <span aria-hidden="true">→</span></a></section>
  <details class="academy-explore"><summary>Explore the other <?=count($modules)-1?> modules</summary><div class="academy-module-list"><?php $number=0;foreach($modules as $slug=>$module):$number++;if($slug==='greetings')continue;$accessible=french_academy_module_accessible($slug);?><article class="academy-module-row"><span class="module-icon"><?=h($module['icon'])?></span><div><small>MODULE <?=$number?> · <?= $accessible ? 'AVAILABLE' : 'LOCKED' ?></small><h3><?=h($module['title'])?></h3></div><?php if($accessible):?><a href="course.php?age=<?=h($age)?>&amp;module=<?=h($slug)?>">Open →</a><?php else:?><span class="academy-module-locked">Locked</span><?php endif;?></article><?php endforeach;?></div></details>
  <?php if($tutorSet):?><details class="academy-guides"><summary>Meet your four language guides</summary><div class="tutor-grid"><?php foreach($tutors as $tutor):?><article class="tutor-card"><img src="<?=h($frenchBase.$tutorSet['imagePath'].$tutor['image'])?>" alt="<?=h($tutor['name'].' — '.$tutor['language'].' tutor')?>" loading="lazy"><div><span><?=h($tutor['flag'].' '.$tutor['language'])?></span><h3><?=h($tutor['name'])?></h3><p><?=h($tutor['greeting'])?></p></div></article><?php endforeach;?></div></details><?php endif;?>
</div>
<?php require __DIR__.'/includes/footer.php';?>
