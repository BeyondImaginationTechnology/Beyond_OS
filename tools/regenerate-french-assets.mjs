#!/usr/bin/env node

// Commercial audio regeneration for Beyond French, French Quest, and the
// reviewed Euro-African expansion. The script refuses to run without an
// explicit commercial-license acknowledgement and per-locale voice IDs.
//
// PowerShell example:
//   $env:ELEVENLABS_API_KEY='...'
//   $env:ELEVENLABS_COMMERCIAL_LICENSE='1'
//   $env:ELEVENLABS_VOICE_HT_HT='PEjMkBhSB6492eADs4Ew' # Wesly
//   $env:ELEVENLABS_VOICE_EN_JM='mrDMz4sYNCz18XYFpmyV' # Nicole - Rich and Expressive
//   $env:ELEVENLABS_VOICE_ES_ES='<native Spanish voice id>'
//   node tools/regenerate-french-assets.mjs --scope=lessons,quest

import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const args = new Set(process.argv.slice(2));
const scopes = new Set((process.argv.find((x) => x.startsWith('--scope='))?.slice(8) || 'all').split(',').map((x) => x.trim()).filter(Boolean));
const dryRun = args.has('--dry-run');
const force = args.has('--force');
const env = (name, fallback = '') => String(process.env[name] || fallback).trim();
const apiKey = env('ELEVENLABS_API_KEY');
const endpoint = env('ELEVENLABS_ENDPOINT', 'https://api.elevenlabs.io/v1/text-to-speech').replace(/\/$/, '');
const model = env('ELEVENLABS_MODEL', 'eleven_multilingual_v2');
const outputFormat = env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128');
const commercial = /^(1|true|yes)$/i.test(env('ELEVENLABS_COMMERCIAL_LICENSE'));

