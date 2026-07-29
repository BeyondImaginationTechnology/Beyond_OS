<?php
declare(strict_types=1);

function beyond_tv_catalog_entry_playable(array $entry): bool
{
    if (($entry['source_type'] ?? '') === 'watchlist') return false;
    return trim((string)($entry['video_url'] ?? '')) !== ''
        || trim((string)($entry['archive_id'] ?? '')) !== ''
        || trim((string)($entry['archive_episode_map'] ?? '')) !== '';
}

function beyond_tv_catalog_entry_matches_channel(array $entry, string $slug): bool
{
    if (!beyond_tv_catalog_entry_playable($entry)) return false;
    $assigned = trim((string)($entry['channel_slug'] ?? ''));
    if ($assigned !== '') return $assigned === $slug;

    $genre = strtolower((string)($entry['genre'] ?? ''));
    return match ($slug) {
        'beyond-comedy' => str_contains($genre, 'comedy'),
        'beyond-family' => (
            str_contains($genre, 'family')
            || str_contains($genre, 'fantasy')
            || str_contains($genre, 'animation')
        ) && !str_contains($genre, 'horror'),
        'beyond-after-dark' => str_contains($genre, 'horror')
            || str_contains($genre, 'thriller')
            || str_contains($genre, 'cult'),
        'beyond-mystery' => str_contains($genre, 'mystery')
            || str_contains($genre, 'crime')
            || str_contains($genre, 'thriller'),
        default => false,
    };
}

function beyond_tv_catalog_entries_for_channel(array $catalog, string $slug): array
{
    return array_values(array_filter(
        $catalog,
        static fn($entry): bool => is_array($entry) && beyond_tv_catalog_entry_matches_channel($entry, $slug)
    ));
}
