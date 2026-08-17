<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function read_json(string $file, array $fallback = []): array {
    if (!is_file($file)) return $fallback;
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return $fallback;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}
function all_lessons(): array {
    $lessons = read_json(LESSONS_FILE);
    usort($lessons, static fn(array $a, array $b): int => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $lessons;
}
function lesson_by_id(int $id): ?array {
    foreach (all_lessons() as $lesson) if ((int)($lesson['id'] ?? 0) === $id) return $lesson;
    return null;
}
function todays_lesson(): ?array {
    $lessons = all_lessons(); $today = date('Y-m-d');
    foreach ($lessons as $lesson) if (($lesson['date'] ?? '') === $today) return $lesson;
    foreach ($lessons as $lesson) {
        $date=(string)($lesson['date']??'');
        if($date!==''&&$date<$today)return $lesson;
    }
    return $lessons[count($lessons)-1] ?? null;
}
function french_modules(): array {
    return [
        'greetings'=>['title'=>'Greetings','icon'=>'👋','description'=>'Meet people, introduce yourself, and handle everyday conversation.'],
        'food'=>['title'=>'Food','icon'=>'🥐','description'=>'Order meals, shop for ingredients, and talk about what you enjoy.'],
        'transport-travel'=>['title'=>'Transportation & Travel','icon'=>'🚆','description'=>'Use buses, trains, airports, hotels, directions, and travel times.'],
        'ocean'=>['title'=>'Ocean','icon'=>'🌊','description'=>'Explore beaches, weather, boats, and life near the sea.'],
        'sports'=>['title'=>'Sports','icon'=>'⚽','description'=>'Talk about games, movement, teams, and friendly competition.'],
    ];
}
function lesson_module(array $lesson): string {
    $slug=strtolower(trim((string)($lesson['module']??'greetings')));return isset(french_modules()[$slug])?$slug:'greetings';
}
function ordered_lessons(): array {
    $lessons = all_lessons();
    $order=array_flip(array_keys(french_modules()));
    usort($lessons, static function(array $a,array $b) use($order): int {$ma=$order[lesson_module($a)]??99;$mb=$order[lesson_module($b)]??99;return $ma<=>$mb ?: strcmp($a['date']??'',$b['date']??'') ?: ((int)($a['id']??0)<=>(int)($b['id']??0));});
    return $lessons;
}
function lesson_position(int $id): array {
    $ordered=ordered_lessons();$modules=french_modules();$moduleKeys=array_keys($modules);
    foreach ($ordered as $index=>$lesson) if ((int)($lesson['id']??0)===$id){$slug=lesson_module($lesson);$within=0;foreach($ordered as $candidate){if(lesson_module($candidate)===$slug)$within++;if((int)($candidate['id']??0)===$id)break;}return ['index'=>$index,'module'=>array_search($slug,$moduleKeys,true)+1,'module_slug'=>$slug,'module_title'=>$modules[$slug]['title'],'lesson'=>$within,'total'=>count($ordered)];}
    return ['index'=>0,'module'=>1,'module_slug'=>'greetings','module_title'=>'Greetings','lesson'=>1,'total'=>count($ordered)];
}
function french_progress(int $userId): array {
    if($userId<1)return [];$s=sqlite_db()->prepare('SELECT * FROM french_learning_progress WHERE user_id=?');$s->execute([$userId]);return $s->fetch()?:[];
}
function french_continue_lesson(int $userId): ?array {
    $ordered=ordered_lessons();if(!$ordered)return null;$progress=french_progress($userId);$target=(int)($progress['current_lesson_id']??0);
    foreach($ordered as $lesson)if((int)$lesson['id']===$target)return $lesson;return $ordered[0];
}
function french_mark_started(int $userId,int $lessonId): void {
    if($userId<1||$lessonId<1)return;$pdo=sqlite_db();$pdo->prepare('INSERT INTO french_learning_progress(user_id,current_lesson_id,last_lesson_id) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET current_lesson_id=excluded.current_lesson_id,last_lesson_id=excluded.last_lesson_id,updated_at=CURRENT_TIMESTAMP')->execute([$userId,$lessonId,$lessonId]);
}
function french_mark_completed(int $userId,int $lessonId): ?array {
    if($userId<1)return null;$ordered=ordered_lessons();$next=null;foreach($ordered as $index=>$lesson)if((int)$lesson['id']===$lessonId){$next=$ordered[$index+1]??$lesson;break;}$progress=french_progress($userId);$done=json_decode((string)($progress['completed_lessons_json']??'[]'),true);if(!is_array($done))$done=[];$done=array_values(array_unique([...$done,$lessonId]));
    $pdo=sqlite_db();$pdo->prepare('INSERT INTO french_learning_progress(user_id,current_lesson_id,last_lesson_id,completed_lessons_json) VALUES(?,?,?,?) ON CONFLICT(user_id) DO UPDATE SET current_lesson_id=excluded.current_lesson_id,last_lesson_id=excluded.last_lesson_id,completed_lessons_json=excluded.completed_lessons_json,updated_at=CURRENT_TIMESTAMP')->execute([$userId,(int)($next['id']??$lessonId),$lessonId,json_encode($done)]);return $next;
}
function lesson_is_today(?array $lesson): bool { return $lesson !== null && ($lesson['date'] ?? '') === date('Y-m-d'); }
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function is_admin(): bool {
    if (!empty($_SESSION['admin_authenticated'])) return true;
    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    return !empty($_SESSION['user_id']) && in_array($role, ['admin', 'super_admin'], true);
}
function require_admin(): void { if (!is_admin()) { header('Location: login.php'); exit; } }
function french_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function french_verify_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    }
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    return $sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token);
}
function sqlite_db(): PDO {
    static $pdo = null; if ($pdo instanceof PDO) return $pdo;
    if (!is_dir(PRIVATE_DATA_DIR)) mkdir(PRIVATE_DATA_DIR, 0700, true);
    $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS french_subscribers (id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE COLLATE NOCASE, preferred_language TEXT NOT NULL, consent_at TEXT NOT NULL, created_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, blocked_until INTEGER NOT NULL DEFAULT 0, updated_at INTEGER NOT NULL)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_lesson_audio (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lesson_id INTEGER NOT NULL,
        provider TEXT NOT NULL,
        voice TEXT NOT NULL,
        language TEXT NOT NULL,
        format TEXT NOT NULL DEFAULT 'mp3',
        audio_path TEXT NOT NULL DEFAULT '',
        content_hash TEXT NOT NULL,
        generation_status TEXT NOT NULL DEFAULT 'processing' CHECK(generation_status IN ('processing','ready','failed')),
        error_code TEXT NULL,
        created_by INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(lesson_id, content_hash)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_audio_lesson ON french_lesson_audio(lesson_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_audio_status ON french_lesson_audio(generation_status)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_learning_progress (user_id INTEGER PRIMARY KEY,current_lesson_id INTEGER NOT NULL DEFAULT 1,last_lesson_id INTEGER NOT NULL DEFAULT 0,completed_lessons_json TEXT NOT NULL DEFAULT '[]',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_progress (
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        best_score INTEGER NOT NULL DEFAULT 0,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        completed_at TEXT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(learner_key,age_group,module_slug,lesson_number)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_test_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        score INTEGER NOT NULL,
        question_count INTEGER NOT NULL DEFAULT 10,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_exam_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        score INTEGER NOT NULL,
        question_count INTEGER NOT NULL DEFAULT 10,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_practice (
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        round_number INTEGER NOT NULL,
        response TEXT NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(learner_key,age_group,module_slug,lesson_number,round_number)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_academy_test_learner ON french_academy_test_attempts(learner_key,age_group,module_slug,lesson_number)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_academy_exam_learner ON french_academy_exam_attempts(learner_key,age_group,module_slug)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_narration_rate_limits (
        admin_id INTEGER NOT NULL,
        action TEXT NOT NULL,
        window_started_at INTEGER NOT NULL,
        request_count INTEGER NOT NULL DEFAULT 0,
        PRIMARY KEY(admin_id, action)
    )");
    return $pdo;
}

function lesson_audio_map(int $lessonId): array {
    if ($lessonId < 1) return [];
    try {
        $stmt = sqlite_db()->prepare("SELECT language, audio_path FROM french_lesson_audio WHERE lesson_id=? AND generation_status='ready' AND audio_path<>'' ORDER BY id DESC");
        $stmt->execute([$lessonId]);
        $audio = [];
        foreach ($stmt->fetchAll() as $row) {
            $language = (string)($row['language'] ?? '');
            if ($language !== '' && !isset($audio[$language])) $audio[$language] = (string)$row['audio_path'];
        }
        return $audio;
    } catch (Throwable $error) {
        error_log('Lesson audio lookup failed: ' . $error->getMessage());
        return [];
    }
}
function login_blocked(string $ip): bool {
    $s=sqlite_db()->prepare('SELECT blocked_until FROM login_attempts WHERE ip=?'); $s->execute([$ip]); $r=$s->fetch();
    return $r && (int)$r['blocked_until'] > time();
}
function record_login_failure(string $ip): void {
    $pdo=sqlite_db(); $s=$pdo->prepare('SELECT attempts FROM login_attempts WHERE ip=?'); $s->execute([$ip]); $attempts=((int)($s->fetch()['attempts']??0))+1;
    $blocked=$attempts>=5 ? time()+900 : 0;
    $q=$pdo->prepare('INSERT INTO login_attempts(ip,attempts,blocked_until,updated_at) VALUES(?,?,?,?) ON CONFLICT(ip) DO UPDATE SET attempts=excluded.attempts,blocked_until=excluded.blocked_until,updated_at=excluded.updated_at');
    $q->execute([$ip,$attempts,$blocked,time()]);
}
function clear_login_failures(string $ip): void { $s=sqlite_db()->prepare('DELETE FROM login_attempts WHERE ip=?'); $s->execute([$ip]); }

function french_academy_catalog(): array {
    static $catalog = null;
    if (is_array($catalog)) return $catalog;
    $catalog = read_json(FRENCH_ACADEMY_FILE, ['age_groups'=>[], 'modules'=>[]]);
    return $catalog;
}
function french_age_groups(): array {
    $groups=[];
    foreach ((array)(french_academy_catalog()['age_groups']??[]) as $group) {
        $slug=(string)($group['slug']??'');
        if($slug!=='')$groups[$slug]=$group;
    }
    return $groups;
}
function french_academy_modules(): array {
    $modules=[];
    foreach ((array)(french_academy_catalog()['modules']??[]) as $module) {
        $slug=(string)($module['slug']??'');
        if($slug!=='')$modules[$slug]=$module;
    }
    return $modules;
}
function french_valid_age_group(string $age): string {
    $age=strtolower(trim($age));
    return isset(french_age_groups()[$age])?$age:'kids';
}
function french_valid_module(string $module): string {
    $module=strtolower(trim($module));
    return isset(french_academy_modules()[$module])?$module:'greetings';
}
function french_course_lesson(string $age,string $module,int $lessonNumber): ?array {
    $age=french_valid_age_group($age);$module=french_valid_module($module);
    $course=french_academy_modules()[$module]??null;$group=french_age_groups()[$age]??null;
    if(!$course||!$group||$lessonNumber<1||$lessonNumber>10)return null;
    $lesson=$course['lessons'][$lessonNumber-1]??null;
    if(!is_array($lesson))return null;
    return $lesson+['age_group'=>$age,'age_title'=>$group['title'],'age_guidance'=>$group['guidance'],'module_slug'=>$module,'module_title'=>$course['title'],'module_icon'=>$course['icon'],'lesson_number'=>$lessonNumber];
}
function french_learner_key(): string {
    $userId=(int)($_SESSION['user_id']??0);
    if($userId>0)return 'u:'.$userId;
    if(empty($_SESSION['french_learner_key']))$_SESSION['french_learner_key']=bin2hex(random_bytes(16));
    return 's:'.(string)$_SESSION['french_learner_key'];
}
function french_academy_has_full_access(): bool {
    return is_admin()||!empty($_SESSION['french_academy_entitled']);
}
function french_is_guest(): bool {
    return empty($_SESSION['user_id']) && !is_admin();
}
function french_guest_daily_lesson_allowed(?array $lesson): bool {
    if (!french_is_guest()) return true;
    $today = todays_lesson();
    return $lesson !== null && $today !== null && (int)($lesson['id'] ?? 0) === (int)($today['id'] ?? 0);
}
function french_academy_module_accessible(string $module): bool {
    $course=french_academy_modules()[french_valid_module($module)]??[];
    return !empty($course['free'])||french_academy_has_full_access();
}
function french_academy_lesson_passed(string $age,string $module,int $lesson): bool {
    $s=sqlite_db()->prepare('SELECT passed FROM french_academy_progress WHERE learner_key=? AND age_group=? AND module_slug=? AND lesson_number=?');
    $s->execute([french_learner_key(),french_valid_age_group($age),french_valid_module($module),$lesson]);
    return (int)$s->fetchColumn()===1;
}
function french_academy_exam_passed(string $age,string $module): bool {
    $s=sqlite_db()->prepare('SELECT 1 FROM french_academy_exam_attempts WHERE learner_key=? AND age_group=? AND module_slug=? AND passed=1 LIMIT 1');
    $s->execute([french_learner_key(),french_valid_age_group($age),french_valid_module($module)]);
    return (bool)$s->fetchColumn();
}
function french_academy_lesson_unlocked(string $age,string $module,int $lesson): bool {
    $age=french_valid_age_group($age);$module=french_valid_module($module);
    if(is_admin())return true;
    if(!french_academy_module_accessible($module)||$lesson<1||$lesson>10)return false;
    if($lesson>1)return french_academy_lesson_passed($age,$module,$lesson-1);
    $keys=array_keys(french_academy_modules());$index=array_search($module,$keys,true);
    if($index===0)return true;
    return $index!==false&&french_academy_exam_passed($age,$keys[$index-1]);
}
function french_academy_module_progress(string $age,string $module): array {
    $s=sqlite_db()->prepare('SELECT COUNT(*) lessons_passed,COALESCE(MAX(best_score),0) best_score FROM french_academy_progress WHERE learner_key=? AND age_group=? AND module_slug=? AND passed=1');
    $s->execute([french_learner_key(),french_valid_age_group($age),french_valid_module($module)]);$row=$s->fetch()?:[];
    return ['lessons_passed'=>(int)($row['lessons_passed']??0),'best_score'=>(int)($row['best_score']??0),'exam_passed'=>french_academy_exam_passed($age,$module)];
}
function french_academy_practice_responses(string $age,string $module,int $lesson): array {
    $s=sqlite_db()->prepare('SELECT round_number,response FROM french_academy_practice WHERE learner_key=? AND age_group=? AND module_slug=? AND lesson_number=? ORDER BY round_number');
    $s->execute([french_learner_key(),french_valid_age_group($age),french_valid_module($module),$lesson]);
    $responses=[];foreach($s->fetchAll() as $row)$responses[(int)$row['round_number']]=(string)$row['response'];
    return $responses;
}
function french_record_academy_practice(string $age,string $module,int $lesson,int $round,string $response): bool {
    $response=trim($response);if($round<1||$round>3||mb_strlen($response)<3)return false;
    sqlite_db()->prepare("INSERT INTO french_academy_practice(learner_key,age_group,module_slug,lesson_number,round_number,response) VALUES(?,?,?,?,?,?) ON CONFLICT(learner_key,age_group,module_slug,lesson_number,round_number) DO UPDATE SET response=excluded.response,updated_at=CURRENT_TIMESTAMP")->execute([french_learner_key(),french_valid_age_group($age),french_valid_module($module),$lesson,$round,$response]);
    return true;
}
function french_record_lesson_test(string $age,string $module,int $lesson,int $score,int $questionCount=10): bool {
    $age=french_valid_age_group($age);$module=french_valid_module($module);$passed=$score>=8;
    $pdo=sqlite_db();$pdo->prepare('INSERT INTO french_academy_test_attempts(learner_key,age_group,module_slug,lesson_number,score,question_count,passed) VALUES(?,?,?,?,?,?,?)')->execute([french_learner_key(),$age,$module,$lesson,$score,$questionCount,$passed?1:0]);
    $pdo->prepare("INSERT INTO french_academy_progress(learner_key,age_group,module_slug,lesson_number,best_score,passed,completed_at) VALUES(?,?,?,?,?,?,?) ON CONFLICT(learner_key,age_group,module_slug,lesson_number) DO UPDATE SET best_score=MAX(best_score,excluded.best_score),passed=MAX(passed,excluded.passed),completed_at=CASE WHEN excluded.passed=1 THEN CURRENT_TIMESTAMP ELSE completed_at END,updated_at=CURRENT_TIMESTAMP")->execute([french_learner_key(),$age,$module,$lesson,$score,$passed?1:0,$passed?date(DATE_ATOM):null]);
    return $passed;
}
function french_record_module_exam(string $age,string $module,int $score,int $questionCount=10): bool {
    $age=french_valid_age_group($age);$module=french_valid_module($module);$passed=$score>=8;
    sqlite_db()->prepare('INSERT INTO french_academy_exam_attempts(learner_key,age_group,module_slug,score,question_count,passed) VALUES(?,?,?,?,?,?)')->execute([french_learner_key(),$age,$module,$score,$questionCount,$passed?1:0]);
    return $passed;
}
function french_quiz_options(string $correct,array $pool,int $seed): array {
    $values=array_values(array_unique(array_filter(array_map('strval',[$correct,...$pool]),static fn(string $value): bool=>$value!=='')));
    while(count($values)<4)$values[]='Review the lesson and try again '.(count($values)+1);
    $values=array_slice($values,0,4);$shift=$seed%count($values);
    return array_values(array_merge(array_slice($values,$shift),array_slice($values,0,$shift)));
}
function french_phrase_word(string $phrase,bool $last=false): string {
    $clean=trim((string)preg_replace('/[^\pL\pN\'’-]+/u',' ',$phrase));$words=preg_split('/\s+/u',$clean)?:[];
    return (string)($last?($words[count($words)-1]??''):($words[0]??''));
}
function french_lesson_test_questions(string $age,string $module,int $lessonNumber): array {
    $module=french_valid_module($module);$course=french_academy_modules()[$module];$lessons=$course['lessons'];$target=$lessons[$lessonNumber-1]??$lessons[0];
    $others=[];for($i=1;$i<=3;$i++)$others[]=$lessons[($lessonNumber-1+$i)%10];
    $french=array_column($others,'french');$english=array_column($others,'english');$pronunciation=array_column($others,'pronunciation');$titles=array_column($others,'title');
    $lastPool=array_map(static fn(array $l): string=>french_phrase_word((string)$l['french'],true),$others);$firstPool=array_map(static fn(array $l): string=>french_phrase_word((string)$l['french']),$others);
    $moduleTitles=array_column(array_values(french_academy_modules()),'title');$pairs=array_map(static fn(array $l): string=>$l['english'].' — '.$l['french'],$others);
    return [
      ['prompt'=>'Choose the French for “'.$target['english'].'”','answer'=>$target['french'],'options'=>french_quiz_options($target['french'],$french,$lessonNumber)],
      ['prompt'=>'What does “'.$target['french'].'” mean?','answer'=>$target['english'],'options'=>french_quiz_options($target['english'],$english,$lessonNumber+1)],
      ['prompt'=>'Choose the pronunciation for “'.$target['french'].'”','answer'=>$target['pronunciation'],'options'=>french_quiz_options($target['pronunciation'],$pronunciation,$lessonNumber+2)],
      ['prompt'=>'Which French phrase matches the lesson “'.$target['title'].'”?','answer'=>$target['french'],'options'=>french_quiz_options($target['french'],$french,$lessonNumber+3)],
      ['prompt'=>'Complete the phrase: '.preg_replace('/\s+[^\s]+\s*$/u',' _____',(string)$target['french']),'answer'=>french_phrase_word((string)$target['french'],true),'options'=>french_quiz_options(french_phrase_word((string)$target['french'],true),$lastPool,$lessonNumber+4)],
      ['prompt'=>'Which word begins the phrase “'.$target['english'].'”?','answer'=>french_phrase_word((string)$target['french']),'options'=>french_quiz_options(french_phrase_word((string)$target['french']),$firstPool,$lessonNumber+5)],
      ['prompt'=>'Which Academy topic contains this lesson?','answer'=>$course['title'],'options'=>french_quiz_options($course['title'],$moduleTitles,$lessonNumber+6)],
      ['prompt'=>'Choose the correct English–French pair.','answer'=>$target['english'].' — '.$target['french'],'options'=>french_quiz_options($target['english'].' — '.$target['french'],$pairs,$lessonNumber+7)],
      ['prompt'=>'Which phrase should you practice for this action: '.$target['practice'],'answer'=>$target['french'],'options'=>french_quiz_options($target['french'],$french,$lessonNumber+8)],
      ['prompt'=>'Final check: select the meaning of “'.$target['french'].'”','answer'=>$target['english'],'options'=>french_quiz_options($target['english'],$english,$lessonNumber+9)]
    ];
}
function french_module_exam_questions(string $age,string $module): array {
    $course=french_academy_modules()[french_valid_module($module)];$questions=[];$lessons=$course['lessons'];
    foreach($lessons as $index=>$lesson){$pool=[];for($i=1;$i<=3;$i++)$pool[]=$lessons[($index+$i)%10]['french'];$questions[]=['prompt'=>'Choose the French for “'.$lesson['english'].'”','answer'=>$lesson['french'],'options'=>french_quiz_options($lesson['french'],$pool,$index+1)];}
    return $questions;
}
function french_score_quiz(array $questions,array $answers): int {
    $score=0;foreach($questions as $index=>$question)if(trim((string)($answers[$index]??''))===trim((string)$question['answer']))$score++;
    return $score;
}
