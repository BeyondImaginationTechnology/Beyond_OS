<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/ecosystem.php';
$wallet=beyond_app_bootstrap('DailyBreath');
$pdo=beyond_db();
$userId=(int)($_SESSION['user_id']??0);
$slug=preg_replace('/[^a-z0-9-]/','',strtolower((string)($_GET['course']??$_POST['course']??'teen-module-1')));
if(!preg_match('/^(?:teen|adult)-module-[1-5]$/',$slug)){http_response_code(404);exit('Course not found.');}
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
$commandments=[
  1=>['You shall have no other gods before Me.','Exodus 20:3','loyalty to God','Put God first in your choices.'],
  2=>['You shall not make or worship idols.','Exodus 20:4–6','true worship','Do not let possessions, success, or people take God’s place.'],
  3=>['You shall not misuse the name of the Lord.','Exodus 20:7','reverence','Use God’s name with honor in speech and conduct.'],
  4=>['Remember the Sabbath day and keep it holy.','Exodus 20:8–11','worship and rest','Make regular time for worship, gratitude, and restorative rest.'],
  5=>['Honor your father and your mother.','Exodus 20:12','honor','Treat parents and caregivers with respect, truth, and appropriate care.'],
  6=>['You shall not murder.','Exodus 20:13','the value of life','Protect life and reject hatred, cruelty, and violence.'],
  7=>['You shall not commit adultery.','Exodus 20:14','faithfulness','Keep promises and practice loyalty, purity, and respect.'],
  8=>['You shall not steal.','Exodus 20:15','honesty','Respect what belongs to others and give fairly.'],
  9=>['You shall not bear false witness.','Exodus 20:16','truth','Tell the truth and protect others from lies and harmful rumors.'],
 10=>['You shall not covet.','Exodus 20:17','contentment','Practice gratitude instead of resenting what someone else has.'],
];
function quiz_options(string $correct,array $wrong,int $slot): array {$options=array_slice($wrong,0,3);array_splice($options,$slot%4,0,[$correct]);return [$options,$slot%4];}
function commandment_quiz(int $lessonNo,array $commandments): array {
  if($lessonNo===12){$quiz=[];$all=array_column($commandments,0);foreach($commandments as $number=>$fact){$wrong=[];foreach($all as $candidate)if($candidate!==$fact[0])$wrong[]=$candidate;$cut=($number*2)%max(1,count($wrong));$rotated=array_merge(array_slice($wrong,$cut),array_slice($wrong,0,$cut));[$options,$answer]=quiz_options($fact[0],$rotated,$number);$quiz[]=["Which is Commandment $number?",$options,$answer];}return $quiz;}
  if($lessonNo===11){return [
    ['What summarizes the first four commandments?',['Love God with your whole life','Seek possessions first','Avoid all relationships','Win every argument'],0],
    ['What summarizes commandments five through ten?',['Love your neighbor as yourself','Ignore your community','Pursue recognition','Never accept help'],0],
    ['Which commandment teaches loyalty to God?',[$commandments[1][0],$commandments[5][0],$commandments[8][0],$commandments[10][0]],0],
    ['Which commandment protects worship and rest?',[$commandments[4][0],$commandments[2][0],$commandments[7][0],$commandments[9][0]],0],
    ['Which commandment teaches reverence for God’s name?',[$commandments[3][0],$commandments[6][0],$commandments[8][0],$commandments[10][0]],0],
    ['What should love for God shape?',['The whole life','Only one hour a week','Only private thoughts','Only religious vocabulary'],0],
    ['How is love for God made visible?',['Through worship and faithful choices','Through status','Through comparison','Through fear'],0],
    ['What is the correct response to God’s guidance?',['Trust and obedience','Indifference','Pride','Resentment'],0],
    ['Why memorize the commandments?',['To carry God’s guidance into daily decisions','To impress others','To avoid application','To replace prayer'],0],
    ['What comes after this review lesson?',['The final Ten Commandments mastery lesson','The course restarts','All progress is erased','No further learning'],0],
  ];}
  $n=max(1,min(10,$lessonNo));$fact=$commandments[$n];$other=array_values(array_filter($commandments,fn($v,$k)=>$k!==$n,ARRAY_FILTER_USE_BOTH));
  return [
    ["Which statement is Commandment $n?",[$fact[0],$other[0][0],$other[1][0],$other[2][0]],0],
    ['Where is this commandment recorded?',[$fact[1],'Psalm 23:1','Matthew 5:9','Genesis 1:1'],0],
    ['Which value does this commandment especially teach?',[$fact[2],'self-promotion','competition','avoidance'],0],
    ['Which action best applies this commandment?',[$fact[3],'Ignore its meaning','Use it to judge others','Practice the opposite'],0],
    ['Who gave the commandments to guide His people?',['God','Pharaoh','Caesar','Goliath'],0],
    ['The Ten Commandments are found primarily in which chapter?',['Exodus 20','Psalm 20','Matthew 20','Acts 20'],0],
    ['How should this commandment be learned?',['Understand it, remember it, and practice it','Repeat words without meaning','Apply it only to others','Forget it after the test'],0],
    ['What should follow reflection?',['One faithful action','No response','Comparison with others','Skipping ahead'],0],
    ['What should prayer request?',['Wisdom and courage to obey','Public recognition','Freedom from responsibility','Material success'],0],
    ["What number is “{$fact[0]}”?",["Commandment $n",'Not one of the commandments','Only a proverb','Only a course suggestion'],0],
  ];
}
$quiz=commandment_quiz((int)$current['lesson_number'],$commandments);
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
      $reward=beyond_award_reward($userId,'dailybreath','lesson',(string)$current['id'],10,'Bible Academy lesson completed — '.$current['title']);
      $message='Passed with '.$score.'/10. The next lesson is unlocked.'.(!empty($reward['awarded'])?' +10 bit$ earned.':'');
    }else{$message='You scored '.$score.'/10. Review the lesson and try again; 8/10 is required.';}
  }
}
$check=$pdo->prepare('SELECT MAX(score) best_score,MAX(passed) passed FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=?');$check->execute([$userId,$current['id']]);$quizStatus=$check->fetch(PDO::FETCH_ASSOC)?:[];$currentPassed=!empty($quizStatus['passed']);
$body=trim((string)($current['transcript']??''));if($body==='')$body="God’s commands are loving guidance for a life shaped by worship, truth, compassion, and wisdom.\n\nRead Exodus 20:1–17 slowly. What invitation do you hear in this lesson?\n\nPractice one small action today that honors God and your neighbor.";
$next=$lessons[$currentIndex+1]??null;$previous=$lessons[$currentIndex-1]??null;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($course['title'])?> | Bible Academy</title><link rel="stylesheet" href="/dailybreath/academy.css?v=20260730-1"></head><body class="ba-course-page"><main class="shell"><header class="top"><strong>DailyBreath · Bible Academy</strong><a href="academy.php">← All courses</a></header>
<div class="layout"><nav class="lessons" aria-label="Course lessons"><?php foreach($lessons as $index=>$lesson):$unlocked=$index===0;if($index>0){$prior=$lessons[$index-1];$g=$pdo->prepare('SELECT 1 FROM academy_quiz_attempts WHERE user_id=? AND lesson_id=? AND passed=1 LIMIT 1');$g->execute([$userId,$prior['id']]);$unlocked=(bool)$g->fetchColumn();}?><?php if($unlocked):?><a class="<?=(int)$lesson['id']===(int)$current['id']?'active':''?>" href="?course=<?=e($slug)?>&lesson=<?=(int)$lesson['lesson_number']?>"><?= (int)$lesson['lesson_number']?>. <?=e($lesson['title'])?></a><?php else:?><span>🔒 <?= (int)$lesson['lesson_number']?>. <?=e($lesson['title'])?></span><?php endif;?><?php endforeach;?></nav>
<div><article class="lesson"><?php if(isset($_GET['locked'])):?><div class="notice">Pass this lesson’s 10-question test to unlock the next lesson.</div><?php endif;?><span class="eyebrow">LESSON <?= (int)$current['lesson_number']?> OF <?=count($lessons)?></span><h1><?=e($current['title'])?></h1><div class="content"><?=e($body)?></div><div class="next"><?php if($previous):?><a class="btn" href="?course=<?=e($slug)?>&lesson=<?=(int)$previous['lesson_number']?>">← Previous</a><?php else:?><span></span><?php endif;?><?php if($next&&$currentPassed):?><a class="btn" href="?course=<?=e($slug)?>&lesson=<?=(int)$next['lesson_number']?>">Next lesson →</a><?php elseif(!$next&&$currentPassed):?><a class="btn" href="module-exam.php?course=<?=e($slug)?>">Take module exam →</a><?php endif;?></div></article>
<section class="ba-practice-path" id="practice-path"><header><div><span class="eyebrow">REQUIRED PRACTICE</span><h2>Reflect, apply, and explain.</h2><p>Complete three short responses before the lesson test.</p></div><strong><?=count($practiceResponses)?>/3 complete</strong></header><?php if(isset($_GET['practice_saved'])):?><div class="notice">Practice saved. Continue when you are ready.</div><?php endif;?><div class="ba-practice-grid"><?php $practicePrompts=[['Notice','Write one idea or phrase from the lesson that stood out to you.'],['Apply','Describe one specific action that would put this lesson into practice today.'],['Explain','Summarize the lesson in your own words as if you were teaching someone else.']];foreach($practicePrompts as $index=>$prompt):$round=$index+1;$complete=isset($practiceResponses[$round]);?><form class="ba-practice-card <?=$complete?'complete':''?>" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="practice"><input type="hidden" name="course" value="<?=e($slug)?>"><input type="hidden" name="lesson" value="<?=(int)$current['lesson_number']?>"><input type="hidden" name="round" value="<?=$round?>"><span>Practice <?=$round?> <?=$complete?'✓':''?></span><h3><?=e($prompt[0])?></h3><p><?=e($prompt[1])?></p><textarea name="response" rows="4" minlength="3" required><?=e((string)($practiceResponses[$round]??''))?></textarea><button class="btn" type="submit"><?=$complete?'Update practice':'Complete practice'?></button></form><?php endforeach;?></div></section>
<section class="quiz" id="lesson-test"><span class="eyebrow">LESSON TEST · 10 QUESTIONS</span><h2><?=$practiceReady?'Score 8/10 to unlock the next lesson.':'Complete all three practice activities to unlock this test.'?></h2><?php if($message):?><div class="notice"><?=e($message)?></div><?php endif;?><?php if(isset($quizStatus['best_score'])):?><p class="score">Best score: <?= (int)$quizStatus['best_score'] ?>/10<?= $currentPassed?' · Passed':'' ?></p><?php endif;?><?php if($practiceReady):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="quiz"><input type="hidden" name="course" value="<?=e($slug)?>"><input type="hidden" name="lesson" value="<?=(int)$current['lesson_number']?>"><?php foreach($quiz as $i=>$question):?><fieldset class="question"><h3><?=($i+1)?>. <?=e($question[0])?></h3><?php foreach($question[1] as $optionIndex=>$option):?><label class="option"><input type="radio" name="q[<?=$i?>]" value="<?=$optionIndex?>" required> <?=e($option)?></label><?php endforeach;?></fieldset><?php endforeach;?><button class="btn" type="submit">Submit lesson test</button></form><?php else:?><a class="btn" href="#practice-path">Return to required practice</a><?php endif;?></section></div></div></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
