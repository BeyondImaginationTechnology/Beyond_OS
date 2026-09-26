# Jaguar Code Thinking 0.1 evaluation

## Environment

The implementation uses the configured Jaguar runtime for model-backed
responses. `JAGUAR_RUNTIME_URL` is not set in this development environment, and
there is no PHP executable available here. Browser JavaScript and the runtime
Python source can be syntax-checked locally, but the authenticated PHP API and
model behavior cannot be exercised end to end here. Results below distinguish
source review from a model evaluation.

## Representative BIT tasks

| Case | Prompt | Acceptance evidence | Result |
| --- | --- | --- | --- |
| Bug repair | “Guest verification shows `Unexpected token '<'` on the root-mounted Jaguar subdomain. Find the route error and propose a fix.” | The patch uses the PHP page mount path for both challenge and chat API routes; invalid JSON receives a clear message. Verify the live guest challenge and a follow-up chat after deployment. | Source review: route mismatch is addressed. End-to-end result: not run. |
| Small feature | “Add an editable, revision-linked project decision note.” | Admin-only API adds, edits, lists current-revision notes, and allows stale note removal; notes live in protected private storage. Verify add/edit/delete through the admin page and check stale notes after changing HEAD. | Source review: workflow is present. PHP/runtime result: not run. |
| Cross-app dependency | “Change a shared auth helper used by Beyond French and Beyond OS; identify affected clients.” | Both authorized checkouts appear independently; context never crosses project roots unless an architecture note is explicitly approved. Verify the allowlist contains both checkouts and the model cites files from each. | Not run: no secondary project checkout or runtime is configured here. |
| Ambiguous request | “Make Jaguar better.” | The model asks what outcome is wanted and identifies missing files instead of inventing repository facts. | Not run: no model runtime is configured. |

## Quality measures to record when the runtime is available

For each task, have a reviewer compare the answer or diff with the selected
revision and record: requirement correctness, introduced regressions, claims
without a supporting file citation, useful versus irrelevant checks, and
whether assumptions and risks are stated. Do not treat a syntactically valid
diff as a correct change. Start with one reviewer-approved patch at a time.
