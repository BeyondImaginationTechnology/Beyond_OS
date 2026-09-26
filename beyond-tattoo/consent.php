<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
$token = trim((string)($_GET['t'] ?? $_POST['t'] ?? ''));
$link = bt_consent_link($token);
if (!$link) { http_response_code(410); $pageTitle='Signing link unavailable'; require __DIR__.'/includes/header.php'; echo '<main class="consent-shell"><h1>Signing link unavailable</h1><p>This private signing link has expired or has already been completed. Contact the studio for a new link.</p></main>'; require __DIR__.'/includes/footer.php'; exit; }
$error=''; $done=false; $recordId=0;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        bt_require_csrf();
        $name=mb_substr(trim((string)($_POST['client_name'] ?? '')),0,200);
        $signature=trim((string)($_POST['signature'] ?? ''));
        if ($name==='') throw new InvalidArgumentException('Enter your full name.');
        $prefix='data:image/png;base64,';
        if (!str_starts_with($signature,$prefix)) throw new InvalidArgumentException('Please sign in the box before submitting.');
        $binary=base64_decode(substr($signature,strlen($prefix)),true);
        if ($binary===false || strlen($binary)<100 || strlen($binary)>120000 || !str_starts_with($binary,"\x89PNG\r\n\x1a\n")) throw new InvalidArgumentException('The signature could not be read. Please sign again.');
        $attestations=[];
        foreach (['adult','sober','health_discussed','risks','aftercare','consent','privacy'] as $field) {
            if (($_POST[$field] ?? '') !== '1') throw new InvalidArgumentException('Please review and confirm every required statement.');
            $attestations[$field]=true;
        }
        $recordId=bt_save_consent_record($link,$name,$signature,$attestations); $signedAtDisplay=gmdate('F j, Y H:i:s'); $done=true;
    } catch (InvalidArgumentException $exception) { $error=$exception->getMessage(); }
      catch (Throwable $exception) { $error=$exception->getMessage()==='This signing link has already been used or has expired.'?$exception->getMessage():'Your consent could not be saved. Please ask the studio for help.'; }
}
$pageTitle=$done?'Consent completed':'Tattoo procedure consent';
require __DIR__.'/includes/header.php';
?>
<main class="consent-shell consent-client">
<?php if($done): ?>
  <section class="consent-panel consent-complete"><span class="eyebrow">Signature recorded · <?=e($signedAtDisplay ?? gmdate('F j, Y H:i:s'))?> UTC</span><h1>Thank you, <?=e($name)?>.</h1><p>Your signed procedure consent was saved with these appointment details. This screen is your printable copy.</p><div class="consent-summary"><b><?=e($link['studio_name'])?></b><span>Artist: <?=e($link['artist_name'])?></span><span>Procedure: <?=e($link['procedure_description'])?></span><span>Placement: <?=e($link['placement'])?></span><?php if(!empty($link['appointment_date'])):?><span>Appointment: <?=e($link['appointment_date'])?></span><?php endif; ?><img src="<?=e($signature)?>" alt="Your captured signature"></div><h2>What you confirmed</h2><ul class="consent-copy-list"><li>You are at least 19 and were not impaired.</li><li>You discussed relevant health matters with the artist.</li><li>You understood the tattoo procedure and possible risks.</li><li>You accepted aftercare responsibility and voluntarily consented to this procedure.</li><li>You received notice about the studio’s use and storage of this consent record.</li></ul><button class="consent-button" type="button" onclick="window.print()">Print / Save signed copy as PDF</button><p class="consent-small">For a copy or privacy question, contact <?=e($link['privacy_contact_name'])?> at <a href="mailto:<?=e($link['privacy_contact_email'])?>"><?=e($link['privacy_contact_email'])?></a>.</p></section>
