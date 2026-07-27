<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond Games');
?>
<!doctype html>
<html lang="en">
<head>
  <script>(function(){try{var t=localStorage.getItem('beyond-theme');document.documentElement.dataset.theme=['dark','light','sunset'].includes(t)?t:'dark';}catch(e){document.documentElement.dataset.theme='dark';}})();</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#100812">
  <title>Tattoo Master | Beyond Games</title>
  <meta name="description" content="Trace original stencils, master your pressure and build your reputation in Tattoo Master.">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
  <style>
    :root{--ink:#f05cba;--violet:#a15cff;--gold:#ffc96c;--paper:#e7d7c5;--night:#0d0710}
    *{box-sizing:border-box}.tm-page{min-height:100vh;background:radial-gradient(circle at 50% -20%,#4b163e 0,transparent 38%),linear-gradient(180deg,#140918,#08070d 70%);color:#f9f3fb}.tm-main{width:min(1320px,calc(100% - 24px));margin:auto;padding:18px 0 54px}.tm-topbar,.tm-status{display:flex;align-items:center;justify-content:space-between;gap:12px}.tm-topbar{margin-bottom:14px}.tm-back{color:#d8cadc;text-decoration:none;font-weight:900}.tm-brand{font-weight:1000;letter-spacing:.12em;text-transform:uppercase}.tm-brand b{color:var(--ink)}.tm-save{padding:8px 12px;border:1px solid #4a344d;border-radius:999px;color:#a899ad;font-size:.72rem;font-weight:900}
    .tm-shell{overflow:hidden;border:1px solid #432946;border-radius:28px;background:rgba(14,8,16,.92);box-shadow:0 30px 100px rgba(0,0,0,.45)}.tm-status{padding:14px 18px;border-bottom:1px solid #3b273d;background:linear-gradient(90deg,#1d0d20,#110a15)}.tm-client{display:flex;align-items:center;gap:11px}.tm-avatar{display:grid;place-items:center;width:43px;height:43px;border:1px solid #6c3f68;border-radius:50%;background:#29152b;font-size:1.35rem}.tm-client small,.tm-metric small{display:block;color:#9b8c9e;text-transform:uppercase;font-size:.58rem;letter-spacing:.13em}.tm-client strong{display:block;margin-top:3px}.tm-metrics{display:flex;gap:24px}.tm-metric strong{font-size:1.15rem}.tm-metric.reputation strong{color:var(--gold)}
    .tm-game{display:grid;grid-template-columns:minmax(0,1fr) 290px}.tm-stage{position:relative;min-height:680px;display:grid;place-items:center;padding:28px;background:radial-gradient(circle at 50% 42%,rgba(229,75,165,.13),transparent 37%),linear-gradient(135deg,#171019,#0d0a10)}.tm-stage:before{content:"";position:absolute;inset:0;opacity:.22;background-image:linear-gradient(#39243a 1px,transparent 1px),linear-gradient(90deg,#39243a 1px,transparent 1px);background-size:28px 28px}.tm-canvas-wrap{position:relative;width:min(100%,620px);aspect-ratio:1;border:10px solid #2a192a;border-radius:32px;background:var(--paper);box-shadow:0 24px 70px #000,0 0 50px rgba(223,62,164,.17);overflow:hidden;touch-action:none}.tm-canvas-wrap:after{content:"PRACTICE SKIN · 01";position:absolute;right:18px;bottom:13px;color:#8d7a70;font-size:.55rem;font-weight:900;letter-spacing:.16em;pointer-events:none}canvas{position:absolute;inset:0;width:100%;height:100%;cursor:crosshair}.tm-hint{position:absolute;z-index:3;left:50%;bottom:18px;transform:translateX(-50%);padding:9px 14px;border-radius:999px;background:rgba(19,8,20,.84);color:#f2dceb;font-size:.7rem;font-weight:900;white-space:nowrap;pointer-events:none;transition:opacity .2s}.tm-hint.hidden{opacity:0}
    .tm-panel{padding:22px 18px;border-left:1px solid #3a263b;background:#100b12}.tm-panel h1{margin:0;font-size:1.8rem;line-height:.92;text-transform:uppercase}.tm-panel h1 span{display:block;color:var(--ink)}.tm-brief{margin:12px 0 20px;color:#a99baa;font-size:.78rem;line-height:1.55}.tm-label{display:flex;justify-content:space-between;margin:17px 0 8px;color:#cabdcb;font-size:.66rem;font-weight:900;text-transform:uppercase}.tm-pressure{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.tm-pressure button,.tm-action,.tm-designs button{border:1px solid #4b324c;background:#1b111d;color:#c9bdcb;font:inherit;cursor:pointer}.tm-pressure button{padding:10px 4px;border-radius:10px;font-size:.67rem;font-weight:900}.tm-pressure button.active{border-color:var(--ink);background:#3a1532;color:#fff;box-shadow:0 0 18px rgba(240,92,186,.2)}.tm-meter{height:9px;border-radius:999px;background:#281b2a;overflow:hidden}.tm-meter i{display:block;width:0;height:100%;background:linear-gradient(90deg,var(--violet),var(--ink),var(--gold));transition:width .2s}.tm-accuracy{font-size:2rem;font-weight:1000}.tm-accuracy.good{color:#6ff0b2}.tm-designs{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.tm-designs button{display:grid;place-items:center;min-height:62px;border-radius:11px;font-size:1.5rem}.tm-designs button.active{border-color:var(--gold);background:#302016}.tm-action{width:100%;margin-top:20px;padding:14px;border:0;border-radius:12px;background:linear-gradient(135deg,#bd3d9b,#7e40e2);color:#fff;font-weight:1000;text-transform:uppercase}.tm-action.secondary{margin-top:8px;background:#241727;color:#bcaebf}.tm-tip{padding:12px;margin-top:15px;border:1px solid #39263b;border-radius:11px;color:#8f8292;font-size:.67rem;line-height:1.5}.tm-results{position:absolute;z-index:10;inset:0;display:grid;place-items:center;padding:20px;background:rgba(7,4,8,.86);backdrop-filter:blur(12px)}.tm-results[hidden]{display:none}.tm-card{width:min(430px,100%);padding:30px;border:1px solid #694267;border-radius:24px;background:linear-gradient(145deg,#251329,#100b14);text-align:center;box-shadow:0 25px 80px #000}.tm-card .grade{display:grid;place-items:center;width:88px;height:88px;margin:auto;border:3px solid var(--gold);border-radius:50%;color:var(--gold);font-size:3rem;font-weight:1000}.tm-card h2{margin:17px 0 6px;font-size:2rem}.tm-card p{color:#afa1b1}.tm-result-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:20px 0}.tm-result-grid div{padding:12px 5px;border:1px solid #3c293e;border-radius:10px}.tm-result-grid b{display:block;font-size:1.1rem}.tm-result-grid small{color:#8f8191;font-size:.58rem;text-transform:uppercase}.tm-toast{position:fixed;z-index:30;left:50%;bottom:24px;transform:translate(-50%,20px);padding:11px 16px;border:1px solid #865481;border-radius:999px;background:#1e1021;opacity:0;transition:.2s;pointer-events:none}.tm-toast.show{opacity:1;transform:translate(-50%,0)}
    @media(max-width:880px){.tm-game{grid-template-columns:1fr}.tm-stage{min-height:auto;padding:16px}.tm-panel{border-left:0;border-top:1px solid #3a263b}.tm-panel-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}.tm-action{grid-column:1/-1}.tm-status{align-items:flex-start}.tm-metrics{gap:12px}.tm-metric:first-child{display:none}}@media(max-width:560px){.tm-main{width:100%;padding:0}.tm-topbar{padding:12px}.tm-save{display:none}.tm-shell{border-radius:0;border-left:0;border-right:0}.tm-status{padding:10px 12px}.tm-client strong{font-size:.8rem}.tm-stage{padding:9px}.tm-canvas-wrap{border-width:6px;border-radius:20px}.tm-panel{padding:17px 14px}.tm-panel-grid{display:block}.tm-results{position:fixed}.tm-hint{bottom:10px;font-size:.58rem}.tm-metrics{font-size:.75rem}}
  </style>
</head>
<body class="bos-page tm-page">
<main class="tm-main">
  <div class="tm-topbar"><a class="tm-back" href="/beyond-games/">← Beyond Games</a><div class="tm-brand">Beyond <b>Tattoo</b> / Master</div><span class="tm-save">● Progress saved locally</span></div>
  <section class="tm-shell">
    <header class="tm-status">
      <div class="tm-client"><span class="tm-avatar">🧑🏽</span><div><small>Client 01 · First session</small><strong id="clientName">Kai wants a clean celestial piece</strong></div></div>
      <div class="tm-metrics"><div class="tm-metric"><small>Time</small><strong id="timer">01:30</strong></div><div class="tm-metric"><small>Accuracy</small><strong id="topAccuracy">0%</strong></div><div class="tm-metric reputation"><small>Reputation</small><strong id="rep">0 ★</strong></div></div>
    </header>
    <div class="tm-game">
      <section class="tm-stage">
        <div class="tm-canvas-wrap" id="canvasWrap">
          <canvas id="guideCanvas" width="800" height="800" aria-hidden="true"></canvas>
          <canvas id="inkCanvas" width="800" height="800" aria-label="Tattoo tracing canvas"></canvas>
          <span class="tm-hint" id="hint">Press and trace the purple stencil lines</span>
        </div>
        <div class="tm-results" id="results" hidden>
          <div class="tm-card"><div class="grade" id="grade">A</div><h2 id="resultTitle">Clean work.</h2><p id="resultCopy">Kai loves the piece. Your studio reputation is growing.</p><div class="tm-result-grid"><div><b id="finalAccuracy">0%</b><small>Accuracy</small></div><div><b id="finalCoverage">0%</b><small>Coverage</small></div><div><b id="earnedRep">+0</b><small>Reputation</small></div></div><button class="tm-action" id="nextClient">Next client</button></div>
        </div>
      </section>
      <aside class="tm-panel"><h1>Tattoo <span>Master</span></h1><p class="tm-brief">Follow the stencil. Keep the needle centered and choose the right pressure for clean, confident lines.</p>
        <div class="tm-panel-grid">
          <div><div class="tm-label"><span>Needle pressure</span><span id="pressureText">Medium · ideal</span></div><div class="tm-pressure"><button data-pressure="light">Light</button><button class="active" data-pressure="medium">Medium</button><button data-pressure="heavy">Heavy</button></div></div>
          <div><div class="tm-label"><span>Line coverage</span><span id="coverageText">0%</span></div><div class="tm-meter"><i id="coverageBar"></i></div><div class="tm-label"><span>Current accuracy</span></div><div class="tm-accuracy" id="accuracy">0%</div></div>
          <div><div class="tm-label"><span>Stencil</span><span>Choose design</span></div><div class="tm-designs"><button class="active" data-design="rose" aria-label="Celestial rose">✥</button><button data-design="serpent" aria-label="Serpent">〽</button><button data-design="moon" aria-label="Moon phases">☾</button></div></div>
          <button class="tm-action" id="finish">Finish session</button><button class="tm-action secondary" id="clear">Clear ink</button>
        </div>
        <p class="tm-tip"><b>Artist tip:</b> Medium pressure gives the cleanest line. Heavy pressure is wider but punishes mistakes; light pressure is precise but slower.</p>
      </aside>
    </div>
  </section>
</main>
<div class="tm-toast" id="toast">New best score saved</div>
<script>
(()=>{
  const guide=document.querySelector('#guideCanvas'),ink=document.querySelector('#inkCanvas'),g=guide.getContext('2d'),ctx=ink.getContext('2d');
  const W=800, paths={
    rose:[[[400,128],[445,194],[514,187],[488,250],[546,291],[475,320],[478,390],[412,351],[354,392],[350,321],[280,292],[338,247],[313,184],[383,195],[400,128]],[[400,351],[420,456],[386,565],[420,680]],[[398,480],[315,438],[270,470],[333,510]],[[394,542],[474,502],[523,535],[457,575]]],
    serpent:[[[247,173],[340,125],[449,157],[515,236],[479,311],[377,323],[319,377],[344,454],[446,475],[508,533],[491,620],[405,669],[315,640],[286,580]],[[247,173],[214,224],[257,246],[306,214]],[[286,580],[239,624],[286,650]],[[454,161],[495,118]],[[465,174],[526,151]]],
    moon:[[[391,135],[315,166],[262,232],[240,316],[255,409],[307,487],[382,527],[471,521],[539,471],[499,475],[421,450],[369,389],[351,314],[366,238],[415,181],[471,145],[391,135]],[[575,245],[592,274],[624,279],[600,301],[607,333],[576,316],[548,333],[554,301],[531,279],[562,274],[575,245]],[[244,545],[277,576],[244,607],[211,576],[244,545]]]
  };
  let design='rose',pressure='medium',drawing=false,last=null,samples=0,hits=0,coverage=new Set(),seconds=90,ended=false,tick;
  const $=s=>document.querySelector(s), scalePoint=e=>{const r=ink.getBoundingClientRect();return[(e.clientX-r.left)*W/r.width,(e.clientY-r.top)*W/r.height]};
  function drawGuide(){g.clearRect(0,0,W,W);g.lineCap='round';g.lineJoin='round';g.strokeStyle='rgba(137,55,160,.28)';g.lineWidth=34;paths[design].forEach(p=>{g.beginPath();p.forEach((v,i)=>i?g.lineTo(...v):g.moveTo(...v));g.stroke()});g.strokeStyle='#8b459d';g.lineWidth=5;g.setLineDash([13,10]);paths[design].forEach(p=>{g.beginPath();p.forEach((v,i)=>i?g.lineTo(...v):g.moveTo(...v));g.stroke()});g.setLineDash([])}
  function nearest(x,y){let best=999;paths[design].forEach(p=>p.slice(1).forEach((b,i)=>{const a=p[i],dx=b[0]-a[0],dy=b[1]-a[1],t=Math.max(0,Math.min(1,((x-a[0])*dx+(y-a[1])*dy)/(dx*dx+dy*dy))),px=a[0]+t*dx,py=a[1]+t*dy;best=Math.min(best,Math.hypot(x-px,y-py))}));return best}
  function update(){const acc=samples?Math.round(hits/samples*100):0,cov=Math.min(100,Math.round(coverage.size/135*100));$('#accuracy').textContent=acc+'%';$('#accuracy').classList.toggle('good',acc>=80);$('#topAccuracy').textContent=acc+'%';$('#coverageText').textContent=cov+'%';$('#coverageBar').style.width=cov+'%'}
  function start(e){if(ended)return;drawing=true;last=scalePoint(e);ink.setPointerCapture?.(e.pointerId);$('#hint').classList.add('hidden')}
  function move(e){if(!drawing||ended)return;const p=scalePoint(e),dist=nearest(...p),width={light:8,medium:14,heavy:23}[pressure],tolerance={light:22,medium:28,heavy:34}[pressure];ctx.strokeStyle=pressure==='heavy'?'#24111d':'#181117';ctx.lineWidth=width;ctx.lineCap='round';ctx.lineJoin='round';ctx.beginPath();ctx.moveTo(...last);ctx.lineTo(...p);ctx.stroke();const steps=Math.max(1,Math.ceil(Math.hypot(p[0]-last[0],p[1]-last[1])/8));for(let i=0;i<steps;i++){samples++;if(dist<tolerance)hits++;if(dist<38)coverage.add(Math.round(p[0]/18)+':'+Math.round(p[1]/18))}last=p;update()}
  function stop(){drawing=false;last=null}
  function reset(full=true){ctx.clearRect(0,0,W,W);samples=hits=0;coverage.clear();ended=false;$('#results').hidden=true;$('#hint').classList.remove('hidden');if(full){seconds=90;clearInterval(tick);tick=setInterval(time,1000)}update()}
  function time(){seconds--;$('#timer').textContent='0'+Math.floor(seconds/60)+':'+String(seconds%60).padStart(2,'0');if(seconds<=0)finish()}
  function finish(){if(ended)return;ended=true;clearInterval(tick);const acc=samples?Math.round(hits/samples*100):0,cov=Math.min(100,Math.round(coverage.size/135*100)),score=Math.round(acc*.7+cov*.3),grade=score>=90?'S':score>=80?'A':score>=68?'B':score>=50?'C':'D',earned=Math.max(1,Math.round(score/8));let rep=Number(localStorage.getItem('tattoo-master-rep')||0)+earned;localStorage.setItem('tattoo-master-rep',rep);$('#rep').textContent=rep+' ★';$('#grade').textContent=grade;$('#finalAccuracy').textContent=acc+'%';$('#finalCoverage').textContent=cov+'%';$('#earnedRep').textContent='+'+earned;$('#resultTitle').textContent=score>=80?'Clean work.':score>=60?'Solid first pass.':'Keep practicing.';$('#resultCopy').textContent=score>=80?'Your client loves the piece. Your studio reputation is growing.':'Build coverage while keeping your needle closer to the stencil.';$('#results').hidden=false;const best=Number(localStorage.getItem('tattoo-master-best')||0);if(score>best){localStorage.setItem('tattoo-master-best',score);$('#toast').classList.add('show');setTimeout(()=>$('#toast').classList.remove('show'),1800)}}
  ink.addEventListener('pointerdown',start);ink.addEventListener('pointermove',move);ink.addEventListener('pointerup',stop);ink.addEventListener('pointercancel',stop);
  document.querySelectorAll('[data-pressure]').forEach(b=>b.onclick=()=>{pressure=b.dataset.pressure;document.querySelectorAll('[data-pressure]').forEach(x=>x.classList.toggle('active',x===b));$('#pressureText').textContent={light:'Light · precise',medium:'Medium · ideal',heavy:'Heavy · risky'}[pressure]});
  document.querySelectorAll('[data-design]').forEach(b=>b.onclick=()=>{design=b.dataset.design;document.querySelectorAll('[data-design]').forEach(x=>x.classList.toggle('active',x===b));drawGuide();reset()});
  $('#finish').onclick=finish;$('#clear').onclick=()=>reset(false);$('#nextClient').onclick=()=>reset();$('#rep').textContent=(localStorage.getItem('tattoo-master-rep')||0)+' ★';drawGuide();reset();
})();
</script>
<?php bos_page_end(); ?>
