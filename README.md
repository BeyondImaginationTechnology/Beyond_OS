# Beyond OS 2.3 Academy & Certificates

This is a merge-deployment package for the existing Beyond OS installation. Release 2.3 adds a shared learner dashboard, three certificate pathways, assessments, verifiable Beyond-issued certificates, and Beyond ID achievement badges. It is not a database or `var/` replacement.

## Deploy

1. Back up the current web root and private `var/` directory.
2. Merge the package into the web root; do not delete production-only folders.
3. Keep `BEYOND_VAR_PATH` outside the public web root and writable by PHP.
4. For MySQL, optionally set `BEYOND_PHPMYADMIN_URL` to an HTTPS admin URL.
5. Confirm every newsletter and beta-signup form sends the shared `_csrf` token.
6. Test login, healing uploads, admin database pages, SQL presets, app launches, System Health, and Daily Studio.
7. Rotate beta credentials before the alpha release.

The clean ZIP excludes rollback copies, runtime history, generated uploads, nested ZIP files, logs, databases, dependencies, and protected configuration.
