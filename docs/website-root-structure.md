# Website root structure

The repository root is also the production document root. Keep public URLs and
the current StartCP Git deployment layout stable by leaving route folders and
top-level PHP entry points in place. Put supporting material in its owning
project or in a protected operations folder.

## Root layout

| Area | Examples | Rule |
| --- | --- | --- |
| Public entry points | `index.php`, `about.php`, `contact.php`, `service-worker.js` | Keep at the root because the site and browser clients request these paths directly. |
| Public product routes | `academy/`, `api/`, `app-store/`, `beyond-id/`, `beyond-math/`, `beyond-tv/`, `dailybreath/`, `os/`, `u/` | Keep route names stable; move internals only after checking URL and code references. |
| Shared web code | `assets/`, `includes/`, `config/`, `server/` | Keep implementation grouped by responsibility; `config/` contains production wiring and stays protected. |
| Product source projects | `*Android/`, `*Apple/`, `beyond-os-desktop/` | Keep native build trees together by project. Their case-sensitive folder names are part of the source and store workflows. |
| Operations and data | `.git/`, `tools/`, `sql/`, `docs/`, `exports/` | Keep operational material grouped. `.git/` supports StartCP deployment; `tools/`, `sql/`, and `docs/` are protected/excluded from production sync. |
| User-facing profile routes | `u/` | Keep in the web root; this is a live Beyond ID route. |
| Temporary generated media | `exports/` | Keep generated output only; retain files for at most 24 hours. Store generators and source assets under `tools/`. |

## Supporting-file locations

- `docs/mobile/` contains mobile rollout instructions.
- `docs/handoffs/` contains internal project handoff notes.
- `DailyBreathAndroid/scripts/` contains Android project helpers.
- `tools/buildroot/` contains Buildroot support files.
- `tools/certificates/` contains certificate request artifacts that do not
  belong among public routes.
- `tools/daily-space-carousel/` contains the carousel generator and source
  backgrounds; generated PNGs go to `exports/daily-space-4-of-55/`.
- Personal document generators and personal data belong outside the web
  repository. Locally they live in `Beyond_OS_private/resume-builder/`; the
  live generator is kept under `/var/private-builders/`, outside the public
  document root.

## Deployment constraints

The production deploy syncs application files into the existing web root and
preserves excluded production state. Do not rename or relocate public route
folders, `config/`, `u/`, `exports/`, or `.git/` as part of general cleanup.
The deploy script excludes `tools/`, `docs/`, `sql/`, `exports/`, native app
trees, and production configuration. Files moved into excluded directories
therefore need a deliberate live-side move when the repository is organized.
