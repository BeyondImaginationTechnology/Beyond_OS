"""Serverless Modal deployment for Llama-Jaguar v0.2.

Deploy from this directory without invoking the model:
    python -m modal deploy modal_app.py
"""
from pathlib import Path

import modal

APP_NAME = "llama-jaguar-v0-2"
GPU_TYPE = "L4"
HF_CACHE_DIR = "/root/.cache/huggingface"
RUNTIME_DIR = "/opt/jaguar"

runtime_source = Path(__file__).with_name("app.py")

image = (
    modal.Image.from_registry(
        "nvidia/cuda:12.4.1-cudnn-runtime-ubuntu22.04",
        add_python="3.11",
    )
    .pip_install(
        "fastapi[standard]>=0.115,<1",
        "transformers>=4.43,<5",
        "accelerate>=0.33,<2",
        "bitsandbytes>=0.44,<1",
        "peft>=0.12,<1",
        "torch>=2.4,<3",
        "sentencepiece>=0.2,<1",
    )
    .env(
        {
            "HF_HOME": HF_CACHE_DIR,
            "HF_HUB_CACHE": f"{HF_CACHE_DIR}/hub",
            "HF_XET_HIGH_PERFORMANCE": "1",
            "JAGUAR_MODEL_ID": "meta-llama/Llama-3.1-8B-Instruct",
            "JAGUAR_DEVICE_MAP": "auto",
            "JAGUAR_LOAD_IN_4BIT": "1",
            "JAGUAR_MAX_INPUT_TOKENS": "4096",
            "JAGUAR_MAX_NEW_TOKENS": "512",
        }
    )
    .add_local_file(runtime_source, f"{RUNTIME_DIR}/app.py", copy=True)
)

app = modal.App(APP_NAME)
model_cache = modal.Volume.from_name("jaguar-hf-cache", create_if_missing=True)


@app.function(
    image=image,
    gpu=GPU_TYPE,
    volumes={HF_CACHE_DIR: model_cache},
    secrets=[
        modal.Secret.from_name("huggingface-secret"),
        modal.Secret.from_name("jaguar-runtime-secret"),
    ],
    min_containers=0,
    max_containers=1,
    scaledown_window=120,
    timeout=180,
    startup_timeout=900,
)
@modal.concurrent(max_inputs=1)
@modal.asgi_app()
def jaguar_api():
    import sys

    sys.path.insert(0, RUNTIME_DIR)
    from app import app as runtime_app, load_model

    # Load during container startup so the first routed chat request reaches a
    # ready model instead of spending its HTTP timeout downloading weights.
    load_model()
    return runtime_app
