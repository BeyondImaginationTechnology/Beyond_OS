(()=>{
  const one=(selector,root=document)=>root.querySelector(selector);
  const all=(selector,root=document)=>[...root.querySelectorAll(selector)];
  const menu=one('#menuBtn'),nav=one('#nav');
  menu?.addEventListener('click',()=>nav.classList.toggle('open'));
  all('#nav a').forEach(link=>link.addEventListener('click',()=>nav.classList.remove('open')));
  const observer=new IntersectionObserver(entries=>entries.forEach(entry=>entry.isIntersecting&&entry.target.classList.add('visible')),{threshold:.12});
  all('.reveal').forEach(element=>observer.observe(element));
  const modal=one('#modal'),videoModal=one('#videoModal');
  const openModal=element=>{element.classList.add('open');element.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';};
  const closeModal=element=>{element.classList.remove('open');element.setAttribute('aria-hidden','true');document.body.style.overflow='';};
  all('.close').forEach(button=>button.addEventListener('click',()=>closeModal(button.closest('.modal'))));
  all('.modal').forEach(element=>element.addEventListener('click',event=>{if(event.target===element)closeModal(element);}));
  document.addEventListener('keydown',event=>{if(event.key==='Escape')all('.modal.open').forEach(closeModal);});
  all('.card').forEach(card=>card.addEventListener('click',()=>{const story=window.BS_STORIES[Number(card.dataset.story)];one('#modalIcon').textContent=story.icon;one('#modalEyebrow').textContent=story.eyebrow;one('#modalTitle').textContent=story.title;one('#modalCopy').textContent=story.copy;openModal(modal);}));
  one('#watchIntro')?.addEventListener('click',()=>openModal(videoModal));
  const planetPanel=one('#planetPanel');
  all('.world').forEach(world=>world.addEventListener('click',()=>{all('.world').forEach(item=>item.classList.remove('active'));world.classList.add('active');one('h3',planetPanel).textContent=world.dataset.planet;one('p',planetPanel).textContent=world.dataset.fact;}));
  const blackHoleFacts=['A black hole is detected through its effects on nearby matter and light.','The boundary beyond which escape becomes impossible is called the event horizon.','Supermassive black holes are found at the centres of many large galaxies.','An accretion disk can become extremely hot and luminous before matter crosses the horizon.'];
  let factIndex=0;
  one('#nextFact')?.addEventListener('click',()=>{factIndex=(factIndex+1)%blackHoleFacts.length;one('#factBox span').textContent=blackHoleFacts[factIndex];});
  one('.black-hole')?.addEventListener('click',event=>event.currentTarget.classList.toggle('active'));
  fetch('/beyond-space/api/daily-space-fact.php',{headers:{Accept:'application/json'}}).then(response=>response.ok?response.json():null).then(payload=>{
    const fact=payload?.fact;if(!fact)return;
    one('#dailyFactWorld').textContent='🪐 '+(fact.world||'Beyond Space');one('#dailyFactTitle').textContent=fact.title||'';one('#dailyFactCopy').textContent=fact.fact||'';one('#dailyFactLesson').textContent=fact.lesson||'';
    const source=one('#dailyFactSource');if(source&&fact.source_url){source.href=fact.source_url;source.textContent='Open source post ↗';source.hidden=false}
    const asset=one('#dailyFactAsset');if(asset&&fact.asset_url){asset.src=fact.asset_url;asset.alt=(fact.world||'Daily Space Fact')+' artwork';asset.hidden=false}
  }).catch(()=>{});

  const signs=window.BS_SIGNS||[],today=new Date(),todayKey=[today.getFullYear(),String(today.getMonth()+1).padStart(2,'0'),String(today.getDate()).padStart(2,'0')].join('-');
  let dailyHoroscopes={};
  const dailyThemes=['Give one unfinished idea a clear next step.','A useful conversation may change your view—listen for the detail beneath the words.','Protect an hour for focused work and let distractions orbit elsewhere.','Review what is working before adding something new.','Curiosity is stronger than certainty today; ask the better question.','Make one practical choice that your future self will appreciate.','Leave space for rest, reflection, and a small moment of wonder.'];
  const energyWords=['initiate','stabilize','communicate','restore','create','refine','balance','investigate','explore','structure','innovate','imagine'];
  const dateSeed=[...todayKey].reduce((sum,char)=>sum+char.charCodeAt(0),0);
  const renderSign=index=>{
    const sign=signs[index],theme=dailyThemes[(dateSeed+index)%dailyThemes.length];
    all('#zodiacGrid button').forEach((button,buttonIndex)=>button.classList.toggle('active',buttonIndex===index));
    one('.reading-symbol').textContent=sign.symbol;one('#reading h3').textContent=sign.name;one('#reading em').textContent=sign.dates;
    one('#readingElement').textContent=sign.element;one('#readingEnergy').textContent='Energy: '+energyWords[index];
    const generated=dailyHoroscopes[sign.name.toLowerCase()];
    one('#reading p').textContent=generated?.paragraphs?.length?generated.paragraphs.join(' '):sign.message+' '+theme;
    one('#readingSource').textContent=generated?.source||'Beyond Space original';
    try{localStorage.setItem('beyondSpaceSign',String(index));}catch(error){}
  };
  fetch('/beyond-space/api/daily-horoscope.php',{headers:{Accept:'application/json'}}).then(response=>response.ok?response.json():null).then(payload=>{
    if(!payload?.items?.length)return;
    dailyHoroscopes=Object.fromEntries(payload.items.map(item=>[String(item.sign||'').toLowerCase(),item]));
    const active=all('#zodiacGrid button').find(button=>button.classList.contains('active'));
    renderSign(Number(active?.dataset.sign||0));
  }).catch(()=>{});
  all('#zodiacGrid button').forEach(button=>button.addEventListener('click',()=>renderSign(Number(button.dataset.sign))));
  const signFromDate=value=>{
    if(!value)return -1;
    const [,monthText,dayText]=value.split('-'),month=Number(monthText),day=Number(dayText),code=month*100+day;
    if(code>=321&&code<=419)return 0;if(code>=420&&code<=520)return 1;if(code>=521&&code<=620)return 2;if(code>=621&&code<=722)return 3;
    if(code>=723&&code<=822)return 4;if(code>=823&&code<=922)return 5;if(code>=923&&code<=1022)return 6;if(code>=1023&&code<=1121)return 7;
    if(code>=1122&&code<=1221)return 8;if(code>=1222||code<=119)return 9;if(code>=120&&code<=218)return 10;return 11;
  };
  const birthDate=one('#birthDate'),signResult=one('#signResult');
  one('#findSign')?.addEventListener('click',()=>{const index=signFromDate(birthDate.value);if(index<0){signResult.textContent='Choose a birth date first.';return;}const sign=signs[index];renderSign(index);signResult.textContent=`${sign.symbol} ${sign.name} · ${sign.element} · ${sign.dates}`;try{localStorage.setItem('beyondSpaceBirthDate',birthDate.value);}catch(error){}one('#reading').scrollIntoView({behavior:'smooth',block:'center'});});
  const byName=name=>signs.find(sign=>sign.name===name);
  one('#compareSigns')?.addEventListener('click',()=>{const first=byName(one('#compatOne').value),second=byName(one('#compatTwo').value),supportive=first.element===second.element||['Fire:Air','Air:Fire','Earth:Water','Water:Earth'].includes(`${first.element}:${second.element}`),base=first.name===second.name?90:(supportive?80:67),variation=(first.name.length*7+second.name.length*3)%9,score=Math.min(96,base+variation),message=first.name===second.name?'Shared instincts can feel natural; make room for different roles.':supportive?'Traditional element pairings describe an easy exchange of energy; communication still matters.':'Different elements can create growth when both people stay curious and explicit about their needs.';one('#compatResult').textContent=`${first.symbol} ${first.name} + ${second.symbol} ${second.name}: ${score}% reflection score. ${message}`;});
  try{const savedDate=localStorage.getItem('beyondSpaceBirthDate'),savedIndex=Number(localStorage.getItem('beyondSpaceSign'));if(savedDate){birthDate.value=savedDate;const index=signFromDate(savedDate);if(index>=0)signResult.textContent=`Saved profile: ${signs[index].symbol} ${signs[index].name}`;}if(Number.isInteger(savedIndex)&&savedIndex>=0&&savedIndex<signs.length)renderSign(savedIndex);else renderSign(0);}catch(error){renderSign(0);}

  const questions=[
    {q:'Which planet is the largest in our Solar System?',a:['Earth','Jupiter','Saturn','Neptune'],c:1},
    {q:'What is the name of our galaxy?',a:['Andromeda','Sombrero','Milky Way','Whirlpool'],c:2},
    {q:'Which planet is known for its prominent ring system?',a:['Mars','Venus','Saturn','Mercury'],c:2},
    {q:'What does a space telescope avoid by operating above Earth’s atmosphere?',a:['All gravity','Atmospheric distortion','Solar radiation','Orbital motion'],c:1}
  ];
  let questionIndex=0,answered=false;
  const renderQuestion=()=>{answered=false;const question=questions[questionIndex];one('#question').textContent=question.q;one('#answers').replaceChildren();one('#feedback').textContent='';one('#nextQuestion').classList.add('hidden');question.a.forEach((label,index)=>{const button=document.createElement('button');button.className='answer';button.textContent=label;button.addEventListener('click',()=>answerQuestion(index,button));one('#answers').appendChild(button);});};
  const answerQuestion=(index,button)=>{if(answered)return;answered=true;const question=questions[questionIndex];all('.answer')[question.c].classList.add('correct');if(index!==question.c)button.classList.add('wrong');one('#feedback').textContent=index===question.c?'Correct — mission accomplished.':'Not quite. The correct answer is '+question.a[question.c]+'.';one('#nextQuestion').classList.remove('hidden');};
  one('#nextQuestion')?.addEventListener('click',()=>{questionIndex=(questionIndex+1)%questions.length;renderQuestion();});
  renderQuestion();
})();
