</main>
<nav class="mobile-tabbar" aria-label="Beyond French navigation">
    <?php $currentFrenchPage = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
    <a class="<?= $currentFrenchPage === 'index.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>"><span>⌂</span><small>Home</small></a>
    <a class="<?= $currentFrenchPage === 'academy.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>academy.php"><span>▤</span><small>Academy</small></a>
    <a class="tab-primary <?= $currentFrenchPage === 'translate.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>translate.php"><span>文</span><small>Translate</small></a>
    <a class="<?= $currentFrenchPage === 'game.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>game.php"><span>✧</span><small>Trivia</small></a>
    <a class="<?= $currentFrenchPage === 'settings.php' ? 'active' : '' ?>" href="<?= h($frenchBase) ?>settings.php"><span>○</span><small>Settings</small></a>
</nav>
<footer class="site-footer">
    <p>© <?= date('Y') ?> Beyond French · French first. Five languages. Every day.</p>
</footer>
<script src="<?= h($frenchBase) ?>assets/js/app.js?v=<?= h((string)(@filemtime(__DIR__ . '/../assets/js/app.js') ?: time())) ?>"></script>
<script>if('serviceWorker'in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register(<?= json_encode($frenchBase . 'service-worker.js', JSON_UNESCAPED_SLASHES) ?>,{scope:<?= json_encode($frenchBase, JSON_UNESCAPED_SLASHES) ?>}).catch(()=>{}))}</script>
</body>
</html>
