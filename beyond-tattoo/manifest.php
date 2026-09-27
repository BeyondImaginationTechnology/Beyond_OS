<?php
declare(strict_types=1);

$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$appRoot = realpath(__DIR__);
$basePath = '';
if (is_string($documentRoot) && is_string($appRoot)) {
    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $appRoot = rtrim(str_replace('\\', '/', $appRoot), '/');
    if (str_starts_with(strtolower($appRoot), strtolower($documentRoot) . '/')) {
        $basePath = '/' . trim(substr($appRoot, strlen($documentRoot)), '/');
    }
} elseif (preg_match('#(?:^|/)beyond-tattoo(?:/|$)#', str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')))) {
    $basePath = '/beyond-tattoo';
}
$url = static fn(string $path = ''): string => rtrim($basePath, '/') . '/' . ltrim($path, '/');
$scope = $url('/');
$asset = $url;

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo json_encode([
    'id' => $scope,
    'name' => 'Beyond Tattoo — Studio Workspace',
    'short_name' => 'Beyond Tattoo',
    'description' => 'A focused tattoo studio workspace with stencil releases, print-ready assets and shop tools.',
    'start_url' => $asset('index.php'),
    'scope' => $scope,
    'display' => 'standalone',
    'display_override' => ['standalone', 'minimal-ui'],
    'orientation' => 'any',
    'background_color' => '#f7f4ef',
    'theme_color' => '#17131a',
    'prefer_related_applications' => false,
    'icons' => [
        ['src' => $asset('assets/icons/beyond-tattoo-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $asset('assets/icons/beyond-tattoo-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $asset('assets/icons/beyond-tattoo-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => 'Open stencil editor', 'short_name' => 'Stencil editor', 'url' => $asset('stencil-editor.php'), 'icons' => [['src' => $asset('assets/icons/beyond-tattoo-192.png'), 'sizes' => '192x192', 'type' => 'image/png']]],
        ['name' => 'Browse stencil releases', 'short_name' => 'Stencils', 'url' => $asset('stencils.php'), 'icons' => [['src' => $asset('assets/icons/beyond-tattoo-192.png'), 'sizes' => '192x192', 'type' => 'image/png']]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