const voices = {
  // Saved Studio voice for French and Spanish; override per locale when needed.
  'fr-FR': env('ELEVENLABS_VOICE_FR_FR', 'hpp4J3VqNfWAUOO0d1Us'),
  'es-ES': env('ELEVENLABS_VOICE_ES_ES', 'hpp4J3VqNfWAUOO0d1Us'),
  'ht-HT': env('ELEVENLABS_VOICE_HT_HT', 'PEjMkBhSB6492eADs4Ew'), // Wesly
  'en-JM': env('ELEVENLABS_VOICE_EN_JM', 'mrDMz4sYNCz18XYFpmyV'), // Nicole - Rich and Expressive
  'ln-CD': env('ELEVENLABS_VOICE_LN_CD', 'PEjMkBhSB6492eADs4Ew'), // Wesly
  'ar-MA': env('ELEVENLABS_VOICE_AR_MA'),
  'ar-EG': env('ELEVENLABS_VOICE_AR_EG'),
  'sw-KE': env('ELEVENLABS_VOICE_SW_KE'),
};
const fields = { 'fr-FR': 'french', 'es-ES': 'spanish', 'ht-HT': 'kreyol', 'en-JM': 'patois' };
const lessonLocales = Object.keys(fields);
const africaLocales = ['ln-CD', 'ar-MA', 'ar-EG', 'sw-KE'];
const paths = {
  lessons: resolve(root, 'beyond-french/data/lessons.json'),
  dictionary: resolve(root, 'beyond-french/data/dictionary.json'),
  africa: resolve(root, 'beyond-french/data/africa-expansion.json'),
  questModels: resolve(root, 'FrenchQuestApple/Sources/Models.swift'),
  webLessons: resolve(root, 'beyond-french/assets/audio/lessons'),
  webAfrica: resolve(root, 'beyond-french/assets/audio/africa-expansion'),
  frenchResources: resolve(root, 'BeyondFrenchApple/Resources'),
  questResources: resolve(root, 'FrenchQuestApple/Resources'),
  manifest: resolve(root, 'docs/french-assets-commercial-manifest.json'),
};
const json = (file) => readFile(file, 'utf8').then(JSON.parse);
const slug = (value) => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80);
const hasAudio = async (file) => { try { return (await stat(file)).size > 128; } catch { return false; } };
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function validateConfig() {
  if (!commercial) throw new Error('Set ELEVENLABS_COMMERCIAL_LICENSE=1 only after confirming the current account permits commercial speech use.');
  if (!dryRun && !apiKey) throw new Error('ELEVENLABS_API_KEY is required; it is never stored in this repository.');
  if (!/^https:\/\//i.test(endpoint)) throw new Error('ELEVENLABS_ENDPOINT must use HTTPS.');
  const required = new Set();
  if (scopes.has('all') || scopes.has('lessons')) lessonLocales.forEach((x) => required.add(x));
  if (scopes.has('all') || scopes.has('quest')) required.add('fr-FR');
  if (scopes.has('all') || scopes.has('africa')) africaLocales.forEach((x) => required.add(x));
  const missing = [...required].filter((x) => !voices[x]);
  if (missing.length) throw new Error(`Missing voice IDs for ${missing.join(', ')}. Set ELEVENLABS_VOICE_${missing[0].replace('-', '_')}.`);
}

async function synthesize(text, locale) {
  let response;
  for (let attempt = 1; attempt <= 5; attempt += 1) {
    response = await fetch(`${endpoint}/${encodeURIComponent(voices[locale])}?output_format=${encodeURIComponent(outputFormat)}`, {
      method: 'POST', headers: { 'xi-api-key': apiKey, 'Content-Type': 'application/json', Accept: 'audio/mpeg' },
      body: JSON.stringify({ text, model_id: model }),
    });
    if (response.ok) break;
    if (![408, 409, 425, 429, 500, 502, 503, 504].includes(response.status) || attempt === 5) {
      throw new Error(`ElevenLabs ${locale} request failed (${response.status}): ${(await response.text().catch(() => '')).slice(0, 180)}`);
    }
    await sleep(attempt * 1200);
  }
  const audio = Buffer.from(await response.arrayBuffer());
  if (audio.length < 128 || (!audio.subarray(0, 3).equals(Buffer.from('ID3')) && audio[0] !== 0xff)) throw new Error(`Invalid MP3 returned for ${locale}.`);
  return audio;
}

async function generate(file, text, locale, label) {
  await mkdir(dirname(file), { recursive: true });
  if (!force && await hasAudio(file)) return 'reused';
  if (dryRun) return 'planned';
  await writeFile(file, await synthesize(text, locale));
  console.log(`generated ${label}: ${file.slice(root.length + 1)}`);
  await sleep(140);
  return 'generated';
}

async function runLessons(manifest) {
  const lessons = await json(paths.lessons);
  const targets = lessons.filter((x) => x.generated_batch === 'azure-2026-08');
  for (const lesson of targets) {
    lesson.audio_urls ||= {}; lesson.audio_generation ||= {};
    const filename = `${String(lesson.id).padStart(3, '0')}-${slug(lesson.english)}.mp3`;
    for (const locale of lessonLocales) {
      const text = String(lesson[fields[locale]] || '').trim();
      if (!text) throw new Error(`Lesson ${lesson.id} has no ${fields[locale]} text.`);
      await generate(resolve(paths.webLessons, locale, filename), text, locale, `lesson ${lesson.id}`);
      // Swift derives bundled lesson names from the English phrase (without
      // the web batch's numeric prefix).
      const bundled = resolve(paths.frenchResources, 'Audio/lessons', locale, `${slug(lesson.english)}.mp3`);
      if (!dryRun) { await mkdir(dirname(bundled), { recursive: true }); await writeFile(bundled, await readFile(resolve(paths.webLessons, locale, filename))); }
      lesson.audio_urls[locale] = `/beyond-french/assets/audio/lessons/${locale}/${filename}`;
      if (locale === 'fr-FR') lesson.audio_url = lesson.audio_urls[locale];
      lesson.audio_generation[locale] = { provider: 'elevenlabs', model, voice: voices[locale], license: 'commercial-account-confirmed' };
    }
  }
  if (!dryRun) await writeFile(paths.lessons, `${JSON.stringify(lessons, null, 2)}\n`);
  manifest.lessons = { count: targets.length, locales: lessonLocales, output: 'beyond-french/assets/audio/lessons' };
}

function questPhrases(source) {
  const out = [];
  const re = /phrase:\s*"((?:\\.|[^"\\])*)"/g;
  for (const match of source.matchAll(re)) {
    const phrase = JSON.parse(`"${match[1]}"`);
    if (phrase && !out.includes(phrase)) out.push(phrase);
  }
  return out;
}

