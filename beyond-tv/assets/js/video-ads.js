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

function createOverlay(container,label){
  const overlay=document.createElement('section');
  overlay.className='btv-ad-break';
  overlay.setAttribute('aria-label',label);
  overlay.innerHTML='<div class="btv-ad-slot" data-btv-ad-slot></div><div class="btv-ad-filler"><span>BEYOND TV</span><strong data-btv-ad-label></strong><p>Programming resumes in <b data-btv-ad-countdown>5:00</b></p></div>';
  const style=getComputedStyle(container);
  if(style.position==='static')container.style.position='relative';
  container.appendChild(overlay);
  return overlay;
}

function startIma(config,overlay,contentVideo,onAdState){
  if(!config.enabled||!config.ad_tag_url)return Promise.resolve(null);
  return loadIma().then(ima=>new Promise((resolve,reject)=>{
    const slot=overlay.querySelector('[data-btv-ad-slot]');
    const displayContainer=new ima.AdDisplayContainer(slot,contentVideo||undefined);
    const loader=new ima.AdsLoader(displayContainer);
    let manager=null;
    let settled=false;
    const requestTimer=window.setTimeout(()=>fail(new Error('Ad request timed out')),10000);
    const fail=error=>{
      onAdState(false);
      try{manager?.destroy()}catch(_){}
      if(settled)return;
      settled=true;
      window.clearTimeout(requestTimer);
      reject(error instanceof Error?error:new Error('Ad playback failed'));
    };
    loader.addEventListener(ima.AdErrorEvent.Type.AD_ERROR,event=>fail(event.getError()),false);
    loader.addEventListener(ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED,event=>{
      try{
        manager=event.getAdsManager(contentVideo||document.createElement('video'));
        if(settled){manager.destroy();return}
        manager.addEventListener(ima.AdErrorEvent.Type.AD_ERROR,event=>fail(event.getError()));
        manager.addEventListener(ima.AdEvent.Type.STARTED,()=>onAdState(true));
        manager.addEventListener(ima.AdEvent.Type.ALL_ADS_COMPLETED,()=>onAdState(false));
        const width=Math.max(320,overlay.clientWidth);
        const height=Math.max(180,overlay.clientHeight);
        manager.init(width,height,ima.ViewMode.NORMAL);
        manager.start();
        settled=true;
        window.clearTimeout(requestTimer);
        resolve(manager);
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

function playBreak(options={}){
  if(activeBreak)return activeBreak;
  activeBreak=(async()=>{
    const config=await loadConfig();
    const container=options.container;
    if(!(container instanceof HTMLElement))return;
    const duration=Math.max(60,Number(options.duration||config.break_duration||300));
    const contentVideo=options.contentVideo instanceof HTMLVideoElement?options.contentVideo:null;
    if(contentVideo)contentVideo.pause();
    const overlay=createOverlay(container,config.label||'Commercial break');
    const label=overlay.querySelector('[data-btv-ad-label]');
    const countdown=overlay.querySelector('[data-btv-ad-countdown]');
    const filler=overlay.querySelector('.btv-ad-filler');
    label.textContent=config.enabled?'Commercial break':'Beyond TV intermission';
    const started=Date.now();
    const endsAt=started+duration*1000;
    const tick=()=>{countdown.textContent=formatTime((endsAt-Date.now())/1000)};
    tick();
    const countdownTimer=window.setInterval(tick,1000);
    let manager=null;
    try{
      manager=await startIma(config,overlay,contentVideo,playing=>{
        filler.hidden=playing;
        overlay.classList.toggle('is-serving-ad',playing);
      });
    }catch(_){
      label.textContent='Beyond TV intermission';
      filler.hidden=false;
    }
    const remaining=endsAt-Date.now();
    if(remaining>0)await new Promise(resolve=>window.setTimeout(resolve,remaining));
    window.clearInterval(countdownTimer);
    try{manager?.destroy()}catch(_){}
    overlay.remove();
  })().finally(()=>{activeBreak=null});
  return activeBreak;
}

window.BeyondTVAds={playBreak,loadConfig};
})();
