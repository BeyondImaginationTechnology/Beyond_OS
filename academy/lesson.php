<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/academy-certificates.php';
require_once __DIR__ . '/../includes/app-layout.php';
$userId = academy_require_user();
$slug = (string)($_GET['course'] ?? $_POST['course'] ?? '');
$course = academy_course($slug);
$lessonNumber = (int)($_GET['lesson'] ?? $_POST['lesson'] ?? 0);
if (!$course || $lessonNumber < 1 || $lessonNumber > count($course['lessons'])) {
    http_response_code(404);
    exit('Lesson not found.');
}
if (!academy_lesson_unlocked($userId, $slug, $lessonNumber)) {
    http_response_code(403);
    exit('Complete the previous lesson before continuing.');
}
$lesson = $course['lessons'][$lessonNumber - 1];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!academy_verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Please reload and try again.');
    }
    academy_complete_lesson($userId, $slug, $lessonNumber);
    $next = $lessonNumber < count($course['lessons'])
        ? 'lesson.php?course=' . rawurlencode($slug) . '&lesson=' . ($lessonNumber + 1)
        : 'pathway.php?course=' . rawurlencode($slug);
    header('Location: ' . $next);
    exit;
}
$complete = in_array($lessonNumber, academy_completed_lessons($userId, $slug), true);
$rich = isset($lesson['sections']) && is_array($lesson['sections']);
$lab = is_array($lesson['lab'] ?? null) ? $lesson['lab'] : null;
$check = is_array($lesson['check'] ?? null) ? $lesson['check'] : null;
bos_page_start('Beyond Academy', academy_lesson_title($lesson), academy_lesson_summary($lesson));
?>
<link rel="stylesheet" href="<?=e(beyond_url('assets/css/academy-certificates.css?v=20260730-2'))?>">
<main class="academy-shell lesson-content">
  <header class="academy-top"><a href="<?=e(beyond_url('academy/pathway.php?course=' . rawurlencode($slug)))?>"><strong>← <?=e($course['title'])?></strong></a><span>Lesson <?=$lessonNumber?> of <?=count($course['lessons'])?></span></header>
  <article data-lesson-narration>
    <div class="lesson-heading">
      <div><span class="academy-kicker"><?=e($course['title'])?> · Lesson <?=$lessonNumber?></span><h1><?=e(academy_lesson_title($lesson))?></h1><p><?=e(academy_lesson_summary($lesson))?></p></div>
      <?php if($rich):?><button class="academy-action secondary narration-button" type="button" data-narrate>▶ Listen to lesson</button><?php endif;?>
    </div>
    <?php if($rich):?>
      <div class="lesson-meta"><span><?=e((string)($lesson['duration'] ?? 'Self-paced'))?></span><span>Interactive lab</span><span>Knowledge check</span></div>
      <section class="lesson-objectives"><span class="academy-kicker">You will learn to</span><ul><?php foreach((array)($lesson['objectives'] ?? []) as $objective):?><li><?=e((string)$objective)?></li><?php endforeach;?></ul></section>
      <div class="lesson-chapters">
        <?php foreach($lesson['sections'] as $index=>$section):?>
          <section class="lesson-chapter"><span class="chapter-number"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></span><div><h2><?=e((string)$section['title'])?></h2><p><?=e((string)$section['body'])?></p></div></section>
        <?php endforeach;?>
      </div>
      <?php if(!empty($lesson['example'])):$example=$lesson['example'];?>
        <section class="worked-example"><span class="academy-kicker">Worked example</span><h2><?=e((string)$example['title'])?></h2><ol><?php foreach((array)$example['steps'] as $step):?><li><?=e((string)$step)?></li><?php endforeach;?></ol></section>
      <?php endif;?>
      <?php if($lab):?>
        <section class="academy-lab" data-lab="<?=e((string)$lab['type'])?>">
          <div class="lab-heading"><div><span class="academy-kicker">Interactive learning lab</span><h2><?=e((string)$lab['title'])?></h2><p><?=e((string)$lab['prompt'])?></p></div><span class="lab-status">Runs in this page</span></div>
          <?php if($lab['type']==='web-playground'):?>
            <div class="code-workspace">
              <div class="code-editors">
                <label><span>HTML</span><textarea data-code="html" spellcheck="false"><?=e((string)$lab['html'])?></textarea></label>
                <label><span>CSS</span><textarea data-code="css" spellcheck="false"><?=e(str_replace('\n', "\n", (string)$lab['css']))?></textarea></label>
                <label><span>JavaScript</span><textarea data-code="js" spellcheck="false"><?=e((string)$lab['js'])?></textarea></label>
              </div>
              <div class="preview-panel"><div><strong>Live preview</strong><button class="academy-action" type="button" data-run-code>Run preview</button></div><iframe sandbox="allow-scripts" title="Code preview" data-code-preview></iframe></div>
            </div>
          <?php elseif(in_array($lab['type'],['money-sort','fraud-spotter'],true)):?>
            <div class="decision-lab"><?php foreach((array)$lab['items'] as $item):?><label data-answer="<?=e((string)$item['answer'])?>"><span><?=e((string)$item['label'])?></span><select><option value="">Choose…</option><?php foreach($lab['type']==='money-sort'?['Need','Want','Goal']:['Continue carefully','Stop and verify'] as $option):?><option><?=e($option)?></option><?php endforeach;?></select></label><?php endforeach;?></div>
            <button class="academy-action" type="button" data-check-decisions>Check my decisions</button><p class="lab-feedback" data-lab-feedback aria-live="polite"></p>
          <?php elseif($lab['type']==='budget'):?>
            <div class="calculator-grid" data-budget>
              <label>Monthly take-home income <input type="number" min="0" step="10" value="3200" data-budget-income></label>
              <label>Housing and utilities <input type="number" min="0" step="10" value="1350" data-budget-cost></label>
              <label>Food and essentials <input type="number" min="0" step="10" value="500" data-budget-cost></label>
              <label>Transport and obligations <input type="number" min="0" step="10" value="450" data-budget-cost></label>
              <label>Savings and sinking funds <input type="number" min="0" step="10" value="400" data-budget-cost></label>
              <label>Wants and flexible spending <input type="number" min="0" step="10" value="300" data-budget-cost></label>
            </div><div class="calculator-result" data-budget-result aria-live="polite"></div>
          <?php elseif($lab['type']==='savings'):?>
            <div class="calculator-grid" data-savings>
              <label>Goal amount <input type="number" min="1" step="25" value="1500" data-savings-target></label>
              <label>Already saved <input type="number" min="0" step="25" value="300" data-savings-current></label>
              <label>Monthly contribution <input type="number" min="1" step="5" value="100" data-savings-monthly></label>
            </div><div class="calculator-result" data-savings-result aria-live="polite"></div>
          <?php elseif($lab['type']==='credit'):?>
            <div class="calculator-grid" data-credit>
              <label>Current balance <input type="number" min="1" step="25" value="2500" data-credit-balance></label>
              <label>APR (%) <input type="number" min="0" max="100" step=".1" value="19.99" data-credit-apr></label>
              <label>Fixed monthly payment <input type="number" min="1" step="5" value="100" data-credit-payment></label>
            </div><div class="calculator-result" data-credit-result aria-live="polite"></div>
          <?php endif;?>
        </section>
      <?php endif;?>
      <?php if($check):?>
        <section class="knowledge-check" data-knowledge-check data-answer="<?=e((string)$check['answer'])?>">
          <span class="academy-kicker">Knowledge check</span><h2><?=e((string)$check['question'])?></h2>
          <div class="check-options"><?php foreach((array)$check['options'] as $option):?><button type="button" data-check-option="<?=e((string)$option)?>"><?=e((string)$option)?></button><?php endforeach;?></div>
          <p data-check-feedback data-explanation="<?=e((string)$check['explanation'])?>" aria-live="polite">Choose the best answer.</p>
        </section>
      <?php endif;?>
    <?php else:?>
      <h2>Put it into practice</h2>
      <p>Write down one example in your own words, complete a small real-life application, and check that you can explain why your approach works. This reflection prepares you for the final pathway assessment.</p>
    <?php endif;?>
    <form class="lesson-complete-form" method="post">
      <input type="hidden" name="csrf" value="<?=e(academy_csrf())?>">
      <input type="hidden" name="course" value="<?=e($slug)?>">
      <input type="hidden" name="lesson" value="<?=$lessonNumber?>">
      <button class="academy-action" type="submit"><?=$complete ? 'Continue to next lesson' : ($lessonNumber===count($course['lessons'])?'Complete pathway lessons':'Complete lesson and continue')?></button>
    </form>
  </article>
