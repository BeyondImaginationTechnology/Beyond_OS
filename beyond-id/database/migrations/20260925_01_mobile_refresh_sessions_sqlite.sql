ALTER TABLE mobile_access_tokens ADD COLUMN family_id TEXT NULL;
CREATE INDEX IF NOT EXISTS idx_mobile_access_tokens_family ON mobile_access_tokens(family_id, revoked_at);

CREATE TABLE IF NOT EXISTS mobile_token_families (
  family_id TEXT NOT NULL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  audience TEXT NOT NULL,
  app_slug TEXT NOT NULL,
  device_id TEXT NOT NULL,
  device_name TEXT NOT NULL,
  scopes_json TEXT NOT NULL,
  created_at TEXT NOT NULL,
  last_used_at TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  revoked_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_mobile_families_user ON mobile_token_families(user_id, revoked_at);
CREATE INDEX IF NOT EXISTS idx_mobile_families_app_device ON mobile_token_families(user_id, app_slug, device_id, revoked_at);
CREATE INDEX IF NOT EXISTS idx_mobile_families_expiry ON mobile_token_families(expires_at);

CREATE TABLE IF NOT EXISTS mobile_refresh_tokens (
  token_hash TEXT NOT NULL PRIMARY KEY,
  family_id TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  used_at TEXT,
  revoked_at TEXT,
  replaced_by_hash TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY (family_id) REFERENCES mobile_token_families(family_id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_mobile_refresh_family ON mobile_refresh_tokens(family_id, revoked_at);
CREATE INDEX IF NOT EXISTS idx_mobile_refresh_expiry ON mobile_refresh_tokens(expires_at);
