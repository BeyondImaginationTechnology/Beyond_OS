<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/includes/bootstrap.php';
$signedIn = !empty($_SESSION['user_id']);
$displayName = trim((string)($_SESSION['first_name'] ?? $_SESSION['name'] ?? ''));
$csrf = csrf_token();
$scriptDirectory = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/chat.php')));
$appBasePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#08050f">
<title>Jaguar v0.5.2 | Beyond AI</title><meta name="description" content="Jaguar v0.5.2 checks the fast lane first and uses Modal GPU requests only for deeper reasoning and image creation.">
<style>
:root{color-scheme:dark;--bg:#07050d;--panel:#100b1d;--panel2:#171027;--line:rgba(210,152,255,.18);--text:#faf7ff;--muted:#a9a1b9;--purple:#b35cff;--pink:#f264cf;--green:#83efa8}*{box-sizing:border-box}html,body{height:100%;margin:0}body{overflow:hidden;color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Inter,ui-sans-serif,sans-serif;background:radial-gradient(ellipse 64% 58% at 53% 48%,rgba(113,37,177,.22),transparent 72%),radial-gradient(ellipse 42% 34% at 47% 62%,rgba(242,100,207,.1),transparent 78%),var(--bg)}button,textarea{font:inherit}.shell{height:100dvh;display:grid;grid-template-columns:260px 1fr}.sidebar{display:flex;flex-direction:column;padding:22px 17px;border-right:1px solid var(--line);background:rgba(9,5,17,.86);backdrop-filter:blur(20px)}.brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none}.brand-mark{width:40px;height:40px;border:1px solid rgba(197,111,255,.44);border-radius:13px;display:grid;place-items:center;background:linear-gradient(145deg,#4a166c,#160824);box-shadow:0 0 25px rgba(178,75,255,.22);font-size:20px;font-weight:950}.brand strong,.brand small{display:block}.brand strong{font-size:14px;letter-spacing:.04em}.brand small{margin-top:3px;color:#b6a8c7;font-size:9px;letter-spacing:.13em}.new-chat{width:100%;min-height:45px;margin-top:30px;border:1px solid var(--line);border-radius:13px;color:#f7edff;background:rgba(255,255,255,.045);cursor:pointer;font-weight:800}.new-chat:hover{border-color:var(--purple)}.sidebar-note{margin-top:24px;padding:14px;border:1px solid var(--line);border-radius:14px;color:var(--muted);font-size:11px;line-height:1.55}.sidebar-note b{display:block;margin-bottom:5px;color:#e9d7fa}.side-links{margin-top:auto}.side-links a{display:block;padding:10px;color:#aaa0b9;text-decoration:none;font-size:11px}.workspace{min-width:0;display:grid;grid-template-rows:64px 1fr}.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid var(--line);background:rgba(8,5,15,.66);backdrop-filter:blur(18px)}.model-name{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:800}.status{width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 13px var(--green)}.account{color:#b8aec7;font-size:11px}.account a{color:#fff}.chat{position:relative;min-height:0;display:grid;grid-template-rows:1fr auto}.messages{min-height:0;overflow:auto;padding:32px max(24px,calc((100% - 1040px)/2)) 18px;scroll-behavior:smooth;overscroll-behavior:contain;scrollbar-gutter:stable both-edges}.messages:focus-visible{outline:2px solid rgba(179,92,255,.7);outline-offset:-4px}.message-tools{position:absolute;right:max(18px,calc((100% - 1120px)/2));bottom:148px;z-index:3;display:flex;gap:8px;pointer-events:none}.message-tools button{min-height:34px;padding:0 12px;border:1px solid rgba(199,123,255,.38);border-radius:999px;color:#f8efff;background:rgba(25,13,42,.94);box-shadow:0 10px 28px rgba(0,0,0,.3);cursor:pointer;font-size:11px;font-weight:800;opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity .2s ease,transform .2s ease,background-color .2s ease}.message-tools button:hover,.message-tools button:focus-visible{background:rgba(179,92,255,.22)}.message-tools button.is-visible{opacity:1;transform:translateY(0);pointer-events:auto}.welcome{min-height:100%;display:grid;align-content:center;justify-items:center;text-align:center}.jaguar-eye{width:104px;height:104px;border:1px solid rgba(196,112,255,.42);border-radius:31px;background:url('assets/jaguar-model.jpg') center/cover;box-shadow:0 0 55px rgba(174,70,255,.28)}.welcome h1{margin:25px 0 9px;font-size:clamp(38px,6vw,66px);line-height:.95;letter-spacing:-.045em}.welcome h1 span{background:linear-gradient(90deg,#bc6cff,#f268cc);background-clip:text;color:transparent}.welcome>p{max-width:600px;margin:0;color:var(--muted);line-height:1.65}.suggestions{width:100%;max-width:900px;display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:30px}.suggestions button{min-height:92px;padding:15px;border:1px solid var(--line);border-radius:15px;color:#dcd2e7;background:rgba(255,255,255,.035);cursor:pointer;text-align:left;font-size:12px;line-height:1.45}.suggestions button:hover{border-color:var(--purple);background:rgba(179,92,255,.08)}.message{max-width:940px;margin:0 auto 18px;display:grid;grid-template-columns:34px 1fr;gap:13px}.avatar{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;background:#241633;color:#fff;font-size:12px;font-weight:900}.message.assistant .avatar{background:linear-gradient(145deg,#7c2fbd,#ed4fbd)}.bubble{padding-top:5px;color:#eee8f5;font-size:14px;line-height:1.7;white-space:pre-wrap}.message.user .bubble{color:#fff;font-weight:650}.composer-wrap{padding:10px max(20px,calc((100% - 1040px)/2)) max(18px,env(safe-area-inset-bottom));background:linear-gradient(transparent,rgba(7,5,13,.92) 34%)}.composer{max-width:1040px;margin:0 auto;display:grid;grid-template-columns:1fr auto;align-items:end;gap:10px;padding:11px 11px 11px 19px;border:1px solid rgba(218,169,255,.52);border-radius:22px;background:#fff;box-shadow:0 22px 58px rgba(0,0,0,.46),0 0 0 1px rgba(107,33,173,.14)}.composer:focus-within{border-color:var(--purple);box-shadow:0 0 0 4px rgba(179,92,255,.12),0 22px 58px rgba(0,0,0,.46)}textarea{width:100%;max-height:170px;resize:none;border:0;outline:0;color:#170d25;background:transparent;line-height:1.5}textarea::placeholder{color:#6d6575}.send{width:44px;height:44px;border:0;border-radius:14px;color:#fff;background:linear-gradient(145deg,#8c40dc,#ef55bd);cursor:pointer;font-size:19px}.send:disabled{opacity:.45;cursor:default}.fine{text-align:center;margin:9px 0 0;color:#8b819b;font-size:10px}.signin{min-height:100%;display:grid;place-items:center;padding:25px}.signin-card{max-width:510px;padding:38px;border:1px solid var(--line);border-radius:26px;background:rgba(17,10,29,.92);text-align:center}.signin-card h1{font-size:42px;letter-spacing:-.025em}.signin-card p{color:var(--muted);line-height:1.6}.signin-card a{display:inline-flex;min-height:50px;margin-top:13px;padding:0 22px;align-items:center;border-radius:13px;color:#fff;background:linear-gradient(100deg,#7840d5,#eb50bc);text-decoration:none;font-weight:850}@media(max-width:740px){.shell{grid-template-columns:1fr}.sidebar{display:none}.workspace{grid-template-rows:56px 1fr}.topbar{padding:0 15px}.messages{padding:22px 16px 20px}.message-tools{right:14px;bottom:155px;flex-direction:column;align-items:flex-end}.message-tools button{min-height:38px;padding-inline:14px}.suggestions{grid-template-columns:1fr}.suggestions button{min-height:60px}.welcome{align-content:start;padding-top:28px}.jaguar-eye{width:78px;height:78px}.composer-wrap{padding-bottom:max(12px,env(safe-area-inset-bottom))}}
</style><style>.product-context-picker{display:none;align-items:center;gap:8px;margin:0 0 9px;color:#a9a1b9;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.product-context-picker select{min-height:31px;max-width:260px;padding:0 28px 0 10px;border:1px solid var(--line);border-radius:9px;color:#f6efff;background:#171027;font:inherit;font-size:11px;letter-spacing:0;text-transform:none}.product-context-picker span{color:#81788e;font-size:10px;font-weight:500;letter-spacing:0;text-transform:none}.brand-mark-image{display:block;width:40px;height:40px;border:1px solid rgba(197,111,255,.54);border-radius:13px;object-fit:cover;object-position:center;box-shadow:0 0 25px rgba(178,75,255,.28)}.conversation-list{display:grid;gap:4px;max-height:calc(100dvh - 245px);margin-top:14px;overflow:auto}.conversation-list[hidden]{display:none}.conversation-empty{padding:11px 4px;color:#81788e;font-size:11px;line-height:1.45}.conversation-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:5px;align-items:center;padding:7px;border:1px solid transparent;border-radius:10px}.conversation-item:hover,.conversation-item.is-active{border-color:var(--line);background:rgba(255,255,255,.045)}.conversation-open{min-width:0;padding:0;border:0;color:#d9d0e4;background:transparent;cursor:pointer;text-align:left}.conversation-open strong,.conversation-open small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.conversation-open strong{font-size:11px}.conversation-open small{margin-top:3px;color:#8e849b;font-size:10px}.conversation-actions{display:flex;gap:2px}.conversation-actions button{width:24px;height:24px;padding:0;border:0;border-radius:7px;color:#a99eb9;background:transparent;cursor:pointer;font-size:13px}.conversation-actions button:hover{color:#fff;background:rgba(179,92,255,.2)}.usage-status{width:max-content;max-width:100%;margin:0 auto 11px;padding:8px 13px;border:1px solid rgba(131,239,168,.28);border-radius:999px;color:var(--green);background:rgba(13,54,36,.52);box-shadow:inset 0 1px rgba(255,255,255,.07),0 9px 24px rgba(0,0,0,.18);text-align:center;font-size:12px;font-weight:850;line-height:1.35}.usage-status.is-low{border-color:rgba(255,133,157,.42);color:#ff9caf;background:rgba(74,17,35,.6)}.draw-card{grid-column:2;margin:2px 0 8px;overflow:hidden;border:1px solid rgba(207,145,255,.36);border-radius:19px;background:linear-gradient(145deg,rgba(33,18,52,.94),rgba(15,9,27,.96));box-shadow:0 22px 50px rgba(0,0,0,.35)}.draw-card img{display:block;width:100%;max-height:680px;object-fit:contain;background:#08050f}.draw-card-copy{padding:14px 15px 15px}.draw-card-prompt{margin:0;color:#f2ebf7;font-size:13px;font-weight:720;line-height:1.45}.draw-card-receipt{margin:7px 0 0;color:#9eeabb;font-size:11px;font-weight:750}.draw-card-actions{display:flex;gap:8px;margin-top:13px}.draw-card-actions a,.draw-card-actions button{min-height:35px;padding:0 12px;border:1px solid rgba(209,151,255,.32);border-radius:10px;color:#f8efff;background:rgba(255,255,255,.06);font:inherit;font-size:11px;font-weight:800;line-height:33px;text-decoration:none;cursor:pointer}.draw-card-actions button{background:linear-gradient(135deg,#7828d5,#d952ca);border-color:transparent}.draw-card-actions a:hover,.draw-card-actions button:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(0,0,0,.22)}.gate{position:fixed;inset:0;z-index:20;display:grid;place-items:center;padding:22px;background:rgba(5,2,10,.84);backdrop-filter:blur(18px)}.gate[hidden]{display:none}.gate-card{width:min(480px,100%);padding:34px;border:1px solid rgba(201,125,255,.3);border-radius:25px;background:linear-gradient(145deg,rgba(29,16,48,.98),rgba(12,7,21,.98));box-shadow:0 30px 90px rgba(0,0,0,.58);text-align:center}.gate-card h2{margin:18px 0 8px;font-size:28px;letter-spacing:-.025em}.gate-card p{margin:0;color:var(--muted);line-height:1.6}.language-options{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:25px}.language-options button,.gate-cancel{min-height:48px;border:1px solid var(--line);border-radius:13px;color:#fff;background:rgba(255,255,255,.05);cursor:pointer;font-weight:800}.language-options button:hover{border-color:var(--purple);background:rgba(179,92,255,.13)}.guest-badge{margin-left:5px;padding:3px 7px;border:1px solid var(--line);border-radius:99px;color:#cfb9df;font-size:9px}.turnstile-slot{display:flex;justify-content:center;min-height:70px;margin:22px 0 10px}.verification-error{min-height:18px;color:#ff9fcf;font-size:11px}.gate-cancel{margin-top:7px;padding:0 18px}.account a{text-decoration:none}.account a:hover{text-decoration:underline}@media(max-width:740px){.conversation-list{display:none}}@media(max-width:520px){.language-options{grid-template-columns:1fr}.gate-card{padding:27px 20px}.usage-status{font-size:11px}.draw-card{grid-column:1}.draw-card-actions{flex-wrap:wrap}}</style>
<style>
@keyframes jaguarGlow{0%,100%{box-shadow:0 0 0 rgba(179,92,255,0)}50%{box-shadow:0 0 22px rgba(179,92,255,.13)}}
.suggestions button,.new-chat,.sidebar-note,.composer,.gate-card{position:relative;transition:border-color .28s ease,background-color .28s ease,box-shadow .28s ease,transform .28s ease}
.suggestions button{overflow:hidden}
.suggestions button::after{position:absolute;inset:-70% -35%;background:linear-gradient(110deg,transparent 38%,rgba(218,164,255,.12) 49%,rgba(242,100,207,.09) 52%,transparent 63%);transform:translateX(-72%) rotate(4deg);transition:transform .65s ease;pointer-events:none;content:""}
.suggestions button:hover::after,.suggestions button:focus-visible::after{transform:translateX(72%) rotate(4deg)}
.suggestions button:hover,.suggestions button:focus-visible,.new-chat:hover,.new-chat:focus-visible{border-color:rgba(211,139,255,.62);box-shadow:0 10px 30px rgba(119,45,178,.14);transform:translateY(-2px)}
.composer:focus-within{animation:jaguarGlow 2.4s ease-in-out infinite}
.side-links a{border-radius:9px;transition:color .24s ease,background-color .24s ease,transform .24s ease}
.side-links a:hover,.side-links a:focus-visible{color:#f5eaff;background:linear-gradient(90deg,rgba(179,92,255,.1),transparent);transform:translateX(3px)}
.message.assistant .avatar{transition:box-shadow .3s ease,transform .3s ease}.message.assistant:hover .avatar{box-shadow:0 0 20px rgba(224,79,194,.28);transform:scale(1.04)}
@media(prefers-reduced-motion:reduce){.suggestions button,.new-chat,.side-links a,.message.assistant .avatar{transition:none}.suggestions button::after{display:none}.composer:focus-within{animation:none}}
</style><style>.usage-status{line-height:1.4}.draw-notice{margin:0 0 9px;padding:9px 12px;border:1px solid rgba(255,211,125,.45);border-radius:11px;background:rgba(75,48,10,.38);color:#ffe2a1;font-size:11px;line-height:1.5}.draw-notice[hidden]{display:none}.draw-receipt{margin:0 0 9px;padding:9px 12px;border:1px solid rgba(131,239,168,.35);border-radius:11px;background:rgba(15,58,36,.32);color:#d7ffe4;font-size:11px;line-height:1.5}.draw-receipt[hidden]{display:none}.draw-receipt.held{border-color:rgba(255,209,122,.4);background:rgba(70,43,9,.3);color:#ffe6b2}.draw-receipt.released{border-color:var(--line);background:rgba(28,18,41,.7);color:#d8c8e4}.send{position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.48);background:linear-gradient(135deg,#7828d5 0%,#b747ed 45%,#ff71c7 100%);background-size:180% 180%;box-shadow:inset 0 1px 1px rgba(255,255,255,.58),inset 0 -7px 12px rgba(70,9,107,.26),0 6px 0 #6920a3,0 13px 24px rgba(131,43,193,.34);text-shadow:0 1px 2px rgba(41,4,70,.45);transition:transform .2s ease,box-shadow .2s ease,filter .2s ease;animation:sendColor 5s ease-in-out infinite}.send::before{content:'';position:absolute;inset:1px 4px auto;height:42%;border-radius:10px;background:linear-gradient(180deg,rgba(255,255,255,.42),rgba(255,255,255,0));pointer-events:none}.send:hover:not(:disabled),.send:focus-visible:not(:disabled){transform:translateY(-2px);filter:saturate(1.15) brightness(1.06);box-shadow:inset 0 1px 1px rgba(255,255,255,.68),inset 0 -7px 12px rgba(70,9,107,.2),0 8px 0 #6920a3,0 18px 29px rgba(131,43,193,.42)}.send:active:not(:disabled){transform:translateY(4px);box-shadow:inset 0 2px 7px rgba(70,9,107,.28),0 2px 0 #6920a3,0 7px 14px rgba(131,43,193,.28)}.send:disabled{animation:none}@keyframes sendColor{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}@media(prefers-reduced-motion:reduce){.send{animation:none}}</style>
</head><body>
<div class="shell"><aside class="sidebar"><a class="brand" href="/"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" alt="Jaguar eye logo"><span><strong>JAGUAR</strong><small>V0.5.2 · BEYOND AI</small></span></a><button class="new-chat" id="newChat" type="button">＋ New conversation</button><?php if ($signedIn): ?><div class="conversation-list" id="conversationList" aria-label="Saved conversations"><p class="conversation-empty">Loading conversations…</p></div><?php else: ?><div class="sidebar-note"><b>Guest chat</b>Conversation history is not saved in this preview. A quick security check protects guest requests.</div><?php endif; ?><nav class="side-links"><a href="https://beyondimagination.co.technology/ai/">About Jaguar</a><a href="https://beyondimagination.co.technology/release-notes.php#jaguar">Build progress</a><a href="https://beyondimagination.co.technology/">Beyond Imagination</a></nav></aside>
<section class="workspace"><header class="topbar"><div class="model-name"><i class="status"></i> Jaguar Runtime · v0.5.2 Preview</div><div class="account"><?php if ($signedIn): ?><?=e($displayName !== '' ? $displayName : 'Beyond ID')?> · signed in<?php if (in_array(strtolower((string)($_SESSION['role'] ?? '')), ['admin', 'super_admin'], true)): ?> · <a href="code.php">Code Thinking</a> · <a href="admin/training-feedback.php">Training review</a><?php endif; ?> · <a href="https://beyondimagination.co.technology/beyond-id/auth/logout.php">Sign out</a><?php else: ?><a href="https://beyondimagination.co.technology/beyond-id/auth/login.php?return=%2Fai%2Fchat.php">Sign in with Beyond ID</a><?php endif; ?></div></header>
<main class="chat"><div class="messages" id="messages" tabindex="0" role="log" aria-live="polite" aria-label="Conversation"></div><div class="message-tools" aria-label="Conversation navigation"><button id="jumpOldest" type="button" aria-label="Jump to oldest message">↑ Oldest</button><button id="jumpNewest" type="button" aria-label="Jump to newest message">↓ Newest</button></div><div class="composer-wrap"><form class="composer" id="composer"><textarea id="prompt" rows="1" maxlength="8000" placeholder="Message Jaguar…" aria-label="Message Jaguar" required></textarea><button class="send" id="send" type="submit" aria-label="Send message">↑</button></form><p class="fine" id="finePrint">Jaguar can make mistakes. Check important information. Weather and place lookups use Open-Meteo.</p></div></main></section></div>
<div class="gate" id="languageGate" role="dialog" aria-modal="true" aria-labelledby="languageTitle"><div class="gate-card"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" alt="" style="width:58px;height:58px;margin:auto"><h2 id="languageTitle">Choose your language</h2><p>Choose your language · Choisissez votre langue · Elige tu idioma</p><div class="language-options"><button type="button" data-language="en">English</button><button type="button" data-language="fr">Français</button><button type="button" data-language="es">Español</button></div></div></div>
<?php if (!$signedIn): ?><div class="gate" id="verificationGate" hidden role="dialog" aria-modal="true" aria-labelledby="verificationTitle"><div class="gate-card"><h2 id="verificationTitle">One quick check</h2><p id="verificationCopy">Verify that you are human, then Jaguar will send your message.</p><div class="turnstile-slot" id="turnstileWidget"></div><div class="verification-error" id="verificationError"></div><button class="gate-cancel" id="verificationCancel" type="button">Cancel</button></div></div><?php endif; ?>
<div class="gate" id="feedbackGate" hidden role="dialog" aria-modal="true" aria-labelledby="feedbackTitle"><div class="gate-card feedback-card"><h2 id="feedbackTitle">Suggest an improvement</h2><p>Only this selected exchange and your suggested answer will be saved for human review. It will not train the live model automatically.</p><form id="feedbackForm"><label>App scope<select id="feedbackScope"><option value="jaguar">Jaguar general</option><option value="beyond-tattoo">Beyond Tattoo stencil editor</option></select></label><label>Your suggested answer<textarea id="feedbackCorrection" maxlength="5000" rows="5" required placeholder="Write the answer Jaguar should have given."></textarea></label><label class="consent"><input id="feedbackConsent" type="checkbox" required><span>I wrote or have permission to share this correction, removed private or sensitive details, and agree to store this selected exchange for human review and possible training.</span></label><div class="feedback-error" id="feedbackError" role="status"></div><div class="feedback-actions"><button class="gate-cancel" id="feedbackCancel" type="button">Cancel</button><button class="gate-cancel feedback-submit" id="feedbackSubmit" type="submit">Submit suggestion</button></div></form></div></div>
<style>.feedback-card{text-align:left;max-height:90dvh;overflow:auto}.feedback-card>p{margin-bottom:18px}.feedback-card label{display:grid;gap:8px;margin:13px 0;color:#e7dcef;font-size:12px;font-weight:750;text-align:left}.feedback-card select,.feedback-card textarea{width:100%;padding:11px;border:1px solid var(--line);border-radius:10px;color:#fff;background:#171027;font:inherit}.feedback-card textarea{resize:vertical;line-height:1.5}.feedback-card .consent{grid-template-columns:20px 1fr;align-items:start;font-size:11px;font-weight:500;line-height:1.5}.consent input{width:17px;height:17px;margin:1px 0;accent-color:#bd62f5}.feedback-actions{display:flex;justify-content:flex-end;gap:9px}.feedback-submit{border-color:transparent;background:linear-gradient(100deg,#7840d5,#eb50bc)}.feedback-error{min-height:18px;color:#ff9fcf;font-size:11px}.feedback-trigger{grid-column:2;justify-self:start;margin-top:8px;padding:5px 0;border:0;color:#c8a5e9;background:transparent;cursor:pointer;font-size:11px}.feedback-trigger:hover{text-decoration:underline}.message.assistant{grid-template-rows:auto auto}.message.assistant .avatar{grid-row:1/3}</style>
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
    const publicMode = 'core';
    const usageStatus = document.createElement('div');
    usageStatus.id = 'usageStatus';
    usageStatus.className = 'usage-status';
    usageStatus.setAttribute('role', 'status');
    usageStatus.setAttribute('aria-live', 'polite');
    usageStatus.textContent = 'Fast lane ready · checking GPU capacity…';
    form.parentNode.insertBefore(usageStatus, form);
    const drawReceipt = document.createElement('div');
    drawReceipt.id = 'drawReceipt';
    drawReceipt.className = 'draw-receipt';
    drawReceipt.setAttribute('role', 'status');
    drawReceipt.setAttribute('aria-live', 'polite');
    drawReceipt.hidden = true;
    form.parentNode.insertBefore(drawReceipt, usageStatus);
    const drawNotice = document.createElement('div');
    drawNotice.className = 'draw-notice';
    drawNotice.setAttribute('role', 'status');
    drawNotice.hidden = true;
    form.parentNode.insertBefore(drawNotice, form);
    const feedbackGate = document.getElementById('feedbackGate');
    const feedbackForm = document.getElementById('feedbackForm');
    let feedbackExchange = null;
    const finePrint = document.getElementById('finePrint');
    const languageGate = document.getElementById('languageGate');
    const verificationGate = document.getElementById('verificationGate');
    const verificationError = document.getElementById('verificationError');
    let history = [];
    let language = 'en';
    let pendingText = '';
    let guestProof = null;
    const conversationList = document.getElementById('conversationList');
    let currentConversationId = null;
    let savedConversations = [];

    async function conversationRequest(body = null, id = null) {
        const suffix = id ? `?id=${encodeURIComponent(id)}` : '';
        const response = await fetch(`${appBasePath}/api/conversations.php${suffix}`, {
            method: body ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
            headers: body ? {'Content-Type': 'application/json', 'X-CSRF-Token': csrf} : {},
            body: body ? JSON.stringify(body) : undefined
        });
        const data = await response.json().catch(() => null);
        if (!response.ok || !data) throw new Error(data?.error || 'Saved conversations are unavailable.');
        return data;
    }

    function renderConversationList() {
        if (!conversationList) return;
        conversationList.replaceChildren();
        if (!savedConversations.length) {
            const empty = document.createElement('p');
            empty.className = 'conversation-empty';
            empty.textContent = 'Your saved conversations will appear here.';
            conversationList.append(empty);
            return;
        }
        savedConversations.forEach(conversation => {
            const row = document.createElement('article');
            row.className = `conversation-item${conversation.id === currentConversationId ? ' is-active' : ''}`;
            const open = document.createElement('button');
            open.type = 'button'; open.className = 'conversation-open';
            const title = document.createElement('strong'); title.textContent = conversation.title;
            const preview = document.createElement('small'); preview.textContent = conversation.preview || 'No messages yet';
            open.append(title, preview); open.addEventListener('click', () => loadConversation(conversation.id));
            const actions = document.createElement('div'); actions.className = 'conversation-actions';
            const rename = document.createElement('button'); rename.type = 'button'; rename.title = 'Rename conversation'; rename.textContent = '✎';
            rename.addEventListener('click', async () => {
                const nextTitle = window.prompt('Rename conversation', conversation.title);
                if (!nextTitle || !nextTitle.trim()) return;
                try { await conversationRequest({action: 'rename', id: conversation.id, title: nextTitle.trim()}); await refreshConversations(); }
                catch (error) { console.error(error); }
            });
            const remove = document.createElement('button'); remove.type = 'button'; remove.title = 'Delete conversation'; remove.textContent = '×';
            remove.addEventListener('click', async () => {
                if (!window.confirm(`Delete “${conversation.title}”?`)) return;
                try {
                    await conversationRequest({action: 'delete', id: conversation.id});
                    if (currentConversationId === conversation.id) resetConversation();
                    await refreshConversations();
                } catch (error) { console.error(error); }
            });
            actions.append(rename, remove); row.append(open, actions); conversationList.append(row);
        });
    }

    async function refreshConversations() {
        if (!signedIn || !conversationList) return;
        try { savedConversations = (await conversationRequest()).conversations || []; renderConversationList(); }
        catch (error) { conversationList.textContent = 'Saved conversations are temporarily unavailable.'; }
    }

    async function loadConversation(id) {
        if (!signedIn) return;
        try {
            const conversation = (await conversationRequest(null, id)).conversation;
            currentConversationId = conversation.id;
            history = conversation.messages.map(message => ({role: message.role, content: message.content}));
            messages.replaceChildren();
            if (!history.length) renderWelcome();
            else history.forEach(message => addMessage(message.role, message.content));
            renderConversationList();
            input.focus();
        } catch (error) { console.error(error); }
    }

    async function persistExchange(promptText, answerText) {
        if (!signedIn) return;
        if (currentConversationId === null) {
            const created = await conversationRequest({action: 'create', title: promptText, language});
            currentConversationId = created.conversation.id;
        }
        await conversationRequest({action: 'append', id: currentConversationId, messages: [
            {role: 'user', content: promptText}, {role: 'assistant', content: answerText}
        ]});
        await refreshConversations();
    }

    const isDrawPrompt = value => /^\s*(?:draw|dibuja|dessine)(?:\s|:)/iu.test(value)
        || /^\s*(?:generate|create)\s+(?:an?\s+)?image(?:\s|:)/iu.test(value)
        || /^\s*(?:génère|genere)\s+une?\s+image(?:\s|:)/iu.test(value)
        || /^\s*genera\s+una?\s+imagen(?:\s|:)/iu.test(value);

    function updateDrawNotice() {
        if (!isDrawPrompt(input.value)) { drawNotice.hidden = true; return; }
        drawNotice.textContent = signedIn
            ? 'Image request: this uses 1 Modal GPU request. Jaguar places a temporary 10 BIT$ hold and charges it only when an image is delivered.'
            : 'Image request: sign in to generate. A successful image uses 1 Modal GPU request and 10 BIT$ from your Beyond wallet.';
        drawNotice.hidden = false;
    }

    function updateUsageStatus(usage) {
        if (!usage || typeof usage !== 'object') return;
        const bitDollarsLeft = Number(usage.bit_dollars_left ?? 0.10).toFixed(2);
        const requestLimit = Number(usage.request_limit || 5);
        const wallet = Number(usage.wallet_bit_balance);
        const walletCopy = Number.isFinite(wallet) ? ` · Wallet: ${wallet.toLocaleString()} BIT$` : '';
        const requestsLeft = Math.max(0, requestLimit - Number(usage.requests || 0) - Number(usage.reserved_requests || 0));
        usageStatus.classList.toggle('is-low', requestsLeft <= 2 || Number(bitDollarsLeft) <= 0.04 || (Number.isFinite(wallet) && wallet <= 0.04));
        usageStatus.textContent = `Fast lane ready · ${requestsLeft} GPU requests left · ${bitDollarsLeft} BIT$${walletCopy}`;
    }

    function updateWalletStatus(balance) {
        const amount = Number(balance);
        if (!Number.isFinite(amount)) return;
        usageStatus.textContent = usageStatus.textContent.replace(/\s· Wallet:.*$/, '') + ` · Wallet: ${amount.toLocaleString()} BIT$`;
    }

    function updateDrawReceipt(receipt) {
        if (!signedIn || !receipt || !['held', 'charged', 'released'].includes(receipt.status)) return;
        const amount = Number(receipt.amount_bit_dollars);
        const value = Number.isFinite(amount) ? `${amount.toLocaleString()} BIT$` : 'BIT$';
        const event = receipt.status === 'charged' ? `Image generated · ${value} charged`
            : receipt.status === 'released' ? `Image unavailable · ${value} hold released · no charge`
            : `Draw processing · ${value} temporarily held`;
        const date = new Date(receipt.updated_at);
        const when = Number.isNaN(date.getTime()) ? '' : ` · ${date.toLocaleString()}`;
        const id = typeof receipt.receipt_id === 'string' ? ` · Receipt ${receipt.receipt_id}` : '';
        drawReceipt.className = `draw-receipt ${receipt.status}`;
        drawReceipt.textContent = `Last Draw: ${event}${when}${id}`;
        if (receipt.status === 'charged' && receipt.image_available === true
            && typeof receipt.receipt_id === 'string' && /^[a-f0-9]{16}$/.test(receipt.receipt_id)) {
            drawReceipt.append(document.createTextNode(' · '));
            const imageLink = document.createElement('a');
            imageLink.href = `${appBasePath}/api/draw-image.php?receipt=${encodeURIComponent(receipt.receipt_id)}`;
            imageLink.target = '_blank';
            imageLink.rel = 'noopener';
            imageLink.textContent = 'Open saved image (7 days)';
            imageLink.setAttribute('aria-label', 'Open your saved Jaguar Draw image; available for seven days');
            drawReceipt.append(imageLink);
        }
        drawReceipt.hidden = false;
    }

    function addDrawCard(bubble, prompt, imageUrl, chargedBitDollars) {
        const card = document.createElement('section');
        card.className = 'draw-card';
        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = `Jaguar Draw result for: ${prompt}`;
        image.loading = 'lazy';
        image.referrerPolicy = 'no-referrer';
        const copy = document.createElement('div');
        copy.className = 'draw-card-copy';
        const promptCopy = document.createElement('p');
        promptCopy.className = 'draw-card-prompt';
        promptCopy.textContent = prompt;
        const receipt = document.createElement('p');
        receipt.className = 'draw-card-receipt';
        const price = Number(chargedBitDollars);
        receipt.textContent = `Image delivered · 1 GPU request${Number.isFinite(price) ? ` · ${price.toLocaleString()} BIT$ charged` : ''}`;
        const actions = document.createElement('div');
        actions.className = 'draw-card-actions';
        const save = document.createElement('a');
        save.href = imageUrl;
        save.download = 'jaguar-draw.png';
        save.textContent = 'Save image';
        const variation = document.createElement('button');
        variation.type = 'button';
        variation.textContent = 'Create variation';
        variation.addEventListener('click', () => {
            input.value = `draw a variation of: ${prompt}`;
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
        actions.append(save, variation);
        copy.append(promptCopy, receipt, actions);
        card.append(image, copy);
        bubble.closest('.message')?.appendChild(card);
    }

    fetch(`${appBasePath}/api/usage.php`, {credentials: 'same-origin', cache: 'no-store'})
        .then(response => response.ok ? response.json() : null)
        .then(data => {
            if (data?.usage) { updateUsageStatus(data.usage); updateDrawReceipt(data.draw_receipt); }
            else usageStatus.textContent = 'Monthly BIT$ usage is temporarily unavailable.';
        })
        .catch(() => { usageStatus.textContent = 'Monthly BIT$ usage is temporarily unavailable.'; });

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
            placeholder: 'Message Jaguar…', fine: 'Jaguar checks the fast lane first. Questions that need deeper reasoning use a Modal GPU request. Check important information.', waking: 'Jaguar is thinking…', thinkingStages: ['Understanding your request…', 'Preparing a response…', 'Checking the result…'], timedOut: 'Jaguar is taking longer than expected. Please try again.', explicit: 'Jaguar cannot help with explicit sexual content.', user: 'YOU',
            newChat: '＋ New conversation', verifyTitle: 'One quick check', verifyCopy: 'Verify that you are human, then Jaguar will send your message.', cancel: 'Cancel'
        },
        fr: {
            title: 'Jusqu’où irons-nous <span>au-delà ?</span>',
            intro: 'Demandez à Jaguar d’expliquer une idée, de structurer un plan ou de trouver un meilleur point de départ.',
            suggestions: ['Explique les jetons IA avec une analogie mémorable.', 'Transforme mon idée en plan de projet clair.', 'Enseigne-moi un sujet difficile simplement.'],
            placeholder: 'Écrivez à Jaguar…', fine: 'Jaguar vérifie d’abord la voie rapide. Les questions qui demandent un raisonnement approfondi utilisent une requête GPU Modal. Vérifiez les informations importantes.', waking: 'Jaguar réfléchit…', thinkingStages: ['Compréhension de votre demande…', 'Préparation de la réponse…', 'Vérification du résultat…'], timedOut: 'Jaguar met plus de temps que prévu. Veuillez réessayer.', explicit: 'Jaguar ne peut pas aider avec du contenu sexuel explicite.', user: 'VOUS',
            newChat: '＋ Nouvelle conversation', verifyTitle: 'Une vérification rapide', verifyCopy: 'Confirmez que vous êtes une personne, puis Jaguar enverra votre message.', cancel: 'Annuler'
        },
        es: {
            title: '¿Hasta dónde iremos <span>más allá?</span>',
            intro: 'Pídele a Jaguar que explique una idea, organice un plan o encuentre un mejor punto de partida.',
            suggestions: ['Explica los tokens de IA con una analogía memorable.', 'Convierte mi idea en un plan de proyecto claro.', 'Enséñame algo difícil con palabras sencillas.'],
            placeholder: 'Escribe a Jaguar…', fine: 'Jaguar revisa primero la vía rápida. Las preguntas que necesitan razonamiento profundo usan una solicitud de GPU Modal. Verifica la información importante.', waking: 'Jaguar está pensando…', thinkingStages: ['Entendiendo tu solicitud…', 'Preparando una respuesta…', 'Comprobando el resultado…'], timedOut: 'Jaguar está tardando más de lo esperado. Inténtalo de nuevo.', explicit: 'Jaguar no puede ayudar con contenido sexual explícito.', user: 'TÚ',
            newChat: '＋ Nueva conversación', verifyTitle: 'Una verificación rápida', verifyCopy: 'Confirma que eres una persona y Jaguar enviará tu mensaje.', cancel: 'Cancelar'
        }
    };

    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));

    function renderWelcome() {
        const text = copy[language];
        messages.innerHTML = `<section class="welcome" id="welcome"><img class="brand-mark-image" src="assets/jaguar-eye-v0.2.png" width="40" height="40" alt="Jaguar eye logo"><h1>${text.title}</h1><p>${escapeHtml(text.intro)}</p><div class="suggestions">${text.suggestions.map(suggestion => `<button type="button">${escapeHtml(suggestion)}</button>`).join('')}</div></section>`;
        input.placeholder = text.placeholder;
        finePrint.textContent = text.fine;
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

    function attachFeedback(bubble, promptText, answerText, responseMode) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'feedback-trigger';
        button.textContent = 'Suggest an improvement';
        button.addEventListener('click', () => {
            feedbackExchange = {prompt: promptText, answer: answerText, mode: responseMode};
            document.getElementById('feedbackCorrection').value = '';
            document.getElementById('feedbackConsent').checked = false;
            document.getElementById('feedbackError').textContent = '';
            feedbackGate.hidden = false;
            document.getElementById('feedbackCorrection').focus();
        });
        bubble.closest('.message')?.appendChild(button);
    }

    function bindSuggestions() {
        messages.querySelectorAll('.suggestions button').forEach(button => button.addEventListener('click', () => {
            input.value = button.textContent;
            input.focus();
        }));
    }

    function resetConversation() {
        currentConversationId = null;
        history = [];
        renderWelcome();
        renderConversationList();
        input.focus();
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
        updateDrawNotice();
        input.style.height = 'auto';
        send.disabled = true;
        const requestMode = isDrawPrompt(text) ? 'draw' : publicMode;
        const thinking = addMessage('assistant', requestMode === 'draw' ? 'Creating image…' : 'Fast lane check…');
        const startedAt = performance.now();
        let stage = 0;
        const updateThinking = () => {
            const elapsed = Math.floor((performance.now() - startedAt) / 1000);
            const state = requestMode === 'draw' ? 'Creating image' : stage === 0 ? 'Fast lane check' : 'Reasoning';
            thinking.textContent = `${state} · ${elapsed}s\n${copy[language].thinkingStages[stage]}`;
            updateMessageTools();
        };
        const thinkingTimer = window.setInterval(() => { stage = (stage + 1) % copy[language].thinkingStages.length; updateThinking(); }, 2200);
        updateThinking();
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), requestMode === 'draw' ? 205000 : 115000);
        try {
            const requestBody = JSON.stringify({mode: requestMode, language, messages: history, proof: signedIn ? null : guestProof});
            const requestOptions = {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
                body: requestBody,
                cache: 'no-store',
                signal: controller.signal
            };
            const response = await fetch(`${appBasePath}/api/chat.php?v=20260927-1`, {...requestOptions, credentials: 'same-origin'});
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
                if (data.monthly_limit) {
                    updateUsageStatus(data.usage);
                    thinking.textContent = data.error || 'You have reached this month’s Jaguar model request allowance.';
                    return;
                }
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
            updateWalletStatus(data.wallet_bit_balance);
            updateDrawReceipt(data.draw_receipt);
            if (!response.ok) throw new Error(data.error || 'Jaguar is unavailable.');
            updateUsageStatus(data.usage);
            thinking.textContent = data.message;
            if (requestMode === 'draw' && typeof data.image_url === 'string') addDrawCard(thinking, text, data.image_url, data.charged_bit_dollars);
            history.push({role: 'assistant', content: data.message});
            attachFeedback(thinking, text, data.message, requestMode);
            persistExchange(text, data.message).catch(error => console.error('Jaguar conversation save failed.', error));
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
        updateDrawNotice();
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
    document.getElementById('verificationCancel')?.addEventListener('click', () => {
        verificationGate.hidden = true;
        pendingText = '';
        guestProof = null;
        verificationError.textContent = '';
    });
    document.getElementById('feedbackCancel').addEventListener('click', () => { feedbackGate.hidden = true; feedbackExchange = null; });
    feedbackForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!feedbackExchange) return;
        const submit = document.getElementById('feedbackSubmit');
        const error = document.getElementById('feedbackError');
        submit.disabled = true;
        error.textContent = '';
        try {
            const response = await fetch(`${appBasePath}/api/training-feedback.php`, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
                body: JSON.stringify({prompt: feedbackExchange.prompt, answer: feedbackExchange.answer, mode: feedbackExchange.mode,
                    correction: document.getElementById('feedbackCorrection').value,
                    scope: document.getElementById('feedbackScope').value,
                    consent: document.getElementById('feedbackConsent').checked})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'The suggestion could not be saved.');
            feedbackGate.hidden = true;
            feedbackExchange = null;
            addMessage('assistant', 'Thanks. Your suggestion was saved for human review; it will not change Jaguar automatically.');
        } catch (err) {
            error.textContent = err instanceof Error ? err.message : 'The suggestion could not be saved.';
        } finally { submit.disabled = false; }
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
    refreshConversations();
    const urlPrompt = new URLSearchParams(window.location.search).get('prompt');
    if (urlPrompt) { input.value = urlPrompt.slice(0, 8000); input.focus(); }
    const restoredDraft = sessionStorage.getItem('jaguar_draft');
    if (restoredDraft) { input.value = restoredDraft; sessionStorage.removeItem('jaguar_draft'); input.focus(); }
})();
</script></body></html>
