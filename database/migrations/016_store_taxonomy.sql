CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE products
    ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER category,
    ADD COLUMN brand_id BIGINT UNSIGNED NULL AFTER brand,
    ADD CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_products_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE SET NULL;

INSERT INTO categories (name, slug)
SELECT DISTINCT category, LOWER(REPLACE(REPLACE(REPLACE(category, ' & ', '-'), ' ', '-'), '/', '-'))
FROM products WHERE category IS NOT NULL AND category<>''
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO brands (name, slug)
SELECT DISTINCT brand, LOWER(REPLACE(REPLACE(REPLACE(brand, ' & ', '-'), ' ', '-'), '/', '-'))
FROM products WHERE brand IS NOT NULL AND brand<>''
ON DUPLICATE KEY UPDATE name=VALUES(name);

UPDATE products p JOIN categories c ON c.name=p.category SET p.category_id=c.id WHERE p.category_id IS NULL;
UPDATE products p JOIN brands b ON b.name=p.brand SET p.brand_id=b.id WHERE p.brand_id IS NULL;
