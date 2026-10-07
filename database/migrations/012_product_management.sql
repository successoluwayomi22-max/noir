ALTER TABLE products
    ADD COLUMN short_description TEXT NULL AFTER name,
    ADD COLUMN description LONGTEXT NULL AFTER short_description,
    ADD COLUMN sku VARCHAR(100) NULL AFTER slug,
    ADD COLUMN barcode VARCHAR(120) NULL AFTER sku,
    ADD COLUMN category VARCHAR(120) NULL AFTER barcode,
    ADD COLUMN subcategory VARCHAR(120) NULL AFTER category,
    ADD COLUMN brand VARCHAR(120) NULL AFTER subcategory,
    ADD COLUMN tags TEXT NULL AFTER brand,
    ADD COLUMN cost_price DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER price,
    ADD COLUMN compare_at_price DECIMAL(14,2) NULL AFTER cost_price,
    ADD COLUMN quantity INT NOT NULL DEFAULT 0 AFTER compare_at_price,
    ADD COLUMN low_stock_alert INT NOT NULL DEFAULT 5 AFTER quantity,
    ADD COLUMN track_inventory BOOLEAN NOT NULL DEFAULT TRUE AFTER low_stock_alert,
    ADD COLUMN allow_backorders BOOLEAN NOT NULL DEFAULT FALSE AFTER track_inventory,
    ADD COLUMN featured BOOLEAN NOT NULL DEFAULT FALSE AFTER allow_backorders,
    ADD COLUMN featured_image VARCHAR(255) NULL AFTER featured,
    ADD COLUMN weight DECIMAL(10,3) NULL,
    ADD COLUMN length DECIMAL(10,2) NULL,
    ADD COLUMN width DECIMAL(10,2) NULL,
    ADD COLUMN height DECIMAL(10,2) NULL,
    ADD COLUMN shipping_class VARCHAR(80) NULL,
    ADD COLUMN meta_title VARCHAR(255) NULL,
    ADD COLUMN meta_description TEXT NULL,
    ADD COLUMN keywords TEXT NULL,
    ADD COLUMN og_image VARCHAR(255) NULL;

CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX(product_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    sku VARCHAR(100) NULL,
    price DECIMAL(14,2) NULL,
    quantity INT NOT NULL DEFAULT 0,
    attributes JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX(product_id)
) ENGINE=InnoDB;
