-- Migration 038: Digital VIP Luxury Gift Cards & Vouchers (Enhancement)
ALTER TABLE gift_cards 
    ADD COLUMN IF NOT EXISTS recipient_name VARCHAR(160) DEFAULT NULL AFTER recipient_email,
    ADD COLUMN IF NOT EXISTS theme VARCHAR(50) DEFAULT 'gold_foil' AFTER currency,
    ADD COLUMN IF NOT EXISTS purchaser_id INT DEFAULT NULL AFTER theme;

CREATE TABLE IF NOT EXISTS gift_card_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gift_card_id BIGINT UNSIGNED NOT NULL,
    order_id INT DEFAULT NULL,
    order_number VARCHAR(60) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    amount_redeemed DECIMAL(14, 2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (gift_card_id),
    INDEX (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a default demo luxury gift card if not present
INSERT IGNORE INTO gift_cards (code, initial_amount, balance, currency, theme, sender_name, recipient_name, recipient_email, message, status, expires_at, created_at, updated_at) VALUES
('NOIR-VIP-8800', 50000.00, 50000.00, 'NGN', 'gold_foil', 'NOIR Atelier Executive', 'VIP Guest', 'guest@noiratelier.com', 'A complimentary gesture of bespoke distinction. Welcome to NOIR & Co.', 'active', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW(), NOW());
