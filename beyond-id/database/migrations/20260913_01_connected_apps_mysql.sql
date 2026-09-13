CREATE TABLE IF NOT EXISTS connected_apps (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  app_slug VARCHAR(80) NOT NULL,
  permissions_json LONGTEXT DEFAULT NULL,
  connected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME DEFAULT NULL,
  revoked_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_connected_app (user_id, app_slug),
  KEY idx_connected_apps_user (user_id, revoked_at),
  CONSTRAINT fk_connected_apps_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
