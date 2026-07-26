-- Beyond OS 2.3: Academy progress, assessments, credentials, and Beyond ID badges.
-- Apply after backing up the production database.

CREATE TABLE IF NOT EXISTS academy_course_progress (
    user_id BIGINT UNSIGNED NOT NULL,
    course_slug VARCHAR(120) NOT NULL,
    lesson_number SMALLINT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, course_slug, lesson_number),
    KEY idx_academy_progress_course (course_slug, completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academy_assessment_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_slug VARCHAR(120) NOT NULL,
    score SMALLINT UNSIGNED NOT NULL,
    question_count SMALLINT UNSIGNED NOT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_academy_attempt_user (user_id, course_slug, passed),
    KEY idx_academy_attempt_date (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academy_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    credential_id VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    course_slug VARCHAR(120) NOT NULL,
    learner_name VARCHAR(120) NOT NULL,
    score SMALLINT UNSIGNED NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    UNIQUE KEY uq_academy_user_course (user_id, course_slug),
    KEY idx_academy_verify (credential_id, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academy_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    badge_slug VARCHAR(120) NOT NULL,
    title VARCHAR(180) NOT NULL,
    credential_id VARCHAR(40) NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_academy_badge (user_id, badge_slug),
    KEY idx_academy_badge_user (user_id, awarded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
