<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/classic-schedule.php';
require_once __DIR__ . '/includes/beyond-cartoons-schedule.php';
require_once __DIR__ . '/includes/eight-channel-guide.php';
require_once __DIR__ . '/includes/public-channel-catalog.php';
if (!empty($_SESSION['user_id'])) { beyond_track_app('Beyond TV'); }
$signedIn = !empty($_SESSION['user_id']);
$tvHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$tvBase = str_starts_with($tvHost, 'tv.') ? '/' : '/beyond-tv/';
$channels = json_decode((string)file_get_contents(__DIR__ . '/data/channels.json'), true) ?: [];
$featuredChannels = json_decode((string)@file_get_contents(__DIR__.'/data/featured-channels.json'), true) ?: [];
$demoChannels = beyond_tv_public_channels($channels, $featuredChannels);
$demoChannelCount = count($demoChannels);
$channelPanelArt = [
    'beyond-after-dark' => ['channel-backgrounds-sprite.png', '0% 0%'],
    'beyond-cartoons' => ['channel-backgrounds-sprite-v2.png', '0% 0%'],
    'yugioh-tv' => ['channel-backgrounds-sprite.png', '33.333% 0%'],
    'classic-cinema' => ['channel-backgrounds-sprite.png', '33.333% 100%'],
    'beyond-comedy' => ['channel-backgrounds-sprite-v2.png', '66.666% 0%'],
    'beyond-family' => ['channel-backgrounds-sprite-v2.png', '100% 0%'],
    'classic-cartoon-theater' => ['channel-backgrounds-sprite-v2.png', '33.333% 0%'],
    'bubble-guppies' => ['channel-backgrounds-sprite-v2.png', '100% 100%'],
    'preschool-francais' => ['channel-backgrounds-sprite.png', '66.666% 0%'],
    'space-tv' => ['channel-backgrounds-sprite.png', '100% 0%'],
    'beyond-ancient' => ['channel-backgrounds-sprite.png', '0% 100%'],
    'beyond-french' => ['channel-backgrounds-sprite.png', '66.666% 100%'],
    'beyond-health' => ['channel-backgrounds-sprite.png', '100% 100%'],
    'beyond-trailers' => ['channel-backgrounds-sprite-v2.png', '33.333% 100%'],
    'beyond-sports' => ['channel-backgrounds-sprite-v2.png', '66.666% 100%'],
    'beyond-mystery' => ['channel-backgrounds-sprite-v2.png', '0% 100%'],
];
$classic = null; $cartoons = null; $afterDark = null;
foreach ($channels as $channel) {
    if (($channel['slug'] ?? '') === 'classic-cartoon-theater') $classic = $channel;
    if (($channel['slug'] ?? '') === 'beyond-cartoons') $cartoons = $channel;
    if (($channel['slug'] ?? '') === 'beyond-after-dark') $afterDark = $channel;
}
$classic ??= ['slug'=>'classic-cartoon-theater','name'=>'Beyond Animated Classics','stream_endpoint'=>'/beyond-tv/api/classic-live.php','icon'=>'⚡'];
$cartoons ??= ['slug'=>'beyond-cartoons','name'=>'Beyond Cartoons','icon'=>'📺'];
$classicState = beyond_classic_schedule_state();
$cartoonState = beyond_cartoons_schedule_state();
$slots = range(0, 22, 2);
$guideChannels = beyond_tv_eight_channel_guide($classicState, $cartoonState);
$currentHour = (int)(new DateTimeImmutable('now', new DateTimeZone('America/Vancouver')))->format('G');
$afterDarkGuide = null;
foreach ($guideChannels as $guideChannel) {
    if (($guideChannel['slug'] ?? '') === 'beyond-after-dark') { $afterDarkGuide = $guideChannel; break; }
}
$afterDarkRows = $afterDarkGuide['rows'] ?? [];
$current = beyond_tv_guide_block($afterDarkRows, $currentHour);
$currentIndex = 0;
foreach ($afterDarkRows as $index => $row) {
    if ($currentHour >= (int)($row['start'] ?? 0) && $currentHour < (int)($row['end'] ?? 0)) { $currentIndex = $index; break; }
}
$next = $afterDarkRows ? $afterDarkRows[($currentIndex + 1) % count($afterDarkRows)] : ['title'=>'Next supernatural story'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#401532"><script>(function(){try{const t=localStorage.getItem('beyond-tv-theme');document.documentElement.dataset.tvTheme=['dark','light','sunset'].includes(t)?t:'sunset';}catch(e){document.documentElement.dataset.tvTheme='sunset';}})();</script><title>Beyond TV | <?=$demoChannelCount?> Channels</title><meta name="description" content="Explore all <?=$demoChannelCount?> Beyond TV demo channels across cartoons, movies, sports, trailers, learning, culture, and wellness."><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/beyond-splash.css?v=20260802-1"><script src="/assets/js/beyond-splash.js?v=20260802-1" defer></script><link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.1">
<style>
:root{color-scheme:dark}.btv{min-height:100vh;color:#fff;font-family:Inter,system-ui,sans-serif;background:#070914 url('/beyond-tv/assets/img/beyond-tv-promo.webp') center 90px/cover fixed no-repeat}.btv:before{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(4,6,15,.78) 0%,rgba(4,6,15,.94) 42%,#070914 76%)}.btv-nav{position:sticky;top:0;z-index:60;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px clamp(16px,4vw,52px);background:rgba(5,7,16,.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.1)}.btv-brand{font-size:1.2rem;font-weight:900;letter-spacing:-.04em}.btv-actions{display:flex;gap:9px}.btv-btn{display:inline-flex;align-items:center;justify-content:center;min-height:43px;padding:0 16px;border-radius:12px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.08);color:#fff;font-weight:850;text-decoration:none}.btv-btn.primary{background:linear-gradient(135deg,#7659ef,#b246d8);border-color:transparent}.btv-shell{width:min(1440px,calc(100% - 28px));margin:auto}.hero{padding:26px 0 10px}.hero-title{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:14px}.hero-title h1{margin:4px 0;font-size:clamp(2rem,5vw,4.7rem);letter-spacing:-.065em;line-height:.95}.eyebrow{font-size:.74rem;font-weight:900;letter-spacing:.14em;color:#c6b7ff}.live-chip{display:inline-flex;gap:8px;align-items:center;padding:9px 12px;border-radius:999px;background:rgba(5,7,15,.72);border:1px solid rgba(255,255,255,.14);font-size:.75rem;font-weight:900}.live-chip:before{content:"";width:8px;height:8px;border-radius:50%;background:#ff4343;box-shadow:0 0 0 5px rgba(255,67,67,.16)}.mega-player{position:relative;width:100%;aspect-ratio:16/9;max-height:78vh;background:#000;border-radius:24px;overflow:hidden;border:1px solid rgba(255,255,255,.13);box-shadow:0 30px 90px rgba(0,0,0,.5)}.mega-player video,.mega-player iframe{position:absolute;inset:0;width:100%;height:100%;border:0;object-fit:contain;background:#000}.mega-player .player-loading,.mega-player .player-fallback{position:absolute;inset:0;display:grid;place-items:center;background:#070914;text-align:center;padding:24px}.mega-player .player-fallback[hidden],.mega-player .player-loading[hidden],.mega-player iframe[hidden]{display:none}.unmute-hint{position:absolute;left:14px;bottom:14px;z-index:3;border:0;border-radius:999px;padding:11px 14px;background:rgba(0,0,0,.72);color:#fff;font-weight:850}.now-strip{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,.5fr);gap:14px;margin-top:14px}.now-panel,.next-panel{padding:18px 20px;border-radius:18px;background:rgba(10,12,25,.84);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(16px)}.now-panel small,.next-panel small{display:block;color:#b7a9c9;font-weight:900;letter-spacing:.1em}.now-panel strong,.next-panel strong{display:block;font-size:clamp(1.15rem,2vw,1.55rem);margin:7px 0}.channel-picker{padding:34px 0 18px}.section-head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:14px}.section-head h2{margin:4px 0;font-size:clamp(1.65rem,3vw,2.6rem);letter-spacing:-.045em}.channel-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.channel-card-new{position:relative;display:flex;min-height:210px;align-items:flex-end;padding:24px;border-radius:22px;overflow:hidden;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.14);box-shadow:0 18px 45px rgba(0,0,0,.3);transition:.2s}.channel-card-new:hover{transform:translateY(-4px);border-color:#baa7ff}.channel-card-new:before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 10%,rgba(5,6,14,.88) 88%)}.channel-card-new>*{position:relative}.channel-card-new.one{background:linear-gradient(135deg,#551f68,#15101f)}.channel-card-new.two{background:linear-gradient(135deg,#1d3172,#120c25)}.channel-card-new.three{background:linear-gradient(135deg,#087caa,#f25fab)}.channel-card-new.four{background:linear-gradient(135deg,#08173e,#1768b8)}.channel-card-new.five{background:linear-gradient(135deg,#5b2c08,#d59c31)}.channel-card-new.six{background:linear-gradient(135deg,#2c220c,#a06b1e)}.channel-card-new.seven{background:linear-gradient(135deg,#132b66,#b32956)}.channel-card-new.eight{background:linear-gradient(135deg,#0a3c31,#25a46f)}.channel-num{position:absolute;top:18px;left:18px;padding:7px 10px;border-radius:999px;background:rgba(0,0,0,.58);font-size:.72rem;font-weight:900}.channel-live{position:absolute;top:18px;right:18px;color:#ffb4b4;font-size:.72rem;font-weight:900}.channel-card-new h3{font-size:clamp(1.45rem,3vw,2.2rem);margin:0 0 7px;letter-spacing:-.04em}.channel-card-new p{margin:0;color:#ded8e8}.guide-wrap{padding:34px 0 64px}.epg{overflow-x:auto;border-radius:18px;border:1px solid rgba(255,255,255,.14);background:#0b1020;box-shadow:0 18px 50px rgba(0,0,0,.32)}.epg-grid{display:grid;grid-template-columns:220px repeat(8,minmax(180px,1fr));min-width:1660px}.epg-cell{min-height:96px;padding:14px;border-right:1px solid #2c3550;border-bottom:1px solid #2c3550;background:#111a31}.epg-time{min-height:48px;background:#15213d;font-size:.78rem;font-weight:900;color:#c5cde2;display:flex;align-items:center}.epg-channel{position:sticky;left:0;z-index:3;background:#10182d;display:flex;align-items:center;gap:12px}.epg-channel strong{display:block}.epg-channel small{display:block;color:#9da8c3;margin-top:4px}.epg-program{position:relative}.epg-program strong{display:block;font-size:.95rem}.epg-program small{display:block;color:#aeb8d2;margin-top:7px;line-height:1.35}.epg-program.current{background:#1a2750;box-shadow:inset 0 0 0 3px #b9e300}.epg-program.current:after{content:"NOW";position:absolute;right:9px;top:9px;padding:4px 6px;border-radius:5px;background:#b9e300;color:#111827;font-size:.62rem;font-weight:900}.epg-corner{position:sticky;left:0;z-index:4;background:#0d162b}.footer{padding:26px 0 42px;color:#9da6bd;font-size:.84rem}@media(max-width:1050px){.channel-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.btv-shell{width:min(100% - 20px,1440px)}.hero-title{align-items:start;flex-direction:column}.mega-player{width:calc(100% + 20px);margin-left:-10px;border-radius:0;max-height:none}.now-strip{grid-template-columns:1fr}.channel-cards{grid-template-columns:1fr}.channel-card-new{min-height:175px;padding:19px}.btv-actions .secondary{display:none}.epg-grid{grid-template-columns:150px repeat(8,170px);min-width:1510px}.epg-cell{padding:11px}.epg-channel{gap:7px}.epg-channel span{font-size:1.2rem}}

.btv-theme-toggle{display:inline-flex;align-items:center;gap:7px;min-height:43px;padding:0 14px;border-radius:12px;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.09);color:inherit;font-weight:900;cursor:pointer}.btv-theme-toggle:hover,.btv-theme-toggle:focus-visible{border-color:#baa7ff;background:rgba(118,89,239,.22)}
html[data-tv-theme="light"]{color-scheme:light}html[data-tv-theme="light"] .btv{color:#171a2e;background:#eef2fb}html[data-tv-theme="light"] .btv:before{background:linear-gradient(180deg,rgba(246,248,255,.74),rgba(238,242,251,.94) 42%,#eef2fb 76%)}html[data-tv-theme="light"] .btv-nav{background:rgba(255,255,255,.9);border-color:rgba(20,28,55,.12)}html[data-tv-theme="light"] .btv-brand,html[data-tv-theme="light"] .btv-btn,html[data-tv-theme="light"] .btv-theme-toggle{color:#171a2e}html[data-tv-theme="light"] .btv-btn,html[data-tv-theme="light"] .btv-theme-toggle{background:rgba(255,255,255,.86);border-color:rgba(20,28,55,.16)}html[data-tv-theme="light"] .btv-btn.primary{color:#fff;background:linear-gradient(135deg,#7659ef,#b246d8)}html[data-tv-theme="light"] .live-chip,html[data-tv-theme="light"] .now-panel,html[data-tv-theme="light"] .next-panel{background:rgba(255,255,255,.88);border-color:rgba(20,28,55,.14);color:#171a2e}html[data-tv-theme="light"] .now-panel small,html[data-tv-theme="light"] .next-panel small,html[data-tv-theme="light"] .channel-card-new p{color:#5f667a}html[data-tv-theme="light"] .epg{background:#fff;border-color:rgba(20,28,55,.14);box-shadow:0 18px 45px rgba(40,48,80,.12)}html[data-tv-theme="light"] .epg-cell{background:#f8f9fd;border-color:#dfe3ef;color:#171a2e}html[data-tv-theme="light"] .epg-time{background:#edf1f9;color:#4c556c}html[data-tv-theme="light"] .epg-channel,html[data-tv-theme="light"] .epg-corner{background:#f1f4fa}html[data-tv-theme="light"] .epg-program small,html[data-tv-theme="light"] .epg-channel small,html[data-tv-theme="light"] .footer{color:#657087}html[data-tv-theme="light"] .epg-program.current{background:#e8edff}html[data-tv-theme="light"] .mega-player{border-color:rgba(20,28,55,.18);box-shadow:0 25px 65px rgba(26,34,65,.18)}@media(max-width:760px){.btv-theme-toggle span{display:none}}



html[data-tv-theme="sunset"]{color-scheme:dark}html[data-tv-theme="sunset"] .btv,html[data-tv-theme="sunset"] .classic-home,html[data-tv-theme="sunset"] body.tv-app{color:#fff7f2;background:radial-gradient(circle at 50% -10%,#7b2d58 0,#32133f 34%,#151326 72%)}html[data-tv-theme="sunset"] .btv:before{background:linear-gradient(180deg,rgba(78,25,70,.38),rgba(28,15,42,.88) 45%,#111325 80%)}html[data-tv-theme="sunset"] .btv-nav,html[data-tv-theme="sunset"] .classic-nav{background:rgba(40,17,43,.90);border-color:rgba(255,198,166,.18)}html[data-tv-theme="sunset"] .btv-btn,html[data-tv-theme="sunset"] .btv-theme-toggle,html[data-tv-theme="sunset"] .classic-btn,html[data-tv-theme="sunset"] .channel-theme-toggle{background:rgba(106,43,76,.45);border-color:rgba(255,205,176,.25);color:#fff7f2}html[data-tv-theme="sunset"] .now-panel,html[data-tv-theme="sunset"] .next-panel,html[data-tv-theme="sunset"] .live-chip,html[data-tv-theme="sunset"] .channel-switch,html[data-tv-theme="sunset"] .classic-info,html[data-tv-theme="sunset"] .guide-block,html[data-tv-theme="sunset"] .channel-card,html[data-tv-theme="sunset"] .release-note,html[data-tv-theme="sunset"] .channel-detail,html[data-tv-theme="sunset"] .schedule-mini>div{background:rgba(54,23,52,.90)!important;border-color:rgba(255,195,160,.20)!important;color:#fff7f2}html[data-tv-theme="sunset"] .epg{background:#2a1733;border-color:rgba(255,194,158,.20)}html[data-tv-theme="sunset"] .epg-cell{background:#321b3e;border-color:#5d3556;color:#fff7f2}html[data-tv-theme="sunset"] .epg-time,html[data-tv-theme="sunset"] .epg-channel,html[data-tv-theme="sunset"] .epg-corner{background:#402047;color:#ffd9c6}html[data-tv-theme="sunset"] .epg-program.current{background:#6e345e;box-shadow:inset 0 0 0 3px #ffb36b}html[data-tv-theme="sunset"] .channel-detail p,html[data-tv-theme="sunset"] .schedule-mini small,html[data-tv-theme="sunset"] .provider-status,html[data-tv-theme="sunset"] .clock-line{color:#e5bdb5}
.channel-featured-9{background:linear-gradient(135deg,#67295f,#c14c77)}.channel-featured-10{background:linear-gradient(135deg,#172d49,#47799b)}.channel-featured-11{background:linear-gradient(135deg,#171522,#563960)}.channel-featured-12{background:linear-gradient(135deg,#514019,#c08a35)}.channel-featured-13{background:linear-gradient(135deg,#123f2f,#26a269)}.channel-featured-14{background:linear-gradient(135deg,#32104f,#a21caf 52%,#f97316)}.channel-featured-15{background:linear-gradient(135deg,#071b3d,#0757a6 50%,#16a3d6)}
/* Twelve two-hour columns; the first channel column remains sticky. */
.epg-grid{grid-template-columns:220px repeat(12,minmax(180px,1fr));min-width:2380px}
@media(max-width:760px){.epg-grid{grid-template-columns:150px repeat(12,180px);min-width:2310px}}

/* Beyond TV 3.0 — cinematic Sunset-first home */
.btv{background-color:#160f23;background-image:radial-gradient(circle at 82% 2%,rgba(242,120,98,.22),transparent 28%),radial-gradient(circle at 8% 28%,rgba(161,58,126,.24),transparent 32%),url('/beyond-tv/assets/img/beyond-tv-promo.webp');background-blend-mode:screen,screen,normal}
.btv:before{background:linear-gradient(180deg,rgba(28,10,30,.62),rgba(24,12,33,.9) 38%,#120f20 76%)}
.btv-nav{min-height:72px;padding:10px clamp(16px,4vw,54px);background:rgba(35,14,35,.76);border-color:rgba(255,211,185,.15)}
.btv-brand{display:flex;align-items:center;gap:11px;color:#fff;text-decoration:none}.btv-brand img{width:44px;height:44px;border-radius:13px;object-fit:cover;box-shadow:0 8px 24px rgba(0,0,0,.3)}.btv-brand em{color:#ffb187;font-style:normal}
.btv-primary-nav{position:absolute;left:50%;display:flex;gap:7px;transform:translateX(-50%)}.btv-primary-nav a{padding:10px 15px;border-radius:999px;color:#d9c7d7;font-size:.86rem;font-weight:800;text-decoration:none}.btv-primary-nav a:hover,.btv-primary-nav a[aria-current="page"]{background:rgba(255,255,255,.1);color:#fff}
.btv-btn,.btv-theme-toggle{border-radius:999px}.btv-btn.primary{background:linear-gradient(120deg,#f06f74,#a344aa 60%,#6e54d9);box-shadow:0 10px 32px rgba(177,57,128,.28)}.btv-home-btn{display:none}
.hero{padding-top:44px}.hero-title{align-items:center;margin-bottom:20px}.hero-title>div{max-width:820px}.hero-title p{max-width:660px;margin:10px 0 0;color:#d8c6d5;line-height:1.6}.hero-title h1{font-size:clamp(2.8rem,6vw,5.6rem);background:linear-gradient(110deg,#fff 12%,#ffd1ba 55%,#db9ce4);-webkit-background-clip:text;color:transparent}
.mega-player{border-radius:28px;border-color:rgba(255,215,194,.2);box-shadow:0 38px 110px rgba(7,4,15,.68),0 0 0 1px rgba(255,255,255,.05)}
.now-panel,.next-panel{background:linear-gradient(145deg,rgba(70,28,63,.84),rgba(31,20,45,.9));border-color:rgba(255,204,177,.16);box-shadow:0 16px 44px rgba(8,5,16,.2)}
.channel-picker{padding-top:64px}.section-head{margin-bottom:22px}.section-head h2{font-size:clamp(2rem,4vw,3.4rem)}
.channel-cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.channel-card-new{min-height:280px;padding:24px;border-radius:24px;border-color:rgba(255,217,195,.15);box-shadow:0 20px 55px rgba(7,4,14,.3)}.channel-card-new:first-child{grid-column:span 2}.channel-card-new:before{background:linear-gradient(180deg,rgba(15,7,20,.03) 18%,rgba(15,7,20,.94) 96%)}.channel-card-new:hover{transform:translateY(-6px) scale(1.01);border-color:rgba(255,187,153,.58);box-shadow:0 28px 70px rgba(7,4,14,.48)}.channel-card-new small{display:block;margin-bottom:8px;color:#f0af91;font-size:.68rem;font-weight:900;letter-spacing:.12em}.channel-card-new h3{font-size:clamp(1.4rem,2.3vw,2rem)}.channel-card-new p{display:-webkit-box;max-width:520px;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;color:#dacfd9;font-size:.88rem;line-height:1.5}.channel-num,.channel-live{top:16px}.channel-num{left:16px;background:rgba(14,8,19,.74);border:1px solid rgba(255,255,255,.12)}.channel-live{right:16px;padding:7px 10px;border-radius:999px;background:rgba(14,8,19,.68);color:#ffb0ab}
.guide-wrap{padding-top:70px}.epg{border-radius:24px;border-color:rgba(255,206,178,.18);background:#25152d}.epg-cell{border-color:#5b3452;background:#321d3d}.epg-time{background:#492544;color:#ffd1bd}.epg-channel,.epg-corner{background:#3c203c}.epg-program.current{background:#713b61;box-shadow:inset 0 0 0 3px #ffb177}.epg-program.current:after{background:#ffb177}
.on-now-guide{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.on-now-card{position:relative;min-width:0;min-height:154px;overflow:hidden;padding:18px;border:1px solid rgba(255,213,190,.16);border-radius:20px;background:linear-gradient(145deg,rgba(69,30,62,.9),rgba(25,18,39,.94));color:#fff;text-decoration:none;box-shadow:0 15px 38px rgba(8,4,15,.22);transition:.22s}.on-now-card:hover,.on-now-card:focus-visible{transform:translateY(-4px);border-color:rgba(255,184,151,.62);box-shadow:0 22px 50px rgba(8,4,15,.38)}.on-now-channel{display:flex;align-items:center;justify-content:space-between;gap:10px;color:#efb59d;font-size:.68rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase}.on-now-channel i{width:7px;height:7px;flex:0 0 7px;border-radius:50%;background:#ff4f67;box-shadow:0 0 0 4px rgba(255,79,103,.14)}.on-now-card strong{display:block;margin:22px 0 7px;overflow:hidden;font-size:1rem;text-overflow:ellipsis;white-space:nowrap}.on-now-card small{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;color:#cdbfcb;line-height:1.45}.on-now-card b{display:block;margin-top:13px;color:#ffb796;font-size:.7rem}
.footer{display:flex;align-items:center;justify-content:space-between;gap:20px;border-top:1px solid rgba(255,208,183,.14)}.footer strong{color:#fff}.footer a{color:#ffc1a2;font-weight:800}
html[data-tv-theme="dark"] .btv{background:#080812 url('/beyond-tv/assets/img/beyond-tv-promo.webp') center 90px/cover fixed no-repeat}html[data-tv-theme="dark"] .btv:before{background:linear-gradient(180deg,rgba(4,6,15,.78),rgba(4,6,15,.94) 42%,#070914 76%)}html[data-tv-theme="dark"] .btv-nav{background:rgba(5,7,16,.88)}
@media(max-width:1050px){.channel-cards,.on-now-guide{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.btv-nav{min-height:62px}.btv-brand span{font-size:1rem}.btv-brand img{width:39px;height:39px}.btv-primary-nav{display:none}.btv-home-btn{display:inline-flex}.btv-btn{min-height:40px;padding:0 13px}.hero{padding-top:28px}.hero-title{align-items:flex-start}.hero-title h1{font-size:clamp(2.4rem,13vw,4rem)}.hero-title p{font-size:.92rem}.mega-player{border-radius:0}.channel-picker{padding-top:42px}.channel-cards,.on-now-guide{grid-template-columns:1fr}.channel-card-new:first-child{grid-column:auto}.channel-card-new{min-height:230px}.footer{align-items:flex-start;flex-direction:column}}
</style></head><body class="btv">
<header class="btv-nav"><a class="btv-brand" href="/beyond-tv/"><img src="/beyond-tv/assets/img/beyond-tv-logo.webp" alt=""><span>BEYOND <em>TV</em></span></a><nav class="btv-primary-nav" aria-label="Beyond TV"><a href="/">Home</a><a href="/beyond-tv/" aria-current="page">Watch</a><a href="/beyond-tv/live-tv.php">Guide</a><a href="/beyond-tv/browse.php">Browse</a></nav><nav class="btv-actions"><a class="btv-btn btv-home-btn" href="/">Home</a><button class="btv-theme-toggle" type="button" data-tv-theme-toggle aria-label="Change theme">🌅</button><?php if($signedIn):?><a class="btv-btn primary" href="/dashboard/">Beyond ID</a><?php else:?><a class="btv-btn primary" href="/beyond-id/auth/login.php?return=/beyond-tv/">Sign in</a><?php endif;?></nav></header>
<main>
<section class="hero btv-shell"><div class="hero-title"><div><span class="eyebrow"><?=$demoChannelCount?> CHANNELS · FREE TO WATCH · NO ACCOUNT REQUIRED</span><h1>Beyond After Dark</h1><p>Supernatural stories, cult animation, and late-night mystery—streaming now on channel 01.</p></div><span class="live-chip">LIVE NOW</span></div>
<div class="mega-player provider-player"><iframe src="<?=htmlspecialchars($tvBase)?>embed-player.php?slug=beyond-after-dark" title="Beyond After Dark live on Beyond TV" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
<div class="now-strip"><div class="now-panel"><small>NOW PLAYING</small><strong data-guide-current><?=htmlspecialchars((string)(($current['icon'] ?? '🌙').' '.($current['title'] ?? 'Beyond After Dark')))?></strong><span><?=htmlspecialchars((string)($current['lineup'] ?? 'Supernatural stories and late-night mysteries'))?></span></div><div class="next-panel"><small>UP NEXT</small><strong data-guide-next><?=htmlspecialchars((string)($next['title'] ?? 'Next supernatural story'))?></strong><span data-guide-clock><?=htmlspecialchars((string)$classicState['time_label'])?></span> · Vancouver</div></div></section>
<section class="channel-picker btv-shell"><div class="section-head"><div><span class="eyebrow">COMPLETE DEMO LINEUP</span><h2>All <?=$demoChannelCount?> channels across the ecosystem</h2></div></div><div class="channel-cards">
<?php foreach($demoChannels as $demoChannel):
    $channelStatus = !empty($demoChannel['live']) ? '● LIVE' : (($demoChannel['release_status'] ?? '') === 'development' ? 'PREVIEW' : 'ON DEMAND');
    $channelSlug = (string)($demoChannel['slug'] ?? '');
    $panelArt = $channelPanelArt[$channelSlug] ?? ['channel-backgrounds-sprite-v2.png', '0% 0%'];
    $channelBackground = 'background-image:linear-gradient(180deg,rgba(15,7,20,.02),rgba(15,7,20,.94)),url("/beyond-tv/assets/img/'
        . rawurlencode($panelArt[0]) . '");background-position:center,' . $panelArt[1]
        . ';background-size:cover,400% 200%;background-repeat:no-repeat';
?>
<a class="channel-card-new" style="<?=htmlspecialchars($channelBackground)?>" href="/beyond-tv/channel.php?slug=<?=urlencode($channelSlug)?>"><span class="channel-num">CH <?=str_pad((string)$demoChannel['display_number'],2,'0',STR_PAD_LEFT)?></span><span class="channel-live"><?=htmlspecialchars($channelStatus)?></span><div><small><?=htmlspecialchars(strtoupper((string)($demoChannel['category'] ?? 'ENTERTAINMENT')))?></small><h3><?=htmlspecialchars((string)($demoChannel['icon'] ?? '📺').' '.($demoChannel['name'] ?? 'Beyond TV'))?></h3><p><?=htmlspecialchars((string)($demoChannel['description'] ?? $demoChannel['now'] ?? 'Explore this Beyond TV channel.'))?></p></div></a>
<?php endforeach; ?>
</div></section>
<section class="guide-wrap btv-shell" id="guide"><div class="section-head"><div><span class="eyebrow">ON NOW · <?=$demoChannelCount?> FREE CHANNELS</span><h2>Currently playing on Beyond TV</h2></div><a class="btv-btn" href="/beyond-tv/live-tv.php">Open full guide →</a></div><div class="on-now-guide" aria-label="Currently playing Beyond TV channels">
<?php foreach($guideChannels as $number=>$guideChannel):
    $currentGuideIndex = 0;
    foreach($guideChannel['rows'] as $guideIndex=>$guideRow) {
        if ($currentHour >= (int)($guideRow['start'] ?? 0) && $currentHour < (int)($guideRow['end'] ?? 0)) { $currentGuideIndex = (int)$guideIndex; break; }
    }
    $currentGuideBlock = $guideChannel['rows'][$currentGuideIndex] ?? beyond_tv_guide_block($guideChannel['rows'], $currentHour);
    $nextGuideBlock = $guideChannel['rows'] ? $guideChannel['rows'][($currentGuideIndex + 1) % count($guideChannel['rows'])] : [];
?>
<a class="on-now-card" href="/beyond-tv/channel.php?slug=<?=urlencode((string)$guideChannel['slug'])?>"><span class="on-now-channel"><span>CH <?=str_pad((string)($number+1),2,'0',STR_PAD_LEFT)?> · <?=htmlspecialchars((string)$guideChannel['name'])?></span><i aria-hidden="true"></i></span><strong><?=htmlspecialchars((string)(($currentGuideBlock['icon'] ?? '▶').' '.($currentGuideBlock['title'] ?? 'Live now')))?></strong><small><?=htmlspecialchars((string)($currentGuideBlock['lineup'] ?? 'Curated live library'))?></small><b>Up next: <?=htmlspecialchars((string)($nextGuideBlock['title'] ?? 'More Beyond TV'))?> →</b></a>
<?php endforeach; ?>
</div></section>
</main><footer class="footer btv-shell"><strong>BEYOND TV</strong><span><?=$demoChannelCount?> curated channels · Schedule synchronized to America/Vancouver</span><a href="/beyond-tv/browse.php">Explore the library →</a></footer><script src="/beyond-tv/assets/js/app.js?v=3.0.1"></script><script>
(function(){
  const currentElement=document.querySelector('[data-guide-current]');
  const nextElement=document.querySelector('[data-guide-next]');
  const clockElement=document.querySelector('[data-guide-clock]');
  if(!currentElement||!nextElement||!clockElement)return;

  const timeFormatter=new Intl.DateTimeFormat('en-CA',{
    timeZone:'America/Vancouver',
    hour:'numeric',
    minute:'2-digit'
  });

  async function refresh(){
    try{
      const response=await fetch('/beyond-tv/api/channel-stream.php?slug=beyond-after-dark',{cache:'no-store'});
      if(!response.ok)throw new Error('HTTP '+response.status);
      const payload=await response.json();
      if(!payload?.ok)return;
      const state=payload.state||{};
      if(state.current?.title)currentElement.textContent=state.current.title;
      if(state.next?.title)nextElement.textContent=state.next.title;
      if(payload.server_time)clockElement.textContent=timeFormatter.format(new Date(Number(payload.server_time)*1000));
    }catch(error){
      console.warn('Beyond TV hero metadata refresh unavailable',error);
    }
  }

  refresh();
  window.setInterval(refresh,30000);
})();
</script><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
