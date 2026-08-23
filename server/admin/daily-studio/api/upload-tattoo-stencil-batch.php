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
    return substr($stem !== '' ? $stem : 'stencil', 0, 120);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') tattooBatchJson(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) tattooBatchJson(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) tattooBatchJson(['ok' => false, 'error' => 'Invalid security token.'], 403);

    $expectedCount = (int)($_POST['expected_count'] ?? 55);
    $position = (int)($_POST['position'] ?? 0);
    if ($expectedCount !== 55) tattooBatchJson(['ok' => false, 'error' => 'A stencil sorting batch must contain exactly 55 files.'], 422);
    if ($position < 1 || $position > $expectedCount) tattooBatchJson(['ok' => false, 'error' => 'Batch position must be between 1 and 55.'], 422);
    if (!isset($_FILES['stencil']) || !is_array($_FILES['stencil'])) tattooBatchJson(['ok' => false, 'error' => 'Choose a stencil image to upload.'], 422);

    $upload = $_FILES['stencil'];
    if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) {
        tattooBatchJson(['ok' => false, 'error' => 'The stencil upload did not complete.'], 422);
    }
    $size = (int)($upload['size'] ?? 0);
    if ($size < 1 || $size > 20 * 1024 * 1024) tattooBatchJson(['ok' => false, 'error' => 'Each stencil must be 20 MB or smaller.'], 422);
    $bytes = file_get_contents((string)$upload['tmp_name']);
    if (!is_string($bytes)) tattooBatchJson(['ok' => false, 'error' => 'The uploaded stencil could not be read.'], 422);
    $info = @getimagesizefromstring($bytes);
    $mime = is_array($info) ? (string)($info['mime'] ?? '') : '';
    $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) tattooBatchJson(['ok' => false, 'error' => 'Upload a valid PNG, JPG, or WebP stencil image.'], 422);
    $width = (int)($info[0] ?? 0);
    $height = (int)($info[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
        tattooBatchJson(['ok' => false, 'error' => 'Stencil images must be no larger than 40 megapixels or 12,000px on either side.'], 422);
    }

    $batchId = strtolower(trim((string)($_POST['batch_id'] ?? '')));
    if ($batchId === '') $batchId = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
    if (!preg_match('/^[a-z0-9][a-z0-9-]{7,48}$/', $batchId)) tattooBatchJson(['ok' => false, 'error' => 'Invalid batch identifier.'], 422);

    $batchDirectory = dirname(__DIR__) . '/storage/stencil-inbox/' . $batchId;
    if (!is_dir($batchDirectory) && !mkdir($batchDirectory, 0770, true) && !is_dir($batchDirectory)) {
        throw new RuntimeException('The private stencil inbox could not be created.');
    }
    $lock = fopen($batchDirectory . '/.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('The stencil batch could not be locked.');
    try {
        $manifestFile = $batchDirectory . '/manifest.json';
        $manifest = [
            'schema_version' => 1,
            'batch_id' => $batchId,
            'purpose' => 'gpt-stencil-sort',
            'status' => 'uploading',
            'expected_count' => $expectedCount,
            'uploaded_count' => 0,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
            'items' => [],
        ];
        if (is_file($manifestFile)) {
            $decoded = json_decode((string)file_get_contents($manifestFile), true);
            if (!is_array($decoded) || (int)($decoded['expected_count'] ?? 0) !== $expectedCount) throw new RuntimeException('The existing batch manifest is invalid.');
            $manifest = $decoded;
        }
        $positionKey = (string)$position;
        if (isset($manifest['items'][$positionKey])) tattooBatchJson(['ok' => false, 'error' => 'That position is already present in this batch.'], 409);
        $sha256 = hash('sha256', $bytes);
        foreach ($manifest['items'] as $item) {
            if (($item['sha256'] ?? '') === $sha256) tattooBatchJson(['ok' => false, 'error' => 'This exact stencil is already in the batch.'], 409);
        }

        $sourceName = mb_substr(basename((string)($upload['name'] ?? 'stencil')), 0, 180);
        $storedName = sprintf('%02d-%s-%s.%s', $position, substr($sha256, 0, 10), tattooBatchSafeStem($sourceName), $extensions[$mime]);
        if (file_put_contents($batchDirectory . '/' . $storedName, $bytes, LOCK_EX) === false) throw new RuntimeException('The stencil could not be saved.');
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
            'sort_status' => 'awaiting_gpt',
            'assignment' => ['sequence' => null, 'title' => null, 'collection' => null, 'confidence' => null],
            'uploaded_at' => gmdate('c'),
        ];
        ksort($manifest['items'], SORT_NUMERIC);
        $manifest['uploaded_count'] = count($manifest['items']);
        $manifest['status'] = $manifest['uploaded_count'] === $expectedCount ? 'ready_for_gpt_sort' : 'uploading';
        $manifest['updated_at'] = gmdate('c');
        if (file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            throw new RuntimeException('The stencil batch manifest could not be saved.');
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
        'expected_count' => $expectedCount,
        'status' => $manifest['status'],
        'message' => $manifest['status'] === 'ready_for_gpt_sort'
            ? 'All 55 stencils are safely staged and ready for GPT sorting.'
            : sprintf('Stencil %d of 55 staged.', $manifest['uploaded_count']),
    ]);
} catch (Throwable $error) {
    error_log('Tattoo stencil batch upload failed: ' . $error->getMessage());
    tattooBatchJson(['ok' => false, 'error' => $error->getMessage()], 400);
}
