# Jaguar local runtime

Jaguar runs `meta-llama/Llama-3.1-8B-Instruct` locally through Transformers. A Hugging Face **read-only** token with access to the gated model is required only on the host that runs it. After training, point `JAGUAR_ADAPTER_PATH` at the retrieved Beyond-1 LoRA directory to load Jaguar's tuned teaching voice on top of the base model.

The v0.2 runtime uses 4-bit inference by default. This keeps Llama-Jaguar practical on a Modal L4 while retaining a bfloat16 fallback for environments where quantization is disabled.

Copy `.env.example` to an untracked `.env`, set `HF_TOKEN`, then run:

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^([^#=]+)=(.*)$') { Set-Item -Path "Env:$($matches[1])" -Value $matches[2] } }
py -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn app:app --host 127.0.0.1 --port 8088
```

The first model request downloads the base-model weights. Use a CUDA-capable GPU with sufficient VRAM for practical local inference; CPU mode is suitable only for development. Check `GET http://127.0.0.1:8088/health` before connecting the site. The health response reports the configured adapter path, but does not load the model just to answer the health check.

## Load the trained adapter

After `modal volume get beyond-1-vol /artifacts/jaguar-v0.1 ./artifacts/jaguar-v0.1`, copy the adapter directory to the runtime host and set:

```text
JAGUAR_ADAPTER_PATH=/opt/jaguar/artifacts/jaguar-v0.1
JAGUAR_MAX_INPUT_TOKENS=4096
```

The runtime applies the adapter to the selected Llama 3.1 base model at startup. Keep the adapter private until its evaluations meet your release bar.

For production, run this behind authenticated application infrastructure; do not expose the local runtime directly to the internet.

## Needle Bot training pack

`../training/needle-bot-beyond-tattoo-stencil-editor-v0.1.jsonl` contains 13
examples intended for Needle Bot, Beyond Tattoo's AI companion. Retrieval
approval is paused because a cited editor source changed since review. The
examples are not loaded by Jaguar, and the live Needle Bot endpoint is not
connected to the pack. This is not fine-tuning or a live weight update.

## Admin Code Thinking

`../code.php` is the administrator-only Code Thinking 0.1 workspace. Its PHP
API checks the Beyond ID `admin` or `super_admin` role before listing projects,
reading repository context, storing project notes, or running checks. The
workspace uses a server-side project allowlist; never accept repository paths
from browser input. By default, a Git checkout of this repository is listed as
`Beyond OS`. To authorize additional BIT checkouts, configure
`JAGUAR_CODE_PROJECTS_JSON` on the web host with an explicit map such as:

```json
{
  "beyond-os": {
    "label": "Beyond OS",
    "path": "/srv/beyond-os",
    "checks": [["php", "-l", "ai/api/chat.php"]]
  },
  "beyond-french": {
    "label": "Beyond French",
    "path": "/srv/beyond-french",
    "checks": [["php", "-l", "api/jaguar.php"]]
  }
}
```

Every configured path must be a Git-tracked project directory inside a checkout
(a monorepo subdirectory is allowed and becomes the file-access boundary). Check entries are
argument arrays and run in a disposable detached checkout, without a shell,
when an administrator selects **Run configured checks** or submits a valid patch. Keep them
to local lint/test commands; do not configure deploy, publish, merge, migration,
or production-data commands. Code Thinking reads committed blobs at the displayed
revision. It accepts patches only for source files supplied to the model, applies
them only in the disposable checkout, and returns the actual diff and check results
for human review; the configured project checkout is never patched. Project notes are stored under private
Beyond runtime data and are included only when their recorded Git revision
matches the selected checkout. To share a note across project contexts, an
administrator must check the explicit BIT-wide approval box when saving it.
Shared notes cite their source project and revision and are omitted when that
source revision changes; an administrator can review, update, or remove stale
notes.

The web catalog maps `core` to runtime `explain` and public Build to its own
runtime `build` mode. Build is a text-only software brainstorming, technical
and UI design, and coding-guidance preview; it does not receive repository
context or generate images or video. Beyond Tattoo training examples belong
to Needle Bot and are not sent to Jaguar.
Admin Code Thinking calls the runtime `code` mode only through its separate
role-protected API.

## Website deployment

The public prompt interface is served from `ai/chat.php`. Configure the subdomain document root as the repository's `ai` directory, then set these production environment variables:

```text
BEYOND_AI_ORIGIN=https://ai.beyondimagination.co.technology
BEYOND_SESSION_COOKIE_DOMAIN=.beyondimagination.co.technology
JAGUAR_RUNTIME_URL=https://your-private-jaguar-runtime.example
JAGUAR_RUNTIME_TOKEN=a-long-random-secret-shared-only-with-the-runtime
JAGUAR_DRAW_RUNTIME_URL=https://your-private-draw-worker.example
```

The shared hosting account serves the PHP interface and authenticated proxy. The model runtime must run separately on GPU-capable infrastructure. Set the same non-empty `JAGUAR_RUNTIME_TOKEN` on both hosts: the PHP proxy sends it as a bearer token and the runtime rejects unauthenticated chat requests.

On the PHP host, the protected `var/config/live.php` may hold the endpoint settings under `jaguar.runtime_url`, `jaguar.draw_runtime_url`, and `jaguar.runtime_token`. Environment variables `JAGUAR_RUNTIME_URL`, `JAGUAR_DRAW_RUNTIME_URL`, and `JAGUAR_RUNTIME_TOKEN` take precedence when present; never commit live credentials.

Draw uses a separate private GPU worker configured as `JAGUAR_DRAW_RUNTIME_URL`. Deploy `draw_modal_app.py` with the existing Hugging Face and runtime secrets. It accepts `POST /v1/draw` with `{ "prompt": "...", "language": "en" }` and returns a bounded PNG data URL. The PHP proxy requires a signed-in user with at least 10 BIT$ and a prompt of at most 2,000 characters. Apply the `20260929_01_jaguar_draw_holds` database migration before enabling this path. PHP reserves 10 BIT$ atomically before the GPU call, records an idempotent debit only after a valid image, and releases the hold on failure. An abandoned hold is recovered after ten minutes on the next Jaguar balance read for that user.

The PHP proxy answers greetings, capability/version questions, thanks, and basic two-number arithmetic through `jaguar-fast-lane`. These requests never start a Modal GPU. Prompts that require language-model reasoning continue to the scale-to-zero L4 runtime.

Unsigned visitors can enter Jaguar without logging in. Their first send receives a short-lived, first-party proof-of-work challenge; the PHP API validates the signed session challenge and one-time work result before forwarding the prompt. Signed-in Beyond ID members bypass this check. No third-party verification service is required.

## Modal v0.2 deployment and schedule

`modal_app.py` deploys the FastAPI runtime as `llama-jaguar-v0-2` on one L4 GPU. Its cost-safe autoscaling policy is:

```text
minimum containers: 0
maximum containers: 1
idle shutdown window: 120 seconds
concurrent prompts per container: 1
```

This is request-driven scheduling: no GPU stays warm when Jaguar has no traffic. The first request after scale-to-zero will have a cold start while the container and model load.

Create the two Modal secrets before deploying. Never commit either value:

```powershell
python -m modal secret create huggingface-secret HF_TOKEN=YOUR_READ_TOKEN
python -m modal secret create jaguar-runtime-secret JAGUAR_RUNTIME_TOKEN=YOUR_LONG_RANDOM_RUNTIME_TOKEN
```

Then deploy from `ai/runtime`:

```powershell
python -m modal deploy modal_app.py
```

Deploy the Draw worker separately when the Hugging Face account has accepted the selected model license:

```powershell
python -m modal deploy draw_modal_app.py
```

Deployment builds the container and publishes the endpoint but does not load Llama onto a GPU. Copy the resulting Modal URL into `JAGUAR_RUNTIME_URL` on the PHP host, and configure the matching `JAGUAR_RUNTIME_TOKEN` there. The first authenticated `/v1/chat` request is the first GPU-backed model invocation.
