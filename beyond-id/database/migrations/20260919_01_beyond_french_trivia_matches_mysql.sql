-- Authoritative two-player trivia matches for Beyond French.
CREATE TABLE IF NOT EXISTS beyond_french_trivia_matches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  join_code CHAR(6) NOT NULL UNIQUE,
  host_user_id BIGINT UNSIGNED NOT NULL,
  guest_user_id BIGINT UNSIGNED NULL,
  status ENUM('waiting','active','completed','abandoned') NOT NULL DEFAULT 'waiting',
  question_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
  host_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  guest_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  active_user_id BIGINT UNSIGNED NULL,
  state_version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_beyond_french_trivia_matches_guest (guest_user_id),
  KEY idx_beyond_french_trivia_matches_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
