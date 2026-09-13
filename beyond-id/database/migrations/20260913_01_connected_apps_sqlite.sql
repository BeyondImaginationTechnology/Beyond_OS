CREATE TABLE IF NOT EXISTS connected_apps (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  app_slug TEXT NOT NULL,
  permissions_json TEXT,
  connected_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at TEXT,
  revoked_at TEXT,
  UNIQUE (user_id, app_slug),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_connected_apps_user ON connected_apps(user_id, revoked_at);
