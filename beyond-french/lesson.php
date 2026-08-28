<?php
require __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$lesson = lesson_by_id($id);
if (!$lesson) {
    http_response_code(404);
    exit('Lesson not found.');
}
if (!french_guest_daily_lesson_allowed($lesson)) {
    http_response_code(403);
    $pageTitle='Beyond ID required | Beyond French';
    require __DIR__.'/includes/header.php';
    echo '<section class="section center"><span class="big-icon">🔒</span><h1>Today’s lesson is free.</h1><p>Create a free Beyond ID to open the full lesson archive and save your progress.</p><a class="button primary" href="../beyond-id/auth/register.php?app=beyond-french">Create Beyond ID</a></section>';
    require __DIR__.'/includes/footer.php';
    exit;
}
$userId=(int)($_SESSION['user_id']??0);french_mark_started($userId,$id);$position=lesson_position($id);
$lessonAudio=lesson_audio_map($id);$frenchAudioUrl=(string)($lessonAudio['fr-FR']??$lessonAudio['fr-CA']??$lesson['audio_url']??'');
$pageTitle = $lesson['english'] . ' | Beyond French';
require __DIR__ . '/includes/header.php';
?>
<section class="section page-top">
    <a class="back-link" href="archive.php">← Back to lessons</a>
    <div class="lesson-progress-label" style="display:inline-flex;margin-bottom:12px;padding:8px 11px;border-radius:999px;color:#fff;background:#1768ff;font-size:12px;font-weight:900">Module <?= (int)$position['module'] ?> · <?= h($position['module_title']) ?> · Lesson <?= (int)$position['lesson'] ?></div>
    <span class="eyebrow"><?= h($lesson['category']) ?></span>
    <h1><?= h($lesson['english']) ?></h1>
    <article class="lesson-card">
        <div class="translation-grid">
            <div class="translation"><span class="flag">🇫🇷</span><small>Français</small><strong><?= h($lesson['french']) ?></strong><em><?= h($lesson['french_pronunciation']) ?></em></div>
            <div class="translation"><span class="flag">🇯🇲</span><small>Patois</small><strong><?= h($lesson['patois']) ?></strong></div>
            <div class="translation"><span class="flag">🇭🇹</span><small>Kreyòl</small><strong><?= h($lesson['kreyol']) ?></strong></div>
            <div class="translation"><span class="flag">🇪🇸</span><small>Español</small><strong><?= h($lesson['spanish']) ?></strong></div>
        </div>
        <div class="lesson-actions">
            <?php foreach ([
                ['French', 'fr-FR', $lesson['french'], $frenchAudioUrl],
                ['Spanish', 'es-ES', $lesson['spanish'], (string)($lessonAudio['es-ES'] ?? '')],
                ['Kreyòl', 'ht-HT', $lesson['kreyol'], (string)($lessonAudio['ht-HT'] ?? '')],
                ['Patois', 'en-JM', $lesson['patois'], (string)($lessonAudio['en-JM'] ?? '')],
            ] as [$audioLabel, $audioLocale, $audioText, $audioUrl]): ?>
                <button class="button secondary lesson-audio-button" type="button" data-label="<?= h($audioLabel) ?>" data-locale="<?= h($audioLocale) ?>" data-audio-url="<?= h($audioUrl) ?>" data-speak="<?= h($audioText) ?>">🔊 Hear <?= h($audioLabel) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="culture-note"><strong>💡 Culture note:</strong> <?= h($lesson['culture_note']) ?></div>
        <a class="button primary" href="challenge.php?id=<?= (int)$lesson['id'] ?>">Take this challenge</a>
    </article>
</section>
<script>
document.querySelectorAll('.lesson-audio-button').forEach(button=>button.addEventListener('click',async function(){
 const url=button.dataset.audioUrl,text=button.dataset.speak||'',locale=button.dataset.locale||'fr-FR',label=button.dataset.label||'lesson';
 if(url){try{button.textContent='▶ Playing…';const audio=new Audio(url);audio.onended=()=>button.textContent='🔊 Hear '+label;audio.onerror=()=>button.textContent='Audio unavailable';await audio.play();return}catch(error){}}
 try{button.textContent='Loading personal-test voice…';const response=await fetch('api/voice.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({text,locale})});if(!response.ok)throw new Error('unavailable');const audio=new Audio(URL.createObjectURL(await response.blob()));audio.onended=()=>button.textContent='🔊 Hear '+label;await audio.play()}catch(error){button.textContent='Test voice unavailable'}
}));
</script>
<p><small>Personal testing only · Kreyòl and Patois voice demos powered by <a href="https://elevenlabs.io" rel="noopener" target="_blank">elevenlabs.io</a></small></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
