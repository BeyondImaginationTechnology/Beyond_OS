const fs = require('fs');
const path = require('path');

const collections = {
  'season-one-opening': [
    ['Celestial Rose', '2026-07-14'], ['Eye of Horus Anubis', '2026-07-16'],
    ['Sacred Heart', '2026-07-18'], ['Archangel Michael', '2026-07-19']
  ],
  'divine-realism': [
    ['Biblical Realism', '2026-07-17'], ['Dove & Radiant Cross', '2026-07-22'],
    ['Cherub & Clouds', '2026-07-23'], ['Gates of Heaven', '2026-07-24'],
    ['Crown & Cross', '2026-07-25'], ['Angel of Light', '2026-07-26']
  ],
  'beyond-ancient': [
    ['Ancient Sentinel', '2026-07-27'], ['Solar Eye Motif', '2026-07-28'], ['Pharaoh Portrait', '2026-07-29'], ['Sacred Scarab', '2026-07-30'],
    ['Sekhmet', '2026-07-31'], ['Isis', '2026-08-01'], ['Pyramid Gateway', '2026-08-02'], ['Osiris', '2026-08-03'],
    ['Bastet', '2026-08-04'], ['Egyptian Sacred Symbols', '2026-08-05'], ['Hieroglyphic Guardian', '2026-08-06'], ['Ornamental Egyptian Frame', '2026-08-07']
  ],
  'japanese-legends': [
    ['Hannya Mask', '2026-08-08'], ['Oni Warrior', '2026-08-09'], ['Japanese Dragon', '2026-08-10'], ['Koi & Lotus', '2026-08-11'],
    ['Samurai Portrait', '2026-08-12'], ['Geisha & Fan', '2026-08-13'], ['Japanese Tiger', '2026-08-14'], ['Snake & Chrysanthemum', '2026-08-15'],
    ['Peony Arrangement', '2026-08-16'], ['Great Wave', '2026-08-17'], ['Temple Guardian', '2026-08-18'], ['Kitsune Mask', '2026-08-19'],
    ['Phoenix', '2026-08-20'], ['Raijin', '2026-08-21'], ['Nio Guardians', '2026-08-22']
  ],
  'dark-realism': [
    ['Gothic Skull', '2026-08-23'], ['Raven & Moon', '2026-08-24'], ['Hooded Reaper', '2026-08-25'], ['Broken Angel Statue', '2026-08-26'],
    ['Demon Portrait', '2026-08-27'], ['Gothic Cathedral', '2026-08-28'], ['Skull in Smoke', '2026-08-29'], ['Chained Soul', '2026-08-30'],
    ['Broken Clock', '2026-08-31'], ['Plague Doctor', '2026-09-01'], ['Weeping Stone Face', '2026-09-02'], ['Grim Knight', '2026-09-03'],
    ['Raven Skull', '2026-09-04'], ['Haunted Gate', '2026-09-05'], ['Death & Hourglass', '2026-09-06'], ['Dark Seraph', '2026-09-07'],
    ['Possessed Statue', '2026-09-08'], ['Final Judgment', '2026-09-09']
  ]
};

let seq = 0;
let missingReport = [];
let allDrops = [];

for (const [colSlug, stencils] of Object.entries(collections)) {
  stencils.forEach(([title, date], idx) => {
    seq++;
    const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const folderName = `${String(idx + 1).padStart(2, '0')}-${slug}`;
    const bundledDir = path.join('beyond-tattoo', 'assets', 'stencils', colSlug, folderName);
    const uploadDir = path.join('beyond-tattoo', 'uploads', 'stencil-library', colSlug, folderName);

    let targetDir = fs.existsSync(bundledDir) ? bundledDir : (fs.existsSync(uploadDir) ? uploadDir : null);

    const checkFile = (stem, exts) => {
      if (!targetDir) return false;
      return exts.some(ext => fs.existsSync(path.join(targetDir, `${stem}.${ext}`)));
    };

    const hasMeta = checkFile('metadata', ['json']);
    const hasPrint = checkFile('stencil-print-ready', ['png', 'jpg', 'svg']);
    const hasOutline = checkFile('stencil-outline', ['png', 'jpg', 'svg']);
    const hasPreview = checkFile('preview-watermarked', ['png', 'jpg', 'webp']);
    const hasRef = checkFile('reference-artwork', ['jpg', 'webp', 'png']);
    const hasMockup = checkFile('placement-mockup', ['jpg', 'webp', 'png']);

    let missing = [];
    if (!hasMeta) missing.push('metadata.json');
    if (!hasPrint) missing.push('stencil-print-ready');
    if (!hasOutline) missing.push('stencil-outline');
    if (!hasPreview) missing.push('preview-watermarked');
    if (!hasRef) missing.push('reference-artwork');
    if (!hasMockup) missing.push('placement-mockup');

    const dropInfo = { seq, title, date, colSlug, idx, folderName, dir: targetDir, missing };
    allDrops.push(dropInfo);

    if (missing.length > 0 || !targetDir) {
      missingReport.push(dropInfo);
    }
  });
}

console.log(`Total drops in 55-schedule: ${seq}`);
console.log(`Drops needing assets/dirs: ${missingReport.length}`);
missingReport.forEach(item => {
  console.log(`[Drop ${String(item.seq).padStart(2, '0')}] ${item.title} (${item.colSlug}/${item.folderName}): ${item.dir ? item.missing.join(', ') : 'DIR MISSING'}`);
});
