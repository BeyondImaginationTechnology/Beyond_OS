(() => {
  const speakButton = document.querySelector('[data-narrate-lesson]');
  speakButton?.addEventListener('click', () => {
    if (!('speechSynthesis' in window)) return;
    if (speechSynthesis.speaking) {
      speechSynthesis.cancel();
      speakButton.textContent = '🔊 Listen to this free lesson';
      return;
    }
    const title = document.querySelector('.la-reading h1')?.textContent || '';
    const copy = [...document.querySelectorAll('[data-narration-copy]')].map(node => node.textContent).join(' ');
    const utterance = new SpeechSynthesisUtterance(`${title}. ${copy}`);
    utterance.lang = 'en-US';
    utterance.rate = 0.92;
    utterance.onend = () => speakButton.textContent = '🔊 Listen to this free lesson';
    speakButton.textContent = '■ Stop narration';
    speechSynthesis.speak(utterance);
  });

  const host = document.querySelector('[data-math-activity]');
  if (!host) return;
  const mount = host.querySelector('.math-activity');
  const type = host.dataset.mathActivity;
  const lesson = Number(host.dataset.lesson || 1);
  const games = {
    'place-value': {q:'Build 472', controls:true, answer:'472'},
    'number-name': {q:'Which name matches 406?', choices:['four hundred sixty','four hundred six','forty-six'], answer:'four hundred six'},
    'compare': {q:'563 __ 536', choices:['<','>','='], answer:'>'},
    'number-line': {q:'Place the marker on 70', range:true, answer:'70'},
    'rounding': {q:'67 rounded to the nearest ten is…', choices:['60','65','70'], answer:'70'},
    'pattern': {q:'4, 8, 12, 16, __', choices:['18','20','24'], answer:'20'},
    'expanded': {q:'Choose the expanded form of 729', choices:['700 + 20 + 9','70 + 20 + 9','700 + 29 + 9'], answer:'700 + 20 + 9'},
    'estimate': {q:'Five rows of about 10 buttons is closest to…', choices:['15','50','500'], answer:'50'},
    'story': {q:'There are 198 guests. What is the best estimate for planning chairs?', choices:['20','200','2,000'], answer:'200'},
    'mission': {q:'Round 398 and 403, then estimate their total.', choices:['400','800','1,200'], answer:'800'}
  };
  const game = games[type] || games['number-line'];
  mount.innerHTML = `<div class="math-lab"><span class="math-lab__tag">GAME ${lesson}</span><h3>${game.q}</h3><div class="math-lab__work"></div><p class="math-lab__feedback">Make your move when you’re ready.</p></div>`;
  const work = mount.querySelector('.math-lab__work');
  const feedback = mount.querySelector('.math-lab__feedback');
  const check = value => {
    const correct = String(value) === game.answer;
    feedback.textContent = correct ? '⭐ Great reasoning! You got it.' : 'Keep going—use the worked example, then try again.';
    feedback.className = `math-lab__feedback ${correct ? 'correct' : 'retry'}`;
  };
  if (game.controls) {
    work.innerHTML = ['Hundreds','Tens','Ones'].map((label,i) => `<label>${label}<input type="number" inputmode="numeric" min="0" max="9" value="0" data-place="${i}"></label>`).join('') + '<button type="button">Check my number</button>';
    work.querySelector('button').onclick = () => check([...work.querySelectorAll('input')].map(input => input.value).join(''));
  } else if (game.range) {
    work.innerHTML = '<input aria-label="Number line value" type="range" min="0" max="100" step="10" value="0"><output>0</output><button type="button">Check position</button>';
    const range=work.querySelector('input'),output=work.querySelector('output');range.oninput=()=>output.textContent=range.value;work.querySelector('button').onclick=()=>check(range.value);
  } else game.choices.forEach(choice => { const button=document.createElement('button');button.type='button';button.textContent=choice;button.onclick=()=>check(choice);work.append(button); });
})();
