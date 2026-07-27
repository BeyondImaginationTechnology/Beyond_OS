<?php
declare(strict_types=1);
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/stencil-content.php';
require_once __DIR__ . '/includes/library-catalog.php';
$disableBeyondShell = true;
$stencilDay = bt_stencil_content();
$downloadFile = $stencilDay['package_url'];
$pageTitle = 'About — Beyond Tattoo';
require __DIR__ . '/includes/header.php';
?>
<main class="bt-storefront bt-library-page" id="top">
  <div class="bt-announcement"><div class="bt-wrap bt-announcement-inner"><span>✦ Free stencils every day</span><span>◆ Premium quality</span><span>Artist focused</span><a href="<?= e($downloadFile) ?>" download>Free stencil packs →</a></div></div>
  <header class="bt-site-header"><div class="bt-wrap bt-site-header-inner">
    <a class="bt-brand" href="index.php"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><span><strong>BEYOND</strong><b>TATTOO</b></span></a>
    <nav class="bt-desktop-nav"><a href="index.php">Home</a><a href="stencils.php" >Stencils</a><a href="collections.php" >Collections</a><a href="studios.php">Studios</a><a href="about.php" class="is-active">About</a></nav>
    <div class="bt-header-actions"><a class="bt-header-download" href="<?= e($downloadFile) ?>" download>↓ Free pack</a><a class="bt-login-link" href="login.php">Studio login</a><details class="bt-mobile-menu"><summary>☰</summary><div><a href="stencils.php">Stencils</a><a href="collections.php">Collections</a><a href="studios.php">Studios</a><a href="about.php">About</a><a href="login.php">Studio login</a></div></details></div>
  </div></header>

<section class="bt-page-hero bt-about-hero"><div class="bt-wrap"><p class="bt-gold-kicker">✦ BUILT FOR WORKING ARTISTS</p><h1>ART FIRST.<br><strong>TRANSFER READY.</strong></h1><p>Beyond Tattoo is a curated daily stencil library focused on professional presentation, practical transfer files and ambitious hand-designed concepts.</p></div></section>
<section class="bt-page-section"><div class="bt-wrap bt-about-grid"><article><span>01</span><h2>Curated—not generated</h2><p>The public library is built from individually designed artwork. Every release is reviewed before it is scheduled, packaged and published.</p></article><article><span>02</span><h2>Designed for the studio</h2><p>Each completed pack includes clean linework, transfer-ready spacing, printable files and practical size and placement guidance.</p></article><article><span>03</span><h2>A new drop every day</h2><p>Season One contains 55 scheduled releases across Divine Realism, Beyond Ancient, Japanese Legends and Dark Realism.</p></article></div></section>
<section class="bt-page-section"><div class="bt-wrap bt-process-panel"><div><p class="bt-purple-kicker">THE RELEASE PROCESS</p><h2>From artwork to daily drop</h2><p>Artwork is finished first, then prepared as a complete professional package before it appears on the storefront.</p></div><ol><li><b>Design</b><span>Create and refine the original tattoo composition.</span></li><li><b>Prepare</b><span>Build clean linework, mirrored transfer and print files.</span></li><li><b>Package</b><span>Add metadata, placement notes, preview and video assets.</span></li><li><b>Publish</b><span>Feature the release on its scheduled date and update downloads.</span></li></ol></div></section>
<section class="bt-page-section"><div class="bt-wrap bt-license-panel"><div><p class="bt-gold-kicker">PERSONAL & PROFESSIONAL USE</p><h2>Use the stencil. Make it your own.</h2><p>Artists may use downloaded stencils as a starting point for tattoo work and adapt them to fit the client, anatomy and placement. Redistribution or resale of the original digital pack is not permitted.</p></div><a class="bt-outline-button" href="../legal/terms.php">Read full terms →</a></div></section>

  <footer class="bt-store-footer"><div class="bt-wrap bt-store-footer-grid"><div class="bt-footer-brand"><span class="bt-brand-mark"><svg viewBox="0 0 64 64"><ellipse cx="32" cy="32" rx="25" ry="10"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(60 32 32)"/><ellipse cx="32" cy="32" rx="25" ry="10" transform="rotate(120 32 32)"/><circle cx="32" cy="32" r="4"/></svg></span><div><strong>Beyond Tattoo</strong><small>Beyond imagination. Beyond limits.</small></div></div><div class="bt-footer-links"><a href="../">Beyond OS</a><a href="login.php">Studio login</a><a href="../legal/terms.php">Terms</a><a href="../legal/privacy.php">Privacy</a></div></div></footer>
  <a class="bt-mobile-sticky-download" href="<?= e($downloadFile) ?>" download>↓ Download today’s free stencil</a>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
