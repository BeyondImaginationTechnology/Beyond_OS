<?php
require_once __DIR__ . '/functions.php';
$app = require __DIR__ . '/../config/app.php';

$allowedAdminThemes = ['midnight', 'light', 'ocean', 'forest', 'sunset'];
$adminTheme = (string)($_COOKIE['beyond_theme'] ?? 'midnight');
if (!in_array($adminTheme, $allowedAdminThemes, true)) {
    $adminTheme = 'midnight';
}

$adminEmail = (string)($_SESSION['email'] ?? 'Administrator');
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
$adminPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($adminTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark light">
<title><?= e($title ?? 'Admin') ?> | Beyond ID</title>
<link rel="preload" href="../assets/css/admin-v2.css?v=2.4" as="style">
<link rel="stylesheet" href="../assets/css/admin-v2.css?v=2.4">
<?php if ($adminPage === 'index.php'): ?><link rel="stylesheet" href="../assets/css/admin-fall.css?v=1.0.0"><?php endif; ?>
<style>
/* Critical shell fallback. Keep this small but complete enough for a safe admin UI. */
:root{--bg:#080d19;--panel:#111a2c;--panel2:#172238;--text:#f8fafc;--muted:#9aabc2;--border:#26344d;--accent:#7c3aed;--accent2:#38bdf8;--good:#34d399;--warn:#fbbf24;--bad:#fb7185;--sidebar:272px}*{box-sizing:border-box}html{background:var(--bg)}body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;line-height:1.5}.shell{display:flex;min-height:100vh}.sidebar{position:fixed;inset:0 auto 0 0;z-index:30;width:var(--sidebar);padding:20px 16px;background:#0b1222;border-right:1px solid var(--border);overflow:auto}.main{min-width:0;width:calc(100% - var(--sidebar));margin-left:var(--sidebar)}.topbar{position:sticky;top:0;z-index:20;min-height:76px;padding:12px 28px;display:flex;align-items:center;gap:16px;background:rgba(8,13,25,.92);border-bottom:1px solid var(--border)}.content{width:100%;max-width:1440px;padding:34px 32px 56px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px}.card,.tile{padding:22px;border:1px solid var(--border);border-radius:20px;background:var(--panel)}a{color:#a5b4fc}.menu-btn{display:none}.sidebar-scrim{display:none}@media(max-width:920px){.sidebar{width:min(86vw,300px);transform:translateX(-105%);transition:transform .22s ease}.sidebar-open .sidebar{transform:translateX(0)}.main{width:100%;margin-left:0}.menu-btn{display:grid}.content{padding:28px 22px 48px}.sidebar-open .sidebar-scrim{display:block;position:fixed;inset:0;z-index:25;border:0;background:rgba(2,6,23,.72)}}@media(max-width:720px){.topbar{min-height:64px;gap:10px;padding:10px 14px}.admin-search-wrap{display:none}.topbar-title{display:block;flex:1;min-width:0}.top-user{margin-left:0}.user-copy{display:none}.content{padding:24px 16px 42px}.page-heading{align-items:flex-start;flex-direction:column;margin-bottom:20px}.grid{grid-template-columns:1fr}.card,.tile{padding:18px;border-radius:18px}}@media(max-width:420px){.theme-toggle{display:none}.topbar{padding-inline:12px}.content{padding-inline:14px}.page-heading h1{font-size:30px}}
</style>
</head>
<body class="admin-page" data-admin-page="<?= e($adminPage) ?>">
<div class="shell">
