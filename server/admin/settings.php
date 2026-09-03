<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/deployment.php';
Auth::requireLogin();

$user = Auth::user();
$canDeploy = is_array($user) && in_array((string)($user['role'] ?? ''), ['admin', 'super_admin'], true);
$notice = ''; $noticeType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'queue_deployment') {
    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) { $notice = 'The security token expired. Reload the page and try again.'; $noticeType = 'error'; }
    elseif (!$canDeploy) { http_response_code(403); $notice = 'Only administrators can request a deployment.'; $noticeType = 'error'; }
    else {
        $queued = beyond_queue_deployment($user ?? []); $notice = $queued['message']; $noticeType = $queued['ok'] ? 'success' : 'error';
        Auth::log((int)($user['id'] ?? 0), $queued['ok'] ? 'deployment_queued' : 'deployment_queue_failed', $queued['message']);
    }
}

$projectRoot = dirname(__DIR__, 2);
$repository = beyond_git_state($projectRoot);
$deployment = beyond_deployment_public_status(beyond_deployment_status());
$branch = $deployment['branch'] ?: ($repository['branch'] ?: 'main');
$commit = $deployment['commit'] ?: $repository['commit'];
$timestamp = $deployment['finished_at'] ?: ($deployment['started_at'] ?: $deployment['requested_at']);
$result = $deployment['result'];
$resultLabels = ['never' => 'Not run', 'queued' => 'Queued', 'running' => 'Running', 'success' => 'Successful', 'failed' => 'Failed'];
$resultLabel = $resultLabels[$result] ?? ucfirst($result);
$resultClass = $result === 'success' ? 'deploy-good' : ($result === 'failed' ? 'deploy-bad' : 'deploy-pending');
$startCpUrl = 'https://www.startcp.com/services/0cd6006873ea5ed2/files/git-version-control';
require __DIR__ . '/_header.php';
?>
<style>
.deployment-card{margin-top:20px}.deployment-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.deployment-stat{padding:15px;border:1px solid #263c5e;border-radius:16px;background:#091525}.deployment-stat span{display:block;margin-bottom:7px;color:#91a4c1;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.deployment-stat strong{display:block;overflow-wrap:anywhere}.deploy-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.deploy-link{display:inline-flex;text-decoration:none}.deploy-secondary{background:#172a45;border:1px solid #355176}.deploy-status{display:inline-flex;padding:7px 11px;border-radius:999px;font-size:13px;font-weight:900}.deploy-good{background:#123f2d;color:#7dffb1}.deploy-bad{background:#4b0b1c;color:#ffd2dc}.deploy-pending{background:#3a3212;color:#ffe58a}.deploy-help{margin-top:18px;padding-top:16px;border-top:1px solid #243650}.deploy-help code{color:#a9e9ff}.alert-success{background:#123f2d;color:#aaffca;border:1px solid #26714d}@media(max-width:900px){.deployment-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.deployment-grid{grid-template-columns:1fr}}
</style>
<h1>Settings</h1>
<?php if ($notice !== ''): ?><div class="alert <?= $noticeType === 'success' ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<div class="card"><p class="muted">Manage protected Beyond OS operational settings.</p></div>
<section class="card deployment-card" aria-labelledby="deployment-title">
  <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
    <div><h2 id="deployment-title" style="margin-top:0">Deployments</h2><p class="muted">Queue a safe production deployment from the checked-out <code>main</code> branch.</p></div>
    <span class="deploy-status <?= htmlspecialchars($resultClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resultLabel, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <div class="deployment-grid">
    <div class="deployment-stat"><span>Branch</span><strong><?= htmlspecialchars($branch ?: 'Unknown', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="deployment-stat"><span>Commit</span><strong><code><?= htmlspecialchars($commit !== '' ? substr($commit, 0, 12) : 'Unknown', ENT_QUOTES, 'UTF-8') ?></code></strong></div>
    <div class="deployment-stat"><span>Timestamp</span><strong><?= htmlspecialchars($timestamp !== '' ? $timestamp : 'No queued deployment', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="deployment-stat"><span>Last result</span><strong><?= htmlspecialchars($deployment['message'] ?: $resultLabel, ENT_QUOTES, 'UTF-8') ?></strong></div>
  </div>
  <div class="deploy-actions">
    <?php if ($canDeploy): ?><form method="post" onsubmit="return confirm('Queue deployment of the latest main branch to production?');"><input type="hidden" name="csrf" value="<?= htmlspecialchars(Auth::csrf(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="queue_deployment"><button class="btn" type="submit"<?= in_array($result, ['queued', 'running'], true) ? ' disabled' : '' ?>>Queue production deploy</button></form><?php endif; ?>
    <a class="btn deploy-link deploy-secondary" href="<?= htmlspecialchars($startCpUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Open StartCP Deploy ↗</a>
  </div>
  <?php if (!$canDeploy): ?><p class="muted">Deployment requests require an administrator account.</p><?php endif; ?>
  <div class="deploy-help muted">The web request never executes shell commands. A locked CLI cron worker processes the queue and preserves <code>var/</code>, <code>config/live.php</code>, repository metadata, and development-only artifacts.</div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
