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
.newsletter-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px}.newsletter-head h1{margin:4px 0}.newsletter-layout{display:grid;grid-template-columns:minmax(320px,.72fr) minmax(430px,1.28fr);gap:20px}.newsletter-panel{padding:22px;border:1px solid var(--border,#2c3140);border-radius:22px;background:var(--card,#171a23)}.newsletter-fields{display:grid;gap:13px}.newsletter-fields label{display:grid;gap:6px;color:var(--muted,#9ca3af);font-size:12px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.newsletter-fields textarea{min-height:76px;resize:vertical}.newsletter-fields .body-field{min-height:112px}.newsletter-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:17px}.newsletter-actions button{cursor:pointer}.newsletter-preview{display:grid;place-items:center;min-height:690px;background:linear-gradient(145deg,#0a1711,#19221d)}#newsletter-canvas{display:block;width:min(100%,570px);height:auto;border-radius:4px;box-shadow:0 25px 70px #0008}.export-note{margin:12px 0 0;color:var(--muted,#9ca3af);font-size:12px;line-height:1.5}@media(max-width:900px){.newsletter-layout{grid-template-columns:1fr}.newsletter-preview{min-height:540px}}
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
<article class="newsletter-panel newsletter-preview"><canvas id="newsletter-canvas" width="1275" height="1650" aria-label="Newsletter preview"></canvas></article></section>
<script>
(()=>{
const seed=<?=json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
const ids=['headline','verse','reference','devotional-title','devotional','challenge-title','challenge'];
const field=Object.fromEntries(ids.map(id=>[id,document.getElementById('field-'+id)]));
const preview=document.getElementById('newsletter-canvas');
const value=()=>({...seed,headline:field.headline.value,verse:field.verse.value,reference:field.reference.value,devotionalTitle:field['devotional-title'].value,devotional:field.devotional.value,challengeTitle:field['challenge-title'].value,challenge:field.challenge.value});
function rounded(ctx,x,y,w,h,r){ctx.beginPath();ctx.roundRect(x,y,w,h,r);ctx.fill()}
function wrap(ctx,text,x,y,maxWidth,lineHeight,maxLines=99){const words=String(text).trim().split(/\s+/),lines=[];let line='';for(const word of words){const test=line?line+' '+word:word;if(ctx.measureText(test).width>maxWidth&&line){lines.push(line);line=word}else line=test}if(line)lines.push(line);const visible=lines.slice(0,maxLines);if(lines.length>maxLines)visible[maxLines-1]=visible[maxLines-1].replace(/[.,;:]?$/,'…');visible.forEach((item,index)=>ctx.fillText(item,x,y+index*lineHeight));return y+visible.length*lineHeight}
function draw(canvas,mode){const square=mode==='square',w=square?1080:1275,h=square?1080:1650;canvas.width=w;canvas.height=h;const ctx=canvas.getContext('2d'),d=value(),m=square?70:92;ctx.fillStyle='#071d14';ctx.fillRect(0,0,w,h);const glow=ctx.createRadialGradient(w*.82,h*.12,10,w*.82,h*.12,w*.7);glow.addColorStop(0,'rgba(208,163,76,.28)');glow.addColorStop(1,'rgba(7,29,20,0)');ctx.fillStyle=glow;ctx.fillRect(0,0,w,h);ctx.fillStyle='#d0a34c';ctx.fillRect(m,m,82,8);ctx.font=`800 ${square?25:30}px Arial`;ctx.letterSpacing='3px';ctx.fillText('DAILY BREATH',m,m+(square?58:68));ctx.fillStyle='#b9d1c2';ctx.font=`600 ${square?17:20}px Arial`;ctx.fillText(String(d.issueDate).toUpperCase(),w-m-ctx.measureText(String(d.issueDate).toUpperCase()).width,m+(square?58:68));ctx.fillStyle='#fffaf0';ctx.font=`700 ${square?64:82}px Georgia`;ctx.fillText(d.headline,m,m+(square?145:178));let y=m+(square?215:275);ctx.fillStyle='rgba(255,255,255,.075)';rounded(ctx,m,y,w-m*2,square?285:390,square?30:36);ctx.fillStyle='#d0a34c';ctx.font=`800 ${square?18:22}px Arial`;ctx.fillText('VERSE FOR THE WEEK',m+38,y+48);ctx.fillStyle='#fff';ctx.font=`500 ${square?32:43}px Georgia`;let verseBottom=wrap(ctx,'“'+d.verse+'”',m+38,y+(square?102:118),w-m*2-76,square?42:56,square?4:5);ctx.fillStyle='#f0cb77';ctx.font=`800 ${square?19:24}px Arial`;ctx.fillText(d.reference,m+38,Math.min(y+(square?252:350),verseBottom+24));y+=square?325:438;ctx.fillStyle='#f0cb77';ctx.font=`800 ${square?18:22}px Arial`;ctx.fillText('RECOVERY REFLECTION',m,y);ctx.fillStyle='#fff';ctx.font=`700 ${square?31:39}px Arial`;y=wrap(ctx,d.devotionalTitle,m,y+(square?48:58),w-m*2,square?38:48,2);ctx.fillStyle='#c8d8cf';ctx.font=`400 ${square?22:28}px Arial`;y=wrap(ctx,d.devotional,m,y+(square?15:22),w-m*2,square?31:39,square?3:5);y+=square?42:62;ctx.fillStyle='#2d694b';rounded(ctx,m,y,w-m*2,square?190:265,square?26:32);ctx.fillStyle='#f4d98e';ctx.font=`800 ${square?17:21}px Arial`;ctx.fillText('THIS WEEK’S CHALLENGE',m+32,y+43);ctx.fillStyle='#fff';ctx.font=`700 ${square?28:36}px Arial`;ctx.fillText(d.challengeTitle,m+32,y+(square?82:94));ctx.fillStyle='#dce9e0';ctx.font=`400 ${square?20:26}px Arial`;wrap(ctx,d.challenge,m+32,y+(square?120:142),w-m*2-64,square?28:35,square?2:3);ctx.fillStyle='#91b7a0';ctx.font=`600 ${square?15:18}px Arial`;ctx.fillText('BREATHE THROUGH THE CRAVING. YOU ARE NOT ALONE.',m,h-m+8);return canvas}
function download(blob,name){const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000)}
function bytes(text){return new TextEncoder().encode(text)}
function concat(parts){const size=parts.reduce((sum,p)=>sum+p.length,0),out=new Uint8Array(size);let at=0;for(const part of parts){out.set(part,at);at+=part.length}return out}
async function pdfFromCanvas(canvas){const jpegBlob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.94)),jpeg=new Uint8Array(await jpegBlob.arrayBuffer());const objects=[];objects[1]=bytes('<< /Type /Catalog /Pages 2 0 R >>');objects[2]=bytes('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');objects[3]=bytes('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /XObject << /Im0 5 0 R >> >> /Contents 4 0 R >>');const content=bytes('q 612 0 0 792 0 0 cm /Im0 Do Q');objects[4]=concat([bytes(`<< /Length ${content.length} >>\nstream\n`),content,bytes('\nendstream')]);objects[5]=concat([bytes(`<< /Type /XObject /Subtype /Image /Width ${canvas.width} /Height ${canvas.height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpeg.length} >>\nstream\n`),jpeg,bytes('\nendstream')]);const parts=[bytes('%PDF-1.4\n%DBREATH\n')],offsets=[0];let length=parts[0].length;for(let i=1;i<=5;i++){offsets[i]=length;const part=concat([bytes(`${i} 0 obj\n`),objects[i],bytes('\nendobj\n')]);parts.push(part);length+=part.length}const xref=length;let table='xref\n0 6\n0000000000 65535 f \n';for(let i=1;i<=5;i++)table+=String(offsets[i]).padStart(10,'0')+' 00000 n \n';parts.push(bytes(table+`trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`));return new Blob([concat(parts)],{type:'application/pdf'})}
function refresh(){draw(preview,'letter')}ids.forEach(id=>field[id].addEventListener('input',refresh));refresh();
document.getElementById('export-png').onclick=()=>{const canvas=document.createElement('canvas');draw(canvas,'square');canvas.toBlob(blob=>download(blob,'daily-breath-recovery-weekly-<?=DailyStudio::esc($targetDate)?>-instagram.png'),'image/png')};
document.getElementById('export-pdf').onclick=async()=>{const canvas=document.createElement('canvas');draw(canvas,'letter');download(await pdfFromCanvas(canvas),'daily-breath-recovery-weekly-<?=DailyStudio::esc($targetDate)?>.pdf')};
})();
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
