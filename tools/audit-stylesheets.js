const fs = require('fs');
const path = require('path');

const jsonPath = path.join('beyond-tattoo', 'data', 'autumn-ink-stylesheets.json');
const assetsBase = path.join('beyond-tattoo');

if (!fs.existsSync(jsonPath)) {
  console.error(`JSON file missing at ${jsonPath}`);
  process.exit(1);
}

const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
const campaign = data.campaign || {};
const releases = data.releases || [];

console.log(`Campaign Name: ${campaign.name}`);
console.log(`Total Scheduled Releases: ${campaign.total_releases}`);
console.log(`Current Entries in JSON: ${releases.length}`);

let missingAssets = [];
let missingMetaData = [];

for (let seq = 1; seq <= (campaign.total_releases || 31); seq++) {
  const rel = releases.find(r => r.sequence === seq);
  if (!rel) {
    missingMetaData.push(seq);
  } else {
    const fullAssetPath = path.join(assetsBase, ...rel.asset.split('/'));
    if (!fs.existsSync(fullAssetPath)) {
      missingAssets.push({ seq, title: rel.title, asset: rel.asset, path: fullAssetPath });
    }
  }
}

console.log(`\nMissing Metadata Entries in JSON (Seq 1-${campaign.total_releases}): ${missingMetaData.length}`);
if (missingMetaData.length > 0) {
  console.log(`Missing Sequences: ${missingMetaData.join(', ')}`);
}

console.log(`\nMissing Asset Files on Disk: ${missingAssets.length}`);
missingAssets.forEach(item => {
  console.log(`[Seq ${String(item.seq).padStart(2, '0')}] ${item.title}: File missing at ${item.path}`);
});
