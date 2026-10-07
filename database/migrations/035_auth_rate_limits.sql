-- Migration 035: Authentication Rate Limiting & Brute-force Prevention

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_hash VARCHAR(64) NOT NULL,
    action VARCHAR(32) NOT NULL,
    identifier VARCHAR(190) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    locked_until DATETIME NULL,
    last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(key_hash),
    INDEX(action),
    INDEX(identifier),
    INDEX(locked_until)
) ENGINE=InnoDB;
