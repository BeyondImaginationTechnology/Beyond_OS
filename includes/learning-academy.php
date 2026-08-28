<?php
declare(strict_types=1);

require_once __DIR__.'/../config/bootstrap.php';
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']),'samesite'=>'Lax']);session_start();}
if(!isset($academyConfig)||!is_array($academyConfig))throw new RuntimeException('Academy configuration is required.');

function la_h(string $value): string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
function la_slug(string $value): string{$slug=strtolower(trim((string)preg_replace('/[^a-z0-9]+/i','-',$value),'-'));return $slug!==''?$slug:'module';}
function la_ages(): array{return [
 'preschool'=>['title'=>'Preschool','ages'=>'3–5','icon'=>'🧸','guide'=>'Short, visual, playful practice with a grown-up helper.'],
 'kids'=>['title'=>'Kids','ages'=>'6–9','icon'=>'🎨','guide'=>'Clear examples, short challenges, and hands-on learning.'],
 'preteen'=>['title'=>'Preteen','ages'=>'10–12','icon'=>'🚀','guide'=>'Real-life problems, independent practice, and creative projects.'],
 'teen'=>['title'=>'Teen','ages'=>'13–17','icon'=>'🎧','guide'=>'Deeper reasoning, practical application, and portfolio-ready challenges.'],
 'adult'=>['title'=>'Adult','ages'=>'18+','icon'=>'☕','guide'=>'Useful skills for daily life, work, confidence, and continued learning.']
];}
function la_groups(array $config): array{$groups=$config['paths']??null;return is_array($groups)&&$groups!==[]?$groups:la_ages();}
function la_age(string $age,array $config=[]): string{$age=strtolower(trim($age));$groups=la_groups($config);if(isset($groups[$age]))return $age;$default=(string)($config['default_path']??'kids');return isset($groups[$default])?$default:(string)array_key_first($groups);}
function la_phases(): array{return [
 ['Discover','Learn the core idea and connect it to something familiar.','Explain the idea in one clear sentence.'],
 ['Vocabulary','Learn the important words, symbols, or tools for this topic.','Match each new term to its meaning or purpose.'],
 ['Guided Example','Follow a complete example one step at a time.','Repeat the example and explain why each step works.'],
 ['Skill Builder','Practice the main skill with supportive prompts.','Complete three short practice rounds without rushing.'],
 ['Patterns','Notice relationships, repeated structures, and useful clues.','Find and describe two patterns in the topic.'],
 ['Real Life','Apply the skill to an everyday decision or situation.','Use the skill in one realistic scenario.'],
 ['Problem Solving','Choose a strategy, test it, and adjust when needed.','Solve a new challenge and record your reasoning.'],
 ['Create','Turn the skill into a small project, demonstration, or plan.','Build a mini project that shows what you understand.'],
 ['Explain','Teach the idea back in your own words and answer questions.','Give a one-minute explanation without reading notes.'],
 ['Mastery','Review the full topic and prepare for the module exam.','Complete a final mixed challenge and identify what to review.']
];}
function la_modules(array $config,string $age): array{
 $titles=(array)($config['tracks'][$age]??$config['tracks']['kids']??[]);$modules=[];
 foreach(array_values($titles) as $index=>$item){$title=is_array($item)?(string)($item['title']??'Module'):strval($item);$description=is_array($item)?(string)($item['description']??''):'Build practical, age-appropriate skills in '.$title.' through examples, practice, and a final project.';$lessons=[];$moduleSlug=la_slug($title);$custom=(array)($config['rich_lessons'][$age][$moduleSlug]??$config['rich_lessons']['*'][$moduleSlug]??[]);
  if($custom){foreach(array_values($custom) as $lessonIndex=>$lesson)$lessons[]=[
   'number'=>$lessonIndex+1,
   'title'=>(string)$lesson['title'],
   'focus'=>(string)$lesson['focus'],
   'teaching'=>(string)$lesson['teaching'],
   'practice'=>(string)$lesson['practice'],
   'concept'=>(string)($lesson['concept']??''),
   'example'=>(string)($lesson['example']??''),
   'activity'=>(string)($lesson['activity']??'number-line'),
   'mission'=>(string)($lesson['mission']??''),
   'objectives'=>(array)($lesson['objectives']??[]),
   'practices'=>(array)($lesson['practices']??[]),
  ];}
  else foreach(la_phases() as $lessonIndex=>$phase)$lessons[]=['number'=>$lessonIndex+1,'title'=>$phase[0].': '.$title,'focus'=>$phase[0],'teaching'=>$phase[1].' '.$description,'practice'=>$phase[2]];
  $modules[]=['number'=>$index+1,'slug'=>$moduleSlug,'title'=>$title,'description'=>$description,'free'=>$index===0,'lessons'=>$lessons];
 }
 return $modules;
}
function la_module(array $config,string $age,string $slug): ?array{foreach(la_modules($config,$age) as $module)if($module['slug']===$slug||$module['number']===(int)$slug)return $module;return null;}
function la_db(): PDO{
 static $pdo=null;
 if($pdo instanceof PDO)return $pdo;
 $path=beyond_private_root().'/learning-academy.sqlite';
 $pdo=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $pdo->exec('PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;');
 $pdo->exec("CREATE TABLE IF NOT EXISTS learning_academy_progress(app_slug TEXT NOT NULL,learner_key TEXT NOT NULL,age_group TEXT NOT NULL,module_slug TEXT NOT NULL,lesson_number INTEGER NOT NULL,best_score INTEGER NOT NULL DEFAULT 0,passed INTEGER NOT NULL DEFAULT 0,completed_at TEXT NULL,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(app_slug,learner_key,age_group,module_slug,lesson_number))");
 $pdo->exec("CREATE TABLE IF NOT EXISTS learning_academy_attempts(id INTEGER PRIMARY KEY AUTOINCREMENT,app_slug TEXT NOT NULL,learner_key TEXT NOT NULL,age_group TEXT NOT NULL,module_slug TEXT NOT NULL,assessment_type TEXT NOT NULL,lesson_number INTEGER NULL,score INTEGER NOT NULL,question_count INTEGER NOT NULL DEFAULT 10,passed INTEGER NOT NULL DEFAULT 0,attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS learning_academy_practice(app_slug TEXT NOT NULL,learner_key TEXT NOT NULL,age_group TEXT NOT NULL,module_slug TEXT NOT NULL,lesson_number INTEGER NOT NULL,round_number INTEGER NOT NULL,response TEXT NOT NULL,completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(app_slug,learner_key,age_group,module_slug,lesson_number,round_number))");
 $pdo->exec('CREATE INDEX IF NOT EXISTS idx_learning_attempts ON learning_academy_attempts(app_slug,learner_key,age_group,module_slug,assessment_type)');
 return $pdo;
}
function la_learner(): string{$user=(int)($_SESSION['user_id']??0);if($user>0)return 'u:'.$user;if(empty($_SESSION['learning_academy_key']))$_SESSION['learning_academy_key']=bin2hex(random_bytes(16));return 's:'.$_SESSION['learning_academy_key'];}
function la_full_access(): bool{$role=strtolower((string)($_SESSION['role']??''));return !empty($_SESSION['user_id'])||in_array($role,['admin','super_admin'],true)||!empty($_SESSION['learning_academy_entitled']);}
function la_admin(): bool{$role=strtolower((string)($_SESSION['role']??''));return in_array($role,['admin','super_admin'],true);}
function la_lesson_passed(string $app,string $age,string $module,int $lesson): bool{$s=la_db()->prepare('SELECT passed FROM learning_academy_progress WHERE app_slug=? AND learner_key=? AND age_group=? AND module_slug=? AND lesson_number=?');$s->execute([$app,la_learner(),$age,$module,$lesson]);return (int)$s->fetchColumn()===1;}
function la_exam_passed(string $app,string $age,string $module): bool{$s=la_db()->prepare("SELECT 1 FROM learning_academy_attempts WHERE app_slug=? AND learner_key=? AND age_group=? AND module_slug=? AND assessment_type='exam' AND passed=1 LIMIT 1");$s->execute([$app,la_learner(),$age,$module]);return (bool)$s->fetchColumn();}
function la_lesson_unlocked(array $config,string $age,array $module,int $lesson): bool{if(la_admin())return true;if(!$module['free']&&!la_full_access())return false;if($lesson>1)return la_lesson_passed($config['slug'],$age,$module['slug'],$lesson-1);if($module['number']===1)return true;$modules=la_modules($config,$age);return la_exam_passed($config['slug'],$age,$modules[$module['number']-2]['slug']);}
function la_progress(string $app,string $age,string $module): array{$s=la_db()->prepare('SELECT COUNT(*) count,COALESCE(MAX(best_score),0) best FROM learning_academy_progress WHERE app_slug=? AND learner_key=? AND age_group=? AND module_slug=? AND passed=1');$s->execute([$app,la_learner(),$age,$module]);$r=$s->fetch()?:[];return ['count'=>(int)($r['count']??0),'best'=>(int)($r['best']??0),'exam'=>la_exam_passed($app,$age,$module)];}
function la_practice_responses(string $app,string $age,string $module,int $lesson): array{
 $s=la_db()->prepare('SELECT round_number,response FROM learning_academy_practice WHERE app_slug=? AND learner_key=? AND age_group=? AND module_slug=? AND lesson_number=? ORDER BY round_number');
 $s->execute([$app,la_learner(),$age,$module,$lesson]);
 $rows=[];foreach($s->fetchAll() as $row)$rows[(int)$row['round_number']]=(string)$row['response'];
 return $rows;
}
function la_practice_count(string $app,string $age,string $module,int $lesson): int{return count(la_practice_responses($app,$age,$module,$lesson));}
function la_record_practice(array $config,string $age,array $module,int $lesson,int $round,string $response): bool{
 $response=trim($response);if($round<1||$round>3||strlen($response)<3)return false;
 la_db()->prepare("INSERT INTO learning_academy_practice(app_slug,learner_key,age_group,module_slug,lesson_number,round_number,response) VALUES(?,?,?,?,?,?,?) ON CONFLICT(app_slug,learner_key,age_group,module_slug,lesson_number,round_number) DO UPDATE SET response=excluded.response,updated_at=CURRENT_TIMESTAMP")->execute([$config['slug'],la_learner(),$age,$module['slug'],$lesson,$round,$response]);
 return true;
}
function la_practice_checks_pass(array $step,string $response): bool{
 $checks=array_values(array_filter(array_map('strval',(array)($step['checks']??[]))));
 if($checks===[])return true;
 $response=strtolower(preg_replace('/\s+/',' ',$response)??$response);
 foreach($checks as $check)if(!str_contains($response,strtolower(preg_replace('/\s+/',' ',$check)??$check)))return false;
 return true;
}
function la_practice_steps(array $item): array{
 $custom=array_values(array_filter((array)($item['practices']??[]),'is_array'));
 if(count($custom)>=3)return array_slice($custom,0,3);
 $practice=(string)($item['practice']??'Apply the main idea from this lesson.');
 return [
  ['title'=>'Guided try','instruction'=>$practice,'prompt'=>'Record what you tried, including your first result.'],
  ['title'=>'Change one thing','instruction'=>'Repeat the skill with one detail, value, source, or condition changed.','prompt'=>'Explain what changed and what happened.'],
  ['title'=>'Independent challenge','instruction'=>'Create a fresh example without copying the worked example. Check it, revise it, and explain your final choice.','prompt'=>'Show your result and one revision you made.'],
 ];
}
function la_options(string $correct,array $pool,int $seed): array{$all=array_values(array_unique(array_filter([$correct,...$pool])));while(count($all)<4)$all[]='Review the lesson '.(count($all)+1);$all=array_slice($all,0,4);$shift=$seed%4;return array_values([...array_slice($all,$shift),...array_slice($all,0,$shift)]);}
function la_test_questions(array $config,string $age,array $module,int $lesson): array{$ages=la_groups($config);$item=$module['lessons'][$lesson-1];$others=[];for($i=1;$i<=3;$i++)$others[]=$module['lessons'][($lesson-1+$i)%10];$modulePool=array_map(fn($m)=>$m['title'],array_slice(la_modules($config,$age),0,4));$focusPool=array_column($others,'focus');$teachPool=array_column($others,'teaching');$practicePool=array_column($others,'practice');$titlePool=array_column($others,'title');return [
 ['q'=>'Which course module are you studying?','a'=>$module['title'],'o'=>la_options($module['title'],$modulePool,$lesson)],
 ['q'=>'Which lesson is the focus right now?','a'=>$item['title'],'o'=>la_options($item['title'],$titlePool,$lesson+1)],
 ['q'=>'What is the main learning stage in this lesson?','a'=>$item['focus'],'o'=>la_options($item['focus'],$focusPool,$lesson+2)],
 ['q'=>'Choose the teaching statement from this lesson.','a'=>$item['teaching'],'o'=>la_options($item['teaching'],$teachPool,$lesson+3)],
 ['q'=>'Choose the practice action assigned in this lesson.','a'=>$item['practice'],'o'=>la_options($item['practice'],$practicePool,$lesson+4)],
 ['q'=>'Which learning path is this lesson designed for?','a'=>$ages[$age]['title'],'o'=>la_options($ages[$age]['title'],array_column($ages,'title'),$lesson+5)],
 ['q'=>'What is the lesson number?','a'=>(string)$lesson,'o'=>la_options((string)$lesson,[(string)(($lesson%10)+1),(string)max(1,$lesson-1),(string)min(10,$lesson+2)],$lesson+6)],
 ['q'=>'What should happen before moving to the next lesson?','a'=>'Pass this lesson test','o'=>la_options('Pass this lesson test',['Skip the practice','Open the final exam immediately','Change age groups'],$lesson+7)],
 ['q'=>'What score is required to pass?','a'=>'8 out of 10','o'=>la_options('8 out of 10',['5 out of 10','6 out of 10','10 out of 10'],$lesson+8)],
 ['q'=>'Which learning order is used?','a'=>'Learn, practice, test','o'=>la_options('Learn, practice, test',['Test, skip, finish','Guess, submit, leave','Read, ignore, advance'],$lesson+9)]
 ];}
