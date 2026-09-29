import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const configUrl = new URL('../public/daily-breath-video.json', import.meta.url);
const config = JSON.parse(await readFile(configUrl, 'utf8'));
const cronUrl = new URL('../../../server/cron/daily-breath-video.php', import.meta.url);
const dailyCronUrl = new URL('../../../server/cron/daily-studio-daily.php', import.meta.url);
const [rendererCron, dailyCron] = await Promise.all([
  readFile(cronUrl, 'utf8'),
  readFile(dailyCronUrl, 'utf8'),
]);

test('daily breath configuration has valid video dimensions and timing', () => {
  assert.equal(config.width, 1080);
  assert.equal(config.height, 1920);
  assert.equal(config.fps, 30);
  assert.ok(Number.isInteger(config.cycleCount) && config.cycleCount > 0);
  assert.ok(Number.isInteger(config.introSeconds) && config.introSeconds >= 0);
  assert.ok(Number.isInteger(config.outroSeconds) && config.outroSeconds >= 0);
});

test('breathing phases have positive durations and continuous scale transitions', () => {
  assert.ok(Array.isArray(config.phases) && config.phases.length >= 2);
  for (const phase of config.phases) {
    assert.ok(phase.label.trim().length > 0);
    assert.ok(Number.isInteger(phase.durationSeconds) && phase.durationSeconds > 0);
    assert.ok(Number.isFinite(phase.fromScale) && phase.fromScale > 0);
    assert.ok(Number.isFinite(phase.toScale) && phase.toScale > 0);
  }
  for (let index = 0; index < config.phases.length; index += 1) {
    const current = config.phases[index];
    const next = config.phases[(index + 1) % config.phases.length];
    assert.equal(current.toScale, next.fromScale);
  }
});

test('default practice is a one-minute breathing session with safety guidance', () => {
  const practiceSeconds = config.cycleCount * config.phases.reduce(
    (total, phase) => total + phase.durationSeconds,
    0,
  );
  assert.equal(practiceSeconds, 60);
  assert.match(config.safetyNote, /pause or stop/i);
  assert.ok(config.title.trim().length > 0);
  assert.ok(config.closing.trim().length > 0);
});

test('daily render publishes a dated asset through the latest manifest', () => {
  const totalSeconds = config.introSeconds
    + config.outroSeconds
    + config.cycleCount * config.phases.reduce(
      (total, phase) => total + phase.durationSeconds,
      0,
    );
  assert.equal(totalSeconds, 67);
  assert.equal(config.timezone, 'America/Vancouver');
  assert.match(rendererCron, /\/dailybreath\/assets\/videos\/breathing\/'\s*\.\s*\$date\s*\.\s*'\.mp4/);
  assert.match(rendererCron, /'\/latest\.json'/);
  assert.match(dailyCron, /dailybreath_render_daily_video\(\)/);
});
