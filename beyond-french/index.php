<?php
require_once __DIR__ . '/../includes/ecosystem.php';
$isGuest = empty($_SESSION['user_id']);
if ($isGuest) {
    $beyondWallet = beyond_nav_bootstrap('Beyond French', ['balance'=>0,'currency'=>'BITS','status'=>'guest']);
} else {
    $beyondWallet = beyond_app_bootstrap('Beyond French');
}
$pageTitle = 'Beyond French | Daily Academy';
require __DIR__ . '/includes/header.php';
$lesson = todays_lesson();
$lessonAudio = $lesson ? lesson_audio_map((int)$lesson['id']) : [];
$frenchAudioUrl = (string)($lessonAudio['fr-FR'] ?? $lessonAudio['fr-CA'] ?? $lesson['audio_url'] ?? '');
$continueLesson = french_continue_lesson((int)($_SESSION['user_id'] ?? 0));
$continuePosition = lesson_position((int)($continueLesson['id'] ?? 1));
$learningProgress = french_progress((int)($_SESSION['user_id'] ?? 0));
$isReturningLearner = !empty($learningProgress['last_lesson_id']);
?>
<style>.french-splash{position:fixed;inset:0;z-index:2147483600;display:grid;place-items:center;padding:24px;color:#fff;background:linear-gradient(135deg,rgba(7,21,47,.94),rgba(23,104,255,.88),rgba(239,51,64,.78));transition:opacity .35s,visibility .35s}.french-splash.hidden{opacity:0;visibility:hidden}.french-splash-inner{width:min(520px,100%);text-align:center}.french-splash img{width:112px;height:112px;border-radius:50%;box-shadow:0 22px 60px #0006}.french-splash h1{font-size:clamp(46px,10vw,78px);line-height:.95;letter-spacing:-.06em;margin:22px 0 12px}.french-splash p{font-size:18px;color:#e9eefc}.french-splash a{display:inline-flex;margin:16px 0 13px;padding:15px 22px;border-radius:999px;color:#07152f;background:#ffbf00;font-weight:900}.french-splash small{display:block;color:#d8e1f5;font-weight:800}.lesson-progress-label{display:inline-flex;margin-bottom:12px;padding:8px 11px;border-radius:999px;color:#fff;background:#1768ff;font-size:12px;font-weight:900}</style>
<div class="french-splash" id="french-splash"><div class="french-splash-inner"><img src="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp" alt=""><span class="eyebrow" style="color:#ffbf00">BEYOND FRENCH · DAILY ACADEMY</span><h1>Parlez français.</h1><p><?= $isReturningLearner ? 'Your next lesson is ready.' : 'Start small. Speak with confidence.' ?></p><a id="enter-french" href="<?= h($frenchBase) ?>dictionary.php">Open free dictionary →</a><small>Dictionary · Written translation · No Beyond ID required</small></div></div>
<script>const frenchSplash=document.getElementById('french-splash');if(sessionStorage.getItem('beyond-french-entered')==='1')frenchSplash.classList.add('hidden');document.getElementById('enter-french').addEventListener('click',()=>sessionStorage.setItem('beyond-french-entered','1'));</script>
<section class="section app-today-intro" aria-labelledby="today-app-title">
    <div class="app-today-copy">
        <span class="eyebrow">FRANÇAIS DU JOUR</span>
        <h1 id="today-app-title">Today’s lesson</h1>
        <p>Learn the phrase, hear the supported voices, then practice it.</p>
    </div>
    <div class="app-today-actions">
        <a class="button secondary" href="<?= h($frenchBase) ?>archive.php">Past lessons</a>
        <a class="button primary" href="<?= h($frenchBase) ?>challenge.php<?= $lesson ? '?id=' . (int)$lesson['id'] : '' ?>">Start practice</a>
    </div>
</section>

<?php if ($lesson): ?>
<section class="section" id="today">
    <div class="section-heading">
        <div>
            <span class="eyebrow">FRANÇAIS DU JOUR</span>
            <h2><?= lesson_is_today($lesson) ? "Today’s phrase" : "Latest phrase" ?></h2>
        </div>
        <span class="date-badge"><?= h(date('M j', strtotime($lesson['date']))) ?></span>
    </div>

    <article class="lesson-card">
        <div class="english-phrase">
            <small>English</small>
            <h3>“<?= h($lesson['english']) ?>”</h3>
        </div>
        <div class="translation-grid">
            <div class="translation">
                <span class="flag">🇫🇷</span><small>Français</small>
                <strong><?= h($lesson['french']) ?></strong>
                <em><?= h($lesson['french_pronunciation']) ?></em>
            </div>
            <div class="translation">
                <span class="flag">🇯🇲</span><small>Patois</small>
                <strong><?= h($lesson['patois']) ?></strong>
            </div>
            <div class="translation">
                <span class="flag">🇭🇹</span><small>Kreyòl</small>
                <strong><?= h($lesson['kreyol']) ?></strong>
            </div>
            <div class="translation">
                <span class="flag">🇪🇸</span><small>Español</small>
                <strong><?= h($lesson['spanish']) ?></strong>
            </div>
        </div>
        <section class="voice-lab" aria-labelledby="voice-lab-title">
            <div class="voice-lab-head">
                <div>
                    <span class="eyebrow">LESSON AUDIO</span>
                    <h3 id="voice-lab-title">Listen to today’s phrase</h3>
                    <p>French and Spanish audio are available when supported. Kreyòl and Patois remain clear written language guides.</p>
                </div>
                <button class="voice-stop" type="button" aria-label="Stop audio">■ Stop</button>
            </div>
            <div class="voice-grid">
                <button class="voice-card active" type="button" data-locale="fr-FR" data-language="French" data-label="French · France" data-speak="<?= h($lesson['french']) ?>" data-audio-url="<?= h($frenchAudioUrl) ?>">
                    <span class="voice-flag">🇫🇷</span><span><strong>Français</strong><small>France voice</small></span><i>▶</i>
                </button>
                <button class="voice-card" type="button" data-locale="es-ES" data-language="Spanish" data-label="Spanish · Spain" data-speak="<?= h($lesson['spanish']) ?>" data-audio-url="<?= h((string)($lessonAudio['es-ES'] ?? '')) ?>">
                    <span class="voice-flag">🇪🇸</span><span><strong>Español</strong><small>Spanish voice</small></span><i>▶</i>
                </button>
            </div>
            <div class="voice-controls">
                <label>Voice<select id="local-voice-select" aria-label="Available local voices"><option>Loading device voices…</option></select></label>
                <label>Speed<input id="voice-rate" type="range" min="0.65" max="1.15" value="0.88" step="0.05"><output id="voice-rate-output">0.88×</output></label>
            </div>
            <div class="voice-status" role="status" aria-live="polite">Choose a voice and tap play.</div>
        </section>
        <div class="culture-note"><strong>💡 Culture note:</strong> <?= h($lesson['culture_note']) ?></div>
        <div class="lesson-actions">
            <button class="button secondary speak-phrase" type="button" data-locale="fr-FR" data-speak="<?= h($lesson['french']) ?>">🔊 Quick listen</button>
            <button class="button secondary copy-phrase" type="button" data-copy="<?= h($lesson['french']) ?>">Copy French</button>
            <a class="button primary" href="challenge.php?id=<?= (int)$lesson['id'] ?>">Practice now →</a>
        </div>
        <div class="lesson-next">
            <span>Next step</span>
            <strong>Use the phrase in a real conversation challenge.</strong>
            <a href="challenge.php?id=<?= (int)$lesson['id'] ?>">Start challenge</a>
        </div>
    </article>
</section>
<?php endif; ?>

<section class="section app-next" id="academy">
    <div class="section-heading">
        <div><span class="eyebrow">KEEP LEARNING</span><h2>Continue from here</h2></div>
    </div>
    <div class="app-tool-grid">
        <a href="<?= h($frenchBase) ?>challenge.php<?= $lesson ? '?id=' . (int)$lesson['id'] : '' ?>"><span>💬</span><strong>Practice today’s phrase</strong><small>Complete the conversation challenge.</small></a>
        <a href="<?= h($frenchBase) ?>archive.php"><span>📚</span><strong>Browse lessons</strong><small>Review previous phrases anytime.</small></a>
    </div>
</section>
<section class="section" id="modules"><div class="section-heading"><div><span class="eyebrow">LEARNING PATH</span><h2>Five practical course modules</h2></div><a class="button primary" href="<?= h($frenchBase) ?>academy.php">Open French Academy</a></div><div class="archive-grid"><?php foreach(french_modules() as $slug=>$module):?><a class="archive-card" href="<?= h($frenchBase) ?>academy.php"><span style="font-size:2rem"><?= h($module['icon']) ?></span><small>MODULE <?= array_search($slug,array_keys(french_modules()),true)+1 ?></small><h3><?= h($module['title']) ?></h3><p><?= h($module['description']) ?></p></a><?php endforeach;?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
