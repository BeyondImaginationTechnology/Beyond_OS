import crypto from 'node:crypto';
import http from 'node:http';
import net from 'node:net';
import { GoogleAuth } from 'google-auth-library';
import WebSocket, { WebSocketServer } from 'ws';
import { readFile } from 'node:fs/promises';

const profiles = JSON.parse(await readFile(new URL('./machine-profiles.json', import.meta.url), 'utf8')).profiles;
const profileById = new Map(profiles.map((profile) => [profile.id, profile]));
const appOrigin = (process.env.BEYOND_WEBS_APP_ORIGIN || '').replace(/\/$/, '');
const lookupUrl = process.env.BEYOND_WEBS_LOOKUP_URL || '';
const provisionerSecret = process.env.BEYOND_WEBS_PROVISIONER_SECRET || '';
const ticketSecret = process.env.BEYOND_WEBS_GATEWAY_TICKET_SECRET || '';
const cookieSecret = process.env.BEYOND_WEBS_GATEWAY_COOKIE_SECRET || '';
const project = process.env.GCP_PROJECT_ID || '';
const zone = process.env.GCP_ZONE || '';
const image = process.env.BIT_OS_IMAGE || '';
const network = process.env.GCP_NETWORK || 'global/networks/default';
const subnetwork = process.env.GCP_SUBNETWORK || '';
const diskType = process.env.GCP_BOOT_DISK_TYPE || 'pd-balanced';
const machineTypes = parseJsonEnv('GCP_PROFILE_MACHINE_TYPES_JSON');
const accelerators = parseJsonEnv('GCP_PROFILE_ACCELERATORS_JSON');
const maxRunSeconds = Math.max(1800, Math.min(43200, Number.parseInt(process.env.SESSION_MAX_RUN_SECONDS || '14400', 10) || 14400));
const port = Number.parseInt(process.env.PORT || '8080', 10);
const serviceMode = process.env.SERVICE_MODE || 'combined';
const computeAuth = new GoogleAuth({ scopes: ['https://www.googleapis.com/auth/cloud-platform'] });

if (!['gateway', 'provisioner', 'combined'].includes(serviceMode)) throw new Error('SERVICE_MODE must be gateway, provisioner, or combined.');
if (serviceMode !== 'gateway' && provisionerSecret.length < 32) throw new Error('Provisioner signing secret must be at least 32 characters.');
if (serviceMode !== 'provisioner' && (!lookupUrl.startsWith('https://') || !appOrigin.startsWith('https://') || ticketSecret.length < 32 || cookieSecret.length < 32)) {
  throw new Error('Gateway requires HTTPS lookup and app origins and strong ticket/cookie secrets.');
}

function parseJsonEnv(name) {
  try { const value = JSON.parse(process.env[name] || '{}'); return value && typeof value === 'object' && !Array.isArray(value) ? value : {}; }
  catch { return {}; }
}

