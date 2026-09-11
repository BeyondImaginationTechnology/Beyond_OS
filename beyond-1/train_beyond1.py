"""Train Jaguar (Beyond-1) as a QLoRA adapter on Modal.

Run from this directory: modal run train_beyond1.py
"""

import modal

APP_NAME = "jaguar-beyond-1-trainer"
VOLUME_NAME = "beyond-1-vol"
DATASET_PATH = "/vol/jaguar_v0_1.jsonl"
ARTIFACT_DIR = "/vol/artifacts/jaguar-v0.1"
MODEL_NAME = "unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit"
MAX_SEQ_LENGTH = 2048

app = modal.App(APP_NAME)
image = (
    modal.Image.from_registry("pytorch/pytorch:2.3.0-cuda12.1-cudnn8-devel")
    .pip_install(
        "unsloth @ git+https://github.com/unslothai/unsloth.git",
        "transformers==4.44.2",
        "trl==0.9.6",
        "datasets==3.0.1",
        "accelerate",
        "bitsandbytes",
        "peft",
    )
)
volume = modal.Volume.from_name(VOLUME_NAME, create_if_missing=True)


@app.function(
    image=image,
    gpu="A100-40GB:1",
    timeout=7200,
    volumes={"/vol": volume},
    secrets=[modal.Secret.from_name("huggingface-secret")],
)
def train() -> str:
    import os
    from pathlib import Path

    import torch
    from datasets import load_dataset
    from transformers import TrainingArguments
    from trl import SFTTrainer
    from unsloth import FastLanguageModel

    if not Path(DATASET_PATH).is_file():
        raise FileNotFoundError(
            f"Dataset missing at {DATASET_PATH}. Upload it with `modal volume put`."
        )

    model, tokenizer = FastLanguageModel.from_pretrained(
        model_name=MODEL_NAME,
        max_seq_length=MAX_SEQ_LENGTH,
        dtype=None,
        load_in_4bit=True,
    )
    model = FastLanguageModel.get_peft_model(
        model,
        r=32,
        target_modules=["q_proj", "k_proj", "v_proj", "o_proj", "gate_proj", "up_proj", "down_proj"],
        lora_alpha=64,
        lora_dropout=0,
        bias="none",
        use_gradient_checkpointing="unsloth",
        random_state=3407,
    )

    dataset = load_dataset("json", data_files=DATASET_PATH, split="train")

    def format_examples(examples):
        texts = []
        for instruction, user_input, answer in zip(
            examples["instruction"], examples["input"], examples["output"]
        ):
            user_text = f"{instruction}\n{user_input}".strip()
            texts.append(
                "<|begin_of_text|><|start_header_id|>system<|end_header_id|>\n\n"
                "You are Jaguar, the Beyond-1 assistant built by GGOG. Teach AI like "
                "Feynman: plain language, useful analogies, and Socratic questions when "
                "the learner is confused. Grade prompts using Score, Issues, Fixed, Why."
                "<|eot_id|><|start_header_id|>user<|end_header_id|>\n\n"
                f"{user_text}<|eot_id|><|start_header_id|>assistant<|end_header_id|>\n\n"
                f"{answer}<|eot_id|>"
            )
        return {"text": texts}

    dataset = dataset.map(format_examples, batched=True, remove_columns=dataset.column_names)
    trainer = SFTTrainer(
        model=model,
        tokenizer=tokenizer,
        train_dataset=dataset,
        dataset_text_field="text",
        max_seq_length=MAX_SEQ_LENGTH,
        dataset_num_proc=2,
        args=TrainingArguments(
            per_device_train_batch_size=2,
            gradient_accumulation_steps=4,
            warmup_ratio=0.03,
            num_train_epochs=3,
            learning_rate=2e-4,
            fp16=not torch.cuda.is_bf16_supported(),
            bf16=torch.cuda.is_bf16_supported(),
            logging_steps=5,
            optim="adamw_8bit",
            weight_decay=0.01,
            lr_scheduler_type="linear",
            seed=3407,
            output_dir="/tmp/jaguar-checkpoints",
            save_strategy="epoch",
            report_to="none",
        ),
    )
    trainer.train()
    model.save_pretrained(ARTIFACT_DIR)
    tokenizer.save_pretrained(ARTIFACT_DIR)
    (Path(ARTIFACT_DIR) / "training_metadata.txt").write_text(
        f"base_model={MODEL_NAME}\nexamples={len(dataset)}\nsequence_length={MAX_SEQ_LENGTH}\n",
        encoding="utf-8",
    )
    volume.commit()
    return ARTIFACT_DIR


@app.local_entrypoint()
def main():
    artifact_path = train.remote()
    print(f"Jaguar adapter saved to {artifact_path}")

