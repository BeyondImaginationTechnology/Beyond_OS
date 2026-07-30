CREATE TABLE IF NOT EXISTS card_program_customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    issuer VARCHAR(32) NOT NULL DEFAULT 'peoples_trust',
    processor VARCHAR(32) NOT NULL DEFAULT 'marqeta',
    provider_user_token VARCHAR(36) NOT NULL,
    provider_status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    consent_version VARCHAR(32) NOT NULL,
    consented_at TIMESTAMP NOT NULL,
    last_synced_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_card_program_user (user_id),
    UNIQUE KEY uq_card_program_provider_user (provider_user_token),
    CONSTRAINT fk_card_program_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    card_program_customer_id BIGINT UNSIGNED NOT NULL,
    processor VARCHAR(32) NOT NULL DEFAULT 'marqeta',
    provider_card_token VARCHAR(36) NOT NULL,
    card_product_token VARCHAR(36) NOT NULL,
    state VARCHAR(32) NOT NULL DEFAULT 'UNACTIVATED',
    fulfillment_status VARCHAR(32) NULL,
    last_four CHAR(4) NULL,
    expiration_time VARCHAR(40) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'CAD',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payment_card_provider_token (provider_card_token),
    KEY idx_payment_cards_user (user_id, created_at),
    CONSTRAINT fk_payment_card_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_card_customer FOREIGN KEY (card_program_customer_id) REFERENCES card_program_customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS card_provider_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    processor VARCHAR(32) NOT NULL,
    event_key VARCHAR(190) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    payload_json JSON NOT NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_card_provider_event (processor, event_key),
    KEY idx_card_provider_events_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
