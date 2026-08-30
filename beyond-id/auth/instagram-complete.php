<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/social-auth.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../config/admin-alerts.php';
require_once __DIR__ . '/../../config/roles.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$pending = is_array($_SESSION['pending_instagram_identity'] ?? null) ? $_SESSION['pending_instagram_identity'] : [];
if ($pending === [] || time() - (int)($pending['created_at'] ?? 0) > 600 || empty($pending['subject'])) {
    unset($_SESSION['pending_instagram_identity']);
    $_SESSION['oauth_error'] = 'Instagram sign-in expired. Please try again.';
    header('Location: login.php');
    exit;
}

$username = trim((string)($pending['username'] ?? ''));
$displayName = trim((string)($pending['name'] ?? '')) ?: ($username !== '' ? '@' . $username : 'Instagram account');
$accountType = trim((string)($pending['account_type'] ?? ''));
$error = '';
$success = '';
$isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
$signedInUser = null;
if (!empty($_SESSION['user_id'])) {
    $statement = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $statement->execute([(int)$_SESSION['user_id']]);
    $signedInUser = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Reload this page and try again.';
    } elseif (($_POST['action'] ?? '') === 'link_existing') {
        if (!$signedInUser || ($signedInUser['status'] ?? '') !== 'active') {
            $error = 'Sign in to your existing Beyond ID before linking Instagram.';
        } elseif (empty($signedInUser['email_verified']) && empty($signedInUser['email_verified_at'])) {
            $error = 'Verify your Beyond ID email before linking Instagram.';
        } else {
            try {
                $check = $pdo->prepare('SELECT user_id FROM social_identities WHERE provider=? AND provider_user_id=? LIMIT 1');
                $check->execute(['instagram', $pending['subject']]);
                $linkedUserId = (int)($check->fetchColumn() ?: 0);
                if ($linkedUserId && $linkedUserId !== (int)$signedInUser['id']) {
                    throw new RuntimeException('This Instagram account is already linked to another Beyond ID.');
                }
                $existingProvider = $pdo->prepare('SELECT provider_user_id FROM social_identities WHERE user_id=? AND provider=? LIMIT 1');
                $existingProvider->execute([(int)$signedInUser['id'], 'instagram']);
                $existingSubject = (string)($existingProvider->fetchColumn() ?: '');
                if ($existingSubject !== '' && !hash_equals($existingSubject, (string)$pending['subject'])) {
                    throw new RuntimeException('This Beyond ID already has a different Instagram account linked.');
                }
                if (!$linkedUserId) {
                    $now = date('Y-m-d H:i:s');
                    $link = $pdo->prepare('INSERT INTO social_identities (user_id,provider,provider_user_id,email,display_name,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
                    $link->execute([(int)$signedInUser['id'], 'instagram', $pending['subject'], $signedInUser['email'], $displayName, $now, $now]);
                } else {
                    $update = $pdo->prepare('UPDATE social_identities SET email=?,display_name=?,updated_at=? WHERE provider=? AND provider_user_id=?');
                    $update->execute([$signedInUser['email'], $displayName, date('Y-m-d H:i:s'), 'instagram', $pending['subject']]);
                }
                log_activity($pdo, (int)$signedInUser['id'], 'oauth_link_instagram');
                unset($_SESSION['pending_instagram_identity']);
                $destination = safe_return_path($_SESSION['beyond_return_to'] ?? null, '../dashboard/');
                unset($_SESSION['beyond_return_to']);
                header('Location: ' . $destination);
                exit;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }
    } elseif (($_POST['action'] ?? '') === 'create_account') {
        $first = trim((string)($_POST['first_name'] ?? ''));
        $last = trim((string)($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if ($first === '' || $last === '' || $email === '') $error = 'Complete all required fields.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
        elseif (empty($_POST['accept_terms'])) $error = 'Accept the Terms and Privacy Policy to create a Beyond ID.';
        else {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
                $check->execute([$email]);
                if ($check->fetchColumn()) throw new RuntimeException('A Beyond ID already uses this email. Sign in to it above, then link Instagram.');

                $pdo->beginTransaction();
                $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));
                $role = beyond_signup_role($email);
                $now = date('Y-m-d H:i:s');
                $insert = $pdo->prepare("INSERT INTO users (first_name,last_name,name,email,password,password_hash,email_verified,email_verified_at,verification_token,verification_sent_at,role,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,'active')");
                $insert->execute([$first, $last, trim($first . ' ' . $last), $email, $hash, $hash, $isSqlite ? 1 : 0, $isSqlite ? $now : null, $isSqlite ? null : $token, $isSqlite ? null : $now, $role]);
                $userId = (int)$pdo->lastInsertId();
                try { $pdo->prepare("UPDATE users SET terms_accepted_at=?,terms_version='2.1-beta-instagram' WHERE id=?")->execute([$now, $userId]); } catch (Throwable $exception) {}
                try { $pdo->prepare('INSERT INTO profiles (user_id) VALUES (?)')->execute([$userId]); } catch (Throwable $exception) {}
                $walletSql = $isSqlite ? "INSERT OR IGNORE INTO beyond_wallets (user_id,balance,currency,status) VALUES (?,0,'BITS','active')" : "INSERT IGNORE INTO beyond_wallets (user_id,balance,currency,status) VALUES (?,0,'BITS','active')";
                $preferenceSql = $isSqlite ? 'INSERT OR IGNORE INTO user_preferences (user_id) VALUES (?)' : 'INSERT IGNORE INTO user_preferences (user_id) VALUES (?)';
                try { $pdo->prepare($walletSql)->execute([$userId]); } catch (Throwable $exception) {}
                try { $pdo->prepare($preferenceSql)->execute([$userId]); } catch (Throwable $exception) {}
                $link = $pdo->prepare('INSERT INTO social_identities (user_id,provider,provider_user_id,email,display_name,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
                $link->execute([$userId, 'instagram', $pending['subject'], $email, $displayName, $now, $now]);
                create_notification($pdo, $userId, 'Welcome to Beyond OS', 'Your Beyond ID is connected to Instagram.', '/beyond-id/dashboard/profile.php', 'welcome');
                log_activity($pdo, $userId, 'register_instagram_terms_accepted_v2.1-beta');
                $pdo->commit();
                unset($_SESSION['pending_instagram_identity']);
                send_beyond_id_admin_signup_alert(['id' => $userId, 'first_name' => $first, 'last_name' => $last, 'email' => $email, 'created_at' => $now], 'Beyond ID Instagram signup');
                if ($isSqlite) {
                    $userStatement = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
                    $userStatement->execute([$userId]);
                    beyond_social_login_session($pdo, $userStatement->fetch(PDO::FETCH_ASSOC), 'instagram');
                }
                $success = send_verification_email($email, $token, 'beyond_id', trim($first . ' ' . $last))
                    ? 'Check your inbox to verify your email. After verification, use Continue with Instagram to sign in.'
                    : 'Your account was created, but the verification email could not be sent. Contact support before signing in.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Beyond ID could not connect this Instagram account. Please try again.';
                error_log('Instagram account completion failed: ' . $exception->getMessage());
            }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Connect Instagram | Beyond ID</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;padding:24px;background:radial-gradient(circle at 15% 15%,#4d1d75 0,transparent 34%),radial-gradient(circle at 85% 80%,#9c213f 0,transparent 36%),#070711;color:#fff;font-family:Inter,system-ui,sans-serif}.shell{width:min(100%,560px);margin:5vh auto}.brand{font-weight:950;letter-spacing:.09em}.card{margin-top:18px;padding:32px;border:1px solid #3c3c50;border-radius:28px;background:rgba(17,17,31,.94);box-shadow:0 24px 70px rgba(0,0,0,.38)}h1{margin:0 0 8px;font-size:34px}.sub{margin:0 0 22px;color:#aaaabd;line-height:1.55}.identity{display:flex;align-items:center;gap:13px;padding:14px;border-radius:16px;background:#222235}.icon{display:grid;place-items:center;width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045);font-weight:950}.identity strong,.identity span{display:block}.identity span{margin-top:3px;color:#aaaabd;font-size:12px}.choice{margin-top:20px;padding-top:20px;border-top:1px solid #353548}h2{font-size:20px;margin:0 0 7px}.choice p{color:#aaaabd;line-height:1.5;font-size:14px}.button,button{display:flex;width:100%;min-height:50px;align-items:center;justify-content:center;border:0;border-radius:14px;color:#fff;text-decoration:none;font-weight:900;cursor:pointer}.instagram{background:linear-gradient(90deg,#833ab4,#fd1d1d,#fcb045)}.secondary{background:#2d2d42}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{margin:12px 0}.field label{display:block;margin-bottom:7px;font-size:13px;font-weight:800}.field input{width:100%;padding:14px;border:1px solid #424257;border-radius:13px;background:#0b0b17;color:#fff;font:inherit}.check{display:flex;gap:10px;margin:15px 0;color:#b9b9ca;font-size:13px;line-height:1.45}.check input{margin-top:3px}.check a{color:#d1b7ff}.error,.success{margin:15px 0;padding:13px;border-radius:13px}.error{background:#651a25}.success{background:#124c38}.fine{color:#89899c;font-size:12px;line-height:1.5}@media(max-width:600px){.card{padding:23px}.grid{grid-template-columns:1fr}}
</style></head><body><main class="shell"><div class="brand">BEYOND ID</div><section class="card"><h1>Finish with Instagram</h1><p class="sub">Instagram does not share a verified email, so Beyond ID needs one secure final step.</p><div class="identity"><div class="icon">◎</div><div><strong><?= e($displayName) ?></strong><span><?= e($accountType !== '' ? ucfirst(strtolower($accountType)) . ' professional account' : 'Instagram professional account') ?></span></div></div><?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="success"><?= e($success) ?></div><p><a class="button secondary" href="login.php">Return to sign in</a></p><?php elseif ($signedInUser): ?><section class="choice"><h2>Link to your signed-in Beyond ID</h2><p><?= e((string)$signedInUser['email']) ?></p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="link_existing"><button class="instagram">Link Instagram and continue</button></form></section><?php else: ?><section class="choice"><h2>Already have a Beyond ID?</h2><p>Sign in with email, Google, or Facebook. You will return here to approve the Instagram link.</p><a class="button secondary" href="login.php?return=<?= rawurlencode('/beyond-id/auth/instagram-complete.php') ?>">Sign in to existing Beyond ID</a></section><section class="choice"><h2>Create a new Beyond ID</h2><p>Add an email you can verify. No password is required; Instagram will be your sign-in method.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_account"><div class="grid"><div class="field"><label>First name</label><input name="first_name" autocomplete="given-name" value="<?= e($_POST['first_name'] ?? '') ?>" required></div><div class="field"><label>Last name</label><input name="last_name" autocomplete="family-name" value="<?= e($_POST['last_name'] ?? '') ?>" required></div></div><div class="field"><label>Email address</label><input type="email" name="email" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required></div><label class="check"><input type="checkbox" name="accept_terms" value="1" required><span>I agree to the <a href="terms.php" target="_blank">Terms</a> and acknowledge the <a href="privacy.php" target="_blank">Privacy Policy</a>.</span></label><button class="instagram">Create Beyond ID and verify email</button></form></section><?php endif; ?><p class="fine">Instagram sign-in supports Instagram professional accounts (Business or Creator). Beyond OS never receives your Instagram password.</p></section></main></body></html>
