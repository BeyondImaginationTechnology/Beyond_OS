(function(){
let ytPromise=null;
function loadYT(){if(window.YT&&window.YT.Player)return Promise.resolve(window.YT);if(ytPromise)return ytPromise;ytPromise=new Promise(resolve=>{const prior=window.onYouTubeIframeAPIReady;window.onYouTubeIframeAPIReady=()=>{try{prior&&prior()}catch(_){}resolve(window.YT)};const s=document.createElement('script');s.src='https://www.youtube.com/iframe_api';document.head.appendChild(s)});return ytPromise}
window.BeyondTVClassicFallback=async function(frame,payload){
 if(!frame)return;const fallbacks=Array.isArray(payload?.fallbacks)?payload.fallbacks:[];let pos=Math.max(0,fallbacks.findIndex(x=>x.embed_url===payload.state.embed_url));let player=null;
 async function report(status,item){try{await fetch('/beyond-tv/api/classic-source-status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({library:payload.state.library_key,source_index:Number(item?.source_index||0),embed_url:item?.embed_url||'',status})})}catch(_){}}
 async function play(index){pos=index;if(pos>=fallbacks.length)return;const item=fallbacks[pos];try{player&&player.destroy&&player.destroy()}catch(_){}player=null;frame.src=item.embed_url;frame.hidden=false;if(item.source_index==='intermission')return;try{const YT=await loadYT();player=new YT.Player(frame,{events:{onReady:()=>report('valid',item),onError:()=>{report('failed',item);play(pos+1)}}})}catch(_){setTimeout(()=>play(pos+1),2500)}}
 play(pos);
};
})();
const menuBtn=document.querySelector(".menu-btn"),mobileNav=document.querySelector(".mobile-nav");function initProviderPlayer(e){if(!e||"1"===e.dataset.ready)return;e.dataset.ready="1";const t=e.querySelector(".beyond-video"),n=e.querySelector(".player-loading"),r=e.querySelector(".player-fallback"),a=e.querySelector(".archive-embed"),d=document.querySelector(".provider-status");let o=[],i=-1,c="",l=0,s=0;const u=e=>{d&&(d.textContent=e)},y=()=>{l&&(clearTimeout(l),l=0)},m=()=>{if(y(),i+=1,i>=o.length)return t&&(t.hidden=!0),n&&(n.hidden=!0),r&&(r.hidden=!1),void u("Direct providers unavailable — backup player ready.");const e=o[i];n&&(n.hidden=!1,n.textContent=`Tuning to ${e.provider}…`),r&&(r.hidden=!0),t&&(t.hidden=!1,t.pause(),t.removeAttribute("src"),t.load(),t.src=e.url),l=setTimeout(m,11e3);const a=t?.play();a&&a.catch&&a.catch(()=>{})};t?.addEventListener("loadedmetadata",()=>{if(y(),s>0&&Number.isFinite(t.duration)&&t.duration>s)try{t.currentTime=s}catch(e){}n&&(n.hidden=!0);const e=o[i];e&&u(`Playing from ${e.provider} · ${e.title}`)}),t?.addEventListener("playing",()=>{y(),n&&(n.hidden=!0)}),t?.addEventListener("error",m),t?.addEventListener("ended",m),e.querySelector("[data-open-embed]")?.addEventListener("click",()=>{c&&(r&&(r.hidden=!0),t&&(t.hidden=!0),a&&(a.hidden=!1,a.src=c),u("Playing with the backup provider."))}),e.querySelector("[data-unmute]")?.addEventListener("click",e=>{t&&(t.muted=!1,t.play().catch(()=>{})),e.currentTarget.hidden=!0}),fetch(e.dataset.streamEndpoint,{headers:{Accept:"application/json"}}).then(e=>{if(!e.ok)throw new Error("endpoint");return e.json()}).then(e=>{if(e?.mode==="youtube-library"&&e?.state?.embed_url){y();o=[];c=e.state.embed_url;s=0;t&&(t.pause(),t.hidden=!0);r&&(r.hidden=!0);n&&(n.hidden=!0);if(a){a.hidden=!1;a.src=c;a.dataset.library=e.state.library_key||"";a.dataset.sourceIndex=String(e.state.source_index||0);a.dataset.fallbacks=JSON.stringify(e.fallbacks||[]);a.title=`${e.state.current?.library_name||"Channel 1"} Episode ${e.state.episode_number||1}`;}u(`Playing ${e.state.current?.library_name||"Channel 1"} · Episode ${e.state.episode_number||1}`);window.BeyondTVClassicFallback?.(a,e);return}o=Array.isArray(e.sources)?e.sources:[],c=e.embed_fallback||"",s=Number(e.start_offset||0),m()}).catch(()=>{n&&(n.hidden=!0),r&&(r.hidden=!1),u("Provider lookup failed — backup player ready.")})}menuBtn&&mobileNav&&menuBtn.addEventListener("click",()=>{const e="true"===menuBtn.getAttribute("aria-expanded");menuBtn.setAttribute("aria-expanded",String(!e)),mobileNav.hidden=e}),document.querySelectorAll("[data-stream-endpoint]").forEach(initProviderPlayer),document.querySelectorAll(".tv-channel-tile[data-channel]").forEach(e=>e.addEventListener("click",()=>{let t;try{t=JSON.parse(e.dataset.channel||"{}")}catch(e){return}if(e.dataset.external)return void window.open(e.dataset.external,"_blank","noopener");document.querySelectorAll(".tv-channel-tile").forEach(e=>e.classList.remove("is-active")),e.classList.add("is-active");const n=document.querySelector("[data-tv-stage] .provider-player");if(!n)return;const r=n.cloneNode(!0);r.dataset.streamEndpoint=t.stream_endpoint,r.dataset.ready="0";const a=r.querySelector("video");a&&(a.removeAttribute("src"),a.load());const d=r.querySelector("iframe");d&&(d.src="",d.hidden=!0);const o=r.querySelector(".player-fallback");o&&(o.hidden=!0);const i=r.querySelector(".player-loading");i&&(i.hidden=!1,i.textContent=`Tuning into ${t.name}…`),n.replaceWith(r),document.querySelector("[data-stage-title]").textContent=t.name,document.querySelector("[data-stage-now]").textContent=t.now,document.querySelector("[data-stage-next]").textContent=t.up_next||"",initProviderPlayer(r),window.scrollTo({top:0,behavior:"smooth"})})),document.querySelectorAll("[data-my-list]").forEach(e=>e.addEventListener("click",()=>{e.textContent=e.textContent.includes("Added")?"＋ My List":"✓ Added to My List"}));
(function initRotatingNowPlaying(){
  const stage=document.querySelector('[data-tv-stage]');
  const dataNode=document.getElementById('tv-rotation-data');
  if(!stage||!dataNode)return;
  let channels=[];
  try{channels=JSON.parse(dataNode.textContent||'[]')}catch(_){return}
  if(!Array.isArray(channels)||!channels.length)return;

  const preferred='yugioh-tv';
  let current=Math.max(0,channels.findIndex(channel=>channel.slug===preferred));
  let timer=0;
  const dots=document.querySelector('[data-rotation-dots]');

  async function youtubeUrl(channel){
    if(channel.source_type==='youtube_playlist_live' && channel.sync_endpoint){
      try{
        const response=await fetch(channel.sync_endpoint,{cache:'no-store',headers:{Accept:'application/json'}});
        const payload=await response.json();
        if(payload?.ok && payload?.state?.embed_url){
          channel.now=`Season 1 · Episode ${payload.state.episode_number}`;
          channel.up_next=`Episode ${payload.state.next_episode_number}`;
          return payload.state.embed_url;
        }
      }catch(_){ }
      const list=encodeURIComponent(channel.youtube_playlist_id||'');
      return `https://www.youtube-nocookie.com/embed/videoseries?list=${list}&autoplay=1&mute=1&controls=1&rel=0&playsinline=1`;
    }
    const id=encodeURIComponent(channel.youtube_id||'');
    const start=Math.max(0,Number(channel.youtube_start||0));
    const params=new URLSearchParams({autoplay:'1',mute:'1',controls:'1',rel:'0',playsinline:'1',modestbranding:'1'});
    if(start)params.set('start',String(start));
    return `https://www.youtube-nocookie.com/embed/${id}?${params.toString()}`;
  }

  async function createPlayer(channel){
    const old=stage.querySelector('.provider-player');
    if(!old)return;
    const player=document.createElement('div');
    player.className='watch-player provider-player';
    player.dataset.channelSlug=channel.slug||'';

    const isYoutube=(channel.source_type==='youtube_embed'&&channel.youtube_id)||(channel.source_type==='youtube_playlist_live'&&channel.youtube_playlist_id);
    if(isYoutube){
      player.classList.add('youtube-player');
      player.innerHTML=`<video class="beyond-video" controls playsinline autoplay muted hidden></video>
        <iframe class="youtube-hero-frame" title="${escapeHtml(channel.name||'Beyond TV')} player" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
        <div class="player-loading" role="status">Tuning into ${escapeHtml(channel.name||'Beyond TV')}…</div>
        <div class="player-fallback" hidden><p>This provider is taking a break.</p></div>
        <iframe class="archive-embed" title="Backup player" allow="autoplay; fullscreen" allowfullscreen hidden></iframe>
        <button class="unmute-hint" type="button">🔊 Use player controls for sound</button>`;
      old.replaceWith(player);
      const frame=player.querySelector('.youtube-hero-frame');
      const loading=player.querySelector('.player-loading');
      frame.addEventListener('load',()=>{if(loading)loading.hidden=true},{once:true});
      frame.src=await youtubeUrl(channel);
      setTimeout(()=>{if(loading)loading.hidden=true},2200);
    }else if(channel.stream_endpoint){
      player.dataset.streamEndpoint=channel.stream_endpoint;
      player.innerHTML=`<video class="beyond-video" controls playsinline autoplay muted preload="metadata" poster="/beyond-tv/assets/img/beyond-tv-promo.webp"></video>
        <iframe class="youtube-hero-frame" title="Beyond TV player" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen hidden></iframe>
        <div class="player-loading" role="status">Tuning into ${escapeHtml(channel.name||'Beyond TV')}…</div>
        <div class="player-fallback" hidden><p>This provider is taking a break.</p><button class="btn btn-secondary" type="button" data-open-embed>Open backup player</button></div>
        <iframe class="archive-embed" title="Backup player" allow="autoplay; fullscreen" allowfullscreen hidden></iframe>
        <button class="unmute-hint" type="button" data-unmute>🔊 Tap for sound</button>`;
      old.replaceWith(player);
      initProviderPlayer(player);
    }
  }

  function escapeHtml(value){
    return String(value).replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
  }

  function updateMeta(channel,index){
    const title=document.querySelector('[data-stage-title]');
    const now=document.querySelector('[data-stage-now]');
    const next=document.querySelector('[data-stage-next]');
    const number=document.querySelector('[data-stage-channel]');
    if(title)title.textContent=channel.name||'';
    if(now)now.textContent=channel.now||'';
    if(next)next.textContent=channel.up_next||'';
    if(number)number.textContent=`CH ${String(index+1).padStart(2,'0')}`;
    dots?.querySelectorAll('button').forEach((dot,i)=>dot.classList.toggle('is-active',i===index));
  }

  async function show(index,manual=false){
    current=(index+channels.length)%channels.length;
    const channel=channels[current];
    await createPlayer(channel);
    updateMeta(channel,current);
    if(manual)restart();
  }

  function restart(){
    if(timer)clearInterval(timer);
    timer=setInterval(()=>show(current+1),12000);
  }

  if(dots){
    dots.innerHTML='';
    channels.forEach((channel,index)=>{
      const button=document.createElement('button');
      button.type='button';
      button.title=channel.name||`Channel ${index+1}`;
      button.setAttribute('aria-label',`Play ${channel.name||'channel'}`);
      button.addEventListener('click',()=>show(index,true));
      dots.appendChild(button);
    });
  }

  show(current);
  restart();
})();


// Beyond TV Beta Build 2.1.1 theme flavors: Dark → Light → Sunset
(function(){document.querySelectorAll('.footer,.classic-footer').forEach(function(footer){footer.childNodes.forEach(function(node){if(node.nodeType===3)node.nodeValue=node.nodeValue.replace(/Beyond TV 2\.2/g,'Beyond TV · Beta Build 2.1.1')})})})();
(function(){const root=document.documentElement,themes=['dark','light','sunset'],icons={dark:'🌙',light:'☀️',sunset:'🌅'},labels={dark:'Dark',light:'Light',sunset:'Sunset'};let saved='sunset';try{saved=localStorage.getItem('beyond-tv-theme')||'sunset'}catch(e){}if(!themes.includes(saved))saved='sunset';function apply(t){root.dataset.tvTheme=t;document.querySelectorAll('[data-tv-theme-toggle]').forEach(btn=>{btn.innerHTML=icons[t]+'<span class="sr-only"> '+labels[t]+'</span>';const next=themes[(themes.indexOf(t)+1)%themes.length];btn.setAttribute('aria-label','Current theme '+labels[t]+'. Switch to '+labels[next]);btn.title='Theme: '+labels[t]+' · Next: '+labels[next]})}apply(saved);document.addEventListener('click',e=>{const btn=e.target.closest('[data-tv-theme-toggle]');if(!btn)return;const current=themes.includes(root.dataset.tvTheme)?root.dataset.tvTheme:'dark',next=themes[(themes.indexOf(current)+1)%themes.length];try{localStorage.setItem('beyond-tv-theme',next)}catch(e){}apply(next)})})();

// Homepage Beyond TV iframe sync: 30 minutes for episodes, 2 hours for long-form content.
(function initHomeBeyondTvSync(){
  const frame=document.getElementById('homeBeyondTvPlayer');
  const stage=document.querySelector('.home-live-stage');
  if(!frame||!stage)return;

  const buttons=[...stage.querySelectorAll('[data-home-channel]')];
  if(!buttons.length)return;

  const EPISODE_SYNC_MS=30*60*1000;
  const LONG_FORM_SYNC_MS=2*60*60*1000;
  const longFormChannels=new Set(['space','ancient','cinema','health','comedy','family']);
  let syncTimer=0;
  let nextSyncAt=0;

  const name=document.getElementById('homeLiveChannelName');
  const now=document.getElementById('homeLiveNow');
  const open=document.getElementById('homeLiveOpen');
  const kicker=document.getElementById('homeLiveKicker');
  const heading=document.getElementById('homeLiveHeading');
  const description=document.getElementById('homeLiveDescription');
  const clock=document.querySelector('.home-live-clock');
  const clean=value=>String(value||'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

  buttons.forEach(button=>{
    const endpoint=button.dataset.endpoint||'';
    if(endpoint)button.dataset.syncEndpoint=endpoint;
    delete button.dataset.endpoint;
  });

  function syncInterval(button){
    const explicit=Number(button?.dataset.syncMs||0);
    if(Number.isFinite(explicit)&&explicit>=60000)return explicit;
    return longFormChannels.has(button?.dataset.homeChannel||'')?LONG_FORM_SYNC_MS:EPISODE_SYNC_MS;
  }

  function syncLabel(button){
    return syncInterval(button)===LONG_FORM_SYNC_MS?'Long-form sync · every 2 hours':'Episode sync · every 30 minutes';
  }

  function normalizedEmbed(embed){
    if(!embed)return '';
    const isYoutube=/youtube(?:-nocookie)?\.com/.test(embed);
    const withApi=!isYoutube||embed.includes('enablejsapi=1')?embed:(embed+(embed.includes('?')?'&':'?')+'enablejsapi=1');
    try{return new URL(withApi,window.location.href).href}catch(_){return withApi}
  }

  function setFrame(embed,forceReload=false){
    const nextSrc=normalizedEmbed(embed);
    if(!nextSrc)return;
    if(forceReload||frame.src!==nextSrc)frame.src=nextSrc;
  }

  function updateMeta(button,state={}){
    const channelName=button.dataset.channelName||'Beyond TV';
    const channelNumber=button.dataset.channelNumber||'';
    const current=state.current||state.playing||{};
    const next=state.next||{};
    const block=current.title||state.episode_title||button.dataset.now||state.programme||'Live now';
    const lineup=current.lineup||(!current.title?button.dataset.now:'')||state.episode_title||'';
    const upNext=next.title||button.dataset.next||'';
    const icon=current.icon||button.dataset.icon||button.textContent.trim().split(' ')[0]||'📺';
    stage.dataset.channelTheme=button.dataset.homeChannel||'cartoons';
    if(name)name.textContent=channelName;
    if(now)now.textContent=block;
    if(kicker)kicker.innerHTML='<i></i> Beyond TV · Channel '+clean(channelNumber)+' live';
    if(heading)heading.textContent=channelName+' is playing now.';
    if(description)description.innerHTML='<strong>'+clean(icon)+' '+clean(block)+'</strong>'+(lineup&&lineup!==block?' · '+clean(lineup):'')+(upNext?' · Up next: '+clean(upNext):'')+' · Vancouver time';
    if(open)open.href=button.dataset.open||'/beyond-tv/';
    if(clock)clock.textContent=syncLabel(button)+' · America/Vancouver';
  }

  async function sync(button){
    if(!button||!button.classList.contains('active'))return;
    const endpoint=button.dataset.syncEndpoint||'';
    try{
      if(endpoint){
        const response=await fetch(endpoint,{cache:'no-store'});
        if(!response.ok)throw new Error('HTTP '+response.status);
        const data=await response.json();
        const state=data.state||data;
        const embed=state.player_url||state.embed_url||button.dataset.embed||'';
        const sourceKey=String(state.source_key||state.current?.source_key||'');
        const sourceChanged=Boolean(sourceKey&&button.dataset.streamKey&&sourceKey!==button.dataset.streamKey);
        setFrame(embed,sourceChanged);
        if(sourceKey)button.dataset.streamKey=sourceKey;
        updateMeta(button,state);
      }else{
        setFrame(button.dataset.embed||frame.src,true);
        updateMeta(button);
      }
    }catch(error){
      console.warn('Beyond TV scheduled iframe sync unavailable',error);
    }finally{
      schedule(button);
    }
  }

  function schedule(button){
    if(syncTimer)clearTimeout(syncTimer);
    if(!button)return;
    const delay=syncInterval(button);
    nextSyncAt=Date.now()+delay;
    syncTimer=window.setTimeout(()=>sync(button),delay);
    if(clock)clock.textContent=syncLabel(button)+' · America/Vancouver';
  }

  buttons.forEach(button=>{
    button.addEventListener('click',()=>{
      const endpoint=button.dataset.syncEndpoint||'';
      if(endpoint)button.dataset.endpoint=endpoint;
    },true);
    button.addEventListener('click',()=>{
      window.setTimeout(()=>{delete button.dataset.endpoint},0);
      schedule(button);
    });
  });

  document.addEventListener('visibilitychange',()=>{
    if(document.hidden)return;
    const active=stage.querySelector('[data-home-channel].active');
    if(active&&nextSyncAt&&Date.now()>=nextSyncAt)sync(active);
  });

  schedule(stage.querySelector('[data-home-channel].active'));
})();
