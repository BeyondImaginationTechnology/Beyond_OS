<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/remember-me.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/roles.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$done = false;
$reset = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $pdo->prepare('SELECT id,user_id FROM password_resets WHERE token=? AND used_at IS NULL AND expires_at>? LIMIT 1');
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$reset) {
        $error = 'This reset link is invalid or expired.';
    } elseif (strlen($_POST['password'] ?? '') < 8) {
        $error = 'Use at least eight characters.';
    } elseif (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        try {
            try {
                $pdo->prepare('UPDATE users SET password_hash=?, password=? WHERE id=?')->execute([$hash, $hash, (int)$reset['user_id']]);
            } catch (Throwable $exception) {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, (int)$reset['user_id']]);
            }
            $pdo->prepare('UPDATE password_resets SET used_at=? WHERE id=? AND used_at IS NULL')->execute([date('Y-m-d H:i:s'), (int)$reset['id']]);
            beyondRememberRevokeAll($pdo, (int)$reset['user_id']);
            $pdo->commit();
            log_activity($pdo, (int)$reset['user_id'], 'password_reset');
            $done = true;
            $reset = false;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Password reset failed: ' . $exception->getMessage());
            $error = 'The password could not be updated. Please request a new link and try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset password | Beyond ID</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#30206b,#090912 55%);color:white;font-family:system-ui}.card{width:min(440px,calc(100% - 32px));padding:32px;border:1px solid #3b3b50;border-radius:26px;background:#151522}input,button{box-sizing:border-box;width:100%;padding:15px;border-radius:13px;margin-top:10px}input{background:#0b0b15;border:1px solid #414157;color:white}button{border:0;background:#7c3aed;color:white;font-weight:900}a{color:#c4b5fd}.err{padding:12px;background:#651d29;border-radius:12px}</style>
</head>
<body><main class="card"><?php if ($done): ?><h1>Password updated</h1><p><a href="login.php">Continue to sign in →</a></p><?php else: ?><h1>Choose a new password</h1><?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?><?php if ($reset): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="token" value="<?= e($token) ?>"><input type="password" name="password" placeholder="New password" autocomplete="new-password" minlength="8" required><input type="password" name="confirm" placeholder="Confirm password" autocomplete="new-password" minlength="8" required><button type="submit">Update password</button></form><?php elseif (!$error): ?><p>This reset link is invalid or expired.</p><a href="forgot-password.php">Request another link</a><?php endif; ?><?php endif; ?></main><script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
