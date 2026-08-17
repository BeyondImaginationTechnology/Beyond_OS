import { mkdir, readFile, writeFile, access } from 'node:fs/promises';
import { constants } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const lessonsPath = resolve(root, 'beyond-french/data/lessons.json');
const dictionaryPath = resolve(root, 'beyond-french/data/dictionary.json');
const outputDir = resolve(root, 'beyond-french/assets/audio/french');
const key = String(process.env.AZURE_SPEECH_KEY || '').trim();
const region = String(process.env.AZURE_SPEECH_REGION || 'canadacentral').trim().toLowerCase();
const voice = String(process.env.AZURE_SPEECH_VOICE || 'fr-CA-SylvieNeural').trim();

if (!key) throw new Error('Set AZURE_SPEECH_KEY in the current shell before running this batch.');
if (!/^[a-z0-9-]+$/.test(region)) throw new Error('AZURE_SPEECH_REGION is invalid.');

const lessons = JSON.parse(await readFile(lessonsPath, 'utf8'));
const dictionary = JSON.parse(await readFile(dictionaryPath, 'utf8'));
const targets = lessons.filter((lesson) => lesson.generated_batch === 'azure-2026-08');
await mkdir(outputDir, { recursive: true });

const xml = (value) => String(value).replace(/[<>&"']/g, (char) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&apos;' })[char]);
const slug = (value) => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 54);
const sleep = (ms) => new Promise((resolvePromise) => setTimeout(resolvePromise, ms));

let generated = 0;
let reused = 0;
for (const [index, lesson] of targets.entries()) {
  const filename = `${String(lesson.id).padStart(3, '0')}-${slug(lesson.english)}.mp3`;
  const file = resolve(outputDir, filename);
  const publicUrl = `/beyond-french/assets/audio/french/${filename}`;
  try {
    await access(file, constants.R_OK);
    reused += 1;
  } catch {
    const ssml = `<speak version="1.0" xml:lang="fr-CA"><voice name="${xml(voice)}"><prosody rate="-10%">${xml(lesson.french)}</prosody></voice></speak>`;
    let response;
    for (let attempt = 1; attempt <= 4; attempt += 1) {
      response = await fetch(`https://${region}.tts.speech.microsoft.com/cognitiveservices/v1`, {
        method: 'POST',
        headers: {
          'Ocp-Apim-Subscription-Key': key,
          'Content-Type': 'application/ssml+xml',
          'X-Microsoft-OutputFormat': 'audio-24khz-48kbitrate-mono-mp3',
          'User-Agent': 'BeyondFrenchBatch',
        },
        body: ssml,
      });
      if (response.ok) break;
      if (![408, 409, 425, 429, 500, 502, 503, 504].includes(response.status) || attempt === 4) {
        throw new Error(`Azure request failed for lesson ${lesson.id} with HTTP ${response.status}`);
      }
      await sleep(attempt * 1500);
    }
    const audio = Buffer.from(await response.arrayBuffer());
    if (audio.length < 128 || !audio.subarray(0, 3).equals(Buffer.from('ID3')) && audio[0] !== 0xff) {
      throw new Error(`Azure returned invalid MP3 data for lesson ${lesson.id}`);
    }
    await writeFile(file, audio);
    generated += 1;
    await sleep(180);
  }
  lesson.audio_url = publicUrl;
  const word = dictionary.find((item) => Number(item.lesson_id) === Number(lesson.id));
  if (word) word.audio_url = publicUrl;
  process.stdout.write(`\r${index + 1}/${targets.length} ready`);
}

await writeFile(lessonsPath, `${JSON.stringify(lessons, null, 2)}\n`, 'utf8');
await writeFile(dictionaryPath, `${JSON.stringify(dictionary, null, 2)}\n`, 'utf8');
console.log(`\nAzure batch complete: ${generated} generated, ${reused} reused.`);