function b64url(value) { return Buffer.from(value).toString('base64url'); }
function constantEqual(a, b) {
  const left = Buffer.from(String(a)); const right = Buffer.from(String(b));
  return left.length === right.length && crypto.timingSafeEqual(left, right);
}
function signJwt(claims, secret) {
  const body = `${b64url('{"alg":"HS256","typ":"JWT"}')}.${b64url(JSON.stringify(claims))}`;
  return `${body}.${b64url(crypto.createHmac('sha256', secret).update(body).digest())}`;
}
function verifyJwt(token, secret, expectedAudience) {
  if (typeof token !== 'string' || token.length > 4096) throw new Error('Invalid ticket.');
  const parts = token.split('.');
  if (parts.length !== 3) throw new Error('Invalid ticket.');
  const signed = `${parts[0]}.${parts[1]}`;
  const expected = b64url(crypto.createHmac('sha256', secret).update(signed).digest());
  if (!constantEqual(parts[2], expected)) throw new Error('Invalid ticket signature.');
  const claims = JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8'));
  const now = Math.floor(Date.now() / 1000);
  if (claims.aud !== expectedAudience || !Number.isInteger(claims.sub) || claims.sub < 1
    || !/^[a-f0-9]{32}$/.test(claims.rid || '') || !Number.isInteger(claims.exp)
    || claims.exp <= now || claims.exp > now + 120) throw new Error('Ticket is expired or invalid.');
  return claims;
}
function json(res, status, value) {
  const body = JSON.stringify(value);
  res.writeHead(status, { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store', 'content-length': Buffer.byteLength(body), 'x-content-type-options': 'nosniff' });
  res.end(body);
}
async function readBody(req, max = 8192) {
  const chunks = []; let length = 0;
  for await (const chunk of req) { length += chunk.length; if (length > max) throw new Error('Request too large.'); chunks.push(chunk); }
  return Buffer.concat(chunks).toString('utf8');
}
function privateIpv4(value) {
  if (net.isIPv4(value) !== 4) return false;
  const octets = value.split('.').map(Number);
  return octets[0] === 10 || (octets[0] === 172 && octets[1] >= 16 && octets[1] <= 31)
    || (octets[0] === 192 && octets[1] === 168);
}
function verifyServiceRequest(req, body) {
  if (!provisionerSecret) throw new Error('Internal service is not configured.');
  const timestamp = String(req.headers['x-beyond-timestamp'] || '');
  const signature = String(req.headers['x-beyond-signature'] || '');
  const seconds = Number.parseInt(timestamp, 10);
  if (!Number.isInteger(seconds) || Math.abs(Date.now() / 1000 - seconds) > 60) throw new Error('Expired service request.');
  const expected = crypto.createHmac('sha256', provisionerSecret).update(`${timestamp}\n${body}`).digest('hex');
  if (!constantEqual(signature, expected)) throw new Error('Invalid service signature.');
}
async function googleRequest(path, options = {}) {
  const client = await computeAuth.getClient();
  const access = await client.getAccessToken();
  const token = typeof access === 'string' ? access : access?.token;
  if (!token) throw new Error('Google Cloud credentials are unavailable.');
  const response = await fetch(`https://compute.googleapis.com/compute/v1/projects/${encodeURIComponent(project)}${path}`, {
    ...options, headers: { authorization: `Bearer ${token}`, 'content-type': 'application/json', ...(options.headers || {}) },
    signal: AbortSignal.timeout(30000),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(`Compute Engine API returned ${response.status}: ${data.error?.message || 'request failed'}`);
  return data;
}
async function waitForOperation(operationName, maxMs = 150000) {
  const deadline = Date.now() + maxMs;
  while (Date.now() < deadline) {
    const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/operations/${encodeURIComponent(operationName)}`);
    if (operation.status === 'DONE') {
      if (operation.error?.errors?.length) throw new Error(operation.error.errors.map((item) => item.message).join('; '));
      return operation;
    }
    await new Promise((resolve) => setTimeout(resolve, 1800));
  }
  throw new Error('Compute Engine operation is still pending.');
}
async function getInstance(name) {
  return googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}`);
}
function privateAddress(instance) {
  const address = instance.networkInterfaces?.[0]?.networkIP || '';
  if (!privateIpv4(address)) throw new Error('The VPS has no private VPC address.');
  return address;
}
async function waitForNoVnc(ip, maxMs = 90000) {
  const deadline = Date.now() + maxMs;
  while (Date.now() < deadline) {
    try {
      const response = await fetch(`http://${ip}:6080/vnc.html`, { signal: AbortSignal.timeout(2200), redirect: 'error' });
      if (response.ok) return;
    } catch { /* Wait for the session image's noVNC service. */ }
    await new Promise((resolve) => setTimeout(resolve, 1800));
  }
  throw new Error('The VM started, but its private noVNC service did not become ready.');
}
function ownerLabel(userId) { return crypto.createHash('sha256').update(`${provisionerSecret}:${userId}`).digest('hex').slice(0, 24); }
function instanceName(requestId) { return `bw-${requestId.slice(0, 28)}`; }
function requireRequest(input) {
  if (!Number.isInteger(input.user_id) || input.user_id < 1 || !/^[a-f0-9]{32}$/.test(input.request_id || '')) throw new Error('Invalid session request.');
}
async function createInstance(input) {
  requireRequest(input);
  const profile = profileById.get(input.profile);
  const machineType = machineTypes[input.profile];
  if (!profile || !machineType || !project || !zone || !/^projects\/[a-z0-9-]+\/global\/images\/[a-z0-9-]+$/.test(image)) {
    throw new Error('This BIT OS profile is not configured for provisioning.');
  }
  const accelerator = accelerators[input.profile];
  if (profile.gpu === 'required' && (!accelerator || !accelerator.type || !Number.isInteger(accelerator.count) || accelerator.count < 1)) {
    throw new Error('The GPU profile is not configured for this region.');
  }
  if (accelerator && (!accelerator.type || !Number.isInteger(accelerator.count) || accelerator.count < 1)) throw new Error('Invalid GPU profile configuration.');
  const name = instanceName(input.request_id);
  const existing = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}`).catch((error) => {
    if (String(error.message).includes('returned 404')) return null;
    throw error;
  });
  const expectedOwner = ownerLabel(input.user_id);
  if (existing) {
    if (existing.labels?.['beyond-owner'] !== expectedOwner || existing.labels?.['beyond-request'] !== input.request_id) throw new Error('The matching VM name is owned by another session.');
    if (existing.status === 'TERMINATED') return changeInstanceState({ ...input, instance_name: name, zone }, 'resume');
    if (existing.status !== 'RUNNING') throw new Error(`The existing VPS is ${existing.status.toLowerCase()} and cannot be reused.`);
    const ip = privateAddress(existing);
    await waitForNoVnc(ip);
    return { instance_name: name, zone, private_ip: ip, runtime_limit_seconds: maxRunSeconds };
  }
  const networkInterface = { network };
  if (subnetwork) networkInterface.subnetwork = subnetwork;
  const resource = {
    name,
    machineType: `zones/${zone}/machineTypes/${machineType}`,
    labels: { 'beyond-owner': expectedOwner, 'beyond-request': input.request_id, 'beyond-profile': input.profile.toLowerCase() },
    tags: { items: ['beyond-webs-session'] },
    disks: [{ boot: true, autoDelete: true, initializeParams: {
      sourceImage: image, diskSizeGb: String(profile.ssd_gb), diskType: `zones/${zone}/diskTypes/${diskType}`,
    }}],
    networkInterfaces: [networkInterface],
    metadata: { items: [
      { key: 'block-project-ssh-keys', value: 'TRUE' },
      { key: 'enable-oslogin', value: 'TRUE' },
      { key: 'beyond-webs-profile', value: input.profile },
      { key: 'beyond-webs-flavour', value: String(input.flavour || '') },
      { key: 'beyond-webs-work-mode', value: String(input.work_mode || '') },
    ]},
    scheduling: {
      automaticRestart: false,
      onHostMaintenance: accelerator ? 'TERMINATE' : 'MIGRATE',
      maxRunDuration: { seconds: String(maxRunSeconds) },
      instanceTerminationAction: 'STOP',
    },
  };
  if (accelerator) resource.guestAccelerators = [{ acceleratorType: `zones/${zone}/acceleratorTypes/${accelerator.type}`, acceleratorCount: accelerator.count }];
  let created = false;
  try {
    const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances`, { method: 'POST', body: JSON.stringify(resource) });
    created = true;
    await waitForOperation(operation.name);
    const instance = await getInstance(name);
    if (instance.status !== 'RUNNING') throw new Error('The VPS did not reach the running state.');
    const ip = privateAddress(instance);
    await waitForNoVnc(ip);
    return { instance_name: name, zone, private_ip: ip, runtime_limit_seconds: maxRunSeconds };
  } catch (error) {
    if (created) {
      try {
        const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}`, { method: 'DELETE' });
        await waitForOperation(operation.name, 60000);
      } catch (cleanupError) { console.error('VPS cleanup after failed provision did not finish:', cleanupError.message); }
    }
    throw error;
  }
}
async function changeInstanceState(input, action) {
  requireRequest(input);
  const name = input.instance_name;
  if (typeof name !== 'string' || !/^bw-[a-f0-9]{28}$/.test(name) || input.zone !== zone) throw new Error('Invalid assigned VPS instance.');
  const instance = await getInstance(name);
  if (instance.labels?.['beyond-owner'] !== ownerLabel(input.user_id) || instance.labels?.['beyond-request'] !== input.request_id) throw new Error('VPS ownership check failed.');
  if (action === 'delete') {
    const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}`, { method: 'DELETE' });
    await waitForOperation(operation.name, 60000);
    return { state: 'DELETED' };
  }
  if (action === 'stop') {
    if (instance.status !== 'RUNNING') return { state: instance.status };
    const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}/stop`, { method: 'POST', body: '{}' });
    await waitForOperation(operation.name);
    return { state: 'TERMINATED' };
  }
  if (instance.status === 'RUNNING') return { instance_name: name, zone, private_ip: privateAddress(instance), runtime_limit_seconds: maxRunSeconds };
  if (instance.status !== 'TERMINATED') throw new Error('VPS is not ready to resume.');
  const scheduling = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}/setScheduling`, {
    method: 'POST', body: JSON.stringify({ automaticRestart: false, maxRunDuration: { seconds: String(maxRunSeconds) }, instanceTerminationAction: 'STOP' }),
  });
  await waitForOperation(scheduling.name);
  const operation = await googleRequest(`/zones/${encodeURIComponent(zone)}/instances/${encodeURIComponent(name)}/start`, { method: 'POST', body: '{}' });
  await waitForOperation(operation.name);
  const running = await getInstance(name);
  const ip = privateAddress(running);
  await waitForNoVnc(ip);
  return { instance_name: name, zone, private_ip: ip, runtime_limit_seconds: maxRunSeconds };
}

