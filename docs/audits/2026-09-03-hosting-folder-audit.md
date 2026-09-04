# Hosting folder audit — 2026-09-03

Scope: local `Beyond_OS` checkout and the StartCP file manager root for
`beyondimagination.co.technology`.

## Protected production paths

- `var/` is live private runtime storage and must not be deleted, renamed, or
  replaced during deployment.
- Observed server contents include `config/`, SQLite databases, uploads, logs,
  analytics, caches, generated Daily Studio data, and TV runtime data.
- The code and deployment documentation consistently expect the live runtime
  directory outside the public web root through `BEYOND_VAR_PATH`.
- `beyond/` mirrors the local source checkout and contains the application
  source plus repository/development files. It was left unchanged.
- `public_html/` is the public web root. It was left unchanged.

## Cleanup performed

- Server folder `beyond-os-var/` contained only an empty
  `legacy-public-data/` directory.
- It was reversibly renamed to
  `ZZ_REVIEW_beyond-os-var_empty_20260903/` rather than deleted.
- The production `var/` directory was not modified.

## Other root folders reviewed

- `legacy/` contains older copies of `beyond-baby-names`, `beyond-french`,
  `beyond-market`, and `beyond-tv`. It is a cleanup candidate, but was not
  changed because content equality and rollback dependencies were not proven.
- `beyond-os-node/`, `live1/`, and `Sandbox/` were not changed. The file manager
  did not expose enough evidence to prove that they are unused.
- `logs/`, `aws/`, `.github/`, and `public_ftp/` were not changed.

## Why some server files resist deletion

Two `.nfs*` entries were visible inside `var/`. These are typically temporary
NFS placeholders for deleted files that are still open by a process. They
cannot normally be removed until the process releases the file handle. Do not
force-delete them or rename the production `var/` directory; restart or stop
the owning application process, or ask the host to identify the open handle.

## Local checkout notes

- The local repository has no tracked `var/` directory; `.gitignore` excludes
  `/var/`, databases, logs, uploads, and generated runtime output.
- Existing uncommitted user changes were preserved in
  `DailyBreathApple/Sources/BreatheView.swift`, `app-store/index.php`, and
  `includes/ecosystem.php`.
- No local folders were deleted or renamed because no exact redundant
  top-level directory was proven safe to remove.

## Protected SMTP configuration update

The production `var/config/live.php` SMTP section now accepts these environment
variable overrides while preserving the previously stored values as fallbacks:

- `SMTP_HOST`
- `SMTP_PORT` (cast to an integer)
- `SMTP_USERNAME` (mapped to both supported username keys)
- `SMTP_PASSWORD`
- `SMTP_FROM`
- `SMTP_USE_SSL` (`true` selects implicit SSL; `false` selects TLS)

No credential values are recorded in this audit. The live site was reloaded
successfully after the configuration file was saved.
