(function(){
'use strict';
const CONFIG_URL='/beyond-tv/api/ad-config.php';
const IMA_URL='https://imasdk.googleapis.com/js/sdkloader/ima3.js';
let configPromise=null;
let sdkPromise=null;
let activeBreak=null;

function loadConfig(){
  if(!configPromise){
    configPromise=fetch(CONFIG_URL,{headers:{Accept:'application/json'}})
      .then(response=>response.ok?response.json():Promise.reject(new Error('Ad configuration unavailable')))
      .catch(()=>({enabled:false,ad_tag_url:'',break_duration:300,label:'Commercial break'}));
  }
  return configPromise;
}

function loadIma(){
  if(window.google?.ima)return Promise.resolve(window.google.ima);
  if(sdkPromise)return sdkPromise;
  sdkPromise=new Promise((resolve,reject)=>{
    const script=document.createElement('script');
    script.src=IMA_URL;
    script.async=true;
    script.onload=()=>window.google?.ima?resolve(window.google.ima):reject(new Error('IMA SDK unavailable'));
    script.onerror=()=>reject(new Error('IMA SDK failed to load'));
    document.head.appendChild(script);
  });
  return sdkPromise;
}

function formatTime(seconds){
  const value=Math.max(0,Math.ceil(seconds));
  return `${Math.floor(value/60)}:${String(value%60).padStart(2,'0')}`;
}

function createOverlay(container,label,experience){
  const overlay=document.createElement('section');
  overlay.className=`btv-ad-break${experience==='breath-hourglass'?' is-breath-hourglass':''}`;
  overlay.setAttribute('aria-label',label);
  const gameExperiences={
    'mini-game':{url:'/beyond-games/bit-runner.php?break=1',title:'Play Bit Runner during the Beyond TV break',eyebrow:'BEYOND TV · MINI-GAME BREAK'},
    'tattoo-stencil':{url:'/beyond-games/tattoo-master.php?break=1',title:'Master a tattoo stencil during the Tattoo Channel break',eyebrow:'TATTOO CHANNEL · MASTER THE STENCIL'}
  };
  const gameExperience=gameExperiences[experience]||gameExperiences['mini-game'];
  overlay.innerHTML=experience==='breath-hourglass'
    ? '<div class="btv-breath-break" data-btv-breath-break><span class="btv-breath-eyebrow">DAILY BREATH TV · BREATH HOURGLASS</span><div class="btv-hourglass" aria-hidden="true"><span class="btv-hourglass-frame"></span><span class="btv-hourglass-top"></span><span class="btv-hourglass-stream"></span><span class="btv-hourglass-bottom"></span></div><strong class="btv-breath-cue" data-btv-breath-cue>Breathe in</strong><p class="btv-breath-copy" data-btv-breath-copy>Settle in. Programming returns shortly.</p><time class="btv-breath-time" data-btv-ad-countdown>5:00</time><small data-btv-ad-label></small><button class="btv-ad-change-channel" type="button" data-btv-change-channel>Change channel</button></div><div class="btv-ad-slot" data-btv-ad-slot></div>'
    : `<iframe class="btv-ad-game" data-btv-ad-game src="${gameExperience.url}" title="${gameExperience.title}" loading="eager" allow="autoplay"></iframe><div class="btv-ad-slot" data-btv-ad-slot></div><div class="btv-ad-filler"><span>${gameExperience.eyebrow}</span><strong data-btv-ad-label></strong><p>Programming resumes in <b data-btv-ad-countdown>5:00</b></p><button class="btv-ad-change-channel" type="button" data-btv-change-channel>Change channel</button></div>`;
  const style=getComputedStyle(container);
  if(style.position==='static')container.style.position='relative';
  container.appendChild(overlay);
  return overlay;
}

function startIma(config,overlay,contentVideo,onAdState,maxSeconds){
  if(!config.enabled||!config.ad_tag_url)return Promise.resolve();
  return loadIma().then(ima=>new Promise((resolve,reject)=>{
    const slot=overlay.querySelector('[data-btv-ad-slot]');
    const displayContainer=new ima.AdDisplayContainer(slot,contentVideo||undefined);
    const loader=new ima.AdsLoader(displayContainer);
    let manager=null;
    let settled=false;
    let requestTimer=window.setTimeout(()=>fail(new Error('Ad request timed out')),10000);
    let playbackTimer=0;
    const finish=()=>{
      if(settled)return;
      settled=true;
      window.clearTimeout(requestTimer);
      window.clearTimeout(playbackTimer);
      onAdState(false);
      try{manager?.destroy()}catch(_){}
      resolve();
    };
    const fail=error=>{
      onAdState(false);
      try{manager?.destroy()}catch(_){}
      if(settled)return;
      settled=true;
      window.clearTimeout(requestTimer);
      window.clearTimeout(playbackTimer);
      reject(error instanceof Error?error:new Error('Ad playback failed'));
    };
    loader.addEventListener(ima.AdErrorEvent.Type.AD_ERROR,event=>fail(event.getError()),false);
    loader.addEventListener(ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED,event=>{
      try{
        manager=event.getAdsManager(contentVideo||document.createElement('video'));
        if(settled){manager.destroy();return}
        manager.addEventListener(ima.AdErrorEvent.Type.AD_ERROR,event=>fail(event.getError()));
        manager.addEventListener(ima.AdEvent.Type.STARTED,()=>{
          window.clearTimeout(requestTimer);
          onAdState(true);
          playbackTimer=window.setTimeout(finish,maxSeconds*1000);
        });
        manager.addEventListener(ima.AdEvent.Type.ALL_ADS_COMPLETED,finish);
        manager.addEventListener(ima.AdEvent.Type.CONTENT_RESUME_REQUESTED,finish);
        const width=Math.max(320,overlay.clientWidth);
        const height=Math.max(180,overlay.clientHeight);
        manager.init(width,height,ima.ViewMode.NORMAL);
        manager.start();
      }catch(error){fail(error)}
    },false);
    try{
      displayContainer.initialize();
      const request=new ima.AdsRequest();
      request.adTagUrl=config.ad_tag_url;
      request.linearAdSlotWidth=Math.max(320,overlay.clientWidth);
      request.linearAdSlotHeight=Math.max(180,overlay.clientHeight);
      request.nonLinearAdSlotWidth=request.linearAdSlotWidth;
      request.nonLinearAdSlotHeight=Math.max(90,Math.round(request.linearAdSlotHeight/3));
      request.setAdWillAutoPlay(true);
      request.setAdWillPlayMuted(Boolean(contentVideo?.muted));
      request.setContinuousPlayback(true);
      loader.requestAds(request);
    }catch(error){fail(error)}
  }));
}

function startNativeAd(onAdState){
  const bridge=window.BeyondTVNativeAds;
  if(!bridge?.postMessage)return Promise.resolve(false);
  return new Promise(resolve=>{
    const id=`break-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    let started=false;
    let settled=false;
    const prior=bridge.onmessage;
    let timer=0;
    const finish=()=>{
      if(settled)return;
      settled=true;
      window.clearTimeout(timer);
      onAdState(false);
      bridge.onmessage=prior;
      resolve(started);
    };
    bridge.onmessage=event=>{
      let data;
      try{data=JSON.parse(event.data)}catch(_){return}
      if(data.id!==id)return;
      if(data.type==='started'){
        started=true;
        onAdState(true);
        window.clearTimeout(timer);
        timer=window.setTimeout(finish,120000);
      }else if(data.type==='complete'||data.type==='failed')finish();
    };
    timer=window.setTimeout(()=>{
      try{bridge.postMessage(JSON.stringify({type:'cancel-break-ad',id}))}catch(_){}
      finish();
    },10000);
    try{bridge.postMessage(JSON.stringify({type:'show-break-ad',id}))}catch(_){finish()}
  });
}

function playBreak(options={}){
  if(activeBreak)return activeBreak;
  activeBreak=(async()=>{
    const config=await loadConfig();
    const container=options.container;
    if(!(container instanceof HTMLElement))return;
    const duration=Math.max(60,Number(options.duration||config.break_duration||300));
    const experience=options.experience||window.BeyondTVAdBreakExperience||'mini-game';
    const contentVideo=options.contentVideo instanceof HTMLVideoElement?options.contentVideo:null;
    if(contentVideo)contentVideo.pause();
    const overlay=createOverlay(container,config.label||'Commercial break',experience);
    const label=overlay.querySelector('[data-btv-ad-label]');
    const countdown=overlay.querySelector('[data-btv-ad-countdown]');
    const filler=overlay.querySelector('.btv-ad-filler');
    const game=overlay.querySelector('[data-btv-ad-game]');
    const breath=overlay.querySelector('[data-btv-breath-break]');
    const breathCue=overlay.querySelector('[data-btv-breath-cue]');
    const breathCopy=overlay.querySelector('[data-btv-breath-copy]');
    let endBreak;
    const endEarly=new Promise(resolve=>{endBreak=resolve});
    overlay.querySelector('[data-btv-change-channel]')?.addEventListener('click',()=>{
      window.dispatchEvent(new CustomEvent('beyond-tv:change-channel'));
      endBreak();
    });
    const endsAt=Date.now()+duration*1000;
    const breathPhases=[
      {cue:'Breathe in',copy:'Slowly inhale through your nose.',className:'is-inhaling'},
      {cue:'Hold gently',copy:'Let the moment become still.',className:'is-holding'},
      {cue:'Breathe out',copy:'Release slowly and soften your shoulders.',className:'is-exhaling'},
      {cue:'Rest',copy:'Notice the quiet before the next breath.',className:'is-resting'}
    ];
    const tick=()=>{
      countdown.textContent=formatTime((endsAt-Date.now())/1000);
      if(!breath||!breathCue||!breathCopy)return;
      const elapsed=Math.max(0,duration-((endsAt-Date.now())/1000));
      const phase=breathPhases[Math.floor(elapsed/4)%breathPhases.length];
      breathCue.textContent=phase.cue;
      breathCopy.textContent=phase.copy;
      breath.classList.remove('is-inhaling','is-holding','is-exhaling','is-resting');
      breath.classList.add(phase.className);
    };
    tick();
    const countdownTimer=window.setInterval(tick,1000);
    const adState=playing=>{
      if(filler)filler.hidden=playing;
      if(game)game.hidden=playing;
      if(breath)breath.hidden=playing;
      overlay.classList.toggle('is-serving-ad',playing);
      try{game?.contentWindow?.postMessage({type:'beyond-tv:break-ad-state',playing},location.origin)}catch(_){}
    };
    label.textContent=experience==='breath-hourglass'
      ? 'Programming resumes after this mindful pause'
      : (experience==='tattoo-stencil'?'Trace the stencil while you wait':'Play Bit Runner while you wait');
    try{
      if(window.BeyondTVNativeAds?.postMessage){
        const nativeStarted=await startNativeAd(adState);
        if(!nativeStarted)await startIma(config,overlay,contentVideo,adState,duration);
      }else{
        await startIma(config,overlay,contentVideo,adState,duration);
      }
    }catch(error){console.warn('Beyond TV ad unavailable',error);}
    const remaining=endsAt-Date.now();
    if(remaining>0)await Promise.race([new Promise(resolve=>window.setTimeout(resolve,remaining)),endEarly]);
    window.clearInterval(countdownTimer);
    overlay.remove();
  })().finally(()=>{activeBreak=null});
  return activeBreak;
}

window.BeyondTVAds={playBreak,loadConfig};
})();
