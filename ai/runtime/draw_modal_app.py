"""Modal deployment for the private Jaguar Draw v0.5 worker."""
from pathlib import Path

import modal

APP_NAME = "jaguar-draw-v0-5"
RUNTIME_DIR = "/opt/jaguar-draw"
source = Path(__file__).with_name("draw_worker.py")

image = (
    modal.Image.from_registry("nvidia/cuda:12.4.1-cudnn-runtime-ubuntu22.04", add_python="3.11")
    .pip_install(
        "fastapi[standard]>=0.115,<1",
        "diffusers>=0.31,<1",
        "transformers>=4.46,<5",
        "accelerate>=0.34,<2",
        "safetensors>=0.4,<1",
        "torch>=2.4,<3",
        "Pillow>=10,<12",
    )
    .env({"JAGUAR_DRAW_MODEL_ID": "stabilityai/sdxl-turbo"})
    .add_local_file(source, f"{RUNTIME_DIR}/draw_worker.py", copy=True)
)

app = modal.App(APP_NAME)


@app.function(
    image=image,
    gpu="L4",
    secrets=[modal.Secret.from_name("huggingface-secret"), modal.Secret.from_name("jaguar-runtime-secret")],
    min_containers=0,
    max_containers=1,
    scaledown_window=60,
    timeout=300,
    startup_timeout=900,
)
@modal.asgi_app()
def jaguar_draw_api():
    import sys

    sys.path.insert(0, RUNTIME_DIR)
    from draw_worker import app as worker_app

    return worker_app

