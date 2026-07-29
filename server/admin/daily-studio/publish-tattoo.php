<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/beyond-tattoo/includes/stencil-content.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /server/admin/daily-studio/stencil-library.php#publish', true, 302);
    exit;
}
header('Content-Type: application/json; charset=UTF-8');

function optionalPublishedPng(array $input, string $key, string $label): ?string
{
    $data = trim((string)($input[$key] ?? ''));
    if ($data === '') return null;
    if (!preg_match('#^data:image/png;base64,(.+)$#s', $data, $match)) {
        throw new RuntimeException("Generated {$label} PNG is invalid.");
    }
    $png = base64_decode($match[1], true);
    $info = is_string($png) ? @getimagesizefromstring($png) : false;
    if (!is_string($png) || strlen($png) > 15 * 1024 * 1024 || $info === false || ($info['mime'] ?? '') !== 'image/png') {
        throw new RuntimeException("Generated {$label} PNG is invalid or too large.");
    }
    return $png;
}

try {
    if (!Auth::check()) { http_response_code(403); throw new RuntimeException('Administrator access required.'); }
    $csrf = is_string($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
    if (!Auth::verifyCsrf($csrf)) { http_response_code(403); throw new RuntimeException('Invalid security token.'); }
    $input = json_decode((string)file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $title = trim((string)($input['title'] ?? 'Untitled Stencil'));
    $svg = (string)($input['svg'] ?? '');
    $pngData = (string)($input['png'] ?? '');
    if ($svg === '' || !str_contains($svg, '<svg')) throw new RuntimeException('Generated SVG is missing.');
    if (strlen($svg) > 2 * 1024 * 1024 || preg_match('/<(?:script|foreignObject)\b|\son[a-z]+\s*=|(?:href|src)\s*=\s*["\']\s*(?:javascript:|https?:|\/\/)/i', $svg)) throw new RuntimeException('Generated SVG contains unsupported or unsafe content.');
    if (!preg_match('#^data:image/png;base64,(.+)$#s', $pngData, $m)) throw new RuntimeException('Generated PNG is missing.');
    $png = base64_decode($m[1], true); if ($png === false) throw new RuntimeException('PNG could not be decoded.');
    $pngInfo = @getimagesizefromstring($png);
    if (strlen($png) > 15 * 1024 * 1024 || $pngInfo === false || ($pngInfo['mime'] ?? '') !== 'image/png') throw new RuntimeException('Generated PNG is invalid or too large.');
    $referencePng = optionalPublishedPng($input, 'reference_png', 'reference artwork');
    $placementPng = optionalPublishedPng($input, 'placement_png', 'placement mockup');
    $packPng = optionalPublishedPng($input, 'pack_png', 'pack');
    $loreCardPng = optionalPublishedPng($input, 'lore_card_png', 'lore card');
    $styleCardPng = optionalPublishedPng($input, 'style_card_png', 'style card');
    $releaseDate = trim((string)($input['release_date'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) throw new RuntimeException('Scheduled release date is invalid.');
    $release = new DateTimeImmutable($releaseDate);
    $sequence = max(1, min(55, (int)($input['sequence'] ?? 1)));
    $lore = mb_substr(trim((string)($input['lore'] ?? '')), 0, 1200);
    $dir = dirname(__DIR__, 3) . '/beyond-tattoo/uploads/stencil-day';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Could not create stencil upload folder.');
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'generated-stencil';
    $svgFile = $dir . '/generated-stencil-of-the-day.svg';
    $pngFile = $dir . '/generated-stencil-of-the-day.png';
    $igFile = $dir . '/generated-instagram-post.png';
    $referenceFile = $dir . '/generated-reference-artwork.png';
    $placementFile = $dir . '/generated-placement-mockup.png';
    $packFile = $dir . '/generated-pack-image.png';
    $loreCardFile = $dir . '/generated-lore-card.png';
    $styleCardFile = $dir . '/generated-style-card.png';
    $metaFile = $dir . '/generated-stencil-metadata.json';
    $readmeFile = $dir . '/STUDIO-TRANSFER-NOTES.txt';
    file_put_contents($svgFile, $svg, LOCK_EX);
    file_put_contents($pngFile, $png, LOCK_EX);
    file_put_contents($igFile, $packPng ?? $png, LOCK_EX);
    if ($referencePng !== null) file_put_contents($referenceFile, $referencePng, LOCK_EX);
    if ($placementPng !== null) file_put_contents($placementFile, $placementPng, LOCK_EX);
    if ($packPng !== null) file_put_contents($packFile, $packPng, LOCK_EX);
    if ($loreCardPng !== null) file_put_contents($loreCardFile, $loreCardPng, LOCK_EX);
    if ($styleCardPng !== null) file_put_contents($styleCardFile, $styleCardPng, LOCK_EX);
    $meta = ['title'=>$title,'slug'=>$slug,'motif'=>(string)($input['motif']??''),'style'=>(string)($input['style']??''),'collection'=>(string)($input['collection']??'Beyond Ancient Collection'),'placement'=>(string)($input['placement']??'Artist choice'),'release_date'=>$releaseDate,'sequence'=>$sequence,'season_total'=>55,'lore'=>$lore,'seed'=>(string)($input['seed']??''),'assets'=>['reference_artwork'=>$referencePng !== null,'printer_ready_stencil'=>true,'placement_mockup'=>$placementPng !== null,'premium_packaging'=>$packPng !== null,'lore_card'=>$loreCardPng !== null,'style_card'=>$styleCardPng !== null],'published_at'=>gmdate('c')];
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
    $referenceLine = $referencePng !== null ? "- generated-reference-artwork.png: high-detail visual reference\n" : '';
    $placementLine = $placementPng !== null ? "- generated-placement-mockup.png: anatomy and scale presentation\n" : '';
    $packLine = $packPng !== null ? "- generated-pack-image.png: premium storefront package artwork\n" : '';
    $loreLine = $loreCardPng !== null ? "- generated-lore-card.png: information and collection lore card\n" : '';
    $styleLine = $styleCardPng !== null ? "- generated-style-card.png: scheduled design and style card\n" : '';
    file_put_contents($readmeFile, "BEYOND TATTOO — STUDIO TRANSFER FILE\n\nDesign: {$title}\nRelease: {$meta['release_date']} · {$meta['sequence']} / 55\nCollection: {$meta['collection']}\nStyle: {$meta['style']}\nSuggested placement: {$meta['placement']}\nLore: {$meta['lore']}\n\nFiles\n{$referenceLine}- generated-stencil-of-the-day.svg: editable vector master\n- generated-stencil-of-the-day.png: high-resolution transfer image\n{$placementLine}{$packLine}{$loreLine}{$styleLine}- generated-instagram-post.png: scheduled social asset\n- generated-stencil-metadata.json: design settings and lore\n\nBefore tattooing, the artist must verify scale, line spacing, transfer orientation, anatomy and skin suitability.\n", LOCK_EX);
    $zipPath = $dir . '/generated-stencil-of-the-day.zip';
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP ZipArchive is required to rebuild the package.');
    $zip = new ZipArchive(); if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Could not create package ZIP.');
    $packageFiles = [$svgFile,$pngFile,$igFile,$metaFile,$readmeFile];
    if ($referencePng !== null) $packageFiles[] = $referenceFile;
    if ($placementPng !== null) $packageFiles[] = $placementFile;
    if ($packPng !== null) $packageFiles[] = $packFile;
    if ($loreCardPng !== null) $packageFiles[] = $loreCardFile;
    if ($styleCardPng !== null) $packageFiles[] = $styleCardFile;
    foreach ($packageFiles as $f) $zip->addFile($f, basename($f)); $zip->close();
    $packImageUrl = $packPng !== null ? 'uploads/stencil-day/generated-pack-image.png' : 'uploads/stencil-day/generated-stencil-of-the-day.png';
    bt_stencil_save(['title'=>$title,'collection'=>$meta['collection'],'display_date'=>$release->format('l, F j, Y'),'iso_date'=>$releaseDate,'description'=>$meta['style'].' · '.$meta['motif'].' · Generated in Beyond Studio','placement'=>$meta['placement'],'lore'=>$lore,'sequence'=>$sequence,'season_total'=>55,'preview_url'=>'uploads/stencil-day/generated-stencil-of-the-day.png','reference_image_url'=>$referencePng !== null ? 'uploads/stencil-day/generated-reference-artwork.png' : '','placement_image_url'=>$placementPng !== null ? 'uploads/stencil-day/generated-placement-mockup.png' : '','pack_image_url'=>$packImageUrl,'lore_card_url'=>$loreCardPng !== null ? 'uploads/stencil-day/generated-lore-card.png' : '','style_card_url'=>$styleCardPng !== null ? 'uploads/stencil-day/generated-style-card.png' : '','package_url'=>'uploads/stencil-day/generated-stencil-of-the-day.zip','ig_post_url'=>'uploads/stencil-day/generated-instagram-post.png','editable_url'=>'uploads/stencil-day/generated-stencil-of-the-day.svg','transfer_png_url'=>'uploads/stencil-day/generated-stencil-of-the-day.png']);
    echo json_encode(['ok'=>true,'package_url'=>'/beyond-tattoo/uploads/stencil-day/generated-stencil-of-the-day.zip','pack_image_url'=>'/beyond-tattoo/'.$packImageUrl,'asset_count'=>count(array_filter($meta['assets']))]);
} catch (Throwable $e) { if (http_response_code() < 400) http_response_code(400); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
