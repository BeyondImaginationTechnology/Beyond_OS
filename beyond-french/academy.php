<?php
declare(strict_types=1);
require __DIR__.'/includes/functions.php';
$age=french_valid_age_group((string)($_GET['age']??($_SESSION['french_academy_age']??'kids')));$_SESSION['french_academy_age']=$age;
$groups=french_age_groups();$modules=french_academy_modules();$pageTitle='French Academy | Beyond French';
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
<div class="academy-wrap">
  <section class="academy-hero"><span class="eyebrow">BEYOND FRENCH ACADEMY</span><h1>Speak it. Test it. Keep going.</h1><p>Choose a French learning path and complete five practical course modules. Every lesson includes three required speaking and writing practices before its 10-question test.</p><div class="academy-metrics"><div><strong>3</strong><span>FRENCH PATHS</span></div><div><strong>5</strong><span>COURSE MODULES</span></div><div><strong>50</strong><span>LESSONS PER PATH</span></div><div><strong>150</strong><span>PRACTICES PER PATH</span></div></div></section>
  <nav class="age-tabs" aria-label="Age group"><?php foreach($groups as $slug=>$group):?><a class="age-tab<?= $slug===$age?' active':''?>" href="?age=<?=h($slug)?>"><?=h($group['icon'].' '.$group['title'])?><small> <?=h(($group['level']??'').' · '.$group['ages'])?></small></a><?php endforeach;?></nav>
  <p class="age-note"><strong><?=h($groups[$age]['title'])?> · <?=h($groups[$age]['level']??'') ?>:</strong> 1 module is open now. <?=h($age==='kids'?'French ABC Foundations':'Greetings')?> has 10 free lessons; Food, Transportation &amp; Travel, Ocean, and Sports require an Academy unlock.</p>
  <div class="academy-path-action"><span>1 module open</span><a class="academy-button" href="course.php?age=<?=h($age)?>&module=greetings">Open <?=h($age==='kids'?'ABC Foundations':'Greetings')?> →</a></div>
  <?php if($tutorSet):?><section class="tutor-introduction" aria-labelledby="tutor-heading">
    <div class="tutor-introduction-copy"><span class="eyebrow">YOUR LANGUAGE TEAM</span><h2 id="tutor-heading"><?=h($tutorSet['heading'])?></h2><p><?=h($tutorSet['intro'])?></p></div>
    <div class="tutor-grid"><?php foreach($tutors as $tutor):?><article class="tutor-card"><img src="<?=h($frenchBase.$tutorSet['imagePath'].$tutor['image'])?>" alt="<?=h($tutor['name'].' — '.$tutor['language'].' tutor')?>" loading="lazy"><div><span><?=h($tutor['flag'].' '.$tutor['language'])?></span><h3><?=h($tutor['name'])?></h3><p><?=h($tutor['greeting'])?></p></div></article><?php endforeach;?></div>
  </section><?php endif;?>
  <div class="module-grid"><?php $number=0;foreach($modules as $slug=>$module):$number++;$progress=french_academy_module_progress($age,$slug);$accessible=french_academy_module_accessible($slug);?>
    <article class="module-card"><div class="module-top"><span class="module-icon"><?=h($module['icon'])?></span><span class="academy-badge <?=!empty($module['free'])?'free':($accessible?'':'locked')?>"><?=!empty($module['free'])?'OPEN NOW':($accessible?'ACADEMY UNLOCKED':'LOCKED')?></span></div><small>MODULE <?=$number?></small><h2><?=h($module['title'])?></h2><p><?=h($module['description'])?></p><div class="progress-track"><span style="width:<?=min(100,$progress['lessons_passed']*10)?>%"></span></div><div class="module-actions"><small><?=$progress['lessons_passed']?>/10 lessons · <?=($progress['exam_passed']?'Exam passed':'Exam pending')?></small><a class="academy-button <?=$accessible?'':'locked'?>" href="course.php?age=<?=h($age)?>&module=<?=h($slug)?>"><?=$accessible?'Open now':'Locked'?></a></div></article>
  <?php endforeach;?></div>
</div>
<?php require __DIR__.'/includes/footer.php';?>
