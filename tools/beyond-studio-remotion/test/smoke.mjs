import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';

const port = 44319;
const token = 'beyond-remotion-smoke-test';
const aiToken = 'beyond-remotion-ai-smoke-test';
const child = spawn(process.execPath, ['server.mjs'], {
  cwd: new URL('..', import.meta.url),
  env: {
    ...process.env,
    BEYOND_STUDIO_REMOTION_HOST: '127.0.0.1',
    BEYOND_STUDIO_REMOTION_PORT: String(port),
    BEYOND_STUDIO_REMOTION_TOKEN: token,
    BEYOND_STUDIO_REMOTION_AI_TOKEN: aiToken,
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
  assert.equal(health.aiApiReady, true);
  assert.equal(health.maxUploadBytes, 100 * 1024 * 1024);

  const schemaResponse = await fetch(`http://127.0.0.1:${port}/api/ai/openapi.json`);
  assert.equal(schemaResponse.status, 200);
  const schema = await schemaResponse.json();
  assert.equal(schema.openapi, '3.1.0');
  assert.equal(schema.paths['/api/ai/renders'].post.operationId, 'createBeyondStudioRender');

  const artifactsResponse = await fetch(`http://127.0.0.1:${port}/api/ai/artifacts`, {
    headers: {Authorization: `Bearer ${aiToken}`},
  });
  assert.equal(artifactsResponse.status, 200);
  const artifacts = await artifactsResponse.json();
  assert.deepEqual(artifacts.artifacts, []);

  const aiTokenCannotUseAdminApi = await fetch(`http://127.0.0.1:${port}/api/artifacts`, {
    headers: {Authorization: `Bearer ${aiToken}`},
  });
  assert.equal(aiTokenCannotUseAdminApi.status, 401);

  const adminTokenCannotUseAiApi = await fetch(`http://127.0.0.1:${port}/api/ai/artifacts`, {
    headers: {Authorization: `Bearer ${token}`},
  });
  assert.equal(adminTokenCannotUseAiApi.status, 401);

  const unauthorized = await fetch(`http://127.0.0.1:${port}/api/artifacts`);
  assert.equal(unauthorized.status, 401);

  const publicHealth = await fetch(`http://127.0.0.1:${port}/api/health`);
  assert.equal(publicHealth.status, 200);

  const forbiddenOrigin = await fetch(`http://127.0.0.1:${port}/api/health`, {
    headers: {Authorization: `Bearer ${token}`, Origin: 'https://evil.example'},
  });
  assert.equal(forbiddenOrigin.status, 403);

  console.log(`Bridge smoke test passed (Remotion installed: ${health.remotionReady}).`);
} finally {
  child.kill('SIGTERM');
}
