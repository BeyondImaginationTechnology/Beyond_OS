<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth-check.php';
$uid = (int)$_SESSION['user_id'];
$message = '';
$error = '';
$locales = ['en'=>'English','fr'=>'Français','ht'=>'Kreyòl Ayisyen','en-JM'=>'Jamaica · English / Patois','es-DO'=>'República Dominicana · Español'];
$isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
try {
    $insert = $isSqlite ? 'INSERT OR IGNORE INTO user_preferences(user_id) VALUES(?)' : 'INSERT IGNORE INTO user_preferences(user_id) VALUES(?)';
    $pdo->prepare($insert)->execute([$uid]);
} catch (Throwable $exception) {
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } else {
        $locale = array_key_exists($_POST['locale'] ?? '', $locales) ? $_POST['locale'] : 'en';
        $theme = in_array($_POST['theme'] ?? 'system', ['system','light','dark'], true) ? $_POST['theme'] : 'system';
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET preferred_locale=?,timezone=? WHERE id=?')->execute([$locale, substr(trim($_POST['timezone'] ?? 'America/Vancouver'), 0, 64), $uid]);
            $pdo->prepare('UPDATE user_preferences SET theme=?,email_notifications=?,in_app_notifications=?,marketing_emails=? WHERE user_id=?')->execute([$theme, isset($_POST['email_notifications']) ? 1 : 0, isset($_POST['in_app_notifications']) ? 1 : 0, isset($_POST['marketing_emails']) ? 1 : 0, $uid]);
            $pdo->commit();
            $_SESSION['locale'] = $locale;
            $message = 'Preferences saved for your Beyond ID.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Unable to save preferences.';
        }
    }
}
$preferences = ['preferred_locale'=>'en','timezone'=>'America/Vancouver','theme'=>'system','email_notifications'=>1,'in_app_notifications'=>1,'marketing_emails'=>0];
try {
    $stmt = $pdo->prepare('SELECT u.preferred_locale,u.timezone,p.theme,p.email_notifications,p.in_app_notifications,p.marketing_emails FROM users u LEFT JOIN user_preferences p ON p.user_id=u.id WHERE u.id=?');
    $stmt->execute([$uid]);
    $preferences = array_merge($preferences, $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
} catch (Throwable $exception) {
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Settings | Beyond ID</title><link rel="stylesheet" href="../assets/css/admin-v2.css?v=2.1.4"><style>label{display:block;margin:15px 0 7px;font-weight:800}.toggle{display:flex;gap:10px;align-items:flex-start;padding:13px 0}.toggle input{margin-top:4px}.msg,.err{padding:12px;border-radius:12px}.msg{background:#dcfce7;color:#166534}.err{background:#fee2e2;color:#991b1b}</style></head><body><main class="content" style="max-width:850px;margin:auto"><p><a class="app-back" href="index.php">Dashboard</a></p><div class="card"><span class="badge">SHARED SETTINGS</span><h1>Preferences</h1><?php if ($message): ?><p class="msg"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Language and locale</label><select name="locale"><?php foreach ($locales as $value=>$label): ?><option value="<?= e($value) ?>" <?= $preferences['preferred_locale']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><label>Time zone</label><input type="text" name="timezone" value="<?= e($preferences['timezone']) ?>"><label>Appearance</label><select name="theme"><?php foreach (['system'=>'Use device setting','light'=>'Light','dark'=>'Dark'] as $value=>$label): ?><option value="<?= $value ?>" <?= $preferences['theme']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><label class="toggle"><input type="checkbox" name="in_app_notifications" <?= $preferences['in_app_notifications']?'checked':'' ?>><span><strong>In-app notifications</strong><br><small>Rewards, security notices, and app updates.</small></span></label><label class="toggle"><input type="checkbox" name="email_notifications" <?= $preferences['email_notifications']?'checked':'' ?>><span><strong>Account emails</strong></span></label><label class="toggle"><input type="checkbox" name="marketing_emails" <?= $preferences['marketing_emails']?'checked':'' ?>><span><strong>Product news</strong></span></label><button>Save preferences</button></form></div></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
