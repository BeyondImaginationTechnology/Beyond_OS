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
$academyCompletedCount = 0;
try {
    $lesson = todays_lesson();
    $lessonAudio = $lesson ? lesson_audio_map((int)$lesson['id']) : [];
    $frenchAudioUrl = (string)($lessonAudio['fr-FR'] ?? $lessonAudio['fr-CA'] ?? $lesson['audio_url'] ?? '');
    $continueLesson = french_continue_lesson((int)($_SESSION['user_id'] ?? 0));
    $continuePosition = lesson_position((int)($continueLesson['id'] ?? 1));
    $learningProgress = french_progress((int)($_SESSION['user_id'] ?? 0));
    $isReturningLearner = !empty($learningProgress['last_lesson_id']);
    $completedLessons = json_decode((string)($learningProgress['completed_lessons_json'] ?? '[]'), true);
    $academyCompletedCount = is_array($completedLessons) ? count($completedLessons) : 0;
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
body.app-shell .translation{border:1px solid transparent;box-shadow:inset 0 3px 0 var(--language-accent)}body.app-shell .translation small{color:var(--language-accent);font-weight:900}body.app-shell .translation-fr{--language-accent:#1768ff;background:#edf4ff;border-color:#cfe0ff}body.app-shell .translation-ht{--language-accent:#e44761;background:#fff0f2;border-color:#ffd4da}body.app-shell .translation-jm{--language-accent:#079b69;background:#e9f9f2;border-color:#c6efdd}body.app-shell .translation-es{--language-accent:#c47b18;background:#fff7e8;border-color:#f6dfb1}
 .difficulty-paths{display:grid;gap:10px;margin:24px 0}.difficulty-paths button{display:grid;gap:5px;text-align:left;padding:16px 18px;border:1px solid #ffffff55;border-radius:17px;color:#fff;background:#07152f66;cursor:pointer}.difficulty-paths button:hover,.difficulty-paths button:focus{background:#1768ff;border-color:#83b0ff}.difficulty-paths strong{font-size:1.05rem}.difficulty-paths small{color:#d8e1f5}.difficulty-cast,.language-choice{display:none}.difficulty-cast.visible,.language-choice.visible{display:block}.difficulty-cast{grid-template-columns:repeat(4,1fr);gap:8px;margin:16px 0}.difficulty-cast.visible{display:grid}.difficulty-cast figure{margin:0}.difficulty-cast img{width:100%;aspect-ratio:1;object-fit:cover;object-position:top;border-radius:14px;border:1px solid #ffffff44}.difficulty-cast figcaption{font-size:.7rem;color:#fff;font-weight:800;margin-top:4px}.language-choice{padding-top:14px;border-top:1px solid #ffffff33}.language-choice>strong{color:#fff;font-size:.9rem}.language-choice .language-paths{margin:12px 0}.language-choice small{color:#d8e1f5}
body.app-shell .speech-check{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;margin-top:14px;padding:15px;border:1px solid #dbe4f1;border-radius:20px;background:#f7faff}body.app-shell .speech-check h3{margin:4px 0;font-size:1rem}body.app-shell .speech-check p{margin:0;color:var(--muted);font-size:.78rem;line-height:1.4}body.app-shell .speech-check .eyebrow{font-size:.62rem}body.app-shell #record-phrase{padding:11px 13px;white-space:nowrap}body.app-shell #record-phrase.recording{background:#ef3340;color:#fff}body.app-shell .speech-match{grid-column:1/-1;padding:10px 12px;border-radius:12px;background:#fff;color:var(--muted);font-size:.82rem}body.app-shell .speech-match strong{color:var(--blue);font-size:1.15rem}@media(max-width:560px){body.app-shell .speech-check{grid-template-columns:1fr}.speech-check #record-phrase{width:100%}}
body.app-shell .language-set-control{display:grid;grid-template-columns:auto minmax(0,1fr);gap:6px 10px;align-items:center;max-width:720px;margin:0 auto 10px;padding:0 20px;color:var(--muted);font-size:.78rem}body.app-shell .language-set-control label{font-weight:900;color:var(--text)}body.app-shell .language-set-control select{min-width:0;border:1px solid #d6dfec;background:#fff;color:var(--text);border-radius:12px;padding:9px 11px;font-size:.78rem}body.app-shell .language-set-control small{grid-column:2}@media(max-width:560px){body.app-shell .language-set-control{padding:0 18px;grid-template-columns:1fr}.language-set-control small{grid-column:auto}}
body.app-shell .today-stats{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}body.app-shell .today-stats span{padding:8px 10px;border:1px solid #dce4ef;border-radius:999px;background:#fff;color:var(--muted);font-size:.76rem;font-weight:800}body.app-shell .today-stats strong{color:var(--text)}
body.app-shell .mobile-tabbar .tab-primary{transform:none;width:auto;height:auto;border-radius:14px;background:rgba(255,255,255,.1);box-shadow:none;color:#fff;padding:8px 10px}body.app-shell .mobile-tabbar a{opacity:.72}body.app-shell .mobile-tabbar a:hover,body.app-shell .mobile-tabbar a:focus-visible{opacity:1}
body.app-shell{background:#061633;color:#fff}body.app-shell>.site-header{background:#071a3b;color:#fff;border-bottom-color:#ffffff18}body.app-shell>.site-header .brand small{color:#aabbd5}body.app-shell .app-today-copy h1{color:#fff}body.app-shell .app-today-copy p{color:#b7c8e3}body.app-shell #today .section-heading h2{color:#fff}body.app-shell .language-set-control{color:#b7c8e3}body.app-shell .language-set-control label{color:#fff}.french-splash{background:radial-gradient(circle at 50% 42%,#173d73 0,#071a3b 45%,#030b1c 100%);overflow:hidden}.splash-cast{position:absolute;inset:0;pointer-events:none}.splash-cast img{position:absolute;width:clamp(125px,20vw,250px);height:clamp(175px,32vw,340px);object-fit:cover;object-position:top;border-radius:36px;opacity:.34;filter:saturate(.85) blur(.2px);box-shadow:0 20px 70px #0008}.splash-cast img:nth-child(1){left:-3%;top:10%;transform:rotate(-8deg)}.splash-cast img:nth-child(2){left:5%;bottom:7%;transform:rotate(7deg)}.splash-cast img:nth-child(3){right:4%;top:8%;transform:rotate(8deg)}.splash-cast img:nth-child(4){right:-4%;bottom:6%;transform:rotate(-7deg)}.french-splash-inner{position:relative;z-index:1}.french-splash-inner>img{border:3px solid #ffffff55}.difficulty-paths button{background:#07152fbb;border-color:#ffffff44;backdrop-filter:blur(8px)}
</style>
<div class="french-splash" id="french-splash"><div class="splash-cast" aria-hidden="true"><img data-splash-tutor="louis" src="<?= h($frenchBase) ?>assets/images/tutors/louis.jpg" alt=""><img data-splash-tutor="irie" src="<?= h($frenchBase) ?>assets/images/tutors/irie.jpg" alt=""><img data-splash-tutor="jazzy" src="<?= h($frenchBase) ?>assets/images/tutors/jazzy.jpg" alt=""><img data-splash-tutor="pablo" src="<?= h($frenchBase) ?>assets/images/tutors/pablo.jpg" alt=""></div><div class="french-splash-inner"><img src="<?= h($frenchBase) ?>assets/images/beyond-french-logo.webp" alt=""><span class="eyebrow" style="color:#ffbf00">BEYOND FRENCH · DAILY ACADEMY</span><h1>Choose your learning level.</h1><p><?= $isReturningLearner ? 'Your next lesson is ready.' : 'Your guide and lesson style will follow your choice.' ?></p><div class="difficulty-paths" role="list"><button data-difficulty="beginner" data-age="kids"><strong>Beginner</strong><small>Friendly chibbi guides · start with confidence</small></button><button data-difficulty="intermediate" data-age="teen"><strong>Intermediate</strong><small>Teen and young-adult chibbis · build fluency</small></button><button data-difficulty="advanced" data-age="adult"><strong>Advanced</strong><small>Realistic guides · conversation and culture</small></button></div><div class="difficulty-cast" id="difficulty-cast" aria-live="polite"></div></div></div>
<script>(function(){const frenchSplash=document.getElementById('french-splash'),savedDifficulty=localStorage.getItem('beyond-french.difficulty'),cast=document.getElementById('difficulty-cast'),castBase='<?= h($frenchBase) ?>assets/images/tutors/';const castByAge={kids:[['Louis','louis.jpg'],['Irie','irie.jpg'],['Jazzy','jazzy.jpg'],['Pablo','pablo.jpg']],teen:[['Louis','louis.jpg'],['Irie','irie.jpg'],['Jazzy','jazzy.jpg'],['Pablo','pablo.jpg']],adult:[['Louis','louis.jpg'],['Irie','irie.jpg'],['Jazzy','jazzy.jpg'],['Pablo','pablo.jpg']]};function choose(button){const age=button.dataset.age,difficulty=button.dataset.difficulty;localStorage.setItem('beyond-french.difficulty',difficulty);localStorage.setItem('beyond-french.age',age);cast.innerHTML=castByAge[age].map((t)=>'<figure><img src="'+castBase+(age==='kids'?'':age==='teen'?'high-school/':'advanced/')+t[1]+'" alt="'+t[0]+'"><figcaption>'+t[0]+'</figcaption></figure>').join('');document.querySelectorAll('[data-splash-tutor]').forEach(image=>{image.src=castBase+(age==='kids'?'':age==='teen'?'high-school/':'advanced/')+image.dataset.splashTutor+'.jpg'});cast.classList.add('visible');sessionStorage.setItem('beyond-french-entered','1');frenchSplash.classList.add('hidden')}document.querySelectorAll('[data-difficulty]').forEach(button=>button.addEventListener('click',()=>choose(button)));if(sessionStorage.getItem('beyond-french-entered')==='1'&&savedDifficulty)frenchSplash.classList.add('hidden');})();</script>
<section class="section app-today-intro" aria-labelledby="today-app-title">
    <div class="app-today-copy">
        <span class="eyebrow">FRANÇAIS DU JOUR</span>
        <h1 id="today-app-title">Today’s lesson</h1>
        <p>Learn the phrase, hear the supported voices, then practice it.</p>
        <div class="today-stats"><span>🔥 <strong data-daily-streak>0</strong> day streak</span><span>🎓 <strong><?= (int)$academyCompletedCount ?></strong> Academy lessons</span></div>
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
    <div class="language-set-control"><label for="language-set">Language Region:</label><select id="language-set"><option value="core">Caribbean · French, Haitian Kreyòl, Jamaican Patois, Spanish</option><option value="euro">Europe · French, Italian, German, Portuguese, Russian</option><option value="africa">Africa · French, Lingala, Swahili, Wolof</option></select><small id="language-set-note">Caribbean bridge · four voices, one daily phrase.</small></div>

    <article class="lesson-card">
        <div class="english-phrase">
            <small>English</small>
            <h3>“<?= h($lesson['english']) ?>”</h3>
        </div>
        <div class="translation-grid">
            <div class="translation translation-fr">
                <span class="flag">🇫🇷</span><small>Français</small>
                <strong><?= h($lesson['french']) ?></strong>
                <em><?= h($lesson['french_pronunciation']) ?></em>
            </div>
            <div class="translation translation-jm">
                <span class="flag">🇯🇲</span><small>Patois</small>
                <strong><?= h($lesson['patois']) ?></strong>
                <em><?= h($lesson['patois_pronunciation'] ?? '') ?></em>
            </div>
            <div class="translation translation-ht">
                <span class="flag">🇭🇹</span><small>Kreyòl</small>
                <strong><?= h($lesson['kreyol']) ?></strong>
                <em><?= h($lesson['kreyol_pronunciation'] ?? '') ?></em>
            </div>
            <div class="translation translation-es">
                <span class="flag">🇪🇸</span><small>Español</small>
                <strong><?= h($lesson['spanish']) ?></strong>
                <em><?= h($lesson['spanish_pronunciation'] ?? '') ?></em>
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
        <section class="speech-check" aria-labelledby="speech-check-title">
            <div><span class="eyebrow">SPEAK IT BACK</span><h3 id="speech-check-title">Can you match the phrase?</h3><p>Tap record, say the French phrase, and compare your speech-to-text match.</p></div>
            <button class="button secondary" id="record-phrase" type="button">● Record yourself</button>
            <div class="speech-match" id="speech-match" role="status" aria-live="polite">Match rate will appear here.</div>
        </section>
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
<script>(function(){const record=document.getElementById('record-phrase'),match=document.getElementById('speech-match');if(!record||!match)return;const target='<?= h($lesson['french']??'') ?>';const normalize=(value)=>value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9 ]/g,' ').replace(/\s+/g,' ').trim();const score=(heard)=>{const expected=normalize(target).split(' '),actual=normalize(heard).split(' ');if(!expected.length)return 0;let hits=0;expected.forEach((word,index)=>{if(actual[index]===word||actual.includes(word))hits++});return Math.round((hits/expected.length)*100)};const Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(!Recognition){record.addEventListener('click',()=>{match.textContent='Speech-to-text is not supported in this browser. Try Chrome, Edge, iOS, or Android.'});return}const recognition=new Recognition();recognition.lang='fr-FR';recognition.interimResults=false;recognition.maxAlternatives=1;recognition.onstart=()=>{record.textContent='■ Listening…';record.classList.add('recording');match.textContent='Speak the French phrase now.'};recognition.onresult=(event)=>{const heard=event.results[0][0].transcript,rate=score(heard);match.innerHTML='<strong>'+rate+'% match</strong> · “'+heard+'”'};recognition.onerror=()=>{record.textContent='● Record yourself';record.classList.remove('recording');match.textContent='We could not hear that. Try again in a quiet place.'};recognition.onend=()=>{record.textContent='● Record yourself';record.classList.remove('recording')};record.addEventListener('click',()=>{if(record.classList.contains('recording'))recognition.stop();else recognition.start()})})();</script>
<script>(function(){const select=document.getElementById('language-set'),note=document.getElementById('language-set-note');if(!select||!note)return;select.addEventListener('change',()=>{note.textContent=select.value==='core'?'Caribbean bridge · four voices, one daily phrase.':select.value==='euro'?'Europe region · Italian, German, Portuguese, and Russian tutor set.':'Africa region · Lingala, Swahili, and Wolof expansion set.'})})();</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
