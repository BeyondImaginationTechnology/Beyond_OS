<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/beyond-tattoo/includes/library-catalog.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function tattooApprovalResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function tattooApprovalSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') tattooApprovalResponse(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) tattooApprovalResponse(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) tattooApprovalResponse(['ok' => false, 'error' => 'Invalid security token.'], 403);

    $sequence = (int)($_POST['sequence'] ?? 0);
    $requestedStatus = strtolower(trim((string)($_POST['status'] ?? 'approved')));
    if (!in_array($requestedStatus, ['approved', 'draft'], true)) tattooApprovalResponse(['ok' => false, 'error' => 'Status must be approved or draft.'], 422);

    $drop = null;
    $currentSequence = 0;
    foreach (bt_library_collections() as $collectionSlug => $collection) {
        foreach ($collection['stencils'] as $collectionIndex => [$title, $releaseDate]) {
            $currentSequence++;
            if ($currentSequence === $sequence) {
                $drop = compact('collectionSlug', 'collectionIndex', 'title', 'releaseDate');
                $drop['collection'] = $collection['name'];
                break 2;
            }
        }
    }
    if (!is_array($drop)) tattooApprovalResponse(['ok' => false, 'error' => 'Choose a valid Season One drop from 1 to 55.'], 422);

    $folderName = sprintf('%02d-%s', $drop['collectionIndex'] + 1, tattooApprovalSlug($drop['title']));
    $tattooRoot = dirname(__DIR__, 4) . '/beyond-tattoo';
    $uploadDirectory = $tattooRoot . '/uploads/stencil-library/' . $drop['collectionSlug'] . '/' . $folderName;
    $bundledDirectory = $tattooRoot . '/assets/stencils/' . $drop['collectionSlug'] . '/' . $folderName;
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The tattoo library metadata folder could not be created.');
    }
    $metadataFile = $uploadDirectory . '/metadata.json';
    $fallbackMetadataFile = $bundledDirectory . '/metadata.json';
    $metadata = [];
    foreach ([$metadataFile, $fallbackMetadataFile] as $candidate) {
        if (!is_file($candidate)) continue;
        $decoded = json_decode((string)file_get_contents($candidate), true);
        if (is_array($decoded)) { $metadata = $decoded; break; }
    }

    if ($requestedStatus === 'approved') {
        $previewExists = is_file($uploadDirectory . '/preview-watermarked.png') || is_file($bundledDirectory . '/preview-watermarked.png');
        $stencilExists = is_file($uploadDirectory . '/stencil-print-ready.png') || is_file($bundledDirectory . '/stencil-print-ready.png');
        if (!$previewExists || !$stencilExists) tattooApprovalResponse(['ok' => false, 'error' => 'Preview and print-ready stencil files are required before approval.'], 422);
        if (!filter_var($_POST['rights_confirmed'] ?? false, FILTER_VALIDATE_BOOL)) {
            tattooApprovalResponse(['ok' => false, 'error' => 'Confirm that Beyond Tattoo has permission to publish these assets.'], 422);
        }
        $metadata['rights_confirmed'] = true;
        $metadata['approved_at'] = gmdate('c');
        $metadata['approved_by'] = (int)($_SESSION['user_id'] ?? 0);
        foreach (['description' => 1200, 'style' => 180, 'placement' => 240, 'difficulty' => 80] as $field => $limit) {
            $value = mb_substr(trim((string)($_POST[$field] ?? '')), 0, $limit);
            if ($value !== '') $metadata[$field] = $value;
        }
    }

    $metadata = array_replace($metadata, [
        'sequence' => $sequence,
        'season_total' => 55,
        'title' => $drop['title'],
        'collection' => $drop['collection'],
        'collection_slug' => $drop['collectionSlug'],
        'release_date' => $drop['releaseDate'],
        'status' => $requestedStatus,
        'updated_at' => gmdate('c'),
    ]);
    if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        throw new RuntimeException('The approval metadata could not be saved.');
    }

    tattooApprovalResponse([
        'ok' => true,
        'sequence' => $sequence,
        'title' => $drop['title'],
        'status' => $requestedStatus,
        'message' => $requestedStatus === 'approved' ? 'Drop approved for the 1.2 library.' : 'Drop returned to draft.',
    ]);
} catch (Throwable $error) {
    error_log('Tattoo library approval failed: ' . $error->getMessage());
    tattooApprovalResponse(['ok' => false, 'error' => $error->getMessage()], 400);
}
