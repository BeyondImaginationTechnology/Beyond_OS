<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/beyond-tattoo/includes/library-catalog.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function tattooLibraryJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function tattooLibrarySlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function tattooLibrarySchedule(): array
{
    $schedule = [];
    $sequence = 0;
    foreach (bt_library_collections() as $collectionSlug => $collection) {
        foreach ($collection['stencils'] as $collectionIndex => [$title, $releaseDate]) {
            $sequence++;
            $schedule[$sequence] = [
                'sequence' => $sequence,
                'title' => $title,
                'release_date' => $releaseDate,
                'collection' => $collection['name'],
                'collection_slug' => $collectionSlug,
                'collection_index' => $collectionIndex,
            ];
        }
    }
    return $schedule;
}

function tattooLibraryImage(string $bytes, string $mime, string $format, bool $watermark): string
{
    if ($mime === 'image/' . $format && !$watermark) return $bytes;
    if (!function_exists('imagecreatefromstring')) {
        throw new RuntimeException('PHP GD is required to convert this image or apply a preview watermark.');
    }
    $canvas = @imagecreatefromstring($bytes);
    if ($canvas === false) throw new RuntimeException('The uploaded tattoo asset could not be decoded.');
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    if ($watermark) {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $barHeight = max(30, (int)round($height * 0.045));
        $background = imagecolorallocatealpha($canvas, 5, 4, 8, 38);
        $foreground = imagecolorallocatealpha($canvas, 255, 255, 255, 18);
        imagefilledrectangle($canvas, 0, $height - $barHeight, $width, $height, $background);
        imagestring($canvas, 5, max(12, (int)round($width * 0.025)), $height - $barHeight + max(7, (int)(($barHeight - 15) / 2)), 'BEYOND TATTOO  |  PREVIEW', $foreground);
    }
    ob_start();
    if ($format === 'webp') {
        if (!function_exists('imagewebp')) throw new RuntimeException('PHP GD WebP support is required for this asset type.');
        imagewebp($canvas, null, 92);
    } else {
        imagepng($canvas, null, 9);
    }
    $converted = ob_get_clean();
    imagedestroy($canvas);
    if (!is_string($converted) || $converted === '') throw new RuntimeException('The tattoo asset could not be converted.');
    return $converted;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') tattooLibraryJson(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) tattooLibraryJson(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) tattooLibraryJson(['ok' => false, 'error' => 'Invalid security token.'], 403);

    $sequence = (int)($_POST['sequence'] ?? 0);
    $role = strtolower(trim((string)($_POST['role'] ?? 'preview')));
    $replace = filter_var($_POST['replace'] ?? false, FILTER_VALIDATE_BOOL);
    $watermark = $role === 'preview' && filter_var($_POST['watermark'] ?? true, FILTER_VALIDATE_BOOL);
    $roleSpecs = [
        'preview' => ['file' => 'preview-watermarked.png', 'kind' => 'image', 'format' => 'png'],
        'stencil' => ['file' => 'stencil-print-ready.png', 'kind' => 'image', 'format' => 'png'],
        'transfer' => ['file' => 'studio-transfer-template.png', 'kind' => 'image', 'format' => 'png'],
        'pdf' => ['file' => 'stencil-print-ready.pdf', 'kind' => 'pdf'],
        'reference' => ['file' => 'reference-artwork.webp', 'kind' => 'image', 'format' => 'webp'],
        'placement' => ['file' => 'placement-mockup.webp', 'kind' => 'image', 'format' => 'webp'],
        'pack' => ['file' => 'premium-packaging.webp', 'kind' => 'image', 'format' => 'webp'],
        'lore' => ['file' => 'lore-card.webp', 'kind' => 'image', 'format' => 'webp'],
        'style' => ['file' => 'style-card.webp', 'kind' => 'image', 'format' => 'webp'],
    ];
    if (!isset($roleSpecs[$role])) tattooLibraryJson(['ok' => false, 'error' => 'Choose a valid tattoo library asset type.'], 422);
    $roleSpec = $roleSpecs[$role];

    $schedule = tattooLibrarySchedule();
    if (!isset($schedule[$sequence])) tattooLibraryJson(['ok' => false, 'error' => 'Choose a valid Season One drop from 1 to 55.'], 422);
    if (!isset($_FILES['asset']) || !is_array($_FILES['asset'])) tattooLibraryJson(['ok' => false, 'error' => 'Choose a tattoo image to upload.'], 422);
    $upload = $_FILES['asset'];
    if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) {
        tattooLibraryJson(['ok' => false, 'error' => 'The tattoo image upload did not complete.'], 422);
    }
    $size = (int)($upload['size'] ?? 0);
    if ($size < 1 || $size > 20 * 1024 * 1024) tattooLibraryJson(['ok' => false, 'error' => 'Tattoo assets must be 20 MB or smaller.'], 422);
    $bytes = file_get_contents((string)$upload['tmp_name']);
    if (!is_string($bytes)) tattooLibraryJson(['ok' => false, 'error' => 'The uploaded file could not be read.'], 422);
    $width = 0;
    $height = 0;
    if ($roleSpec['kind'] === 'pdf') {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: '';
        if ($mime !== 'application/pdf' || !str_starts_with($bytes, '%PDF-')) tattooLibraryJson(['ok' => false, 'error' => 'Upload a valid PDF document.'], 422);
        $output = $bytes;
        $outputMime = 'application/pdf';
    } else {
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? (string)($info['mime'] ?? '') : '';
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) tattooLibraryJson(['ok' => false, 'error' => 'Upload a valid PNG, JPG, or WebP tattoo image.'], 422);
        $width = (int)($info[0] ?? 0);
        $height = (int)($info[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
            tattooLibraryJson(['ok' => false, 'error' => 'Tattoo images must be no larger than 40 megapixels or 12,000px on either side.'], 422);
        }
        if ($width < 600 || $height < 600) tattooLibraryJson(['ok' => false, 'error' => 'Tattoo library images must be at least 600px on both sides.'], 422);
        $output = tattooLibraryImage($bytes, $mime, (string)$roleSpec['format'], $watermark);
        $outputMime = 'image/' . $roleSpec['format'];
    }

    $drop = $schedule[$sequence];
    $folderName = sprintf('%02d-%s', $drop['collection_index'] + 1, tattooLibrarySlug($drop['title']));
    $relativeFolder = 'uploads/stencil-library/' . $drop['collection_slug'] . '/' . $folderName;
    $root = dirname(__DIR__, 4) . '/beyond-tattoo';
    $directory = $root . '/' . $relativeFolder;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('The tattoo library asset folder could not be created.');
    }
    $destination = $directory . '/' . $roleSpec['file'];
    if (is_file($destination) && !$replace) {
        tattooLibraryJson(['ok' => false, 'error' => 'This drop already has that asset. Enable Replace existing assets to overwrite it.'], 409);
    }

    $sha256 = hash('sha256', $output);
    foreach (glob($root . '/uploads/stencil-library/*/*/metadata.json') ?: [] as $otherMetadataFile) {
        if (dirname($otherMetadataFile) === $directory) continue;
        $otherMetadata = json_decode((string)file_get_contents($otherMetadataFile), true);
        if (($otherMetadata['assets'][$role]['sha256'] ?? '') === $sha256) {
            tattooLibraryJson(['ok' => false, 'error' => 'This exact asset is already assigned to another Season One drop.'], 409);
        }
    }

    if (file_put_contents($destination, $output, LOCK_EX) === false) throw new RuntimeException('The tattoo asset could not be saved.');
    @chmod($destination, 0664);

    $metadataFile = $directory . '/metadata.json';
    $metadata = [];
    if (is_file($metadataFile)) {
        $decoded = json_decode((string)file_get_contents($metadataFile), true);
        if (is_array($decoded)) $metadata = $decoded;
    }
    $metadata = array_replace($metadata, [
        'sequence' => $sequence,
        'season_total' => 55,
        'title' => $drop['title'],
        'collection' => $drop['collection'],
        'collection_slug' => $drop['collection_slug'],
        'release_date' => $drop['release_date'],
        'status' => 'draft',
        'rights_confirmed' => (bool)($metadata['rights_confirmed'] ?? false),
        'updated_at' => gmdate('c'),
    ]);
    $metadata['assets'] = is_array($metadata['assets'] ?? null) ? $metadata['assets'] : [];
    unset($metadata['approved_at'], $metadata['approved_by']);
    $metadata['assets'][$role] = [
        'file' => $roleSpec['file'],
        'source_name' => mb_substr(basename((string)($upload['name'] ?? 'uploaded-image')), 0, 180),
        'mime' => $outputMime,
        'width' => $width,
        'height' => $height,
        'watermarked' => $watermark,
        'sha256' => $sha256,
        'uploaded_at' => gmdate('c'),
    ];
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);

    tattooLibraryJson([
        'ok' => true,
        'sequence' => $sequence,
        'title' => $drop['title'],
        'collection' => $drop['collection'],
        'role' => $role,
        'status' => 'draft',
        'asset_url' => '/beyond-tattoo/' . $relativeFolder . '/' . $roleSpec['file'],
        'message' => sprintf('Drop %02d · %s %s uploaded.', $sequence, $drop['title'], $role),
    ]);
} catch (Throwable $error) {
    error_log('Tattoo library upload failed: ' . $error->getMessage());
    tattooLibraryJson(['ok' => false, 'error' => $error->getMessage()], 400);
}
