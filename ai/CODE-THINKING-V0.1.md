# Jaguar Code Thinking 0.1 — v0.4 brainstorm

## Product intent

Give the Beyond ecosystem administrator a project-aware coding partner that can
act like a lead developer: understand the selected project, explain tradeoffs,
plan changes, and produce reviewable code work. Keep this as a private admin
capability; public guest Explain remains a separate experience.

“Lead developer” describes the quality and ownership of the work, not authority
to deploy or change production. Code Thinking should earn trust through
repo-grounded answers, useful plans, and changes that pass project checks.

## First slice: Code Thinking 0.1

- Add an admin-only Code Thinking mode and enforce access in the API, not only
  by hiding the mode in the browser.
- Let the administrator select an authorized BIT project/workspace. Keep each
  project's instructions and context scoped to that project; share only
  explicitly approved BIT-wide architecture notes.
- Start with read, explain, plan, and patch workflows. Show affected files,
  assumptions, risks, and verification results with every proposed change.
- Work in a bounded checkout and present a diff for review. Do not deploy,
  publish, merge, change credentials, or alter production data from this mode.
- Keep project memory as cited notes tied to a repository revision, with a way
  to correct or remove stale decisions.
- If no repository context or tool is available, say so and give a plan rather
  than implying that files were inspected or changed.

## v0.4 delivery order

1. Fix the guest challenge routing for both a root-mounted `ai` subdomain and a
   `/ai` path deployment. Return a useful message if the endpoint responds with
   HTML or another invalid response.
2. Resolve the mode contract deliberately: public `core` maps to runtime
   `explain`, public software-design and coding-guidance `build` maps to
   runtime `build`, and private admin Code Thinking maps to runtime `code`.
3. Add an admin entitlement and project picker, then a read-only project
   briefing and task planner.
4. Add patch generation in a bounded workspace, a visible diff, and checks
   selected from project instructions.
5. Evaluate on representative BIT work: bug repair, small feature, cross-app
   dependency change, and an ambiguous request. Record correctness, regressions,
   fabricated repository claims, and whether the proposed tests are useful.

## Ready-to-expand signal

Code Thinking 0.1 is ready to grow when it reliably grounds claims in the
selected repository, produces reviewable changes, reports failed or unavailable
checks honestly, and respects the admin/project boundary. Broader autonomous
work should follow those results rather than precede them.

## Implementation status

- Guest challenge and chat API URLs now derive from the mounted `ai` page path,
  so root-mounted and `/ai` deployments resolve correctly. Invalid challenge
  responses produce a useful status message.
- The PHP mode catalog maps `core` → `explain`, public Build → `build`, and
  keeps the admin Code Thinking API on `code`. Build is a text-only public mode
  for software brainstorming, design, and coding guidance; it receives no
  repository context and cannot change files or generate media.
- Added a separate Beyond-admin Code Thinking page/API. Its project roots come
  from `JAGUAR_CODE_PROJECTS_JSON` (or the current Git checkout by default), and
  requested files are resolved and constrained inside the selected root.
- Read, plan, and patch actions send scoped project context to Jaguar. Patch
  output is a diff for review; the API does not write repository files.
- Added per-project private notes tied to Git revisions, stale-note review and
  removal, and explicitly configured local check commands.
- Evaluation cases and current evidence are recorded in
  `ai/CODE-THINKING-EVALUATION.md`. Model quality remains unmeasured because
  this workspace has no configured Jaguar runtime URL.
