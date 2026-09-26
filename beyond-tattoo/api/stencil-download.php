<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/stencil-tracking.php';
require_once __DIR__ . '/../includes/stencil-content.php';
require_once __DIR__ . '/../includes/stencil-package.php';
$stencil = bt_stencil_content();
$requestedId = trim((string)($_GET['id'] ?? ''));
if ($requestedId !== '') {
    if (!preg_match('/^[a-z0-9-]+$/', $requestedId)) { http_response_code(400); exit('Invalid stencil ID.'); }
    $selected = null;
    foreach (bt_asset_library() as $asset) {
        if ($asset['id'] === $requestedId) { $selected = $asset; break; }
    }
    if ($selected === null) { http_response_code(404); exit('Stencil package unavailable.'); }
    $packageFiles = [];
    foreach ($selected['files'] as $assetFile) $packageFiles[$assetFile['url']] = $selected['slug'] . '/' . $assetFile['file'];
    $stencil = ['slug' => $selected['id'], 'package_files' => $packageFiles];
}
$type = strtolower((string)($_GET['type'] ?? 'package'));
$slug = preg_replace('/[^a-z0-9-]+/i', '-', (string)($stencil['slug'] ?? 'stencil-of-the-day'));

try {
    if ($requestedId !== '' && $type !== 'package') { http_response_code(400); exit('Only stencil packages support an ID.'); }
    if ($type === 'package') {
        $file = bt_stencil_package($stencil);
        $mime = 'application/zip'; $name = 'beyond-tattoo-' . trim((string)$slug, '-') . '.zip';
    } else {
        $map = [
            'preview' => ['preview_url', 'auto', 'preview'],
            'png' => ['transfer_png_url', 'image/png', 'studio-transfer.png'],
            'outline' => ['outline_png_url', 'image/png', 'outline-stencil.png'],
            'pdf' => ['transfer_pdf_url', 'application/pdf', 'studio-transfer.pdf'],
            'editable' => ['editable_url', 'image/svg+xml; charset=UTF-8', 'editable-master.svg'],
            'placement' => ['placement_guide_url', 'application/pdf', 'placement-guide.pdf'],
            'ig' => ['ig_post_url', 'auto', 'social-preview'],
        ];
        if (!isset($map[$type])) { http_response_code(400); exit('Unknown stencil asset type.'); }
        [$field,$mime,$suffix] = $map[$type];
        $relative = (string)($stencil[$field] ?? '');
        $file = bt_stencil_asset_path($relative);
        if ($type === 'png' || $type === 'outline') {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
                $suffix = preg_replace('/\.(?:png|jpe?g)$/i', '', $suffix) . '.' . $extension;
            }
        }
        if ($mime === 'auto') {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mime = ['png' => 'image/png', 'webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$extension] ?? 'application/octet-stream';
            $suffix .= '.' . ($extension ?: 'bin');
        }
        $name = 'beyond-tattoo-' . trim((string)$slug, '-') . '-' . $suffix;
    }
} catch (Throwable $e) {
    error_log('Stencil download unavailable: ' . $e->getMessage());
    http_response_code(404); exit('The current stencil asset is unavailable. Please try again shortly.');
}
track_stencil_download(($requestedId !== '' ? 'library.' : 'stencil-of-day.') . $type);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($file);
