<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/ecosystem.php';
$wallet=beyond_app_bootstrap('DailyBreath');
$pdo=beyond_db();
$userId=(int)$_SESSION['user_id'];
$catalog=[
 'bible'=>['Bible','All ages','✝️',['Bible Foundations','Stories and Themes','Wisdom and Practice','Jesus and the Gospels','Faith in Action']],
 'tanakh'=>['Tanakh','All ages','✡️',['Torah Foundations','Prophets and Justice','Wisdom and Poetry','Covenant and Community','Faith in Action']],
 'quran'=>['Quran','All ages','☪️',['Quran Foundations','Prophets and Stories','Mercy and Justice','Prayer and Character','Faith in Action']],
];
$allAgesId=0;
try{
  $a=$pdo->prepare('SELECT id FROM academy_age_groups WHERE slug=?');$a->execute(['all-ages']);$allAgesId=(int)$a->fetchColumn();
  if(!$allAgesId){$pdo->prepare('INSERT INTO academy_age_groups(slug,name,min_age,max_age,icon,sort_order) VALUES(?,?,?,?,?,?)')->execute(['all-ages','All ages',NULL,NULL,'🌍',5]);$allAgesId=(int)$pdo->lastInsertId();}
  foreach($catalog as $traditionSlug=>[$traditionName,$ages,$icon,$modules]){
    foreach($modules as $offset=>$title){
      $number=$offset+1;$slug=$traditionSlug.'-module-'.$number;$q=$pdo->prepare('SELECT id FROM academy_courses WHERE slug=?');$q->execute([$slug]);$courseId=(int)$q->fetchColumn();
      if(!$courseId){$pdo->prepare('INSERT INTO academy_courses(slug,title,summary,is_free,is_published,sort_order) VALUES(?,?,?,?,1,?)')->execute([$slug,$title,"Ten guided $traditionName sacred-text lessons for all ages.",$number===1?1:0,($offset+1)*10+array_search($traditionSlug,array_keys($catalog))*100]);$courseId=(int)$pdo->lastInsertId();}
      $m=$pdo->prepare('SELECT 1 FROM academy_course_age_groups WHERE course_id=? AND age_group_id=?');$m->execute([$courseId,$allAgesId]);if(!$m->fetchColumn())$pdo->prepare('INSERT INTO academy_course_age_groups(course_id,age_group_id) VALUES(?,?)')->execute([$courseId,$allAgesId]);
      for($lesson=1;$lesson<=10;$lesson++){
        $l=$pdo->prepare('SELECT 1 FROM academy_lessons WHERE course_id=? AND lesson_number=?');$l->execute([$courseId,$lesson]);
        if(!$l->fetchColumn())$pdo->prepare('INSERT INTO academy_lessons(course_id,lesson_number,title,lesson_type,is_preview,is_published) VALUES(?,?,?,\'reading\',?,1)')->execute([$courseId,$lesson,$title.' · Lesson '.$lesson,$number===1?1:0]);
      }
    }
  }
}catch(Throwable $exception){error_log('DailyBreath Academy catalog bootstrap: '.$exception->getMessage());}
$subscribed=false;
try{$statement=$pdo->prepare("SELECT 1 FROM academy_subscriptions WHERE user_id=? AND status IN ('active','trialing') AND (current_period_end IS NULL OR current_period_end>=CURRENT_TIMESTAMP) LIMIT 1");$statement->execute([$userId]);$subscribed=(bool)$statement->fetchColumn();}catch(Throwable $exception){}
$selected=(string)($_GET['tradition']??'bible');if(!isset($catalog[$selected]))$selected='bible';
$statement=$pdo->prepare('SELECT c.* FROM academy_courses c JOIN academy_course_age_groups m ON m.course_id=c.id WHERE m.age_group_id=? AND c.slug LIKE ? AND c.is_published=1 ORDER BY c.sort_order,c.id');$statement->execute([$allAgesId,$selected.'-module-%']);$courses=$statement->fetchAll(PDO::FETCH_ASSOC);
$courseProgress=[];
foreach($courses as $course){
  $query=$pdo->prepare('SELECT COUNT(DISTINCT lesson_id) FROM academy_quiz_attempts WHERE user_id=? AND passed=1 AND lesson_id IN(SELECT id FROM academy_lessons WHERE course_id=?)');$query->execute([$userId,$course['id']]);$passedLessons=(int)$query->fetchColumn();
  $query=$pdo->prepare('SELECT 1 FROM academy_module_exam_attempts WHERE user_id=? AND course_id=? AND passed=1 LIMIT 1');$query->execute([$userId,$course['id']]);$courseProgress[(int)$course['id']]=['lessons'=>$passedLessons,'exam'=>(bool)$query->fetchColumn()];
}
[$selectedName,$selectedAges,$selectedIcon]=$catalog[$selected];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#f7faf6"><title>DailyBreath Sacred Text Academy</title><meta name="description" content="Guided Bible, Tanakh, and Quran learning for every age."><link rel="stylesheet" href="/dailybreath/academy.css?v=20260730-1"></head><body>
<header class="ba-nav">
  <a class="ba-brand" href="/dailybreath/"><span class="ba-mark">DB</span><span><strong>DailyBreath</strong><small>Sacred Text Academy</small></span></a>
  <nav aria-label="Academy navigation"><a class="active" href="/dailybreath/academy.php">Academy</a><a href="/dailybreath/scripture.php">Sacred Text Library</a><a href="/dailybreath/">DailyBreath home</a></nav>
