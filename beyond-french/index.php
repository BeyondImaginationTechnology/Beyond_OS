<?php
require_once __DIR__ . '/../includes/ecosystem.php';
$isGuest = empty($_SESSION['user_id']);
if ($isGuest) {
    $beyondWallet = beyond_nav_bootstrap('Beyond French', ['balance'=>0,'currency'=>'BITS','status'=>'guest']);
} else {
    $beyondWallet = beyond_app_bootstrap('Beyond French');
}
$pageTitle = 'Beyond French | Daily Academy';
$appShell = true;
require __DIR__ . '/includes/header.php';
$lesson = null;
$lessonAudio = [];
$frenchAudioUrl = '';
$continueLesson = null;
$continuePosition = [];
$learningProgress = [];
$isReturningLearner = false;
try {
    $lesson = todays_lesson();
    $lessonAudio = $lesson ? lesson_audio_map((int)$lesson['id']) : [];
    $frenchAudioUrl = (string)($lessonAudio['fr-FR'] ?? $lessonAudio['fr-CA'] ?? $lesson['audio_url'] ?? '');
    $continueLesson = french_continue_lesson((int)($_SESSION['user_id'] ?? 0));
    $continuePosition = lesson_position((int)($continueLesson['id'] ?? 1));
    $learningProgress = french_progress((int)($_SESSION['user_id'] ?? 0));
    $isReturningLearner = !empty($learningProgress['last_lesson_id']);
} catch (Throwable $error) {
    error_log('Beyond French homepage data unavailable: ' . $error->getMessage());
}
?>
<style>
.french-splash{position:fixed;inset:0;z-index:2147483600;display:grid;place-items:center;padding:24px;color:#fff;background:linear-gradient(135deg,rgba(7,21,47,.94),rgba(23,104,255,.88),rgba(239,51,64,.78));transition:opacity .35s,visibility .35s}.french-splash.hidden{opacity:0;visibility:hidden}.french-splash-inner{width:min(520px,100%);text-align:center}.french-splash img{width:112px;height:112px;border-radius:50%;box-shadow:0 22px 60px #0006}.french-splash h1{font-size:clamp(46px,10vw,78px);line-height:.95;letter-spacing:-.06em;margin:22px 0 12px}.french-splash p{font-size:18px;color:#e9eefc}.french-splash small{display:block;color:#d8e1f5;font-weight:800}.language-paths{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:24px 0}.language-paths button{padding:14px;border:1px solid #ffffff55;border-radius:15px;color:#fff;background:#07152f66;font-weight:900;cursor:pointer}.language-paths button:hover{background:#ffbf00;color:#07152f}.lesson-progress-label{display:inline-flex;margin-bottom:12px;padding:8px 11px;border-radius:999px;color:#fff;background:#1768ff;font-size:12px;font-weight:900}
body.app-shell{background:#f3f5fa;padding-bottom:92px}body.app-shell>.site-header{max-width:none;padding:16px max(18px,calc((100vw - 760px)/2));background:#fff;border-bottom:1px solid #e7eaf1;box-shadow:0 4px 18px rgba(7,21,47,.05)}body.app-shell>.site-header .nav{display:none}body.app-shell>.site-header .brand img{width:44px;height:44px}body.app-shell>.site-header .brand strong{font-size:.96rem}body.app-shell>.site-header .brand small{font-size:.76rem}
body.app-shell .app-today-intro{max-width:720px;margin:auto;padding:28px 20px 10px;display:block}body.app-shell .app-today-copy h1{font-size:2rem;margin:5px 0 7px}body.app-shell .app-today-copy p{font-size:.9rem}body.app-shell .app-today-actions{display:none}body.app-shell #today{max-width:720px;padding:12px 20px 32px}body.app-shell #today .section-heading{margin-bottom:14px;align-items:center}body.app-shell #today .section-heading h2{font-size:1.05rem;margin:5px 0}body.app-shell #today .eyebrow{font-size:.64rem}body.app-shell .date-badge{padding:8px 11px;border-radius:12px;font-size:.78rem}
body.app-shell .lesson-card{padding:18px;border-radius:28px;box-shadow:0 12px 34px rgba(7,21,47,.09)}body.app-shell .english-phrase{text-align:left;padding:5px 2px 20px}body.app-shell .english-phrase small{font-size:.66rem}body.app-shell .english-phrase h3{font-size:clamp(1.8rem,6vw,2.65rem);line-height:1.08;margin:9px 0 0;letter-spacing:-.045em}body.app-shell .translation-grid{grid-template-columns:1fr 1fr;gap:9px}body.app-shell .translation{min-height:0;padding:14px;border-radius:16px;gap:5px}body.app-shell .translation .flag{font-size:1.35rem}body.app-shell .translation small{font-size:.62rem}body.app-shell .translation strong{font-size:.93rem;line-height:1.25}body.app-shell .translation em{font-size:.76rem}
body.app-shell .voice-lab{margin-top:14px;padding:15px;border-radius:20px}body.app-shell .voice-lab-head{align-items:center}body.app-shell .voice-lab-head h3{font-size:1.05rem;margin:3px 0}body.app-shell .voice-lab-head p,body.app-shell .voice-stop,body.app-shell .voice-controls,body.app-shell .voice-status,body.app-shell .voice-lab>small{display:none}body.app-shell .voice-grid{grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}body.app-shell .voice-card{padding:10px;border-radius:13px;grid-template-columns:auto 1fr auto}body.app-shell .voice-flag{font-size:1.35rem}body.app-shell .voice-card strong{font-size:.82rem}body.app-shell .voice-card small{font-size:.65rem}body.app-shell .voice-card i{font-size:.85rem}body.app-shell .culture-note{margin-top:12px;padding:12px;font-size:.82rem}body.app-shell .lesson-actions{margin-top:14px}body.app-shell .lesson-actions .speak-phrase,body.app-shell .lesson-actions .copy-phrase{display:none}body.app-shell .lesson-actions .primary{width:100%;padding:13px}body.app-shell .lesson-next{margin-top:12px;padding:12px;font-size:.8rem}body.app-shell .lesson-next a{font-size:.82rem}
body.app-shell #academy,body.app-shell #modules{display:none}body.app-shell .mobile-tabbar{position:fixed;left:50%;transform:translateX(-50%);bottom:calc(10px + env(safe-area-inset-bottom));width:min(680px,calc(100% - 24px));height:74px;padding:8px 10px;display:grid;grid-template-columns:repeat(5,1fr);align-items:center;background:rgba(7,21,47,.96);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.16);border-radius:24px;box-shadow:0 18px 45px rgba(7,21,47,.32);z-index:100}body.app-shell .mobile-tabbar a{color:#fff;display:grid;place-items:center;gap:2px;font-weight:800}body.app-shell .mobile-tabbar span{font-size:1.2rem}body.app-shell .mobile-tabbar small{font-size:.66rem}body.app-shell .mobile-tabbar .tab-primary{transform:translateY(-12px);width:62px;height:62px;justify-self:center;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--red));box-shadow:0 8px 24px rgba(23,104,255,.35)}body.app-shell>.site-footer{display:none}
@media(max-width:560px){body.app-shell .app-today-intro{padding:24px 18px 8px}body.app-shell #today{padding-left:18px;padding-right:18px}body.app-shell .lesson-card{padding:14px}.french-splash h1{font-size:3rem}}
</style>
<div class="french-splash" id="french-splash"><div class="french-splash-inner"><img src="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp" alt=""><span class="eyebrow" style="color:#ffbf00">BEYOND FRENCH · DAILY ACADEMY</span><h1>Choose your language path.</h1><p><?= $isReturningLearner ? 'Your next lesson is ready.' : 'Start small. Speak with confidence.' ?></p><div class="language-paths"><button data-learning-language="french">🇫🇷 French</button><button data-learning-language="kreyol">🇭🇹 Kreyòl</button><button data-learning-language="patois">🇯🇲 Patois</button><button data-learning-language="spanish">🇪🇸 Spanish</button></div><small>Change this anytime in Settings.</small></div></div>
<script>const frenchSplash=document.getElementById('french-splash'),savedLanguage=localStorage.getItem('beyond-french.learning-language');if(sessionStorage.getItem('beyond-french-entered')==='1'&&savedLanguage)frenchSplash.classList.add('hidden');document.querySelectorAll('[data-learning-language]').forEach(button=>button.addEventListener('click',()=>{localStorage.setItem('beyond-french.learning-language',button.dataset.learningLanguage);sessionStorage.setItem('beyond-french-entered','1');frenchSplash.classList.add('hidden')}));</script>
<section class="section app-today-intro" aria-labelledby="today-app-title">
    <div class="app-today-copy">
        <span class="eyebrow">FRANÇAIS DU JOUR</span>
        <h1 id="today-app-title">Today’s lesson</h1>
        <p>Learn the phrase, hear the supported voices, then practice it.</p>
    </div>
    <div class="app-today-actions">
        <button class="button secondary install-app" id="install-beyond-french" type="button" hidden>Install app</button>
        <a class="button primary" href="<?= h($frenchBase) ?>challenge.php<?= $lesson ? '?id=' . (int)$lesson['id'] : '' ?>">Continue learning</a>
        <a class="button secondary" href="<?= h($frenchBase) ?>academy.php">Academy</a>
        <a class="button secondary" href="<?= h($frenchBase) ?>game.php">French Quest</a>
        <a class="button secondary" href="<?= h($frenchBase) ?>settings.php">Settings</a>
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
                    <p>French uses prerecorded Azure audio. Spanish uses Azure Elvira live; Kreyòl and Patois use temporary noncommercial ElevenLabs test voices.</p>
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
                <button class="voice-card" type="button" data-locale="ht-HT" data-language="Kreyòl" data-label="Haitian Creole" data-speak="<?= h($lesson['kreyol']) ?>" data-audio-url="<?= h((string)($lessonAudio['ht-HT'] ?? '')) ?>">
                    <span class="voice-flag">🇭🇹</span><span><strong>Kreyòl</strong><small>Haitian voice</small></span><i>▶</i>
                </button>
                <button class="voice-card" type="button" data-locale="en-JM" data-language="Patois" data-label="Jamaican Patois" data-speak="<?= h($lesson['patois']) ?>" data-audio-url="<?= h((string)($lessonAudio['en-JM'] ?? '')) ?>">
                    <span class="voice-flag">🇯🇲</span><span><strong>Patois</strong><small>Jamaican voice</small></span><i>▶</i>
                </button>
            </div>
            <div class="voice-controls">
                <label>Voice<select id="local-voice-select" aria-label="Available local voices"><option>Loading device voices…</option></select></label>
                <label>Speed<input id="voice-rate" type="range" min="0.65" max="1.15" value="0.88" step="0.05"><output id="voice-rate-output">0.88×</output></label>
            </div>
            <div class="voice-status" role="status" aria-live="polite">Choose a voice and tap play.</div>
            <small>Personal testing only · Kreyòl and Patois voice demos powered by <a href="https://elevenlabs.io" rel="noopener" target="_blank">elevenlabs.io</a></small>
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
<?php if (!$lesson): ?>
<section class="section" id="today-empty" aria-labelledby="today-empty-title">
    <div class="lesson-card empty-today-card">
        <span class="eyebrow">FRANÇAIS DU JOUR</span>
        <h2 id="today-empty-title">Your next lesson is being prepared.</h2>
        <p>Today’s phrase is temporarily unavailable, but your learning path is still open. Browse the archive or start with the dictionary while the daily lesson syncs.</p>
        <div class="lesson-actions"><a class="button primary" href="<?= h($frenchBase) ?>archive.php">Browse daily lessons →</a><a class="button secondary" href="<?= h($frenchBase) ?>dictionary.php">Open dictionary</a></div>
    </div>
</section>
<?php endif; ?>

<section class="section app-next" id="academy">
    <div class="section-heading">
        <div><span class="eyebrow">KEEP LEARNING</span><h2>Continue from here</h2></div>
    </div>
    <div class="app-tool-grid">
        <a href="<?= h($frenchBase) ?>challenge.php<?= $lesson ? '?id=' . (int)$lesson['id'] : '' ?>"><span>▶</span><strong>Continue learning</strong><small>Resume today’s phrase challenge.</small></a>
        <a href="<?= h($frenchBase) ?>academy.php"><span>🎓</span><strong>Academy</strong><small>Follow your guided learning path.</small></a>
        <a href="<?= h($frenchBase) ?>game.php"><span>🗺️</span><strong>French Quest</strong><small>Story mode, routes, and trivia.</small></a>
        <a href="<?= h($frenchBase) ?>settings.php"><span>⚙️</span><strong>Settings</strong><small>Change your learning language.</small></a>
    </div>
</section>
<section class="section" id="modules"><div class="section-heading"><div><span class="eyebrow">LEARNING PATH</span><h2>Five practical course modules</h2></div><a class="button primary" href="<?= h($frenchBase) ?>academy.php">Open French Academy</a></div><div class="archive-grid"><?php foreach(french_modules() as $slug=>$module):?><a class="archive-card" href="<?= h($frenchBase) ?>academy.php"><span style="font-size:2rem"><?= h($module['icon']) ?></span><small>MODULE <?= array_search($slug,array_keys(french_modules()),true)+1 ?></small><h3><?= h($module['title']) ?></h3><p><?= h($module['description']) ?></p></a><?php endforeach;?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
