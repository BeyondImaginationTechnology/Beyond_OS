(()=>{
  const narration=document.querySelector('[data-narrate-lesson]');
  if(narration&&'speechSynthesis' in window){
    narration.addEventListener('click',()=>{
      if(speechSynthesis.speaking){speechSynthesis.cancel();narration.textContent='🔊 Listen to this free lesson';return;}
      const copy=[...document.querySelectorAll('[data-narration-copy]')].map(node=>node.textContent).join('. ');
      const voice=new SpeechSynthesisUtterance(copy);voice.rate=.94;voice.onend=()=>narration.textContent='🔊 Listen to this free lesson';
      speechSynthesis.speak(voice);narration.textContent='■ Stop narration';
    });
  }
  document.querySelectorAll('.math-activity').forEach((mount,index)=>{
    const key=`beyondCodingStudio:${location.search}:${index}`;
    const studio=document.createElement('div');studio.className='coding-studio';
    studio.innerHTML='<div class="coding-studio__steps"><label><input type="checkbox"> I can explain the core idea</label><label><input type="checkbox"> I tried the guided practice</label><label><input type="checkbox"> I checked one edge case</label></div><label class="coding-studio__note"><span>Builder notes</span><textarea rows="5" placeholder="Write a code idea, test result, design decision, or question…"></textarea></label><div class="coding-studio__actions"><button type="button">Save on this device</button><span aria-live="polite"></span></div>';
    mount.replaceChildren(studio);
    const textarea=studio.querySelector('textarea'),checks=[...studio.querySelectorAll('input')],status=studio.querySelector('[aria-live]');
    try{const saved=JSON.parse(localStorage.getItem(key)||'{}');textarea.value=saved.note||'';checks.forEach((item,i)=>item.checked=Boolean(saved.checks&&saved.checks[i]));}catch(error){}
    studio.querySelector('button').addEventListener('click',()=>{localStorage.setItem(key,JSON.stringify({note:textarea.value,checks:checks.map(item=>item.checked)}));status.textContent='Saved privately on this device ✓';});
  });
})();
