<?php
declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="dark">
  <title>Beyond Movies Live Player</title>
  <style>
    *{box-sizing:border-box}html,body{height:100%;margin:0;background:#03050b;color:#fff;font-family:Inter,system-ui,sans-serif}body{overflow:hidden}.player,video{width:100%;height:100%}.player{position:relative;background:radial-gradient(circle at 50% 12%,#553718,#080a12 65%)}video{display:block;object-fit:contain;background:#03050b}.status{position:absolute;left:18px;right:18px;bottom:18px;display:flex;align-items:center;gap:10px;padding:10px 13px;border:1px solid rgba(255,255,255,.2);border-radius:12px;background:rgba(4,6,13,.82);backdrop-filter:blur(14px);font-size:13px}.status i{width:9px;height:9px;border-radius:50%;background:#f5bd48;box-shadow:0 0 14px #f5bd48}.status b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.error{color:#ffd1da}.unmute{position:absolute;right:18px;top:18px;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(4,6,13,.84);color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}
  </style>
</head>
<body>
<main class="player">
  <video id="video" controls playsinline autoplay muted preload="metadata"></video>
  <button class="unmute" id="unmute" type="button">🔊 Tap for sound</button>
  <div class="status" id="status"><i></i><b>Opening Beyond Movies…</b></div>
</main>
<script>
(()=>{'use strict';const endpoint='/beyond-tv/api/movies-live.php',video=document.getElementById('video'),status=document.getElementById('status'),label=status.querySelector('b'),unmute=document.getElementById('unmute');let sources=[],index=0,offset=0,failures=0;function play(){const item=sources[index];if(!item){status.classList.add('error');label.textContent='The movie channel is temporarily unavailable.';return}label.textContent=item.title+' · General audience presentation';video.src=item.url;video.onloadedmetadata=()=>{failures=0;if(offset>0&&Number.isFinite(video.duration))video.currentTime=Math.min(offset,Math.max(0,video.duration-2));offset=0;video.play().catch(()=>{})};video.onerror=()=>{failures+=1;if(failures>=sources.length){status.classList.add('error');label.textContent='The movie provider is temporarily unavailable.';return}index=(index+1)%sources.length;offset=0;play()};video.onended=()=>{index=(index+1)%sources.length;offset=0;play()}}fetch(endpoint,{cache:'default'}).then(response=>{if(!response.ok)throw new Error('Movie channel unavailable');return response.json()}).then(data=>{sources=Array.isArray(data.sources)?data.sources:[];offset=Number(data.start_offset)||0;play()}).catch(error=>{status.classList.add('error');label.textContent=error.message||'Movie channel unavailable'});unmute.onclick=()=>{video.muted=false;video.volume=1;video.play().catch(()=>{});unmute.hidden=true}})();
</script>
</body>
</html>
