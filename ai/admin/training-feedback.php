<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/admin-check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/training-feedback.php';
require_once __DIR__ . '/../../beyond-id/includes/functions.php';

$db = beyond_db();
jaguar_feedback_table($db);
$scope = is_string($_GET['scope'] ?? null) && in_array($_GET['scope'], ['jaguar', 'beyond-tattoo'], true) ? $_GET['scope'] : 'beyond-tattoo';
$status = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : 'pending';
$csrf = csrf_token();
$message = isset($_GET['saved']) ? 'Review action saved.' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Your secure session expired. Refresh and try again.');
    }
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    if (!$id || !in_array($action, ['approve', 'reject', 'delete'], true)) {
        http_response_code(422);
        exit('Invalid training review action.');
    }
    if ($action === 'delete') {
        $statement = $db->prepare('DELETE FROM jaguar_feedback_submissions WHERE id=?');
        $statement->execute([$id]);
        $message = 'Submission deleted.';
    } else {
        $statement = $db->prepare('UPDATE jaguar_feedback_submissions SET status=?,reviewed_by=?,reviewed_at=? WHERE id=?');
        $statement->execute([$action === 'approve' ? 'approved' : 'rejected', (int)$_SESSION['user_id'], gmdate('Y-m-d H:i:s'), $id]);
        $message = $action === 'approve' ? 'Approved for later export.' : 'Marked as rejected.';
    }
    header('Location: ?scope=' . rawurlencode($scope) . '&status=' . rawurlencode($status) . '&saved=1');
    exit;
}
if (isset($_GET['export']) && $_GET['export'] === 'jsonl') {
    $statement = $db->prepare("SELECT prompt,answer,correction,scope FROM jaguar_feedback_submissions WHERE status='approved' AND scope=? ORDER BY id ASC");
    $statement->execute([$scope]);
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Content-Disposition: attachment; filename="jaguar-' . $scope . '-approved.jsonl"');
    header('Cache-Control: no-store, private');
    foreach ($statement as $row) {
        $input = "User: " . $row['prompt'] . "\nPrevious Jaguar answer: " . $row['answer'];
        echo json_encode(['instruction' => 'Answer the user accurately and helpfully within the ' . $row['scope'] . ' product scope.', 'input' => $input, 'output' => $row['correction'], 'type' => $row['scope']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }
    exit;
}
$statement = $db->prepare('SELECT id,scope,mode,prompt,answer,correction,user_id,consent_at,status,created_at FROM jaguar_feedback_submissions WHERE scope=? AND status=? ORDER BY id DESC LIMIT 100');
$statement->execute([$scope, $status]);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
$countsStatement = $db->prepare('SELECT status,COUNT(*) AS total FROM jaguar_feedback_submissions WHERE scope=? GROUP BY status');
$countsStatement->execute([$scope]);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($countsStatement->fetchAll(PDO::FETCH_ASSOC) as $countRow) $counts[$countRow['status']] = (int)$countRow['total'];
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Jaguar training review</title><style>
body{margin:0;background:#0b0711;color:#f7f2fc;font:15px/1.55 system-ui,sans-serif}.wrap{max-width:1180px;margin:auto;padding:28px 20px 70px}a{color:#d6a3ff}.head{display:flex;justify-content:space-between;align-items:start;gap:18px}.head p,.muted{color:#b4a9c1}.filters,.stats,.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.filters{margin:22px 0}.filters a,.button{display:inline-block;padding:8px 13px;border:1px solid #49365a;border-radius:10px;background:#191123;color:#f8f3fc;text-decoration:none}.stats{margin:20px 0}.stat,.entry{padding:17px;border:1px solid #382747;border-radius:14px;background:#140e1d}.entry{margin:12px 0}.entry h2{font-size:15px}.entry pre{white-space:pre-wrap;overflow-wrap:anywhere;color:#dfd5e9;background:#0d0913;padding:12px;border-radius:9px;font:13px/1.5 system-ui,sans-serif}.actions form{display:inline}.actions button{cursor:pointer}.danger{color:#ffc2d1}.scope-status{display:flex;gap:10px;flex-wrap:wrap}.scope-status span{padding:5px 9px;border-radius:8px;background:#21182d}small{color:#b4a9c1}
</style></head><body><main class="wrap"><header class="head"><div><p class="muted">Jaguar · human review queue</p><h1>Training suggestions</h1><p class="muted">Submissions are stored only after the user submits an explicit correction and consent. Approval makes an example exportable; it does not update the live model.</p></div><a href="../chat.php">Back to chat</a></header>
<?php if ($message !== ''): ?><p role="status"><?= $e($message) ?></p><?php endif; ?>
<nav class="filters"><strong>Scope:</strong><a href="?scope=beyond-tattoo">Beyond Tattoo</a><a href="?scope=jaguar">Jaguar general</a><strong>Status:</strong><a href="?scope=<?= $e($scope) ?>&status=pending">Pending</a><a href="?scope=<?= $e($scope) ?>&status=approved">Approved</a><a href="?scope=<?= $e($scope) ?>&status=rejected">Rejected</a><a href="?scope=<?= $e($scope) ?>&export=jsonl">Export approved JSONL</a></nav>
<div class="stats"><?php foreach ($counts as $key => $count): ?><div class="stat"><strong><?= number_format($count) ?></strong> <?= $e(ucfirst($key)) ?></div><?php endforeach; ?></div>
<?php if (!$rows): ?><p class="muted">No <?= $e($status) ?> submissions for this scope.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="entry"><div class="scope-status"><span><?= $e($row['scope']) ?></span><span><?= $e($row['mode']) ?> mode</span><small>Submitted <?= $e($row['created_at']) ?> · user <?= $row['user_id'] === null ? 'guest' : '#' . (int)$row['user_id'] ?></small></div><h2>User prompt</h2><pre><?= $e($row['prompt']) ?></pre><h2>Jaguar response</h2><pre><?= $e($row['answer']) ?></pre><h2>Suggested answer</h2><pre><?= $e($row['correction']) ?></pre><small>Consent recorded: <?= $e($row['consent_at']) ?></small><div class="actions"><form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="button" name="action" value="approve">Approve</button><button class="button" name="action" value="reject">Reject</button><button class="button danger" name="action" value="delete" onclick="return confirm('Delete this submission permanently?')">Delete</button></form></div></article><?php endforeach; ?>
</main></body></html>
