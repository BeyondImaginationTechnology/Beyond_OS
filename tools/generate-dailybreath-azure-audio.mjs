import { access, copyFile, mkdir, readFile, writeFile } from 'node:fs/promises';
import { constants } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const outputDir = resolve(root, 'DailyBreathApple/Resources/Audio/Narration');
const webOutputDir = resolve(root, 'dailybreath/assets/audio/verses');
const verseLibraryPath = resolve(root, 'dailybreath/data/daily-verses.json');
const key = String(process.env.AZURE_SPEECH_KEY || '').trim();
const region = String(process.env.AZURE_SPEECH_REGION || 'canadacentral').trim().toLowerCase();
const voice = String(process.env.AZURE_SPEECH_VOICE || 'en-US-JennyNeural').trim();

if (!key) throw new Error('Set AZURE_SPEECH_KEY in the current shell before generating narration.');
if (!/^[a-z0-9-]+$/.test(region)) throw new Error('AZURE_SPEECH_REGION is invalid.');

const clips = [
  { file: 'breath-pattern-1.mp3', text: 'Peace Breath. Settle your pace before the day asks for more. Inhale for four, hold for four, and exhale for six.' },
  { file: 'breath-pattern-2.mp3', text: 'Mercy Breath. Make room for patience with yourself and others. Inhale for three, hold for three, and exhale for five.' },
  { file: 'breath-pattern-3.mp3', text: 'Courage Breath. Enter the next step with a steady heart. Inhale for four, hold for two, and exhale for four.' },
  { file: 'breath-inhale.mp3', text: 'Inhale.' },
  { file: 'breath-hold.mp3', text: 'Hold.' },
  { file: 'breath-exhale.mp3', text: 'Exhale.' },
  { file: 'breath-complete.mp3', text: 'Complete. Carry this peace with you.' },
  { file: 'verse-psalm-34-17.mp3', text: 'The righteous cry, and the Lord hears, and delivers them out of all their troubles. Psalm, chapter thirty-four, verse seventeen.' },
  { file: 'verse-psalm-46-1.mp3', text: 'God is our refuge and strength, a very present help in trouble. Psalm, chapter forty-six, verse one.' },
  { file: 'verse-psalm-46-10.mp3', text: 'Be still, and know that I am God. Psalm, chapter forty-six, verse ten.' },
  { file: 'verse-psalm-50-15.mp3', text: 'Call on me in the day of trouble. I will deliver you, and you will honor me. Psalm, chapter fifty, verse fifteen.' },
  { file: 'verse-psalm-55-22.mp3', text: 'Cast your burden on the Lord and he will sustain you. Psalm, chapter fifty-five, verse twenty-two.' },
  { file: 'verse-psalm-107-13.mp3', text: 'Then they cried to the Lord in their trouble, and he saved them out of their distresses. Psalm, chapter one hundred seven, verse thirteen.' },
  { file: 'verse-psalm-107-14.mp3', text: 'He brought them out of darkness and the shadow of death, and broke away their chains. Psalm, chapter one hundred seven, verse fourteen.' },
  { file: 'verse-proverbs-3-5.mp3', text: 'Trust in the Lord with all your heart, and don’t lean on your own understanding. Proverbs, chapter three, verse five.' },
  { file: 'verse-proverbs-3-6.mp3', text: 'In all your ways acknowledge him, and he will make your paths straight. Proverbs, chapter three, verse six.' },
  { file: 'verse-isaiah-40-29.mp3', text: 'He gives power to the weak. He increases the strength of him who has no might. Isaiah, chapter forty, verse twenty-nine.' },
  { file: 'verse-isaiah-41-10.mp3', text: 'Don’t you be afraid, for I am with you. Don’t be dismayed, for I am your God. I will strengthen you. Yes, I will help you. Isaiah, chapter forty-one, verse ten.' },
  { file: 'verse-isaiah-43-2.mp3', text: 'When you pass through the waters, I will be with you, and through the rivers, they will not overflow you. Isaiah, chapter forty-three, verse two.' },
  { file: 'verse-lamentations-3-22.mp3', text: 'It is because of the Lord’s loving kindnesses that we are not consumed, because his mercies don’t fail. Lamentations, chapter three, verse twenty-two.' },
  { file: 'verse-lamentations-3-23.mp3', text: 'They are new every morning. Great is your faithfulness. Lamentations, chapter three, verse twenty-three.' },
  { file: 'verse-matthew-11-28.mp3', text: 'Come to me, all you who labor and are heavily burdened, and I will give you rest. Matthew, chapter eleven, verse twenty-eight.' },
  { file: 'verse-john-8-36.mp3', text: 'If therefore the Son makes you free, you will be free indeed. John, chapter eight, verse thirty-six.' },
  { file: 'verse-romans-6-14.mp3', text: 'For sin will not have dominion over you, for you are not under law, but under grace. Romans, chapter six, verse fourteen.' },
  { file: 'verse-romans-12-2.mp3', text: 'Don’t be conformed to this world, but be transformed by the renewing of your mind. Romans, chapter twelve, verse two.' },
  { file: 'verse-romans-12-21.mp3', text: 'Don’t be overcome by evil, but overcome evil with good. Romans, chapter twelve, verse twenty-one.' },
  { file: 'verse-1-corinthians-6-19.mp3', text: 'Don’t you know that your body is a temple of the Holy Spirit who is in you, whom you have from God? First Corinthians, chapter six, verse nineteen.' },
  { file: 'verse-1-corinthians-6-20.mp3', text: 'For you were bought with a price. Therefore glorify God in your body and in your spirit, which are God’s. First Corinthians, chapter six, verse twenty.' },
  { file: 'verse-1-corinthians-9-27.mp3', text: 'I beat my body and bring it into submission. First Corinthians, chapter nine, verse twenty-seven.' },
  { file: 'verse-1-corinthians-10-13.mp3', text: 'God is faithful, who will not allow you to be tempted above what you are able, but will with the temptation also make the way of escape. First Corinthians, chapter ten, verse thirteen.' },
  { file: 'verse-2-corinthians-12-9.mp3', text: 'My grace is sufficient for you, for my power is made perfect in weakness. Second Corinthians, chapter twelve, verse nine.' },
  { file: 'verse-galatians-5-1.mp3', text: 'Stand firm therefore in the liberty by which Christ has made us free, and don’t be entangled again with a yoke of bondage. Galatians, chapter five, verse one.' },
  { file: 'verse-galatians-5-16.mp3', text: 'Walk by the Spirit, and you won’t fulfill the lust of the flesh. Galatians, chapter five, verse sixteen.' },
  { file: 'verse-galatians-5-22.mp3', text: 'The fruit of the Spirit is love, joy, peace, patience, kindness, goodness, faith. Galatians, chapter five, verse twenty-two.' },
  { file: 'verse-galatians-5-23.mp3', text: 'Gentleness, and self-control. Against such things there is no law. Galatians, chapter five, verse twenty-three.' },
  { file: 'verse-ephesians-6-11.mp3', text: 'Put on the whole armor of God, that you may be able to stand against the wiles of the devil. Ephesians, chapter six, verse eleven.' },
  { file: 'verse-philippians-4-6.mp3', text: 'In nothing be anxious, but in everything, by prayer and petition with thanksgiving, let your requests be made known to God. Philippians, chapter four, verse six.' },
  { file: 'verse-philippians-4-7.mp3', text: 'And the peace of God, which surpasses all understanding, will guard your hearts and your thoughts in Christ Jesus. Philippians, chapter four, verse seven.' },
  { file: 'verse-philippians-4-13.mp3', text: 'I can do all things through Christ who strengthens me. Philippians, chapter four, verse thirteen.' },
  { file: 'verse-2-timothy-1-7.mp3', text: 'For God didn’t give us a spirit of fear, but of power, love, and self-control. Second Timothy, chapter one, verse seven.' },
  { file: 'verse-titus-2-12.mp3', text: 'Denying ungodliness and worldly lusts, we would live soberly, righteously, and godly in this present age. Titus, chapter two, verse twelve.' },
  { file: 'verse-hebrews-12-1.mp3', text: 'Let’s lay aside every weight and the sin which so easily entangles us, and run with perseverance the race that is set before us. Hebrews, chapter twelve, verse one.' },
  { file: 'verse-james-4-7.mp3', text: 'Be subject therefore to God. Resist the devil, and he will flee from you. James, chapter four, verse seven.' },
  { file: 'verse-1-peter-5-7.mp3', text: 'Casting all your worries on him, because he cares for you. First Peter, chapter five, verse seven.' },
  { file: 'verse-1-john-5-4.mp3', text: 'This is the victory that has overcome the world: your faith. First John, chapter five, verse four.' },
  {
    file: 'academy-101.mp3',
    text: 'Start With Stillness. Psalm 46:10. Stillness is not a delay in your faith. It is often the doorway into a clearer response. Psalm 46 invites you to stop striving long enough to remember that God is present before the pressure, before the decision, and before the next task. A daily rhythm of stillness helps faith move from an idea into the body: one breath, one verse, one faithful step. Practice. Set a timer for one minute. Breathe slowly, repeat Psalm 46:10 once, and name one pressure you can release before moving on.',
  },
  {
    file: 'academy-102.mp3',
    text: 'Read Before You React. James 1:19. A reactive day pulls your attention in every direction. Scripture gives you a different beginning. James teaches a posture of quick listening, slow speaking, and slow anger. That rhythm is not passive. It is strong enough to interrupt hurry and patient enough to choose wisdom. Before you answer, scroll, decide, or defend yourself, let one verse slow the moment down. Practice. Before one reply today, pause and ask: have I listened well enough to answer with patience?',
  },
  {
    file: 'academy-201.mp3',
    text: 'The Pace of Jesus. Mark 1:35. Jesus moved toward people with compassion, but he also withdrew to pray. Mark shows him rising early, going to a quiet place, and grounding his day in communion with the Father. This is not escape. It is alignment. The Daily Breath rhythm follows that same pattern in miniature: quiet first, then action. Practice. Choose one part of tomorrow morning to begin with a short prayer before opening messages or tasks.',
  },
  {
    file: 'academy-301.mp3',
    text: 'A Prayer You Can Carry. Proverbs 3:5 through 6. Wisdom often begins with surrender. Proverbs does not ask you to ignore your mind; it asks you not to make your own understanding the final authority. A carryable prayer can be simple: Lord, help me trust you here. Make the next step straight. Repeat it before a meeting, a message, a purchase, or a hard conversation. Practice. Write one decision in a sentence, then pray: Lord, help me trust you here and make the next step straight.',
  },
];

