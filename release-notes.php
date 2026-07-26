<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app-layout.php';
bos_page_start('Beyond OS', '2.3 Release Notes', 'Beyond OS 2.3 Academy and Certificates release notes.');
?>
<main class="bos-main">
  <section class="bos-hero">
    <span class="bos-kicker">Release 2.3</span>
    <h1>Learn it.<br>Prove it.</h1>
    <p>Beyond Academy now connects lesson progress, assessments, skills certificates, public verification, and achievement badges through Beyond ID.</p>
    <div class="bos-actions"><a class="bos-btn" href="<?=e(beyond_url('academy/dashboard.php'))?>">Open learner dashboard</a><a class="bos-btn secondary" href="<?=e(beyond_url('academy/verify.php'))?>">Verify a certificate</a></div>
  </section>
  <section class="bos-section">
    <span class="bos-kicker">Launch pathways</span><h2>Three practical starting points</h2>
    <div class="bos-grid">
      <?=bos_app_card('Essential Math','Numeracy, percentages, measurement and applied problem solving.','academy/pathway.php?course=essential-math','∑','Start learning','@atom')?>
      <?=bos_app_card('Web Development Foundations','HTML, CSS, JavaScript, responsive design and accessibility.','academy/pathway.php?course=web-development-foundations','CODE','Start learning','@atom')?>
      <?=bos_app_card('Personal Finance Foundations','Budgeting, saving, credit and fraud awareness.','academy/pathway.php?course=personal-finance-foundations','$','Start learning','@atom')?>
    </div>
  </section>
  <section class="bos-section">
    <span class="bos-kicker">Deployment gates</span><h2>Release safely</h2>
    <div class="bos-grid">
      <?=bos_app_card('Database migration','Back up production, then apply the 2.3 Academy migration.','docs/patch-notes/BEYOND-OS-2.3-ACADEMY-CERTIFICATES.md','DB','Review migration','@atom')?>
      <?=bos_app_card('Completion test','Test passing, failing, verification, ownership and repeat attempts.','docs/patch-notes/BEYOND-OS-2.3-ACADEMY-CERTIFICATES.md','QA','Review checklist','@atom')?>
      <?=bos_app_card('Credential language','Keep Beyond-issued wording visible and avoid accreditation claims.','academy/verify.php','✓','View verifier','@atom')?>
    </div>
  </section>
</main>
<?php bos_page_end(); ?>