async function postLookup(requestId, userId) {
  if (!lookupUrl || !cookieSecret) throw new Error('Beyond ID lookup is not configured.');
  const body = JSON.stringify({ request_id: requestId, user_id: userId });
  const timestamp = String(Math.floor(Date.now() / 1000));
  const signature = crypto.createHmac('sha256', cookieSecret).update(`${timestamp}\n${body}`).digest('hex');
  const response = await fetch(lookupUrl, { method: 'POST', headers: {
    'content-type': 'application/json', 'x-beyond-timestamp': timestamp, 'x-beyond-signature': signature,
  }, body, signal: AbortSignal.timeout(8000) });
  const data = await response.json().catch(() => ({}));
  if (!response.ok || !data.active || !privateIpv4(data.private_ip)) throw new Error('This Beyond ID session is not active.');
  const expiry = Number(data.session_expires_at);
  if (!Number.isInteger(expiry) || expiry <= Date.now() / 1000) throw new Error('This VPS session has expired.');
  return { privateIp: data.private_ip, expiresAt: expiry };
}
function gatewayClaims(req) {
  const cookies = String(req.headers.cookie || '').split(';').map((part) => part.trim());
  const value = cookies.find((part) => part.startsWith('BW_GATEWAY='))?.slice('BW_GATEWAY='.length);
  const claims = verifyJwt(value, cookieSecret, 'beyond-webs-session');
  return claims;
}
async function resolveTarget(req) {
  const claims = gatewayClaims(req);
  const target = await postLookup(claims.rid, claims.sub);
  return { claims, ...target };
}
function proxyHttp(req, res, target, path) {
  const upstream = http.request({ hostname: target.privateIp, port: 6080, path, method: req.method, headers: {
    accept: req.headers.accept || '*/*', 'accept-encoding': 'identity',
    'user-agent': 'Beyond-Webs-noVNC-gateway',
  }, timeout: 15000 }, (response) => {
    res.writeHead(response.statusCode || 502, { 'cache-control': 'no-store', 'content-type': response.headers['content-type'] || 'application/octet-stream', 'x-content-type-options': 'nosniff' });
    response.pipe(res);
  });
  upstream.on('timeout', () => upstream.destroy(new Error('Desktop request timed out.')));
  upstream.on('error', () => { if (!res.headersSent) json(res, 502, { error: 'The BIT OS desktop is not responding.' }); else res.destroy(); });
  req.pipe(upstream);
}
function requestPath(req) {
  const pathname = new URL(req.url || '/', 'http://gateway.local').pathname;
  if (pathname.length > 2048 || pathname.includes('..') || pathname.includes('\\') || pathname.includes('\0')) throw new Error('Invalid desktop path.');
  if (pathname === '/') return '/vnc.html?autoconnect=true&resize=scale&path=websockify';
  return pathname + new URL(req.url || '/', 'http://gateway.local').search;
}
async function exchangeTicket(req, res) {
  if (!ticketSecret || !cookieSecret || !appOrigin) return json(res, 503, { error: 'Browser session gateway is not configured.' });
  const origin = String(req.headers.origin || '');
  if (origin && origin !== appOrigin) return json(res, 403, { error: 'Unexpected Beyond Webs origin.' });
  const raw = await readBody(req, 4096);
  let ticket = '';
  if ((req.headers['content-type'] || '').startsWith('application/x-www-form-urlencoded')) ticket = new URLSearchParams(raw).get('ticket') || '';
  else { try { ticket = JSON.parse(raw).ticket || ''; } catch { /* Invalid ticket body. */ } }
  let claims;
  try { claims = verifyJwt(ticket, ticketSecret, 'beyond-webs-gateway'); }
  catch { return json(res, 401, { error: 'The desktop ticket is invalid or expired.' }); }
  const session = await postLookup(claims.rid, claims.sub);
  const sessionExpiry = Math.min(Number(claims.session_exp) || 0, session.expiresAt);
  if (sessionExpiry <= Date.now() / 1000) return json(res, 401, { error: 'The VPS session has expired.' });
  const cookie = signJwt({ aud: 'beyond-webs-session', sub: claims.sub, rid: claims.rid, iat: Math.floor(Date.now() / 1000), exp: sessionExpiry }, cookieSecret);
  const maxAge = Math.max(1, sessionExpiry - Math.floor(Date.now() / 1000));
  res.writeHead(303, {
    location: '/vnc.html?autoconnect=true&resize=scale&path=websockify',
    'set-cookie': `BW_GATEWAY=${cookie}; Path=/; Max-Age=${maxAge}; Secure; HttpOnly; SameSite=Strict`,
    'cache-control': 'no-store', 'referrer-policy': 'no-referrer',
  });
  res.end();
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', 'http://gateway.local');
  try {
    if (req.method === 'GET' && url.pathname === '/_health') return json(res, 200, { ok: true });
    if (req.method === 'POST' && url.pathname === '/auth/exchange') return await exchangeTicket(req, res);
    if (serviceMode === 'gateway' && url.pathname.startsWith('/internal/')) return json(res, 404, { error: 'Not found.' });
    if (serviceMode !== 'gateway' && ['/internal/provision', '/internal/resume', '/internal/stop', '/internal/delete'].includes(url.pathname) && req.method === 'POST') {
      const body = await readBody(req);
      verifyServiceRequest(req, body);
      const input = JSON.parse(body);
      const result = url.pathname === '/internal/provision' ? await createInstance(input)
        : await changeInstanceState(input, ({ '/internal/stop': 'stop', '/internal/delete': 'delete' })[url.pathname] || 'resume');
      return json(res, 200, result);
    }
    if (serviceMode === 'provisioner') return json(res, 404, { error: 'Not found.' });
    if (!['GET', 'HEAD'].includes(req.method || '')) return json(res, 405, { error: 'Method not allowed.' });
    const target = await resolveTarget(req);
    if (target.claims.exp <= Date.now() / 1000) return json(res, 401, { error: 'Desktop session expired.' });
    const path = requestPath(req);
    proxyHttp(req, res, target, path);
  } catch (error) {
    const status = error.message.includes('expired') ? 401 : error.message.includes('configured') ? 503 : 400;
    if (!res.headersSent) json(res, status, { error: status === 400 ? 'The desktop request could not be authorized.' : error.message });
  }
});

