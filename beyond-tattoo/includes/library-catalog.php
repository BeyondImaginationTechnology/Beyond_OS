<?php
declare(strict_types=1);

function bt_library_collections(): array
{
    return [
        'divine-realism' => [
            'name' => 'Divine Realism',
            'count' => 10,
            'dates' => 'Jul 17–26, 2026',
            'image' => 'assets/img/storefront/collection-divine.webp',
            'description' => 'Biblical portraiture, angels and sacred iconography composed for black-and-grey realism.',
            'stencils' => [
                ['Biblical Realism', '2026-07-17'], ['Archangel Michael', '2026-07-18'], ['Sacred Heart', '2026-07-19'],
                ['Praying Hands & Rosary', '2026-07-20'], ['Guardian Angel', '2026-07-21'], ['Dove & Radiant Cross', '2026-07-22'],
                ['Cherub & Clouds', '2026-07-23'], ['Gates of Heaven', '2026-07-24'], ['Crown & Cross', '2026-07-25'], ['Angel of Light', '2026-07-26'],
            ],
        ],
        'beyond-ancient' => [
            'name' => 'Beyond Ancient', 'count' => 12, 'dates' => 'Jul 27–Aug 7, 2026',
            'image' => 'assets/img/storefront/collection-ancient.webp',
            'description' => 'Egyptian gods, royal portraits and sacred symbols framed with ornamental hieroglyphic detail.',
            'stencils' => [
                ['Ancient Sentinel', '2026-07-27'], ['Solar Eye Motif', '2026-07-28'], ['Pharaoh Portrait', '2026-07-29'], ['Sacred Scarab', '2026-07-30'],
                ['Sekhmet', '2026-07-31'], ['Isis', '2026-08-01'], ['Pyramid Gateway', '2026-08-02'], ['Osiris', '2026-08-03'],
                ['Bastet', '2026-08-04'], ['Egyptian Sacred Symbols', '2026-08-05'], ['Hieroglyphic Guardian', '2026-08-06'], ['Ornamental Egyptian Frame', '2026-08-07'],
            ],
        ],
        'japanese-legends' => [
            'name' => 'Japanese Legends', 'count' => 15, 'dates' => 'Aug 8–22, 2026',
            'image' => 'assets/img/storefront/collection-japanese.webp',
            'description' => 'Masks, warriors, animals and mythological figures shaped for flowing Japanese compositions.',
            'stencils' => [
                ['Hannya Mask', '2026-08-08'], ['Oni Warrior', '2026-08-09'], ['Japanese Dragon', '2026-08-10'], ['Koi & Lotus', '2026-08-11'],
                ['Samurai Portrait', '2026-08-12'], ['Geisha & Fan', '2026-08-13'], ['Japanese Tiger', '2026-08-14'], ['Snake & Chrysanthemum', '2026-08-15'],
                ['Peony Arrangement', '2026-08-16'], ['Great Wave', '2026-08-17'], ['Temple Guardian', '2026-08-18'], ['Kitsune Mask', '2026-08-19'],
                ['Phoenix', '2026-08-20'], ['Raijin', '2026-08-21'], ['Nio Guardians', '2026-08-22'],
            ],
        ],
        'dark-realism' => [
            'name' => 'Dark Realism', 'count' => 18, 'dates' => 'Aug 23–Sep 9, 2026',
            'image' => 'assets/img/storefront/collection-dark.webp',
            'description' => 'Gothic portraiture, skulls, ravens and dramatic high-contrast compositions.',
            'stencils' => [
                ['Gothic Skull', '2026-08-23'], ['Raven & Moon', '2026-08-24'], ['Hooded Reaper', '2026-08-25'], ['Broken Angel Statue', '2026-08-26'],
                ['Demon Portrait', '2026-08-27'], ['Gothic Cathedral', '2026-08-28'], ['Skull in Smoke', '2026-08-29'], ['Chained Soul', '2026-08-30'],
                ['Broken Clock', '2026-08-31'], ['Plague Doctor', '2026-09-01'], ['Weeping Stone Face', '2026-09-02'], ['Grim Knight', '2026-09-03'],
                ['Raven Skull', '2026-09-04'], ['Haunted Gate', '2026-09-05'], ['Death & Hourglass', '2026-09-06'], ['Dark Seraph', '2026-09-07'],
                ['Possessed Statue', '2026-09-08'], ['Final Judgment', '2026-09-09'],
            ],
        ],
    ];
}

function bt_pretty_date(string $iso): string
{
    try { return (new DateTimeImmutable($iso))->format('M j, Y'); }
    catch (Throwable $e) { return $iso; }
}
