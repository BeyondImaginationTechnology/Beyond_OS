#!/usr/bin/env node
import { access, copyFile, mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { constants } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const lessonsPath = resolve(root, 'beyond-french/data/lessons.json');
const outputRoot = resolve(root, 'beyond-french/assets/audio/lessons');
const legacyFrenchRoot = resolve(root, 'beyond-french/assets/audio/french');
const key = String(process.env.AZURE_SPEECH_KEY || '').trim();
const region = String(process.env.AZURE_SPEECH_REGION || 'canadacentral').trim().toLowerCase();
const endpoint = String(process.env.AZURE_SPEECH_ENDPOINT || `https://${region}.tts.speech.microsoft.com`).trim().replace(/\/$/, '');
const batch = String(process.env.BEYOND_FRENCH_AUDIO_BATCH || 'azure-2026-08').trim();
const outputFormat = process.env.AZURE_SPEECH_OUTPUT_FORMAT || 'audio-24khz-48kbitrate-mono-mp3';

if (!key) throw new Error('AZURE_SPEECH_KEY is required.');
if (!/^https:\/\//i.test(endpoint)) throw new Error('AZURE_SPEECH_ENDPOINT must be an HTTPS URL.');

const languages = [
  ['fr-FR', 'french', 'fr-CA-SylvieNeural'],
  ['es-ES', 'spanish', 'en-US-JennyMultilingualNeural'],
  ['ht-HT', 'kreyol', 'en-US-JennyMultilingualNeural'],
  ['en-JM', 'patois', 'en-US-JennyMultilingualNeural'],
];

const lessons = JSON.parse(await readFile(lessonsPath, 'utf8'));
const targets = lessons.filter((lesson) => lesson.generated_batch === batch);
if (!targets.length) throw new Error(`No lessons found for batch ${batch}.`);

const xml = (value) => String(value).replace(/[<>&"']/g, (char) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&apos;' })[char]);
const slug = (value) => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 54);
const sleep = (ms) => new Promise((resolvePromise) => setTimeout(resolvePromise, ms));

async function existsWithAudio(file) {
  try {
    await access(file, constants.R_OK);
    const info = await stat(file);
    return info.size > 128;
  } catch {
    return false;
  }
}

function voiceFor(locale, fallback) {
  return String(process.env[`AZURE_SPEECH_VOICE_${locale.replace('-', '_')}`] || fallback).trim();
}

async function synthesize(text, locale, fallbackVoice) {
  const voice = voiceFor(locale, fallbackVoice);
  const ssml = `<speak version="1.0" xml:lang="${xml(locale)}"><voice name="${xml(voice)}"><prosody rate="-5%">${xml(text)}</prosody></voice></speak>`;
  let response;
  for (let attempt = 1; attempt <= 5; attempt += 1) {
    response = await fetch(`${endpoint}/cognitiveservices/v1`, {
      method: 'POST',
      headers: {
        'Ocp-Apim-Subscription-Key': key,
        'Content-Type': 'application/ssml+xml',
        'X-Microsoft-OutputFormat': outputFormat,
        'User-Agent': 'BeyondFrenchDailyLessonBatch',
        Accept: 'audio/mpeg',
      },
      body: ssml,
    });
    if (response.ok) break;
    const retryable = [408, 409, 425, 429, 500, 502, 503, 504].includes(response.status);
    if (!retryable || attempt === 5) {
      const detail = await response.text().catch(() => '');
      throw new Error(`Azure request failed (${locale}) with HTTP ${response.status}: ${detail.slice(0, 180)}`);
    }
    await sleep(attempt * 1200);
  }
  const audio = Buffer.from(await response.arrayBuffer());
  if (audio.length < 128 || (!audio.subarray(0, 3).equals(Buffer.from('ID3')) && audio[0] !== 0xff)) {
    throw new Error(`Azure returned invalid MP3 data for ${locale}.`);
  }
  return audio;
}

let generated = 0;
let reused = 0;
for (const [lessonIndex, lesson] of targets.entries()) {
  lesson.audio_urls ||= {};
  const filename = `${String(lesson.id).padStart(3, '0')}-${slug(lesson.english)}.mp3`;
  for (const [locale, field, fallbackVoice] of languages) {
    const directory = resolve(outputRoot, locale);
    const file = resolve(directory, filename);
    await mkdir(directory, { recursive: true });
    if (!(await existsWithAudio(file))) {
      const legacyFrench = resolve(legacyFrenchRoot, filename);
      if (locale === 'fr-FR' && await existsWithAudio(legacyFrench)) {
        await copyFile(legacyFrench, file);
        reused += 1;
      } else {
        const text = String(lesson[field] || '').trim();
        if (!text) throw new Error(`Lesson ${lesson.id} has no ${field} text.`);
        await writeFile(file, await synthesize(text, locale, fallbackVoice));
        generated += 1;
        await sleep(140);
      }
    } else {
      reused += 1;
    }
    lesson.audio_urls[locale] = `/beyond-french/assets/audio/lessons/${locale}/${filename}`;
    if (locale === 'fr-FR') lesson.audio_url = lesson.audio_urls[locale];
  }
  process.stdout.write(`\r${lessonIndex + 1}/${targets.length} lessons ready`);
}

await writeFile(lessonsPath, `${JSON.stringify(lessons, null, 2)}\n`, 'utf8');
console.log(`\nDaily lesson batch complete: ${generated} generated, ${reused} reused.`);
