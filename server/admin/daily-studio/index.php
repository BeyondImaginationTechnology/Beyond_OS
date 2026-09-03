<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require dirname(__DIR__).'/_header.php';
$apps=[
 'dailybreath'=>['name'=>'DailyBreath','icon'=>'🌿','summary'=>'Faith, recovery, and weekly publishing','tools'=>[['content','Content manager','Verse, devotional, challenge, and academy','dailybreath-content.php'],['breath','Scheduled generator','Generate dated verse content','breath-generator.php'],['recovery-newsletter','Recovery newsletter','Package the weekly shareable issue','recovery-newsletter.php']]],
 'french'=>['name'=>'Beyond French','icon'=>'🇫🇷','summary'=>'Daily language content and expansion','tools'=>[['french','Core generator','Create the daily phrase experience','french-generator.php'],['multilingual','Euro expansion','Build French-to-European language assets','multilingual-generator.php'],['africa','Africa expansion','Prepare the African language set','africa-generator.php'],['french-options','Options','Configure French content settings','french-options.php'],['quest-assets','FrenchQuest assets','Manage Azure quest assets','frenchquest-assets.php']]],
 'space'=>['name'=>'Beyond Space','icon'=>'🪐','summary'=>'Astrology, facts, and space media','tools'=>[['space','Astrology generator','Generate all 12 daily horoscopes','space-generator.php'],['space-facts','Daily fact generator','Build and publish the space fact','space-fact-generator.php']]],
 'ancient'=>['name'=>'Beyond Ancient','icon'=>'🏺','summary'=>'History facts and visual learning','tools'=>[['history','History generator','Create the historical carousel','history-generator.php'],['history-facts','Import 12/55 facts','Edit and publish the daily history fact','history-fact-generator.php']]],
 'shared'=>['name'=>'Shared production','icon'=>'✦','summary'=>'Voice, video, and reusable assets','tools'=>[['voices','Premium voices','Configure shared narration','voice-settings.php'],['video-templates','Video templates','Open production templates','video-templates.php'],['remotion','Remotion renderer','Render a finished media artifact','remotion-renderer.php']]],
 'tattoo'=>['name'=>'Beyond Tattoo','icon'=>'🖋️','summary'=>'Stencils and artist assets','tools'=>[['tattoo-publish','Generate & publish','Publish a stencil library drop','stencil-library.php#publish'],['tattoo-assets','Asset inbox','Review uploaded tattoo assets','tattoo-asset-import.php']]],
];
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css">
<section class="studio-console">
  <header class="console-hero">
    <div>
      <p class="console-eyebrow">Beyond OS · production workspace</p>
      <h1>What are we making <span>today?</span></h1>
      <p>Pick a product, then open the one tool you need. No nested dashboards and no competing themes.</p>
    </div>
    <div class="console-status"><span class="status-dot"></span><div><strong>Studio online</strong><small>Six production rooms</small></div></div>
  </header>
  <nav class="app-switcher" aria-label="Production apps" role="tablist">
    <?php foreach($apps as $key=>$app): ?>
      <button type="button" role="tab" class="app-switch" data-app="<?=DailyStudio::esc($key)?>" aria-selected="false">
        <span class="app-switch-icon"><?=DailyStudio::esc($app['icon'])?></span>
        <span><strong><?=DailyStudio::esc($app['name'])?></strong><small><?=DailyStudio::esc($app['summary'])?></small></span>
      </button>
    <?php endforeach; ?>
  </nav>
  <main class="room" aria-live="polite">
    <div class="room-heading">
      <div><p class="console-eyebrow">Selected room</p><h2 id="roomName">DailyBreath</h2><p id="roomSummary">Faith, recovery, and weekly publishing</p></div>
      <span class="room-count" id="roomCount">3 tools</span>
    </div>
    <div id="toolList" class="tool-list"></div>
  </main>
</section>
<script>
const rooms=<?=json_encode($apps,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
const appButtons=[...document.querySelectorAll('.app-switch')],roomName=document.getElementById('roomName'),roomSummary=document.getElementById('roomSummary'),roomCount=document.getElementById('roomCount'),toolList=document.getElementById('toolList');
let activeApp='dailybreath';
try{activeApp=localStorage.getItem('beyond-studio-app')||activeApp}catch(e){}
function selectApp(key){
  activeApp=rooms[key]?key:'dailybreath';
  const room=rooms[activeApp];
  appButtons.forEach(button=>button.setAttribute('aria-selected',button.dataset.app===activeApp?'true':'false'));
  roomName.textContent=room.name;roomSummary.textContent=room.summary;roomCount.textContent=`${room.tools.length} ${room.tools.length===1?'tool':'tools'}`;
  toolList.innerHTML=room.tools.map((tool,index)=>`<a class="tool-card" href="${tool[3]}"><span class="tool-number">${String(index+1).padStart(2,'0')}</span><span class="tool-icon">${room.icon}</span><span class="tool-copy"><strong>${tool[1]}</strong><small>${tool[2]}</small></span><span class="tool-arrow" aria-hidden="true">→</span></a>`).join('');
  try{localStorage.setItem('beyond-studio-app',activeApp)}catch(e){}
}
appButtons.forEach(button=>button.addEventListener('click',()=>selectApp(button.dataset.app)));
selectApp(activeApp);
</script>
<?php require dirname(__DIR__).'/_footer.php'; ?>
