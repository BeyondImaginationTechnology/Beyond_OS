<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$csrf = Auth::csrf();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Beyond Tattoo AI Studio</title>
<style>
:root{--ink:#08090b;--paper:#f4f0e8;--gold:#d8ab52;--red:#8f2f2a;--muted:#8b8a86;--line:#2b2b2d}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#352419 0,transparent 31rem),#08090b;color:#fff;font:15px/1.5 Inter,system-ui,sans-serif}
button,input,textarea,select{font:inherit}.wrap{max-width:1420px;margin:auto;padding:34px}.hero{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:25px}.eyebrow{color:var(--gold);font-size:11px;font-weight:950;letter-spacing:.18em}.hero h1{margin:7px 0;font:700 clamp(38px,6vw,68px)/.96 Georgia,serif}.hero p{max-width:720px;margin:0;color:#b5b1aa}.badges{display:flex;gap:8px;flex-wrap:wrap}.badge{padding:9px 12px;border:1px solid #55462e;border-radius:999px;background:#171511;color:#e4c98d;font-size:12px;font-weight:850}.layout{display:grid;grid-template-columns:minmax(350px,.82fr) minmax(470px,1.18fr);gap:22px}.panel{padding:24px;border:1px solid var(--line);border-radius:25px;background:rgba(18,18,20,.94);box-shadow:0 30px 80px #0008}.section-title{margin:0 0 18px;font-size:20px}.field{margin-bottom:15px}.field label{display:block;margin-bottom:6px;font-weight:850}.field small{display:block;margin-top:5px;color:#898985}.field input,.field textarea,.field select{width:100%;padding:12px 13px;border:1px solid #38383c;border-radius:12px;background:#0d0e10;color:#fff;outline:0}.field textarea{min-height:134px;resize:vertical}.field input:focus,.field textarea:focus,.field select:focus{border-color:var(--gold);box-shadow:0 0 0 3px #d8ab5222}.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.check{display:flex;align-items:center;gap:9px;margin:4px 0 15px;color:#d6d2ca;font-weight:800}.check input{width:18px;height:18px;accent-color:var(--gold)}.actions{display:flex;gap:9px;flex-wrap:wrap}.btn{border:0;border-radius:13px;padding:13px 16px;background:linear-gradient(135deg,#d8ab52,#b78027);color:#090a0b;font-weight:950;cursor:pointer}.btn.secondary{border:1px solid #414145;background:#222327;color:#fff}.btn.remotion{width:100%;margin-top:9px;background:linear-gradient(135deg,#8f2f2a,#d05b43);color:#fff}.btn:disabled{opacity:.45;cursor:wait}.status{min-height:25px;margin:13px 0;color:#d7bd86;font-weight:750}.status.error{color:#ff9ba0}.workflow{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:0 0 18px}.workflow span{padding:9px;border:1px solid #343438;border-radius:11px;color:#777;text-align:center;font-size:11px;font-weight:900}.workflow span.active{border-color:#8e733e;background:#292214;color:#f2cf84}.workflow span.complete{border-color:#2f6e45;color:#88dba4}.preview{position:relative;min-height:760px;display:grid;place-items:center;overflow:hidden;border:1px solid #343438;border-radius:20px;background:radial-gradient(circle,#302b25,#111214 66%)}.preview:before{content:"";position:absolute;inset:0;opacity:.14;background-image:radial-gradient(#fff .65px,transparent .65px);background-size:11px 11px}.preview img{position:relative;display:block;max-width:92%;max-height:760px;object-fit:contain;border-radius:12px;box-shadow:0 28px 70px #000b}.empty{position:relative;max-width:360px;text-align:center;color:#8e8c88}.empty b{display:block;color:var(--gold);font-size:58px}.meta-bar{display:flex;justify-content:space-between;gap:15px;margin-top:14px;color:#8d8b86;font-size:12px}.output{margin-top:19px;padding-top:18px;border-top:1px solid #303034}.output p{color:#9d9a94}.safe-note{padding:11px 13px;border:1px solid #3c493f;border-radius:12px;background:#172019;color:#99d2a9;font-size:12px}
@media(max-width:960px){.wrap{padding:20px}.hero{display:block}.badges{margin-top:15px}.layout{grid-template-columns:1fr}.preview{min-height:520px}.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="wrap">
  <header class="hero">
    <div>
      <div class="eyebrow">BEYOND TATTOO · OPENAI CREATIVE STUDIO</div>
      <h1>Daily stencil generator</h1>
      <p>Generate a transfer-ready tattoo master with GPT Image 2, review the actual artwork, publish its artist pack, and export a polished animated Remotion reel.</p>
    </div>
    <div class="badges"><span class="badge">GPT Image 2</span><span class="badge">High quality · 1024×1536</span><span class="badge">Remotion · 1080×1920</span></div>
  </header>

  <section class="layout">
    <article class="panel">
      <div class="workflow" aria-label="Generation workflow"><span class="active">1 · Create</span><span>2 · Review</span><span>3 · Publish / Video</span></div>
      <h2 class="section-title">Creative direction</h2>
      <div class="field">
        <label for="idea">Describe the original stencil</label>
        <textarea id="idea" maxlength="700" placeholder="Example: A celestial lion with a crescent halo, sacred geometry, botanical accents, and a strong central silhouette."></textarea>
        <small>Describe an original concept. The API prompt automatically enforces clean, transfer-ready black linework.</small>
      </div>
      <div class="row">
        <div class="field"><label for="style">Tattoo style</label><select id="style"><option>Fine-line blackwork</option><option>Neo-traditional linework</option><option>Geometric ornamental</option><option>Engraving realism</option><option>Japanese-inspired flow</option><option>Dark illustrative blackwork</option><option>Minimalist single-line</option></select></div>
        <div class="field"><label for="placement">Body placement</label><select id="placement"><option>Outer forearm</option><option>Upper arm</option><option>Calf</option><option>Thigh</option><option>Back panel</option><option>Sternum</option><option>Full sleeve focal panel</option></select></div>
      </div>
      <div class="row">
        <div class="field"><label for="composition">Composition</label><select id="composition"><option>Centered vertical emblem</option><option>Organic vertical flow</option><option>Symmetrical ornamental crest</option><option>Diagonal anatomical sweep</option><option>Full-panel narrative composition</option></select></div>
        <div class="field"><label for="lineWeight">Line-weight plan</label><select id="lineWeight"><option>Balanced transfer-ready hierarchy</option><option>Bold structural contours</option><option>Fine-line dominant with bold anchors</option><option>Graphic blackwork contrast</option></select></div>
      </div>
      <div class="field"><label for="detail">Detail density</label><select id="detail"><option>High detail with controlled open skin breaks</option><option>Medium detail with generous negative space</option><option>Intricate ornamental detail</option><option>Minimal, iconic and highly readable</option></select></div>
      <div class="row">
        <div class="field"><label for="title">Stencil title</label><input id="title" maxlength="100" placeholder="Celestial Lion"></div>
        <div class="field"><label for="collection">Collection</label><select id="collection"><option>Beyond Divine Collection</option><option>Beyond Ancient Collection</option><option>Beyond Dark Collection</option><option>Beyond Japanese Collection</option></select></div>
      </div>
      <label class="check"><input id="includeNarration" type="checkbox" checked> Include OpenAI narration in the Remotion video</label>
      <div class="actions"><button class="btn" id="generate" type="button">✦ Generate high-quality stencil</button><button class="btn secondary" id="clear" type="button">Clear</button></div>
      <p class="status" id="status" role="status" aria-live="polite"></p>
      <div class="safe-note">The existing static stencil package remains available. Promotional video output uses the separate animated Remotion renderer.</div>
      <div class="output">
        <h2>Approved output</h2>
        <p>Review the generated master before publishing or rendering. Neither action runs automatically.</p>
        <div class="actions"><button class="btn" id="publishBtn" disabled type="button">Publish artist pack</button></div>
        <button class="btn remotion" id="renderVideo" disabled type="button">Export animated Remotion MP4</button>
      </div>
    </article>

    <article class="panel">
      <div class="preview" id="preview"><div class="empty"><b>✦</b><h2>Generated stencil preview</h2><p>Your GPT Image 2 artwork will appear here at the same 2:3 aspect ratio used by the final stencil and video.</p></div></div>
      <div class="meta-bar"><span id="providerMeta">Awaiting generation</span><span id="qualityMeta">High quality · PNG</span></div>
    </article>
  </section>
</main>

<script src="/server/admin/daily-studio/assets/beyond-tattoo-remotion-renderer.js?v=20260729-1"></script>
<script>
(() => {
  'use strict';
  const csrf = <?=json_encode($csrf)?>;
  const $ = (id) => document.getElementById(id);
  const status = $('status');
  const preview = $('preview');
  const workflow = [...document.querySelectorAll('.workflow span')];
  let image = '';
  let renderToken = '';
  let provider = '';

  const message = (text, error = false) => {
    status.textContent = text;
    status.classList.toggle('error', error);
  };
  const setStep = (active) => workflow.forEach((step, index) => {
    step.classList.toggle('active', index === active);
    step.classList.toggle('complete', index < active);
  });
  const title = () => $('title').value.trim() || $('idea').value.trim().split(/[,.]/)[0].slice(0, 70) || 'Generated Stencil';
  const videoCaption = () => `${$('style').value} · ${$('idea').value.trim()}`.slice(0, 480);
  const videoPayload = (includeNarration = $('includeNarration').checked) => ({
    render_token: renderToken,
    stencilTitle: title(),
    collectionName: $('collection').value,
    suggestedPlacement: $('placement').value,
    caption: videoCaption(),
    style: $('style').value,
    includeNarration,
  });
  const generationPayload = () => ({
    idea: $('idea').value.trim(),
    style: $('style').value,
    placement: $('placement').value,
    composition: $('composition').value,
    line_weight: $('lineWeight').value,
    detail: $('detail').value,
  });
  const download = (blob, name) => {
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = name;
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1600);
  };
  const show = (data) => {
    image = data.image;
    renderToken = data.render_token || '';
    provider = data.provider || '';
    preview.innerHTML = '';
    const img = new Image();
    img.src = image;
    img.alt = `${title()} generated tattoo stencil`;
    preview.appendChild(img);
    $('publishBtn').disabled = false;
    $('renderVideo').disabled = !renderToken;
    $('providerMeta').textContent = `${data.provider === 'openai' ? 'OpenAI' : data.provider} · ${data.model}`;
    $('qualityMeta').textContent = `${data.quality || 'high'} quality · ${data.size || '1024×1536'} · PNG`;
    setStep(1);
  };

  $('generate').onclick = async () => {
    const idea = $('idea').value.trim();
    if (idea.length < 8) {
      message('Describe the stencil concept in a little more detail.', true);
      return;
    }
    $('generate').disabled = true;
    $('publishBtn').disabled = true;
    $('renderVideo').disabled = true;
    setStep(0);
    message('GPT Image 2 is creating the high-quality transfer master…');
    try {
      const response = await fetch('api/generate-stencil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(generationPayload()),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Image generation failed.');
      show(data);
      message('Stencil generated. Review the linework, then publish or export its Remotion reel.');
    } catch (error) {
      message(error.message || 'Image generation failed.', true);
    } finally {
      $('generate').disabled = false;
    }
  };

  $('clear').onclick = () => {
    image = '';
    renderToken = '';
    provider = '';
    $('publishBtn').disabled = true;
    $('renderVideo').disabled = true;
    preview.innerHTML = '<div class="empty"><b>✦</b><h2>Generated stencil preview</h2><p>Your GPT Image 2 artwork will appear here at the same 2:3 aspect ratio used by the final stencil and video.</p></div>';
    $('providerMeta').textContent = 'Awaiting generation';
    setStep(0);
    message('');
  };

  $('publishBtn').onclick = async () => {
    if (!image) return;
    $('publishBtn').disabled = true;
    message('Building and publishing the static artist pack…');
    try {
      const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1536"><image width="1024" height="1536" href="generated-stencil-of-the-day.png"/></svg>';
      const response = await fetch('publish-tattoo.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify({
          title: title(),
          motif: $('idea').value,
          style: $('style').value,
          placement: $('placement').value,
          collection: $('collection').value,
          svg,
          png: image,
          seed: `${provider || 'openai'}-gpt-image-2`,
        }),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Publishing failed.');
      setStep(2);
      message('Artist pack published. Beyond Tattoo now uses this stencil.');
      $('publishBtn').textContent = 'Published ✓';
    } catch (error) {
      message(error.message || 'Publishing failed.', true);
      $('publishBtn').disabled = false;
    }
  };

  const browserTattooVideo = async () => {
    const renderer = window.BeyondTattooRemotion;
    if (!renderer?.render) throw new Error('The Beyond Tattoo browser renderer did not load.');
    let audioUrl = '';
    try {
      if ($('includeNarration').checked) {
        message('Generating OpenAI narration for the tattoo reel…');
        const response = await fetch('api/render-tattoo-remotion.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
          body: JSON.stringify({...videoPayload(true), audio_only: true}),
        });
        if (!response.ok) {
          let text = 'Tattoo narration could not be generated.';
          try { text = (await response.json()).error || text; } catch {}
          throw new Error(text);
        }
        audioUrl = URL.createObjectURL(await response.blob());
      }
      const support = await renderer.canRender(!!audioUrl);
      if (!support.supported) throw new Error(support.message);
      const date = new Date().toLocaleDateString('en-US', {
        weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
      });
      const blob = await renderer.render({
        props: {
          mainArtwork: image,
          studioTransfer: image,
          collectionName: $('collection').value,
          stencilTitle: title(),
          date,
          suggestedPlacement: $('placement').value,
          downloadUrl: 'https://beyondimagination.co.technology/beyond-tattoo/stencil-of-day.php',
          caption: videoCaption(),
          style: $('style').value,
          audioFile: '',
          showQrCode: true,
        },
        artworkUrl: image,
        audioUrl,
        onProgress: (progress) => message(`Rendering animated Beyond Tattoo MP4… ${Math.round(progress * 100)}%`),
      });
      if (blob.size < 1024) throw new Error('The Remotion browser renderer returned an empty video.');
      download(blob, `beyond-tattoo-${title().toLowerCase().replace(/[^a-z0-9]+/g, '-')}-remotion.mp4`);
      setStep(2);
      message('Animated Remotion MP4 exported in your browser.');
    } finally {
      if (audioUrl) URL.revokeObjectURL(audioUrl);
    }
  };

  const serverTattooVideo = async () => {
    const response = await fetch('api/render-tattoo-remotion.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
      body: JSON.stringify(videoPayload()),
    });
    if (!response.ok) {
      let text = 'The Remotion video could not be rendered.';
      try { text = (await response.json()).error || text; } catch {}
      throw new Error(text);
    }
    if (response.headers.get('X-Video-Renderer') !== 'Remotion') throw new Error('The server did not return a Remotion video.');
    download(await response.blob(), `beyond-tattoo-${title().toLowerCase().replace(/[^a-z0-9]+/g, '-')}-remotion.mp4`);
    setStep(2);
    message('Animated Remotion MP4 exported by the server.');
  };

  $('renderVideo').onclick = async () => {
    if (!renderToken) return;
    if (location.protocol === 'file:') {
      message('Remotion requires the served Studio URL, not a raw file:// page.', true);
      return;
    }
    $('renderVideo').disabled = true;
    message('Starting the animated Beyond Tattoo render…');
    try {
      if (window.BeyondTattooRemotion?.render) await browserTattooVideo();
      else await serverTattooVideo();
    } catch (error) {
      message(error.message || 'The Remotion video could not be rendered.', true);
    } finally {
      $('renderVideo').disabled = false;
    }
  };
})();
</script>
</body>
</html>
