<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/ecosystem.php';
$wallet=beyond_app_bootstrap('DailyBreath', false);
$pdo=beyond_db();
$userId=(int)($_SESSION['user_id']??0);
$slug=preg_replace('/[^a-z0-9-]/','',strtolower((string)($_GET['course']??$_POST['course']??'bible-module-1')));
if(!preg_match('/^(?:bible|tanakh|quran)-module-[1-5]$/',$slug)){http_response_code(404);exit('Course not found.');}
$guest=$userId<1;
if($guest && ((int)substr($slug,-1)>1 || $_SERVER['REQUEST_METHOD']==='POST')){
  $_SESSION['beyond_return_to']=beyond_return_url();
  header('Location: /beyond-id/auth/login.php?app=dailybreath&required=1');exit;
}
$lessonNo=max(1,(int)($_GET['lesson']??$_POST['lesson']??1));
$s=$pdo->prepare('SELECT * FROM academy_courses WHERE slug=? AND is_published=1 LIMIT 1');$s->execute([$slug]);$course=$s->fetch(PDO::FETCH_ASSOC);
if(!$course){http_response_code(404);exit('Course not found.');}
$s=$pdo->prepare('SELECT * FROM academy_lessons WHERE course_id=? AND is_published=1 ORDER BY lesson_number');$s->execute([$course['id']]);$lessons=$s->fetchAll(PDO::FETCH_ASSOC);
$currentIndex=0;foreach($lessons as $index=>$candidate)if((int)$candidate['lesson_number']===$lessonNo)$currentIndex=$index;
$current=$lessons[$currentIndex]??null;if(!$current){http_response_code(404);exit('Lesson not found.');}

