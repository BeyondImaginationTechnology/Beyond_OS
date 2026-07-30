CREATE TABLE IF NOT EXISTS crypto_watch_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    network TEXT NOT NULL,
    label TEXT NOT NULL DEFAULT '',
    public_address TEXT NOT NULL,
    address_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, network, address_hash),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_crypto_watch_user ON crypto_watch_accounts(user_id, created_at);
