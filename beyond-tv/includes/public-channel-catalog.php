<?php
declare(strict_types=1);

/**
 * Build the curated guest-facing Beyond TV catalogue.
 *
 * Only channels deliberately included in featured-channels.json appear in the
 * public demo. The larger catalogue also contains unfinished and retired ideas.
 *
 * @param array<int, array<string, mixed>> $catalogue
 * @param array<int, array<string, mixed>> $featured
 * @return array<int, array<string, mixed>>
 */
function beyond_tv_public_channels(array $catalogue, array $featured): array
{
    $bySlug = [];
    foreach ($catalogue as $channel) {
        $slug = (string)($channel['slug'] ?? '');
        if ($slug !== '' && ($channel['access'] ?? 'guest') === 'guest') {
            $bySlug[$slug] = $channel;
        }
    }

    $publicChannels = [];
    $seen = [];

    foreach ($featured as $featuredChannel) {
        $slug = (string)($featuredChannel['slug'] ?? '');
        if ($slug === '' || !isset($bySlug[$slug]) || isset($seen[$slug])) {
            continue;
        }

        $publicChannels[] = array_merge($bySlug[$slug], $featuredChannel);
        $seen[$slug] = true;
    }

    foreach ($publicChannels as $index => &$channel) {
        $channel['display_number'] = $index + 1;
    }
    unset($channel);

    return $publicChannels;
}
