<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';

bos_require_admin();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!academy_verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Please reload and try again.');
    }
    $credentialId = strtoupper(trim((string)($_POST['credential_id'] ?? '')));
    if (academy_approve_credential($credentialId, (int)($_SESSION['user_id'] ?? 0))) {
        $message = 'Certificate approved and the learner was emailed.';
    } else {
        $message = 'That certificate is no longer awaiting approval.';
    }
}
$pending = academy_db()->query("SELECT * FROM academy_credentials WHERE approval_status='pending' AND revoked_at IS NULL ORDER BY approval_requested_at ASC")->fetchAll();
bos_page_start('Beyond Academy', 'Certificate approvals', 'Review pending Beyond Academy certifications.');
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell lesson-content">
  <header class="academy-top"><a href="<?=e(beyond_url('beyond-id/admin/'))?>"><strong>← Admin</strong></a></header>
  <section class="course-head"><span class="academy-kicker">Administrator review</span><h1>Certificate approvals</h1><p>Approve only verified course completions. Approval emails the learner a link to their certificate.</p></section>
  <?php if ($message): ?><p class="academy-notice" role="status"><?=e($message)?></p><?php endif; ?>
  <?php if (!$pending): ?><section class="verify-card"><h2>No certificate requests are waiting.</h2></section>
  <?php else: ?><section class="assessment-review"><h2><?=count($pending)?> pending request<?=count($pending) === 1 ? '' : 's'?></h2><?php foreach ($pending as $credential): $course = academy_course((string)$credential['course_slug']); ?><article><div><strong><?=e((string)$credential['learner_name'])?></strong><p><?=e((string)($course['title'] ?? $credential['course_slug']))?> · Score <?=e((string)$credential['score'])?>/<?=count($course['questions'] ?? [])?><br><?=e((string)$credential['learner_email'])?> · Requested <?=e((string)$credential['approval_requested_at'])?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(academy_csrf())?>"><input type="hidden" name="credential_id" value="<?=e((string)$credential['credential_id'])?>"><button class="academy-action" type="submit">Approve certificate</button></form></article><?php endforeach; ?></section><?php endif; ?>
</main>
<?php bos_page_end(); ?>