const sockets = new WebSocketServer({ noServer: true, maxPayload: 1024 * 1024 });
server.on('upgrade', async (req, socket, head) => {
  try {
    if (serviceMode === 'provisioner') throw new Error('Gateway is disabled.');
    if (new URL(req.url || '/', 'http://gateway.local').pathname !== '/websockify') throw new Error('Invalid desktop socket.');
    const target = await resolveTarget(req);
    if (target.claims.exp <= Date.now() / 1000) throw new Error('Desktop session expired.');
    const upstream = new WebSocket(`ws://${target.privateIp}:6080/websockify`, { handshakeTimeout: 8000, maxPayload: 1024 * 1024 });
    sockets.handleUpgrade(req, socket, head, (browser) => {
      let connected = false;
      const closeBoth = (code = 1011) => { if (browser.readyState < WebSocket.CLOSING) browser.close(code); if (upstream.readyState < WebSocket.CLOSING) upstream.close(code); };
      upstream.on('open', () => { connected = true; });
      upstream.on('message', (data, binary) => { if (browser.readyState === WebSocket.OPEN) browser.send(data, { binary }); });
      browser.on('message', (data, binary) => {
        if (upstream.readyState === WebSocket.OPEN && upstream.bufferedAmount < 8 * 1024 * 1024) upstream.send(data, { binary });
        else closeBoth(1009);
      });
      browser.on('close', () => { if (upstream.readyState < WebSocket.CLOSING) upstream.close(); });
      upstream.on('close', () => { if (browser.readyState < WebSocket.CLOSING) browser.close(); });
      upstream.on('error', () => closeBoth());
      browser.on('error', () => closeBoth());
      setTimeout(() => { if (!connected) closeBoth(1013); }, 10000).unref();
    });
  } catch {
    socket.write('HTTP/1.1 401 Unauthorized\r\nConnection: close\r\n\r\n');
    socket.destroy();
  }
});

server.listen(port, '0.0.0.0', () => console.log(`Beyond Webs cloud control listening on ${port}`));
