ALTER TABLE beyond_webs_requests
  ADD COLUMN work_mode VARCHAR(32) NOT NULL DEFAULT 'developer' AFTER plan;
