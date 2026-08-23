<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/beyond-tattoo/includes/library-catalog.php';

$csrf = Auth::csrf();
$drops = [];
$sequence = 0;
$uploadRoot = dirname(__DIR__, 3) . '/beyond-tattoo/uploads/stencil-library';
$bundledRoot = dirname(__DIR__, 3) . '/beyond-tattoo/assets/stencils';
foreach (bt_library_collections() as $collectionSlug => $collection) {
    foreach ($collection['stencils'] as $collectionIndex => [$title, $releaseDate]) {
        $sequence++;
        $slug = strtolower(trim((string)(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? ''), '-'));
        $folderName = sprintf('%02d-%s', $collectionIndex + 1, $slug);
        $folder = $uploadRoot . '/' . $collectionSlug . '/' . $folderName;
        $bundledFolder = $bundledRoot . '/' . $collectionSlug . '/' . $folderName;
        $metadataFile = is_file($folder . '/metadata.json') ? $folder . '/metadata.json' : $bundledFolder . '/metadata.json';
        $metadata = is_file($metadataFile) ? json_decode((string)file_get_contents($metadataFile), true) : [];
        $publicationStatus = is_array($metadata) ? strtolower(trim((string)($metadata['status'] ?? 'draft'))) : 'draft';
        $drops[] = [
            'sequence' => $sequence,
            'title' => $title,
            'release_date' => $releaseDate,
            'collection' => $collection['name'],
            'collection_slug' => $collectionSlug,
            'assets' => [
                'preview' => is_file($folder . '/preview-watermarked.png') || is_file($bundledFolder . '/preview-watermarked.png'),
                'stencil' => is_file($folder . '/stencil-print-ready.png') || is_file($bundledFolder . '/stencil-print-ready.png'),
                'transfer' => is_file($folder . '/studio-transfer-template.png') || is_file($bundledFolder . '/studio-transfer-template.png'),
                'pdf' => is_file($folder . '/stencil-print-ready.pdf') || is_file($bundledFolder . '/stencil-print-ready.pdf'),
                'reference' => is_file($folder . '/reference-artwork.webp') || is_file($bundledFolder . '/reference-artwork.webp'),
                'placement' => is_file($folder . '/placement-mockup.webp') || is_file($bundledFolder . '/placement-mockup.webp'),
                'pack' => is_file($folder . '/premium-packaging.webp') || is_file($bundledFolder . '/premium-packaging.webp'),
                'lore' => is_file($folder . '/lore-card.webp') || is_file($bundledFolder . '/lore-card.webp'),
                'style' => is_file($folder . '/style-card.webp') || is_file($bundledFolder . '/style-card.webp'),
            ],
            'status' => in_array($publicationStatus, ['approved', 'published'], true) ? 'approved' : 'draft',
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Beyond Tattoo · Asset inbox</title>
<style>
:root{--ink:#08090b;--panel:#121214;--line:#303034;--gold:#d8ab52;--muted:#99958d;--green:#78d49a;--red:#ff9ba0}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 0,#392419 0,transparent 34rem),var(--ink);color:#fff;font:15px/1.5 Inter,system-ui,sans-serif}button,input,select{font:inherit}.wrap{width:min(1320px,calc(100% - 36px));margin:auto;padding:34px 0 60px}.top{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:24px}.eyebrow{color:var(--gold);font-size:11px;font-weight:950;letter-spacing:.17em;text-transform:uppercase}h1{margin:7px 0;font:700 clamp(36px,6vw,66px)/.96 Georgia,serif}.lead{max-width:780px;margin:0;color:#bbb5ab}.back{color:#e7ca8f;text-decoration:none;font-weight:900}.grid{display:grid;grid-template-columns:minmax(310px,.72fr) minmax(560px,1.28fr);gap:20px}.panel{padding:22px;border:1px solid var(--line);border-radius:22px;background:rgba(18,18,20,.95);box-shadow:0 24px 70px #0008}.panel h2{margin:0 0 15px}.field{margin-bottom:14px}.field[hidden]{display:none}.field label{display:block;margin-bottom:6px;font-weight:850}.field small{display:block;margin-top:5px;color:var(--muted)}.field input,.field select{width:100%;padding:12px;border:1px solid #3a3a3e;border-radius:11px;background:#0c0d0f;color:#fff}.check{display:flex;align-items:center;gap:9px;margin:10px 0;color:#ddd8ce;font-weight:800}.check input{width:18px;height:18px;accent-color:var(--gold)}.btn{width:100%;padding:13px 16px;border:0;border-radius:12px;background:linear-gradient(135deg,#d8ab52,#b78027);color:#090a0b;font-weight:950;cursor:pointer}.btn:disabled{opacity:.45;cursor:wait}.note{margin:15px 0;padding:12px;border:1px solid #594725;border-radius:12px;background:#211a10;color:#dec48d}.note strong{color:#fff}.progress{height:10px;margin-top:15px;overflow:hidden;border-radius:999px;background:#29292d}.progress span{display:block;width:0;height:100%;background:linear-gradient(90deg,#b78027,#f0cf87);transition:width .18s}.status{min-height:46px;margin:10px 0 0;color:#d9bd83;font-weight:750}.status.error{color:var(--red)}.summary{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:14px}.summary div{padding:12px;border:1px solid #343438;border-radius:12px;background:#0d0e10}.summary b{display:block;color:var(--gold);font-size:22px}.summary span{color:var(--muted);font-size:11px;text-transform:uppercase}.table-wrap{max-height:760px;overflow:auto;border:1px solid #303034;border-radius:14px}table{width:100%;border-collapse:collapse}th,td{padding:10px 11px;border-bottom:1px solid #29292d;text-align:left;vertical-align:top}th{position:sticky;top:0;z-index:2;background:#1c1c1f;color:#d9c28f;font-size:11px;text-transform:uppercase}td:first-child{color:var(--gold);font-weight:950}.drop-title{font-weight:850}.drop-meta,.file-name{color:#8f8d88;font-size:12px}.mapped-file{display:block;margin-bottom:4px;color:#cbc6bd}.mapped-file b{color:#e7ca8f}.asset-state{display:inline-flex;padding:4px 7px;border-radius:999px;background:#252529;color:#aaa;font-size:11px;font-weight:900}.asset-state.ready{background:#183221;color:var(--green)}.asset-state.queued{background:#352a17;color:#e7c778}.asset-state.failed{background:#3b1c20;color:var(--red)}.approve-btn{display:block;margin-top:6px;padding:5px 8px;border:1px solid #69552d;border-radius:8px;background:#211b10;color:#e8ca8c;font-size:10px;font-weight:900;cursor:pointer}.approve-btn:disabled{opacity:.38;cursor:not-allowed}.approve-btn.published{border-color:#285b39;background:#14271a;color:var(--green)}@media(max-width:900px){.top{display:block}.back{display:inline-block;margin-top:14px}.grid{grid-template-columns:1fr}.summary{grid-template-columns:1fr 1fr}.wrap{width:min(100% - 22px,700px);padding-top:22px}}
</style>
<style>
.inbox{margin-bottom:20px;border-color:#674f27;background:linear-gradient(135deg,rgba(41,31,17,.96),rgba(18,18,20,.96))}.inbox-grid{display:grid;grid-template-columns:1fr auto;align-items:end;gap:16px}.inbox .field{margin:0}.inbox .btn{width:auto;min-width:220px}.batch-id{margin:9px 0 0;color:var(--muted);font:12px ui-monospace,monospace}@media(max-width:900px){.inbox-grid{grid-template-columns:1fr}.inbox .btn{width:100%}}
</style>
</head>
<body>
<main class="wrap">
  <header class="top"><div><div class="eyebrow">Beyond Studio · Beyond Tattoo</div><h1>Asset inbox</h1><p class="lead">Upload everything you have now, keep every original private, and sort it into drops, packs, roles, and categories later.</p></div><a class="back" href="stencil-library.php">← Generator &amp; publisher</a></header>
  <section class="panel inbox">
    <h2>Unsorted asset inbox</h2>
    <p class="lead">Stage any number of mixed tattoo assets—50, 100, or more. Nothing is assigned or published; filenames and originals are preserved for a later sorting pass.</p>
    <div class="inbox-grid">
      <div class="field"><label for="inboxFiles">Choose unsorted assets</label><input id="inboxFiles" type="file" accept=".png,.jpg,.jpeg,.webp,.gif,.heic,.heif,.svg,.pdf,.zip,.mp4,.mov,image/*,video/mp4,video/quicktime,application/pdf,application/zip" multiple><small>iPhone photos and videos, other images, SVG, PDF, or ZIP · up to 500 files per batch (repeat anytime) · 100 MB maximum each.</small></div>
      <button class="btn" id="uploadInbox" type="button" disabled>Upload to private inbox</button>
    </div>
    <div class="progress" aria-hidden="true"><span id="inboxProgress"></span></div>
    <p class="status" id="inboxStatus" role="status" aria-live="polite">Choose any assets you have so far. You can organize them later.</p>
    <p class="batch-id" id="inboxBatchId"></p>
  </section>
  <section class="grid">
    <article class="panel">
      <h2>Optional: organize assets now</h2>
      <div class="field"><label for="mode">How should assets be assigned?</label><select id="mode"><option value="roleBatch">Numbered files (recommended)</option><option value="exact">Choose one numbered drop</option><option value="auto">Upload organized asset folders</option></select><small>Files named 01, 02, … 55 map directly to the matching drop.</small></div>
      <div class="field" id="dropField" hidden><label for="drop">Numbered drop</label><select id="drop"><?php foreach ($drops as $drop): ?><option value="<?= (int)$drop['sequence'] ?>"><?= str_pad((string)$drop['sequence'], 2, '0', STR_PAD_LEFT) ?> · <?= htmlspecialchars($drop['title']) ?> — <?= htmlspecialchars($drop['collection']) ?></option><?php endforeach; ?></select><small>This is the safest option when a filename is unclear.</small></div>
      <div class="field" id="roleField" hidden><label for="role">Asset type</label><select id="role"><option value="preview">Public preview</option><option value="stencil">Print-ready stencil</option><option value="transfer">Studio transfer template</option><option value="pdf">Printable PDF</option><option value="reference">Reference artwork</option><option value="placement">Placement mockup</option><option value="pack">Premium packaging</option><option value="lore">Lore card</option><option value="style">Style card</option></select></div>
      <div class="field" id="mappingField"><label for="mapping">Number mapping</label><select id="mapping"><option value="smart">Use each filename's number</option><option value="order">Use selection order (Drop 01 onward)</option></select><small>Leading zeros are optional: 1, 01, and 001 all map to Drop 01.</small></div>
      <div class="field"><label for="files" id="filesLabel">Batch upload all assets</label><input id="files" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" multiple><small id="filesHelp">Select previews, stencils, transfers, PDFs, and cards together · 20 MB maximum each.</small></div>
      <label class="check"><input id="watermark" type="checkbox" checked> Apply Beyond Tattoo footer watermark to previews</label>
      <label class="check"><input id="replace" type="checkbox"> Replace assets already uploaded in the selected slots</label>
      <label class="check"><input id="rights" type="checkbox"> I confirm Beyond Tattoo has permission to publish these assets</label>
      <div class="field"><label for="description">Approval description</label><input id="description" maxlength="1200" placeholder="Subject, composition, and realism details"><small>Optional. Applied to the drop whose Approve button you select.</small></div>
      <div class="field"><label for="styleMeta">Style</label><input id="styleMeta" maxlength="180" placeholder="Dark realism, black-and-grey realism…"></div>
      <div class="field"><label for="placementMeta">Recommended placements</label><input id="placementMeta" maxlength="240" placeholder="Upper arm, forearm, calf, back…"></div>
      <div class="field"><label for="difficultyMeta">Difficulty</label><select id="difficultyMeta"><option value="">Use collection default</option><option>Intermediate</option><option>Advanced</option><option>Expert</option></select></div>
      <div class="note"><strong>Mixed assets?</strong> Choose “Upload organized asset folders,” then select one parent folder containing folders such as Previews, Stencils, Transfers, Placement, Lore, and Style. The numbered filename picks the drop; its folder picks the asset type.</div>
      <button class="btn" id="upload" type="button" disabled>Upload mapped assets</button>
      <div class="progress" aria-hidden="true"><span id="progressBar"></span></div>
      <p class="status" id="status" role="status" aria-live="polite">Choose images to preview their scheduled mapping.</p>
    </article>
    <article class="panel">
      <div class="summary"><div><b>55</b><span>Season drops</span></div><div><b id="selectedCount">0</b><span>Selected</span></div><div><b id="mappedCount">0</b><span>Mapped</span></div><div><b id="readyCount">0</b><span>Role ready</span></div><div><b id="approvedCount">0</b><span>Published</span></div></div>
      <div class="table-wrap"><table><thead><tr><th>Drop</th><th>Schedule</th><th>Mapped file</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($drops as $drop): ?><tr data-sequence="<?= (int)$drop['sequence'] ?>"><td><?= str_pad((string)$drop['sequence'], 2, '0', STR_PAD_LEFT) ?></td><td><div class="drop-title"><?= htmlspecialchars($drop['title']) ?></div><div class="drop-meta"><?= htmlspecialchars($drop['collection']) ?> · <?= htmlspecialchars($drop['release_date']) ?></div></td><td class="file-name" data-file>—</td><td><span class="asset-state<?= $drop['assets']['preview'] ? ' ready' : '' ?>" data-state><?= $drop['assets']['preview'] ? 'Ready' : 'Missing' ?></span><button class="approve-btn<?= $drop['status'] === 'approved' ? ' published' : '' ?>" type="button" data-approve><?= $drop['status'] === 'approved' ? 'Published' : 'Approve drop' ?></button></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </article>
  </section>
</main>
<script>
(() => {
  'use strict';
  const csrf = <?= json_encode($csrf) ?>;
  const drops = <?= json_encode($drops, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const roleLabels = {preview:'Preview',stencil:'Stencil',transfer:'Transfer',pdf:'PDF',reference:'Reference',placement:'Placement',pack:'Packaging',lore:'Lore',style:'Style'};
  const $ = (id) => document.getElementById(id);
  const rows = new Map([...document.querySelectorAll('[data-sequence]')].map((row) => [Number(row.dataset.sequence), row]));
  const runtimeAssets = Object.fromEntries(drops.map((drop) => [drop.sequence, {...drop.assets}]));
  const runtimeStatus = Object.fromEntries(drops.map((drop) => [drop.sequence, drop.status]));
  let queue = [];
  let inboxBatchId = '';
  let inboxCompleted = 0;
  const syncInbox = () => {
    const count = $('inboxFiles').files.length;
    const valid = count > 0 && count <= 500;
    $('uploadInbox').disabled = !valid;
    $('inboxStatus').textContent = count === 0
      ? 'Choose any assets you have so far. You can organize them later.'
      : (valid ? `${count} asset${count === 1 ? '' : 's'} selected. Ready for the private inbox.` : `${count} selected · upload at most 500 files in one batch.`);
    $('inboxStatus').classList.toggle('error', count > 500);
    $('inboxProgress').style.width = '0%';
    $('inboxBatchId').textContent = '';
    inboxBatchId = '';
    inboxCompleted = 0;
  };
  $('inboxFiles').addEventListener('change', syncInbox);
  $('uploadInbox').addEventListener('click', async () => {
    const files = [...$('inboxFiles').files];
    if (files.length < 1 || files.length > 500) return syncInbox();
    const total = files.length;
    $('uploadInbox').disabled = true;
    try {
      for (const [index, file] of files.entries()) {
        if (index < inboxCompleted) continue;
        $('inboxStatus').textContent = `Uploading ${index + 1} of ${total} · ${file.name}…`;
        const body = new FormData();
        body.append('asset', file, file.name);
        body.append('position', String(index + 1));
        body.append('total_count', String(total));
        if (inboxBatchId) body.append('batch_id', inboxBatchId);
        const response = await fetch('api/upload-tattoo-stencil-batch.php', {method: 'POST', headers: {'X-CSRF-Token': csrf}, body});
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.error || `Asset ${index + 1} failed.`);
        inboxBatchId = data.batch_id;
        inboxCompleted++;
        $('inboxProgress').style.width = `${Math.round((inboxCompleted / total) * 100)}%`;
        $('inboxBatchId').textContent = `Batch: ${inboxBatchId}`;
      }
      $('inboxStatus').textContent = `${inboxCompleted} asset${inboxCompleted === 1 ? '' : 's'} safely uploaded. Leave them unsorted or organize them later.`;
      $('inboxStatus').classList.remove('error');
    } catch (error) {
      $('inboxStatus').textContent = `${inboxCompleted} staged. ${error.message || 'Batch upload failed.'} Keep this selection and retry after checking the server.`;
      $('inboxStatus').classList.add('error');
      $('uploadInbox').disabled = false;
    }
  });
  const normalize = (value) => String(value || '').toLowerCase().replace(/\.[a-z0-9]+$/i, '').replace(/[^a-z0-9]+/g, ' ').trim();
  const status = (message, error = false) => { $('status').textContent = message; $('status').classList.toggle('error', error); };
  const updateRoleStates = () => {
    const role = $('role').value;
    let count = 0;
    drops.forEach((drop) => {
      const ready = !!runtimeAssets[drop.sequence]?.[role];
      if (ready) count++;
      const state = rows.get(drop.sequence).querySelector('[data-state]');
      const queued = queue.filter((item) => item.drop.sequence === drop.sequence);
      if (!queued.length) {
        state.textContent = ready ? 'Ready' : 'Missing';
        state.className = `asset-state${ready ? ' ready' : ''}`;
      } else {
        state.textContent = `${queued.length} queued`;
        state.className = 'asset-state queued';
      }
      const approve = rows.get(drop.sequence).querySelector('[data-approve]');
      const approved = runtimeStatus[drop.sequence] === 'approved';
      approve.textContent = approved ? 'Published' : 'Approve drop';
      approve.classList.toggle('published', approved);
      approve.disabled = !approved && !(runtimeAssets[drop.sequence]?.preview && runtimeAssets[drop.sequence]?.stencil);
    });
    $('readyCount').textContent = String(count);
    $('approvedCount').textContent = String(Object.values(runtimeStatus).filter((value) => value === 'approved').length);
    $('watermark').disabled = $('mode').value !== 'auto' && role !== 'preview';
  };
  const recognizeDrop = (fileName) => {
    const name = normalize(fileName);
    const sequenceMatch = name.match(/(?:^|\s)0*(\d{1,2})(?:\s|$)/);
    if (sequenceMatch) {
      const candidate = drops.find((drop) => drop.sequence === Number(sequenceMatch[1]));
      if (candidate) return candidate;
    }
    if (/\bnio\s+guardian\b/.test(name)) {
      const guardian = drops.find((drop) => drop.title === 'Nio Guardians');
      if (guardian) return guardian;
    }
    return [...drops]
      .sort((a, b) => normalize(b.title).length - normalize(a.title).length)
      .find((drop) => name.includes(normalize(drop.title)) || normalize(drop.title).includes(name)) || null;
  };
  const recognizeRole = (fileName) => {
    const name = normalize(fileName);
    if (/\.pdf$/i.test(fileName)) return 'pdf';
    const rules = [
      ['transfer', /\b(transfers?|thermal|thermofax)\b/],
      ['placement', /\b(placements?|mockups?|mock up|on skin)\b/],
      ['reference', /\b(references?|source artworks?|original artworks?|reference artworks?)\b/],
      ['pack', /\b(packs?|packages?|packaging|premium packaging)\b/],
      ['lore', /\b(lore|story cards?|history cards?)\b/],
      ['style', /\b(styles?|style cards?|style guides?)\b/],
      ['preview', /\b(previews?|watermarks?|watermarked|public)\b/],
      ['stencil', /\b(stencils?|print ready|linework|line art)\b/],
    ];
    return rules.find(([, pattern]) => pattern.test(name))?.[0] || null;
  };
  const findRoleBatchDrop = (fileName, used) => {
    const recognized = recognizeDrop(fileName);
    return recognized && !used.has(recognized.sequence) ? recognized : null;
  };
  const renderQueue = () => {
    drops.forEach((drop) => {
      const items = queue.filter((item) => item.drop.sequence === drop.sequence);
      rows.get(drop.sequence).querySelector('[data-file]').innerHTML = items.length
        ? items.map((item) => `<span class="mapped-file"><b>${roleLabels[item.role]}:</b> ${escapeHtml(item.file.name)}</span>`).join('')
        : '—';
    });
    $('mappedCount').textContent = String(queue.length);
    $('upload').disabled = queue.length === 0;
    $('progressBar').style.width = '0%';
    updateRoleStates();
  };
  const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
  const syncMode = (clearFiles = true) => {
    const mode = $('mode').value;
    $('dropField').hidden = mode !== 'exact';
    $('roleField').hidden = mode === 'auto';
    $('mappingField').hidden = mode !== 'roleBatch';
    $('files').multiple = mode !== 'exact';
    $('files').webkitdirectory = mode === 'auto';
    if (mode === 'auto') $('files').setAttribute('webkitdirectory', '');
    else $('files').removeAttribute('webkitdirectory');
    $('files').accept = mode === 'auto' ? 'image/png,image/jpeg,image/webp,application/pdf' : ($('role').value === 'pdf' ? 'application/pdf' : 'image/png,image/jpeg,image/webp');
    $('filesLabel').textContent = mode === 'auto' ? 'Choose the parent asset folder' : (mode === 'exact' ? 'Choose one asset' : 'Choose numbered files');
    $('filesHelp').textContent = mode === 'auto'
      ? 'Numbered filenames determine drops; folder names determine asset types.'
      : (mode === 'exact' ? 'The selected numbered drop and asset type are used exactly.' : 'Choose the asset type once; each file number determines its drop.');
    if (clearFiles) {
      $('files').value = '';
      queue = [];
      $('selectedCount').textContent = '0';
      renderQueue();
    }
  };
  const mapFiles = () => {
    const mode = $('mode').value;
    const limit = mode === 'auto' ? 495 : (mode === 'exact' ? 1 : 55);
    const allFiles = [...$('files').files];
    const files = allFiles.slice(0, limit);
    queue = [];
    const used = new Set();
    let unrecognized = 0;
    files.forEach((file, index) => {
      let drop = null;
      let role = $('role').value;
      if (mode === 'exact') {
        drop = drops.find((item) => item.sequence === Number($('drop').value)) || null;
      } else if (mode === 'auto') {
        const path = file.webkitRelativePath || file.name;
        drop = recognizeDrop(file.name);
        role = recognizeRole(path);
      } else {
        drop = $('mapping').value === 'order' ? drops[index] : findRoleBatchDrop(file.name, used);
      }
      const slot = drop && role ? `${drop.sequence}:${role}` : '';
      if (!drop || !role || used.has(slot)) {
        unrecognized++;
        return;
      }
      used.add(mode === 'roleBatch' ? drop.sequence : slot);
      queue.push({drop, file, role});
    });
    $('selectedCount').textContent = String(files.length);
    renderQueue();
    if (allFiles.length > limit) status(`Only the first ${limit} selected assets can be mapped.`, true);
    else if (unrecognized) status(`${queue.length} mapped; ${unrecognized} could not be confidently assigned. Check the file number, choose the right asset type, or place mixed files in named asset folders.`, true);
    else status(queue.length ? `${queue.length} asset${queue.length === 1 ? '' : 's'} mapped. Review the table, then upload.` : 'Choose assets to preview their assignments.');
  };
  $('files').addEventListener('change', mapFiles);
  $('mapping').addEventListener('change', mapFiles);
  $('drop').addEventListener('change', mapFiles);
  $('mode').addEventListener('change', () => syncMode());
  $('role').addEventListener('change', () => {
    syncMode();
    status($('mode').value === 'exact' ? 'Choose one asset for the numbered drop.' : 'Choose assets for the selected role.');
  });
  $('upload').addEventListener('click', async () => {
    if (!queue.length) return;
    $('upload').disabled = true;
    let completed = 0;
    let failed = 0;
    for (const item of queue) {
      const row = rows.get(item.drop.sequence);
      const state = row.querySelector('[data-state]');
      state.textContent = 'Uploading…';
      state.className = 'asset-state queued';
      status(`Uploading Drop ${String(item.drop.sequence).padStart(2, '0')} · ${item.drop.title} · ${roleLabels[item.role]}…`);
      const body = new FormData();
      body.append('asset', item.file, item.file.name);
      body.append('sequence', String(item.drop.sequence));
      body.append('role', item.role);
      body.append('watermark', item.role === 'preview' && $('watermark').checked ? '1' : '0');
      body.append('replace', $('replace').checked ? '1' : '0');
      try {
        const response = await fetch('api/upload-tattoo-library-asset.php', {method: 'POST', headers: {'X-CSRF-Token': csrf}, body});
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.error || 'Upload failed.');
        runtimeAssets[item.drop.sequence][item.role] = true;
        runtimeStatus[item.drop.sequence] = 'draft';
        state.textContent = 'Uploaded';
        state.className = 'asset-state ready';
        completed++;
      } catch (error) {
        state.textContent = 'Failed';
        state.className = 'asset-state failed';
        row.querySelector('[data-file]').title = error.message || 'Upload failed.';
        failed++;
      }
      $('progressBar').style.width = `${Math.round(((completed + failed) / queue.length) * 100)}%`;
    }
    queue = [];
    updateRoleStates();
    $('mappedCount').textContent = '0';
    $('upload').disabled = true;
    status(failed ? `${completed} uploaded; ${failed} failed. Hover failed filenames for details.` : `${completed} asset${completed === 1 ? '' : 's'} uploaded and connected to the Beyond Tattoo library.`, failed > 0);
  });
  document.querySelectorAll('[data-approve]').forEach((button) => button.addEventListener('click', async () => {
    const sequence = Number(button.closest('[data-sequence]').dataset.sequence);
    const currentlyApproved = runtimeStatus[sequence] === 'approved';
    if (!currentlyApproved && !$('rights').checked) {
      status('Confirm publishing permission before approving a drop.', true);
      return;
    }
    button.disabled = true;
    const body = new URLSearchParams({
      sequence: String(sequence),
      status: currentlyApproved ? 'draft' : 'approved',
      rights_confirmed: $('rights').checked ? '1' : '0',
      description: $('description').value,
      style: $('styleMeta').value,
      placement: $('placementMeta').value,
      difficulty: $('difficultyMeta').value,
    });
    try {
      const response = await fetch('api/approve-tattoo-library-drop.php', {method: 'POST', headers: {'X-CSRF-Token': csrf, 'Content-Type': 'application/x-www-form-urlencoded'}, body});
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Approval failed.');
      runtimeStatus[sequence] = data.status;
      status(data.message);
    } catch (error) {
      status(error.message || 'Approval failed.', true);
    }
    updateRoleStates();
  }));
  syncMode(false);
  updateRoleStates();
})();
</script>
</body>
</html>
