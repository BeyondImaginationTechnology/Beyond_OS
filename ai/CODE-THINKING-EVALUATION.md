# Jaguar Code Thinking 0.1 evaluation

## Environment

The local checkout has no `JAGUAR_RUNTIME_URL` or token. The hosted
`var/config/live.php` contains both and points to the production Modal runtime.
On 2026-09-25 the health endpoint returned `ok: true`, with the base
`meta-llama/Llama-3.1-8B-Instruct` model loaded on CUDA. All four prompts below
completed; the runtime reported `adapter: null`, so these results measure the
base model served by Jaguar, not a Jaguar-tuned adapter. Do not copy the
production token into this report or the repository. Requests went directly to
the authenticated model endpoint with selected source excerpts; they did not
exercise the PHP admin entitlement, CSRF, project picker, or patch API flow.

A checksum-verified portable PHP 8.5.11 CLI was used to lint the PHP files and
probe repository context loading locally. This validates PHP syntax and the
context guard, but not authenticated HTTP behavior.

The guest chat source also called an undefined `updateNonceFromResponse`
function after each API response. The repository has no such function and the
PHP API does not rotate the CSRF token, so the dead call was removed from
`ai/chat.php`.

Local verification on 2026-09-25: `php -l` passed for `ai/chat.php`,
`ai/code.php`, `ai/api/challenge.php`, `ai/api/chat.php`,
`ai/api/code-thinking.php`, `ai/includes/modes.php`, and
`ai/includes/code-thinking.php`. The embedded chat JavaScript passed `node --check` after replacing its three PHP-generated values with test placeholders. The context probe returned the current Git
revision and a requested allowed file while omitting `../var/config/live.php`
and `.env`. This probe exposed a broken path-validation regex; the guard now
checks path segments directly. `git diff --check` passed.

## Representative BIT tasks

| Case | Correctness | Regressions | Unsupported repository claims | Proposed checks useful? |
| --- | --- | --- | --- | --- |
| Bug repair: guest challenge routing and invalid response | **0/5.** Given PHP source for `ai/chat.php` and `ai/api/challenge.php`, the model invented `live_guest_page.js`, `server.js`, and Node endpoints. Its diff cannot apply to this repository. | High patch regression risk; fabricated file/framework claims. | **0/5.** Suggested Node commands and unrelated root URLs do not verify the PHP routes or `/ai` mount. |
| Small feature: copy the selected Git revision | **0/5.** Given the actual `ai/code.php`, the model invented Django models, views, and templates instead of using the supplied page. | High patch regression risk; fabricated Django project claims. | **0/5.** Generic `git diff`, `git log`, and `git status` do not verify clipboard behavior. |
| Cross-app dependency: shared Beyond ID session used by Jaguar | **0/5.** Given the PHP session helper and bootstraps, the model invented Flask modules and proposed an unsupported cookie change. | High patch regression risk; fabricated Flask file/framework claims. | **0/5.** No relevant root-subdomain, `/ai` path, cookie-scope, or admin-session checks were proposed. |
| Ambiguous request: “Make Jaguar better.” | **3/5.** It asked which kind of improvement the user meant, which is appropriate. It also added an irrelevant self-score and claimed a fix despite making none. | No file-level repository claims; some fabricated progress language. | Not applicable until the request is clarified. |

Across the three grounded coding tasks, repository grounding and patch quality
were poor. Proposed tests were not useful for the supplied PHP code. The diffs
were not applied, so regressions were not executed; their invented targets make
the regression risk high. These four calls are a small qualitative check, not a
statistical benchmark. Four reviewed counterexamples were added to the
Beyond-1 dataset and its formatter now uses a Code Thinking training prompt for
those examples. The full dataset has 12 examples, far below its documented
500-example minimum, so no fine-tuning run or adapter release was made.

## Evaluation rubric

For each task, have a reviewer compare the answer or diff with the selected
revision and record: requirement correctness, introduced regressions, claims
without a supporting file citation, useful versus irrelevant checks, and
whether assumptions and risks are stated. Do not treat a syntactically valid
diff as a correct change. Start with one reviewer-approved patch at a time.
