# Jaguar Thinking

Jaguar is the model family. **Jaguar Thinking** is the product layer that chooses how Jaguar helps with a request.

## Current routing

| Mode | State | Runtime | Planned usage |
| --- | --- | --- | --- |
| Explain | Live | Jaguar base/LoRA runtime | Free preview |
| Code | Preview | Same runtime with a coding instruction | Free preview |
| Research | Planned | Retrieval pipeline | 2 credits |
| Translate | Planned | Language workflow | 1 credit |
| Speak | Planned | Text-to-speech worker | 5 credits |
| Draw | Planned | Image-generation GPU worker | 10 credits |

The mode catalog in `ai/includes/modes.php` is shared by the authenticated chat UI and the PHP API. The API rejects planned modes instead of pretending that a paid or GPU workflow is already available.

## Subscription shape

The credit values are product-design estimates, not billing charges. Before taking payment, add a durable usage ledger and entitlement checks to the Beyond ID account service. A production request should then follow:

```text
Beyond ID → mode entitlement → credit reservation → Jaguar Thinking router → worker → usage settlement
```

Text modes can remain the low-cost/free entry point. Draw and Speak should use reservations and hard limits because their GPU/audio costs vary by request.

## Runtime contract

`POST /v1/chat` accepts a `mode` plus the existing message list and returns the selected mode in the response. Explain and Code currently share the same base model and adapter; their first distinction is the mode-specific system instruction. Research, Translate, Speak, and Draw should get dedicated workers once their dependencies and cost controls are ready.
