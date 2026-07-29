<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/visitor-analytics.php';

$title = 'Visitor Analytics';
$allowedRanges = [7, 30, 90];
$rangeDays = (int)($_GET['range'] ?? 30);
if (!in_array($rangeDays, $allowedRanges, true)) $rangeDays = 30;

$analyticsReady = true;
$analyticsError = '';
$summary = [
    'active_now' => 0, 'today_views' => 0, 'today_visitors' => 0, 'range_views' => 0, 'range_visitors' => 0,
    'range_sessions' => 0, 'signed_in_views' => 0, 'pages_per_session' => 0.0,
    'start_utc' => '', 'end_utc' => '',
];
$trend = $topPages = $topApps = $referrers = $devices = $browsers = $recent = $liveVisitors = [];
$currentVisitorHash = beyond_analytics_current_visitor_hash();

try {
    $summary = beyond_analytics_summary($pdo, $rangeDays);
    $trend = beyond_analytics_daily_trend($pdo, min($rangeDays, 30));
    $topPages = beyond_analytics_grouped($pdo, 'path', $summary['start_utc'], $summary['end_utc'], 10);
    $topApps = beyond_analytics_grouped($pdo, 'app_slug', $summary['start_utc'], $summary['end_utc'], 8);
    $referrers = beyond_analytics_grouped($pdo, 'referrer_host', $summary['start_utc'], $summary['end_utc'], 8, "AND referrer_host IS NOT NULL AND referrer_host <> 'internal'");
    $devices = beyond_analytics_grouped($pdo, 'device_type', $summary['start_utc'], $summary['end_utc'], 5);
    $browsers = beyond_analytics_grouped($pdo, 'browser', $summary['start_utc'], $summary['end_utc'], 6);
    $recentStmt = $pdo->query(
        'SELECT path,page_title,app_slug,visitor_hash,session_hash,device_type,browser,operating_system,
                referrer_host,referrer_path,country_code,ip_address,viewport_width,client_language,
                client_timezone,occurred_at,last_seen_at,user_id
         FROM visitor_traffic
         ORDER BY COALESCE(last_seen_at,occurred_at) DESC
         LIMIT 75'
    );
    $allRecent = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $recent = array_slice($allRecent, 0, 30);
    $activeCutoff = time() - 5 * 60;
    $seenSessions = [];
    foreach ($allRecent as $row) {
        $lastSeenValue = (string)($row['last_seen_at'] ?? $row['occurred_at'] ?? '');
        $lastSeen = $lastSeenValue !== '' ? (strtotime($lastSeenValue . ' UTC') ?: 0) : 0;
        $sessionHash = (string)($row['session_hash'] ?? '');
        if ($lastSeen < $activeCutoff || $sessionHash === '' || isset($seenSessions[$sessionHash])) continue;
        $seenSessions[$sessionHash] = true;
        $liveVisitors[] = $row;
    }
} catch (Throwable $exception) {
    $analyticsReady = false;
    $analyticsError = $exception->getMessage();
    error_log('Admin visitor analytics unavailable: ' . $analyticsError);
}

$maxTrendViews = 1;
foreach ($trend as $day) $maxTrendViews = max($maxTrendViews, (int)$day['views']);
$percent = static fn(int $value, int $total): int => $total > 0 ? max(2, (int)round(($value / $total) * 100)) : 0;
$formatApp = static function (?string $slug): string {
    $slug = trim((string)$slug);
    if ($slug === '') return 'Beyond OS';
    return ucwords(str_replace('-', ' ', $slug));
};
$localTime = static function (?string $utc): string {
    if (!$utc) return '—';
    try {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Vancouver'))->format('M j, g:i:s A');
    } catch (Throwable $exception) {
        return (string)$utc;
    }
};
$timeAgo = static function (?string $utc): string {
    $timestamp = $utc ? strtotime($utc . ' UTC') : false;
    if ($timestamp === false) return 'unknown';
    $seconds = max(0, time() - $timestamp);
    if ($seconds < 10) return 'just now';
    if ($seconds < 60) return $seconds . 's ago';
    if ($seconds < 3600) return intdiv($seconds, 60) . 'm ago';
    return intdiv($seconds, 3600) . 'h ago';
};
$visitorLabel = static function (?string $hash): string {
    $hash = strtoupper(substr((string)$hash, 0, 8));
    return $hash === '' ? 'Unknown visitor' : 'Visitor ' . $hash;
};
$visibleIp = static function (array $row): string {
    $occurred = strtotime((string)($row['occurred_at'] ?? '') . ' UTC') ?: 0;
    if ($occurred < time() - 30 * 86400) return 'Expired';
    return (string)($row['ip_address'] ?: 'IP unavailable');
};

