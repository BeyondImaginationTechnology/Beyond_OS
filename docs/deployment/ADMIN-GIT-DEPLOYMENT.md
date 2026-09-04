# Beyond OS admin Git deployment

The Settings page queues deployments without executing operating-system commands from a web request. A CLI-only cron worker claims the request under an exclusive non-blocking lock, updates `main` with a fast-forward-only merge, copies production files, and writes a protected status record under `var/`.

## Server paths

- Repository: `/home/sites/42b/a/a9823859bb/beyondimagination.co.technology/beyond`
- Public web root: `/home/sites/42b/a/a9823859bb/beyondimagination.co.technology/public_html`
- Private queue and status: `$BEYOND_VAR_PATH/deployments/`
- Worker: `server/cron/deploy-worker.php`
- Deployment script: `tools/deploy-production.sh`

## Cron

Run the worker once per minute with the hosting account's PHP CLI binary:

```cron
* * * * * /usr/bin/php81 /home/sites/42b/a/a9823859bb/beyondimagination.co.technology/beyond/server/cron/deploy-worker.php >/dev/null 2>&1
```

StartCP identifies `/usr/bin/php81` as its PHP 8.1 CLI interpreter. The worker exits when no job is queued, and `flock()` prevents overlapping deployments.

## StartCP deployment shortcut

The existing StartCP repository can use this Deployment Script:

```sh
bash tools/deploy-production.sh
```

The script refuses detached or non-`main` branches, refuses all local changes and untracked files, fetches and fast-forwards from `origin/main`, and deploys with `rsync`. It intentionally does not use `--delete`. Both StartCP-triggered and cron-triggered deployments write the protected deployment status used by the admin card.

## Preserved content

The deployment excludes `.git`, `.github`, `.cache`, `var`, `config/live.php`, documentation, SQL, tools, exports, mobile projects, pipeline files, signing requests, and repository documentation. Production runtime configuration remains in the private sibling `var/` directory.
