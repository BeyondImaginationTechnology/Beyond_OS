<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/ecosystem.php';
$wallet=beyond_app_bootstrap('DailyBreath', false);
$pdo=beyond_db();
$userId=(int)($_SESSION['user_id']??0);
$catalog=[
 'bible'=>['Bible','All ages','✝️',['Bible Foundations','Stories and Themes','Wisdom and Practice','Jesus and the Gospels','Faith in Action']],
 'tanakh'=>['Tanakh','All ages','✡️',['Torah Foundations','Prophets and Justice','Wisdom and Poetry','Covenant and Community','Faith in Action']],
 'quran'=>['Quran','All ages','☪️',['Quran Foundations','Prophets and Stories','Mercy and Justice','Prayer and Character','Faith in Action']],
];
$lessonCurriculum=[
 'bible'=>[
  ['Creation and human dignity · Genesis 1–2','Covenant and calling · Genesis 12, 15, 17','Liberation and responsibility · Exodus 3–20','Wilderness, trust, and provision · Exodus 16–17','Justice, mercy, and neighbor-love · Leviticus 19','Courage and belonging · Ruth 1–4','Prayer in seasons of change · Psalms 23, 46, 139','Wisdom for daily choices · Proverbs 3, 8','Hope after loss · Lamentations 3','Reading the Bible with humility · Luke 24'],
  ['Beginnings and promise · Genesis 1–12','Joseph: integrity under pressure · Genesis 37–50','Moses: freedom and formation · Exodus 1–20','David: courage and accountability · 1 Samuel 16–17; 2 Samuel 11–12','Esther: courage for the vulnerable · Esther 4–8','Elijah: listening beyond spectacle · 1 Kings 18–19','Jonah: mercy beyond our boundaries · Jonah 1–4','The prophets’ call to justice · Amos 5; Micah 6','Wisdom through suffering · Job 1–3, 38–42','How stories shape faithful action · Hebrews 11'],
  ['The fear of the Lord and wisdom · Proverbs 1–9','A time for every season · Ecclesiastes 3','Honesty in prayer · Psalms 13, 51','Gratitude and contentment · Psalms 103; Philippians 4','Rest, Sabbath, and limits · Exodus 20; Mark 2','Hospitality and the stranger · Deuteronomy 10; Hebrews 13','Peacemaking and reconciliation · Matthew 5; Romans 12','Stewardship of body and earth · Genesis 2; 1 Corinthians 6','Truthful speech and integrity · Ephesians 4; James 3','A rule of life for the week · Matthew 7'],
  ['The kingdom of God in Jesus’ teaching · Mark 1–4','Seeing the neighbor · Luke 10','Prayer as relationship · Matthew 6','Mercy over performance · Matthew 9; Luke 18','Bread, hunger, and generosity · John 6','Forgiveness and restored community · Matthew 18','The cross and costly love · Mark 14–15','Resurrection and renewed hope · John 20','The Spirit and a diverse community · Acts 2','Following Jesus with wisdom · John 15'],
  ['Faithfulness in small things · Luke 16','Practicing gratitude · 1 Thessalonians 5','Choosing courage over fear · 2 Timothy 1','Repairing harm · Matthew 5','Serving without seeking status · Mark 10','Welcoming the overlooked · Luke 14','Caring for creation · Psalm 24','Building trustworthy community · Acts 4','Hope, patience, and endurance · Romans 5','A personal next step · James 1'],
 ],
 'tanakh'=>[
  ['Creation and human responsibility · Genesis 1–2','Avraham’s call and journey · Genesis 12','Covenant and memory · Genesis 15, 17','Hagar, Sarah, and seeing the unseen · Genesis 16, 21','Yaakov: struggle and transformation · Genesis 25–33','Yosef and repair after betrayal · Genesis 37–50','Miriam, Moshe, and liberation · Exodus 1–15','Sinai and the shape of community · Exodus 19–20','Sabbath as holy time · Exodus 31; Deuteronomy 5','Reading Torah as a living invitation · Deuteronomy 30'],
  ['The call of Shemot · Exodus 1–4','A people learning freedom · Exodus 12–15','Justice in the camp · Exodus 22–23','The Mishkan and the nearness of God · Exodus 25–40','Holiness in ordinary life · Leviticus 19','Remembering and teaching · Deuteronomy 6','Choosing life and blessing · Deuteronomy 30','Entering the land with courage · Joshua 1–4','Ruth: loyalty and belonging · Ruth 1–4','Torah, memory, and responsibility'],
  ['Devorah and courageous leadership · Judges 4–5','Shmuel learns to listen · 1 Samuel 3','David and the limits of power · 1 Samuel 16–17; 2 Samuel 11–12','Solomon asks for wisdom · 1 Kings 3','Elijah and the quiet voice · 1 Kings 18–19','Esther advocates for her people · Esther 4–8','Nehemiah rebuilds through shared work · Nehemiah 2–4','The prophets and public justice · Amos 5','Micah’s concise call · Micah 6','Leadership that serves the covenant'],
  ['A song for every season · Psalms 1, 23, 27','Lament as faithful speech · Psalms 13, 42','Praise and gratitude · Psalms 100, 103','Wisdom’s invitation · Proverbs 1–9','Words, restraint, and repair · Proverbs 15, 18','The limits of certainty · Job 38–42','Joy, time, and mortality · Ecclesiastes 3','Love and mutual delight · Song of Songs 2','Hope in return and renewal · Isaiah 40','Making a personal practice from wisdom'],
  ['Tzedakah and the vulnerable · Deuteronomy 15','Welcoming the stranger · Leviticus 19; Deuteronomy 10','Honest weights and honest words · Leviticus 19','Rest, release, and Jubilee · Leviticus 25','Repair after conflict · Genesis 33','Teshuvah: turning and returning · Hosea 14','Hope for dry bones · Ezekiel 37','Peace as a shared vocation · Isaiah 2','Courage to speak truth · Esther 4','A faithful next step in community'],
 ],
 'quran'=>[
  ['The Quran as guidance · 2:2','Creation, knowledge, and human dignity · 2:30–39','Adam, repentance, and return · 7:11–25','Signs in the world and the self · 41:53','Mercy as a divine attribute · 7:156','Gratitude and increase · 14:7','The straight path · 1:1–7','Learning with humility · 20:114','Truthfulness and clear speech · 33:70–71','Beginning a respectful Quran practice'],
  ['Nuh and perseverance · 11:25–49','Ibrahim’s trust and questioning · 6:74–83','Musa and liberation · 20:9–79','Yusuf and patient integrity · 12:1–101','Maryam and devotion · 19:16–36','Isa and mercy · 3:45–51','The People of the Cave and steadfastness · 18:9–26','Dhul-Qarnayn and responsible power · 18:83–98','Muhammad as mercy and example · 21:107','What prophetic stories ask of us'],
  ['Justice even against ourselves · 4:135','Care for the orphan and vulnerable · 93:9–11','Giving without humiliation · 2:261–274','No compulsion and freedom of conscience · 2:256','Knowing one another across peoples · 49:13','Avoiding suspicion and backbiting · 49:11–12','Fair trade and honest measure · 83:1–6','Reconciliation between people · 49:9–10','Mercy in strength · 3:134','Turning values into a fair action'],
  ['Prayer and remembrance · 20:14; 13:28','Purification of intention · 98:5','Fasting and self-discipline · 2:183–187','Charity and shared responsibility · 9:60','Patience in hardship · 2:153–157','Reliance without passivity · 3:159–160','Repentance and hope · 39:53','Moderation and balance · 7:31','Good character and gentle speech · 17:53','A simple daily rhythm of worship and care'],
  ['Keeping promises · 16:91','Honoring parents with compassion · 17:23–24','Neighborliness and generosity · 4:36','Responding to harm with what is better · 41:34','Consultation and shared decisions · 42:38','Courage with wisdom · 8:60–61','Gratitude in ordinary life · 31:12–19','Trust, accountability, and the earth · 33:72','Hope, patience, and steadfastness · 103:1–3','A personal next step in character'],
 ],
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
      $lessonPlan=$lessonCurriculum[$traditionSlug][$number-1]??[];
      for($lesson=1;$lesson<=10;$lesson++){
        $lessonTitle=$lessonPlan[$lesson-1]??($title.' · Lesson '.$lesson);
        $transcript="Focus: $lessonTitle.\n\nRead the referenced passage slowly in the selected sacred text. Notice what it says about people, responsibility, mercy, courage, or hope. Keep the passage’s own voice distinct from your first reaction.\n\nReflect: What question does this reading open for you? Apply: choose one small, respectful action that carries its wisdom into today. This is a learning invitation, not a substitute for the full text or for guidance from a trusted teacher in your tradition.";
        $l=$pdo->prepare('SELECT 1 FROM academy_lessons WHERE course_id=? AND lesson_number=?');$l->execute([$courseId,$lesson]);
        $lessonId=(int)$l->fetchColumn();
        if($lessonId){$pdo->prepare('UPDATE academy_lessons SET title=?,transcript=?,lesson_type=\'reading\',is_preview=?,is_published=1 WHERE id=?')->execute([$lessonTitle,$transcript,$number===1?1:0,$lessonId]);}
        else $pdo->prepare('INSERT INTO academy_lessons(course_id,lesson_number,title,lesson_type,transcript,is_preview,is_published) VALUES(?,?,?,\'reading\',?,?,1)')->execute([$courseId,$lesson,$lessonTitle,$transcript,$number===1?1:0]);
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
  if($userId<1){$courseProgress[(int)$course['id']]=['lessons'=>0,'exam'=>false];continue;}
  $query=$pdo->prepare('SELECT COUNT(DISTINCT lesson_id) FROM academy_quiz_attempts WHERE user_id=? AND passed=1 AND lesson_id IN(SELECT id FROM academy_lessons WHERE course_id=?)');$query->execute([$userId,$course['id']]);$passedLessons=(int)$query->fetchColumn();
  $query=$pdo->prepare('SELECT 1 FROM academy_module_exam_attempts WHERE user_id=? AND course_id=? AND passed=1 LIMIT 1');$query->execute([$userId,$course['id']]);$courseProgress[(int)$course['id']]=['lessons'=>$passedLessons,'exam'=>(bool)$query->fetchColumn()];
}
[$selectedName,$selectedAges,$selectedIcon]=$catalog[$selected];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#f7faf6"><title>DailyBreath Sacred Text Academy</title><meta name="description" content="Guided Bible, Tanakh, and Quran learning for every age."><link rel="stylesheet" href="/dailybreath/academy.css?v=20260730-1"></head><body>
<header class="ba-nav">
  <a class="ba-brand" href="/dailybreath/"><span class="ba-mark">DB</span><span><strong>DailyBreath</strong><small>Sacred Text Academy</small></span></a>
  <nav aria-label="Academy navigation"><a class="active" href="/dailybreath/academy.php">Academy</a><a href="/dailybreath/scripture.php">Sacred Text Library</a><a href="/dailybreath/">DailyBreath home</a><span class="ba-language" aria-label="Choose language"><a href="?lang=en">English</a><a href="?lang=fr">Français</a><a href="?lang=es">Español</a></span></nav>
</header>
<main class="ba-main">
  <section class="ba-hero">
    <div><span class="ba-kicker">SCRIPTURE · WISDOM · PRACTICE</span><h1>Grow deeper.<br>Live with purpose.</h1><p>Guided learning in the Bible, Tanakh, and Quran for every age, with knowledge checks, saved progress, and cumulative module exams.</p></div>
    <div class="ba-hero-card"><span>YOUR PATH</span><strong><?=$selectedIcon?> <?=e($selectedName)?></strong><small>All ages · 5 modules · 50 lessons</small></div>
  </section>
  <section class="ba-stats" aria-label="Academy overview"><div><b>3</b><span>sacred text paths</span></div><div><b>15</b><span>guided modules</span></div><div><b>150</b><span>lessons</span></div><div><b>80%</b><span>passing standard</span></div></section>
  <nav class="ba-paths" aria-label="Choose a sacred text path"><?php foreach($catalog as $slug=>[$name,$ages,$icon]):?><a class="<?=$slug===$selected?'active':''?>" href="?tradition=<?=e($slug)?>"><span><?=$icon?></span><div><strong><?=e($name)?></strong><small>All ages</small></div></a><?php endforeach;?></nav>
  <p class="ba-audience-note"><strong>Audience:</strong> Every Academy path is open to learners of all ages. Browse the free first module without an account; sign in to save progress, purchase full access, and continue across devices.</p>
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
