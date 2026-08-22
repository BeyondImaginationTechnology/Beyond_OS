<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/asset-library.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300');

$base = 'https://beyondimagination.co.technology/beyond-tattoo/';
$assets = bt_asset_library();
$today = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
$collections = [];
$items = [];
foreach ($assets as $asset) {
    $collectionSlug = $asset['collection_slug'];
    $collections[$collectionSlug] ??= [
        'id' => $collectionSlug,
        'name' => $asset['collection'],
        'description' => $asset['collection_description'],
        'assets' => [],
    ];
    $item = [
        'id' => $asset['id'],
        'title' => $asset['title'],
        'sequence' => $asset['sequence'],
        'collection_id' => $collectionSlug,
        'collection' => $asset['collection'],
        'release_date' => $asset['release_date'],
        'display_date' => $asset['display_date'],
        'summary' => $asset['description'],
        'style' => $asset['style'],
        'placement' => $asset['placement'],
        'difficulty' => $asset['difficulty'],
        'status' => 'approved',
        'rights_confirmed' => $asset['rights_confirmed'],
        'reward_bits' => $asset['reward_bits'],
        'is_released' => $asset['release_date'] <= $today,
        'preview_url' => $base . $asset['preview_url'],
        'stencil_url' => $base . $asset['stencil_url'],
        'transfer_url' => $asset['transfer_png_url'] !== '' ? $base . $asset['transfer_png_url'] : null,
        'pdf_url' => $asset['transfer_pdf_url'] !== '' ? $base . $asset['transfer_pdf_url'] : null,
        'reference_url' => $asset['reference_image_url'] !== '' ? $base . $asset['reference_image_url'] : null,
        'placement_image_url' => $asset['placement_image_url'] !== '' ? $base . $asset['placement_image_url'] : null,
        'pack_url' => $asset['pack_image_url'] !== '' ? $base . $asset['pack_image_url'] : null,
        'lore_url' => $asset['lore_card_url'] !== '' ? $base . $asset['lore_card_url'] : null,
        'style_card_url' => $asset['style_card_url'] !== '' ? $base . $asset['style_card_url'] : null,
    ];
    $collections[$collectionSlug]['assets'][] = $asset['id'];
    $items[] = $item;
}
$released = array_values(array_filter($items, static fn(array $asset): bool => $asset['is_released']));
$daily = $released ? end($released) : ($items[0] ?? null);

echo json_encode([
    'version' => '1.2',
    'season_total' => 55,
    'asset_count' => count($items),
    'daily_id' => $daily['id'] ?? null,
    'generated_at' => gmdate('c'),
    'collections' => array_values($collections),
    'assets' => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