require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content analytics-page">
  <div class="page-heading analytics-heading">
    <div>
      <p class="eyebrow">First-party traffic</p>
      <h1>Visitor analytics</h1>
      <p class="muted">See who is on the site now, recognize your own browser, and inspect recent traffic from this private admin view.</p>
    </div>
    <form class="analytics-range" method="get">
      <label for="analytics-range">Reporting window</label>
      <select id="analytics-range" name="range" onchange="this.form.submit()">
        <?php foreach ($allowedRanges as $days): ?>
          <option value="<?= $days ?>"<?= $rangeDays === $days ? ' selected' : '' ?>>Last <?= $days ?> days</option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if (!$analyticsReady): ?>
    <article class="card analytics-empty">
      <h2>Analytics is ready to install</h2>
      <p>The visitor table is not available yet. Deploy the included database migration, or leave automatic migrations enabled so Beyond ID creates it on the next database connection.</p>
    </article>
  <?php else: ?>
    <div class="metrics-grid analytics-metrics" aria-label="Visitor traffic summary">
      <article class="tile analytics-live-metric"><div class="tile-head"><span class="stat-icon">●</span><span class="metric-trend">Last 5 min</span></div><div class="metric-value"><?= number_format((int)$summary['active_now']) ?></div><p>Active visitors</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">👤</span><span class="metric-trend">Today</span></div><div class="metric-value"><?= number_format((int)$summary['today_visitors']) ?></div><p>Unique visitors</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">↗</span><span class="metric-trend">Today</span></div><div class="metric-value"><?= number_format((int)$summary['today_views']) ?></div><p>Page views</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">◎</span><span class="metric-trend"><?= $rangeDays ?> days</span></div><div class="metric-value"><?= number_format((int)$summary['range_visitors']) ?></div><p>Unique visitors</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">◫</span><span class="metric-trend"><?= $rangeDays ?> days</span></div><div class="metric-value"><?= number_format((int)$summary['range_views']) ?></div><p>Total page views</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">⌁</span><span class="metric-trend">Engagement</span></div><div class="metric-value"><?= number_format((float)$summary['pages_per_session'], 1) ?></div><p>Pages per session</p></article>
      <article class="tile"><div class="tile-head"><span class="stat-icon">ID</span><span class="metric-trend">Beyond ID</span></div><div class="metric-value"><?= number_format((int)$summary['signed_in_views']) ?></div><p>Signed-in page views</p></article>
    </div>

    <article class="card analytics-live-card">
      <div class="card-heading analytics-card-heading">
        <div><h2>On the site now</h2><p>Presence updates about once per minute while a public page remains visible.</p></div>
        <span class="badge ok">● Live · auto-refresh</span>
      </div>
      <?php if ($liveVisitors === []): ?>
        <div class="analytics-zero analytics-live-empty"><strong>No active visitors right now</strong><span>Open a public page in another tab to verify your visit.</span></div>
      <?php else: ?>
        <div class="analytics-live-grid">
          <?php foreach ($liveVisitors as $row):
              $isYou = $currentVisitorHash !== null && hash_equals($currentVisitorHash, (string)$row['visitor_hash']);
          ?>
            <section class="analytics-live-visitor<?= $isYou ? ' is-you' : '' ?>">
              <div class="live-visitor-head">
                <div><strong><?= e($visitorLabel((string)$row['visitor_hash'])) ?></strong><small><?= e($timeAgo((string)($row['last_seen_at'] ?? $row['occurred_at']))) ?></small></div>
                <?= $isYou ? '<span class="badge ok">You</span>' : ($row['user_id'] !== null ? '<span class="badge ok">Signed in</span>' : '<span class="badge">Guest</span>') ?>
              </div>
              <code class="analytics-ip"><?= e($visibleIp($row)) ?></code>
              <a href="<?= e((string)$row['path']) ?>" target="_blank" rel="noopener"><?= e((string)$row['path']) ?> ↗</a>
              <p><?= e(ucfirst((string)$row['device_type'])) ?> · <?= e((string)$row['operating_system']) ?> · <?= e((string)$row['browser']) ?><?php if ((int)$row['viewport_width'] > 0): ?> · <?= number_format((int)$row['viewport_width']) ?>px<?php endif; ?></p>
              <p><?= e((string)($row['country_code'] ?: 'Unknown country')) ?> · <?= e((string)($row['client_language'] ?: 'Unknown language')) ?> · <?= e((string)($row['client_timezone'] ?: 'Unknown timezone')) ?></p>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>

    <article class="card traffic-chart-card">
      <div class="card-heading analytics-card-heading">
        <div><h2>Daily traffic</h2><p>Page views and unique visitors in Vancouver time.</p></div>
        <div class="chart-legend"><span><i class="legend-view"></i>Views</span><span><i class="legend-visitor"></i>Visitors</span></div>
      </div>
      <?php if ($trend === [] || (int)$summary['range_views'] === 0): ?>
        <div class="analytics-zero"><strong>No traffic recorded yet</strong><span>New page views will begin appearing after this build is deployed.</span></div>
      <?php else: ?>
        <div class="traffic-chart" role="img" aria-label="Daily visitor traffic chart">
          <?php foreach ($trend as $day):
              $viewHeight = max(3, (int)round(((int)$day['views'] / $maxTrendViews) * 100));
              $visitorHeight = max(2, (int)round(((int)$day['visitors'] / $maxTrendViews) * 100));
          ?>
            <div class="traffic-day" title="<?= e($day['date']) ?>: <?= number_format((int)$day['views']) ?> views, <?= number_format((int)$day['visitors']) ?> visitors">
              <div class="traffic-bars"><span class="traffic-view" style="height:<?= $viewHeight ?>%"></span><span class="traffic-visitor" style="height:<?= $visitorHeight ?>%"></span></div>
              <small><?= e($day['label']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </article>

    <div class="analytics-two-column">
      <article class="card analytics-list-card">
        <div class="card-heading"><h2>Top pages</h2><span><?= $rangeDays ?> days</span></div>
        <?php if ($topPages === []): ?><p class="analytics-list-empty">No page data yet.</p><?php endif; ?>
        <?php foreach ($topPages as $row): ?>
          <div class="analytics-rank-row">
            <div class="rank-copy"><strong><?= e((string)$row['label']) ?></strong><small><?= number_format((int)$row['visitors']) ?> visitors</small></div>
            <div class="rank-value"><?= number_format((int)$row['views']) ?></div>
            <span class="rank-bar"><i style="width:<?= $percent((int)$row['views'], (int)$summary['range_views']) ?>%"></i></span>
          </div>
        <?php endforeach; ?>
      </article>

      <article class="card analytics-list-card">
        <div class="card-heading"><h2>Apps and sections</h2><span><?= $rangeDays ?> days</span></div>
        <?php if ($topApps === []): ?><p class="analytics-list-empty">No app traffic yet.</p><?php endif; ?>
        <?php foreach ($topApps as $row): ?>
          <div class="analytics-rank-row">
            <div class="rank-copy"><strong><?= e($formatApp((string)$row['label'])) ?></strong><small><?= number_format((int)$row['visitors']) ?> visitors</small></div>
            <div class="rank-value"><?= number_format((int)$row['views']) ?></div>
            <span class="rank-bar"><i style="width:<?= $percent((int)$row['views'], (int)$summary['range_views']) ?>%"></i></span>
          </div>
        <?php endforeach; ?>
      </article>
    </div>

    <div class="analytics-three-column">
      <article class="card analytics-mini-card"><h2>Referrers</h2><?php if ($referrers === []): ?><p>Mostly direct or internal traffic so far.</p><?php endif; ?><?php foreach ($referrers as $row): ?><div><span><?= e((string)$row['label']) ?></span><strong><?= number_format((int)$row['views']) ?></strong></div><?php endforeach; ?></article>
      <article class="card analytics-mini-card"><h2>Devices</h2><?php if ($devices === []): ?><p>No device data yet.</p><?php endif; ?><?php foreach ($devices as $row): ?><div><span><?= e(ucfirst((string)$row['label'])) ?></span><strong><?= number_format((int)$row['views']) ?></strong></div><?php endforeach; ?></article>
      <article class="card analytics-mini-card"><h2>Browsers</h2><?php if ($browsers === []): ?><p>No browser data yet.</p><?php endif; ?><?php foreach ($browsers as $row): ?><div><span><?= e((string)$row['label']) ?></span><strong><?= number_format((int)$row['views']) ?></strong></div><?php endforeach; ?></article>
    </div>

    <article class="card analytics-recent-card">
      <div class="card-heading"><div><h2>Recent visits</h2><p>Latest page views and last activity, shown in Vancouver time.</p></div><span class="badge">30 visits</span></div>
      <div class="analytics-table-wrap">
        <table class="analytics-table">
          <thead><tr><th>Last seen</th><th>Visitor</th><th>IP address</th><th>Page</th><th>Device</th><th>Source</th><th>Identity</th></tr></thead>
          <tbody>
          <?php if ($recent === []): ?><tr><td colspan="7">No visits recorded yet.</td></tr><?php endif; ?>
          <?php foreach ($recent as $row):
              $isYou = $currentVisitorHash !== null && hash_equals($currentVisitorHash, (string)$row['visitor_hash']);
              $source = ($row['referrer_host'] ?? '') === 'internal'
                  ? 'Internal' . (!empty($row['referrer_path']) ? ' · ' . $row['referrer_path'] : '')
                  : (($row['referrer_host'] ?? '') ?: 'Direct');
          ?>
            <tr>
              <td><strong><?= e($timeAgo((string)($row['last_seen_at'] ?? $row['occurred_at']))) ?></strong><small class="analytics-cell-detail"><?= e($localTime((string)($row['last_seen_at'] ?? $row['occurred_at']))) ?></small></td>
              <td><strong><?= e($visitorLabel((string)$row['visitor_hash'])) ?></strong><small class="analytics-cell-detail"><?= e($formatApp((string)$row['app_slug'])) ?></small></td>
              <td><code class="analytics-ip"><?= e($visibleIp($row)) ?></code><small class="analytics-cell-detail"><?= e((string)($row['country_code'] ?: '—')) ?></small></td>
              <td><a href="<?= e((string)$row['path']) ?>" target="_blank" rel="noopener"><code><?= e((string)$row['path']) ?></code></a><small class="analytics-cell-detail"><?= e((string)($row['page_title'] ?: 'Untitled page')) ?></small></td>
              <td><?= e(ucfirst((string)$row['device_type'])) ?> · <?= e((string)$row['browser']) ?><small class="analytics-cell-detail"><?= e((string)$row['operating_system']) ?><?php if ((int)$row['viewport_width'] > 0): ?> · <?= number_format((int)$row['viewport_width']) ?>px<?php endif; ?></small></td>
              <td><?= e($source) ?><small class="analytics-cell-detail"><?= e((string)($row['client_timezone'] ?: 'Unknown timezone')) ?></small></td>
              <td><?= $isYou ? '<span class="badge ok">You</span>' : ($row['user_id'] !== null ? '<span class="badge ok">Signed in</span>' : '<span class="badge">Guest</span>') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="analytics-privacy-note">
      <strong>Private operational data</strong>
      <span>IP addresses are visible only in this administrator view and are automatically cleared after 30 days. Visitor and session IDs remain one-way hashed. Complete user-agent strings and URL query parameters are not stored, and Do Not Track is respected.</span>
    </article>
  <?php endif; ?>
</section>
<script>
try {
  const savedScroll = Number(sessionStorage.getItem('analytics-scroll') || 0);
  if (savedScroll > 0) {
    window.requestAnimationFrame(() => window.scrollTo({top: savedScroll, behavior: 'auto'}));
    sessionStorage.removeItem('analytics-scroll');
  }
} catch (_) {}
window.setTimeout(() => {
  if (document.visibilityState !== 'visible') return;
  try { sessionStorage.setItem('analytics-scroll', String(window.scrollY)); } catch (_) {}
  window.location.reload();
}, 60_000);
</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
