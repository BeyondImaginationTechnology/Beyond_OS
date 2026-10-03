CREATE TABLE IF NOT EXISTS jaguar_draw_images (
  idempotency_key TEXT PRIMARY KEY,
  receipt_id TEXT NOT NULL UNIQUE,
  mime_type TEXT NOT NULL,
  sha256 TEXT NOT NULL,
  byte_size INTEGER NOT NULL,
  created_at INTEGER NOT NULL,
  expires_at INTEGER NOT NULL,
  FOREIGN KEY (idempotency_key) REFERENCES jaguar_draw_holds(idempotency_key) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_jaguar_draw_image_expiry ON jaguar_draw_images(expires_at);
