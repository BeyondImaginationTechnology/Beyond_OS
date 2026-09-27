ALTER TABLE jaguar_monthly_usage
  ADD COLUMN reserved_request_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER request_count;
