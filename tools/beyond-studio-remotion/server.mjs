import {createServer} from 'node:http';
import {createReadStream, createWriteStream} from 'node:fs';
import {access, mkdir, readFile, readdir, rename, stat, writeFile} from 'node:fs/promises';
import {basename, dirname, extname, join, resolve, sep} from 'node:path';
import {randomUUID, timingSafeEqual} from 'node:crypto';
import {spawn} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {inferHtmlRenderOptions} from './lib/html-artifact.mjs';

const root = dirname(fileURLToPath(import.meta.url));
const repositoryRoot = resolve(root, '..', '..');
const workspace = join(root, 'workspace');
const importsDirectory = join(workspace, 'imports');
const outputsDirectory = join(workspace, 'outputs');
const jobs = new Map();
const imports = new Map();
const port = Number(process.env.PORT || process.env.BEYOND_STUDIO_REMOTION_PORT || 4317);
const host = process.env.BEYOND_STUDIO_REMOTION_HOST || '127.0.0.1';
const accessToken = process.env.BEYOND_STUDIO_REMOTION_TOKEN || '';
const aiAccessToken = process.env.BEYOND_STUDIO_REMOTION_AI_TOKEN || '';
const configuredOrigins = (process.env.BEYOND_STUDIO_REMOTION_ORIGINS || '')
  .split(',')
  .map((origin) => origin.trim().replace(/\/$/, ''))
  .filter(Boolean);
const maxUploadBytes = 100 * 1024 * 1024;

await mkdir(importsDirectory, {recursive: true});
await mkdir(outputsDirectory, {recursive: true});

const json = (response, status, payload) => {
  response.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
    ...corsHeaders(response.beyondOrigin),
  });
  response.end(JSON.stringify(payload));
};

const corsHeaders = (origin = 'null') => ({
  'Access-Control-Allow-Origin': origin,
  'Vary': 'Origin',
  'Access-Control-Allow-Headers': 'Authorization, Content-Type, X-Artifact-Name',
  'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
});

const safeName = (name) => basename(name || 'artifact').replace(/[^a-zA-Z0-9._-]+/g, '-').slice(0, 160);
const allowedOrigin = (origin) => {
  if (!origin || origin === 'null') return 'null';
  try {
    const url = new URL(origin);
    const local = ['localhost', '127.0.0.1', '::1'].includes(url.hostname);
    const beyond = url.protocol === 'https:' && (url.hostname === 'beyondimagination.co.technology' || url.hostname.endsWith('.beyondimagination.co.technology'));
    const configured = configuredOrigins.includes(url.origin);
    return local || beyond || configured ? origin : null;
  } catch (_) {
    return null;
  }
};
const tokenMatches = (request, expectedToken = accessToken) => {
  if (!expectedToken) return true;
  const header = String(request.headers.authorization || '');
  const supplied = header.startsWith('Bearer ') ? header.slice(7) : '';
  const expectedBuffer = Buffer.from(expectedToken);
  const suppliedBuffer = Buffer.from(supplied);
  return expectedBuffer.length === suppliedBuffer.length && timingSafeEqual(expectedBuffer, suppliedBuffer);
};
const aiTokenMatches = (request) => aiAccessToken !== '' && tokenMatches(request, aiAccessToken);
const readJsonBody = async (request, maxBytes = 64 * 1024) => {
  const chunks = [];
  let bytes = 0;
  for await (const chunk of request) {
    bytes += chunk.length;
    if (bytes > maxBytes) throw new Error('JSON request is too large.');
    chunks.push(chunk);
  }
  try {
    return JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}');
  } catch (_) {
    throw new Error('Request body must be valid JSON.');
  }
};
const exists = async (path) => access(path).then(() => true).catch(() => false);
const run = (command, args, options = {}) => new Promise((resolvePromise, reject) => {
  const child = spawn(command, args, {windowsHide: true, ...options});
  let stdout = '';
  let stderr = '';
  child.stdout?.on('data', (chunk) => { stdout += chunk.toString(); options.onOutput?.(chunk.toString()); });
  child.stderr?.on('data', (chunk) => { stderr += chunk.toString(); options.onOutput?.(chunk.toString()); });
  child.on('error', reject);
  child.on('close', (code) => code === 0
    ? resolvePromise({stdout, stderr})
    : reject(new Error((stderr || stdout || `${command} exited with ${code}`).trim())));
});

