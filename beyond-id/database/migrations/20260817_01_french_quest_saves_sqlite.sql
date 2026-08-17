-- French Quest cloud save snapshots — SQLite 3
CREATE TABLE IF NOT EXISTS french_quest_saves (
  user_id INTEGER NOT NULL PRIMARY KEY,
  completed_challenge_ids TEXT NOT NULL,
  xp INTEGER NOT NULL DEFAULT 0,
  hearts INTEGER NOT NULL DEFAULT 5,
  streak INTEGER NOT NULL DEFAULT 0,
  theme TEXT NOT NULL DEFAULT 'riviera',
  schema_version INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_french_quest_updated ON french_quest_saves(updated_at);
