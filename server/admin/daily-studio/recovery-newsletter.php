<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$targetDate = (string)($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate) || !strtotime($targetDate)) $targetDate = date('Y-m-d');
$dataRoot = dirname(__DIR__, 3) . '/dailybreath/data';

function newsletter_json(string $path): array {
    if (!is_file($path)) return [];
    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded['entries'] ?? null) ? $decoded['entries'] : [];
}
function newsletter_dated(array $entries, string $date): ?array {
    foreach ($entries as $entry) if (($entry['schedule_date'] ?? null) === $date && ($entry['schedule_role'] ?? 'primary') === 'primary') return $entry;
    foreach ($entries as $entry) if (($entry['schedule_date'] ?? null) === $date) return $entry;
    return $entries ? $entries[(int)(abs(crc32($date)) % count($entries))] : null;
}
function newsletter_challenge(array $entries, string $date): ?array {
    $active = array_values(array_filter($entries, static fn(array $entry): bool =>
        (string)($entry['starts_on'] ?? '') <= $date && (string)($entry['ends_on'] ?? '') >= $date
    ));
    usort($active, static fn(array $left, array $right): int => strcmp((string)$right['starts_on'], (string)$left['starts_on']));
    return $active[0] ?? ($entries[0] ?? null);
}

$verse = newsletter_dated(newsletter_json($dataRoot . '/daily-verses.json'), $targetDate) ?: [];
$devotional = newsletter_dated(newsletter_json($dataRoot . '/daily-devotionals.json'), $targetDate) ?: [];
$challenge = newsletter_challenge(newsletter_json($dataRoot . '/recovery-challenges.json'), $targetDate) ?: [];
$steps = array_values(array_slice(is_array($challenge['steps'] ?? null) ? $challenge['steps'] : [], 0, 3));
$payload = [
    'issueDate' => date('F j, Y', strtotime($targetDate)),
    'headline' => 'Recovery Weekly',
    'verse' => (string)($verse['text'] ?? 'Be still, and know that I am God.'),
    'reference' => (string)($verse['reference'] ?? 'Psalm 46:10'),
    'devotionalTitle' => (string)($devotional['title'] ?? 'Make Room for Peace'),
    'devotional' => (string)($devotional['excerpt'] ?? 'Take the next faithful step with patience and support.'),
    'challengeTitle' => (string)($challenge['title'] ?? 'Build Your Support Circle'),
    'challenge' => (string)($challenge['description'] ?? 'Make one intentional recovery connection each day.'),
    'steps' => $steps,
];
require dirname(__DIR__) . '/_header.php';
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css"><link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css">
<style>
.newsletter-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px}.newsletter-head h1{margin:4px 0}.newsletter-layout{display:grid;grid-template-columns:minmax(320px,.72fr) minmax(430px,1.28fr);gap:20px}.newsletter-panel{padding:22px;border:1px solid var(--border,#2c3140);border-radius:22px;background:var(--card,#171a23)}.newsletter-fields{display:grid;gap:13px}.newsletter-fields label{display:grid;gap:6px;color:var(--muted,#9ca3af);font-size:12px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.newsletter-fields textarea{min-height:76px;resize:vertical}.newsletter-fields .body-field{min-height:112px}.newsletter-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:17px}.newsletter-actions button{cursor:pointer}.newsletter-preview{display:block;min-height:690px;background:linear-gradient(145deg,#0a1711,#19221d)}.preview-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;width:min(100%,570px);margin:0 auto 18px;color:#e8f2eb}.preview-toolbar strong,.preview-toolbar small{display:block}.preview-toolbar small{margin-top:3px;color:#b9cec0;font-size:12px}.preview-sizes{display:flex;gap:7px}.preview-size{padding:8px 11px;border:1px solid #567061;border-radius:10px;background:#14271d;color:#dce9e0;font:inherit;font-size:12px;font-weight:700;line-height:1.2;cursor:pointer}.preview-size[aria-pressed=true]{border-color:#efc76e;background:#efc76e;color:#17251c}#newsletter-canvas{display:block;width:min(100%,570px);height:auto;margin:0 auto;border-radius:14px;box-shadow:0 25px 70px #0008}.export-note{margin:12px 0 0;color:var(--muted,#9ca3af);font-size:12px;line-height:1.5}@media(max-width:900px){.newsletter-layout{grid-template-columns:1fr}.newsletter-preview{min-height:540px}}@media(max-width:480px){.newsletter-panel{padding:16px}.preview-toolbar{align-items:flex-start;flex-direction:column}.preview-sizes{width:100%}.preview-size{flex:1}}
</style>
<div class="newsletter-head"><div><p class="studio-eyebrow">Daily Breath campaign</p><h1>Weekly Recovery Newsletter</h1><p class="muted">Edit one issue, then export a printable PDF or an Instagram-ready square PNG.</p></div><form method="get"><label class="muted" for="newsletter-date">Issue date</label><input class="input compact" id="newsletter-date" type="date" name="date" value="<?=DailyStudio::esc($targetDate)?>" onchange="this.form.submit()"></form></div>
<section class="newsletter-layout"><article class="newsletter-panel"><div class="newsletter-fields">
<label>Headline<input class="input" id="field-headline" value="<?=DailyStudio::esc($payload['headline'])?>"></label>
<label>Verse<textarea class="input" id="field-verse"><?=DailyStudio::esc($payload['verse'])?></textarea></label>
<label>Reference<input class="input" id="field-reference" value="<?=DailyStudio::esc($payload['reference'])?>"></label>
<label>Devotional title<input class="input" id="field-devotional-title" value="<?=DailyStudio::esc($payload['devotionalTitle'])?>"></label>
<label>Devotional summary<textarea class="input body-field" id="field-devotional"><?=DailyStudio::esc($payload['devotional'])?></textarea></label>
<label>Challenge title<input class="input" id="field-challenge-title" value="<?=DailyStudio::esc($payload['challengeTitle'])?>"></label>
<label>Challenge summary<textarea class="input" id="field-challenge"><?=DailyStudio::esc($payload['challenge'])?></textarea></label>
</div><div class="newsletter-actions"><button class="btn" id="export-pdf" type="button">Export PDF</button><button class="btn secondary" id="export-png" type="button">Export Instagram PNG</button></div><p class="export-note">PDF exports at US Letter proportions. Instagram PNG exports at exactly 1080 × 1080 pixels.</p></article>
<article class="newsletter-panel newsletter-preview"><div class="preview-toolbar"><div><strong>Live preview</strong><small id="preview-hint">Print · US Letter</small></div><div class="preview-sizes" role="group" aria-label="Preview format"><button class="preview-size" id="preview-letter" type="button" aria-pressed="true">Print</button><button class="preview-size" id="preview-square" type="button" aria-pressed="false">Instagram</button></div></div><canvas id="newsletter-canvas" width="1275" height="1650" aria-label="Newsletter preview"></canvas></article></section>
<script>
(()=>{
const seed=<?=json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
const ids=['headline','verse','reference','devotional-title','devotional','challenge-title','challenge'];
const field=Object.fromEntries(ids.map(id=>[id,document.getElementById('field-'+id)]));
const preview=document.getElementById('newsletter-canvas');
const value=()=>({...seed,headline:field.headline.value,verse:field.verse.value,reference:field.reference.value,devotionalTitle:field['devotional-title'].value,devotional:field.devotional.value,challengeTitle:field['challenge-title'].value,challenge:field.challenge.value});
function rounded(ctx,x,y,w,h,r){ctx.beginPath();ctx.roundRect(x,y,w,h,r);ctx.fill()}
function wrap(ctx,text,x,y,maxWidth,lineHeight,maxLines=99){const words=String(text).trim().split(/\s+/),lines=[];let line='';for(const word of words){const test=line?line+' '+word:word;if(ctx.measureText(test).width>maxWidth&&line){lines.push(line);line=word}else line=test}if(line)lines.push(line);const visible=lines.slice(0,maxLines);if(lines.length>maxLines){let last=visible[maxLines-1].replace(/[.,;:!?]$/,'');while(last&&ctx.measureText(last+'…').width>maxWidth)last=last.slice(0,-1);visible[maxLines-1]=last+'…'}visible.forEach((item,index)=>ctx.fillText(item,x,y+index*lineHeight));return y+Math.max(0,visible.length-1)*lineHeight}
function draw(canvas,mode){
  const square=mode==='square',w=square?1080:1275,h=square?1080:1650,m=square?64:92,content=w-m*2;
  canvas.width=w;canvas.height=h;
  const ctx=canvas.getContext('2d'),d=value();
  ctx.fillStyle='#071d14';ctx.fillRect(0,0,w,h);
  const glow=ctx.createRadialGradient(w*.83,h*.08,8,w*.83,h*.08,w*.72);glow.addColorStop(0,'rgba(208,163,76,.25)');glow.addColorStop(.55,'rgba(70,112,67,.12)');glow.addColorStop(1,'rgba(7,29,20,0)');ctx.fillStyle=glow;ctx.fillRect(0,0,w,h);
  ctx.strokeStyle='rgba(230,241,233,.16)';ctx.lineWidth=2;ctx.beginPath();ctx.roundRect(22,22,w-44,h-44,square?28:34);ctx.stroke();
  const brandY=square?99:139;ctx.fillStyle='#eec96f';ctx.beginPath();ctx.arc(m+9,brandY-8,square?8:10,0,Math.PI*2);ctx.fill();ctx.fillStyle='#f4f7f2';ctx.font=`800 ${square?21:26}px Arial`;ctx.letterSpacing=square?'2px':'3px';ctx.fillText('DAILY BREATH',m+30,brandY);ctx.letterSpacing='0px';
  ctx.textAlign='right';ctx.fillStyle='#c4d8ca';ctx.font=`700 ${square?15:19}px Arial`;ctx.fillText(String(d.issueDate).toUpperCase(),w-m,brandY);ctx.textAlign='left';
  ctx.fillStyle='#fffaf0';ctx.font=`700 ${square?61:82}px Georgia`;ctx.letterSpacing=square?'-1px':'-1.5px';wrap(ctx,d.headline,m,square?184:246,content,square?68:91,2);ctx.letterSpacing='0px';
  const cardY=square?280:370,cardH=square?252:402,cardX=m,cardW=content;
  ctx.fillStyle='rgba(255,255,255,.075)';rounded(ctx,cardX,cardY,cardW,cardH,square?26:32);
  ctx.fillStyle='#edc66b';rounded(ctx,cardX+23,cardY+25,5,cardH-50,3);
  ctx.fillStyle='#f0cb77';ctx.font=`800 ${square?16:21}px Arial`;ctx.letterSpacing='2px';ctx.fillText('VERSE FOR THE WEEK',cardX+48,cardY+(square?46:54));ctx.letterSpacing='0px';
  ctx.fillStyle='#fff';ctx.font=`500 ${square?31:44}px Georgia`;wrap(ctx,'“'+d.verse+'”',cardX+48,cardY+(square?96:122),cardW-96,square?39:55,square?3:4);
  ctx.fillStyle='#f0cb77';ctx.font=`800 ${square?18:23}px Arial`;ctx.fillText(d.reference,cardX+48,cardY+cardH-(square?25:34));
  const reflectY=square?586:865;ctx.fillStyle='#edc66b';ctx.fillRect(m,reflectY-15,square?34:42,4);ctx.fillStyle='#f0cb77';ctx.font=`800 ${square?15:20}px Arial`;ctx.letterSpacing='2px';ctx.fillText('RECOVERY REFLECTION',m+(square?48:58),reflectY);ctx.letterSpacing='0px';
  ctx.fillStyle='#fffaf0';ctx.font=`700 ${square?31:40}px Arial`;const reflectionTitleLast=wrap(ctx,d.devotionalTitle,m,reflectY+(square?52:62),content,square?39:50,2);
  ctx.fillStyle='#d6e4da';ctx.font=`400 ${square?21:28}px Arial`;wrap(ctx,d.devotional,m,reflectionTitleLast+(square?40:52),content,square?29:39,square?2:3);
  const challengeY=square?772:1250,challengeH=square?205:282;ctx.fillStyle='#24583f';rounded(ctx,m,challengeY,content,challengeH,square?24:30);
  ctx.fillStyle='#f4d98e';ctx.font=`800 ${square?15:20}px Arial`;ctx.letterSpacing='1.8px';ctx.fillText('THIS WEEK’S CHALLENGE',m+30,challengeY+(square?40:48));ctx.letterSpacing='0px';
  ctx.fillStyle='#fff';ctx.font=`700 ${square?27:36}px Arial`;const challengeTitleLast=wrap(ctx,d.challengeTitle,m+30,challengeY+(square?80:98),content-60,square?34:44,2);
  ctx.fillStyle='#e4efe7';ctx.font=`400 ${square?19:25}px Arial`;wrap(ctx,d.challenge,m+30,challengeTitleLast+(square?31:43),content-60,square?25:33,square?2:3);
  ctx.fillStyle='#b7d1bf';ctx.font=`700 ${square?13:17}px Arial`;ctx.letterSpacing=square?'1.4px':'2px';ctx.textAlign='center';ctx.fillText('ONE SMALL STEP · ONE STEADY BREATH · YOU ARE NOT ALONE',w/2,h-(square?34:52));ctx.textAlign='left';ctx.letterSpacing='0px';return canvas
}
function download(blob,name){const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)}
function bytes(text){return new TextEncoder().encode(text)}
function concat(parts){const size=parts.reduce((sum,p)=>sum+p.length,0),out=new Uint8Array(size);let at=0;for(const part of parts){out.set(part,at);at+=part.length}return out}
async function pdfFromCanvas(canvas){const jpegBlob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.94)),jpeg=new Uint8Array(await jpegBlob.arrayBuffer());const objects=[];objects[1]=bytes('<< /Type /Catalog /Pages 2 0 R >>');objects[2]=bytes('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');objects[3]=bytes('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /XObject << /Im0 5 0 R >> >> /Contents 4 0 R >>');const content=bytes('q 612 0 0 792 0 0 cm /Im0 Do Q');objects[4]=concat([bytes(`<< /Length ${content.length} >>\nstream\n`),content,bytes('\nendstream')]);objects[5]=concat([bytes(`<< /Type /XObject /Subtype /Image /Width ${canvas.width} /Height ${canvas.height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpeg.length} >>\nstream\n`),jpeg,bytes('\nendstream')]);const parts=[bytes('%PDF-1.4\n%DBREATH\n')],offsets=[0];let length=parts[0].length;for(let i=1;i<=5;i++){offsets[i]=length;const part=concat([bytes(`${i} 0 obj\n`),objects[i],bytes('\nendobj\n')]);parts.push(part);length+=part.length}const xref=length;let table='xref\n0 6\n0000000000 65535 f \n';for(let i=1;i<=5;i++)table+=String(offsets[i]).padStart(10,'0')+' 00000 n \n';parts.push(bytes(table+`trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`));return new Blob([concat(parts)],{type:'application/pdf'})}
let previewMode='letter';
function refresh(){draw(preview,previewMode)}
ids.forEach(id=>field[id].addEventListener('input',refresh));
const previewHint=document.getElementById('preview-hint'),letterButton=document.getElementById('preview-letter'),squareButton=document.getElementById('preview-square');
function setPreview(mode){previewMode=mode;letterButton.setAttribute('aria-pressed',String(mode==='letter'));squareButton.setAttribute('aria-pressed',String(mode==='square'));previewHint.textContent=mode==='square'?'Instagram · 1080 × 1080':'Print · US Letter';refresh()}
letterButton.addEventListener('click',()=>setPreview('letter'));squareButton.addEventListener('click',()=>setPreview('square'));refresh();
document.getElementById('export-png').onclick=()=>{const canvas=document.createElement('canvas');draw(canvas,'square');canvas.toBlob(blob=>download(blob,'daily-breath-recovery-weekly-<?=DailyStudio::esc($targetDate)?>-instagram.png'),'image/png')};
document.getElementById('export-pdf').onclick=async()=>{const canvas=document.createElement('canvas');draw(canvas,'letter');download(await pdfFromCanvas(canvas),'daily-breath-recovery-weekly-<?=DailyStudio::esc($targetDate)?>.pdf')};
})();
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
