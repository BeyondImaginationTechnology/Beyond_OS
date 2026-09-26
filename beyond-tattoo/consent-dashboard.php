<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require_login();
$user = bt_current_user();
if (!$user || ($user['account_type'] ?? '') !== 'owner') { http_response_code(403); exit('Studio owner access required.'); }
$createdLink = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $required = ['studio_name','artist_name','privacy_contact_name','privacy_contact_email','procedure_description','placement'];
        foreach ($required as $field) if (trim((string)($_POST[$field] ?? '')) === '') throw new InvalidArgumentException('Complete all required appointment details.');
        if (!filter_var(trim((string)$_POST['privacy_contact_email']), FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid privacy contact email.');
        $createdLink = bt_app_url('consent.php?t=' . rawurlencode(bt_create_consent_link(bt_current_user_id(), [
            'studio_name' => $_POST['studio_name'], 'artist_name' => $_POST['artist_name'],
            'privacy_contact_name' => $_POST['privacy_contact_name'], 'privacy_contact_email' => $_POST['privacy_contact_email'],
            'procedure_description' => $_POST['procedure_description'], 'placement' => $_POST['placement'],
            'appointment_date' => $_POST['appointment_date'] ?? '',
        ])));
    } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'The signing link could not be created. Please try again.'; }
}
$studioDefault = trim((string)($user['studio_name'] ?? '')) ?: 'Beyond Tattoo Studio';
$records = bt_list_consent_records(bt_current_user_id());
$pageTitle = 'Tattoo consent — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<main class="consent-shell">
  <a class="consent-back" href="dashboard.php">← Studio dashboard</a>
  <header class="consent-heading"><span class="eyebrow">Studio workspace · Client records</span><h1>Procedure consent</h1><p>Create a private, single-use signing link for an adult client. The link expires after 30 days; completed records are visible only to this studio account.</p></header>
  <?php if ($error !== ''): ?><div class="consent-notice is-error"><?=e($error)?></div><?php endif; ?>
  <?php if ($createdLink !== ''): ?><section class="consent-notice"><strong>Signing link ready</strong><p>Share this secure one-time link with your client. It expires in 30 days and cannot be reused.</p><label for="new-consent-link">Client link</label><div class="consent-copy-row"><input id="new-consent-link" readonly value="<?=e($createdLink)?>"><button class="consent-button" type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-consent-link').value)">Copy link</button></div></section><?php endif; ?>
  <section class="consent-panel"><h2>Create a client signing link</h2><form method="post" class="consent-grid"><input type="hidden" name="_csrf" value="<?=e(bt_csrf_token())?>">
    <label>Studio name<input name="studio_name" maxlength="200" value="<?=e($_POST['studio_name'] ?? $studioDefault)?>" required></label>
    <label>Artist name<input name="artist_name" maxlength="200" value="<?=e($_POST['artist_name'] ?? ($user['name'] ?? ''))?>" required></label>
    <label>Consent/privacy contact name<input name="privacy_contact_name" maxlength="200" value="<?=e($_POST['privacy_contact_name'] ?? ($user['name'] ?? ''))?>" required></label>
    <label>Consent/privacy contact email<input type="email" name="privacy_contact_email" maxlength="255" value="<?=e($_POST['privacy_contact_email'] ?? ($user['email'] ?? ''))?>" required></label>
    <label>Appointment date<input type="date" name="appointment_date" value="<?=e($_POST['appointment_date'] ?? '')?>"></label>
    <label>Placement<input name="placement" maxlength="160" value="<?=e($_POST['placement'] ?? '')?>" placeholder="e.g. left forearm" required></label>
    <label class="consent-full">Procedure / design description<textarea name="procedure_description" maxlength="500" rows="3" required><?=e($_POST['procedure_description'] ?? '')?></textarea></label>
    <button class="consent-button consent-full" type="submit">Create secure signing link →</button>
  </form><p class="consent-small">The online form is for adults 19 and older. Clients under 19 must use the downloadable form and complete guardian consent in person. Do not enter diagnoses or detailed health notes in the appointment description.</p></section>
  <section class="consent-panel"><div class="consent-list-heading"><div><span class="eyebrow">Private to your studio</span><h2>Signed records</h2></div><span><?=count($records)?> recent</span></div>
    <?php if (!$records): ?><p class="consent-muted">Completed client consents will appear here.</p><?php else: ?><div class="consent-record-list"><?php foreach ($records as $record): ?><a href="consent-record.php?id=<?=(int)$record['id']?>"><span><strong><?=e($record['client_name'])?></strong><small><?=e($record['studio_name'])?> · <?=e($record['placement'])?> · <?=e($record['signed_at'])?> UTC</small></span><b>View / print →</b></a><?php endforeach; ?></div><?php endif; ?>
  </section>
  <p class="consent-legal-note">Beyond Tattoo provides a consent-form workflow, not legal advice. Have the studio’s final procedure wording reviewed for its circumstances and applicable requirements.</p>
</main>
<style>
.consent-shell{max-width:900px;margin:0 auto;padding:32px 20px 90px;color:#f6f2eb}.consent-back{display:inline-block;margin-bottom:28px;color:#d8bd8a}.consent-heading h1{font-size:clamp(2.3rem,7vw,4rem);margin:8px 0}.consent-heading p,.consent-small,.consent-legal-note,.consent-muted{color:#b7b0a4;line-height:1.65}.consent-panel,.consent-notice{background:#141414;border:1px solid #3d3830;border-radius:18px;padding:24px;margin:22px 0}.consent-panel h2{margin:0 0 18px}.consent-notice{background:#211e18;border-color:#75603e}.consent-notice.is-error{background:#291817;border-color:#8a3935}.consent-notice p{margin:8px 0 12px}.consent-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.consent-grid label{display:grid;gap:7px;font-weight:700}.consent-grid input,.consent-grid textarea,.consent-copy-row input{width:100%;min-height:46px;padding:12px;border-radius:10px;border:1px solid #514b42;background:#0c0c0c;color:#fff;font:inherit}.consent-full{grid-column:1/-1}.consent-button{border:1px solid #bd9d63;background:#b68b4c;color:#15110b;border-radius:10px;padding:13px 16px;font-weight:850;cursor:pointer}.consent-copy-row{display:flex;gap:8px}.consent-copy-row input{flex:1}.consent-list-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}.consent-list-heading h2{margin:6px 0}.consent-record-list{display:grid}.consent-record-list a{display:flex;justify-content:space-between;gap:14px;padding:16px 0;border-top:1px solid #38332c;text-decoration:none;color:inherit}.consent-record-list small{display:block;color:#b7b0a4;margin-top:5px}.consent-record-list b{color:#d8bd8a;white-space:nowrap}.consent-small,.consent-legal-note{font-size:.83rem}.consent-legal-note{text-align:center;margin:28px auto;max-width:730px}@media(max-width:600px){.consent-grid{grid-template-columns:1fr}.consent-grid>*{grid-column:1}.consent-panel,.consent-notice{padding:18px}.consent-copy-row{flex-direction:column}.consent-record-list a{flex-direction:column}}
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
