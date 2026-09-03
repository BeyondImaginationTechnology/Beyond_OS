<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/web-app.php';
require_once __DIR__ . '/includes/sacred-text.php';

$isGuestPreview = empty($_SESSION['user_id']);
if ($isGuestPreview) {
    header('X-Beyond-Guest-Preview: DailyBreath-Scripture');
    $beyondWallet = beyond_nav_bootstrap('DailyBreath', ['balance'=>0,'currency'=>'BITS','status'=>'guest']);
} else {
    $beyondWallet = beyond_app_bootstrap('DailyBreath');
}

$tradition = dailybreath_faith_tradition((string)($_GET['tradition'] ?? $_COOKIE['dailybreath_faith'] ?? 'bible'));
setcookie('dailybreath_faith', $tradition, ['expires'=>time()+31536000,'path'=>'/dailybreath/','samesite'=>'Lax']);
$traditionLabel = dailybreath_tradition_label($tradition);

if ($tradition === 'quran') {
    $surahs = dailybreath_quran_surahs();
    $groups = ['114 Surahs'=>[]];
    foreach ($surahs as $number=>$name) $groups['114 Surahs'][(string)$number] = 1;
    $defaultBook = '1';
} else {
    $surahs = [];
    $groups = dailybreath_bible_book_groups($tradition === 'torah');
    $defaultBook = $tradition === 'torah' ? 'Genesis' : 'Psalms';
}
$flatBooks = array_merge(...array_values($groups));
$book = (string)($_GET['book'] ?? $defaultBook);
if (!isset($flatBooks[$book])) $book = $defaultBook;
$chapter = max(1, min((int)($_GET['chapter'] ?? ($tradition === 'bible' ? 46 : 1)), (int)$flatBooks[$book]));
$verses = dailybreath_sacred_chapter($tradition, $book, $chapter);
$query = substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$searchResults = $query === '' ? [] : dailybreath_search_sacred_text($tradition, $query);

$bookName = $tradition === 'quran'
    ? ($surahs[(int)$book] ?? ('Surah '.$book))
    : ($tradition === 'torah' ? dailybreath_jewish_book_name($book) : $book);
$readerTitle = $tradition === 'quran' ? 'Surah '.$bookName.' · '.$book : $bookName.' '.$chapter;
$isTanakh = $tradition === 'torah';
$readingNoun = $tradition === 'quran' ? 'ayahs' : ($isTanakh ? 'passages' : 'verses');
$bookControlLabel = $tradition === 'quran' ? 'Surah' : ($isTanakh ? 'Sefer' : 'Book');
$chapterControlLabel = $isTanakh ? 'Perek' : 'Chapter';
$reflectionHref = $isTanakh ? 'index.php?faith=torah#devotionals' : ($tradition === 'quran' ? 'index.php?faith=quran#devotionals' : 'devotionals.php');
$reflectionLabel = $isTanakh || $tradition === 'quran' ? 'Reflection' : 'Devotionals';
$translation = $tradition === 'torah'
    ? 'Tanakh text in the World English Bible translation, public domain. Book names follow Jewish usage.'
    : ($tradition === 'quran'
        ? 'The Meaning of the Glorious Koran, English translation by Marmaduke Pickthall. Project Gutenberg eBook 16955; public domain in the USA.'
        : 'World English Bible (WEBP), public domain.');

