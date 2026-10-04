# Needle Bot training pack: Beyond Tattoo Stencil Editor

**Status:** The 13 examples target Needle Bot, Beyond Tattoo’s AI companion. The product owner reviewed and approved the pack for retrieval on 2026-10-04 against the source revision recorded in its manifest. Needle Bot retrieves matching examples only; Jaguar does not retrieve them. This is not a fine-tuned adapter or live weight update.

**Scope:** Beyond Tattoo stencil-editor questions only. Keep these examples separate from Jaguar's shared/general knowledge, Daily Breath, and any other app’s training data.

**Source basis:** Repository base revision `202b7397b12c16421b0367b77afb54196fdf5e82`, plus the working-tree Draw Studio implementation in the source manifest. The manifest records SHA-256 hashes; a changed hash pauses retrieval until the pack is reviewed again.

The 13 reviewed examples in JSONL use the current training-example fields `instruction`, `input`, `output`, and `type`. They teach Needle Bot the verified editor workflow: browser-side image processing, separate image inputs and their limits, Draw Studio export, layer controls, paper presets, and professional review boundaries. The promotional images supplied for Needle Bot establish brand and companion direction; they do not independently verify tattooing techniques shown in artwork, so technique lessons need qualified review before being added as factual training examples.

## Retrieval and update rules

- Keep this knowledge scoped to Needle Bot in Beyond Tattoo. Do not append it to Jaguar instructions or unrelated app contexts.
- Prefer reviewed retrieval at answer time. Do not fine-tune model weights for every editor change.
- Include the cited source revision with retrieved notes. Stop using the pack and request review when a cited source hash changes.
- Human-review any new user-submitted answer before approving it for a future export. These 13 entries are approved for retrieval after checking the current source behavior and refresh of their source hashes.
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
