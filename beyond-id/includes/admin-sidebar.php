<?php
$adminNavGroups = [
    'Workspace' => [
        ['index.php', 'Overview', 'overview'],
        ['review.php', 'Admin Review', 'review'],
        ['/server/admin/daily-studio/', 'Beyond Studio Home', 'magic'],
    ],
    'Management' => [
        ['users.php', 'Users', 'users'],
        ['apps.php', 'Apps', 'apps'],
        ['database.php', 'Database', 'database'],
        ['sql.php', 'SQL Console', 'terminal'],
    ],
    'Operations' => [
        ['analytics.php', 'Visitor Analytics', 'analytics'],
        ['logs.php', 'Audit Logs', 'logs'],
        ['system.php', 'System Health', 'health'],
        ['settings.php', 'Appearance', 'settings'],
    ],
];
if (!beyond_sql_console_enabled()) {
    $adminNavGroups['Management'] = array_values(array_filter(
        $adminNavGroups['Management'],
        static fn(array $item): bool => $item[0] !== 'sql.php'
    ));
}

$adminIconPaths = [
    'overview' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-7h6v7"/>',
    'review' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    'spark' => '<path d="m12 3-1.6 4.4L6 9l4.4 1.6L12 15l1.6-4.4L18 9l-4.4-1.6L12 3Z"/><path d="m5 15-.8 2.2L2 18l2.2.8L5 21l.8-2.2L8 18l-2.2-.8L5 15Z"/>',
    'content' => '<path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/><path d="m18 3 3 3-7.5 7.5-3.5.5.5-3.5z"/>',
    'breath' => '<path d="M12 21c4.5-3.1 7-6.2 7-10a4 4 0 0 0-7-2.6A4 4 0 0 0 5 11c0 3.8 2.5 6.9 7 10Z"/><path d="M12 8v8M9 12h6"/>',
    'language' => '<path d="M5 8l6 6"/><path d="M4 14l6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m14 22 4-9 4 9"/><path d="M15.5 19h5"/>',
    'voice' => '<rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v4M8 21h8"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'apps' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
    'terminal' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 9 3 3-3 3"/><path d="M13 15h4"/>',
    'analytics' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19V2"/><path d="M2 19h22"/>',
    'logs' => '<path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
    'health' => '<path d="M3 12h4l2-5 4 10 2-5h6"/><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
    'magic' => '<path d="M12 2l2.4 5.6L20 10l-5.6 2.4L12 18l-2.4-5.6L4 10l5.6-2.4z"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
];

$adminIcon = static function (string $name) use ($adminIconPaths): string {
    $paths = $adminIconPaths[$name] ?? $adminIconPaths['overview'];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
?>
<button class="sidebar-scrim" type="button" data-sidebar-close aria-label="Close navigation"></button>
<aside class="sidebar" id="admin-sidebar" aria-label="Admin navigation">
  <a class="brand" href="index.php" aria-label="Beyond ID admin overview">
    <span class="brand-mark" aria-hidden="true">
      <svg viewBox="0 0 48 48" fill="none">
        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2" opacity=".45"/>
        <path d="M24 10a8 8 0 0 0-4.5 14.6V36h9V24.6A8 8 0 0 0 24 10Z" fill="currentColor"/>
        <circle cx="24" cy="18" r="2.4" fill="var(--brand-core)"/>
      </svg>
    </span>
    <span><strong>Beyond ID</strong><small>Admin Console</small></span>
  </a>

  <nav class="admin-nav" aria-label="Primary">
    <?php foreach ($adminNavGroups as $groupLabel => $items): ?>
      <div class="nav-group">
        <p class="nav-label"><?= e($groupLabel) ?></p>
        <?php foreach ($items as [$href, $label, $iconName]):
            $isActive = $adminPage === $href;
        ?>
          <a href="<?= e($href) ?>" class="nav-link<?= $isActive ? ' active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?> data-admin-search-item="<?= e($label) ?>">
            <span class="nav-icon"><?= $adminIcon($iconName) ?></span>
            <span><?= e($label) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="side-card">
    <span class="status-dot" aria-hidden="true"></span>
    <div><strong>Beyond OS</strong><small>All systems operational</small></div>
  </div>

  <div class="sidebar-footer">
    <a href="../dashboard/">User dashboard</a>
    <a href="../auth/logout.php">Log out</a>
  </div>
</aside>

<main class="main">
<header class="topbar">
  <button class="menu-btn icon-button" type="button" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
  </button>

  <div class="topbar-title">
    <small>Admin Console</small>
    <strong><?= e($title ?? 'Overview') ?></strong>
  </div>

  <div class="admin-search-wrap">
    <label class="admin-search" for="admin-quick-search">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg>
      <input id="admin-quick-search" type="search" placeholder="Jump to an admin tool" autocomplete="off" data-admin-search>
      <kbd>/</kbd>
    </label>
    <div class="search-results" data-search-results hidden></div>
  </div>

  <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle light and dark theme" title="Toggle theme">
    <svg class="theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
    <svg class="theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
  </button>

  <div class="top-user" title="<?= e($adminEmail) ?>">
    <span class="user-avatar"><?= e($adminInitial) ?></span>
    <span class="user-copy"><strong><?= e($adminEmail) ?></strong><small>Administrator</small></span>
  </div>
</header>
