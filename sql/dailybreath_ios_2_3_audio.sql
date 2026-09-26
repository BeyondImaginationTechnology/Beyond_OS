CREATE TABLE IF NOT EXISTS dailybreath_daily_audio (
    publish_date DATE NOT NULL,
    tradition VARCHAR(16) NOT NULL,
    locale VARCHAR(5) NOT NULL,
    script_hash CHAR(64) NOT NULL,
    audio_url VARCHAR(512) NOT NULL,
    voice_id VARCHAR(120) NOT NULL,
    provider VARCHAR(24) NOT NULL DEFAULT 'elevenlabs',
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publish_date, tradition, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
