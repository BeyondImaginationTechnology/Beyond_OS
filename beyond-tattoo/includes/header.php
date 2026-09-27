<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../includes/ecosystem.php';
if (empty($disableBeyondShell)) { beyond_nav_bootstrap('Beyond Tattoo'); }
$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#17131a">
  <meta name="application-name" content="Beyond Tattoo">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="icon" type="image/png" sizes="192x192" href="<?= e(bt_app_url('assets/icons/beyond-tattoo-192.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(bt_app_url('assets/icons/beyond-tattoo-192.png')) ?>">
  <link rel="manifest" href="<?= e(bt_app_url('manifest.php')) ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(bt_app_url('assets/css/app.css')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/app.css') ?: '20260716')) ?>">
  <link rel="stylesheet" href="<?= e(bt_app_url('assets/css/theme-02.css')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/theme-02.css') ?: '20260922')) ?>">
  <link rel="stylesheet" href="<?= e(bt_app_url('assets/css/studio-upgrades.css')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/studio-upgrades.css') ?: '20260727')) ?>">
  <link rel="stylesheet" href="<?= e(bt_app_url('assets/css/responsive.css')) ?>?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/responsive.css') ?: '20260926')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
