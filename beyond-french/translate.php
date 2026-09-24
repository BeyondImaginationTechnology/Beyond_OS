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
body.app-shell .dictionary-browser{margin-top:14px;padding:14px;border:1px solid #2b5ba2;border-radius:18px;background:#0d2249}
body.app-shell .dictionary-browser[hidden]{display:none}
body.app-shell .dictionary-browser-header{display:flex;justify-content:space-between;gap:12px;align-items:center;color:#fff;font-size:.85rem}
body.app-shell .dictionary-browser-header span{color:#9fb5d7;font-size:.75rem}
body.app-shell .dictionary-list{display:grid;gap:8px;margin-top:10px}
body.app-shell .dictionary-entry{padding:11px 12px;border:1px solid #467bc9;border-radius:14px;background:#123c7a}
body.app-shell .dictionary-entry strong{display:block;color:#fff;font-size:1rem}
body.app-shell .dictionary-entry .dictionary-french{display:block;margin-top:3px;color:#dce9ff;font-weight:800}
body.app-shell .dictionary-entry em{display:block;margin-top:3px;color:#a9c6e9;font-size:.78rem}
body.app-shell .dictionary-entry small{display:block;margin-top:6px;color:#b9d0f5}
body.app-shell .translate-shell{width:min(820px,calc(100% - 32px));padding:32px 0 112px}
body.app-shell .translate-shell>.eyebrow{color:#ffbf00;font-size:.7rem}
body.app-shell .translate-shell h1{max-width:680px;margin:9px 0;font-size:clamp(2.3rem,7vw,4rem);line-height:1.02;letter-spacing:-.05em}
body.app-shell .translate-shell-intro{margin:0;color:#b9c9df;font-size:1rem}
body.app-shell .translate-panel{margin-top:24px;padding:22px;border:1px solid #344968;border-radius:26px;background:linear-gradient(160deg,#10213d,#0b1830);box-shadow:0 24px 60px #0004}
body.app-shell .translate-modes{grid-template-columns:repeat(3,1fr);gap:7px;margin:-2px -2px 20px;padding:5px;border-color:#304663;border-radius:16px;background:#08152b}
body.app-shell .translate-modes button{min-height:42px;color:#b8c8df;font-size:.86rem}
body.app-shell .translate-modes button.active{background:#ffbf00;color:#101b2d;box-shadow:0 5px 16px #ffbf0033}
body.app-shell .translate-toolbar{grid-template-columns:minmax(0,1fr) 44px minmax(0,1fr);gap:10px}
body.app-shell .language-choice{display:grid;gap:7px;min-width:0;padding:11px 13px;border:1px solid #344968;border-radius:15px;background:#0b1930}
body.app-shell .language-choice>span,.translate-input-label{color:#9eb2ce;font-size:.65rem;font-weight:900;letter-spacing:.12em}
body.app-shell .translate-toolbar .language-choice select{width:100%;padding:0;border:0;background:transparent;font-size:1rem;font-weight:800}
body.app-shell .swap-languages{width:42px;height:42px;border-color:#5b512b;background:#292619;color:#ffcf45;font-size:1.25rem}
body.app-shell .translate-input-label{display:block;margin:20px 0 8px}
body.app-shell .translate-panel textarea{min-height:160px;margin:0;padding:17px;border-color:#344968;border-radius:16px;background:#08152b;font-size:1.06rem;line-height:1.55;resize:vertical}
body.app-shell .translate-panel textarea:focus{border-color:#ffbf00;outline:3px solid #ffbf0025}
body.app-shell .translate-actions{display:grid;grid-template-columns:auto minmax(0,1fr);gap:10px;margin-top:13px}
body.app-shell .translate-actions .button{min-height:50px;flex:initial}
body.app-shell .translate-actions .secondary{border-color:#344968;background:#172944;color:#e4ecf7}
body.app-shell .translate-actions .primary{background:#ffbf00;color:#101b2d;font-weight:950}
body.app-shell .translate-actions .primary:hover{background:#ffd34d}
body.app-shell .translate-output{min-height:120px;margin-top:18px;padding:16px 18px;border-color:#435a76;border-radius:17px;background:#14243e}
body.app-shell .translate-output-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
body.app-shell .translate-output-head small{color:#ffcf45;font-size:.65rem;letter-spacing:.12em}
body.app-shell .translate-output-head button{padding:7px 11px;border:1px solid #536782;border-radius:10px;background:#1b2f4b;color:#fff;font-size:.75rem;font-weight:800;cursor:pointer}
body.app-shell .translate-output-head button:disabled{opacity:.4;cursor:default}
body.app-shell .translate-output p{margin:18px 0 2px;color:#c7d5e8;font-size:1rem;font-weight:500;line-height:1.55}
body.app-shell .translate-output p strong{color:#fff;font-size:1.35rem;font-weight:850}
body.app-shell .translate-output p em{color:#ffcf45;font-size:.92rem}
body.app-shell .jaguar-card{padding:0;border:1px solid #304663;background:#0d1c36;box-shadow:none}
body.app-shell .jaguar-card>summary{padding:16px 18px;color:#ffcf45;font-weight:850;cursor:pointer;list-style:none}
body.app-shell .jaguar-card>summary::-webkit-details-marker{display:none}
body.app-shell .jaguar-card>summary::after{content:'＋';float:right;color:#ffbf00}
body.app-shell .jaguar-card[open]>summary::after{content:'−'}
body.app-shell .jaguar-guide{padding:0 18px 12px;grid-template-columns:80px 1fr}
body.app-shell .jaguar-guide img{width:80px;height:80px}
body.app-shell .jaguar-guide h2{margin:4px 0;font-size:1.1rem}
body.app-shell .jaguar-guide p{font-size:.82rem}
body.app-shell .jaguar-prompts{padding:0 18px 17px}
body.app-shell .jaguar-prompts button{border-color:#435674;background:#14243e;color:#e6eef9}
body.app-shell .translate-note{max-width:650px;margin:12px auto 0;font-size:.72rem;line-height:1.5}
@media(max-width:560px){body.app-shell .translate-shell{width:min(100% - 24px,820px);padding-top:26px}body.app-shell .translate-panel{padding:15px;border-radius:21px}body.app-shell .translate-toolbar{grid-template-columns:minmax(0,1fr) 36px minmax(0,1fr);gap:6px}body.app-shell .language-choice{padding:10px 8px}body.app-shell .translate-toolbar .language-choice select{font-size:.84rem}body.app-shell .swap-languages{width:36px;height:36px}body.app-shell .translate-panel textarea{min-height:140px}body.app-shell .translate-actions{grid-template-columns:1fr 1.5fr}body.app-shell .translate-actions .button{width:100%;padding:12px 10px;font-size:.88rem}body.app-shell .jaguar-guide{grid-template-columns:58px 1fr;gap:12px}body.app-shell .jaguar-guide img{width:58px;height:58px;border-radius:15px}body.app-shell .jaguar-prompts{flex-wrap:wrap}}
</style>
<section class="translate-shell">
    <span class="eyebrow">BEYOND FRENCH · TRANSLATOR</span>
    <h1>Say it in another language.</h1>
    <p class="translate-shell-intro">Translate a phrase, look up a word, or ask Jaguar for help.</p>
    <section class="translate-panel" aria-labelledby="translate-title">
        <h2 id="translate-title" class="sr-only">Translation workspace</h2>
        <div class="translate-modes" role="tablist" aria-label="Practice mode"><button class="active" type="button" role="tab" aria-selected="true" data-mode="translate">Translate</button><button type="button" role="tab" aria-selected="false" data-mode="dictionary">Dictionary</button><button type="button" role="tab" aria-selected="false" data-mode="question">Ask</button></div>
        <div class="translate-toolbar"><label class="language-choice"><span>FROM</span><select id="translate-from" aria-label="Translate from"><option value="english">English</option><option value="french">French</option><option value="spanish">Spanish</option><option value="kreyol">Haitian Kreyòl</option><option value="patois">Jamaican Patois</option><option value="italian">Italian</option><option value="german">German</option><option value="portuguese">Portuguese</option><option value="russian">Russian</option><option value="lingala">Lingala</option><option value="swahili">Swahili</option><option value="arabic">Arabic</option></select></label><button class="swap-languages" id="swap-languages" type="button" aria-label="Swap languages">⇄</button><label class="language-choice"><span>TO</span><select id="translate-to" aria-label="Translate to"><option value="french">French</option><option value="english">English</option><option value="spanish">Spanish</option><option value="kreyol">Haitian Kreyòl</option><option value="patois">Jamaican Patois</option><option value="italian">Italian</option><option value="german">German</option><option value="portuguese">Portuguese</option><option value="russian">Russian</option><option value="lingala">Lingala</option><option value="swahili">Swahili</option><option value="arabic">Arabic</option></select></label></div>
        <label class="translate-input-label" for="translate-input">YOUR PHRASE</label>
        <textarea id="translate-input" placeholder="Type or paste a phrase here…"></textarea>
        <div class="translate-actions"><button class="button secondary" id="translate-record" type="button"><span aria-hidden="true">🎙</span> Speak</button><button class="button primary" id="translate-submit" type="button">Translate <span aria-hidden="true">→</span></button></div>
        <div class="translate-output" id="translate-output" role="status" aria-live="polite"><div class="translate-output-head"><small>TRANSLATION</small><button type="button" id="translate-copy" aria-label="Copy translation" disabled>Copy</button></div><p>Your translation will appear here.</p></div>
        <div class="dictionary-browser" id="dictionary-browser" hidden aria-live="polite"><div class="dictionary-browser-header"><strong>Vocabulary from the iOS dictionary</strong><span id="dictionary-count"></span></div><div class="dictionary-list" id="dictionary-list"></div></div>
    </section>
    <details class="jaguar-card"><summary>Need help? Ask Beyond-1 Llama Jaguar</summary><div class="jaguar-guide"><img src="<?= h($frenchBase) ?>assets/images/jaguar/beyond-1-llama-jaguar.png" alt="Beyond-1 Llama Jaguar language guide"><div><h2>Your language guide</h2><p>Get a correction, pronunciation tip, cultural note, or comparison across languages.</p></div></div><div class="jaguar-prompts"><button type="button" data-prompt="How do I say this politely in French?">Say it politely</button><button type="button" data-prompt="Explain the grammar in this phrase.">Explain grammar</button><button type="button" data-prompt="Compare this in all four languages.">Compare languages</button></div></details>
    <p class="translate-note">Dictionary lookup and speech input are available in free mode. Jaguar answers need the language service to be online.</p>
</section>
<script>
(()=>{
    const from=document.getElementById('translate-from'),to=document.getElementById('translate-to'),input=document.getElementById('translate-input'),output=document.getElementById('translate-output'),submit=document.getElementById('translate-submit'),record=document.getElementById('translate-record'),toolbar=document.querySelector('.translate-toolbar'),browser=document.getElementById('dictionary-browser'),list=document.getElementById('dictionary-list'),count=document.getElementById('dictionary-count'),words=<?= json_encode($dictionaryWords, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const showOutput=(label,content,copyable=false)=>{output.innerHTML='<div class="translate-output-head"><small>'+label+'</small><button type="button" id="translate-copy" aria-label="Copy translation" '+(copyable?'':'disabled')+'>Copy</button></div><p>'+content+'</p>'};
    output.addEventListener('click',async event=>{if(event.target.closest('#translate-copy')){const text=output.querySelector('p')?.innerText||'';try{await navigator.clipboard.writeText(text);event.target.closest('button').textContent='Copied'}catch(error){event.target.closest('button').textContent='Select and copy'}}});
    let mode='translate';
    const locales={english:'en-US',french:'fr-FR',spanish:'es-ES',kreyol:'ht-HT',patois:'en-JM',italian:'it-IT',german:'de-DE',portuguese:'pt-PT',russian:'ru-RU',lingala:'ln-CD',swahili:'sw-KE',arabic:'ar-SA'};
    const labels={english:'English',french:'French',spanish:'Spanish',kreyol:'Kreyòl',patois:'Patois',italian:'Italian',german:'German',portuguese:'Portuguese',russian:'Russian',lingala:'Lingala',swahili:'Swahili',arabic:'Arabic'};
    const fields=Object.keys(labels),esc=value=>String(value??'').replace(/[&<>]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[char]));
    const matchesFor=query=>{const needle=String(query||'').trim().toLocaleLowerCase();return words.filter(word=>!needle||fields.some(field=>String(word[field]||'').toLocaleLowerCase().includes(needle))).slice(0,24)};
    const renderDictionary=query=>{const matches=matchesFor(query);browser.hidden=false;count.textContent=matches.length+' shown';list.innerHTML=matches.length?matches.map(word=>{const lines=fields.filter(field=>word[field]).map(field=>'<small>'+esc(labels[field])+': '+esc(word[field])+'</small>').join('');return '<article class="dictionary-entry"><strong>'+esc(word.english||word.french||'Vocabulary')+'</strong><span class="dictionary-french">'+esc(word.french||'')+'</span><em>'+esc(word.pronunciation||word.french_pronunciation||'')+'</em>'+lines+'</article>'}).join(''):'<p>No matching entry yet. Try another word or phrase.</p>'};
    const setMode=next=>{mode=next;document.querySelectorAll('[data-mode]').forEach(button=>{const active=button.dataset.mode===mode;button.classList.toggle('active',active);button.setAttribute('aria-selected',active?'true':'false')});const copy={translate:['Translate →','Type a phrase or tap the microphone…'],dictionary:['Look up word →','Search the imported iOS dictionary or speak a word…'],question:['Ask','Ask a grammar, culture, or language question…']}[mode];submit.textContent=copy[0];input.placeholder=copy[1];if(toolbar)toolbar.hidden=mode==='question';if(browser)browser.hidden=mode!=='dictionary';if(mode==='dictionary')renderDictionary(input.value)};
    document.querySelectorAll('[data-mode]').forEach(button=>button.addEventListener('click',()=>setMode(button.dataset.mode)));
    document.getElementById('swap-languages').addEventListener('click',()=>{const current=from.value;from.value=to.value;to.value=current});
    submit.addEventListener('click',async()=>{const text=input.value.trim();if(!text){showOutput('READY WHEN YOU ARE','Enter or speak something first.');if(mode==='dictionary')renderDictionary('');return}if(mode==='dictionary'){renderDictionary(text);showOutput('DICTIONARY','Showing matches from the imported iOS vocabulary.');return}showOutput('JAGUAR','Connecting to Beyond-1 Llama Jaguar…');try{const response=await fetch('api/jaguar.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({input:text,from:from.value,to:to.value,mode})}),data=await response.json();if(!response.ok)throw new Error(data.message||'Request failed');const translated=data.translation?'<strong>'+esc(data.translation)+'</strong>'+'<br><em>'+esc(data.pronunciation||'')+'</em>':'';showOutput('TRANSLATION',translated+(translated?'<br>':'')+esc(data.message||'Beyond-1 Llama Jaguar response received.'),!!data.translation)}catch(error){showOutput('JAGUAR UNAVAILABLE',esc(error.message||'Check the model connection and try again.'))}});
    input.addEventListener('input',()=>{if(mode==='dictionary')renderDictionary(input.value)});
    document.querySelectorAll('[data-prompt]').forEach(button=>button.addEventListener('click',()=>{input.value=button.dataset.prompt;input.focus()}));
    const Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(Recognition){const recognition=new Recognition();recognition.continuous=false;recognition.interimResults=true;record.addEventListener('click',()=>{if(record.classList.contains('recording')){recognition.stop();return}recognition.lang=locales[from.value]||'fr-FR';try{recognition.start()}catch(error){showOutput('SPEECH INPUT','Microphone is already listening. Try again in a moment.')}});recognition.onstart=()=>{record.textContent='■ Listening…';record.classList.add('recording');showOutput('SPEECH INPUT','Listening… review the transcription when you finish.')};recognition.onresult=event=>{let transcript='';for(let index=0;index<event.results.length;index++)transcript+=event.results[index][0].transcript+' ';input.value=transcript.trim();if(mode==='dictionary')renderDictionary(input.value)};recognition.onerror=()=>{showOutput('SPEECH INPUT','We could not hear that clearly. Try again.')};recognition.onend=()=>{record.textContent='🎙 Speak';record.classList.remove('recording');if(input.value.trim())showOutput('SPEECH INPUT READY','Review the transcription, then submit it.')}}else record.addEventListener('click',()=>{showOutput('MICROPHONE UNAVAILABLE','Speech input is not supported in this browser.')});
    setMode('translate');
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
