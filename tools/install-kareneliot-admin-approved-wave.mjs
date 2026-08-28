import { readFile, writeFile } from 'node:fs/promises';

const catalogPath = new URL('../beyond-tv/data/catalog.json', import.meta.url);
const reviewPath = new URL('../beyond-tv/data/archive-collection-review.json', import.meta.url);
const catalog = JSON.parse(await readFile(catalogPath, 'utf8'));
const reviewQueue = JSON.parse(await readFile(reviewPath, 'utf8'));
const approvedAt = '2026-08-28';
const archivePage = (id) => `https://archive.org/details/${id}`;
const archiveFile = (id, file) => `https://archive.org/download/${id}/${encodeURIComponent(file)}`;

const approvedSingles = [
  ['nanny-mcphee','Nanny McPhee','2005','PG','Comedy · Family · Fantasy','☂️','nanny-mcphee-2005','A mysterious governess uses magic to transform the lives of seven unruly children.','beyond-family'],
  ['nanny-mcphee-returns','Nanny McPhee Returns','2010','PG','Comedy · Family · Fantasy','🌾','nanny-mc-phee-returns-2010','Nanny McPhee brings her unusual lessons to a family struggling on a wartime farm.','beyond-family'],
  ['night-at-the-museum','Night at the Museum','2006','PG','Comedy · Family · Adventure','🏛️','night-at-the-museum-2006','A museum night guard discovers that the exhibits come alive after dark.','beyond-family'],
  ['night-at-the-museum-battle-of-the-smithsonian','Night at the Museum: Battle of the Smithsonian','2009','PG','Comedy · Family · Adventure','🗿','night-at-the-museum-battle-of-the-smithsonian-2009','The living museum exhibits travel to the Smithsonian for a much larger adventure.','beyond-family'],
  ['night-at-the-museum-secret-of-the-tomb','Night at the Museum: Secret of the Tomb','2014','PG','Comedy · Family · Adventure','🏺','night-at-the-museum-secret-of-the-tomb-2014','Larry and the exhibits travel to London to save the magic that brings them to life.','beyond-family'],
  ['mary-poppins-returns','Mary Poppins Returns','2018','PG','Musical · Family · Fantasy','🪁','mary-poppins-returns-2018_202605','Mary Poppins returns to help the Banks family rediscover joy during a difficult time.','beyond-family'],
  ['john-tucker-must-die','John Tucker Must Die','2006','PG-13','Comedy · Romance · Teen','💔','john.-tucker.-must.-die.-2006.720p.-br-rip.x-264.-yify_202605','Three students recruit a new girl to help expose the school heartthrob who deceived them.','beyond-comedy'],
  ['legally-blonde','Legally Blonde','2001','PG-13','Comedy · Romance','💗','legally.-blonde.-2001.1080p.-br-rip.x-264.-yify','Elle Woods enters Harvard Law and proves she is far more capable than anyone expected.','beyond-comedy'],
  ['bad-teacher','Bad Teacher','2011','R','Comedy','🍎','bad.-teacher.-2011.1080p.-blu-ray.x-264-yts.-am_202608','A cynical teacher sets her sights on a wealthy colleague while avoiding responsibility.','beyond-comedy'],
  ['clerks-ii','Clerks II','2006','R','Comedy','🥤','c-2_20240510','Two longtime friends face adulthood while working at a fast-food restaurant.','beyond-comedy'],
  ['bulletproof-monk','Bulletproof Monk','2003','PG-13','Action · Fantasy · Comedy','🥋','bulletproof-monk-2003-vhs-rip','A guardian monk recruits an unlikely successor to protect an ancient scroll.','classic-cinema'],
  ['django-unchained','Django Unchained','2012','R','Western · Drama · Action','🤠','django-unchained-2012_202605','A freed man joins a bounty hunter on a dangerous mission across the American South.','classic-cinema'],
  ['the-equalizer','The Equalizer','2014','R','Action · Thriller','⌚','the.-equalizer.-2014.1080p.-blu-ray.x-264.-yify_202402','A retired operative returns to action to defend someone targeted by violent criminals.','classic-cinema'],
  ['mr-monks-last-case','Mr. Monk’s Last Case: A Monk Movie','2023','TV-14','Mystery · Comedy · Crime','🔍','mr.-monks.-last.-case.-a.-monk.-movie.-2023.1080p.-web.-h.-264-accomplished-yak','Adrian Monk reunites with his friends to solve a personal case involving his stepdaughter.','beyond-mystery'],
].map(([slug,title,year,rating,genre,icon,archiveId,description,channelSlug]) => ({
  slug,
  type: 'movie',
  title,
  subtitle: 'Premium movie · Admin-approved Archive source',
  description,
  icon,
  gradient: channelSlug === 'beyond-family'
    ? 'linear-gradient(135deg,#18345a,#4c69a2 55%,#d4ae53)'
    : channelSlug === 'beyond-comedy'
      ? 'linear-gradient(135deg,#3b1d51,#9b477c 55%,#ed9d4a)'
      : channelSlug === 'beyond-mystery'
        ? 'linear-gradient(135deg,#15172a,#4b416d 55%,#b86455)'
        : 'linear-gradient(135deg,#17283c,#426b82 55%,#c89b4b)',
  rating,
  year,
  genre,
  runtime: 'Feature film',
  source_type: 'archive_embed',
  archive_id: archiveId,
  source_label: 'Internet Archive · Owner and admin approved',
  source_bookmark: 'kareneliot',
  candidate_url: archivePage(archiveId),
  source_url: archivePage(archiveId),
  thumbnail: `https://archive.org/services/img/${archiveId}`,
  channel_slug: channelSlug,
  approved_by: 'owner_and_beyond_os_admin',
  approved_at: approvedAt,
  new_addition: true,
}));

