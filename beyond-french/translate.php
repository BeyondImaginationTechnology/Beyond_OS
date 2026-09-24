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
$dictionaryWords = json_decode((string)@file_get_contents(__DIR__ . '/data/dictionary.json'), true) ?: [];
$multilingualBank = json_decode((string)@file_get_contents(__DIR__ . '/data/multilingual-bank.json'), true) ?: [];
$africaDialectBank = json_decode((string)@file_get_contents(__DIR__ . '/data/africa-meta-dialects.json'), true) ?: [];
foreach ($multilingualBank as $phrase) {
    $dictionaryWords[] = [
        'english' => $phrase['english'] ?? '',
        'french' => $phrase['french'] ?? '',
        'pronunciation' => $phrase['french_pronunciation'] ?? '',
        'italian' => $phrase['italian'] ?? '',
        'german' => $phrase['german'] ?? '',
        'portuguese' => $phrase['portuguese'] ?? '',
        'russian' => $phrase['russian'] ?? '',
        'type' => 'multilingual',
    ];
}
require __DIR__ . '/includes/header.php';
?>
<style>
.translate-shell{max-width:760px;margin:0 auto;padding:30px 20px 110px}.translate-shell h1{margin:7px 0;font-size:clamp(2.2rem,7vw,4.2rem);letter-spacing:-.055em;line-height:.96}.translate-shell-intro{color:var(--muted);line-height:1.55}.translate-panel{margin-top:22px;padding:18px;border:1px solid #dbe4f1;border-radius:26px;background:#fff;box-shadow:0 14px 38px rgba(7,21,47,.08)}.translate-toolbar{display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:center}.translate-toolbar select{width:100%;padding:11px;border:1px solid #d6dfec;border-radius:12px;background:#f8faff;color:var(--text)}.swap-languages{width:38px;height:38px;border:1px solid #d6dfec;border-radius:50%;background:#fff;cursor:pointer}.translate-panel textarea{width:100%;min-height:150px;margin-top:14px;padding:15px;border:1px solid #d6dfec;border-radius:17px;background:#f8faff;color:var(--text);font:inherit;resize:vertical}.translate-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.translate-actions .button{flex:1}.translate-output{margin-top:14px;padding:16px;border-radius:17px;background:#eef4ff;border:1px solid #cfddfb;min-height:100px}.translate-output small{display:block;color:var(--blue);font-weight:900;text-transform:uppercase;letter-spacing:.08em}.translate-output p{margin:9px 0 0;font-size:1.2rem;font-weight:850}.jaguar-card{margin-top:16px;padding:18px;border-radius:22px;color:#fff;background:linear-gradient(145deg,#07152f,#173d73)}.jaguar-card h2{margin:5px 0 7px;font-size:1.35rem}.jaguar-card p{margin:0;color:#cbd8ec;line-height:1.5;font-size:.9rem}.jaguar-prompts{display:flex;gap:7px;overflow:auto;margin-top:14px}.jaguar-prompts button{white-space:nowrap;padding:9px 11px;border:1px solid #ffffff2b;border-radius:999px;background:#ffffff12;color:#fff;cursor:pointer}.translate-note{margin-top:12px;color:var(--muted);font-size:.78rem;text-align:center}@media(max-width:560px){.translate-shell{padding-left:18px;padding-right:18px}.translate-toolbar{grid-template-columns:1fr}.swap-languages{justify-self:center;transform:rotate(90deg)}.translate-actions{display:grid}.translate-actions .button{width:100%}}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
</style>
<style>.jaguar-guide{display:grid;grid-template-columns:112px 1fr;gap:16px;align-items:center}.jaguar-guide img{width:112px;height:112px;object-fit:cover;border-radius:22px;border:1px solid #5c99ff66}@media(max-width:560px){.jaguar-guide{grid-template-columns:76px 1fr}.jaguar-guide img{width:76px;height:76px}}</style>
<style>
body.app-shell .translate-shell{color:#fff}
body.app-shell .translate-shell-intro{color:#b7c8e3}
body.app-shell .translate-panel{border-color:#2f68c6;background:linear-gradient(145deg,#102e64,#173d73);box-shadow:0 20px 46px rgba(0,0,0,.28)}
body.app-shell .translate-toolbar select{border-color:#467bc9;background:#0d2249;color:#fff}
body.app-shell .translate-toolbar select option{background:#0d2249;color:#fff}
body.app-shell .swap-languages{border-color:#467bc9;background:#173d73;color:#fff}
body.app-shell .translate-panel textarea{border-color:#467bc9;background:#0d2249;color:#fff}
body.app-shell .translate-panel textarea::placeholder{color:#9fb5d7}
body.app-shell .translate-actions .secondary{border:1px solid #467bc9;background:#173d73;color:#fff;box-shadow:none}
body.app-shell .translate-actions .primary{background:#1768ff;color:#fff}
body.app-shell .translate-output{background:#0d2249;border-color:#2b5ba2}
body.app-shell .translate-output small{color:#8fc0ff}
body.app-shell .translate-output p{color:#fff}
body.app-shell .translate-note{color:#a9c6e9}
</style>
<style>
body.app-shell .translate-modes{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:14px;padding:4px;border:1px solid #2b5ba2;border-radius:15px;background:#0d2249}
body.app-shell .translate-modes button{border:0;border-radius:11px;padding:10px 8px;background:transparent;color:#a9c6e9;font-size:.78rem;font-weight:900;cursor:pointer}
body.app-shell .translate-modes button.active{background:#1768ff;color:#fff;box-shadow:0 5px 14px rgba(23,104,255,.3)}
@media(max-width:560px){body.app-shell .translate-modes button{font-size:.7rem;padding:9px 5px}}
</style>
<section class="translate-shell">
    <span class="eyebrow">PRACTICE · BEYOND-1</span>
    <h1>Translate with Beyond-1 Llama Jaguar.</h1>
    <p class="translate-shell-intro">Type or speak a phrase, choose the language bridge, and get a clear translation with pronunciation and context.</p>
    <section class="translate-panel" aria-labelledby="translate-title">
        <h2 id="translate-title" class="sr-only">Translation workspace</h2>
        <div class="translate-modes" role="tablist" aria-label="Practice mode"><button class="active" type="button" role="tab" aria-selected="true" data-mode="translate">Translate</button><button type="button" role="tab" aria-selected="false" data-mode="dictionary">Dictionary</button><button type="button" role="tab" aria-selected="false" data-mode="question">Ask a question</button></div>
        <div class="translate-toolbar"><select id="translate-from" aria-label="Translate from"><option value="english">English</option><option value="french">French</option><option value="spanish">Spanish</option><option value="kreyol">Haitian Kreyòl</option><option value="patois">Jamaican Patois</option><option value="italian">Italian</option><option value="german">German</option><option value="portuguese">Portuguese</option><option value="russian">Russian</option><option value="lingala">Lingala</option><option value="swahili">Swahili</option><option value="arabic">Arabic</option></select><button class="swap-languages" id="swap-languages" type="button" aria-label="Swap languages">⇄</button><select id="translate-to" aria-label="Translate to"><option value="french">French</option><option value="english">English</option><option value="spanish">Spanish</option><option value="kreyol">Haitian Kreyòl</option><option value="patois">Jamaican Patois</option><option value="italian">Italian</option><option value="german">German</option><option value="portuguese">Portuguese</option><option value="russian">Russian</option><option value="lingala">Lingala</option><option value="swahili">Swahili</option><option value="arabic">Arabic</option></select></div>
        <textarea id="translate-input" placeholder="Type a phrase or tap the microphone…"></textarea>
        <div class="translate-actions"><button class="button secondary" id="translate-record" type="button">● Speak</button><button class="button primary" id="translate-submit" type="button">Translate →</button></div>
        <div class="translate-output" id="translate-output" role="status" aria-live="polite"><small>Beyond-1 Llama Jaguar output</small><p>Your translation will appear here.</p></div>
    </section>
    <section class="jaguar-card"><div class="jaguar-guide"><img src="<?= h($frenchBase) ?>assets/images/jaguar/beyond-1-llama-jaguar.png" alt="Beyond-1 Llama Jaguar language guide"><div><span class="eyebrow" style="color:#ffbf00">BEYOND-1 LLAMA JAGUAR</span><h2>Have a language question?</h2><p>Ask Jaguar for a correction, pronunciation help, cultural note, or comparison across the Beyond French language bridges.</p></div></div><div class="jaguar-prompts"><button type="button" data-prompt="How do I say this politely in French?">Say it politely</button><button type="button" data-prompt="Explain the grammar in this phrase.">Explain grammar</button><button type="button" data-prompt="Compare this in all four languages.">Compare languages</button></div></section>
    <p class="translate-note">Free mode is active: dictionary matching and speech-to-text work without GPU or paid model usage. Beyond-1 Llama Jaguar inference is optional.</p>
</section>
<script>
(()=>{const from=document.getElementById('translate-from'),to=document.getElementById('translate-to'),input=document.getElementById('translate-input'),output=document.getElementById('translate-output'),submit=document.getElementById('translate-submit'),record=document.getElementById('translate-record'),words=<?= json_encode($dictionaryWords, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;let mode='translate';const locales={english:'en-US',french:'fr-FR',kreyol:'ht-HT',patois:'en-JM',spanish:'es-ES'},esc=value=>String(value??'').replace(/[&<>]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[char]));const setMode=next=>{mode=next;document.querySelectorAll('[data-mode]').forEach(button=>{const active=button.dataset.mode===mode;button.classList.toggle('active',active);button.setAttribute('aria-selected',active?'true':'false')});const copy={translate:['Translate →','Type a phrase or tap the microphone…'],dictionary:['Look up word →','Search the imported iOS dictionary or speak a word…'],question:['Ask Beyond-1 Llama Jaguar →','Ask a grammar, culture, or language question…']}[mode];submit.textContent=copy[0];input.placeholder=copy[1]};document.querySelectorAll('[data-mode]').forEach(button=>button.addEventListener('click',()=>setMode(button.dataset.mode)));submit.addEventListener('click',async event=>{if(mode==='translate')return;event.stopImmediatePropagation();const text=input.value.trim();if(!text){output.innerHTML='<small>Beyond-1 Llama Jaguar</small><p>Enter or speak something first.</p>';return}if(mode==='dictionary'){const q=text.toLocaleLowerCase(),matches=words.filter(word=>[word.english,word.french,word.spanish,word.kreyol,word.patois,word.type].some(value=>String(value||'').toLocaleLowerCase().includes(q))).slice(0,8);output.innerHTML=matches.length?'<small>Dictionary · '+matches.length+' result'+(matches.length===1?'':'s')+'</small>'+matches.map(word=>'<p><strong>'+esc(word.english)+'</strong> → '+esc(word.french)+' <em>('+esc(word.pronunciation||'')+')</em></p>').join(''):'<small>Dictionary</small><p>No matching entry yet. Try a word or phrase from the iOS dictionary.</p>';return}output.innerHTML='<small>Beyond-1 Llama Jaguar</small><p>Connecting to Beyond-1 Llama Jaguar…</p>';try{const response=await fetch('api/jaguar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({input:text,from:from.value,to:to.value,mode})}),data=await response.json();output.innerHTML='<small>Beyond-1 Llama Jaguar</small><p>'+esc(data.message||data.translation||'Beyond-1 Llama Jaguar response received.')+'</p>'}catch(error){output.innerHTML='<small>Beyond-1 Llama Jaguar unavailable</small><p>Check the model connection and try again.</p>'}},true);const Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(Recognition){const recognition=new Recognition();recognition.continuous=false;recognition.interimResults=true;record.addEventListener('click',event=>{event.stopImmediatePropagation();if(record.classList.contains('recording')){recognition.stop();return}recognition.lang=locales[from.value]||'fr-FR';recognition.start()},true);recognition.onstart=()=>{record.textContent='■ Listening…';record.classList.add('recording');output.innerHTML='<small>Speech to text</small><p>Listening… review the transcription when you finish.</p>'};recognition.onresult=event=>{let transcript='';for(let index=0;index<event.results.length;index++)transcript+=event.results[index][0].transcript+' ';input.value=transcript.trim()};recognition.onerror=()=>{output.innerHTML='<small>Speech to text</small><p>We could not hear that clearly. Try again.</p>'};recognition.onend=()=>{record.textContent='● Speak';record.classList.remove('recording');if(input.value.trim())output.innerHTML='<small>Speech to text ready</small><p>Review the transcription, then submit it.</p>'}}})();
const fromLanguage=document.getElementById('translate-from'),toLanguage=document.getElementById('translate-to'),translateInput=document.getElementById('translate-input'),translateOutput=document.getElementById('translate-output');
document.getElementById('swap-languages').addEventListener('click',()=>{const current=fromLanguage.value;fromLanguage.value=toLanguage.value;toLanguage.value=current});
document.getElementById('translate-submit').addEventListener('click',async()=>{const text=translateInput.value.trim();if(!text){translateOutput.innerHTML='<small>Jaguar output</small><p>Enter or speak a phrase first.</p>';return}translateOutput.innerHTML='<small>Beyond-1 Llama Jaguar</small><p>Connecting to Jaguar…</p>';try{const response=await fetch('api/jaguar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({input:text,from:fromLanguage.value,to:toLanguage.value})});const data=await response.json();translateOutput.innerHTML='<small>Beyond-1 Llama Jaguar</small><p>'+String(data.message||data.translation||'Jaguar response received.').replace(/[&<>]/g,'')+'</p>'}catch(error){translateOutput.innerHTML='<small>Jaguar unavailable</small><p>Check the model connection and try again.</p>'}});
document.querySelectorAll('[data-prompt]').forEach(button=>button.addEventListener('click',()=>{translateInput.value=button.dataset.prompt;translateInput.focus()}));
const recordButton=document.getElementById('translate-record'),Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(Recognition){const recognition=new Recognition();recognition.lang='fr-FR';recognition.interimResults=false;recognition.onstart=()=>{recordButton.textContent='■ Listening…';recordButton.classList.add('recording')};recognition.onresult=(event)=>{translateInput.value=event.results[0][0].transcript};recognition.onend=()=>{recordButton.textContent='● Speak';recordButton.classList.remove('recording')};recordButton.addEventListener('click',()=>recordButton.classList.contains('recording')?recognition.stop():recognition.start())}else recordButton.addEventListener('click',()=>{translateOutput.innerHTML='<small>Microphone unavailable</small><p>Speech input is not supported in this browser.</p>'});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
