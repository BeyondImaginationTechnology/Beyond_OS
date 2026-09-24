</main>
<nav class="mobile-tabbar" aria-label="Beyond French navigation">
    <?php
    $currentFrenchPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isFrenchHome = in_array($currentFrenchPage, ['index.php', ''], true);
    $isFrenchAcademy = in_array($currentFrenchPage, ['academy.php', 'archive.php', 'progress.php'], true);
    $isFrenchTranslate = in_array($currentFrenchPage, ['translate.php', 'dictionary.php', 'challenge.php'], true);
    $isFrenchTrivia = $currentFrenchPage === 'game.php';
    $isFrenchSettings = $currentFrenchPage === 'settings.php';
    ?>
    <a class="<?= $isFrenchHome ? 'active' : '' ?>" href="<?= h($frenchBase) ?>"><span>⌂</span><small>Home</small></a>
    <a class="<?= $isFrenchAcademy ? 'active' : '' ?>" href="<?= h($frenchBase) ?>academy.php"><span>▤</span><small>Academy</small></a>
    <a class="tab-primary <?= $isFrenchTranslate ? 'active' : '' ?>" href="<?= h($frenchBase) ?>translate.php"><span>文</span><small>Translate</small></a>
    <a class="<?= $isFrenchTrivia ? 'active' : '' ?>" href="<?= h($frenchBase) ?>game.php"><span>✧</span><small>Trivia</small></a>
    <a class="<?= $isFrenchSettings ? 'active' : '' ?>" href="<?= h($frenchBase) ?>settings.php"><span>○</span><small>Settings</small></a>
</nav>
<footer class="site-footer">
    <p>© <?= date('Y') ?> Beyond French · French first. 12 language bridges. Every day.</p>
</footer>
<script src="<?= h($frenchBase) ?>assets/js/app.js?v=<?= h((string)(@filemtime(__DIR__ . '/../assets/js/app.js') ?: time())) ?>"></script>
<script>if('serviceWorker'in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register(<?= json_encode($frenchBase . 'service-worker.js', JSON_UNESCAPED_SLASHES) ?>,{scope:<?= json_encode($frenchBase, JSON_UNESCAPED_SLASHES) ?>}).catch(()=>{}))}</script>
</body>
</html>
