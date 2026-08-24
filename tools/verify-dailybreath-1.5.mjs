import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const assert = (condition, message) => { if (!condition) throw new Error(message); };

const quran = read('DailyBreathApple', 'Resources', 'quran-pickthall-vpl.txt')
  .split(/\r?\n/)
  .filter(line => line && !line.startsWith('#'));
const parsed = quran.map(line => line.split('|', 4));
const surahs = new Set(parsed.map(parts => Number(parts[0])));
assert(quran.length === 6236, 'Quran resource must contain 6,236 ayahs.');
assert(surahs.size === 114, 'Quran resource must contain all 114 surahs.');
assert(parsed[0][0] === '1' && parsed[0][1] === '1', 'Quran must begin at 1:1.');
assert(parsed.at(-1)[0] === '114' && parsed.at(-1)[1] === '6', 'Quran must end at 114:6.');

const bibleLines = read('DailyBreathApple', 'Resources', 'engwebp_vpl.txt').trim().split(/\r?\n/);
const bibleCodes = new Set(bibleLines.map(line => line.split(' ', 1)[0]));
const firstNewTestamentIndex = bibleLines.findIndex(line => line.startsWith('MAT '));
const hebrewScriptureLines = bibleLines.slice(0, firstNewTestamentIndex);
const hebrewScriptureCodes = new Set(hebrewScriptureLines.map(line => line.split(' ', 1)[0]));
assert(bibleLines.length === 31103, 'Bundled Bible must contain 31,103 verses.');
assert(bibleCodes.size === 66, 'Bundled Bible must contain all 66 source books.');
assert(hebrewScriptureLines.length === 23145, 'Torah/Tanakh edition must contain all 23,145 Hebrew Scripture verses.');
assert(hebrewScriptureCodes.size === 39, 'Torah/Tanakh edition must contain all 39 bundled source volumes.');
const models = read('DailyBreathApple', 'Sources', 'Models.swift');
for (const alias of ['EZE', 'JOE', 'NAH', 'SOL', 'MAR', 'JOH', 'PHI', 'JAM', '1JO', '2JO', '3JO']) {
  assert(models.includes(`"${alias}":`), `Bible parser alias ${alias} is missing.`);
}

const project = read('DailyBreathApple', 'TheDailyBreath.xcodeproj', 'project.pbxproj');
for (const item of ['InterfaithScripture.swift', 'ScriptureLibraryView.swift', 'quran-pickthall-vpl.txt']) {
  assert(project.includes(item), `${item} must be included in the Xcode project.`);
}

const theme = read('DailyBreathApple', 'Sources', 'DailyBreathTheme.swift');
assert(theme.includes('case torahLight'), 'Torah Light theme is missing.');
assert(theme.includes('case quranMoon'), 'Quran Moon theme is missing.');
assert(theme.includes('case .bible: .forest'), 'Bible must restore the Forest theme.');

const store = read('DailyBreathApple', 'Sources', 'DailyBreathStore.swift');
const academyTitles = [
  'Joining the Christian Faith with Chris',
  'Christian Recovery with Chris',
  'Joining the Jewish Faith with Dovi',
  'Jewish Recovery with Dovi',
  'Joining the Muslim Faith with Moe',
  'Muslim Recovery with Moe',
];
for (const item of academyTitles) {
  assert(store.includes(item), `${item} academy path is missing.`);
}
assert((store.match(/AcademyPath\(/g) ?? []).length === 6, 'Academy must contain exactly two paths for each of three faiths.');
for (const recoveryLessonID of [302, 502, 702]) {
  assert(store.includes(`id: ${recoveryLessonID},`), `Expanded recovery lesson ${recoveryLessonID} is missing.`);
}

const academyView = read('DailyBreathApple', 'Sources', 'AcademyView.swift');
assert(academyView.includes('Beyond Imagination Certification'), 'Locked Beyond Imagination certification milestone is missing.');
assert(academyView.includes('Certificate of Completion'), 'Academy certificate view is missing.');
assert(academyView.includes('Complete all \\(totalLessonCount) lessons across both paths'), 'Certificate must require both paths.');
assert(academyView.includes('@AppStorage("selectedFaithTradition")'), 'Academy must share the global faith selection.');
assert(!academyView.includes('academyFaithTradition'), 'Academy still has a separate faith selection.');

const assetsRoot = path.join(root, 'DailyBreathApple', 'Resources', 'Assets.xcassets');
for (const item of ['ChrisGuide', 'DoviGuide', 'MoeGuide']) {
  assert(fs.existsSync(path.join(assetsRoot, `${item}.imageset`, `${item}.png`)), `${item} image is missing.`);
  JSON.parse(read('DailyBreathApple', 'Resources', 'Assets.xcassets', `${item}.imageset`, 'Contents.json'));
}

const notification = read('DailyBreathApple', 'Sources', 'NotificationService.swift');
assert(notification.includes('@preconcurrency import UserNotifications'), 'Swift 6 UserNotifications compatibility fix is missing.');

assert(models.includes('resolvedVerseOfTheDay'), 'Scheduled verse precedence resolver is missing.');
assert(models.includes('Let this recovery verse guide your next faithful step.'), 'Recovery verse reflection copy is incorrect.');
assert(!models.includes('Let this entry.theme verse'), 'Literal entry.theme placeholder remains in the iOS model.');
assert(store.includes('RecoveryContent.resolvedVerseOfTheDay(for: requestedDate, remoteVerse: today.verse)'), 'Remote refresh can still overwrite the scheduled verse.');

const config = read('DailyBreathApple', 'project.yml');
assert(config.includes('MARKETING_VERSION: 1.5'), 'Marketing version must be 1.5.');

console.log(JSON.stringify({
  version: '1.5',
  quranSurahs: surahs.size,
  quranVerses: quran.length,
  bibleBooks: bibleCodes.size,
  bibleVerses: bibleLines.length,
  torahTanakhVolumes: hebrewScriptureCodes.size,
  torahTanakhVerses: hebrewScriptureLines.length,
  guideAssets: 3,
  themes: ['Torah Light', 'Quran Moon'],
  academyJourneys: 6,
  xcodeProjectMembership: true,
  notificationCompatibility: true,
}, null, 2));
