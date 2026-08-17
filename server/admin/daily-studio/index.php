<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require dirname(__DIR__).'/_header.php';
$tabs=[
  'content'=>['DailyBreath','Content','dailybreath-content.php'],
  'breath'=>['DailyBreath','Scheduled generator','breath-generator.php'],
  'recovery-newsletter'=>['DailyBreath','Recovery newsletter','recovery-newsletter.php'],
  'french'=>['Beyond French','Generator','french-generator.php'],
  'french-options'=>['Beyond French','Options','french-options.php'],
  'space'=>['Beyond Space','Horoscope generator','space-generator.php'],
  'tattoo-publish'=>['Beyond Tattoo','Publish','stencil-library.php#publish'],
  'voices'=>['Shared','Voices','voice-settings.php'],
];
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css"><link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css">
<div class="studio-workspace"><div class="studio-head"><div><p class="studio-eyebrow">Sunset workspace</p><h1>Beyond Studio</h1><p class="muted">Tools are grouped by the app that owns the content.</p></div><a class="btn" id="open-studio-page" href="dailybreath-content.php" target="_blank" rel="noopener">Open tool ↗</a></div>
<nav class="studio-tabs" role="tablist" aria-label="Studio tools"><?php $currentGroup=''; foreach($tabs as $key=>[$group,$label,$url]): if($group!==$currentGroup): $currentGroup=$group; ?><span class="studio-tab-group"><?=DailyStudio::esc($group)?></span><?php endif; ?><button type="button" role="tab" data-studio-tab="<?=DailyStudio::esc($key)?>" data-src="<?=DailyStudio::esc($url)?>" aria-selected="false"><?=DailyStudio::esc($label)?></button><?php endforeach;?></nav>
<div class="studio-frame-shell"><iframe id="studio-frame" title="Studio tool" loading="eager"></iframe></div></div>
<script>(function(){const buttons=[...document.querySelectorAll('[data-studio-tab]')],frame=document.getElementById('studio-frame'),open=document.getElementById('open-studio-page');function select(key){const button=buttons.find(item=>item.dataset.studioTab===key)||buttons[0];buttons.forEach(item=>{const active=item===button;item.setAttribute('aria-selected',active?'true':'false');item.classList.toggle('active',active);});frame.src=button.dataset.src;open.href=button.dataset.src;try{localStorage.setItem('beyond-studio-tab',button.dataset.studioTab)}catch(e){}}let initial='content';try{initial=localStorage.getItem('beyond-studio-tab')||'content'}catch(e){}buttons.forEach(button=>button.addEventListener('click',()=>select(button.dataset.studioTab)));select(initial);})();</script>
<?php require dirname(__DIR__).'/_footer.php'; ?>
