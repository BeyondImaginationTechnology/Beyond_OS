<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

if (!Auth::check()) {
    http_response_code(403);
    exit('Administrator access required.');
}
header('Cache-Control: no-store');
$bridgeUrl = rtrim((string)beyond_config('remotion.bridge_url', 'http://127.0.0.1:4317'), '/');
$bridgeToken = (string)beyond_config('remotion.bridge_token', '');
$blueprints = [
    'game-trailer' => ['Game Trailer', 'Launch Sequence', ['Cold open', 'World reveal', 'Gameplay montage', 'Launch card']],
    'ad-spot' => ['Ad Spot', 'Product Pulse', ['Pattern break', 'Product focus', 'Proof point', 'Call to action']],
    'short-film' => ['Short Film', 'Quiet Orbit', ['Establishing image', 'Inciting detail', 'Character turn', 'Final image']],
    'anime-opener' => ['Anime Opener', 'Neon Shonen', ['Hero reveal', 'Team cards', 'Action burst', 'Series title']],
    'cartoon-short' => ['Cartoon Short', 'Saturday Spark', ['Comic setup', 'Escalation', 'Visual punchline', 'End card']],
];
$blueprintId = (string)($_GET['template'] ?? '');
$blueprint = $blueprints[$blueprintId] ?? null;
$ratio = in_array((string)($_GET['ratio'] ?? ''), ['16:9', '9:16', '2.39:1'], true) ? (string)$_GET['ratio'] : '16:9';
$presetWidth = 1920;
$presetHeight = 1080;
$presetSelector = '[class*="aspect-[16/9]"]';
if ($ratio === '9:16') {
    $presetWidth = 1080;
    $presetHeight = 1920;
    $presetSelector = '[class*="aspect-[9/16]"]';
} elseif ($ratio === '2.39:1') {
    $presetHeight = 804;
    $presetSelector = '[data-remotion-root]';
}
$presetFps = max(1, min(60, (int)($_GET['fps'] ?? 30)));
$presetSeconds = max(1, min(300, (int)($_GET['seconds'] ?? 15)));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Remotion renderer · Beyond Studio</title>
  <style>
    :root{color-scheme:dark;--ink:#f7f4ff;--muted:#aaa4bf;--line:rgba(255,255,255,.1);--panel:#11101a;--accent:#d5b6ff;--accent2:#ffbd91;--good:#7ce4b3;--bad:#ff8d99}
    *{box-sizing:border-box}body{margin:0;background:#09080e;color:var(--ink);font:15px/1.5 Inter,ui-sans-serif,system-ui,-apple-system,sans-serif}.shell{max-width:1220px;margin:auto;padding:28px}.hero{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:22px}.eyebrow{margin:0 0 7px;color:var(--accent2);font-size:11px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}h1{margin:0;font-size:clamp(30px,5vw,52px);line-height:1;letter-spacing:-.04em}h1 span{color:var(--accent)}.lede{max-width:720px;margin:13px 0 0;color:var(--muted)}.bridge{display:grid;grid-template-columns:auto 1fr;align-items:center;gap:0 9px;padding:9px 13px;border:1px solid var(--line);border-radius:16px;background:var(--panel);font-size:12px;font-weight:800;white-space:nowrap}.bridge small{grid-column:2;color:var(--muted);font-size:10px;font-weight:700}.dot{grid-row:1/3;width:8px;height:8px;border-radius:50%;background:#766f82;box-shadow:0 0 0 4px rgba(255,255,255,.04)}.bridge.online .dot{background:var(--good);box-shadow:0 0 14px var(--good)}.grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(310px,.7fr);gap:18px}.card{border:1px solid var(--line);border-radius:22px;background:linear-gradient(145deg,rgba(255,255,255,.04),rgba(255,255,255,.015));overflow:hidden}.card-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:17px 19px;border-bottom:1px solid var(--line)}.step{display:flex;align-items:center;gap:10px;font-weight:850}.number{display:grid;place-items:center;width:27px;height:27px;border-radius:9px;background:rgba(213,182,255,.14);color:var(--accent);font-size:12px}.card-body{padding:19px}.drop{display:grid;place-items:center;min-height:230px;padding:30px;border:1.5px dashed rgba(213,182,255,.35);border-radius:17px;background:radial-gradient(circle at 50% 10%,rgba(213,182,255,.1),transparent 55%);text-align:center;cursor:pointer;transition:.2s}.drop:hover,.drop.drag{border-color:var(--accent);background-color:rgba(213,182,255,.05)}.drop strong{font-size:18px}.drop p{max-width:440px;margin:7px auto 0;color:var(--muted);font-size:13px}.drop input{position:absolute;opacity:0;pointer-events:none}.file-pill{display:none;align-items:center;gap:9px;margin-top:14px;padding:11px 13px;border:1px solid var(--line);border-radius:13px;background:#0b0a11}.file-pill.show{display:flex}.file-pill b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.file-pill span{margin-left:auto;color:var(--muted);font-size:12px}.settings{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:16px}.settings label{display:grid;gap:6px;color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em}.settings label.selector{grid-column:1/-1}.settings input,.settings select{width:100%;min-width:0;padding:10px 11px;border:1px solid var(--line);border-radius:11px;background:#0b0a11;color:var(--ink);font:inherit}.action{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;margin-top:16px;padding:13px 17px;border:0;border-radius:13px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#17101c;font:900 14px/1 inherit;cursor:pointer}.action:disabled{filter:grayscale(.6);opacity:.45;cursor:not-allowed}.action.secondary{background:#eae6f4}.notice{margin-top:13px;padding:11px 13px;border:1px solid var(--line);border-radius:12px;color:var(--muted);font-size:12px}.notice.error{border-color:rgba(255,141,153,.35);color:var(--bad)}.composition{display:grid;grid-template-columns:1fr auto;gap:4px 12px;padding:12px;border:1px solid var(--line);border-radius:12px;background:#0b0a11}.composition small{color:var(--muted)}.preview{display:grid;place-items:center;min-height:220px;background:#050508;color:var(--muted);overflow:hidden}.preview iframe{width:100%;height:330px;border:0;background:white}.empty{text-align:center;padding:30px}.empty b{display:block;color:var(--ink);margin-bottom:5px}.recent{margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)}.recent[hidden]{display:none}.recent-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:8px}.recent-head small{color:var(--muted)}.recent-list{display:grid;gap:7px}.recent-button{width:100%;display:grid;grid-template-columns:1fr auto;gap:4px 10px;padding:10px 11px;border:1px solid var(--line);border-radius:11px;background:#0b0a11;color:var(--ink);text-align:left;cursor:pointer}.recent-button:hover{border-color:rgba(213,182,255,.45)}.recent-button small{grid-column:1/3;color:var(--muted)}.progress-wrap{display:none;margin-top:16px}.progress-wrap.show{display:block}.progress-label{display:flex;justify-content:space-between;margin-bottom:7px;font-size:12px}.progress{height:9px;border-radius:999px;background:#23202e;overflow:hidden}.progress span{display:block;width:0;height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .25s}.log{display:none;max-height:150px;overflow:auto;margin-top:12px;padding:11px;border-radius:10px;background:#050508;color:#8f899d;font:10px/1.45 ui-monospace,monospace;white-space:pre-wrap}.trust{margin-top:18px;color:#827c91;font-size:11px}@media(max-width:850px){.shell{padding:16px}.hero{align-items:stretch;flex-direction:column;margin-bottom:16px}.bridge{width:100%}.grid{grid-template-columns:1fr}.settings{grid-template-columns:1fr 1fr}.drop{min-height:180px;padding:22px}.card{border-radius:18px}.card-body{padding:15px}.preview{min-height:170px}}
  </style>
  <style>
    .blueprint{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;margin:-2px 0 20px;padding:13px 15px;border:1px solid rgba(217,255,87,.2);border-radius:14px;background:rgba(217,255,87,.055)}
    .blueprint-mark{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:#d9ff57;color:#14120d;font-weight:950}.blueprint b{display:block}.blueprint small{color:var(--muted)}
    .blueprint-beats{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:5px}.blueprint-beats span{padding:5px 7px;border:1px solid var(--line);border-radius:6px;color:#c9c5d0;font-size:10px}.blueprint-back{color:#d9ff57;text-decoration:none;font-size:11px;font-weight:900;white-space:nowrap}
    @media(max-width:850px){.blueprint{grid-template-columns:auto 1fr}.blueprint-beats{grid-column:1/-1;justify-content:flex-start}}
  </style>
</head>
<body>
<main class="shell">
  <header class="hero">
    <div><p class="eyebrow">Beyond Studio · Video</p><h1>React artifact to <span>MP4.</span></h1><p class="lede">Import a real Remotion project ZIP or a bundled React artifact HTML. Inspect the composition, render with Chromium, and download a production H.264 MP4.</p></div>
    <div class="bridge" id="bridge"><span class="dot"></span><span id="bridgeText">Checking render bridge…</span><small id="bridgeMeta">Secure connection</small></div>
  </header>
  <?php if ($blueprint): ?>
    <section class="blueprint" aria-label="Selected video blueprint"><span class="blueprint-mark" aria-hidden="true">✦</span><div><b><?=htmlspecialchars($blueprint[0])?> · <?=htmlspecialchars($blueprint[1])?></b><small><?=htmlspecialchars($ratio)?> canvas · <?=htmlspecialchars((string)$presetFps)?> FPS · <?=htmlspecialchars((string)$presetSeconds)?> seconds</small></div><div class="blueprint-beats"><?php foreach ($blueprint[2] as $beat): ?><span><?=htmlspecialchars($beat)?></span><?php endforeach; ?></div><a class="blueprint-back" href="video-templates.php">Change blueprint</a></section>
  <?php endif; ?>
  <div class="grid">
    <section class="card">
      <div class="card-head"><div class="step"><span class="number">1</span>Import artifact</div><span id="kindLabel" class="eyebrow">ZIP or HTML</span></div>
      <div class="card-body">
        <label class="drop" id="drop"><input id="artifact" type="file" accept=".zip,.html,.htm,application/zip,text/html"><div><strong>Drop a React / Remotion artifact</strong><p>A ZIP should contain package.json and src/index.ts(x). Bundled HTML is converted into a frame-driven Remotion composition.</p></div></label>
        <div class="file-pill" id="filePill"><b id="fileName"></b><span id="fileSize"></span></div>
        <div class="settings" id="htmlSettings">
          <label>Width<input id="width" type="number" min="320" max="3840" value="<?=htmlspecialchars((string)$presetWidth)?>"></label>
          <label>Height<input id="height" type="number" min="320" max="3840" value="<?=htmlspecialchars((string)$presetHeight)?>"></label>
          <label>FPS<input id="fps" type="number" min="1" max="60" value="<?=htmlspecialchars((string)$presetFps)?>"></label>
          <label>Seconds<input id="seconds" type="number" min="1" max="300" value="<?=htmlspecialchars((string)$presetSeconds)?>"></label>
          <label class="selector">Canvas selector <input id="selector" value='<?=htmlspecialchars($presetSelector, ENT_QUOTES)?>' placeholder="#video-root or [data-remotion-root]"></label>
        </div>
        <button class="action" id="inspect" disabled>Inspect compositions</button>
        <button class="action secondary" id="recordFallback" disabled>Screen-record fallback</button>
        <div class="notice" id="message">Start the local bridge once, then choose an artifact.</div>
      </div>
    </section>
    <aside class="card">
      <div class="card-head"><div class="step"><span class="number">2</span>Render & export</div></div>
      <div class="preview" id="preview"><div class="empty"><b>No artifact loaded</b>HTML previews appear here. ZIP projects expose their native compositions.</div></div>
      <div class="card-body">
        <div class="recent" id="recent" hidden><div class="recent-head"><b>Recent trusted imports</b><small>Also available to AI tools</small></div><div class="recent-list" id="recentList"></div></div>
        <div id="compositionBox" class="composition" hidden><div><b id="compositionName"></b><br><small id="compositionMeta"></small></div><select id="composition"></select></div>
        <button class="action secondary" id="render" disabled>Render H.264 MP4</button>
        <div class="progress-wrap" id="progressWrap"><div class="progress-label"><b id="renderStatus">Rendering</b><span id="progressPercent">0%</span></div><div class="progress"><span id="progressBar"></span></div><pre class="log" id="log"></pre></div>
      </div>
    </aside>
  </div>
  <p class="trust">Security: imported React code runs only in the local renderer. Import trusted artifacts. The bridge is bound to 127.0.0.1 and is not a public upload service.</p>
</main>
<noscript><p class="notice error">JavaScript is required to inspect and render artifacts.</p></noscript>
<script>
(() => {
  const bridgeUrl=<?=json_encode($bridgeUrl, JSON_UNESCAPED_SLASHES)?>;
  const bridgeToken=<?=json_encode($bridgeToken, JSON_UNESCAPED_SLASHES)?>;
  const nativeFetch=window.fetch.bind(window);
  window.fetch=(input,options={})=>nativeFetch(input,{...options,headers:{...(options.headers||{}),...(bridgeToken?{Authorization:'Bearer '+bridgeToken}:{})}});
  const apiFetch=(path,options={})=>window.fetch(bridgeUrl+path,options);
  const $=(id)=>document.getElementById(id);
  let file=null,artifact=null,previewUrl=null;
  const setMessage=(text,error=false)=>{$('message').textContent=text;$('message').classList.toggle('error',error)};
  const checkBridge=async()=>{try{const response=await apiFetch('/api/health');const health=await response.json();if(!response.ok||!health.ok)throw new Error();const ready=health.remotionReady!==false;$('bridge').classList.add('online');$('bridgeText').textContent=ready?'Render bridge online':'Bridge needs dependencies';$('bridgeMeta').textContent=(health.aiApiReady?'Meta AI tools ready · ':'')+Math.round((health.maxUploadBytes||0)/1024/1024)+' MB imports';$('inspect').disabled=!file||!ready;$('recordFallback').disabled=!file||!ready||/\.zip$/i.test(file?.name||'');setMessage(ready?'Bridge ready. Choose a new artifact or reuse a recent trusted import.':'Bridge is online, but Remotion dependencies are not installed. Run pnpm install on the render host.',!ready);if(ready)await loadRecentArtifacts()}catch(error){$('bridge').classList.remove('online');$('bridgeText').textContent='Bridge offline';$('bridgeMeta').textContent='Check renderer connection';setMessage(bridgeUrl.includes('127.0.0.1')?'Start tools/beyond-studio-remotion/start.ps1, then keep that terminal open.':'The cloud render bridge is unavailable or its credentials are invalid.',true)}};
  const choose=(next)=>{if(!next)return;if(!/\.(zip|html?)$/i.test(next.name)){setMessage('Choose a .zip, .html, or .htm artifact.',true);return}file=next;artifact=null;$('fileName').textContent=file.name;$('fileSize').textContent=(file.size/1024/1024).toFixed(2)+' MB';$('filePill').classList.add('show');$('kindLabel').textContent=/\.zip$/i.test(file.name)?'REMOTION ZIP':'REACT HTML';$('htmlSettings').style.display=/\.zip$/i.test(file.name)?'none':'grid';$('inspect').disabled=!$('bridge').classList.contains('online');$('recordFallback').disabled=/\.zip$/i.test(file.name)||!$('bridge').classList.contains('online');$('render').disabled=true;$('compositionBox').hidden=true;setMessage('Ready to inspect '+file.name+'.');if(previewUrl)URL.revokeObjectURL(previewUrl);if(/\.html?$/i.test(file.name)){previewUrl=URL.createObjectURL(file);$('preview').innerHTML='<iframe title="Artifact preview" sandbox="allow-scripts allow-same-origin"></iframe>';$('preview').querySelector('iframe').src=previewUrl}else{$('preview').innerHTML='<div class="empty"><b>Remotion project ZIP</b>Inspect to discover registered compositions.</div>'}};
  $('artifact').addEventListener('change',(event)=>choose(event.target.files[0]));
  $('drop').addEventListener('dragover',(event)=>{event.preventDefault();$('drop').classList.add('drag')});
  $('drop').addEventListener('dragleave',()=> $('drop').classList.remove('drag'));
  $('drop').addEventListener('drop',(event)=>{event.preventDefault();$('drop').classList.remove('drag');choose(event.dataTransfer.files[0])});
  $('inspect').addEventListener('click',async()=>{if(!file)return;$('inspect').disabled=true;setMessage('Uploading locally and asking Remotion for compositions…');try{const params=new URLSearchParams({width:$('width').value,height:$('height').value,fps:$('fps').value,seconds:$('seconds').value,selector:$('selector').value});const response=await fetch(bridgeUrl+'/api/import?'+params,{method:'POST',headers:{'Content-Type':'application/octet-stream','X-Artifact-Name':encodeURIComponent(file.name)},body:file});const payload=await response.json();if(!response.ok||!payload.ok)throw new Error(payload.error||'Import failed.');artifact=payload.artifact;const select=$('composition');select.innerHTML='';artifact.compositions.forEach((item)=>{const option=document.createElement('option');option.value=item.id;option.textContent=item.id;option.dataset.meta=item.width+'×'+item.height+' · '+item.fps+' FPS · '+(item.durationInFrames/item.fps).toFixed(1)+'s';select.append(option)});if(!artifact.compositions.length)throw new Error('No compositions are registered in this artifact.');const updateComposition=()=>{$('compositionName').textContent=select.value;$('compositionMeta').textContent=select.selectedOptions[0].dataset.meta};select.onchange=updateComposition;updateComposition();$('compositionBox').hidden=false;$('render').disabled=false;setMessage(artifact.compositions.length+' composition'+(artifact.compositions.length===1?'':'s')+' ready to render.')}catch(error){setMessage(error.message,true)}finally{$('inspect').disabled=false}});
  const loadArtifact=(nextArtifact)=>{artifact=nextArtifact;const select=$('composition');select.innerHTML='';artifact.compositions.forEach((item)=>{const option=document.createElement('option');option.value=item.id;option.textContent=item.id;option.dataset.meta=item.width+'×'+item.height+' · '+item.fps+' FPS · '+(item.durationInFrames/item.fps).toFixed(1)+'s';select.append(option)});if(!artifact.compositions.length)throw new Error('No compositions are registered in this artifact.');const updateComposition=()=>{$('compositionName').textContent=select.value;$('compositionMeta').textContent=select.selectedOptions[0].dataset.meta};select.onchange=updateComposition;updateComposition();$('compositionBox').hidden=false;$('render').disabled=false;};
  const loadRecentArtifacts=async()=>{try{const response=await apiFetch('/api/artifacts');const payload=await response.json();if(!response.ok||!payload.ok)throw new Error();const recent=(payload.artifacts||[]).slice(-5).reverse();$('recentList').innerHTML='';recent.forEach((item)=>{const button=document.createElement('button');button.type='button';button.className='recent-button';const name=document.createElement('b');name.textContent=item.name;const type=document.createElement('span');type.textContent=item.type.toUpperCase();const meta=document.createElement('small');meta.textContent=item.compositions.length+' composition'+(item.compositions.length===1?'':'s');button.append(name,type,meta);button.addEventListener('click',()=>{loadArtifact(item);$('preview').innerHTML='<div class="empty"><b>'+escapeHtml(item.name)+'</b>Trusted import selected. Choose a composition and render.</div>';setMessage(item.name+' is ready to render.')});$('recentList').append(button)});$('recent').hidden=recent.length===0}catch(_){$('recent').hidden=true}};
  const escapeHtml=(value)=>String(value).replace(/[&<>'"]/g,(character)=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
  $('recordFallback').addEventListener('click',async()=>{let stream=null;$('recordFallback').disabled=true;try{if(!navigator.mediaDevices?.getDisplayMedia||!window.MediaRecorder)throw new Error('This browser does not support display recording.');setMessage('Choose the tab or window that shows your artifact. Recording begins after you share it.');stream=await navigator.mediaDevices.getDisplayMedia({video:{frameRate:Number($('fps').value)||30},audio:true});const mime=['video/webm;codecs=vp9,opus','video/webm;codecs=vp8,opus','video/webm'].find((type)=>MediaRecorder.isTypeSupported(type))||'';const chunks=[];const recorder=new MediaRecorder(stream,mime?{mimeType:mime}:undefined);recorder.ondataavailable=(event)=>{if(event.data.size)chunks.push(event.data)};const stopped=new Promise((resolve,reject)=>{recorder.onstop=resolve;recorder.onerror=()=>reject(recorder.error||new Error('Screen recording failed.'))});recorder.start(250);const seconds=Math.max(1,Math.min(300,Number($('seconds').value)||15));setMessage('Recording '+seconds+' seconds in real time… Play the artifact in the shared tab.');await new Promise((resolve)=>setTimeout(resolve,seconds*1000));recorder.stop();await stopped;stream.getTracks().forEach((track)=>track.stop());stream=null;const recording=new Blob(chunks,{type:recorder.mimeType||'video/webm'});setMessage('Recording complete. Preparing it for H.264 export…');const params=new URLSearchParams({width:$('width').value,height:$('height').value,fps:$('fps').value,seconds:String(seconds)});const response=await fetch(bridgeUrl+'/api/import-recording?'+params,{method:'POST',headers:{'Content-Type':'application/octet-stream','X-Artifact-Name':'screen-recording.webm'},body:recording});const payload=await response.json();if(!response.ok||!payload.ok)throw new Error(payload.error||'Could not prepare screen recording.');loadArtifact(payload.artifact);setMessage('Screen recording ready. Click Render H.264 MP4 to encode it.')}catch(error){setMessage(error.message,true)}finally{stream?.getTracks().forEach((track)=>track.stop());$('recordFallback').disabled=!file||/\.zip$/i.test(file.name)}});
  $('render').addEventListener('click',async()=>{if(!artifact)return;$('render').disabled=true;$('progressWrap').classList.add('show');$('log').style.display='none';$('progressBar').style.width='0%';$('progressPercent').textContent='0%';$('renderStatus').textContent='Starting Chromium…';try{const response=await fetch(bridgeUrl+'/api/artifacts/'+artifact.id+'/render',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({compositionId:$('composition').value})});const payload=await response.json();if(!response.ok||!payload.ok)throw new Error(payload.error||'Render failed to start.');const jobId=payload.job.id;while(true){await new Promise((resolve)=>setTimeout(resolve,1000));const statusResponse=await fetch(bridgeUrl+'/api/jobs/'+jobId);const status=await statusResponse.json();if(!statusResponse.ok||!status.ok)throw new Error(status.error||'Could not read render status.');const job=status.job,percent=Math.round(job.progress*100);$('progressBar').style.width=percent+'%';$('progressPercent').textContent=percent+'%';$('renderStatus').textContent=job.status==='rendering'?'Rendering frames…':job.status;$('log').textContent=job.log||'';if(job.status==='failed')throw new Error(job.error||'Remotion render failed.');if(job.status==='complete'){const anchor=document.createElement('a');anchor.href=bridgeUrl+'/api/jobs/'+jobId+'/download';anchor.download=$('composition').value+'.mp4';document.body.append(anchor);anchor.click();anchor.remove();$('renderStatus').textContent='MP4 ready';setMessage('Render complete. Your MP4 download has started.');break}}}catch(error){$('renderStatus').textContent='Render failed';$('log').style.display='block';setMessage(error.message,true)}finally{$('render').disabled=false}});
  checkBridge();
})();
</script>
</body>
</html>
