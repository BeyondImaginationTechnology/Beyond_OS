<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/includes/anime-schedule.php';
require_once __DIR__ . '/includes/beyond-cartoons-schedule.php';
require_once __DIR__ . '/includes/classic-schedule.php';
require_once __DIR__ . '/includes/space-schedule.php';
require_once __DIR__ . '/includes/movies-schedule.php';
require_once __DIR__ . '/includes/eight-channel-guide.php';

require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/my-list.php';
if (!empty($_SESSION['user_id'])) { beyond_track_app('Beyond TV'); }
$channels=json_decode((string)file_get_contents(__DIR__.'/data/channels.json'),true)?:[];
$slug=(string)($_GET['slug']??''); $channel=null; foreach($channels as $candidate){if(($candidate['slug']??'')===$slug){$channel=$candidate;break;}}
if(!$channel){http_response_code(404);$channel=['name'=>'Channel not found','description'=>'This channel is unavailable.','icon'=>'📺','gradient'=>'linear-gradient(135deg,#222,#555)','now'=>'Unavailable','access'=>'guest'];}
$signedIn=!empty($_SESSION['user_id']);
$inMyList=$signedIn && $channel && ($channel['name'] ?? '') !== 'Channel not found' ? beyond_tv_my_list_has((int)$_SESSION['user_id'], 'channel', $slug) : false;
$isMoviesScheduled=(($channel['source_type']??'')==='scheduled_movies'); $moviesState=$isMoviesScheduled?beyond_movies_schedule_state():null;
$isAnimeChannel = $slug === 'yugioh-tv';
$channelNow = new DateTimeImmutable('now', new DateTimeZone('America/Vancouver'));
$channelHour = (int)$channelNow->format('G');
$channelDay = (int)$channelNow->format('z');
$animeState = $isAnimeChannel ? beyond_anime_schedule_state($channelNow) : null;
$channelNumber = str_pad((string)($channel['display_number'] ?? $channel['number'] ?? '1'), 2, '0', STR_PAD_LEFT);
$isPreview = ($channel['source_type'] ?? '') === 'placeholder';
$isDailyBreathChannel = $slug === 'mrbeast-tv';
$isSlatePreview = $isPreview && !empty($channel['slate_file']);
$slateFile = basename((string)($channel['slate_file'] ?? ''));
$channelSlate = $slateFile !== '' ? (json_decode((string)@file_get_contents(__DIR__ . '/data/' . $slateFile), true) ?: []) : [];
$channelArt = [
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
    'mrbeast-tv' => ['channel-backgrounds-sprite-v2.png', '33.333% 100%'],
    'redbull-tv' => ['channel-backgrounds-sprite-v2.png', '66.666% 100%'],
    'beyond-mystery' => ['channel-backgrounds-sprite-v2.png', '0% 100%'],
];
$activeChannelArt = $channelArt[$slug] ?? ['channel-backgrounds-sprite.png', '33.333% 0%'];
$isAfterDarkCourage = $slug === 'beyond-after-dark' && ($channelHour >= 22 || in_array($channelHour, [4, 10, 16], true));
$courageEpisodeIndex = (($channelHour + $channelDay) % 13);
$courageEmbedUrl = 'https://www.youtube-nocookie.com/embed/videoseries?list=PLToQtMHGzmM0TTzBSVOfxrU-pgAZm6nlK&index=' . $courageEpisodeIndex . '&autoplay=1&mute=1&playsinline=1&rel=0';
$isClassic=($slug==='classic-cartoon-theater' && (($channel['source_type']??'')!=='placeholder')); $isScheduled=(($channel['source_type']??'')==='scheduled_youtube'); $isSpaceScheduled=(($channel['source_type']??'')==='scheduled_space_youtube'); $scheduledState=$isScheduled?beyond_cartoons_schedule_state():($isSpaceScheduled?beyond_space_schedule_state():null); $isStream=!empty($channel['stream_endpoint']); $isExternal=!empty($channel['external_url']); $isPlaylistLive=(($channel['source_type']??'')==='youtube_playlist_live'); $isYouTubePlaylist=(($channel['source_type']??'')==='youtube_playlist_embed'); $isYouTube=(($channel['source_type']??'')==='youtube_embed'); $youtubeId=preg_replace('/[^A-Za-z0-9_-]/','',(string)($channel['youtube_id']??'')); $youtubeStart=max(0,(int)($channel['youtube_start']??0)); $youtubeAutoplay=!empty($channel['youtube_autoplay']); $youtubeMuted=!empty($channel['youtube_muted']); $youtubeParams=http_build_query(['autoplay'=>$youtubeAutoplay?1:0,'mute'=>$youtubeMuted?1:0,'playsinline'=>1,'rel'=>0,'modestbranding'=>1,'start'=>$youtubeStart]); $classicGuideState=beyond_classic_schedule_state(); $cartoonGuideState=beyond_cartoons_schedule_state(); $allGuideChannels=beyond_tv_eight_channel_guide($classicGuideState,$cartoonGuideState); $channelGuide=null; foreach($allGuideChannels as $g){if($g['slug']===$slug){$channelGuide=$g;break;}}
$youtubePlaylistId=preg_replace('/[^A-Za-z0-9_-]/','',(string)($channel['youtube_playlist_id']??'')); $youtubePlaylistVideoId=preg_replace('/[^A-Za-z0-9_-]/','',(string)($channel['youtube_playlist_video_id']??''));
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#401532"><script>(function(){try{const t=localStorage.getItem('beyond-tv-theme');document.documentElement.dataset.tvTheme=['dark','light','sunset'].includes(t)?t:'sunset';}catch(e){document.documentElement.dataset.tvTheme='sunset';}})();</script><title><?=htmlspecialchars((string)$channel['name'])?> | Beyond TV</title><link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.5"><link rel="stylesheet" href="/beyond-tv/assets/css/video-ads.css?v=1.3.1"><style>.channel-page.shell{width:min(1440px,calc(100% - 28px))}.channel-page .player-shell{grid-template-columns:minmax(0,2.4fr) minmax(300px,.6fr);gap:24px}.channel-page .youtube-player-wrap,.channel-page .provider-player{min-height:0;aspect-ratio:16/9}.channel-page .youtube-player-wrap{border-radius:24px;box-shadow:0 28px 80px rgba(0,0,0,.42)}@media(max-width:980px){.channel-page .player-shell{grid-template-columns:1fr}.channel-page.shell{width:100%}.channel-page .app-back,.channel-page .channel-detail{margin-left:16px;margin-right:16px}.channel-page .youtube-player-wrap,.channel-page .provider-player{border-radius:0}}html[data-tv-theme="light"]{color-scheme:light}html[data-tv-theme="light"] body.tv-app{background:#eef2f8;color:#171a2e}html[data-tv-theme="light"] .channel-detail,html[data-tv-theme="light"] .schedule-mini>div{background:#fff!important;color:#171a2e;border-color:#dfe3ef!important}html[data-tv-theme="light"] .channel-detail p,html[data-tv-theme="light"] .schedule-mini small{color:#657087}.channel-theme-row{display:flex;justify-content:flex-end;margin:12px 16px}.channel-theme-toggle{min-height:42px;padding:0 14px;border-radius:12px;border:1px solid #3a3f55;background:#202438;color:#fff;font-weight:850;cursor:pointer}html[data-tv-theme="light"] .channel-theme-toggle{background:#fff;color:#171a2e;border-color:#cfd5e3}

