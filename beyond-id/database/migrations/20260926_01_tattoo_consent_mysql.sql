CREATE TABLE IF NOT EXISTS tattoo_consent_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token_hash CHAR(64) NOT NULL UNIQUE,
  owner_user_id INT NOT NULL,
  studio_name VARCHAR(200) NOT NULL,
  artist_name VARCHAR(200) NOT NULL,
  privacy_contact_name VARCHAR(200) NOT NULL,
  privacy_contact_email VARCHAR(255) NOT NULL,
  procedure_description VARCHAR(500) NOT NULL,
  placement VARCHAR(160) NOT NULL,
  appointment_date DATE NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tattoo_consent_links_owner(owner_user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tattoo_consent_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id BIGINT UNSIGNED NOT NULL UNIQUE,
  owner_user_id INT NOT NULL,
  client_name VARCHAR(200) NOT NULL,
  client_signature MEDIUMTEXT NOT NULL,
  attestations_json TEXT NOT NULL,
  consent_version VARCHAR(40) NOT NULL,
  signed_at DATETIME NOT NULL,
  record_sha256 CHAR(64) NOT NULL,
  INDEX idx_tattoo_consent_records_owner(owner_user_id,signed_at),
  CONSTRAINT fk_tattoo_consent_record_link FOREIGN KEY(link_id) REFERENCES tattoo_consent_links(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
