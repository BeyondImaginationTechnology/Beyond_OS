CREATE TABLE IF NOT EXISTS crypto_watch_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    network VARCHAR(12) NOT NULL,
    label VARCHAR(80) NOT NULL DEFAULT '',
    public_address VARCHAR(128) NOT NULL,
    address_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_crypto_watch_user_address (user_id, network, address_hash),
    KEY idx_crypto_watch_user (user_id, created_at),
    CONSTRAINT fk_crypto_watch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
