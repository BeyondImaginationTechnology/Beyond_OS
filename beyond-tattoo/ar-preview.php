<?php
declare(strict_types=1);

$disableBeyondShell = true;
$pageTitle = 'AR Tattoo Preview — Beyond Tattoo';
$pageDescription = 'Preview a tattoo stencil over a live camera view with optional on-device pose anchoring.';
$pageCanonical = 'https://beyondimagination.co.technology/beyond-tattoo/ar-preview.php';
require __DIR__ . '/includes/header.php';
?>
<style>
:root{--ar-ink:#09060d;--ar-panel:#17101f;--ar-line:#5c4070;--ar-violet:#bd65ff;--ar-pale:#eadbff;--ar-muted:#b9a9c5}*{box-sizing:border-box}.ar-main{min-height:100vh;background:radial-gradient(circle at 78% 5%,#42205d 0,transparent 34rem),var(--ar-ink);color:#fff}.ar-wrap{width:min(1180px,calc(100% - 28px));margin:auto;padding:28px 0 64px}.ar-top{display:flex;justify-content:space-between;gap:16px;align-items:center}.ar-top a{color:var(--ar-pale);font-weight:850;text-decoration:none}.ar-kicker{margin:42px 0 8px;color:#d3a2ff;font-size:.72rem;font-weight:950;letter-spacing:.18em;text-transform:uppercase}.ar-title{max-width:830px;margin:0;font-size:clamp(2.7rem,7vw,5.5rem);line-height:.9;letter-spacing:-.065em;text-transform:uppercase}.ar-title strong{color:var(--ar-violet)}.ar-lead{max-width:720px;margin:18px 0 0;color:var(--ar-muted);font-size:1.02rem;line-height:1.7}.ar-layout{display:grid;grid-template-columns:minmax(260px,330px) minmax(0,1fr);gap:18px;margin-top:28px}.ar-panel{padding:20px;border:1px solid var(--ar-line);border-radius:22px;background:rgba(23,16,31,.94);box-shadow:0 25px 70px #0005}.ar-panel h2{margin:0;font-size:1.1rem;text-transform:uppercase;letter-spacing:.06em}.ar-panel p{color:var(--ar-muted);font-size:.86rem;line-height:1.6}.ar-label{display:block;margin:18px 0 7px;color:#fff;font-size:.78rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase}.ar-file{display:block;width:100%;padding:12px;border:1px dashed #76538c;border-radius:14px;color:var(--ar-muted);background:#0f0a14}.ar-range{width:100%;accent-color:var(--ar-violet)}.ar-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ar-button{width:100%;min-height:48px;border:1px solid #8d62a8;border-radius:13px;background:#2c1b3a;color:#fff;font:800 .85rem system-ui,sans-serif;cursor:pointer}.ar-button.primary{background:linear-gradient(135deg,#c468ff,#7b2fbb);border-color:#d597ff;color:#16081f}.ar-button:disabled{cursor:not-allowed;opacity:.5}.ar-status{min-height:24px;margin:14px 0 0;color:#e5c8ff;font-size:.82rem;font-weight:750}.ar-privacy{padding:14px;border:1px solid rgba(213,151,255,.3);border-radius:13px;background:rgba(189,101,255,.08);color:#d7c9df;font-size:.76rem;line-height:1.55}.ar-stage{position:relative;display:grid;min-height:620px;place-items:center;overflow:hidden;border:1px solid #694d7d;border-radius:22px;background:radial-gradient(circle at 50% 30%,#2c153b,#070509 68%)}.ar-stage video,.ar-stage canvas{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.ar-stage canvas{pointer-events:none}.ar-empty{max-width:360px;padding:34px;text-align:center;color:var(--ar-muted)}.ar-empty b{display:block;margin-bottom:12px;color:#e1b6ff;font-size:3rem}.ar-badge{position:absolute;z-index:2;top:15px;left:15px;padding:8px 11px;border:1px solid rgba(255,255,255,.35);border-radius:999px;background:rgba(7,4,10,.76);color:#fff;font-size:.68rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.ar-badge.is-tracking{border-color:#7df5bb;color:#b8ffd8}.ar-disclaimer{margin:18px 0 0;color:#a99bb4;font-size:.75rem;line-height:1.6}.ar-disclaimer strong{color:#e5d9ea}@media(max-width:800px){.ar-layout{grid-template-columns:1fr}.ar-stage{min-height:520px}.ar-top{font-size:.85rem}}@media(max-width:520px){.ar-wrap{width:min(100% - 20px,580px);padding-top:16px}.ar-title{font-size:3rem}.ar-stage{min-height:420px}.ar-grid{grid-template-columns:1fr}.ar-top a:last-child{display:none}}
</style>
<main class="ar-main">
  <div class="ar-wrap">
    <header class="ar-top"><a href="index.php">← Beyond Tattoo</a><a href="stencil-camera.php">Violet Trace →</a></header>
    <p class="ar-kicker">Violet Trace · AR preview beta</p>
    <h1 class="ar-title">See the stencil<br><strong>before the session.</strong></h1>
    <p class="ar-lead">Use a live camera view to preview your own stencil placement. On supported devices, on-device pose tracking anchors the artwork around the upper torso. You can always adjust the scale and position by hand.</p>

    <div class="ar-layout">
      <section class="ar-panel" aria-label="AR tattoo preview controls">
        <h2>Set up preview</h2>
        <label class="ar-label" for="ar-stencil">Tattoo stencil image</label>
        <input class="ar-file" id="ar-stencil" type="file" accept="image/png,image/jpeg,image/webp">
        <p>Select a clean PNG or image from your device. It stays in this browser tab.</p>
        <button class="ar-button primary" id="ar-enable" type="button">Enable camera preview</button>
        <p class="ar-status" id="ar-status" role="status" aria-live="polite">Camera permission has not been requested.</p>
        <div id="ar-adjustments" hidden>
          <label class="ar-label" for="ar-size">Stencil size</label><input class="ar-range" id="ar-size" type="range" min="15" max="100" value="48">
          <label class="ar-label" for="ar-opacity">Ink opacity</label><input class="ar-range" id="ar-opacity" type="range" min="20" max="100" value="72">
          <div class="ar-grid"><div><label class="ar-label" for="ar-x">Left / right</label><input class="ar-range" id="ar-x" type="range" min="0" max="100" value="50"></div><div><label class="ar-label" for="ar-y">Up / down</label><input class="ar-range" id="ar-y" type="range" min="0" max="100" value="48"></div></div>
          <button class="ar-button" id="ar-tracking" type="button" disabled>Use ML torso anchor</button>
        </div>
        <p class="ar-privacy"><strong>Camera permission:</strong> Your camera starts only after you press Enable camera preview. Video frames stay on your device. If you enable pose tracking, the MediaPipe pose model is downloaded once; frames are still processed locally in your browser.</p>
      </section>

      <section class="ar-stage" aria-label="Live tattoo preview">
        <span class="ar-badge" id="ar-badge">Camera off</span>
        <video id="ar-video" playsinline muted hidden></video><canvas id="ar-overlay" hidden aria-label="Tattoo placement preview"></canvas>
        <div class="ar-empty" id="ar-empty"><b>◌</b><strong>Camera preview is ready when you are.</strong><br>Choose a stencil, then grant camera permission to test placement.</div>
      </section>
    </div>
    <p class="ar-disclaimer"><strong>Preview only.</strong> This is a planning aid, not a body scan or a medical or fit guarantee. A qualified tattoo artist must confirm placement, scale, anatomy and final transfer preparation.</p>
  </div>
</main>
<script type="module">
const $ = (id) => document.getElementById(id);
const video = $('ar-video'); const overlay = $('ar-overlay'); const ctx = overlay.getContext('2d');
let stream = null; let stencil = null; let poseLandmarker = null; let tracking = false; let lastVideoTime = -1; let latestAnchor = null;
const status = (message) => { $('ar-status').textContent = message; };
const badge = (message, active = false) => { $('ar-badge').textContent = message; $('ar-badge').classList.toggle('is-tracking', active); };
const render = () => {
  if (!video.videoWidth || !video.videoHeight) return;
  const width = video.videoWidth, height = video.videoHeight;
  if (overlay.width !== width || overlay.height !== height) { overlay.width = width; overlay.height = height; }
  ctx.clearRect(0, 0, width, height);
  if (!stencil) return;
  const manualX = Number($('ar-x').value) / 100 * width;
  const manualY = Number($('ar-y').value) / 100 * height;
  const anchored = tracking && latestAnchor;
  const centerX = anchored ? latestAnchor.x : manualX;
  const centerY = anchored ? latestAnchor.y : manualY;
  const targetWidth = anchored ? latestAnchor.width : width * Number($('ar-size').value) / 100;
  const targetHeight = targetWidth * stencil.naturalHeight / stencil.naturalWidth;
  ctx.save(); ctx.globalAlpha = Number($('ar-opacity').value) / 100; ctx.globalCompositeOperation = 'multiply';
  ctx.drawImage(stencil, centerX - targetWidth / 2, centerY - targetHeight / 2, targetWidth, targetHeight); ctx.restore();
};
const loop = async () => {
  if (!stream || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) { requestAnimationFrame(loop); return; }
  if (tracking && poseLandmarker && lastVideoTime !== video.currentTime) {
    lastVideoTime = video.currentTime;
    const result = poseLandmarker.detectForVideo(video, performance.now());
    const points = result.landmarks?.[0];
    if (points?.[11] && points?.[12] && points?.[23] && points?.[24]) {
      const shoulder = {x:(points[11].x + points[12].x) / 2, y:(points[11].y + points[12].y) / 2};
      const hip = {x:(points[23].x + points[24].x) / 2, y:(points[23].y + points[24].y) / 2};
      const shoulderSpan = Math.abs(points[11].x - points[12].x);
      latestAnchor = {x:shoulder.x * video.videoWidth, y:(shoulder.y * .58 + hip.y * .42) * video.videoHeight, width:Math.max(100, shoulderSpan * video.videoWidth * 1.35)};
      badge('ML torso anchor', true);
    } else { latestAnchor = null; badge('Finding torso…'); }
  }
  render(); requestAnimationFrame(loop);
};
const loadPoseModel = async () => {
  if (poseLandmarker) return poseLandmarker;
  status('Loading the on-device pose model…');
  const {FilesetResolver, PoseLandmarker} = await import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14');
  const vision = await FilesetResolver.forVisionTasks('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm');
  poseLandmarker = await PoseLandmarker.createFromOptions(vision, {baseOptions:{modelAssetPath:'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_full/float16/1/pose_landmarker_full.task', delegate:'GPU'}, runningMode:'VIDEO', numPoses:1});
  return poseLandmarker;
};
$('ar-enable').addEventListener('click', async () => {
  if (!navigator.mediaDevices?.getUserMedia) { status('This browser does not support camera previews. Use a modern mobile browser over HTTPS.'); return; }
  try {
    $('ar-enable').disabled = true; status('Requesting camera permission…');
    stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}, width:{ideal:1280}, height:{ideal:1280}}, audio:false});
    video.srcObject = stream; await video.play(); video.hidden = false; overlay.hidden = false; $('ar-empty').hidden = true; $('ar-adjustments').hidden = false; $('ar-tracking').disabled = false;
    $('ar-enable').textContent = 'Camera enabled'; status(stencil ? 'Camera preview is live. Adjust the placement or enable ML torso anchoring.' : 'Camera preview is live. Add a stencil image to see placement.'); badge('Live camera'); requestAnimationFrame(loop);
  } catch (error) { $('ar-enable').disabled = false; status(error.name === 'NotAllowedError' ? 'Camera permission was not granted. You can continue using the regular Violet Trace workflow.' : 'The camera could not start. Check that another app is not using it, then try again.'); }
});
$('ar-stencil').addEventListener('change', () => {
  const file = $('ar-stencil').files?.[0]; if (!file || !file.type.startsWith('image/')) { status('Choose a PNG, JPG or WebP stencil image.'); return; }
  const url = URL.createObjectURL(file); const image = new Image(); image.onload = () => { URL.revokeObjectURL(url); stencil = image; status(stream ? 'Stencil loaded. Adjust its placement over the camera preview.' : 'Stencil loaded. Enable the camera preview when ready.'); render(); }; image.onerror = () => { URL.revokeObjectURL(url); status('That stencil image could not be opened.'); }; image.src = url;
});
for (const control of ['ar-size','ar-opacity','ar-x','ar-y']) $(control).addEventListener('input', render);
$('ar-tracking').addEventListener('click', async () => {
  try { $('ar-tracking').disabled = true; await loadPoseModel(); tracking = !tracking; latestAnchor = null; $('ar-tracking').textContent = tracking ? 'Use manual placement' : 'Use ML torso anchor'; status(tracking ? 'ML torso anchoring is on. Stand with shoulders and hips in view.' : 'Manual placement is on.'); badge(tracking ? 'Finding torso…' : 'Live camera', false); }
  catch (error) { tracking = false; status('ML torso anchoring could not load. Manual placement remains available.'); console.warn(error); }
  finally { $('ar-tracking').disabled = false; }
});
window.addEventListener('pagehide', () => { stream?.getTracks().forEach((track) => track.stop()); poseLandmarker?.close?.(); });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
