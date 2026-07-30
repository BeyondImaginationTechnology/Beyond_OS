<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
if (!empty($_SESSION['user_id'])) {
    beyond_track_app('Beyond TV');
}
require_once __DIR__ . '/includes/episode-library.php';

$catalog = json_decode((string) file_get_contents(__DIR__ . '/data/catalog.json'), true) ?: [];
$episodes = json_decode((string) @file_get_contents(__DIR__ . '/data/episodes.json'), true) ?: [];
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['slug'] ?? '')));
$title = null;
foreach ($catalog as $candidate) {
    if (($candidate['slug'] ?? '') === $slug) {
        $title = $candidate;
        break;
    }
}
if (!$title) {
    http_response_code(404);
    $title = [
        'title' => 'Title unavailable',
        'description' => 'This title could not be found.',
        'type' => 'show',
        'icon' => '📺',
        'gradient' => 'linear-gradient(135deg,#111,#444)',
        'source_type' => 'none',
    ];
}

$sourceType = (string) ($title['source_type'] ?? 'none');
$archiveId = preg_replace('/[^A-Za-z0-9_.-]/', '', (string) ($title['archive_id'] ?? ''));
$youtubeId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($title['youtube_id'] ?? ''));
$youtubeStart = max(0, (int) ($title['youtube_start'] ?? 0));
$youtubePlaylistId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($title['youtube_playlist_id'] ?? ''));
$showEpisodes = array_values(array_filter(
    $episodes,
    static fn(array $episode): bool => ($episode['show_slug'] ?? '') === $slug
));
$episodeLibrary = beyond_tv_build_episode_library($title, $showEpisodes, __DIR__ . '/data');
$requestedSeason = max(1, (int) ($_GET['season'] ?? (beyond_tv_forced_season($title) ?: 1)));
$requestedEpisode = max(1, (int) ($_GET['episode'] ?? 1));
$hasExplicitEpisode = isset($_GET['season']) || isset($_GET['episode']);
$currentEpisode = beyond_tv_select_episode($episodeLibrary, $requestedSeason, $requestedEpisode);
$currentSeason = (int) ($currentEpisode['season'] ?? $requestedSeason);
$currentEpisodeNumber = (int) ($currentEpisode['episode'] ?? $requestedEpisode);
$currentEpisodeTitle = (string) ($currentEpisode['title'] ?? '');
$currentEpisodeIsPlayable = !empty($currentEpisode['playable']);
$currentVideoUrl = trim((string) ($currentEpisode['video_url'] ?? ''));
$currentYoutubeId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($currentEpisode['youtube_id'] ?? ''));
$currentPlaylistIndex = max(0, (int) ($currentEpisode['playlist_index'] ?? ($currentEpisodeNumber - 1)));

$episodeSeasons = [];
$currentLibraryIndex = null;
foreach ($episodeLibrary as $index => $episode) {
    $season = max(1, (int) ($episode['season'] ?? 1));
    $episodeSeasons[$season][] = $episode;
    if ($season === $currentSeason && (int) ($episode['episode'] ?? 0) === $currentEpisodeNumber) {
        $currentLibraryIndex = $index;
    }
}
$previousEpisode = $currentLibraryIndex !== null && $currentLibraryIndex > 0 ? $episodeLibrary[$currentLibraryIndex - 1] : null;
$nextEpisode = $currentLibraryIndex !== null && $currentLibraryIndex < count($episodeLibrary) - 1 ? $episodeLibrary[$currentLibraryIndex + 1] : null;
$playableEpisodeCount = count(array_filter($episodeLibrary, static fn(array $episode): bool => !empty($episode['playable'])));
$statusLabels = [
    'Full Episode' => 'Full Episode',
    'Clip' => 'Clip',
    'Compilation' => 'Compilation',
    'Trailer' => 'Trailer',
    'Unavailable' => 'Unavailable',
    'Episode listed' => 'Episode listed',
];

function beyond_tv_episode_href(string $slug, array $episode): string
{
    return '/beyond-tv/title.php?' . http_build_query([
        'slug' => $slug,
        'season' => max(1, (int) ($episode['season'] ?? 1)),
        'episode' => max(1, (int) ($episode['episode'] ?? 1)),
    ]) . '#player';
}

