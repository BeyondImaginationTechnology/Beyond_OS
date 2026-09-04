<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once dirname(__DIR__, 2) . '/server/lib/deployment.php';

$notice = '';
$noticeType = '';
$admin = [
    'id' => (int)($_SESSION['user_id'] ?? 0),
    'email' => (string)($_SESSION['email'] ?? 'admin'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'queue_deployment') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $notice = 'Your security token expired. Refresh the page and try again.';
        $noticeType = 'danger';
    } else {
        $queued = beyond_queue_deployment($admin);
        $notice = $queued['message'];
        $noticeType = $queued['ok'] ? 'ok' : 'danger';
        if ($queued['ok']) {
            log_activity($pdo, $admin['id'] ?: null, 'deployment_queued');
        }
    }
}

$repository = beyond_git_state(dirname(__DIR__, 2));
$deployment = beyond_deployment_public_status(beyond_deployment_status());
$branch = $deployment['branch'] ?: ($repository['branch'] ?: 'main');
$commit = $deployment['commit'] ?: $repository['commit'];
$timestamp = $deployment['finished_at'] ?: ($deployment['started_at'] ?: $deployment['requested_at']);
$result = $deployment['result'];
$resultLabels = ['never' => 'Not run', 'queued' => 'Queued', 'running' => 'Running', 'success' => 'Successful', 'failed' => 'Failed'];
$resultLabel = $resultLabels[$result] ?? ucfirst($result);
$resultClass = $result === 'success' ? 'ok' : ($result === 'failed' ? 'danger' : 'warn');
$startCpUrl = 'https://www.startcp.com/services/0cd6006873ea5ed2/files/git-version-control';

$title = 'Deployments';
require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<style>
.deployments-layout{max-width:1080px}.deployment-summary{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap}.deployment-summary p{margin:7px 0 0}.deployment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:22px 0}.deployment-stat{padding:16px;border:1px solid var(--border);border-radius:16px;background:var(--panel2)}.deployment-stat span{display:block;margin-bottom:6px;color:var(--muted);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.deployment-stat strong{display:block;overflow-wrap:anywhere}.deployment-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.deployment-actions form{margin:0}.deployment-actions button:disabled{cursor:not-allowed;opacity:.55}.deployment-note{margin-top:20px;padding-top:17px;border-top:1px solid var(--border);font-size:12px}.deployment-note code{color:var(--text)}@media(max-width:600px){.deployment-grid{grid-template-columns:1fr}}
</style>
<section class="content deployments-layout">
  <div class="page-heading deployment-summary">
    <div>
      <p class="eyebrow">Operations</p>
      <h1>Deployments</h1>
      <p class="muted">Safely queue the checked-out <code>main</code> branch for production.</p>
    </div>
    <span class="badge <?= e($resultClass) ?>"><?= e($resultLabel) ?></span>
  </div>

  <?php if ($notice !== ''): ?><div class="card" role="status" style="margin-bottom:18px;border-color:var(--<?= e($noticeType === 'ok' ? 'good' : 'bad') ?>)"><?= e($notice) ?></div><?php endif; ?>

  <section class="card" aria-labelledby="deployment-details-title">
    <div class="card-heading"><h2 id="deployment-details-title">Production status</h2></div>
    <div class="deployment-grid">
      <div class="deployment-stat"><span>Branch</span><strong><?= e($branch ?: 'Unknown') ?></strong></div>
      <div class="deployment-stat"><span>Commit</span><strong><code><?= e($commit !== '' ? substr($commit, 0, 12) : 'Unknown') ?></code></strong></div>
      <div class="deployment-stat"><span>Timestamp</span><strong><?= e($timestamp !== '' ? $timestamp : 'No queued deployment') ?></strong></div>
      <div class="deployment-stat"><span>Last result</span><strong><?= e($deployment['message'] ?: $resultLabel) ?></strong></div>
    </div>
    <div class="deployment-actions">
      <form method="post" onsubmit="return confirm('Queue deployment of the latest main branch to production?');">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="queue_deployment">
        <button type="submit"<?= in_array($result, ['queued', 'running'], true) ? ' disabled' : '' ?>>Queue production deploy</button>
      </form>
      <a class="btn btn-secondary" href="<?= e($startCpUrl) ?>" target="_blank" rel="noopener noreferrer">Open StartCP Deploy ↗</a>
    </div>
    <p class="muted deployment-note">This page only writes a protected queue request. A locked CLI cron worker performs deployment, preserving <code>var/</code>, live configuration, repository metadata, and development-only files.</p>
  </section>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
