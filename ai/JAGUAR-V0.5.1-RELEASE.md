# Jaguar v0.5.1 release candidate

This release repairs the v0.5 Code Thinking and Draw flows without changing the
base model or deploying a new adapter. Needle Bot's Beyond Tattoo examples stay
in their separate training scope and do not become a public Jaguar mode.

## Changes

- Code Thinking reads files from the cited Git revision, accepts diffs only for
  supplied existing files, applies them in a disposable checkout, and displays
  the actual diff and check status for human review. Files larger than the
  full-file input limit can be supplied as cited line ranges, such as
  `ai/chat.php:280-380`; omitted lines are explicitly identified. Checks that could execute
  project code are skipped on the PHP host until an isolated runner exists.
- Draw atomically reserves 10 BIT$ before invoking the GPU worker, captures one
  wallet debit after a valid image, and releases the hold on failure. A
  user-scoped receipt shows held, charged, or released status. Abandoned holds
  are released on a later balance read after ten minutes.
- The web client waits longer than the Draw worker deadline and limits Draw
  prompts to the worker's 2,000-character maximum. The iOS model request waits
  120 seconds, beyond PHP's 105-second runtime deadline.
- A valid worker image is size- and format-checked, then saved under private
  server storage before the wallet captures its 10 BIT$ hold. A signed-in
  account can retrieve its charged image for seven days using its receipt;
  retrieval checks ownership, charged status, expiry, file size, and SHA-256.
  Expired image files are cleaned up as Jaguar usage continues.

## Verification and release gates

The focused PHP fixture test covers committed-file citation, invented-file
rejection, an applicable patch, failed application and syntax checks, unchanged
project source, atomic wallet holds, charge, release, stale recovery, and
cross-user receipt isolation. PHP lint, embedded JavaScript syntax, and
`git diff --check` pass locally. This is not an authenticated end-to-end test.

Before enabling paid Draw image recovery on the live site, apply the MySQL or
SQLite `20261003_01_jaguar_draw_images` migration (after
`20260929_01_jaguar_draw_holds`) and verify the production worker URL, token,
PHP request timeout, private storage permissions, wallet schema, and one
signed-in transaction. SDXL-Turbo's published license is non-commercial
and requires a separate Stability AI license for commercial/production or
hosted API use; secure that authorization or select a suitably licensed model
before offering Draw commercially. No paid GPU generation or production wallet
debit was made here.

Before promoting Code Thinking as a development lead, repeat the four
representative BIT evaluations through the authenticated admin API. The last
model evidence is the 2026-09-25 base Llama 3.1 8B run in
`CODE-THINKING-EVALUATION.md`, which failed all three grounded coding tasks.
The v0.5.1 API guards reduce fabricated patch acceptance; they do not improve
the model's code reasoning or establish correctness.

An authenticated admin attempt on 2026-09-30 stopped before the model call:
the live PHP configuration contains the runtime URLs but no Jaguar runtime
token entry. Restore the matching token in the protected PHP configuration and
Modal secret through the administrator's credential workflow, then repeat the
four evaluations. Do not copy the token into this repository or evaluation log.
