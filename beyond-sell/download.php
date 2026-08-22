<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app-layout.php';
require_beyond_id();

$orderItemId = max(0, (int)($_GET['item'] ?? 0));
try {
    $statement = beyond_db()->prepare("SELECT da.file_path FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN digital_assets da ON da.listing_id=oi.listing_id WHERE oi.id=? AND o.buyer_id=? AND o.status IN ('paid','processing','ready_to_ship','shipped','delivered','completed') AND oi.item_type='digital' ORDER BY da.id LIMIT 1");
    $statement->execute([$orderItemId, (int)$_SESSION['user_id']]);
    $path = (string)($statement->fetchColumn() ?: '');
    if ($path === '') throw new RuntimeException('Download not found.');
    if (preg_match('#^https://#i', $path)) {
        header('Location: ' . $path, true, 302);
        exit;
    }
    $root = realpath(__DIR__ . '/..');
    $file = realpath((string)$root . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
    if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) throw new RuntimeException('Download not found.');
    header('Content-Type: application/octet-stream');
    $downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($file)) ?: 'beyond-market-download';
    header('Content-Disposition: attachment; filename="' . addcslashes($downloadName, '"\\') . '"');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
} catch (Throwable $exception) {
    http_response_code(404);
    $wallet = bos_page_start('Beyond Market', 'Download unavailable', 'This order download is unavailable.');
    ?><main class="bos-main"><section class="bos-hero"><span class="bos-kicker">Beyond Market</span><h1>Download unavailable.</h1><p>This file is not attached to your paid order, or the seller has not finished delivery.</p><a class="bos-btn" href="orders.php">Return to my orders</a></section></main><?php
    bos_page_end();
}
