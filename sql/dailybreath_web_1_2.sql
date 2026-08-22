-- DailyBreath web 1.2: persistent progress for bundled/recovery challenges.
CREATE TABLE IF NOT EXISTS dailybreath_challenge_progress (
  user_id BIGINT UNSIGNED NOT NULL,
  challenge_key VARCHAR(160) NOT NULL,
  completed_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, challenge_key),
  KEY idx_dailybreath_challenge_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