// Every lesson after the first requires a passing result on the previous lesson.
if($currentIndex>0){
  $previous=$lessons[$currentIndex-1];$gate=$pdo->prepare('SELECT 1 FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=? AND passed=1 LIMIT 1');$gate->execute([$userId,$previous['id']]);
  if(!$gate->fetchColumn()){header('Location: course.php?course='.rawurlencode($slug).'&lesson='.(int)$previous['lesson_number'].'&locked=1');exit;}
}
$tradition=explode('-',$slug)[0];$textName=['bible'=>'Bible','tanakh'=>'Tanakh','quran'=>'Quran'][$tradition]??'sacred text';
function sacred_text_quiz(string $textName,string $moduleTitle,int $lessonNo): array {
  $topic=$moduleTitle.' · Lesson '.$lessonNo;
  return [
    ["What is the focus of $topic?",[$topic,'A random topic','A score only','A private setting'],0],
    ["Which source should guide this lesson?",[$textName,'A rumor','A ranking','A sales page'],0],
    ['What is the best way to approach sacred reading?',['Read slowly and thoughtfully','Rush to finish','Skip reflection','Memorize without understanding'],0],
    ['What should a learner look for in the text?',['Wisdom, themes, and meaning','Only difficult words','A single correct feeling','A competition'],0],
    ['What makes reflection useful?',['Connecting insight to life','Avoiding questions','Comparing learners','Repeating a slogan'],0],
    ['What should follow understanding?',['A thoughtful and compassionate action','Judgment of others','Immediate certainty','No response'],0],
    ['How can questions support learning?',['With humility and curiosity','By ending the conversation','By mocking others','By skipping the text'],0],
    ['What belongs in a respectful sacred-text study?',['Careful reading and respect','Pressure and ridicule','Personal attacks','Rushing'],0],
    ['What does this Academy path welcome?',['Learners of every age','Only one age group','Only experts','Only teachers'],0],
    ['What is the goal of this lesson?',['Grow in understanding and practice','Win an argument','Finish without reading','Replace personal reflection'],0],
  ];
}
$quiz=sacred_text_quiz($textName,(string)$current['title'],(int)$current['lesson_number']);
$message='';$score=null;$passed=false;$reward=null;
$driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if($driver==='sqlite'){
  $pdo->exec('CREATE TABLE IF NOT EXISTS academy_practice_attempts(user_id INTEGER NOT NULL,lesson_id INTEGER NOT NULL,round_number INTEGER NOT NULL,response_text TEXT NOT NULL,completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(user_id,lesson_id,round_number))');
}else{
  $pdo->exec('CREATE TABLE IF NOT EXISTS academy_practice_attempts(user_id BIGINT NOT NULL,lesson_id BIGINT NOT NULL,round_number TINYINT NOT NULL,response_text TEXT NOT NULL,completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(user_id,lesson_id,round_number)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='practice'){
  $round=(int)($_POST['round']??0);$response=trim((string)($_POST['response']??''));
  if(!verify_csrf_token($_POST['csrf']??null))$message='Your session expired. Reload the lesson and try again.';
  elseif($round<1||$round>3||mb_strlen($response)<3)$message='Add a meaningful response before completing this practice.';
  else{
    if($driver==='sqlite')$pdo->prepare('INSERT INTO academy_practice_attempts(user_id,lesson_id,round_number,response_text) VALUES(?,?,?,?) ON CONFLICT(user_id,lesson_id,round_number) DO UPDATE SET response_text=excluded.response_text,updated_at=CURRENT_TIMESTAMP')->execute([$userId,$current['id'],$round,$response]);
    else $pdo->prepare('INSERT INTO academy_practice_attempts(user_id,lesson_id,round_number,response_text) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE response_text=VALUES(response_text),updated_at=CURRENT_TIMESTAMP')->execute([$userId,$current['id'],$round,$response]);
    header('Location: course.php?'.http_build_query(['course'=>$slug,'lesson'=>$lessonNo,'practice_saved'=>$round]).'#practice-path');exit;
  }
}
$practiceQuery=$pdo->prepare('SELECT round_number,response_text FROM academy_practice_attempts WHERE user_id=? AND lesson_id=? ORDER BY round_number');$practiceQuery->execute([$userId,$current['id']]);$practiceResponses=[];foreach($practiceQuery->fetchAll(PDO::FETCH_ASSOC) as $row)$practiceResponses[(int)$row['round_number']]=(string)$row['response_text'];
$passedQuery=$pdo->prepare('SELECT 1 FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=? AND passed=1 LIMIT 1');$passedQuery->execute([$userId,$current['id']]);$practiceReady=count($practiceResponses)>=3||(bool)$passedQuery->fetchColumn();
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='quiz'){
  if(!$practiceReady){$message='Complete all three practice activities before taking the lesson test.';}
  elseif(!verify_csrf_token($_POST['csrf']??null)){$message='Your session expired. Reload the lesson and try again.';}
  else{
    $answers=[];$score=0;foreach($quiz as $i=>$question){$answer=(int)($_POST['q'][$i]??-1);$answers[$i]=$answer;if($answer===$question[2])$score++;}
    $passed=$score>=8;
    $pdo->prepare('INSERT INTO academy_quiz_attempts(user_id,lesson_id,score,question_count,passed,answers_json) VALUES(?,?,?,?,?,?)')->execute([$userId,$current['id'],$score,10,$passed?1:0,json_encode($answers)]);
    if($passed){
      if($driver==='sqlite')$pdo->prepare('INSERT INTO academy_progress(user_id,lesson_id,progress_seconds,completed_at,updated_at) VALUES(?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON CONFLICT(user_id,lesson_id) DO UPDATE SET completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP')->execute([$userId,$current['id'],(int)($current['duration_seconds']??0)]);
      else $pdo->prepare('INSERT INTO academy_progress(user_id,lesson_id,progress_seconds,completed_at) VALUES(?,?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP')->execute([$userId,$current['id'],(int)($current['duration_seconds']??0)]);
      $reward=beyond_award_reward($userId,'dailybreath','lesson',(string)$current['id'],10,'Sacred Text Academy lesson completed — '.$current['title']);
      $message='Passed with '.$score.'/10. The next lesson is unlocked.'.(!empty($reward['awarded'])?' +10 bit$ earned.':'');
    }else{$message='You scored '.$score.'/10. Review the lesson and try again; 8/10 is required.';}
  }
}
$check=$pdo->prepare('SELECT MAX(score) best_score,MAX(passed) passed FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=?');$check->execute([$userId,$current['id']]);$quizStatus=$check->fetch(PDO::FETCH_ASSOC)?:[];$currentPassed=!empty($quizStatus['passed']);
$body=trim((string)($current['transcript']??''));if($body==='')$body="This all-ages $textName lesson invites careful reading, honest reflection, and a practical response.\n\nRead the selected passage slowly. Notice one word, image, or teaching that stays with you, and consider what it might mean in daily life.\n\nPractice one small action today that reflects wisdom, compassion, truth, or gratitude.";
$next=$lessons[$currentIndex+1]??null;$previous=$lessons[$currentIndex-1]??null;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($course['title'])?> | Sacred Text Academy</title><link rel="stylesheet" href="/dailybreath/academy.css?v=20260730-2"></head><body class="ba-course-page"><main class="shell"><header class="top"><strong>DailyBreath · Sacred Text Academy</strong><a href="academy.php">← All courses</a></header>
<div class="layout"><nav class="lessons" aria-label="Course lessons"><?php foreach($lessons as $index=>$lesson):$unlocked=$index===0;if($index>0){$prior=$lessons[$index-1];$g=$pdo->prepare('SELECT 1 FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=? AND passed=1 LIMIT 1');$g->execute([$userId,$prior['id']]);$unlocked=(bool)$g->fetchColumn();}?><?php if($unlocked):?><a class="<?=(int)$lesson['id']===(int)$current['id']?'active':''?>" href="?course=<?=e($slug)?>&lesson=<?=(int)$lesson['lesson_number']?>"><?= (int)$lesson['lesson_number']?>. <?=e($lesson['title'])?></a><?php else:?><span>🔒 <?= (int)$lesson['lesson_number']?>. <?=e($lesson['title'])?></span><?php endif;?><?php endforeach;?></nav>
<div><article class="lesson"><?php if(isset($_GET['locked'])):?><div class="notice">Pass this lesson’s 10-question test to unlock the next lesson.</div><?php endif;?><span class="eyebrow">LESSON <?= (int)$current['lesson_number']?> OF <?=count($lessons)?></span><h1><?=e($current['title'])?></h1><div class="content"><?=e($body)?></div><div class="next"><?php if($previous):?><a class="btn" href="?course=<?=e($slug)?>&lesson=<?=(int)$previous['lesson_number']?>">← Previous</a><?php else:?><span></span><?php endif;?><?php if($next&&$currentPassed):?><a class="btn" href="?course=<?=e($slug)?>&lesson=<?=(int)$next['lesson_number']?>">Next lesson →</a><?php elseif(!$next&&$currentPassed):?><a class="btn" href="module-exam.php?course=<?=e($slug)?>">Take module exam →</a><?php endif;?></div></article>
<section class="ba-practice-path" id="practice-path"><header><div><span class="eyebrow">REQUIRED PRACTICE</span><h2>Reflect, apply, and explain.</h2><p>Complete three short responses before the lesson test.</p></div><strong><?=count($practiceResponses)?>/3 complete</strong></header><?php if(isset($_GET['practice_saved'])):?><div class="notice">Practice saved. Continue when you are ready.</div><?php endif;?><div class="ba-practice-grid"><?php $practicePrompts=[['Notice','Write one idea or phrase from the lesson that stood out to you.'],['Apply','Describe one specific action that would put this lesson into practice today.'],['Explain','Summarize the lesson in your own words as if you were teaching someone else.']];foreach($practicePrompts as $index=>$prompt):$round=$index+1;$complete=isset($practiceResponses[$round]);?><form class="ba-practice-card <?=$complete?'complete':''?>" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="practice"><input type="hidden" name="course" value="<?=e($slug)?>"><input type="hidden" name="lesson" value="<?=(int)$current['lesson_number']?>"><input type="hidden" name="round" value="<?=$round?>"><span>Practice <?=$round?> <?=$complete?'✓':''?></span><h3><?=e($prompt[0])?></h3><p><?=e($prompt[1])?></p><textarea name="response" rows="4" minlength="3" required><?=e((string)($practiceResponses[$round]??''))?></textarea><button class="btn" type="submit"><?=$complete?'Update practice':'Complete practice'?></button></form><?php endforeach;?></div></section>
<section class="quiz" id="lesson-test"><span class="eyebrow">LESSON TEST · 10 QUESTIONS</span><h2><?=$practiceReady?'Score 8/10 to unlock the next lesson.':'Complete all three practice activities to unlock this test.'?></h2><?php if($message):?><div class="notice"><?=e($message)?></div><?php endif;?><?php if(isset($quizStatus['best_score'])):?><p class="score">Best score: <?= (int)$quizStatus['best_score'] ?>/10<?= $currentPassed?' · Passed':'' ?></p><?php endif;?><?php if($practiceReady):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="quiz"><input type="hidden" name="course" value="<?=e($slug)?>"><input type="hidden" name="lesson" value="<?=(int)$current['lesson_number']?>"><?php foreach($quiz as $i=>$question):?><fieldset class="question"><h3><?=($i+1)?>. <?=e($question[0])?></h3><?php foreach($question[1] as $optionIndex=>$option):?><label class="option"><input type="radio" name="q[<?=$i?>]" value="<?=$optionIndex?>" required> <?=e($option)?></label><?php endforeach;?></fieldset><?php endforeach;?><button class="btn" type="submit">Submit lesson test</button></form><?php else:?><a class="btn" href="#practice-path">Return to required practice</a><?php endif;?></section></div></div></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
