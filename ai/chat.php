<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/modes.php';
$signedIn = !empty($_SESSION['user_id']);
$displayName = trim((string)($_SESSION['first_name'] ?? $_SESSION['name'] ?? ''));
$csrf = csrf_token();
$jaguarModes = jaguar_mode_catalog();
$scriptDirectory = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/chat.php')));
$appBasePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#08050f">
<title>Jaguar v0.3 | Beyond AI</title><meta name="description" content="Jaguar v0.3 is Beyond Imagination Technology’s AI suite for Core, Build, Draw, and Video.">
<style>
:root{color-scheme:dark;--bg:#08050f;--panel:#100b1d;--panel2:#171027;--line:rgba(210,152,255,.18);--text:#faf7ff;--muted:#a9a1b9;--purple:#b35cff;--pink:#f264cf;--green:#83efa8}*{box-sizing:border-box}html,body{height:100%;margin:0}body{overflow:hidden;color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Inter,ui-sans-serif,sans-serif;background:radial-gradient(circle at 73% -10%,rgba(142,43,213,.32),transparent 32%),var(--bg)}button,textarea{font:inherit}.shell{height:100dvh;display:grid;grid-template-columns:260px 1fr}.sidebar{display:flex;flex-direction:column;padding:22px 17px;border-right:1px solid var(--line);background:rgba(9,5,17,.86);backdrop-filter:blur(20px)}.brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none}.brand-mark{width:40px;height:40px;border:1px solid rgba(197,111,255,.44);border-radius:13px;display:grid;place-items:center;background:linear-gradient(145deg,#4a166c,#160824);box-shadow:0 0 25px rgba(178,75,255,.22);font-size:20px;font-weight:950}.brand strong,.brand small{display:block}.brand strong{font-size:14px;letter-spacing:.04em}.brand small{margin-top:3px;color:#b6a8c7;font-size:9px;letter-spacing:.13em}.new-chat{width:100%;min-height:45px;margin-top:30px;border:1px solid var(--line);border-radius:13px;color:#f7edff;background:rgba(255,255,255,.045);cursor:pointer;font-weight:800}.new-chat:hover{border-color:var(--purple)}.sidebar-note{margin-top:24px;padding:14px;border:1px solid var(--line);border-radius:14px;color:var(--muted);font-size:11px;line-height:1.55}.sidebar-note b{display:block;margin-bottom:5px;color:#e9d7fa}.side-links{margin-top:auto}.side-links a{display:block;padding:10px;color:#aaa0b9;text-decoration:none;font-size:11px}.workspace{min-width:0;display:grid;grid-template-rows:64px 1fr}.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid var(--line);background:rgba(8,5,15,.66);backdrop-filter:blur(18px)}.model-name{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:800}.status{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 13px var(--green)}.account{color:#b8aec7;font-size:11px}.account a{color:#fff}.chat{position:relative;min-height:0;display:grid;grid-template-rows:1fr auto}.messages{min-height:0;overflow:auto;padding:42px max(22px,calc((100% - 820px)/2)) 25px;scroll-behavior:smooth;overscroll-behavior:contain;scrollbar-gutter:stable both-edges}.messages:focus-visible{outline:2px solid rgba(179,92,255,.7);outline-offset:-4px}.message-tools{position:absolute;right:max(18px,calc((100% - 900px)/2));bottom:132px;z-index:3;display:flex;gap:8px;pointer-events:none}.message-tools button{min-height:34px;padding:0 12px;border:1px solid rgba(199,123,255,.38);border-radius:999px;color:#f8efff;background:rgba(25,13,42,.94);box-shadow:0 10px 28px rgba(0,0,0,.3);cursor:pointer;font-size:11px;font-weight:800;opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity .2s ease,transform .2s ease,background-color .2s ease}.message-tools button:hover,.message-tools button:focus-visible{background:rgba(179,92,255,.22)}.message-tools button.is-visible{opacity:1;transform:translateY(0);pointer-events:auto}.welcome{min-height:100%;display:grid;align-content:center;justify-items:center;text-align:center}.jaguar-eye{width:104px;height:104px;border:1px solid rgba(196,112,255,.42);border-radius:31px;background:url('assets/jaguar-model.jpg') center/cover;box-shadow:0 0 55px rgba(174,70,255,.28)}.welcome h1{margin:25px 0 9px;font-size:clamp(38px,6vw,66px);line-height:.95;letter-spacing:-.045em}.welcome h1 span{background:linear-gradient(90deg,#bc6cff,#f268cc);background-clip:text;color:transparent}.welcome>p{max-width:560px;margin:0;color:var(--muted);line-height:1.65}.suggestions{width:100%;max-width:720px;display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:30px}.suggestions button{min-height:92px;padding:15px;border:1px solid var(--line);border-radius:15px;color:#dcd2e7;background:rgba(255,255,255,.035);cursor:pointer;text-align:left;font-size:12px;line-height:1.45}.suggestions button:hover{border-color:var(--purple);background:rgba(179,92,255,.08)}.message{max-width:760px;margin:0 auto 22px;display:grid;grid-template-columns:34px 1fr;gap:13px}.avatar{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;background:#241633;color:#fff;font-size:12px;font-weight:900}.message.assistant .avatar{background:linear-gradient(145deg,#7c2fbd,#ed4fbd)}.bubble{padding-top:5px;color:#eee8f5;font-size:14px;line-height:1.7;white-space:pre-wrap}.message.user .bubble{color:#fff;font-weight:650}.composer-wrap{padding:15px max(17px,calc((100% - 820px)/2)) 20px;background:linear-gradient(transparent,rgba(8,5,15,.98) 24%)}.composer{display:grid;grid-template-columns:1fr auto;align-items:end;gap:10px;padding:10px 10px 10px 17px;border:1px solid rgba(199,123,255,.3);border-radius:19px;background:rgba(20,12,34,.96);box-shadow:0 18px 55px rgba(0,0,0,.38)}.composer:focus-within{border-color:var(--purple);box-shadow:0 0 0 3px rgba(179,92,255,.09),0 18px 55px rgba(0,0,0,.38)}textarea{width:100%;max-height:170px;resize:none;border:0;outline:0;color:#fff;background:transparent;line-height:1.5}textarea::placeholder{color:#81788e}.send{width:42px;height:42px;border:0;border-radius:13px;color:#fff;background:linear-gradient(145deg,#8c40dc,#ef55bd);cursor:pointer;font-size:19px}.send:disabled{opacity:.45;cursor:default}.fine{text-align:center;margin:8px 0 0;color:#766e82;font-size:9px}.signin{min-height:100%;display:grid;place-items:center;padding:25px}.signin-card{max-width:510px;padding:38px;border:1px solid var(--line);border-radius:26px;background:rgba(17,10,29,.92);text-align:center}.signin-card h1{font-size:42px;letter-spacing:-.025em}.signin-card p{color:var(--muted);line-height:1.6}.signin-card a{display:inline-flex;min-height:50px;margin-top:13px;padding:0 22px;align-items:center;border-radius:13px;color:#fff;background:linear-gradient(100deg,#7840d5,#eb50bc);text-decoration:none;font-weight:850}@media(max-width:740px){.shell{grid-template-columns:1fr}.sidebar{display:none}.workspace{grid-template-rows:56px 1fr}.topbar{padding:0 15px}.messages{padding:25px 16px 28px}.message-tools{right:14px;bottom:142px;flex-direction:column;align-items:flex-end}.message-tools button{min-height:38px;padding-inline:14px}.suggestions{grid-template-columns:1fr}.suggestions button{min-height:60px}.welcome{align-content:start;padding-top:28px}.jaguar-eye{width:78px;height:78px}.composer-wrap{padding-bottom:max(12px,env(safe-area-inset-bottom))}}
</style><style>.mode-picker{display:flex;align-items:center;gap:8px;margin:0 0 9px;color:#a9a1b9;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.mode-picker select{min-height:31px;padding:0 28px 0 10px;border:1px solid var(--line);border-radius:9px;color:#f6efff;background:#171027;font:inherit;font-size:11px;letter-spacing:0;text-transform:none;outline:0}.mode-picker select:focus{border-color:var(--purple)}.mode-status{color:#81788e;font-size:10px;font-weight:500;letter-spacing:0;text-transform:none}.brand-mark-image{display:block;width:40px;height:40px;border:1px solid rgba(197,111,255,.54);border-radius:13px;object-fit:cover;object-position:center;box-shadow:0 0 25px rgba(178,75,255,.28)}.gate{position:fixed;inset:0;z-index:20;display:grid;place-items:center;padding:22px;background:rgba(5,2,10,.84);backdrop-filter:blur(18px)}.gate[hidden]{display:none}.gate-card{width:min(480px,100%);padding:34px;border:1px solid rgba(201,125,255,.3);border-radius:25px;background:linear-gradient(145deg,rgba(29,16,48,.98),rgba(12,7,21,.98));box-shadow:0 30px 90px rgba(0,0,0,.58);text-align:center}.gate-card h2{margin:18px 0 8px;font-size:28px;letter-spacing:-.025em}.gate-card p{margin:0;color:var(--muted);line-height:1.6}.language-options{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:25px}.language-options button,.gate-cancel{min-height:48px;border:1px solid var(--line);border-radius:13px;color:#fff;background:rgba(255,255,255,.05);cursor:pointer;font-weight:800}.language-options button:hover{border-color:var(--purple);background:rgba(179,92,255,.13)}.guest-badge{margin-left:5px;padding:3px 7px;border:1px solid var(--line);border-radius:99px;color:#cfb9df;font-size:9px}.turnstile-slot{display:flex;justify-content:center;min-height:70px;margin:22px 0 10px}.verification-error{min-height:18px;color:#ff9fcf;font-size:11px}.gate-cancel{margin-top:7px;padding:0 18px}.account a{text-decoration:none}.account a:hover{text-decoration:underline}@media(max-width:520px){.language-options{grid-template-columns:1fr}.gate-card{padding:27px 20px}}</style>
<style>
@keyframes jaguarGlow{0%,100%{box-shadow:0 0 0 rgba(179,92,255,0)}50%{box-shadow:0 0 22px rgba(179,92,255,.13)}}
.suggestions button,.new-chat,.sidebar-note,.mode-picker select,.composer,.gate-card{position:relative;transition:border-color .28s ease,background-color .28s ease,box-shadow .28s ease,transform .28s ease}
.suggestions button{overflow:hidden}
.suggestions button::after{position:absolute;inset:-70% -35%;background:linear-gradient(110deg,transparent 38%,rgba(218,164,255,.12) 49%,rgba(242,100,207,.09) 52%,transparent 63%);transform:translateX(-72%) rotate(4deg);transition:transform .65s ease;pointer-events:none;content:""}
.suggestions button:hover::after,.suggestions button:focus-visible::after{transform:translateX(72%) rotate(4deg)}
.suggestions button:hover,.suggestions button:focus-visible,.new-chat:hover,.new-chat:focus-visible,.mode-picker select:hover,.mode-picker select:focus-visible{border-color:rgba(211,139,255,.62);box-shadow:0 10px 30px rgba(119,45,178,.14);transform:translateY(-2px)}
.composer:focus-within{animation:jaguarGlow 2.4s ease-in-out infinite}
.side-links a{border-radius:9px;transition:color .24s ease,background-color .24s ease,transform .24s ease}
.side-links a:hover,.side-links a:focus-visible{color:#f5eaff;background:linear-gradient(90deg,rgba(179,92,255,.1),transparent);transform:translateX(3px)}
.message.assistant .avatar{transition:box-shadow .3s ease,transform .3s ease}.message.assistant:hover .avatar{box-shadow:0 0 20px rgba(224,79,194,.28);transform:scale(1.04)}
@media(prefers-reduced-motion:reduce){.suggestions button,.new-chat,.mode-picker select,.side-links a,.message.assistant .avatar{transition:none}.suggestions button::after{display:none}.composer:focus-within{animation:none}}
</style>
</head><body>
<div class="shell"><aside class="sidebar"><a class="brand" href="/"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" alt="Jaguar eye logo"><span><strong>JAGUAR</strong><small>V0.3 · BEYOND AI</small></span></a><button class="new-chat" id="newChat" type="button">＋ New conversation</button><div class="sidebar-note"><b>Guest chat</b>Conversation history is not saved in this preview. A quick security check protects guest requests.</div><nav class="side-links"><a href="https://beyondimagination.co.technology/ai/">About Jaguar</a><a href="https://beyondimagination.co.technology/release-notes.php#jaguar">Build progress</a><a href="https://beyondimagination.co.technology/">Beyond Imagination</a></nav></aside>
<section class="workspace"><header class="topbar"><div class="model-name"><i class="status"></i> Llama Jaguar · v0.3 Preview</div><div class="account"><?php if ($signedIn): ?><?=e($displayName !== '' ? $displayName : 'Beyond ID')?> · signed in<?php if (in_array(strtolower((string)($_SESSION['role'] ?? '')), ['admin', 'super_admin'], true)): ?> · <a href="code.php">Code Thinking</a><?php endif; ?><?php else: ?><a href="https://beyondimagination.co.technology/beyond-id/auth/login.php?return=%2Fai%2Fchat.php">Sign in with Beyond ID</a><?php endif; ?></div></header>
<main class="chat"><div class="messages" id="messages" tabindex="0" role="log" aria-live="polite" aria-label="Conversation"></div><div class="message-tools" aria-label="Conversation navigation"><button id="jumpOldest" type="button" aria-label="Jump to oldest message">↑ Oldest</button><button id="jumpNewest" type="button" aria-label="Jump to newest message">↓ Newest</button></div><div class="composer-wrap"><div class="mode-picker"><label for="modeSelect">Jaguar mode</label><select id="modeSelect" aria-label="Jaguar mode"><?php foreach ($jaguarModes as $modeKey => $modeDefinition): ?><option value="<?=e($modeKey)?>" <?=jaguar_mode_is_enabled($modeKey) ? '' : 'disabled'?>><?=e($modeDefinition['label'])?><?=jaguar_mode_is_enabled($modeKey) ? ($modeDefinition['status'] === 'preview' ? ' · preview' : '') : ' · locked'?></option><?php endforeach; ?></select><span class="mode-status" id="modeStatus">Explain is live · fast lane only</span></div><form class="composer" id="composer"><textarea id="prompt" rows="1" maxlength="8000" placeholder="Message Jaguar…" aria-label="Message Jaguar" required></textarea><button class="send" id="send" type="submit" aria-label="Send message">↑</button></form><p class="fine" id="finePrint">Jaguar can make mistakes. Check important information. Weather and place lookups use Open-Meteo.</p></div></main></section></div>
<div class="gate" id="languageGate" role="dialog" aria-modal="true" aria-labelledby="languageTitle"><div class="gate-card"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" alt="" style="width:58px;height:58px;margin:auto"><h2 id="languageTitle">Choose your language</h2><p>Choose your language · Choisissez votre langue · Elige tu idioma</p><div class="language-options"><button type="button" data-language="en">English</button><button type="button" data-language="fr">Français</button><button type="button" data-language="es">Español</button></div></div></div>
<?php if (!$signedIn): ?><div class="gate" id="verificationGate" hidden role="dialog" aria-modal="true" aria-labelledby="verificationTitle"><div class="gate-card"><h2 id="verificationTitle">One quick check</h2><p id="verificationCopy">Verify that you are human, then Jaguar will send your message.</p><div class="turnstile-slot" id="turnstileWidget"></div><div class="verification-error" id="verificationError"></div><button class="gate-cancel" id="verificationCancel" type="button">Cancel</button></div></div><?php endif; ?>
<script>
(() => {
    const signedIn = <?=json_encode($signedIn)?>;
    const csrf = <?=json_encode($csrf)?>;
    const appBasePath = <?=json_encode($appBasePath, JSON_UNESCAPED_SLASHES)?>;
    const form = document.getElementById('composer');
    const input = document.getElementById('prompt');
    const send = document.getElementById('send');
    let retryTimer = null;
    let retryUntil = 0;
    const messages = document.getElementById('messages');
    const jumpOldest = document.getElementById('jumpOldest');
    const jumpNewest = document.getElementById('jumpNewest');
    const newChat = document.getElementById('newChat');
    const modeSelect = document.getElementById('modeSelect');
    const modeStatus = document.getElementById('modeStatus');
    const finePrint = document.getElementById('finePrint');
    const languageGate = document.getElementById('languageGate');
    const verificationGate = document.getElementById('verificationGate');
    const verificationError = document.getElementById('verificationError');
    let history = [];
    let language = 'en';
    let pendingText = '';
    let guestProof = null;

    const nearBottom = () => messages.scrollHeight - messages.scrollTop - messages.clientHeight < 56;
    const updateMessageTools = () => {
        const hasOverflow = messages.scrollHeight > messages.clientHeight + 20;
        jumpOldest.classList.toggle('is-visible', hasOverflow && messages.scrollTop > 80);
        jumpNewest.classList.toggle('is-visible', hasOverflow && !nearBottom());
    };
    const jumpTo = position => {
        messages.scrollTo({top: position === 'oldest' ? 0 : messages.scrollHeight, behavior: 'smooth'});
        window.setTimeout(updateMessageTools, 260);
    };

    const copy = {
        en: {
            title: 'Where will we go <span>beyond?</span>',
            intro: 'Ask Jaguar to explain an idea, shape a plan, or help you find a stronger starting point.',
            suggestions: ['Explain AI tokens with a memorable analogy.', 'Help me turn a rough idea into a clear project plan.', 'Teach me something difficult in plain language.'],
            placeholder: 'Message Jaguar…', fine: 'Jaguar can make mistakes. Check important information. Weather and place lookups use Open-Meteo.', waking: 'Jaguar is thinking…', thinkingStages: ['Understanding your request…', 'Preparing a response…', 'Checking the result…'], timedOut: 'Jaguar is taking longer than expected. Please try again.', explicit: 'Jaguar cannot help with explicit sexual content.', coreStatus: 'Explain is live · fast lane only', buildStatus: 'Build · locked', drawStatus: 'Draw · locked', videoStatus: 'Video · locked', user: 'YOU',
            newChat: '＋ New conversation', verifyTitle: 'One quick check', verifyCopy: 'Verify that you are human, then Jaguar will send your message.', cancel: 'Cancel'
        },
        fr: {
            title: 'Jusqu’où irons-nous <span>au-delà ?</span>',
            intro: 'Demandez à Jaguar d’expliquer une idée, de structurer un plan ou de trouver un meilleur point de départ.',
            suggestions: ['Explique les jetons IA avec une analogie mémorable.', 'Transforme mon idée en plan de projet clair.', 'Enseigne-moi un sujet difficile simplement.'],
            placeholder: 'Écrivez à Jaguar…', fine: 'Jaguar peut se tromper. Vérifiez les informations importantes. Les recherches météo et de lieux utilisent Open-Meteo.', waking: 'Jaguar réfléchit…', thinkingStages: ['Compréhension de votre demande…', 'Préparation de la réponse…', 'Vérification du résultat…'], timedOut: 'Jaguar met plus de temps que prévu. Veuillez réessayer.', explicit: 'Jaguar ne peut pas aider avec du contenu sexuel explicite.', coreStatus: 'Explain est disponible · voie rapide seulement', buildStatus: 'Build · verrouillé', drawStatus: 'Dessiner · verrouillé', videoStatus: 'Vidéo · verrouillée', user: 'VOUS',
            newChat: '＋ Nouvelle conversation', verifyTitle: 'Une vérification rapide', verifyCopy: 'Confirmez que vous êtes une personne, puis Jaguar enverra votre message.', cancel: 'Annuler'
        },
        es: {
            title: '¿Hasta dónde iremos <span>más allá?</span>',
            intro: 'Pídele a Jaguar que explique una idea, organice un plan o encuentre un mejor punto de partida.',
            suggestions: ['Explica los tokens de IA con una analogía memorable.', 'Convierte mi idea en un plan de proyecto claro.', 'Enséñame algo difícil con palabras sencillas.'],
            placeholder: 'Escribe a Jaguar…', fine: 'Jaguar puede equivocarse. Verifica la información importante. Las consultas de tiempo y lugares usan Open-Meteo.', waking: 'Jaguar está pensando…', thinkingStages: ['Entendiendo tu solicitud…', 'Preparando una respuesta…', 'Comprobando el resultado…'], timedOut: 'Jaguar está tardando más de lo esperado. Inténtalo de nuevo.', explicit: 'Jaguar no puede ayudar con contenido sexual explícito.', coreStatus: 'Explain está disponible · solo vía rápida', buildStatus: 'Build · bloqueado', drawStatus: 'Dibujar · bloqueado', videoStatus: 'Video · bloqueado', user: 'TÚ',
            newChat: '＋ Nueva conversación', verifyTitle: 'Una verificación rápida', verifyCopy: 'Confirma que eres una persona y Jaguar enviará tu mensaje.', cancel: 'Cancelar'
        }
    };

    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));

    function renderWelcome() {
        const text = copy[language];
        messages.innerHTML = `<section class="welcome" id="welcome"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" width="40" height="40" alt="Jaguar eye logo"><h1>${text.title}</h1><p>${text.intro}</p><div class="suggestions">${text.suggestions.map(suggestion => `<button type="button">${escapeHtml(suggestion)}</button>`).join('')}</div></section>`;
        input.placeholder = text.placeholder;
        finePrint.textContent = text.fine;
        updateModeStatus();
        newChat.textContent = text.newChat;
        if (verificationGate) {
            document.getElementById('verificationTitle').textContent = text.verifyTitle;
            document.getElementById('verificationCopy').textContent = text.verifyCopy;
            document.getElementById('verificationCancel').textContent = text.cancel;
        }
        bindSuggestions();
    }

    function addMessage(role, text) {
        const wasNearBottom = nearBottom();
        document.getElementById('welcome')?.remove();
        const row = document.createElement('article');
        row.className = `message ${role}`;
        row.innerHTML = `<div class="avatar">${role === 'assistant' ? 'J' : copy[language].user}</div><div class="bubble">${escapeHtml(text)}</div>`;
        messages.appendChild(row);
        if (wasNearBottom) messages.scrollTop = messages.scrollHeight;
        updateMessageTools();
        return row.querySelector('.bubble');
    }

    function bindSuggestions() {
        messages.querySelectorAll('.suggestions button').forEach(button => button.addEventListener('click', () => {
            input.value = button.textContent;
            input.focus();
        }));
    }

    function resetConversation() {
        history = [];
        renderWelcome();
        input.focus();
    }

    function updateModeStatus() {
        const text = copy[language];
        modeStatus.textContent = modeSelect.value === 'build'
            ? text.buildStatus
            : modeSelect.value === 'draw'
                ? text.drawStatus
                : modeSelect.value === 'video' ? text.videoStatus : text.coreStatus;
    }

    function looksExplicit(text) {
        return /\b(porn(?:ography|ographic)?|xxx|nudes?|nudity|naked|onlyfans|blowjob|handjob|masturbat(?:e|ion|ing)|sexual\s+(?:roleplay|story|chat|scene|image|photo|video|content)|explicit(?:ly)?\s+(?:sexual|erotic)|graphic(?:ally)?\s+(?:sexual|erotic))\b/i.test(text);
    }

    async function solveProofOfWork(challenge, difficulty) {
        const requiredZeros = Math.ceil(Number(difficulty) / 4);
        for (let counter = 0; counter < 1_000_000_000; counter += 1) {
            const input = new TextEncoder().encode(`${challenge}:${counter}`);
            const digest = await crypto.subtle.digest('SHA-256', input);
            const bytes = new Uint8Array(digest);
            let valid = true;
            for (let index = 0; index < requiredZeros; index += 1) {
                const nibble = index % 2 === 0 ? bytes[Math.floor(index / 2)] >> 4 : bytes[Math.floor(index / 2)] & 0x0f;
                if (nibble !== 0) { valid = false; break; }
            }
            if (valid) return {challenge, counter: String(counter)};
            if (counter % 500 === 0) await new Promise(resolve => window.setTimeout(resolve, 0));
        }
        throw new Error('The local security check could not complete.');
    }

    async function showVerification(text) {
        pendingText = text;
        verificationError.textContent = 'Completing local security check…';
        verificationGate.hidden = false;
        try {
            const response = await fetch(`${appBasePath}/api/challenge.php?v=20260926-1`, {credentials: 'same-origin', cache: 'no-store'});
            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (error) {
                throw new Error(`The guest security check returned an unexpected ${response.status} response. Refresh the page and try again.`);
            }
            if (!response.ok || !data.challenge) throw new Error(data.error || 'The local security check is unavailable.');
            guestProof = await solveProofOfWork(data.challenge, data.difficulty);
            verificationGate.hidden = true;
            const approvedText = pendingText;
            pendingText = '';
            verificationError.textContent = '';
            await sendMessage(approvedText);
        } catch (error) {
            guestProof = null;
            verificationError.textContent = error instanceof Error ? error.message : 'The local security check failed. Please try again.';
        }
    }

    async function sendMessage(text) {
        if (!text || send.disabled) return;
        history.push({role: 'user', content: text});
        addMessage('user', text);
        input.value = '';
        input.style.height = 'auto';
        send.disabled = true;
        const thinking = addMessage('assistant', copy[language].waking);
        const startedAt = performance.now();
        let stage = 0;
        const updateThinking = () => {
            const elapsed = Math.floor((performance.now() - startedAt) / 1000);
            thinking.textContent = `${copy[language].waking} ${elapsed}s\n${copy[language].thinkingStages[stage]}`;
            updateMessageTools();
        };
        const thinkingTimer = window.setInterval(() => { stage = (stage + 1) % copy[language].thinkingStages.length; updateThinking(); }, 2200);
        updateThinking();
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 115000);
        try {
            const requestBody = JSON.stringify({mode: modeSelect.value, language, messages: history, proof: signedIn ? null : guestProof});
            const requestOptions = {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
                body: requestBody,
                cache: 'no-store',
                signal: controller.signal
            };
            const response = await fetch(`${appBasePath}/api/chat.php?v=20260926-1`, {...requestOptions, credentials: 'same-origin'});
            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (error) {
                // Some hosting/CDN paths label valid JSON as text/html. Parse the
                // body instead of retrying a one-time guest nonce and only reject
                // responses that truly are not JSON.
                throw new Error(`Jaguar API returned an unexpected ${response.status} response. Refresh the page and try again.`);
            }
            if (!response.ok && response.status === 403 && /secure session|security check/i.test(data.error || '')) {
                thinking.textContent = `${data.error || 'Your secure session expired.'}\nRefresh Jaguar and try again.`;
                input.value = text;
                input.focus();
                return;
            }
            if (!response.ok && response.status === 429) {
                const retryHeader = Number(response.headers.get('Retry-After') || 10);
                const retryAfter = Number.isFinite(retryHeader) ? Math.max(1, Math.ceil(retryHeader)) : 10;
                retryUntil = Date.now() + retryAfter * 1000;
                thinking.textContent = `${data.error || 'Jaguar is resting for a moment.'}\nTry again in ${retryAfter}s.`;
                window.clearInterval(retryTimer);
                retryTimer = window.setInterval(() => {
                    const remaining = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));
                    if (!remaining) {
                        window.clearInterval(retryTimer);
                        retryTimer = null;
                        send.disabled = false;
                        return;
                    }
                    thinking.textContent = `${data.error || 'Jaguar is resting for a moment.'}\nTry again in ${remaining}s.`;
                }, 1000);
                return;
            }
            if (!response.ok) throw new Error(data.error || 'Jaguar is unavailable.');
            thinking.textContent = data.message;
            history.push({role: 'assistant', content: data.message});
        } catch (error) {
            thinking.textContent = error instanceof DOMException && error.name === 'AbortError'
                ? copy[language].timedOut
                : error instanceof Error ? error.message : 'Jaguar is unavailable.';
        } finally {
            window.clearTimeout(timeout);
            window.clearInterval(thinkingTimer);
            if (!signedIn) guestProof = null;
            if (!retryTimer) send.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text || send.disabled) return;
        if (looksExplicit(text)) {
            addMessage('assistant', copy[language].explicit);
            return;
        }
        if (!signedIn) showVerification(text);
        else sendMessage(text);
    });
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 170)}px`;
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
    messages.addEventListener('scroll', updateMessageTools, {passive: true});
    jumpOldest.addEventListener('click', () => jumpTo('oldest'));
    jumpNewest.addEventListener('click', () => jumpTo('newest'));
    newChat.addEventListener('click', resetConversation);
    modeSelect.addEventListener('change', () => {
        updateModeStatus();
    });
    document.getElementById('verificationCancel')?.addEventListener('click', () => {
        verificationGate.hidden = true;
        pendingText = '';
        guestProof = null;
        verificationError.textContent = '';
    });
    document.querySelectorAll('[data-language]').forEach(button => button.addEventListener('click', () => {
        language = button.dataset.language;
        document.documentElement.lang = language;
        sessionStorage.setItem('jaguar_language', language);
        languageGate.hidden = true;
        renderWelcome();
        input.focus();
    }));

    const savedLanguage = sessionStorage.getItem('jaguar_language');
    if (copy[savedLanguage]) {
        language = savedLanguage;
        document.documentElement.lang = language;
        languageGate.hidden = true;
    }
    renderWelcome();
    const urlPrompt = new URLSearchParams(window.location.search).get('prompt');
    if (urlPrompt) { input.value = urlPrompt.slice(0, 8000); input.focus(); }
    const restoredDraft = sessionStorage.getItem('jaguar_draft');
    if (restoredDraft) { input.value = restoredDraft; sessionStorage.removeItem('jaguar_draft'); input.focus(); }
})();
</script></body></html>






