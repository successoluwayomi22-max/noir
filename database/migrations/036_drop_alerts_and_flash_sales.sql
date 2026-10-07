-- Migration 036: Drop Alerts & Flash Sales System

CREATE TABLE IF NOT EXISTS drop_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NULL,
    image_url VARCHAR(500) NULL,
    type ENUM('vip_drop', 'flash_sale', 'limited_edition', 'exclusive_capsule') NOT NULL DEFAULT 'vip_drop',
    discount_percentage INT UNSIGNED NULL DEFAULT 0,
    product_ids JSON NULL,
    min_vip_tier ENUM('standard', 'silver', 'gold', 'noir_black') NOT NULL DEFAULT 'standard',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    stock_limit INT UNSIGNED NULL DEFAULT NULL,
    status ENUM('upcoming', 'live', 'ended') NOT NULL DEFAULT 'upcoming',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(status, starts_at),
    INDEX(type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drop_alert_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    drop_alert_id BIGINT UNSIGNED NOT NULL,
    notified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_drop(user_id, drop_alert_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(drop_alert_id) REFERENCES drop_alerts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Initial Luxury Drop Alerts and Flash Sales
INSERT INTO drop_alerts (title, subtitle, description, image_url, type, discount_percentage, min_vip_tier, starts_at, ends_at, stock_limit, status)
VALUES 
(
    'NOIR x Milan Runway FW26 Capsule',
    'Ultra-Exclusive 50-Piece Worldwide Drop',
    'Hand-stitched Italian cashmere outerwear and structured obsidian blazers. Each garment is individually numbered and certified on the blockchain.',
    'assets/images/drops/milan_runway_capsule.jpg',
    'vip_drop',
    0,
    'silver',
    DATE_ADD(NOW(), INTERVAL 4 HOUR),
    DATE_ADD(NOW(), INTERVAL 28 HOUR),
    50,
    'upcoming'
),
(
    'Midnight Flash Sale: 40% OFF Silk & Velour',
    'Flash 6-Hour Exclusive Window',
    'Exclusive 40% luxury markdown on selected Noir Atelier silk evening shirts, velvet smoking jackets, and tailored trousers.',
    'assets/images/drops/midnight_flash_sale.jpg',
    'flash_sale',
    40,
    'standard',
    DATE_SUB(NOW(), INTERVAL 1 HOUR),
    DATE_ADD(NOW(), INTERVAL 5 HOUR),
    120,
    'live'
),
(
    '24K Gold Accent Leather Accessories',
    'Limited Edition High-Jewellery Hardware',
    'Full-grain calfskin clutches and cardholders finished with authentic 24-karat gold dipped hardware.',
    'assets/images/drops/gold_accessories.jpg',
    'limited_edition',
    15,
    'gold',
    DATE_ADD(NOW(), INTERVAL 18 HOUR),
    DATE_ADD(NOW(), INTERVAL 42 HOUR),
    30,
    'upcoming'
)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
