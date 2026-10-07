<?php
declare(strict_types=1);

/**
 * Creates the standard carousel derivatives from approved source artwork.
 * Existing dedicated assets are never overwritten.
 */
const ROOT = __DIR__ . '/../beyond-tattoo/assets/stencils';
const MAX_WIDTH = 1080;
const MAX_HEIGHT = 1350;

function firstAsset(string $directory, array $bases): ?string
{
    foreach ($bases as $base) {
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $extension) {
            $candidate = $directory . '/' . $base . '.' . $extension;
            if (is_file($candidate)) return $candidate;
        }
    }
    return null;
}

function readImage(string $file): GdImage
{
    return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => imagecreatefromjpeg($file),
        'png' => imagecreatefrompng($file),
        'webp' => imagecreatefromwebp($file),
        default => throw new RuntimeException('Unsupported source image: ' . $file),
    };
}

function canvasFrom(string $source): GdImage
{
    $input = readImage($source);
    $width = imagesx($input);
    $height = imagesy($input);
    $scale = min(MAX_WIDTH / $width, MAX_HEIGHT / $height, 1);
    $targetWidth = max(1, (int)round($width * $scale));
    $targetHeight = max(1, (int)round($height * $scale));
    $canvas = imagecreatetruecolor(MAX_WIDTH, MAX_HEIGHT);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $input, (int)((MAX_WIDTH - $targetWidth) / 2), (int)((MAX_HEIGHT - $targetHeight) / 2), 0, 0, $targetWidth, $targetHeight, $width, $height);
    imagedestroy($input);
    return $canvas;
}

function writeDerivative(string $source, string $target): void
{
    $canvas = canvasFrom($source);
    imagewebp($canvas, $target, 92);
    imagedestroy($canvas);
}

function writeVioletTrace(string $source, string $target): void
{
    $canvas = canvasFrom($source);
    $width = imagesx($canvas);
    $height = imagesy($canvas);
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($canvas, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            $luma = (int)(($r * 0.2126) + ($g * 0.7152) + ($b * 0.0722));
            if ($luma < 210) {
                $strength = (210 - $luma) / 210;
                $violet = imagecolorallocate($canvas, (int)(121 - 55 * $strength), (int)(78 - 42 * $strength), (int)(211 + 34 * $strength));
                imagesetpixel($canvas, $x, $y, $violet);
            } else {
                imagesetpixel($canvas, $x, $y, imagecolorallocate($canvas, 255, 255, 255));
            }
        }
    }
    imagepng($canvas, $target, 6);
    imagedestroy($canvas);
}

$created = [];
foreach (glob(ROOT . '/*/*/metadata.json') ?: [] as $metadataFile) {
    $directory = dirname($metadataFile);
    $metadata = json_decode((string)file_get_contents($metadataFile), true);
    if (!is_array($metadata)) continue;
    $collection = basename(dirname($directory));
    $status = strtolower((string)($metadata['status'] ?? 'draft'));
    if (!in_array($status, ['approved', 'published'], true) && $collection !== 'beyond-ancient') continue;

    $preview = firstAsset($directory, ['preview-watermarked', 'reference-artwork', 'detail-artwork', 'stencil-outline', 'stencil-print-ready']);
    $outline = firstAsset($directory, ['stencil-outline', 'stencil-print-ready', 'preview-watermarked', 'reference-artwork']);
    if ($preview === null || $outline === null) continue;

    $derivatives = [
        'reference-artwork.webp' => $preview,
        'placement-mockup.webp' => $preview,
        'premium-packaging.webp' => $preview,
        'lore-card.webp' => $preview,
        'style-card.webp' => $outline,
        'stencil-print-ready.png' => $outline,
    ];
    foreach ($derivatives as $name => $source) {
        $target = $directory . '/' . $name;
        if (is_file($target)) continue;
        writeDerivative($source, $target);
        $created[] = substr($target, strlen(ROOT) + 1);
    }
    $violet = $directory . '/stencil-violet-trace.png';
    if (!is_file($violet)) {
        writeVioletTrace($outline, $violet);
        $created[] = substr($violet, strlen(ROOT) + 1);
    }
}

echo 'Created ' . count($created) . " stencil carousel assets\n";
foreach ($created as $file) echo $file . "\n";
