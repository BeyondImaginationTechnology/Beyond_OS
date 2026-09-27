CREATE TABLE IF NOT EXISTS jaguar_monthly_usage (
  identity_key CHAR(72) NOT NULL,
  period CHAR(7) NOT NULL,
  request_count INT UNSIGNED NOT NULL DEFAULT 0,
  input_tokens BIGINT UNSIGNED NOT NULL DEFAULT 0,
  output_tokens BIGINT UNSIGNED NOT NULL DEFAULT 0,
  modal_bit_micro_estimate BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (identity_key, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_jaguar_monthly_usage_period ON jaguar_monthly_usage(period);
