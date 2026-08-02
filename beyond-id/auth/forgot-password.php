<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../config/mail.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $stmt->execute([$email]);
            $userId = (int)$stmt->fetchColumn();

            if ($userId > 0) {
                $token = bin2hex(random_bytes(32));
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$userId]);
                    $pdo->prepare('INSERT INTO password_resets(user_id,token,expires_at,created_at) VALUES (?,?,?,?)')->execute([
                        $userId,
                        $token,
                        date('Y-m-d H:i:s', time() + 3600),
                        date('Y-m-d H:i:s'),
                    ]);
                    $pdo->commit();

                    $app = require __DIR__ . '/../config/app.php';
                    $url = rtrim((string)($app['url'] ?? 'https://beyondimagination.co.technology/beyond-id'), '/')
                        . '/auth/reset-password.php?token=' . urlencode($token);
                    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                    $sent = send_email($email, 'Reset your Beyond ID password', "<div style='font-family:Arial;padding:28px;background:#10101b;color:#fff'><h2>Reset your Beyond ID</h2><p>This link expires in one hour and can be used once.</p><p><a style='display:inline-block;padding:14px 20px;border-radius:999px;background:#7c3aed;color:#fff;text-decoration:none' href='{$safeUrl}'>Reset password</a></p><p style='font-size:12px;color:#aaa;word-break:break-all'>If the button does not work, copy this link:<br>{$safeUrl}</p></div>");
                    if (!$sent) {
                        error_log('Password reset email failed for user_id=' . $userId . ' email=' . $email);
                    } else {
                        error_log('Password reset email queued for user_id=' . $userId . ' email=' . $email);
                    }
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Password reset request failed: ' . $exception->getMessage());
                }
            } else {
                error_log('Password reset requested for unregistered email=' . $email);
            }
        }

        // Keep this response generic so account existence is never disclosed.
        $message = 'If that email is registered, a reset link has been sent.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot password | Beyond ID</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#30206b,#090912 55%);color:white;font-family:system-ui}.card{width:min(440px,calc(100% - 32px));padding:32px;border:1px solid #3b3b50;border-radius:26px;background:#151522}input,button{box-sizing:border-box;width:100%;padding:15px;border-radius:13px}input{background:#0b0b15;border:1px solid #414157;color:white}button{margin-top:12px;border:0;background:linear-gradient(90deg,#5b8cff,#a044f2,#e9449f);color:white;font-weight:900}a{color:#c4b5fd}.msg{padding:12px;background:#14503b;border-radius:12px}.err{padding:12px;background:#651d29;border-radius:12px}.app-back{display:inline-flex;align-items:center;min-height:42px;padding:9px 15px;border:1px solid rgba(167,139,250,.4);border-radius:999px;background:rgba(167,139,250,.1);color:inherit!important;font-weight:800;text-decoration:none}.app-back:hover{background:rgba(167,139,250,.2)}</style>
</head>
<body><main class="card"><h1>Reset your password</h1><p>We’ll email a one-hour reset link.</p><?php if ($message): ?><div class="msg"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><p><label for="email">Email address</label></p><input id="email" type="email" name="email" autocomplete="email" required><button type="submit">Send reset link</button></form><p><a class="app-back" href="login.php">← Back to sign in</a></p></main><script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
