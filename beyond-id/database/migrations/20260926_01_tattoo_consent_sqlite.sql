CREATE TABLE IF NOT EXISTS tattoo_consent_links (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  token_hash TEXT NOT NULL UNIQUE,
  owner_user_id INTEGER NOT NULL,
  studio_name TEXT NOT NULL,
  artist_name TEXT NOT NULL,
  privacy_contact_name TEXT NOT NULL,
  privacy_contact_email TEXT NOT NULL,
  procedure_description TEXT NOT NULL,
  placement TEXT NOT NULL,
  appointment_date TEXT NULL,
  expires_at TEXT NOT NULL,
  used_at TEXT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_tattoo_consent_links_owner ON tattoo_consent_links(owner_user_id,created_at);

CREATE TABLE IF NOT EXISTS tattoo_consent_records (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  link_id INTEGER NOT NULL UNIQUE,
  owner_user_id INTEGER NOT NULL,
  client_name TEXT NOT NULL,
  client_signature TEXT NOT NULL,
  attestations_json TEXT NOT NULL,
  consent_version TEXT NOT NULL,
  signed_at TEXT NOT NULL,
  record_sha256 TEXT NOT NULL,
  FOREIGN KEY (link_id) REFERENCES tattoo_consent_links(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_tattoo_consent_records_owner ON tattoo_consent_records(owner_user_id,signed_at);
