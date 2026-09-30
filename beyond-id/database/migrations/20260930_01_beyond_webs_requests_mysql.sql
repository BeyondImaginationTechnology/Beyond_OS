CREATE TABLE IF NOT EXISTS beyond_webs_requests (
  user_id INT NOT NULL PRIMARY KEY,
  request_id CHAR(32) NOT NULL UNIQUE,
  flavour VARCHAR(24) NOT NULL,
  plan VARCHAR(24) NOT NULL,
  status VARCHAR(24) NOT NULL DEFAULT 'requested',
  requested_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
