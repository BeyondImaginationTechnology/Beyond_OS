<?php
require_once __DIR__ . '/../includes/ecosystem.php';
beyond_nav_bootstrap('Beyond French');
$pageTitle = 'Beyond French 2.0.0 iOS Beta';
require __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= h($frenchBase) ?>assets/css/ios-beta.css?v=<?= h((string)(@filemtime(__DIR__ . '/assets/css/ios-beta.css') ?: time())) ?>">
<section class="ios-beta-hero">
    <div class="ios-beta-copy">
        <span class="ios-beta-pill">VERSION 2.0.0 · iOS BETA</span>
        <h1>French practice,<br><span>made for iPhone.</span></h1>
        <p>Take the daily phrase, pronunciation practice, four-language comparisons, and your Academy progress wherever you go.</p>
        <div class="ios-beta-actions">
            <a class="button primary" href="<?= h($frenchBase) ?>subscribe.php">Join the iOS beta</a>
            <a class="button secondary" href="<?= h($frenchBase) ?>">Try the web app</a>
        </div>
        <ul>
            <li>Daily French phrase and audio</li>
            <li>French, Kreyòl, Patois, and Spanish together</li>
            <li>Academy lessons and conversation challenges</li>
        </ul>
    </div>
    <figure class="ios-beta-shot">
        <img src="<?= h($frenchBase) ?>assets/app-store/beyond-french-ios-beta-promo.png" alt="Beyond French iOS beta app preview">
        <figcaption>Beta preview · Interface may change before release.</figcaption>
    </figure>
</section>
<section class="section ios-beta-details">
    <span class="eyebrow">BETA ACCESS</span>
    <h2>Help shape Beyond French for iOS.</h2>
    <p>Beta members can test the mobile experience and share feedback before the public App Store release.</p>
    <a class="button primary" href="<?= h($frenchBase) ?>subscribe.php">Request an invitation →</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
