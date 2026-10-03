<?php
declare(strict_types=1);

function beyond_space_local_rotation(array $block, DateTimeImmutable $now): ?array
{
    $manifestPath = dirname(__DIR__) . '/assets/media/space-tv/library.json';
    $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
    if (!is_array($manifest) || !is_array($manifest['items'] ?? null)) {
        return null;
    }

    $key = (string) ($block['key'] ?? '');
    $items = [];
    foreach ($manifest['items'] as $item) {
        if (!is_array($item) || ($item['status'] ?? '') !== 'ready') {
            continue;
        }
        $assetUrl = (string) ($item['asset_url'] ?? '');
        $duration = (int) ($item['duration_seconds'] ?? 0);
        $blocks = is_array($item['blocks'] ?? null) ? $item['blocks'] : [];
        if (
            !str_starts_with($assetUrl, '/beyond-tv/assets/media/space-tv/')
            || str_contains($assetUrl, '..')
            || $duration < 1
            || ($key !== '' && !in_array($key, $blocks, true) && !in_array('all', $blocks, true))
        ) {
            continue;
        }
        $items[] = [
            'id' => (string) ($item['id'] ?? ''),
            'title' => (string) ($item['title'] ?? 'NASA visualization'),
            'program' => (string) ($item['program'] ?? 'Space TV Archive'),
            'url' => $assetUrl,
            'duration' => $duration,
            'credit' => (string) ($item['credit'] ?? 'NASA'),
            'source_url' => (string) ($item['source_url'] ?? ''),
            'audio_policy' => (string) ($item['audio_policy'] ?? 'remove_and_replace'),
            'type' => 'video/mp4',
        ];
    }
    if (!$items) {
        return null;
    }

    $blockStart = $now->setTime((int) $block['start'], 0, 0);
    $elapsed = max(0, $now->getTimestamp() - $blockStart->getTimestamp());
    $cycle = array_sum(array_column($items, 'duration'));
    $position = $cycle > 0 ? $elapsed % $cycle : 0;
    $index = 0;
    foreach ($items as $candidateIndex => $item) {
        if ($position < $item['duration']) {
            $index = $candidateIndex;
            break;
        }
        $position -= $item['duration'];
    }
    $ordered = array_merge(array_slice($items, $index), array_slice($items, 0, $index));
    $current = $ordered[0];

    return [
        'sources' => $ordered,
        'current' => $current,
        'start_offset' => $position,
        'source_key' => hash('sha256', $current['id'] . '|' . $blockStart->format(DATE_ATOM)),
    ];
}
