<?php
declare(strict_types=1);

require_once __DIR__ . '/app-layout.php';

function bos_coming_soon_page(string $appName, string $description): never {
    http_response_code(503);
    header('Retry-After: 86400');
    beyond_nav_bootstrap($appName);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">'
        . '<meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#24140d"><title>' . e($appName) . ' | Coming Soon</title>'
        . '<meta name="description" content="' . e($description) . '"><link rel="stylesheet" href="' . e(beyond_url('assets/css/bos-21.css')) . '"></head><body class="bos-page">'
        . '<main class="bos-main coming-soon-main"><section class="bos-hero coming-soon-hero">'
        . '<span class="bos-kicker">Private preview</span>'
        . '<h1>' . e($appName) . ' is not ready yet.</h1>'
        . '<p>' . e($description) . ' This experience stays locked until it is ready for a public release.</p>'
        . '<div class="bos-actions"><a class="bos-btn" href="' . e(beyond_url('app-store/')) . '">Back to App Store</a>'
        . '<a class="bos-btn secondary" href="' . e(beyond_url()) . '">Beyond OS home</a></div>'
        . '</section></main><style>.coming-soon-main{width:min(980px,calc(100% - 28px))}.coming-soon-hero{min-height:520px;display:flex;flex-direction:column;justify-content:center;background:radial-gradient(circle at 82% 15%,rgba(242,184,75,.24),transparent 28%),linear-gradient(135deg,#23140e,#4c2416 58%,#211318)}.coming-soon-hero h1{max-width:820px}@media(max-width:560px){.coming-soon-main{width:min(100% - 18px,980px)}.coming-soon-hero{min-height:430px;padding:30px 18px}}</style>';
    bos_page_end();
    exit;
}
