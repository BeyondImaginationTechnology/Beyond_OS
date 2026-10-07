CREATE TABLE IF NOT EXISTS jaguar_conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  language VARCHAR(8) NOT NULL DEFAULT 'en',
  created_at BIGINT NOT NULL,
  updated_at BIGINT NOT NULL,
  KEY idx_jaguar_conversations_user_updated (user_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jaguar_conversation_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  role VARCHAR(16) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  mode VARCHAR(24) NOT NULL DEFAULT 'core',
  created_at BIGINT NOT NULL,
  KEY idx_jaguar_conversation_messages_conversation (conversation_id, id),
  CONSTRAINT fk_jaguar_conversation_messages_conversation FOREIGN KEY (conversation_id) REFERENCES jaguar_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
