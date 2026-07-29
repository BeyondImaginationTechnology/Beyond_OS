ALTER TABLE visitor_traffic
    ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER country_code,
    ADD COLUMN viewport_width INT UNSIGNED DEFAULT NULL AFTER ip_address,
    ADD COLUMN client_language VARCHAR(32) DEFAULT NULL AFTER viewport_width,
    ADD COLUMN client_timezone VARCHAR(80) DEFAULT NULL AFTER client_language,
    ADD COLUMN last_seen_at DATETIME DEFAULT NULL AFTER client_timezone;

UPDATE visitor_traffic
SET last_seen_at = occurred_at
WHERE last_seen_at IS NULL;

CREATE INDEX idx_visitor_traffic_last_seen
ON visitor_traffic(last_seen_at);
