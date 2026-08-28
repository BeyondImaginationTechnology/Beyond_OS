import { readFile, writeFile } from 'node:fs/promises';

const catalogPath = new URL('../beyond-tv/data/catalog.json', import.meta.url);
const reviewPath = new URL('../beyond-tv/data/archive-collection-review.json', import.meta.url);
const catalog = JSON.parse(await readFile(catalogPath, 'utf8'));

const verifiedMovies = [
  {
    slug: 'oz-the-great-and-powerful',
    type: 'movie',
    title: 'Oz the Great and Powerful',
    subtitle: 'Premium movie · Owner-verified Archive source',
    description: 'A small-time magician is swept into Oz and drawn into a conflict that will shape the enchanted kingdom.',
    icon: '🪄',
    gradient: 'linear-gradient(135deg,#152b45,#256c72 52%,#d8aa45)',
    rating: 'PG',
    year: '2013',
    genre: 'Fantasy · Adventure · Family',
    runtime: 'Feature film',
    archive_id: '1.-oz-the-great-and-powerful-2013',
    channel_slug: 'beyond-family',
  },
  {
    slug: 'bill-and-ted-face-the-music',
    type: 'movie',
    title: 'Bill & Ted Face the Music',
    subtitle: 'Premium movie · Owner-verified Archive source',
    description: 'Bill and Ted race through time to find the song that can restore harmony to the universe.',
    icon: '🎸',
    gradient: 'linear-gradient(135deg,#25194d,#7046b8 52%,#ef5d99)',
    rating: 'PG-13',
    year: '2020',
    genre: 'Comedy · Science Fiction · Adventure',
    runtime: 'Feature film',
    archive_id: 'bill-ted-face-the-music_202608',
    channel_slug: 'beyond-comedy',
  },
].map((item) => ({
  ...item,
  source_type: 'archive_embed',
  source_label: 'Internet Archive · Owner-verified source',
  source_bookmark: 'kareneliot',
  candidate_url: `https://archive.org/details/${item.archive_id}`,
  source_url: `https://archive.org/details/${item.archive_id}`,
  thumbnail: `https://archive.org/services/img/${item.archive_id}`,
  approved_by: 'owner',
  approved_at: '2026-08-28',
  new_addition: true,
}));

for (const movie of verifiedMovies) {
  const existingIndex = catalog.findIndex((entry) => entry.slug === movie.slug);
  if (existingIndex >= 0) catalog.splice(existingIndex, 1);
}
catalog.unshift(...verifiedMovies);

const collectionReview = [
  ['Throwback Movies (2000–10)', 'freaky-friday-2003_202307'],
  ['Matthew’s Movies & TV Shows', 'milk-money-1994_202604'],
  ['M. Night Shyamalan Movies', 'the-village-2004_202606'],
  ['Movies 2', '28-days-later_202505'],
  ['Other Movies 2', 'other-movies-2'],
  ['All Studio Ghibli’s Movies', 'the-cat-returns_202508'],
  ['Ben Stiller Movies', 'ben-stiller-movies'],
  ['Random Movies', 'transformers-2007_202602'],
  ['Disney Channel Original Movies Collection', 'disney-channel-original-movies'],
  ['Movies and TV', 'men-at-work_202503'],
  ['TV Movie Collection', 'are-you-alone-in-the-house'],
  ['All Animated Videos', 'where-the-wild-things-are-vhs-and-other-maurice-sendak-stories-o-60-fps-2002_202309'],
  ['My Favorite Movies', 'My-Favorite-Movies_202503'],
  ['Movies', 'TR_Sub_Movies'],
  ['John Candy Movies', 'john-candy-uncle-buck'],
  ['My Movies', 'agent-cody-banks-2003'],
  ['Movies-1', 'predestination.-2014.1080p.-blu-ray.x-264.-yify'],
  ['All Animated VHS and DVD Capture', 'cinderella-1988-vhs'],
].map(([title, archiveId]) => ({
  title,
  archive_id: archiveId,
  url: `https://archive.org/details/${archiveId}`,
  source_bookmark: 'kareneliot',
  status: 'pending_manual_review',
  reason: 'Bookmark appears to represent a broader multi-title collection; no contained titles were imported.',
}));

await writeFile(catalogPath, `${JSON.stringify(catalog, null, 2)}\n`, 'utf8');
await writeFile(reviewPath, `${JSON.stringify(collectionReview, null, 2)}\n`, 'utf8');
console.log(`Installed ${verifiedMovies.length} premium post-2000 movies and queued ${collectionReview.length} collection-style bookmarks for review.`);
