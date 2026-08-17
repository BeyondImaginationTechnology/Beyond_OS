<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
if (!empty($_SESSION['user_id'])) {
    beyond_track_app('Beyond TV');
}

$catalog = json_decode((string) file_get_contents(__DIR__ . '/data/catalog.json'), true) ?: [];
$view = strtolower((string)($_GET['view'] ?? 'all'));
if (!in_array($view, ['all','shows','movies'], true)) { $view = 'all'; }
$filtered = array_values(array_filter($catalog, static function(array $item) use ($view): bool {
    return $view === 'all' || ($view === 'shows' && ($item['type'] ?? '') === 'show') || ($view === 'movies' && ($item['type'] ?? '') === 'movie');
}));
$genreCounts = [];
foreach ($filtered as $item) {
    foreach (preg_split('/\s*(?:Â·|·|•)\s*/u', (string)($item['genre'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $genreName) {
        $genreName = trim($genreName);
        if ($genreName !== '') $genreCounts[$genreName] = ($genreCounts[$genreName] ?? 0) + 1;
    }
}
uksort($genreCounts, 'strnatcasecmp');
$heading = $view === 'shows' ? 'TV Shows' : ($view === 'movies' ? 'Free Movies' : 'Browse');
?>
<!doctype html><html lang="en"><head><script>(function(){try{const t=localStorage.getItem("beyond-tv-theme");document.documentElement.dataset.tvTheme=["dark","light","sunset"].includes(t)?t:"sunset"}catch(e){document.documentElement.dataset.tvTheme="sunset"}})();</script><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#401532"><title><?=htmlspecialchars($heading)?> | Beyond TV</title><link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.1"></head><body class="tv-app"><?php include __DIR__.'/partials/header.php'; ?>
<main class="page shell catalog-page">
<span class="kicker">BEYOND TV LIBRARY</span><h1><?=htmlspecialchars($heading)?></h1><p class="lead">Browse the complete library and watch available movies and episodes without an account.</p>
<div class="library-search"><label for="library-filter">Search library</label><input id="library-filter" type="search" placeholder="Search Yu-Gi-Oh!, Mr. Bean, Courage…" autocomplete="off"></div><nav class="catalog-tabs" aria-label="Browse library"><a class="<?=$view==='all'?'is-active':''?>" href="/beyond-tv/browse.php">All</a><a class="<?=$view==='shows'?'is-active':''?>" href="/beyond-tv/browse.php?view=shows">TV Shows</a><a class="<?=$view==='movies'?'is-active':''?>" href="/beyond-tv/browse.php?view=movies">Free Movies</a><a href="/beyond-tv/live-tv.php">Live Guide</a></nav>
<section class="genre-filter" aria-labelledby="genre-filter-title"><div class="genre-filter-heading"><div><span class="kicker">FILTER CATALOGUE</span><h2 id="genre-filter-title">Browse by genre</h2></div><button type="button" class="genre-clear" data-genre-clear hidden>Clear filter ×</button></div><div class="genre-chips" role="group" aria-label="Filter titles by genre"><button type="button" class="is-active" data-genre="all" aria-pressed="true">All genres <span><?=count($filtered)?></span></button><?php foreach($genreCounts as $genreName=>$genreCount): ?><button type="button" data-genre="<?=htmlspecialchars(strtolower($genreName))?>" aria-pressed="false"><?=htmlspecialchars($genreName)?> <span><?=$genreCount?></span></button><?php endforeach; ?></div></section>
<div class="catalog-grid"><?php foreach($filtered as $item): ?>
<?php $posterUrl = '/beyond-tv/api/poster.php?slug=' . rawurlencode((string)($item['slug'] ?? '')) . '&v=archive'; ?>
<article class="catalog-card" data-library-card data-library-title="<?=htmlspecialchars(strtolower((string)$item['title']))?>"><a href="/beyond-tv/title.php?slug=<?=urlencode((string)$item['slug'])?>"><div class="catalog-art" style="background:<?=htmlspecialchars((string)$item['gradient'])?> url(<?=htmlspecialchars($posterUrl)?>) center/cover no-repeat"><span class="catalog-art-shade"></span><span class="catalog-icon"><?=htmlspecialchars((string)$item['icon'])?></span><span class="catalog-type"><?=($item['type']??'')==='movie'?'MOVIE':'SERIES'?></span><span class="catalog-play">▶</span></div><div class="catalog-copy"><small><?=htmlspecialchars((string)($item['subtitle']??''))?></small><h2><?=htmlspecialchars((string)$item['title'])?></h2><p><?=htmlspecialchars((string)($item['year']??''))?> · <?=htmlspecialchars((string)($item['rating']??'NR'))?><?php if(!empty($item['runtime'])):?> · <?=htmlspecialchars((string)$item['runtime'])?><?php endif;?></p><span><?=htmlspecialchars((string)($item['genre']??''))?></span></div></a></article>
<?php endforeach; ?></div>
<p class="library-empty" data-library-empty hidden>No titles match this search and genre.</p></main><?php include __DIR__.'/partials/footer.php'; ?><script src="/beyond-tv/assets/js/app.js?v=3.0.1"></script><script>(function(){const input=document.getElementById('library-filter'),cards=[...document.querySelectorAll('[data-library-card]')],empty=document.querySelector('[data-library-empty]'),buttons=[...document.querySelectorAll('[data-genre]')],clear=document.querySelector('[data-genre-clear]');let genre='all';function filter(){const q=(input?.value||'').trim().toLowerCase();let shown=0;cards.forEach(card=>{const text=card.textContent.toLowerCase(),searchMatch=!q||(card.dataset.libraryTitle||'').includes(q)||text.includes(q),genreMatch=genre==='all'||text.includes(genre);card.hidden=!(searchMatch&&genreMatch);if(!card.hidden)shown++});if(empty)empty.hidden=shown!==0}buttons.forEach(button=>button.addEventListener('click',()=>{genre=button.dataset.genre||'all';buttons.forEach(item=>{const active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-pressed',String(active))});if(clear)clear.hidden=genre==='all';filter()}));clear?.addEventListener('click',()=>buttons.find(button=>button.dataset.genre==='all')?.click());input?.addEventListener('input',filter)})();</script><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
