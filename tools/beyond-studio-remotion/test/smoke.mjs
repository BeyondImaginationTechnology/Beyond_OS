import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';

const port = 44319;
const token = 'beyond-remotion-smoke-test';
const child = spawn(process.execPath, ['server.mjs'], {
  cwd: new URL('..', import.meta.url),
  env: {
    ...process.env,
    BEYOND_STUDIO_REMOTION_HOST: '127.0.0.1',
    BEYOND_STUDIO_REMOTION_PORT: String(port),
    BEYOND_STUDIO_REMOTION_TOKEN: token,
  },
  stdio: ['ignore', 'pipe', 'pipe'],
});

let output = '';
child.stdout.on('data', (chunk) => { output += chunk.toString(); });
child.stderr.on('data', (chunk) => { output += chunk.toString(); });

const waitForServer = async () => {
  const deadline = Date.now() + 10000;
  while (Date.now() < deadline) {
    if (output.includes(`ready on 127.0.0.1:${port}`)) return;
    if (child.exitCode !== null) throw new Error(`Bridge exited early.\n${output}`);
    await new Promise((resolve) => setTimeout(resolve, 50));
  }
  throw new Error(`Bridge did not start.\n${output}`);
};

try {
  await waitForServer();
  const authorized = await fetch(`http://127.0.0.1:${port}/api/health`, {
    headers: {Authorization: `Bearer ${token}`},
  });
  assert.equal(authorized.status, 200);
  const health = await authorized.json();
  assert.equal(health.ok, true);
  assert.equal(health.version, 2);
  assert.equal(typeof health.remotionReady, 'boolean');
  assert.equal(health.maxUploadBytes, 100 * 1024 * 1024);

  const unauthorized = await fetch(`http://127.0.0.1:${port}/api/health`);
  assert.equal(unauthorized.status, 401);

  const forbiddenOrigin = await fetch(`http://127.0.0.1:${port}/api/health`, {
    headers: {Authorization: `Bearer ${token}`, Origin: 'https://evil.example'},
  });
  assert.equal(forbiddenOrigin.status, 403);

  console.log(`Bridge smoke test passed (Remotion installed: ${health.remotionReady}).`);
} finally {
  child.kill('SIGTERM');
}
