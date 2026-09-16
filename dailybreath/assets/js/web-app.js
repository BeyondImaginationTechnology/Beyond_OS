(()=>{
  const settings={theme:'fall',reduceMotion:false,...JSON.parse(localStorage.getItem('dailybreath.settings')||'{}')};
  const requestedTheme=settings.theme||'system';
  const normalizedTheme=requestedTheme==='light'?'dawn':requestedTheme==='dark'?'dusk':requestedTheme==='lilac'?'dawn':requestedTheme;
  const resolvedTheme=normalizedTheme==='system'
    ? (matchMedia('(prefers-color-scheme: dark)').matches?'dusk':'dawn')
    : normalizedTheme;
  document.documentElement.dataset.dbTheme=['dawn','dusk','fall'].includes(resolvedTheme)?resolvedTheme:'dawn';
  if(settings.reduceMotion)document.documentElement.style.scrollBehavior='auto';
  window.DailyBreath={settings,save(next){Object.assign(settings,next);localStorage.setItem('dailybreath.settings',JSON.stringify(settings));},toast(message){let el=document.querySelector('.db-toast');if(!el){el=document.createElement('div');el.className='db-toast';el.setAttribute('role','status');document.body.append(el)}el.textContent=message;el.classList.add('show');clearTimeout(el._timer);el._timer=setTimeout(()=>el.classList.remove('show'),2600)}};
  if('serviceWorker'in navigator){
    navigator.serviceWorker.register('/dailybreath/service-worker.js',{scope:'/dailybreath/',updateViaCache:'none'}).then(reg=>{
      reg.addEventListener('updatefound',()=>{const worker=reg.installing;if(!worker)return;worker.addEventListener('statechange',()=>{if(worker.state==='installed'&&navigator.serviceWorker.controller)window.DailyBreath.toast('Daily Breath updated. Reload for the latest version.')})});
    }).catch(()=>{});
  }
  let promptEvent=null;const install=document.createElement('button');install.className='db-install';install.type='button';install.setAttribute('aria-label','Install Daily Breath as an app');install.innerHTML='<span aria-hidden="true">＋</span> Install Daily Breath';document.body.append(install);
  const standalone=matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
  const ios=/iphone|ipad|ipod/i.test(navigator.userAgent)&&/safari/i.test(navigator.userAgent)&&!/crios|fxios/i.test(navigator.userAgent);
  addEventListener('beforeinstallprompt',event=>{event.preventDefault();promptEvent=event;install.classList.add('show')});
  if(ios&&!standalone){install.innerHTML='<span aria-hidden="true">＋</span> Add to Home Screen';install.classList.add('show')}
  install.addEventListener('click',async()=>{if(promptEvent){promptEvent.prompt();await promptEvent.userChoice;promptEvent=null;install.classList.remove('show');return}if(ios)window.DailyBreath.toast('Open Share, then choose Add to Home Screen.');});
  addEventListener('appinstalled',()=>{install.classList.remove('show');window.DailyBreath.toast('Daily Breath installed on this device.')});
  const entries=[...document.querySelectorAll('.entry')];
  if(entries.length){
    const heading=document.createElement('div');heading.className='db-journal-tools';heading.style.cssText='display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0 0 14px';
    const exportButton=document.createElement('button');exportButton.type='button';exportButton.className='btn';exportButton.textContent='Export reflections';exportButton.addEventListener('click',()=>{const data=entries.map(entry=>({date:entry.querySelector('time')?.textContent.trim()||'',mood:entry.querySelector('.mood')?.textContent.trim()||'',reflection:[...entry.querySelectorAll('p')].map(p=>p.textContent.trim()).join('\n')}));const blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download='dailybreath-reflections.json';link.click();URL.revokeObjectURL(url)});
    heading.append(exportButton);const trend=document.createElement('span');trend.className='privacy';const counts={};entries.forEach(entry=>{const mood=entry.querySelector('.mood')?.textContent.trim();if(mood)counts[mood]=(counts[mood]||0)+1});trend.textContent=Object.keys(counts).length?'Mood trend: '+Object.entries(counts).map(item=>item[0]+' '+item[1]).join(' · '):'Add a mood to see trends.';heading.append(trend);const recent=entries[0]?.closest('.card');if(recent)recent.prepend(heading);
    entries.forEach(entry=>{const actions=entry.querySelector('.entry-top');const deleteForm=actions?.querySelector('form');const id=deleteForm?.querySelector('input[name=entry_id]')?.value;if(!actions||!id)return;const edit=document.createElement('button');edit.type='button';edit.className='delete';edit.textContent='Edit';edit.addEventListener('click',()=>{const current=[...entry.querySelectorAll('p')].map(p=>p.textContent.trim()).join('\n');const next=prompt('Edit this reflection:',current);if(next===null||!next.trim())return;const mood=prompt('Mood (optional):',entry.querySelector('.mood')?.textContent.trim()||'')??'';const form=document.createElement('form');form.method='post';form.innerHTML='<input type="hidden" name="csrf" value="'+(document.querySelector('input[name=csrf]')?.value||'')+'"><input type="hidden" name="action" value="update_journal"><input type="hidden" name="entry_id" value="'+id+'"><input type="hidden" name="entry" value=""><input type="hidden" name="mood" value="">';form.elements.entry.value=next.trim();form.elements.mood.value=mood.trim();document.body.append(form);form.submit()});actions.insertBefore(edit,deleteForm)});
  }
})();
