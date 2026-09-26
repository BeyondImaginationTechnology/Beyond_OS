(()=>{
  const card=document.getElementById('share-card');
  if(!card)return;
  const passage=document.getElementById('share-passage')?.textContent.trim()||'';
  const reference=document.getElementById('share-reference')?.textContent.trim()||'';
  const reflection=document.getElementById('share-reflection')?.textContent.trim()||'';
  const label=card.querySelector('.eyebrow')?.textContent.trim()||'Daily Breath';
  const imageUrl=card.dataset.image;
  function wrap(context,text,maxWidth,lineHeight,maxLines){
    const words=text.split(/\s+/),lines=[];let line='';
    for(const word of words){
      const test=line?line+' '+word:word;
      if(context.measureText(test).width>maxWidth&&line){lines.push(line);line=word;if(lines.length>=maxLines-1)break}
      else line=test;
    }
    if(line)lines.push(line);
    if(lines.length===maxLines&&words.length>lines.join(' ').split(/\s+/).length){
      const last=lines.length-1;
      while(lines[last].length>3&&context.measureText(lines[last]+'…').width>maxWidth)lines[last]=lines[last].slice(0,-1);
      lines[last]+='…';
    }
    return lines.slice(0,maxLines).map((value,index)=>({value,y:index*lineHeight}));
  }
  document.getElementById('download-card')?.addEventListener('click',()=>{
    const canvas=document.createElement('canvas');canvas.width=1200;canvas.height=675;
    const ctx=canvas.getContext('2d');if(!ctx)return;
    const img=new Image();
    const render=()=>{
      if(img.naturalWidth)ctx.drawImage(img,0,0,1200,675);
      else{ctx.fillStyle='#0b3020';ctx.fillRect(0,0,1200,675);const base=ctx.createLinearGradient(0,0,1200,675);base.addColorStop(0,'#17472d');base.addColorStop(1,'#061b12');ctx.fillStyle=base;ctx.fillRect(0,0,1200,675)}
      const shade=ctx.createLinearGradient(0,0,0,675);shade.addColorStop(0,'#00130d48');shade.addColorStop(.55,'#00130d72');shade.addColorStop(1,'#00130d90');ctx.fillStyle=shade;ctx.fillRect(0,0,1200,675);
      ctx.textAlign='center';ctx.textBaseline='middle';ctx.shadowColor='#00150eee';ctx.shadowBlur=14;ctx.fillStyle='#f4d898';ctx.font='600 25px Georgia,serif';ctx.fillText(label,600,88,1040);
      ctx.fillStyle='#fffdf9';ctx.font='500 52px Georgia, "Noto Naskh Arabic", serif';ctx.direction=/[\u0600-\u06ff]/u.test(passage)?'rtl':'ltr';const lines=wrap(ctx,passage,1000,66,4);const verseHeight=lines.length*66;lines.forEach(line=>ctx.fillText(line.value,600,310-verseHeight/2+line.y,1000));
      ctx.fillStyle='#f6d891';ctx.font='600 34px Georgia,serif';ctx.fillText(reference,600,455,1000);
      if(reflection){ctx.fillStyle='#fff5dc';ctx.font='500 24px Georgia,serif';const note=wrap(ctx,reflection,900,32,2);note.forEach(line=>ctx.fillText(line.value,600,510+line.y,900))}
      ctx.shadowBlur=0;ctx.fillStyle='#f4d898';ctx.font='700 20px system-ui,sans-serif';ctx.fillText('@thedaybreath · Faith · Recovery · Hope',600,632,1000);
      canvas.toBlob(blob=>{if(!blob)return;const url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download='daily-breath-'+card.dataset.tradition+'-'+new Date().toISOString().slice(0,10)+'.png';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000)},'image/png');
    };
    if(imageUrl){img.onload=render;img.onerror=render;img.src=imageUrl}else render();
  });
  document.getElementById('share-reading')?.addEventListener('click',async()=>{
    const data={title:document.title,text:passage+'\n\n'+reference,url:location.href};
    if(navigator.share){try{await navigator.share(data)}catch(error){if(error.name!=='AbortError')await navigator.clipboard?.writeText(location.href)}}
    else{try{await navigator.clipboard.writeText(location.href);alert('Share link copied.')}catch{prompt('Copy this share link:',location.href)}}
  });
  document.getElementById('listen-reading')?.addEventListener('click',()=>{
    if(!('speechSynthesis'in window)){alert('Audio narration is not available in this browser.');return}
    speechSynthesis.cancel();const utterance=new SpeechSynthesisUtterance([passage,reference,reflection].filter(Boolean).join('. '));utterance.lang=document.documentElement.lang==='fr'?'fr-FR':document.documentElement.lang==='es'?'es-ES':'en-US';if(document.documentElement.dir==='rtl')utterance.lang='ar';speechSynthesis.speak(utterance);
  });
})();
