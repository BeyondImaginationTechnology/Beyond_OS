# Beyond OS

Beyond OS is a PHP-based web platform for the Beyond ecosystem: learning academies,
certificates, Beyond ID profiles, media experiences, marketplaces, and internal
operations tools.

Current release: **Beyond OS 2.4**

## What's New in 2.4

- A new public **What's New** destination in the main navigation brings current
  app and project updates together in one release view.
- **DailyBreath Web 1.2** is now an installable PWA with narrated Scripture,
  encrypted reflection journaling, weekly challenges, activity history, and
  recovery support.
- **DailyBreath for Apple** adds richer offline content, Bible narration and
  search, widgets, an App Clip, private history, and optional encrypted iCloud
  sync.
- **Beyond Tattoo Apple 1.2** adds asset-backed daily stencils, real downloads,
  healing milestones, bit$ rewards, and a location-aware Canadian studio
  directory.
- **Beyond Studio + Remotion** adds a local trusted-project preview and H.264
  rendering bridge, while the **Beyond French African Expansion** project adds
  reusable vertical and feed campaign compositions.
- Marketplace and seller work continues with connected listings, checkout,
  fulfillment, and creator tooling.

The user-facing summary lives in `release-notes.php`. Detailed implementation
and operating notes remain in each app or project's own `README.md`.

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

Original Beyond OS source code and associated technical documentation are
released under the MIT License. The MIT License does not cover Beyond branding,
images, audio, video, voices, fonts, datasets, generated content, user content,
or third-party material unless an asset-specific notice expressly says so.

See `LICENSE` for the source-code license and `CONTENT_RIGHTS.md` for media,
branding, generated-content, public-domain, and third-party rights guidance.

French audio regeneration is documented in
[`docs/french-assets-commercial-regeneration.md`](docs/french-assets-commercial-regeneration.md).
The batch requires an explicit commercial-license acknowledgement and keeps
provider credentials out of the repository.
