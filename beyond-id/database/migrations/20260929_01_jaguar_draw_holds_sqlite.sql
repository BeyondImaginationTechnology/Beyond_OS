CREATE TABLE IF NOT EXISTS jaguar_draw_holds (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  wallet_id INTEGER NOT NULL,
  amount NUMERIC NOT NULL,
  idempotency_key TEXT NOT NULL UNIQUE,
  status TEXT NOT NULL DEFAULT 'held',
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  FOREIGN KEY (wallet_id) REFERENCES beyond_wallets (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_jaguar_draw_hold_expiry ON jaguar_draw_holds(wallet_id,status,created_at);
