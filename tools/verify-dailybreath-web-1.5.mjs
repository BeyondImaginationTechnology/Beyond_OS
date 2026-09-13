import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const assert = (condition, message) => { if (!condition) throw new Error(message); };

const webApp = read('dailybreath', 'includes', 'web-app.php');
const manifest = JSON.parse(read('dailybreath', 'manifest.webmanifest'));
const serviceWorker = read('dailybreath', 'service-worker.js');
const versionMatch = webApp.match(/DAILYBREATH_WEB_VERSION = '([^']+)'/);
assert(versionMatch, 'Web version constant is missing.');
const version = versionMatch[1];
assert(manifest.name === 'Daily Breath', 'PWA manifest must use the public app name.');
assert(manifest.short_name === 'Daily Breath', 'PWA short name must use the public app name.');
assert(serviceWorker.includes(`dailybreath-${version}-shell-`), 'PWA cache must match the current web version.');

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
const academy = read('dailybreath', 'academy.php');
const academySubscribe = read('dailybreath', 'academy-subscribe.php');
const practices = read('dailybreath', 'practices.php');
const settings = read('dailybreath', 'settings.php');
const todayApi = read('dailybreath', 'api', 'today.php');
for (const tradition of ['bible', 'torah', 'quran']) {
  assert(home.includes(`'${tradition}'`), `Homepage ${tradition} selector is missing.`);
}
assert(sacred.includes('dailybreath_interfaith_verse_of_day'), 'Interfaith daily matching is missing.');
assert(sacred.includes('dailybreath_search_sacred_text'), 'Sacred-text search is missing.');
assert(reader.includes('data-faith='), 'Reader tradition theme is missing.');
assert(todayApi.includes('dailybreath_interfaith_verse_of_day'), 'Today API must return the selected faith tradition.');
assert(todayApi.includes("'reader_url'"), 'Today API reader deep link is missing.');
assert(version === '2.0', 'Web app version must be 2.0.');
assert(manifest.version === '2.0.0', 'PWA manifest version must be 2.0.0.');
assert(home.includes("date('l, F j, Y')"), 'Verse of the day must display its full date.');
assert(home.includes("strtotime('monday this week')") && home.includes('$weeklyVerse'), 'Weekly reflection must use a stable Monday reading.');
assert(home.includes('Weekly devotional') && !home.includes('FIRST MODULE FREE'), 'Homepage 2.0 devotional or Academy wording is incorrect.');
assert(!academy.includes('START FREE') && !academySubscribe.includes('Module 1 stays free'), 'Academy still contains first-module-free wording.');
assert(practices.includes("$prayer['tradition']='Bible'"), 'Bible specific prayers are missing.');
for (const tradition of ['Tanakh', 'Quran']) {
  assert(practices.includes(`'tradition'=>'${tradition}'`), `${tradition} specific prayers are missing.`);
}
assert(settings.includes('Forest Light') && settings.includes('Forest Dark') && !settings.includes('<option value="lilac">'), 'Settings must expose only the forest theme choices.');
assert(settings.includes('beyond-id/auth/privacy.php'), 'Web settings must link to the public privacy notice.');
assert(home.includes('#2d694b66') && practices.includes('id="hg-frame-gradient"') && practices.includes('stop-color="#173f2c"'), 'Forest styling is missing from the web experience.');
assert(practices.includes('.breath-stage{min-height:254px') && practices.includes('width:116px;height:148px'), 'Web hourglass must match the iOS component proportions.');
assert(practices.includes('id="breath-detail"') && practices.includes("p.name==='Exhale'?phaseProgress"), 'Web hourglass phase detail or sand animation is missing.');

console.log(JSON.stringify({
  version,
  traditions: 3,
  bibleVerses: bibleLines.length,
  torahTanakhVerses: matthew,
  quranSurahs: 114,
  quranAyahs: quranLines.length,
  search: true,
  readerControls: true,
  pwaCache: `dailybreath-${version}-shell`,
  fullVerseDate: true,
  weeklyDevotional: true,
  forestTheme: true,
  interfaithPrayers: true,
  publicPrivacyNotice: true,
  iosHourglassParity: true,
}, null, 2));
