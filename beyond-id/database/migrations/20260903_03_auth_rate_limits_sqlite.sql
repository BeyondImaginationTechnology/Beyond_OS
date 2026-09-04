CREATE TABLE IF NOT EXISTS auth_rate_limits (
    bucket_key TEXT PRIMARY KEY,
    attempts INTEGER NOT NULL DEFAULT 0,
    window_started_at INTEGER NOT NULL,
    blocked_until INTEGER NOT NULL DEFAULT 0,
    updated_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_auth_rate_limits_updated
    ON auth_rate_limits(updated_at);
