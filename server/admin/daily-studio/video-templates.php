<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/_header.php';

$templates = [
    [
        'id' => 'game-trailer',
        'category' => 'trailers',
        'index' => '01',
        'title' => 'Game Trailer',
        'kicker' => 'Launch Sequence',
        'description' => 'A high-impact reveal built around a cold open, world reveal, gameplay escalation, and title-card finish.',
        'duration' => 45,
        'ratio' => '16:9',
        'fps' => 30,
        'tone' => 'Electric / cinematic',
        'class' => 'is-game',
        'beats' => ['Cold open', 'World reveal', 'Gameplay montage', 'Launch card'],
    ],
    [
        'id' => 'ad-spot',
        'category' => 'ads',
        'index' => '02',
        'title' => 'Ad Spot',
        'kicker' => 'Product Pulse',
        'description' => 'A clean, conversion-focused spot with an immediate hook, product payoff, social proof, and direct CTA.',
        'duration' => 15,
        'ratio' => '9:16',
        'fps' => 30,
        'tone' => 'Bold / commercial',
        'class' => 'is-ad',
        'beats' => ['Pattern break', 'Product focus', 'Proof point', 'Call to action'],
    ],
    [
        'id' => 'short-film',
        'category' => 'films',
        'index' => '03',
        'title' => 'Short Film',
        'kicker' => 'Quiet Orbit',
        'description' => 'A restrained narrative framework for atmospheric openings, character turns, and an emotionally clean ending.',
        'duration' => 60,
        'ratio' => '2.39:1',
        'fps' => 24,
        'tone' => 'Human / atmospheric',
        'class' => 'is-film',
        'beats' => ['Establishing image', 'Inciting detail', 'Character turn', 'Final image'],
    ],
    [
        'id' => 'anime-opener',
        'category' => 'anime',
        'index' => '04',
        'title' => 'Anime Opener',
        'kicker' => 'Neon Shonen',
        'description' => 'A kinetic opening sequence with character introductions, speed-line transitions, and a heroic logo resolve.',
        'duration' => 30,
        'ratio' => '16:9',
        'fps' => 24,
        'tone' => 'Kinetic / heroic',
        'class' => 'is-anime',
        'beats' => ['Hero reveal', 'Team cards', 'Action burst', 'Series title'],
    ],
    [
        'id' => 'cartoon-short',
        'category' => 'cartoons',
        'index' => '05',
        'title' => 'Cartoon Short',
        'kicker' => 'Saturday Spark',
        'description' => 'A playful comic setup with elastic motion, visual punchlines, and a bright end-card designed for repeat viewing.',
        'duration' => 20,
        'ratio' => '16:9',
        'fps' => 24,
        'tone' => 'Playful / expressive',
        'class' => 'is-cartoon',
        'beats' => ['Comic setup', 'Escalation', 'Visual punchline', 'End card'],
    ],
];
?>
<style>
  :root{--video-ink:#f8f6ff;--video-muted:#aaa5b6;--video-line:rgba(255,255,255,.1);--video-panel:#121019;--video-lime:#d9ff57;--video-violet:#a990ff}
  .video-studio{margin:-30px;min-height:calc(100vh - 1px);padding:42px clamp(22px,4vw,58px) 70px;background:#08070b;color:var(--video-ink);overflow:hidden}
  .video-studio *{box-sizing:border-box}.video-shell{max-width:1400px;margin:0 auto}.video-topline{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:58px}.video-brand{display:flex;align-items:center;gap:11px;font-size:13px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.video-brand-mark{width:11px;height:11px;border-radius:2px;background:var(--video-lime);box-shadow:13px 0 0 var(--video-violet);transform:rotate(45deg);margin-right:10px}.video-status{display:flex;align-items:center;gap:8px;color:#aaa5b6;font:700 11px/1 ui-monospace,SFMono-Regular,monospace;letter-spacing:.08em;text-transform:uppercase}.video-status::before{content:"";width:7px;height:7px;border-radius:50%;background:#75e7a7;box-shadow:0 0 12px rgba(117,231,167,.6)}
  .video-hero{position:relative;display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:70px;align-items:end;padding-bottom:46px;border-bottom:1px solid var(--video-line)}.video-hero::after{content:"";position:absolute;width:440px;height:440px;right:-120px;top:-240px;border:1px solid rgba(169,144,255,.18);border-radius:50%;box-shadow:0 0 0 70px rgba(169,144,255,.03),0 0 0 140px rgba(217,255,87,.018);pointer-events:none}.video-eyebrow{display:flex;align-items:center;gap:10px;margin:0 0 20px;color:var(--video-lime);font:800 11px/1 ui-monospace,SFMono-Regular,monospace;letter-spacing:.16em;text-transform:uppercase}.video-eyebrow::before{content:"";width:32px;height:1px;background:currentColor}.video-hero h1{max-width:900px;margin:0;font-size:clamp(54px,7.3vw,108px);line-height:.85;letter-spacing:-.075em;font-weight:900}.video-hero h1 em{display:block;color:transparent;-webkit-text-stroke:1.5px rgba(248,246,255,.8);font-style:normal}.video-intro{position:relative;z-index:1;margin:0 0 4px;color:var(--video-muted);font-size:15px;line-height:1.72}.video-intro strong{display:block;margin-bottom:10px;color:var(--video-ink);font-size:12px;letter-spacing:.11em;text-transform:uppercase}
  .video-controls{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:26px 0 24px}.video-filters{display:flex;flex-wrap:wrap;gap:7px}.video-filter{border:1px solid transparent;border-radius:999px;padding:9px 13px;background:transparent;color:#8f8999;font:800 11px/1 inherit;letter-spacing:.06em;text-transform:uppercase;cursor:pointer}.video-filter:hover{color:#fff}.video-filter.active{border-color:rgba(217,255,87,.32);background:rgba(217,255,87,.09);color:var(--video-lime)}.video-count{color:#76717e;font:700 11px/1 ui-monospace,SFMono-Regular,monospace;text-transform:uppercase;letter-spacing:.1em;white-space:nowrap}
  .template-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}.template-card{grid-column:span 4;display:flex;flex-direction:column;min-height:530px;border:1px solid var(--video-line);border-radius:4px;background:rgba(255,255,255,.025);overflow:hidden;transition:transform .25s ease,border-color .25s ease,opacity .2s ease}.template-card:nth-child(1),.template-card:nth-child(2){grid-column:span 6}.template-grid.is-filtered .template-card{grid-column:1/-1;display:grid;grid-template-columns:minmax(360px,1.2fr) minmax(320px,.8fr);min-height:0}.template-grid.is-filtered .template-visual{min-height:420px}.template-card:hover{transform:translateY(-4px);border-color:rgba(255,255,255,.24)}.template-card[hidden]{display:none}.template-visual{position:relative;min-height:255px;overflow:hidden;background:#17131f}.template-card:nth-child(-n+2) .template-visual{min-height:300px}.template-visual::before,.template-visual::after{content:"";position:absolute;pointer-events:none}.template-index{position:absolute;z-index:3;left:18px;top:16px;color:rgba(255,255,255,.72);font:700 10px/1 ui-monospace,SFMono-Regular,monospace;letter-spacing:.12em}.template-format{position:absolute;z-index:3;right:15px;top:14px;padding:6px 8px;border:1px solid rgba(255,255,255,.2);background:rgba(5,4,8,.45);backdrop-filter:blur(8px);color:#fff;font:700 9px/1 ui-monospace,SFMono-Regular,monospace;letter-spacing:.08em}.visual-word{position:absolute;z-index:2;inset:auto 18px 16px;font-size:clamp(34px,4.1vw,62px);line-height:.82;letter-spacing:-.06em;font-weight:950;text-transform:uppercase}.is-game .template-visual{background:radial-gradient(circle at 68% 34%,#b46cff 0 3%,transparent 4%),linear-gradient(135deg,#221342,#0b0a12 58%,#172840)}.is-game .template-visual::before{width:280px;height:280px;right:2%;top:-30%;border:1px solid rgba(217,255,87,.75);border-radius:50%;box-shadow:0 0 50px rgba(217,255,87,.25)}.is-game .template-visual::after{inset:0;background:repeating-linear-gradient(112deg,transparent 0 33px,rgba(255,255,255,.035) 34px,transparent 35px)}.is-game .visual-word{color:var(--video-lime);text-shadow:4px 4px 0 #52358b}.is-ad .template-visual{background:linear-gradient(120deg,#ff735c 0 42%,#ffca55 42% 66%,#f7f0dc 66%)}.is-ad .template-visual::before{width:125px;height:190px;right:17%;top:18%;border-radius:28px 28px 13px 13px;background:#111;transform:rotate(11deg);box-shadow:-18px 21px 0 rgba(255,255,255,.25)}.is-ad .visual-word{max-width:65%;color:#111}.is-film .template-visual{background:linear-gradient(to bottom,#8d8b9a 0 44%,#302b2b 45% 49%,#09090c 50%)}.is-film .template-visual::before{left:14%;bottom:24%;width:19px;height:76px;border-radius:50% 50% 7px 7px;background:#08080a;box-shadow:120px 11px 0 -4px #08080a}.is-film .template-visual::after{inset:0;background:linear-gradient(90deg,rgba(216,105,75,.26),transparent 45%,rgba(50,66,100,.4))}.is-film .visual-word{font-family:Georgia,serif;font-weight:400;font-style:italic;text-transform:none;color:#f2e9dd}.is-anime .template-visual{background:radial-gradient(circle at 68% 38%,#fff 0 3%,#ffdf4b 4% 8%,transparent 9%),linear-gradient(145deg,#1b75ff,#8126c7 55%,#ff466d)}.is-anime .template-visual::before{inset:-80%;background:repeating-conic-gradient(from 220deg,rgba(255,255,255,.26) 0 1deg,transparent 1deg 8deg);transform:translate(22%,4%)}.is-anime .template-visual::after{width:78px;height:148px;right:22%;bottom:-10px;border-radius:55% 45% 15% 15%;background:#15101e;box-shadow:0 -22px 0 -8px #15101e;transform:skew(-8deg)}.is-anime .visual-word{color:#fff;text-shadow:3px 3px 0 #15101e}.is-cartoon .template-visual{background:radial-gradient(circle at 74% 29%,#ff625c 0 11%,transparent 11.5%),radial-gradient(circle at 63% 62%,#fff2a4 0 16%,transparent 16.5%),#63d7d2}.is-cartoon .template-visual::before{width:105px;height:105px;right:22%;top:29%;border:13px solid #161321;border-radius:46% 54% 51% 49%;transform:rotate(-9deg);box-shadow:80px -82px 0 -26px #ffe264}.is-cartoon .template-visual::after{inset:0;background:radial-gradient(circle,rgba(18,17,26,.12) 1.2px,transparent 1.4px);background-size:12px 12px;mix-blend-mode:multiply}.is-cartoon .visual-word{color:#fff2a4;text-shadow:3px 3px 0 #161321}
  .template-body{display:flex;flex:1;flex-direction:column;padding:22px}.template-kicker{margin:0 0 8px;color:var(--video-lime);font:800 10px/1 ui-monospace,SFMono-Regular,monospace;letter-spacing:.13em;text-transform:uppercase}.template-title-row{display:flex;align-items:center;justify-content:space-between;gap:15px}.template-title-row h2{margin:0;font-size:24px;letter-spacing:-.035em}.template-meta{color:#8d8797;font:700 10px/1 ui-monospace,SFMono-Regular,monospace;white-space:nowrap}.template-description{margin:14px 0 18px;color:var(--video-muted);font-size:13px;line-height:1.6}.template-beats{display:flex;flex-wrap:wrap;gap:6px;margin:auto 0 20px}.template-beats span{padding:6px 8px;border:1px solid rgba(255,255,255,.08);border-radius:3px;color:#aaa5b6;font-size:10px}.template-action{display:flex;align-items:center;justify-content:space-between;width:100%;padding:13px 0 0;border:0;border-top:1px solid var(--video-line);background:transparent;color:#fff;font:900 11px/1 inherit;letter-spacing:.1em;text-decoration:none;text-transform:uppercase}.template-action span:last-child{color:var(--video-lime);font-size:18px;transition:transform .2s ease}.template-action:hover span:last-child{transform:translateX(5px)}
  .video-footer-note{display:grid;grid-template-columns:auto 1fr auto;gap:22px;align-items:center;margin-top:22px;padding:21px 0;border-top:1px solid var(--video-line);color:#77717f;font-size:11px}.video-footer-note b{color:#aaa5b6;letter-spacing:.1em;text-transform:uppercase}.video-footer-note .line{height:1px;background:var(--video-line)}.video-footer-note a{color:var(--video-lime);font-weight:900;text-decoration:none}
  @media(max-width:1050px){.video-hero{grid-template-columns:1fr;gap:30px}.video-intro{max-width:620px}.template-card,.template-card:nth-child(1),.template-card:nth-child(2){grid-column:span 6}.template-card:nth-child(-n+2) .template-visual{min-height:255px}.video-hero::after{opacity:.5}}
  @media(max-width:800px){.video-studio{margin:-18px;padding:28px 18px 55px}.video-topline{margin-bottom:42px}.video-status{display:none}.video-hero h1{font-size:clamp(49px,15vw,76px)}.video-controls{align-items:flex-start;flex-direction:column}.template-card,.template-card:nth-child(1),.template-card:nth-child(2),.template-grid.is-filtered .template-card{grid-column:1/-1;display:flex;min-height:0}.template-grid.is-filtered .template-visual{min-height:255px}.video-footer-note{grid-template-columns:1fr auto}.video-footer-note .line{display:none}}
  @media(prefers-reduced-motion:reduce){.template-card,.template-action span:last-child{transition:none}}
</style>
<div class="video-studio">
  <main class="video-shell">
    <div class="video-topline"><div class="video-brand"><span class="video-brand-mark" aria-hidden="true"></span>Beyond Studio / Motion</div><div class="video-status">Renderer workflow ready</div></div>
    <header class="video-hero">
      <div><p class="video-eyebrow">Remotion template library</p><h1>Stories built <em>in motion.</em></h1></div>
      <p class="video-intro"><strong>Choose a production blueprint</strong>Start with a scene rhythm, canvas, frame rate, and runtime tuned for the kind of story you are making. Then take the brief into the Remotion renderer.</p>
    </header>
    <div class="video-controls">
      <div class="video-filters" role="group" aria-label="Filter video templates">
        <button class="video-filter active" type="button" data-filter="all" aria-pressed="true">All formats</button>
        <button class="video-filter" type="button" data-filter="trailers" aria-pressed="false">Game trailers</button>
        <button class="video-filter" type="button" data-filter="ads" aria-pressed="false">Ads</button>
        <button class="video-filter" type="button" data-filter="films" aria-pressed="false">Short films</button>
        <button class="video-filter" type="button" data-filter="anime" aria-pressed="false">Anime</button>
        <button class="video-filter" type="button" data-filter="cartoons" aria-pressed="false">Cartoons</button>
      </div>
      <div class="video-count" id="videoCount" aria-live="polite">05 blueprints</div>
    </div>
    <section class="template-grid" aria-label="Video creation templates">
      <?php foreach ($templates as $template): ?>
        <article class="template-card <?=DailyStudio::esc($template['class'])?>" data-category="<?=DailyStudio::esc($template['category'])?>">
          <div class="template-visual" aria-hidden="true"><span class="template-index"><?=DailyStudio::esc($template['index'])?> / 05</span><span class="template-format"><?=DailyStudio::esc($template['ratio'])?> · <?=DailyStudio::esc((string)$template['fps'])?> FPS</span><span class="visual-word"><?=DailyStudio::esc($template['kicker'])?></span></div>
          <div class="template-body">
            <p class="template-kicker"><?=DailyStudio::esc($template['tone'])?></p>
            <div class="template-title-row"><h2><?=DailyStudio::esc($template['title'])?></h2><span class="template-meta"><?=DailyStudio::esc((string)$template['duration'])?> SEC</span></div>
            <p class="template-description"><?=DailyStudio::esc($template['description'])?></p>
            <div class="template-beats" aria-label="Scene structure"><?php foreach ($template['beats'] as $beat): ?><span><?=DailyStudio::esc($beat)?></span><?php endforeach; ?></div>
            <a class="template-action" href="remotion-renderer.php?template=<?=rawurlencode($template['id'])?>&amp;ratio=<?=rawurlencode($template['ratio'])?>&amp;fps=<?=rawurlencode((string)$template['fps'])?>&amp;seconds=<?=rawurlencode((string)$template['duration'])?>"><span>Use this blueprint</span><span aria-hidden="true">→</span></a>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
    <div class="video-footer-note"><b>Bring your own project</b><span class="line"></span><a href="remotion-renderer.php">Import a Remotion ZIP →</a></div>
  </main>
</div>
<script>
(() => {
  const filters = [...document.querySelectorAll('[data-filter]')];
  const cards = [...document.querySelectorAll('[data-category]')];
  const grid = document.querySelector('.template-grid');
  const count = document.getElementById('videoCount');
  filters.forEach((button) => button.addEventListener('click', () => {
    const filter = button.dataset.filter;
    grid.classList.toggle('is-filtered', filter !== 'all');
    filters.forEach((item) => {
      const active = item === button;
      item.classList.toggle('active', active);
      item.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    let visible = 0;
    cards.forEach((card) => {
      const show = filter === 'all' || card.dataset.category === filter;
      card.hidden = !show;
      if (show) visible += 1;
    });
    count.textContent = String(visible).padStart(2, '0') + (visible === 1 ? ' blueprint' : ' blueprints');
  }));
})();
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
