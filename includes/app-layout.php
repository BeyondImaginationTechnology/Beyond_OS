<?php
declare(strict_types=1);
require_once __DIR__.'/platform.php';
function bos_page_start(string $app,string $title,string $description=''): array {
    $wallet=beyond_app_bootstrap($app);
    $isAdmin = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false;
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="'.($isAdmin ? '#f4f6fb' : '#050817').'"><title>'.e($title).' | Beyond OS</title><meta name="description" content="'.e($description).'"><link rel="manifest" href="'.e(beyond_url('manifest.webmanifest')).'"><link rel="stylesheet" href="'.e(beyond_url('assets/css/bos-21.css')).'"><link rel="stylesheet" href="'.e(beyond_url('assets/css/beyond-splash.css?v=20260828-1')).'"><script src="'.e(beyond_url('assets/js/beyond-splash.js?v=20260828-1')).'" defer></script>';
    if ($isAdmin) {
        echo '<link rel="stylesheet" href="'.e(beyond_url('assets/css/admin-light.css')).'">';
    }
    echo '</head><body class="bos-page'.($isAdmin ? ' bos-admin-light' : '').'">';
    return $wallet;
}
function bos_page_end(): void { echo '<script src="'.e(beyond_url('assets/js/pwa-install.js')).'" defer></script><script src="'.e(beyond_url('assets/js/visitor-analytics.js')).'" defer></script></body></html>'; }
