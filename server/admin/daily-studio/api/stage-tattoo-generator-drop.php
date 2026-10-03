<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/beyond-tattoo/includes/library-catalog.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function stageTattooReply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function stageTattooPng(array $input, string $key, bool $required = false): ?string
{
    $data = trim((string)($input[$key] ?? ''));
    if ($data === '' && !$required) return null;
    if (!preg_match('#^data:image/png;base64,(.+)$#s', $data, $match)) throw new RuntimeException("Invalid {$key} PNG.");
    $bytes = base64_decode($match[1], true);
    $info = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
    if (!is_string($bytes) || strlen($bytes) > 15 * 1024 * 1024 || !is_array($info)
        || ($info['mime'] ?? '') !== 'image/png' || ($info[0] ?? 0) < 600 || ($info[1] ?? 0) < 600) {
        throw new RuntimeException("{$key} must be a valid PNG at least 600px wide and tall, under 15 MB.");
    }
    return $bytes;
}

function stageTattooConvert(string $png, string $format, bool $watermark = false): string
{
    if (!function_exists('imagecreatefromstring') || ($format === 'webp' && !function_exists('imagewebp'))) {
        throw new RuntimeException('PHP GD with WebP support is required to prepare library images.');
    }
    $image = @imagecreatefromstring($png);
    if ($image === false) throw new RuntimeException('A generated image could not be decoded.');
    if ($watermark) {
        imagealphablending($image, true);
        $width = imagesx($image);
        $height = imagesy($image);
        $barHeight = max(30, (int)round($height * .045));
        $bar = imagecolorallocatealpha($image, 5, 4, 8, 38);
        $ink = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, $height - $barHeight, $width, $height, $bar);
        imagestring($image, 5, max(12, (int)round($width * .025)), $height - $barHeight + 7, 'BEYOND TATTOO  |  PREVIEW', $ink);
    }
    ob_start();
    $ok = $format === 'webp' ? imagewebp($image, null, 92) : imagepng($image, null, 9);
    $output = ob_get_clean();
    if (!$ok || !is_string($output) || $output === '') throw new RuntimeException('A generated image could not be converted.');
    return $output;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') stageTattooReply(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) stageTattooReply(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) stageTattooReply(['ok' => false, 'error' => 'Invalid security token.'], 403);
    $input = json_decode((string)file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new RuntimeException('Invalid kit payload.');
    $sequence = (int)($input['sequence'] ?? 0);
    $drop = bt_season_one_drops()[$sequence - 1] ?? null;
    if ($drop === null) stageTattooReply(['ok' => false, 'error' => 'Choose a numbered Season One drop from 1 to 55.'], 422);
    if (($input['campaign'] ?? '') !== 'season-one') stageTattooReply(['ok' => false, 'error' => 'This library handoff is for Season One.'], 422);
    $append = !empty($input['append']);
    $stencil = stageTattooPng($input, 'png', !$append);
    $files = $stencil === null ? [] : [
        'preview-watermarked.png' => stageTattooConvert($stencil, 'png', true),
        'stencil-print-ready.png' => $stencil,
    ];
    foreach (['reference_png' => 'reference-artwork.webp', 'placement_png' => 'placement-mockup.webp',
              'pack_png' => 'premium-packaging.webp', 'lore_card_png' => 'lore-card.webp',
              'style_card_png' => 'style-card.webp'] as $key => $filename) {
        $png = stageTattooPng($input, $key);
        if ($png !== null) $files[$filename] = stageTattooConvert($png, 'webp');
    }
    if (!$files) stageTattooReply(['ok' => false, 'error' => 'No image was provided for this stage.'], 422);
    $slug = strtolower(trim((string)(preg_replace('/[^a-z0-9]+/i', '-', $drop['title']) ?? ''), '-'));
    $folderName = sprintf('%02d-%s', $drop['collection_index'] + 1, $slug);
    $root = dirname(__DIR__, 4) . '/beyond-tattoo';
    $directory = $root . '/uploads/stencil-library/' . $drop['collection_slug'] . '/' . $folderName;
    $bundled = $root . '/assets/stencils/' . $drop['collection_slug'] . '/' . $folderName;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('Could not create the drop folder.');
    $lock = fopen($directory . '/.stage.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Could not lock the drop folder.');
    try {
        if ($append && (!is_file($directory . '/metadata.json') || strtolower((string)(json_decode((string)file_get_contents($directory . '/metadata.json'), true)['status'] ?? '')) !== 'draft')) {
            throw new RuntimeException('Start a new draft before adding kit assets.');
        }
        if (!$append && empty($input['replace']) && (is_file($directory . '/metadata.json') || is_file($bundled . '/metadata.json')
            || is_file($directory . '/stencil-print-ready.png') || is_file($bundled . '/stencil-print-ready.png'))) {
            throw new RuntimeException('This drop already has assets. Enable Replace existing assets to stage a revision.', 409);
        }
        if (!$append) {
            $metadata = [];
            foreach ([$directory . '/metadata.json', $bundled . '/metadata.json'] as $candidate) {
                if (!is_file($candidate)) continue;
                $decoded = json_decode((string)file_get_contents($candidate), true);
                if (is_array($decoded)) { $metadata = $decoded; break; }
            }
            $metadata = array_replace($metadata, [
                'sequence' => $sequence, 'season_drop' => $sequence, 'season_total' => 55,
                'title' => $drop['title'], 'collection' => $drop['collection'],
                'collection_slug' => $drop['collection_slug'], 'release_date' => $drop['release_date'],
                'description' => mb_substr(trim((string)($input['lore'] ?? '')), 0, 1200),
                'style' => mb_substr(trim((string)($input['style'] ?? '')), 0, 180),
                'placement' => mb_substr(trim((string)($input['placement'] ?? '')), 0, 240),
                'status' => 'draft', 'rights_confirmed' => false,
                'source' => 'Beyond Tattoo admin generator', 'updated_at' => gmdate('c'),
            ]);
            unset($metadata['approved_at'], $metadata['approved_by']);
            if (file_put_contents($directory . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
                throw new RuntimeException('Could not put the drop into draft review.');
            }
        }
        foreach ($files as $name => $bytes) {
            $temporary = $directory . '/.' . $name . '.' . bin2hex(random_bytes(5));
            if (file_put_contents($temporary, $bytes, LOCK_EX) === false || !rename($temporary, $directory . '/' . $name)) {
                @unlink($temporary);
                throw new RuntimeException("Could not stage {$name}.");
            }
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    stageTattooReply([
        'ok' => true, 'sequence' => $sequence, 'title' => $drop['title'],
        'asset_count' => count($files), 'status' => 'draft',
        'review_url' => '/server/admin/daily-studio/tattoo-asset-import.php',
        'message' => 'Kit staged in the numbered Season One library. Review and approve it before release.',
    ]);
} catch (Throwable $error) {
    error_log('Tattoo generator library staging failed: ' . $error->getMessage());
    stageTattooReply(['ok' => false, 'error' => $error->getMessage()], $error->getCode() === 409 ? 409 : 400);
}