const receiveFile = async (request, destination) => new Promise((resolvePromise, reject) => {
  let bytes = 0;
  const stream = createWriteStream(destination, {flags: 'wx'});
  request.on('data', (chunk) => {
    bytes += chunk.length;
    if (bytes > maxUploadBytes) request.destroy(new Error('Artifacts must be 100 MB or smaller.'));
  });
  request.on('error', reject);
  stream.on('error', reject);
  stream.on('finish', () => resolvePromise(bytes));
  request.pipe(stream);
});

const findProjectRoot = async (directory, depth = 0) => {
  if (await exists(join(directory, 'package.json'))) return directory;
  if (depth >= 3) return null;
  const entries = await readdir(directory, {withFileTypes: true});
  for (const entry of entries) {
    if (!entry.isDirectory() || entry.name === 'node_modules' || entry.name.startsWith('.')) continue;
    const found = await findProjectRoot(join(directory, entry.name), depth + 1);
    if (found) return found;
  }
  return null;
};

const findEntry = async (projectRoot) => {
  const packageJson = JSON.parse(await readFile(join(projectRoot, 'package.json'), 'utf8'));
  const scriptText = Object.values(packageJson.scripts || {}).join(' ');
  const scriptMatch = scriptText.match(/(?:remotion\s+(?:studio|render|bundle|compositions)\s+)([^\s"']+)/);
  const candidates = [
    scriptMatch?.[1], 'src/index.ts', 'src/index.tsx', 'src/index.js', 'src/index.jsx',
    'index.ts', 'index.tsx', 'index.js', 'index.jsx',
  ].filter(Boolean);
  for (const candidate of candidates) {
    const path = resolve(projectRoot, candidate);
    if (path.startsWith(projectRoot + sep) && await exists(path)) return path;
  }
  throw new Error('No Remotion entry point was found. Expected src/index.ts or a Remotion script in package.json.');
};

const validateArchiveEntries = (listing) => {
  const entries = listing.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
  if (entries.length > 5000) throw new Error('ZIP contains too many files.');
  for (const entry of entries) {
    const normalized = entry.replaceAll('\\', '/');
    if (normalized.startsWith('/') || /^[a-zA-Z]:/.test(normalized) || normalized.split('/').includes('..')) {
      throw new Error(`Unsafe path in ZIP: ${entry}`);
    }
  }
};

const remotionCli = join(root, 'node_modules', '@remotion', 'cli', 'remotion-cli.js');
const nodeExecutable = process.execPath;

const inspectCompositions = async (entry, projectRoot) => {
  const result = await run(nodeExecutable, [remotionCli, 'compositions', entry], {
    cwd: projectRoot,
    env: {...process.env, BROWSER_DOWNLOAD_BEHAVIOR: 'download-if-missing'},
  });
  const output = (result.stdout + '\n' + result.stderr).replace(/\u001b\[[0-9;?]*[ -/]*[@-~]/g, '').replace(/\r/g, '');
  const compositions = [];
  for (const line of output.split('\n')) {
    const video = line.match(/^([A-Za-z0-9][\w.-]*)\s+(\d+(?:\.\d+)?)\s+(\d+)x(\d+)\s+(\d+)\s+\(/);
    const still = line.match(/^([A-Za-z0-9][\w.-]*)\s+(\d+)x(\d+)\s+Still\s*$/);
    if (video) compositions.push({id: video[1], fps: Number(video[2]), width: Number(video[3]), height: Number(video[4]), durationInFrames: Number(video[5])});
    if (still) compositions.push({id: still[1], fps: 30, width: Number(still[2]), height: Number(still[3]), durationInFrames: 1});
  }
  if (!compositions.length) throw new Error(`Remotion did not return composition metadata. ${output.slice(-600)}`);
  return compositions;
};

const bridgeScript = String.raw`<script>
(() => {
  let now = 0;
  let lastFrame = -1;
  let nextId = 1;
  let queue = [];
  const cancelled = new Set();
  const nativeNow = performance.now.bind(performance);
  window.requestAnimationFrame = (callback) => { const id = nextId++; queue.push([id, callback]); return id; };
  window.cancelAnimationFrame = (id) => cancelled.add(id);
  try { Object.defineProperty(performance, 'now', {value: () => now}); } catch (_) {}
  const focusCanvas = (selector) => {
    const portrait = innerHeight > innerWidth;
    const aspectCandidates = portrait
      ? ['[class*="aspect-[9/16]"]', '[class*="aspect-[16/9]"]']
      : ['[class*="aspect-[16/9]"]', '[class*="aspect-[9/16]"]'];
    // Prefer an actual canvas/content surface.  Falling straight back to
    // `main` or `#root` is what makes editor workspaces get exported.
    const canvasCandidates = [
      selector,
      '[data-video-canvas]', '[data-export-canvas]', '[data-canvas]',
      '.video-canvas', '.render-canvas', '.export-canvas',
      '[class*="aspect-video"]', ...aspectCandidates,
      '[data-remotion-root]', '[data-composition]', 'canvas', 'video', 'svg',
    ];
    const desiredRatio = innerWidth / Math.max(1, innerHeight);
    const candidates = [];
    const seen = new Set();
    canvasCandidates.filter(Boolean).forEach((item, selectorOrder) => {
      try {
        document.querySelectorAll(item).forEach((element) => {
          if (seen.has(element)) return;
          const rect = element.getBoundingClientRect();
          if (rect.width < 40 || rect.height < 40) return;
          const ratio = rect.width / rect.height;
          const ratioDistance = Math.abs(Math.log(ratio / desiredRatio));
          const area = rect.width * rect.height;
          seen.add(element);
          candidates.push({element, selectorOrder, ratioDistance, area});
        });
      } catch (_) {}
    });
    // If a desktop canvas and a phone preview are both present, choose the
    // surface whose aspect ratio matches the requested composition, then the
    // largest matching surface.
    candidates.sort((a, b) => a.ratioDistance - b.ratioDistance || b.area - a.area || a.selectorOrder - b.selectorOrder);
    const target = candidates[0]?.element
      || ['main', '#root'].map((item) => document.querySelector(item)).find(Boolean);
    if (!target || target === document.body || target === document.documentElement) return;
    Object.assign(document.documentElement.style, {margin:'0', width:'100%', height:'100%', overflow:'hidden', background:'#05070d'});
    Object.assign(document.body.style, {margin:'0', width:'100%', height:'100%', overflow:'hidden', background:'#05070d'});
    target.setAttribute('data-beyond-render-target', 'true');
    Object.assign(target.style, {position:'fixed', inset:'0', width:'100vw', height:'100vh', maxWidth:'none', maxHeight:'none', margin:'0', borderRadius:'0', zIndex:'2147483647'});
  };
  const startPreview = () => {
    const button = [...document.querySelectorAll('button')].find((item) => /play( preview)?/i.test(item.textContent || ''));
    if (button) button.click();
  };
  window.addEventListener('message', (event) => {
    if (!event.data || event.data.type !== 'beyond-remotion-frame') return;
    const fps = event.data.fps || 30;
    const requestedFrame = Math.max(0, Math.floor(event.data.frame || 0));
    // A renderer page normally receives an ascending run of frames. Advance
    // only the missing interval; replaying frame zero on every message makes
    // requestAnimationFrame clocks jump backwards and corrupts animations.
    const firstStep = lastFrame < 0 ? 0 : lastFrame + 1;
    if (requestedFrame < lastFrame) return;
    for (let step = firstStep; step <= requestedFrame; step++) {
      now = (step / fps) * 1000;
      const callbacks = queue; queue = [];
      for (const [id, callback] of callbacks) if (!cancelled.has(id)) callback(now);
      cancelled.clear();
    }
    lastFrame = requestedFrame;
    for (const animation of document.getAnimations()) {
      try { animation.pause(); animation.currentTime = now; } catch (_) {}
    }
    // HTML artifacts often contain an autoplay/looping video.  Let Remotion
    // own its clock; otherwise Chromium plays the media in wall-clock time
    // while Remotion renders frames as fast as it can, producing a sped-up
    // or repeatedly-looped export.
    for (const media of document.querySelectorAll('video, audio')) {
      try {
        media.pause();
        if (Number.isFinite(media.duration) && media.duration > 0) {
          media.currentTime = (now / 1000) % media.duration;
        }
      } catch (_) {}
    }
  });
  addEventListener('DOMContentLoaded', () => setTimeout(() => {
    focusCanvas(new URLSearchParams(location.search).get('selector') || '');
    startPreview();
    parent.postMessage({type:'beyond-artifact-ready', nativeNow: nativeNow()}, '*');
  }, 60));
})();
</script>`;

const createHtmlProject = async (projectRoot, uploadedHtml, options) => {
  const publicDirectory = join(projectRoot, 'public');
  const sourceDirectory = join(projectRoot, 'src');
  await mkdir(publicDirectory, {recursive: true});
  await mkdir(sourceDirectory, {recursive: true});
  let html = await readFile(uploadedHtml, 'utf8');
  const renderOptions = inferHtmlRenderOptions(html, options);
  html = html.includes('<head>') ? html.replace('<head>', `<head>${bridgeScript}`) : bridgeScript + html;
  await writeFile(join(publicDirectory, 'artifact.html'), html);
  await writeFile(join(projectRoot, 'package.json'), JSON.stringify({name: 'beyond-html-artifact', private: true}, null, 2));
  await writeFile(join(sourceDirectory, 'index.tsx'), `import {registerRoot} from 'remotion';\nimport {Root} from './Root';\nregisterRoot(Root);\n`);
  await writeFile(join(sourceDirectory, 'Root.tsx'), `import React, {useEffect, useRef, useState} from 'react';
import {AbsoluteFill, Composition, continueRender, delayRender, staticFile, useCurrentFrame, useVideoConfig} from 'remotion';

const selector = ${JSON.stringify(renderOptions.selector || '')};
const Artifact = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const iframe = useRef<HTMLIFrameElement>(null);
  const [ready, setReady] = useState(false);
  const [handle] = useState(() => delayRender('Waiting for HTML artifact'));
  const frameHandle = React.useMemo(() => delayRender('Advancing HTML artifact to frame ' + frame), [frame]);
  useEffect(() => {
    if (!ready) return;
    iframe.current?.contentWindow?.postMessage({type:'beyond-remotion-frame', frame, fps, time:(frame / fps) * 1000}, '*');
    const timeout = setTimeout(() => continueRender(frameHandle), 80);
    return () => { clearTimeout(timeout); continueRender(frameHandle); };
  }, [frame, fps, ready, frameHandle]);
  return <AbsoluteFill style={{background:'#05070d'}}><iframe ref={iframe} title="HTML artifact" src={staticFile('artifact.html') + '?selector=' + encodeURIComponent(selector)} onLoad={() => {setTimeout(() => {setReady(true); continueRender(handle);}, 250)}} style={{width:'100%',height:'100%',border:0,background:'#05070d'}} /></AbsoluteFill>;
};

export const Root = () => <Composition id="HtmlArtifact" component={Artifact} width={${renderOptions.width}} height={${renderOptions.height}} fps={${renderOptions.fps}} durationInFrames={${renderOptions.durationInFrames}} />;
`);
  return join(sourceDirectory, 'index.tsx');
};

const createRecordingProject = async (projectRoot, uploadedRecording, options) => {
  const publicDirectory = join(projectRoot, 'public');
  const sourceDirectory = join(projectRoot, 'src');
  await mkdir(publicDirectory, {recursive: true});
  await mkdir(sourceDirectory, {recursive: true});
  const recordingName = 'screen-recording' + (extname(uploadedRecording).toLowerCase() || '.webm');
  await rename(uploadedRecording, join(publicDirectory, recordingName));
  await writeFile(join(projectRoot, 'package.json'), JSON.stringify({name: 'beyond-screen-recording', private: true}, null, 2));
  await writeFile(join(sourceDirectory, 'index.tsx'), `import React from 'react';
import {AbsoluteFill, Composition, OffthreadVideo, registerRoot, staticFile} from 'remotion';
const Recording = () => <AbsoluteFill style={{background:'#000'}}><OffthreadVideo src={staticFile(${JSON.stringify(recordingName)})} style={{width:'100%',height:'100%',objectFit:'cover'}} /></AbsoluteFill>;
const Root = () => <Composition id="ScreenRecording" component={Recording} width={${options.width}} height={${options.height}} fps={${options.fps}} durationInFrames={${options.durationInFrames}} />;
registerRoot(Root);
`);
  return join(sourceDirectory, 'index.tsx');
};

const importArtifact = async (request, name, searchParams) => {
  const extension = extname(name).toLowerCase();
  if (!['.zip', '.html', '.htm'].includes(extension)) throw new Error('Choose a .zip or .html React/Remotion artifact.');
  const id = randomUUID();
  const importRoot = join(importsDirectory, id);
  await mkdir(importRoot, {recursive: true});
  const uploadPath = join(importRoot, safeName(name));
  await receiveFile(request, uploadPath);
  let projectRoot;
  let entry;
  let type;
  if (extension === '.zip') {
    type = 'remotion';
    const extracted = join(importRoot, 'project');
    await mkdir(extracted, {recursive: true});
    const listing = await run('unzip', ['-Z1', uploadPath]);
    validateArchiveEntries(listing.stdout);
    await run('unzip', ['-q', uploadPath, '-d', extracted]);
    projectRoot = await findProjectRoot(extracted);
    if (!projectRoot) throw new Error('ZIP does not contain a package.json file.');
    entry = await findEntry(projectRoot);
  } else {
    type = 'html';
    projectRoot = join(importRoot, 'html-project');
    await mkdir(projectRoot, {recursive: true});
    const fps = Math.max(1, Math.min(60, Number(searchParams.get('fps')) || 30));
    const seconds = Math.max(1, Math.min(300, Number(searchParams.get('seconds')) || 15));
    entry = await createHtmlProject(projectRoot, uploadPath, {
      width: Math.max(320, Math.min(3840, Number(searchParams.get('width')) || 1920)),
      height: Math.max(320, Math.min(3840, Number(searchParams.get('height')) || 1080)),
      fps,
      durationInFrames: Math.round(fps * seconds),
      selector: (searchParams.get('selector') || '').slice(0, 300),
    });
  }
  const compositions = await inspectCompositions(entry, projectRoot);
  const record = {id, name, type, projectRoot, entry, compositions, createdAt: Date.now()};
  imports.set(id, record);
  return record;
};

const importScreenRecording = async (request, name, searchParams) => {
  const extension = extname(name).toLowerCase();
  if (!['.webm', '.mp4', '.mkv'].includes(extension)) throw new Error('Screen recording must be WebM, MP4, or MKV.');
  const id = randomUUID();
  const importRoot = join(importsDirectory, id);
  const projectRoot = join(importRoot, 'recording-project');
  await mkdir(projectRoot, {recursive: true});
  const uploadPath = join(importRoot, safeName(name));
  await receiveFile(request, uploadPath);
  const fps = Math.max(1, Math.min(60, Number(searchParams.get('fps')) || 30));
  const seconds = Math.max(1, Math.min(300, Number(searchParams.get('seconds')) || 15));
  const entry = await createRecordingProject(projectRoot, uploadPath, {
    width: Math.max(320, Math.min(3840, Number(searchParams.get('width')) || 1920)),
    height: Math.max(320, Math.min(3840, Number(searchParams.get('height')) || 1080)),
    fps,
    durationInFrames: Math.round(fps * seconds),
  });
  const compositions = await inspectCompositions(entry, projectRoot);
  const record = {id, name, type: 'screen-recording', projectRoot, entry, compositions, createdAt: Date.now()};
  imports.set(id, record);
  return record;
};

const parseProgress = (text) => {
  const matches = [...text.matchAll(/(?:Rendered|Rendering)\s+(\d+)\/(\d+)/gi)];
  if (!matches.length) return null;
  const last = matches.at(-1);
  return Math.max(0, Math.min(0.99, Number(last[1]) / Number(last[2])));
};

const startRender = async (artifact, compositionId) => {
  const composition = artifact.compositions.find((item) => item.id === compositionId);
  if (!composition) throw new Error('Choose a composition from the imported artifact.');
  const jobId = randomUUID();
  const output = join(outputsDirectory, `${jobId}-${safeName(compositionId)}.mp4`);
  const job = {id: jobId, artifactId: artifact.id, compositionId, status: 'queued', progress: 0, log: '', output, error: null};
  jobs.set(jobId, job);
  queueMicrotask(async () => {
    job.status = 'rendering';
    try {
      await run(nodeExecutable, [remotionCli, 'render', artifact.entry, compositionId, output, '--codec=h264', '--overwrite'], {
        cwd: artifact.projectRoot,
        env: {...process.env, BROWSER_DOWNLOAD_BEHAVIOR: 'download-if-missing'},
        onOutput: (chunk) => {
          job.log = (job.log + chunk).slice(-12000);
          const progress = parseProgress(job.log);
          if (progress !== null) job.progress = progress;
        },
      });
      job.status = 'complete';
      job.progress = 1;
    } catch (error) {
      job.status = 'failed';
      job.error = error.message;
    }
  });
  return job;
};

const publicArtifact = (artifact) => ({
  id: artifact.id,
  name: artifact.name,
  type: artifact.type,
  createdAt: new Date(artifact.createdAt).toISOString(),
  compositions: artifact.compositions,
});

const publicJob = (job) => ({
  id: job.id,
  artifactId: job.artifactId,
  compositionId: job.compositionId,
  status: job.status,
  progress: job.progress,
  error: job.error,
  downloadPath: job.status === 'complete' ? `/api/ai/jobs/${job.id}/download` : null,
});

const requestBaseUrl = (request) => {
  const forwardedProtocol = String(request.headers['x-forwarded-proto'] || '').split(',')[0].trim().toLowerCase();
  const protocol = ['http', 'https'].includes(forwardedProtocol) ? forwardedProtocol : 'http';
  const forwardedHost = String(request.headers['x-forwarded-host'] || '').split(',')[0].trim();
  const candidateHost = forwardedHost || String(request.headers.host || `127.0.0.1:${port}`);
  const safeHost = /^[a-z0-9.-]+(?::\d+)?$/i.test(candidateHost) ? candidateHost : `127.0.0.1:${port}`;
  return `${protocol}://${safeHost}`;
};

