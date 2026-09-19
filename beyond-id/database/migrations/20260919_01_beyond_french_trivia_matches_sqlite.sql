-- Authoritative two-player trivia matches for Beyond French.
CREATE TABLE IF NOT EXISTS beyond_french_trivia_matches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  join_code TEXT NOT NULL UNIQUE,
  host_user_id INTEGER NOT NULL,
  guest_user_id INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'waiting' CHECK(status IN ('waiting','active','completed','abandoned')),
  question_index INTEGER NOT NULL DEFAULT 0,
  host_score INTEGER NOT NULL DEFAULT 0,
  guest_score INTEGER NOT NULL DEFAULT 0,
  active_user_id INTEGER NULL,
  state_version INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_beyond_french_trivia_matches_guest ON beyond_french_trivia_matches(guest_user_id);
CREATE INDEX IF NOT EXISTS idx_beyond_french_trivia_matches_status ON beyond_french_trivia_matches(status, updated_at);
