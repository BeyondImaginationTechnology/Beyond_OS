<?php
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/web-app.php';
$isGuestPreview = empty($_SESSION['user_id']);
if ($isGuestPreview) {
    header('X-Beyond-Guest-Preview: DailyBreath-Bible');
    $beyondWallet = beyond_nav_bootstrap('DailyBreath', ['balance'=>0,'currency'=>'BITS','status'=>'guest']);
} else {
    $beyondWallet = beyond_app_bootstrap('DailyBreath');
}
$books = [
    'Old Testament' => ['Genesis'=>50,'Exodus'=>40,'Leviticus'=>27,'Numbers'=>36,'Deuteronomy'=>34,'Joshua'=>24,'Judges'=>21,'Ruth'=>4,'1 Samuel'=>31,'2 Samuel'=>24,'1 Kings'=>22,'2 Kings'=>25,'1 Chronicles'=>29,'2 Chronicles'=>36,'Ezra'=>10,'Nehemiah'=>13,'Esther'=>10,'Job'=>42,'Psalms'=>150,'Proverbs'=>31,'Ecclesiastes'=>12,'Song of Solomon'=>8,'Isaiah'=>66,'Jeremiah'=>52,'Lamentations'=>5,'Ezekiel'=>48,'Daniel'=>12,'Hosea'=>14,'Joel'=>3,'Amos'=>9,'Obadiah'=>1,'Jonah'=>4,'Micah'=>7,'Nahum'=>3,'Habakkuk'=>3,'Zephaniah'=>3,'Haggai'=>2,'Zechariah'=>14,'Malachi'=>4],
    'New Testament' => ['Matthew'=>28,'Mark'=>16,'Luke'=>24,'John'=>21,'Acts'=>28,'Romans'=>16,'1 Corinthians'=>16,'2 Corinthians'=>13,'Galatians'=>6,'Ephesians'=>6,'Philippians'=>4,'Colossians'=>4,'1 Thessalonians'=>5,'2 Thessalonians'=>3,'1 Timothy'=>6,'2 Timothy'=>4,'Titus'=>3,'Philemon'=>1,'Hebrews'=>13,'James'=>5,'1 Peter'=>5,'2 Peter'=>3,'1 John'=>5,'2 John'=>1,'3 John'=>1,'Jude'=>1,'Revelation'=>22],
];
$flatBooks = array_merge(...array_values($books));
$bookCodes = array_combine(array_keys($flatBooks), ['GEN','EXO','LEV','NUM','DEU','JOS','JDG','RUT','1SA','2SA','1KI','2KI','1CH','2CH','EZR','NEH','EST','JOB','PSA','PRO','ECC','SOL','ISA','JER','LAM','EZE','DAN','HOS','JOE','AMO','OBA','JON','MIC','NAH','HAB','ZEP','HAG','ZEC','MAL','MAT','MAR','LUK','JOH','ACT','ROM','1CO','2CO','GAL','EPH','PHI','COL','1TH','2TH','1TI','2TI','TIT','PHM','HEB','JAM','1PE','2PE','1JO','2JO','3JO','JUD','REV']);
$book = $_GET['book'] ?? 'Psalms';
if (!isset($flatBooks[$book])) $book = 'Psalms';
$chapter = max(1, min((int)($_GET['chapter'] ?? 46), $flatBooks[$book]));
$verses = [];

$bookNames = array_keys($flatBooks);
$bookIndex = array_search($book, $bookNames, true);
$previousBook = $book;
$previousChapter = $chapter - 1;
$nextBook = $book;
$nextChapter = $chapter + 1;
if ($chapter <= 1) {
    if ($bookIndex > 0) {
        $previousBook = $bookNames[$bookIndex - 1];
        $previousChapter = $flatBooks[$previousBook];
    } else {
        $previousChapter = null;
    }
}
if ($chapter >= $flatBooks[$book]) {
    if ($bookIndex < count($bookNames) - 1) {
        $nextBook = $bookNames[$bookIndex + 1];
        $nextChapter = 1;
    } else {
        $nextChapter = null;
    }
}
$chapterUrl = static fn(string $targetBook, int $targetChapter): string => '?book=' . urlencode($targetBook) . '&chapter=' . $targetChapter . '#reader-top';
try {
    $pdo = beyond_db();
    $stmt = $pdo->prepare('SELECT verse_number,verse_text FROM bible_verses WHERE book_name=? AND chapter_number=? ORDER BY verse_number');
    $stmt->execute([$book, $chapter]);
    $verses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}
