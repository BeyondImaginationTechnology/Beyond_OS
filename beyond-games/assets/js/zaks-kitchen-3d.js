(() => {
  'use strict';
  const canvas = document.getElementById('kitchen3d');
  if (!canvas) return;
  const context = canvas.getContext('2d');
  const buttons = [...document.querySelectorAll('[data-station]')];
  const labels = {
    prep:['PREP','🔪','#47b9a8'],
    season:['SEASON','✦','#e5a746'],
    stove:['STOVE','🍳','#e45e32'],
    grill:['GRILL','🔥','#e23e2d'],
    fryer:['FRYER','♨','#d8912e'],
    plate:['PLATE','🍽','#4e89d8']
  };
  const stations = [
    {id:'prep',x:-2.75,z:.05,w:1.35,d:1.05,h:.72},
    {id:'season',x:-.95,z:-.05,w:1.25,d:1.05,h:.72},
    {id:'stove',x:.9,z:-.05,w:1.25,d:1.05,h:.72},
    {id:'grill',x:2.7,z:.05,w:1.35,d:1.05,h:.72},
    {id:'fryer',x:-1.85,z:2.82,w:1.35,d:1.1,h:.78},
    {id:'plate',x:1.7,z:2.82,w:1.65,d:1.1,h:.72}
  ];
  let width = canvas.width;
  let height = canvas.height;
  let scale = 70;
  let pixelRatio = 1;
  let hover = '';
  let chef = {x:0,z:1.45,targetX:0,targetZ:1.45,bob:0};
  let hitAreas = [];
  let last = performance.now();

  function resize() {
    const rect = canvas.getBoundingClientRect();
    pixelRatio = Math.min(2, window.devicePixelRatio || 1);
    width = Math.max(420, Math.round(rect.width * pixelRatio));
    height = Math.max(310, Math.round(rect.height * pixelRatio));
    if (canvas.width !== width || canvas.height !== height) {
      canvas.width = width;
      canvas.height = height;
    }
    scale = Math.min(width / 12.8, height / 7.2);
  }

  function project(x,z,y=0) {
    return {
      x: width*.5 + (x-z)*scale,
      y: height*.16 + (x+z)*scale*.46 - y*scale
    };
  }

  function polygon(points, fill, stroke='') {
    context.beginPath();
    context.moveTo(points[0].x,points[0].y);
    points.slice(1).forEach(point=>context.lineTo(point.x,point.y));
    context.closePath();
    context.fillStyle=fill;
    context.fill();
    if(stroke){context.strokeStyle=stroke;context.lineWidth=Math.max(1,pixelRatio);context.stroke();}
  }

  function shade(hex, amount) {
    const value=parseInt(hex.slice(1),16);
    const r=Math.max(0,Math.min(255,(value>>16)+amount));
    const g=Math.max(0,Math.min(255,((value>>8)&255)+amount));
    const b=Math.max(0,Math.min(255,(value&255)+amount));
    return `rgb(${r},${g},${b})`;
  }

  function drawBox(item, active, working) {
    const color=labels[item.id][2];
    const x1=item.x-item.w/2,x2=item.x+item.w/2,z1=item.z-item.d/2,z2=item.z+item.d/2;
    const a=project(x1,z1,0),b=project(x2,z1,0),c=project(x2,z2,0),d=project(x1,z2,0);
    const at=project(x1,z1,item.h),bt=project(x2,z1,item.h),ct=project(x2,z2,item.h),dt=project(x1,z2,item.h);
    polygon([d,c,ct,dt],shade(color,-48),'rgba(255,255,255,.12)');
    polygon([b,c,ct,bt],shade(color,-72),'rgba(255,255,255,.1)');
    context.save();
    if(active||working){context.shadowColor=color;context.shadowBlur=working?30*pixelRatio:19*pixelRatio;}
    polygon([at,bt,ct,dt],active?shade(color,22):shade(color,-16),'rgba(255,255,255,.34)');
    context.restore();
    const center=project(item.x,item.z,item.h+.04);
    hitAreas.push({id:item.id,x:center.x,y:center.y,r:Math.max(34*pixelRatio,item.w*scale*.48)});
    context.textAlign='center';
    context.textBaseline='middle';
    context.font=`${Math.round(22*pixelRatio)}px system-ui`;
    context.fillText(labels[item.id][1],center.x,center.y-7*pixelRatio);
    context.font=`900 ${Math.round(7.5*pixelRatio)}px system-ui`;
    context.letterSpacing=`${1.2*pixelRatio}px`;
    context.fillStyle='#fff8e9';
    context.fillText(labels[item.id][0],center.x,center.y+15*pixelRatio);
    if(active){
      context.beginPath();
      context.arc(center.x,center.y,item.w*scale*.54,0,Math.PI*2);
      context.strokeStyle='rgba(255,229,163,.82)';
      context.lineWidth=2*pixelRatio;
      context.setLineDash([6*pixelRatio,5*pixelRatio]);
      context.stroke();
      context.setLineDash([]);
    }
    if(working) drawSteam(center.x,center.y-item.h*scale*.6,color);
  }

  function drawSteam(x,y,color){
    const time=performance.now()/420;
    context.save();
    context.strokeStyle=color;
    context.lineWidth=2*pixelRatio;
    context.globalAlpha=.64;
    for(let i=0;i<3;i++){
      context.beginPath();
      for(let step=0;step<12;step++){
        const yy=y-step*4*pixelRatio;
        const xx=x+(i-1)*11*pixelRatio+Math.sin(time+step*.52+i)*4*pixelRatio;
        if(step===0)context.moveTo(xx,yy);else context.lineTo(xx,yy);
      }
      context.stroke();
    }
    context.restore();
  }

  function drawChef(){
    const point=project(chef.x,chef.z,.12);
    const bob=Math.sin(chef.bob)*2.2*pixelRatio;
    context.save();
    context.translate(point.x,point.y+bob);
    context.shadowColor='rgba(0,0,0,.45)';
    context.shadowBlur=12*pixelRatio;
    context.beginPath();
    context.ellipse(0,12*pixelRatio,18*pixelRatio,8*pixelRatio,0,0,Math.PI*2);
    context.fillStyle='rgba(0,0,0,.4)';
    context.fill();
    context.shadowBlur=0;
    context.fillStyle='#f1f0ea';
    context.beginPath();
    context.roundRect(-13*pixelRatio,-25*pixelRatio,26*pixelRatio,37*pixelRatio,8*pixelRatio);
    context.fill();
    context.fillStyle='#d34a2e';
    context.fillRect(-13*pixelRatio,-4*pixelRatio,26*pixelRatio,6*pixelRatio);
    context.fillStyle='#7a452b';
    context.beginPath();
    context.arc(0,-31*pixelRatio,10*pixelRatio,0,Math.PI*2);
    context.fill();
    context.fillStyle='#fff';
    context.beginPath();
    context.ellipse(0,-42*pixelRatio,16*pixelRatio,8*pixelRatio,0,0,Math.PI*2);
    context.fill();
    context.fillRect(-10*pixelRatio,-44*pixelRatio,20*pixelRatio,8*pixelRatio);
    context.fillStyle='#20202a';
    context.font=`900 ${Math.round(7*pixelRatio)}px system-ui`;
    context.textAlign='center';
    context.fillText('ZAK',0,-1*pixelRatio);
    context.restore();
  }

  function drawRoom(){
    const gradient=context.createLinearGradient(0,0,0,height);
    gradient.addColorStop(0,'#132a34');
    gradient.addColorStop(.43,'#1c2930');
    gradient.addColorStop(1,'#080c0e');
    context.fillStyle=gradient;
    context.fillRect(0,0,width,height);
    context.fillStyle='rgba(255,188,92,.08)';
    context.beginPath();
    context.arc(width*.72,height*.06,width*.28,0,Math.PI*2);
    context.fill();
    const floor=[project(-4.5,-1.15),project(4.5,-1.15),project(4.5,5),project(-4.5,5)];
    polygon(floor,'#171b1b','rgba(255,205,135,.22)');
    context.save();
    context.strokeStyle='rgba(255,255,255,.055)';
    context.lineWidth=pixelRatio;
    for(let x=-4.5;x<=4.5;x+=.75){
      const p1=project(x,-1.15),p2=project(x,5);
      context.beginPath();context.moveTo(p1.x,p1.y);context.lineTo(p2.x,p2.y);context.stroke();
    }
    for(let z=-1.15;z<=5;z+=.65){
      const p1=project(-4.5,z),p2=project(4.5,z);
      context.beginPath();context.moveTo(p1.x,p1.y);context.lineTo(p2.x,p2.y);context.stroke();
    }
    context.restore();
    const sign=project(0,-1.05,2.2);
    context.textAlign='center';
    context.fillStyle='#ffcf76';
    context.font=`italic 800 ${Math.round(23*pixelRatio)}px Georgia`;
    context.fillText("Zak’s Kitchen",sign.x,sign.y);
    context.fillStyle='rgba(255,255,255,.55)';
    context.font=`900 ${Math.round(6.5*pixelRatio)}px system-ui`;
    context.fillText('GOOD FOOD · GOOD PEOPLE',sign.x,sign.y+17*pixelRatio);
  }

  function update(dt){
    const dx=chef.targetX-chef.x,dz=chef.targetZ-chef.z;
    const distance=Math.hypot(dx,dz);
    if(distance>.015){
      const speed=Math.min(distance,dt*3.4);
      chef.x+=dx/distance*speed;
      chef.z+=dz/distance*speed;
      chef.bob+=dt*13;
    }
  }

  function draw(){
    resize();
    context.clearRect(0,0,width,height);
    drawRoom();
    hitAreas=[];
    const sorted=[...stations].sort((a,b)=>(a.x+a.z)-(b.x+b.z));
    sorted.forEach(station=>{
      const button=buttons.find(item=>item.dataset.station===station.id);
      drawBox(station,button?.classList.contains('next')||hover===station.id,button?.classList.contains('working'));
    });
    drawChef();
    const activeTicket=document.getElementById('cookHeading')?.textContent||'';
    if(activeTicket&&!activeTicket.includes('Ready')){
      context.fillStyle='rgba(5,8,10,.72)';
      context.fillRect(width*.03,height*.82,width*.32,height*.095);
      context.fillStyle='#ffe3ad';
      context.textAlign='left';
      context.font=`900 ${Math.round(8*pixelRatio)}px system-ui`;
      context.fillText('NOW COOKING',width*.045,height*.855);
      context.fillStyle='#fff';
      context.font=`800 ${Math.round(10*pixelRatio)}px system-ui`;
      context.fillText(activeTicket.replace(/^\S+\s/,''),width*.045,height*.89);
    }
  }

  function loop(now){
    const dt=Math.min(.05,(now-last)/1000);
    last=now;
    update(dt);
    draw();
    requestAnimationFrame(loop);
  }

  function pointer(event,activate=false){
    const rect=canvas.getBoundingClientRect();
    const x=(event.clientX-rect.left)*pixelRatio;
    const y=(event.clientY-rect.top)*pixelRatio;
    const nearest=hitAreas
      .map(area=>({...area,distance:Math.hypot(area.x-x,area.y-y)}))
      .filter(area=>area.distance<area.r)
      .sort((a,b)=>a.distance-b.distance)[0];
    hover=nearest?.id||'';
    canvas.style.cursor=hover?'pointer':'default';
    if(activate&&nearest){
      const station=stations.find(item=>item.id===nearest.id);
      chef.targetX=station.x;
      chef.targetZ=station.z+.65;
      buttons.find(item=>item.dataset.station===nearest.id)?.click();
    }
  }

  canvas.addEventListener('pointermove',event=>pointer(event));
  canvas.addEventListener('pointerleave',()=>{hover='';canvas.style.cursor='default';});
  canvas.addEventListener('pointerup',event=>pointer(event,true));
  buttons.forEach(button=>button.addEventListener('click',()=>{
    const station=stations.find(item=>item.id===button.dataset.station);
    if(station){chef.targetX=station.x;chef.targetZ=station.z+.65;}
  }));
  window.addEventListener('resize',resize,{passive:true});
  resize();
  requestAnimationFrame(loop);
})();
