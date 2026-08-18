</main>
<nav class="mobile-tabbar" aria-label="Beyond French navigation">
    <a href="<?= h($frenchBase) ?>"><span>🏠</span><small>Today</small></a>
    <a href="<?= h($frenchBase) ?>academy.php"><span>🎓</span><small>Academy</small></a>
    <a class="tab-primary" href="<?= h($frenchBase) ?>challenge.php"><span>💬</span><small>Practice</small></a>
    <a href="<?= h($frenchBase) ?>archive.php"><span>📚</span><small>Daily</small></a>
    <a href="<?= h($frenchBase) ?>archive.php"><span>🔥</span><small>Streak</small></a>
</nav>
<footer class="site-footer">
    <p>© <?= date('Y') ?> Beyond French · French first. Five languages. Every day. · Version 1.2.0</p>
</footer>
<script src="<?= h($frenchBase) ?>assets/js/app.js?v=<?= h((string)(@filemtime(__DIR__ . '/../assets/js/app.js') ?: time())) ?>"></script>
</body>
</html>
