(()=>{
  const buttons=document.querySelectorAll('[data-dailybreath-narration]');
  if(!buttons.length)return;
  const cacheName='dailybreath-narration-v2.3';
  const cacheRequest=button=>new Request(`${location.origin}/dailybreath/offline-narration/${encodeURIComponent(button.dataset.date)}-${encodeURIComponent(button.dataset.tradition)}-${encodeURIComponent(button.dataset.locale)}-${encodeURIComponent(button.dataset.contentHash)}.mp3`);
  const setStatus=(button,message)=>{const status=button.parentElement.querySelector('[data-dailybreath-narration-status]');if(status)status.textContent=message};
  const playBlob=async(button,audio,blob)=>{const previous=audio.dataset.objectUrl;if(previous)URL.revokeObjectURL(previous);const source=URL.createObjectURL(blob);audio.dataset.objectUrl=source;audio.src=source;audio.hidden=false;audio.load();await audio.play();button.textContent='Play narration again'};
  buttons.forEach(button=>button.addEventListener('click',async()=>{
    const audio=button.parentElement.querySelector('[data-dailybreath-audio]');
    if(!audio)return;
    const original=button.textContent;
    button.disabled=true;
    button.textContent='Preparing narration…';
    setStatus(button,'');
    try{
      const key=cacheRequest(button);
      let cache=null,cached=null;
      if('caches'in window){try{cache=await caches.open(cacheName);cached=await cache.match(key)}catch{}}
      if(cached){await playBlob(button,audio,await cached.blob());setStatus(button,'Saved for offline playback.');return}
      if(!navigator.onLine)throw new Error('Connect to the internet once to prepare this narration for offline playback.');
      let audioUrl=audio.getAttribute('src');
      if(!audioUrl){
        const response=await fetch(button.dataset.api,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({date:button.dataset.date,tradition:button.dataset.tradition,locale:button.dataset.locale,content_hash:button.dataset.contentHash})});
        const data=await response.json().catch(()=>({}));
        if(!response.ok||!data.audio_url)throw new Error(data.error||'Narration is temporarily unavailable.');
        audioUrl=data.audio_url;
      }
      const response=await fetch(audioUrl,{cache:'no-store'});
      if(!response.ok)throw new Error('Narration audio could not be downloaded.');
      const blob=await response.blob();
      if(cache)try{await cache.put(key,new Response(blob,{headers:{'Content-Type':'audio/mpeg'}}))}catch{}
      await playBlob(button,audio,blob);
      setStatus(button,'Saved for offline playback.');
    }catch(error){
      button.textContent=original;
      setStatus(button,error instanceof Error?error.message:'Narration is temporarily unavailable.');
      window.DailyBreath?.toast(error instanceof Error?error.message:'Narration is temporarily unavailable.');
    }finally{button.disabled=false}
  }));
})();
