<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/web-app.php';

/** @return array{label:string,iso:string} */
function db_live_next(array $schedule): array
{
    $zone = new DateTimeZone((string)$schedule['timezone']);
    $now = new DateTimeImmutable('now', $zone);
    for ($offset = 0; $offset < 8; $offset++) {
        $candidate = $now->setTime((int)$schedule['hour'], (int)$schedule['minute'])->modify('+' . $offset . ' days');
        if (in_array((int)$candidate->format('N'), $schedule['days'], true) && $candidate > $now) {
            return [
                'label' => $candidate->format('D, M j · g:i A T'),
                'iso' => $candidate->format(DateTimeInterface::ATOM),
            ];
        }
    }
    return ['label' => 'See official schedule', 'iso' => ''];
}

$services = [
    'Christian Live Services' => [[
        'name' => 'Woodvale Pentecostal Church',
        'tradition' => 'Christian · Pentecostal',
        'city' => 'Ottawa, Ontario',
        'timezone' => 'America/Toronto',
        'schedule' => ['days'=>[7], 'hour'=>9, 'minute'=>0, 'timezone'=>'America/Toronto'],
        'schedule_note' => 'Sunday service; a second service follows at 11:15 AM.',
        'embed_url' => 'https://live.woodvale.ca/',
        'official_url' => 'https://live.woodvale.ca/',
    ]],
    'Jewish Services & Learning' => [[
        'name' => 'Holy Blossom Temple',
        'tradition' => 'Jewish · Reform',
        'city' => 'Toronto, Ontario',
        'timezone' => 'America/Toronto',
        'schedule' => ['days'=>[1,2,3,4,5], 'hour'=>7, 'minute'=>30, 'timezone'=>'America/Toronto'],
        'schedule_note' => 'Weekday Shacharit; Shabbat and holiday services have their own schedule.',
        'embed_url' => '',
        'official_url' => 'https://holyblossom.org/holy-blossom-temple-livestream/',
    ]],
    'Muslim Live Services' => [[
        'name' => 'As-Salam Mosque & Community Centre',
        'tradition' => 'Muslim · Sunni',
        'city' => 'Regina, Saskatchewan',
        'timezone' => 'America/Regina',
        'schedule' => ['days'=>[5], 'hour'=>13, 'minute'=>15, 'timezone'=>'America/Regina'],
        'schedule_note' => 'Friday Jumu’ah khutbah; a second khutbah is scheduled at 2:00 PM.',
        'embed_url' => '',
        'official_url' => 'https://www.iaosregina.com/',
    ]],
];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Live Services | Daily Breath</title><?= dailybreath_web_head('Live Services | Daily Breath') ?>
<style>
:root{--forest:#123927;--ink:#173022;--cream:#f6f4ee;--gold:#d7aa51;--line:#cddbd0}*{box-sizing:border-box}body{margin:0;color:var(--ink);background:radial-gradient(circle at 8% 0,#dcebdd,transparent 31%),linear-gradient(145deg,#f8faf6,#eef5ed);font:16px/1.55 Inter,system-ui,sans-serif}.shell{width:min(1120px,calc(100% - 34px));margin:auto;padding:42px 0 112px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px}.brand{display:flex;align-items:center;gap:11px;font-weight:900;text-decoration:none}.brand img{width:42px;height:42px;border-radius:13px}.back{padding:9px 13px;border:1px solid var(--line);border-radius:999px;color:var(--forest);font-size:13px;font-weight:800;text-decoration:none}.hero{max-width:740px;margin:48px 0 28px}.eyebrow{color:#47745a;font-size:12px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}.hero h1{margin:8px 0 10px;font:500 clamp(42px,7vw,70px)/1 Georgia,serif;letter-spacing:-.04em}.hero p{max-width:650px;margin:0;color:#526d5b;font-size:17px}.notice{margin:22px 0 34px;padding:14px 16px;border:1px solid #dce4d9;border-radius:16px;background:#fffffff0;color:#49604e;font-size:13px}.group{margin:34px 0}.group h2{margin:0 0 13px;font:500 29px/1.15 Georgia,serif}.service-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.service{overflow:hidden;border:1px solid var(--line);border-radius:23px;background:#fff;box-shadow:0 16px 36px #315b3a12}.service-main{padding:22px}.tradition{display:inline-flex;padding:6px 9px;border-radius:999px;color:#245a3a;background:#e2f1e5;font-size:11px;font-weight:850}.service h3{margin:15px 0 5px;font-size:21px;line-height:1.18}.meta{margin:0;color:#5d7262;font-size:13px}.next{margin:20px 0 6px;color:#315b42;font-size:12px;font-weight:900;letter-spacing:.07em;text-transform:uppercase}.next strong{display:block;margin-top:4px;color:var(--ink);font-size:17px;letter-spacing:0;text-transform:none}.schedule-note{margin:0;color:#607665;font-size:13px}.actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:20px}.button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 13px;border:0;border-radius:12px;color:#fff;background:var(--forest);font:800 13px Inter,system-ui;text-decoration:none;cursor:pointer}.button.secondary{color:var(--forest);background:#eff5ef}.player{padding:0 22px 22px}.player[hidden]{display:none}.player iframe{display:block;width:100%;aspect-ratio:16/9;border:1px solid var(--line);border-radius:14px;background:#0a2114}.nearby{padding:26px;border:1px solid #c9d9cc;border-radius:24px;background:linear-gradient(135deg,#143f2a,#245d3e);color:#fff}.nearby h2{margin:0 0 7px;font:500 29px Georgia,serif}.nearby p{max-width:700px;margin:0;color:#dcebdd}.nearby .button{margin-top:18px;color:#173f2c;background:#f1ce80}.nearby-results{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:18px}.nearby-results a{padding:12px;border:1px solid #ffffff2c;border-radius:14px;color:#fff;background:#ffffff12;text-decoration:none;font-size:13px;font-weight:800}.nearby-status{margin-top:12px;color:#dcebdd;font-size:13px}@media(max-width:620px){.shell{padding-top:26px}.top{align-items:flex-start}.hero{margin-top:34px}.nearby-results{grid-template-columns:1fr}}
</style></head><body><main class="shell">
<header class="top"><a class="brand" href="index.php"><img src="assets/icons/dailybreath-mark-v2.png" alt=""><span>Daily Breath</span></a><a class="back" href="index.php">Home →</a></header>
<section class="hero"><span class="eyebrow">Daily Breath TV</span><h1>Live Services</h1><p>Join trusted public faith services and learning from their official sources. Schedules remain with each organization and can change for holidays or local events.</p></section>
<p class="notice">Daily Breath lists these organizations for discovery. Each stream remains operated by its organization. If an embedded player is unavailable, use <strong>Open official stream</strong>.</p>
<?php foreach ($services as $heading => $items): ?><section class="group"><h2><?= e($heading) ?></h2><div class="service-grid"><?php foreach ($items as $service): $next = db_live_next($service['schedule']); ?><article class="service"><div class="service-main"><span class="tradition"><?= e($service['tradition']) ?></span><h3><?= e($service['name']) ?></h3><p class="meta"><?= e($service['city']) ?> · <?= e(str_replace('America/', '', $service['timezone'])) ?> time</p><p class="next">Next live time<strong><time datetime="<?= e($next['iso']) ?>"><?= e($next['label']) ?></time></strong></p><p class="schedule-note"><?= e($service['schedule_note']) ?></p><div class="actions"><?php if ($service['embed_url'] !== ''): ?><button class="button" type="button" data-open-player>Watch live</button><?php endif; ?><a class="button secondary" href="<?= e($service['official_url']) ?>" target="_blank" rel="noopener noreferrer">Open official stream ↗</a></div></div><?php if ($service['embed_url'] !== ''): ?><div class="player" hidden><iframe title="<?= e($service['name']) ?> official live player" src="<?= e($service['embed_url']) ?>" loading="lazy" allow="autoplay; fullscreen" referrerpolicy="strict-origin-when-cross-origin"></iframe></div><?php endif; ?></article><?php endforeach; ?></div></section><?php endforeach; ?>
<section class="nearby" aria-labelledby="nearby-title"><h2 id="nearby-title">Nearby Services</h2><p>Use your location only when you choose to search. Daily Breath opens local map searches for churches, synagogues, and mosques; it does not save your location.</p><button class="button" id="find-nearby" type="button">Find nearby services</button><p class="nearby-status" id="nearby-status" role="status">Location is off until you request it.</p><div class="nearby-results" id="nearby-results" hidden></div></section>
</main><script>
document.querySelectorAll('[data-open-player]').forEach(button=>button.addEventListener('click',()=>{const player=button.closest('.service').querySelector('.player');player.hidden=!player.hidden;button.textContent=player.hidden?'Watch live':'Hide player';}));
const nearbyButton=document.getElementById('find-nearby'),nearbyStatus=document.getElementById('nearby-status'),nearbyResults=document.getElementById('nearby-results');nearbyButton.addEventListener('click',()=>{if(!navigator.geolocation){nearbyStatus.textContent='Location is unavailable in this browser.';return}nearbyStatus.textContent='Getting your location…';navigator.geolocation.getCurrentPosition(position=>{const point=`${position.coords.latitude.toFixed(4)},${position.coords.longitude.toFixed(4)}`,types=['church','synagogue','mosque'];nearbyResults.replaceChildren(...types.map(type=>{const link=document.createElement('a');link.href=`https://www.google.com/maps/search/${encodeURIComponent(type+' near '+point)}`;link.target='_blank';link.rel='noopener noreferrer';link.textContent=`Nearby ${type}s ↗`;return link}));nearbyResults.hidden=false;nearbyStatus.textContent='Local map searches are ready. Your coordinates were used only in these links.';},()=>{nearbyStatus.textContent='We could not access your location. Enable it in your browser or search your city in Maps.';},{enableHighAccuracy:false,timeout:10000,maximumAge:300000});});
</script><?= dailybreath_web_scripts() ?><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
