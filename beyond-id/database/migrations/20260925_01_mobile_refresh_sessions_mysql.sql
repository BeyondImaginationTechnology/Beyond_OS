ALTER TABLE mobile_access_tokens
  ADD COLUMN family_id CHAR(64) NULL,
  ADD KEY idx_mobile_access_tokens_family (family_id, revoked_at);

CREATE TABLE IF NOT EXISTS mobile_token_families (
  family_id CHAR(64) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  audience VARCHAR(64) NOT NULL,
  app_slug VARCHAR(80) NOT NULL,
  device_id VARCHAR(128) NOT NULL,
  device_name VARCHAR(120) NOT NULL,
  scopes_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  last_used_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  KEY idx_mobile_families_user (user_id, revoked_at),
  KEY idx_mobile_families_app_device (user_id, app_slug, device_id, revoked_at),
  KEY idx_mobile_families_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_refresh_tokens (
  token_hash CHAR(64) NOT NULL PRIMARY KEY,
  family_id CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  replaced_by_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_mobile_refresh_family (family_id, revoked_at),
  KEY idx_mobile_refresh_expiry (expires_at),
  CONSTRAINT fk_mobile_refresh_family FOREIGN KEY (family_id) REFERENCES mobile_token_families(family_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
