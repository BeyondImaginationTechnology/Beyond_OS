<?php
require_once __DIR__ . '/../../includes/ecosystem.php';
render_beyond_bar('Beyond TV');
$signedIn = !empty($_SESSION['user_id']);
$currentTvPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/beyond-tv/'), PHP_URL_PATH) ?: '/beyond-tv/');
$isWatch = $currentTvPath === '/beyond-tv/' || str_ends_with($currentTvPath, '/beyond-tv/index.php');
$isGuide = str_ends_with($currentTvPath, '/live-tv.php');
$isBrowse = str_ends_with($currentTvPath, '/browse.php') || str_ends_with($currentTvPath, '/title.php');
$isSafety = str_ends_with($currentTvPath, '/source-safety.php');
?>
<header class="site-header tv-header">
  <div class="shell nav-wrap">
    <a class="brand" href="/beyond-tv/">
      <img src="/beyond-tv/assets/img/beyond-tv-logo.webp" alt="Beyond TV">
      <span><b>BEYOND <em>TV</em></b><small>LIVE • LEARN • RELAX</small></span>
    </a>
    <nav class="desktop-nav" aria-label="Beyond TV">
      <a href="/">Home</a><a href="/beyond-tv/"<?=$isWatch?' aria-current="page"':''?>>Watch</a><a href="/beyond-tv/live-tv.php"<?=$isGuide?' aria-current="page"':''?>>Guide</a><a href="/beyond-tv/browse.php"<?=$isBrowse?' aria-current="page"':''?>>Browse</a><a href="/beyond-tv/source-safety.php"<?=$isSafety?' aria-current="page"':''?>>Source Safety</a>
      <?php if ($signedIn): ?><a href="/beyond-tv/browse.php?list=mine">My List</a><?php endif; ?>
    </nav>
    <div class="nav-actions">
      <button class="icon-btn btv-theme-toggle" type="button" data-tv-theme-toggle aria-label="Change theme" title="Change theme">🌅</button>
      <a class="icon-btn" href="/beyond-tv/browse.php" aria-label="Search channels">⌕</a>
      <?php if (!$signedIn): ?><a class="tv-signin" href="/beyond-id/auth/login.php?return=/beyond-tv/">Sign in</a><?php endif; ?>
      <button class="menu-btn" type="button" aria-label="Toggle menu" aria-expanded="false">☰</button>
    </div>
  </div>
  <nav class="mobile-nav" aria-label="Mobile" hidden><a href="/">Home</a><a href="/beyond-tv/">Watch</a><a href="/beyond-tv/live-tv.php">Guide</a><a href="/beyond-tv/browse.php">Browse</a><a href="/beyond-tv/source-safety.php">Source Safety</a><?php if ($signedIn): ?><a href="/beyond-tv/browse.php?list=mine">My List</a><?php else: ?><a href="/beyond-id/auth/login.php?return=/beyond-tv/">Sign in</a><?php endif; ?></nav>
</header>
<script>
(function() {
  function initTvMenu() {
    const btn = document.querySelector('.tv-header .menu-btn');
    const nav = document.querySelector('.tv-header .mobile-nav');
    if (btn && nav && !btn.dataset.tvMenuBound) {
      btn.dataset.tvMenuBound = 'true';
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const open = nav.classList.toggle('is-open');
        if (open) {
          nav.removeAttribute('hidden');
        } else {
          nav.setAttribute('hidden', '');
        }
        btn.setAttribute('aria-expanded', String(open));
      });
      document.addEventListener('click', function(e) {
        if (nav.classList.contains('is-open') && !btn.contains(e.target) && !nav.contains(e.target)) {
          nav.classList.remove('is-open');
          nav.setAttribute('hidden', '');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTvMenu);
  } else {
    initTvMenu();
  }
})();
</script>
