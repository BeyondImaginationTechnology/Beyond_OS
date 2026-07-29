ALTER TABLE visitor_traffic ADD COLUMN ip_address TEXT;
ALTER TABLE visitor_traffic ADD COLUMN viewport_width INTEGER;
ALTER TABLE visitor_traffic ADD COLUMN client_language TEXT;
ALTER TABLE visitor_traffic ADD COLUMN client_timezone TEXT;
ALTER TABLE visitor_traffic ADD COLUMN last_seen_at TEXT;

UPDATE visitor_traffic
SET last_seen_at = occurred_at
WHERE last_seen_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_visitor_traffic_last_seen
ON visitor_traffic(last_seen_at);
