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
const menuBtn=document.querySelector('.menu-btn');
const mobileNav=document.querySelector('.mobile-nav');

function initProviderPlayer(container){
  if(!container||container.dataset.ready==='1')return;
  container.dataset.ready='1';
  const video=container.querySelector('.beyond-video');
  const loading=container.querySelector('.player-loading');
  const fallback=container.querySelector('.player-fallback');
  const embed=container.querySelector('.archive-embed');
  const status=document.querySelector('.provider-status');
  let sources=[];
  let sourceIndex=-1;
  let embedFallback='';
  let timer=0;
  let startOffset=0;
  const setStatus=value=>{if(status)status.textContent=value};
  const clearTimer=()=>{if(timer){clearTimeout(timer);timer=0}};
  const showFallback=()=>{
    clearTimer();
    if(video){video.pause();video.hidden=true}
    if(embed)embed.hidden=true;
    if(loading)loading.hidden=true;
    if(fallback)fallback.hidden=false;
    setStatus('Direct providers unavailable — backup player ready.');
  };
  const playNext=()=>{
    clearTimer();
    sourceIndex+=1;
    if(sourceIndex>=sources.length){showFallback();return}
    const source=sources[sourceIndex];
    const sourceOffset=sourceIndex===0?startOffset:0;
    if(loading){loading.hidden=false;loading.textContent=`Tuning to ${source.provider}…`}
    if(fallback)fallback.hidden=true;
    if(String(source.type||'').toLowerCase()==='youtube'){
      if(video){video.pause();video.hidden=true;video.removeAttribute('src');video.load()}
      if(embed){
        const scheduledUrl=new URL(source.url,window.location.href);
        if(sourceOffset>0)scheduledUrl.searchParams.set('start',String(Math.floor(sourceOffset)));
        embed.hidden=false;
        embed.title=source.title||'Preschool program';
        embed.src=scheduledUrl.href;
      }
      if(loading)loading.hidden=true;
      setStatus(`Playing from ${source.provider} · ${source.title}`);
      const remaining=Math.max(60,Number(source.duration||0)-sourceOffset);
      timer=setTimeout(playNext,remaining*1000);
      return;
    }
    if(embed){embed.hidden=true;embed.src=''}
    if(video){
      video.hidden=false;
      video.pause();
      video.removeAttribute('src');
      video.load();
      video.src=source.url;
      timer=setTimeout(playNext,11000);
      video.play().catch(()=>{});
    }else playNext();
  };
  video?.addEventListener('loadedmetadata',()=>{
    clearTimer();
    const sourceOffset=sourceIndex===0?startOffset:0;
    if(sourceOffset>0&&Number.isFinite(video.duration)&&video.duration>sourceOffset){
      try{video.currentTime=sourceOffset}catch(_){}
    }
    if(loading)loading.hidden=true;
    const source=sources[sourceIndex];
    if(source)setStatus(`Playing from ${source.provider} · ${source.title}`);
  });
  video?.addEventListener('playing',()=>{clearTimer();if(loading)loading.hidden=true});
  video?.addEventListener('error',playNext);
  video?.addEventListener('ended',playNext);
  embed?.addEventListener('load',()=>{if(loading)loading.hidden=true});
  container.querySelector('[data-open-embed]')?.addEventListener('click',()=>{
    if(!embedFallback)return;
    if(fallback)fallback.hidden=true;
    if(video){video.pause();video.hidden=true}
    if(embed){embed.hidden=false;embed.src=embedFallback}
    setStatus('Playing with the backup provider.');
  });
  container.querySelector('[data-unmute]')?.addEventListener('click',event=>{
    if(video){video.muted=false;video.play().catch(()=>{})}
    event.currentTarget.hidden=true;
  });
  fetch(container.dataset.streamEndpoint,{headers:{Accept:'application/json'}})
    .then(response=>{if(!response.ok)throw new Error('endpoint');return response.json()})
    .then(payload=>{
      if(payload?.mode==='youtube-library'&&payload?.state?.embed_url){
        clearTimer();sources=[];embedFallback=payload.state.embed_url;startOffset=0;
        if(video){video.pause();video.hidden=true}
        if(fallback)fallback.hidden=true;
        if(loading)loading.hidden=true;
        if(embed){
          embed.hidden=false;embed.src=embedFallback;
          embed.dataset.library=payload.state.library_key||'';
          embed.dataset.sourceIndex=String(payload.state.source_index||0);
          embed.dataset.fallbacks=JSON.stringify(payload.fallbacks||[]);
          embed.title=`${payload.state.current?.library_name||'Channel 1'} Episode ${payload.state.episode_number||1}`;
        }
        setStatus(`Playing ${payload.state.current?.library_name||'Channel 1'} · Episode ${payload.state.episode_number||1}`);
        window.BeyondTVClassicFallback?.(embed,payload);
        return;
      }
      sources=Array.isArray(payload.sources)?payload.sources:[];
      embedFallback=payload.embed_fallback||'';
      startOffset=Number(payload.start_offset||0);
      playNext();
    })
    .catch(()=>{if(loading)loading.hidden=true;if(fallback)fallback.hidden=false;setStatus('Provider lookup failed — backup player ready.')});
}