function la_exam_questions(array $module): array{$questions=[];$titles=array_column($module['lessons'],'title');foreach($module['lessons'] as $i=>$lesson){$pool=[];for($j=1;$j<=3;$j++)$pool[]=$titles[($i+$j)%10];$questions[]=['q'=>'Which lesson matches this practice: '.$lesson['practice'],'a'=>$lesson['title'],'o'=>la_options($lesson['title'],$pool,$i+1)];}return $questions;}
function la_score(array $questions,array $answers): int{$score=0;foreach($questions as $i=>$q)if(trim((string)($answers[$i]??''))===trim((string)$q['a']))$score++;return $score;}
function la_record_test(array $config,string $age,array $module,int $lesson,int $score): bool{$passed=$score>=8;$pdo=la_db();$pdo->prepare("INSERT INTO learning_academy_attempts(app_slug,learner_key,age_group,module_slug,assessment_type,lesson_number,score,passed) VALUES(?,?,?,?, 'lesson',?,?,?)")->execute([$config['slug'],la_learner(),$age,$module['slug'],$lesson,$score,$passed?1:0]);$pdo->prepare("INSERT INTO learning_academy_progress(app_slug,learner_key,age_group,module_slug,lesson_number,best_score,passed,completed_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(app_slug,learner_key,age_group,module_slug,lesson_number) DO UPDATE SET best_score=MAX(best_score,excluded.best_score),passed=MAX(passed,excluded.passed),completed_at=CASE WHEN excluded.passed=1 THEN CURRENT_TIMESTAMP ELSE completed_at END,updated_at=CURRENT_TIMESTAMP")->execute([$config['slug'],la_learner(),$age,$module['slug'],$lesson,$score,$passed?1:0,$passed?date(DATE_ATOM):null]);return $passed;}
function la_record_exam(array $config,string $age,array $module,int $score): bool{$passed=$score>=8;la_db()->prepare("INSERT INTO learning_academy_attempts(app_slug,learner_key,age_group,module_slug,assessment_type,score,passed) VALUES(?,?,?,?, 'exam',?,?)")->execute([$config['slug'],la_learner(),$age,$module['slug'],$score,$passed?1:0]);return $passed;}
function la_csrf(): string{if(empty($_SESSION['learning_academy_csrf']))$_SESSION['learning_academy_csrf']=bin2hex(random_bytes(32));return $_SESSION['learning_academy_csrf'];}
function la_verify(): bool{return isset($_POST['csrf'])&&hash_equals((string)($_SESSION['learning_academy_csrf']??''),(string)$_POST['csrf']);}

