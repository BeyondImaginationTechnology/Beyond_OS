<?php
declare(strict_types=1);

/** Compact, app-aware legal navigation for public app homepages. */
function beyond_legal_links(string $appName): void {
    $app = rawurlencode($appName);
    $label = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
    echo '<nav class="app-legal-links" aria-label="'.$label.' legal information">'
        . '<a href="/legal/privacy.php?app='.$app.'">Privacy</a>'
        . '<a href="/legal/terms.php?app='.$app.'">Terms</a>'
        . '</nav>';
}
