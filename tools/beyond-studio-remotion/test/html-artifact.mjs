import assert from 'node:assert/strict';
import {
  inferHtmlRenderOptions,
  LANDSCAPE_CANVAS_SELECTOR,
  PORTRAIT_CANVAS_SELECTOR,
} from '../lib/html-artifact.mjs';

const defaults = {
  width: 1920,
  height: 1080,
  fps: 30,
  durationInFrames: 450,
  selector: LANDSCAPE_CANVAS_SELECTOR,
};

const portrait = inferHtmlRenderOptions(
  '<div class="aspect-[9/16]">portrait composition</div>',
  defaults,
);
assert.equal(portrait.width, 1080);
assert.equal(portrait.height, 1920);
assert.equal(portrait.selector, PORTRAIT_CANVAS_SELECTOR);
assert.equal(portrait.detectedRatio, '9:16');

const explicit = inferHtmlRenderOptions(
  '<div class="aspect-[9/16]">portrait composition</div>',
  {...defaults, selector: '#custom-crop'},
);
assert.equal(explicit.width, 1920);
assert.equal(explicit.height, 1080);
assert.equal(explicit.selector, '#custom-crop');

const landscape = inferHtmlRenderOptions(
  '<div class="aspect-[16/9]">landscape composition</div>',
  defaults,
);
assert.deepEqual(landscape, defaults);

const mixed = inferHtmlRenderOptions(
  '<div class="aspect-[16/9]"></div><div class="aspect-[9/16]"></div>',
  defaults,
);
assert.deepEqual(mixed, defaults);

console.log('HTML artifact inference tests passed.');
