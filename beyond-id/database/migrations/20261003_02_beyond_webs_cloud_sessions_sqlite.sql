ALTER TABLE beyond_webs_requests ADD COLUMN provider_instance_name TEXT NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN provider_zone TEXT NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN provider_private_ip TEXT NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN session_runtime_seconds INTEGER NOT NULL DEFAULT 0;
ALTER TABLE beyond_webs_requests ADD COLUMN hourly_rate_amount NUMERIC NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN hourly_rate_currency TEXT NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN usage_seconds INTEGER NOT NULL DEFAULT 0;
ALTER TABLE beyond_webs_requests ADD COLUMN started_at TEXT NULL;
ALTER TABLE beyond_webs_requests ADD COLUMN stopped_at TEXT NULL;
