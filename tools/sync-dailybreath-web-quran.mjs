import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const source = path.join(root, 'DailyBreathApple', 'Resources', 'quran-pickthall-vpl.txt');
const destination = path.join(root, 'dailybreath', 'data', 'quran-pickthall-vpl.txt');

if (!fs.existsSync(source)) throw new Error(`Missing verified Quran resource: ${source}`);

const content = fs.readFileSync(source, 'utf8');
const verses = content.split(/\r?\n/).filter(line => line && !line.startsWith('#'));
const surahs = new Set(verses.map(line => Number(line.split('|', 1)[0])));
if (verses.length !== 6236 || surahs.size !== 114) {
  throw new Error(`Expected 114 surahs and 6,236 ayahs; found ${surahs.size} and ${verses.length}.`);
}

fs.writeFileSync(destination, content, 'utf8');
console.log(JSON.stringify({ destination, surahs: surahs.size, ayahs: verses.length }));
