#!/usr/bin/env node
import { readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const recoverySourcePath = resolve(root, 'server/admin/daily-studio/api/random-content.php');
const biblePath = resolve(root, 'dailybreath/data/engwebp_vpl.txt');
const webOutputPath = resolve(root, 'dailybreath/data/daily-verses.json');
const appOutputPath = resolve(root, 'DailyBreathApple/Resources/daily-verses.json');
const webDevotionalsPath = resolve(root, 'dailybreath/data/daily-devotionals.json');
const appDevotionalsPath = resolve(root, 'DailyBreathApple/Resources/daily-devotionals.json');
const webChallengesPath = resolve(root, 'dailybreath/data/recovery-challenges.json');
const appChallengesPath = resolve(root, 'DailyBreathApple/Resources/recovery-challenges.json');
const batchId = 'recovery-2026-08-16';
const startDate = new Date('2026-08-16T12:00:00Z');

const challengeSource = [
  ['2026-08-16', 'Build Your Support Circle', 'Move recovery out of isolation by making one intentional connection each day.', 'Ecclesiastes 4:9', ['List three safe people or resources.', 'Contact one person before a difficult moment.', 'End each day by noting where support helped.']],
  ['2026-08-19', 'Bonus: Ten-Minute Pause', 'Practice delaying urges long enough for choice to return.', 'Psalm 46:10', ['Set a ten-minute timer when an urge rises.', 'Breathe slowly and change your location.', 'Choose the next healthy action when the timer ends.']],
  ['2026-08-23', 'Clear One Trigger', 'Reduce one source of easy access to an old pattern and replace it with support.', 'Proverbs 4:23', ['Identify one repeat trigger.', 'Remove or block its easiest access point.', 'Place a healthy alternative in the same path.']],
  ['2026-08-30', 'Tell the Truth Gently', 'Practice one honest recovery conversation without shame or exaggeration.', 'Ephesians 4:25', ['Choose a trusted listener.', 'Name what happened and what you need.', 'Agree on one concrete follow-up.']],
  ['2026-09-06', 'Morning Before Messages', 'Give the first minutes of the day to grounding rather than urgency.', 'Psalm 143:8', ['Keep your phone out of reach overnight.', 'Read one recovery verse after waking.', 'Choose today’s support step before opening messages.']],
  ['2026-09-09', 'Bonus: Craving Care Kit', 'Prepare a small set of tools for high-pressure moments.', '1 Corinthians 10:13', ['Add water and a grounding reminder.', 'Save two support contacts.', 'Include one activity that changes your environment.']],
  ['2026-09-13', 'Practice HALT Awareness', 'Notice when hunger, anger, loneliness, or tiredness is increasing vulnerability.', '1 Peter 5:8', ['Check HALT at midday and evening.', 'Respond to the need before judging it.', 'Record which state most affects your choices.']],
  ['2026-09-20', 'Replace, Don’t Just Remove', 'Build a life-giving routine where an old habit used to live.', 'Romans 12:2', ['Choose one high-risk time of day.', 'Plan a specific healthy replacement.', 'Repeat it at the same time for seven days.']],
  ['2026-09-27', 'One Day of Clean Inputs', 'Protect your attention from media or environments that intensify harmful urges.', 'Philippians 4:8', ['Identify triggering input.', 'Mute, unfollow, or avoid it for one day.', 'Fill the space with something peaceful and true.']],
  ['2026-09-30', 'Bonus: Five Honest Minutes', 'Use a short daily journal check-in to catch pressure before it grows.', 'Psalm 139:23', ['Name the strongest feeling.', 'Name the strongest urge.', 'Write the safest next action.']],
  ['2026-10-04', 'Move Through the Urge', 'Use gentle movement to lower stress and interrupt automatic behavior.', 'Isaiah 40:31', ['Choose a safe ten-minute movement.', 'Begin when pressure rises, not after it peaks.', 'Notice how the urge changes before and after.']],
  ['2026-10-11', 'Repair One Small Thing', 'Strengthen recovery by taking one manageable step toward restored trust.', 'Romans 12:18', ['Choose a repair that is safe and appropriate.', 'Own your part without demanding a response.', 'Let consistent action support your words.']],
  ['2026-10-18', 'Protect Your Evening', 'Create a calmer final hour that supports sleep and lowers late-day vulnerability.', 'Psalm 4:8', ['Set a stopping time for stimulating media.', 'Prepare tomorrow’s first healthy choice.', 'End with a brief prayer or breathing practice.']],
  ['2026-10-21', 'Bonus: Gratitude Without Denial', 'Notice what is good while remaining honest about what is hard.', '1 Thessalonians 5:18', ['Name one difficult truth.', 'Name three sources of help or goodness.', 'Thank someone who supported your recovery.']],
  ['2026-10-25', 'Ask Before the Crisis', 'Reach for support while the pressure is still manageable.', 'Psalm 50:15', ['Rate pressure from one to ten each afternoon.', 'Contact support at five instead of waiting for nine.', 'Write down what made early help useful.']],
  ['2026-11-01', 'Practice a Clean No', 'Use a clear boundary to protect recovery without overexplaining.', 'Galatians 5:1', ['Identify one risky invitation or request.', 'Prepare a short respectful no.', 'Offer a safe alternative only if you want to.']],
  ['2026-11-08', 'Serve Without Escaping', 'Choose one act of service that connects you to purpose while honoring your limits.', 'Galatians 5:13', ['Choose a small useful act.', 'Keep the commitment realistic.', 'Reflect on the difference between service and avoidance.']],
  ['2026-11-11', 'Bonus: Reset After a Slip', 'Respond to a setback quickly with truth, support, and a renewed plan.', 'Lamentations 3:23', ['Interrupt shame and tell the truth.', 'Contact support and reduce immediate risk.', 'Write one lesson and restart today.']],
  ['2026-11-15', 'Celebrate Real Progress', 'Recognize growth without waiting for perfection.', 'Philippians 1:6', ['List three changes you have practiced.', 'Thank someone who helped.', 'Choose the next milestone worth pursuing.']],
  ['2026-11-22', 'Carry Hope Forward', 'Build a simple plan for continuing recovery beyond this campaign.', 'Romans 15:13', ['Keep the three tools that helped most.', 'Schedule your next support connection.', 'Write a promise to your future self.']],
];

// One reference per day, inclusive, from August 16 through November 23, 2026.
const scheduledReferences = `
PSA 3:3
PSA 4:8
PSA 9:9
PSA 16:8
PSA 18:2
PSA 23:1
PSA 23:4
PSA 27:1
PSA 27:14
PSA 30:5
PSA 31:24
PSA 32:7
PSA 34:4
PSA 34:8
PSA 37:5
PSA 40:1
PSA 42:11
PSA 56:3
PSA 61:2
PSA 62:1
PSA 62:5
PSA 63:8
PSA 68:19
PSA 73:26
PSA 84:11
PSA 91:1
PSA 91:2
PSA 94:19
PSA 103:2
PSA 103:5
PSA 118:5
PSA 118:14
PSA 118:24
PSA 119:9
PSA 119:11
PSA 119:28
PSA 119:50
PSA 119:105
PSA 121:1
PSA 121:2
PSA 121:7
PSA 130:5
PSA 138:3
PSA 139:23
PSA 143:8
PRO 4:23
PRO 12:25
PRO 16:3
PRO 18:10
PRO 24:16
ECC 3:1
ECC 4:9
ISA 26:3
ISA 30:15
ISA 35:4
ISA 40:31
ISA 43:1
ISA 43:18
ISA 43:19
ISA 44:22
ISA 49:15
ISA 57:15
JER 17:7
JER 29:11
JER 31:3
JER 33:3
EZE 36:26
MIC 7:8
NAH 1:7
MAT 5:4
MAT 5:6
MAT 6:13
MAT 6:25
MAT 6:33
MAT 7:7
MAT 11:29
MAT 17:20
MAT 19:26
MAR 9:23
MAR 10:27
MAR 11:24
LUK 1:37
LUK 12:32
LUK 18:27
JOH 14:1
JOH 14:27
JOH 15:5
JOH 16:33
ACT 2:21
ACT 16:31
ROM 5:3
ROM 5:4
ROM 5:5
ROM 8:1
ROM 8:6
ROM 8:28
ROM 8:31
ROM 8:37
ROM 13:14
ROM 15:13
`.trim().split('\n').map((value) => value.trim());

const bookNames = {
  PSA: 'Psalm', PRO: 'Proverbs', ECC: 'Ecclesiastes', ISA: 'Isaiah', JER: 'Jeremiah',
  EZE: 'Ezekiel', MIC: 'Micah', NAH: 'Nahum', MAT: 'Matthew', MAR: 'Mark', LUK: 'Luke',
  JOH: 'John', ACT: 'Acts', ROM: 'Romans', '1CO': '1 Corinthians', '2CO': '2 Corinthians',
  GAL: 'Galatians', EPH: 'Ephesians', PHI: 'Philippians', '2TI': '2 Timothy', TIT: 'Titus',
  HEB: 'Hebrews', JAM: 'James', '1PE': '1 Peter', '1JO': '1 John', LAM: 'Lamentations',
};

function slugify(value) {
  return value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function themeFor(text) {
  const value = text.toLowerCase();
  if (/free|liberty|chain|deliver|escape|dominion/.test(value)) return 'freedom';
  if (/fear|afraid|courage|bold|dismay/.test(value)) return 'courage';
  if (/peace|rest|still|quiet|sleep|anxious|worr/.test(value)) return 'peace';
  if (/endure|persever|wait|hope|race|rise|fall/.test(value)) return 'perseverance';
  if (/heart|mind|self-control|tempt|body|sober/.test(value)) return 'self-control';
  if (/trust|faith|believ/.test(value)) return 'faith';
  return 'spiritual support';
}

function createEntry({ id, text, reference, scheduleDate = null, generatedBatch = null }) {
  return {
    id,
    text,
    reference,
    theme: themeFor(text),
    schedule_date: scheduleDate,
    audio_file: `verse-${slugify(reference)}.mp3`,
    translation: 'WEB',
    generated_batch: generatedBatch,
  };
}

const devotionalThemes = {
  freedom: {
    title: 'Practice Freedom Today',
    excerpt: 'Grace makes room for a choice that is no longer controlled by the old pattern.',
    response: 'Recovery grows when freedom becomes a present-tense practice. You do not have to solve every future urge; you can receive enough grace to choose the next healthy step.',
    prayer: 'God of freedom, loosen the grip of every harmful pattern and help me choose what leads to life today.',
    practice: 'Name one trigger and write down the free, healthy response you will choose when it appears.',
  },
  courage: {
    title: 'Courage for the Next Step',
    excerpt: 'Fear can speak loudly without receiving the final word.',
    response: 'Courage in recovery is rarely the absence of fear. It is the decision to reach for help, tell the truth, and take one faithful step while fear is still present.',
    prayer: 'God of courage, steady me when fear rises and give me strength for the next honest step.',
    practice: 'Send one honest message to a trusted person before the day ends.',
  },
  peace: {
    title: 'Make Room for Peace',
    excerpt: 'A slower breath can interrupt urgency and reopen the space where wise choices live.',
    response: 'Peace does not require pretending that the craving or pressure is small. It lets you pause long enough to remember that the feeling is temporary and that you are not facing it alone.',
    prayer: 'God of peace, quiet the urgency in me and guard my thoughts while I wait for this moment to pass.',
    practice: 'Take five slow breaths, then delay any impulsive choice for ten minutes.',
  },
  perseverance: {
    title: 'Keep Going with Grace',
    excerpt: 'Lasting change is built through faithful returns, not flawless days.',
    response: 'A difficult moment does not erase your progress. Perseverance means returning to the path, learning from what happened, and refusing to let shame turn one struggle into surrender.',
    prayer: 'Faithful God, renew my hope and help me return to the path with humility and determination.',
    practice: 'Write down one sign of progress from the last week, however small.',
  },
  'self-control': {
    title: 'Choose What Strengthens You',
    excerpt: 'Self-control grows through prepared choices, supportive boundaries, and practiced attention.',
    response: 'Recovery becomes more durable when the healthy choice is made easier before pressure arrives. Boundaries are not punishment; they protect the person you are becoming.',
    prayer: 'God of wisdom, strengthen my mind and help me prepare choices that protect my healing.',
    practice: 'Remove one easy access point to a harmful habit and replace it with a healthy alternative.',
  },
  faith: {
    title: 'Trust This Moment to God',
    excerpt: 'Trust brings what feels unmanageable into the care of One who sees the whole road.',
    response: 'Faith does not deny the work recovery requires. It keeps that work rooted in hope, invites support, and reminds you that your identity is larger than your struggle.',
    prayer: 'God, I entrust this moment to you. Guide my choices and help me receive the support I need.',
    practice: 'Turn your biggest concern into a one-sentence prayer, then share the next practical step with someone safe.',
  },
  'spiritual support': {
    title: 'You Are Not Alone',
    excerpt: 'Support becomes strength when you allow yourself to receive it.',
    response: 'Isolation gives harmful patterns more room to grow. Recovery deepens through honest connection—with God, with wise helpers, and with people who can remind you of hope when your own voice is tired.',
    prayer: 'Present God, help me receive your care and recognize the people you have placed beside me.',
    practice: 'Identify one person or support resource you can contact today and keep that connection within reach.',
  },
};

function createDevotional(entry, index) {
  const copy = devotionalThemes[entry.theme] || devotionalThemes['spiritual support'];
  const companionDate = new Date(startDate);
  companionDate.setUTCDate(companionDate.getUTCDate() + index);
  return {
    id: `devotional-${String(index + 1).padStart(3, '0')}`,
    verse_id: entry.id,
    title: `${copy.title} — ${entry.reference}`,
    excerpt: copy.excerpt,
    body: `${entry.reference} says, “${entry.text}” ${copy.response} Let this verse shape the next decision rather than asking it to fix the entire journey at once.`,
    scripture_reference: entry.reference,
    prayer: copy.prayer,
    practice: copy.practice,
    duration_minutes: 4,
    theme: entry.theme,
    schedule_date: entry.schedule_date || companionDate.toISOString().slice(0, 10),
    schedule_role: entry.schedule_date ? 'primary' : 'companion',
    generated_batch: batchId,
  };
}

if (scheduledReferences.length !== 100 || new Set(scheduledReferences).size !== 100) {
  throw new Error('The scheduled reference list must contain exactly 100 unique entries.');
}

const [recoverySource, bibleSource] = await Promise.all([
  readFile(recoverySourcePath, 'utf8'),
  readFile(biblePath, 'utf8'),
]);

const recoveryStart = recoverySource.indexOf('$englishRecovery = [');
const recoveryEnd = recoverySource.indexOf('];', recoveryStart);
if (recoveryStart < 0 || recoveryEnd < 0) throw new Error('Could not find the existing English recovery bank.');
const recoveryBlock = recoverySource.slice(recoveryStart, recoveryEnd);
const existingMatches = [...recoveryBlock.matchAll(/\['([^']+)','([^']+)','([^']+)'\]/gu)];
if (existingMatches.length !== 38) throw new Error(`Expected 38 existing recovery entries, found ${existingMatches.length}.`);

const titleReference = (reference) => reference.toLowerCase().replace(/(^|\s)[a-z]/g, (match) => match.toUpperCase());
const existingEntries = existingMatches.map((match, index) => createEntry({
  id: `recovery-${String(index + 1).padStart(3, '0')}`,
  text: match[2],
  reference: titleReference(match[3]),
}));

const bible = new Map();
for (const line of bibleSource.split(/\r?\n/u)) {
  const match = line.match(/^([1-3]?[A-Z]{2,3})\s+(\d+):(\d+)\s+(.+)$/u);
  if (match) bible.set(`${match[1]} ${match[2]}:${match[3]}`, match[4].trim());
}

const scheduledEntries = scheduledReferences.map((referenceCode, index) => {
  const match = referenceCode.match(/^([1-3]?[A-Z]{2,3})\s+(\d+):(\d+)$/u);
  if (!match || !bookNames[match[1]]) throw new Error(`Unsupported reference: ${referenceCode}`);
  const text = bible.get(referenceCode);
  if (!text) throw new Error(`Verse text not found: ${referenceCode}`);
  const date = new Date(startDate);
  date.setUTCDate(date.getUTCDate() + index);
  return createEntry({
    id: `recovery-${String(existingEntries.length + index + 1).padStart(3, '0')}`,
    text,
    reference: `${bookNames[match[1]]} ${Number(match[2])}:${Number(match[3])}`,
    scheduleDate: date.toISOString().slice(0, 10),
    generatedBatch: batchId,
  });
});

const entries = [...existingEntries, ...scheduledEntries];
const referenceKeys = entries.map((entry) => slugify(entry.reference));
if (entries.length !== 138 || new Set(referenceKeys).size !== 138) {
  throw new Error(`Expected 138 unique recovery entries, found ${entries.length} entries and ${new Set(referenceKeys).size} unique references.`);
}

const document = {
  schema_version: 1,
  title: 'Daily Breath Recovery Verse Library',
  translation: 'World English Bible',
  separate_from_bible_library: true,
  entry_count: entries.length,
  scheduled_batch: {
    id: batchId,
    starts_on: scheduledEntries[0].schedule_date,
    ends_on: scheduledEntries.at(-1).schedule_date,
    entry_count: scheduledEntries.length,
  },
  entries,
};
const devotionals = entries.map(createDevotional);
const devotionalDocument = {
  schema_version: 1,
  title: 'Daily Breath Recovery Devotional Library',
  entry_count: devotionals.length,
  primary_schedule: {
    starts_on: scheduledEntries[0].schedule_date,
    ends_on: scheduledEntries.at(-1).schedule_date,
    scheduled_entries: devotionals.length,
    primary_entries: devotionals.filter((entry) => entry.schedule_role === 'primary').length,
    companion_entries: devotionals.filter((entry) => entry.schedule_role === 'companion').length,
  },
  entries: devotionals,
};
if (challengeSource.length !== 20 || new Set(challengeSource.map((entry) => entry[1])).size !== 20) {
  throw new Error('The recovery challenge library must contain exactly 20 unique challenges.');
}
const challenges = challengeSource.map(([startsOn, title, description, scriptureReference, steps], index) => {
  const endsOn = new Date(`${startsOn}T12:00:00Z`);
  endsOn.setUTCDate(endsOn.getUTCDate() + 6);
  return {
    id: `challenge-${String(index + 1).padStart(2, '0')}`,
    title,
    description,
    scripture_reference: scriptureReference,
    steps,
    target_count: 7,
    starts_on: startsOn,
    ends_on: endsOn.toISOString().slice(0, 10) > '2026-11-23' ? '2026-11-23' : endsOn.toISOString().slice(0, 10),
    schedule_type: title.startsWith('Bonus:') ? 'bonus' : 'weekly',
    generated_batch: batchId,
  };
});
const challengeDocument = {
  schema_version: 1,
  title: 'Daily Breath Recovery Challenge Library',
  entry_count: challenges.length,
  starts_on: challenges[0].starts_on,
  ends_on: '2026-11-23',
  entries: challenges,
};

const json = `${JSON.stringify(document, null, 2)}\n`;
const devotionalJson = `${JSON.stringify(devotionalDocument, null, 2)}\n`;
const challengeJson = `${JSON.stringify(challengeDocument, null, 2)}\n`;
await Promise.all([
  writeFile(webOutputPath, json, 'utf8'),
  writeFile(appOutputPath, json, 'utf8'),
  writeFile(webDevotionalsPath, devotionalJson, 'utf8'),
  writeFile(appDevotionalsPath, devotionalJson, 'utf8'),
  writeFile(webChallengesPath, challengeJson, 'utf8'),
  writeFile(appChallengesPath, challengeJson, 'utf8'),
]);

console.log(JSON.stringify({
  existingEntries: existingEntries.length,
  scheduledEntries: scheduledEntries.length,
  totalEntries: entries.length,
  devotionals: devotionals.length,
  challenges: challenges.length,
  startsOn: document.scheduled_batch.starts_on,
  endsOn: document.scheduled_batch.ends_on,
}));
