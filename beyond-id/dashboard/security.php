<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/mobile-auth.php';

$uid = (int)$_SESSION['user_id'];
$message = '';
$error = '';
$linkedProviders = [];
try {
    $statement = $pdo->prepare('SELECT provider,email,display_name FROM social_identities WHERE user_id=? ORDER BY provider');
    $statement->execute([$uid]);
    $linkedProviders = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Reload the page and try again.';
    } elseif (isset($_POST['revoke_mobile_device'])) {
        $familyId = trim((string)$_POST['revoke_mobile_device']);
        if (!preg_match('/^[a-f0-9]{64}$/', $familyId)) {
            $error = 'That mobile session could not be found.';
        } else {
            try {
                $now = date('Y-m-d H:i:s');
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE mobile_token_families SET revoked_at=? WHERE family_id=? AND user_id=? AND revoked_at IS NULL')->execute([$now, $familyId, $uid]);
                $pdo->prepare('UPDATE mobile_refresh_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
                $pdo->prepare('UPDATE mobile_access_tokens SET revoked_at=? WHERE family_id=? AND revoked_at IS NULL')->execute([$now, $familyId]);
                $pdo->commit();
                log_activity($pdo, $uid, 'mobile_device_signed_out');
                $message = 'That mobile device has been signed out.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Mobile device revocation failed: ' . $exception->getMessage());
                $error = 'The device could not be signed out. Please try again.';
            }
        }
    } elseif (isset($_POST['revoke_session'])) {
        try {
            $pdo->prepare('UPDATE user_sessions SET revoked_at=? WHERE id=? AND user_id=?')->execute([date('Y-m-d H:i:s'), (int)$_POST['revoke_session'], $uid]);
            $message = 'Browser session revoked.';
        } catch (Throwable $exception) {
            $error = 'The browser session could not be revoked.';
        }
    } elseif (isset($_POST['change_password'])) {
        if (strlen($_POST['password'] ?? '') < 8) {
            $error = 'Use at least eight characters.';
        } elseif (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password=?,password_hash=? WHERE id=?')->execute([$hash, $hash, $uid]);
            log_activity($pdo, $uid, 'password_changed');
            $message = 'Password updated.';
        }
    }
}

$mobileDevices = [];
try {
    $statement = $pdo->prepare('SELECT family_id,audience,app_slug,device_name,device_id,last_used_at,expires_at FROM mobile_token_families WHERE user_id=? AND revoked_at IS NULL AND expires_at>? ORDER BY last_used_at DESC');
    $statement->execute([$uid, date('Y-m-d H:i:s')]);
    $mobileDevices = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}
$sessions = [];
try {
    $statement = $pdo->prepare('SELECT id,ip_address,user_agent,last_seen_at,expires_at,session_token_hash FROM user_sessions WHERE user_id=? AND revoked_at IS NULL AND expires_at>? ORDER BY last_seen_at DESC');
    $statement->execute([$uid, date('Y-m-d H:i:s')]);
    $sessions = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
}
$current = hash('sha256', session_id());
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Security | Beyond ID</title>
<link rel="stylesheet" href="../assets/css/admin-v2.css?v=2.1.4">
<style>
label{display:block;margin:14px 0 7px}input{width:100%;padding:14px;border:1px solid var(--border);border-radius:12px;background:#0d0d18;color:#fff}.session,.social-row,.mobile-device{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:16px 0;border-bottom:1px solid var(--border)}.social-row:last-child,.mobile-device:last-child{border-bottom:0}.social-name{display:flex;align-items:center;gap:12px}.social-icon{display:grid;width:42px;height:42px;place-items:center;border-radius:13px;background:#303043;font-weight:950;text-transform:uppercase}.social-action{display:inline-flex;min-height:42px;padding:0 15px;align-items:center;border-radius:11px;background:#303043;color:#fff;text-decoration:none;font-size:13px;font-weight:850}.account-note{color:var(--muted);line-height:1.55}.mobile-device form{margin:0}.mobile-device button{min-height:42px;padding:9px 13px}@media(max-width:600px){.session,.social-row,.mobile-device{align-items:flex-start;flex-direction:column}.mobile-device form,.mobile-device button{width:100%}}
</style>
</head>
<body>
<main class="content" style="max-width:900px;margin:auto">
<p><a class="app-back" href="index.php">Dashboard</a></p>
<?php if ($message): ?><p class="ok" role="status"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="danger" role="alert"><?= e($error) ?></p><?php endif; ?>
<div class="card"><span class="badge">SECURITY</span><h1>Protect your Beyond ID</h1><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label for="new-password">New password</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" required><label for="confirm-password">Confirm password</label><input id="confirm-password" type="password" name="confirm" autocomplete="new-password" minlength="8" required><button name="change_password" value="1">Update password</button></form></div>
<div class="card" style="margin-top:16px"><h2>Sign-in methods</h2><p class="account-note">Provider sign-ins with the same verified email connect to this Beyond ID. A provider using a different email may create a separate account.</p><?php if (!$linkedProviders): ?><p class="muted">No provider sign-ins are connected yet.</p><?php endif; ?><?php foreach ($linkedProviders as $identity): $providerName = ucfirst((string)$identity['provider']); ?><div class="social-row"><div class="social-name"><span class="social-icon" aria-hidden="true"><?= e(substr($providerName, 0, 1)) ?></span><div><strong><?= e($providerName) ?></strong><br><small class="muted"><?= e((string)($identity['email'] ?: $identity['display_name'] ?: 'Connected')) ?></small></div></div><span class="badge ok">Connected</span></div><?php endforeach; ?></div>
<div class="card" style="margin-top:16px"><h2>Mobile app devices</h2><p class="account-note">Each device has its own Beyond ID session. Signing one out immediately revokes its access and refresh tokens.</p><?php if (!$mobileDevices): ?><p class="muted">No active mobile app sessions.</p><?php endif; ?><?php foreach ($mobileDevices as $device): $client = beyond_api_client((string)$device['audience']); ?><div class="mobile-device"><div><strong><?= e((string)($device['device_name'] ?: 'Mobile device')) ?></strong><br><small class="muted"><?= e((string)($client['name'] ?? $device['app_slug'])) ?> · Last active <?= e(date('M j, Y g:i A', strtotime((string)$device['last_used_at']))) ?></small></div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="danger" name="revoke_mobile_device" value="<?= e((string)$device['family_id']) ?>">Sign out this device</button></form></div><?php endforeach; ?><p><a href="connected-apps.php">Manage connected apps and their permissions</a></p></div>
<div class="card" style="margin-top:16px"><h2>Browser sessions</h2><?php if (!$sessions): ?><p class="muted">No other active browser sessions.</p><?php endif; ?><?php foreach ($sessions as $session): ?><div class="session"><div><strong><?= hash_equals($current, (string)$session['session_token_hash']) ? 'This device' : 'Signed-in browser' ?></strong><br><small class="muted"><?= e(substr((string)($session['user_agent'] ?? 'Browser'), 0, 100)) ?><br><?= e((string)($session['ip_address'] ?? '')) ?> · Last active <?= e((string)$session['last_seen_at']) ?></small></div><?php if (!hash_equals($current, (string)$session['session_token_hash'])): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button name="revoke_session" value="<?= (int)$session['id'] ?>">Sign out browser</button></form><?php else: ?><span class="badge ok">Current</span><?php endif; ?></div><?php endforeach; ?></div>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script>
</body>
</html>
