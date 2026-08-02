# Beyond OS

Beyond OS is a PHP-based web platform for the Beyond ecosystem: learning academies,
certificates, Beyond ID profiles, media experiences, marketplaces, and internal
operations tools.

This repository contains the public application source, SQL migrations, docs,
and Apple app project folders used across the Beyond product family. Runtime
data, credentials, uploads, logs, generated media, local databases, dependencies,
and production-only private configuration are intentionally excluded from Git.

## Project Status

This codebase is being prepared for open-source collaboration. The current
focus is making local setup, deployment, security expectations, and contribution
paths clear without exposing production secrets or user data.

## What's Included

- `index.php`, `about.php`, `contact.php`, and other top-level PHP pages for the
  public Beyond OS web root.
- `beyond-*` product folders for Beyond TV, Beyond French, Beyond Math, Beyond
  Market, Beyond ID, and other experiences.
- `academy/` and `dailybreath/` learning and certificate workflows.
- `assets/`, `includes/`, and `config/` shared frontend, layout, platform, and
  configuration helpers.
- `server/` admin, cron, helpers, and backend classes.
- `sql/` database migrations and schema snapshots.
- `docs/` deployment notes, patch notes, and security hardening notes.
- `Beyond*Apple/` Swift/Xcode companion app projects.

## Requirements

- PHP 8.1 or newer.
- MySQL or MariaDB for the main Beyond OS database.
- A web server capable of serving PHP, such as Apache with `.htaccess` support.
- Optional SMTP, Stripe, Google Maps, YouTube, and OpenAI credentials for
  features that integrate with those services.

## Local Setup

1. Clone the repository.
2. Create a private runtime directory outside the public web root.
3. Copy `config/live.example.php` to `var/config/live.php` inside that private
   runtime directory.
4. Set `BEYOND_VAR_PATH` to the absolute path of the private runtime directory.
5. Fill in local database and service settings in `var/config/live.php`.
6. Import the SQL migrations needed for the feature area you are working on.
7. Serve the repository root with PHP.

Example using PHP's built-in server for local development:

```sh
export BEYOND_VAR_PATH="/absolute/path/to/beyond-var"
php -S localhost:8080
```

The built-in server is useful for quick development, but production deployments
should use a real PHP-capable web server and keep `BEYOND_VAR_PATH` outside the
served document root.

## Configuration

Runtime configuration is loaded from:

```text
$BEYOND_VAR_PATH/config/live.php
```

That file must return a PHP array. Use `config/live.example.php` as the template.
Do not commit real credentials, production URLs, customer data, user uploads, or
generated private assets.

## Security

Please do not open public issues for suspected vulnerabilities. Follow
`SECURITY.md` for responsible disclosure details and keep exploit details private
until there is a coordinated fix.

Before publishing a fork or release, run a fresh secret scan, review SQL dumps
for user data, and verify that private runtime files are not tracked.

## Contributing

Contributions are welcome. Start with `CONTRIBUTING.md` for setup expectations,
coding guidelines, and pull request checks.

Good first areas for help include documentation, local setup improvements, PHP
lint fixes, test coverage, accessibility passes, and separating product-specific
configuration from reusable platform code.

## License

Beyond OS is released under the MIT License. See `LICENSE` for details.
