# Couple Mode database

The Couple Mode API uses SQLite through PDO and creates its schema idempotently.
By default the database is stored outside the public web root under
`$BEYOND_VAR_PATH/beyond-baby-names/couples.sqlite`. Override it
with `BEYOND_BABY_NAMES_DB` in production.

## Privacy model

- A couple space accepts exactly two members.
- Invite codes contain 64 random bits and only their SHA-256 hashes are stored.
- Member bearer tokens contain 256 random bits and only their hashes are stored.
- Each member may fetch their own decisions, never their partner's private list.
- A name is disclosed as a match only after both members independently love it.
- Couple spaces expire after 180 days unless their lifecycle is extended later.

## JSON API

`POST /beyond-baby-names/api/couples.php`

- `{"action":"create","displayName":"Alex"}`
- `{"action":"join","inviteCode":"BBN-....","displayName":"Sam"}`
- `{"action":"savePick","name":"Luna","decision":"love"}` with a bearer token
- `{"action":"state"}` with a bearer token

Production deployment should add edge-level rate limiting to `join` attempts,
TLS-only transport, database backups, and a scheduled expiry cleanup.
