<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';

$slug = trim((string)($_GET['studio'] ?? $_POST['studio'] ?? 'beyond-studio-nanaimo'));
$studio = bt_get_studio($slug) ?: bt_get_studio('beyond-studio-nanaimo');
$submitted = false;
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $idea = trim((string)($_POST['idea'] ?? ''));
    if ($name === '') $errors[] = 'Tell us your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Add a valid email so the studio can reply.';
    if ($idea === '') $errors[] = 'Share a little about the piece you have in mind.';
    if (!$errors) $submitted = true;
}

$pageTitle = 'Book a consultation — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<div class="app-shell booking-page"><header class="app-header"><div class="container app-header-inner"><a class="btn btn-secondary" href="<?= $studio ? 'studio-profile.php?slug=' . urlencode($studio['slug']) : 'studios.php' ?>">← Back to studio</a><span class="brand">Booking inquiry</span></div></header>
<main class="container dashboard booking-shell">
  <section class="booking-intro"><span class="eyebrow">Beyond Studio · Nanaimo, BC</span><h1>Make the idea<br><span class="gradient-text">real.</span></h1><p class="section-copy">Tell the studio what you’re dreaming up. This first message helps match your idea, placement, and timing with the right artist.</p><div class="booking-steps"><span><b>01</b> Your idea</span><span><b>02</b> Artist match</span><span><b>03</b> Consultation</span></div></section>
  <section class="booking-card panel">
    <?php if ($submitted): ?>
      <div class="booking-success"><span class="status">✓ Inquiry ready</span><h2>You’re on the list.</h2><p>Your inquiry for <strong><?= e($studio['name'] ?? 'Beyond Studio') ?></strong> is ready to be reviewed. The studio will follow up at <strong><?= e($email) ?></strong>.</p><a class="btn btn-primary" href="studio-profile.php?slug=<?= urlencode($studio['slug']) ?>">Return to studio</a></div>
    <?php else: ?>
      <?php if ($errors): ?><div class="notice error-notice"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
      <div class="booking-card-heading"><span class="eyebrow">Start here</span><h2>Book with <?= e($studio['name'] ?? 'Beyond Studio') ?></h2><p class="meta">A thoughtful first step. No payment required.</p></div>
      <form method="post" class="form-grid two booking-form"><input type="hidden" name="studio" value="<?= e($studio['slug'] ?? 'beyond-studio-nanaimo') ?>"><label><span>Name</span><input class="input" name="name" value="<?= e((string)($_POST['name'] ?? '')) ?>" placeholder="Your name" required></label><label><span>Email</span><input class="input" type="email" name="email" value="<?= e((string)($_POST['email'] ?? '')) ?>" placeholder="you@example.com" required></label><label><span>Preferred style</span><select class="input" name="style"><option>Open to ideas</option><option>Fine line</option><option>Black &amp; grey</option><option>Colour</option><option>Custom / illustrative</option></select></label><label><span>Placement</span><input class="input" name="placement" placeholder="e.g. outer forearm"></label><label class="booking-wide"><span>Tell us about the piece</span><textarea class="input" name="idea" rows="6" placeholder="Subject, size, references, or anything you already know..." required><?= e((string)($_POST['idea'] ?? '')) ?></textarea></label><button class="btn btn-primary booking-wide" type="submit">Send booking inquiry <span>→</span></button></form>
    <?php endif; ?>
  </section>
</main></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
