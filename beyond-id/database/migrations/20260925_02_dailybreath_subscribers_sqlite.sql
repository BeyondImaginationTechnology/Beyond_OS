CREATE TABLE IF NOT EXISTS dailybreath_subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    email TEXT NOT NULL UNIQUE,
    source TEXT NOT NULL DEFAULT 'dailybreath_web',
    status TEXT NOT NULL DEFAULT 'active',
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
