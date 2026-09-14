"""Local inference runtime for Jaguar."""
from __future__ import annotations

import os
import secrets
from functools import lru_cache
from typing import Literal

import torch
from fastapi import Depends, FastAPI, Header, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from peft import PeftModel
from pydantic import BaseModel, Field
from transformers import AutoModelForCausalLM, AutoTokenizer, BitsAndBytesConfig

MODEL_ID = os.getenv("JAGUAR_MODEL_ID", "meta-llama/Llama-3.1-8B-Instruct")
ADAPTER_PATH = os.getenv("JAGUAR_ADAPTER_PATH", "").strip()
HF_TOKEN = os.getenv("HF_TOKEN")
DEVICE_MAP = os.getenv("JAGUAR_DEVICE_MAP", "auto")
LOAD_IN_4BIT = os.getenv("JAGUAR_LOAD_IN_4BIT", "1").strip().lower() not in {"0", "false", "no"}
MAX_NEW_TOKENS = min(max(int(os.getenv("JAGUAR_MAX_NEW_TOKENS", "512")), 1), 1024)
MAX_INPUT_TOKENS = min(max(int(os.getenv("JAGUAR_MAX_INPUT_TOKENS", "4096")), 512), 8192)
RUNTIME_TOKEN = os.getenv("JAGUAR_RUNTIME_TOKEN", "").strip()
SYSTEM_PROMPT = (
    "You are Jaguar, the Beyond-1 assistant built by GGOG. Teach AI like Feynman: "
    "use plain language, useful analogies, and Socratic questions when the learner "
    "is confused. Grade prompts using Score, Issues, Fixed, Why. State uncertainty "
    "rather than inventing facts. Never claim to take actions outside this conversation."
)
MODE_INSTRUCTIONS = {
    "explain": "Teach clearly with plain language, useful analogies, and a practical next step.",
    "code": "Act as a careful coding partner. Explain assumptions, show secure maintainable code, and call out how to test it.",
    "research": "When enabled, synthesize sources carefully and distinguish evidence from inference.",
    "translate": "When enabled, preserve meaning, tone, and cultural context rather than translating word for word.",
    "speak": "When enabled, write concise, natural spoken responses with clear pacing.",
    "draw": "When enabled, turn the user intent into a precise visual brief before generation.",
}
LANGUAGE_INSTRUCTIONS = {
    "en": "Respond in English unless the user asks otherwise.",
    "fr": "Respond in French unless the user asks otherwise.",
    "es": "Respond in Spanish unless the user asks otherwise.",
}

class ChatMessage(BaseModel):
    role: Literal["user", "assistant"]
    content: str = Field(min_length=1, max_length=8000)

class ChatRequest(BaseModel):
    mode: Literal["explain", "code", "research", "translate", "speak", "draw"] = "explain"
    language: Literal["en", "fr", "es"] = "en"
    messages: list[ChatMessage] = Field(min_length=1, max_length=24)
    max_new_tokens: int | None = Field(default=None, ge=1, le=1024)

class ChatResponse(BaseModel):
    model: str
    adapter: str | None = None
    mode: str
    message: str

@lru_cache(maxsize=1)
def load_model():
    if not HF_TOKEN:
        raise RuntimeError("HF_TOKEN is not configured")
    tokenizer = AutoTokenizer.from_pretrained(MODEL_ID, token=HF_TOKEN)
    if tokenizer.pad_token is None:
        tokenizer.pad_token = tokenizer.eos_token
    dtype = torch.bfloat16 if torch.cuda.is_available() else torch.float32
    model_options: dict[str, object] = {
        "token": HF_TOKEN,
        "device_map": DEVICE_MAP,
        "low_cpu_mem_usage": True,
    }
    if LOAD_IN_4BIT and torch.cuda.is_available():
        model_options["quantization_config"] = BitsAndBytesConfig(
            load_in_4bit=True,
            bnb_4bit_quant_type="nf4",
            bnb_4bit_compute_dtype=torch.bfloat16,
            bnb_4bit_use_double_quant=True,
        )
    else:
        model_options["torch_dtype"] = dtype
    model = AutoModelForCausalLM.from_pretrained(MODEL_ID, **model_options)
    if ADAPTER_PATH:
        model = PeftModel.from_pretrained(model, ADAPTER_PATH, is_trainable=False)
    model.eval()
    return tokenizer, model


def require_runtime_token(authorization: str | None = Header(default=None)) -> None:
    """Authenticate callers when this runtime is deployed beyond localhost."""
    if not RUNTIME_TOKEN:
        return
    expected = f"Bearer {RUNTIME_TOKEN}"
    if authorization is None or not secrets.compare_digest(authorization, expected):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Unauthorized runtime request")

app = FastAPI(title="Llama-Jaguar Runtime", version="0.2.0")
origins = [origin.strip() for origin in os.getenv("JAGUAR_ALLOWED_ORIGINS", "").split(",") if origin.strip()]
if origins:
    app.add_middleware(CORSMiddleware, allow_origins=origins, allow_methods=["POST"], allow_headers=["Content-Type"])

@app.get("/health")
def health() -> dict[str, object]:
    return {
        "ok": True,
        "model": MODEL_ID,
        "adapter": ADAPTER_PATH or None,
        "load_in_4bit": LOAD_IN_4BIT,
        "loaded": load_model.cache_info().currsize > 0,
        "cuda": torch.cuda.is_available(),
    }

@app.post("/v1/chat", response_model=ChatResponse, dependencies=[Depends(require_runtime_token)])
def chat(request: ChatRequest) -> ChatResponse:
    try:
        tokenizer, model = load_model()
    except RuntimeError as error:
        raise HTTPException(status_code=503, detail="Jaguar is not configured") from error
    mode_instruction = MODE_INSTRUCTIONS[request.mode]
    language_instruction = LANGUAGE_INSTRUCTIONS[request.language]
    messages = [{"role": "system", "content": f"{SYSTEM_PROMPT} Current Jaguar Thinking mode: {request.mode}. {mode_instruction} {language_instruction}"}]
    messages.extend(message.model_dump() for message in request.messages)
    inputs = tokenizer.apply_chat_template(
        messages,
        add_generation_prompt=True,
        return_tensors="pt",
        truncation=True,
        max_length=MAX_INPUT_TOKENS,
    ).to(model.device)
    with torch.inference_mode():
        output = model.generate(
            inputs,
            max_new_tokens=request.max_new_tokens or MAX_NEW_TOKENS,
            do_sample=True,
            temperature=0.7,
            top_p=0.9,
            pad_token_id=tokenizer.pad_token_id,
            eos_token_id=tokenizer.eos_token_id,
        )
    generated = output[0][inputs.shape[-1]:]
    return ChatResponse(
        model=MODEL_ID,
        adapter=ADAPTER_PATH or None,
        mode=request.mode,
        message=tokenizer.decode(generated, skip_special_tokens=True).strip(),
    )
