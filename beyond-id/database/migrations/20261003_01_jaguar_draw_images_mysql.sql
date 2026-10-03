CREATE TABLE IF NOT EXISTS jaguar_draw_images (
  idempotency_key VARCHAR(120) NOT NULL PRIMARY KEY,
  receipt_id CHAR(16) NOT NULL UNIQUE,
  mime_type VARCHAR(24) NOT NULL,
  sha256 CHAR(64) NOT NULL,
  byte_size INT UNSIGNED NOT NULL,
  created_at BIGINT NOT NULL,
  expires_at BIGINT NOT NULL,
  KEY idx_jaguar_draw_image_expiry (expires_at),
  CONSTRAINT fk_jaguar_draw_image_hold FOREIGN KEY (idempotency_key) REFERENCES jaguar_draw_holds (idempotency_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
