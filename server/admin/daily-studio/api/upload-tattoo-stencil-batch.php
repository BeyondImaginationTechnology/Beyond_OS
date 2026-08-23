<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function tattooBatchJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function tattooBatchSafeStem(string $value): string
{
    $stem = (string)pathinfo($value, PATHINFO_FILENAME);
    $stem = trim((string)(preg_replace('/[^a-zA-Z0-9._-]+/', '-', $stem) ?? ''), '.-_');
    return substr($stem !== '' ? $stem : 'asset', 0, 120);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') tattooBatchJson(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) tattooBatchJson(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) tattooBatchJson(['ok' => false, 'error' => 'Invalid security token.'], 403);

    $totalCount = (int)($_POST['total_count'] ?? 0);
    $position = (int)($_POST['position'] ?? 0);
    if ($totalCount < 1 || $totalCount > 500) tattooBatchJson(['ok' => false, 'error' => 'An asset inbox batch may contain between 1 and 500 files.'], 422);
    if ($position < 1 || $position > $totalCount) tattooBatchJson(['ok' => false, 'error' => 'The asset position is outside this batch.'], 422);
    if (!isset($_FILES['asset']) || !is_array($_FILES['asset'])) tattooBatchJson(['ok' => false, 'error' => 'Choose an asset to upload.'], 422);

    $upload = $_FILES['asset'];
    if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) {
        tattooBatchJson(['ok' => false, 'error' => 'The asset upload did not complete.'], 422);
    }
    $size = (int)($upload['size'] ?? 0);
    if ($size < 1 || $size > 100 * 1024 * 1024) tattooBatchJson(['ok' => false, 'error' => 'Each asset must be 100 MB or smaller.'], 422);
    $bytes = file_get_contents((string)$upload['tmp_name']);
    if (!is_string($bytes)) tattooBatchJson(['ok' => false, 'error' => 'The uploaded asset could not be read.'], 422);

    $sourceName = mb_substr(basename((string)($upload['name'] ?? 'asset')), 0, 180);
    $extension = strtolower((string)pathinfo($sourceName, PATHINFO_EXTENSION));
    $extension = $extension === 'jpeg' ? 'jpg' : $extension;
    $allowedExtensions = ['png', 'jpg', 'webp', 'gif', 'heic', 'heif', 'svg', 'pdf', 'zip', 'mp4', 'mov'];
    if (!in_array($extension, $allowedExtensions, true)) {
        tattooBatchJson(['ok' => false, 'error' => 'Supported assets are PNG, JPG, WebP, GIF, HEIC, HEIF, SVG, PDF, ZIP, MP4, and MOV.'], 422);
    }
    $mime = (string)($upload['type'] ?? 'application/octet-stream');
    if (function_exists('finfo_open')) {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo !== false) {
            $detectedMime = finfo_file($fileInfo, (string)$upload['tmp_name']);
            if (is_string($detectedMime) && $detectedMime !== '') $mime = $detectedMime;
            finfo_close($fileInfo);
        }
    }
    $width = null;
    $height = null;
    if (in_array($extension, ['png', 'jpg', 'webp', 'gif'], true)) {
        $info = @getimagesizefromstring($bytes);
        if (!is_array($info)) tattooBatchJson(['ok' => false, 'error' => 'The selected image is invalid or damaged.'], 422);
        $width = (int)($info[0] ?? 0);
        $height = (int)($info[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 16000 || $height > 16000 || ($width * $height) > 100000000) {
            tattooBatchJson(['ok' => false, 'error' => 'Images must be no larger than 100 megapixels or 16,000px on either side.'], 422);
        }
    } elseif ($extension === 'pdf' && !str_starts_with($bytes, '%PDF-')) {
        tattooBatchJson(['ok' => false, 'error' => 'The selected PDF is invalid.'], 422);
    } elseif ($extension === 'zip' && !str_starts_with($bytes, "PK")) {
        tattooBatchJson(['ok' => false, 'error' => 'The selected ZIP archive is invalid.'], 422);
    } elseif ($extension === 'svg' && stripos(substr($bytes, 0, 4096), '<svg') === false) {
        tattooBatchJson(['ok' => false, 'error' => 'The selected SVG is invalid.'], 422);
    }

    $batchId = strtolower(trim((string)($_POST['batch_id'] ?? '')));
    if ($batchId === '') $batchId = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
    if (!preg_match('/^[a-z0-9][a-z0-9-]{7,48}$/', $batchId)) tattooBatchJson(['ok' => false, 'error' => 'Invalid batch identifier.'], 422);

    $batchDirectory = dirname(__DIR__) . '/storage/asset-inbox/' . $batchId;
    if (!is_dir($batchDirectory) && !mkdir($batchDirectory, 0770, true) && !is_dir($batchDirectory)) {
        throw new RuntimeException('The private asset inbox could not be created.');
    }
    $lock = fopen($batchDirectory . '/.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('The asset batch could not be locked.');
    try {
        $manifestFile = $batchDirectory . '/manifest.json';
        $manifest = [
            'schema_version' => 2,
            'batch_id' => $batchId,
            'purpose' => 'unsorted-tattoo-asset-inbox',
            'status' => 'uploading',
            'total_count' => $totalCount,
            'uploaded_count' => 0,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
            'items' => [],
        ];
        if (is_file($manifestFile)) {
            $decoded = json_decode((string)file_get_contents($manifestFile), true);
            if (!is_array($decoded) || (int)($decoded['total_count'] ?? 0) !== $totalCount) throw new RuntimeException('The existing batch manifest is invalid.');
            $manifest = $decoded;
        }
        $positionKey = (string)$position;
        if (isset($manifest['items'][$positionKey])) tattooBatchJson(['ok' => false, 'error' => 'That position is already present in this batch.'], 409);
        $sha256 = hash('sha256', $bytes);

        $storedName = sprintf('%03d-%s-%s.%s', $position, substr($sha256, 0, 10), tattooBatchSafeStem($sourceName), $extension);
        if (file_put_contents($batchDirectory . '/' . $storedName, $bytes, LOCK_EX) === false) throw new RuntimeException('The asset could not be saved.');
        @chmod($batchDirectory . '/' . $storedName, 0660);
        $manifest['items'][$positionKey] = [
            'inbox_position' => $position,
            'source_name' => $sourceName,
            'stored_name' => $storedName,
            'mime' => $mime,
            'bytes' => $size,
            'width' => $width,
            'height' => $height,
            'sha256' => $sha256,
            'sort_status' => 'unsorted',
            'assignment' => ['sequence' => null, 'title' => null, 'collection' => null, 'role' => null, 'category' => null, 'confidence' => null],
            'uploaded_at' => gmdate('c'),
        ];
        ksort($manifest['items'], SORT_NUMERIC);
        $manifest['uploaded_count'] = count($manifest['items']);
        $manifest['status'] = $manifest['uploaded_count'] === $totalCount ? 'ready_to_sort' : 'uploading';
        $manifest['updated_at'] = gmdate('c');
        if (file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            throw new RuntimeException('The asset batch manifest could not be saved.');
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    tattooBatchJson([
        'ok' => true,
        'batch_id' => $batchId,
        'position' => $position,
        'uploaded_count' => $manifest['uploaded_count'],
        'total_count' => $totalCount,
        'status' => $manifest['status'],
        'message' => $manifest['status'] === 'ready_to_sort'
            ? sprintf('All %d assets are safely stored and ready to sort later.', $totalCount)
            : sprintf('Asset %d of %d uploaded.', $manifest['uploaded_count'], $totalCount),
    ]);
} catch (Throwable $error) {
    error_log('Tattoo asset inbox upload failed: ' . $error->getMessage());
    tattooBatchJson(['ok' => false, 'error' => $error->getMessage()], 400);
}
