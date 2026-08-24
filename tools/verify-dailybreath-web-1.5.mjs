import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const assert = (condition, message) => { if (!condition) throw new Error(message); };

const webApp = read('dailybreath', 'includes', 'web-app.php');
const manifest = JSON.parse(read('dailybreath', 'manifest.webmanifest'));
const serviceWorker = read('dailybreath', 'service-worker.js');
assert(webApp.includes("DAILYBREATH_WEB_VERSION = '1.5'"), 'Web version constant must be 1.5.');
assert(manifest.name === 'DailyBreath 1.5', 'PWA manifest must be version 1.5.');
assert(serviceWorker.includes("dailybreath-1.5-shell-v1"), 'PWA cache must be refreshed for 1.5.');

const quranLines = read('dailybreath', 'data', 'quran-pickthall-vpl.txt').split(/\r?\n/).filter(line => line && !line.startsWith('#'));
const quranParts = quranLines.map(line => line.split('|', 4));
assert(quranLines.length === 6236, 'Web Quran must contain all 6,236 ayahs.');
assert(new Set(quranParts.map(parts => Number(parts[0]))).size === 114, 'Web Quran must contain all 114 surahs.');

const bibleLines = read('dailybreath', 'data', 'engwebp_vpl.txt').split(/\r?\n/).filter(Boolean);
const matthew = bibleLines.findIndex(line => line.startsWith('MAT '));
assert(bibleLines.length === 31103, 'Web Bible must contain all 31,103 verses.');
assert(matthew === 23145, 'Web Torah/Tanakh edition must contain all 23,145 Hebrew Scripture verses.');

const sacred = read('dailybreath', 'includes', 'sacred-text.php');
const reader = read('dailybreath', 'scripture.php');
const home = read('dailybreath', 'index.php');
const todayApi = read('dailybreath', 'api', 'today.php');
for (const tradition of ['bible', 'torah', 'quran']) {
  assert(home.includes(`'${tradition}'`), `Homepage ${tradition} selector is missing.`);
}
assert(sacred.includes('dailybreath_interfaith_verse_of_day'), 'Interfaith daily matching is missing.');
assert(sacred.includes('dailybreath_search_sacred_text'), 'Sacred-text search is missing.');
assert(reader.includes('data-faith='), 'Reader tradition theme is missing.');
assert(reader.includes('Narrate'), 'Reader narration is missing.');
assert(todayApi.includes('dailybreath_interfaith_verse_of_day'), 'Today API must return the selected faith tradition.');
assert(todayApi.includes("'reader_url'"), 'Today API reader deep link is missing.');

console.log(JSON.stringify({
  version: '1.5',
  traditions: 3,
  bibleVerses: bibleLines.length,
  torahTanakhVerses: matthew,
  quranSurahs: 114,
  quranAyahs: quranLines.length,
  search: true,
  narration: true,
  pwaCache: 'dailybreath-1.5-shell-v1',
}, null, 2));