const numberWords = [
  'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
  'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen',
];
const tensWords = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
const numberToWords = (number) => {
  if (number < 20) return numberWords[number];
  if (number < 100) return `${tensWords[Math.floor(number / 10)]}${number % 10 ? '-' + numberWords[number % 10] : ''}`;
  return `one hundred${number % 100 ? ' ' + numberToWords(number % 100) : ''}`;
};
const spokenReference = (reference) => {
  const match = String(reference).match(/^(.+?)\s+(\d+):(\d+)$/u);
  if (!match) return reference;
  const book = match[1].replace(/^1\s/, 'First ').replace(/^2\s/, 'Second ').replace(/^3\s/, 'Third ');
  return `${book}, chapter ${numberToWords(Number(match[2]))}, verse ${numberToWords(Number(match[3]))}`;
};

const verseLibrary = JSON.parse(await readFile(verseLibraryPath, 'utf8'));
if (!Array.isArray(verseLibrary.entries) || verseLibrary.entries.length !== 138) {
  throw new Error('Daily Breath verse library must contain exactly 138 entries.');
}
const knownFiles = new Set(clips.map((clip) => clip.file));
for (const entry of verseLibrary.entries) {
  if (!entry.audio_file || knownFiles.has(entry.audio_file)) continue;
  clips.push({ file: entry.audio_file, text: `${entry.text} ${spokenReference(entry.reference)}.` });
  knownFiles.add(entry.audio_file);
}

