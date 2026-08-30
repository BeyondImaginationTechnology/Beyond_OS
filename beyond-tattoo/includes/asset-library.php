<?php
declare(strict_types=1);

require_once __DIR__ . '/library-catalog.php';

function bt_asset_library_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function bt_asset_library_file(string $uploadedFolder, string $bundledFolder, string $file): ?array
{
    $root = dirname(__DIR__);
    foreach ([$uploadedFolder, $bundledFolder] as $folder) {
        $relative = $folder . '/' . $file;
        if (is_file($root . '/' . $relative)) return ['path' => $root . '/' . $relative, 'url' => $relative, 'file' => $file];
    }
    return null;
}

function bt_asset_library(): array
{
    $assets = [];
    $sequence = 0;
    $today = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    $styleDefaults = [
        'divine-realism' => 'Black-and-grey realism',
        'beyond-ancient' => 'Engraving realism',
        'japanese-legends' => 'Japanese-inspired flow',
          'dark-realism' => 'Dark realism',
         'beyond-studio-originals' => 'Gothic illustrative blackwork',
         'beyond-studio-japanese' => 'Japanese-inspired blackwork',
      ];
    foreach (bt_library_collections() as $collectionSlug => $collection) {
        foreach ($collection['stencils'] as $collectionIndex => [$title, $releaseDate]) {
            $sequence++;
            $folderName = sprintf('%02d-%s', $collectionIndex + 1, bt_asset_library_slug($title));
            $uploadedFolder = 'uploads/stencil-library/' . $collectionSlug . '/' . $folderName;
            $bundledFolder = 'assets/stencils/' . $collectionSlug . '/' . $folderName;
            $metadataFile = bt_asset_library_file($uploadedFolder, $bundledFolder, 'metadata.json');
            $metadata = [];
            if ($metadataFile !== null) {
                $decoded = json_decode((string)file_get_contents($metadataFile['path']), true);
                if (is_array($decoded)) $metadata = $decoded;
            }
            $publicationStatus = strtolower(trim((string)($metadata['status'] ?? 'draft')));
            if (!in_array($publicationStatus, ['approved', 'published'], true)) continue;
            if ($releaseDate > $today) continue;
            $preview = bt_asset_library_file($uploadedFolder, $bundledFolder, 'preview-watermarked.png');
            $stencil = bt_asset_library_file($uploadedFolder, $bundledFolder, 'stencil-print-ready.png');
            if ($preview === null || $stencil === null) continue;
            $transfer = bt_asset_library_file($uploadedFolder, $bundledFolder, 'studio-transfer-template.png');
            $pdf = bt_asset_library_file($uploadedFolder, $bundledFolder, 'stencil-print-ready.pdf');
            $reference = bt_asset_library_file($uploadedFolder, $bundledFolder, 'reference-artwork.webp');
            $placementImage = bt_asset_library_file($uploadedFolder, $bundledFolder, 'placement-mockup.webp');
            $pack = bt_asset_library_file($uploadedFolder, $bundledFolder, 'premium-packaging.webp');
            $lore = bt_asset_library_file($uploadedFolder, $bundledFolder, 'lore-card.webp');
            $styleCard = bt_asset_library_file($uploadedFolder, $bundledFolder, 'style-card.webp');
            $updated = max(array_map(static fn(array $asset): int => (int)filemtime($asset['path']), array_filter([$preview, $stencil, $transfer, $pdf, $reference, $placementImage, $pack, $lore, $styleCard])));
            $description = trim((string)($metadata['description'] ?? ''));
            if ($description === '') $description = $collection['description'];
            $assets[] = [
                'id' => bt_asset_library_slug($title) . '-' . $releaseDate,
                'slug' => bt_asset_library_slug($title),
                'title' => $title,
                'sequence' => $sequence,
                'season_total' => 55,
                'collection' => $collection['name'],
                'collection_slug' => $collectionSlug,
                'collection_description' => $collection['description'],
                'release_date' => $releaseDate,
                'display_date' => (new DateTimeImmutable($releaseDate))->format('l, F j, Y'),
                'description' => $description,
                'style' => trim((string)($metadata['style'] ?? $styleDefaults[$collectionSlug] ?? 'Tattoo linework')),
                'placement' => trim((string)($metadata['placement'] ?? 'Artist-selected placement')),
                'difficulty' => trim((string)($metadata['difficulty'] ?? 'Advanced')),
                'status' => $publicationStatus,
                'rights_confirmed' => (bool)($metadata['rights_confirmed'] ?? false),
                'reward_bits' => 25,
                'preview_url' => $preview['url'],
                'stencil_url' => $stencil['url'],
                'transfer_png_url' => $transfer['url'] ?? '',
                'transfer_pdf_url' => $pdf['url'] ?? '',
                'reference_image_url' => $reference['url'] ?? '',
                'placement_image_url' => $placementImage['url'] ?? '',
                'pack_image_url' => $pack['url'] ?? $preview['url'],
                'lore_card_url' => $lore['url'] ?? '',
                'style_card_url' => $styleCard['url'] ?? '',
                'updated_at' => gmdate('c', $updated),
                'files' => array_values(array_filter([$preview, $stencil, $transfer, $pdf, $reference, $placementImage, $pack, $lore, $styleCard])),
            ];
        }
    }
    usort($assets, static fn(array $left, array $right): int => $left['sequence'] <=> $right['sequence']);
    return $assets;
}

function bt_asset_library_daily(): array
{
    $assets = bt_asset_library();
    if (!$assets) throw new RuntimeException('The Beyond Tattoo 1.2 library has no complete asset-backed stencils.');
    $today = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    $released = array_values(array_filter($assets, static fn(array $asset): bool => $asset['release_date'] <= $today));
    $asset = $released ? end($released) : $assets[0];
    $packageFiles = [];
    foreach ($asset['files'] as $file) $packageFiles[$file['url']] = $asset['slug'] . '/' . $file['file'];
    return [
        'library_version' => '1.2',
        'slug' => $asset['id'],
        'title' => $asset['title'],
        'collection' => $asset['collection'] . ' Collection',
        'display_date' => $asset['display_date'],
        'iso_date' => $asset['release_date'],
        'description' => $asset['description'],
        'preview_url' => $asset['preview_url'],
        'reference_image_url' => $asset['reference_image_url'],
        'placement_image_url' => $asset['placement_image_url'],
        'pack_image_url' => $asset['pack_image_url'],
        'lore_card_url' => $asset['lore_card_url'],
        'style_card_url' => $asset['style_card_url'],
        'package_url' => 'api/stencil-download.php?type=package',
        'ig_post_url' => $asset['pack_image_url'],
        'editable_url' => '',
        'transfer_png_url' => $asset['transfer_png_url'] !== '' ? $asset['transfer_png_url'] : $asset['stencil_url'],
        'transfer_pdf_url' => $asset['transfer_pdf_url'],
        'placement_guide_url' => '',
        'placement' => $asset['placement'],
        'style' => $asset['style'],
        'sequence' => $asset['sequence'],
        'season_total' => 55,
        'public_url' => 'https://beyondimagination.co.technology/beyond-tattoo/stencil-of-day.php',
        'reward_bits' => $asset['reward_bits'],
        'updated_at' => $asset['updated_at'],
        'package_files' => $packageFiles,
    ];
}
