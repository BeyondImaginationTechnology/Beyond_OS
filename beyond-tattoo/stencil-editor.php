<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
$stencilMeta = require __DIR__ . '/config/stencil-day.php';
$stencilTitle = (string)$stencilMeta['title'];
$stencilSource = (string)$stencilMeta['transfer_png_url'];
$stencilSlug = trim((string)(preg_replace('/[^a-z0-9]+/i', '-', $stencilTitle) ?? ''), '-');
beyond_nav_bootstrap('Beyond Tattoo', beyond_wallet());
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Advanced Stencil Editor | Beyond Tattoo</title>
<style>
:root{--bg:#08050d;--panel:#15101c;--panel2:#211329;--line:#ffffff18;--muted:#bcaec3;--purple:#9a48ff;--pink:#ef3fa5;--green:#27e77d}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:#fff;font:15px/1.42 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.app{display:grid;grid-template-columns:340px 1fr;min-height:calc(100vh - 64px)}aside{padding:18px;background:linear-gradient(180deg,#17101e,#0e0913);border-right:1px solid var(--line);overflow:auto;max-height:calc(100vh - 64px);position:sticky;top:64px}.brand{font-size:24px;font-weight:950;letter-spacing:-.4px}.muted{color:var(--muted)}.toolbar{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin:16px 0}.tool,.btn,.select{border:1px solid #ffffff16;border-radius:12px;padding:11px 9px;font-weight:800;color:#fff;background:#291d33;cursor:pointer;text-align:center;text-decoration:none}.tool{font-size:12px;padding:10px 4px}.tool span{display:block;font-size:19px;margin-bottom:2px}.tool.active,.btn.active,.primary{background:linear-gradient(105deg,var(--purple),var(--pink));border-color:#d878ff}.group{margin:15px 0;padding-top:14px;border-top:1px solid var(--line)}.group-title{font-weight:900;margin-bottom:9px}.group label{display:flex;justify-content:space-between;margin:10px 0 5px}.group input[type=range]{width:100%;accent-color:var(--purple)}.row{display:grid;grid-template-columns:1fr 1fr;gap:8px}.row3{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.select{width:100%;appearance:auto}.text-input{width:100%;border:1px solid #ffffff1c;border-radius:12px;background:#100b15;color:#fff;padding:12px}.status{border:1px solid #78458b;background:#211029;border-radius:12px;padding:10px 12px;color:#e8d8ee;font-size:13px;margin-top:12px}main{display:grid;place-items:center;padding:28px;overflow:auto;background:radial-gradient(circle at 50% 35%,#32163f,#09070d 65%)}.workspace{width:min(100%,900px);display:grid;gap:12px}.sheet-wrap{display:grid;place-items:center;min-height:500px}.paper{position:relative;background:#fff;box-shadow:0 30px 80px #000c;overflow:hidden;touch-action:none}.paper.letter{width:min(100%,720px);aspect-ratio:8.5/11}.paper.a4{width:min(100%,720px);aspect-ratio:210/297}.paper.thermal{width:min(100%,430px);aspect-ratio:4/6}.paper.landscape{aspect-ratio:11/8.5}.paper canvas{width:100%;height:100%;display:block}.guide-layer{position:absolute;inset:0;pointer-events:none;display:none}.guide-layer.center::before,.guide-layer.center::after{content:"";position:absolute;background:#e53232aa}.guide-layer.center::before{left:50%;top:0;bottom:0;width:1px}.guide-layer.center::after{top:50%;left:0;right:0;height:1px}.guide-layer.grid{background-image:linear-gradient(#df3f3f55 1px,transparent 1px),linear-gradient(90deg,#df3f3f55 1px,transparent 1px);background-size:10% 10%}.caption{position:absolute;bottom:1%;left:0;right:0;text-align:center;color:#222;font:600 12px system-ui;pointer-events:none}.quickbar{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}.quickbar .btn{padding:9px 14px}.hide{display:none!important}
@media(max-width:800px){.app{grid-template-columns:1fr}.app aside{position:relative;top:0;max-height:none;border-right:0;border-bottom:1px solid var(--line)}main{padding:12px}.toolbar{position:sticky;top:64px;z-index:8;background:#120c18;padding:8px;margin:12px -8px}.sheet-wrap{min-height:420px}.controls-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.group{margin:7px 0;padding-top:8px}.paper.letter,.paper.a4{width:min(100%,560px)}}
@media(max-width:480px){.toolbar{grid-template-columns:repeat(5,1fr)}.tool{font-size:10px}.tool span{font-size:17px}.row3{grid-template-columns:1fr}.controls-grid{grid-template-columns:1fr}.brand{font-size:21px}}
@media print{#beyond-os-shell,aside,.quickbar{display:none!important}.app{display:block}.app main{padding:0;background:#fff}.workspace,.sheet-wrap{display:block}.paper{width:100vw!important;height:100vh!important;max-width:none;box-shadow:none;margin:0}.caption{display:block}.guide-layer{display:none!important}}
</style>
</head>
<body>
<div class="app">
<aside>
  <div class="brand">Advanced Stencil Editor</div>
  <p class="muted">Edit, position and prepare <?=htmlspecialchars($stencilTitle, ENT_QUOTES, 'UTF-8')?> from the asset-backed 1.2 library.</p>

  <div class="toolbar" role="toolbar" aria-label="Editing tools">
    <button class="tool active" data-tool="move"><span>↔</span>Move</button>
    <button class="tool" data-tool="brush"><span>✎</span>Brush</button>
    <button class="tool" data-tool="eraser"><span>⌫</span>Eraser</button>
    <button class="tool" data-tool="text"><span>T</span>Text</button>
    <button class="tool" data-tool="shape"><span>◇</span>Shape</button>
  </div>

  <div id="textControls" class="group hide">
    <div class="group-title">Add text</div>
    <input class="text-input" id="textValue" maxlength="80" placeholder="Type text…">
    <div class="row" style="margin-top:8px"><input class="text-input" id="fontSize" type="number" min="8" max="96" value="28" aria-label="Font size"><button class="btn primary" id="addText">Place text</button></div>
  </div>

  <div id="shapeControls" class="group hide">
    <div class="group-title">Add shape</div>
    <div class="row3"><button class="btn shapeBtn" data-shape="circle">Circle</button><button class="btn shapeBtn" data-shape="square">Square</button><button class="btn shapeBtn" data-shape="diamond">Diamond</button></div>
  </div>

  <div class="controls-grid">
    <div class="group">
      <div class="group-title">Transform</div>
      <div class="row"><button class="btn" id="mirrorH">Mirror ↔</button><button class="btn" id="mirrorV">Mirror ↕</button></div>
      <div class="row3" style="margin-top:8px"><button class="btn" id="rotateLeft">↶ 15°</button><button class="btn" id="rotateRight">↷ 15°</button><button class="btn" id="rotate90">90°</button></div>
      <label>Scale <span id="scaleVal">100%</span></label><input id="scale" type="range" min="35" max="180" value="100">
      <label>X position <span id="xVal">0</span></label><input id="xPos" type="range" min="-300" max="300" value="0">
      <label>Y position <span id="yVal">0</span></label><input id="yPos" type="range" min="-400" max="400" value="0">
      <div class="row" style="margin-top:8px"><button class="btn" id="fitPage">Fit page</button><button class="btn" id="centerArt">Center</button></div>
    </div>

    <div class="group">
      <div class="group-title">Line preparation</div>
      <label>Contrast <span id="contrastVal">100%</span></label><input id="contrast" type="range" min="50" max="250" value="100">
      <label>Brightness <span id="brightnessVal">100%</span></label><input id="brightness" type="range" min="50" max="180" value="100">
      <label>Line weight <span id="weightVal">0px</span></label><input id="weight" type="range" min="0" max="5" step=".5" value="0">
      <label>Drawing width <span id="brushVal">4px</span></label><input id="brushSize" type="range" min="1" max="30" value="4">
      <div class="row" style="margin-top:8px"><button class="btn" id="invert">Invert</button><button class="btn" id="grayscale">B&amp;W</button></div>
    </div>

    <div class="group">
      <div class="group-title">Paper & guides</div>
      <select class="select" id="paperSize"><option value="letter">US Letter 8.5 × 11</option><option value="a4">A4</option><option value="thermal">Stencil printer 4 × 6</option></select>
      <div class="row" style="margin-top:8px"><button class="btn" id="orientation">Landscape</button><button class="btn" id="marginToggle">Safe margin</button></div>
      <div class="row3" style="margin-top:8px"><button class="btn guideBtn active" data-guide="off">Off</button><button class="btn guideBtn" data-guide="center">Center</button><button class="btn guideBtn" data-guide="grid">Grid</button></div>
    </div>

    <div class="group">
      <div class="group-title">History & output</div>
      <div class="row"><button class="btn" id="undo">Undo</button><button class="btn" id="redo">Redo</button></div>
      <div style="display:grid;gap:8px;margin-top:8px">
        <button class="btn primary" id="savePng">Save high-res PNG</button>
        <button class="btn primary" onclick="window.print()">Print / Save PDF</button>
        <a class="btn" href="/beyond-tattoo/<?=htmlspecialchars($stencilSource, ENT_QUOTES, 'UTF-8')?>" download="beyond-tattoo-<?=htmlspecialchars($stencilSlug, ENT_QUOTES, 'UTF-8')?>-source.png">Download source PNG</a>
        <button class="btn" id="reset">Reset editor</button>
      </div>
      <p class="muted">iPhone PDF: Print → pinch out preview → Share → Save to Files.</p>
    </div>
  </div>
  <div class="status" id="status">Move tool selected. Drag the stencil to position it.</div>
</aside>

<main>
  <div class="workspace">
    <div class="quickbar"><button class="btn" id="zoomOut">−</button><button class="btn" id="zoomReset">100%</button><button class="btn" id="zoomIn">+</button><button class="btn" id="clearMarks">Clear added marks</button></div>
    <div class="sheet-wrap">
      <div id="paper" class="paper letter">
        <canvas id="canvas" width="1700" height="2200" aria-label="Stencil editing canvas"></canvas>
        <div id="guideLayer" class="guide-layer"></div>
        <div class="caption">Beyond Tattoo · <?=htmlspecialchars($stencilTitle, ENT_QUOTES, 'UTF-8')?> · <?=htmlspecialchars((string)$stencilMeta['display_date'], ENT_QUOTES, 'UTF-8')?></div>
      </div>
    </div>
  </div>
</main>
</div>
<script>
'use strict';
const canvas=document.getElementById('canvas'),ctx=canvas.getContext('2d',{willReadFrequently:true}),paper=document.getElementById('paper'),guideLayer=document.getElementById('guideLayer'),statusEl=document.getElementById('status');
const source=new Image(); source.src=<?=json_encode('/beyond-tattoo/' . $stencilSource)?>;
const state={tool:'move',scale:1,rotation:0,flipX:1,flipY:1,x:0,y:0,contrast:1,brightness:1,invert:false,gray:true,weight:0,brushSize:4,zoom:1,dragging:false,lastX:0,lastY:0,marks:[],currentPath:null,guide:'off',safeMargin:false,paper:'letter',landscape:false};
let history=[],future=[];
function snapshot(){history.push(JSON.stringify({marks:state.marks,scale:state.scale,rotation:state.rotation,flipX:state.flipX,flipY:state.flipY,x:state.x,y:state.y,contrast:state.contrast,brightness:state.brightness,invert:state.invert,gray:state.gray,weight:state.weight}));if(history.length>40)history.shift();future=[]}
function restore(raw){if(!raw)return;Object.assign(state,JSON.parse(raw));syncControls();render()}
function setStatus(t){statusEl.textContent=t}
function paperDims(){if(state.paper==='a4')return state.landscape?[2480,1754]:[1754,2480];if(state.paper==='thermal')return state.landscape?[1800,1200]:[1200,1800];return state.landscape?[2200,1700]:[1700,2200]}
function resizeCanvas(){const [w,h]=paperDims();canvas.width=w;canvas.height=h;paper.className='paper '+state.paper+(state.landscape?' landscape':'');render()}
function drawBase(){const w=canvas.width,h=canvas.height;ctx.save();ctx.clearRect(0,0,w,h);ctx.fillStyle='#fff';ctx.fillRect(0,0,w,h);ctx.translate(w/2+state.x,h/2+state.y);ctx.rotate(state.rotation*Math.PI/180);ctx.scale(state.flipX*state.scale,state.flipY*state.scale);const ratio=Math.min((w*.78)/source.naturalWidth,(h*.82)/source.naturalHeight);const dw=source.naturalWidth*ratio,dh=source.naturalHeight*ratio;ctx.filter=`contrast(${state.contrast}) brightness(${state.brightness})${state.invert?' invert(1)':''}${state.gray?' grayscale(1)':''}`;ctx.drawImage(source,-dw/2,-dh/2,dw,dh);if(state.weight>0){ctx.globalCompositeOperation='source-over';ctx.filter='none';ctx.strokeStyle='#000';ctx.lineWidth=state.weight*2;ctx.strokeRect(-dw/2,-dh/2,dw,dh)}ctx.restore()}
function drawMarks(){ctx.save();ctx.lineCap='round';ctx.lineJoin='round';for(const m of state.marks){ctx.globalCompositeOperation=m.erase?'destination-out':'source-over';ctx.strokeStyle=m.color||'#000';ctx.fillStyle=m.color||'#000';ctx.lineWidth=m.width||4;if(m.type==='path'){ctx.beginPath();m.points.forEach((p,i)=>i?ctx.lineTo(p.x,p.y):ctx.moveTo(p.x,p.y));ctx.stroke()}else if(m.type==='text'){ctx.globalCompositeOperation='source-over';ctx.font=`700 ${m.size}px system-ui`;ctx.fillText(m.text,m.x,m.y)}else if(m.type==='shape'){ctx.globalCompositeOperation='source-over';ctx.beginPath();if(m.shape==='circle')ctx.arc(m.x,m.y,m.size,0,Math.PI*2);else if(m.shape==='square')ctx.rect(m.x-m.size,m.y-m.size,m.size*2,m.size*2);else{ctx.moveTo(m.x,m.y-m.size);ctx.lineTo(m.x+m.size,m.y);ctx.lineTo(m.x,m.y+m.size);ctx.lineTo(m.x-m.size,m.y);ctx.closePath()}ctx.stroke()}}ctx.restore()}
function drawSafeMargin(){if(!state.safeMargin)return;ctx.save();ctx.strokeStyle='#777';ctx.setLineDash([18,14]);ctx.lineWidth=2;ctx.strokeRect(canvas.width*.05,canvas.height*.05,canvas.width*.9,canvas.height*.9);ctx.restore()}
function render(){if(!source.complete)return;drawBase();drawMarks();drawSafeMargin()}
source.onload=()=>{resizeCanvas();snapshot()};
function point(e){const r=canvas.getBoundingClientRect();const t=e.touches?e.touches[0]:e;return{x:(t.clientX-r.left)*canvas.width/r.width,y:(t.clientY-r.top)*canvas.height/r.height}}
function down(e){e.preventDefault();const p=point(e);state.dragging=true;state.lastX=p.x;state.lastY=p.y;if(state.tool==='brush'||state.tool==='eraser'){snapshot();state.currentPath={type:'path',points:[p],width:state.brushSize*(canvas.width/850),erase:state.tool==='eraser',color:'#000'};state.marks.push(state.currentPath)}}
function move(e){if(!state.dragging)return;e.preventDefault();const p=point(e);if(state.tool==='move'){state.x+=p.x-state.lastX;state.y+=p.y-state.lastY;state.lastX=p.x;state.lastY=p.y;updateXY();render()}else if(state.currentPath){state.currentPath.points.push(p);render()}}
function up(){if(state.dragging&&state.tool==='move')snapshot();state.dragging=false;state.currentPath=null}
canvas.addEventListener('pointerdown',down);canvas.addEventListener('pointermove',move);canvas.addEventListener('pointerup',up);canvas.addEventListener('pointercancel',up);
function updateXY(){xPos.value=Math.max(-300,Math.min(300,Math.round(state.x/3)));yPos.value=Math.max(-400,Math.min(400,Math.round(state.y/3)));xVal.textContent=xPos.value;yVal.textContent=yPos.value}
function syncControls(){scale.value=Math.round(state.scale*100);scaleVal.textContent=scale.value+'%';contrast.value=Math.round(state.contrast*100);contrastVal.textContent=contrast.value+'%';brightness.value=Math.round(state.brightness*100);brightnessVal.textContent=brightness.value+'%';weight.value=state.weight;weightVal.textContent=state.weight+'px';updateXY();invert.classList.toggle('active',state.invert);grayscale.classList.toggle('active',state.gray)}
document.querySelectorAll('.tool').forEach(b=>b.onclick=()=>{state.tool=b.dataset.tool;document.querySelectorAll('.tool').forEach(x=>x.classList.toggle('active',x===b));textControls.classList.toggle('hide',state.tool!=='text');shapeControls.classList.toggle('hide',state.tool!=='shape');setStatus(state.tool==='move'?'Drag the stencil to position it.':state.tool==='brush'?'Draw directly on the stencil.':state.tool==='eraser'?'Erase added brush marks.':state.tool==='text'?'Type text, then tap Place text.':'Choose a shape to place in the center.')});
function transformAction(fn){snapshot();fn();render()}
mirrorH.onclick=()=>transformAction(()=>state.flipX*=-1);mirrorV.onclick=()=>transformAction(()=>state.flipY*=-1);rotateLeft.onclick=()=>transformAction(()=>state.rotation-=15);rotateRight.onclick=()=>transformAction(()=>state.rotation+=15);rotate90.onclick=()=>transformAction(()=>state.rotation+=90);
scale.oninput=e=>{state.scale=e.target.value/100;scaleVal.textContent=e.target.value+'%';render()};scale.onchange=snapshot;
xPos.oninput=e=>{state.x=Number(e.target.value)*3;xVal.textContent=e.target.value;render()};xPos.onchange=snapshot;
yPos.oninput=e=>{state.y=Number(e.target.value)*3;yVal.textContent=e.target.value;render()};yPos.onchange=snapshot;
contrast.oninput=e=>{state.contrast=e.target.value/100;contrastVal.textContent=e.target.value+'%';render()};contrast.onchange=snapshot;
brightness.oninput=e=>{state.brightness=e.target.value/100;brightnessVal.textContent=e.target.value+'%';render()};brightness.onchange=snapshot;
weight.oninput=e=>{state.weight=Number(e.target.value);weightVal.textContent=e.target.value+'px';render()};weight.onchange=snapshot;
brushSize.oninput=e=>{state.brushSize=Number(e.target.value);brushVal.textContent=e.target.value+'px'};
invert.onclick=()=>transformAction(()=>{state.invert=!state.invert;invert.classList.toggle('active',state.invert)});grayscale.onclick=()=>transformAction(()=>{state.gray=!state.gray;grayscale.classList.toggle('active',state.gray)});
fitPage.onclick=()=>transformAction(()=>{state.scale=.92;state.x=0;state.y=0;syncControls()});centerArt.onclick=()=>transformAction(()=>{state.x=0;state.y=0;updateXY()});
paperSize.onchange=e=>{snapshot();state.paper=e.target.value;resizeCanvas()};orientation.onclick=()=>{snapshot();state.landscape=!state.landscape;orientation.textContent=state.landscape?'Portrait':'Landscape';resizeCanvas()};marginToggle.onclick=()=>{state.safeMargin=!state.safeMargin;marginToggle.classList.toggle('active',state.safeMargin);render()};
document.querySelectorAll('.guideBtn').forEach(b=>b.onclick=()=>{state.guide=b.dataset.guide;document.querySelectorAll('.guideBtn').forEach(x=>x.classList.toggle('active',x===b));guideLayer.className='guide-layer'+(state.guide==='off'?'':' '+state.guide);guideLayer.style.display=state.guide==='off'?'none':'block'});
addText.onclick=()=>{const t=textValue.value.trim();if(!t)return; snapshot();state.marks.push({type:'text',text:t,size:Number(fontSize.value)*(canvas.width/850),x:canvas.width*.5,y:canvas.height*.5,color:'#000'});textValue.value='';render();setStatus('Text placed at the center. Use Undo to remove it.')};
document.querySelectorAll('.shapeBtn').forEach(b=>b.onclick=()=>{snapshot();state.marks.push({type:'shape',shape:b.dataset.shape,x:canvas.width*.5,y:canvas.height*.5,size:canvas.width*.08,width:Math.max(4,canvas.width/300),color:'#000'});render();setStatus(b.textContent+' added to the center.')});
undo.onclick=()=>{if(history.length<2)return;future.push(history.pop());restore(history[history.length-1])};redo.onclick=()=>{const n=future.pop();if(!n)return;history.push(n);restore(n)};
clearMarks.onclick=()=>{snapshot();state.marks=[];render()};reset.onclick=()=>{if(confirm('Reset all stencil edits?'))location.reload()};
zoomIn.onclick=()=>{state.zoom=Math.min(1.5,state.zoom+.1);paper.style.transform=`scale(${state.zoom})`;zoomReset.textContent=Math.round(state.zoom*100)+'%'};zoomOut.onclick=()=>{state.zoom=Math.max(.6,state.zoom-.1);paper.style.transform=`scale(${state.zoom})`;zoomReset.textContent=Math.round(state.zoom*100)+'%'};zoomReset.onclick=()=>{state.zoom=1;paper.style.transform='scale(1)';zoomReset.textContent='100%'};
savePng.onclick=()=>{render();const a=document.createElement('a');a.download=<?=json_encode('beyond-tattoo-' . $stencilSlug . '-edited.png')?>;a.href=canvas.toDataURL('image/png',1);a.click();setStatus('High-resolution PNG saved.')};
window.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='z'){e.preventDefault();e.shiftKey?redo.click():undo.click()}});
</script>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
