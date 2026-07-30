<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/public-channel-catalog.php';
require_once __DIR__ . '/includes/classic-schedule.php';
require_once __DIR__ . '/includes/beyond-cartoons-schedule.php';
require_once __DIR__ . '/includes/eight-channel-guide.php';
if (!empty($_SESSION['user_id'])) {
    beyond_track_app('Beyond TV');
}
$catalogue = json_decode((string)file_get_contents(__DIR__ . '/data/channels.json'), true) ?: [];
$featured = json_decode((string)file_get_contents(__DIR__ . '/data/featured-channels.json'), true) ?: [];
$channels = beyond_tv_public_channels($catalogue, $featured);
$classicState = beyond_classic_schedule_state();
$cartoonState = beyond_cartoons_schedule_state();
$guideChannels = beyond_tv_eight_channel_guide($classicState, $cartoonState);
$slots = range(0, 22, 2);
$timezone = new DateTimeZone('America/Vancouver');
$today = new DateTimeImmutable('today', $timezone);
$currentHour = (int)(new DateTimeImmutable('now', $timezone))->format('G');
?>
<!doctype html>
<html lang="en">
<head>
<script>(function(){try{const t=localStorage.getItem("beyond-tv-theme");document.documentElement.dataset.tvTheme=["dark","light","sunset"].includes(t)?t:"sunset"}catch(e){document.documentElement.dataset.tvTheme="sunset"}})();</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#401532">
<title>Full <?=count($channels)?>-Channel Guide | Beyond TV</title>
<meta name="description" content="Browse the complete Beyond TV schedule across every free live channel.">
<link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.1">
</head>
<body class="tv-app">
<div class="ambient-orb ambient-orb-one"></div><div class="ambient-orb ambient-orb-two"></div>
<?php include 'partials/header.php'; ?>
<main class="page shell guide-page full-guide-page">
  <span class="kicker">FULL 24-HOUR SCHEDULE · AMERICA/VANCOUVER</span>
  <h1>The complete Beyond TV guide.</h1>
  <p class="lead">See what is playing now and what comes next across all <?=count($channels)?> free channels. Scroll across the timeline to explore the full day.</p>
  <div class="full-guide-meta"><strong><?=htmlspecialchars((string)$classicState['date_label'])?></strong><span><i aria-hidden="true"></i> Current time highlighted</span></div>
  <div class="epg" aria-label="Complete Beyond TV 24-hour guide">
    <div class="epg-grid">
      <div class="epg-cell epg-time epg-corner">Channel</div>
      <?php foreach($slots as $slot): ?>
        <div class="epg-cell epg-time<?=$currentHour >= $slot && $currentHour < $slot + 2 ? ' is-current-time':''?>"><?=$today->setTime($slot,0)->format('g:i A')?></div>
      <?php endforeach; ?>
      <?php foreach($guideChannels as $number=>$guideChannel): ?>
        <a class="epg-cell epg-channel" href="/beyond-tv/channel.php?slug=<?=urlencode((string)$guideChannel['slug'])?>"><span><?=htmlspecialchars((string)$guideChannel['icon'])?></span><div><strong>CH <?=str_pad((string)($number+1),2,'0',STR_PAD_LEFT)?> · <?=htmlspecialchars((string)$guideChannel['name'])?></strong><small>Free live library</small></div></a>
        <?php foreach($slots as $slot):
          $block = beyond_tv_guide_block($guideChannel['rows'], $slot);
          $isNow = $currentHour >= (int)($block['start'] ?? 0) && $currentHour < (int)($block['end'] ?? 0);
        ?>
          <a class="epg-cell epg-program<?=$isNow?' current':''?>" href="/beyond-tv/channel.php?slug=<?=urlencode((string)$guideChannel['slug'])?>"><strong><?=htmlspecialchars((string)(($block['icon'] ?? '▶').' '.($block['title'] ?? 'Beyond TV')))?></strong><small><?=htmlspecialchars((string)($block['lineup'] ?? 'Curated presentation'))?></small></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
</main>
<?php include 'partials/footer.php'; ?>
<script src="/beyond-tv/assets/js/app.js?v=3.0.1"></script>
<script src="/assets/js/visitor-analytics.js" defer></script>
</body>
</html>
