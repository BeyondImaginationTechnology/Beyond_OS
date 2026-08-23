<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ecosystem.php';
require_once __DIR__ . '/beyond-tv/includes/classic-schedule.php';
require_once __DIR__ . '/beyond-tv/includes/beyond-cartoons-schedule.php';
require_once __DIR__ . '/beyond-tv/includes/public-channel-catalog.php';
beyond_nav_bootstrap('Beyond OS');
$signedIn = isset($_SESSION['user_id']);

$homeVerse = [
    'text' => 'Be still, and know that I am God.',
    'reference' => 'Psalm 46:10',
    'book' => 'Psalms',
    'chapter' => 46,
    'verse' => 10,
];
try {
    require_once __DIR__ . '/dailybreath/includes/verse-of-day.php';
    $homeVerse = dailybreath_verse_of_day(beyond_db(), (string)($_SESSION['locale'] ?? 'en'));
} catch (Throwable $exception) {
    // The bundled verse remains available if a production database is temporarily unavailable.
}
$homeBibleUrl = '/dailybreath/bible.php?book=' . rawurlencode((string)($homeVerse['book'] ?? 'Psalms'))
    . '&chapter=' . max(1, (int)($homeVerse['chapter'] ?? 46))
    . '#verse-' . max(1, (int)($homeVerse['verse'] ?? 1));

$homeFrench = [
    'id' => 1,
    'english' => 'Keep going.',
    'french' => 'Continue.',
    'french_pronunciation' => 'Kohn-tee-new',
];
$frenchLessonsPath = __DIR__ . '/beyond-french/data/lessons.json';
if (is_file($frenchLessonsPath)) {
    $decodedLessons = json_decode((string)file_get_contents($frenchLessonsPath), true);
    if (is_array($decodedLessons) && $decodedLessons !== []) {
        $today = date('Y-m-d');
        $selectedLesson = null;
        foreach ($decodedLessons as $lesson) {
            if (is_array($lesson) && ($lesson['date'] ?? '') === $today) {
                $selectedLesson = $lesson;
                break;
            }
        }
        if ($selectedLesson === null) {
            $lessonIndex = (int)(abs(crc32($today)) % count($decodedLessons));
            $selectedLesson = is_array($decodedLessons[$lessonIndex] ?? null) ? $decodedLessons[$lessonIndex] : null;
        }
        if ($selectedLesson !== null) {
            $homeFrench = array_merge($homeFrench, $selectedLesson);
        }
    }
}

$homeMarketListing = [
    'id' => 0,
    'title' => 'Custom Coffee Mugs',
    'item_type' => 'physical',
    'listing_type' => 'buy_now',
    'price_cash' => 16,
    'price_bits' => null,
    'currency' => 'CAD',
];
$homeMarketListings = [];
try {
    $homeMarketListings = beyond_db()->query(
        "SELECT id,title,item_type,listing_type,price_cash,price_bits,currency
         FROM listings
         WHERE status='active'
         ORDER BY created_at DESC
         LIMIT 8"
    )->fetchAll();
    if (is_array($homeMarketListings[0] ?? null)) {
        $homeMarketListing = array_merge($homeMarketListing, $homeMarketListings[0]);
    }
} catch (Throwable $exception) {
    $homeMarketListings = [];
    // The Canvas product fallback keeps this live-demo card useful before the first seller listing.
}
$homeMarketPrice = '';
if ($homeMarketListing['price_bits'] !== null) {
    $homeMarketPrice = number_format((int)$homeMarketListing['price_bits']) . ' bit$';
}
if ((float)$homeMarketListing['price_cash'] > 0) {
    $cashPrice = '$' . number_format((float)$homeMarketListing['price_cash'], 2) . ' ' . (string)$homeMarketListing['currency'];
    $homeMarketPrice .= $homeMarketPrice !== '' ? ' or ' . $cashPrice : $cashPrice;
}
$homeMarketUrl = (int)$homeMarketListing['id'] > 0
    ? '/beyond-sell/listing.php?id=' . (int)$homeMarketListing['id']
    : '/beyond-market/#shop';
$homeMarketFeedIsPublished = $homeMarketListings !== [];
$homeMarketFeed = $homeMarketFeedIsPublished
    ? $homeMarketListings
    : require __DIR__ . '/beyond-market/data/redbubble-featured.php';