$units = [];
foreach ($groups as $section=>$items) foreach ($items as $unitBook=>$chapterCount) {
    for ($number=1; $number<=$chapterCount; $number++) $units[] = ['book'=>(string)$unitBook,'chapter'=>$number];
}
$currentUnit = 0;
foreach ($units as $index=>$unit) if ($unit['book']===$book && $unit['chapter']===$chapter) { $currentUnit=$index; break; }
$previous = $units[$currentUnit-1] ?? null;
$next = $units[$currentUnit+1] ?? null;
$chapterUrl = static fn(array $unit): string => '?tradition='.rawurlencode($tradition).'&book='.rawurlencode($unit['book']).'&chapter='.$unit['chapter'].'#reader-top';
?>
<!doctype html>
<html lang="en" data-faith="<?= e($tradition) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($readerTitle) ?> | DailyBreath <?= e($traditionLabel) ?></title>
<meta name="description" content="Read and search the complete DailyBreath <?= e($traditionLabel) ?> library.">
<?= dailybreath_web_head($readerTitle . ' | DailyBreath ' . $traditionLabel) ?>
<style>
:root{--bg:#06150d;--panel:#103624e8;--panel2:#092a1c;--ink:#f6fbf7;--muted:#bfd2c3;--accent:#f1cf7d;--accentInk:#173f2c;--line:#ffffff2d;--glow:#3d9b6255}html[data-faith=torah]{--bg:#f2ead8;--panel:#fffaf0ed;--panel2:#f4e7cc;--ink:#252117;--muted:#665f50;--accent:#2d6eaa;--accentInk:#fff;--line:#765f3233;--glow:#f2c85b55}html[data-faith=quran]{--bg:#050817;--panel:#10152eea;--panel2:#080c21;--ink:#f4f4ff;--muted:#b9bdd9;--accent:#9b8cff;--accentInk:#090b20;--line:#9b8cff40;--glow:#6351d855}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;padding-bottom:104px;color:var(--ink);background:radial-gradient(circle at 82% 0,var(--glow),transparent 31%),var(--bg);font:16px/1.55 Inter,system-ui,sans-serif}.shell{width:min(1280px,calc(100% - 28px));margin:auto;padding:28px 0}.top{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.top a{color:var(--accent);text-decoration:none;font-weight:850}.faith-switch{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:0 0 20px;padding:8px;border:1px solid var(--line);border-radius:18px;background:var(--panel2)}.faith-switch a{padding:11px 12px;border-radius:12px;color:var(--muted);text-align:center;text-decoration:none;font-size:13px;font-weight:900}.faith-switch a.active{color:var(--accentInk);background:var(--accent)}.search{display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:20px}.search input{min-width:0;padding:13px 15px;border:1px solid var(--line);border-radius:13px;color:var(--ink);background:var(--panel);font:inherit}.search button{padding:0 18px;border:0;border-radius:13px;color:var(--accentInk);background:var(--accent);font-weight:900}.results{margin-bottom:20px;padding:18px;border:1px solid var(--line);border-radius:20px;background:var(--panel)}.results-head{display:flex;justify-content:space-between;gap:15px}.results a{display:block;padding:13px 0;border-top:1px solid var(--line);color:var(--ink);text-decoration:none}.results strong{display:block;color:var(--accent)}.results p{margin:3px 0;color:var(--muted)}.library{display:grid;grid-template-columns:290px minmax(0,1fr);gap:22px}.books,.reader{border:1px solid var(--line);border-radius:24px;background:var(--panel);box-shadow:0 24px 65px #0004}.books{max-height:calc(100vh - 36px);overflow:auto;padding:18px;position:sticky;top:18px}.books h2{margin:12px 8px 8px;color:var(--accent);font-size:14px}.book-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px}.book-grid a{padding:8px;border-radius:9px;color:var(--muted);text-decoration:none;font-size:12px;font-weight:700}.book-grid a:hover,.book-grid a.active{color:var(--accentInk);background:var(--accent)}.reader{min-width:0;padding:34px 40px}.reader-head{display:flex;align-items:start;justify-content:space-between;gap:18px}.eyebrow{color:var(--accent);font-size:11px;font-weight:950;letter-spacing:.13em}.reader h1{margin:5px 0 22px;font:700 clamp(34px,6vw,54px)/1 Georgia,serif}.chapter-controls{position:sticky;z-index:10;top:12px;margin-bottom:27px;padding:11px;border:1px solid var(--line);border-radius:16px;background:var(--panel2);box-shadow:0 12px 34px #0003}.pickers{display:grid;grid-template-columns:1fr minmax(100px,.35fr);gap:8px}.pickers label{display:grid;gap:4px;color:var(--muted);font-size:10px;font-weight:900}.pickers select{width:100%;min-width:0;padding:10px;border:1px solid var(--line);border-radius:10px;color:var(--ink);background:var(--panel);font-weight:800}.chapters{display:flex;gap:7px;overflow:auto;margin-top:9px;padding-bottom:3px}.chapters a{flex:0 0 36px;display:grid;place-items:center;height:36px;border-radius:10px;color:var(--muted);background:#ffffff0b;text-decoration:none;font-size:12px;font-weight:800}.chapters a.active{color:var(--accentInk);background:var(--accent)}#chapter-text{max-width:76ch;margin:auto}.verse{scroll-margin-top:155px;margin:0;padding:8px 0;font:19px/1.75 Georgia,serif}.verse sup{margin-right:8px;color:var(--accent);font:800 11px system-ui}.verse-tools{display:flex;gap:6px;margin:0 0 11px;padding-left:25px}.verse-tool{padding:5px 8px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:transparent;font-size:10px;cursor:pointer}.verse-tool.active{color:var(--accentInk);background:var(--accent)}.verse[data-highlight=gold]{background:#eec95b23}.verse[data-highlight=green]{background:#54c77d20}.verse-note{margin:-6px 0 14px 25px;padding:9px;border-left:3px solid var(--accent);color:var(--muted);font-size:12px;white-space:pre-wrap}.source{max-width:76ch;margin:28px auto 0;padding:14px;border:1px solid var(--line);border-radius:13px;color:var(--muted);font-size:11px}.end-nav{max-width:76ch;margin:20px auto 0;display:grid;grid-template-columns:1fr auto 1fr;gap:8px}.end-nav a,.end-nav span{min-height:46px;padding:11px;border:1px solid var(--line);border-radius:13px;color:var(--ink);background:var(--panel2);text-decoration:none;font-size:12px;font-weight:800}.end-nav a:last-child{text-align:right}.end-nav .top-link{text-align:center;color:var(--accent)}.disabled{opacity:.35}.bottom{position:fixed;z-index:20;left:50%;bottom:12px;transform:translateX(-50%);width:min(650px,calc(100% - 20px));height:72px;display:flex;align-items:center;justify-content:space-around;border:1px solid var(--line);border-radius:23px;background:var(--panel2);box-shadow:0 18px 55px #0007}.bottom a{color:var(--muted);text-decoration:none;font-size:11px;font-weight:800}.bottom .active{color:var(--accent)}@media(max-width:800px){.shell{width:calc(100% - 16px);padding-top:16px}.top{align-items:flex-start;flex-direction:column}.library{grid-template-columns:1fr}.reader{order:1;padding:22px 15px}.books{order:2;position:static;max-height:none}.reader-head{flex-direction:column}.pickers{grid-template-columns:1fr}.end-nav{grid-template-columns:1fr 1fr}.end-nav .top-link{grid-column:1/-1;grid-row:1}.verse{font-size:18px}.faith-switch a{padding:10px 4px;font-size:11px}}
</style>
<style>.bottom-dock{position:fixed;z-index:20;left:50%;bottom:18px;transform:translateX(-50%);width:min(430px,calc(100% - 36px));height:52px;display:flex;align-items:center;justify-content:space-around;border:1px solid var(--line);border-radius:999px;background:var(--panel2);box-shadow:0 14px 38px #0007}.bottom-dock a{color:var(--muted);text-decoration:none;font-size:10px;font-weight:800;padding:10px 7px}.bottom-dock .active{color:var(--accent)}</style>
</head>
<body>
<main class="shell">
<header class="top"><strong>DailyBreath · Sacred Text Library</strong><a href="index.php?faith=<?= e($tradition) ?>">DailyBreath home →</a></header>
<nav class="faith-switch" aria-label="Choose faith tradition">
<?php foreach (['bible'=>'Bible','torah'=>'Tanakh','quran'=>'Quran'] as $key=>$label): ?><a class="<?= $tradition===$key?'active':'' ?>" href="?tradition=<?= e($key) ?>"><?= e($label) ?></a><?php endforeach; ?>
</nav>
<form class="search" method="get"><input type="hidden" name="tradition" value="<?= e($tradition) ?>"><input name="q" value="<?= e($query) ?>" placeholder="Search all <?= e($traditionLabel) ?> <?= e($readingNoun) ?>" aria-label="Search <?= e($traditionLabel) ?> <?= e($readingNoun) ?>"><button>Search</button></form>
<?php if ($query !== ''): ?><section class="results"><div class="results-head"><strong><?= count($searchResults) ?> results for “<?= e($query) ?>”</strong><a href="?tradition=<?= e($tradition) ?>">Clear</a></div><?php foreach ($searchResults as $result): ?><a href="<?= e($result['url']) ?>"><strong><?= e($result['reference']) ?></strong><p><?= e($result['text']) ?></p></a><?php endforeach; ?><?php if (!$searchResults): ?><p>No matching verses. Try fewer words.</p><?php endif; ?></section><?php endif; ?>
<div class="library">
<aside class="books"><?php foreach ($groups as $section=>$items): ?><h2><?= e($section) ?></h2><div class="book-grid"><?php foreach ($items as $itemBook=>$count): ?><?php $itemName=$tradition==='quran' ? $itemBook.'. '.($surahs[(int)$itemBook]??'Surah') : ($tradition==='torah' ? dailybreath_jewish_book_name((string)$itemBook) : $itemBook); ?><a class="<?= $book===(string)$itemBook?'active':'' ?>" href="?tradition=<?= e($tradition) ?>&book=<?= rawurlencode((string)$itemBook) ?>&chapter=1#reader-top"><?= e($itemName) ?></a><?php endforeach; ?></div><?php endforeach; ?></aside>
<article class="reader" id="reader-top">
<div class="reader-head"><div><span class="eyebrow"><?= e(strtoupper($traditionLabel)) ?> · COMPLETE LOCAL EDITION</span><h1><?= e($readerTitle) ?></h1></div></div>
<div class="chapter-controls"><div class="pickers"><label><?= e($bookControlLabel) ?><select id="book-select"><?php foreach ($groups as $section=>$items): ?><optgroup label="<?= e($section) ?>"><?php foreach ($items as $itemBook=>$count): ?><?php $itemName=$tradition==='quran' ? $itemBook.'. '.($surahs[(int)$itemBook]??'Surah') : ($tradition==='torah' ? dailybreath_jewish_book_name((string)$itemBook) : $itemBook); ?><option value="<?= e((string)$itemBook) ?>" <?= $book===(string)$itemBook?'selected':'' ?>><?= e($itemName) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></label><?php if ($tradition!=='quran'): ?><label><?= e($chapterControlLabel) ?><select id="chapter-select"><?php for($number=1;$number<=$flatBooks[$book];$number++): ?><option value="<?= $number ?>" <?= $number===$chapter?'selected':'' ?>><?= $number ?></option><?php endfor; ?></select></label><?php endif; ?></div><?php if ($tradition!=='quran'): ?><nav class="chapters" aria-label="<?= e($chapterControlLabel) ?>s"><?php for($number=1;$number<=$flatBooks[$book];$number++): ?><a class="<?= $number===$chapter?'active':'' ?>" href="?tradition=<?= e($tradition) ?>&book=<?= rawurlencode($book) ?>&chapter=<?= $number ?>#reader-top"><?= $number ?></a><?php endfor; ?></nav><?php endif; ?></div>
<div id="chapter-text"><?php if ($verses): ?><?php foreach($verses as $verse): ?><?php $key=$tradition.'-'.$book.'-'.$chapter.'-'.$verse['verse_number']; ?><p class="verse" id="verse-<?= $verse['verse_number'] ?>" data-verse-key="<?= e($key) ?>"><sup><?= $verse['verse_number'] ?></sup><?= e($verse['verse_text']) ?></p><div class="verse-tools" data-for="<?= e($key) ?>"><button class="verse-tool favorite" type="button">♡ Save</button><button class="verse-tool highlight" type="button">Highlight</button><button class="verse-tool note" type="button">Note</button></div><?php endforeach; ?><?php else: ?><p>Text unavailable for this chapter.</p><?php endif; ?></div>
<p class="source"><?= e($translation) ?></p>
<nav class="end-nav"><?php if($previous): ?><a href="<?= e($chapterUrl($previous)) ?>">← Previous</a><?php else: ?><span class="disabled">← Beginning</span><?php endif; ?><a class="top-link" href="#reader-top">↑ Top</a><?php if($next): ?><a href="<?= e($chapterUrl($next)) ?>">Next →</a><?php else: ?><span class="disabled">End →</span><?php endif; ?></nav>
</article></div></main>
<nav class="bottom-dock" aria-label="DailyBreath navigation"><a href="index.php?faith=<?= e($tradition) ?>">Home</a><a href="<?= e($reflectionHref) ?>"><?= e($reflectionLabel) ?></a><a class="active" href="scripture.php?tradition=<?= e($tradition) ?>"><?= e($traditionLabel) ?></a><a href="academy.php">Academy</a><a href="practices.php?section=breathing">Breathe</a></nav>
<script>
const tradition=<?= json_encode($tradition) ?>,book=<?= json_encode($book) ?>,bookSelect=document.getElementById('book-select'),chapterSelect=document.getElementById('chapter-select');bookSelect.onchange=()=>location.href='?tradition='+tradition+'&book='+encodeURIComponent(bookSelect.value)+'&chapter=1#reader-top';if(chapterSelect)chapterSelect.onchange=()=>location.href='?tradition='+tradition+'&book='+encodeURIComponent(book)+'&chapter='+chapterSelect.value+'#reader-top';
const annotationKey='dailybreath.verseAnnotations',annotations=(()=>{try{return JSON.parse(localStorage.getItem(annotationKey)||'{}')}catch(e){return{}}})(),save=()=>localStorage.setItem(annotationKey,JSON.stringify(annotations));document.querySelectorAll('.verse-tools').forEach(tools=>{const key=tools.dataset.for,verse=document.querySelector(`[data-verse-key="${CSS.escape(key)}"]`),favorite=tools.querySelector('.favorite'),highlight=tools.querySelector('.highlight'),note=tools.querySelector('.note');const render=()=>{const item=annotations[key]||{};favorite.classList.toggle('active',!!item.favorite);favorite.textContent=item.favorite?'♥ Saved':'♡ Save';highlight.classList.toggle('active',!!item.highlight);verse.dataset.highlight=item.highlight||'';document.querySelector(`[data-note-for="${CSS.escape(key)}"]`)?.remove();if(item.note){const el=document.createElement('div');el.className='verse-note';el.dataset.noteFor=key;el.textContent=item.note;tools.after(el)}note.classList.toggle('active',!!item.note)};favorite.onclick=()=>{annotations[key]={...(annotations[key]||{}),favorite:!annotations[key]?.favorite};save();render()};highlight.onclick=()=>{const current=annotations[key]?.highlight||'',next=current===''?'gold':current==='gold'?'green':'';annotations[key]={...(annotations[key]||{}),highlight:next};save();render()};note.onclick=()=>{const value=prompt('Private note stored on this browser:',annotations[key]?.note||'');if(value===null)return;annotations[key]={...(annotations[key]||{}),note:value.trim()};save();render()};render()});
</script>
<?= dailybreath_web_scripts() ?><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
