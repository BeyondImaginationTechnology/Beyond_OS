CREATE TABLE IF NOT EXISTS mobile_authorization_codes (
  code_hash TEXT NOT NULL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  audience TEXT NOT NULL,
  code_challenge TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  used_at TEXT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_mobile_authorization_codes_expiry ON mobile_authorization_codes(expires_at);
