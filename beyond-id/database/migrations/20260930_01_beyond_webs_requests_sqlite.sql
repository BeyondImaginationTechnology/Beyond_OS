CREATE TABLE IF NOT EXISTS beyond_webs_requests (
  user_id INTEGER NOT NULL PRIMARY KEY,
  request_id TEXT NOT NULL UNIQUE,
  flavour TEXT NOT NULL,
  plan TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'requested',
  requested_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
