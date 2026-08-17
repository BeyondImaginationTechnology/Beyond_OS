<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
require_once __DIR__ . '/includes/verse-of-day.php';
$beyondWallet = beyond_app_bootstrap('DailyBreath');
$pdo = beyond_db();
$userId = (int)$_SESSION['user_id'];
$section = in_array($_GET['section'] ?? '', ['breathing','prayers','journal','challenge'], true) ? $_GET['section'] : 'breathing';
if (empty($_SESSION['practice_csrf'])) $_SESSION['practice_csrf'] = bin2hex(random_bytes(24));
$notice='';$error='';

function journal_key(): string {
  if (!function_exists('sodium_crypto_secretbox')) throw new RuntimeException('Secure journal encryption requires the PHP Sodium extension.');
  $env = trim((string)getenv('BEYOND_JOURNAL_KEY'));
  if ($env !== '') {
    $decoded = base64_decode($env, true);
    if ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) return $decoded;
  }
  $privateDir = __DIR__ . '/../beyond-id/storage/private';
  $keyFile = $privateDir . '/dailybreath-journal.key';
  if (!is_dir($privateDir) && !mkdir($privateDir, 0700, true) && !is_dir($privateDir)) throw new RuntimeException('The private journal storage folder could not be created.');
  $denyFile = $privateDir . '/.htaccess';
  if (!is_file($denyFile)) @file_put_contents($denyFile, "Require all denied\nDeny from all\n");
  if (!is_file($keyFile)) {
    $created = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    if (file_put_contents($keyFile, $created, LOCK_EX) === false) throw new RuntimeException('The journal encryption key could not be saved. Check folder permissions.');
    @chmod($keyFile, 0600);
  }
  $decoded = base64_decode(trim((string)file_get_contents($keyFile)), true);
  if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) throw new RuntimeException('The private journal encryption key is invalid.');
  return $decoded;
}
function journal_encrypt(string $plain): string {
  $key=journal_key();$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  return base64_encode($nonce.sodium_crypto_secretbox($plain,$nonce,$key));
}
function journal_decrypt(string $encoded): string {
  $raw=base64_decode($encoded,true);if($raw===false||strlen($raw)<=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)return '';
  $nonce=substr($raw,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);$cipher=substr($raw,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  $plain=sodium_crypto_secretbox_open($cipher,$nonce,journal_key());return $plain===false?'':$plain;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  try {
    if (!hash_equals((string)$_SESSION['practice_csrf'], (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Reload the page and try again.');
    $action=$_POST['action'] ?? '';
    if ($action==='breath') {
      $pdo->prepare('INSERT INTO breathing_sessions(user_id,exercise_id,completed_cycles,duration_seconds,completed_at) VALUES(?,?,?,?,CURRENT_TIMESTAMP)')->execute([$userId,(int)$_POST['exercise_id'],(int)$_POST['cycles'],(int)$_POST['duration']]);
      $notice='Breathing session completed.';
    } elseif ($action==='journal') {
      $plain=trim((string)($_POST['entry'] ?? ''));if($plain==='')throw new RuntimeException('Write a reflection before saving.');
      $mood=substr(trim((string)($_POST['mood']??'')),0,40);
      $pdo->prepare('INSERT INTO reflection_journal_entries(user_id,prompt_id,content_ciphertext,encryption_version,mood,entry_date) VALUES(?,?,?,?,?,?)')->execute([$userId,(int)$_POST['prompt_id']?:null,journal_encrypt($plain),1,$mood,date('Y-m-d')]);
      $_POST=[];$notice='Reflection submitted and encrypted securely.';
    } elseif ($action==='delete_journal') {
      $entryId=(int)($_POST['entry_id']??0);if($entryId<1)throw new RuntimeException('Journal entry not found.');
      $stmt=$pdo->prepare('DELETE FROM reflection_journal_entries WHERE id=? AND user_id=?');$stmt->execute([$entryId,$userId]);
      if(!$stmt->rowCount())throw new RuntimeException('Journal entry not found.');
      $notice='Journal entry deleted.';
    } elseif ($action==='challenge') {
      if(($_POST['challenge_source']??'')==='bundled'){
        $id=preg_replace('/[^a-z0-9-]/','',(string)($_POST['challenge_id']??''));
        if($id==='')throw new RuntimeException('Challenge not found.');
        $_SESSION['recovery_challenge_progress'][$id]=min(7,(int)($_SESSION['recovery_challenge_progress'][$id]??0)+1);
      }else{
        $id=(int)$_POST['challenge_id'];$driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql=$driver==='sqlite'?'INSERT INTO weekly_challenge_progress(user_id,challenge_id,completed_count,updated_at) VALUES(?,?,1,CURRENT_TIMESTAMP) ON CONFLICT(user_id,challenge_id) DO UPDATE SET completed_count=MIN(completed_count+1,7),updated_at=CURRENT_TIMESTAMP':'INSERT INTO weekly_challenge_progress(user_id,challenge_id,completed_count) VALUES(?,?,1) ON DUPLICATE KEY UPDATE completed_count=LEAST(completed_count+1,7),updated_at=CURRENT_TIMESTAMP';
        $pdo->prepare($sql)->execute([$userId,$id]);
      }
      $notice='Today’s challenge progress recorded.';
    }
  } catch(Throwable $e){$error=$e->getMessage();}
}

$exercise=$pdo->query("SELECT * FROM breathing_exercises WHERE is_published=1 ORDER BY sort_order,id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$prayers=$pdo->query("SELECT * FROM guided_prayers WHERE is_published=1 ORDER BY sort_order,id")->fetchAll(PDO::FETCH_ASSOC);
$prompt=$pdo->query("SELECT * FROM reflection_prompts WHERE is_published=1 ORDER BY prompt_date DESC,id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$challenge=dailybreath_recovery_challenge_for_date(date('Y-m-d'));if(!$challenge)try{$challenge=$pdo->query("SELECT * FROM weekly_challenges WHERE is_published=1 AND starts_on<=CURRENT_DATE AND ends_on>=CURRENT_DATE ORDER BY starts_on DESC,id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC)?:null;}catch(Throwable $e){}
$progress=0;if($challenge){if(($challenge['source']??'')==='bundled_recovery_challenge'){$progress=(int)($_SESSION['recovery_challenge_progress'][$challenge['id']]??0);}else{$s=$pdo->prepare('SELECT completed_count FROM weekly_challenge_progress WHERE user_id=? AND challenge_id=?');$s->execute([$userId,$challenge['id']]);$progress=(int)$s->fetchColumn();}}
$journalEntries=[];
if($section==='journal'){
  $s=$pdo->prepare('SELECT j.id,j.content_ciphertext,j.mood,j.entry_date,j.created_at,p.prompt_text FROM reflection_journal_entries j LEFT JOIN reflection_prompts p ON p.id=j.prompt_id WHERE j.user_id=? ORDER BY j.created_at DESC LIMIT 25');
  $s->execute([$userId]);
  foreach($s->fetchAll(PDO::FETCH_ASSOC) as $row){try{$row['content_plain']=journal_decrypt((string)$row['content_ciphertext']);}catch(Throwable $e){$row['content_plain']='This entry could not be decrypted on this server.';}$journalEntries[]=$row;}
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Faith Practices | DailyBreath</title><style>
*{box-sizing:border-box}body{margin:0;padding-bottom:90px;color:#243128;font-family:Inter,system-ui,sans-serif;background-image:linear-gradient(#00180c66,#00180caa),url('../assets/dailybreath-login-background.webp');background-position:center top;background-size:cover;background-attachment:fixed}.shell{max-width:900px;margin:auto;padding:45px 20px}.top{display:flex;justify-content:space-between;color:#fff}.top a{color:#f0cf7e}.hero{padding:42px 0 22px;color:#fff}.hero h1{font:500 clamp(42px,7vw,68px) Georgia,serif;margin:8px 0}.tabs{display:flex;gap:8px;overflow:auto;margin-bottom:18px}.tabs a{padding:11px 14px;border-radius:999px;color:#173f2c;background:#fffdf2e8;text-decoration:none;white-space:nowrap;font-weight:800}.tabs a.active{color:#fff;background:#2d694b}.card{margin:13px 0;padding:26px;border:1px solid #ffffff88;border-radius:22px;background:#fffdf7f3;box-shadow:0 22px 65px #00180c55}.card h2{color:#173f2c}.muted{color:#68736c}.notice,.error{padding:14px;border-radius:13px}.notice{background:#dff1e4;color:#214b31}.error{background:#f8dede;color:#742a2a}.breath-card{overflow:hidden;background:linear-gradient(145deg,#0d3b28e8,#072619ef);color:#fff;border-color:#bfe0c777}.breath-card h2{color:#fff;margin-bottom:4px}.breath-card .muted{color:#c6d8cd}.breath-stage{position:relative;display:grid;place-items:center;min-height:330px;margin:20px 0;border-radius:26px;background:radial-gradient(circle at center,#7cc69222 0 22%,transparent 55%),linear-gradient(180deg,#ffffff08,#ffffff02);border:1px solid #ffffff26;overflow:hidden}.breath-rings{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none}.breath-rings span{position:absolute;width:220px;height:220px;border-radius:50%;border:1px solid #d7f3df42;transform:scale(.78);opacity:.35;transition:transform 1s ease,opacity 1s ease}.breath-rings span:nth-child(2){width:270px;height:270px;opacity:.2}.breath-orb{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;justify-content:center;width:184px;height:184px;border-radius:50%;color:#fff;background:radial-gradient(circle at 35% 30%,#a5d9ae,#2d694b 62%,#153e2b);box-shadow:0 20px 80px #0008,0 0 0 14px #8db48d19;transform:scale(.82);transition:transform 1s ease,background 1s ease,box-shadow 1s ease}.breath-orb.active-inhale{transform:scale(1.18);background:radial-gradient(circle at 35% 30%,#d3f0d7,#4b9168 62%,#1f5238);box-shadow:0 24px 90px #0008,0 0 48px #8fe0a95c}.breath-orb.active-hold{transform:scale(1.18)}.breath-orb.active-exhale{transform:scale(.82)}.breath-label{font-size:28px;font-weight:900;letter-spacing:.02em}.breath-count{font-size:50px;line-height:1;font-family:Georgia,serif;margin-top:8px}.breath-sub{font-size:12px;letter-spacing:.16em;text-transform:uppercase;opacity:.78;margin-top:9px}.breath-meta{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:8px}.breath-meta span{padding:8px 12px;border-radius:999px;background:#ffffff12;border:1px solid #ffffff1f;color:#dce9e0;font-size:13px}.breath-progress{height:9px;margin:14px 0 6px;border-radius:99px;background:#ffffff1a;overflow:hidden}.breath-progress span{display:block;height:100%;width:0;background:linear-gradient(90deg,#78c78d,#f1d176);transition:width .25s linear}.breath-actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}.breath-btn{min-width:122px;justify-content:center;background:#f1d176;color:#173f2c}.breath-btn.secondary{background:#ffffff14;color:#fff;border:1px solid #ffffff32}.breath-btn:disabled{opacity:.45;cursor:not-allowed}.breath-settings{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;margin:16px 0}.breath-settings label{display:block;font-weight:800;margin-bottom:7px}.breath-settings select{width:100%;padding:12px 14px;border-radius:12px;border:1px solid #ffffff2b;background:#ffffff12;color:#fff;font:inherit}.breath-scripture{margin:18px 0;padding:17px 18px;border-left:3px solid #f1d176;border-radius:4px 14px 14px 4px;background:#ffffff0b;line-height:1.6}.completion-panel{display:none;text-align:center;padding:18px;border-radius:18px;background:#ffffff10;border:1px solid #ffffff26;margin-top:18px}.completion-panel.show{display:block}.completion-panel strong{display:block;font-size:22px;margin-bottom:5px}.prayer{padding:18px;border-left:4px solid #d0a34c;background:#f7f3e8;margin:12px 0}.prayer strong{color:#173f2c}.field{width:100%;padding:13px;border:1px solid #d5ddd7;border-radius:12px;font:inherit}.area{min-height:190px;resize:vertical}.btn{display:inline-flex;margin-top:14px;padding:12px 17px;border:0;border-radius:12px;color:#fff;background:#173f2c;font:inherit;font-weight:900;cursor:pointer}.progress{height:12px;border-radius:99px;background:#dfe6df;overflow:hidden}.progress span{display:block;height:100%;background:#d0a34c}.journal-head{display:flex;justify-content:space-between;gap:16px;align-items:start}.journal-count{padding:8px 12px;border-radius:999px;background:#e2eee5;color:#245037;font-weight:800;white-space:nowrap}.entry{margin-top:14px;padding:18px;border:1px solid #d9e4dc;border-radius:16px;background:#ffffffc9}.entry-top{display:flex;justify-content:space-between;gap:12px;align-items:center}.entry time{color:#68736c;font-size:13px}.mood{display:inline-block;padding:5px 9px;border-radius:999px;background:#edf4ee;color:#2d694b;font-size:12px;font-weight:800}.entry p{white-space:pre-wrap;line-height:1.6}.delete{border:0;background:transparent;color:#8a3535;font-weight:800;cursor:pointer}.submit-row{display:flex;justify-content:space-between;align-items:center;gap:12px}.privacy{font-size:12px;color:#68736c}@media(max-width:600px){.journal-head,.submit-row{display:block}.journal-count{display:inline-block;margin-top:8px}.breath-stage{min-height:300px}.breath-orb{width:164px;height:164px}.breath-settings{grid-template-columns:1fr}.breath-actions .btn{flex:1;min-width:0}}@media(prefers-reduced-motion:reduce){.breath-orb,.breath-rings span,.breath-progress span{transition:none}}
</style></head><body><main class="shell"><header class="top"><strong>DailyBreath · Practices</strong><a href="index.php">Home →</a></header><section class="hero"><span>FAITH-CENTERED WELLNESS</span><h1>Pause. Pray. Reflect.</h1></section><?php if($notice):?><div class="notice"><?= e($notice) ?></div><?php endif;?><?php if($error):?><div class="error"><?= e($error) ?></div><?php endif;?><nav class="tabs"><?php foreach(['breathing'=>'Breathing','prayers'=>'Prayers','journal'=>'Journal','challenge'=>'Weekly Challenge'] as $key=>$label):?><a class="<?= $section===$key?'active':'' ?>" href="?section=<?= $key ?>"><?= $label ?></a><?php endforeach;?></nav>
<?php if($section==='breathing'&&$exercise):?>
<section class="card breath-card" id="breath-practice" data-inhale="<?= (int)$exercise['inhale_seconds'] ?>" data-hold="<?= (int)$exercise['hold_seconds'] ?>" data-exhale="<?= (int)$exercise['exhale_seconds'] ?>" data-default-cycles="<?= (int)$exercise['cycles'] ?>">
  <h2><?= e($exercise['title']) ?></h2>
  <p class="muted">A guided Scripture-centered rhythm for calm, focus and prayer.</p>
  <div class="breath-meta"><span>Inhale <?= (int)$exercise['inhale_seconds'] ?>s</span><?php if((int)$exercise['hold_seconds']>0):?><span>Hold <?= (int)$exercise['hold_seconds'] ?>s</span><?php endif;?><span>Exhale <?= (int)$exercise['exhale_seconds'] ?>s</span></div>
  <div class="breath-stage" aria-live="polite">
    <div class="breath-rings"><span></span><span></span></div>
    <div class="breath-orb" id="breath-orb"><span class="breath-label" id="breath-label">Ready</span><span class="breath-count" id="breath-count">—</span><span class="breath-sub" id="breath-cycle">Cycle 0</span></div>
  </div>
  <div class="breath-progress" aria-label="Breathing session progress"><span id="breath-progress-bar"></span></div>
  <div class="breath-settings"><div><label for="cycle-select">Session length</label><select id="cycle-select"><option value="3">3 cycles · quick reset</option><option value="5">5 cycles · calm focus</option><option value="7">7 cycles · deeper practice</option><option value="10">10 cycles · extended prayer</option></select></div><button class="btn secondary" id="sound-toggle" type="button" aria-pressed="true">🔔 Gentle cues</button></div>
  <div class="breath-actions"><button class="btn breath-btn" id="breath-start" type="button">Start breathing</button><button class="btn breath-btn secondary" id="breath-pause" type="button" disabled>Pause</button><button class="btn breath-btn secondary" id="breath-reset" type="button">Reset</button></div>
  <blockquote class="breath-scripture"><strong><?= e($exercise['scripture_reference']) ?></strong><br><?= e($exercise['prompt_text']) ?></blockquote>
  <div class="completion-panel" id="breath-complete"><strong>Peace be with you.</strong><span>Your guided breathing practice is complete.</span><form method="post" id="breath-complete-form"><input type="hidden" name="csrf" value="<?= e($_SESSION['practice_csrf']) ?>"><input type="hidden" name="action" value="breath"><input type="hidden" name="exercise_id" value="<?= (int)$exercise['id'] ?>"><input type="hidden" name="cycles" id="completed-cycles" value="<?= (int)$exercise['cycles'] ?>"><input type="hidden" name="duration" id="completed-duration" value="<?= ((int)$exercise['inhale_seconds']+(int)$exercise['hold_seconds']+(int)$exercise['exhale_seconds'])*(int)$exercise['cycles'] ?>"><button class="btn" type="submit">Save completed session</button></form></div>
</section>
<script>
(()=>{
 const root=document.getElementById('breath-practice'); if(!root)return;
 const inhale=Number(root.dataset.inhale)||4, hold=Number(root.dataset.hold)||0, exhale=Number(root.dataset.exhale)||6;
 const orb=document.getElementById('breath-orb'), label=document.getElementById('breath-label'), count=document.getElementById('breath-count'), cycleText=document.getElementById('breath-cycle'), bar=document.getElementById('breath-progress-bar');
 const start=document.getElementById('breath-start'), pause=document.getElementById('breath-pause'), reset=document.getElementById('breath-reset'), select=document.getElementById('cycle-select'), sound=document.getElementById('sound-toggle'), complete=document.getElementById('breath-complete');
 const cycleInput=document.getElementById('completed-cycles'), durationInput=document.getElementById('completed-duration');
 const allowed=[3,5,7,10], preferred=Number(root.dataset.defaultCycles)||5; select.value=String(allowed.includes(preferred)?preferred:5);
 let timer=null,running=false,paused=false,phaseIndex=0,secondsLeft=0,completed=0,soundOn=true;
 const phases=[{name:'Inhale',seconds:inhale,cls:'active-inhale'},{name:'Hold',seconds:hold,cls:'active-hold'},{name:'Exhale',seconds:exhale,cls:'active-exhale'}].filter(p=>p.seconds>0);
 function totalSeconds(){return (inhale+hold+exhale)*Number(select.value)}
 function elapsed(){const full=inhale+hold+exhale; let phaseDone=0; for(let i=0;i<phaseIndex;i++)phaseDone+=phases[i].seconds; return completed*full+phaseDone+(phases[phaseIndex]?phases[phaseIndex].seconds-secondsLeft:0)}
 function cue(){if(!soundOn)return; try{const C=window.AudioContext||window.webkitAudioContext;if(!C)return;const ctx=new C(),o=ctx.createOscillator(),g=ctx.createGain();o.frequency.value=phaseIndex===0?440:phaseIndex===1?520:360;g.gain.setValueAtTime(.035,ctx.currentTime);g.gain.exponentialRampToValueAtTime(.001,ctx.currentTime+.18);o.connect(g);g.connect(ctx.destination);o.start();o.stop(ctx.currentTime+.2)}catch(e){}}
 function render(){const p=phases[phaseIndex];orb.className='breath-orb '+(p?p.cls:'');label.textContent=p?p.name:'Ready';count.textContent=p?secondsLeft:'—';cycleText.textContent=`Cycle ${Math.min(completed+1,Number(select.value))} of ${select.value}`;bar.style.width=Math.min(100,(elapsed()/Math.max(1,totalSeconds()))*100)+'%'}
 function setPhase(index){phaseIndex=index;secondsLeft=phases[index].seconds;cue();render()}
 function tick(){if(!running||paused)return;secondsLeft--;if(secondsLeft<=0){if(phaseIndex<phases.length-1){setPhase(phaseIndex+1)}else{completed++;if(completed>=Number(select.value)){finish();return}else setPhase(0)}}else render()}
 function begin(){if(running&&!paused)return;complete.classList.remove('show');select.disabled=true;running=true;paused=false;start.textContent='Breathing…';start.disabled=true;pause.disabled=false;pause.textContent='Pause';if(secondsLeft<=0)setPhase(0);timer=setInterval(tick,1000)}
 function togglePause(){if(!running)return;paused=!paused;pause.textContent=paused?'Resume':'Pause';start.textContent=paused?'Paused':'Breathing…';if(!paused)cue()}
 function resetSession(){clearInterval(timer);timer=null;running=paused=false;phaseIndex=completed=secondsLeft=0;select.disabled=false;start.disabled=false;start.textContent='Start breathing';pause.disabled=true;pause.textContent='Pause';complete.classList.remove('show');orb.className='breath-orb';label.textContent='Ready';count.textContent='—';cycleText.textContent='Cycle 0';bar.style.width='0%'}
 function finish(){clearInterval(timer);timer=null;running=false;paused=false;bar.style.width='100%';orb.className='breath-orb active-hold';label.textContent='Complete';count.textContent='✓';cycleText.textContent=`${select.value} cycles`;start.disabled=true;pause.disabled=true;complete.classList.add('show');cycleInput.value=select.value;durationInput.value=totalSeconds();cue();if(navigator.vibrate)navigator.vibrate([80,60,80])}
 start.addEventListener('click',begin);pause.addEventListener('click',togglePause);reset.addEventListener('click',resetSession);select.addEventListener('change',resetSession);sound.addEventListener('click',()=>{soundOn=!soundOn;sound.setAttribute('aria-pressed',String(soundOn));sound.textContent=soundOn?'🔔 Gentle cues':'🔕 Cues off'});document.addEventListener('visibilitychange',()=>{if(document.hidden&&running&&!paused)togglePause()});render();
})();
</script>
<?php endif;?>
<?php if($section==='prayers'):?><section class="card"><h2>Prayers for this moment</h2><?php foreach($prayers as $prayer):?><article class="prayer"><strong><?= e($prayer['title']) ?></strong><p><?= e($prayer['prayer_text']) ?></p><small><?= e($prayer['scripture_reference']) ?></small></article><?php endforeach;?></section><?php endif;?>
<?php if($section==='journal'):?><section class="card"><div class="journal-head"><div><h2>Private Reflection Journal</h2><p class="muted">Your entries are encrypted before they are stored.</p></div><span class="journal-count"><?= count($journalEntries) ?> recent</span></div><p><strong>Today’s prompt:</strong> <?= e($prompt['prompt_text']??'What is God inviting you to notice today?') ?></p><form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($_SESSION['practice_csrf']) ?>"><input type="hidden" name="action" value="journal"><input type="hidden" name="prompt_id" value="<?= (int)($prompt['id']??0) ?>"><label for="journal-mood">Mood</label><input id="journal-mood" class="field" name="mood" maxlength="40" value="<?= e((string)($_POST['mood']??'')) ?>" placeholder="Peaceful, hopeful, uncertain…"><label for="journal-entry">Reflection</label><textarea id="journal-entry" class="field area" name="entry" maxlength="10000" required placeholder="Write privately here…"><?= e((string)($_POST['entry']??'')) ?></textarea><div class="submit-row"><button class="btn" type="submit">Submit private reflection</button><span class="privacy">🔒 Visible only through your Beyond ID</span></div></form></section><section class="card"><h2>Recent reflections</h2><?php if(!$journalEntries):?><p class="muted">Your submitted reflections will appear here.</p><?php endif;?><?php foreach($journalEntries as $entry):?><article class="entry"><div class="entry-top"><div><time><?= e(date('M j, Y · g:i a',strtotime((string)$entry['created_at']))) ?></time><?php if(trim((string)$entry['mood'])!==''):?> <span class="mood"><?= e($entry['mood']) ?></span><?php endif;?></div><form method="post" onsubmit="return confirm('Delete this private journal entry?')"><input type="hidden" name="csrf" value="<?= e($_SESSION['practice_csrf']) ?>"><input type="hidden" name="action" value="delete_journal"><input type="hidden" name="entry_id" value="<?= (int)$entry['id'] ?>"><button class="delete" type="submit">Delete</button></form></div><?php if(!empty($entry['prompt_text'])):?><small class="muted"><?= e($entry['prompt_text']) ?></small><?php endif;?><p><?= e($entry['content_plain']) ?></p></article><?php endforeach;?></section><?php endif;?>
<?php if($section==='challenge'&&$challenge):?><section class="card"><h2><?= e($challenge['title']) ?></h2><p><?= e($challenge['description']) ?></p><p class="muted"><?= e($challenge['scripture_reference']) ?></p><?php if(!empty($challenge['steps'])):?><ol><?php foreach($challenge['steps'] as $step):?><li><?= e($step) ?></li><?php endforeach;?></ol><?php endif;?><div class="progress"><span style="width:<?= min(100,round($progress/max(1,(int)$challenge['target_count'])*100)) ?>%"></span></div><p><?= $progress ?> of <?= (int)$challenge['target_count'] ?> days complete</p><form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['practice_csrf']) ?>"><input type="hidden" name="action" value="challenge"><input type="hidden" name="challenge_id" value="<?= e((string)$challenge['id']) ?>"><input type="hidden" name="challenge_source" value="<?= ($challenge['source']??'')==='bundled_recovery_challenge'?'bundled':'database' ?>"><button class="btn">Mark today complete</button></form></section><?php endif;?></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
