<?php
$pageTitle = 'Lesson Archive | Beyond French';
require __DIR__ . '/includes/header.php';
$lessons = all_lessons();
if (french_is_guest()) {
    $todayLesson = todays_lesson();
    $lessons = $todayLesson ? [$todayLesson] : [];
}
$module = strtolower(trim((string)($_GET['module'] ?? '')));
if ($module !== '' && isset(french_modules()[$module])) $lessons=array_values(array_filter($lessons,fn(array $lesson): bool => lesson_module($lesson)===$module));
$query = trim($_GET['q'] ?? '');
if ($query !== '') {
    $lessons = array_values(array_filter($lessons, function(array $lesson) use ($query): bool {
        $haystack = implode(' ', $lesson);
        return stripos($haystack, $query) !== false;
    }));
}
?>
<section class="section page-top">
    <span class="eyebrow">LESSON ARCHIVE</span>
    <h1>Keep learning.</h1>
    <?php if($module!==''&&isset(french_modules()[$module])):?><p class="eyebrow">MODULE <?= array_search($module,array_keys(french_modules()),true)+1 ?> · <?= h(french_modules()[$module]['title']) ?></p><?php endif;?>
    <form class="search-form" method="get">
        <input type="search" name="q" value="<?= h($query) ?>" placeholder="Search phrases">
        <button class="button primary">Search</button>
    </form>
    <div class="archive-grid">
        <?php foreach ($lessons as $lesson): ?>
        <a class="archive-card" href="lesson.php?id=<?= (int)$lesson['id'] ?>">
            <small><?= h($lesson['date']) ?> · <?= h($lesson['category']) ?></small>
            <h3><?= h($lesson['english']) ?></h3>
            <p>🇫🇷 <?= h($lesson['french']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
