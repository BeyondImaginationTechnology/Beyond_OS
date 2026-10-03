<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
header('Cache-Control: private, no-store');

if (!Auth::check()) { http_response_code(403); exit('Administrator access required.'); }
$root = dirname(__DIR__) . '/storage/asset-inbox';
$batch = trim((string)($_GET['batch'] ?? ''));
$position = (int)($_GET['position'] ?? 0);
if ($batch !== '' && !preg_match('/^[a-z0-9][a-z0-9-]{7,48}$/', $batch)) { http_response_code(400); exit('Invalid batch.'); }
if ($position > 0) {
    $manifestPath = $root . '/' . $batch . '/manifest.json';
    $manifest = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : null;
    $item = is_array($manifest) ? ($manifest['items'][(string)$position] ?? null) : null;
    $stored = is_array($item) ? (string)($item['stored_name'] ?? '') : '';
    if (!is_array($item) || !preg_match('/^[0-9]{3}-[a-f0-9]{10}-[a-zA-Z0-9._-]+\.(png|jpe?g|webp|gif)$/i', $stored)) { http_response_code(404); exit('Preview unavailable.'); }
    $path = $root . '/' . $batch . '/' . $stored;
    $mime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif'][strtolower(pathinfo($stored, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    if (!is_file($path)) { http_response_code(404); exit('Preview unavailable.'); }
    header('Content-Type: ' . $mime); header('X-Content-Type-Options: nosniff');
    readfile($path); exit;
}
header('Content-Type: application/json; charset=UTF-8');
$batches = [];
foreach (glob($root . '/*/manifest.json') ?: [] as $manifestPath) {
    $data = json_decode((string)file_get_contents($manifestPath), true);
    if (!is_array($data) || ($data['purpose'] ?? '') !== 'unsorted-tattoo-asset-inbox') continue;
    $batches[] = ['id'=>(string)($data['batch_id'] ?? basename(dirname($manifestPath))), 'uploaded'=>(int)($data['uploaded_count'] ?? 0), 'total'=>(int)($data['total_count'] ?? 0), 'created'=>(string)($data['created_at'] ?? ''), 'status'=>(string)($data['status'] ?? '')];
}
usort($batches, static fn($a,$b) => strcmp($b['created'], $a['created']));
if ($batch === '') { echo json_encode(['ok'=>true,'batches'=>$batches], JSON_UNESCAPED_SLASHES); exit; }
$manifestPath = $root . '/' . $batch . '/manifest.json';
$manifest = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : null;
if (!is_array($manifest) || ($manifest['purpose'] ?? '') !== 'unsorted-tattoo-asset-inbox') { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Batch not found.']); exit; }
$items = [];
foreach (($manifest['items'] ?? []) as $entry) {
    if (!is_array($entry)) continue;
    $name = (string)($entry['stored_name'] ?? '');
    $image = preg_match('/\.(png|jpe?g|webp|gif)$/i', $name) === 1;
    $items[] = ['position'=>(int)($entry['inbox_position'] ?? 0),'name'=>(string)($entry['source_name'] ?? $name),'mime'=>(string)($entry['mime'] ?? ''),'bytes'=>(int)($entry['bytes'] ?? 0),'width'=>$entry['width'] ?? null,'height'=>$entry['height'] ?? null,'sha256'=>(string)($entry['sha256'] ?? ''),'image'=>$image,'preview'=>$image ? ('api/browse-tattoo-asset-inbox.php?batch=' . rawurlencode($batch) . '&position=' . (int)($entry['inbox_position'] ?? 0)) : null];
}
echo json_encode(['ok'=>true,'batch'=>$manifest,'items'=>$items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
