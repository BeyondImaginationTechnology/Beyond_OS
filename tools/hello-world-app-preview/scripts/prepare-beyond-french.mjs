import fs from 'node:fs/promises';
import path from 'node:path';

const projectRoot = path.resolve('.');
const publicDir = path.join(projectRoot, 'public');
const assetDir = path.join(publicDir, 'beyond-french');
const outputPath = path.join(publicDir, 'beyond-french.render.json');
const configUrl = process.env.BEYOND_FRENCH_CONFIG_URL || '';
const requestedLessonId = Number(
  process.env.BEYOND_FRENCH_LESSON_ID || process.argv[2] || 1,
);
const requestedAudio = process.env.BEYOND_FRENCH_AUDIO_FILE || '';

await fs.mkdir(assetDir, {recursive: true});

const isRemote = (value) => /^https?:\/\//i.test(String(value || ''));

async function download(url, filename) {
  const response = await fetch(url, {
    headers: {'User-Agent': 'Beyond-French-Video-Renderer/1.0'},
  });
  if (!response.ok) {
    throw new Error(`Download failed (${response.status}): ${url}`);
  }
  await fs.writeFile(
    path.join(assetDir, filename),
    new Uint8Array(await response.arrayBuffer()),
  );
  return `beyond-french/${filename}`;
}

let config;
if (configUrl) {
  const response = await fetch(configUrl, {
    headers: {'User-Agent': 'Beyond-French-Video-Renderer/1.0'},
  });
  if (!response.ok) {
    throw new Error(`Config request failed (${response.status})`);
  }
  const payload = await response.json();
  config = payload.video || payload;
} else {
  const lessonsPath = path.resolve(
    projectRoot,
    '../../beyond-french/data/lessons.json',
  );
  const lessons = JSON.parse(await fs.readFile(lessonsPath, 'utf8'));
  const lesson =
    lessons.find((entry) => Number(entry.id) === requestedLessonId) || lessons[0];
  if (!lesson) throw new Error('No Beyond French lessons are available.');
  config = {
    lessonId: Number(lesson.id),
    english: String(lesson.english || ''),
    french: String(lesson.french || ''),
    kreyol: String(lesson.kreyol || ''),
    spanish: String(lesson.spanish || ''),
    patois: String(lesson.patois || ''),
    category: String(lesson.category || 'Daily'),
    audioFile: requestedAudio,
  };
}

if (isRemote(config.audioFile)) {
  config.audioFile = await download(config.audioFile, 'daily-narration.mp3');
} else if (config.audioFile) {
  await fs.copyFile(
    path.resolve(config.audioFile),
    path.join(assetDir, 'daily-narration.mp3'),
  );
  config.audioFile = 'beyond-french/daily-narration.mp3';
}

await fs.writeFile(outputPath, `${JSON.stringify(config, null, 2)}\n`);
console.log(
  `Prepared lesson ${config.lessonId || requestedLessonId}: ${outputPath}`,
);
