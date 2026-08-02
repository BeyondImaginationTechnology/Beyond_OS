<?php
$user = Auth::user();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isStudio = strpos($currentPath, '/server/admin/daily-studio/') === 0;
$studioGroups = [
    'DailyBreath' => [
        ['/server/admin/daily-studio/dailybreath-content.php', 'Content manager', '📖'],
        ['/server/admin/daily-studio/breath-generator.php', 'Verse generator', '✨'],
    ],
    'Beyond French' => [
        ['/server/admin/daily-studio/french-generator.php', 'French generator', '🇫🇷'],
        ['/server/admin/daily-studio/french-options.php', 'Lesson options', '⚙️'],
    ],
    'Beyond Tattoo' => [
        ['/server/admin/daily-studio/stencil-packs.php', 'Stencil packs', '🎨'],
        ['/server/admin/daily-studio/stencil-library.php', 'Generate & publish', '📤'],
    ],
    'Shared tools' => [
        ['/server/admin/daily-studio/voice-settings.php', 'Voice settings', '🎙️'],
    ],
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $isStudio ? 'Beyond Studio' : 'Beyond Imagination Admin' ?></title>
  <link rel="stylesheet" href="/server/admin/assets/css/app.css">
  <?php if ($isStudio): ?><link rel="stylesheet" href="/server/admin/daily-studio/studio-light.css"><link rel="stylesheet" href="/server/admin/daily-studio/studio-organized.css?v=20260719-1"><?php endif; ?>
</head>
<body<?= $isStudio ? ' class="studio-body"' : '' ?>>
<div class="layout<?= $isStudio ? ' studio-layout' : '' ?>">
  <aside class="sidebar<?= $isStudio ? ' studio-sidebar' : '' ?>">
    <div class="brand"><div class="logo"><?= $isStudio ? 'BS' : 'BI' ?></div><div><strong><?= $isStudio ? 'Beyond Studio' : 'Beyond Imagination' ?></strong><div class="muted"><?= $isStudio ? 'Content workspace' : 'Admin v2.3.3' ?></div></div></div>
    <?php if ($isStudio): ?>
      <nav class="nav studio-nav" aria-label="Beyond Studio navigation">
        <?php $homeActive = rtrim($currentPath, '/') === '/server/admin/daily-studio'; ?>
        <a class="studio-home-link<?= $homeActive ? ' active' : '' ?>" href="/server/admin/daily-studio/"<?= $homeActive ? ' aria-current="page"' : '' ?>><span class="studio-nav-icon" aria-hidden="true">⌂</span><span>Studio Home</span></a>
        <?php foreach ($studioGroups as $groupLabel => $links):
          $groupActive = false;
          foreach ($links as $candidate) { if (rtrim($currentPath, '/') === rtrim($candidate[0], '/')) { $groupActive = true; break; } }
        ?>
          <details class="studio-nav-group"<?= $groupActive ? ' open' : '' ?>><summary><?= htmlspecialchars($groupLabel) ?></summary><div class="studio-nav-group-links">
            <?php foreach ($links as [$href, $label, $icon]): $active = rtrim($currentPath, '/') === rtrim($href, '/'); ?>
              <a href="<?= htmlspecialchars($href) ?>"<?= $active ? ' class="active" aria-current="page"' : '' ?>><span class="studio-nav-icon" aria-hidden="true"><?= $icon ?></span><span><?= htmlspecialchars($label) ?></span></a>
            <?php endforeach; ?>
          </div></details>
        <?php endforeach; ?>
      </nav>
      <div class="studio-sidebar-foot"><a class="studio-admin-link" href="/beyond-id/admin/index.php">← Beyond ID Admin</a><a class="studio-logout-link" href="/beyond-id/auth/logout.php">Log out</a></div>
    <?php else: ?>
      <nav class="nav"><a href="/server/admin/dashboard.php">Dashboard</a><a href="/server/admin/users.php">Users</a><a href="/server/admin/catering.php">Beyond Catering</a><a href="/server/admin/dailybreath.php">DailyBreath</a><a href="/server/admin/daily-studio/">Beyond Studio</a><a href="/server/admin/analytics.php">Analytics</a><a href="/server/admin/settings.php">Settings</a><a href="/server/admin/change-password.php">Change Password</a><a href="/beyond-id/auth/logout.php">Logout</a></nav>
    <?php endif; ?>
  </aside>
  <main class="main"><div class="top"><div><div class="muted"><?= $isStudio ? 'Beyond ID administrator' : 'Signed in as' ?></div><strong><?= htmlspecialchars($user['email'] ?? '') ?></strong></div></div>
