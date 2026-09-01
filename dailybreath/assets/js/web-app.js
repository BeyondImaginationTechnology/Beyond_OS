(()=>{
  const settings={theme:'system',reduceMotion:false,...JSON.parse(localStorage.getItem('dailybreath.settings')||'{}')};
  const requestedTheme=settings.theme||'system';
  const normalizedTheme=requestedTheme==='light'?'dawn':requestedTheme==='dark'?'dusk':requestedTheme;
  const resolvedTheme=normalizedTheme==='system'
    ? (matchMedia('(prefers-color-scheme: dark)').matches?'dusk':'dawn')
    : normalizedTheme;
  document.documentElement.dataset.dbTheme=['dawn','dusk','lilac'].includes(resolvedTheme)?resolvedTheme:'dawn';
  if(settings.reduceMotion)document.documentElement.style.scrollBehavior='auto';
  window.DailyBreath={settings,save(next){Object.assign(settings,next);localStorage.setItem('dailybreath.settings',JSON.stringify(settings));},toast(message){let el=document.querySelector('.db-toast');if(!el){el=document.createElement('div');el.className='db-toast';el.setAttribute('role','status');document.body.append(el)}el.textContent=message;el.classList.add('show');clearTimeout(el._timer);el._timer=setTimeout(()=>el.classList.remove('show'),2600)}};
  if('serviceWorker'in navigator)navigator.serviceWorker.register('/dailybreath/service-worker.js',{scope:'/dailybreath/'}).catch(()=>{});
  let promptEvent=null;const install=document.createElement('button');install.className='db-install';install.type='button';install.innerHTML='<span>＋</span> Install DailyBreath';document.body.append(install);
  addEventListener('beforeinstallprompt',event=>{event.preventDefault();promptEvent=event;install.classList.add('show')});
  install.addEventListener('click',async()=>{if(!promptEvent)return;promptEvent.prompt();await promptEvent.userChoice;promptEvent=null;install.classList.remove('show')});
  addEventListener('appinstalled',()=>window.DailyBreath.toast('DailyBreath installed on this device.'));
})();