$laAge=la_age((string)($_GET['age']??$_POST['age']??$_SESSION[$academyConfig['slug'].'_age']??''),$academyConfig);$_SESSION[$academyConfig['slug'].'_age']=$laAge;$laView=(string)($_GET['view']??'home');$laModule=la_module($academyConfig,$laAge,(string)($_GET['module']??$_POST['module']??'1'));$laLesson=max(1,min(10,(int)($_GET['lesson']??$_POST['lesson']??1)));$laResult=null;$laPracticeError='';
$laPracticeReady=$laModule&&(la_practice_count($academyConfig['slug'],$laAge,$laModule['slug'],$laLesson)>=3||la_lesson_passed($academyConfig['slug'],$laAge,$laModule['slug'],$laLesson)||la_admin());
$laAssessmentAllowed=$laModule&&($laView==='test'?(la_lesson_unlocked($academyConfig,$laAge,$laModule,$laLesson)&&$laPracticeReady):($laView==='exam'?(la_progress($academyConfig['slug'],$laAge,$laModule['slug'])['count']===10||la_admin()):true));
if($_SERVER['REQUEST_METHOD']==='POST'&&$laModule&&$laView==='lesson'&&isset($_POST['practice_round'])){
 $practiceRound=(int)$_POST['practice_round'];
 $practiceResponse=(string)($_POST['practice_response']??'');
 $practiceStep=la_practice_steps($laModule['lessons'][$laLesson-1])[$practiceRound-1]??[];
 if(!la_verify())$laPracticeError='Session expired. Reload the lesson and try again.';
 elseif(!la_lesson_unlocked($academyConfig,$laAge,$laModule,$laLesson))$laPracticeError='Complete the previous lesson first.';
 elseif(!is_array($practiceStep)||!la_practice_checks_pass($practiceStep,$practiceResponse))$laPracticeError='Run the check and complete every code requirement before saving this practice.';
 elseif(la_record_practice($academyConfig,$laAge,$laModule,$laLesson,$practiceRound,$practiceResponse)){
  $target=(string)$academyConfig['base'].'?'.http_build_query(['view'=>'lesson','age'=>$laAge,'module'=>$laModule['slug'],'lesson'=>$laLesson,'practice_saved'=>$practiceRound]).'#practice-path';
  header('Location: '.$target);exit;
 }else $laPracticeError='Add a meaningful response before completing this practice.';
}
if($_SERVER['REQUEST_METHOD']==='POST'&&$laModule&&in_array($laView,['test','exam'],true)){if(!$laAssessmentAllowed){http_response_code(403);$laResult=['score'=>0,'passed'=>false,'error'=>'Assessment locked.'];}elseif(!la_verify())$laResult=['score'=>0,'passed'=>false,'error'=>'Session expired.'];else{$questions=$laView==='test'?la_test_questions($academyConfig,$laAge,$laModule,$laLesson):la_exam_questions($laModule);$score=la_score($questions,(array)($_POST['answers']??[]));$passed=$laView==='test'?la_record_test($academyConfig,$laAge,$laModule,$laLesson,$score):la_record_exam($academyConfig,$laAge,$laModule,$score);$laResult=['score'=>$score,'passed'=>$passed];}}
$laBase=(string)$academyConfig['base'];$laCss=(string)($academyConfig['css']??'/assets/css/learning-academy.css');$laGroups=la_groups($academyConfig);$laGroupLabel=(string)($academyConfig['group_label']??'age paths');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=la_h($academyConfig['title'])?></title><meta name="description" content="<?=la_h($academyConfig['description'])?>"><link rel="stylesheet" href="<?=la_h($laCss)?>"><link rel="stylesheet" href="/assets/css/learning-academy-practice.css?v=20260730-1"><link rel="stylesheet" href="/assets/css/beyond-splash.css?v=20260828-1"><script src="/assets/js/beyond-splash.js?v=20260828-1" defer></script></head><body style="--academy-accent:<?=la_h($academyConfig['accent'])?>"><header class="la-nav"><a class="la-brand" href="<?=la_h($laBase)?>"><?php if(!empty($academyConfig['logo'])):?><img src="<?=la_h((string)$academyConfig['logo'])?>" alt=""><?php else:?><span><?=la_h($academyConfig['icon'])?></span><?php endif;?><span class="la-brand-copy"><strong><?=la_h($academyConfig['title'])?></strong><?php if(!empty($academyConfig['tagline'])):?><small><?=la_h((string)$academyConfig['tagline'])?></small><?php endif;?></span></a><nav aria-label="Academy navigation"><a class="active" href="<?=la_h($laBase)?>"><span>⌂</span> Academy</a><a href="/dashboard/"><span>◎</span> Student portal</a><a href="/"><span>↗</span> Beyond home</a></nav></header><main class="la-main">
<?php if($laView==='home'): $modules=la_modules($academyConfig,$laAge);?>
<section class="la-hero"><span class="la-kicker">LIVE · LEARN · EARN</span><h1><?=la_h($academyConfig['headline'])?></h1><p><?=la_h($academyConfig['description'])?></p><div class="la-stats"><div><b><?=count($laGroups)?></b><span><?=la_h($laGroupLabel)?></span></div><div><b>5</b><span>modules</span></div><div><b>50</b><span>lessons</span></div><div><b>150</b><span>practice labs</span></div></div></section><nav class="la-ages"><?php foreach($laGroups as $slug=>$age):?><a class="<?=$slug===$laAge?'active':''?>" href="?age=<?=la_h($slug)?>"><?=la_h($age['icon'].' '.$age['title'])?><small><?=la_h($age['ages'])?></small></a><?php endforeach;?></nav><p class="la-guide"><strong><?=la_h($laGroups[$laAge]['title'])?>:</strong> <?=la_h($laGroups[$laAge]['guide'])?> Every lesson now includes three required practice rounds before its test. Module 1 is free.</p><section class="la-grid"><?php foreach($modules as $module):$p=la_progress($academyConfig['slug'],$laAge,$module['slug']);$access=$module['free']||la_full_access();?><article class="la-card"><div class="la-card-top"><span class="la-num">0<?=$module['number']?></span><span class="la-badge <?=$module['free']?'free':($access?'':'locked')?>"><?=$module['free']?'Free':($access?'Member':'Locked')?></span></div><h2><?=la_h($module['title'])?></h2><p><?=la_h($module['description'])?></p><div class="la-progress"><span style="width:<?=$p['count']*10?>%"></span></div><footer><small><?=$p['count']?>/10 lessons · <?=$p['exam']?'Exam passed':'Exam pending'?></small><a class="la-button <?=$access?'':'disabled'?>" href="?view=module&age=<?=la_h($laAge)?>&module=<?=la_h($module['slug'])?>"><?=$access?'Open':'Members'?></a></footer></article><?php endforeach;?></section>
<?php elseif($laModule&&$laView==='module'): $access=$laModule['free']||la_full_access();$p=la_progress($academyConfig['slug'],$laAge,$laModule['slug']);?>
<a class="la-back" href="?age=<?=la_h($laAge)?>">← All modules</a><?php if(!$access):?><section class="la-lock"><h1>🔒 Member module</h1><p>Module 1 is free for every learning path. Sign in with Beyond ID to access the full academy.</p><a class="la-button" href="?age=<?=la_h($laAge)?>">Open free module</a></section><?php else:?><section class="la-title"><span class="la-kicker"><?=la_h($laGroups[$laAge]['title'])?> · MODULE <?=$laModule['number']?></span><h1><?=la_h($laModule['title'])?></h1><p><?=la_h($laModule['description'])?></p></section><section class="la-list"><?php foreach($laModule['lessons'] as $lesson):$passed=la_lesson_passed($academyConfig['slug'],$laAge,$laModule['slug'],$lesson['number']);$unlocked=la_lesson_unlocked($academyConfig,$laAge,$laModule,$lesson['number']);?><article class="la-row <?=$unlocked?'':'locked'?>"><span><?=$lesson['number']?></span><div><h3><?=la_h($lesson['title'])?></h3><p>Guided lesson · 3 practice labs · 10-question test</p></div><?php if($unlocked):?><a class="la-button secondary" href="?view=lesson&age=<?=la_h($laAge)?>&module=<?=la_h($laModule['slug'])?>&lesson=<?=$lesson['number']?>"><?=$passed?'Review':'Start'?></a><?php else:?><b>Locked</b><?php endif;?></article><?php endforeach;?></section><section class="la-exam"><h2>Module Exam</h2><p>Complete all 10 lesson tests, then score 8/10 on the exam.</p><a class="la-button <?=$p['count']===10?'':'disabled'?>" href="?view=exam&age=<?=la_h($laAge)?>&module=<?=la_h($laModule['slug'])?>"><?=$p['exam']?'Review exam':($p['count']===10?'Take exam':'Complete lessons')?></a></section><?php endif;?>
<?php elseif($laModule&&$laView==='lesson'&&la_lesson_unlocked($academyConfig,$laAge,$laModule,$laLesson)):
 $item=$laModule['lessons'][$laLesson-1];
 $practiceSteps=la_practice_steps($item);
 $practiceResponses=la_practice_responses($academyConfig['slug'],$laAge,$laModule['slug'],$laLesson);
 $practiceCount=count($practiceResponses);
 $testReady=$practiceCount>=3||la_lesson_passed($academyConfig['slug'],$laAge,$laModule['slug'],$laLesson)||la_admin();
