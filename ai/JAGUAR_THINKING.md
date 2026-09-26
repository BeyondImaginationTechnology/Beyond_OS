# Jaguar Thinking

Jaguar is the model family. **Jaguar Thinking** is the product layer that chooses how Jaguar helps with a request.

## Current routing

| Mode | State | Runtime | Planned usage |
| --- | --- | --- | --- |
| Explain | Live | Jaguar base/LoRA runtime | Free preview |
| Build | Public preview | Jaguar base/LoRA runtime (`build`) | Software brainstorming, coding guidance, and technical/UI design; no repository or tool access |
| Code Thinking 0.1 | Admin preview | Same runtime with a project-scoped coding instruction | Internal |
| Research | Planned | Retrieval pipeline | 2 credits |
| Translate | Planned | Language workflow | 1 credit |
| Speak | Planned | Text-to-speech worker | 5 credits |
| Draw | Planned | Image-generation GPU worker | 10 credits |

The public mode catalog in `ai/includes/modes.php` is shared by the chat UI and PHP API. It maps `core` to runtime `explain` and public `build` to the distinct runtime mode `build`. Build is a text-only software ideation and design preview: it can brainstorm features, discuss architecture and UX, and offer coding guidance or illustrative examples, but cannot inspect repositories, tools, or production systems. Image and video generation belong to Draw and Video. Admin Code Thinking remains separate and maps to runtime `code`; its API checks the Beyond ID administrator role before project listing or repository access.

## Subscription shape

The credit values are product-design estimates, not billing charges. Before taking payment, add a durable usage ledger and entitlement checks to the Beyond ID account service. A production request should then follow:

```text
Beyond ID → mode entitlement → credit reservation → Jaguar Thinking router → worker → usage settlement
```

Text modes can remain the low-cost/free entry point. Draw and Speak should use reservations and hard limits because their GPU/audio costs vary by request.

## Runtime contract

`POST /v1/chat` accepts a `mode` plus the existing message list and returns the selected mode in the response. Explain, Build, and Code share the same base model and adapter but have separate instructions. Public Build receives no repository context. Admin Code can receive bounded project context on the admin route and returns proposed unified diffs for review without writing to the checkout. Research, Translate, Speak, and Draw should get dedicated workers once their dependencies and cost controls are ready.
