# Jaguar (Beyond-1)

Jaguar is Beyond AI's teaching-focused assistant: a LoRA adapter fine-tuned on
Meta Llama 3.1 8B Instruct. It explains AI with clear analogies, guides confused
learners Socratically, and improves prompts using `Score / Issues / Fixed / Why`.

This directory is deliberately set up for repeatable cloud training. The base
model and final adapter are too large to commit to this repository.

## Before training

1. Create and activate a Python virtual environment.
2. Install and authenticate Modal: `pip install modal` then `modal token new`.
3. Create a Hugging Face read token with access to Llama 3.1, accept Meta's
   Llama license on Hugging Face, then save it to Modal:
   `modal secret create huggingface-secret HF_TOKEN=hf_...`
4. Validate the dataset:
   `python validate_dataset.py dataset/jaguar_v0_1.jsonl`
5. Create the Modal volume and upload the dataset:
   `modal volume create beyond-1-vol`
   `modal volume put beyond-1-vol dataset/jaguar_v0_1.jsonl /jaguar_v0_1.jsonl`

## Train

```powershell
modal run train_beyond1.py
```

The default job uses an A100 40 GB for up to two hours. It downloads the base
model, trains a QLoRA adapter, saves it to the persistent `beyond-1-vol` volume,
and prints a stable artifact path. Set `WANDB_PROJECT` as a Modal secret only if
you want experiment tracking.

The starter dataset is intentionally tiny and only verifies the pipeline. Build
a reviewed dataset of at least 500 examples before judging the model, and aim for
5,000+ diverse, human-edited examples for a release candidate. Never include
private chats, API keys, copyrighted training dumps, or personal data without
permission.

## Retrieve the adapter

```powershell
modal volume get beyond-1-vol /artifacts/jaguar-v0.1 ./artifacts/jaguar-v0.1
```

Upload the adapter and tokenizer files to a private Hugging Face repo, or merge
the adapter with an appropriately licensed base model for the inference platform
you choose. Keep the Llama license and attribution with every distribution.