?>
<a class="la-back" href="?view=module&amp;age=<?=la_h($laAge)?>&amp;module=<?=la_h($laModule['slug'])?>">← <?=la_h($laModule['title'])?></a>
<article class="la-reading">
 <span class="la-kicker"><?=la_h($laGroups[$laAge]['title'])?> · LEARNING CENTER · LESSON <?=$laLesson?></span>
 <h1><?=la_h($item['title'])?></h1>
 <?php if(!empty($item['mission'])||!empty($item['objectives'])):?><section class="la-mission">
  <span class="la-practice-label">Your mission</span>
  <?php if(!empty($item['mission'])):?><h2><?=la_h((string)$item['mission'])?></h2><?php endif;?>
  <?php if(!empty($item['objectives'])):?><ul><?php foreach($item['objectives'] as $objective):?><li><?=la_h((string)$objective)?></li><?php endforeach;?></ul><?php endif;?>
 </section><?php endif;?>
 <button class="la-button secondary" type="button" data-narrate-lesson>🔊 Listen to this free lesson</button>
 <section><h2>Discover</h2><p data-narration-copy><?=la_h($item['teaching'])?></p><?php if(!empty($item['concept'])):?><div class="la-concept"><strong>Big idea</strong><p data-narration-copy><?=la_h($item['concept'])?></p></div><?php endif;?></section>
 <?php if(!empty($item['example'])):?><section><h2>Worked example</h2><p data-narration-copy><?=la_h($item['example'])?></p></section><?php endif;?>
 <section class="la-interactive" data-math-activity="<?=la_h((string)($item['activity']??''))?>" data-lesson="<?=$laLesson?>"><h2>Warm-up</h2><p><?=la_h($item['practice'])?></p><div class="math-activity" aria-live="polite"></div></section>
 <div class="la-two"><section><h2>Explain it</h2><p>Say how you found the answer. Using your own words helps the idea stick.</p></section><section><h2>Connect it</h2><p>Find one place you could use this skill at home, at school, or in a project.</p></section></div>
 <section class="la-practice-path" id="practice-path" data-practice-path>
  <header class="la-practice-head"><div><span class="la-practice-label">Required before the test</span><h2>Complete all three practice labs</h2><p>Try it with support, change the conditions, then solve an independent challenge.</p></div><strong><?=$practiceCount?>/3 complete</strong></header>
  <?php if($laPracticeError!==''):?><p class="la-practice-alert"><?=la_h($laPracticeError)?></p><?php elseif(isset($_GET['practice_saved'])):?><p class="la-practice-alert success">Practice <?=max(1,min(3,(int)$_GET['practice_saved']))?> saved. Keep building.</p><?php endif;?>
  <div class="la-practice-progress" aria-label="<?=$practiceCount?> of 3 practices complete"><span style="width:<?=min(100,$practiceCount/3*100)?>%"></span></div>
  <div class="la-practice-grid">
  <?php foreach($practiceSteps as $practiceIndex=>$step):
   $round=$practiceIndex+1;
   $complete=array_key_exists($round,$practiceResponses);
   $isCode=trim((string)($step['starter']??''))!=='';
   $response=$practiceResponses[$round]??($isCode?(string)$step['starter']:'');
   $checks=array_values(array_filter(array_map('strval',(array)($step['checks']??[]))));
  ?>
   <form class="la-practice-card <?=$complete?'complete':''?> <?=$isCode?'is-code':''?>" method="post" <?=$isCode?'data-code-practice':''?> data-practice-round="<?=$round?>" data-checks="<?=la_h((string)json_encode($checks,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))?>">
    <input type="hidden" name="csrf" value="<?=la_h(la_csrf())?>">
    <input type="hidden" name="age" value="<?=la_h($laAge)?>">
    <input type="hidden" name="module" value="<?=la_h($laModule['slug'])?>">
    <input type="hidden" name="lesson" value="<?=$laLesson?>">
    <input type="hidden" name="practice_round" value="<?=$round?>">
    <div class="la-practice-card-head"><span><?=$complete?'✓':$round?></span><div><small>Practice <?=$round?></small><h3><?=la_h((string)($step['title']??('Practice '.$round)))?></h3></div></div>
    <p><?=la_h((string)($step['instruction']??$item['practice']))?></p>
    <?php if($isCode):?>
     <div class="la-code-workspace">
      <label><span>HTML editor</span><textarea name="practice_response" rows="11" spellcheck="false" autocapitalize="off" autocomplete="off"><?=la_h($response)?></textarea></label>
      <div class="la-code-preview"><span>Live preview</span><iframe title="Practice <?=$round?> preview" sandbox=""></iframe></div>
     </div>
     <p class="la-code-feedback" aria-live="polite"><?=la_h((string)($step['hint']??'Run your code to check the requirements.'))?></p>
     <div class="la-practice-actions"><button class="la-button secondary" type="button" data-run-code>Run &amp; check</button><button class="la-button" type="submit" data-complete-practice <?=$complete?'':'disabled'?>><?=$complete?'Update saved practice':'Complete practice'?></button></div>
    <?php else:?>
     <label class="la-practice-response"><span><?=la_h((string)($step['prompt']??'Record your work and reasoning.'))?></span><textarea name="practice_response" rows="5" minlength="3" required><?=la_h($response)?></textarea></label>
     <button class="la-button" type="submit"><?=$complete?'Update practice':'Complete practice '.$round?></button>
    <?php endif;?>
   </form>
  <?php endforeach;?>
  </div>
  <div class="la-practice-gate <?=$testReady?'ready':''?>"><div><strong><?=$testReady?'Lesson test unlocked':'Keep practicing'?></strong><p><?=$testReady?'All required practice is complete. You can take the lesson test now.':'Complete '.max(0,3-$practiceCount).' more practice '.(3-$practiceCount===1?'lab':'labs').' to unlock the test.'?></p></div><a class="la-button <?=$testReady?'':'disabled'?>" aria-disabled="<?=$testReady?'false':'true'?>" href="<?=$testReady?'?view=test&amp;age='.rawurlencode($laAge).'&amp;module='.rawurlencode($laModule['slug']).'&amp;lesson='.$laLesson:'#practice-path'?>"><?=$testReady?'Take the lesson test →':'Test locked'?></a></div>
 </section>
 <?php if(!empty($academyConfig['disclaimer'])):?><p class="la-disclaimer"><?=la_h($academyConfig['disclaimer'])?></p><?php endif;?>
