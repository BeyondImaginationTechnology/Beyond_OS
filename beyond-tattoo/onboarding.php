<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require_login();

$user = bt_current_user();
$allowedRoles = ['client', 'artist', 'owner'];
$studioSetup = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? ($_POST['workspace'] ?? '') : ($_GET['workspace'] ?? '')) === 'studio';
$role = $studioSetup
    ? 'owner'
    : (string)(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? ($_POST['account_type'] ?? 'client') : ($user['account_type'] ?? 'client'));
if (!in_array($role, $allowedRoles, true)) $role = 'client';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city = trim((string)($_POST['city'] ?? ''));
    if ($city === '') {
        $error = 'Add your city or service area to continue.';
    } else {
        bt_save_profile(bt_current_user_id(), [
            'account_type' => $role,
            'city' => $city,
            'bio' => $_POST['bio'] ?? '',
            'styles' => $_POST['styles'] ?? '',
            'experience' => $_POST['experience'] ?? '',
            'studio_name' => $_POST['studio_name'] ?? '',
            'budget' => $_POST['budget'] ?? '',
            'availability' => $_POST['availability'] ?? '',
        ]);
        flash('success', 'Your Beyond Tattoo profile is ready.');
        redirect('dashboard.php');
    }
}
$pageTitle = 'Set up profile — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap onboarding-wrap"><div class="auth-card role-card-wide">
  <span class="eyebrow">Beyond ID connected • <?= $studioSetup ? 'Studio workspace' : 'Tattoo profile' ?></span>
  <h1><?= $studioSetup ? 'Set up your studio.' : 'How will you use Beyond Tattoo?' ?></h1>
  <p class="small"><?= $studioSetup ? 'Use your existing Beyond ID—there is no second Tattoo account. Add the shop details that will power your owner workspace.' : 'Choose a workspace and add the details that should power your Tattoo experience.' ?></p>
  <?php if (isset($error)): ?><div class="notice error-notice"><?= e($error) ?></div><?php endif; ?>
  <form class="form-grid" method="post">
    <input type="hidden" name="_csrf" value="<?= e(bt_csrf_token()) ?>">
    <?php if ($studioSetup): ?><input type="hidden" name="workspace" value="studio"><input type="hidden" name="account_type" value="owner"><?php endif; ?>
    <?php if (!$studioSetup): ?>
    <div class="role-picker">
      <label class="role-option <?= $role === 'client' ? 'selected' : '' ?>"><input type="radio" name="account_type" value="client" <?= $role === 'client' ? 'checked' : '' ?>><span class="role-symbol">🖼️</span><strong>Canvas</strong><small>Find artists, plan tattoos, and track healing.</small></label>
      <label class="role-option <?= $role === 'artist' ? 'selected' : '' ?>"><input type="radio" name="account_type" value="artist" <?= $role === 'artist' ? 'checked' : '' ?>><span class="role-symbol">🎨</span><strong>Artist</strong><small>Build a portfolio and connect with studios.</small></label>
      <label class="role-option <?= $role === 'owner' ? 'selected' : '' ?>"><input type="radio" name="account_type" value="owner" <?= $role === 'owner' ? 'checked' : '' ?>><span class="role-symbol">🏪</span><strong>Studio owner</strong><small>Manage a studio and artist opportunities.</small></label>
    </div>
    <?php else: ?>
    <div class="role-option selected studio-owner-confirm"><span class="role-symbol">🏪</span><strong>Studio owner workspace</strong><small>Manage your team, hiring, stencil library and public studio presence.</small></div>
    <?php endif; ?>
    <input class="input" name="city" placeholder="City or service area" required value="<?= e($user['city'] ?? '') ?>">
    <input class="input" name="styles" placeholder="Styles or interests" value="<?= e($user['styles'] ?? '') ?>">
    <input class="input" name="experience" placeholder="Experience (artists)" value="<?= e($user['experience'] ?? '') ?>">
    <input class="input" name="studio_name" placeholder="Studio name (owners)" value="<?= e($user['studio_name'] ?? '') ?>">
    <input class="input" name="availability" placeholder="Availability or hiring needs" value="<?= e($user['availability'] ?? '') ?>">
    <input class="input" name="budget" placeholder="Typical project budget (optional)" value="<?= e($user['budget'] ?? '') ?>">
    <textarea class="input" name="bio" rows="4" placeholder="Tell the community a little about you"><?= e($user['bio'] ?? '') ?></textarea>
    <button class="btn btn-primary" type="submit"><?= $studioSetup ? 'Open studio workspace →' : 'Save Tattoo profile →' ?></button>
  </form>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
