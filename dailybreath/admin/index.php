<?php
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
if (ADMIN_PASSWORD === '') {
    http_response_code(503);
    exit('DailyBreath admin is unavailable until DAILYBREATH_ADMIN_PASSWORD is configured.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beyond_require_csrf();
    $action = (string)($_POST['action'] ?? 'login');
    if ($action === 'logout') { $_SESSION=[]; session_regenerate_id(true); header('Location: index.php'); exit; }
    if (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) { session_regenerate_id(true); $_SESSION['dailybreath_admin']=true; }
    else $error='Wrong password.';
}
if (empty($_SESSION['dailybreath_admin'])):
?><!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../assets/css/style.css"><title>DailyBreath Admin</title></head><body><main class="page"><form class="newsletter admin-login" method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars(beyond_csrf_token(),ENT_QUOTES,'UTF-8')?>"><a href="/" style="display:inline-block;margin-bottom:16px;color:inherit;font-weight:800">← Back to Home</a><h1>DailyBreath Admin</h1><?php if(isset($error)) echo '<div class="alert error">'.htmlspecialchars($error,ENT_QUOTES,'UTF-8').'</div>'; ?><input type="password" name="password" placeholder="Admin password" required><button>Login</button></form></main></body></html><?php exit;endif;
$rows=db()->query('SELECT id,name,email,status,created_at FROM dailybreath_subscribers ORDER BY created_at DESC')->fetchAll();
?><!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="../assets/css/style.css"><title>Subscribers</title></head><body><main class="admin"><h1>DailyBreath Subscribers</h1><p><a href="export.php">Export CSV</a></p><form method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars(beyond_csrf_token(),ENT_QUOTES,'UTF-8')?>"><button name="action" value="logout">Logout</button></form><table><tr><th>Name</th><th>Email</th><th>Status</th><th>Date</th></tr><?php foreach($rows as $row):?><tr><td><?=htmlspecialchars($row['name']??'',ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars($row['email'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars($row['status'],ENT_QUOTES,'UTF-8')?></td><td><?=htmlspecialchars($row['created_at'],ENT_QUOTES,'UTF-8')?></td></tr><?php endforeach;?></table></main></body></html>
