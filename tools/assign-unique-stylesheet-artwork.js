const fs = require('fs');
const path = require('path');

const jsonPath = path.join('beyond-tattoo', 'data', 'autumn-ink-stylesheets.json');
const targetDir = path.join('beyond-tattoo', 'assets', 'stylesheets');

if (!fs.existsSync(jsonPath)) {
  console.error(`JSON missing at ${jsonPath}`);
  process.exit(1);
}

const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

// 31 Unique, distinct source image mappings
const uniqueArtworkMapping = {
  1: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-01-pumpkin-harvest.jpg', ext: 'jpg' },
  2: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-02-midnight-harvest.png', ext: 'png' },
  3: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-03-thorned-harvest.jpg', ext: 'jpg' },
  4: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-04-harvest-haunts.jpg', ext: 'jpg' },
  5: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-05-witching-harvest.png', ext: 'png' },
  6: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-06-midnight-reliquary.png', ext: 'png' },
  7: { src: 'beyond-tattoo/assets/stylesheets/autumn-ink-07-corn-hay-rusted-tools.png', ext: 'png' },
  8: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/01-chain-lantern-cathedral/preview-watermarked.png', ext: 'png' },
  9: { src: 'beyond-tattoo/assets/stencils/dark-realism/02-raven-moon/stencil-outline.png', ext: 'png' },
  10: { src: 'beyond-tattoo/assets/stencils/dark-realism/16-dark-seraph/stencil-violet-trace.png', ext: 'png' },
  11: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/03-crow-ruins/preview-watermarked.png', ext: 'png' },
  12: { src: 'beyond-tattoo/assets/stencils/dark-realism/01-gothic-skull/lore-card.jpg', ext: 'jpg' },
  13: { src: 'beyond-tattoo/assets/stencils/dark-realism/03-hooded-reaper/stencil-outline.png', ext: 'png' },
  14: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/02-skull-cathedral-smoke/preview-watermarked.png', ext: 'png' },
  15: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/07-hooded-reaper/preview-watermarked.png', ext: 'png' },
  16: { src: 'beyond-tattoo/assets/stencils/dark-realism/08-chained-soul/stencil-violet-trace.png', ext: 'png' },
  17: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/04-demon-cathedral/preview-watermarked.png', ext: 'png' },
  18: { src: 'beyond-tattoo/assets/stencils/dark-realism/14-haunted-gate/stencil-outline.png', ext: 'png' },
  19: { src: 'beyond-tattoo/assets/stencils/dark-realism/04-broken-angel-statue/stencil-outline.png', ext: 'png' },
  20: { src: 'beyond-tattoo/assets/stencils/dark-realism/09-broken-clock/stencil-violet-trace.png', ext: 'png' },
  21: { src: 'beyond-tattoo/assets/stencils/beyond-studio-originals/08-crow-moon/preview-watermarked.png', ext: 'png' },
  22: { src: 'beyond-tattoo/assets/stencils/season-one-opening/01-celestial-rose/preview-watermarked.jpg', ext: 'jpg' },
  23: { src: 'beyond-tattoo/assets/stencils/dark-realism/10-plague-doctor/preview-watermarked.png', ext: 'png' },
  24: { src: 'beyond-tattoo/assets/stencils/dark-realism/08-chained-soul/stencil-print-ready.png', ext: 'png' },
  25: { src: 'beyond-tattoo/assets/stencils/public-domain-flash-archive/01-smithsonian-tattoo-flash-sheet-a/preview-watermarked.png', ext: 'png' },
  26: { src: 'beyond-tattoo/assets/stencils/dark-realism/11-weeping-stone-face/stencil-outline.png', ext: 'png' },
  27: { src: 'beyond-tattoo/assets/stencils/dark-realism/15-death-hourglass/stencil-outline.png', ext: 'png' },
  28: { src: 'beyond-tattoo/assets/stencils/dark-realism/12-grim-knight/stencil-outline.png', ext: 'png' },
  29: { src: 'beyond-tattoo/assets/stencils/dark-realism/05-demon-portrait/stencil-outline.png', ext: 'png' },
  30: { src: 'beyond-tattoo/assets/stencils/dark-realism/17-possessed-statue/stencil-outline.png', ext: 'png' },
  31: { src: 'beyond-tattoo/assets/img/campaign/autumn-ink-halloween-31-square.png', ext: 'png' }
};

let updatedCount = 0;

data.releases.forEach(rel => {
  const seq = rel.sequence;
  const map = uniqueArtworkMapping[seq];
  if (map && fs.existsSync(map.src)) {
    const slug = rel.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const fileName = `autumn-ink-${String(seq).padStart(2, '0')}-${slug}.${map.ext}`;
    const targetPath = path.join(targetDir, fileName);

    fs.copyFileSync(map.src, targetPath);
    rel.asset = `assets/stylesheets/${fileName}`;
    updatedCount++;
    console.log(`[Seq ${String(seq).padStart(2, '0')}] ${rel.title} -> ${fileName}`);
  }
});

fs.writeFileSync(jsonPath, JSON.stringify(data, null, 2), 'utf8');

console.log(`Successfully assigned distinct unique artwork for ${updatedCount}/31 stylesheets.`);
