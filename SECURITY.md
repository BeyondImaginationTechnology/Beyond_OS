# Security Policy

## Supported Versions

This repository is preparing its first open-source release. Security fixes should
target the current main branch unless a maintained release branch is explicitly
documented.

## Reporting a Vulnerability

Please do not disclose vulnerabilities in public issues, pull requests, or
discussions.

Until a dedicated security contact is published, report suspected
vulnerabilities privately to the project maintainers. Include:

- Affected path or component.
- Steps to reproduce.
- Expected and actual behavior.
- Impact assessment.
- Any suggested fix, if you have one.

The maintainers should acknowledge valid reports, investigate privately, prepare
a fix, and coordinate disclosure timing with the reporter.

## Security Expectations

- Store runtime configuration in `$BEYOND_VAR_PATH/config/live.php`, outside the
  public web root.
- Never commit real database credentials, SMTP credentials, API keys, OAuth
  secrets, Stripe secrets, JWT secrets, private keys, user uploads, production
  databases, logs, or generated private media.
- Rotate credentials before public releases and after any suspected exposure.
- Review SQL dumps before publication to ensure they contain schema or seed data
  only.
- Keep production-only admin routes and cron entry points behind hosting-level
  access controls.
