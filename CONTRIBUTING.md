# Contributing to Beyond OS

Thanks for helping improve Beyond OS. This project is moving from a private
deployment package toward a contributor-friendly open-source codebase, so clear
documentation and small, well-scoped changes are especially valuable.

## Development Guidelines

- Keep production credentials, customer data, uploads, logs, databases, and
  generated media out of Git.
- Use `config/live.example.php` as the reference for local runtime config.
- Keep `BEYOND_VAR_PATH` outside the public web root.
- Prefer small pull requests with a focused behavior change.
- Match the existing PHP style unless a file already has a more specific local
  pattern.
- Include setup or deployment documentation updates when behavior depends on new
  configuration.

## Local Checks

Run PHP lint on changed PHP files before opening a pull request:

```sh
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

If you touch SQL migrations, test them against a disposable local database
instead of a production clone.

## Pull Request Checklist

- The change is scoped to one feature, fix, or documentation improvement.
- New runtime configuration is documented in `config/live.example.php`.
- No secrets, private data, generated files, logs, databases, or uploads are
  committed.
- PHP files touched by the change pass `php -l`.
- Security-sensitive changes mention the threat model or relevant risk in the
  pull request description.

## Reporting Security Issues

Please follow `SECURITY.md` instead of filing public vulnerability reports.
