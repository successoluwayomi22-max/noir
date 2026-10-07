-- Migration 034: AI Stylist Fashion Intelligence & Personalization

CREATE TABLE IF NOT EXISTS user_fashion_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    primary_style VARCHAR(50) NOT NULL DEFAULT 'Minimalist',
    secondary_style VARCHAR(50) NULL,
    preferred_colors JSON NULL,
    avoided_colors JSON NULL,
    preferred_categories JSON NULL,
    preferred_sizes JSON NULL,
    budget_min DECIMAL(14,2) NOT NULL DEFAULT 10000.00,
    budget_max DECIMAL(14,2) NOT NULL DEFAULT 150000.00,
    fit_preference VARCHAR(40) NOT NULL DEFAULT 'Tailored',
    gender_preference VARCHAR(40) NOT NULL DEFAULT 'All',
    occasion_frequency JSON NULL,
    interactions_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_stylist_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NULL,
    context_state JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(session_id),
    INDEX(user_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_stylist_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender ENUM('user', 'assistant') NOT NULL,
    content TEXT NOT NULL,
    structured_payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(conversation_id) REFERENCES ai_stylist_conversations(id) ON DELETE CASCADE,
    INDEX(conversation_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_stylist_feedback (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(100) NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    recommendation_type VARCHAR(50) NOT NULL DEFAULT 'outfit',
    action ENUM('like', 'dislike', 'save_outfit', 'not_my_style', 'too_expensive', 'wrong_color', 'wrong_fit', 'purchased', 'clicked') NOT NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id),
    INDEX(session_id),
    INDEX(action),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_saved_outfits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    occasion VARCHAR(80) NULL,
    total_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    product_ids JSON NOT NULL,
    stylist_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ai_stylist_config (
    key_name VARCHAR(80) PRIMARY KEY,
    value JSON NOT NULL,
    description VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO ai_stylist_config (key_name, value, description) VALUES
('weights', '{"occasion": 0.25, "preference": 0.20, "color_harmony": 0.15, "style_personality": 0.15, "budget": 0.15, "availability": 0.10}', 'Scoring weights for product & outfit ranking')
ON DUPLICATE KEY UPDATE value = VALUES(value);
