CREATE TABLE IF NOT EXISTS beyond_cash_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    currency TEXT NOT NULL,
    available_balance NUMERIC NOT NULL DEFAULT 0,
    pending_balance NUMERIC NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending_provider',
    provider TEXT,
    provider_account_reference TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, currency),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS beyond_cash_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cash_account_id INTEGER NOT NULL,
    direction TEXT NOT NULL,
    transaction_type TEXT NOT NULL,
    amount NUMERIC NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    provider TEXT,
    provider_reference TEXT,
    idempotency_key TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT,
    FOREIGN KEY(cash_account_id) REFERENCES beyond_cash_accounts(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_beyond_cash_user ON beyond_cash_accounts(user_id, currency);
CREATE INDEX IF NOT EXISTS idx_beyond_cash_activity ON beyond_cash_transactions(cash_account_id, created_at);
