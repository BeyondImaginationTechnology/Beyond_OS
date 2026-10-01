import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const config = JSON.parse(await readFile(new URL('../public/daily-breath-story.json', import.meta.url), 'utf8'));
const [composition, root, builder, generator, renderer, studioIndex] = await Promise.all([
  readFile(new URL('../src/DailyBreathStory.tsx', import.meta.url), 'utf8'),
  readFile(new URL('../src/Root.tsx', import.meta.url), 'utf8'),
  readFile(new URL('../../../server/admin/daily-studio/dailybreath-story.php', import.meta.url), 'utf8'),
  readFile(new URL('../../../server/admin/daily-studio/api/generate-dailybreath-story.php', import.meta.url), 'utf8'),
  readFile(new URL('../../../server/admin/daily-studio/api/render-dailybreath-story.php', import.meta.url), 'utf8'),
  readFile(new URL('../../../server/admin/daily-studio/index.php', import.meta.url), 'utf8'),
]);

const timeline = [
  ['intro', 0, 10],
  ['inciting', 10, 10],
  ['rising', 20, 15],
  ['peak', 35, 7],
  ['falling', 42, 4],
  ['resolution', 46, 4],
];

test('story brief covers the six narrative beats and the full one-minute duration', () => {
  assert.deepEqual(
    config.beats.map(({id, startSeconds, durationSeconds}) => [id, startSeconds, durationSeconds]),
    timeline,
  );
  assert.equal(config.beats.at(-1).startSeconds + config.beats.at(-1).durationSeconds, 50);
  assert.equal(config.fps, 30);
  assert.equal(config.width, 1080);
  assert.equal(config.height, 1920);
  assert.ok(config.sources.length > 0);
});

test('every beat carries narration, concise screen copy, and a visual prompt', () => {
  const narrationWords = config.beats.flatMap(
    ({narration}) => narration.match(/[\p{L}\p{N}]+(?:[’'-][\p{L}\p{N}]+)*/gu) || [],
  ).length;
  assert.ok(narrationWords <= 95);
  for (const beat of config.beats) {
    assert.ok(beat.narration.trim());
    assert.ok(beat.onScreenText.trim());
    assert.ok(beat.visualPrompt.trim());
    assert.ok((beat.onScreenText.match(/[\p{L}\p{N}]+/gu) || []).length <= 8);
  }
});

test('Remotion composition preserves fixed narrative and end-card timings', () => {
  assert.match(root, /id="DailyBreathStory"[\s\S]*?durationInFrames=\{1800\}[\s\S]*?fps=\{30\}[\s\S]*?width=\{1080\}[\s\S]*?height=\{1920\}/);
  assert.match(composition, /from=\{50 \* fps\} durationInFrames=\{5 \* fps\}/);
  assert.match(composition, /from=\{55 \* fps\} durationInFrames=\{5 \* fps\}/);
});

test('Daily Studio exposes authenticated generation, review, and Remotion export', () => {
  assert.match(studioIndex, /60-second Story Builder/);
  assert.match(builder, /Generate six beats/);
  assert.match(builder, /Download story brief JSON/);
  assert.match(builder, /Render 60-second MP4/);
  assert.match(generator, /Auth::verifyCsrf/);
  assert.match(generator, /'store' => false/);
  assert.match(generator, /'sources' => \$cleanSources/);
  assert.match(generator, /'visualPrompt' => 600/);
  assert.match(renderer, /Auth::verifyCsrf/);
  assert.match(renderer, /'DailyBreathStory'/);
});
