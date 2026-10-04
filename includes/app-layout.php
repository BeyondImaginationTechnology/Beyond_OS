<?php
declare(strict_types=1);
require_once __DIR__.'/platform.php';
function bos_page_start(string $app,string $title,string $description=''): array {
    $GLOBALS['bos_page_app'] = $app;
    $wallet=beyond_app_bootstrap($app);
    $isAdmin = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false;
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="'.($isAdmin ? '#f4f6fb' : '#050817').'"><title>'.e($title).' | Beyond Imagination Technology</title><meta name="description" content="'.e($description).'"><link rel="manifest" href="'.e(beyond_url('manifest.webmanifest')).'"><link rel="stylesheet" href="'.e(beyond_url('assets/css/bos-21.css')).'"><link rel="stylesheet" href="'.e(beyond_url('assets/css/beyond-splash.css?v=20260828-1')).'"><script src="'.e(beyond_url('assets/js/beyond-splash.js?v=20260904-1')).'" defer></script>';
    if ($isAdmin) {
        echo '<link rel="stylesheet" href="'.e(beyond_url('assets/css/admin-light.css')).'">';
    }
    echo '</head><body class="bos-page'.($isAdmin ? ' bos-admin-light' : '').'">';
    return $wallet;
}
function bos_page_end(): void {
    require_once __DIR__ . '/../legal/footer-links.php';
    $app = (string) ($GLOBALS['bos_page_app'] ?? 'Beyond OS');
    echo '<footer class="bos-legal-footer"><span>'.e($app).' · Beyond Imagination Technology</span>';
    beyond_legal_links($app);
    echo '</footer><style>.bos-legal-footer{width:min(1180px,calc(100% - 32px));margin:32px auto 0;padding:20px 0 calc(20px + env(safe-area-inset-bottom));border-top:1px solid var(--bos-line,#2a3148);display:flex;align-items:center;justify-content:space-between;gap:16px;color:var(--bos-muted,#9da6be);font:700 12px/1.4 Inter,system-ui,sans-serif}.app-legal-links{display:flex;gap:16px;flex-wrap:wrap}.app-legal-links a{color:inherit;text-decoration:none}.app-legal-links a:hover,.app-legal-links a:focus-visible{text-decoration:underline}@media(max-width:560px){.bos-legal-footer{width:min(100% - 22px,1180px);align-items:flex-start;flex-direction:column}.app-legal-links a{min-height:28px;display:inline-flex;align-items:center}}</style><script src="'.e(beyond_url('assets/js/pwa-install.js')).'" defer></script><script src="'.e(beyond_url('assets/js/visitor-analytics.js')).'" defer></script></body></html>';
}
