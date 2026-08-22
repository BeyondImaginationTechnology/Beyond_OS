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
  if (app !== 'BeyondFrenchApple') continue;
  const locales = ['fr-FR', 'es-ES', 'ht-HT', 'en-JM'];
  for (const lesson of lessons.filter((item) => item.generated_batch === 'azure-2026-08' && item.audio_url)) {
    for (const locale of locales) {
      const publicUrl = lesson.audio_urls?.[locale] || (locale === 'fr-FR' ? lesson.audio_url : '');
      if (!publicUrl) continue;
      const source = resolve(root, 'beyond-french/assets/audio/lessons', locale, basename(publicUrl));
      const legacyFrenchSource = resolve(root, 'beyond-french/assets/audio/french', basename(publicUrl));
      const audioDir = resolve(resources, 'Audio/lessons', locale);
      await mkdir(audioDir, { recursive: true });
      try {
        await copyFile(source, resolve(audioDir, `${slug(lesson.english)}.mp3`));
      } catch (error) {
        if (locale !== 'fr-FR' || error?.code !== 'ENOENT') throw error;
        await copyFile(legacyFrenchSource, resolve(audioDir, `${slug(lesson.english)}.mp3`));
      }
    }
  }
}

const lessonClipsPerApp = lessons
  .filter((item) => item.generated_batch === 'azure-2026-08')
  .reduce((count, item) => count + ['fr-FR', 'es-ES', 'ht-HT', 'en-JM'].filter((locale) => item.audio_urls?.[locale] || (locale === 'fr-FR' && item.audio_url)).length, 0);
console.log(JSON.stringify({ dictionaryEntries: portableDictionary.length, prerecordedLessonClipsPerApp: lessonClipsPerApp, apps }));
