<?php
declare(strict_types=1);
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$streamEndpoint = 'api/channel-stream.php?slug=' . rawurlencode($slug);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="dark">
  <title>Beyond TV Live Player</title>
  <style>
    *{box-sizing:border-box}html,body{height:100%;margin:0;background:#050715;color:#fff;font-family:Inter,system-ui,sans-serif}body{overflow:hidden}.player,video{width:100%;height:100%}.player{position:relative;background:radial-gradient(circle at 50% 15%,#37205d,#070916 62%)}video{display:block;object-fit:contain;background:#050715}.status{position:absolute;left:18px;right:18px;bottom:18px;display:flex;align-items:center;gap:10px;padding:10px 13px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(5,7,21,.78);backdrop-filter:blur(14px);font-size:13px}.status i{width:9px;height:9px;border-radius:50%;background:#b8e600;box-shadow:0 0 12px #b8e600}.status b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.error{color:#ffd1da}.unmute{position:absolute;right:18px;top:18px;border:1px solid rgba(255,255,255,.24);border-radius:999px;background:rgba(5,7,21,.82);color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}
  </style>
</head>
<body>
<main class="player">
  <video id="video" controls playsinline autoplay muted preload="metadata"></video>
  <button class="unmute" id="unmute" type="button">🔊 Tap for sound</button>
  <div class="status" id="status"><i></i><b>Tuning Beyond TV…</b></div>
</main>
<script>
(()=>{'use strict';const endpoint=<?=json_encode($streamEndpoint, JSON_UNESCAPED_SLASHES)?>,video=document.getElementById('video'),status=document.getElementById('status'),label=status.querySelector('b'),unmute=document.getElementById('unmute');let sources=[],index=0,offset=0,failures=0;function playCurrent(){const item=sources[index];if(!item){status.classList.add('error');label.textContent='This channel is temporarily unavailable.';return}label.textContent=item.title||'Beyond TV live';video.src=item.url;video.onloadedmetadata=()=>{failures=0;if(offset>0&&Number.isFinite(video.duration))video.currentTime=Math.min(offset,Math.max(0,video.duration-2));offset=0;video.play().catch(()=>{})};video.onerror=()=>{failures+=1;if(failures>=sources.length){status.classList.add('error');label.textContent='The video provider is temporarily unavailable.';return}index=(index+1)%sources.length;offset=0;playCurrent()};video.onended=()=>{index=(index+1)%sources.length;offset=0;playCurrent()}}fetch(endpoint,{cache:'default'}).then(response=>{if(!response.ok)throw new Error('Channel unavailable');return response.json()}).then(data=>{sources=Array.isArray(data.sources)?data.sources:[];offset=Number(data.start_offset)||0;playCurrent()}).catch(error=>{status.classList.add('error');label.textContent=error.message||'Channel unavailable'});unmute.onclick=()=>{video.muted=false;video.volume=1;video.play().catch(()=>{});unmute.hidden=true}})();
</script>
</body>
</html>
