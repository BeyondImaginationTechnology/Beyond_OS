<?php
$adminWelcomeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
$adminWelcomeName = trim((string)($_SESSION['last_name'] ?? ''));
if ($adminWelcomeName === '') {
    $adminWelcomeName = trim((string)($_SESSION['name'] ?? '')) ?: 'Administrator';
}
?>
Welcome back, <?php if ($adminWelcomeEmail === 'rosiergreg@gmail.com'): ?>Mr. <span role="img" aria-label="Rosier" title="Rosier">🌹</span><?php else: ?><?= e($adminWelcomeName) ?><?php endif; ?>
