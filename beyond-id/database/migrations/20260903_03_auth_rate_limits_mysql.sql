CREATE TABLE IF NOT EXISTS auth_rate_limits (
    bucket_key CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at BIGINT UNSIGNED NOT NULL,
    blocked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at BIGINT UNSIGNED NOT NULL,
    KEY idx_auth_rate_limits_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
