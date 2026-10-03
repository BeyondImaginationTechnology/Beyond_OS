<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/_header.php';
$episode = json_decode((string)@file_get_contents(dirname(__DIR__, 3) . '/tools/daily-stencil-video/public/daily-breath-story.json'), true);
if (!is_array($episode)) $episode = [];
$csrf = Auth::csrf();
?>
<style>
  .dbtv{max-width:920px;margin:0 auto;padding:32px 22px 70px}.dbtv .card{padding:26px;border:1px solid #ffffff24;border-radius:20px;background:#11271c;color:#f4f7ef}.dbtv h1{margin:0;font:500 clamp(34px,5vw,58px)/1 Georgia,serif}.dbtv p{line-height:1.6;color:#cad6cb}.dbtv .meta{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:22px 0}.dbtv .meta div{padding:14px;border-radius:12px;background:#08180f}.dbtv .meta strong{display:block;color:#d9dc96}.dbtv button{padding:13px 18px;border:0;border-radius:10px;background:#d9dc96;color:#172418;font-weight:850;cursor:pointer}.dbtv button:disabled{opacity:.6;cursor:wait}.dbtv #status{min-height:22px;margin-top:16px}.dbtv #status.error{color:#ffb4ad}@media(max-width:640px){.dbtv .meta{grid-template-columns:1fr}}
</style>
<main class="dbtv"><section class="card"><p>DAILY BREATH TV · CHANNEL 14</p><h1><?=DailyStudio::esc((string)($episode['title'] ?? 'First episode'))?></h1><p><?=DailyStudio::esc((string)($episode['subtitle'] ?? 'Bible devotional'))?></p><div class="meta"><div><strong>Show</strong>Daily Breath</div><div><strong>Rotation</strong>24-hour original channel</div><div><strong>Voice</strong>ElevenLabs test key</div></div><p>Publishing renders the episode with its ElevenLabs voiceover, saves the MP4 in Daily Breath’s video library, and makes it the first item in the live Channel 14 rotation.</p><button type="button" id="publish">Render and place in live rotation</button><p id="status" role="status" aria-live="polite"></p></section></main>
<script>
(()=>{const episode=<?=json_encode($episode,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>,button=document.getElementById('publish'),status=document.getElementById('status');button.addEventListener('click',async()=>{button.disabled=true;status.className='';status.textContent='Recording with ElevenLabs and rendering the live episode…';try{const response=await fetch('api/render-dailybreath-story.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':<?=json_encode($csrf)?>},body:JSON.stringify({...episode,recordVoiceover:true,publishToDailyBreathTv:true})});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.error||'The episode could not be published.');status.textContent='Live now: '+result.episode.title+'. Open Beyond TV to watch it.';button.textContent='Published to Daily Breath TV';}catch(error){status.className='error';status.textContent=error instanceof Error?error.message:'The episode could not be published.';button.disabled=false;}})})();
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
