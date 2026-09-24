<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../includes/ecosystem.php';
beyond_nav_bootstrap('Beyond French');
$pageTitle = $pageTitle ?? APP_NAME;
$frenchBase = rtrim(beyond_url('beyond-french/'), '/') . '/';
$frenchCssVersion = (string)(@filemtime(__DIR__ . '/../assets/css/style.css') ?: time());
$academyCssVersion = (string)(@filemtime(__DIR__ . '/../assets/css/academy.css') ?: time());
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="French first. Five languages. Every day.">
    <meta name="theme-color" content="#1768ff">
    <meta name="application-name" content="Beyond French">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Beyond French">
    <link rel="manifest" href="<?= h($frenchBase) ?>manifest.webmanifest">
    <link rel="apple-touch-icon" href="<?= h($frenchBase) ?>assets/app-store/AppIcon-180.png">
    <link rel="stylesheet" href="<?= h($frenchBase) ?>assets/css/style.css?v=<?= h($frenchCssVersion) ?>">
    <link rel="stylesheet" href="<?= h($frenchBase) ?>assets/css/academy.css?v=<?= h($academyCssVersion) ?>">
</head>
<body class="<?= !empty($appShell) ? 'app-shell' : '' ?>" data-beyond-french-base="<?= h($frenchBase) ?>">
<header class="site-header">
    <a class="brand" href="<?= h($frenchBase) ?>">
        <img src="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp" alt="Beyond French logo">
        <span><strong>Beyond French</strong><small>Daily Academy</small></span>
    </a>
    <button class="menu-toggle" type="button" aria-label="Open menu">☰</button>
    <nav class="nav">
        <a href="<?= h($frenchBase) ?>">Home</a>
        <a href="<?= h($frenchBase) ?>academy.php">Academy</a>
        <a href="<?= h($frenchBase) ?>translate.php">Translate</a>
        <a href="<?= h($frenchBase) ?>game.php">Trivia</a>
        <a class="nav-cta" href="<?= h($frenchBase) ?>settings.php">Settings</a>
    </nav>
</header>
<main>
