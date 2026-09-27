CREATE TABLE IF NOT EXISTS jaguar_monthly_usage (
  identity_key TEXT NOT NULL,
  period TEXT NOT NULL,
  request_count INTEGER NOT NULL DEFAULT 0,
  input_tokens INTEGER NOT NULL DEFAULT 0,
  output_tokens INTEGER NOT NULL DEFAULT 0,
  modal_bit_micro_estimate INTEGER NOT NULL DEFAULT 0,
  updated_at INTEGER NOT NULL,
  PRIMARY KEY (identity_key, period)
);
CREATE INDEX IF NOT EXISTS idx_jaguar_monthly_usage_period ON jaguar_monthly_usage(period);