function beyond_tv_episode_code(array $episode): string
{
    return sprintf('S%02dE%02d', max(1, (int) ($episode['season'] ?? 1)), max(1, (int) ($episode['episode'] ?? 1)));
}
?>
<!doctype html>
<html lang="en">
<head>
<script>(function(){try{const t=localStorage.getItem("beyond-tv-theme");document.documentElement.dataset.tvTheme=["dark","light","sunset"].includes(t)?t:"sunset"}catch(e){document.documentElement.dataset.tvTheme="sunset"}})();</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#401532">
<title><?= htmlspecialchars((string) $title['title']) ?><?= $currentEpisodeIsPlayable && $currentEpisodeTitle !== '' ? ' · ' . htmlspecialchars(beyond_tv_episode_code($currentEpisode ?? []) . ' ' . $currentEpisodeTitle) : '' ?> | Beyond TV</title>
<link rel="stylesheet" href="/beyond-tv/assets/css/app.css?v=3.0.0">
</head>
<body class="tv-app">
<?php include __DIR__ . '/partials/header.php'; ?>
<main class="page shell title-page">
<a class="back app-back" href="/beyond-tv/browse.php">← Browse</a>
<section class="title-layout" id="player">
<div class="title-player">
<?php if ($currentYoutubeId !== ''): ?>
<div class="youtube-player-wrap"><iframe class="youtube-player" src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($currentYoutubeId) ?>?playsinline=1&amp;rel=0&amp;modestbranding=1" title="<?= htmlspecialchars($currentEpisodeTitle ?: (string) $title['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
<?php elseif ($sourceType === 'youtube_playlist_embed' && $youtubePlaylistId !== ''): ?>
<div class="youtube-player-wrap library-playlist-player"><iframe class="youtube-player" src="https://www.youtube-nocookie.com/embed/videoseries?list=<?= htmlspecialchars($youtubePlaylistId) ?>&amp;index=<?= $currentPlaylistIndex ?>&amp;playsinline=1&amp;rel=0&amp;modestbranding=1" title="<?= htmlspecialchars((string) $title['title']) ?> <?= htmlspecialchars(beyond_tv_episode_code($currentEpisode ?? [])) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
<?php elseif ($currentVideoUrl !== ''): ?>
<div class="provider-player title-video"><video class="beyond-video" controls playsinline preload="metadata" src="<?= htmlspecialchars($currentVideoUrl) ?>"></video></div>
<?php elseif ($sourceType === 'youtube_embed' && $youtubeId !== ''): ?>
<?php $params = http_build_query(['autoplay' => !empty($title['autoplay']) ? 1 : 0, 'mute' => !empty($title['muted']) ? 1 : 0, 'playsinline' => 1, 'rel' => 0, 'start' => $youtubeStart]); ?>
<div class="youtube-player-wrap"><iframe class="youtube-player" src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($youtubeId) ?>?<?= htmlspecialchars($params) ?>" title="<?= htmlspecialchars((string) $title['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
<?php elseif ($sourceType === 'archive_embed' && $archiveId !== ''): ?>
<div class="youtube-player-wrap"><iframe class="youtube-player" src="https://archive.org/embed/<?= htmlspecialchars($archiveId) ?>" title="<?= htmlspecialchars((string) $title['title']) ?>" allow="autoplay; fullscreen" allowfullscreen></iframe></div>
<?php elseif ($sourceType === 'watchlist'): ?>
<?php $isHdPending = (string) ($title['episode_catalog'] ?? '') === 'pending_hd'; ?>
<div class="external-player" style="background:<?= htmlspecialchars((string) ($title['gradient'] ?? 'linear-gradient(135deg,#111,#444)')) ?>"><span class="external-icon"><?= htmlspecialchars((string) ($title['icon'] ?? '📺')) ?></span><span class="source-pill"><?= htmlspecialchars(strtoupper((string) ($title['source_label'] ?? 'OWNER WATCHLIST'))) ?></span><h2><?= $isHdPending ? 'HD source required' : 'Interested' ?></h2><p><?= $isHdPending ? 'The supplied Archive item currently exposes only SD files. Playback remains disabled until a verified HD collection is available.' : 'This title is saved for curation. Playback stays disabled until a rights-authorized source is approved.' ?></p><?php if (!empty($title['candidate_url'])): ?><a class="btn btn-secondary" href="<?= htmlspecialchars((string) $title['candidate_url']) ?>" target="_blank" rel="noopener">Review candidate metadata ↗</a><?php endif; ?></div>
<?php else: ?>
<div class="external-player" style="background:<?= htmlspecialchars((string) ($title['gradient'] ?? 'linear-gradient(135deg,#111,#444)')) ?>"><span class="external-icon"><?= htmlspecialchars((string) ($title['icon'] ?? '📺')) ?></span><h2>Catalogue available</h2><p>No approved full-show source has been added. Browse the complete episode guide below.</p></div>
<?php endif; ?>

<?php if (($title['type'] ?? '') === 'show' && $currentEpisode && $currentEpisodeIsPlayable): ?>
<div class="current-episode-bar" data-tv-progress data-slug="<?= htmlspecialchars($slug) ?>" data-season="<?= $currentSeason ?>" data-episode="<?= $currentEpisodeNumber ?>" data-title="<?= htmlspecialchars($currentEpisodeTitle) ?>" data-explicit="<?= $hasExplicitEpisode ? '1' : '0' ?>">
<div class="current-episode-copy"><span class="kicker">CURRENT EPISODE</span><strong><?= htmlspecialchars(beyond_tv_episode_code($currentEpisode)) ?> · <?= htmlspecialchars($currentEpisodeTitle) ?></strong><small><?= $currentLibraryIndex !== null ? 'Episode ' . ($currentLibraryIndex + 1) . ' of ' . count($episodeLibrary) : 'Episode selected' ?><?= !empty($currentEpisode['runtime']) ? ' · ' . htmlspecialchars((string) $currentEpisode['runtime']) : '' ?></small></div>
<div class="episode-nav" aria-label="Episode navigation">
<?php if ($previousEpisode && !empty($previousEpisode['playable'])): ?><a class="btn btn-secondary" href="<?= htmlspecialchars(beyond_tv_episode_href($slug, $previousEpisode)) ?>">← Previous</a><?php endif; ?>
<span id="episode-resume-slot"></span>
<?php if ($nextEpisode && !empty($nextEpisode['playable'])): ?><a class="btn" href="<?= htmlspecialchars(beyond_tv_episode_href($slug, $nextEpisode)) ?>">Next →</a><?php endif; ?>
</div>
</div>
<?php endif; ?>

<?php if ($archiveId !== ''): ?><div class="playlist-actions"><span class="source-pill">INTERNET ARCHIVE</span><a class="btn btn-secondary" target="_blank" rel="noopener" href="https://archive.org/details/<?= htmlspecialchars($archiveId) ?>">Open source item ↗</a></div><?php endif; ?>
</div>

<aside class="title-info">
<span class="source-pill"><?= ($title['type'] ?? '') === 'movie' ? 'FREE MOVIE' : 'TV SHOW' ?></span>
<h1><?= htmlspecialchars((string) $title['title']) ?></h1>
<p class="title-meta"><?= htmlspecialchars((string) ($title['year'] ?? '')) ?> · <?= htmlspecialchars((string) ($title['rating'] ?? 'NR')) ?><?php if (!empty($title['runtime'])): ?> · <?= htmlspecialchars((string) $title['runtime']) ?><?php endif; ?></p>
<p><?= htmlspecialchars((string) ($title['description'] ?? '')) ?></p>
<p><strong><?= htmlspecialchars((string) ($title['genre'] ?? '')) ?></strong></p>
<?php if (($title['type'] ?? '') === 'show'): ?>
<p class="catalog-summary"><strong><?= count($episodeSeasons) ?> season<?= count($episodeSeasons) === 1 ? '' : 's' ?></strong> · <?= count($episodeLibrary) ?> episode<?= count($episodeLibrary) === 1 ? '' : 's' ?> listed<?php if ($playableEpisodeCount > 0): ?> · <?= $playableEpisodeCount ?> playable<?php endif; ?></p>
<?php endif; ?>
<?php if (!empty($title['source_label'])): ?><p class="source-note"><span class="source-pill"><?= htmlspecialchars(strtoupper((string) $title['source_label'])) ?></span></p><?php endif; ?>
<?php if ($youtubePlaylistId !== ''): ?><a class="btn btn-secondary" target="_blank" rel="noopener" href="https://www.youtube.com/playlist?list=<?= htmlspecialchars($youtubePlaylistId) ?>">Open official collection ↗</a><?php endif; ?>
</aside>
</section>

<?php if (($title['type'] ?? '') === 'show'): ?>
<section class="episode-catalog season-library" aria-labelledby="episode-library-heading">
<div class="episode-heading"><div><span class="kicker">COMPLETE EPISODE GUIDE</span><h2 id="episode-library-heading">Seasons & episodes</h2></div><p>Choose an episode to load it in the player. The selected episode stays highlighted so you always know where you are.</p></div>
<?php if ($episodeSeasons): ?>
<?php foreach ($episodeSeasons as $seasonNumber => $seasonEpisodes): ?>
<details class="season-panel" <?= $seasonNumber === $currentSeason ? 'open' : '' ?>><summary><strong>Season <?= (int) $seasonNumber ?></strong><span><?= count($seasonEpisodes) ?> episode<?= count($seasonEpisodes) === 1 ? '' : 's' ?></span></summary><div class="season-library-grid">
<?php foreach ($seasonEpisodes as $episode): ?>
<?php $playable = !empty($episode['playable']); $isCurrent = $currentEpisodeIsPlayable && $playable && (int) $episode['season'] === $currentSeason && (int) $episode['episode'] === $currentEpisodeNumber; $code = beyond_tv_episode_code($episode); ?>
<?php if ($playable): ?><a class="season-episode-card <?= $isCurrent ? 'is-playing' : '' ?>" href="<?= htmlspecialchars(beyond_tv_episode_href($slug, $episode)) ?>" aria-current="<?= $isCurrent ? 'true' : 'false' ?>"><?php else: ?><article class="season-episode-card is-unavailable" aria-label="<?= htmlspecialchars($code . ' ' . (string) $episode['title']) ?> unavailable"><?php endif; ?>
<span class="episode-index"><?= htmlspecialchars($code) ?></span>
<strong><?= htmlspecialchars((string) $episode['title']) ?></strong>
<?php if (!empty($episode['synopsis'])): ?><small class="episode-card-summary"><?= htmlspecialchars((string) $episode['synopsis']) ?></small><?php endif; ?>
<span class="episode-card-action"><?= $isCurrent ? 'Now selected' : ($playable ? 'Play episode' : htmlspecialchars($statusLabels[(string) ($episode['status'] ?? '')] ?? 'Not available')) ?><?= $playable ? ' ▶' : '' ?></span>
<?php if ($playable): ?></a><?php else: ?></article><?php endif; ?>
<?php endforeach; ?>
</div></details>
<?php endforeach; ?>
<?php else: ?>
<div class="episode-empty"><strong>Episode guide pending</strong><p>This show is listed in the catalogue, but its episode metadata has not been connected yet.</p></div>
<?php endif; ?>
</section>
<?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
(function(){
  const progress = document.querySelector('[data-tv-progress]');
  if (!progress) return;
  const key = 'beyond-tv-progress:' + progress.dataset.slug;
  const current = {
    season: Number(progress.dataset.season || 1),
    episode: Number(progress.dataset.episode || 1),
    title: progress.dataset.title || '',
    href: location.pathname + '?slug=' + encodeURIComponent(progress.dataset.slug) + '&season=' + encodeURIComponent(progress.dataset.season) + '&episode=' + encodeURIComponent(progress.dataset.episode) + '#player'
  };
  try {
    const saved = JSON.parse(localStorage.getItem(key) || 'null');
    if (progress.dataset.explicit === '1') {
      localStorage.setItem(key, JSON.stringify(current));
      return;
    }
    if (saved && (saved.season !== current.season || saved.episode !== current.episode)) {
      const slot = document.getElementById('episode-resume-slot');
      if (slot) {
        const link = document.createElement('a');
        link.className = 'btn btn-secondary resume-episode';
        link.href = saved.href;
        link.textContent = 'Resume S' + String(saved.season).padStart(2,'0') + 'E' + String(saved.episode).padStart(2,'0');
        slot.replaceChildren(link);
      }
    }
  } catch (error) {}
})();
</script>
<script src="/assets/js/visitor-analytics.js" defer></script>
</body>
</html>