const escapeXml = (value) => String(value).replace(
  /[<>&"']/g,
  (character) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&apos;' })[character],
);
const sleep = (milliseconds) => new Promise((resolvePromise) => setTimeout(resolvePromise, milliseconds));

await Promise.all([
  mkdir(outputDir, { recursive: true }),
  mkdir(webOutputDir, { recursive: true }),
]);
let generated = 0;
let reused = 0;

for (const [index, clip] of clips.entries()) {
  const filePath = resolve(outputDir, clip.file);
  try {
    await access(filePath, constants.R_OK);
    reused += 1;
  } catch {
    const ssml = '<speak version="1.0" xml:lang="en-US"><voice name="' + escapeXml(voice) + '"><prosody rate="-12%" pitch="-2%">' + escapeXml(clip.text) + '</prosody></voice></speak>';
    let response;
    for (let attempt = 1; attempt <= 4; attempt += 1) {
      response = await fetch('https://' + region + '.tts.speech.microsoft.com/cognitiveservices/v1', {
        method: 'POST',
        headers: {
          'Ocp-Apim-Subscription-Key': key,
          'Content-Type': 'application/ssml+xml',
          'X-Microsoft-OutputFormat': 'audio-24khz-48kbitrate-mono-mp3',
          'User-Agent': 'DailyBreathOfflineNarration',
        },
        body: ssml,
      });
      if (response.ok) break;
      if (![408, 425, 429, 500, 502, 503, 504].includes(response.status) || attempt === 4) {
        throw new Error('Azure request failed for ' + clip.file + ' with HTTP ' + response.status);
      }
      await sleep(attempt * 1500);
    }
    const audio = Buffer.from(await response.arrayBuffer());
    const validMp3 = audio.length >= 128 && (
      audio.subarray(0, 3).equals(Buffer.from('ID3')) || audio[0] === 0xff
    );
    if (!validMp3) throw new Error('Azure returned invalid MP3 data for ' + clip.file);
    await writeFile(filePath, audio);
    generated += 1;
    await sleep(180);
  }
  if (clip.file.startsWith('verse-')) {
    await copyFile(filePath, resolve(webOutputDir, clip.file));
  }
  process.stdout.write('\r' + (index + 1) + '/' + clips.length + ' ready');
}

console.log('\nDaily Breath narration complete: ' + generated + ' generated, ' + reused + ' reused.');