const throwbackId = 'freaky-friday-2003_202307';
const throwbackRows = [
  ['a-cinderella-story','A Cinderella Story','2004','PG','Comedy · Romance · Family','👠','A Cinderella Story (2004).mp4','A modern fairy-tale romance unfolds through secret messages and a masked school dance.'],
  ['a-walk-to-remember','A Walk to Remember','2002','PG','Drama · Romance','⭐','A Walk To Remember (2002).mp4','Two very different high-school students form a relationship that changes both of their lives.'],
  ['16-wishes','16 Wishes','2010','TV-G','Family · Fantasy · Comedy','🎂','16 Wishes (2010).mp4','A teenager’s birthday wishes begin coming true with unexpected consequences.'],
  ['another-cinderella-story','Another Cinderella Story','2008','PG','Musical · Romance · Comedy','💃','Another Cinderella Story (2008).mp4','A talented dancer connects with a pop star at a masked ball.'],
  ['aquamarine','Aquamarine','2006','PG','Family · Fantasy · Comedy','🧜','Aquamarine (2006).mp4','Two friends help a mermaid discover whether true love exists.'],
  ['bratz','Bratz','2007','PG','Comedy · Family · Music','💄','Bratz (2007).mp4','Four friends challenge the social divisions at their high school.'],
  ['camp-rock','Camp Rock','2008','TV-G','Musical · Family · Comedy','🎤','Camp Rock (2008).mp4','A talented young singer finds her voice at a summer music camp.'],
  ['crossroads-2002','Crossroads','2002','PG-13','Drama · Romance · Music','🛣️','Crossroads (2002).mp4','Three childhood friends reconnect during a cross-country road trip.'],
  ['freaky-friday-2003','Freaky Friday','2003','PG','Comedy · Family · Fantasy','🔄','Freaky Friday (2003).mp4','A mother and daughter magically switch bodies and must navigate each other’s lives.'],
  ['high-school-musical','High School Musical','2006','TV-G','Musical · Family · Romance','🏀','High School Musical (2006).mp4','Two students challenge school expectations when they audition for a musical together.'],
  ['honey-2003','Honey','2003','PG-13','Drama · Music · Romance','🎧','Honey (2003).mp4','A dancer and choreographer pursues her dream while supporting young performers in her community.'],
  ['mean-girls','Mean Girls','2004','PG-13','Comedy · Teen','👑','Mean Girls (2004).mp4','A new student enters the complicated social world of an American high school.'],
  ['shes-the-man','She’s the Man','2006','PG-13','Comedy · Romance · Sports','⚽',"She's the Man (2006).mp4",'A determined soccer player disguises herself to compete on a boys’ school team.'],
  ['step-up-2-the-streets','Step Up 2: The Streets','2008','PG-13','Drama · Music · Romance','🕺','Step Up 2 The Streets (2008).mp4','A street dancer joins an elite arts school and forms a crew for a major competition.'],
  ['the-cheetah-girls','The Cheetah Girls','2003','TV-G','Musical · Family · Comedy','🐆','The Cheetah Girls (2003).mp4','Four friends pursue a music career while learning to protect their friendship.'],
  ['the-even-stevens-movie','The Even Stevens Movie','2003','TV-G','Comedy · Family · Adventure','🏝️','The Even Stevens Movie (2003).mp4','The Stevens family vacation becomes a reality-show adventure.'],
  ['the-lizzie-mcguire-movie','The Lizzie McGuire Movie','2003','PG','Comedy · Family · Romance','🇮🇹','The Lizzie McGuire Movie (2003).mp4','Lizzie’s school trip to Rome turns into an unexpected pop-star adventure.'],
  ['the-perfect-man','The Perfect Man','2005','PG','Comedy · Romance · Family','💌','The Perfect Man (2005).mp4','A daughter invents a secret admirer in hopes of helping her mother find happiness.'],
  ['the-princess-diaries','The Princess Diaries','2001','G','Comedy · Family · Romance','👸','The Princess Diaries (2001).mp4','An awkward teenager discovers she is heir to a European kingdom.'],
  ['what-a-girl-wants','What a Girl Wants','2003','PG','Comedy · Romance · Family','🇬🇧','What a Girl Wants (2003).mp4','An American teenager travels to London to meet the father she has never known.'],
].map(([slug,title,year,rating,genre,icon,file,description]) => ({
  slug,
  type: 'movie',
  title,
  subtitle: 'Throwback Movies · Admin-approved collection',
  description,
  icon,
  gradient: 'linear-gradient(135deg,#442052,#b54b85 54%,#efb84e)',
  rating,
  year,
  genre,
  runtime: 'Feature film',
  source_type: 'direct_video',
  video_url: archiveFile(throwbackId, file),
  archive_id: throwbackId,
  source_label: 'Internet Archive · Owner and admin approved collection',
  source_bookmark: 'kareneliot',
  source_url: archivePage(throwbackId),
  candidate_url: archivePage(throwbackId),
  thumbnail: `https://archive.org/services/img/${throwbackId}`,
  channel_slug: genre.includes('Family') ? 'beyond-family' : 'beyond-comedy',
  approved_by: 'owner_and_beyond_os_admin',
  approved_at: approvedAt,
  new_addition: true,
}));

const additions = [];
for (const item of [...approvedSingles, ...throwbackRows]) {
  const duplicate = catalog.some((entry) => entry.slug === item.slug || String(entry.title).toLowerCase() === item.title.toLowerCase());
  if (!duplicate) additions.push(item);
}
catalog.unshift(...additions);

const throwbackReview = reviewQueue.find((item) => item.archive_id === throwbackId);
if (throwbackReview) {
  throwbackReview.status = 'approved_and_imported';
  throwbackReview.approved_by = 'owner_and_beyond_os_admin';
  throwbackReview.approved_at = approvedAt;
  throwbackReview.imported_titles = throwbackRows.map((item) => item.title);
  throwbackReview.skipped_existing_titles = ['Bring It On'];
  throwbackReview.reason = 'Collection reviewed and approved; individual feature files imported with duplicate titles skipped.';
}

await writeFile(catalogPath, `${JSON.stringify(catalog, null, 2)}\n`, 'utf8');
await writeFile(reviewPath, `${JSON.stringify(reviewQueue, null, 2)}\n`, 'utf8');
console.log(`Installed ${additions.length} approved movies (${approvedSingles.length} individual approvals and ${throwbackRows.length} unique Throwback titles).`);
