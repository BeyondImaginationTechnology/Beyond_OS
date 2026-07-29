<?php
$pageTitle = 'Welcome to the Beta | Beyond French';
require __DIR__ . '/includes/header.php';
$status = $_GET['status'] ?? 'new';
$isIos = ($_GET['product'] ?? '') === 'ios';
?>
<section class="section page-top center">
    <span class="big-icon"><?= $isIos ? '📱' : '🎓' ?></span>
    <span class="eyebrow"><?= $isIos ? 'iOS BETA ACCESS' : 'BETA ACCESS' ?></span>
    <h1><?= $status === 'existing' ? 'You’re already on the list.' : ($isIos ? 'Your iOS beta request is in.' : 'Welcome to Beyond French.') ?></h1>
    <p><?= $status === 'existing' ? 'We already have that email saved.' : ($isIos ? 'We saved your email and notified the Beyond French team. Watch your inbox for TestFlight news.' : 'Your beta signup has been saved successfully.') ?></p>
    <a class="button primary" href="index.php">Return home</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
