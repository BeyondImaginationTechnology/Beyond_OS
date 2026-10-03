CREATE TABLE IF NOT EXISTS jaguar_draw_holds (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  wallet_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  idempotency_key VARCHAR(120) NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'held',
  created_at BIGINT NOT NULL,
  updated_at BIGINT NOT NULL,
  UNIQUE KEY uniq_jaguar_draw_hold_key (idempotency_key),
  KEY idx_jaguar_draw_hold_expiry (wallet_id,status,created_at),
  CONSTRAINT fk_jaguar_draw_hold_wallet FOREIGN KEY (wallet_id) REFERENCES beyond_wallets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