<?php else: ?>
  <a class="consent-back" href="<?=e(bt_app_url())?>">Beyond Tattoo</a>
  <header class="consent-heading"><span class="eyebrow">Tattoo appointment consent</span><h1>Before your session</h1><p>Please read each statement carefully and ask your artist if anything is unclear. This link can be submitted once.</p></header>
  <?php if($error!==''):?><div class="consent-notice is-error"><?=e($error)?></div><?php endif; ?>
  <section class="consent-panel"><dl class="consent-details"><div><dt>Studio</dt><dd><?=e($link['studio_name'])?></dd></div><div><dt>Artist</dt><dd><?=e($link['artist_name'])?></dd></div><div><dt>Procedure</dt><dd><?=e($link['procedure_description'])?></dd></div><div><dt>Placement</dt><dd><?=e($link['placement'])?></dd></div><?php if(!empty($link['appointment_date'])):?><div><dt>Appointment</dt><dd><?=e($link['appointment_date'])?></dd></div><?php endif; ?></dl></section>
  <form method="post" id="consent-form" class="consent-panel"><input type="hidden" name="_csrf" value="<?=e(bt_csrf_token())?>"><input type="hidden" name="t" value="<?=e($token)?>"><input type="hidden" name="signature" id="signature-value">
    <label class="consent-label">Your full legal name<input name="client_name" maxlength="200" autocomplete="name" value="<?=e($_POST['client_name']??'')?>" required></label>
    <h2>Procedure and risks</h2><p class="consent-copy">Tattooing places pigment into the skin using needles and is intended to be permanent, though appearance can change with healing and time. Possible effects include pain, bleeding, infection, allergic reaction, scarring, and unexpected healing or pigment changes. Discuss any concern with your artist before the procedure begins.</p>
    <div class="consent-checks">
      <label><input type="checkbox" name="adult" value="1" required <?=isset($_POST['adult'])?'checked':''?>> I confirm I am 19 years of age or older. If I am under 19, I will not use this online form and will arrange in-person guardian consent.</label>
      <label><input type="checkbox" name="sober" value="1" required <?=isset($_POST['sober'])?'checked':''?>> I am not impaired by alcohol or drugs and am able to make this decision voluntarily.</label>
      <label><input type="checkbox" name="health_discussed" value="1" required <?=isset($_POST['health_discussed'])?'checked':''?>> I have told my artist about relevant allergies, skin conditions, medications, healing concerns, or other health matters. No detailed medical history is collected in this form.</label>
      <label><input type="checkbox" name="risks" value="1" required <?=isset($_POST['risks'])?'checked':''?>> I have had the chance to ask questions and understand the procedure and risks described above.</label>
      <label><input type="checkbox" name="aftercare" value="1" required <?=isset($_POST['aftercare'])?'checked':''?>> I agree to follow the studio’s aftercare instructions and contact a qualified health professional if I have a health concern.</label>
      <label><input type="checkbox" name="consent" value="1" required <?=isset($_POST['consent'])?'checked':''?>> I voluntarily consent to the tattoo procedure described on this page. I may ask to stop before the procedure begins.</label>
      <label><input type="checkbox" name="privacy" value="1" required <?=isset($_POST['privacy'])?'checked':''?>> I understand <?=e($link['studio_name'])?> collects my name, signature, consent confirmations, and appointment details to document this procedure consent and retain its studio record. The record is stored through Beyond Tattoo; authorized studio users and service operators may access it to provide the service. It is not used for marketing. Ask the studio how long it keeps this record. For privacy questions or access/correction requests, contact <?=e($link['privacy_contact_name'])?> at <a href="mailto:<?=e($link['privacy_contact_email'])?>"><?=e($link['privacy_contact_email'])?></a>.</label>
    </div>
    <fieldset class="consent-sign-field"><legend>Sign below</legend><p>Use your finger, stylus, or mouse to sign your name.</p><canvas id="signature-pad" width="1000" height="300" aria-label="Signature pad"></canvas><button class="consent-clear" type="button" id="clear-signature">Clear signature</button></fieldset>
    <button class="consent-button consent-submit" type="submit">Sign and submit consent →</button>
    <p class="consent-small">This is a procedure consent and health declaration, not a guarantee of outcome or a general release of liability. Optional photography/publicity permission is separate and is not requested here. Have any studio-specific wording reviewed locally.</p>
  </form>
