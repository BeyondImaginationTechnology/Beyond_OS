import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const sourcePath = path.resolve(process.argv[2] ?? path.join(root, 'DailyBreathApple', 'Resources', 'quran-three-translations-source.txt'));
const outputPath = path.join(root, 'DailyBreathApple', 'Resources', 'quran-pickthall-vpl.txt');
if (!fs.existsSync(sourcePath)) {
  throw new Error(`Download https://www.gutenberg.org/cache/epub/16955/pg16955.txt and pass its path as the first argument.`);
}
const source = fs.readFileSync(sourcePath, 'utf8')
  .replace(/\r/g, '')
  // Correct four damaged verse-number lines in Gutenberg's source transcription.
  .replace(/^0\.033$/m, '017.033')
  .replace(/^039\.04$/m, '039.046')
  .replace(/^04\.032$/m, '045.032')
  .replace(/^05\.026$/m, '056.026');
const lines = source.split('\n');

let chapter = 0;
let chapterName = '';
let verseKey = null;
let activeTranslation = null;
const verses = [];
const missingPickthall = [];
const seenVerseKeys = [];
const translations = { Y: [], P: [], S: [] };

function flushVerse() {
  if (!verseKey) return;
  const text = translations.P.join(' ').replace(/\s+/g, ' ').trim();
  const [surah, verse] = verseKey.split('.').map(Number);
  if (text) {
    verses.push({ surah, verse, name: chapterName, text });
  } else {
    missingPickthall.push(verseKey);
  }
  verseKey = null;
  activeTranslation = null;
  translations.Y = [];
  translations.P = [];
  translations.S = [];
}

for (let index = 0; index < lines.length; index += 1) {
  const line = lines[index];
  const chapterMatch = line.match(/^\s*Chapter\s+(\d+):\s*$/);
  if (chapterMatch) {
    flushVerse();
    chapter = Number(chapterMatch[1]);
    for (let cursor = index + 1; cursor < lines.length; cursor += 1) {
      const candidate = lines[cursor].trim();
      if (candidate && !candidate.startsWith('-')) {
        chapterName = candidate.replace(/\s+/g, ' ');
        break;
      }
    }
    continue;
  }

  const verseMatch = line.match(/^\s*(\d{3}\.\d{3})\s*$/);
  if (verseMatch) {
    flushVerse();
    verseKey = verseMatch[1];
    seenVerseKeys.push(verseKey);
    continue;
  }

  if (!verseKey) continue;
  const translationMatch = line.match(/^([YPS]):\s*(.*)$/);
  if (translationMatch) {
    activeTranslation = translationMatch[1];
    translations[activeTranslation].push(translationMatch[2].trim());
    continue;
  }

  if (activeTranslation && line.trim() && !line.trim().startsWith('-')) {
    translations[activeTranslation].push(line.trim());
  }
}
flushVerse();

if (chapter !== 114) throw new Error(`Expected 114 chapters, found ${chapter}.`);
if (verses.length !== 6236) {
  const gaps = [];
  for (let surah = 1; surah <= 114; surah += 1) {
    const numbers = seenVerseKeys.filter(key => Number(key.slice(0, 3)) === surah).map(key => Number(key.slice(4)));
    for (let verse = 1; verse <= Math.max(...numbers); verse += 1) {
      if (!numbers.includes(verse)) gaps.push(`${surah}.${verse}`);
    }
  }
  throw new Error(`Expected 6,236 Pickthall verses, found ${verses.length}; missing translations ${missingPickthall.join(', ')}; numbering gaps ${gaps.join(', ')}.`);
}

const header = [
  '# The Meaning of the Glorious Koran — English translation by Marmaduke Pickthall',
  '# Source: Project Gutenberg eBook 16955 (public domain in the USA)',
  '# https://www.gutenberg.org/ebooks/16955',
  '# Format: surah|verse|surah name|translation',
];
const body = verses.map(({ surah, verse, name, text }) => `${surah}|${verse}|${name}|${text}`);
fs.writeFileSync(outputPath, `${[...header, ...body].join('\n')}\n`, 'utf8');

console.log(JSON.stringify({ chapters: chapter, verses: verses.length, outputPath }, null, 2));
