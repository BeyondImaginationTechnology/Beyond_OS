<?php
require_once __DIR__ . '/../includes/ecosystem.php';
$isGuest = empty($_SESSION['user_id']);
if ($isGuest) {
    beyond_nav_bootstrap('Beyond French', ['balance'=>0,'currency'=>'BITS','status'=>'guest']);
} else {
    beyond_app_bootstrap('Beyond French');
}
$appShell = true;
$pageTitle = 'Translate | Beyond French';
require __DIR__ . '/includes/header.php';
?>
<style>
.translate-shell{max-width:760px;margin:0 auto;padding:30px 20px 110px}.translate-shell h1{margin:7px 0;font-size:clamp(2.2rem,7vw,4.2rem);letter-spacing:-.055em;line-height:.96}.translate-shell-intro{color:var(--muted);line-height:1.55}.translate-panel{margin-top:22px;padding:18px;border:1px solid #dbe4f1;border-radius:26px;background:#fff;box-shadow:0 14px 38px rgba(7,21,47,.08)}.translate-toolbar{display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:center}.translate-toolbar select{width:100%;padding:11px;border:1px solid #d6dfec;border-radius:12px;background:#f8faff;color:var(--text)}.swap-languages{width:38px;height:38px;border:1px solid #d6dfec;border-radius:50%;background:#fff;cursor:pointer}.translate-panel textarea{width:100%;min-height:150px;margin-top:14px;padding:15px;border:1px solid #d6dfec;border-radius:17px;background:#f8faff;color:var(--text);font:inherit;resize:vertical}.translate-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.translate-actions .button{flex:1}.translate-output{margin-top:14px;padding:16px;border-radius:17px;background:#eef4ff;border:1px solid #cfddfb;min-height:100px}.translate-output small{display:block;color:var(--blue);font-weight:900;text-transform:uppercase;letter-spacing:.08em}.translate-output p{margin:9px 0 0;font-size:1.2rem;font-weight:850}.jaguar-card{margin-top:16px;padding:18px;border-radius:22px;color:#fff;background:linear-gradient(145deg,#07152f,#173d73)}.jaguar-card h2{margin:5px 0 7px;font-size:1.35rem}.jaguar-card p{margin:0;color:#cbd8ec;line-height:1.5;font-size:.9rem}.jaguar-prompts{display:flex;gap:7px;overflow:auto;margin-top:14px}.jaguar-prompts button{white-space:nowrap;padding:9px 11px;border:1px solid #ffffff2b;border-radius:999px;background:#ffffff12;color:#fff;cursor:pointer}.translate-note{margin-top:12px;color:var(--muted);font-size:.78rem;text-align:center}@media(max-width:560px){.translate-shell{padding-left:18px;padding-right:18px}.translate-toolbar{grid-template-columns:1fr}.swap-languages{justify-self:center;transform:rotate(90deg)}.translate-actions{display:grid}.translate-actions .button{width:100%}}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
</style>
<section class="translate-shell">
    <span class="eyebrow">PRACTICE · BEYOND-1</span>
    <h1>Translate with Jaguar.</h1>
    <p class="translate-shell-intro">Type or speak a phrase, choose the language bridge, and get a clear translation with pronunciation and context.</p>
    <section class="translate-panel" aria-labelledby="translate-title">
        <h2 id="translate-title" class="sr-only">Translation workspace</h2>
        <div class="translate-toolbar"><select id="translate-from" aria-label="Translate from"><option value="english">English</option><option value="french">French</option><option value="kreyol">Kreyòl</option><option value="patois">Patois</option><option value="spanish">Spanish</option></select><button class="swap-languages" id="swap-languages" type="button" aria-label="Swap languages">⇄</button><select id="translate-to" aria-label="Translate to"><option value="french">French</option><option value="english">English</option><option value="kreyol">Kreyòl</option><option value="patois">Patois</option><option value="spanish">Spanish</option></select></div>
        <textarea id="translate-input" placeholder="Type a phrase or tap the microphone…"></textarea>
        <div class="translate-actions"><button class="button secondary" id="translate-record" type="button">● Speak</button><button class="button primary" id="translate-submit" type="button">Translate →</button></div>
        <div class="translate-output" id="translate-output" role="status" aria-live="polite"><small>Jaguar output</small><p>Your translation will appear here.</p></div>
    </section>
    <section class="jaguar-card"><span class="eyebrow" style="color:#ffbf00">ASK JAGUAR</span><h2>Have a language question?</h2><p>Ask for a correction, pronunciation help, a cultural note, or a comparison across the Beyond French language bridges.</p><div class="jaguar-prompts"><button type="button" data-prompt="How do I say this politely in French?">Say it politely</button><button type="button" data-prompt="Explain the grammar in this phrase.">Explain grammar</button><button type="button" data-prompt="Compare this in all four languages.">Compare languages</button></div></section>
    <p class="translate-note">Scaffold mode: connect this workspace to the Beyond-1 Llama Jaguar translation endpoint when the model service is ready.</p>
</section>
<script>
const fromLanguage=document.getElementById('translate-from'),toLanguage=document.getElementById('translate-to'),translateInput=document.getElementById('translate-input'),translateOutput=document.getElementById('translate-output');
document.getElementById('swap-languages').addEventListener('click',()=>{const current=fromLanguage.value;fromLanguage.value=toLanguage.value;toLanguage.value=current});
document.getElementById('translate-submit').addEventListener('click',()=>{const text=translateInput.value.trim();if(!text){translateOutput.innerHTML='<small>Jaguar output</small><p>Enter or speak a phrase first.</p>';return}translateOutput.innerHTML='<small>Jaguar output</small><p>Ready to translate “'+text.replace(/[&<>]/g,'')+'” from '+fromLanguage.value+' to '+toLanguage.value+'.</p>'});
document.querySelectorAll('[data-prompt]').forEach(button=>button.addEventListener('click',()=>{translateInput.value=button.dataset.prompt;translateInput.focus()}));
const recordButton=document.getElementById('translate-record'),Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(Recognition){const recognition=new Recognition();recognition.lang='fr-FR';recognition.interimResults=false;recognition.onstart=()=>{recordButton.textContent='■ Listening…';recordButton.classList.add('recording')};recognition.onresult=(event)=>{translateInput.value=event.results[0][0].transcript};recognition.onend=()=>{recordButton.textContent='● Speak';recordButton.classList.remove('recording')};recordButton.addEventListener('click',()=>recordButton.classList.contains('recording')?recognition.stop():recognition.start())}else recordButton.addEventListener('click',()=>{translateOutput.innerHTML='<small>Microphone unavailable</small><p>Speech input is not supported in this browser.</p>'});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
