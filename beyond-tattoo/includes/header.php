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
  <meta name="theme-color" content="#09070f">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="/beyond-tattoo/assets/css/app.css?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/app.css') ?: '20260716')) ?>">
  <link rel="stylesheet" href="/beyond-tattoo/assets/css/studio-upgrades.css?v=<?= rawurlencode((string) (@filemtime(__DIR__ . '/../assets/css/studio-upgrades.css') ?: '20260727')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
