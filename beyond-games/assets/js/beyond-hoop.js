(() => {
  'use strict';
  const canvas=document.getElementById('hoopGame');
  if(!canvas)return;
  const context=canvas.getContext('2d');
  const stage=document.getElementById('hoopStage');
  const overlay=document.getElementById('hoopOverlay');
  const startButton=document.getElementById('hoopStart');
  const feedback=document.getElementById('hoopFeedback');
  const ui={
    score:document.getElementById('hoopScore'),
    made:document.getElementById('hoopMade'),
    streak:document.getElementById('hoopStreak'),
    time:document.getElementById('hoopTime'),
    best:document.getElementById('hoopBest'),
    aim:document.getElementById('aimReadout'),
    power:document.getElementById('powerReadout'),
    fill:document.getElementById('powerFill'),
    rewards:document.getElementById('hoopRewards'),
    achievements:document.getElementById('hoopAchievements')
  };
  const storeKey='beyond-games-beyond-hoop-v1';
  const today=new Date().toISOString().slice(0,10);
  const blankSave=()=>({best:0,rewards:0,rewardDate:today,achievements:{}});
  let saved;
  try{saved=JSON.parse(localStorage.getItem(storeKey)||'null')||blankSave()}catch(error){saved=blankSave()}
  if(saved.rewardDate!==today){saved.rewardDate=today;saved.rewards=0}
  const badges=[
    ['first-shot','First Release','Take your first rooftop shot',5],
    ['first-bucket','Nothing But Net','Make your first basket',10],
    ['streak-3','Heating Up','Make three shots in a row',15],
    ['score-25','Rooftop Regular','Score 25 points in one run',15],
    ['perfect-3','Gold Touch','Make three perfect releases',15]
  ];
  const hoop={x:0,y:205,z:520};
  let width=canvas.width,height=canvas.height,ratio=1;
  let state='ready',last=performance.now(),seconds=60,score=0,shots=0,made=0,streak=0,perfects=0;
  let charging=false,power=.08,powerDirection=1,aimTarget=0,sound=true,resetClock=0,netPulse=0;
  let ball={x:0,y:38,z:24,vx:0,vy:0,vz:0,r:18,active:false,scored:false};

  function save(){try{localStorage.setItem(storeKey,JSON.stringify(saved))}catch(error){}}
  function tone(frequency=520,duration=.08,type='sine'){
    if(!sound)return;
    try{
      const audio=tone.audio||(tone.audio=new(window.AudioContext||window.webkitAudioContext)());
      const oscillator=audio.createOscillator(),gain=audio.createGain();
      oscillator.type=type;oscillator.frequency.value=frequency;
      gain.gain.setValueAtTime(.045,audio.currentTime);
      gain.gain.exponentialRampToValueAtTime(.001,audio.currentTime+duration);
      oscillator.connect(gain);gain.connect(audio.destination);oscillator.start();oscillator.stop(audio.currentTime+duration);
    }catch(error){}
  }
  function setFeedback(text,kind=''){feedback.textContent=text;feedback.className='hoop-feedback'+(kind?' '+kind:'')}
  function renderBadges(){
    ui.achievements.innerHTML=badges.map(([id,name,copy,reward])=>`<div class="${saved.achievements[id]?'earned':''}"><span>${saved.achievements[id]?'✓':'◇'}</span><p><b>${name}</b><small>${copy} · ${reward} demo bit$</small></p></div>`).join('');
    ui.rewards.textContent=saved.rewards;
  }
  function unlock(id){
    if(saved.achievements[id])return;
    const badge=badges.find(item=>item[0]===id);
    saved.achievements[id]=true;
    const award=Math.min(badge[3],Math.max(0,60-saved.rewards));
    saved.rewards+=award;save();renderBadges();
    setFeedback(`${badge[1]} unlocked${award?` · +${award} demo bit$`:''}`,'perfect');
  }
  function resize(){
    const rect=canvas.getBoundingClientRect();
    ratio=Math.min(2,window.devicePixelRatio||1);
    width=Math.max(420,Math.round(rect.width*ratio));
    height=Math.max(420,Math.round(rect.height*ratio));
    if(canvas.width!==width||canvas.height!==height){canvas.width=width;canvas.height=height}
  }
  function project(x,y,z){
    const focal=760*ratio;
    const depth=focal/(focal+z*ratio);
    return {x:width*.5+x*ratio*depth,y:height*.86-y*ratio*depth-z*ratio*.48*depth,scale:depth};
  }
  function path(points,fill,stroke=''){
    context.beginPath();context.moveTo(points[0].x,points[0].y);
    points.slice(1).forEach(point=>context.lineTo(point.x,point.y));
    context.closePath();if(fill){context.fillStyle=fill;context.fill()}if(stroke){context.strokeStyle=stroke;context.lineWidth=2*ratio;context.stroke()}
  }
  function drawBackground(){
    const gradient=context.createLinearGradient(0,0,0,height);
    gradient.addColorStop(0,'#100826');gradient.addColorStop(.48,'#35113d');gradient.addColorStop(1,'#090a14');
    context.fillStyle=gradient;context.fillRect(0,0,width,height);
    for(let i=0;i<42;i++){
      const x=(i*193%1100)/1100*width,y=(i*73%260)/650*height;
      context.fillStyle=i%5?'rgba(255,255,255,.34)':'rgba(255,180,92,.66)';
      context.fillRect(x,y,Math.max(1,ratio),Math.max(1,ratio));
    }
    const skylineY=height*.49;
    for(let i=0;i<20;i++){
      const buildingWidth=width/18+(i%3)*7*ratio;
      const x=i*width/19-buildingWidth*.25;
      const buildingHeight=(48+(i*37)%135)*ratio;
      context.fillStyle=i%2?'#100d1c':'#171021';
      context.fillRect(x,skylineY-buildingHeight,buildingWidth,buildingHeight);
      context.fillStyle='rgba(255,168,76,.36)';
      for(let row=0;row<4;row++)for(let column=0;column<3;column++)if((i+row+column)%3)context.fillRect(x+8*ratio+column*13*ratio,skylineY-buildingHeight+12*ratio+row*23*ratio,4*ratio,6*ratio);
    }
  }
  function drawCourt(){
    const nearLeft=project(-300,0,0),nearRight=project(300,0,0),farRight=project(205,0,650),farLeft=project(-205,0,650);
    path([nearLeft,nearRight,farRight,farLeft],'#2a1a35','rgba(255,139,70,.52)');
    context.strokeStyle='rgba(255,255,255,.35)';context.lineWidth=2*ratio;
    const centreNear=project(0,0,0),centreFar=project(0,0,650);
    context.beginPath();context.moveTo(centreNear.x,centreNear.y);context.lineTo(centreFar.x,centreFar.y);context.stroke();
    [-220,-110,110,220].forEach(x=>{
      const start=project(x,0,0),end=project(x*.7,0,650);
      context.strokeStyle='rgba(255,255,255,.08)';context.beginPath();context.moveTo(start.x,start.y);context.lineTo(end.x,end.y);context.stroke();
    });
    [130,260,390,520].forEach(z=>{
      const left=project(-270+z*.1,0,z),right=project(270-z*.1,0,z);
      context.beginPath();context.moveTo(left.x,left.y);context.lineTo(right.x,right.y);context.stroke();
    });
    const keyA=project(-75,0,390),keyB=project(75,0,390),keyC=project(75,0,610),keyD=project(-75,0,610);
    path([keyA,keyB,keyC,keyD],'rgba(255,115,50,.11)','rgba(255,183,124,.42)');
  }
  function drawHoop(){
    const board=project(0,270,565),boardScale=board.scale*ratio;
    context.fillStyle='rgba(235,240,255,.14)';context.strokeStyle='rgba(255,255,255,.8)';context.lineWidth=3*ratio;
    context.fillRect(board.x-92*boardScale,board.y-58*boardScale,184*boardScale,116*boardScale);
    context.strokeRect(board.x-92*boardScale,board.y-58*boardScale,184*boardScale,116*boardScale);
    context.strokeStyle='#ff9250';context.strokeRect(board.x-28*boardScale,board.y+5*boardScale,56*boardScale,38*boardScale);
    const rim=project(hoop.x,hoop.y,hoop.z);
    context.save();context.translate(rim.x,rim.y);context.scale(1,.32);
    context.beginPath();context.arc(0,0,35*rim.scale*ratio,0,Math.PI*2);
    context.strokeStyle=netPulse>0?'#ffd36e':'#ff6e32';context.lineWidth=5*ratio;context.shadowColor='#ff7a32';context.shadowBlur=(netPulse>0?28:10)*ratio;context.stroke();context.restore();
    context.strokeStyle='rgba(255,255,255,.58)';context.lineWidth=1.5*ratio;
    for(let i=-3;i<=3;i++){
      context.beginPath();context.moveTo(rim.x+i*9*rim.scale*ratio,rim.y+3*ratio);context.lineTo(rim.x+i*4*rim.scale*ratio,rim.y+52*rim.scale*ratio+netPulse*2*ratio);context.stroke();
    }
    context.beginPath();context.moveTo(rim.x-28*rim.scale*ratio,rim.y+22*rim.scale*ratio);context.lineTo(rim.x+28*rim.scale*ratio,rim.y+22*rim.scale*ratio);context.stroke();
  }
  function drawGuide(){
    if(!charging||ball.active)return;
    const shot=shotVelocity();
    context.save();context.fillStyle='rgba(255,210,105,.58)';
    for(let t=.12;t<1.65;t+=.14){
      const point=project(ball.x+shot.vx*t,ball.y+shot.vy*t-250*t*t,ball.z+shot.vz*t);
      context.beginPath();context.arc(point.x,point.y,2.2*ratio*point.scale,0,Math.PI*2);context.fill();
    }
    context.restore();
  }
  function drawBall(){
    const point=project(ball.x,ball.y,ball.z),radius=ball.r*ratio*point.scale;
    context.save();context.translate(point.x,point.y);context.rotate((ball.z+ball.y)*.02);
    const gradient=context.createRadialGradient(-radius*.35,-radius*.38,2,radius*.05,radius*.05,radius);
    gradient.addColorStop(0,'#ffbe67');gradient.addColorStop(.55,'#ee7435');gradient.addColorStop(1,'#8d2d20');
    context.fillStyle=gradient;context.shadowColor='rgba(0,0,0,.58)';context.shadowBlur=18*ratio*point.scale;
    context.beginPath();context.arc(0,0,radius,0,Math.PI*2);context.fill();context.shadowBlur=0;
    context.strokeStyle='#472016';context.lineWidth=Math.max(1.3*ratio,radius*.08);
    context.beginPath();context.arc(0,0,radius*.82,-1.15,1.15);context.stroke();
    context.beginPath();context.moveTo(-radius,0);context.bezierCurveTo(-radius*.35,-radius*.25,radius*.35,radius*.25,radius,0);context.stroke();
    context.restore();
  }
  function shotVelocity(){
    const vz=325+power*35;
    const travel=(hoop.z-ball.z)/vz;
    return {vz,vy:430+power*105,vx:(aimTarget-ball.x)/travel};
  }
  function startCharge(){
    if(state!=='playing'||ball.active||resetClock>0)return;
    charging=true;power=Math.max(.08,power);powerDirection=1;setFeedback('Release inside the gold zone.');
  }
  function shoot(){
    if(!charging||state!=='playing'||ball.active)return;
    charging=false;const velocity=shotVelocity();
    Object.assign(ball,{vx:velocity.vx,vy:velocity.vy,vz:velocity.vz,active:true,scored:false});
    shots++;unlock('first-shot');tone(270,.07,'triangle');updateUi();
  }
  function resetBall(){
    Object.assign(ball,{x:0,y:38,z:24,vx:0,vy:0,vz:0,active:false,scored:false});
    power=.08;aimTarget=0;resetClock=0;
  }
  function resolveShot(){
    if(ball.scored)return;
    streak=0;setFeedback('Off the rim. Set your feet and shoot again.','bad');tone(120,.16,'square');ball.scored=true;updateUi();
  }
  function updateBall(dt){
    if(!ball.active)return;
    const previousZ=ball.z;
    ball.vy-=500*dt;ball.x+=ball.vx*dt;ball.y+=ball.vy*dt;ball.z+=ball.vz*dt;
    if(previousZ<hoop.z&&ball.z>=hoop.z&&!ball.scored){
      const clean=Math.abs(ball.x-hoop.x)<28&&Math.abs(ball.y-hoop.y)<23&&ball.vy<0;
      if(clean){
        const perfect=power>=.46&&power<=.62;
        const points=perfect?3:2;
        score+=points+Math.min(4,streak);made++;streak++;if(perfect)perfects++;
        ball.scored=true;netPulse=1;setFeedback(perfect?`PERFECT RELEASE · +${points+Math.min(4,streak-1)}`:`Bucket · +${points+Math.min(4,streak-1)}`,perfect?'perfect':'good');
        tone(perfect?880:680,.13,'sine');
        unlock('first-bucket');if(streak>=3)unlock('streak-3');if(score>=25)unlock('score-25');if(perfects>=3)unlock('perfect-3');
        saved.best=Math.max(saved.best,score);save();updateUi();
      }else if(Math.abs(ball.x)<55&&Math.abs(ball.y-hoop.y)<38){
        ball.vz*=-.34;ball.vx+=(ball.x>0?1:-1)*45;ball.vy*=.55;tone(190,.06,'square');
      }
    }
    if(ball.y<=0){
      ball.y=0;ball.vy=Math.abs(ball.vy)*.43;ball.vz*=.72;ball.vx*=.72;
      if(Math.abs(ball.vy)<45){if(!ball.scored)resolveShot();resetClock=.65}
    }
    if(ball.z>730||Math.abs(ball.x)>420){if(!ball.scored)resolveShot();resetClock=.55}
  }
  function update(dt){
    if(state==='playing'){
      seconds=Math.max(0,seconds-dt);
      if(charging){
        power+=dt*.72*powerDirection;
        if(power>=1){power=1;powerDirection=-1}else if(power<=.08){power=.08;powerDirection=1}
      }
      updateBall(dt);
      if(resetClock>0){resetClock-=dt;if(resetClock<=0){resetBall();if(seconds<=0)endGame()}}
      if(seconds<=0&&!ball.active&&!charging)endGame();
    }
    netPulse=Math.max(0,netPulse-dt*2.6);
    updateUi();
  }
  function updateUi(){
    ui.score.textContent=score;ui.made.textContent=`${made} / ${shots}`;ui.streak.textContent=streak;ui.time.textContent=Math.ceil(seconds);ui.best.textContent=saved.best;
    ui.fill.style.width=`${Math.round(power*100)}%`;ui.power.textContent=charging?`${Math.round(power*100)}%`:'HOLD';
    ui.aim.textContent=Math.abs(aimTarget)<9?'CENTRE':aimTarget<0?'LEFT':'RIGHT';
  }
  function startGame(){
    state='playing';seconds=60;score=shots=made=streak=perfects=0;charging=false;resetBall();overlay.classList.add('hidden');setFeedback('Hold the ball to charge your shot.');last=performance.now();updateUi();
  }
  function endGame(){
    if(state!=='playing')return;
    state='over';charging=false;saved.best=Math.max(saved.best,score);save();updateUi();
    document.getElementById('hoopOverlayTitle').textContent='Run complete';
    document.getElementById('hoopOverlayCopy').textContent=`${score} points · ${made} made from ${shots} shots. Your rooftop best is ${saved.best}.`;
    startButton.textContent='Shoot again';overlay.classList.remove('hidden');setFeedback('Shootaround complete.');
  }
  function draw(){
    resize();context.clearRect(0,0,width,height);drawBackground();drawCourt();drawGuide();drawHoop();drawBall();
  }
  function loop(now){
    const dt=Math.min(.04,(now-last)/1000);last=now;update(dt);draw();requestAnimationFrame(loop);
  }
  function setAim(clientX){
    const rect=canvas.getBoundingClientRect();
    const normalized=Math.max(-1,Math.min(1,((clientX-rect.left)/rect.width-.5)*2));
    aimTarget=normalized*92;
  }
  canvas.addEventListener('pointerdown',event=>{event.preventDefault();setAim(event.clientX);canvas.setPointerCapture?.(event.pointerId);startCharge()});
  canvas.addEventListener('pointermove',event=>{if(charging)setAim(event.clientX)});
  canvas.addEventListener('pointerup',event=>{event.preventDefault();setAim(event.clientX);shoot()});
  canvas.addEventListener('pointercancel',()=>shoot());
  window.addEventListener('keydown',event=>{
    if(event.code==='Space'&&!event.repeat){event.preventDefault();startCharge()}
    if(event.key==='ArrowLeft'){event.preventDefault();aimTarget=Math.max(-92,aimTarget-9)}
    if(event.key==='ArrowRight'){event.preventDefault();aimTarget=Math.min(92,aimTarget+9)}
  });
  window.addEventListener('keyup',event=>{if(event.code==='Space'){event.preventDefault();shoot()}});
  startButton.addEventListener('click',startGame);
  document.getElementById('hoopSound').addEventListener('click',event=>{sound=!sound;event.currentTarget.textContent=`Sound: ${sound?'On':'Off'}`;event.currentTarget.setAttribute('aria-pressed',String(sound));if(sound)tone(620,.05)});
  document.getElementById('hoopReset').addEventListener('click',()=>{
    if(!confirm('Reset Beyond Hoop best score and achievements?'))return;
    saved=blankSave();save();renderBadges();updateUi();setFeedback('Local progress reset.');
  });
  window.addEventListener('resize',resize,{passive:true});
  renderBadges();updateUi();resize();requestAnimationFrame(loop);
})();