</header>
<main class="ba-main">
  <section class="ba-hero">
    <div><span class="ba-kicker">SCRIPTURE · WISDOM · PRACTICE</span><h1>Grow deeper.<br>Live with purpose.</h1><p>Guided learning in the Bible, Tanakh, and Quran for every age, with knowledge checks, saved progress, and cumulative module exams.</p></div>
    <div class="ba-hero-card"><span>YOUR PATH</span><strong><?=$selectedIcon?> <?=e($selectedName)?></strong><small>All ages · 5 modules · 50 lessons</small></div>
  </section>
  <section class="ba-stats" aria-label="Academy overview"><div><b>3</b><span>sacred text paths</span></div><div><b>15</b><span>guided modules</span></div><div><b>150</b><span>lessons</span></div><div><b>80%</b><span>passing standard</span></div></section>
  <nav class="ba-paths" aria-label="Choose a sacred text path"><?php foreach($catalog as $slug=>[$name,$ages,$icon]):?><a class="<?=$slug===$selected?'active':''?>" href="?tradition=<?=e($slug)?>"><span><?=$icon?></span><div><strong><?=e($name)?></strong><small>All ages</small></div></a><?php endforeach;?></nav>
  <p class="ba-audience-note"><strong>Audience:</strong> Every Academy path is open to learners of all ages. Choose the sacred text you want to study; age-restricted wellness content remains separate.</p>
  <section class="ba-membership">
    <div><span class="ba-kicker"><?=$subscribed?'ACADEMY UNLOCKED':'START LEARNING'?></span><h2><?=$subscribed?'Every module is unlocked.':'Begin with Module 1.'?></h2><p><?=$subscribed?'Continue any sacred-text pathway and keep your saved progress.':'Start your selected sacred-text path, then unlock every module with a one-time payment.'?></p></div>
    <?php if(!$subscribed):?><a class="ba-button secondary" href="academy-subscribe.php">Unlock the Academy</a><?php endif;?>
  </section>
  <section class="ba-section-head"><div><span class="ba-kicker"><?=e(strtoupper($selectedName))?> PATHWAY</span><h2>Build understanding one module at a time.</h2></div><p>Every lesson includes three reflection and application practices before its check. Each module closes with one cumulative exam.</p></section>
  <section class="ba-modules">
    <?php foreach($courses as $index=>$course):$number=$index+1;$locked=!(bool)$course['is_free']&&!$subscribed;$progress=$courseProgress[(int)$course['id']]??['lessons'=>0,'exam'=>false];$percent=$progress['lessons']*10;?>
      <article class="ba-module">
        <div class="ba-module-top"><span class="ba-number"><?=str_pad((string)$number,2,'0',STR_PAD_LEFT)?></span><span class="ba-badge <?=$locked?'locked':''?>"><?=$locked?'Member':'Unlocked'?></span></div>
        <h3><?=e($course['title'])?></h3><p><?=e($course['summary'])?></p>
        <div class="ba-progress" aria-label="<?=$percent?>% complete"><span style="width:<?=$percent?>%"></span></div>
        <small><?=$progress['lessons']?>/10 checks passed<?=$progress['exam']?' · Exam passed':' · Exam pending'?></small>
        <a class="ba-button <?=$locked?'disabled':''?>" href="<?=$locked?'academy-subscribe.php':'course.php?course='.rawurlencode($course['slug']).'&lesson=1'?>"><?=$locked?'Unlock module':($progress['lessons']?'Continue learning':'Begin module')?> →</a>
      </article>
    <?php endforeach;?>
  </section>
</main>
<footer class="ba-footer"><strong>DailyBreath Sacred Text Academy</strong><span>All-ages learning · Educational faith content</span></footer>
<script src="/assets/js/visitor-analytics.js" defer></script></body></html>
