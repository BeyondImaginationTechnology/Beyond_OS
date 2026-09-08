PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;

CREATE TABLE IF NOT EXISTS couple_spaces (
    id TEXT PRIMARY KEY,
    invite_code_hash TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'closed')),
    created_at TEXT NOT NULL,
    expires_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS couple_members (
    id TEXT PRIMARY KEY,
    couple_id TEXT NOT NULL REFERENCES couple_spaces(id) ON DELETE CASCADE,
    role TEXT NOT NULL CHECK (role IN ('creator', 'partner')),
    display_name TEXT NOT NULL DEFAULT '',
    auth_token_hash TEXT NOT NULL UNIQUE,
    joined_at TEXT NOT NULL,
    UNIQUE (couple_id, role)
);

CREATE TABLE IF NOT EXISTS couple_picks (
    couple_id TEXT NOT NULL REFERENCES couple_spaces(id) ON DELETE CASCADE,
    member_id TEXT NOT NULL REFERENCES couple_members(id) ON DELETE CASCADE,
    name_key TEXT NOT NULL,
    name_label TEXT NOT NULL,
    decision TEXT NOT NULL CHECK (decision IN ('pass', 'maybe', 'love')),
    updated_at TEXT NOT NULL,
    PRIMARY KEY (member_id, name_key)
);

CREATE INDEX IF NOT EXISTS couple_picks_match_lookup
    ON couple_picks (couple_id, name_key, decision);

CREATE INDEX IF NOT EXISTS couple_spaces_expiry
    ON couple_spaces (expires_at);

CREATE TABLE IF NOT EXISTS couple_join_limits (
    client_hash TEXT NOT NULL,
    window_started INTEGER NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (client_hash, window_started)
);
