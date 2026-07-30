CREATE TABLE IF NOT EXISTS beyond_cash_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    currency CHAR(3) NOT NULL,
    available_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    pending_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(32) NOT NULL DEFAULT 'pending_provider',
    provider VARCHAR(32) NULL,
    provider_account_reference VARCHAR(190) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_beyond_cash_user_currency (user_id, currency),
    CONSTRAINT fk_beyond_cash_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beyond_cash_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cash_account_id BIGINT UNSIGNED NOT NULL,
    direction VARCHAR(12) NOT NULL,
    transaction_type VARCHAR(32) NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'pending',
    provider VARCHAR(32) NULL,
    provider_reference VARCHAR(190) NULL,
    idempotency_key VARCHAR(190) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_beyond_cash_idempotency (idempotency_key),
    KEY idx_beyond_cash_activity (cash_account_id, created_at),
    CONSTRAINT fk_beyond_cash_account FOREIGN KEY (cash_account_id) REFERENCES beyond_cash_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
