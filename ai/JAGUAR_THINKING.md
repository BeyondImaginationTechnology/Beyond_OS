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
| Draw | Signed-in v0.5 preview | Separate image-generation GPU worker | 10 BIT$ per successful image |

The public mode catalog in `ai/includes/modes.php` is shared by the chat UI and PHP API. It maps `core` to runtime `explain` and public `build` to the distinct runtime mode `build`. Build is a text-only software ideation and design preview: it can brainstorm features, discuss architecture and UX, and offer coding guidance or illustrative examples, but cannot inspect repositories, tools, or production systems. Image and video generation belong to Draw and Video. Admin Code Thinking remains separate and maps to runtime `code`; its API checks the Beyond ID administrator role before project listing or repository access.

## Subscription shape

The remaining planned mode values are product-design estimates. Draw uses a wallet hold and an idempotent debit after a successful image. The request flow is:

```text
Beyond ID → mode entitlement → credit reservation → Jaguar Thinking router → worker → usage settlement
```

Text modes remain the low-cost/free entry point. Draw uses an atomic 10 BIT$ reservation to prevent concurrent GPU calls from overspending one wallet. Speak still needs reservations and hard limits before release.

## Runtime contract

`POST /v1/chat` accepts a `mode` plus the existing message list and returns the selected mode in the response. Explain, Build, and Code share the same base model and any configured adapter but have separate instructions. Public Build receives no repository context. Admin Code receives committed project files at a cited revision; proposed diffs are accepted only for supplied files, applied in a disposable checkout, and shown for review without modifying the selected project. The PHP host currently runs only configured PHP/JavaScript syntax checks and Git whitespace checks there. Project tests that execute code are reported as skipped until an isolated check runner exists; a disposable checkout alone does not sandbox code execution. Draw has a separate private GPU worker. Research, Translate, and Speak still need dedicated workflows.
