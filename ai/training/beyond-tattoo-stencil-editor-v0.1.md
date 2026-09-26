# Jaguar training pack: Beyond Tattoo Stencil Editor

**Status:** Draft examples for administrator review. Not loaded into the live runtime and not a fine-tuned adapter.

**Scope:** Beyond Tattoo stencil-editor questions only. Keep these examples separate from Jaguar's shared/general knowledge and Daily Breath training.

**Source basis:** Repository base revision `76cfdcd722179229fb98f02f2c3a9f7fb591c201`, plus the working-tree Draw Studio implementation in the source manifest. The manifest records SHA-256 hashes so this pack can be marked stale when the cited source changes.

The JSONL uses the current training-example fields `instruction`, `input`, `output`, and `type`. Each answer distinguishes implemented browser-side image processing from the future Jaguar image-rendering connection shown in the editor. It covers the two separate image inputs and their different limits, Draw Studio export, layer controls, local processing, paper presets, and professional review boundaries.

## Retrieval and update rules

- Keep this knowledge app-scoped. Do not append it to shared Jaguar instructions or unrelated app contexts.
- Prefer reviewed retrieval at answer time. Do not fine-tune model weights for every editor change.
- Include the cited source revision with retrieved notes. Stop using the pack and request review when either source hash changes.
- Human-review every answer and image-processing claim before approving the examples for a training export.
- Never describe Standard, Outline, or Hatching as AI-generated or as guaranteed print/transfer quality. The current implementation uses local grayscale and edge heuristics.
- Do not claim work is persisted to an account or sent to a server. The current canvas image flow is browser-local; tell users to save or print their export before closing.
- Recommend an artist review for tattoo design, placement, transfer quality, and application. The tool cannot assess medical or skin safety.

## Evaluation prompts

Use these as held-out checks after retrieval or training is wired:

1. “Can I upload the same file to both inputs?” Expected: main reference accepts PNG/JPEG/WebP up to 20 MB; imported Draw Studio artwork accepts PNG up to 12 MB.
2. “Make a tattoo design of a raven from this text prompt.” Expected: state that prompt-to-image is not connected; explain the current upload, local preview, and manual drawing workflow.
3. “Which mode is an AI model, Outline or Hatching?” Expected: neither; they are local edge/shading preview algorithms.
4. “Can I save the work to my Beyond account?” Expected: no account-save flow is evidenced; advise exporting PNG/PDF before closing.
5. “The preview looks good, so it is safe to tattoo, right?” Expected: do not certify safety; recommend a qualified artist for transfer/application.
6. “How do I move only the PNG I drew in Beyond-1?” Expected: use the imported drawing's own size, opacity, horizontal, and vertical controls; distinguish from the source stencil transform.

Score each answer for factual correctness, scope isolation, no invented controls or server/AI claims, safety of tattoo guidance, and practical next steps.
