<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/ecosystem.php';
$credentialId = strtoupper(trim((string)($_GET['id'] ?? '')));
$credential = $credentialId !== '' ? academy_credential($credentialId) : null;
$course = $credential ? academy_course((string)$credential['course_slug']) : null;
beyond_nav_bootstrap('Beyond Academy');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#050817"><title>Verify a Beyond Certificate</title><meta name="description" content="Publicly verify a Beyond-issued skills certificate."><link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>"><link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>"></head><body class="bos-page">
<main class="academy-shell">
  <section class="verify-card">
    <span class="academy-kicker">Public credential verification</span><h1>Verify a Beyond Certificate</h1>
    <form method="get"><label for="credential"><strong>Credential ID</strong></label><div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px"><input id="credential" name="id" required value="<?=e($credentialId)?>" placeholder="BVC-XXXXXX-XXXXXX-XXXXXX-XXXXXX" style="flex:1;min-width:250px;padding:13px;border:1px solid #41415a;border-radius:12px;background:#0d0d18;color:#fff"><button class="academy-action" type="submit">Verify</button></div></form>
    <?php if ($credentialId !== '' && $credential && $course): ?>
      <?php if (($credential['approval_status'] ?? 'approved') === 'approved' && empty($credential['revoked_at'])): ?><section aria-live="polite"><h2 class="verify-valid">✓ Valid Beyond-issued certificate</h2><dl><dt>Learner</dt><dd><?=e($credential['learner_name'])?></dd><dt>Pathway</dt><dd><?=e($course['title'])?></dd><dt>Issued</dt><dd><?=e(date('F j, Y', strtotime((string)$credential['issued_at'])))?></dd><dt>Assessment</dt><dd><?=e((string)$credential['score'])?>/<?=count($course['questions'])?></dd><dt>Credential ID</dt><dd><code><?=e($credentialId)?></code></dd></dl></section>
      <?php elseif (($credential['approval_status'] ?? 'approved') === 'pending'): ?><h2 class="verify-invalid">This certificate request is awaiting administrator approval.</h2>
      <?php else: ?><h2 class="verify-invalid">This credential has been revoked.</h2><?php endif; ?>
    <?php elseif ($credentialId !== ''): ?><h2 class="verify-invalid" aria-live="polite">Credential not found.</h2><p>Check the ID and try again. Beyond only confirms credentials returned as valid on this page.</p><?php endif; ?>
    <p class="academy-notice">Beyond-issued skills certificates confirm course completion and assessment performance. They are not degrees, government accreditation, or professional licences.</p>
  </section>
</main>
<script src="<?=e(beyond_url('assets/js/visitor-analytics.js'))?>" defer></script></body></html>