if (!$verses) {
    $handle = @fopen(__DIR__ . '/data/engwebp_vpl.txt', 'rb');
    $code = $bookCodes[$book];
    $started = false;
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/^([A-Z0-9]{3}) (\d+):(\d+) (.+)$/u', trim($line), $match)) continue;
            if ($match[1] === $code && (int)$match[2] === $chapter) {
                $started = true;
                $verses[] = ['verse_number'=>(int)$match[3], 'verse_text'=>$match[4]];
            } elseif ($started) {
                break;
            }
        }
        fclose($handle);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($book) ?> <?= $chapter ?> | DailyBreath Bible</title>
<?= dailybreath_web_head($book . ' ' . $chapter . ' | DailyBreath Bible') ?>
<style>
*{box-sizing:border-box}html{width:100%;max-width:100%;overflow-x:hidden;scroll-behavior:smooth}body{margin:0;padding-bottom:190px;color:#eef7ef;font-family:Georgia,serif;background-image:linear-gradient(#00180c55,#00180caa);background-position:center top;background-size:cover;background-attachment:fixed}.shell{width:100%;max-width:1120px;min-width:0;margin:auto;padding:38px 22px;overflow:hidden}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;color:#fff;text-shadow:0 2px 12px #000}.top a{color:#f1cf7d}.library{display:grid;grid-template-columns:minmax(0,300px) minmax(0,1fr);gap:24px;min-width:0;margin-top:28px}.books,.reader{min-width:0;max-width:100%;padding:24px;border:1px solid #ffffff38;border-radius:26px;background:#0a321fbb;box-shadow:0 24px 65px #00180c88;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px)}.books h2{font-family:system-ui;color:#f1cf7d}.book-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}.book-grid a{padding:8px;color:#d8e7da;text-decoration:none;font:13px system-ui;border-radius:9px}.book-grid a:hover{background:#ffffff12}.book-grid a.active{color:#112718;background:#f1cf7d}.reader{position:relative;overflow:hidden}.reader-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.reader h1{font-size:44px;margin:0 0 18px}.chapter-strip{position:sticky;top:10px;z-index:20;margin:0 -8px 24px;padding:10px;border:1px solid #ffffff24;border-radius:18px;background:#0a2f20e8;box-shadow:0 12px 35px #00180c88;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}.book-picker{display:grid;grid-template-columns:minmax(0,1fr) minmax(110px,.55fr);gap:8px;margin-bottom:10px}.book-picker label{display:grid;gap:5px;color:#bcd2c0;font:800 11px system-ui;letter-spacing:.04em}.book-picker select{width:100%;min-width:0;padding:10px 34px 10px 12px;border:1px solid #ffffff28;border-radius:13px;color:#fff;background:#173f2c;font:800 13px system-ui}.chapter-nav{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 8px;scrollbar-width:none}.chapter-nav::-webkit-scrollbar{display:none}.chapter-nav a{flex:0 0 38px;display:grid;place-items:center;width:38px;height:38px;border:1px solid #ffffff1f;border-radius:12px;background:#ffffff10;color:#dce8dc;text-decoration:none;font:700 13px system-ui}.chapter-nav a.active{background:#f1cf7d;color:#173f2c;border-color:#f1cf7d;box-shadow:0 0 0 4px #f1cf7d22}.chapter-jump{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:4px;font:700 12px system-ui;color:#bcd2c0}.chapter-jump select{max-width:150px;padding:8px 34px 8px 12px;border:1px solid #ffffff28;border-radius:999px;color:#fff;background:#173f2c;font:700 12px system-ui}.verse{max-width:100%;scroll-margin-top:170px;font-size:20px;line-height:1.8;color:#f7fbf7;overflow-wrap:anywhere;word-break:normal}.verse sup{color:#f1cf7d;font:bold 12px system-ui;margin-right:7px}.empty,.source{padding:18px;background:#ffffff10;border:1px solid #ffffff1c;border-radius:13px;font-family:system-ui;color:#d7e4d8}.source{margin-top:30px;font-size:12px}.source a{color:#f1cf7d}.end-nav{display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:center;margin-top:28px;padding-top:22px;border-top:1px solid #ffffff22;font-family:system-ui}.end-nav a,.end-nav span{display:flex;align-items:center;gap:9px;min-height:50px;padding:12px 15px;border:1px solid #ffffff25;border-radius:16px;color:#fff;background:#ffffff0d;text-decoration:none}.end-nav a:last-child{justify-content:flex-end}.end-nav .disabled{opacity:.35}.end-nav .top-link{justify-content:center;color:#f1cf7d}.chapter-dock{position:fixed;z-index:2147483499;left:50%;bottom:96px;transform:translateX(-50%);width:min(620px,calc(100% - 24px));display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:8px;padding:9px;border:1px solid #ffffff38;border-radius:22px;color:#fff;background:#0d3828e8;box-shadow:0 18px 55px #00180c99;backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);font:750 12px system-ui}.chapter-dock a,.chapter-dock span{min-height:46px;display:flex;align-items:center;gap:8px;padding:8px 13px;border-radius:15px;color:inherit;text-decoration:none;background:#ffffff0c}.chapter-dock a:last-child{justify-content:flex-end}.chapter-dock .current{justify-content:center;min-width:118px;color:#173f2c;background:#f1cf7d}.chapter-dock .disabled{opacity:.35}.bottom{position:fixed;z-index:2147483500;left:50%;bottom:14px;transform:translateX(-50%);width:min(650px,calc(100% - 24px));height:72px;display:flex;align-items:center;justify-content:space-around;border:1px solid #ffffff35;border-radius:24px;color:#dce7dd;background:#123927e8;box-shadow:0 18px 55px #00180c99;backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);font:750 11px system-ui}.bottom a{color:inherit;text-decoration:none}.bottom .active{color:#f1cf7d}.guest-preview{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:20px 0 4px;padding:16px 18px;border:1px solid #f1cf7d66;border-radius:18px;background:linear-gradient(120deg,#173f2ce8,#1f5037e8);box-shadow:0 16px 45px #00180c66;font-family:system-ui}.guest-preview strong{display:block;color:#f7d98f;margin-bottom:3px}.guest-preview span{color:#dbe8dd;font-size:13px}.guest-preview-actions{display:flex;gap:8px;flex-wrap:wrap}.guest-preview a{padding:10px 13px;border-radius:999px;color:#173f2c;background:#f1cf7d;text-decoration:none;font-size:12px;font-weight:900;white-space:nowrap}.guest-preview a.secondary{color:#fff;background:#ffffff12;border:1px solid #ffffff2d}
@media(min-width:761px){
body{padding-bottom:24px}
.shell{max-width:1280px;padding:34px 28px 18px}
.library{grid-template-columns:280px minmax(0,820px);justify-content:center;align-items:start;gap:28px}
.books{position:sticky;top:24px;max-height:calc(100vh - 48px);overflow:auto;padding:22px 18px;scrollbar-width:thin}
.books h2{margin:8px 0 12px;font-size:18px}
.book-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.book-grid a{padding:7px 8px;font-size:12px}
.reader{padding:38px 42px 44px;overflow:visible}
.reader h1{font-size:clamp(40px,4vw,54px)}
.reader-head{margin-bottom:8px}
.chapter-strip{top:18px;margin:0 0 28px}
#chapter-text{max-width:72ch;margin:0 auto}
.verse{font-size:19px;line-height:1.78;overflow-wrap:break-word}
.source,.end-nav{max-width:72ch;margin-left:auto;margin-right:auto}
.chapter-dock{display:none}
.bottom{position:relative;left:auto;bottom:auto;transform:none;width:min(760px,calc(100% - 48px));height:68px;margin:28px auto 20px}
}
@media(max-width:760px){body{width:100%;max-width:100vw;overflow-x:hidden;background-attachment:scroll}.shell{width:100%;max-width:100vw;padding:18px 10px}.library{grid-template-columns:1fr}.books{order:2}.reader{order:1;width:100%;padding:16px 14px;border-radius:20px}.book-grid{grid-template-columns:repeat(2,1fr)}.reader h1{max-width:100%;font-size:clamp(28px,9vw,36px);overflow-wrap:anywhere}.reader-head{align-items:start;flex-direction:column}.chapter-strip{top:8px}.chapter-jump{align-items:stretch;flex-direction:column}.chapter-jump select{max-width:none;width:100%}.end-nav{grid-template-columns:1fr 1fr}.end-nav .top-link{grid-column:1/-1;grid-row:1}.chapter-dock{bottom:92px;width:calc(100% - 16px);max-width:620px}.bottom{bottom:8px;width:calc(100% - 16px);height:72px}.bottom a{font-size:10px}.verse{font-size:clamp(18px,5.2vw,21px);line-height:1.65}.chapter-dock .label{display:none}.guest-preview{align-items:stretch;flex-direction:column}.guest-preview-actions{display:grid;grid-template-columns:1fr}.guest-preview a{text-align:center}}
</style>
<style>.verse-tools{display:flex;gap:7px;margin:6px 0 18px;padding-left:26px;font-family:system-ui}.verse-tool{padding:6px 9px;border:1px solid #ffffff25;border-radius:999px;color:#dce9df;background:#ffffff0d;font-size:11px;cursor:pointer}.verse-tool.active{color:#173f2c;background:#f1cf7d}.verse[data-highlight="gold"]{margin-inline:-10px;padding:9px 10px;border-radius:12px;background:#f1cf7d22}.verse[data-highlight="green"]{margin-inline:-10px;padding:9px 10px;border-radius:12px;background:#6bc88d22}.verse-note{margin:-10px 0 17px 26px;padding:10px 12px;border-left:3px solid #f1cf7d;color:#dce9df;background:#ffffff0b;font:13px/1.5 system-ui;white-space:pre-wrap}</style>
</head>
<body>
<main class="shell">
<header class="top"><strong>DailyBreath · Bible Library</strong><a href="index.php">DailyBreath home →</a></header>
<?php if ($isGuestPreview): ?><section class="guest-preview" aria-label="Guest Bible preview"><div><strong>You’re testing the complete DailyBreath Bible</strong><span>Browse all 66 books without signing in. Create a Beyond ID to save progress and connect your Bible Academy journey.</span></div><div class="guest-preview-actions"><a href="../beyond-id/auth/register.php?app=dailybreath&return=<?= rawurlencode('/dailybreath/bible.php') ?>">Create Beyond ID</a><a class="secondary" href="../beyond-id/auth/login.php?app=dailybreath&return=<?= rawurlencode('/dailybreath/bible.php') ?>">Sign in</a></div></section><?php endif; ?>
<div class="library">
<aside class="books"><?php foreach ($books as $testament=>$items): ?><h2><?= e($testament) ?></h2><div class="book-grid"><?php foreach ($items as $name=>$count): ?><a class="<?= $name===$book?'active':'' ?>" href="?book=<?= urlencode($name) ?>&chapter=1#reader-top"><?= e($name) ?></a><?php endforeach; ?></div><?php endforeach; ?></aside>
<article class="reader" id="reader-top">
<div class="reader-head"><h1><?= e($book) ?> <?= $chapter ?></h1></div>
<div class="chapter-strip">
<div class="book-picker">
<label>Book
<select id="book-select" aria-label="Choose Bible book">
<?php foreach ($books as $testament=>$items): ?><optgroup label="<?= e($testament) ?>"><?php foreach ($items as $name=>$count): ?><option value="<?= e($name) ?>" <?= $name===$book?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
</select>
</label>
<label>Chapter
<select id="chapter-select-top" aria-label="Choose chapter"><?php for ($number=1;$number<=$flatBooks[$book];$number++): ?><option value="<?= $number ?>" <?= $number===$chapter?'selected':'' ?>><?= $number ?></option><?php endfor; ?></select>
</label>
</div>
<nav class="chapter-nav" id="chapter-nav" aria-label="Chapters"><?php for ($number=1;$number<=$flatBooks[$book];$number++): ?><a class="<?= $number===$chapter?'active':'' ?>" href="?book=<?= urlencode($book) ?>&chapter=<?= $number ?>#reader-top" <?= $number===$chapter?'aria-current="page"':'' ?>><?= $number ?></a><?php endfor; ?></nav>
<div class="chapter-jump"><span>Swipe chapters or jump directly</span><select id="chapter-select" aria-label="Choose chapter"><?php for ($number=1;$number<=$flatBooks[$book];$number++): ?><option value="<?= $number ?>" <?= $number===$chapter?'selected':'' ?>>Chapter <?= $number ?></option><?php endfor; ?></select></div>
</div>
<div id="chapter-text"><?php if ($verses): ?><?php foreach ($verses as $verse): ?><?php $verseKey=$book.'-'.$chapter.'-'.(int)$verse['verse_number'];?><p class="verse" id="verse-<?= (int)$verse['verse_number'] ?>" data-verse-key="<?=e($verseKey)?>"><sup><?= (int)$verse['verse_number'] ?></sup><?= e($verse['verse_text']) ?></p><div class="verse-tools" data-for="<?=e($verseKey)?>"><button class="verse-tool favorite" type="button" aria-label="Favorite <?=e($book.' '.$chapter.':'.(int)$verse['verse_number'])?>">♡ Favorite</button><button class="verse-tool highlight" type="button">Highlight</button><button class="verse-tool note" type="button">Note</button></div><?php endforeach; ?><?php else: ?><p class="empty">Bible text is unavailable for this chapter.</p><?php endif; ?></div>
<p class="source">World English Bible (WEBP), public domain. Text preserved unchanged from <a href="https://ebible.org/details.php?id=engwebp">eBible.org</a>.</p>
<nav class="end-nav" aria-label="End of chapter navigation">
<?php if ($previousChapter !== null): ?><a href="<?= e($chapterUrl($previousBook,$previousChapter)) ?>">← <span><small>Previous</small><br><?= e($previousBook) ?> <?= $previousChapter ?></span></a><?php else: ?><span class="disabled">← Beginning</span><?php endif; ?>
<a class="top-link" href="#reader-top">↑ Chapter top</a>
<?php if ($nextChapter !== null): ?><a href="<?= e($chapterUrl($nextBook,$nextChapter)) ?>"><span><small>Next</small><br><?= e($nextBook) ?> <?= $nextChapter ?></span> →</a><?php else: ?><span class="disabled">End →</span><?php endif; ?>
</nav>
</article>
</div>
</main>
<nav class="chapter-dock" aria-label="Persistent chapter navigation">
<?php if ($previousChapter !== null): ?><a href="<?= e($chapterUrl($previousBook,$previousChapter)) ?>">← <span class="label"><?= e($previousBook) ?> <?= $previousChapter ?></span></a><?php else: ?><span class="disabled">←</span><?php endif; ?>
<a class="current" href="#reader-top"><?= e($book) ?> <?= $chapter ?> ↑</a>
<?php if ($nextChapter !== null): ?><a href="<?= e($chapterUrl($nextBook,$nextChapter)) ?>"><span class="label"><?= e($nextBook) ?> <?= $nextChapter ?></span> →</a><?php else: ?><span class="disabled">→</span><?php endif; ?>
</nav>
<nav class="bottom"><a href="index.php">Home</a><a href="index.php#devotionals">Devotionals</a><a class="active" href="bible.php">Bible</a><a href="academy.php">Academy</a><a href="practices.php?section=breathing">Breathe</a></nav>
<script>
const bookSelect=document.getElementById('book-select'),chapterSelectTop=document.getElementById('chapter-select-top');bookSelect.addEventListener('change',()=>{location.href='?book='+encodeURIComponent(bookSelect.value)+'&chapter=1#reader-top'});chapterSelectTop.addEventListener('change',()=>{location.href='?book=<?= rawurlencode($book) ?>&chapter='+chapterSelectTop.value+'#reader-top'});
const chapterSelect=document.getElementById('chapter-select');chapterSelect.addEventListener('change',()=>{location.href='?book=<?= rawurlencode($book) ?>&chapter='+chapterSelect.value+'#reader-top'});
const activeChapter=document.querySelector('.chapter-nav a.active');if(activeChapter){requestAnimationFrame(()=>activeChapter.scrollIntoView({behavior:'instant',block:'nearest',inline:'center'}));}
const annotationKey='dailybreath.verseAnnotations';let annotations={};try{annotations=JSON.parse(localStorage.getItem(annotationKey)||'{}')}catch(e){};
const saveAnnotations=()=>localStorage.setItem(annotationKey,JSON.stringify(annotations));
document.querySelectorAll('.verse-tools').forEach(tools=>{const key=tools.dataset.for,verse=document.querySelector(`[data-verse-key="${CSS.escape(key)}"]`),favorite=tools.querySelector('.favorite'),highlight=tools.querySelector('.highlight'),note=tools.querySelector('.note');const render=()=>{const item=annotations[key]||{};favorite.classList.toggle('active',!!item.favorite);favorite.textContent=item.favorite?'♥ '+(item.collection||'Saved verses'):'♡ Favorite';highlight.classList.toggle('active',!!item.highlight);verse.dataset.highlight=item.highlight||'';document.querySelector(`[data-note-for="${CSS.escape(key)}"]`)?.remove();if(item.note){const el=document.createElement('div');el.className='verse-note';el.dataset.noteFor=key;el.textContent=item.note;tools.after(el)}note.classList.toggle('active',!!item.note)};favorite.onclick=()=>{if(annotations[key]?.favorite){annotations[key]={...(annotations[key]||{}),favorite:false}}else{const collection=prompt('Save to which collection?','Saved verses');if(collection===null)return;annotations[key]={...(annotations[key]||{}),favorite:true,collection:collection.trim()||'Saved verses'}}saveAnnotations();render()};highlight.onclick=()=>{const current=annotations[key]?.highlight||'',next=current===''?'gold':current==='gold'?'green':'';annotations[key]={...(annotations[key]||{}),highlight:next};saveAnnotations();render()};note.onclick=()=>{const value=prompt('Private note stored on this browser:',annotations[key]?.note||'');if(value===null)return;annotations[key]={...(annotations[key]||{}),note:value.trim()};saveAnnotations();render()};render()});
</script>
<?= dailybreath_web_scripts() ?><script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