$homeGameDemos = [];
try {
    $homeGamesJson = file_get_contents(__DIR__ . '/beyond-games/data/games.json');
    $homeGamesCatalog = json_decode((string)$homeGamesJson, true, 512, JSON_THROW_ON_ERROR);
    foreach ($homeGamesCatalog as $homeGame) {
        if (!is_array($homeGame) || empty($homeGame['playable']) || empty($homeGame['play_url'])) continue;
        $playUrl = (string)$homeGame['play_url'];
        if (!str_starts_with($playUrl, '/')) $playUrl = '/beyond-games/' . ltrim($playUrl, '/');
        $homeGame['play_url'] = $playUrl;
        $homeGameDemos[] = $homeGame;
    }
} catch (Throwable $exception) {
    $homeGameDemos = [];
}
?>
<!doctype html>
<html lang="en" data-default-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<script>(function(){try{const t=localStorage.getItem('beyond-theme');document.documentElement.dataset.theme=['dark','light','sunset','ocean','forest'].includes(t)?t:'light';}catch(e){document.documentElement.dataset.theme='light';}try{const c=localStorage.getItem('beyond-currency');document.documentElement.dataset.currency=['USD','CAD','BITS'].includes(c)?c:'CAD';}catch(e){document.documentElement.dataset.currency='CAD';}})();</script>
<meta name="theme-color" content="#f4f6fc">
<title>Beyond OS 2.4 | Live. Learn. Earn. Explore.</title>
<meta name="description" content="Health, education, wallet and entertainment connected through one secure Beyond ID and one shared bit$ balance.">
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<link rel="stylesheet" href="/assets/css/beyond-splash.css?v=20260802-1">
<script src="/assets/js/beyond-splash.js?v=20260818-2" defer></script>
<style>
:root{--bg:#030611;--panel:#09101f;--line:rgba(255,255,255,.13);--text:#f7f8ff;--muted:#b8bed2;--pink:#f2469d;--violet:#7057ff;--green:#51db78;--gold:#ffbf32;--blue:#448cff}
*{box-sizing:border-box}html{scroll-behavior:smooth;background:var(--bg)}body{margin:0;color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 75% 10%,rgba(73,54,204,.18),transparent 28%),linear-gradient(180deg,#050817,#02040d 72%);overflow-x:hidden}a{color:inherit}.wrap{width:min(1180px,calc(100% - 32px));margin-inline:auto}.top{min-height:84px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{text-decoration:none;font-weight:1000;font-size:23px;letter-spacing:-.045em}.brand span{background:linear-gradient(100deg,#7667ff,#ec4caa);background-clip:text;color:transparent}.brand small{display:block;margin-top:5px;font-size:9px;letter-spacing:.13em;color:#c7c9d5;font-weight:700}.nav{display:flex;align-items:center;gap:29px}.nav>a:not(.primary){text-decoration:none;font-size:13px;color:#f6f7ff;padding:12px 0;border-bottom:2px solid transparent}.nav a[href="#health"]{border-color:var(--green)}.nav a[href="#education"]{border-color:var(--gold)}.nav a[href="#finance"]{border-color:var(--blue)}.primary{display:inline-flex;align-items:center;justify-content:center;min-height:47px;padding:0 23px;border-radius:9px;text-decoration:none;font-weight:850;font-size:13px;background:linear-gradient(100deg,#586cff,#ef4897);box-shadow:0 12px 34px rgba(106,74,255,.28)}.menu{display:none;background:none;border:0;color:#fff;font-size:29px}.hero{min-height:600px;display:grid;grid-template-columns:.88fr 1.12fr;align-items:center;gap:45px;padding:46px 0 55px}.hero h1{font-size:clamp(58px,7.3vw,102px);line-height:.83;letter-spacing:-.075em;margin:0 0 26px}.hero h1 span{display:block}.hero .h{color:var(--green)}.hero .e{color:var(--gold)}.hero .f{color:var(--blue)}.hero .x{color:var(--pink)}.tagline{font-size:25px;font-weight:850;margin:0 0 20px}.intro{max-width:430px;color:var(--muted);line-height:1.65;font-size:16px}.hero-actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:30px}.ghost{display:inline-flex;align-items:center;justify-content:center;min-height:50px;padding:0 23px;border:1px solid rgba(255,255,255,.24);border-radius:12px;text-decoration:none;font-weight:800;background:rgba(255,255,255,.025)}.benefits{display:flex;gap:28px;flex-wrap:wrap;margin-top:26px;color:#c9cedd;font-size:12px}.benefits span{display:flex;align-items:center;gap:8px}.benefits b{font-size:16px;color:#fff}.orbit{position:relative;aspect-ratio:1.18/1;display:grid;place-items:center;isolation:isolate}.orbit:before{content:"";position:absolute;inset:5%;background:radial-gradient(circle,rgba(99,78,255,.22),transparent 50%);filter:blur(15px);z-index:-1}.ring{position:absolute;border:1px solid rgba(117,158,255,.42);border-radius:50%;width:70%;aspect-ratio:1}.ring.r2{width:91%;border-color:rgba(255,188,86,.34)}.core{width:170px;aspect-ratio:1;border-radius:50%;display:grid;place-items:center;font-size:74px;font-weight:1000;background:radial-gradient(circle at 40% 35%,#251b55,#090919 58%);border:2px solid #9a52ff;box-shadow:0 0 0 12px rgba(92,88,255,.09),0 0 55px #6c51ff88,inset 0 0 45px #2f225f}.core span{background:linear-gradient(145deg,#516fff,#e745a3);background-clip:text;color:transparent}.planet{position:absolute;width:112px;aspect-ratio:1;border-radius:50%;display:grid;place-items:center;text-align:center;font-weight:900;font-size:12px;border:1px solid currentColor;background:rgba(6,11,25,.9);box-shadow:0 0 33px currentColor}.planet i{font-style:normal;font-size:38px;display:block;line-height:1.1}.ph{left:13%;top:14%;color:var(--green)}.pe{right:8%;top:18%;color:var(--gold)}.pf{bottom:3%;left:24%;color:var(--blue)}.px{bottom:3%;right:8%;color:var(--pink)}.welcome{margin-bottom:18px;padding:20px 26px;border:1px solid rgba(255,255,255,.12);border-radius:17px;background:linear-gradient(100deg,rgba(82,52,217,.9),rgba(201,36,125,.86));display:flex;align-items:center;justify-content:space-between;gap:18px}.welcome-copy{display:flex;align-items:center;gap:17px}.gift{font-size:38px}.welcome strong{display:block;font-size:16px}.welcome p{margin:5px 0 0;color:#e6def4;font-size:13px}.welcome .primary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.22);box-shadow:none}.world{--accent:var(--green);position:relative;margin:0 auto 18px;min-height:380px;border:1px solid color-mix(in srgb,var(--accent) 55%,transparent);border-radius:20px;overflow:hidden;background:#08121b}.world:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 72% 40%,color-mix(in srgb,var(--accent) 22%,transparent),transparent 38%),linear-gradient(90deg,rgba(2,7,14,.97) 0%,rgba(3,8,17,.78) 38%,rgba(3,8,17,.18) 75%,rgba(3,8,17,.65) 100%)}.world.health{--accent:var(--green);background:radial-gradient(ellipse at 72% 26%,#2bb6a055 0,transparent 28%),linear-gradient(130deg,#06170f,#052b30 55%,#06141d)}.world.education{--accent:var(--gold);background:radial-gradient(circle at 62% 32%,#7f3ad466 0,transparent 30%),linear-gradient(130deg,#1b0f08,#1d1030 56%,#2c1608)}.world.finance{--accent:var(--blue);background:radial-gradient(ellipse at 75% 30%,#2f62c555 0,transparent 34%),linear-gradient(130deg,#061328,#081d45 58%,#190c37)}.world-inner{position:relative;z-index:1;min-height:380px;padding:31px 31px 26px;display:grid;grid-template-columns:320px 1fr;align-items:end;gap:26px}.world-copy{align-self:start}.world-title{display:flex;align-items:center;gap:15px;color:var(--accent)}.world-icon{width:56px;height:56px;border-radius:15px;display:grid;place-items:center;font-size:28px;background:color-mix(in srgb,var(--accent) 18%,rgba(255,255,255,.04));border:1px solid color-mix(in srgb,var(--accent) 48%,transparent)}.world h2{font-size:32px;margin:0;letter-spacing:-.035em}.world h3{font-size:17px;margin:22px 0 9px}.world p{max-width:290px;color:#c0c6d4;line-height:1.5;font-size:14px}.explore{display:inline-flex;margin-top:8px;min-height:42px;padding:0 16px;border:1px solid color-mix(in srgb,var(--accent) 72%,transparent);border-radius:12px;align-items:center;text-decoration:none;color:var(--accent);font-weight:850;font-size:13px}.apps{display:grid;grid-template-columns:repeat(auto-fit,minmax(92px,1fr));gap:10px;align-self:end}.app{position:relative;min-height:104px;border:1px solid rgba(255,255,255,.14);border-radius:14px;background:rgba(5,10,22,.75);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:9px;text-align:center;text-decoration:none;font-size:11px;font-weight:780;padding:8px;backdrop-filter:blur(10px);transition:.2s}.app:hover{transform:translateY(-4px);border-color:var(--accent)}.app b{font-size:25px;color:var(--accent)}.app-icon{width:48px;height:48px;border-radius:13px;object-fit:cover;border:1px solid rgba(255,255,255,.18);box-shadow:0 8px 22px rgba(0,0,0,.34)}.soon{position:absolute;right:6px;top:-8px;padding:3px 6px;border-radius:8px;background:#dedfe8;color:#202333;font-size:8px}.all{border-style:dashed}.identity{margin:18px auto 0;padding:24px 30px;border:1px solid rgba(207,107,255,.32);border-radius:18px;background:linear-gradient(100deg,rgba(61,30,151,.76),rgba(192,35,123,.72));display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:24px}.shield{width:74px;height:74px;border-radius:22px;display:grid;place-items:center;font-size:38px;overflow:hidden;background:linear-gradient(145deg,#6a57ff,#ed47a2);box-shadow:0 0 35px #8a50ff66}.shield img{width:100%;height:100%;object-fit:cover}.identity h2{font-size:24px;line-height:1.05;margin:0 0 8px}.identity p{margin:0;color:#d7d2e6;font-size:13px;line-height:1.5}.id-action{text-align:center}.id-action small{display:block;margin-top:10px;color:#ece8f4}.footer{margin-top:25px;padding:36px 0 50px;border-top:1px solid rgba(255,255,255,.09);display:grid;grid-template-columns:1.4fr repeat(4,1fr);gap:28px;color:#8f96aa;font-size:12px}.footer h4{margin:0 0 12px;color:#d7dbe8;font-size:11px;letter-spacing:.08em}.footer a{display:block;text-decoration:none;margin:7px 0}.footer .brand{color:#fff;font-size:18px}.copyright{margin-top:15px}.mobile-links{display:none}
@media(max-width:850px){.nav>a:not(.primary){display:none}.hero{grid-template-columns:1fr;padding-top:34px}.orbit{max-width:590px;width:100%;margin:auto}.hero h1{font-size:clamp(58px,15vw,88px)}.world-inner{grid-template-columns:1fr;padding:25px 20px}.apps{grid-template-columns:repeat(3,1fr)}.identity{grid-template-columns:auto 1fr}.id-action{grid-column:1/-1}.id-action .primary{width:100%}.footer{grid-template-columns:1fr 1fr 1fr}.footer>div:first-child{grid-column:1/-1}}@media(max-width:560px){.wrap{width:min(100% - 22px,1180px)}.top{min-height:72px}.brand{font-size:19px}.brand small{display:none}.nav .primary{padding:0 14px;min-height:42px}.hero{gap:20px;min-height:auto;padding:35px 0}.tagline{font-size:21px}.intro{font-size:15px}.hero-actions>*{width:100%}.benefits{gap:12px 18px}.orbit{aspect-ratio:1;transform:scale(.96)}.core{width:116px;font-size:50px}.planet{width:78px;font-size:9px}.planet i{font-size:27px}.ph{left:3%}.pe{right:1%}.pf{bottom:0;left:10%}.px{bottom:0;right:1%}.welcome{padding:17px;align-items:flex-start}.welcome .primary{display:none}.world{min-height:500px}.world-inner{min-height:500px;display:flex;flex-direction:column;align-items:stretch}.world-copy{width:100%}.apps{margin-top:auto;grid-template-columns:repeat(3,1fr)}.app{min-height:94px;font-size:10px}.identity{padding:22px;grid-template-columns:1fr;text-align:left}.shield{width:60px;height:60px}.footer{grid-template-columns:1fr 1fr}.footer>div:first-child{grid-column:1/-1}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
@media(max-width:560px){.planet{overflow:hidden}.planet .label{display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.05;font-size:8px}.planet .label i{margin-bottom:2px}.planet{width:82px;height:82px}.planet .label br{display:block}}


/* Beyond OS 2.1 vector hero: additive overrides preserve the existing page CSS. */
.ecosystem-svg{position:absolute;inset:0;width:100%;height:100%;overflow:visible;z-index:0;pointer-events:none}.orbit>.planet,.orbit>.orbit-copy{z-index:2}.svg-orbits use{vector-effect:non-scaling-stroke}.svg-gateway{transform-box:fill-box;transform-origin:center;animation:gateway-breathe 6.8s ease-in-out infinite}.svg-atom{transform-box:fill-box;transform-origin:center;animation:atom-drift 14s linear infinite}.svg-sheen{animation:sheen-pulse 5.8s ease-in-out infinite}.svg-connections path{stroke-dasharray:7 13;animation:connection-flow 13s linear infinite}.svg-connections path:nth-child(2){animation-delay:-4s}.svg-connections path:nth-child(3){animation-delay:-8s}@keyframes gateway-breathe{0%,100%{opacity:.92}50%{opacity:1}}@keyframes atom-drift{to{transform:rotate(360deg)}}@keyframes sheen-pulse{0%,65%,100%{opacity:.13}78%{opacity:.34}}@keyframes connection-flow{to{stroke-dashoffset:-120}}html[data-theme="light"] .ecosystem-svg #gatewaySurface stop:first-child{stop-color:#ffffff}html[data-theme="light"] .ecosystem-svg #gatewaySurface stop:nth-child(2){stop-color:#eceefe}html[data-theme="light"] .ecosystem-svg #gatewaySurface stop:last-child{stop-color:#dfe3f7}html[data-theme="light"] .svg-ambient circle{opacity:.05}html[data-theme="light"] .svg-nucleus{animation:nucleusPulse 4s ease-in-out infinite;transform-origin:center}.svg-nucleus circle{filter:drop-shadow(0 0 10px rgba(168,85,247,.55))}html[data-theme="light"] .svg-nucleus circle{filter:drop-shadow(0 0 6px rgba(124,58,237,.35))}@keyframes nucleusPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}@media(prefers-reduced-motion:reduce){.svg-gateway,.svg-atom,.svg-sheen,.svg-connections path{animation:none!important}.svg-particles{display:none}}@media(max-width:560px){.ecosystem-svg{inset:2% -2% -2%;width:104%;height:100%}.svg-connections{opacity:.72}}
.root-live-guide{display:grid;grid-template-columns:repeat(2,minmax(0,1fr)) auto;gap:10px;align-items:stretch;margin:10px 0 4px}.root-live-guide>div,.root-live-guide>a{display:flex;flex-direction:column;justify-content:center;min-height:82px;padding:13px 15px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);text-decoration:none;color:#fff}.root-live-guide b{font-size:12px}.root-live-guide span{margin-top:5px;font-weight:850}.root-live-guide small{margin-top:3px;color:#aeb4c7}.root-live-guide>a{font-weight:850;color:#d7caff}@media(max-width:760px){.root-live-guide{grid-template-columns:1fr}.root-live-guide>a{min-height:52px}}

/* 2.2 hero scale refinement */
@media(min-width:851px){.hero{min-height:720px;grid-template-columns:.82fr 1.18fr;gap:64px;padding:64px 0 72px}.orbit{width:108%;margin-left:-4%;}.hero h1{font-size:clamp(66px,7.8vw,112px)}.core{width:190px}.planet{width:124px}}


html[data-theme="sunset"]{background:#1a0d24}html[data-theme="sunset"] body{color:#fff7f2;background:radial-gradient(circle at 76% 8%,rgba(255,111,97,.30),transparent 30%),radial-gradient(circle at 20% 34%,rgba(255,179,71,.18),transparent 35%),linear-gradient(180deg,#32113d 0%,#1d102b 46%,#0d1021 100%)}html[data-theme="sunset"] .brand small,html[data-theme="sunset"] .intro,html[data-theme="sunset"] .world p,html[data-theme="sunset"] .identity p{color:#f2c9c1}html[data-theme="sunset"] .login-btn,html[data-theme="sunset"] .ghost,html[data-theme="sunset"] .theme-toggle{border-color:rgba(255,220,190,.28);background:rgba(83,34,66,.48)}html[data-theme="sunset"] .core{background:radial-gradient(circle at 40% 35%,#fff0c6,#ff8a72 58%,#8f3b73);box-shadow:0 0 0 12px rgba(255,151,98,.09),0 0 52px rgba(255,96,108,.55),inset 0 0 35px rgba(255,222,170,.55)}html[data-theme="sunset"] .planet{background:rgba(70,25,60,.92);border-color:rgba(255,190,160,.30)}html[data-theme="sunset"] .world:before{background:linear-gradient(90deg,rgba(39,12,35,.95),rgba(54,17,46,.72) 42%,rgba(255,126,84,.12) 78%,rgba(36,14,44,.68))}html[data-theme="sunset"] .app{background:rgba(45,18,47,.78);border-color:rgba(255,207,176,.18)}html[data-theme="sunset"] .footer{border-color:rgba(255,210,183,.14);color:#d5aeb0}html[data-theme="sunset"] .footer h4{color:#ffe6dc}

/* Live Marketplace landing */
.home-market{position:relative;margin-top:28px;padding:42px 0 24px;isolation:isolate;overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:30px;background:linear-gradient(145deg,rgba(17,20,42,.96),rgba(5,8,20,.98) 56%,rgba(23,8,34,.96));box-shadow:0 30px 90px rgba(0,0,0,.38),inset 0 1px rgba(255,255,255,.05)}
.home-market:before{content:"";position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(115deg,rgba(255,190,50,.6),transparent 26%,transparent 69%,rgba(242,70,157,.55));-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.home-market__glow{position:absolute;z-index:-1;width:480px;height:480px;right:-180px;top:-250px;border-radius:50%;background:radial-gradient(circle,rgba(242,70,157,.24),rgba(112,87,255,.11) 38%,transparent 70%);filter:blur(8px)}
.home-market__heading{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;padding:0 34px 28px}
.home-market__heading h2{margin:8px 0 7px;font-family:"Space Grotesk",Inter,sans-serif;font-size:clamp(34px,5vw,58px);line-height:.96;letter-spacing:-.055em}
.home-market__heading p{max-width:600px;margin:0;color:#aeb6cc;font-size:14px;line-height:1.55}
.home-market__kicker{display:inline-flex;align-items:center;gap:9px;color:#ffd66e;font-size:10px;font-weight:900;letter-spacing:.17em}
.home-market__kicker i{width:8px;height:8px;border-radius:50%;background:#55e58b;box-shadow:0 0 0 5px rgba(85,229,139,.1),0 0 18px rgba(85,229,139,.75);animation:market-live 2s ease-in-out infinite}
@keyframes market-live{50%{opacity:.48;transform:scale(.82)}}
.home-market__heading-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap}
.home-market__sell,.home-market__browse{min-height:43px;display:inline-flex;align-items:center;justify-content:center;padding:0 17px;border-radius:12px;text-decoration:none;font-family:"Space Grotesk",Inter,sans-serif;font-size:12px;font-weight:780;white-space:nowrap;transition:.2s ease}
.home-market__sell{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06)}
.home-market__browse{border:1px solid rgba(255,200,73,.45);color:#171005;background:linear-gradient(135deg,#ffe083,#ffb932);box-shadow:0 10px 26px rgba(255,185,50,.17)}
.home-market__sell:hover,.home-market__browse:hover{transform:translateY(-2px)}
.home-market__controls{display:flex;gap:7px}
.home-market__controls button{width:43px;height:43px;border:1px solid rgba(255,255,255,.16);border-radius:50%;color:#fff;background:rgba(255,255,255,.07);font:700 18px/1 inherit;cursor:pointer;transition:.2s ease}
.home-market__controls button:hover,.home-market__controls button:focus-visible{border-color:#ffd16b;background:rgba(255,209,107,.12);transform:translateY(-2px)}
.home-market__controls button:disabled{opacity:.3;cursor:default;transform:none}
.home-market__feed{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(248px,292px);gap:15px;padding:7px 34px 20px;overflow-x:auto;overscroll-behavior-inline:contain;scroll-snap-type:x mandatory;scrollbar-width:none;outline:none}
.home-market__feed::-webkit-scrollbar{display:none}
.home-market-card{position:relative;min-width:0;scroll-snap-align:start;overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:20px;background:rgba(10,14,30,.86);box-shadow:0 18px 38px rgba(0,0,0,.25);transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease}
.home-market-card:hover{transform:translateY(-5px);border-color:rgba(255,210,107,.42);box-shadow:0 24px 50px rgba(0,0,0,.36)}
.home-market-card__visual{position:relative;height:184px;display:flex;align-items:center;justify-content:center;overflow:hidden;text-decoration:none;background:radial-gradient(circle at 68% 20%,hsla(var(--market-card-hue),88%,68%,.35),transparent 34%),radial-gradient(circle at 12% 92%,rgba(255,196,66,.18),transparent 35%),linear-gradient(145deg,hsl(var(--market-card-hue) 45% 17%),#080b19 70%)}
.home-market-card__visual:before,.home-market-card__visual:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.12);border-radius:50%;transform:rotate(-24deg)}
.home-market-card__visual:before{width:188px;height:78px}.home-market-card__visual:after{width:118px;height:188px}
.home-market-card__icon{position:relative;z-index:1;width:72px;height:72px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.24);border-radius:22px;background:linear-gradient(145deg,rgba(255,255,255,.2),rgba(255,255,255,.04));font:600 34px/1 "Space Grotesk",sans-serif;box-shadow:0 16px 34px rgba(0,0,0,.28),inset 0 1px rgba(255,255,255,.25);backdrop-filter:blur(9px)}
.home-market-card__badge{position:absolute;left:12px;top:12px;z-index:2;padding:6px 8px;border:1px solid rgba(255,255,255,.18);border-radius:9px;background:rgba(3,6,17,.64);font-size:8px;font-weight:900;letter-spacing:.11em;text-transform:uppercase;backdrop-filter:blur(10px)}
.home-market-card__preview{position:absolute;right:12px;bottom:11px;color:rgba(255,255,255,.55);font-size:8px;font-weight:900;letter-spacing:.15em}
.home-market-card__visual{background-color:#eee5dc;background-image:url("/beyond-market/assets/images/beyond-product-categories.png");background-repeat:no-repeat;background-size:400% 200%}
.home-market-card__visual:before,.home-market-card__visual:after{content:none}
.home-market-card__visual--hoodie{background-position:0 0}
.home-market-card__visual--art-print{background-position:66.667% 0}
.home-market-card__visual--sticker{background-position:66.667% 100%}
.home-market-card__visual--journal{background-position:100% 100%}
.home-market-card__copy{padding:17px 17px 16px}
.home-market-card__copy small{color:#8f99b3;font-size:10px;font-weight:760;text-transform:uppercase;letter-spacing:.08em}
.home-market-card__copy h3{min-height:43px;margin:7px 0 13px;font-family:"Space Grotesk",Inter,sans-serif;font-size:17px;line-height:1.2;letter-spacing:-.02em}
.home-market-card__copy h3 a{text-decoration:none}
.home-market-card__copy>div{display:flex;align-items:center;justify-content:space-between;gap:12px}
.home-market-card__copy strong{color:#ffe083;font-size:13px}
.home-market-card__copy button{width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.13);border-radius:50%;color:#fff;background:rgba(255,255,255,.05);font-size:19px;cursor:pointer;transition:.2s}
.home-market-card__copy button:hover,.home-market-card__copy button[aria-pressed="true"]{border-color:#f2469d;color:#ff79bd;background:rgba(242,70,157,.12);transform:scale(1.06)}
.home-market-card--create{min-height:304px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:9px;color:#d9dcee;text-align:center;text-decoration:none;border-style:dashed;background:linear-gradient(145deg,rgba(112,87,255,.11),rgba(255,255,255,.025))}
.home-market-card--create span{width:58px;height:58px;display:grid;place-items:center;border:1px solid rgba(166,147,255,.38);border-radius:18px;color:#cabfff;background:rgba(112,87,255,.14);font-size:30px}
.home-market-card--create strong{font-family:"Space Grotesk",Inter,sans-serif;font-size:17px}.home-market-card--create small{color:#8993ae}
.home-market__footer{display:flex;align-items:center;justify-content:space-between;padding:2px 34px 0;color:#7f89a2;font-size:11px}
.home-market__footer b{color:#fff}.home-market__footer a{color:#bfc6d9;text-decoration:none;font-weight:760}.home-market__footer a:hover{color:#ffe083}
html[data-theme="light"] .home-market{color:#f9faff;background:linear-gradient(145deg,#171b37,#070b19 62%,#210d31)}
@media(max-width:850px){.home-market__heading{align-items:flex-start;flex-direction:column}.home-market__heading-actions{justify-content:flex-start}.home-market__feed{grid-auto-columns:minmax(240px,44vw)}}
@media(max-width:560px){.home-market{width:calc(100% - 14px);margin-top:18px;padding:30px 0 20px;border-radius:24px}.home-market__heading{padding:0 18px 22px;gap:18px}.home-market__heading h2{font-size:38px}.home-market__heading-actions{width:100%}.home-market__sell,.home-market__browse{flex:1;padding-inline:10px}.home-market__controls{display:none}.home-market__feed{grid-auto-columns:80vw;padding:4px 18px 17px}.home-market__footer{padding-inline:18px}.home-market__footer a{max-width:190px;text-align:right}}
@media(prefers-reduced-motion:reduce){.home-market__kicker i{animation:none}}
</style>
<style>
.login-btn{display:inline-flex!important;align-items:center;justify-content:center;min-height:47px;padding:0 21px!important;border:1px solid rgba(255,255,255,.28)!important;border-radius:9px;text-decoration:none!important;font-weight:850!important;font-size:13px!important;background:rgba(255,255,255,.04);transition:.2s}
.login-btn:hover{border-color:#a99cff!important;background:rgba(112,87,255,.14)}
.core{font-size:0}.core .atom{position:relative;display:block;width:104px;height:104px;background:none;color:inherit}.core .atom i{position:absolute;display:block;inset:28px 4px;border:5px solid #7f6dff;border-radius:50%;transform:rotate(0deg)}.core .atom i:nth-child(2){transform:rotate(60deg);border-color:#3f91ff}.core .atom i:nth-child(3){transform:rotate(120deg);border-color:#35d69b}.core .atom b{position:absolute;left:50%;top:50%;width:62px;height:62px;border-radius:18px;transform:translate(-50%,-50%);background:#090519 url('assets/img/keyhole-hero.webp') center 72%/180% auto no-repeat;border:2px solid rgba(212,141,255,.82);box-shadow:0 0 0 5px rgba(90,70,255,.12),0 0 28px #8a61ff;z-index:2}
@media(max-width:560px){.nav{gap:8px}.nav .login-btn,.nav .primary{min-height:42px;padding:0 12px!important}.nav .login-btn{display:inline-flex!important}}
</style>
<style>
.theme-toggle{width:43px;height:43px;flex:0 0 43px;border:1px solid rgba(255,255,255,.22);border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.05);color:inherit;font-size:18px;cursor:pointer}.theme-toggle:hover{background:rgba(112,87,255,.15)}
.core .atom b{top:43%;width:46px;height:46px;border-radius:50%;background:#0b0620 url('assets/img/keyhole-hero.webp') center 67%/235% auto no-repeat;border:2px solid rgba(230,186,255,.9);box-shadow:0 0 0 6px rgba(90,70,255,.12),0 0 30px #8a61ff}.core .atom b:after{content:"";position:absolute;left:50%;top:29px;width:31px;height:40px;transform:translateX(-50%);clip-path:polygon(33% 0,67% 0,100% 100%,0 100%);background:#0b0620 url('assets/img/keyhole-hero.webp') center 79%/300% auto no-repeat;border-bottom:2px solid rgba(230,186,255,.85);filter:drop-shadow(0 8px 8px rgba(91,70,255,.42));z-index:-1}.core{overflow:visible}.core .atom{filter:drop-shadow(0 0 10px rgba(104,81,255,.35))}
.benefits{display:none}.division-icon{display:block;width:40px;height:40px;margin:0 auto 4px}.division-icon svg,.world-icon svg{display:block;width:100%;height:100%;fill:none;stroke:currentColor;stroke-width:2.35;stroke-linecap:round;stroke-linejoin:round}.world-icon svg{width:34px;height:34px}.orbit-copy{position:absolute;top:2%;left:50%;transform:translateX(-50%);width:90%;text-align:center;z-index:3;pointer-events:none}.orbit-copy strong{display:block;font-size:12px;letter-spacing:.08em;color:#dce1f2}.orbit-copy span{display:block;margin-top:5px;color:#a99cff;font-size:11px;font-weight:900;letter-spacing:.13em;text-transform:uppercase}.ring{opacity:.7;aspect-ratio:1.68/1;animation:ring-orbit 46s linear infinite}.ring.r2{opacity:.66;animation-duration:62s;animation-direction:reverse}.planet{transition:background .25s ease,box-shadow .25s ease}.planet:hover,.planet:focus-visible{background:rgba(13,18,35,.97);box-shadow:0 0 46px currentColor;z-index:5}.planet em{display:block;max-height:0;opacity:0;overflow:hidden;font-size:10px;font-style:normal;letter-spacing:.08em;transition:max-height .25s ease,opacity .25s ease,margin .25s ease}.planet:hover em,.planet:focus-visible em{max-height:18px;opacity:1;margin-top:3px}.welcome{box-shadow:0 18px 50px rgba(42,21,105,.32),inset 0 1px rgba(255,255,255,.12)}.welcome-bits,.id-action small{color:#ffe17a!important;font-weight:900}.core .atom{animation:atom-float 7s ease-in-out infinite}.core .atom b{animation:keyhole-pulse 5.8s ease-in-out infinite}@keyframes ring-orbit{to{transform:rotate(360deg)}}@keyframes atom-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}@keyframes removed-keyhole-pulse{0%,72%,100%{box-shadow:0 0 0 6px rgba(90,70,255,.12),0 0 30px #8a61ff}84%{box-shadow:0 0 0 9px rgba(90,70,255,.16),0 0 48px #b174ff}}@media(prefers-reduced-motion:reduce){.ring,.core .atom,.core .atom b{animation:none!important}}@media(max-width:560px){.orbit-copy{top:0}.orbit-copy strong{font-size:10px}.orbit-copy span{font-size:9px}.division-icon{width:28px;height:28px}.planet em{display:none}}
html[data-theme="light"]{background:#f4f6fc}html[data-theme="light"] body{color:#15182a;background:radial-gradient(circle at 75% 10%,rgba(112,87,255,.13),transparent 30%),linear-gradient(180deg,#fbfcff,#eef2fb 72%)}html[data-theme="light"] .brand small,html[data-theme="light"] .intro,html[data-theme="light"] .world p,html[data-theme="light"] .identity p{color:#596077}html[data-theme="light"] .nav>a:not(.primary),html[data-theme="light"] .login-btn{color:#171a2e}html[data-theme="light"] .login-btn,html[data-theme="light"] .ghost,html[data-theme="light"] .theme-toggle{border-color:rgba(23,26,46,.2);background:rgba(255,255,255,.62)}html[data-theme="light"] .benefits{color:#4d546a}html[data-theme="light"] .benefits b{color:#20243a}html[data-theme="light"] .orbit:before{background:radial-gradient(circle,rgba(99,78,255,.18),transparent 54%)}html[data-theme="light"] .core{background:radial-gradient(circle at 40% 35%,#fff,#e8eafd 62%);box-shadow:0 0 0 12px rgba(92,88,255,.07),0 0 45px #6c51ff55,inset 0 0 35px #d8d9f2}html[data-theme="light"] .planet{background:rgba(255,255,255,.92)}html[data-theme="light"] .world:before{background:linear-gradient(90deg,rgba(255,255,255,.94),rgba(255,255,255,.74) 42%,rgba(255,255,255,.18) 78%,rgba(255,255,255,.58))}html[data-theme="light"] .world.health{background:linear-gradient(130deg,#ecfff4,#dff8f6 55%,#eaf5fb)}html[data-theme="light"] .world.education{background:linear-gradient(130deg,#fff8e8,#f2eaff 56%,#fff2dc)}html[data-theme="light"] .world.finance{background:linear-gradient(130deg,#eef5ff,#e2ecff 58%,#f2e9ff)}html[data-theme="light"] .world p{color:#565e73}html[data-theme="light"] .app{color:#22263a;background:rgba(255,255,255,.78);border-color:rgba(35,40,65,.14)}html[data-theme="light"] .identity{color:#fff}html[data-theme="light"] .identity p{color:#eee8f7}html[data-theme="light"] .footer{border-color:rgba(23,26,46,.12);color:#626a80}html[data-theme="light"] .footer h4{color:#30364b}html[data-theme="light"] .footer .brand{color:#171a2e}@media(max-width:560px){.theme-toggle{width:40px;height:40px;flex-basis:40px}.nav{gap:6px}}
</style>
<style>
.nav a[href="#entertainment"]{border-color:var(--pink)}.nav-tools{display:flex;align-items:center;gap:8px}.visually-hidden{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important}.locale-picker{position:relative;width:43px;height:43px;flex:0 0 43px}.locale-picker:before{content:"🌐";position:absolute;inset:0;display:grid;place-items:center;font-size:18px;pointer-events:none}.locale-picker select{width:100%;height:100%;border:1px solid rgba(255,255,255,.22);border-radius:50%;background:rgba(255,255,255,.05);color:transparent;cursor:pointer;appearance:none}.locale-picker select:focus-visible{outline:2px solid #a99cff;outline-offset:2px}.locale-picker option{color:#111;background:#fff}.world.entertainment{--accent:var(--pink);background:radial-gradient(circle at 72% 28%,#f2469d55 0,transparent 31%),linear-gradient(130deg,#19071b,#2a0b2f 54%,#101438)}.daily-demos{display:grid;gap:14px;margin:0 auto 24px}.daily-demo{position:relative;overflow:hidden;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:20px;padding:24px 26px;border:1px solid rgba(255,255,255,.14);border-radius:21px;text-decoration:none;background:linear-gradient(135deg,rgba(18,22,48,.94),rgba(54,28,79,.86));box-shadow:0 16px 44px rgba(0,0,0,.25)}.daily-demo.french{border-color:rgba(78,137,255,.42);background:linear-gradient(135deg,rgba(8,35,75,.95),rgba(52,24,91,.9))}.daily-demo.verse{border-color:rgba(81,219,120,.36);background:linear-gradient(135deg,rgba(10,50,35,.96),rgba(26,68,49,.88))}.daily-demo-icon{font-size:34px}.daily-demo-kicker{display:block;margin-bottom:6px;color:#aaa0ff;font-size:10px;font-weight:950;letter-spacing:.15em;text-transform:uppercase}.daily-demo.verse .daily-demo-kicker{color:#8ce5a8}.daily-demo h2{margin:0 0 5px;font-size:clamp(21px,3vw,29px)}.daily-demo p{margin:0;color:#c5cada;line-height:1.5}.daily-demo-action{font-size:13px;font-weight:900;white-space:nowrap}.demo-badge{position:absolute;right:13px;top:10px;color:#b8bed2;font-size:8px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}html[data-theme="light"] .locale-picker select{border-color:rgba(23,26,46,.2);background:rgba(255,255,255,.62)}html[data-theme="light"] .daily-demo{color:#fff}@media(max-width:650px){.daily-demo{grid-template-columns:auto 1fr;padding:21px 18px}.daily-demo-action{grid-column:2}.nav-tools{gap:5px}.locale-picker,.theme-toggle{width:40px;height:40px;flex-basis:40px}}
</style>
<style>
.nav a[href="#wallet"]{border-color:var(--blue)}
.world.wallet{--accent:var(--blue);background:radial-gradient(ellipse at 75% 30%,#2f62c555 0,transparent 34%),linear-gradient(130deg,#061328,#081d45 58%,#190c37)}
html[data-theme="light"] .world.wallet{background:linear-gradient(130deg,#eef5ff,#e2ecff 58%,#f2e9ff)}
#beyond-os-shell .locale-picker,#beyond-os-shell .theme-toggle{width:38px;height:38px;flex:0 0 38px}
@media(max-width:650px){#beyond-os-shell .bos-actions{gap:6px}}
.brand-atom{display:inline-grid;width:38px;height:38px;place-items:center;vertical-align:middle;margin-right:9px;border:1px solid rgba(255,255,255,.14);border-radius:12px;background:radial-gradient(circle at 35% 25%,rgba(117,91,255,.2),rgba(8,9,20,.94) 68%);box-shadow:0 8px 24px rgba(88,108,255,.28),inset 0 1px rgba(255,255,255,.08)}
.brand-atom img{display:block;width:34px;height:34px;filter:drop-shadow(0 0 8px rgba(143,100,255,.32))}
.nav>a[href="/academy/"]{border-color:var(--gold)}.nav>a[href="/beyond-tv/"]{border-color:var(--pink)}.nav>a[href="/beyond-games/"]{border-color:#a855f7}.nav>a[href="/beyond-market/"]{border-color:var(--blue)}.nav>a[href="/release-notes.php"]{border-color:#51db78}
.nav>a[href="/academy/"]:hover,.nav>a[href="/academy/"]:focus-visible{color:#ffd16b}.nav>a[href="/beyond-tv/"]:hover,.nav>a[href="/beyond-tv/"]:focus-visible{color:#ff73ba}.nav>a[href="/beyond-games/"]:hover,.nav>a[href="/beyond-games/"]:focus-visible{color:#c69cff}.nav>a[href="/beyond-market/"]:hover,.nav>a[href="/beyond-market/"]:focus-visible{color:#70a7ff}.nav>a[href="/release-notes.php"]:hover,.nav>a[href="/release-notes.php"]:focus-visible{color:#74e798}
.currency-picker{position:relative;display:flex;align-items:center;min-width:84px;height:43px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.055);overflow:hidden}.currency-picker:focus-within{outline:2px solid #a99cff;outline-offset:2px}.currency-picker>span{position:absolute;left:11px;z-index:1;color:#c9bcff;font-size:12px;font-weight:950;pointer-events:none}.currency-picker select{position:relative;width:100%;height:100%;padding:0 25px 0 29px;border:0;outline:0;color:#fff;background:transparent;font:900 11px/1 inherit;cursor:pointer;appearance:none}.currency-picker:after{content:"⌄";position:absolute;right:10px;top:11px;color:#aeb4ca;font-size:12px;pointer-events:none}.currency-picker option{color:#111;background:#fff}html[data-theme="light"] .currency-picker{border-color:rgba(23,26,46,.2);background:rgba(255,255,255,.62)}html[data-theme="light"] .currency-picker select{color:#171a2e}@media(max-width:560px){.currency-picker{min-width:72px;height:40px}.currency-picker select{padding-left:25px;padding-right:20px;font-size:10px}.currency-picker>span{left:9px}.currency-picker:after{right:7px}}
.brand,.nav,.primary,.ghost,.home-live-button,.live-app-actions a,.live-app-actions button{font-family:"Space Grotesk",Inter,system-ui,sans-serif}.brand{font-weight:700;letter-spacing:-.055em}.nav>a:not(.primary){font-weight:600;letter-spacing:-.015em}.primary{position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.16);background:linear-gradient(105deg,#526dff 0%,#8658f6 50%,#e950aa 100%);font-weight:700;letter-spacing:-.02em;box-shadow:0 14px 36px rgba(101,72,255,.34),inset 0 1px rgba(255,255,255,.22)}.primary:hover,.primary:focus-visible{transform:translateY(-1px);box-shadow:0 18px 42px rgba(101,72,255,.42),inset 0 1px rgba(255,255,255,.28)}
</style>
</head>
<body class="home-page">
<header class="top wrap">
    <a class="brand" href="./"><b class="brand-atom" aria-hidden="true"><img src="/assets/images/bos-logo-mark.svg?v=20260727-3" alt=""></b>BEYOND <span>OS</span><small>THE CONNECTED IMAGINATION ECOSYSTEM</small></a>
    <nav class="nav" aria-label="Primary navigation">
          <a href="/academy/">Academy</a><a href="/beyond-tv/">TV</a><a href="/beyond-games/">Games</a><a href="/beyond-market/">Marketplace</a><a href="/release-notes.php">What’s New</a>
          <label class="currency-picker"><span aria-hidden="true">$</span><span class="visually-hidden">Display currency</span><select id="homeCurrency" aria-label="Display currency"><option value="USD">USD</option><option value="CAD">CAD</option><option value="BITS">bit$</option></select></label>
          <a class="primary" href="/app-store/">App Store</a>
    </nav>
</header>
<main>
<section class="hero wrap">
    <div>
        <h1><span class="h">Health.</span><span class="e">Education.</span><span class="f">Wallet.</span><span class="x">Entertainment.</span></h1>
        <p class="tagline">Live. Learn. Earn. Explore.</p>
        <p class="intro">Everything you need to grow, create and discover—connected in one ecosystem.</p>
        <div class="hero-actions">
            <a class="ghost" href="app-store/">Open App Store &nbsp;▶</a>
        </div>
        <div class="benefits"><span><b>∞</b> Every possibility, connected</span></div>
    </div>
    <div class="orbit" aria-label="Health, education, wallet and entertainment orbit Beyond OS">
        <svg class="ecosystem-svg" viewBox="0 0 720 610" role="img" aria-labelledby="ecosystemTitle ecosystemDesc">
            <title id="ecosystemTitle">The Beyond OS connected ecosystem</title>
            <desc id="ecosystemDesc">Health, education, wallet and entertainment connect in one ecosystem.</desc>
            <defs>
                <radialGradient id="gatewaySurface" cx="38%" cy="30%" r="76%">
                    <stop offset="0" stop-color="#342466"/><stop offset=".58" stop-color="#0b0b1d"/><stop offset="1" stop-color="#050713"/>
                </radialGradient>
                <linearGradient id="atomStroke" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#4f8cff"/><stop offset=".5" stop-color="#8d58ff"/><stop offset="1" stop-color="#4ee097"/>
                </linearGradient>
                <linearGradient id="keyholeFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#e853c1"/><stop offset=".46" stop-color="#755cff"/><stop offset="1" stop-color="#080a18"/>
                </linearGradient>
                <filter id="gatewayGlow" x="-80%" y="-80%" width="260%" height="260%">
                    <feGaussianBlur stdDeviation="13" result="blur"/><feFlood flood-color="#7657ff" flood-opacity=".78"/><feComposite in2="blur" operator="in"/><feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="particleGlow" x="-300%" y="-300%" width="700%" height="700%"><feGaussianBlur stdDeviation="4" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                <path id="orbitA" d="M86 304 C92 126 628 110 638 304 C648 496 99 487 86 304Z"/>
                <path id="orbitB" d="M151 128 C324 56 594 179 566 345 C541 500 252 573 121 416 C18 292 42 174 151 128Z"/>
                <path id="orbitC" d="M122 418 C58 273 237 109 430 122 C608 134 681 309 564 435 C451 558 188 567 122 418Z"/>
                <path id="orbitD" d="M105 228 C214 76 518 70 625 226 C729 378 557 548 357 544 C160 541 3 374 105 228Z"/>
            </defs>
            <g class="svg-ambient" aria-hidden="true">
                <circle cx="360" cy="306" r="150" fill="#775cff" opacity=".08"/>
                <circle cx="360" cy="306" r="112" fill="#4b8cff" opacity=".06"/>
            </g>
            <g class="svg-orbits" fill="none" aria-hidden="true">
                <use href="#orbitA" stroke="#5792ff" stroke-opacity=".36"/>
                <use href="#orbitB" stroke="#ffbd49" stroke-opacity=".27"/>
                <use href="#orbitC" stroke="#54df83" stroke-opacity=".26"/>
                <use href="#orbitD" stroke="#f2469d" stroke-opacity=".30"/>
            </g>
            <g class="svg-particles" aria-hidden="true" filter="url(#particleGlow)">
                <circle r="5" fill="#70a7ff"><animateMotion dur="12s" repeatCount="indefinite"><mpath href="#orbitA"/></animateMotion></circle>
                <circle r="4" fill="#ffd16b"><animateMotion dur="16s" begin="-6s" repeatCount="indefinite"><mpath href="#orbitB"/></animateMotion></circle>
                <circle r="4.5" fill="#70e99a"><animateMotion dur="18s" begin="-11s" repeatCount="indefinite"><mpath href="#orbitC"/></animateMotion></circle>
                <circle r="4.5" fill="#ff6fba"><animateMotion dur="21s" begin="-9s" repeatCount="indefinite"><mpath href="#orbitD"/></animateMotion></circle>
            </g>
            <g class="svg-connections" fill="none" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M313 267 C262 228 205 210 164 192" stroke="#51db78" stroke-opacity=".32"/>
                <path d="M407 267 C469 229 519 219 570 208" stroke="#ffbf32" stroke-opacity=".30"/>
                <path d="M335 372 C301 421 251 463 210 505" stroke="#448cff" stroke-opacity=".34"/>
                <path d="M389 370 C432 418 490 462 544 505" stroke="#f2469d" stroke-opacity=".34"/>
            </g>
            <g class="svg-gateway" transform="translate(360 306)" filter="url(#gatewayGlow)">
                <circle r="101" fill="none" stroke="#9259ff" stroke-width="3" opacity=".92"/>
                <circle r="84" fill="url(#gatewaySurface)" stroke="#6d69ff" stroke-width="2"/>
                <g class="svg-atom" fill="none" stroke="url(#atomStroke)" stroke-width="8" stroke-linecap="round">
                    <ellipse rx="72" ry="31" transform="rotate(0)"/>
                    <ellipse rx="72" ry="31" transform="rotate(60)"/>
                    <ellipse rx="72" ry="31" transform="rotate(120)"/>
                </g>
                <g class="svg-nucleus">
                    <path d="M0-25A17 17 0 0 0-9.5 6L-17 32H17L9.5 6A17 17 0 0 0 0-25ZM0-17A9 9 0 0 0-4.5-.2L-9 24H9L4.5-.2A9 9 0 0 0 0-17Z" fill="#090b18" fill-rule="evenodd" clip-rule="evenodd"/>
                    <path d="M0-25A17 17 0 0 0-9.5 6L-17 32H17L9.5 6A17 17 0 0 0 0-25Z" fill="none" stroke="#f2eaff" stroke-width="4.5" stroke-linejoin="round" style="filter:drop-shadow(0 0 10px rgba(168,85,247,.85))"/>
                    <path d="M0-17A9 9 0 0 0-4.5-.2L-9 24H9L4.5-.2A9 9 0 0 0 0-17Z" fill="none" stroke="url(#keyholeFill)" stroke-width="3" stroke-linejoin="round"/>
                </g>
                <circle class="svg-sheen" cx="-22" cy="-36" r="10" fill="#fff" opacity=".18"/>
            </g>
        </svg>
        <a class="planet ph" href="app-store/#featured"><span><i>♥</i>HEALTH</span></a>
        <a class="planet pe" href="app-store/"><span><i>🏫</i>EDUCATION</span></a>
            <a class="planet pf" href="beyond-id/dashboard/wallet.php"><span><i>👛</i>WALLET</span></a>
        <a class="planet px" href="beyond-tv/"><span class="label"><i>▶</i>ENTERTAIN<br>MENT</span></a>
    </div>
</section>
<?php
$classicSchedule = beyond_classic_schedule_state();
$classicCurrent = $classicSchedule['current'];
$classicNext = $classicSchedule['next'];
$cartoonSchedule = beyond_cartoons_schedule_state();
$cartoonCurrent = $cartoonSchedule['current'];
$cartoonNext = $cartoonSchedule['next'];
$homeLiveCatalogue = json_decode((string)@file_get_contents(__DIR__ . '/beyond-tv/data/channels.json'), true) ?: [];
$homeLiveFeatured = json_decode((string)@file_get_contents(__DIR__ . '/beyond-tv/data/featured-channels.json'), true) ?: [];
$homeLiveChannels = beyond_tv_public_channels($homeLiveCatalogue, $homeLiveFeatured);
$homeLiveControls = [
    'beyond-after-dark' => ['theme'=>'after-dark','endpoint'=>'/beyond-tv/api/channel-stream.php?slug=beyond-after-dark','embed'=>'/beyond-tv/embed-player.php?slug=beyond-after-dark','icon'=>'moon-star','label'=>'After Dark','now'=>'Loading the live program...','next'=>'Live schedule connecting'],
    'beyond-cartoons' => ['theme'=>'cartoons','endpoint'=>'/beyond-tv/api/beyond-cartoons-live.php','icon'=>'tv','label'=>'Kartoons'],
    'yugioh-tv' => ['theme'=>'anime','endpoint'=>'/beyond-tv/api/anime-live.php','icon'=>'zap','label'=>'Anime'],
    'classic-cinema' => ['theme'=>'cinema','endpoint'=>'/beyond-tv/api/movies-live.php','embed'=>'/beyond-tv/movie-player.php','icon'=>'clapperboard','label'=>'Movies','now'=>'Loading the live feature...','next'=>'Next movie loading','sync'=>'7200000'],
    'beyond-comedy' => ['theme'=>'comedy','endpoint'=>'/beyond-tv/api/channel-stream.php?slug=beyond-comedy','embed'=>'/beyond-tv/embed-player.php?slug=beyond-comedy','icon'=>'laugh','label'=>'Comedy','now'=>'Loading the live program...','next'=>'Live schedule connecting'],
    'beyond-family' => ['theme'=>'family','endpoint'=>'/beyond-tv/api/channel-stream.php?slug=beyond-family','embed'=>'/beyond-tv/embed-player.php?slug=beyond-family','icon'=>'sparkles','label'=>'Family','now'=>'Loading the live program...','next'=>'Live schedule connecting'],
    'classic-cartoon-theater' => ['theme'=>'classic','endpoint'=>'/beyond-tv/api/classic-live.php','icon'=>'film','label'=>'Classic'],
    'bubble-guppies' => ['theme'=>'preschool','endpoint'=>'/beyond-tv/api/bluey-live.php','embed'=>'https://www.youtube-nocookie.com/embed/61fSXCbzF1M?autoplay=1&mute=1&playsinline=1&rel=0&enablejsapi=1','icon'=>'paw-print','label'=>'Preschool EN','now'=>"English preschool demo",'next'=>"Bluey, Blue's Clues and more"],
    'preschool-francais' => ['theme'=>'preschool-fr','endpoint'=>'/beyond-tv/api/channel-stream.php?slug=preschool-francais','embed'=>'/beyond-tv/embed-player.php?slug=preschool-francais','icon'=>'languages','label'=>'Preschool FR','now'=>'Caillou en francais','next'=>'Histoires educatives en francais'],
    'space-tv' => ['theme'=>'space','endpoint'=>'/beyond-tv/api/space-live.php','icon'=>'satellite','label'=>'Space','now'=>'The Sun & The Milky Way','next'=>'Weekly space rotation'],
    'beyond-ancient' => ['theme'=>'ancient','endpoint'=>'/beyond-tv/api/schedule-live.php?slug=beyond-ancient','embed'=>'https://www.youtube-nocookie.com/embed/BR2ZMj3o5EU?autoplay=1&mute=1&playsinline=1&rel=0&enablejsapi=1','icon'=>'landmark','label'=>'Ancient','now'=>'Ancient Egypt Documentary','next'=>'Pyramids, pharaohs and archaeology'],
    'beyond-french' => ['theme'=>'french','endpoint'=>'/beyond-tv/api/schedule-live.php?slug=beyond-french','icon'=>'languages','label'=>'French'],
    'beyond-health' => ['theme'=>'health','endpoint'=>'/beyond-tv/api/schedule-live.php?slug=beyond-health','icon'=>'heart-pulse','label'=>'Health'],
    'beyond-trailers' => ['theme'=>'trailers','endpoint'=>'/beyond-tv/api/schedule-live.php?slug=beyond-trailers','icon'=>'popcorn','label'=>'Trailers'],
    'beyond-sports' => ['theme'=>'sports','endpoint'=>'/beyond-tv/api/schedule-live.php?slug=beyond-sports','icon'=>'trophy','label'=>'Sports'],
    'beyond-mystery' => ['theme'=>'mystery','endpoint'=>'/beyond-tv/api/channel-stream.php?slug=beyond-mystery','embed'=>'/beyond-tv/embed-player.php?slug=beyond-mystery','icon'=>'search','label'=>'Mystery','now'=>'Loading the live program...','next'=>'Live schedule connecting'],
];
?>
<section class="home-live-stage" data-channel-theme="after-dark" data-sync-owner="page" aria-labelledby="homeLiveHeading">
  <div class="home-live-stage__background" aria-hidden="true"></div>
  <div class="home-live-stage__inner">
    <header class="home-live-stage__top">
      <div>
        <span class="home-live-kicker" id="homeLiveKicker"><i></i> Beyond TV · Channel 1 live</span>
        <h2 id="homeLiveHeading">Beyond After Dark is playing now.</h2>
        <p id="homeLiveDescription"><strong>🌙 Connecting to the live program…</strong> · Synchronized premium channel preview · Vancouver time</p>
      </div>
      <div class="home-live-actions">
        <a class="home-live-button secondary" href="/beyond-tv/#guide">TV Guide</a>
        <a class="home-live-button" href="/beyond-tv/channel.php?slug=beyond-after-dark" id="homeLiveOpen">Watch full channel →</a>
      </div>
    </header>

    <div class="home-live-player">
      <iframe id="homeBeyondTvPlayer" src="/beyond-tv/embed-player.php?slug=beyond-after-dark" title="Beyond After Dark live on Beyond TV" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>

    <div class="home-live-meta">
      <div><span class="live-dot"></span><b id="homeLiveChannelName">Beyond After Dark</b><span id="homeLiveNow">Loading live schedule…</span></div>
      <span class="home-live-clock">Live schedule · America/Vancouver</span>
    </div>

    <div class="home-live-switch" role="group" aria-label="Choose a Beyond TV channel">
      <?php foreach ($homeLiveChannels as $homeLiveIndex => $homeLiveChannel):
        $homeLiveSlug = (string)($homeLiveChannel['slug'] ?? '');
        $homeLiveControl = $homeLiveControls[$homeLiveSlug] ?? [
            'theme' => $homeLiveSlug,
            'endpoint' => '/beyond-tv/api/schedule-live.php?slug=' . rawurlencode($homeLiveSlug),
            'icon' => 'tv',
            'label' => (string)($homeLiveChannel['short_name'] ?? $homeLiveChannel['name'] ?? 'Channel'),
        ];
      ?>
      <button type="button"<?=$homeLiveIndex === 0 ? ' class="active"' : ''?> data-home-channel="<?=htmlspecialchars((string)$homeLiveControl['theme'])?>" data-channel-slug="<?=htmlspecialchars($homeLiveSlug)?>" data-channel-number="<?=htmlspecialchars((string)($homeLiveChannel['display_number'] ?? ($homeLiveIndex + 1)))?>" data-channel-name="<?=htmlspecialchars((string)($homeLiveChannel['name'] ?? 'Beyond TV'))?>" data-endpoint="<?=htmlspecialchars((string)$homeLiveControl['endpoint'])?>"<?php if (!empty($homeLiveControl['embed'])): ?> data-embed="<?=htmlspecialchars((string)$homeLiveControl['embed'])?>"<?php endif; ?><?php if (!empty($homeLiveControl['sync'])): ?> data-sync-ms="<?=htmlspecialchars((string)$homeLiveControl['sync'])?>"<?php endif; ?> data-now="<?=htmlspecialchars((string)($homeLiveControl['now'] ?? $homeLiveChannel['now'] ?? 'Loading the live program...'))?>" data-next="<?=htmlspecialchars((string)($homeLiveControl['next'] ?? $homeLiveChannel['up_next'] ?? 'Live schedule connecting'))?>" data-icon-name="<?=htmlspecialchars((string)$homeLiveControl['icon'])?>" data-open="/beyond-tv/channel.php?slug=<?=urlencode($homeLiveSlug)?>"><span class="home-live-switch__icon" aria-hidden="true"><i data-lucide="<?=htmlspecialchars((string)$homeLiveControl['icon'])?>"></i></span><span class="home-live-switch__number"><?=str_pad((string)($homeLiveChannel['display_number'] ?? ($homeLiveIndex + 1)), 2, '0', STR_PAD_LEFT)?></span><span class="home-live-switch__label"><?=htmlspecialchars((string)$homeLiveControl['label'])?></span></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="home-market wrap" aria-labelledby="homeMarketTitle">
  <div class="home-market__glow" aria-hidden="true"></div>
  <header class="home-market__heading">
    <div>
      <span class="home-market__kicker"><i></i> LIVE MARKETPLACE</span>
      <h2 id="homeMarketTitle">Fresh from Beyond Market.</h2>
      <p><?=$homeMarketFeedIsPublished ? 'New active listings flow here automatically from Beyond Sell.' : 'Shop real products from independent artists. Checkout and fulfillment happen securely on Redbubble.'?></p>
    </div>
    <div class="home-market__heading-actions">
      <a class="home-market__sell" href="/beyond-sell/">Start selling</a>
      <a class="home-market__browse" href="/beyond-market/">Open Marketplace →</a>
      <div class="home-market__controls" aria-label="Marketplace listing controls">
        <button type="button" data-home-market-prev aria-label="Previous Marketplace listing">←</button>
        <button type="button" data-home-market-next aria-label="Next Marketplace listing">→</button>
      </div>
    </div>
  </header>
  <div class="home-market__feed" data-home-market-carousel tabindex="0" role="region" aria-roledescription="carousel" aria-label="Latest Beyond Market listings">
    <?php foreach ($homeMarketFeed as $marketIndex => $marketItem):
      $marketPriceParts = [];
      if (($marketItem['price_bits'] ?? null) !== null) {
          $marketPriceParts[] = number_format((int)$marketItem['price_bits']) . ' bit$';
      }
      if ((float)($marketItem['price_cash'] ?? 0) > 0) {
          $marketPriceParts[] = '$' . number_format((float)$marketItem['price_cash'], 2) . ' ' . (string)($marketItem['currency'] ?? 'CAD');
      }
      $marketPriceLabel = isset($marketItem['price_label'])
          ? (string)$marketItem['price_label']
          : ($marketPriceParts ? implode(' or ', $marketPriceParts) : 'Free');
      $marketItemUrl = (string)($marketItem['market_url'] ?? ('/beyond-sell/listing.php?id=' . (int)($marketItem['id'] ?? 0)));
      $marketItemExternal = !empty($marketItem['external']);
      $marketItemType = (string)($marketItem['item_type'] ?? 'digital');
      $marketVisualOptions = ['hoodie','art-print','sticker','journal'];
      $marketVisual = (string)($marketItem['visual'] ?? $marketVisualOptions[$marketIndex % count($marketVisualOptions)]);
      $marketSeller = (string)($marketItem['seller'] ?? 'Live listing');
    ?>
    <article class="home-market-card" data-market-slide>
      <a class="home-market-card__visual home-market-card__visual--<?=e($marketVisual)?>" href="<?=e($marketItemUrl)?>"<?=$marketItemExternal ? ' target="_blank" rel="external noopener"' : ''?>>
        <span class="home-market-card__badge"><?=e(ucwords(str_replace('_', ' ', (string)($marketItem['listing_type'] ?? 'listing'))))?></span>
        <span class="home-market-card__preview"><?=$marketItemExternal ? 'SOLD ON REDBUBBLE' : 'BEYOND MARKET'?></span>
      </a>
      <div class="home-market-card__copy">
        <small><?=e(ucfirst($marketItemType))?> · <?=e($marketSeller)?></small>
        <h3><a href="<?=e($marketItemUrl)?>"<?=$marketItemExternal ? ' target="_blank" rel="external noopener"' : ''?>><?=e((string)($marketItem['title'] ?? 'Marketplace listing'))?></a></h3>
        <div><strong><?=e($marketPriceLabel)?></strong><button type="button" data-market-save aria-label="Save <?=e((string)($marketItem['title'] ?? 'listing'))?>" aria-pressed="false">♡</button></div>
      </div>
    </article>
    <?php endforeach; ?>
    <a class="home-market-card home-market-card--create" href="/beyond-sell/create.php">
      <span>＋</span><strong>List something new.</strong><small>Publish through Beyond Sell</small>
    </a>
  </div>
  <footer class="home-market__footer"><span><b data-home-market-position>1</b> / <?=count($homeMarketFeed)+1?></span><a href="/beyond-market/#live-listings">See the full seller floor →</a></footer>
</section>

<?php if ($homeGameDemos): ?>
<section class="live-apps live-game-demos wrap" aria-labelledby="liveAppsTitle">
  <header class="live-apps-heading">
    <div><span>PLAYABLE NOW · BEYOND GAMES</span><h2 id="liveAppsTitle">Live demo games.</h2></div>
    <div class="live-apps-heading__actions">
      <a href="/beyond-games/">Explore Beyond Games →</a>
      <div class="live-app-controls" aria-label="Game demo carousel controls">
        <button type="button" data-live-app-prev aria-label="Previous playable game">←</button>
        <button type="button" data-live-app-next aria-label="Next playable game">→</button>
      </div>
    </div>
  </header>
  <div class="live-app-grid" data-live-app-carousel tabindex="0" role="region" aria-roledescription="carousel" aria-label="Playable Beyond Games demos">
    <?php foreach ($homeGameDemos as $homeGame): ?>
    <article class="live-app-card game-demo-card game-demo-<?=e((string)$homeGame['slug'])?>" style="--game-color:<?=e((string)($homeGame['color'] ?? '#ffb33d'))?>">
      <div class="live-app-card__art" aria-hidden="true"><?=e((string)($homeGame['icon'] ?? '▶'))?></div>
      <span class="live-app-label"><?=e(strtoupper((string)($homeGame['category'] ?? 'Beyond Games')))?> · <?=e(strtoupper((string)($homeGame['status'] ?? 'PLAYABLE DEMO')))?></span>
      <h3><?=e((string)$homeGame['title'])?></h3>
      <p class="translation"><?=e((string)($homeGame['tagline'] ?? 'Play the latest Beyond Games demo.'))?></p>
      <p class="pronunciation"><?=e((string)($homeGame['gameplay'] ?? 'Jump in and play directly in your browser.'))?></p>
      <div class="live-app-actions">
        <a href="<?=e((string)$homeGame['play_url'])?>">Play demo →</a>
        <a href="/beyond-games/">Game details</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <div class="live-app-progress" data-live-app-progress aria-label="Game carousel position"></div>
</section>
<?php endif; ?>

<nav class="home-shortcuts wrap" aria-label="Beyond OS quick destinations">
  <a href="/app-store/"><span>🛍</span><strong>App Store</strong><small>Discover every Beyond app</small></a>
  <a href="/beyond-id/dashboard/wallet.php"><span>👛</span><strong>Wallet</strong><small>bit$, purchases and earnings</small></a>
  <a href="/beyond-market/"><span>🌐</span><strong>Marketplace</strong><small>Explore creators and products</small></a>
  <a href="/release-notes.php"><span>✦</span><strong>What’s New</strong><small>See the latest app and project updates</small></a>
</nav>

<style>
.home-live-stage{position:relative;isolation:isolate;width:min(1600px,calc(100vw - 24px));margin:4px auto 42px;overflow:hidden;border:1px solid rgba(151,112,255,.48);border-radius:32px;background:#080a18;box-shadow:0 30px 90px rgba(0,0,0,.48)}
.home-live-stage__background{position:absolute;inset:-18px;z-index:-2;background-image:url('/beyond-tv/assets/img/channel-backgrounds-sprite.png');background-repeat:no-repeat;background-size:400% 200%;background-position:var(--channel-bg,33.333% 0);filter:blur(9px) saturate(1.34) contrast(1.08) hue-rotate(var(--channel-hue,0deg));transform:scale(1.035);opacity:1;transition:background-position .35s ease,filter .35s ease}
.home-live-stage:after{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(3,5,13,.12),rgba(3,5,13,.48) 82%,rgba(5,7,18,.72));pointer-events:none}
.home-live-stage[data-channel-theme="after-dark"]{--channel-bg:0 0;--channel-hue:-18deg}.home-live-stage[data-channel-theme="cartoons"]{--channel-bg:33.333% 0;--channel-hue:0deg}.home-live-stage[data-channel-theme="anime"]{--channel-bg:100% 0;--channel-hue:22deg}.home-live-stage[data-channel-theme="classic"]{--channel-bg:0 0}.home-live-stage[data-channel-theme="preschool"]{--channel-bg:66.666% 0}.home-live-stage[data-channel-theme="preschool-fr"]{--channel-bg:66.666% 100%}.home-live-stage[data-channel-theme="space"]{--channel-bg:100% 0}.home-live-stage[data-channel-theme="ancient"]{--channel-bg:0 100%}.home-live-stage[data-channel-theme="cinema"]{--channel-bg:33.333% 100%}.home-live-stage[data-channel-theme="french"]{--channel-bg:66.666% 100%}.home-live-stage[data-channel-theme="health"]{--channel-bg:100% 100%}.home-live-stage[data-channel-theme="comedy"]{--channel-bg:33.333% 100%}.home-live-stage[data-channel-theme="family"]{--channel-bg:66.666% 0}.home-live-stage[data-channel-theme="trailers"]{--channel-bg:33.333% 100%;--channel-hue:32deg}.home-live-stage[data-channel-theme="sports"]{--channel-bg:100% 0;--channel-hue:160deg}.home-live-stage[data-channel-theme="mystery"]{--channel-bg:0 100%;--channel-hue:-40deg}
.home-live-stage__inner{padding:clamp(18px,3vw,42px)}.home-live-stage__top{display:flex;align-items:end;justify-content:space-between;gap:28px;margin-bottom:20px}.home-live-stage h2{margin:5px 0 0;font-size:clamp(34px,5vw,68px);line-height:.96;letter-spacing:-.055em}.home-live-stage__top p{max-width:850px;margin:12px 0 0;color:#d0d4e2;line-height:1.55}.home-live-description__icon{display:inline-grid;width:24px;height:24px;margin-right:7px;place-items:center;vertical-align:-7px;color:var(--channel-accent,#c6baff)}.home-live-description__icon svg{width:21px;height:21px}.home-live-kicker{display:inline-flex;align-items:center;gap:9px;color:#c6baff;font-size:11px;font-weight:950;letter-spacing:.14em;text-transform:uppercase}.home-live-kicker i,.live-dot{width:9px;height:9px;border-radius:50%;background:#ff365f;box-shadow:0 0 0 5px rgba(255,54,95,.16)}.home-live-actions{display:flex;gap:10px;flex:0 0 auto}.home-live-button{display:inline-flex;min-height:46px;align-items:center;justify-content:center;padding:0 17px;border-radius:12px;text-decoration:none;font-size:12px;font-weight:900;background:linear-gradient(100deg,#6857ff,#e946a0);box-shadow:0 12px 30px rgba(95,73,255,.28)}.home-live-button.secondary{border:1px solid rgba(255,255,255,.24);background:rgba(6,8,18,.48);box-shadow:none}.home-live-player{width:100%;aspect-ratio:16/8.4;min-height:540px;overflow:hidden;border:1px solid rgba(255,255,255,.18);border-radius:25px;background:#000;box-shadow:0 25px 70px rgba(0,0,0,.58)}.home-live-player iframe{display:block;width:100%;height:100%;border:0}.home-live-meta{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 3px 13px}.home-live-meta>div{display:flex;align-items:center;gap:11px;flex-wrap:wrap}.home-live-meta span{color:#c3c8d8}.home-live-clock{font-size:11px}.home-live-switch{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:9px}.home-live-switch button{--channel-accent:#a994ff;position:relative;min-width:0;min-height:90px;overflow:hidden;padding:12px 8px 10px;border:1px solid rgba(255,255,255,.15);border-radius:16px;color:#fff;background:linear-gradient(155deg,rgba(255,255,255,.075),rgba(4,7,17,.7) 62%);font:800 11px/1.15 inherit;cursor:pointer;box-shadow:inset 0 1px rgba(255,255,255,.08),0 10px 25px rgba(0,0,0,.18);transition:transform .22s ease,border-color .22s ease,background .22s ease,box-shadow .22s ease}.home-live-switch button:before{content:"";position:absolute;inset:auto -30% -65% 10%;height:90%;border-radius:50%;background:var(--channel-accent);filter:blur(22px);opacity:.11;pointer-events:none}.home-live-switch button[data-home-channel="after-dark"]{--channel-accent:#b59cff}.home-live-switch button[data-home-channel="cartoons"]{--channel-accent:#57d8ff}.home-live-switch button[data-home-channel="anime"]{--channel-accent:#ffd45c}.home-live-switch button[data-home-channel="cinema"]{--channel-accent:#ff628f}.home-live-switch button[data-home-channel="classic"]{--channel-accent:#f0b66e}.home-live-switch button[data-home-channel="preschool"]{--channel-accent:#74e79b}.home-live-switch button[data-home-channel="preschool-fr"]{--channel-accent:#719cff}.home-live-switch button[data-home-channel="space"]{--channel-accent:#7fe6ff}.home-live-switch button[data-home-channel="ancient"]{--channel-accent:#d9ad63}.home-live-switch button[data-home-channel="comedy"]{--channel-accent:#ffca57}.home-live-switch button[data-home-channel="family"]{--channel-accent:#ff89cc}.home-live-switch button[data-home-channel="french"]{--channel-accent:#7ca7ff}.home-live-switch button[data-home-channel="health"]{--channel-accent:#7df0b0}.home-live-switch button[data-home-channel="trailers"]{--channel-accent:#ff9a57}.home-live-switch button[data-home-channel="sports"]{--channel-accent:#54d4ff}.home-live-switch button[data-home-channel="mystery"]{--channel-accent:#d0a6ff}.home-live-switch__icon{position:relative;display:grid;width:40px;height:40px;margin:0 auto 8px;place-items:center;border:1px solid color-mix(in srgb,var(--channel-accent) 48%,transparent);border-radius:13px;color:var(--channel-accent);background:color-mix(in srgb,var(--channel-accent) 12%,rgba(255,255,255,.025));box-shadow:inset 0 1px rgba(255,255,255,.1),0 7px 18px color-mix(in srgb,var(--channel-accent) 13%,transparent)}.home-live-switch__icon svg{width:21px;height:21px;stroke-width:2}.home-live-switch__number{position:absolute;right:8px;top:7px;color:rgba(255,255,255,.36);font:750 8px/1 "Space Grotesk",Inter,sans-serif;letter-spacing:.1em}.home-live-switch__label{position:relative;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:.01em}.home-live-switch button:hover,.home-live-switch button:focus-visible{transform:translateY(-3px);border-color:color-mix(in srgb,var(--channel-accent) 68%,transparent);background:linear-gradient(155deg,color-mix(in srgb,var(--channel-accent) 13%,rgba(255,255,255,.06)),rgba(5,7,17,.78));box-shadow:0 15px 30px rgba(0,0,0,.25)}.home-live-switch button:focus-visible{outline:2px solid #fff;outline-offset:2px}.home-live-switch button.active{border-color:var(--channel-accent);background:linear-gradient(150deg,color-mix(in srgb,var(--channel-accent) 28%,#17162c),rgba(17,14,38,.94));box-shadow:0 12px 30px color-mix(in srgb,var(--channel-accent) 25%,transparent),inset 0 1px rgba(255,255,255,.15)}.home-live-switch button.active .home-live-switch__icon{color:#090b16;background:var(--channel-accent);border-color:var(--channel-accent);box-shadow:0 8px 24px color-mix(in srgb,var(--channel-accent) 36%,transparent)}
.live-apps{margin-bottom:24px}.live-apps-heading{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:16px}.live-apps-heading span{color:#a99cff;font-size:10px;font-weight:950;letter-spacing:.15em}.live-apps-heading h2{margin:5px 0 0;font-size:clamp(30px,4vw,50px);letter-spacing:-.05em}.live-apps-heading>a{font-size:13px;font-weight:900;text-decoration:none}.live-app-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.live-app-card{position:relative;isolation:isolate;min-height:400px;overflow:hidden;padding:clamp(24px,4vw,44px);display:flex;flex-direction:column;justify-content:flex-end;border:1px solid rgba(255,255,255,.16);border-radius:28px;box-shadow:0 22px 60px rgba(0,0,0,.34)}.live-app-card:before{content:"";position:absolute;inset:0;z-index:-2}.live-app-card:after{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(3,6,14,.04),rgba(3,6,14,.9) 78%)}.verse-card:before{background:radial-gradient(circle at 75% 20%,rgba(243,218,143,.32),transparent 23%),linear-gradient(135deg,#071d14,#175137 58%,#596720)}.french-card:before{background:radial-gradient(circle at 77% 17%,rgba(255,255,255,.24),transparent 22%),linear-gradient(135deg,#061d4e,#173c9e 55%,#d92549)}.live-app-card__art{position:absolute;right:7%;top:12%;font-size:clamp(80px,13vw,175px);opacity:.2}.live-app-label{color:#d7d2ff;font-size:10px;font-weight:950;letter-spacing:.15em}.verse-card .live-app-label{color:#9be9b2}.live-app-card blockquote,.live-app-card h3{max-width:760px;margin:15px 0 10px;font-size:clamp(34px,5vw,64px);line-height:1;letter-spacing:-.045em;font-weight:900}.live-app-card blockquote{font-family:Georgia,serif;font-weight:500}.live-app-card p{margin:0;color:#d0d5e2;font-size:16px}.live-app-card .translation{font-size:22px;font-weight:800;color:#fff}.live-app-card .pronunciation{margin-top:7px;font-size:14px}.live-app-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.live-app-actions a,.live-app-actions button{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:0 16px;border:1px solid rgba(255,255,255,.27);border-radius:999px;color:#fff;background:rgba(255,255,255,.09);font:900 12px/1 inherit;text-decoration:none;cursor:pointer}.live-app-actions a:first-child{background:rgba(255,255,255,.15)}
.live-apps-heading__actions{display:flex;align-items:center;gap:14px}.live-apps-heading__actions>a{font-size:13px;font-weight:900;text-decoration:none}.live-app-controls{display:flex;gap:8px}.live-app-controls button{display:grid;place-items:center;width:44px;height:44px;border:1px solid rgba(255,255,255,.19);border-radius:50%;background:rgba(12,15,31,.86);color:#fff;font:900 18px/1 inherit;cursor:pointer;box-shadow:0 10px 28px rgba(0,0,0,.22);transition:transform .2s ease,border-color .2s ease,background .2s ease}.live-app-controls button:hover,.live-app-controls button:focus-visible{transform:translateY(-2px);border-color:#b7a8ff;background:#6c55ef}.live-app-controls button:disabled{cursor:default;opacity:.35;transform:none}.live-app-grid{display:flex;gap:18px;overflow-x:auto;overscroll-behavior-inline:contain;scroll-snap-type:x mandatory;scrollbar-width:none;padding:4px max(0px,calc((100% - 1180px)/2)) 28px}.live-app-grid::-webkit-scrollbar{display:none}.live-app-grid:focus-visible{outline:2px solid #a99cff;outline-offset:7px;border-radius:22px}.live-app-card{flex:0 0 clamp(310px,67vw,760px);scroll-snap-align:start;scroll-snap-stop:always;min-height:470px;transform:translateZ(0);transition:transform .35s ease,border-color .35s ease,box-shadow .35s ease}.live-app-card.is-current{border-color:rgba(194,181,255,.62);box-shadow:0 30px 80px rgba(0,0,0,.46),0 0 0 1px rgba(169,156,255,.17)}.live-app-card:not(.is-current){transform:scale(.975)}.live-app-progress{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:-7px}.live-app-progress button{width:7px;height:7px;padding:0;border:0;border-radius:999px;background:rgba(255,255,255,.26);cursor:pointer;transition:width .25s ease,background .25s ease}.live-app-progress button.active{width:28px;background:linear-gradient(90deg,#a996ff,#ef5da8)}.live-app-progress button:focus-visible{outline:2px solid #fff;outline-offset:3px}
.home-shortcuts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:45px}.home-shortcuts a{display:grid;grid-template-columns:auto 1fr;column-gap:12px;align-items:center;padding:18px;border:1px solid rgba(255,255,255,.14);border-radius:18px;background:rgba(10,14,29,.72);text-decoration:none}.home-shortcuts span{grid-row:1/3;font-size:29px}.home-shortcuts strong{font-size:15px}.home-shortcuts small{margin-top:3px;color:#aeb5c9}
@media(max-width:1100px){.home-live-player{min-height:0;aspect-ratio:16/9}.home-live-switch{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media(max-width:800px){.home-live-stage{width:calc(100vw - 12px);border-radius:23px;margin-bottom:32px}.home-live-stage__inner{padding:14px}.home-live-stage__top{align-items:flex-start;flex-direction:column;margin-bottom:14px}.home-live-actions{width:100%}.home-live-button{flex:1}.home-live-player{aspect-ratio:16/9;border-radius:16px}.home-live-meta{align-items:flex-start;flex-direction:column}.home-live-switch{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 1px 8px}.home-live-switch button{min-width:102px;scroll-snap-align:start}.live-apps-heading{align-items:flex-start;flex-direction:column}.live-apps-heading__actions{width:100%;justify-content:space-between}.live-app-grid{width:calc(100vw - 16px);margin-left:calc((100% - 100vw)/2 + 8px);padding-inline:4px}.live-app-card{flex-basis:min(88vw,560px);min-height:390px}.home-shortcuts{grid-template-columns:1fr}}
.games-card:before{background:radial-gradient(circle at 77% 17%,rgba(255,203,103,.34),transparent 24%),linear-gradient(135deg,#24120b,#7c2f20 55%,#e88a25)}.games-card .live-app-label{color:#ffd17f}.games-card .live-app-card__art{filter:drop-shadow(0 18px 32px rgba(0,0,0,.28))}
.game-demo-card:before{background:radial-gradient(circle at 78% 18%,color-mix(in srgb,var(--game-color) 38%,transparent),transparent 25%),linear-gradient(135deg,#07101a,color-mix(in srgb,var(--game-color) 42%,#111827) 58%,#111820)}
.game-demo-card .live-app-label{color:color-mix(in srgb,var(--game-color) 48%,#fff)}
.game-demo-card .live-app-card__art{opacity:.42;filter:drop-shadow(0 20px 38px color-mix(in srgb,var(--game-color) 36%,transparent))}
.game-demo-card .live-app-actions a:first-child{border-color:color-mix(in srgb,var(--game-color) 62%,#fff);background:color-mix(in srgb,var(--game-color) 32%,rgba(255,255,255,.12))}
.casino-card:before{background:radial-gradient(circle at 77% 17%,rgba(255,216,109,.3),transparent 23%),linear-gradient(135deg,#160b25,#54205c 55%,#a42e65)}.casino-card .live-app-label{color:#ffd86d}.casino-card h3{font-size:clamp(34px,5vw,64px)}
.math-card:before{background:radial-gradient(circle at 78% 18%,rgba(91,219,69,.3),transparent 24%),linear-gradient(135deg,#06172d,#0a4c82 58%,#178b73)}.math-card .live-app-label{color:#8ff0b3}.coding-card:before{background:radial-gradient(circle at 78% 18%,rgba(53,214,255,.28),transparent 24%),linear-gradient(135deg,#16092c,#51269a 56%,#087f9b)}.coding-card .live-app-label{color:#95eaff}.coding-card .live-app-card__art{font-size:clamp(60px,9vw,124px);font-weight:950;letter-spacing:-.12em}
@media(min-width:1051px){.live-app-card{flex-basis:min(62vw,760px)}}
@media(max-width:480px){.home-live-stage h2{font-size:34px}.home-live-stage__top p{font-size:13px}.home-live-actions{display:grid;grid-template-columns:1fr 1fr}.home-live-button{padding:0 10px}.home-live-player{aspect-ratio:16/10}.home-live-clock{display:none}.live-app-card{min-height:315px;padding:23px}.live-app-card blockquote,.live-app-card h3{font-size:36px}}
html[data-theme="light"] .home-live-stage,html[data-theme="light"] .live-app-card{color:#fff}html[data-theme="light"] .home-shortcuts a{background:rgba(255,255,255,.82);border-color:rgba(26,31,54,.14)}html[data-theme="light"] .home-shortcuts small{color:#5e667a}
</style>

<script>
(function(){
 const frame=document.getElementById('homeBeyondTvPlayer');
 const stage=document.querySelector('.home-live-stage');
 if(!frame||!stage)return;
 const buttons=[...stage.querySelectorAll('[data-home-channel]')];
 const name=document.getElementById('homeLiveChannelName');
 const now=document.getElementById('homeLiveNow');
 const open=document.getElementById('homeLiveOpen');
 const kicker=document.getElementById('homeLiveKicker');
 const heading=document.getElementById('homeLiveHeading');
 const description=document.getElementById('homeLiveDescription');
 const clock=stage.querySelector('.home-live-clock');
 const EPISODE_SYNC_MS=30*60*1000;
 const LONG_FORM_SYNC_MS=2*60*60*1000;
 const longFormChannels=new Set(['space','ancient','cinema','health','comedy','family']);
 let syncTimer=0;
 let nextSyncAt=0;
 const clean=value=>String(value||'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
 function syncInterval(button){
   const explicit=Number(button?.dataset.syncMs||0);
   if(Number.isFinite(explicit)&&explicit>=60000)return explicit;
   return longFormChannels.has(button?.dataset.homeChannel||'')?LONG_FORM_SYNC_MS:EPISODE_SYNC_MS;
 }
 function updateClock(button){
   if(!clock)return;
   clock.textContent=(syncInterval(button)===LONG_FORM_SYNC_MS?'Long-form sync · every 2 hours':'Episode sync · every 30 minutes')+' · America/Vancouver';
 }
 function render(button,state={}){
   stage.dataset.channelTheme=button.dataset.homeChannel||'cartoons';
   const channelName=button.dataset.channelName||'Beyond TV';
   const channelNumber=button.dataset.channelNumber||'';
   const current=state.current||state.playing||{};
   const next=state.next||{};
   const block=current.title||state.episode_title||button.dataset.now||state.programme||'Live now';
   const lineup=current.lineup||(!current.title?button.dataset.now:'')||state.episode_title||'';
   const upNext=next.title||button.dataset.next||'';
   const iconName=button.dataset.iconName||'tv';
   const embed=state.player_url||state.embed_url||button.dataset.embed||'';
   if(embed){
     const withApi=/youtube(?:-nocookie)?\.com/.test(embed)&&!embed.includes('enablejsapi=1')?embed+(embed.includes('?')?'&':'?')+'enablejsapi=1':embed;
     const nextSrc=new URL(withApi,window.location.href).href;
     if(frame.src!==nextSrc)frame.src=nextSrc;
   }
   const sourceKey=String(state.source_key||current.source_key||'');
   if(sourceKey)button.dataset.streamKey=sourceKey;
   name.textContent=channelName;
   now.textContent=block;
   kicker.innerHTML='<i></i> Beyond TV · Channel '+clean(channelNumber)+' live';
   heading.textContent=channelName+' is playing now.';
   description.innerHTML='<strong><span class="home-live-description__icon" aria-hidden="true"><i data-lucide="'+clean(iconName)+'"></i></span>'+clean(block)+'</strong>'+(lineup&&lineup!==block?' · '+clean(lineup):'')+(upNext?' · Up next: '+clean(upNext):'')+' · Vancouver time';
   if(window.lucide)window.lucide.createIcons({attrs:{'stroke-width':2}});
   open.href=button.dataset.open||'/beyond-tv/';
 }
 function schedule(button){
   if(syncTimer)window.clearTimeout(syncTimer);
   const delay=syncInterval(button);
   nextSyncAt=Date.now()+delay;
   syncTimer=window.setTimeout(()=>tune(button),delay);
   updateClock(button);
 }
 async function tune(button,{refreshState=true}={}){
   buttons.forEach(item=>{
     const isActive=item===button;
     item.classList.toggle('active',isActive);
     item.setAttribute('aria-pressed',String(isActive));
   });
   render(button);
   const playerOwnsSchedule=String(button.dataset.embed||'').startsWith('/beyond-tv/embed-player.php');
   if(!refreshState||playerOwnsSchedule||!button.dataset.endpoint){schedule(button);return;}
   const requestedEndpoint=button.dataset.endpoint;
   try{
     const response=await fetch(requestedEndpoint,{cache:'default'});
     if(!response.ok)throw new Error('HTTP '+response.status);
     const data=await response.json();
     if(button.classList.contains('active'))render(button,data.state||data);
   }catch(error){console.warn('Beyond TV channel refresh unavailable',error);}
   finally{if(button.classList.contains('active'))schedule(button);}
 }
 buttons.forEach(button=>button.addEventListener('click',()=>tune(button)));
 window.addEventListener('message',event=>{
   if(event.origin!==window.location.origin||event.source!==frame.contentWindow)return;
   if(event.data?.type!=='beyond-tv:state'||typeof event.data.slug!=='string')return;
   const active=stage.querySelector('[data-home-channel].active');
   if(active?.dataset.channelSlug===event.data.slug)render(active,event.data.state||{});
 });
 const initial=stage.querySelector('[data-home-channel].active');
 if(initial)tune(initial,{refreshState:false});
 document.addEventListener('visibilitychange',()=>{
   if(document.hidden||!nextSyncAt||Date.now()<nextSyncAt)return;
   const active=stage.querySelector('[data-home-channel].active');
   if(active)tune(active);
 });
})();
window.addEventListener('DOMContentLoaded',()=>{
 if(window.lucide)window.lucide.createIcons({attrs:{'stroke-width':2}});
});
(function(){
 const carousel=document.querySelector('[data-home-market-carousel]');
 const previous=document.querySelector('[data-home-market-prev]');
 const next=document.querySelector('[data-home-market-next]');
 const position=document.querySelector('[data-home-market-position]');
 if(!carousel||!previous||!next||!position)return;
 const cards=[...carousel.querySelectorAll('.home-market-card')];
 let current=0;
 let frame=0;

 cards.forEach((card,index)=>{
   card.setAttribute('role','group');
   card.setAttribute('aria-roledescription','slide');
   card.setAttribute('aria-label',`${index+1} of ${cards.length}`);
 });

 function update(){
   cards.forEach((card,index)=>card.classList.toggle('is-current',index===current));
   position.textContent=String(current+1);
   previous.disabled=current===0;
   next.disabled=current===cards.length-1;
 }

 function goTo(index){
   current=Math.max(0,Math.min(cards.length-1,index));
   cards[current].scrollIntoView({behavior:'smooth',block:'nearest',inline:'start'});
   update();
 }

 previous.addEventListener('click',()=>goTo(current-1));
 next.addEventListener('click',()=>goTo(current+1));
 carousel.addEventListener('keydown',event=>{
   if(event.key==='ArrowLeft'){event.preventDefault();goTo(current-1);}
   if(event.key==='ArrowRight'){event.preventDefault();goTo(current+1);}
   if(event.key==='Home'){event.preventDefault();goTo(0);}
   if(event.key==='End'){event.preventDefault();goTo(cards.length-1);}
 });
 carousel.addEventListener('scroll',()=>{
   if(frame)return;
   frame=requestAnimationFrame(()=>{
     frame=0;
     const left=carousel.getBoundingClientRect().left;
     current=cards.reduce((best,card,index)=>{
       const distance=Math.abs(card.getBoundingClientRect().left-left);
       const bestDistance=Math.abs(cards[best].getBoundingClientRect().left-left);
       return distance<bestDistance?index:best;
     },current);
     update();
   });
 },{passive:true});

 carousel.querySelectorAll('[data-market-save]').forEach(button=>{
   button.addEventListener('click',()=>{
     const saved=button.getAttribute('aria-pressed')!=='true';
     button.setAttribute('aria-pressed',saved?'true':'false');
     button.textContent=saved?'♥':'♡';
   });
 });
 update();
})();
(function(){
 const listen=document.getElementById('homeFrenchListen');
 if(!listen)return;
 listen.addEventListener('click',()=>{
   if(!('speechSynthesis' in window))return;
   window.speechSynthesis.cancel();
   const utterance=new SpeechSynthesisUtterance(listen.dataset.speak||'');
   utterance.lang='fr-FR'; utterance.rate=.88;
   window.speechSynthesis.speak(utterance);
 });
})();
(function(){
 const carousel=document.querySelector('[data-live-app-carousel]');
 const progress=document.querySelector('[data-live-app-progress]');
 const previous=document.querySelector('[data-live-app-prev]');
 const next=document.querySelector('[data-live-app-next]');
 if(!carousel||!progress||!previous||!next)return;
 const cards=[...carousel.querySelectorAll('.live-app-card')];
 let current=0;

 const dots=cards.map((card,index)=>{
   card.setAttribute('role','group');
   card.setAttribute('aria-roledescription','slide');
   card.setAttribute('aria-label',`${index+1} of ${cards.length}`);
   const dot=document.createElement('button');
   dot.type='button';
   dot.setAttribute('aria-label',`Show app experience ${index+1}`);
   dot.addEventListener('click',()=>goTo(index));
   progress.appendChild(dot);
   return dot;
 });

 function goTo(index,behavior='smooth'){
   current=Math.max(0,Math.min(cards.length-1,index));
   cards[current].scrollIntoView({behavior,block:'nearest',inline:'start'});
   update();
 }

 function update(){
   cards.forEach((card,index)=>card.classList.toggle('is-current',index===current));
   dots.forEach((dot,index)=>{
     dot.classList.toggle('active',index===current);
     dot.setAttribute('aria-current',index===current?'true':'false');
   });
   previous.disabled=current===0;
   next.disabled=current===cards.length-1;
 }

 previous.addEventListener('click',()=>goTo(current-1));
 next.addEventListener('click',()=>goTo(current+1));
 carousel.addEventListener('keydown',event=>{
   if(event.key==='ArrowLeft'){event.preventDefault();goTo(current-1);}
   if(event.key==='ArrowRight'){event.preventDefault();goTo(current+1);}
 });

 let frame=0;
 carousel.addEventListener('scroll',()=>{
   if(frame)return;
   frame=requestAnimationFrame(()=>{
     frame=0;
     const left=carousel.getBoundingClientRect().left;
     current=cards.reduce((best,card,index)=>{
       const distance=Math.abs(card.getBoundingClientRect().left-left);
       const bestDistance=Math.abs(cards[best].getBoundingClientRect().left-left);
       return distance<bestDistance?index:best;
     },current);
     update();
   });
 },{passive:true});

 update();
})();
(function(){
 const picker=document.getElementById('homeCurrency');
 if(!picker)return;
 const supported=['USD','CAD','BITS'];
 const root=document.documentElement;

 function applyCurrency(value,announce=false){
   const currency=supported.includes(value)?value:'CAD';
   root.dataset.currency=currency;
   picker.value=currency;
   try{localStorage.setItem('beyond-currency',currency);}catch(error){}
   document.cookie=`beyond_currency=${encodeURIComponent(currency)}; path=/; max-age=31536000; SameSite=Lax`;
   window.BeyondCurrency={
     code:currency,
     label:currency==='BITS'?'bit$':currency,
     symbol:currency==='USD'?'US$':currency==='CAD'?'CA$':'bit$'
   };
   if(announce)window.dispatchEvent(new CustomEvent('beyond:currencychange',{detail:window.BeyondCurrency}));
 }

 applyCurrency(root.dataset.currency||'CAD');
 picker.addEventListener('change',()=>applyCurrency(picker.value,true));
})();
</script>
<script src="/beyond-tv/assets/js/app.js?v=3.0.2"></script>
</main>
<footer class="footer wrap">
    <div><a class="brand" href="./">BEYOND <span>OS</span></a><p>The connected imagination ecosystem.</p><p class="copyright">© 2026 Beyond Imagination Corp.</p></div>
    <div><h4>DISCOVER</h4><a href="app-store/">App Store</a><a href="release-notes.php">What’s New</a><a href="beyond-id/dashboard/wallet.php">Wallet</a><a href="academy/">Learn</a><a href="beyond-tv/">Beyond TV</a></div>
    <div><h4>COMPANY</h4><a href="about.php">About</a><a href="blog.php">Blog</a><a href="coding-school/">Career Pathways</a><a href="contact.php">Contact</a></div>
    <div><h4>SUPPORT</h4><a href="help-center.php">Help Center</a><a href="legal/privacy.php">Privacy Policy</a><a href="legal/terms.php">Terms of Service</a></div>
    <div><h4>FOLLOW US</h4><a href="https://www.instagram.com/beyondimaginationtech/" target="_blank" rel="noopener noreferrer">Instagram @beyondimaginationtech</a></div>
</footer>
<script>
(function(){
const icons={
health:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 42V15a4 4 0 0 1 4-4h20a4 4 0 0 1 4 4v27M7 42h34M18 42V31h12v11M20 20h8M24 16v8"/></svg>',
education:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 42V18l17-10 17 10v24M4 42h40M14 24h5v5h-5zM29 24h5v5h-5zM20 42V33h8v9M13 16h22"/></svg>',
wallet:'<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 14h31a4 4 0 0 1 4 4v22H7a4 4 0 0 1-4-4V12a4 4 0 0 1 4-4h27v6M31 25h11v9H31a4 4 0 0 1 0-9Z"/></svg>',
entertainment:'<svg viewBox="0 0 48 48" aria-hidden="true"><rect x="6" y="10" width="36" height="28" rx="5"/><path d="m20 18 11 6-11 6V18ZM16 43h16"/></svg>'
};
const names={health:'HEALTH',education:'EDUCATION',wallet:'WALLET',entertainment:'ENTERTAINMENT'},actions={health:'LIVE',education:'LEARN',wallet:'EARN',entertainment:'EXPLORE'},planetClasses={health:'.ph',education:'.pe',wallet:'.pf',entertainment:'.px'};
Object.keys(icons).forEach(function(id){
const planet=document.querySelector(planetClasses[id]);
if(planet)planet.innerHTML='<span><i class="division-icon">'+icons[id]+'</i>'+names[id]+'<em>'+actions[id]+'</em></span>';
const panel=document.querySelector('.world.'+id+' .world-icon');if(panel)panel.innerHTML=icons[id];
});
const orbit=document.querySelector('.orbit');if(orbit){const copy=document.createElement('div');copy.className='orbit-copy';copy.innerHTML='<strong>Live &bull; Learn &bull; Earn &bull; Explore</strong><span>Every Possibility</span>';orbit.appendChild(copy);}
})();
</script>
<script src="assets/js/pwa-install.js" defer></script><script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
