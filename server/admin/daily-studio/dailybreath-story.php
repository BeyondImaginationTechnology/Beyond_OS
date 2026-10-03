<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/_header.php';
$csrf = Auth::csrf();
?>
<style>
  :root{--story-ink:#f4f5ef;--story-muted:#a6b0a4;--story-line:#ffffff20;--story-panel:#13271d;--story-accent:#c9dc9e;--story-warm:#f0c77c;--story-error:#ffaaa0}
  .story-builder{max-width:1320px;margin:-26px auto 0;padding:28px 24px 70px;color:var(--story-ink)}
  .story-builder *{box-sizing:border-box}.story-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:25px}.story-eyebrow{margin:0 0 9px;color:var(--story-warm);font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase}.story-head h1{margin:0;font:500 clamp(34px,5vw,56px)/1 Georgia,serif}.story-head p:last-child{max-width:670px;margin:12px 0 0;color:var(--story-muted);line-height:1.6}.story-duration{flex:0 0 auto;padding:10px 14px;border:1px solid var(--story-line);border-radius:999px;color:var(--story-accent);font-size:12px;font-weight:850}
  .story-columns{display:grid;grid-template-columns:minmax(290px,.72fr) minmax(0,1.28fr);gap:17px;align-items:start}.story-panel{border:1px solid var(--story-line);border-radius:20px;background:linear-gradient(145deg,rgba(255,255,255,.045),rgba(255,255,255,.018));overflow:hidden}.story-panel-head{padding:16px 18px;border-bottom:1px solid var(--story-line);font-size:14px;font-weight:850}.story-panel-body{padding:18px}.story-field{display:grid;gap:7px;margin-bottom:15px}.story-field label{color:#d8dfd4;font-size:12px;font-weight:850}.story-field small,.story-note{color:var(--story-muted);font-size:11px;line-height:1.55}.story-field textarea,.story-field input,.beat-field textarea{width:100%;padding:11px 12px;border:1px solid #ffffff25;border-radius:11px;background:#0b1b13;color:var(--story-ink);font:13px/1.5 Inter,system-ui,sans-serif}.story-field textarea{min-height:105px;resize:vertical}.story-field textarea.sources-input{min-height:215px}.story-field input:focus,.story-field textarea:focus,.beat-field textarea:focus{outline:2px solid var(--story-accent);outline-offset:1px}.story-action{display:flex;flex-wrap:wrap;gap:9px}.story-action button,.story-download{min-height:43px;padding:11px 14px;border:1px solid #ffffff28;border-radius:11px;background:#f0c77c;color:#182216;font:850 12px Inter,system-ui,sans-serif;text-decoration:none;cursor:pointer}.story-action button.secondary,.story-download{background:#1b3828;color:var(--story-ink)}.story-action button:disabled{opacity:.5;cursor:wait}.story-status{min-height:20px;margin:12px 0 0;color:var(--story-muted);font-size:12px}.story-status[aria-busy=true]{color:var(--story-accent)}.story-status.error{color:var(--story-error)}.story-topline{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:15px}.story-timeline{display:flex;flex-wrap:wrap;gap:5px}.story-timeline span{padding:6px 8px;border:1px solid var(--story-line);border-radius:7px;color:#d7decf;font-size:10px;font-weight:750}.story-meta-fields{display:grid;grid-template-columns:1fr 1fr;gap:10px}.story-meta-fields .story-field{margin-bottom:10px}.beat-list{display:grid;gap:10px}.beat{border:1px solid var(--story-line);border-radius:15px;background:#0b1b13a8;overflow:hidden}.beat summary{display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;list-style:none}.beat summary::-webkit-details-marker{display:none}.beat-time{min-width:90px;color:var(--story-warm);font:800 11px ui-monospace,monospace}.beat summary strong{font-size:13px}.beat-body{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 14px 14px}.beat-field{display:grid;gap:6px}.beat-field.wide{grid-column:1/-1}.beat-field label{color:var(--story-muted);font-size:10px;font-weight:850;letter-spacing:.04em;text-transform:uppercase}.beat-field textarea{min-height:65px;font-size:12px}.beat-field.wide textarea{min-height:72px}.story-sources{margin-top:12px;border:1px solid var(--story-line);border-radius:13px}.story-sources summary{padding:12px 14px;color:var(--story-accent);font-size:12px;font-weight:850;cursor:pointer}.story-source-item{padding:12px 14px;border-top:1px solid var(--story-line);font-size:12px}.story-source-item p{margin:6px 0 0;color:var(--story-muted);line-height:1.5;white-space:pre-wrap}.story-source-item a{display:block;margin-top:5px;color:var(--story-warm);overflow-wrap:anywhere}.story-export{display:flex;flex-wrap:wrap;gap:9px;margin-top:14px}.story-help{margin-top:20px;padding:14px;border-left:2px solid var(--story-warm);background:#ffffff08;color:var(--story-muted);font-size:11px;line-height:1.6}
  .story-rendered-video{display:block;width:min(360px,100%);margin:16px auto 0;border-radius:14px;background:#050b07}.story-rendered-video[hidden]{display:none}
  @media(max-width:900px){.story-columns{grid-template-columns:1fr}.story-head{align-items:start;flex-direction:column}.story-builder{padding:22px 14px 55px}.beat-body{grid-template-columns:1fr}.beat-field.wide{grid-column:auto}}
  @media(max-width:520px){.story-meta-fields{grid-template-columns:1fr;gap:0}.story-timeline{display:grid;grid-template-columns:repeat(2,1fr)}.story-timeline span{text-align:center}.story-duration{align-self:flex-start}}
</style>
<section class="story-builder" data-csrf="<?=DailyStudio::esc($csrf)?>">
  <header class="story-head">
    <div><p class="story-eyebrow">Daily Breath · Remotion production template</p><h1>Story Builder</h1><p>Enter a topic and verified source notes. Generate a source-grounded six-beat script, then review, revise, download the story brief, or render the finished vertical video.</p></div>
    <span class="story-duration">60 SEC · 9:16 · 30 FPS</span>
  </header>
  <div class="story-columns">
    <section class="story-panel">
      <div class="story-panel-head">1 · Story brief</div>
      <div class="story-panel-body">
        <div class="story-field"><label for="storyTopic">Topic</label><textarea id="storyTopic" maxlength="2000" placeholder="What story should Daily Breath tell? Include the person, event, scripture, place, or historical setting."></textarea><small>Include relevant context and the intended audience. Generated factual claims must be supported by the notes below.</small></div>
        <div class="story-field"><label for="storySources">Sources and supporting notes</label><textarea class="sources-input" id="storySources" placeholder="One source per block, separated by a blank line:&#10;Citation or source title | https://example.org/reference | Paste the relevant excerpt or verified notes here."></textarea><small>Provide 1–4 sources. Each block must contain a citation, optional HTTP(S) URL, and excerpt or notes. URLs are credited but not fetched; paste the evidence used for drafting.</small></div>
        <div class="story-action"><button type="button" id="generateStory">Generate six beats</button></div>
        <p class="story-status" id="storyStatus" role="status" aria-live="polite"></p>
        <div class="story-help">Drafts are generated for editorial review and are never published automatically. Narration is delivered as a voiceover script; the MP4 is a silent motion-graphics render. Visual prompts are production directions—the template does not generate or fetch imagery.</div>
      </div>
    </section>
    <section class="story-panel">
      <div class="story-panel-head">2 · Review, refine, and render</div>
      <div class="story-panel-body">
        <div class="story-topline"><div class="story-timeline" aria-label="Fixed story timeline"><span>00–10 Intro</span><span>10–20 Incident</span><span>20–35 Rising</span><span>35–42 Peak</span><span>42–46 Falling</span><span>46–50 Resolution</span><span>50–55 Sources</span><span>55–60 Outro</span></div></div>
        <div id="storyOutput" hidden>
          <div class="story-meta-fields">
            <div class="story-field"><label for="storyTitle">Story title</label><input id="storyTitle" maxlength="100"></div>
            <div class="story-field"><label for="storySubtitle">Subtitle</label><input id="storySubtitle" maxlength="140"></div>
          </div>
          <div class="beat-list" id="beatList"></div>
          <div class="story-field" style="margin-top:12px"><label for="storyOutro">Daily Breath outro</label><textarea id="storyOutro" maxlength="180" style="min-height:56px"></textarea></div>
          <details class="story-sources" id="sourceDetails"><summary>Sources · complete citations and notes</summary><div id="sourceList"></div></details>
          <div class="story-export"><button class="story-download" type="button" id="downloadJson">Download story brief JSON</button><button type="button" id="renderStory">Render 60-second MP4</button><button class="story-download" type="button" id="downloadMp4" disabled>Download MP4</button></div>
          <video id="renderedVideo" class="story-rendered-video" controls playsinline hidden aria-label="Rendered Daily Breath story video"></video>
        </div>
      </div>
    </section>
  </div>
</section>
<script>
(() => {
  const root = document.querySelector('.story-builder');
  const $ = (id) => document.getElementById(id);
  const timings = {
    intro: ['00–10', 0, 10],
    inciting: ['10–20', 10, 10],
    rising: ['20–35', 20, 15],
    peak: ['35–42', 35, 7],
    falling: ['42–46', 42, 4],
    resolution: ['46–50', 46, 4],
  };
  let story = null;
  let renderedVideoUrl = '';
  const status = (message, error = false, busy = false) => {
    $('storyStatus').textContent = message;
    $('storyStatus').classList.toggle('error', error);
    $('storyStatus').setAttribute('aria-busy', busy ? 'true' : 'false');
  };
  const parseSources = () => {
    const blocks = $('storySources').value.trim().split(/\n\s*\n/).filter(Boolean);
    if (blocks.length < 1 || blocks.length > 4) throw new Error('Add one to four source blocks.');
    return blocks.map((block) => {
      const [citation = '', url = '', ...notes] = block.split('|').map((part) => part.trim());
      if (!citation || notes.join(' | ').trim().length < 1) throw new Error('Each source needs a citation and supporting excerpt or notes, separated with |.');
      return {citation, url, notes: notes.join(' | ').trim()};
    });
  };
  const buildBeat = (beat) => {
    const details = document.createElement('details');
    details.className = 'beat';
    details.open = beat.id === 'intro' || beat.id === 'peak';
    const summary = document.createElement('summary');
    const time = document.createElement('span');
    time.className = 'beat-time';
    time.textContent = timings[beat.id][0] + ' SEC';
    const heading = document.createElement('strong');
    heading.textContent = beat.label;
    summary.append(time, heading);
    const body = document.createElement('div');
    body.className = 'beat-body';
    const fields = [
      ['label', 'Beat label', false],
      ['onScreenText', 'On-screen text', false],
      ['narration', 'Narration', true],
      ['visualPrompt', 'Visual prompt', true],
    ];
    for (const [key, label, wide] of fields) {
      const field = document.createElement('div');
      field.className = 'beat-field' + (wide ? ' wide' : '');
      const fieldLabel = document.createElement('label');
      fieldLabel.textContent = label;
      const input = document.createElement('textarea');
      input.value = beat[key];
      input.maxLength = key === 'narration' || key === 'visualPrompt' ? 600 : key === 'label' ? 70 : 90;
      input.addEventListener('input', () => {
        beat[key] = input.value;
        if (key === 'label') heading.textContent = input.value;
      });
      field.append(fieldLabel, input);
      body.append(field);
    }
    details.append(summary, body);
    return details;
  };
  const showStory = (nextStory) => {
    story = nextStory;
    $('storyOutput').hidden = false;
    $('storyTitle').value = story.title;
    $('storySubtitle').value = story.subtitle;
    $('storyOutro').value = story.outroText;
    $('beatList').replaceChildren(...story.beats.map(buildBeat));
    const sources = $('sourceList');
    sources.replaceChildren();
    story.sources.forEach((source) => {
      const item = document.createElement('article');
      item.className = 'story-source-item';
      const citation = document.createElement('strong');
      citation.textContent = source.citation;
      const notes = document.createElement('p');
      notes.textContent = source.notes;
      item.append(citation);
      if (source.url) {
        const link = document.createElement('a');
        link.href = source.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = source.url;
        item.append(link);
      }
      item.append(notes);
      sources.append(item);
    });
  };
  const currentStory = () => {
    if (!story) throw new Error('Generate a story before exporting.');
    story.title = $('storyTitle').value.trim();
    story.subtitle = $('storySubtitle').value.trim();
    story.outroText = $('storyOutro').value.trim();
    if (!story.title || !story.subtitle || !story.outroText) throw new Error('Complete the title, subtitle, and outro.');
    if (story.beats.some((beat) => !beat.label.trim() || !beat.narration.trim() || !beat.onScreenText.trim() || !beat.visualPrompt.trim())) {
      throw new Error('Complete every field in all six beats.');
    }
    const words = story.beats.flatMap((beat) => beat.narration.match(/[\p{L}\p{N}]+(?:[’'-][\p{L}\p{N}]+)*/gu) || []).length;
    if (words > 95) throw new Error('Keep narration to 95 words or fewer for the 50-second story window.');
    if (story.beats.some((beat) => (beat.onScreenText.match(/[\p{L}\p{N}]+/gu) || []).length > 8)) {
      throw new Error('Keep each on-screen phrase to eight words or fewer.');
    }
    if (story.sources.length < 1 || story.sources.length > 4) throw new Error('A story needs one to four sources.');
    return story;
  };
  $('generateStory').addEventListener('click', async () => {
    const button = $('generateStory');
    button.disabled = true;
    try {
      const topic = $('storyTopic').value.trim();
      if (!topic) throw new Error('Enter a story topic.');
      const sources = parseSources();
      status('Generating a source-grounded six-beat story…', false, true);
      const response = await fetch('api/generate-dailybreath-story.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': root.dataset.csrf},
        body: JSON.stringify({topic, sources}),
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.error || 'Story generation failed.');
      showStory(payload.story);
      status('Story draft ready. Review each beat and source before export.');
    } catch (error) {
      status(error instanceof Error ? error.message : 'Story generation failed.', true);
    } finally {
      button.disabled = false;
    }
  });
  $('downloadJson').addEventListener('click', () => {
    try {
      const data = currentStory();
      const config = {...data, brand: 'Daily Breath', series: 'A story to carry with you', fps: 30, width: 1080, height: 1920, palette: {background: '#10271F', foreground: '#F5F1E8', accent: '#B9D6A1', muted: '#B9C5BB'}};
      const blob = new Blob([JSON.stringify(config, null, 2) + '\n'], {type: 'application/json'});
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'daily-breath-story.json';
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 60000);
      status('Story brief JSON downloaded.');
    } catch (error) {
      status(error instanceof Error ? error.message : 'Story brief could not be downloaded.', true);
    }
  });
  $('renderStory').addEventListener('click', async () => {
    const button = $('renderStory');
    button.disabled = true;
    try {
      const data = currentStory();
      status('Rendering the 60-second vertical MP4 with Remotion…', false, true);
      const response = await fetch('api/render-dailybreath-story.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': root.dataset.csrf},
        body: JSON.stringify(data),
      });
      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.error || 'Remotion render failed.');
      }
      const blob = await response.blob();
      if (!blob.size) throw new Error('Remotion returned an empty video.');
      if (renderedVideoUrl) URL.revokeObjectURL(renderedVideoUrl);
      renderedVideoUrl = URL.createObjectURL(blob);
      $('renderedVideo').src = renderedVideoUrl;
      $('renderedVideo').hidden = false;
      $('downloadMp4').disabled = false;
      status('MP4 ready. Preview it below or download when approved.');
    } catch (error) {
      status(error instanceof Error ? error.message : 'Remotion render failed.', true);
    } finally {
      button.disabled = false;
    }
  });
  $('downloadMp4').addEventListener('click', () => {
    if (!renderedVideoUrl) return;
    const link = document.createElement('a');
    link.href = renderedVideoUrl;
    link.download = 'daily-breath-story.mp4';
    link.click();
  });
})();
</script>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
