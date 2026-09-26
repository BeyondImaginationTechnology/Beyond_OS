<?php
declare(strict_types=1);
require_once __DIR__ . '/../../beyond-id/includes/admin-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/web-app.php';
require_once __DIR__ . '/../includes/verse-of-day.php';
require_once __DIR__ . '/../includes/sacred-text.php';
$pdo = db();
dailybreath_ensure_content_table($pdo);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    beyond_require_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'generate_audio') {
        $date = (string)($_POST['publish_date'] ?? '');
        $tradition = (string)($_POST['tradition'] ?? '');
        $locale = (string)($_POST['locale'] ?? '');
        $content = dailybreath_published_content($pdo, $date, $tradition, $locale);
        $commercialLicense = (string)getenv('DAILYBREATH_ELEVENLABS_COMMERCIAL_LICENSE') === '1'
            || beyond_config('narration.elevenlabs.commercial_license', false) === true;
        if (!$content) $error = 'Approve the reading before generating narration.';
        elseif (!$commercialLicense) $error = 'Commercial narration is disabled until the paid ElevenLabs license is confirmed in server configuration.';
        else {
            try {
                require_once __DIR__ . '/../../includes/narration/StudioNarration.php';
                $script = dailybreath_narration_script($content);
                $voiceLocale = preg_match('/[\x{0590}-\x{05FF}]/u', $script) ? 'he-IL'
                    : (preg_match('/[\x{0600}-\x{06FF}]/u', $script) ? 'ar-SA'
                    : ['fr'=>'fr-FR','es'=>'es-ES'][$locale] ?? 'en-US');
                $voice = studio_narration_voice('elevenlabs', $voiceLocale);
                if ($voice === '') throw new RuntimeException('Select an ElevenLabs voice for ' . $voiceLocale . ' in Premium Voices.');
                $generated = studio_narration_generate($script, $voiceLocale, 'elevenlabs', $voice);
                $stored = studio_store_mp3((string)$generated['audio_content'], 'daily-breath', $date, $voiceLocale, $script);
                dailybreath_ensure_audio_table($pdo);
                $values = [$date,$tradition,$locale,hash('sha256',$script),(string)$stored['url'],$voice,'elevenlabs'];
                if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                    $query = $pdo->prepare('INSERT OR REPLACE INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?)');
                } else {
                    $query = $pdo->prepare('INSERT INTO dailybreath_daily_audio (publish_date,tradition,locale,script_hash,audio_url,voice_id,provider) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE script_hash=VALUES(script_hash),audio_url=VALUES(audio_url),voice_id=VALUES(voice_id),provider=VALUES(provider),generated_at=CURRENT_TIMESTAMP');
                }
                $query->execute($values);
                header('Location: daily-content.php?date='.rawurlencode($date).'&tradition='.rawurlencode($tradition).'&locale='.rawurlencode($locale).'&saved=audio'); exit;
            } catch (Throwable $exception) {
                error_log('Daily Breath ElevenLabs narration: ' . $exception->getMessage());
                $error = 'Narration could not be generated. Check the paid license, voice, and server audio storage.';
            }
        }
    } elseif (in_array($action, ['save_draft','publish'], true)) {
        $date = trim((string)($_POST['publish_date'] ?? ''));
        $tradition = (string)($_POST['tradition'] ?? 'bible');
        $locale = (string)($_POST['locale'] ?? 'en');
        $passage = trim((string)($_POST['passage'] ?? ''));
        $reference = trim((string)($_POST['reference'] ?? ''));
        $reflection = trim((string)($_POST['reflection'] ?? ''));
        $book = trim((string)($_POST['reader_book'] ?? ''));
        $chapter = max(1, (int)($_POST['reader_chapter'] ?? 1));
        $verse = max(1, (int)($_POST['reader_verse'] ?? 1));
        $theme = (string)($_POST['theme'] ?? 'seasonal');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))) $error = 'Choose a valid publication date.';
        elseif (!in_array($tradition, ['bible','torah','quran'], true) || !in_array($locale, ['en','fr','es'], true) || !in_array($theme, ['seasonal','scripture'], true)) $error = 'Choose a supported tradition and language.';
        elseif ($passage === '' || $reference === '') $error = 'Passage text and reference are required.';
        else {
            $status = $action === 'publish' ? 'published' : 'draft';
            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
            $existing = $pdo->prepare('SELECT id FROM dailybreath_daily_content WHERE publish_date=? AND tradition=? AND locale=? LIMIT 1');
            $existing->execute([$date,$tradition,$locale]);
            $id = $existing->fetchColumn();
            $values = [$passage,$reference,$reflection,$book,$chapter,$verse,$theme,$status,$publishedAt];
            if ($id) {
                $statement = $pdo->prepare('UPDATE dailybreath_daily_content SET passage=?,reference=?,reflection=?,reader_book=?,reader_chapter=?,reader_verse=?,theme=?,status=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $statement->execute([...$values,(int)$id]);
            } else {
                $statement = $pdo->prepare('INSERT INTO dailybreath_daily_content (publish_date,tradition,locale,passage,reference,reflection,reader_book,reader_chapter,reader_verse,theme,status,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $statement->execute([$date,$tradition,$locale,$passage,$reference,$reflection,$book,$chapter,$verse,$theme,$status,$publishedAt]);
            }
            header('Location: daily-content.php?date='.rawurlencode($date).'&tradition='.rawurlencode($tradition).'&locale='.rawurlencode($locale).'&saved='.$status); exit;
        }
    }
}
function dc_e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$date = (string)($_GET['date'] ?? date('Y-m-d'));
$tradition = in_array((string)($_GET['tradition'] ?? 'bible'), ['bible','torah','quran'], true) ? (string)($_GET['tradition'] ?? 'bible') : 'bible';
$locale = in_array((string)($_GET['locale'] ?? 'en'), ['en','fr','es'], true) ? (string)($_GET['locale'] ?? 'en') : 'en';
$suggestion = [];
try { $suggestion = dailybreath_interfaith_verse_of_day($pdo, $tradition, $locale, $date); } catch (Throwable $exception) {}
$query = $pdo->prepare('SELECT * FROM dailybreath_daily_content WHERE publish_date=? AND tradition=? AND locale=? LIMIT 1');
$query->execute([$date,$tradition,$locale]);
$content = $query->fetch(PDO::FETCH_ASSOC) ?: [];
$audio = null;
try { $audio = $content ? dailybreath_published_audio($pdo, $content) : null; } catch (Throwable $exception) {}
$recent = $pdo->query('SELECT publish_date,tradition,locale,reference,status,updated_at FROM dailybreath_daily_content ORDER BY publish_date DESC,updated_at DESC LIMIT 24')->fetchAll(PDO::FETCH_ASSOC);
$shareUrl = '/dailybreath/daily.php?date='.rawurlencode($date).'&tradition='.rawurlencode($tradition).'&lang='.rawurlencode($locale);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daily content · Daily Breath</title><style>
*{box-sizing:border-box}body{margin:0;background:#eef3ec;color:#1b2b21;font:15px/1.5 system-ui,sans-serif}.shell{width:min(1440px,calc(100% - 28px));margin:auto;padding:24px 0 60px}.top,.actions,.recent a{display:flex;align-items:center;justify-content:space-between;gap:12px}.top{margin-bottom:18px}.top h1{margin:0;font:500 clamp(30px,5vw,46px) Georgia,serif}.top a,.recent a{color:#1d6540;font-weight:750}.columns{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(0,1.1fr);gap:18px}.panel{padding:20px;border:1px solid #d6e0d7;border-radius:18px;background:#fff;box-shadow:0 12px 35px #1e41220c}.panel h2{margin:0 0 14px;font:500 24px Georgia,serif}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:5px}.field.full{grid-column:1/-1}.field label{font-size:12px;font-weight:800;color:#526356}.field input,.field select,.field textarea{width:100%;padding:10px 11px;border:1px solid #cdd9cf;border-radius:10px;font:inherit;color:#1b2b21;background:#fcfdfb}.field textarea{min-height:96px;resize:vertical}.field textarea[name=passage]{min-height:120px}.notice{padding:11px 13px;border-radius:11px;background:#f3ead1;color:#4d3c18}.status{margin:0 0 15px;padding:10px;border-radius:10px;background:#e9f6ec}#load-suggestion{margin:0 0 12px;padding:9px 12px;border:1px solid #d6e0d7;border-radius:9px;color:#1d6540;background:#f7faf6;font:700 13px system-ui;cursor:pointer}.actions{justify-content:flex-start;margin-top:15px;flex-wrap:wrap}.actions button,.preview button{min-height:44px;padding:10px 15px;border:0;border-radius:10px;background:#1d6540;color:#fff;font:700 14px system-ui;cursor:pointer}.actions .publish{background:#a76b16}.preview-stack{display:grid;gap:16px}.preview-card{position:relative;display:flex;min-height:380px;flex-direction:column;align-items:center;justify-content:center;gap:22px;overflow:hidden;padding:85px 10%;border:1px solid #cfa759;border-radius:16px;color:white;text-align:center;background:#052115 center/cover no-repeat;box-shadow:0 15px 45px #00150b40}.preview-card:before{position:absolute;inset:0;content:"";background:linear-gradient(#00130d36,#00130d55)}.preview-card>*{position:relative;text-shadow:0 2px 12px #00150e}.preview-card .eyebrow{color:#f4d898;font:600 15px Georgia,serif}.preview-card blockquote{margin:0;font:500 clamp(25px,3vw,42px)/1.2 Georgia,serif}.preview-card blockquote[dir=rtl]{font-family:"Noto Naskh Arabic","Geeza Pro",Georgia,serif;unicode-bidi:plaintext}.preview-card .reference{color:#f6d891;font:600 23px Georgia,serif}.previews{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.mini{padding:14px;border:1px solid #d6e0d7;border-radius:12px;background:#fbfcfa}.mini h3{margin:0 0 9px;font-size:15px}.mini textarea{width:100%;min-height:92px;padding:9px;border:1px solid #d6e0d7;border-radius:9px;font:12px/1.45 ui-monospace,monospace}.mobile-frame{width:min(230px,100%);min-height:400px;margin:auto;padding:18px 12px;border:7px solid #1d2a21;border-radius:28px;background:#f7f9f6}.mobile-frame .preview-card{min-height:350px;padding:75px 9%;border-radius:17px}.recent{margin-top:18px}.recent ul{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:8px;padding:0;list-style:none}.recent li a{padding:10px;border:1px solid #d6e0d7;border-radius:10px;background:#fff;text-decoration:none}.muted{color:#607166;font-size:13px}@media(max-width:900px){.columns{grid-template-columns:1fr}}@media(max-width:520px){.form-grid,.previews{grid-template-columns:1fr}.top{align-items:flex-start;flex-direction:column}.preview-card{min-height:440px;padding:100px 9%}}
</style></head><body><main class="shell"><header class="top"><h1>Daily content</h1><nav><a href="/beyond-id/admin/overview.php">Admin home</a> · <a href="../index.php">Daily Breath</a></nav></header><?php if(isset($_GET['saved'])):?><p class="status" role="status"><?= $_GET['saved']==='published'?'Approved and published.':($_GET['saved']==='audio'?'ElevenLabs narration is ready to stream online.':'Saved as draft. Nothing is public until approval.') ?><?php if(($_GET['saved']??'')==='published'):?> <a href="<?=dc_e($shareUrl)?>">Open share page</a><?php endif?></p><?php endif?><?php if($error!==''):?><p class="status" role="alert"><?=dc_e($error)?></p><?php endif?><div class="columns"><section class="panel"><h2>Review and approval</h2><p class="notice">AI-generated copy stays in draft until you approve it. This editor does not send content or credentials to an AI service.</p><p class="muted">The passage fields start from the local Verse of the Day library for this date and tradition. Review or replace the suggestion before saving.</p><form method="post" id="content-form"><input type="hidden" name="_csrf" value="<?=dc_e(beyond_csrf_token())?>"><button type="button" id="load-suggestion">Load local reading for these choices</button><div class="form-grid"><div class="field"><label for="publish_date">Publish date</label><input id="publish_date" type="date" name="publish_date" value="<?=dc_e($_POST['publish_date']??$content['publish_date']??$date)?>" required></div><div class="field"><label for="tradition">Tradition</label><select id="tradition" name="tradition"><?php foreach(['bible'=>'Bible','torah'=>'Tanakh','quran'=>'Quran'] as $key=>$label):?><option value="<?=dc_e($key)?>" <?=($_POST['tradition']??$tradition)===$key?'selected':''?>><?=dc_e($label)?></option><?php endforeach?></select></div><div class="field"><label for="locale">Language</label><select id="locale" name="locale"><?php foreach(['en'=>'English','fr'=>'Français','es'=>'Español'] as $key=>$label):?><option value="<?=dc_e($key)?>" <?=($_POST['locale']??$locale)===$key?'selected':''?>><?=dc_e($label)?></option><?php endforeach?></select></div><div class="field"><label for="theme">Share card theme</label><select id="theme" name="theme"><option value="seasonal" <?=($_POST['theme']??$content['theme']??'seasonal')==='seasonal'?'selected':''?>>Seasonal (default)</option><option value="scripture" <?=($_POST['theme']??$content['theme']??'seasonal')==='scripture'?'selected':''?>>Tradition artwork</option></select></div><div class="field"><label for="reference">Passage reference</label><input id="reference" name="reference" maxlength="255" value="<?=dc_e($_POST['reference']??$content['reference']??$suggestion['reference']??'')?>" required placeholder="Psalm 121:7"></div><div class="field full"><label for="passage">Passage text</label><textarea id="passage" name="passage" required><?=dc_e($_POST['passage']??$content['passage']??$suggestion['text']??'')?></textarea></div><div class="field full"><label for="reflection">Reflection</label><textarea id="reflection" name="reflection" placeholder="A short, reviewed reflection for today."><?=dc_e($_POST['reflection']??$content['reflection']??'')?></textarea></div><div class="field"><label for="reader_book">Reader book or surah</label><input id="reader_book" name="reader_book" value="<?=dc_e($_POST['reader_book']??$content['reader_book']??$suggestion['reader_book']??'')?>" placeholder="Psalms or 113"></div><div class="field"><label for="reader_chapter">Chapter</label><input id="reader_chapter" type="number" min="1" name="reader_chapter" value="<?=dc_e($_POST['reader_chapter']??$content['reader_chapter']??$suggestion['reader_chapter']??'1')?>"></div><div class="field"><label for="reader_verse">Verse / ayah</label><input id="reader_verse" type="number" min="1" name="reader_verse" value="<?=dc_e($_POST['reader_verse']??$content['reader_verse']??$suggestion['verse']??'1')?>"></div></div><div class="actions"><button name="action" value="save_draft">Save as draft</button><button class="publish" name="action" value="publish">Approve &amp; publish</button><button type="button" id="listen">Preview audio narration</button></div></form><?php if(($content['status']??'')==='published'):?><form method="post" class="actions"><input type="hidden" name="_csrf" value="<?=dc_e(beyond_csrf_token())?>"><input type="hidden" name="publish_date" value="<?=dc_e($date)?>"><input type="hidden" name="tradition" value="<?=dc_e($tradition)?>"><input type="hidden" name="locale" value="<?=dc_e($locale)?>"><button name="action" value="generate_audio">Generate ElevenLabs narration</button></form><?php if($audio):?><p class="muted">Online narration · ElevenLabs · <?=dc_e($audio['generated_at'])?></p><audio controls preload="none" src="<?=dc_e($audio['audio_url'])?>"></audio><?php endif?><?php endif?></section><section class="panel"><h2>Format previews</h2><p class="muted">Preview updates as you edit. Audio uses the browser’s speech service; it is not saved as an MP3. Podcast details and captions are prepared for review and are not posted automatically.</p><div class="preview-stack"><article class="preview-card" id="desktop-card"><span class="eyebrow" id="preview-label">Bible Verse of the Day · September 25, 2026</span><blockquote id="preview-passage">Your passage preview</blockquote><span class="reference" id="preview-reference">Reference</span><p id="preview-reflection"></p></article><div class="mobile-frame"><article class="preview-card" id="mobile-card"><span class="eyebrow" id="mobile-label"></span><blockquote id="mobile-passage"></blockquote><span class="reference" id="mobile-reference"></span><p id="mobile-reflection"></p></article></div><div class="previews"><section class="mini"><h3>Podcast episode</h3><textarea id="podcast-preview" readonly aria-label="Podcast episode details"></textarea></section><section class="mini"><h3>Social captions</h3><textarea id="social-preview" readonly aria-label="Social caption previews"></textarea></section><section class="mini"><h3>Audio narration script</h3><textarea id="audio-preview" readonly aria-label="Audio narration script"></textarea></section><section class="mini"><h3>Share page</h3><textarea id="share-preview" readonly aria-label="Share page link preview"></textarea></section></div></div></section></div><section class="panel recent"><h2>Recent drafts and published readings</h2><ul><?php foreach($recent as $item):?><li><a href="?date=<?=rawurlencode((string)$item['publish_date'])?>&tradition=<?=rawurlencode((string)$item['tradition'])?>&locale=<?=rawurlencode((string)$item['locale'])?>"><span><strong><?=dc_e($item['publish_date'])?> · <?=dc_e(ucfirst((string)$item['tradition']))?></strong><br><small><?=dc_e($item['reference'])?> · <?=dc_e($item['locale'])?></small></span><span><?=dc_e(ucfirst((string)$item['status']))?></span></a></li><?php endforeach?></ul></section></main><script>
(()=>{
  const form=document.getElementById('content-form');
  const get=name=>form.elements[name]?.value||'';
  const traditionName=()=>({bible:'Bible Verse',torah:'Tanakh Passage',quran:'Quran Ayah'}[get('tradition')]||'Scripture');
  const cards=[['preview-label','preview-passage','preview-reference','preview-reflection'],['mobile-label','mobile-passage','mobile-reference','mobile-reflection']];
  const images={bible:'bible',torah:'tanakh',quran:'quran'};document.getElementById('load-suggestion').addEventListener('click',()=>{const url=new URL(location.href);url.searchParams.set('date',get('publish_date'));url.searchParams.set('tradition',get('tradition'));url.searchParams.set('locale',get('locale'));location.href=url.toString()});
  function update(){
    const date=get('publish_date')||new Date().toISOString().slice(0,10),ref=get('reference')||'Reference',passage=get('passage')||'Your passage preview',reflection=get('reflection'),tradition=get('tradition')||'bible',locale=get('locale')||'en',theme=get('theme')||'seasonal';
    const title=traditionName()+' — '+date,url='https://beyondimagination.co.technology/dailybreath/daily.php?date='+encodeURIComponent(date)+'&tradition='+tradition+'&lang='+locale;
    const script=passage+'\n\n'+ref+(reflection?'\n\n'+reflection:'');
    const rtl=/[\u0600-\u06ff]/u.test(passage);cards.forEach(ids=>{document.getElementById(ids[0]).textContent=title;document.getElementById(ids[1]).textContent=passage;document.getElementById(ids[1]).dir=rtl?'rtl':'ltr';document.getElementById(ids[1]).lang=rtl?'ar':locale;document.getElementById(ids[2]).textContent=ref;document.getElementById(ids[3]).textContent=reflection});
    ['desktop-card','mobile-card'].forEach(id=>document.getElementById(id).style.backgroundImage=theme==='scripture'?'linear-gradient(#00130d33,#00130d66),url(../assets/images/'+images[tradition]+'-forest-landscape.png)':'linear-gradient(145deg,#17472d,#061b12)');
    document.getElementById('audio-preview').value=script;
    document.getElementById('podcast-preview').value='Title: Daily Breath · '+ref+'\nDescription: '+passage+(reflection?' '+reflection:'')+'\nLanguage: '+locale+'\nEnclosure: add approved audio file before publishing to the podcast feed';
    document.getElementById('social-preview').value='X / Threads:\n'+passage+'\n'+ref+'\n'+url+'\n\nInstagram:\n'+title+'\n“'+passage+'”\n'+ref+(reflection?'\n\n'+reflection:'')+'\nRead and reflect with Daily Breath.\n\nFacebook:\n'+passage+'\n\n'+ref+(reflection?'\n\n'+reflection:'')+'\n\n'+url;
    document.getElementById('share-preview').value=url;
  }
  form.addEventListener('input',update);form.addEventListener('change',update);update();
  document.getElementById('listen').addEventListener('click',()=>{
    if(!('speechSynthesis'in window)){alert('Speech preview is not available in this browser.');return}
    const text=document.getElementById('audio-preview').value;speechSynthesis.cancel();const utterance=new SpeechSynthesisUtterance(text);utterance.lang=/[\u0600-\u06ff]/u.test(text)?'ar':({en:'en-US',fr:'fr-FR',es:'es-ES'}[get('locale')]||'en-US');speechSynthesis.speak(utterance);
  });
})();
</script></body></html>
