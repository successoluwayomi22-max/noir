-- Migration 040: Global Multi-Currency & Geolocation FX
CREATE TABLE IF NOT EXISTS currency_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(60) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    rate_to_ngn DECIMAL(14, 6) NOT NULL, -- how many NGN is 1 unit of this currency
    symbol_position ENUM('before', 'after') DEFAULT 'before',
    decimal_places INT DEFAULT 2,
    is_active TINYINT(1) DEFAULT 1,
    auto_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_currency_mappings (
    country_code VARCHAR(5) PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL,
    currency_code VARCHAR(10) NOT NULL,
    INDEX (currency_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed currencies
INSERT IGNORE INTO currency_rates (code, name, symbol, rate_to_ngn, symbol_position, decimal_places, is_active) VALUES
('NGN', 'Nigerian Naira', '₦', 1.000000, 'before', 0, 1),
('USD', 'US Dollar', '$', 1580.000000, 'before', 2, 1),
('EUR', 'Euro', '€', 1710.000000, 'before', 2, 1),
('GBP', 'British Pound', '£', 2010.000000, 'before', 2, 1),
('AED', 'UAE Dirham', 'AED ', 430.000000, 'before', 2, 1),
('CAD', 'Canadian Dollar', 'CA$', 1150.000000, 'before', 2, 1);

-- Seed country mappings
INSERT IGNORE INTO country_currency_mappings (country_code, country_name, currency_code) VALUES
('NG', 'Nigeria', 'NGN'),
('US', 'United States', 'USD'),
('GB', 'United Kingdom', 'GBP'),
('AE', 'United Arab Emirates', 'AED'),
('DE', 'Germany', 'EUR'),
('FR', 'France', 'EUR'),
('IT', 'Italy', 'EUR'),
('CA', 'Canada', 'CAD');
