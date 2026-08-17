<?php
declare(strict_types=1);
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$streamEndpoints = [
    'classic-cinema' => 'api/movies-live.php',
    'beyond-cartoons' => 'api/beyond-cartoons-live.php',
    'yugioh-tv' => 'api/anime-live.php',
    'classic-cartoon-theater' => 'api/classic-live.php',
    'space-tv' => 'api/space-live.php',
];
$streamEndpoint = $streamEndpoints[$slug] ?? ('api/channel-stream.php?slug=' . rawurlencode($slug));
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="dark">
  <title>Beyond TV Live Player</title>
  <style>
    *{box-sizing:border-box}html,body{height:100%;margin:0;background:#050715;color:#fff;font-family:Inter,system-ui,sans-serif}body{overflow:hidden}.player,video{width:100%;height:100%}.player{position:relative;background:radial-gradient(circle at 50% 15%,#37205d,#070916 62%)}video{display:block;object-fit:contain;background:#050715}.status{position:absolute;left:18px;right:18px;bottom:18px;display:flex;align-items:center;gap:10px;padding:10px 13px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(5,7,21,.78);backdrop-filter:blur(14px);font-size:13px;transition:opacity .35s ease,transform .35s ease}.status.is-hiding{opacity:0;transform:translateY(8px);pointer-events:none}.status[hidden]{display:none}.status i{flex:0 0 auto;width:9px;height:9px;border-radius:50%;background:#b8e600;box-shadow:0 0 12px #b8e600}.status b{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.countdown{display:inline-grid;flex:0 0 auto;place-items:center;min-width:25px;height:25px;margin-left:auto;padding:0 7px;border:1px solid rgba(184,230,0,.35);border-radius:999px;background:rgba(184,230,0,.12);color:#d9ff45;font-size:11px;font-weight:900;font-variant-numeric:tabular-nums}.error{color:#ffd1da}.error .countdown{display:none}.unmute{position:absolute;right:18px;top:18px;border:1px solid rgba(255,255,255,.24);border-radius:999px;background:rgba(5,7,21,.82);color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}
  </style>
</head>
<body>
<main class="player">
  <video id="video" controls playsinline autoplay muted preload="metadata"></video>
  <button class="unmute" id="unmute" type="button">🔊 Tap for sound</button>
  <div class="status" id="status" role="status" aria-live="polite"><i></i><b>Tuning Beyond TV…</b><span class="countdown" id="status-countdown" hidden aria-label="Overlay countdown"></span></div>
</main>
<script>
(()=>{'use strict';
const endpoint=<?=json_encode($streamEndpoint, JSON_UNESCAPED_SLASHES)?>;
const video=document.getElementById('video');
const status=document.getElementById('status');
const label=status.querySelector('b');
const countdown=document.getElementById('status-countdown');
const unmute=document.getElementById('unmute');
let sources=[],index=0,offset=0,failures=0,countdownTimer=0,hideTimer=0;

function clearStatusTimers(){
  if(countdownTimer)window.clearInterval(countdownTimer);
  if(hideTimer)window.clearTimeout(hideTimer);
  countdownTimer=0;
  hideTimer=0;
}

function showStatus(message,{error=false}={}){
  clearStatusTimers();
  status.hidden=false;
  status.classList.remove('is-hiding');
  status.classList.toggle('error',error);
  countdown.hidden=true;
  label.textContent=message;
}

function hideStatusAfter(seconds=5){
  clearStatusTimers();
  let remaining=seconds;
  countdown.hidden=false;
  countdown.textContent=String(remaining);
  countdown.setAttribute('aria-label',`Title overlay hides in ${remaining} seconds`);
  countdownTimer=window.setInterval(()=>{
    remaining-=1;
    countdown.textContent=String(Math.max(remaining,0));
    countdown.setAttribute('aria-label',`Title overlay hides in ${Math.max(remaining,0)} seconds`);
    if(remaining<=0){
      window.clearInterval(countdownTimer);
      countdownTimer=0;
    }
  },1000);
  hideTimer=window.setTimeout(()=>{
    status.classList.add('is-hiding');
    window.setTimeout(()=>{status.hidden=true},350);
  },seconds*1000);
}

function playCurrent(){
  const item=sources[index];
  if(!item){
    showStatus('This channel is temporarily unavailable.',{error:true});
    return;
  }
  showStatus(item.title||'Beyond TV live');
  video.src=item.url;
  video.onloadedmetadata=()=>{
    failures=0;
    if(offset>0&&Number.isFinite(video.duration))video.currentTime=Math.min(offset,Math.max(0,video.duration-2));
    offset=0;
    video.play().catch(()=>{});
  };
  video.onplaying=()=>hideStatusAfter(5);
  video.onerror=()=>{
    failures+=1;
    if(failures>=sources.length){
      showStatus('The video provider is temporarily unavailable.',{error:true});
      return;
    }
    index=(index+1)%sources.length;
    offset=0;
    playCurrent();
  };
  video.onended=()=>{
    index=(index+1)%sources.length;
    offset=0;
    playCurrent();
  };
}

fetch(endpoint,{cache:'default'})
  .then(response=>{if(!response.ok)throw new Error('Channel unavailable');return response.json()})
  .then(data=>{
    sources=Array.isArray(data.sources)?data.sources:[];
    offset=Number(data.start_offset)||0;
    if(window.parent!==window)window.parent.postMessage({type:'beyond-tv:state',slug:<?=json_encode($slug)?>,state:data.state||data},window.location.origin);
    playCurrent();
  })
  .catch(error=>showStatus(error.message||'Channel unavailable',{error:true}));

unmute.onclick=()=>{video.muted=false;video.volume=1;video.play().catch(()=>{});unmute.hidden=true};
})();
</script>
</body>
</html>
