<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
$query = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
$studios = array_map(static function(array $studio): array {
    return [
        'slug' => $studio['slug'],
        'name' => $studio['name'],
        'city' => $studio['city'],
        'province' => $studio['province'],
        'address' => trim($studio['address_line1'] . ', ' . $studio['city'] . ', ' . $studio['province'] . ' ' . $studio['postal_code']),
        'phone' => $studio['phone'],
        'description' => $studio['description'],
        'services' => array_values(array_filter(array_map('trim', explode(',', (string)$studio['services'])))),
        'walk_ins' => (bool)$studio['walk_ins'],
        'is_verified' => ($studio['verification_status'] ?? '') === 'verified',
        'latitude' => isset($studio['latitude']) ? (float)$studio['latitude'] : null,
        'longitude' => isset($studio['longitude']) ? (float)$studio['longitude'] : null,
        'artist_count' => (int)$studio['artist_count'],
        'profile_url' => beyond_url('beyond-tattoo/studio-profile.php?slug=' . rawurlencode($studio['slug'])),
        'website_url' => $studio['website_url'] ?? $studio['instagram_url'],
        'booking_url' => $studio['booking_url'] ?? $studio['instagram_url'],
    ];
}, bt_list_studios($query));
echo json_encode(['version'=>'1.2','provider'=>'beyond-tattoo-directory','query'=>$query,'count'=>count($studios),'studios'=>$studios], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