</article>
<?php elseif($laModule&&in_array($laView,['test','exam'],true)&&$laAssessmentAllowed): $questions=$laView==='test'?la_test_questions($academyConfig,$laAge,$laModule,$laLesson):la_exam_questions($laModule);?>
<a class="la-back" href="?view=module&age=<?=la_h($laAge)?>&module=<?=la_h($laModule['slug'])?>">← <?=la_h($laModule['title'])?></a><section class="la-title"><span class="la-kicker"><?=$laView==='exam'?'MODULE EXAM':'LESSON TEST'?></span><h1><?=$laView==='exam'?la_h($laModule['title']):la_h($laModule['lessons'][$laLesson-1]['title'])?></h1><p>Answer 10 questions. A score of 8/10 is required.</p></section><?php if($laResult):?><div class="la-result <?=$laResult['passed']?'pass':''?>"><h2><?=$laResult['passed']?'Passed!':'Review and try again'?></h2><p>Score: <strong><?=$laResult['score']?>/10</strong></p></div><?php endif;?><form class="la-quiz" method="post"><input type="hidden" name="csrf" value="<?=la_h(la_csrf())?>"><input type="hidden" name="age" value="<?=la_h($laAge)?>"><input type="hidden" name="module" value="<?=la_h($laModule['slug'])?>"><input type="hidden" name="lesson" value="<?=$laLesson?>"><?php foreach($questions as $i=>$q):?><fieldset><legend><?=($i+1)?>. <?=la_h($q['q'])?></legend><?php foreach($q['o'] as $option):?><label><input type="radio" name="answers[<?=$i?>]" value="<?=la_h((string)$option)?>" required><span><?=la_h((string)$option)?></span></label><?php endforeach;?></fieldset><?php endforeach;?><button class="la-button" type="submit">Submit <?=$laView==='exam'?'exam':'test'?></button></form>
<?php else:
 $practiceBlocked=$laModule&&$laView==='test'&&la_lesson_unlocked($academyConfig,$laAge,$laModule,$laLesson)&&!$laPracticeReady;
?><section class="la-lock"><h1><?=$practiceBlocked?'Practice required':'Lesson locked'?></h1><p><?=$practiceBlocked?'Complete all three practice labs in this lesson before taking its test.':'Complete the previous assessment to continue.'?></p><a class="la-button" href="<?=$practiceBlocked?'?view=lesson&amp;age='.rawurlencode($laAge).'&amp;module='.rawurlencode($laModule['slug']).'&amp;lesson='.$laLesson.'#practice-path':'?age='.rawurlencode($laAge)?>"><?=$practiceBlocked?'Return to practice':'Return to academy'?></a></section><?php endif;?></main><footer class="la-footer"><strong><?=la_h($academyConfig['title'])?></strong><span>5 modules · 10 lessons each · 3 practice labs per lesson</span></footer><script src="/assets/js/learning-academy-practice.js?v=20260730-1" defer></script><?=($academyConfig['scripts']??'')?><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