menuBtn&&mobileNav&&menuBtn.addEventListener('click',()=>{const open=menuBtn.getAttribute('aria-expanded')==='true';menuBtn.setAttribute('aria-expanded',String(!open));mobileNav.hidden=open});
document.querySelectorAll('[data-stream-endpoint]').forEach(initProviderPlayer);
document.querySelectorAll('.tv-channel-tile[data-channel]').forEach(tile=>tile.addEventListener('click',()=>{
  let channel;try{channel=JSON.parse(tile.dataset.channel||'{}')}catch(_){return}
  if(tile.dataset.external){window.open(tile.dataset.external,'_blank','noopener');return}
  document.querySelectorAll('.tv-channel-tile').forEach(item=>item.classList.remove('is-active'));
  tile.classList.add('is-active');
  const current=document.querySelector('[data-tv-stage] .provider-player');
  if(!current)return;
  const player=current.cloneNode(true);
  player.dataset.streamEndpoint=channel.stream_endpoint;
  player.dataset.ready='0';
  const video=player.querySelector('video');if(video){video.removeAttribute('src');video.load()}
  const frame=player.querySelector('iframe');if(frame){frame.src='';frame.hidden=true}
  const fallback=player.querySelector('.player-fallback');if(fallback)fallback.hidden=true;
  const loading=player.querySelector('.player-loading');if(loading){loading.hidden=false;loading.textContent=`Tuning into ${channel.name}…`}
  current.replaceWith(player);
  document.querySelector('[data-stage-title]').textContent=channel.name;
  document.querySelector('[data-stage-now]').textContent=channel.now;
  document.querySelector('[data-stage-next]').textContent=channel.up_next||'';
  initProviderPlayer(player);
  window.scrollTo({top:0,behavior:'smooth'});
}));
document.querySelectorAll('[data-my-list]').forEach(button=>button.addEventListener('click',()=>{button.textContent=button.textContent.includes('Added')?'＋ My List':'✓ Added to My List'}));
(function initRotatingNowPlaying(){
  const stage=document.querySelector('[data-tv-stage]');
  const dataNode=document.getElementById('tv-rotation-data');
  if(!stage||!dataNode)return;
  let channels=[];
  try{channels=JSON.parse(dataNode.textContent||'[]')}catch(_){return}
  if(!Array.isArray(channels)||!channels.length)return;

  let current=0;
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


// Beyond TV 3.0 theme flavors: Sunset → Dark → Light
(function(){const root=document.documentElement,themes=['sunset','dark','light'],icons={dark:'🌙',light:'☀️',sunset:'🌅'},labels={dark:'Dark',light:'Light',sunset:'Sunset'};let saved='sunset';try{saved=localStorage.getItem('beyond-tv-theme')||'sunset'}catch(e){}if(!themes.includes(saved))saved='sunset';function apply(t){root.dataset.tvTheme=t;document.querySelector('meta[name="theme-color"]')?.setAttribute('content',t==='light'?'#f6f1f4':t==='dark'?'#080812':'#401532');document.querySelectorAll('[data-tv-theme-toggle]').forEach(btn=>{btn.innerHTML=icons[t]+'<span class="sr-only"> '+labels[t]+'</span>';const next=themes[(themes.indexOf(t)+1)%themes.length];btn.setAttribute('aria-label','Current theme '+labels[t]+'. Switch to '+labels[next]);btn.title='Theme: '+labels[t]+' · Next: '+labels[next]})}apply(saved);document.addEventListener('click',e=>{const btn=e.target.closest('[data-tv-theme-toggle]');if(!btn)return;const current=themes.includes(root.dataset.tvTheme)?root.dataset.tvTheme:'sunset',next=themes[(themes.indexOf(current)+1)%themes.length];try{localStorage.setItem('beyond-tv-theme',next)}catch(e){}apply(next)})})();

// Homepage Beyond TV iframe sync: 30 minutes for episodes, 2 hours for long-form content.
(function initHomeBeyondTvSync(){
  const frame=document.getElementById('homeBeyondTvPlayer');
  const stage=document.querySelector('.home-live-stage');
  if(!frame||!stage)return;
  if(stage.dataset.syncOwner==='page')return;

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
    const interval=syncInterval(button);
    return interval===LONG_FORM_SYNC_MS?'Long-form sync · every 2 hours':'Episode sync · every 30 minutes';
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
