# Jaguar local runtime

Jaguar runs `meta-llama/Llama-3.1-8B-Instruct` locally through Transformers. A Hugging Face **read-only** token with access to the gated model is required only on the host that runs it.

Copy `.env.example` to an untracked `.env`, set `HF_TOKEN`, then run:

```powershell
Get-Content .env | ForEach-Object { if ($_ -match '^([^#=]+)=(.*)$') { Set-Item -Path "Env:$($matches[1])" -Value $matches[2] } }
py -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn app:app --host 127.0.0.1 --port 8088
```

The first model request downloads the model weights. Use a CUDA-capable GPU with sufficient VRAM for practical local inference; CPU mode is suitable only for development. Check `GET http://127.0.0.1:8088/health` before connecting the site.

For production, run this behind authenticated application infrastructure; do not expose the local runtime directly to the internet.

## Website deployment

The public prompt interface is served from `ai/chat.php`. Configure the subdomain document root as the repository's `ai` directory, then set these production environment variables:

```text
BEYOND_AI_ORIGIN=https://ai.beyondimagination.co.technology
BEYOND_SESSION_COOKIE_DOMAIN=.beyondimagination.co.technology
JAGUAR_RUNTIME_URL=https://your-private-jaguar-runtime.example
```

The shared hosting account serves the PHP interface and authenticated proxy. The model runtime must run separately on GPU-capable infrastructure.
