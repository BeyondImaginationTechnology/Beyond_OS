CREATE TABLE IF NOT EXISTS card_program_customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    issuer TEXT NOT NULL DEFAULT 'peoples_trust',
    processor TEXT NOT NULL DEFAULT 'marqeta',
    provider_user_token TEXT NOT NULL UNIQUE,
    provider_status TEXT NOT NULL DEFAULT 'PENDING',
    consent_version TEXT NOT NULL,
    consented_at TEXT NOT NULL,
    last_synced_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment_cards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    card_program_customer_id INTEGER NOT NULL,
    processor TEXT NOT NULL DEFAULT 'marqeta',
    provider_card_token TEXT NOT NULL UNIQUE,
    card_product_token TEXT NOT NULL,
    state TEXT NOT NULL DEFAULT 'UNACTIVATED',
    fulfillment_status TEXT,
    last_four TEXT,
    expiration_time TEXT,
    currency TEXT NOT NULL DEFAULT 'CAD',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(card_program_customer_id) REFERENCES card_program_customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS card_provider_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    processor TEXT NOT NULL,
    event_key TEXT NOT NULL,
    event_type TEXT NOT NULL,
    payload_json TEXT NOT NULL,
    received_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TEXT,
    UNIQUE(processor, event_key)
);

CREATE INDEX IF NOT EXISTS idx_payment_cards_user ON payment_cards(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_card_provider_events_received ON card_provider_events(received_at);
