import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';

const pagePath = new URL('../server/admin/daily-studio/tattoo-asset-import.php', import.meta.url);
const endpointPath = new URL('../server/admin/daily-studio/api/upload-tattoo-stencil-batch.php', import.meta.url);
const page = await readFile(pagePath, 'utf8');
const endpoint = await readFile(endpointPath, 'utf8');

const scripts = [...page.matchAll(/<script>([\s\S]*?)<\/script>/g)];
assert.ok(scripts.length > 0, 'Importer must contain a script.');
const browserScript = scripts.at(-1)[1].replace(/<\?=[\s\S]*?\?>/g, 'null');
new Function(browserScript);

assert.match(page, /files\.length !== 55/);
assert.match(page, /expected_count', '55'/);
assert.match(page, /ready for GPT sorting/i);
assert.match(endpoint, /\$expectedCount !== 55/);
assert.match(endpoint, /sort_status' => 'awaiting_gpt'/);
assert.match(endpoint, /ready_for_gpt_sort/);
assert.match(endpoint, /hash\('sha256', \$bytes\)/);
assert.match(endpoint, /flock\(\$lock, LOCK_EX\)/);

console.log('Tattoo 55-stencil batch checks passed.');