</main>
<script>
(()=>{
  const money=value=>new Intl.NumberFormat('en-CA',{style:'currency',currency:'CAD',maximumFractionDigits:2}).format(value);
  const narrate=document.querySelector('[data-narrate]');
  if(narrate&&'speechSynthesis' in window){narrate.addEventListener('click',()=>{if(speechSynthesis.speaking){speechSynthesis.cancel();narrate.textContent='▶ Listen to lesson';return;}const copy=document.querySelector('[data-lesson-narration]').innerText.replace(/▶ Listen to lesson/g,'');const voice=new SpeechSynthesisUtterance(copy);voice.rate=.94;voice.onend=()=>narrate.textContent='▶ Listen to lesson';speechSynthesis.speak(voice);narrate.textContent='■ Stop narration';});}
  document.querySelectorAll('[data-lab="web-playground"]').forEach(lab=>{const frame=lab.querySelector('[data-code-preview]');const run=()=>{const html=lab.querySelector('[data-code="html"]').value,css=lab.querySelector('[data-code="css"]').value,js=lab.querySelector('[data-code="js"]').value;frame.srcdoc=`<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><style>${css}</style>${html}<script>${js}<\/script>`;};lab.querySelector('[data-run-code]').addEventListener('click',run);run();});
  document.querySelectorAll('[data-check-decisions]').forEach(button=>button.addEventListener('click',()=>{const lab=button.closest('.academy-lab'),rows=[...lab.querySelectorAll('.decision-lab label')],correct=rows.filter(row=>row.querySelector('select').value===row.dataset.answer).length,feedback=lab.querySelector('[data-lab-feedback]');feedback.textContent=`${correct} of ${rows.length} correct. ${correct===rows.length?'Excellent—your reasoning matches the scenario.':'Review the purpose and risk of each choice, then try again.'}`;feedback.className='lab-feedback '+(correct===rows.length?'correct':'retry');}));
  const budget=document.querySelector('[data-budget]');if(budget){const render=()=>{const income=Number(budget.querySelector('[data-budget-income]').value)||0,costs=[...budget.querySelectorAll('[data-budget-cost]')].reduce((sum,input)=>sum+(Number(input.value)||0),0),left=income-costs;document.querySelector('[data-budget-result]').innerHTML=`<strong>${money(left)} ${left>=0?'remaining':'over budget'}</strong><span>Planned: ${money(costs)} of ${money(income)} · ${income?Math.round(costs/income*100):0}%</span>`;};budget.addEventListener('input',render);render();}
  const savings=document.querySelector('[data-savings]');if(savings){const render=()=>{const target=Number(savings.querySelector('[data-savings-target]').value)||0,current=Number(savings.querySelector('[data-savings-current]').value)||0,monthly=Number(savings.querySelector('[data-savings-monthly]').value)||0,remaining=Math.max(0,target-current),months=monthly>0?Math.ceil(remaining/monthly):0;document.querySelector('[data-savings-result]').innerHTML=`<strong>${remaining===0?'Goal reached':months+' month'+(months===1?'':'s')}</strong><span>${money(remaining)} remaining at ${money(monthly)} per month</span>`;};savings.addEventListener('input',render);render();}
  const credit=document.querySelector('[data-credit]');if(credit){const render=()=>{let balance=Number(credit.querySelector('[data-credit-balance]').value)||0;const original=balance,apr=(Number(credit.querySelector('[data-credit-apr]').value)||0)/100,payment=Number(credit.querySelector('[data-credit-payment]').value)||0,rate=apr/12;let months=0,interest=0;while(balance>0&&months<600){const charge=balance*rate;if(payment<=charge){months=601;break;}interest+=charge;balance+=charge;balance-=Math.min(payment,balance);months++;}document.querySelector('[data-credit-result]').innerHTML=months>600?'<strong>Payment is too low</strong><span>It does not reduce the balance under these assumptions.</span>':`<strong>${months} months to repay</strong><span>Estimated interest: ${money(interest)} · Total paid: ${money(original+interest)}</span>`;};credit.addEventListener('input',render);render();}
  document.querySelectorAll('[data-knowledge-check]').forEach(check=>{check.querySelectorAll('[data-check-option]').forEach(button=>button.addEventListener('click',()=>{const correct=button.dataset.checkOption===check.dataset.answer,feedback=check.querySelector('[data-check-feedback]');check.querySelectorAll('button').forEach(item=>item.classList.remove('selected','correct','incorrect'));button.classList.add('selected',correct?'correct':'incorrect');feedback.textContent=correct?'Correct. '+feedback.dataset.explanation:'Not quite. Re-read the lesson and try another answer.';feedback.className=correct?'correct':'retry';}));});
})();
</script>
<?php bos_page_end(); ?>