const aiOpenApi = (request) => ({
  openapi: '3.1.0',
  info: {
    title: 'Beyond Studio Remotion AI API',
    version: '1.0.0',
    description: 'Render only trusted artifacts that an administrator has already imported into Beyond Studio. This API cannot upload or execute new source code.',
  },
  servers: [{url: requestBaseUrl(request)}],
  security: [{bearerAuth: []}],
  paths: {
    '/api/ai/artifacts': {
      get: {
        operationId: 'listBeyondStudioArtifacts',
        summary: 'List trusted artifacts and their renderable compositions',
        responses: {'200': {description: 'Trusted artifacts currently available in the renderer'}},
      },
    },
    '/api/ai/renders': {
      post: {
        operationId: 'createBeyondStudioRender',
        summary: 'Start an H.264 render from a trusted artifact',
        requestBody: {
          required: true,
          content: {'application/json': {schema: {
            type: 'object',
            additionalProperties: false,
            required: ['artifactId', 'compositionId'],
            properties: {
              artifactId: {type: 'string', format: 'uuid', description: 'ID returned by listBeyondStudioArtifacts'},
              compositionId: {type: 'string', minLength: 1, maxLength: 160},
            },
          }}},
        },
        responses: {'202': {description: 'Render accepted'}, '404': {description: 'Artifact or composition not found'}},
      },
    },
    '/api/ai/jobs/{jobId}': {
      get: {
        operationId: 'getBeyondStudioRender',
        summary: 'Read render progress and the download path when complete',
        parameters: [{name: 'jobId', in: 'path', required: true, schema: {type: 'string', format: 'uuid'}}],
        responses: {'200': {description: 'Current render status'}, '404': {description: 'Render job not found'}},
      },
    },
  },
  components: {securitySchemes: {bearerAuth: {type: 'http', scheme: 'bearer'}}},
});

