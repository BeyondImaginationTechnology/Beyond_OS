# Beyond Studio Remotion bridge

This local-only service lets the authenticated Beyond Studio web UI inspect and
render trusted Remotion projects without giving the public PHP server permission
to execute uploaded JavaScript.

## Start

```powershell
cd tools/beyond-studio-remotion
pnpm install
pnpm start
```

The bridge listens on `http://127.0.0.1:4317`. Keep the terminal open while
using **Beyond Studio → Video → Remotion renderer**.

The script is named `start.ps1` (singular), not `starts.ps1`.

If the PHP admin is not running locally, open `http://127.0.0.1:4317/studio`
for the same renderer as a standalone local workspace.

## Accepted artifacts

- `.zip`: a Remotion project containing `package.json` and a source entry such
  as `src/index.ts` or `src/index.tsx`. Core Remotion/React packages are supplied
  by the bridge. Add project-specific packages to this bridge if an artifact
  imports them.
- `.html`: a bundled React artifact. The bridge injects a virtual animation
  clock, wraps the page in a Remotion composition, and renders it as MP4. Use the
  optional selector in Beyond Studio to isolate the visual canvas from editor UI.

For HTML that cannot be driven frame-by-frame, choose **Screen-record fallback**.
The browser asks which tab or window to share, records it in real time, and the
bridge transcodes that capture to an H.264 MP4 through Remotion.

Imported JavaScript executes locally during bundling/rendering. Only import
artifacts you trust.

## Azure VM

For the production, token-protected HTTPS setup, follow [AZURE-VM.md](./AZURE-VM.md).
The cloud bridge supports these environment variables:

- `BEYOND_STUDIO_REMOTION_HOST` — keep `127.0.0.1` behind Nginx; use
  `0.0.0.0` only inside a secured container network.
- `PORT` or `BEYOND_STUDIO_REMOTION_PORT` — defaults to `4317`.
- `BEYOND_STUDIO_REMOTION_TOKEN` — required bearer token for cloud API calls.
- `BEYOND_STUDIO_REMOTION_AI_TOKEN` — separate bearer token for AI tool calls.
  It can list trusted imports and render them, but cannot upload or execute new code.
- `BEYOND_STUDIO_REMOTION_ORIGINS` — optional comma-separated HTTPS origins.

Cloud hosts must have the `unzip` command installed so uploaded Remotion ZIP
projects can be inspected and extracted safely.

## Meta AI / model tool calls

Meta's model API can request tool calls, while your calling application executes
them. Register the bridge's OpenAPI document as the tool contract:

```text
https://render.beyondimagination.co.technology/api/ai/openapi.json
```

Send `BEYOND_STUDIO_REMOTION_AI_TOKEN` as a bearer token. The scoped API can:

1. `GET /api/ai/artifacts` — list projects already imported and trusted by a
   Beyond Studio administrator.
2. `POST /api/ai/renders` — start a render with an `artifactId` and
   `compositionId`.
3. `GET /api/ai/jobs/{jobId}` — poll status and receive the download path.

The AI API intentionally cannot upload a ZIP or HTML artifact. This prevents a
prompt or external model from introducing arbitrary executable code to the
renderer. Import and approve projects through the Studio UI first.

## AWS EC2

For an AWS deployment using the same bridge, systemd unit, and Nginx proxy, see
[AWS-EC2.md](./AWS-EC2.md). The bridge does not need AWS credentials for this
first setup; artifacts and MP4 outputs remain on the encrypted instance volume.