html[data-tv-theme="sunset"]{color-scheme:dark}html[data-tv-theme="sunset"] .btv,html[data-tv-theme="sunset"] .classic-home,html[data-tv-theme="sunset"] body.tv-app{color:#fff7f2;background:radial-gradient(circle at 50% -10%,#7b2d58 0,#32133f 34%,#151326 72%)}html[data-tv-theme="sunset"] .btv:before{background:linear-gradient(180deg,rgba(78,25,70,.38),rgba(28,15,42,.88) 45%,#111325 80%)}html[data-tv-theme="sunset"] .btv-nav,html[data-tv-theme="sunset"] .classic-nav{background:rgba(40,17,43,.90);border-color:rgba(255,198,166,.18)}html[data-tv-theme="sunset"] .btv-btn,html[data-tv-theme="sunset"] .btv-theme-toggle,html[data-tv-theme="sunset"] .classic-btn,html[data-tv-theme="sunset"] .channel-theme-toggle{background:rgba(106,43,76,.45);border-color:rgba(255,205,176,.25);color:#fff7f2}html[data-tv-theme="sunset"] .now-panel,html[data-tv-theme="sunset"] .next-panel,html[data-tv-theme="sunset"] .live-chip,html[data-tv-theme="sunset"] .channel-switch,html[data-tv-theme="sunset"] .classic-info,html[data-tv-theme="sunset"] .guide-block,html[data-tv-theme="sunset"] .channel-card,html[data-tv-theme="sunset"] .release-note,html[data-tv-theme="sunset"] .channel-detail,html[data-tv-theme="sunset"] .schedule-mini>div{background:rgba(54,23,52,.90)!important;border-color:rgba(255,195,160,.20)!important;color:#fff7f2}html[data-tv-theme="sunset"] .epg{background:#2a1733;border-color:rgba(255,194,158,.20)}html[data-tv-theme="sunset"] .epg-cell{background:#321b3e;border-color:#5d3556;color:#fff7f2}html[data-tv-theme="sunset"] .epg-time,html[data-tv-theme="sunset"] .epg-channel,html[data-tv-theme="sunset"] .epg-corner{background:#402047;color:#ffd9c6}html[data-tv-theme="sunset"] .epg-program.current{background:#6e345e;box-shadow:inset 0 0 0 3px #ffb36b}html[data-tv-theme="sunset"] .channel-detail p,html[data-tv-theme="sunset"] .schedule-mini small,html[data-tv-theme="sunset"] .provider-status,html[data-tv-theme="sunset"] .clock-line{color:#e5bdb5}
</style></head><body class="tv-app channel-view<?= $isDailyBreathChannel ? ' daily-breath-channel' : '' ?>" style="--channel-art:url('/beyond-tv/assets/img/<?=htmlspecialchars($activeChannelArt[0])?>');--channel-art-position:<?=htmlspecialchars($activeChannelArt[1])?>;--channel-gradient:<?=htmlspecialchars((string)($channel['gradient'] ?? 'linear-gradient(135deg,#511a62,#15215f)'))?>">
<div class="channel-ambient" aria-hidden="true"><span class="channel-ambient-art"></span><span class="channel-ambient-wash"></span><span class="channel-ambient-grid"></span></div>
<?php include __DIR__.'/partials/header.php'; ?>
<?php if($isSlatePreview): ?><style>
.slate-preview-player{aspect-ratio:16/9;min-height:320px;border-radius:24px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:14px;padding:28px}
.slate-preview-player.french-theme{background:linear-gradient(135deg,#122b5a,#f4f0e8 52%,#ab3147);color:#142346}
.slate-preview-player.daily-breath-theme{background:linear-gradient(135deg,#103c31,#416f4c 55%,#ccbd79);color:#fff9e9}
.slate-preview-player .preview-eyebrow{font-size:12px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}
.slate-preview-player h2{font-size:clamp(32px,5vw,64px);margin:0}
.slate-preview-player p{max-width:560px;margin:0;font-weight:650}
.slate-preview-player a{margin-top:8px}
.slate-section{margin:32px 16px 64px}.slate-section h2{font-size:clamp(26px,3vw,40px)}
.slate-blocks,.slate-shows{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.slate-blocks article,.slate-shows article{padding:20px;border:1px solid rgba(255,255,255,.18);border-radius:18px;background:rgba(45,23,57,.8)}
.slate-blocks h3,.slate-shows h3{margin:0 0 8px}.slate-blocks p,.slate-shows p{margin:7px 0;color:#e7d4dc}
.slate-preview-note{font-weight:750;color:#ffd3a0}.slate-shows{margin-top:16px}.slate-shows small{display:block;color:#e7d4dc;margin:6px 0 10px}
.slate-sample{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;padding:0;list-style:none}.slate-sample li{padding:14px;border-radius:12px;background:rgba(45,23,57,.8)}
.slate-pipeline{padding:18px;border:1px solid rgba(255,255,255,.18);border-radius:16px;background:rgba(45,23,57,.8);line-height:1.9}
@media(max-width:980px){.slate-preview-player{border-radius:0}.slate-section{margin:28px 16px 52px}}
</style><?php endif; ?>
<main class="page shell channel-page"><a class="back app-back" href="/beyond-tv/">← Back to channels</a>
<section class="player-shell real-channel">
<?php if($isClassic): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars((string)$classicGuideState['embed_url'])?>" title="Beyond Animated Classics live rotation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><span class="source-pill">LIVE · VANCOUVER TIME</span><span><?=htmlspecialchars((string)$classicGuideState['current']['title'])?> · joins in progress</span></div>
<?php elseif(($isScheduled || $isSpaceScheduled) && $scheduledState): ?>
<?php if($isSpaceScheduled && !empty($scheduledState['player_url'])): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars((string)$scheduledState['player_url'])?>" title="Space TV scheduled programming" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><span class="source-pill"><?=htmlspecialchars((string)($scheduledState['source_label']??'NASA SVS · VANCOUVER TIME'))?></span><span><?=htmlspecialchars((string)($scheduledState['current']['lineup']??$scheduledState['current']['title']))?> · joins in progress</span></div>
<?php else: ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars((string)$scheduledState['embed_url'])?>" title="<?=htmlspecialchars((string)$channel['name'])?> scheduled programming" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><span class="source-pill"><?=$isSpaceScheduled?'NASA VIDEOS · VANCOUVER TIME':'LIVE · VANCOUVER TIME'?></span><span><?=htmlspecialchars((string)($isSpaceScheduled?$scheduledState['current']['title']:($scheduledState['playing']['title']??$scheduledState['current']['title'])))?><?=$isSpaceScheduled?' · official NASA YouTube videos':' · joins in progress'?></span></div><?php endif; ?>
<?php elseif($isMoviesScheduled && $moviesState): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars((string)$moviesState['player_url'])?>" title="Beyond Movies scheduled feature" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><span class="source-pill"><?=htmlspecialchars((string)$moviesState['label'])?></span><span><?=htmlspecialchars((string)$moviesState['current']['title'])?> · <?=htmlspecialchars((string)$moviesState['current']['genre'])?></span></div>
<?php elseif($isAnimeChannel && $animeState): ?>
<?php if(!empty($animeState['player_url'])): ?><div class="youtube-player-wrap"><video class="youtube-player" src="<?=htmlspecialchars((string)$animeState['player_url'])?>#t=<?=intval($animeState['start_offset'])?>" controls autoplay muted playsinline></video></div>
<?php else: ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars((string)($animeState['embed_url'] ?? ''))?>" title="Beyond Anime synchronized live rotation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><?php endif; ?>
<div class="playlist-actions"><span class="source-pill"><?=htmlspecialchars((string)$animeState['label'])?></span><span><?=htmlspecialchars((string)$animeState['current']['title'])?> · <?=htmlspecialchars((string)$animeState['current']['lineup'])?></span></div>
<?php elseif($isAfterDarkCourage): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="<?=htmlspecialchars($courageEmbedUrl)?>" title="Courage the Cowardly Dog After Dark rotation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><span class="source-pill">AFTER DARK · LIVE</span><span>Courage the Cowardly Dog · Season 1 Episode <?=$courageEpisodeIndex+1?></span></div>
<?php elseif($isStream): ?><div class="provider-player" data-stream-endpoint="<?=htmlspecialchars((string)$channel['stream_endpoint'])?>"><video class="beyond-video" controls playsinline autoplay muted preload="metadata" poster="/beyond-tv/assets/img/beyond-tv-promo.webp"></video><div class="player-loading" role="status">Tuning in…</div><div class="player-fallback" hidden><p>Direct playback is unavailable.</p><button class="btn btn-secondary" type="button" data-open-embed>Open backup player</button></div><iframe class="archive-embed" title="Backup player" allow="autoplay; fullscreen" allowfullscreen hidden></iframe><button class="unmute-hint" type="button" data-unmute>🔊 Tap for sound</button></div>
<?php elseif($isYouTubePlaylist && $youtubePlaylistId): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="https://www.youtube-nocookie.com/embed/videoseries?list=<?=htmlspecialchars($youtubePlaylistId)?>&amp;autoplay=1&amp;mute=1&amp;playsinline=1&amp;rel=0" title="<?=htmlspecialchars((string)$channel['name'])?> official video collection" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><div class="playlist-actions"><a class="btn btn-secondary" href="https://www.youtube.com/playlist?list=<?=htmlspecialchars($youtubePlaylistId)?>" target="_blank" rel="noopener">▶ Open official collection</a><span class="source-pill"><?=htmlspecialchars((string)($channel['source_label']??'YOUTUBE PLAYLIST'))?></span></div>
<?php elseif($isYouTube && $youtubeId): ?><div class="youtube-player-wrap"><iframe class="youtube-player" src="https://www.youtube-nocookie.com/embed/<?=htmlspecialchars($youtubeId)?>?<?=htmlspecialchars($youtubeParams)?>" title="<?=htmlspecialchars((string)$channel['name'])?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div><?php if($youtubePlaylistId): ?><div class="playlist-actions"><a class="btn btn-secondary" href="https://www.youtube.com/watch?v=<?=htmlspecialchars($youtubePlaylistVideoId ?: $youtubeId)?>&amp;list=<?=htmlspecialchars($youtubePlaylistId)?>" target="_blank" rel="noopener">▶ Play Full Episode Playlist</a><span class="source-pill">YOUTUBE PLAYLIST</span></div><?php endif; ?>
<?php elseif($isYouTube): ?><div class="external-player series-ready" style="background:<?=$channel['gradient']?>"><span class="external-icon"><?=htmlspecialchars((string)$channel['icon'])?></span><span class="source-pill">OFFICIAL EMBED READY</span><h2>Add an approved episode</h2><p>This series page is ready. An administrator can paste an official YouTube video ID in TV Content Manager.</p><a class="btn btn-primary" href="<?=htmlspecialchars((string)($channel['official_url']??'#'))?>" target="_blank" rel="noopener">Browse <?=htmlspecialchars((string)($channel['official_channel']??'official channel'))?> ↗</a></div>
<?php elseif($isExternal): ?><div class="external-player" style="background:<?=$channel['gradient']?>"><span class="external-icon"><?=htmlspecialchars((string)$channel['icon'])?></span><h2>Official live source</h2><p>Beyond TV opens this channel from its official provider.</p><a class="btn btn-primary" href="<?=htmlspecialchars((string)$channel['external_url'])?>" target="_blank" rel="noopener">Open official stream ↗</a></div>
<?php elseif($isSlatePreview && $channelSlate): ?><div class="slate-preview-player <?=$slug==='mrbeast-tv'?'daily-breath-theme':'french-theme'?>"><span class="preview-eyebrow">Channel <?=$channelNumber?> · Programming preview</span><h2><?=htmlspecialchars((string)($channelSlate['network_name'] ?? $channel['name']))?></h2><p><?=htmlspecialchars((string)($channelSlate['theme'] ?? 'Programming in development'))?></p><a class="btn btn-primary" href="<?=htmlspecialchars((string)($channelSlate['project_url'] ?? $channel['official_url'] ?? '#'))?>"><?=htmlspecialchars((string)($channelSlate['project_label'] ?? 'Explore project'))?> ↗</a></div>
<?php else: ?><div class="video-placeholder" style="background:<?=$channel['gradient']?>"><span><?=htmlspecialchars((string)$channel['icon'])?></span></div><?php endif; ?>
<div class="channel-detail">
<div class="channel-station-line"><span class="live-badge"><?=$isPreview?'PREVIEW':'LIVE'?></span><span>BEYOND TV · CHANNEL <?=$channelNumber?></span><span class="signal-bars"><i></i><i></i><i></i><i></i></span></div>
<?php if(!empty($channel['weekend'])):?><span class="weekend-badge">MEMBER PREVIEW</span><?php endif;?>
<h1><?=htmlspecialchars((string)$channel['name'])?></h1>
<p><?=htmlspecialchars((string)$channel['description'])?></p>
<div class="channel-broadcast-meta"><span><?=$isPreview?'PROGRAMMING PREVIEW':'● ON AIR'?></span><span>VANCOUVER TIME</span><span><?=$isPreview?'EPISODES IN DEVELOPMENT':'FREE TO WATCH'?></span></div>
<?php if(!$isPreview): ?>
<p><strong>Now playing:</strong> <?=htmlspecialchars((string)(
    $isMoviesScheduled
        ? $moviesState['current']['title']
        : ($isAnimeChannel
            ? $animeState['current']['title'] . ' · ' . $animeState['current']['lineup']
            : (($isScheduled || $isSpaceScheduled)
                ? (($scheduledState['current']['icon']??'') . ' ' . ($scheduledState['current']['title']??''))
                : $channel['now']))
))?></p>
<p><strong>Up next:</strong> <?=htmlspecialchars((string)(
    $isMoviesScheduled
        ? $moviesState['next']['title']
        : ($isAnimeChannel
            ? $animeState['next']['title'] . ' · ' . $animeState['next']['lineup']
            : (($isScheduled || $isSpaceScheduled)
                ? ($scheduledState['next']['title']??'')
                : ($channel['up_next']??'')))
))?></p>
<?php endif; ?>
<?php if($isSpaceScheduled && $scheduledState):?><h2>Daily Space TV programming · Vancouver time</h2><div class="schedule-mini" style="display:grid;gap:8px;margin:18px 0"><?php foreach($scheduledState['blocks'] as $block):?><div style="padding:10px 12px;border:1px solid #303446;border-radius:10px"><strong><?=htmlspecialchars(sprintf('%02d:00–%02d:00 %s %s',(int)$block['start'],(int)$block['end'],(string)$block['icon'],(string)$block['title']))?></strong><br><small><?=htmlspecialchars((string)$block['lineup'])?></small></div><?php endforeach;?></div><div class="schedule-mini" style="margin:18px 0;padding:12px;border:1px solid #6d5ae6;border-radius:10px"><strong>✨ 8:45 AM + 8:45 PM · Cosmic Compass: Daily Astrology</strong><br><small>Five minutes of entertainment-only zodiac reflections from Beyond Space. Astronomy and astrology stay clearly separated.</small></div><p>Reviewed silent NASA SVS archive clips play locally when available. Official NASA YouTube programming fills each block until that archive is ready. <a href="https://svs.gsfc.nasa.gov/" target="_blank" rel="noopener noreferrer">Explore NASA SVS</a></p>
<?php elseif($channelGuide && !$isPreview):?><div class="schedule-mini" style="display:grid;gap:8px;margin:18px 0"><?php foreach($channelGuide['rows'] as $block):?><div style="padding:10px 12px;border:1px solid #303446;border-radius:10px"><strong><?=htmlspecialchars((string)$block['icon'].' '.$block['title'])?></strong><br><small><?=htmlspecialchars((string)$block['lineup'])?></small></div><?php endforeach;?></div><?php endif;?>
<?php if($isAnimeChannel):?><p class="source-note"><span class="source-pill"><?=htmlspecialchars((string)($channel['source_label']??'DAILY ANIME ROTATION'))?></span></p>
<?php elseif($isPlaylistLive):?><p class="source-note"><span class="source-pill"><?=htmlspecialchars((string)($channel['source_label']??'YOUTUBE EMBED'))?></span></p>
<?php elseif($isYouTubePlaylist):?><p class="source-note"><span class="source-pill"><?=htmlspecialchars((string)($channel['source_label']??'YOUTUBE PLAYLIST'))?></span></p>
<?php elseif($isYouTube && $youtubeId):?><p class="source-note"><span class="source-pill"><?=htmlspecialchars((string)($channel['source_label']??'YOUTUBE EMBED'))?></span></p>
<?php elseif($isExternal):?><p class="source-note"><span class="source-pill">OFFICIAL SOURCE</span></p><?php endif;?>
<?php if(!$isPreview): ?><p class="rights-note"><a id="channel-report-link" href="/beyond-tv/source-safety.php?page=<?=rawurlencode('https://beyondimagination.co.technology'.($_SERVER['REQUEST_URI']??'/beyond-tv/'))?>&amp;channel=<?=rawurlencode($slug)?>">Source details & report</a> · <?=$isStream?'Testing · Rights unverified':'External source'?></p><?php endif; ?>
<?php if($signedIn && $channel['name'] !== 'Channel not found'):?><button class="btn btn-secondary" type="button" data-my-list data-list-type="channel" data-list-slug="<?=htmlspecialchars($slug)?>" data-list-token="<?=htmlspecialchars(beyond_tv_my_list_token())?>" aria-pressed="<?=$inMyList?'true':'false'?>"><?=$inMyList?'✓ Added to My List':'＋ My List'?></button><?php elseif(!$signedIn):?><a class="btn btn-secondary" href="/beyond-id/auth/login.php?return=<?=urlencode($_SERVER['REQUEST_URI']??'/beyond-tv/')?>">Sign in to save</a><?php endif;?>
</div>
</section>
<?php if($slug==='mrbeast-tv' && !empty($channelSlate['weekly_specials'])): ?><section class="schedule-mini" aria-labelledby="daily-breath-specials"><h2 id="daily-breath-specials">Weekly services & observances</h2><?php foreach($channelSlate['weekly_specials'] as $special): ?><div style="padding:10px 12px;border:1px solid #303446;border-radius:10px;margin:8px 0"><strong><?=htmlspecialchars((string)($special['day']??''))?><?=!empty($special['time'])?' · '.htmlspecialchars((string)$special['time']):''?> · <?=htmlspecialchars((string)($special['show']??''))?></strong><br><small><?=htmlspecialchars((string)($special['focus']??''))?></small></div><?php endforeach; ?></section><?php endif; ?>
<?php if($isSlatePreview && $channelSlate): ?>
<section class="slate-section" aria-labelledby="channel-slate-title">
<span class="kicker">CHANNEL <?=$channelNumber?> · PROGRAMMING PLAN</span>
<h2 id="channel-slate-title"><?=$slug==='mrbeast-tv'?'A network built from recurring shows.':'French for every stage of the day.'?></h2>
<p class="slate-preview-note">This is the proposed lineup. Episodes and broadcast times are in development.</p>
<div class="slate-blocks">
<?php foreach(($channelSlate['blocks'] ?? []) as $block): ?>
<article><h3><?=htmlspecialchars((string)$block['name'])?></h3><p><?=htmlspecialchars((string)$block['hours'])?> · <?=htmlspecialchars((string)$block['goal'])?></p><p><?=htmlspecialchars(implode(' → ', (array)($block['shows'] ?? [])))?></p></article>
<?php endforeach; ?>
</div>
<h2>The 12-show slate</h2>
<div class="slate-shows">
<?php foreach(($channelSlate['shows'] ?? []) as $show): ?>
<article><h3><?=htmlspecialchars((string)$show['name'])?></h3><?php if(!empty($show['flagship'])): ?><strong class="kicker">FLAGSHIP SERIES</strong><?php endif; ?><small><?=htmlspecialchars((string)$show['format'])?> · <?=htmlspecialchars((string)$show['episode_length'])?></small><p><?=htmlspecialchars((string)$show['concept'])?></p><?php if(!empty($show['project_url'])): ?><a class="btn btn-secondary" href="<?=htmlspecialchars((string)$show['project_url'])?>">Explore <?=htmlspecialchars((string)$show['name'])?> ↗</a><?php endif; ?></article>
<?php endforeach; ?>
</div>
<?php if(!empty($channelSlate['sample_morning'])): ?>
<h2>Sample morning run</h2><p class="slate-preview-note">An example of how produced episodes could fill the schedule.</p>
<ol class="slate-sample"><?php foreach($channelSlate['sample_morning'] as $slot): ?><li><strong><?=htmlspecialchars((string)$slot['time'])?></strong><br><?=htmlspecialchars((string)$slot['show'])?></li><?php endforeach; ?></ol>
<?php endif; ?>
<?php if(!empty($channelSlate['production_pipeline'])): ?>
<h2>From show to broadcast</h2><p class="slate-pipeline"><?=htmlspecialchars(implode(' → ', (array)$channelSlate['production_pipeline']))?></p>
<?php endif; ?>
<?php if($slug==='beyond-french'): ?><p><a href="/beyond-tv/channel.php?slug=preschool-francais">Looking for French shows for young children? Visit Préscolaire Français →</a></p><?php endif; ?>
</section>
<?php endif; ?>
</main><?php if($slug==='mrbeast-tv'): ?><script>window.BeyondTVAdBreakExperience='breath-hourglass';</script><?php elseif($slug==='redbull-tv'): ?><script>window.BeyondTVAdBreakExperience='tattoo-stencil';</script><?php endif; ?><script src="/beyond-tv/assets/js/video-ads.js?v=1.3.0"></script><script src="/beyond-tv/assets/js/app.js?v=1.1.1"></script><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