const sendDownload = async (response, job) => {
  if (job.status !== 'complete' || !await exists(job.output)) return json(response, 404, {ok: false, error: 'Render output is not ready.'});
  const info = await stat(job.output);
  response.writeHead(200, {
    'Content-Type': 'video/mp4',
    'Content-Length': info.size,
    'Content-Disposition': `attachment; filename="${safeName(job.compositionId)}.mp4"`,
    ...corsHeaders(response.beyondOrigin),
  });
  createReadStream(job.output).pipe(response);
};

const server = createServer(async (request, response) => {
  const url = new URL(request.url || '/', `http://127.0.0.1:${port}`);
  const origin = allowedOrigin(request.headers.origin);
  response.beyondOrigin = origin || 'null';
  if (request.headers.origin && !origin) {
    return json(response, 403, {ok: false, error: 'This website is not allowed to use the local render bridge.'});
  }
  if (request.method === 'OPTIONS') {
    response.writeHead(204, corsHeaders(response.beyondOrigin));
    return response.end();
  }
  const isCapabilityDownload = /^\/api\/jobs\/[a-f0-9-]+\/download$/.test(url.pathname);
  const isPublicHealth = request.method === 'GET' && url.pathname === '/api/health';
  const isPublicAiSchema = request.method === 'GET' && url.pathname === '/api/ai/openapi.json';
  const isAiApi = url.pathname.startsWith('/api/ai/');
  if (url.pathname.startsWith('/api/') && !isCapabilityDownload && !isPublicHealth && !isPublicAiSchema) {
    const validToken = isAiApi ? aiTokenMatches(request) : tokenMatches(request, accessToken);
    if (!validToken) return json(response, 401, {ok: false, error: 'A valid render bridge token is required.'});
  }
  try {
    if (request.method === 'GET' && (url.pathname === '/' || url.pathname === '/studio')) {
      const studioPath = join(repositoryRoot, 'server', 'admin', 'daily-studio', 'remotion-renderer.php');
      if (!(await exists(studioPath))) {
        response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store', ...corsHeaders(response.beyondOrigin)});
        return response.end('<!doctype html><meta charset="utf-8"><title>Beyond Remotion</title><h1>Beyond Studio Remotion bridge</h1><p>The renderer is online.</p>');
      }
      const php = await readFile(studioPath, 'utf8');
      const html = php
        .replace(/^<\?php[\s\S]*?\?>\s*/, '')
        .replace(/<\?=json_encode\(\$bridgeUrl,[\s\S]*?\)\?>/, JSON.stringify(`http://127.0.0.1:${port}`))
        .replace(/<\?=json_encode\(\$bridgeToken,[\s\S]*?\)\?>/, JSON.stringify(accessToken));
      response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store', ...corsHeaders(response.beyondOrigin)});
      return response.end(html);
    }
    if (request.method === 'GET' && url.pathname === '/api/health') {
      return json(response, 200, {
        ok: true,
        service: 'Beyond Studio Remotion bridge',
        version: 2,
        runtime: process.version,
        remotionReady: await exists(remotionCli),
        aiApiReady: Boolean(aiAccessToken),
        maxUploadBytes,
      });
    }
    if (request.method === 'GET' && url.pathname === '/api/ai/openapi.json') {
      return json(response, 200, aiOpenApi(request));
    }
    if (request.method === 'GET' && (url.pathname === '/api/artifacts' || url.pathname === '/api/ai/artifacts')) {
      return json(response, 200, {ok: true, artifacts: [...imports.values()].map(publicArtifact)});
    }
    if (request.method === 'POST' && url.pathname === '/api/ai/renders') {
      const options = await readJsonBody(request);
      const artifactId = String(options.artifactId || '');
      const compositionId = String(options.compositionId || '');
      const artifact = imports.get(artifactId);
      if (!artifact) return json(response, 404, {ok: false, error: 'Trusted artifact not found. Import it in Beyond Studio first.'});
      const job = await startRender(artifact, compositionId);
      return json(response, 202, {ok: true, job: publicJob(job)});
    }
    const aiJobMatch = url.pathname.match(/^\/api\/ai\/jobs\/([a-f0-9-]+)$/);
    if (request.method === 'GET' && aiJobMatch) {
      const job = jobs.get(aiJobMatch[1]);
      if (!job) return json(response, 404, {ok: false, error: 'Render job was not found.'});
      return json(response, 200, {ok: true, job: publicJob(job)});
    }
    const aiDownloadMatch = url.pathname.match(/^\/api\/ai\/jobs\/([a-f0-9-]+)\/download$/);
    if (request.method === 'GET' && aiDownloadMatch) {
      const job = jobs.get(aiDownloadMatch[1]);
      if (!job) return json(response, 404, {ok: false, error: 'Render job was not found.'});
      return sendDownload(response, job);
    }
    if (request.method === 'POST' && url.pathname === '/api/import') {
      const name = decodeURIComponent(request.headers['x-artifact-name'] || 'artifact');
      const artifact = await importArtifact(request, name, url.searchParams);
      return json(response, 200, {ok: true, artifact: {
        id: artifact.id, name: artifact.name, type: artifact.type, compositions: artifact.compositions,
      }});
    }
    if (request.method === 'POST' && url.pathname === '/api/import-recording') {
      const name = decodeURIComponent(request.headers['x-artifact-name'] || 'screen-recording.webm');
      const artifact = await importScreenRecording(request, name, url.searchParams);
      return json(response, 200, {ok: true, artifact: {
        id: artifact.id, name: artifact.name, type: artifact.type, compositions: artifact.compositions,
      }});
    }
    const renderMatch = url.pathname.match(/^\/api\/artifacts\/([a-f0-9-]+)\/render$/);
    if (request.method === 'POST' && renderMatch) {
      const artifact = imports.get(renderMatch[1]);
      if (!artifact) return json(response, 404, {ok: false, error: 'Import expired. Import the artifact again.'});
      const options = await readJsonBody(request);
      const job = await startRender(artifact, options.compositionId);
      return json(response, 202, {ok: true, job: {id: job.id, status: job.status, progress: job.progress}});
    }
    const jobMatch = url.pathname.match(/^\/api\/jobs\/([a-f0-9-]+)$/);
    if (request.method === 'GET' && jobMatch) {
      const job = jobs.get(jobMatch[1]);
      if (!job) return json(response, 404, {ok: false, error: 'Render job was not found.'});
      return json(response, 200, {ok: true, job: {id: job.id, status: job.status, progress: job.progress, error: job.error, log: job.log}});
    }
    const downloadMatch = url.pathname.match(/^\/api\/jobs\/([a-f0-9-]+)\/download$/);
    if (request.method === 'GET' && downloadMatch) {
      const job = jobs.get(downloadMatch[1]);
      if (!job) return json(response, 404, {ok: false, error: 'Render job was not found.'});
      return sendDownload(response, job);
    }
    return json(response, 404, {ok: false, error: 'Not found.'});
  } catch (error) {
    return json(response, 400, {ok: false, error: error.message});
  }
});

server.listen(port, host, () => {
  console.log(`Beyond Studio Remotion bridge ready on ${host}:${port}`);
  if (host !== '127.0.0.1' && !accessToken) console.warn('WARNING: Set BEYOND_STUDIO_REMOTION_TOKEN before exposing this service to a network.');
  console.log('Keep this terminal open while importing or rendering trusted artifacts.');
});

const shutdown = async () => {
  server.close();
  process.exit(0);
};
process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
