CREATE TABLE IF NOT EXISTS dailybreath_daily_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  publish_date DATE NOT NULL,
  tradition VARCHAR(16) NOT NULL,
  locale VARCHAR(5) NOT NULL,
  passage TEXT NOT NULL,
  reference VARCHAR(255) NOT NULL,
  reflection TEXT NOT NULL,
  reader_book VARCHAR(120) NOT NULL DEFAULT '',
  reader_chapter INT UNSIGNED NOT NULL DEFAULT 1,
  reader_verse INT UNSIGNED NOT NULL DEFAULT 1,
  theme VARCHAR(32) NOT NULL DEFAULT 'seasonal',
  status VARCHAR(16) NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  published_at TIMESTAMP NULL,
  UNIQUE KEY uq_dailybreath_content_day (publish_date, tradition, locale),
  KEY idx_dailybreath_content_status (status, publish_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
