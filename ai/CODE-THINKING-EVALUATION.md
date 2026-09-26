# Jaguar Code Thinking 0.1 evaluation

## Environment

The implementation uses the configured Jaguar runtime for model-backed
responses. `JAGUAR_RUNTIME_URL` is not set in this development environment, and
there is no PHP executable available here. Browser JavaScript and the runtime
Python source can be syntax-checked locally, but the authenticated PHP API and
model behavior cannot be exercised end to end here. Results below distinguish
source review from a model evaluation.

## Representative BIT tasks

| Case | Correctness | Regressions | Unsupported repository claims | Proposed checks useful? |
| --- | --- | --- | --- | --- |
| Bug repair: guest challenge fails on the root-mounted Jaguar subdomain | Source review confirms both API routes use the page mount path and malformed challenge JSON gets a readable error. Live guest send was not run. | Not measured end to end. | Not measured against model output. | Yes: exercise root-mounted and `/ai` path installs, then send one guest message. |
| Small feature: add/edit/remove a revision-linked project decision | Source review confirms project notes are revision keyed, stale notes can be reviewed/removed, and cross-project notes require the approval control. PHP/API actions were not run. | Not measured; PHP CLI is unavailable here. | Not measured against model output. | Yes: test admin and non-admin access, note CRUD, stale revision, and private storage. |
| Cross-app dependency: change a shared auth helper | Not run: no second authorized checkout is configured. | Not measured. | Not measured. | Yes: configure two subprojects and ensure each response cites only the selected project plus currently approved architecture notes. |
| Ambiguous request: “Make Jaguar better.” | Not run: no Jaguar model runtime is configured in this workspace. | Not measured. | Not measured. | Yes: require a clarifying question and no invented file-level claims. |

## Quality measures to record when the runtime is available

For each task, have a reviewer compare the answer or diff with the selected
revision and record: requirement correctness, introduced regressions, claims
without a supporting file citation, useful versus irrelevant checks, and
whether assumptions and risks are stated. Do not treat a syntactically valid
diff as a correct change. Start with one reviewer-approved patch at a time.