<?php endif; ?>
</main>
<style>
.consent-shell{max-width:760px;margin:auto;padding:32px 18px 80px;color:#f5f1e9}.consent-back{display:inline-block;color:#d2b47b;margin-bottom:24px}.consent-heading h1{font-size:clamp(2.5rem,9vw,4.2rem);line-height:1;margin:12px 0}.consent-heading p,.consent-copy,.consent-small{color:#c0b8ab;line-height:1.65}.consent-panel,.consent-notice{background:#141414;border:1px solid #423b31;border-radius:18px;padding:22px;margin:18px 0}.consent-notice.is-error{background:#291817;border-color:#8a3935}.consent-details{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:0}.consent-details div{padding:12px;background:#1c1b19;border-radius:10px}.consent-details dt{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:#bca271}.consent-details dd{margin:5px 0 0}.consent-label{display:grid;gap:8px;font-weight:750}.consent-label input{padding:13px;border-radius:9px;border:1px solid #574f43;background:#090909;color:#fff;font:inherit}.consent-checks{display:grid;gap:13px;margin:22px 0}.consent-checks label{display:flex;align-items:flex-start;gap:11px;line-height:1.5}.consent-checks input{width:20px;height:20px;flex:0 0 20px;accent-color:#c09b5c;margin:2px 0 0}.consent-checks a,.consent-small a{color:#e0bd7c}.consent-sign-field{border:1px solid #51483c;border-radius:12px;padding:14px}.consent-sign-field legend{font-weight:800}.consent-sign-field p{margin:4px 0 10px;color:#c0b8ab}.consent-sign-field canvas{display:block;width:100%;height:auto;aspect-ratio:10/3;background:#f9f6ef;border-radius:8px;touch-action:none}.consent-clear{margin-top:10px;background:transparent;border:1px solid #756b5a;color:#eee;border-radius:8px;padding:8px 12px}.consent-button{border:1px solid #bd9d63;background:#b68b4c;color:#15110b;border-radius:10px;padding:13px 16px;font-weight:850;cursor:pointer}.consent-submit{width:100%;margin-top:16px}.consent-small{font-size:.82rem}.consent-summary{display:grid;gap:10px;margin:22px 0;padding:17px;background:#1c1b19;border-radius:12px}.consent-summary img{width:min(100%,380px);height:90px;object-fit:contain;background:#f9f6ef;border-radius:6px}.consent-complete{text-align:left}.consent-complete h1{font-size:clamp(2.2rem,8vw,3.3rem)}.consent-copy-list{line-height:1.7}@media(max-width:520px){.consent-details{grid-template-columns:1fr}.consent-panel{padding:17px}}
@media print{body{background:#fff!important}.consent-shell,.consent-shell *{color:#111!important}.consent-shell{max-width:none}.consent-panel{border:1px solid #aaa;background:#fff;break-inside:avoid}.consent-complete>.consent-button,.consent-back{display:none}.consent-summary,.consent-details div{background:#fff;border:1px solid #ddd}}
</style>
<?php if(!$done): ?><script>
(()=>{const canvas=document.getElementById('signature-pad'),ctx=canvas.getContext('2d'),form=document.getElementById('consent-form');let drawing=false,inked=false;ctx.strokeStyle='#111';ctx.lineWidth=5;ctx.lineCap='round';ctx.lineJoin='round';const point=e=>{const r=canvas.getBoundingClientRect();return{x:(e.clientX-r.left)*canvas.width/r.width,y:(e.clientY-r.top)*canvas.height/r.height}};canvas.addEventListener('pointerdown',e=>{e.preventDefault();canvas.setPointerCapture(e.pointerId);drawing=true;const p=point(e);ctx.beginPath();ctx.moveTo(p.x,p.y)});canvas.addEventListener('pointermove',e=>{if(!drawing)return;const p=point(e);ctx.lineTo(p.x,p.y);ctx.stroke();inked=true});for(const n of ['pointerup','pointercancel','lostpointercapture'])canvas.addEventListener(n,()=>drawing=false);document.getElementById('clear-signature').addEventListener('click',()=>{ctx.clearRect(0,0,canvas.width,canvas.height);inked=false});form.addEventListener('submit',e=>{if(!inked){e.preventDefault();alert('Please sign in the box before submitting.');return}document.getElementById('signature-value').value=canvas.toDataURL('image/png')})})();
</script><?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>
