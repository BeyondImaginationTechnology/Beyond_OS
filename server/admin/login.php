<?php
require_once __DIR__ . '/bootstrap.php';
if (Auth::check()) { header('Location: /server/admin/dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf'] ?? '')) $error = 'Security token expired. Refresh and try again.';
    else {
        try {
            $res = Auth::login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
            if ($res['ok']) { header('Location: /server/admin/dashboard.php'); exit; }
            $error = $res['message'];
        } catch (Throwable $e) { error_log($e->getMessage()); $error = 'Login error. Check database config.'; }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title><link rel="stylesheet" href="/server/admin/assets/css/app.css"></head><body class="login-page"><div class="login-card"><p><a href="/">← Back to Home</a></p><div class="brand"><div class="logo">BI</div><div><h1>Admin Portal v2.3.3</h1><p class="muted">Secure Beyond Imagination control center</p></div></div><?php if($error): ?><div class="alert alert-error"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars(Auth::csrf())?>"><div class="field"><label>Email</label><input class="input" type="email" name="email" required autocomplete="email"></div><div class="field"><label>Password</label><input class="input" type="password" name="password" required autocomplete="current-password"></div><button class="btn" type="submit">Login</button></form><p><a href="/server/admin/forgot-password.php">Forgot password?</a></p></div></body></html>
