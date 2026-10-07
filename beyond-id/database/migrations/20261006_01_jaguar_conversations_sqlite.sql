CREATE TABLE IF NOT EXISTS jaguar_conversations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  language TEXT NOT NULL DEFAULT 'en',
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_jaguar_conversations_user_updated ON jaguar_conversations(user_id, updated_at DESC);

CREATE TABLE IF NOT EXISTS jaguar_conversation_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  conversation_id INTEGER NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('user', 'assistant')),
  content TEXT NOT NULL,
  mode TEXT NOT NULL DEFAULT 'core',
  created_at INTEGER NOT NULL,
  FOREIGN KEY (conversation_id) REFERENCES jaguar_conversations(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_jaguar_conversation_messages_conversation ON jaguar_conversation_messages(conversation_id, id);
