<?php
require_once __DIR__ . '/../includes/ecosystem.php';
$pageTitle = 'French Quest | The World Tour';
require __DIR__ . '/includes/header.php';
?>
<div class="quest-game" id="quest-game" data-logo="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp">
    <section class="quest-shell" aria-labelledby="quest-title">
        <header class="quest-header">
            <a class="quest-brand" href="<?= h($frenchBase) ?>"><img src="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp" alt=""><span><strong>FRENCH QUEST</strong><small>THE WORLD TOUR</small></span></a>
            <div class="quest-header-actions"><button class="quest-icon-button" id="quest-music" type="button" aria-pressed="false" title="Toggle music">♫</button><a class="quest-icon-button" href="<?= h($frenchBase) ?>" aria-label="Return to French home">⌂</a></div>
        </header>
        <div id="quest-content"></div>
    </section>
</div>
<script src="<?= h($frenchBase) ?>assets/js/quest.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
