<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/public-channel-catalog.php';
if (!empty($_SESSION['user_id'])) {
    beyond_track_app('Beyond TV');
}
$catalogue = json_decode((string)file_get_contents(__DIR__ . '/data/channels.json'), true) ?: [];
$featured = json_decode((string)file_get_contents(__DIR__ . '/data/featured-channels.json'), true) ?: [];
$channels = beyond_tv_public_channels($catalogue, $featured);
?>
<!doctype html><html lang="en"><head><script>(function(){try{const t=localStorage.getItem("beyond-tv-theme");document.documentElement.dataset.tvTheme=["dark","light","sunset"].includes(t)?t:"sunset"}catch(e){document.documentElement.dataset.tvTheme="sunset"}})();</script><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#401532"><title><?=count($channels)?> Channels | Beyond TV</title><link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.0"></head><body class="tv-app"><div class="ambient-orb ambient-orb-one"></div><div class="ambient-orb ambient-orb-two"></div><?php include 'partials/header.php'; ?><main class="page shell guide-page"><span class="kicker">THE COMPLETE LINEUP</span><h1><?=count($channels)?> channels. One place.</h1><p class="lead">Animation, movies, comedy, family, learning, wellness, official trailers, and sports—free to explore without an account.</p><div class="guide"><?php foreach($channels as $i=>$c): ?><a href="channel.php?slug=<?= urlencode($c['slug']) ?>"><time>CH <?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></time><span class="guide-icon"><?=htmlspecialchars((string)($c['icon']??'📺'))?></span><strong><?= htmlspecialchars($c['name']) ?></strong><span><?= htmlspecialchars($c['now']) ?><?php if (!empty($c['weekend'])): ?> · <?= htmlspecialchars((string)($c['weekend_label'] ?? 'This weekend')) ?><?php endif; ?></span><b><?=!empty($c['live'])?'LIVE':'WATCH'?></b></a><?php endforeach; ?></div></main><?php include 'partials/footer.php'; ?><script src="/beyond-tv/assets/js/app.js?v=3.0.0"></script><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
