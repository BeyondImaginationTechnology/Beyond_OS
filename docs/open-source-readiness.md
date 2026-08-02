# Open Source Readiness

Use this checklist before publishing Beyond OS to a public repository.

## Required Before Public Release

- Confirm the repository owner approves the selected license in `LICENSE`.
- Run a full secret scan across all tracked files.
- Review every SQL dump for production data, user emails, hashes, sessions,
  private URLs, and payment records.
- Review JSON data files for copyrighted media links, private customer data, and
  production-only service URLs.
- Verify `BEYOND_VAR_PATH` examples point outside the public web root.
- Confirm `config/live.php`, `.env`, uploads, logs, databases, generated media,
  dependencies, and production archives are ignored and untracked.
- Replace private deployment language with reusable setup instructions.
- Publish a security contact or private vulnerability-reporting path.

## Recommended Cleanup

- Add automated PHP linting in CI once the target PHP version is finalized.
- Add a minimal test database seed for contributors.
- Split sample data from production catalog data where licensing is unclear.
- Add architecture notes for `config/`, `includes/`, `server/`, and each
  `beyond-*` product folder.
- Mark experimental or owner-only product areas in their local README files.
- Document which Apple projects are maintained and how to build them.

## Release Notes

This repository now includes a public README, MIT license, contribution guide,
security policy, sample runtime config, and private config ignore rule. That is
the foundation, not the finish line: the most important remaining work is a
human review of data and third-party content licensing.
