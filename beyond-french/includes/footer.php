</main>
<nav class="mobile-tabbar" aria-label="Beyond French navigation">
    <?php
    $currentFrenchPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isFrenchHome = in_array($currentFrenchPage, ['index.php', ''], true);
    $isFrenchAcademy = in_array($currentFrenchPage, ['academy.php', 'archive.php', 'progress.php'], true);
    $isFrenchMore = in_array($currentFrenchPage, ['game.php', 'settings.php'], true);
    ?>
    <a class="<?= $isFrenchHome ? 'active' : '' ?>" href="<?= h($frenchBase) ?>"><span>⌂</span><small>Home</small></a>
    <a class="<?= $isFrenchAcademy ? 'active' : '' ?>" href="<?= h($frenchBase) ?>academy.php"><span>▤</span><small>Academy</small></a>
    <a href="<?= h($frenchBase) ?>translate.php"><span>文</span><small>Translate</small></a>
    <a class="<?= $currentFrenchPage === 'dictionary.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>dictionary.php"><span>▣</span><small>Dictionary</small></a>
    <a class="<?= $isFrenchMore ? 'active' : '' ?>" href="<?= h($frenchBase) ?>settings.php"><span>•••</span><small>More</small></a>
</nav>
<footer class="site-footer">
    <p>© <?= date('Y') ?> Beyond French · French first. 12 language bridges. Every day.</p>
    <nav class="app-legal-links" aria-label="Beyond French legal information"><a href="/legal/privacy.php?app=Beyond%20French">Privacy</a><a href="/legal/terms.php?app=Beyond%20French">Terms</a></nav>
</footer>
<script src="<?= h($frenchBase) ?>assets/js/app.js?v=<?= h((string)(@filemtime(__DIR__ . '/../assets/js/app.js') ?: time())) ?>"></script>
<script>if('serviceWorker'in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register(<?= json_encode($frenchBase . 'service-worker.js', JSON_UNESCAPED_SLASHES) ?>,{scope:<?= json_encode($frenchBase, JSON_UNESCAPED_SLASHES) ?>}).catch(()=>{}))}</script>
</body>
</html>
