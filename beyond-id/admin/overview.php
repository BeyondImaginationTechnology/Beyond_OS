<?php
require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/visitor-analytics.php';

$title = 'System overview';

$countRows = static function (PDO $pdo, string $sql): int {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Throwable $error) {
        error_log('Admin metric unavailable: ' . $error->getMessage());
        return 0;
    }
};

$totalUsers = $countRows($pdo, 'SELECT COUNT(*) FROM users');
$adminUsers = $countRows($pdo, "SELECT COUNT(*) FROM users WHERE role IN ('admin','super_admin')");
$connectedApps = 6;
$todayVisitors = 0;
try {
    $todayVisitors = (int)(beyond_analytics_summary($pdo, 1)['today_visitors'] ?? 0);
} catch (Throwable $error) {
    error_log('Visitor metric unavailable: ' . $error->getMessage());
}

require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content overview-page">
  <div class="page-heading">
    <div>
      <p class="eyebrow">Live administration</p>
      <h1><?php require __DIR__ . '/../includes/admin-greeting.php'; ?></h1>
      <p class="muted">Monitor the Beyond ecosystem and reach your publishing and account tools from one place.</p>
    </div>
    <div class="page-status" aria-label="System status operational">
      <span class="status-dot" aria-hidden="true"></span>
      <div><strong>All systems operational</strong><small>PHP and SQLite connected</small></div>
    </div>
  </div>

  <div class="metrics-grid" aria-label="Administration metrics">
    <article class="tile">
      <div class="tile-head">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg></span>
        <span class="metric-trend">Live</span>
      </div>
      <div class="metric-value"><?= number_format($totalUsers) ?></div>
      <p>Total users</p>
    </article>

    <article class="tile">
      <div class="tile-head">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg></span>
        <span class="metric-trend">Secure</span>
      </div>
      <div class="metric-value"><?= number_format($adminUsers) ?></div>
      <p>Administrators</p>
    </article>

    <article class="tile">
      <div class="tile-head">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg></span>
        <span class="metric-trend">Shared ID</span>
      </div>
      <div class="metric-value"><?= number_format($connectedApps) ?></div>
      <p>Connected apps</p>
    </article>

    <article class="tile">
      <div class="tile-head">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-7"/></svg></span>
        <span class="metric-trend">Today</span>
      </div>
      <div class="metric-value"><?= number_format($todayVisitors) ?></div>
      <p>Unique visitors</p>
    </article>

    <article class="tile">
      <div class="tile-head">
        <span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/><circle cx="12" cy="12" r="9"/></svg></span>
        <span class="metric-trend">Healthy</span>
      </div>
      <div class="metric-value ok">Good</div>
      <p>System status</p>
    </article>
  </div>

  <div class="overview-layout">
    <article class="card">
      <div class="card-heading">
        <h2>Connected apps</h2>
        <a href="apps.php">View ecosystem</a>
      </div>

      <div class="app-row">
        <div class="app-meta"><span class="app-avatar">ID</span><div><strong>Beyond ID</strong><small>Identity, profiles, and access</small></div></div>
        <span class="badge ok">Online</span>
      </div>
      <div class="app-row">
        <div class="app-meta"><span class="app-avatar">DB</span><div><strong>Daily Breath</strong><small>Verse, Bible, journal, and practice</small></div></div>
        <span class="badge ok">Online</span>
      </div>
      <div class="app-row">
        <div class="app-meta"><span class="app-avatar">BF</span><div><strong>Beyond French</strong><small>Daily phrases and language learning</small></div></div>
        <span class="badge ok">Online</span>
      </div>
      <div class="app-row">
        <div class="app-meta"><span class="app-avatar">BT</span><div><strong>Beyond Tattoo</strong><small>Stencil and artist ecosystem</small></div></div>
        <span class="badge warn">Beta</span>
      </div>
    </article>

    <article class="card">
      <div class="card-heading">
        <h2>Quick tools</h2>
        <a href="review.php">Review hub</a>
      </div>
      <div class="tools-list">
        <a class="tool-link" href="verse-generator.php"><span>Verse of the Day Generator</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="french-generator.php"><span>Francais du Jour Generator</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="users.php"><span>User Manager</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="analytics.php"><span>Visitor Analytics</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="database.php"><span>Database Explorer</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="settings.php"><span>Themes and Appearance</span><span aria-hidden="true">&rarr;</span></a>
      </div>
    </article>
  </div>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
