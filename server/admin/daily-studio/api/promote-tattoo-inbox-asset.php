<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/beyond-tattoo/includes/library-catalog.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function inboxPromoteJson(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
function inboxPromoteSlug(string $value): string { return trim((string)(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))) ?? ''), '-'); }
function inboxPromoteText(string $value, int $limit): string { return function_exists('mb_substr') ? mb_substr($value, 0, $limit) : substr($value, 0, $limit); }
function inboxPromoteImage(string $bytes, string $format, bool $watermark): string {
    $imageInfo = @getimagesizefromstring($bytes);
    if (!$watermark && $format === 'png' && is_array($imageInfo) && ($imageInfo['mime'] ?? '') === 'image/png') return $bytes;
    if (!function_exists('imagecreatefromstring')) throw new RuntimeException('PHP GD is required to prepare this library image.');
    $canvas = @imagecreatefromstring($bytes);
    if ($canvas === false) throw new RuntimeException('The stored image could not be decoded.');
    imagealphablending($canvas, true); imagesavealpha($canvas, true);
    if ($watermark) {
        $width = imagesx($canvas); $height = imagesy($canvas); $barHeight = max(30, (int)round($height * .045));
        imagefilledrectangle($canvas, 0, $height - $barHeight, $width, $height, imagecolorallocatealpha($canvas, 5, 4, 8, 38));
        imagestring($canvas, 5, max(12, (int)round($width * .025)), $height - $barHeight + max(7, (int)(($barHeight - 15) / 2)), 'BEYOND TATTOO  |  PREVIEW', imagecolorallocatealpha($canvas, 255, 255, 255, 18));
    }
    ob_start();
    if ($format === 'webp') { if (!function_exists('imagewebp')) throw new RuntimeException('PHP GD WebP support is required.'); imagewebp($canvas, null, 92); }
    else imagepng($canvas, null, 9);
    $output = ob_get_clean(); imagedestroy($canvas);
    if (!is_string($output) || $output === '') throw new RuntimeException('The stored image could not be prepared.');
    return $output;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') inboxPromoteJson(['ok'=>false,'error'=>'POST required.'], 405);
    if (!Auth::check()) inboxPromoteJson(['ok'=>false,'error'=>'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) inboxPromoteJson(['ok'=>false,'error'=>'Invalid security token.'], 403);
    $batch = strtolower(trim((string)($_POST['batch'] ?? ''))); $position = (int)($_POST['position'] ?? 0); $sequence = (int)($_POST['sequence'] ?? 0);
    $role = strtolower(trim((string)($_POST['role'] ?? ''))); $replace = filter_var($_POST['replace'] ?? false, FILTER_VALIDATE_BOOL);
    if (!preg_match('/^[a-z0-9][a-z0-9-]{7,48}$/', $batch) || $position < 1) inboxPromoteJson(['ok'=>false,'error'=>'Choose a valid private inbox asset.'], 422);
    $specs = ['preview'=>['file'=>'preview-watermarked.png','format'=>'png'],'stencil'=>['file'=>'stencil-print-ready.png','format'=>'png'],'outline'=>['file'=>'stencil-outline.png','format'=>'png'],'transfer'=>['file'=>'studio-transfer-template.png','format'=>'png'],'reference'=>['file'=>'reference-artwork.webp','format'=>'webp'],'placement'=>['file'=>'placement-mockup.webp','format'=>'webp'],'pack'=>['file'=>'premium-packaging.webp','format'=>'webp'],'lore'=>['file'=>'lore-card.webp','format'=>'webp'],'style'=>['file'=>'style-card.webp','format'=>'webp']];
    if (!isset($specs[$role])) inboxPromoteJson(['ok'=>false,'error'=>'Choose an image asset role.'], 422);
    $schedule = []; foreach (bt_season_one_drops() as $drop) $schedule[(int)$drop['sequence']] = $drop;
    if (!isset($schedule[$sequence])) inboxPromoteJson(['ok'=>false,'error'=>'Choose a valid Season One drop.'], 422);
    $inboxRoot = dirname(__DIR__) . '/storage/asset-inbox'; $manifestFile = $inboxRoot . '/' . $batch . '/manifest.json';
    $manifest = is_file($manifestFile) ? json_decode((string)file_get_contents($manifestFile), true) : null;
    $entry = is_array($manifest) ? ($manifest['items'][(string)$position] ?? null) : null; $stored = is_array($entry) ? (string)($entry['stored_name'] ?? '') : '';
    if (!is_array($entry) || !preg_match('/^[0-9]{3}-[a-f0-9]{10}-[a-zA-Z0-9._-]+\.(png|jpe?g|webp|gif)$/i', $stored)) inboxPromoteJson(['ok'=>false,'error'=>'This inbox item is not a supported image.'], 422);
    $source = $inboxRoot . '/' . $batch . '/' . $stored; $bytes = is_file($source) ? file_get_contents($source) : false;
    if (!is_string($bytes) || $bytes === '') inboxPromoteJson(['ok'=>false,'error'=>'The original inbox item is unavailable.'], 404);
    $info = @getimagesizefromstring($bytes); $mime = is_array($info) ? (string)($info['mime'] ?? '') : '';
    $width = (int)($info[0] ?? 0); $height = (int)($info[1] ?? 0);
    if (!in_array($mime, ['image/png','image/jpeg','image/webp','image/gif'], true) || $width < 600 || $height < 600 || $width > 12000 || $height > 12000 || $width * $height > 40000000) inboxPromoteJson(['ok'=>false,'error'=>'Library images must be valid PNG, JPG, WebP, or GIF files at least 600px on both sides.'], 422);
    $watermark = $role === 'preview' && filter_var($_POST['watermark'] ?? true, FILTER_VALIDATE_BOOL); $output = inboxPromoteImage($bytes, $specs[$role]['format'], $watermark);
    $drop = $schedule[$sequence]; $folder = sprintf('%02d-%s', $drop['collection_index'] + 1, inboxPromoteSlug($drop['title'])); $root = dirname(__DIR__, 4) . '/beyond-tattoo';
    $relative = 'uploads/stencil-library/' . $drop['collection_slug'] . '/' . $folder; $directory = $root . '/' . $relative;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('The library folder could not be created.');
    $destination = $directory . '/' . $specs[$role]['file']; if (is_file($destination) && !$replace) inboxPromoteJson(['ok'=>false,'error'=>'This drop already has that asset. Enable Replace existing assets to overwrite it.'], 409);
    $sha = hash('sha256', $output); foreach (glob($root . '/uploads/stencil-library/*/*/metadata.json') ?: [] as $metadataPath) { if (dirname($metadataPath) === $directory) continue; $metadata = json_decode((string)file_get_contents($metadataPath), true); if (($metadata['assets'][$role]['sha256'] ?? '') === $sha) inboxPromoteJson(['ok'=>false,'error'=>'This exact asset is already assigned to another Season One drop.'], 409); }
    if (file_put_contents($destination, $output, LOCK_EX) === false) throw new RuntimeException('The library asset could not be saved.'); @chmod($destination, 0664);
    $metadataFile = $directory . '/metadata.json'; $metadata = is_file($metadataFile) ? json_decode((string)file_get_contents($metadataFile), true) : []; if (!is_array($metadata)) $metadata = [];
    $metadata = array_replace($metadata, ['sequence'=>$sequence,'season_drop'=>$sequence,'season_total'=>55,'title'=>$drop['title'],'collection'=>$drop['collection'],'collection_slug'=>$drop['collection_slug'],'release_date'=>$drop['release_date'],'status'=>'draft','rights_confirmed'=>(bool)($metadata['rights_confirmed'] ?? false),'updated_at'=>gmdate('c')]); unset($metadata['approved_at'],$metadata['approved_by']);
    $metadata['assets'] = is_array($metadata['assets'] ?? null) ? $metadata['assets'] : []; $metadata['assets'][$role] = ['file'=>$specs[$role]['file'],'source_name'=>inboxPromoteText((string)($entry['source_name'] ?? $stored),180),'inbox_batch'=>$batch,'inbox_position'=>$position,'mime'=>'image/'.$specs[$role]['format'],'width'=>$width,'height'=>$height,'watermarked'=>$watermark,'sha256'=>$sha,'uploaded_at'=>gmdate('c')];
    if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) throw new RuntimeException('The library metadata could not be saved.');
    $manifest['items'][(string)$position]['sort_status'] = 'assigned'; $manifest['items'][(string)$position]['assignment'] = ['sequence'=>$sequence,'title'=>$drop['title'],'collection'=>$drop['collection'],'role'=>$role,'category'=>'library','confidence'=>'reviewed']; $manifest['updated_at'] = gmdate('c'); file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    inboxPromoteJson(['ok'=>true,'sequence'=>$sequence,'role'=>$role,'message'=>sprintf('Private upload %03d assigned to Drop %02d · %s.', $position, $sequence, $role)]);
} catch (Throwable $error) { error_log('Tattoo inbox promotion failed: ' . $error->getMessage()); inboxPromoteJson(['ok'=>false,'error'=>$error->getMessage()], 400); }
