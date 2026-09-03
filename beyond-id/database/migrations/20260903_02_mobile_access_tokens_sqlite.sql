CREATE TABLE IF NOT EXISTS mobile_access_tokens (
  jti TEXT NOT NULL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  audience TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  revoked_at TEXT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_mobile_access_tokens_user ON mobile_access_tokens(user_id, revoked_at);
CREATE INDEX IF NOT EXISTS idx_mobile_access_tokens_expiry ON mobile_access_tokens(expires_at);
