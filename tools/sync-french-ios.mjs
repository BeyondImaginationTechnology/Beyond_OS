import { copyFile, mkdir, readFile, writeFile } from 'node:fs/promises';
import { basename, resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const webDictionary = JSON.parse(await readFile(resolve(root, 'beyond-french/data/dictionary.json'), 'utf8'));
const lessons = JSON.parse(await readFile(resolve(root, 'beyond-french/data/lessons.json'), 'utf8'));
const apps = ['BeyondFrenchApple', 'FrenchQuestApple'];
const slug = (value) => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
const portableDictionary = webDictionary.map(({ english, french, pronunciation, spanish, kreyol, patois, type }) => ({
  english, french, pronunciation, spanish, kreyol, patois, type,
}));

for (const app of apps) {
  const resources = resolve(root, app, 'Resources');
  await writeFile(resolve(resources, 'dictionary.json'), `${JSON.stringify(portableDictionary, null, 2)}\n`, 'utf8');
  const audioDir = resolve(resources, 'Audio/dictionary/fr-FR');
  await mkdir(audioDir, { recursive: true });
  for (const lesson of lessons.filter((item) => item.generated_batch === 'azure-2026-08' && item.audio_url)) {
    const source = resolve(root, 'beyond-french/assets/audio/french', basename(lesson.audio_url));
    await copyFile(source, resolve(audioDir, `${slug(lesson.english)}.mp3`));
  }
}

console.log(JSON.stringify({ dictionaryEntries: portableDictionary.length, prerecordedFrenchClipsPerApp: lessons.filter((item) => item.generated_batch === 'azure-2026-08' && item.audio_url).length, apps }));
