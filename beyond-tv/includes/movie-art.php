<?php
declare(strict_types=1);

function beyond_tv_rpdb_api_key(): string
{
    $environmentKey = getenv('RPDB_API_KEY');
    if (is_string($environmentKey) && trim($environmentKey) !== '') {
        $key = trim($environmentKey);
        return strcasecmp($key, 't0-free-rpdb') === 0 ? '' : $key;
    }
    return '';
}

function beyond_tv_rpdb_known_ids(): array
{
    return [
        '21-jump-street-2012' => ['imdb', 'tt1232829'],
        'the-princess-bride' => ['imdb', 'tt0093779'],
        'matilda-1996' => ['imdb', 'tt0117008'],
        'the-hateful-eight-extended' => ['imdb', 'tt3460252'],
        'aladdin-1993-vhs' => ['imdb', 'tt0103639'],
        'sister-act-2' => ['imdb', 'tt0108147'],
        'bring-it-on' => ['imdb', 'tt0204946'],
        'little-fockers' => ['imdb', 'tt0970866'],
        'meet-the-parents' => ['imdb', 'tt0212338'],
        'tropic-thunder' => ['imdb', 'tt0942385'],
        'zoolander' => ['imdb', 'tt0196229'],
        'a-goofy-movie' => ['imdb', 'tt0113198'],
        'bee-movie' => ['imdb', 'tt0389790'],
        'jackass-the-movie' => ['imdb', 'tt0322802'],
        'the-spongebob-squarepants-movie' => ['imdb', 'tt0345950'],
        'up' => ['imdb', 'tt1049413'],
        'wall-e' => ['imdb', 'tt0910970'],
        'transformers' => ['imdb', 'tt0418279'],
        'taking-woodstock' => ['imdb', 'tt1127896'],
    ];
}

function beyond_tv_rpdb_media_id(array $item): ?array
{
    $mediaType = preg_replace('/[^a-z]/', '', strtolower((string)($item['rpdb_media_type'] ?? 'imdb')));
    $mediaId = trim((string)($item['rpdb_id'] ?? $item['imdb_id'] ?? ''));
    if ($mediaId !== '') {
        return [$mediaType ?: 'imdb', $mediaId];
    }

    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($item['slug'] ?? '')));
    $knownIds = beyond_tv_rpdb_known_ids();
    return $knownIds[$slug] ?? null;
}

function beyond_tv_rpdb_poster_url(array $item): ?string
{
    $apiKey = beyond_tv_rpdb_api_key();
    $media = beyond_tv_rpdb_media_id($item);
    if ($apiKey === '' || !$media) {
        return null;
    }

    [$mediaType, $mediaId] = $media;
    if (!in_array($mediaType, ['imdb', 'tvdb'], true)) {
        return null;
    }
    if (!preg_match('/^(?:tt\d+|(?:movie|series)-\d+)$/', $mediaId)) {
        return null;
    }

    return 'https://api.ratingposterdb.com/'
        . rawurlencode($apiKey) . '/'
        . rawurlencode($mediaType) . '/poster-default/'
        . rawurlencode($mediaId) . '.jpg?fallback=true&theme=bar';
}

function beyond_tv_poster_url(array $item): ?string
{
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($item['slug'] ?? '')));
    if ($slug === '') {
        return null;
    }

    $rpdbPoster = beyond_tv_rpdb_poster_url($item);
    if ($rpdbPoster !== null) {
        return $rpdbPoster;
    }

    foreach (['poster_url', 'thumbnail'] as $field) {
        $explicitPoster = trim((string)($item[$field] ?? ''));
        if ($explicitPoster !== '') {
            return $explicitPoster;
        }
    }

    return null;
}
