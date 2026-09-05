<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$credentialId = strtoupper((string)($_GET['id'] ?? ''));
$credential = academy_credential($credentialId);
if (!$credential || (int)$credential['user_id'] !== $userId) {
    http_response_code(404);
    exit('Certificate not found.');
}
$course = academy_course((string)$credential['course_slug']);
if (!$course) {
    http_response_code(404);
    exit('Certificate pathway not found.');
}
if (($credential['approval_status'] ?? 'approved') !== 'approved') {
    bos_page_start('Beyond Academy', 'Certificate under review', 'Your certification request is being reviewed.');
    ?>
    <main class="academy-shell"><section class="verify-card"><span class="academy-kicker">Certificate request received</span><h1>Your certificate is under review.</h1><p>A Beyond Imagination administrator will review your completion. You should receive your certificate within 24 hours.</p><a class="academy-action" href="<?=e(beyond_url('academy/dashboard.php'))?>">Return to dashboard</a></section></main>
    <?php
    bos_page_end();
    exit;
}
$verifyUrl = beyond_url('academy/verify.php?id=' . rawurlencode($credentialId));
bos_page_start('Beyond Academy', $course['title'] . ' Certificate', 'A verifiable Beyond-issued skills certificate.');
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css'))?>">
<main class="academy-shell">
  <header class="academy-top"><a href="<?=e(beyond_url('academy/dashboard.php'))?>"><strong>← Learner dashboard</strong></a></header>
  <section class="credential" aria-labelledby="certificate-title">
    <div class="credential-mark" aria-hidden="true">B</div>
    <p>BEYOND ACADEMY PRESENTS THIS</p>
    <h1 id="certificate-title">Beyond-Issued<br>Skills Certificate</h1>
    <p>This confirms that</p>
    <h2><?=e($credential['learner_name'])?></h2>
    <p>completed the learning pathway and passed the final assessment for</p>
    <h2><?=e($course['title'])?></h2>
    <p>Assessment score: <?=e((string)$credential['score'])?>/<?=count($course['questions'])?> · Issued <?=e(date('F j, Y', strtotime((string)$credential['issued_at'])))?></p>
    <span class="credential-id"><?=e($credentialId)?></span>
    <p class="credential-note">Verify at <?=e($verifyUrl)?></p>
    <p class="credential-note">This Beyond-issued skills certificate is not a degree, government accreditation, or professional licence.</p>
  </section>
  <div class="credential-actions">
    <button class="academy-action" type="button" onclick="window.print()">Download / print PDF</button>
    <button class="academy-action secondary" id="shareCredential" type="button">Share certificate</button>
    <a class="academy-action secondary" href="<?=e($verifyUrl)?>">Public verification</a>
  </div>
</main>
<script>
document.getElementById('shareCredential').addEventListener('click', async () => {
  const data = {title: <?=json_encode($course['title'] . ' — Beyond Certificate')?>, text: <?=json_encode('I earned a Beyond-issued ' . $course['title'] . ' skills certificate.')?>, url: <?=json_encode($verifyUrl)?>};
  if (navigator.share) { try { await navigator.share(data); } catch (error) {} }
  else { await navigator.clipboard.writeText(data.url); alert('Verification link copied.'); }
});
</script>
<?php bos_page_end(); ?>
