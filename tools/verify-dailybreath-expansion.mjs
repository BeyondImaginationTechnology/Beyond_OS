#!/usr/bin/env node
import { readFile, readdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const appResources = resolve(root, 'DailyBreathApple/Resources');
const webData = resolve(root, 'dailybreath/data');
const appAudio = resolve(appResources, 'Audio/Narration');
const webAudio = resolve(root, 'dailybreath/assets/audio/verses');

const loadPair = async (name) => {
  const [web, app] = await Promise.all([
    readFile(resolve(webData, name), 'utf8'),
    readFile(resolve(appResources, name), 'utf8'),
  ]);
  if (web !== app) throw new Error(`${name} differs between the website and iOS app.`);
  return JSON.parse(web);
};
const validMp3 = async (path) => {
  const data = await readFile(path);
  return data.length >= 128 && (data.subarray(0, 3).equals(Buffer.from('ID3')) || data[0] === 0xff);
};
const assert = (condition, message) => { if (!condition) throw new Error(message); };

const [verses, devotionals, challenges] = await Promise.all([
  loadPair('daily-verses.json'),
  loadPair('daily-devotionals.json'),
  loadPair('recovery-challenges.json'),
]);

assert(verses.entry_count === 138 && verses.entries.length === 138, 'Expected 138 recovery verses.');
assert(new Set(verses.entries.map((entry) => entry.reference)).size === 138, 'Recovery verse references are not unique.');
const scheduled = verses.entries.filter((entry) => entry.generated_batch === 'recovery-2026-08-16');
assert(scheduled.length === 100, 'Expected 100 scheduled verse additions.');
scheduled.forEach((entry, index) => {
  const expected = new Date('2026-08-16T12:00:00Z');
  expected.setUTCDate(expected.getUTCDate() + index);
  assert(entry.schedule_date === expected.toISOString().slice(0, 10), `Schedule gap at ${entry.id}.`);
});
assert(scheduled.at(-1).schedule_date === '2026-11-23', 'Verse schedule must end November 23, 2026.');

assert(devotionals.entry_count === 138 && devotionals.entries.length === 138, 'Expected 138 recovery devotionals.');
assert(new Set(devotionals.entries.map((entry) => entry.title)).size === 138, 'Recovery devotional titles are not unique.');
assert(devotionals.entries.filter((entry) => entry.schedule_date).length === 138, 'Expected all 138 devotionals to be scheduled.');
assert(devotionals.entries.filter((entry) => entry.schedule_role === 'primary').length === 100, 'Expected 100 primary dated devotionals.');
assert(devotionals.entries.filter((entry) => entry.schedule_role === 'companion').length === 38, 'Expected 38 scheduled companion devotionals.');

assert(challenges.entry_count === 20 && challenges.entries.length === 20, 'Expected 20 recovery challenges.');
assert(new Set(challenges.entries.map((entry) => entry.title)).size === 20, 'Recovery challenge titles are not unique.');
for (const entry of challenges.entries) {
  assert(entry.starts_on >= '2026-08-16' && entry.starts_on <= '2026-11-23', `${entry.id} starts outside the campaign.`);
  assert(entry.ends_on <= '2026-11-23', `${entry.id} ends outside the campaign.`);
}

for (const entry of verses.entries) {
  const [appOk, webOk] = await Promise.all([
    validMp3(resolve(appAudio, entry.audio_file)),
    validMp3(resolve(webAudio, entry.audio_file)),
  ]);
  assert(appOk, `Invalid iOS MP3: ${entry.audio_file}`);
  assert(webOk, `Invalid website MP3: ${entry.audio_file}`);
}

const appMp3s = (await readdir(appAudio)).filter((name) => name.endsWith('.mp3'));
const webMp3s = (await readdir(webAudio)).filter((name) => name.endsWith('.mp3'));
assert(appMp3s.length === 149, `Expected 149 total iOS narration files, found ${appMp3s.length}.`);
assert(webMp3s.length === 138, `Expected 138 website verse files, found ${webMp3s.length}.`);

console.log(JSON.stringify({
  verses: verses.entries.length,
  newScheduledVerses: scheduled.length,
  devotionals: devotionals.entries.length,
  challenges: challenges.entries.length,
  iosNarrationMp3s: appMp3s.length,
  websiteVerseMp3s: webMp3s.length,
  schedule: `${scheduled[0].schedule_date}..${scheduled.at(-1).schedule_date}`,
  resourcesMatch: true,
  audioReferencesValid: true,
}));
