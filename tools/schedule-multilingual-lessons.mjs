#!/usr/bin/env node
import { readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const bankPath = resolve(root, 'beyond-french/data/multilingual-bank.json');
const schedulePath = resolve(root, 'beyond-french/data/multilingual-lessons.json');
const startDate = process.env.MULTILINGUAL_SCHEDULE_START || new Date().toISOString().slice(0, 10);

const russianMap = {
  А:'A', Б:'B', В:'V', Г:'G', Д:'D', Е:'Ye', Ё:'Yo', Ж:'Zh', З:'Z', И:'I', Й:'Y', К:'K', Л:'L', М:'M', Н:'N', О:'O', П:'P', Р:'R', С:'S', Т:'T', У:'U', Ф:'F', Х:'Kh', Ц:'Ts', Ч:'Ch', Ш:'Sh', Щ:'Shch', Ъ:'', Ы:'Y', Ь:'', Э:'E', Ю:'Yu', Я:'Ya',
  а:'a', б:'b', в:'v', г:'g', д:'d', е:'ye', ё:'yo', ж:'zh', з:'z', и:'i', й:'y', к:'k', л:'l', м:'m', н:'n', о:'o', п:'p', р:'r', с:'s', т:'t', у:'u', ф:'f', х:'kh', ц:'ts', ч:'ch', ш:'sh', щ:'shch', ъ:'', ы:'y', ь:'', э:'e', ю:'yu', я:'ya',
};

const romanizeRussian = (value) => [...String(value)].map((character) => russianMap[character] ?? character).join('');
const addDays = (date, offset) => {
  const value = new Date(`${date}T12:00:00Z`);
  if (Number.isNaN(value.valueOf())) throw new Error(`Invalid schedule start date: ${date}`);
  value.setUTCDate(value.getUTCDate() + offset);
  return value.toISOString().slice(0, 10);
};

const bank = JSON.parse(await readFile(bankPath, 'utf8'));
if (!Array.isArray(bank) || !bank.length) throw new Error('The multilingual bank is empty.');

for (const item of bank) {
  item.italian_pronunciation ||= item.italian;
  item.german_pronunciation ||= item.german;
  item.russian_pronunciation ||= romanizeRussian(item.russian);
  item.portuguese_pronunciation ||= item.portuguese;
}

const complete = bank.filter((item) => Object.keys(item.audio_urls || {}).length === 5);
const schedule = complete.map((item, index) => ({
  ...item,
  id: index + 1,
  date: addDays(startDate, index),
  generator: {
    version: '1.3.0',
    provider: 'azure',
    schedule: 'automatic-bank',
    scheduled_at: new Date().toISOString(),
  },
}));

await writeFile(bankPath, `${JSON.stringify(bank, null, 2)}\n`, 'utf8');
await writeFile(schedulePath, `${JSON.stringify(schedule, null, 2)}\n`, 'utf8');
console.log(JSON.stringify({ bank: bank.length, scheduled: schedule.length, startsOn: schedule[0]?.date, endsOn: schedule.at(-1)?.date }));
