ALTER TABLE beyond_webs_requests
  ADD COLUMN provider_instance_name VARCHAR(63) NULL,
  ADD COLUMN provider_zone VARCHAR(63) NULL,
  ADD COLUMN provider_private_ip VARCHAR(45) NULL,
  ADD COLUMN session_runtime_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN hourly_rate_amount DECIMAL(12,4) NULL,
  ADD COLUMN hourly_rate_currency CHAR(3) NULL,
  ADD COLUMN usage_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN started_at DATETIME NULL,
  ADD COLUMN stopped_at DATETIME NULL;
