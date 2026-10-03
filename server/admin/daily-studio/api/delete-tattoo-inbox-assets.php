<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');

function inboxDeleteJson(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') inboxDeleteJson(['ok'=>false,'error'=>'POST required.'], 405);
    if (!Auth::check()) inboxDeleteJson(['ok'=>false,'error'=>'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) inboxDeleteJson(['ok'=>false,'error'=>'Invalid security token.'], 403);
    $batch = trim((string)($_POST['batch'] ?? ''));
    $positions = $_POST['positions'] ?? [];
    if (!preg_match('/^[a-z0-9][a-z0-9-]{7,48}$/', $batch) || !is_array($positions)) inboxDeleteJson(['ok'=>false,'error'=>'Choose valid private inbox assets.'], 422);
    $positions = array_values(array_unique(array_filter(array_map('intval', $positions), static fn(int $position): bool => $position > 0)));
    if ($positions === []) inboxDeleteJson(['ok'=>false,'error'=>'Choose one or more assets to remove.'], 422);
    if (count($positions) > 100) inboxDeleteJson(['ok'=>false,'error'=>'Remove at most 100 assets in one review pass.'], 422);
    $root = dirname(__DIR__) . '/storage/asset-inbox';
    $manifestFile = $root . '/' . $batch . '/manifest.json';
    $manifest = is_file($manifestFile) ? json_decode((string)file_get_contents($manifestFile), true) : null;
    if (!is_array($manifest) || ($manifest['purpose'] ?? '') !== 'unsorted-tattoo-asset-inbox') inboxDeleteJson(['ok'=>false,'error'=>'Private batch not found.'], 404);
    $deleted = [];
    foreach ($positions as $position) {
        $entry = $manifest['items'][(string)$position] ?? null;
        $stored = is_array($entry) ? (string)($entry['stored_name'] ?? '') : '';
        if (!is_array($entry) || !preg_match('/^[0-9]{3}-[a-f0-9]{10}-[a-zA-Z0-9._-]+$/i', $stored)) continue;
        $path = $root . '/' . $batch . '/' . $stored;
        if (is_file($path) && !unlink($path)) throw new RuntimeException('Could not remove private upload ' . str_pad((string)$position, 3, '0', STR_PAD_LEFT) . '.');
        unset($manifest['items'][(string)$position]);
        $deleted[] = $position;
    }
    if ($deleted === []) inboxDeleteJson(['ok'=>false,'error'=>'None of the selected assets could be removed.'], 404);
    $manifest['uploaded_count'] = count($manifest['items']);
    $manifest['updated_at'] = gmdate('c');
    if (file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX) === false) throw new RuntimeException('Could not update the private batch manifest.');
    inboxDeleteJson(['ok'=>true,'deleted'=>$deleted,'message'=>count($deleted) . ' private upload' . (count($deleted) === 1 ? '' : 's') . ' removed.']);
} catch (Throwable $error) {
    error_log('Tattoo inbox deletion failed: ' . $error->getMessage());
    inboxDeleteJson(['ok'=>false,'error'=>$error->getMessage()], 400);
}