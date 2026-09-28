"""Private Jaguar Draw worker for the v0.5 image contract."""
from __future__ import annotations

import base64
import io
import os
import secrets
from functools import lru_cache

import torch
from diffusers import AutoPipelineForText2Image
from fastapi import Depends, FastAPI, Header, HTTPException, status
from pydantic import BaseModel, Field

MODEL_ID = os.getenv("JAGUAR_DRAW_MODEL_ID", "stabilityai/sdxl-turbo")
HF_TOKEN = os.getenv("HF_TOKEN", "").strip()
RUNTIME_TOKEN = os.getenv("JAGUAR_RUNTIME_TOKEN", "").strip()
MAX_PROMPT = 2000


class DrawRequest(BaseModel):
    prompt: str = Field(min_length=1, max_length=MAX_PROMPT)
    language: str = Field(default="en", pattern="^(en|fr|es)$")


def require_runtime_token(authorization: str | None = Header(default=None)) -> None:
    if not RUNTIME_TOKEN:
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail="Draw authentication is not configured")
    if authorization is None or not secrets.compare_digest(authorization, f"Bearer {RUNTIME_TOKEN}"):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Unauthorized draw request")


@lru_cache(maxsize=1)
def load_pipeline():
    if not HF_TOKEN:
        raise RuntimeError("HF_TOKEN is not configured")
    if not torch.cuda.is_available():
        raise RuntimeError("Jaguar Draw requires a CUDA GPU")
    pipeline = AutoPipelineForText2Image.from_pretrained(
        MODEL_ID,
        torch_dtype=torch.float16,
        variant="fp16",
        token=HF_TOKEN,
    )
    pipeline.to("cuda")
    pipeline.set_progress_bar_config(disable=True)
    return pipeline


app = FastAPI(title="Jaguar Draw Runtime", version="0.5.0")


@app.get("/health")
def health() -> dict[str, object]:
    return {"ok": True, "model": MODEL_ID, "cuda": torch.cuda.is_available(), "loaded": load_pipeline.cache_info().currsize > 0}


@app.post("/v1/draw", dependencies=[Depends(require_runtime_token)])
def draw(request: DrawRequest) -> dict[str, str]:
    try:
        pipeline = load_pipeline()
        with torch.inference_mode():
            result = pipeline(
                request.prompt,
                num_inference_steps=4,
                guidance_scale=0.0,
                width=768,
                height=768,
            )
        image = result.images[0]
        output = io.BytesIO()
        image.save(output, format="PNG", optimize=True)
        encoded = base64.b64encode(output.getvalue()).decode("ascii")
        return {"model": MODEL_ID, "image_url": "data:image/png;base64," + encoded}
    except RuntimeError as error:
        raise HTTPException(status_code=503, detail="Jaguar Draw is not ready") from error

