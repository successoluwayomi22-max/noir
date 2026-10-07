CREATE TABLE IF NOT EXISTS broadcast_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    type VARCHAR(60) NOT NULL DEFAULT 'announcement',
    target_audience VARCHAR(60) NOT NULL DEFAULT 'all_customers',
    channels VARCHAR(60) NOT NULL DEFAULT 'both',
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(created_at)
) ENGINE=InnoDB;
