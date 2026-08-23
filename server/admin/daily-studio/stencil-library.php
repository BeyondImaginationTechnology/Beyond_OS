<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/beyond-tattoo/includes/library-catalog.php';
require_once dirname(__DIR__, 3) . '/beyond-tattoo/includes/stencil-content.php';
$csrf = Auth::csrf();

$dropSchedule = [];
$sequence = 0;
$collectionPresets = [
    'divine-realism' => [
        'label' => 'Divine Realism Collection',
        'style' => 'Engraving realism',
        'placement' => 'Outer upper arm',
        'composition' => 'Centered vertical emblem',
        'accent' => 'antique gold and restrained royal purple',
        'concept' => 'sacred realism, radiant geometry, devotional ornament and a strong protective silhouette',
        'lore' => 'a contemporary emblem of devotion, guardianship and perseverance',
    ],
    'beyond-ancient' => [
        'label' => 'Beyond Ancient Collection',
        'style' => 'Engraving realism',
        'placement' => 'Outer forearm',
        'composition' => 'Symmetrical ornamental crest',
        'accent' => 'antique gold, lapis blue and obsidian black',
        'concept' => 'Egyptian ceremonial ornament, carved-stone geometry, sacred botanical accents and a regal central silhouette',
        'lore' => 'an artist-led meditation on memory, protection and the enduring language of ancient symbols',
    ],
    'japanese-legends' => [
        'label' => 'Japanese Legends Collection',
        'style' => 'Japanese-inspired flow',
        'placement' => 'Full sleeve focal panel',
        'composition' => 'Organic vertical flow',
        'accent' => 'aged gold, vermilion and deep indigo',
        'concept' => 'traditional Japanese movement, wind bars, cloud rhythm, botanical balance and clear body-flow direction',
        'lore' => 'a modern tribute to transformation, courage and the stories carried through Japanese visual tradition',
    ],
    'dark-realism' => [
        'label' => 'Dark Realism Collection',
        'style' => 'Dark realism · Season recommended',
        'placement' => 'Calf',
        'composition' => 'Full-panel narrative composition',
        'accent' => 'burnished silver, antique gold and restrained crimson',
        'concept' => 'cinematic dark realism, believable anatomy, dimensional gothic architecture, weathered materials, controlled micro-texture and dramatic high-contrast depth',
        'lore' => 'a visual reminder that mortality, time and shadow can sharpen purpose rather than erase it',
    ],
];
foreach (bt_library_collections() as $collectionSlug => $collection) {
    $preset = $collectionPresets[$collectionSlug];
    foreach ($collection['stencils'] as [$stencilTitle, $releaseDate]) {
        $sequence++;
        $dropSchedule[] = [
            'sequence' => $sequence,
            'title' => $stencilTitle,
            'release_date' => $releaseDate,
            'display_date' => (new DateTimeImmutable($releaseDate))->format('l, F j, Y'),
            'collection' => $preset['label'],
            'collection_slug' => $collectionSlug,
            'collection_description' => $collection['description'],
            'style' => $preset['style'],
            'placement' => $preset['placement'],
            'composition' => $preset['composition'],
            'accent' => $preset['accent'],
            'concept' => $stencilTitle . ' interpreted through ' . $preset['concept'] . '.',
            'lore' => $stencilTitle . ' is presented in the ' . $preset['label'] . ' as ' . $preset['lore'] . '.',
        ];
    }
}
$today = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
$nextDropDate = $today;
$publishedDrop = bt_stencil_content();
$publishedDate = trim((string)($publishedDrop['iso_date'] ?? ''));
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedDate) && $publishedDate >= $today) {
    $nextDropDate = (new DateTimeImmutable($publishedDate, new DateTimeZone('America/Vancouver')))->modify('+1 day')->format('Y-m-d');
}
$defaultDropIndex = count($dropSchedule) - 1;
foreach ($dropSchedule as $index => $drop) {
    if ($drop['release_date'] >= $nextDropDate) {
        $defaultDropIndex = $index;
        break;
    }
}
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
.schedule-card{margin-bottom:22px;padding:17px;border:1px solid #594725;border-radius:17px;background:linear-gradient(135deg,#241d12,#111214)}.schedule-card label{display:block;margin-bottom:8px;color:#f1cf87;font-weight:950}.schedule-card select{width:100%;padding:13px;border:1px solid #6d562c;border-radius:12px;background:#090a0b;color:#fff;font-weight:800}.drop-meta{display:flex;gap:7px;flex-wrap:wrap;margin-top:11px}.drop-meta span{padding:6px 9px;border-radius:999px;background:#342a19;color:#e6c57f;font-size:11px;font-weight:900}.preview-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px}.preview-tab{border:1px solid #39393d;border-radius:12px;padding:10px;background:#17181b;color:#92918e;font-weight:900;cursor:pointer}.preview-tab.active{border-color:#a07d3c;background:#302617;color:#f2ce83}.tool-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.tool-grid .wide{grid-column:1/-1}.lore-box{min-height:118px!important}.review-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin:12px 0}.review-grid .check{margin:0;padding:10px;border:1px solid #333438;border-radius:11px;background:#111214;font-size:12px}
@media(max-width:960px){.wrap{padding:20px}.hero{display:block}.badges{margin-top:15px}.layout{grid-template-columns:1fr}.preview{min-height:520px}.row{grid-template-columns:1fr}.preview-tabs{grid-template-columns:repeat(2,1fr)}}
.asset-intel{margin-bottom:14px;padding:15px;border:1px solid #342f25;border-radius:16px;background:linear-gradient(135deg,#17130d,#111214)}.asset-intel-kicker{margin:0 0 4px;color:#d8ab52;font-size:11px;font-weight:950;letter-spacing:.16em;text-transform:uppercase}.asset-intel h3{margin:0 0 7px;font-size:20px}.asset-intel p{margin:0;color:#b8b0a3}.asset-intel-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}.asset-intel-grid span{padding:8px 10px;border:1px solid #3a352d;border-radius:10px;background:#0d0e10;color:#d8c79d;font-size:12px;font-weight:850}@media(max-width:620px){.asset-intel-grid{grid-template-columns:1fr}}
.fallback-panel{display:none;margin:14px 0 16px;padding:14px;border:1px solid #5d4824;border-radius:15px;background:#18140d}.fallback-panel.active{display:block}.fallback-panel h3{margin:0 0 6px;color:#f0cf87;font-size:16px}.fallback-panel p{margin:0 0 10px;color:#bdb4a5}.fallback-panel textarea{min-height:190px;margin-bottom:10px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;line-height:1.45}.fallback-actions{display:flex;gap:8px;flex-wrap:wrap}
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
    <div class="badges"><span class="badge">GPT Image 2</span><span class="badge">High quality · 1024×1536</span><span class="badge">IG post video · 1080×1080</span></div>
  </header>

  <section class="layout">
    <article class="panel">
      <div class="workflow" aria-label="Generation workflow"><span class="active">1 · Create</span><span>2 · Review</span><span>3 · Publish / Video</span></div>
      <div class="schedule-card">
        <label for="dropSchedule">Scheduled drop</label>
        <select id="dropSchedule">
          <?php foreach ($dropSchedule as $index => $drop): ?>
            <option value="<?=$index?>" <?=$index === $defaultDropIndex ? 'selected' : ''?>>
              #<?=str_pad((string)$drop['sequence'], 2, '0', STR_PAD_LEFT)?> · <?=htmlspecialchars($drop['display_date'])?> · <?=htmlspecialchars($drop['title'])?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="drop-meta"><span id="dropNumber">Drop — / 55</span><span id="dropDate">Choose a release</span><span id="dropCollection">Collection</span></div>
      </div>
      <h2 class="section-title">Creative direction</h2>
      <div class="field">
        <label for="idea">Describe the original stencil</label>
        <textarea id="idea" maxlength="700" placeholder="Example: A celestial lion with a crescent halo, sacred geometry, botanical accents, and a strong central silhouette."></textarea>
        <small>Describe an original concept. The API prompt automatically enforces clean, transfer-ready black linework.</small>
      </div>
      <div class="row">
        <div class="field"><label for="style">Tattoo style</label><select id="style"><optgroup label="Season recommendation"><option>Dark realism · Season recommended</option></optgroup><optgroup label="Realism"><option>Black-and-grey realism</option><option>Hyperrealism</option><option>Portrait realism</option><option>Engraving realism</option><option>Surreal realism</option><option>Micro realism</option></optgroup><optgroup label="Other styles"><option>Fine-line blackwork</option><option>Neo-traditional linework</option><option>Geometric ornamental</option><option>Japanese-inspired flow</option><option>Dark illustrative blackwork</option><option>Minimalist single-line</option></optgroup></select><small>Dark Realism drops automatically use the Season One recommended style.</small></div>
        <div class="field"><label for="placement">Body placement</label><select id="placement"><optgroup label="Arms"><option>Inner forearm</option><option>Outer forearm</option><option>Full forearm wrap</option><option>Inner bicep</option><option>Outer upper arm</option><option>Shoulder cap</option><option>Elbow panel</option><option>Wrist</option><option>Full sleeve focal panel</option><option>Half sleeve</option></optgroup><optgroup label="Torso"><option>Upper chest</option><option>Full chest panel</option><option>Sternum</option><option>Ribs / side torso</option><option>Upper back</option><option>Full back panel</option><option>Spine</option><option>Shoulder blade</option><option>Abdomen</option></optgroup><optgroup label="Legs"><option>Front thigh</option><option>Outer thigh</option><option>Inner thigh</option><option>Calf</option><option>Shin</option><option>Knee panel</option><option>Full leg sleeve focal panel</option><option>Ankle</option></optgroup><optgroup label="Other"><option>Nape / back of neck</option><option>Side of neck</option><option>Hand</option><option>Foot</option></optgroup></select><small>Choose the exact anatomy so the composition and placement mockup follow the body’s flow.</small></div>
      </div>
      <div class="row">
        <div class="field"><label for="composition">Composition</label><select id="composition"><option>Centered vertical emblem</option><option>Organic vertical flow</option><option>Symmetrical ornamental crest</option><option>Diagonal anatomical sweep</option><option>Full-panel narrative composition</option></select></div>
        <div class="field"><label for="lineWeight">Line-weight plan</label><select id="lineWeight"><option>Balanced transfer-ready hierarchy</option><option>Bold structural contours</option><option>Fine-line dominant with bold anchors</option><option>Graphic blackwork contrast</option></select></div>
      </div>
      <div class="field"><label for="detail">Realism detail</label><select id="detail"><optgroup label="Realism-focused"><option>High realism · anatomical accuracy, material texture and controlled skin breaks</option><option>Hyperreal detail · pores, weathering, reflections and crisp depth cues</option><option>Portrait realism · accurate features, expression, hair and fabric texture</option><option>Dark realism · deep value separation, aged texture and cinematic focal detail</option><option>Micro realism · selective fine texture with durable open space</option></optgroup><optgroup label="General"><option>High detail with controlled open skin breaks</option><option>Medium detail with generous negative space</option><option>Intricate ornamental detail</option><option>Minimal, iconic and highly readable</option></optgroup></select><small>Realism presets prioritize believable anatomy, surface texture, light direction and dimensional depth.</small></div>
      <div class="row">
        <div class="field"><label for="title">Stencil title</label><input id="title" maxlength="100" placeholder="Celestial Lion"></div>
        <div class="field"><label for="collection">Collection</label><select id="collection"><option>Divine Realism Collection</option><option>Beyond Ancient Collection</option><option>Japanese Legends Collection</option><option>Dark Realism Collection</option></select></div>
      </div>
      <div class="field"><label for="lore">Stencil lore</label><textarea class="lore-box" id="lore" maxlength="1200" placeholder="Collection story, symbolism and artist-facing meaning."></textarea><small>Schedule-aware lore is prepared automatically and can be edited before publishing.</small></div>
      <div class="actions"><button class="btn secondary" id="generateLore" type="button">Generate scheduled lore</button><button class="btn secondary" id="copyCaption" type="button">Copy drop caption</button></div>
      <label class="check"><input id="includeNarration" type="checkbox"> Include server narration in the Remotion video</label>
      <div class="field"><label for="stencilUpload">Or upload the approved stencil</label><input id="stencilUpload" type="file" accept="image/png,image/jpeg,image/webp"><small>The selected schedule supplies the title, collection, date, sequence and lore automatically.</small></div>
      <div class="actions" style="margin-bottom:15px"><a class="btn secondary" href="tattoo-asset-import.php" style="text-decoration:none">Upload assets to the private inbox →</a></div>
      <label class="check"><input id="preferFreeFallback" type="checkbox" checked> Use free image source before paid API</label>
      <div class="actions"><button class="btn" id="generate" type="button">✦ Prepare free prompt</button><button class="btn secondary" id="generatePaidFallback" type="button">Use paid API fallback</button><button class="btn secondary" id="clear" type="button">Clear</button></div>
      <p class="status" id="status" role="status" aria-live="polite"></p>
      <div class="fallback-panel" id="freeFallback" aria-live="polite">
        <h3>Free image fallback sources</h3>
        <p>Copy this prompt into a fallback image source, generate the image there, then upload the saved PNG/JPG/WebP above to continue the tattoo kit. Pexels, Unsplash and Pixabay are better for reference photos than final stencil output.</p>
        <div class="field"><label for="fallbackProvider">Fallback source</label><select id="fallbackProvider"><optgroup label="Free browser tools"><option value="meta">Meta AI</option><option value="designer">Microsoft Designer</option><option value="firefly">Adobe Firefly</option><option value="canva">Canva Text to Image</option><option value="ideogram">Ideogram</option></optgroup><optgroup label="API candidates"><option value="runware">Runware API</option><option value="openrouter">OpenRouter Image API</option><option value="leonardo">Leonardo.AI API</option><option value="deepai">DeepAI Pro API</option></optgroup></select></div>
        <textarea id="fallbackPrompt" readonly></textarea>
        <div class="fallback-actions">
          <button class="btn secondary" id="copyFallbackPrompt" type="button">Copy fallback prompt</button>
          <button class="btn secondary" id="openFallbackSource" type="button">Open selected source</button>
        </div>
        <div class="field" style="margin:12px 0 0"><label for="fallbackAssetUpload">Upload fallback result for selected tab</label><input id="fallbackAssetUpload" type="file" accept="image/png,image/jpeg,image/webp"></div>
      </div>
      <div class="safe-note">The existing static stencil package remains available. Promotional video output uses the separate animated Remotion renderer.</div>
      <div class="output">
        <h2>Complete daily drop kit</h2>
        <p>Build the six coordinated assets shown in your reference sheets. Free prompts are prepared first by default; paid API generation stays available as an explicit fallback. Both cards are composed locally with exact schedule text.</p>
        <div class="field"><label for="packStyle">Package format</label><select id="packStyle"><option>Premium retail hanging pack</option><option>Collector card and foil pouch</option><option>Luxury resealable stencil pouch</option><option>Artist box and supply pouch</option></select></div>
        <div class="tool-grid">
          <button class="btn secondary" id="generateReference" disabled type="button">1 · Generate reference art</button>
          <button class="btn secondary" id="generatePlacement" disabled type="button">3 · Generate placement mockup</button>
          <button class="btn secondary" id="generatePack" disabled type="button">4 · Generate premium pack</button>
          <button class="btn secondary" id="buildCards" disabled type="button">5–6 · Build lore + style cards</button>
          <button class="btn wide" id="generateFullKit" disabled type="button">Generate complete 6-piece kit · 3 image requests</button>
          <button class="btn secondary" id="downloadStencil" disabled type="button">Download stencil PNG</button>
          <button class="btn secondary" id="downloadMirror" disabled type="button">Download mirrored transfer</button>
          <button class="btn secondary wide" id="downloadActive" disabled type="button">Download selected asset</button>
        </div>
      </div>
      <div class="output">
        <h2>Approved output</h2>
        <p>Complete the artist preflight before publishing. Nothing runs automatically.</p>
        <div class="review-grid">
          <label class="check"><input class="preflight" type="checkbox"> Clean line hierarchy</label>
          <label class="check"><input class="preflight" type="checkbox"> Open negative space</label>
          <label class="check"><input class="preflight" type="checkbox"> Full design in frame</label>
          <label class="check"><input class="preflight" type="checkbox"> No stray text artifacts</label>
        </div>
        <div class="actions"><button class="btn" id="publishBtn" disabled type="button">Publish artist pack</button></div>
        <button class="btn remotion" id="renderVideo" disabled type="button">Export animated Remotion MP4</button>
      </div>
    </article>

    <article class="panel">
      <div class="preview-tabs">
        <button class="preview-tab" data-preview="reference" type="button">1 · Reference</button>
        <button class="preview-tab active" data-preview="stencil" type="button">2 · Stencil</button>
        <button class="preview-tab" data-preview="placement" type="button">3 · Placement</button>
        <button class="preview-tab" data-preview="pack" type="button">4 · Packaging</button>
        <button class="preview-tab" data-preview="lore" type="button">5 · Lore card</button>
        <button class="preview-tab" data-preview="style" type="button">6 · Style card</button>
      </div>
      <div class="asset-intel" id="assetIntel" aria-live="polite"></div>
      <div class="preview" id="preview"><div class="empty"><b>✦</b><h2>Generated stencil preview</h2><p>Your GPT Image 2 artwork will appear here at the same 2:3 aspect ratio used by the final stencil and video.</p></div></div>
      <div class="meta-bar"><span id="providerMeta">Awaiting generation</span><span id="qualityMeta">High quality · PNG</span></div>
    </article>
  </section>
</main>

<script src="/server/admin/daily-studio/assets/beyond-tattoo-remotion-renderer.js?v=<?=urlencode((string)(@filemtime(__DIR__ . '/assets/beyond-tattoo-remotion-renderer.js') ?: '1'))?>"></script>
<script>
(() => {
  'use strict';
  const csrf = <?=json_encode($csrf)?>;
  const drops = <?=json_encode($dropSchedule, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
  const $ = (id) => document.getElementById(id);
  const status = $('status');
  const preview = $('preview');
  const assetIntel = $('assetIntel');
  const freeFallback = $('freeFallback');
  const fallbackPrompt = $('fallbackPrompt');
  const fallbackSources = {
    meta: {label: 'Meta AI', url: 'https://www.meta.ai/'},
    designer: {label: 'Microsoft Designer', url: 'https://designer.microsoft.com/image-creator'},
    firefly: {label: 'Adobe Firefly', url: 'https://firefly.adobe.com/generate/images'},
    canva: {label: 'Canva Text to Image', url: 'https://www.canva.com/ai-image-generator/'},
    ideogram: {label: 'Ideogram', url: 'https://ideogram.ai/t/explore'},
    runware: {label: 'Runware API', url: 'https://runware.ai/docs'},
    openrouter: {label: 'OpenRouter Image API', url: 'https://openrouter.ai/docs/guides/overview/multimodal/image-generation'},
    leonardo: {label: 'Leonardo.AI API', url: 'https://leonardo.ai/api'},
    deepai: {label: 'DeepAI Pro API', url: 'https://deep.ai/docs'},
  };
  const workflow = [...document.querySelectorAll('.workflow span')];
  let image = '';
  let referenceImage = '';
  let placementImage = '';
  let packImage = '';
  let loreCard = '';
  let styleCard = '';
  let renderToken = '';
  let provider = '';
  let previewMode = 'stencil';
  const previewLabels = {
    reference: 'reference artwork',
    stencil: 'printer-ready stencil',
    placement: 'placement mockup',
    pack: 'premium packaging',
    lore: 'information and lore card',
    style: 'design and style card',
  };
  const assetIntelCopy = {
    reference: 'High-detail visual reference for mood, materials, lighting, and artist consultation.',
    stencil: 'Printer-ready transfer master with subtle bit-atom provenance marks baked into the stencil canvas.',
    placement: 'Anatomy and scale mockup to confirm flow before final studio sizing.',
    pack: 'Instagram-square premium package post with the resealable pack kept visible and text kept off the package body.',
    lore: 'Story card for symbolism, collection context, and collector-facing release notes.',
    style: 'Technical card for design language, placement, release metadata, and studio handoff.',
  };

  const message = (text, error = false) => {
    status.textContent = text;
    status.classList.toggle('error', error);
  };
  const setFallbackPrompt = (prompt, note = 'Free image fallback prompt is ready.') => {
    const value = String(prompt || '').trim();
    fallbackPrompt.value = value;
    freeFallback.classList.toggle('active', value !== '');
    if (value && note) message(note, false);
  };
  const buildMetaStencilPrompt = () => {
    const payload = generationPayload();
    const realismDirection = payload.style.toLowerCase().includes('realism')
      ? ['', 'REALISM DIRECTION', 'Prioritize believable anatomy and proportions, coherent lighting, convincing material and surface texture, dimensional foreground/midground/background separation, and a sharply resolved focal area. Translate values into tattooable contour, hatching, stipple and deliberate black shapes instead of muddy gray shading.']
      : [];
    return [
      'Create one original, premium tattoo stencil master suitable for a professional artist.',
      '',
      'DESIGN BRIEF',
      `- Concept: ${payload.idea}`,
      `- Tattoo style: ${payload.style}`,
      `- Intended body placement: ${payload.placement}`,
      `- Composition: ${payload.composition}`,
      `- Line-weight plan: ${payload.line_weight}`,
      `- Detail density: ${payload.detail}`,
      ...realismDirection,
      '',
      'OUTPUT REQUIREMENTS',
      'Return a single isolated vertical stencil on a pure white background. Crisp black linework only. No skin, body, person, studio scene, paper texture, mockup, frame, border, crop marks, typography, letters, numbers, signature, logo, watermark, color, gray wash, soft shading, drop shadow, glow, or photographic rendering. Keep the entire design inside the canvas with comfortable white margins.',
    ].join('\n');
  };
  const buildMetaAssetPrompt = (assetType) => {
    const drop = activeDrop();
    const shared = [
      `Scheduled design: ${drop.title}`,
      `Collection: ${drop.collection}`,
      `Release date: ${drop.release_date}`,
      `Season sequence: ${drop.sequence} of 55`,
      `Creative context: ${$('idea').value.trim()}`,
      `Tattoo style: ${$('style').value}`,
      `Realism detail: ${$('detail').value}`,
    ].join('\n');
    const lead = {
      reference: `Create a museum-quality, high-detail 2:3 vertical reference artwork for the Beyond Tattoo daily stencil "${drop.title}". Use the uploaded black-and-white stencil as the exact design blueprint. Preserve the central subject, pose, silhouette, major symbols, framing and proportions.`,
      placement: `Create a premium editorial tattoo placement mockup in a 2:3 vertical frame for the Beyond Tattoo daily stencil "${drop.title}". Use the uploaded black-and-white stencil as the exact tattoo design. Show one consenting adult model in a tasteful, non-sexual, modest crop at this placement: ${$('placement').value}.`,
      pack: `Create a premium, straight-on 2:3 vertical product-packaging hero for Beyond Tattoo. Use the uploaded black-and-white tattoo stencil as the exact central featured artwork in a large clean white or warm-ivory window. Package format: ${$('packStyle').value}.`,
    }[assetType] || 'Create a premium Beyond Tattoo image using the uploaded stencil as the exact source artwork.';
    return [
      lead,
      '',
      shared,
      '',
      'CONSTRAINTS',
      'Preserve the uploaded stencil identity. Do not invent readable words, dates, numbers, logos or watermarks. Keep the subject fully visible with safe margins. Return one finished vertical image.',
    ].join('\n');
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
    date: activeDrop().display_date,
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
  const activeDrop = () => drops[Number($('dropSchedule').value)] || drops[0];
  const slug = (value) => value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'daily-stencil';
  const assetSource = (mode) => ({
    reference: referenceImage,
    stencil: image,
    placement: placementImage,
    pack: packImage,
    lore: loreCard,
    style: styleCard,
  }[mode] || '');
  const emptyPreview = (mode = 'stencil') => `<div class="empty"><b>${mode === 'stencil' ? '✦' : '◇'}</b><h2>${previewLabels[mode] || 'Daily drop'} preview</h2><p>${mode === 'stencil' ? 'Generate or upload the approved stencil to unlock the complete six-piece kit.' : `Create the ${previewLabels[mode]} from the approved scheduled stencil.`}</p></div>`;
  const renderPreview = () => {
    const source = assetSource(previewMode);
    const drop = activeDrop();
    assetIntel.innerHTML = `<p class="asset-intel-kicker">${previewLabels[previewMode] || 'Daily asset'}</p><h3>${drop.title}</h3><p>${assetIntelCopy[previewMode] || drop.lore}</p><div class="asset-intel-grid"><span>${drop.collection}</span><span>Drop ${drop.sequence} / 55</span><span>${$('placement').value}</span></div>`;
    preview.innerHTML = '';
    if (!source) {
      preview.innerHTML = emptyPreview(previewMode);
      $('downloadActive').disabled = true;
      return;
    }
    const img = new Image();
    img.src = source;
    img.alt = `${title()} ${previewLabels[previewMode] || 'daily drop asset'}`;
    preview.appendChild(img);
    $('downloadActive').disabled = false;
  };
  const selectPreview = (mode) => {
    previewMode = mode;
    document.querySelectorAll('[data-preview]').forEach((button) => {
      button.classList.toggle('active', button.dataset.preview === mode);
    });
    renderPreview();
  };
  const clearAssets = () => {
    image = '';
    referenceImage = '';
    placementImage = '';
    packImage = '';
    loreCard = '';
    styleCard = '';
    renderToken = '';
    provider = '';
    $('publishBtn').disabled = true;
    $('renderVideo').disabled = true;
    $('generateReference').disabled = true;
    $('generatePlacement').disabled = true;
    $('generatePack').disabled = true;
    $('buildCards').disabled = true;
    $('generateFullKit').disabled = true;
    $('downloadStencil').disabled = true;
    $('downloadMirror').disabled = true;
    $('downloadActive').disabled = true;
    $('stencilUpload').value = '';
    $('publishBtn').textContent = 'Publish artist pack';
    document.querySelectorAll('.preflight').forEach((checkbox) => { checkbox.checked = false; });
    $('providerMeta').textContent = 'Awaiting generation';
    $('qualityMeta').textContent = 'High quality · PNG';
    setFallbackPrompt('', '');
    setStep(0);
    selectPreview('stencil');
  };
  const applyDrop = (resetAssets = true) => {
    const drop = activeDrop();
    if (!drop) return;
    if (resetAssets) clearAssets();
    $('title').value = drop.title;
    $('collection').value = drop.collection;
    $('idea').value = drop.concept;
    $('style').value = drop.style;
    $('placement').value = drop.placement;
    $('composition').value = drop.composition;
    $('lineWeight').value = 'Balanced transfer-ready hierarchy';
    $('detail').value = drop.collection_slug === 'dark-realism'
      ? 'Dark realism · deep value separation, aged texture and cinematic focal detail'
      : drop.style.toLowerCase().includes('realism')
        ? 'High realism · anatomical accuracy, material texture and controlled skin breaks'
        : 'High detail with controlled open skin breaks';
    $('lore').value = drop.lore;
    $('dropNumber').textContent = `Drop ${drop.sequence} / 55`;
    $('dropDate').textContent = drop.display_date;
    $('dropCollection').textContent = drop.collection;
    message(`Scheduled metadata loaded for ${drop.title}.`);
  };
  const dropCaption = () => {
    const drop = activeDrop();
    return [
      `BEYOND TATTOO · DAILY STENCIL DROP ${drop.sequence}/55`,
      `${drop.title} — ${drop.display_date}`,
      drop.collection,
      $('lore').value.trim(),
      'Printer ready · Clean lines · Easy transfer · Studio approved',
      '#BeyondTattoo #DailyStencil #TattooStencil #TattooArtist',
    ].filter(Boolean).join('\n\n');
  };
  const download = (blob, name) => {
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = name;
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1600);
  };
  const downloadDataUrl = async (dataUrl, name) => {
    if (!dataUrl) return;
    download(await (await fetch(dataUrl)).blob(), name);
  };
  const readImageFile = async (file) => {
    if (!file) throw new Error('Choose a PNG, JPG or WebP image.');
    if (file.size > 15 * 1024 * 1024) throw new Error('Upload an image smaller than 15 MB.');
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('The selected image could not be read.'));
      reader.readAsDataURL(file);
    });
  };
  const loadImage = (source) => new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('The generated image could not be prepared.'));
    img.src = source;
  });
  const drawBitAtom = (ctx, x, y, size, alpha = .16) => {
    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.strokeStyle = '#111';
    ctx.fillStyle = '#111';
    ctx.lineWidth = Math.max(1, size * .035);
    ctx.translate(x, y);
    for (const rotation of [0, Math.PI / 3, -Math.PI / 3]) {
      ctx.save();
      ctx.rotate(rotation);
      ctx.beginPath();
      ctx.ellipse(0, 0, size * .5, size * .16, 0, 0, Math.PI * 2);
      ctx.stroke();
      ctx.restore();
    }
    ctx.beginPath();
    ctx.arc(0, 0, size * .08, 0, Math.PI * 2);
    ctx.fill();
    ctx.font = `900 ${Math.max(11, size * .16)}px Arial`;
    ctx.textAlign = 'center';
    ctx.fillText('bit$', 0, size * .68);
    ctx.restore();
  };
  const watermarkStencilDataUrl = async (source) => {
    const stencil = await loadImage(source);
    const canvas = document.createElement('canvas');
    canvas.width = stencil.naturalWidth || stencil.width || 1024;
    canvas.height = stencil.naturalHeight || stencil.height || 1536;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(stencil, 0, 0, canvas.width, canvas.height);
    const size = Math.min(canvas.width, canvas.height) * .16;
    drawBitAtom(ctx, canvas.width - size * .7, size * .62, size, .13);
    drawBitAtom(ctx, size * .7, canvas.height - size * .64, size, .1);
    return canvas.toDataURL('image/png');
  };
  const fitCanvasText = (ctx, text, maxWidth, start, min = 22) => {
    let size = start;
    while (size > min) {
      ctx.font = `900 ${size}px Georgia, serif`;
      if (ctx.measureText(text).width <= maxWidth) break;
      size -= 2;
    }
    return size;
  };
  const wrapCanvasText = (ctx, text, x, y, maxWidth, lineHeight, maxLines = 8) => {
    const words = String(text || '').trim().split(/\s+/);
    let line = '';
    let lineNumber = 0;
    for (let index = 0; index < words.length && lineNumber < maxLines; index += 1) {
      const candidate = line ? `${line} ${words[index]}` : words[index];
      if (ctx.measureText(candidate).width > maxWidth && line) {
        ctx.fillText(line, x, y + lineNumber * lineHeight);
        line = words[index];
        lineNumber += 1;
      } else {
        line = candidate;
      }
    }
    if (line && lineNumber < maxLines) ctx.fillText(line, x, y + lineNumber * lineHeight);
    return y + (lineNumber + 1) * lineHeight;
  };
  const cardCanvas = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1536;
    const ctx = canvas.getContext('2d');
    const background = ctx.createRadialGradient(512, 510, 80, 512, 760, 920);
    background.addColorStop(0, '#251b2a');
    background.addColorStop(.48, '#0b0b0d');
    background.addColorStop(1, '#020203');
    ctx.fillStyle = background;
    ctx.fillRect(0, 0, 1024, 1536);
    ctx.strokeStyle = '#d8ab52';
    ctx.lineWidth = 5;
    ctx.strokeRect(34, 34, 956, 1468);
    ctx.strokeStyle = 'rgba(216,171,82,.48)';
    ctx.lineWidth = 1;
    ctx.strokeRect(49, 49, 926, 1438);
    ctx.textAlign = 'center';
    return {canvas, ctx};
  };
  const drawCardArtwork = async (ctx, source, x, y, width, height) => {
    if (!source) return;
    const artwork = await loadImage(source);
    const scale = Math.max(width / artwork.width, height / artwork.height);
    const sourceWidth = width / scale;
    const sourceHeight = height / scale;
    const sourceX = Math.max(0, (artwork.width - sourceWidth) / 2);
    const sourceY = Math.max(0, (artwork.height - sourceHeight) / 2);
    ctx.save();
    ctx.beginPath();
    ctx.rect(x, y, width, height);
    ctx.clip();
    ctx.drawImage(artwork, sourceX, sourceY, sourceWidth, sourceHeight, x, y, width, height);
    ctx.restore();
    ctx.strokeStyle = '#d8ab52';
    ctx.lineWidth = 3;
    ctx.strokeRect(x, y, width, height);
  };
  const composePackImage = async (source) => {
    const drop = activeDrop();
    const base = await loadImage(source);
    const canvas = document.createElement('canvas');
    canvas.width = 1080;
    canvas.height = 1080;
    const ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    const background = ctx.createRadialGradient(540, 390, 120, 540, 540, 760);
    background.addColorStop(0, '#312419');
    background.addColorStop(.56, '#09090b');
    background.addColorStop(1, '#020203');
    ctx.fillStyle = background;
    ctx.fillRect(0, 0, 1080, 1080);
    const maxW = 650;
    const maxH = 900;
    const scale = Math.min(maxW / base.width, maxH / base.height);
    const drawW = base.width * scale;
    const drawH = base.height * scale;
    const drawX = 64 + (maxW - drawW) / 2;
    const drawY = 72 + (maxH - drawH) / 2;
    ctx.save();
    ctx.shadowColor = 'rgba(0,0,0,.62)';
    ctx.shadowBlur = 34;
    ctx.shadowOffsetY = 18;
    ctx.drawImage(base, drawX, drawY, drawW, drawH);
    ctx.restore();
    ctx.strokeStyle = '#d8ab52';
    ctx.lineWidth = 5;
    ctx.strokeRect(28, 28, 1024, 1024);
    ctx.strokeStyle = 'rgba(216,171,82,.52)';
    ctx.lineWidth = 1;
    ctx.strokeRect(44, 44, 992, 992);
    ctx.textAlign = 'left';
    ctx.fillStyle = '#e8c77b';
    ctx.font = '800 22px Arial';
    ctx.letterSpacing = '8px';
    ctx.fillText('BEYOND TATTOO', 750, 118);
    ctx.letterSpacing = '0px';
    ctx.fillStyle = '#f3d58d';
    const titleSize = fitCanvasText(ctx, drop.title.toUpperCase(), 286, 40, 25);
    ctx.font = `900 ${titleSize}px Georgia, serif`;
    wrapCanvasText(ctx, drop.title.toUpperCase(), 750, 188, 270, titleSize * 1.08, 3);
    ctx.fillStyle = '#caa34f';
    ctx.font = '800 19px Arial';
    wrapCanvasText(ctx, drop.collection.toUpperCase(), 750, 330, 270, 26, 3, 'left');
    ctx.strokeStyle = 'rgba(216,171,82,.5)';
    ctx.beginPath();
    ctx.moveTo(750, 414);
    ctx.lineTo(1010, 414);
    ctx.stroke();
    ctx.fillStyle = '#f1d28a';
    ctx.font = '900 23px Arial';
    ctx.fillText(drop.release_date, 750, 468);
    ctx.fillText(`DROP ${drop.sequence} / 55`, 750, 505);
    ctx.fillStyle = '#d0c2aa';
    ctx.font = '700 19px Arial';
    wrapCanvasText($('placement').value.toUpperCase(), 750, 566, 260, 27, 3, 'left');
    const features = ['PRINTER READY', 'CLEAN LINES', 'EASY TRANSFER', 'STUDIO APPROVED'];
    features.forEach((feature, index) => {
      const x = 100 + index * 236;
      ctx.strokeStyle = '#d8ab52';
      ctx.beginPath();
      ctx.arc(x, 996, 28, 0, Math.PI * 2);
      ctx.stroke();
      ctx.fillStyle = '#e8c77b';
      ctx.textAlign = 'center';
      ctx.font = '800 18px Arial';
      ctx.fillText(['▣', '✦', '◇', '✓'][index], x, 1003);
      ctx.font = '800 15px Arial';
      ctx.fillText(feature, x, 1042);
    });
    drawBitAtom(ctx, 984, 842, 76, .22);
    return canvas.toDataURL('image/png');
  };
  const composeLoreCard = async () => {
    const drop = activeDrop();
    const {canvas, ctx} = cardCanvas();
    ctx.fillStyle = '#e8c77b';
    ctx.font = '800 25px Arial';
    ctx.fillText('BEYOND TATTOO', 512, 92);
    ctx.font = '900 58px Georgia, serif';
    ctx.fillText(drop.title.toUpperCase(), 512, 168);
    ctx.fillStyle = '#a77ac4';
    ctx.font = '800 23px Arial';
    ctx.fillText(drop.collection.toUpperCase(), 512, 212);
    await drawCardArtwork(ctx, referenceImage || packImage || image, 112, 252, 800, 520);
    ctx.fillStyle = '#f0d28e';
    ctx.font = '900 30px Georgia, serif';
    ctx.fillText('STENCIL LORE', 512, 830);
    ctx.fillStyle = '#e8e0d3';
    ctx.font = '500 24px Georgia, serif';
    wrapCanvasText(ctx, $('lore').value.trim() || drop.lore, 512, 882, 760, 38, 7);
    ctx.strokeStyle = 'rgba(216,171,82,.65)';
    ctx.beginPath();
    ctx.moveTo(150, 1172);
    ctx.lineTo(874, 1172);
    ctx.stroke();
    ctx.fillStyle = '#a77ac4';
    ctx.font = '900 21px Arial';
    ctx.fillText('WHAT’S INCLUDED', 512, 1220);
    ctx.fillStyle = '#e8e0d3';
    ctx.font = '700 20px Arial';
    ['Reference artwork', 'Printer-ready stencil', 'Placement mockup', 'Premium packaging', 'Information + style cards'].forEach((item, index) => {
      ctx.fillText(`◆  ${item}`, 512, 1264 + index * 34);
    });
    ctx.fillStyle = '#d8ab52';
    ctx.font = '900 23px Arial';
    ctx.fillText(`${drop.sequence} / 55  ·  ${drop.release_date}`, 512, 1464);
    return canvas.toDataURL('image/png');
  };
  const composeStyleCard = async () => {
    const drop = activeDrop();
    const {canvas, ctx} = cardCanvas();
    ctx.fillStyle = '#e8c77b';
    ctx.font = '800 25px Arial';
    ctx.fillText('BEYOND TATTOO', 512, 92);
    ctx.fillStyle = '#a77ac4';
    ctx.font = '800 22px Arial';
    ctx.fillText(drop.collection.toUpperCase(), 512, 137);
    ctx.fillStyle = '#f1d28a';
    const titleSize = fitCanvasText(ctx, drop.title.toUpperCase(), 840, 68, 34);
    ctx.font = `900 ${titleSize}px Georgia, serif`;
    ctx.fillText(drop.title.toUpperCase(), 512, 222);
    await drawCardArtwork(ctx, referenceImage || image, 178, 270, 668, 580);
    ctx.textAlign = 'left';
    const rows = [
      ['DESIGN', drop.title.toUpperCase()],
      ['STYLE', $('style').value.toUpperCase()],
      ['PLACEMENT', $('placement').value.toUpperCase()],
      ['RELEASE', `${drop.sequence} / 55`],
      ['DATE', drop.release_date],
    ];
    rows.forEach(([label, value], index) => {
      const y = 930 + index * 82;
      ctx.fillStyle = '#d8ab52';
      ctx.font = '900 20px Arial';
      ctx.fillText(label, 142, y);
      ctx.fillStyle = '#eee6d8';
      ctx.font = '700 22px Arial';
      ctx.fillText(value, 348, y);
      ctx.strokeStyle = 'rgba(216,171,82,.25)';
      ctx.beginPath();
      ctx.moveTo(142, y + 24);
      ctx.lineTo(882, y + 24);
      ctx.stroke();
    });
    ctx.textAlign = 'center';
    ctx.fillStyle = '#d8ab52';
    ctx.font = '900 24px Arial';
    ctx.fillText('55 DAYS  ·  55 MASTERPIECES', 512, 1452);
    return canvas.toDataURL('image/png');
  };
  const buildLocalCards = async () => {
    loreCard = await composeLoreCard();
    styleCard = await composeStyleCard();
    selectPreview('lore');
  };
  const show = async (data) => {
    image = await watermarkStencilDataUrl(data.image);
    referenceImage = '';
    placementImage = '';
    packImage = '';
    loreCard = '';
    styleCard = '';
    renderToken = data.render_token || '';
    provider = data.provider || '';
    setFallbackPrompt('', '');
    $('publishBtn').disabled = false;
    $('publishBtn').textContent = 'Publish artist pack';
    $('renderVideo').disabled = !renderToken;
    $('generateReference').disabled = false;
    $('generatePlacement').disabled = false;
    $('generatePack').disabled = false;
    $('buildCards').disabled = false;
    $('generateFullKit').disabled = false;
    $('downloadStencil').disabled = false;
    $('downloadMirror').disabled = false;
    $('downloadActive').disabled = false;
    document.querySelectorAll('.preflight').forEach((checkbox) => { checkbox.checked = false; });
    $('providerMeta').textContent = `${data.provider === 'openai' ? 'OpenAI' : data.provider} · ${data.model}`;
    $('qualityMeta').textContent = `${data.quality || 'high'} quality · ${data.size || '1024×1536'} · PNG`;
    selectPreview('stencil');
    setStep(1);
  };

  const runPaidStencilGeneration = async () => {
    const idea = $('idea').value.trim();
    if (idea.length < 8) {
      message('Describe the stencil concept in a little more detail.', true);
      return;
    }
    $('generate').disabled = true;
    $('generatePaidFallback').disabled = true;
    $('publishBtn').disabled = true;
    $('renderVideo').disabled = true;
    setStep(0);
    message('Paid image API fallback is creating the high-quality transfer master…');
    try {
      const response = await fetch('api/generate-stencil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(generationPayload()),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        setFallbackPrompt(data.fallback_prompt || buildMetaStencilPrompt(), 'Image APIs failed. Free image fallback prompt is ready below.');
        throw new Error(data.error || 'Image generation failed.');
      }
      await show(data);
      message('Stencil generated. Review the linework, then publish or export its Remotion reel.');
    } catch (error) {
      message(error.message || 'Image generation failed.', true);
    } finally {
      $('generate').disabled = false;
      $('generatePaidFallback').disabled = false;
    }
  };
  $('generate').onclick = async () => {
    const idea = $('idea').value.trim();
    if (idea.length < 8) {
      message('Describe the stencil concept in a little more detail.', true);
      return;
    }
    if (!$('preferFreeFallback').checked) {
      await runPaidStencilGeneration();
      return;
    }
    const source = fallbackSources[$('fallbackProvider').value] || fallbackSources.meta;
    setFallbackPrompt(buildMetaStencilPrompt(), `${source.label} prompt is ready. Generate there first, then upload the result here.`);
  };
  $('generatePaidFallback').onclick = runPaidStencilGeneration;

  const stageUploadedStencil = async (file) => {
    message(`Staging ${activeDrop().title} for pack, publish and video tools…`);
    const source = await readImageFile(file);
    const response = await fetch('api/generate-stencil.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
      body: JSON.stringify({...generationPayload(), stencil_image: source}),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'The uploaded stencil could not be staged.');
    await show(data);
    message(`${activeDrop().title} uploaded. Schedule metadata is ready for pack generation, publishing and Remotion.`);
  };
  $('stencilUpload').onchange = async () => {
    const file = $('stencilUpload').files[0];
    if (!file) return;
    try {
      await stageUploadedStencil(file);
    } catch (error) {
      message(error.message || 'The uploaded stencil could not be staged.', true);
      $('stencilUpload').value = '';
    }
  };
  $('fallbackAssetUpload').onchange = async () => {
    const file = $('fallbackAssetUpload').files[0];
    if (!file) return;
    try {
      if (previewMode === 'stencil') {
        await stageUploadedStencil(file);
      } else {
        const source = await readImageFile(file);
        if (previewMode === 'reference') referenceImage = source;
        if (previewMode === 'placement') placementImage = source;
        if (previewMode === 'pack') packImage = source;
        if (previewMode === 'lore') loreCard = source;
        if (previewMode === 'style') styleCard = source;
        $('downloadActive').disabled = false;
        $('providerMeta').textContent = `Free image fallback upload · ${previewLabels[previewMode]}`;
        $('qualityMeta').textContent = 'source quality · uploaded image';
        renderPreview();
        message(`${previewLabels[previewMode][0].toUpperCase()}${previewLabels[previewMode].slice(1)} loaded from free image fallback.`);
      }
      $('fallbackAssetUpload').value = '';
    } catch (error) {
      message(error.message || 'The fallback image could not be loaded.', true);
      $('fallbackAssetUpload').value = '';
    }
  };

  const requestAiAsset = async (assetType) => {
    const drop = activeDrop();
    const response = await fetch('api/generate-tattoo-pack.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
      body: JSON.stringify({
        asset_type: assetType,
        stencil_image: image,
        title: drop.title,
        collection: drop.collection,
        release_date: drop.release_date,
        sequence: drop.sequence,
        pack_style: $('packStyle').value,
        concept: $('idea').value.trim(),
        placement: $('placement').value,
        style: $('style').value,
        detail: $('detail').value,
      }),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
      setFallbackPrompt(data.fallback_prompt || buildMetaAssetPrompt(assetType), `${previewLabels[assetType][0].toUpperCase()}${previewLabels[assetType].slice(1)} APIs failed. Free image fallback prompt is ready below.`);
      throw new Error(data.error || `${previewLabels[assetType]} generation failed.`);
    }
    const result = assetType === 'pack' ? await composePackImage(data.image) : data.image;
    if (assetType === 'reference') referenceImage = result;
    if (assetType === 'placement') placementImage = result;
    if (assetType === 'pack') packImage = result;
    $('providerMeta').textContent = `${data.provider === 'openai' ? 'OpenAI' : data.provider} · ${data.model} · ${previewLabels[assetType]}`;
    $('qualityMeta').textContent = `${data.quality || 'high'} quality · ${assetType === 'pack' ? 'IG-square package post · ' : ''}PNG`;
    selectPreview(assetType);
    return result;
  };
  const runAiAsset = async (assetType, buttonId) => {
    if (!image) return;
    if ($('preferFreeFallback').checked) {
      const source = fallbackSources[$('fallbackProvider').value] || fallbackSources.meta;
      selectPreview(assetType);
      setFallbackPrompt(buildMetaAssetPrompt(assetType), `${source.label} prompt is ready for ${previewLabels[assetType]}. Generate there, then upload the result for this tab.`);
      return;
    }
    const button = $(buttonId);
    button.disabled = true;
    message(`Creating ${previewLabels[assetType]} for ${activeDrop().title}…`);
    try {
      await requestAiAsset(assetType);
      message(`${previewLabels[assetType][0].toUpperCase()}${previewLabels[assetType].slice(1)} generated for the scheduled drop.`);
    } catch (error) {
      message(error.message || `${previewLabels[assetType]} generation failed.`, true);
    } finally {
      button.disabled = false;
    }
  };
  $('generateReference').onclick = () => runAiAsset('reference', 'generateReference');
  $('generatePlacement').onclick = () => runAiAsset('placement', 'generatePlacement');
  $('generatePack').onclick = () => runAiAsset('pack', 'generatePack');
  $('buildCards').onclick = async () => {
    if (!image) return;
    $('buildCards').disabled = true;
    message('Composing the exact-text lore and design cards locally…');
    try {
      await buildLocalCards();
      message('Lore and style cards built with the selected schedule metadata.');
    } catch (error) {
      message(error.message || 'The collection cards could not be built.', true);
    } finally {
      $('buildCards').disabled = false;
    }
  };
  $('generateFullKit').onclick = async () => {
    if (!image) return;
    if ($('preferFreeFallback').checked) {
      const source = fallbackSources[$('fallbackProvider').value] || fallbackSources.meta;
      selectPreview('reference');
      setFallbackPrompt(buildMetaAssetPrompt('reference'), `${source.label} reference prompt is ready. Work through reference, placement and pack one tab at a time, then build the cards locally.`);
      return;
    }
    const buttons = ['generateReference', 'generatePlacement', 'generatePack', 'buildCards', 'generateFullKit'].map($);
    buttons.forEach((button) => { button.disabled = true; });
    try {
      message('1/5 · Creating the high-detail reference artwork…');
      await requestAiAsset('reference');
      message('2/5 · Creating the anatomy-aware placement mockup…');
      await requestAiAsset('placement');
      message('3/5 · Creating the premium scheduled packaging…');
      await requestAiAsset('pack');
      message('4–5/5 · Building the exact-text lore and style cards…');
      await buildLocalCards();
      message('Complete six-piece daily stencil kit is ready to review and publish.');
    } catch (error) {
      message(error.message || 'The complete daily kit stopped before all assets were finished.', true);
    } finally {
      buttons.forEach((button) => { button.disabled = false; });
    }
  };

  $('downloadStencil').onclick = () => downloadDataUrl(image, `beyond-tattoo-${slug(title())}-stencil.png`);
  $('downloadActive').onclick = () => {
    const names = {
      reference: 'reference-artwork',
      stencil: 'stencil',
      placement: 'placement-mockup',
      pack: 'premium-pack',
      lore: 'lore-card',
      style: 'style-card',
    };
    downloadDataUrl(assetSource(previewMode), `beyond-tattoo-${slug(title())}-${names[previewMode] || 'asset'}.png`);
  };
  $('downloadMirror').onclick = async () => {
    if (!image) return;
    try {
      const source = await loadImage(image);
      const canvas = document.createElement('canvas');
      canvas.width = source.naturalWidth || 1024;
      canvas.height = source.naturalHeight || 1536;
      const ctx = canvas.getContext('2d');
      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
      ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
      downloadDataUrl(canvas.toDataURL('image/png'), `beyond-tattoo-${slug(title())}-mirrored-transfer.png`);
      message('Mirrored thermal-transfer PNG downloaded.');
    } catch (error) {
      message(error.message || 'The mirrored transfer could not be prepared.', true);
    }
  };

  $('generateLore').onclick = () => {
    const drop = activeDrop();
    $('lore').value = `${drop.lore} The composition is built around ${drop.concept.toLowerCase()} Its scheduled place as drop ${drop.sequence} of 55 connects the piece to a season-long progression from sacred iconography through ancient, Japanese and dark-realism traditions.`;
    message(`Lore prepared for ${drop.title}.`);
  };
  $('copyCaption').onclick = async () => {
    try {
      await navigator.clipboard.writeText(dropCaption());
      message('Scheduled drop caption copied.');
    } catch {
      message('Clipboard access was unavailable. Select and copy the stencil lore manually.', true);
    }
  };
  $('copyFallbackPrompt').onclick = async () => {
    const source = fallbackSources[$('fallbackProvider').value] || fallbackSources.meta;
    if (!fallbackPrompt.value.trim()) setFallbackPrompt(buildMetaStencilPrompt(), `${source.label} fallback prompt is ready below.`);
    try {
      await navigator.clipboard.writeText(fallbackPrompt.value);
      message(`${source.label} fallback prompt copied. Generate there, then upload the result above.`);
    } catch {
      fallbackPrompt.focus();
      fallbackPrompt.select();
      message('Clipboard access was unavailable. The fallback prompt is selected for copying.', true);
    }
  };
  $('openFallbackSource').onclick = () => {
    if (!fallbackPrompt.value.trim()) setFallbackPrompt(buildMetaStencilPrompt(), '');
    const source = fallbackSources[$('fallbackProvider').value] || fallbackSources.meta;
    window.open(source.url, '_blank', 'noopener,noreferrer');
    message(`${source.label} opened. Paste the fallback prompt, save the image, then upload it here.`);
  };

  $('clear').onclick = () => {
    clearAssets();
    message('');
  };

  $('publishBtn').onclick = async () => {
    if (!image) return;
    if (![...document.querySelectorAll('.preflight')].every((checkbox) => checkbox.checked)) {
      message('Complete all four artist preflight checks before publishing.', true);
      return;
    }
    const drop = activeDrop();
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
          release_date: drop.release_date,
          sequence: drop.sequence,
          lore: $('lore').value.trim(),
          svg,
          png: image,
          reference_png: referenceImage,
          placement_png: placementImage,
          pack_png: packImage,
          lore_card_png: loreCard,
          style_card_png: styleCard,
          seed: provider ? `${provider}-image` : 'free-fallback-upload',
        }),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Publishing failed.');
      setStep(2);
      const completed = [referenceImage, image, placementImage, packImage, loreCard, styleCard].filter(Boolean).length;
      message(packImage
        ? `Artist pack published with ${completed} of 6 daily-drop assets. The storefront package stage uses the premium pack image and the daily card keeps the clean stencil.`
        : `Artist pack published with ${completed} of 6 assets and the clean stencil remains the storefront preview. Generate packaging before the next publish to upgrade the package stage.`);
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
        message('Generating paid server narration for the tattoo reel…');
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
      const date = activeDrop().display_date;
      const blob = await renderer.render({
        props: {
          mainArtwork: image,
          studioTransfer: image,
          packImage: packImage || '',
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
  $('dropSchedule').addEventListener('change', () => applyDrop(true));
  document.querySelectorAll('[data-preview]').forEach((button) => {
    button.addEventListener('click', () => selectPreview(button.dataset.preview));
  });
  applyDrop(false);
})();
</script>
</body>
</html>
