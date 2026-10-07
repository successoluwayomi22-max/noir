CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    discount_type ENUM('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
    minimum_order_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    expires_at DATETIME NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(status, starts_at, expires_at)
) ENGINE=InnoDB;

INSERT INTO coupons (code, description, discount_type, discount_value, minimum_order_amount, expires_at)
VALUES
    ('WELCOME15', '15% off your next order', 'percent', 15, 0, '2026-08-31 23:59:59'),
    ('NOIRSHIP', 'Free delivery on orders over ₦100,000', 'free_shipping', 0, 100000, NULL);
