<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#110b19">
<title>Violet Trace · Beyond Tattoo</title>
<style>
:root{color-scheme:dark;--violet:#a861ef;--ink:#100b18;--panel:#1b1426;--line:#42314f;--muted:#bbafc8}*{box-sizing:border-box}[hidden]{display:none!important}body{margin:0;background:radial-gradient(circle at 80% 0,#3d2254,transparent 38rem),var(--ink);color:#fff;font:15px/1.45 Inter,system-ui,sans-serif}a{color:#e7caff}.wrap{width:min(1100px,calc(100% - 28px));margin:auto;padding:24px 0 70px}header{display:flex;align-items:center;justify-content:space-between;gap:18px}header a{text-decoration:none;font-weight:800}h1{font:700 clamp(36px,7vw,68px)/1 Georgia,serif;margin:22px 0 8px}p{color:var(--muted)}.eyebrow{color:#d2a2ff;font-size:12px;letter-spacing:.18em;font-weight:900;text-transform:uppercase}.layout{display:grid;grid-template-columns:minmax(260px,320px) 1fr;gap:18px;margin-top:22px}.panel{background:rgba(27,20,38,.96);border:1px solid var(--line);border-radius:20px;padding:20px}.controls label{display:block;font-weight:800;margin:16px 0 6px}.controls small{display:block;color:var(--muted);margin-top:5px}.controls input[type=file]{display:block;width:100%;color:var(--muted)}.controls input[type=range]{width:100%;accent-color:var(--violet)}.mode{display:flex;gap:8px}.mode button,.actions button{border:1px solid #674d7b;border-radius:11px;background:#2d1e3c;color:#fff;font:inherit;font-weight:850;padding:10px 12px;cursor:pointer}.mode button.active,.actions button.primary{background:#9d52e9;color:#130919;border-color:#bb83f5}.actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.actions button:disabled{opacity:.45;cursor:not-allowed}.canvas-wrap{min-height:480px;display:grid;place-items:center;overflow:hidden;background:#0b0710;border:1px solid var(--line);border-radius:16px;padding:12px}.canvas-wrap canvas{display:block;max-width:100%;max-height:70vh;background:white;box-shadow:0 16px 40px #0009}.empty{text-align:center;max-width:340px;padding:40px;color:var(--muted)}.empty b{display:block;color:#d8a3ff;font-size:48px}.status{min-height:22px;color:#dab3ff;font-weight:750}.hint{font-size:13px}.placement{margin-top:18px}.placement-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}@media(max-width:750px){.layout{grid-template-columns:1fr}.canvas-wrap{min-height:350px}.wrap{padding-top:16px}}@media print{body{background:#fff}.wrap{width:100%;padding:0}.controls,header,h1,.eyebrow,.intro,.status,.hint,.actions{display:none!important}.layout{display:block;margin:0}.panel{border:0;background:#fff;padding:0}.canvas-wrap{display:block;min-height:0;border:0;padding:0;background:#fff}.canvas-wrap canvas{width:auto;max-width:100%;max-height:100vh;box-shadow:none}}
</style>
</head>
<body>
<main class="wrap">
<header><a href="index.php">← Beyond Tattoo</a><a href="tattoo-generator.php">Tattoo imagination generator →</a></header>
<div class="eyebrow">Violet Trace · v1.2.2</div>
<h1>Picture to stencil</h1>
<p class="intro">Photograph a drawing or choose an image. Tune its outline, save the PNG, or print a clean transfer reference. Review every line with your tattoo artist before skin application.</p>
<div class="layout">
<section class="panel controls" aria-label="Stencil controls">
<label for="source">1 · Picture or drawing</label>
<input id="source" type="file" accept="image/*" capture="environment">
<small>Camera capture is offered on supported phones. Files stay on your device.</small>
<label for="threshold">2 · Outline detail</label>
<input id="threshold" type="range" min="25" max="220" value="90"><small id="thresholdValue">90 · medium detail</small>
<label>3 · Trace color</label>
<div class="mode"><button type="button" id="violet" class="active" aria-pressed="true">Violet Trace</button><button type="button" id="black" aria-pressed="false">Black ink</button></div>
<div class="placement" id="placement" hidden>
<label for="bodyPhoto">Optional · Placement photo</label><input id="bodyPhoto" type="file" accept="image/*" capture="environment">
<small>Overlay the stencil on a body photo for a rough scale preview.</small>
<div class="placement-grid"><div><label for="scale">Size</label><input id="scale" type="range" min="15" max="90" value="50"></div><div><label for="opacity">Opacity</label><input id="opacity" type="range" min="20" max="100" value="75"></div><div><label for="horizontal">Left / right</label><input id="horizontal" type="range" min="0" max="100" value="50"></div><div><label for="vertical">Up / down</label><input id="vertical" type="range" min="0" max="100" value="50"></div></div>
</div>
<div class="actions"><button class="primary" id="save" type="button" disabled>Save stencil PNG</button><button id="share" type="button" disabled>Share</button><button id="print" type="button" disabled>Print</button><button id="placementToggle" type="button" disabled>Placement preview</button></div>
<p class="hint">The black outline is the printer-friendly version. Violet Trace is for review and social previews.</p>
</section>
<section class="panel"><div class="canvas-wrap" id="canvasWrap"><div class="empty" id="empty"><b>✧</b>Take a picture or choose an image to start.</div><canvas id="result" hidden aria-label="Stencil preview"></canvas></div><p class="status" id="status" role="status" aria-live="polite"></p></section>
</div>
</main>
<script>
(() => {
  'use strict';
  const $ = (id) => document.getElementById(id);
  const canvas = $('result');
  const ctx = canvas.getContext('2d', {willReadFrequently:true});
  let source = null;
  let body = null;
  let stencilPixels = null;
  let color = 'violet';
  let placementMode = false;
  const status = (message) => { $('status').textContent = message; };
  const readImage = (file) => new Promise((resolve, reject) => {
    if (!file || !file.type.startsWith('image/')) { reject(new Error('Choose an image file.')); return; }
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('This image could not be opened.')); };
    img.src = url;
  });
  const setColor = (next) => {
    color = next;
    for (const option of ['violet', 'black']) {
      $(option).classList.toggle('active', option === next);
      $(option).setAttribute('aria-pressed', String(option === next));
    }
    render();
  };
  const makeOutline = () => {
    if (!source) return;
    const ratio = Math.min(1, 1800 / Math.max(source.naturalWidth, source.naturalHeight));
    const width = Math.max(1, Math.round(source.naturalWidth * ratio));
    const height = Math.max(1, Math.round(source.naturalHeight * ratio));
    const scratch = document.createElement('canvas');
    scratch.width = width; scratch.height = height;
    const scratchCtx = scratch.getContext('2d', {willReadFrequently:true});
    scratchCtx.fillStyle = '#fff'; scratchCtx.fillRect(0, 0, width, height);
    scratchCtx.drawImage(source, 0, 0, width, height);
    const rgba = scratchCtx.getImageData(0, 0, width, height).data;
    const gray = new Uint8Array(width * height);
    for (let i = 0; i < gray.length; i++) gray[i] = .299 * rgba[i*4] + .587 * rgba[i*4+1] + .114 * rgba[i*4+2];
    const output = new Uint8ClampedArray(width * height);
    const threshold = Number($('threshold').value);
    for (let y = 1; y < height - 1; y++) {
      for (let x = 1; x < width - 1; x++) {
        const i = y * width + x;
        const gx = -gray[i-width-1] + gray[i-width+1] - 2*gray[i-1] + 2*gray[i+1] - gray[i+width-1] + gray[i+width+1];
        const gy = -gray[i-width-1] - 2*gray[i-width] - gray[i-width+1] + gray[i+width-1] + 2*gray[i+width] + gray[i+width+1];
        output[i] = Math.hypot(gx, gy) >= threshold ? 255 : 0;
      }
    }
    stencilPixels = {width, height, mask:output};
    render();
  };
  const stencilCanvas = () => {
    if (!stencilPixels) return null;
    const {width, height, mask} = stencilPixels;
    const next = document.createElement('canvas'); next.width = width; next.height = height;
    const context = next.getContext('2d');
    const pixels = context.createImageData(width, height);
    const ink = color === 'violet' ? [129, 42, 185] : [0, 0, 0];
    for (let i = 0; i < mask.length; i++) {
      const offset = i * 4;
      pixels.data[offset] = mask[i] ? ink[0] : 255;
      pixels.data[offset+1] = mask[i] ? ink[1] : 255;
      pixels.data[offset+2] = mask[i] ? ink[2] : 255;
      pixels.data[offset+3] = 255;
    }
    context.putImageData(pixels, 0, 0);
    return next;
  };
  const render = () => {
    const stencil = stencilCanvas();
    if (!stencil) return;
    canvas.hidden = false; $('empty').hidden = true;
    if (placementMode && body) {
      const ratio = Math.min(1, 1800 / Math.max(body.naturalWidth, body.naturalHeight));
      canvas.width = Math.round(body.naturalWidth * ratio); canvas.height = Math.round(body.naturalHeight * ratio);
      ctx.drawImage(body, 0, 0, canvas.width, canvas.height);
      const targetWidth = canvas.width * Number($('scale').value) / 100;
      const targetHeight = targetWidth * stencil.height / stencil.width;
      const x = (canvas.width - targetWidth) * Number($('horizontal').value) / 100;
      const y = (canvas.height - targetHeight) * Number($('vertical').value) / 100;
      const stencilImage = stencil.getContext('2d').getImageData(0, 0, stencil.width, stencil.height);
      for (let i = 0; i < stencilPixels.mask.length; i++) stencilImage.data[i*4+3] = stencilPixels.mask[i] ? 255 : 0;
      stencil.getContext('2d').putImageData(stencilImage, 0, 0);
      ctx.globalAlpha = Number($('opacity').value) / 100;
      ctx.drawImage(stencil, x, y, targetWidth, targetHeight);
      ctx.globalAlpha = 1;
    } else {
      canvas.width = stencil.width; canvas.height = stencil.height;
      ctx.drawImage(stencil, 0, 0);
    }
    for (const id of ['save','share','print','placementToggle']) $(id).disabled = false;
  };
  $('source').addEventListener('change', async () => {
    try { source = await readImage($('source').files[0]); makeOutline(); status('Outline ready. Adjust detail and choose violet or black.'); }
    catch (error) { status(error.message); }
  });
  $('bodyPhoto').addEventListener('change', async () => {
    try { body = await readImage($('bodyPhoto').files[0]); render(); status('Placement preview ready. Adjust scale and position.'); }
    catch (error) { status(error.message); }
  });
  $('threshold').addEventListener('input', () => { $('thresholdValue').textContent = `${$('threshold').value} · ${Number($('threshold').value) < 65 ? 'more detail' : Number($('threshold').value) > 145 ? 'fewer lines' : 'medium detail'}`; makeOutline(); });
  $('violet').onclick = () => setColor('violet'); $('black').onclick = () => setColor('black');
  $('placementToggle').onclick = () => { placementMode = !placementMode; $('placement').hidden = !placementMode; $('placementToggle').textContent = placementMode ? 'Stencil only' : 'Placement preview'; render(); };
  for (const id of ['scale','opacity','horizontal','vertical']) $(id).addEventListener('input', render);
  const fileName = () => `beyond-tattoo-${placementMode && body ? 'placement-preview' : color === 'violet' ? 'violet-trace' : 'outline-stencil'}.png`;
  $('save').onclick = () => { const a = document.createElement('a'); a.href = canvas.toDataURL('image/png'); a.download = fileName(); a.click(); };
  $('share').onclick = async () => {
    try {
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
      if (!blob) throw new Error('Could not prepare PNG.');
      const file = new File([blob], fileName(), {type:'image/png'});
      if (navigator.canShare?.({files:[file]})) await navigator.share({files:[file], title:'Beyond Tattoo · Violet Trace'});
      else { $('save').click(); status('Sharing is unavailable here, so the PNG was downloaded.'); }
    } catch (error) { if (error.name !== 'AbortError') status(error.message || 'Sharing was unavailable.'); }
  };
  $('print').onclick = () => window.print();
})();
</script>
</body>
</html>