async function runQuest(manifest) {
  const phrases = questPhrases(await readFile(paths.questModels, 'utf8'));
  for (const phrase of phrases) await generate(resolve(paths.questResources, 'Audio/quest/fr-FR', `${slug(phrase)}.mp3`), phrase, 'fr-FR', `quest ${phrase}`);
  manifest.quest = { count: phrases.length, locale: 'fr-FR', output: 'FrenchQuestApple/Resources/Audio/quest/fr-FR' };
}

async function runDictionary(manifest) {
  const dictionary = await json(paths.dictionary);
  for (const entry of dictionary) {
    for (const locale of lessonLocales) {
      const text = String(entry[fields[locale]] || '').trim();
      if (!text) continue;
      const file = `${slug(entry.english)}.mp3`;
      const frenchFile = resolve(paths.frenchResources, 'Audio/dictionary', locale, file);
      await generate(frenchFile, text, locale, `dictionary ${entry.english}`);
      // Keep one ElevenLabs request per phrase and mirror the validated MP3
      // into French Quest's resource tree.
      if (!dryRun) {
        const questFile = resolve(paths.questResources, 'Audio/dictionary', locale, file);
        await mkdir(dirname(questFile), { recursive: true });
        await writeFile(questFile, await readFile(frenchFile));
      }
    }
  }
  manifest.dictionary = { count: dictionary.length, locales: lessonLocales, outputs: ['BeyondFrenchApple/Resources/Audio/dictionary', 'FrenchQuestApple/Resources/Audio/dictionary'] };
}

async function runAfrica(manifest) {
  const items = await json(paths.africa);
  if (!items.length) throw new Error('beyond-french/data/africa-expansion.json is empty; populate reviewed Euro-African phrases before generating audio.');
  const tracks = [
    ['ln', 'ln-CD', 'lingala'], ['ma', 'ar-MA', 'darija'],
    ['eg', 'ar-EG', 'egyptian_arabic'], ['sw', 'sw-KE', 'swahili'],
  ];
  for (const item of items) {
    const date = String(item.publish_date || item.id).trim();
    for (const [key, locale, field] of tracks) {
      const text = String(item[field] || '').trim();
      if (!text) throw new Error(`Africa item ${item.id} has no ${field} text.`);
      await generate(resolve(paths.webAfrica, locale, `${date}.mp3`), text, locale, `Africa ${item.id}`);
      item.audio_urls ||= {}; item.audio_urls[key] = `/beyond-french/assets/audio/africa-expansion/${locale}/${date}.mp3`;
      item.audio_voices ||= {}; item.audio_voices[key] = voices[locale];
      item.audio_providers ||= {}; item.audio_providers[key] = 'elevenlabs';
    }
  }
  if (!dryRun) await writeFile(paths.africa, `${JSON.stringify(items, null, 2)}\n`);
  manifest.africa = { count: items.length, locales: africaLocales, output: 'beyond-french/assets/audio/africa-expansion' };
}

validateConfig();
if (scopes.has('all') || scopes.has('africa')) {
  const africaItems = await json(paths.africa);
  if (!Array.isArray(africaItems) || !africaItems.length) {
    throw new Error('beyond-french/data/africa-expansion.json is empty; populate reviewed Euro-African phrases before generating audio.');
  }
  const unreviewed = africaItems.filter((item) => item.native_reviewed !== true);
  if (unreviewed.length) {
    throw new Error(`${unreviewed.length} Africa record(s) are not marked native_reviewed; obtain native review before generating audio.`);
  }
}
const manifest = {
  generated_at: new Date().toISOString(), provider: 'elevenlabs', model, output_format: outputFormat,
  commercial_license_acknowledged: commercial, voices, dry_run: dryRun,
};
if (scopes.has('all') || scopes.has('lessons')) await runLessons(manifest);
if (scopes.has('all') || scopes.has('dictionary')) await runDictionary(manifest);
if (scopes.has('all') || scopes.has('quest')) await runQuest(manifest);
if (scopes.has('all') || scopes.has('africa')) await runAfrica(manifest);
if (!dryRun) await writeFile(paths.manifest, `${JSON.stringify(manifest, null, 2)}\n`);
console.log(JSON.stringify(manifest, null, 2));
