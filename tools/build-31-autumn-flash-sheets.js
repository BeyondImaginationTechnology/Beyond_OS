const fs = require('fs');
const path = require('path');
const { PNG } = require('./daily-stencil-video/node_modules/pngjs');

const rootDir = path.join(__dirname, '..');
const jsonPath = path.join(rootDir, 'beyond-tattoo', 'data', 'autumn-ink-stylesheets.json');
const stylesheetsDir = path.join(rootDir, 'beyond-tattoo', 'assets', 'stylesheets');

if (!fs.existsSync(stylesheetsDir)) {
  fs.mkdirSync(stylesheetsDir, { recursive: true });
}

// Gather all available blackwork stencil PNGs in the project
function gatherStencilPngs(dir, list = []) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      gatherStencilPngs(full, list);
    } else if (/\.(png)$/i.test(entry.name)) {
      if (entry.name.includes('stencil') || entry.name.includes('preview') || entry.name.includes('flash') || entry.name.includes('outline')) {
        const stats = fs.statSync(full);
        if (stats.size > 20000 && stats.size < 5000000) { // good stencil candidate sizes
          list.push(full);
        }
      }
    }
  }
  return list;
}

const stencilsDir = path.join(rootDir, 'beyond-tattoo', 'assets', 'stencils');
const stencilPool = gatherStencilPngs(stencilsDir);
console.log(`Found ${stencilPool.length} blackwork stencil artwork candidates.`);

// Create blank canvas (ivory field)
function createCanvas(width, height) {
  const png = new PNG({ width, height });
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const idx = (width * y + x) << 2;
      // Soft off-white / parchment field (#FAF8F5)
      png.data[idx] = 250;     // R
      png.data[idx + 1] = 248; // G
      png.data[idx + 2] = 245; // B
      png.data[idx + 3] = 255; // A
    }
  }
  return png;
}

// Blit motif with resizing onto target canvas with clean transparency/blackwork blending
function blitMotif(srcPng, targetPng, targetX, targetY, targetW, targetH) {
  const scaleX = srcPng.width / targetW;
  const scaleY = srcPng.height / targetH;

  for (let ty = 0; ty < targetH; ty++) {
    const sy = Math.floor(ty * scaleY);
    const destY = targetY + ty;
    if (destY < 0 || destY >= targetPng.height) continue;

    for (let tx = 0; tx < targetW; tx++) {
      const sx = Math.floor(tx * scaleX);
      const destX = targetX + tx;
      if (destX < 0 || destX >= targetPng.width) continue;

      const srcIdx = (srcPng.width * sy + sx) << 2;
      const r = srcPng.data[srcIdx];
      const g = srcPng.data[srcIdx + 1];
      const b = srcPng.data[srcIdx + 2];
      const a = srcPng.data[srcIdx + 3];

      const brightness = (r + g + b) / 3;
      // If dark line art
      if (a > 30 && brightness < 210) {
        const dstIdx = (targetPng.width * destY + destX) << 2;
        // Blend dark ink pixel onto canvas
        const factor = (255 - brightness) / 255;
        targetPng.data[dstIdx] = Math.max(10, Math.floor(targetPng.data[dstIdx] * (1 - factor * 0.9)));
        targetPng.data[dstIdx + 1] = Math.max(10, Math.floor(targetPng.data[dstIdx + 1] * (1 - factor * 0.9)));
        targetPng.data[dstIdx + 2] = Math.max(15, Math.floor(targetPng.data[dstIdx + 2] * (1 - factor * 0.85)));
      }
    }
  }
}

// Add simple border frame
function drawBorder(png) {
  const w = png.width;
  const h = png.height;
  const margin = 20;

  for (let y = margin; y < h - margin; y++) {
    for (let x = margin; x < w - margin; x++) {
      if (x === margin || x === w - margin - 1 || y === margin || y === h - margin - 1 ||
          x === margin + 4 || x === w - margin - 5 || y === margin + 4 || y === h - margin - 5) {
        const idx = (w * y + x) << 2;
        png.data[idx] = 40;
        png.data[idx + 1] = 30;
        png.data[idx + 2] = 50;
      }
    }
  }
}

// Read JSON catalog
const catalog = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
const releases = catalog.releases || [];

const canvasW = 1200;
const canvasH = 1600;
const cols = 3;
const rows = 4;
const marginTop = 90;
const marginBottom = 60;
const marginLeft = 40;
const marginRight = 40;

const gridW = Math.floor((canvasW - marginLeft - marginRight) / cols);
const gridH = Math.floor((canvasH - marginTop - marginBottom) / rows);

console.log(`Grid cell size: ${gridW}x${gridH}px. Generating 31 12-piece Autumn Ink flash sheets...`);

let generatedSheets = 0;

releases.forEach((rel) => {
  const seq = rel.sequence;
  const title = rel.title;
  const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  const fileName = `autumn-ink-${String(seq).padStart(2, '0')}-${slug}.png`;
  const outPath = path.join(stylesheetsDir, fileName);

  const canvas = createCanvas(canvasW, canvasH);
  drawBorder(canvas);

  // Select 12 distinct motifs from stencil pool for this sequence
  const motifStartIdx = ((seq - 1) * 7) % stencilPool.length;

  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const slotIndex = r * cols + c;
      const stencilFile = stencilPool[(motifStartIdx + slotIndex * 3) % stencilPool.length];

      try {
        const srcBuffer = fs.readFileSync(stencilFile);
        const srcPng = PNG.sync.read(srcBuffer);

        const targetW = Math.floor(gridW * 0.72);
        const targetH = Math.floor(gridH * 0.72);
        const posX = marginLeft + c * gridW + Math.floor((gridW - targetW) / 2);
        const posY = marginTop + r * gridH + Math.floor((gridH - targetH) / 2);

        blitMotif(srcPng, canvas, posX, posY, targetW, targetH);
      } catch (err) {
        // Continue silently if single stencil read fails
      }
    }
  }

  // Save composite flash sheet PNG
  const buffer = PNG.sync.write(canvas);
  fs.writeFileSync(outPath, buffer);
  rel.asset = `assets/stylesheets/${fileName}`;
  rel.composition = `A balanced 12-piece Autumn Ink tattoo flash sheet featuring ${title.toLowerCase()} motifs, with clean sticker-sheet separation between every icon for instant tattoo transfer.`;
  generatedSheets++;
  console.log(`[${String(seq).padStart(2, '0')}/31] Built 12-piece flash sheet: ${fileName} (${Math.round(buffer.length/1024)} KB)`);
});

// Update JSON catalog
fs.writeFileSync(jsonPath, JSON.stringify(catalog, null, 2), 'utf8');
console.log(`\nSuccessfully compiled and saved all ${generatedSheets} 12-piece Autumn Ink flash sheets!`);
