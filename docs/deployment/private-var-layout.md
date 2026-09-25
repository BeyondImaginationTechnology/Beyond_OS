# Private `var` layout

The private `var` directory is beside `www`, outside the web root. `BEYOND_VAR_PATH`
can override its location. `config/live.php` remains in place and must never be
committed to Git.

| Path | Purpose |
| --- | --- |
| `config/` | Live configuration and credentials |
| `db/` | App SQLite databases |
| `data/daily-studio/` | Studio content and published channel snapshots |
| `data/beyond-tattoo/` | Stencil settings |
| `data/beyond-tv/` | Channel progress |
| `data/beyond-ai/` | AI usage records |
| `data/` | Other private app data |
| `cache/` | Regenerable caches |
| `uploads/` | User and generated media |
| `tmp/` | Temporary work and the layout lock |
| `logs/`, `deployments/`, `analytics/` | Operational records |
| `backups/` | Private snapshots made before storage moves |

After deploying the compatible code, run
`php tools/migrate-private-var.php --apply` once from the hosting CLI. It waits
for requests using private storage to finish, checkpoints SQLite WAL files,
copies the files being moved into a dated `backups/` directory, and moves the
known databases and data files. A current hosting webspace snapshot is also
recommended. The script can be rerun if an individual move
fails. The compatible code keeps reading the legacy path until each file moves.

The migration intentionally leaves `beyond-health.sqlite`, `android_keys/`,
and any unrecognized files where they are until their external consumers are
identified. It also leaves empty legacy directories for a later cleanup.
