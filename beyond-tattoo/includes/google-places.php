<?php
declare(strict_types=1);

/**
 * Fetches live Google Places photos for a confirmed physical studio.
 * Photo URLs are intentionally not cached; only the Place ID belongs in our database.
 */
function bt_google_studio_photos(array $studio, int $limit = 4): array
{
    if (($studio['slug'] ?? '') === 'beyond-studio-nanaimo') return [];
    $placeId = trim((string)($studio['google_place_id'] ?? ''));
    $apiKey = defined('GOOGLE_MAPS_API_KEY') ? trim((string)GOOGLE_MAPS_API_KEY) : '';
    if ($placeId === '' || $apiKey === '') return [];

    $detailsUrl = 'https://places.googleapis.com/v1/places/' . rawurlencode($placeId);
    $details = bt_google_places_json($detailsUrl, $apiKey, 'displayName,photos,googleMapsUri');
    if (!is_array($details)) return [];

    $photos = [];
    foreach (array_slice(is_array($details['photos'] ?? null) ? $details['photos'] : [], 0, max(1, min($limit, 6))) as $photo) {
        $name = trim((string)($photo['name'] ?? ''));
        if ($name === '') continue;
        $mediaUrl = 'https://places.googleapis.com/v1/' . ltrim($name, '/') . '/media?maxWidthPx=1400&skipHttpRedirect=true';
        $media = bt_google_places_json($mediaUrl, $apiKey);
        $photoUri = trim((string)($media['photoUri'] ?? ''));
        if ($photoUri === '') continue;
        $authors = [];
        foreach (($photo['authorAttributions'] ?? []) as $author) {
            if (!is_array($author)) continue;
            $authors[] = ['name' => trim((string)($author['displayName'] ?? '')), 'url' => trim((string)($author['uri'] ?? ''))];
        }
        $photos[] = ['url' => $photoUri, 'authors' => $authors];
    }
    return ['photos' => $photos, 'maps_url' => trim((string)($details['googleMapsUri'] ?? '')), 'source' => 'Google Maps'];
}

function bt_google_places_json(string $url, string $apiKey, string $fieldMask = ''): ?array
{
    $headers = ['X-Goog-Api-Key: ' . $apiKey, 'Accept: application/json'];
    if ($fieldMask !== '') $headers[] = 'X-Goog-FieldMask: ' . $fieldMask;
    $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true, 'header' => implode("\r\n", $headers)]]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false || trim($raw) === '') return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}
