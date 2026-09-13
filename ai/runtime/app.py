"""Local inference runtime for Jaguar."""
from __future__ import annotations

import os
from functools import lru_cache
from typing import Literal

import torch
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from transformers import AutoModelForCausalLM, AutoTokenizer

MODEL_ID = os.getenv("JAGUAR_MODEL_ID", "meta-llama/Llama-3.1-8B-Instruct")
HF_TOKEN = os.getenv("HF_TOKEN")
DEVICE_MAP = os.getenv("JAGUAR_DEVICE_MAP", "auto")
MAX_NEW_TOKENS = min(max(int(os.getenv("JAGUAR_MAX_NEW_TOKENS", "512")), 1), 1024)
SYSTEM_PROMPT = "You are Jaguar, the AI guide for Beyond Imagination Technology. Be clear, practical, encouraging, and concise. State uncertainty rather than inventing facts. Never claim to take actions outside this conversation."

class ChatMessage(BaseModel):
    role: Literal["user", "assistant"]
    content: str = Field(min_length=1, max_length=8000)

class ChatRequest(BaseModel):
    messages: list[ChatMessage] = Field(min_length=1, max_length=24)
    max_new_tokens: int | None = Field(default=None, ge=1, le=1024)

class ChatResponse(BaseModel):
    model: str
    message: str

@lru_cache(maxsize=1)
def load_model():
    if not HF_TOKEN:
        raise RuntimeError("HF_TOKEN is not configured")
    tokenizer = AutoTokenizer.from_pretrained(MODEL_ID, token=HF_TOKEN)
    dtype = torch.bfloat16 if torch.cuda.is_available() else torch.float32
    model = AutoModelForCausalLM.from_pretrained(MODEL_ID, token=HF_TOKEN, torch_dtype=dtype, device_map=DEVICE_MAP)
    model.eval()
    return tokenizer, model

app = FastAPI(title="Jaguar Runtime", version="0.1.0")
origins = [origin.strip() for origin in os.getenv("JAGUAR_ALLOWED_ORIGINS", "").split(",") if origin.strip()]
if origins:
    app.add_middleware(CORSMiddleware, allow_origins=origins, allow_methods=["POST"], allow_headers=["Content-Type"])

@app.get("/health")
def health() -> dict[str, object]:
    return {"ok": True, "model": MODEL_ID, "loaded": load_model.cache_info().currsize > 0, "cuda": torch.cuda.is_available()}

@app.post("/v1/chat", response_model=ChatResponse)
def chat(request: ChatRequest) -> ChatResponse:
    try:
        tokenizer, model = load_model()
    except RuntimeError as error:
        raise HTTPException(status_code=503, detail="Jaguar is not configured") from error
    messages = [{"role": "system", "content": SYSTEM_PROMPT}]
    messages.extend(message.model_dump() for message in request.messages)
    inputs = tokenizer.apply_chat_template(messages, add_generation_prompt=True, return_tensors="pt").to(model.device)
    with torch.inference_mode():
        output = model.generate(inputs, max_new_tokens=request.max_new_tokens or MAX_NEW_TOKENS, do_sample=True, temperature=0.7, top_p=0.9)
    generated = output[0][inputs.shape[-1]:]
    return ChatResponse(model=MODEL_ID, message=tokenizer.decode(generated, skip_special_tokens=True).strip())
